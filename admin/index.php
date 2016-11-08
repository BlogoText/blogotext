<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

if (!file_exists('../config/user.ini') || !file_exists('../config/prefs.php')) {
    header('Location: install.php');
    exit;
}

$begin = microtime(true);
define('BT_ROOT', '../');

require_once '../inc/inc.php';

operate_session();

// open bases
$GLOBALS['db_handle'] = open_base();
$GLOBALS['liste_fichiers'] = open_serialzd_file(FILES_DB);

// migration 2.1.0.0 => 2.1.0.1 FIXME : remove later
if (!isset($GLOBALS['liste_fichiers'][0]['bt_path'])) {
    foreach ($GLOBALS['liste_fichiers'] as $i => $file) {
        $GLOBALS['liste_fichiers'][$i]['bt_path'] = '';
    }
    file_put_contents(FILES_DB, '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers']))).' */');
}

afficher_html_head($GLOBALS['lang']['label_resume']);

echo '<div id="header">'."\n";
echo '<div id="top">'."\n";
afficher_msg();
echo moteur_recherche();
afficher_topnav($GLOBALS['lang']['label_resume']);
echo '</div>'."\n";
echo '</div>'."\n";
$total_artic = liste_elements_count("SELECT count(ID) AS nbr FROM articles", array());
$total_links = liste_elements_count("SELECT count(ID) AS nbr FROM links", array());
$total_comms = liste_elements_count("SELECT count(ID) AS nbr FROM commentaires", array());
$total_rss = liste_elements_count("SELECT count(ID) AS nbr FROM rss", array());

$total_nb_fichiers = sizeof($GLOBALS['liste_fichiers']);


echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";
echo '<div id="graphs">'."\n";


// transforme les valeurs numériques d’un tableau pour les ramener la valeur max du tableau à $maximum. Les autres valeurs du tableau sont à l’échelle
function scaled_size($tableau, $maximum)
{
    $ratio = max(array_values($tableau))/$maximum;

    $return = array();
    foreach ($tableau as $key => $value) {
        if ($ratio != 0) {
            $return[] = array('nb'=> $value , 'nb_scale' => floor($value/$ratio), 'date' => $key);
        } else {
            $return[] = array('nb'=> $value , 'nb_scale' => 0, 'date' => $key);
        }
    }
    return $return;
}

// compte le nombre d’éléments dans la base, pour chaque mois les 12 derniers mois.
/*
 * retourne un tableau YYYYMM => nb;
 *
*
*/
function get_tableau_date($data_type)
{
    $table_months = array();
    for ($i = 96; $i >= 0; $i--) {
        $table_months[date('Ym', mktime(0, 0, 0, date("m")-$i, 1, date("Y")))] = 0;
    }

    // met tout ça au format YYYYMMDDHHIISS où DDHHMMSS vaut 00000000 (pour correspondre au format de l’ID de BT qui est \d{14}
    $max = max(array_keys($table_months)).date('dHis');
    $min = min(array_keys($table_months)).'00000000';
    $bt_date = ($data_type == 'articles') ? 'bt_date' : 'bt_id';

    $query = "SELECT substr($bt_date, 1, 6) AS date, count(*) AS idbydate FROM $data_type WHERE $bt_date BETWEEN $min AND $max GROUP BY date ORDER BY date";

    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute();
        $tab = $req-> fetchAll(PDO::FETCH_ASSOC);
        foreach ($tab as $i => $month) {
            if (isset($table_months[$month['date']])) {
                $table_months[$month['date']] = $month['idbydate'];
            }
        }
    } catch (Exception $e) {
        die('Erreur 86459: '.$e->getMessage());
    }
    return $table_months;
}

/* Une recherche a été faite : affiche la recherche */
if (!empty($_GET['q'])) {
    $q = htmlspecialchars($_GET['q']);
    $nb_articles = liste_elements_count("SELECT count(ID) AS nbr FROM articles WHERE ( bt_content || bt_title ) LIKE ?", array('%'.$q.'%'));
    $nb_liens = liste_elements_count("SELECT count(ID) AS nbr FROM links WHERE ( bt_content || bt_title || bt_link ) LIKE ?", array('%'.$q.'%'));
    $nb_commentaires = liste_elements_count("SELECT count(ID) AS nbr FROM commentaires WHERE bt_content LIKE ?", array('%'.$q.'%'));
    $nb_feeds = liste_elements_count("SELECT count(ID) AS nbr FROM rss WHERE ( bt_content || bt_title ) LIKE ?", array('%'.$q.'%'));
    $nb_files = sizeof(liste_base_files('recherche', urldecode($_GET['q']), ''));

    echo '<div class="graph">'."\n";
    echo '<div class="form-legend">'.$GLOBALS['lang']['recherche'].'  <span style="font-style: italic">'.htmlspecialchars($_GET['q']).'</span></div>'."\n";
    echo '<ul id="resultat-recherche">';
    echo "\t".'<li><a href="articles.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_objets($nb_articles, 'article').'</a></li>';
    echo "\t".'<li><a href="links.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_objets($nb_liens, 'link').'</a></li>';
    echo "\t".'<li><a href="commentaires.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_objets($nb_commentaires, 'commentaire').'</a></li>';
    echo "\t".'<li><a href="fichiers.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_objets($nb_files, 'fichier').'</a></li>';
    echo "\t".'<li><a href="feed.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_objets($nb_feeds, 'feed_entry').'</a></li>';
    echo '</ul>';
    echo '</div>'."\n";
} /* sinon, affiche les graphes. */

