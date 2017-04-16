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
 *
 */
function afficher_liste_articles($arr)
{
    if ($arr) {
        $out = '<ul id="billets">';
        foreach ($arr as $post) {
            $out .= '<li'.(($post['bt_date'] > date('YmdHis')) ? ' class="planned"' : '').'>';
            $title = trim(htmlspecialchars(mb_substr(strip_tags(((empty($post['bt_abstract'])) ? $post['bt_content'] : $post['bt_abstract'])), 0, 249), ENT_QUOTES)).'…';
            $out .= '<span class="'.(($post['bt_statut']) ? 'on' : 'off').'">'.'<a href="ecrire.php?post_id='.$post['bt_id'].'" title="'.$title.'">'.$post['bt_title'].'</a>'.'</span>';
            $out .= '<span><a href="'.basename($_SERVER['SCRIPT_NAME']).'?filtre='.substr($post['bt_date'], 0, 8).'">'.date_formate($post['bt_date']).'</a><span>, '.heure_formate($post['bt_date']).'</span></span>';
            $out .= '<span><a href="commentaires.php?post_id='.$post['bt_id'].'">'.$post['bt_nb_comments'].'</a></span>';
            $out .= '<span><a href="'.$post['bt_link'].'" title="'.$GLOBALS['lang'][(($post['bt_statut']) ? 'post_link' : 'preview')].'"></a></span>';
            $out .= '</li>';
        }
        $out .= '</ul>'."\n\n";
    } else {
        $out = info($GLOBALS['lang']['note_no_article']);
    }
    $out .= '<a id="fab" class="add-article" href="ecrire.php" title="'.$GLOBALS['lang']['titre_ecrire'].'">'.$GLOBALS['lang']['titre_ecrire'].'</a>';

    echo $out;
}


/**
 * process
 */

$tableau = array();
$query = (string)filter_input(INPUT_GET, 'q');
$filter = (string)filter_input(INPUT_GET, 'filtre');
if ($query) {
    $arr = parse_search($query);
    $sqlWhere = implode(array_fill(0, count($arr), '( bt_content || bt_title ) LIKE ?'), 'AND'); // AND operator between words
    $query = '
        SELECT *
          FROM articles
         WHERE '.$sqlWhere.'
         ORDER BY bt_date DESC';
    $tableau = liste_elements($query, $arr, 'articles');
} elseif ($filter) {
    // for "tags" the requests is "tag.$search" : here we split the type of search and what we search.
    $type = substr($filter, 0, -strlen(strstr($filter, '.')));
    $search = htmlspecialchars(ltrim(strstr($filter, '.'), '.'));

    if (preg_match('#^\d{6}(\d{1,8})?$#', $filter)) {
        $query = '
            SELECT *
              FROM articles
             WHERE bt_date LIKE ?
             ORDER BY bt_date DESC';
        $tableau = liste_elements($query, array($filter.'%'), 'articles');
    } elseif ($filter == 'draft' or $filter == 'pub') {
        $query = '
            SELECT *
              FROM articles
             WHERE bt_statut = ?
             ORDER BY bt_date DESC';
        $tableau = liste_elements($query, array((($filter == 'draft') ? 0 : 1)), 'articles');
    } elseif ($type == 'tag' and $search != '') {
        $query = '
            SELECT *
              FROM articles
             WHERE bt_tags LIKE ?
                   OR bt_tags LIKE ?
                   OR bt_tags LIKE ?
                   OR bt_tags LIKE ?
             ORDER BY bt_date DESC';
        $tableau = liste_elements($query, array($search, $search.',%', '%, '.$search, '%, '.$search.', %'), 'articles');
    } else {
        $query = '
            SELECT *
              FROM articles
             ORDER BY bt_date DESC LIMIT 0, '.$GLOBALS['max_bill_admin'];
        $tableau = liste_elements($query, array(), 'articles');
    }
} else {
    $query = '
        SELECT *
          FROM articles
         ORDER BY bt_date DESC LIMIT 0, '.$GLOBALS['max_bill_admin'];
    $tableau = liste_elements($query, array(), 'articles');
}


/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['mesarticles']);

echo '<div id="header">';
    echo '<div id="top">';
        tpl_show_msg();
        echo moteur_recherche();
        echo tpl_show_topnav($GLOBALS['lang']['mesarticles']);
    echo '</div>';
echo '</div>';

echo '<div id="axe">';
    echo '<div id="subnav">';
    afficher_form_filtre('articles', htmlspecialchars($filter));
    echo '<div class="nombre-elem">';
        echo ucfirst(nombre_objets(count($tableau), 'article')).' '.$GLOBALS['lang']['sur'].' '.liste_elements_count('SELECT count(*) AS nbr FROM articles', array());
    echo '</div>';
echo '</div>';

echo '<div id="page">';

afficher_liste_articles($tableau);

echo <<<EOS
<script src="style/javascript.js"></script>
<script>
    var scrollPos = 0;
    window.addEventListener("scroll", function() { scrollingFabHideShow(); });
</script>
EOS;

echo tpl_get_footer($begin);
