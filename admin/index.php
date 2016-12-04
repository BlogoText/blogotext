<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BoboTiG/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***

require_once 'inc/boot.php';


// Open bases
$GLOBALS['db_handle'] = open_base();


// transforme les valeurs numériques d’un tableau pour les ramener la valeur max du tableau à $maximum. Les autres valeurs du tableau sont à l’échelle
function scaled_size($tableau, $maximum)
{
    $return = array();
    if (!$tableau) {
        return $return;
    }

    $ratio = max(array_values($tableau))/$maximum;

    foreach ($tableau as $key => $value) {
        if ($ratio != 0) {
            $return[] = array('nb'=> $value, 'nb_scale' => floor($value/$ratio), 'date' => $key);
        } else {
            $return[] = array('nb'=> $value, 'nb_scale' => 0, 'date' => $key);
        }
    }

    return $return;
}

/**
 * compte le nombre d’éléments dans la base, pour chaque mois les 12 derniers mois.
 * retourne un tableau YYYYMM => nb;
 */
function get_tableau_date($data_type)
{
    $table_months = array();
    // for ($i = 96; $i >= 0; $i--) {
        // $table_months[date('Ym', mktime(0, 0, 0, date("m")-$i, 1, date("Y")))] = 0;
    // }

    $show_max = 36; // (int) older to show (in month)
    $show_min = 12; // (int) min month to show

    // met tout ça au format YYYYMMDDHHIISS où DDHHMMSS vaut 00000000 (pour correspondre au format de l’ID de BT qui est \d{14}
    $min = date('Ym', mktime(0, 0, 0, date("m")-$show_max, 1, date("Y"))).'00000000';
    $max = date('Ym').date('dHis');

    $bt_date = ($data_type == 'articles') ? 'bt_date' : 'bt_id';

    $query = '
        SELECT substr('.$bt_date.', 1, 6) AS date, count(*) AS idbydate
          FROM '.$data_type.'
         WHERE '.$bt_date.' BETWEEN '.$min.' AND '.$max.'
         GROUP BY date
         ORDER BY date';

    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute();
        $tab = $req-> fetchAll(PDO::FETCH_ASSOC);
        foreach ($tab as $i => $month) {
            // if (isset($table_months[$month['date']])) {
                $table_months[$month['date']] = $month['idbydate'];
            // }
        }
    } catch (Exception $e) {
        die('Erreur 86459: '.$e->getMessage());
    }

    if (!$table_months) {
        return $table_months;
    }

    $start_at = min(array_keys($table_months));
    // is first month younger than $show_min months
    if ($start_at > date('Ym', mktime(0, 0, 0, date("m")-$show_min, 1, date("Y")))) {
        for ($i = $show_min; $i >= 0; $i--) {
            $month = date('Ym', mktime(0, 0, 0, date("m")-$i, 1, date("Y")));
            if (!isset($table_months[$month])) {
                $table_months[$month] = 0;
            }
        }
    } else {
        // start for 1 first month
        // dirty
        $d1 = new DateTime(implode('-', str_split($start_at, 4)));
        $d2 = new DateTime(date('Y-m'));
        $i = ($d1->diff($d2)->m) + ($d1->diff($d2)->y*12) + 1;
        while (--$i) {
            $month = date('Ym', mktime(0, 0, 0, date("m")-$i, 1, date("Y")));
            if (!isset($table_months[$month])) {
                $table_months[$month] = 0;
            }
        }
    }

    // order
    ksort($table_months);

    return $table_months;
}

// process
if (!empty($_GET['q'])) {
    $GLOBALS['liste_fichiers'] = open_serialzd_file(FILES_DB);
    $total_nb_fichiers = sizeof($GLOBALS['liste_fichiers']);

    $q = htmlspecialchars($_GET['q']);
    $nb_articles = liste_elements_count('SELECT count(ID) AS nbr FROM articles WHERE ( bt_content || bt_title ) LIKE ?', array('%'.$q.'%'));
    $nb_liens = liste_elements_count('SELECT count(ID) AS nbr FROM links WHERE ( bt_content || bt_title || bt_link ) LIKE ?', array('%'.$q.'%'));
    $nb_commentaires = liste_elements_count('SELECT count(ID) AS nbr FROM commentaires WHERE bt_content LIKE ?', array('%'.$q.'%'));
    $nb_feeds = liste_elements_count('SELECT count(ID) AS nbr FROM rss WHERE ( bt_content || bt_title ) LIKE ?', array('%'.$q.'%'));
    $nb_files = sizeof(liste_base_files('recherche', urldecode($_GET['q']), ''));
} else {
    $total_artic = liste_elements_count('SELECT count(ID) AS nbr FROM articles', array());
    $total_links = liste_elements_count('SELECT count(ID) AS nbr FROM links', array());
    $total_comms = liste_elements_count('SELECT count(ID) AS nbr FROM commentaires', array());
    // useless ?
    // $total_rss = liste_elements_count('SELECT count(ID) AS nbr FROM rss', array());

    $table_article = scaled_size(get_tableau_date('articles'), 150);
    $table_article = array_reverse($table_article);
    $table_links = scaled_size(get_tableau_date('links'), 150);
    $table_links = array_reverse($table_links);
    $table_comms = scaled_size(get_tableau_date('commentaires'), 150);
    $table_comms = array_reverse($table_comms);
}


