<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BlogoText/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***

require_once 'inc/boot.php';


/**
 * Scale numeric values based on $maximum.
 */
function scaled_size($arr, $maximum)
{
    $return = array();
    if (!$arr) {
        return $return;
    }

    $ratio = max(array_values($arr)) / $maximum;
    if ($ratio <= 0) {
        $ratio = 1;
    }
    foreach ($arr as $key => $value) {
        $return[] = array('nb' => $value, 'nb_scale' => floor($value / $ratio), 'date' => $key);
    }

    return $return;
}

/**
 * Count the number of items into the DTB for the Nth last months.
 * Return an associated array: YYYYMM => number
 */
function get_tableau_date($dataType)
{
    $showMin = 12;  // (int) minimal number of months to show
    $showMax = 36;  // (int) maximal number of months to show
    $tableMonths = array();

    // Uniformize date format. YYYYMMDDHHIISS where DDHHMMSS is 00000000 (to match with the ID format which is \d{14})
    $min = date('Ym', mktime(0, 0, 0, date('m') - $showMax, 1, date('Y'))).'01000000';
    $max = date('Ymd').'235959';

    $btDate = ($dataType == 'articles') ? 'bt_date' : 'bt_id';

    $sql = '
        SELECT substr('.$btDate.', 1, 6) AS date, count(*) AS idbydate
          FROM '.$dataType.'
         WHERE '.$btDate.' BETWEEN '.$min.' AND '.$max.'
         GROUP BY date
         ORDER BY date';

    $req = $GLOBALS['db_handle']->prepare($sql);
    $req->execute();
    $tab = $req->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tab as $i => $month) {
        $tableMonths[$month['date']] = $month['idbydate'];
    }

    // Fill empty months
    for ($i = $showMin; $i >= 0; $i--) {
        $month = date('Ym', mktime(0, 0, 0, date('m') - $i, 1, date('Y')));
        if (!isset($tableMonths[$month])) {
            $tableMonths[$month] = 0;
        }
    }

    // order
    ksort($tableMonths);

    return $tableMonths;
}

/**
 * Display one graphic.
 */
function display_graph($arr, $title, $cls)
{
    $txt = '<div class="graph">';
    $txt .= '<div class="form-legend">'.ucfirst($title).'</div>';
    $txt .= '<div class="graph-container" id="graph-container-'.$cls.'">';
    $txt .= '<canvas height="150" width="400"></canvas>';
    $txt .= '<div class="graphique" id="'.$cls.'">';
    $txt .= '<div class="month"><div class="month-bar"></div></div>';
    foreach ($arr as $data) {
        $txt .= '<div class="month"><div class="month-bar" style="height:'.$data['nb_scale'].'px;margin-top:'.max(3 - $data['nb_scale'], 0).'px"></div>';
        $txt .= '<span class="month-nb">'.$data['nb'].'</span><a href="articles.php?filtre='.$data['date'].'"><span class="month-name">'.mb_substr(mois_en_lettres(substr($data['date'], 4, 2)), 0, 3).'<br>'.substr($data['date'], 2, 2).'</span></a></div>';
    }
    $txt .= '</div>';
    $txt .= '</div>';
    $txt .= '</div>';

    echo $txt;
}


/**
 * Process
 */

$query = (string)filter_input(INPUT_GET, 'q');
if ($query) {
    $query = htmlspecialchars($query);
    $numberOfPosts = liste_elements_count('SELECT count(ID) AS nbr FROM articles WHERE ( bt_content || bt_title ) LIKE ?', array('%'.$query.'%'));
    $numberOfLinks = liste_elements_count('SELECT count(ID) AS nbr FROM links WHERE ( bt_content || bt_title || bt_link ) LIKE ?', array('%'.$query.'%'));
    $numberOfComments = liste_elements_count('SELECT count(ID) AS nbr FROM commentaires WHERE bt_content LIKE ?', array('%'.$query.'%'));
    $numberOfFeeds = liste_elements_count('SELECT count(ID) AS nbr FROM rss WHERE ( bt_content || bt_title ) LIKE ?', array('%'.$query.'%'));
    $numberOfFiles = sizeof(liste_base_files('recherche', urldecode($query), ''));
} else {
    $numberOfPosts = liste_elements_count('SELECT count(ID) AS nbr FROM articles', array());
    $numberOfLinks = liste_elements_count('SELECT count(ID) AS nbr FROM links', array());
    $numberOfComments = liste_elements_count('SELECT count(ID) AS nbr FROM commentaires', array());

    $posts = scaled_size(get_tableau_date('articles'), 150);
    $posts = array_reverse($posts);
    $links = scaled_size(get_tableau_date('links'), 150);
    $links = array_reverse($links);
    $comments = scaled_size(get_tableau_date('commentaires'), 150);
    $comments = array_reverse($comments);
}


/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['label_resume']);

echo '<div id="header">';
    echo '<div id="top">';
        tpl_show_msg();
        echo moteur_recherche();
        echo tpl_show_topnav($GLOBALS['lang']['label_resume']);
    echo '</div>';
echo '</div>';

echo '<div id="axe">';
echo '<div id="page">';
echo '<div id="graphs">';

if ($query) {
    // Show search results
    echo '<div class="graph">';
    echo '<div class="form-legend">'.$GLOBALS['lang']['recherche'].'  <span style="font-style: italic">'.$query.'</span></div>';
    echo '<ul id="resultat-recherche">';
        echo '<li><a href="articles.php?q='.$query.'">'.nombre_objets($numberOfPosts, 'article').'</a></li>';
        echo '<li><a href="links.php?q='.$query.'">'.nombre_objets($numberOfLinks, 'link').'</a></li>';
        echo '<li><a href="commentaires.php?q='.$query.'">'.nombre_objets($numberOfComments, 'commentaire').'</a></li>';
        echo '<li><a href="fichiers.php?q='.$query.'">'.nombre_objets($numberOfFiles, 'fichier').'</a></li>';
        echo '<li><a href="feed.php?q='.$query.'">'.nombre_objets($numberOfFeeds, 'feed_entry').'</a></li>';
    echo '</ul>';
    echo '</div>';
} else {
    // Main Dashboard
    if ($numberOfPosts) {
        display_graph($posts, $GLOBALS['lang']['label_articles'], 'posts');
    }
    if ($numberOfComments) {
        display_graph($comments, $GLOBALS['lang']['label_commentaires'], 'comments');
    }
    if ($numberOfLinks) {
        display_graph($links, $GLOBALS['lang']['label_links'], 'links');
    }
    if (!max($numberOfPosts, $numberOfComments, $numberOfLinks)) {
        echo info($GLOBALS['lang']['note_no_article']);
    }
}

echo '</div>';
echo <<<EOS
<script src="style/javascript.js"></script>
<script>
    var containers = document.querySelectorAll(".graph-container"),
        month_min_width = 40; // in px
    function indexGraphStat()
    {
        for (var i = 0, clen = containers.length; i < clen; i += 1) {
            var months = containers[i].querySelectorAll('.month'),
                months_ct = months.length,
                month_to_show = containers[i].clientWidth / month_min_width;
            if (month_to_show > months_ct) {
                month_to_show = months_ct;
            }
            for (var j = 0; j < months_ct; j += 1) {
                months[j].style.width = (100 / month_to_show) + '%';
            }
        }
        respondCanvas();
    }

    window.addEventListener("resize", indexGraphStat);
    indexGraphStat();
</script>
EOS;

echo tpl_get_footer($begin);
