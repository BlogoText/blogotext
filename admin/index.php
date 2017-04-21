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
    global $is_mobile;

    $showMin = 12;  // (int) minimal number of months to show
    $showMax = 24;  // (int) maximal number of months to show
    if ($is_mobile) {
        $showMin = 6;
        $showMax = 12;
    }
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
    $useragent = htmlentities($_SERVER['HTTP_USER_AGENT']);
    $is_mobile = (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4)));
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
