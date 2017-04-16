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
require_once BT_ROOT_ADMIN.'inc/links.php';


$vars = array(
    'token' => (string)filter_input(INPUT_POST, 'token'),
    'bt_id' => (string)filter_input(INPUT_POST, 'bt_id'),
    'fichier' => (string)filter_input(INPUT_POST, 'fichier'),

    'url' => (string)filter_input(INPUT_GET, 'url'),
    'id' => (string)filter_input(INPUT_GET, 'id'),
    'filtre' => (string)filter_input(INPUT_GET, 'filtre'),
    'q' => (string)filter_input(INPUT_GET, 'q'),
);
$vars['_verif_envoi'] = (filter_input(INPUT_POST, '_verif_envoi') !== null);
$vars['is_it_edit'] = (filter_input(INPUT_POST, 'is_it_edit') !== null);
$vars['add_to_files'] = (filter_input(INPUT_POST, 'add_to_files') !== null);
$vars['ajout'] = (filter_input(INPUT_GET, 'ajout') !== null);


function validate_form_link()
{
    global $vars;
    $errors = array();

    if (!check_token($vars['token'])) {
        $errors[] = $GLOBALS['lang']['err_wrong_token'];
    }
    if (!preg_match('#^\d{14}$#', $vars['bt_id'])) {
        $errors[] = $GLOBALS['lang']['err_wrong_id'];
    }

    return $errors;
}

// Traitment
$errorsForm = array();
$step = (!$vars['url']) ? 1 : 2;
if (preg_match('#^\d{14}$#', $vars['id'])) {
    $step = 'edit';
}

if ($vars['_verif_envoi']) {
    $link = init_post_link2();
    $errorsForm = validate_form_link($link);
    $step = 'edit';
    if (!$errorsForm) {
        // URL est un fichier !html !js !css !php ![vide] && téléchargement de fichiers activé :
        if (!$vars['is_it_edit'] && $GLOBALS['dl_link_to_files'] >= 1) {
            // dl_link_to_files : 0 = never ; 1 = always ; 2 = ask with checkbox
            if ($vars['add_to_files']) {
                $vars['fichier'] = $link['bt_link'];
                $file = init_post_fichier();
                $errors = valider_form_fichier($file);

                $GLOBALS['liste_fichiers'] = open_serialzd_file(FILES_DB);
                bdd_fichier($file, 'ajout-nouveau', 'download', $link['bt_link']);
            }
        }
        traiter_form_link($link);
    }
}

$arr = array();
if (!$vars['url'] && !$vars['ajout']) {
    if ($vars['filtre']) {
        // for "tags" the requests is "tag.$search" : here we split the type of search and what we search.
        $type = substr($vars['filtre'], 0, -strlen(strstr($vars['filtre'], '.')));
        $search = htmlspecialchars(ltrim(strstr($vars['filtre'], '.'), '.'));

        if (preg_match('#^\d{6}(\d{1,8})?$#', $vars['filtre'])) {
            $sql = '
                SELECT *
                  FROM links
                 WHERE bt_id LIKE ?
                 ORDER BY bt_id DESC';
            $arr = liste_elements($sql, array($vars['filtre'].'%'), 'links');
        } elseif ($vars['filtre'] == 'draft' || $vars['filtre'] == 'pub') {
            $sql = '
                SELECT *
                  FROM links
                 WHERE bt_statut = ?
                 ORDER BY bt_id DESC';
            $arr = liste_elements($sql, array((int)($vars['filtre'] == 'draft')), 'links');
        } elseif ($type == 'tag' && $search) {
            $sql = '
                SELECT *
                  FROM links
                 WHERE bt_tags LIKE ?
                       OR bt_tags LIKE ?
                       OR bt_tags LIKE ?
                       OR bt_tags LIKE ?
                 ORDER BY bt_id DESC';
            $arr = liste_elements($sql, array($search, $search.',%', '%, '.$search, '%, '.$search.', %'), 'links');
        } else {
            $sql = '
                SELECT *
                  FROM links
                 ORDER BY bt_id DESC
                 LIMIT '.($GLOBALS['max_linx_admin'] + 0);
            $arr = liste_elements($sql, array(), 'links');
        }
    } elseif ($vars['q']) {
        $arr = parse_search($vars['q']);
        $sqlWhere = implode(array_fill(0, count($arr), '(bt_content || bt_title || bt_link) LIKE ?'), 'AND');
        $sql = '
            SELECT *
              FROM links
             WHERE '.$sqlWhere.'
             ORDER BY bt_id DESC';
        $arr = liste_elements($sql, $arr, 'links');
    } elseif ($vars['id']) {
        $sql = '
            SELECT *
              FROM links
             WHERE bt_id = ?';
        $arr = liste_elements($sql, array($vars['id']), 'links');
    } else {
        $sql = '
            SELECT *
              FROM links
             ORDER BY bt_id DESC
             LIMIT '.($GLOBALS['nb_list_linx'] + 0);
        $arr = liste_elements($sql, array(), 'links');
    }
}


echo tpl_get_html_head($GLOBALS['lang']['mesliens']);

echo '<div id="header">';
    echo '<div id="top">';
        tpl_show_msg();
        echo moteur_recherche();
        echo tpl_show_topnav($GLOBALS['lang']['mesliens']);
    echo '</div>';
echo '</div>';

echo '<div id="axe">';

// Subnav
echo '<div id="subnav">';
afficher_form_filtre('links', htmlspecialchars($vars['filtre']));
if ($step != 'edit' && $step != 2) {
    echo '<div class="nombre-elem">';
    echo ucfirst(nombre_objets(count($arr), 'link')).' '.$GLOBALS['lang']['sur'].' '.liste_elements_count('SELECT count(*) AS nbr FROM links', array(), 'links');
    echo '</div>';
}
echo '</div>';

echo '<div id="page">';

if ($step == 'edit' && $arr[0]) {
    echo afficher_form_link($step, $errorsForm, $arr[0]);
} elseif ($step == 2) {
    echo afficher_form_link($step, $errorsForm);
} else {
    echo afficher_form_link(1, $errorsForm);
    echo '<div id="list-link">';
    foreach ($arr as $link) {
        afficher_lien($link);
    }
    if (!$vars['ajout']) {
        echo '<a id="fab" class="add-link" href="links.php?ajout" title="'.$GLOBALS['lang']['label_lien_ajout'].'">'.$GLOBALS['lang']['label_lien_ajout'].'</a>';
    }
    echo '</div>';
}

echo '<script src="style/javascript.js"></script>';
echo '<script>';
echo php_lang_to_js(0);
if ($step == 1) {
    echo 'document.getElementById("url").addEventListener("focus", hideFAB, false);';
    echo 'document.getElementById("url").addEventListener("blur", unHideFAB, false);';
    echo 'if (window.getComputedStyle(document.querySelector("#nav > ul")).position != "absolute") {';
    echo '    document.getElementById("url").focus();';
    echo '}';
}
echo 'var scrollPos = 0;';
echo 'window.addEventListener("scroll", function(){ scrollingFabHideShow(); });';
echo '</script>';

echo tpl_get_footer($begin);