echo tpl_get_html_head($GLOBALS['lang']['label_resume']);

echo '<div id="header">'."\n";
echo '<div id="top">'."\n";
tpl_show_msg();
echo moteur_recherche();
tpl_show_topnav($GLOBALS['lang']['label_resume']);
echo '</div>'."\n";
echo '</div>'."\n";

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";
echo '<div id="graphs">'."\n";


// show search results
if (!empty($_GET['q'])) {
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
// Main Dashboard
} else {
    $nothingyet = 0;

    if ($total_artic > 0) {
        echo '<div class="graph">'."\n";
        // print sur chaque div pour les articles.
        echo '<div class="form-legend">'.ucfirst($GLOBALS['lang']['label_articles']).'</div>'."\n";
        echo '<div class="graph-container" id="graph-container-article">'."\n";
            echo '<canvas height="150" width="400"></canvas>'."\n";
            echo '<div class="graphique" id="articles">'."\n";
                echo '<div class="month"><div class="month-bar" style="height:151px;margin-top:20px;"></div></div>';
        foreach ($table_article as $i => $data) {
            echo '<div class="month"><div class="month-bar" style="height:'.$data['nb_scale'].'px;margin-top:'.max(3-$data['nb_scale'], 0).'px"></div><span class="month-nb">'.$data['nb'].'</span><a href="articles.php?filtre='.$data['date'].'"><span class="month-name">'.mb_substr(mois_en_lettres(substr($data['date'], 4, 2)), 0, 3)."\n".substr($data['date'], 2, 2).'</span></a></div>';
        }
            echo '</div>'."\n";
        echo '</div>'."\n";
        echo '</div>'."\n";
    } else {
        $nothingyet++;
    }

    if ($total_comms > 0) {
        echo '<div class="graph">'."\n";
        // print sur chaque div pour les com.
        echo '<div class="form-legend">'.ucfirst($GLOBALS['lang']['label_commentaires']).'</div>'."\n";
        echo '<div class="graph-container" id="graph-container-commentaires">'."\n";
            echo '<canvas height="150" width="400"></canvas>'."\n";
            echo '<div class="graphique" id="commentaires">'."\n";
                echo '<div class="month"><div class="month-bar" style="height:151px;margin-top:20px;"></div></div>';
        foreach ($table_comms as $i => $data) {
            echo '<div class="month"><div class="month-bar" style="height:'.$data['nb_scale'].'px;margin-top:'.max(3-$data['nb_scale'], 0).'px"></div><span class="month-nb">'.$data['nb'].'</span><a href="commentaires.php?filtre='.$data['date'].'"><span class="month-name">'.mb_substr(mois_en_lettres(substr($data['date'], 4, 2)), 0, 3)."\n".substr($data['date'], 2, 2).'</span></a></div>';
        }
            echo '</div>'."\n";
        echo '</div>'."\n";
        echo '</div>'."\n";
    } else {
        $nothingyet++;
    }

    if ($total_links > 0) {
        echo '<div class="graph">'."\n";
        // print sur chaque div pour les liens.
        echo '<div class="form-legend">'.ucfirst($GLOBALS['lang']['label_links']).'</div>'."\n";
        echo '<div class="graph-container" id="graph-container-links">'."\n";
            echo '<canvas height="150" width="400"></canvas>'."\n";
            echo '<div class="graphique" id="links">'."\n";
                echo '<div class="month"><div class="month-bar" style="height:151px;margin-top:20px;"></div></div>';
        foreach ($table_links as $i => $data) {
            echo '<div class="month"><div class="month-bar" style="height:'.$data['nb_scale'].'px; margin-top:'.max(3-$data['nb_scale'], 0).'px"></div><span class="month-nb">'.$data['nb'].'</span><a href="links.php?filtre='.$data['date'].'"><span class="month-name">'.mb_substr(mois_en_lettres(substr($data['date'], 4, 2)), 0, 3)."\n".substr($data['date'], 2, 2).'</span></a></div>';
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
echo "\n".'<script src="style/javascript.js"></script>'."\n";
echo "\n".'<script>'."\n";
echo '\'use strict\''."\n";
echo 'var canvas = document.querySelectorAll(".graph-container canvas");'."\n";
echo 'var containers = document.querySelectorAll(".graph-container");'."\n";
echo 'var graphiques = document.querySelectorAll(".graph-container .graphique");'."\n";
echo 'window.addEventListener("resize", respondCanvas );'."\n";
// echo 'respondCanvas();'."\n";
echo "\n".'</script>'."\n";


?>
<script>
    /**
     * [poc] try to set the width properly
     *       if it's ok :
     *          - set the with threw css (BoboTiG: partily done for month numbers)
     */
    for (var i = 0, clen = containers.length; i < clen; i++) {
        var months = containers[i].querySelectorAll('.month');
        for (var j = 0, t = months.length; j < t; j++) {
            months[j].style.width = (100 / (t)) + '%';
        }
    }
    respondCanvas();
    // end of [poc]
</script>
<?php

echo tpl_get_footer($begin);