else {
    $nothingyet = 0;

    if (!$total_artic == 0) {
        echo '<div class="graph">'."\n";
        // print sur chaque div pour les articles.
        echo '<div class="form-legend">'.ucfirst($GLOBALS['lang']['label_articles']).'</div>'."\n";
        echo '<div class="graph-container" id="graph-container-article">'."\n";
            echo '<canvas height="150" width="400"></canvas>'."\n";
            echo '<div class="graphique" id="articles">'."\n";
                $table = scaled_size(get_tableau_date('articles'), 150);
                $table = array_reverse($table);
                echo '<div class="month"><div class="month-bar" style="height: 151px; margin-top:20px;"></div></div>';
        foreach ($table as $i => $data) {
            echo '<div class="month"><div class="month-bar" style="height: '.$data['nb_scale'].'px; margin-top:'.max(3-$data['nb_scale'], 0).'px"></div><span class="month-nb">'.$data['nb'].'</span><a href="articles.php?filtre='.$data['date'].'"><span class="month-name">'.mb_substr(mois_en_lettres(substr($data['date'], 4, 2)), 0, 3)."\n".substr($data['date'], 2, 2).'</span></a></div>';
        }
            echo '</div>'."\n";
        echo '</div>'."\n";
        echo '</div>'."\n";
    } else {
        $nothingyet++;
    }

    if (!$total_comms == 0) {
        echo '<div class="graph">'."\n";
        // print sur chaque div pour les com.
        echo '<div class="form-legend">'.ucfirst($GLOBALS['lang']['label_commentaires']).'</div>'."\n";
        echo '<div class="graph-container" id="graph-container-commentaires">'."\n";
            echo '<canvas height="150" width="400"></canvas>'."\n";
            echo '<div class="graphique" id="commentaires">'."\n";
                $table = scaled_size(get_tableau_date('commentaires'), 150);
                $table = array_reverse($table);
                echo '<div class="month"><div class="month-bar" style="height: 151px; margin-top:20px;"></div></div>';
        foreach ($table as $i => $data) {
            echo '<div class="month"><div class="month-bar" style="height: '.$data['nb_scale'].'px; margin-top:'.max(3-$data['nb_scale'], 0).'px"></div><span class="month-nb">'.$data['nb'].'</span><a href="commentaires.php?filtre='.$data['date'].'"><span class="month-name">'.mb_substr(mois_en_lettres(substr($data['date'], 4, 2)), 0, 3)."\n".substr($data['date'], 2, 2).'</span></a></div>';
        }
            echo '</div>'."\n";
        echo '</div>'."\n";
        echo '</div>'."\n";
    } else {
        $nothingyet++;
    }

    if (!$total_links == 0) {
        echo '<div class="graph">'."\n";
        // print sur chaque div pour les liens.
        echo '<div class="form-legend">'.ucfirst($GLOBALS['lang']['label_links']).'</div>'."\n";
        echo '<div class="graph-container" id="graph-container-links">'."\n";
            echo '<canvas height="150" width="400"></canvas>'."\n";
            echo '<div class="graphique" id="links">'."\n";
                $table = scaled_size(get_tableau_date('links'), 150);
                $table = array_reverse($table);
                echo '<div class="month"><div class="month-bar" style="height: 151px; margin-top:20px;"></div></div>';
        foreach ($table as $i => $data) {
            echo '<div class="month"><div class="month-bar" style="height: '.$data['nb_scale'].'px; margin-top:'.max(3-$data['nb_scale'], 0).'px"></div><span class="month-nb">'.$data['nb'].'</span><a href="links.php?filtre='.$data['date'].'"><span class="month-name">'.mb_substr(mois_en_lettres(substr($data['date'], 4, 2)), 0, 3)."\n".substr($data['date'], 2, 2).'</span></a></div>';
        }
            echo '</div>'."\n";
        echo '</div>'."\n";
        echo '</div>'."\n";
    } else {
        $nothingyet++;
    }

    if ($nothingyet == 3) {
        echo info($GLOBALS['lang']['note_no_article']);
    }
}

echo '</div>'."\n";
echo "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
echo "\n".'<script type="text/javascript">'."\n";
echo '\'use strict\''."\n";
echo 'var canvas = document.querySelectorAll(".graph-container canvas");'."\n";
echo 'var containers = document.querySelectorAll(".graph-container");'."\n";
echo 'var graphiques = document.querySelectorAll(".graph-container .graphique");'."\n";
echo 'window.addEventListener("resize", respondCanvas );'."\n";
echo 'respondCanvas();'."\n";
echo "\n".'</script>'."\n";

footer($begin);
