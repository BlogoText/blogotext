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

// dependancy
require_once BT_ROOT.'admin/inc/links.php';

$GLOBALS['db_handle'] = open_base();
$step = 0;


// TRAITEMENT
$erreurs_form = array();
if (!isset($_GET['url'])) { // rien : on affiche le premier FORM
    $step = 1;
} else { // URL donné dans le $_GET
    $step = 2;
}
if (isset($_GET['id']) and preg_match('#\d{14}#', $_GET['id'])) {
    $step = 'edit';
}

if (isset($_POST['_verif_envoi'])) {
    $link = init_post_link2();
    $erreurs_form = valider_form_link($link);
    $step = 'edit';
    if (empty($erreurs_form)) {
        // URL est un fichier !html !js !css !php ![vide] && téléchargement de fichiers activé :
        if (!isset($_POST['is_it_edit']) and $GLOBALS['dl_link_to_files'] >= 1) {
            // dl_link_to_files : 0 = never ; 1 = always ; 2 = ask with checkbox
            if (isset($_POST['add_to_files'])) {
                $_POST['fichier'] = $link['bt_link'];
                $fichier = init_post_fichier();
                $erreurs = valider_form_fichier($fichier);

                $GLOBALS['liste_fichiers'] = open_serialzd_file(FILES_DB);
                bdd_fichier($fichier, 'ajout-nouveau', 'download', $link['bt_link']);
            }
        }
        traiter_form_link($link);
    }
}

// create link list.
$tableau = array();

// on affiche les anciens liens seulement si on ne veut pas en ajouter un
if (!isset($_GET['url']) and !isset($_GET['ajout'])) {
    if (!empty($_GET['filtre'])) {
        // for "tags" the requests is "tag.$search" : here we split the type of search and what we search.
        $type = substr($_GET['filtre'], 0, -strlen(strstr($_GET['filtre'], '.')));
        $search = htmlspecialchars(ltrim(strstr($_GET['filtre'], '.'), '.'));
        if (preg_match('#^\d{6}(\d{1,8})?$#', $_GET['filtre'])) { // date
            $query = '
                SELECT *
                  FROM links
                 WHERE bt_id LIKE ?
                 ORDER BY bt_id DESC';
            $tableau = liste_elements($query, array($_GET['filtre'].'%'), 'links');
        } elseif ($_GET['filtre'] == 'draft' or $_GET['filtre'] == 'pub') { // visibles & brouillons
            $query = '
                SELECT *
                  FROM links
                 WHERE bt_statut = ?
                 ORDER BY bt_id DESC';
            $tableau = liste_elements($query, array((($_GET['filtre'] == 'draft') ? 0 : 1)), 'links');
        } elseif ($type == 'tag' and $search != '') { // tags
            $query = '
                SELECT *
                  FROM links
                 WHERE bt_tags LIKE ?
                       OR bt_tags LIKE ?
                       OR bt_tags LIKE ?
                       OR bt_tags LIKE ?
                 ORDER BY bt_id DESC';
            $tableau = liste_elements($query, array($search, $search.',%', '%, '.$search, '%, '.$search.', %'), 'links');
        } else {
            $query = '
                SELECT *
                  FROM links
                 ORDER BY bt_id DESC
                 LIMIT '.($GLOBALS['max_linx_admin'] + 0);
            $tableau = liste_elements($query, array(), 'links');
        }
    } elseif (!empty($_GET['q'])) { // mot clé
        $arr = parse_search($_GET['q']);
        $sql_where = implode(array_fill(0, count($arr), '( bt_content || bt_title || bt_link ) LIKE ?'), 'AND'); // AND operator between words
        $query = '
            SELECT *
              FROM links
             WHERE '.$sql_where.'
             ORDER BY bt_id DESC';
        $tableau = liste_elements($query, $arr, 'links');
    } elseif (!empty($_GET['id']) and is_numeric($_GET['id'])) { // édition d’un lien spécifique
        $query = '
            SELECT *
              FROM links
             WHERE bt_id = ?';
        $tableau = liste_elements($query, array($_GET['id']), 'links');
    } else { // aucun filtre : affiche TOUT
        $query = '
            SELECT *
              FROM links
             ORDER BY bt_id DESC
             LIMIT '.($GLOBALS['max_linx_admin'] + 0);
        $tableau = liste_elements($query, array(), 'links');
    }
}

// count total nb of links
$nb_links_displayed = count($tableau);

tpl_show_html_head($GLOBALS['lang']['mesliens']);

echo '<div id="header">'."\n";
    echo '<div id="top">'."\n";
    tpl_show_msg();
    echo moteur_recherche();
    tpl_show_topnav($GLOBALS['lang']['mesliens']);
    echo '</div>'."\n";
echo '</div>'."\n";

echo '<div id="axe">'."\n";
// SUBNAV
echo '<div id="subnav">'."\n";

// Affichage formulaire filtrage liens
if (isset($_GET['filtre'])) {
    afficher_form_filtre('links', htmlspecialchars($_GET['filtre']));
} else {
    afficher_form_filtre('links', '');
}
if ($step != 'edit' and $step != 2) {
    echo "\t".'<div class="nombre-elem">';
    echo "\t\t".ucfirst(nombre_objets($nb_links_displayed, 'link')).' '.$GLOBALS['lang']['sur'].' '.liste_elements_count("SELECT count(*) AS nbr FROM links", array(), 'links')."\n";
    echo "\t".'</div>'."\n";
}
echo '</div>'."\n";

echo '<div id="page">'."\n";

if ($step == 'edit' and !empty($tableau[0])) { // edit un lien : affiche le lien au dessus du champ d’édit
    //afficher_lien($tableau[0]);
    echo afficher_form_link($step, $erreurs_form, $tableau[0]);
} elseif ($step == 2) {
    // lien donné dans l’URL
    echo afficher_form_link($step, $erreurs_form);
} else {
    // aucun lien à ajouter ou éditer : champ nouveau lien + listage des liens en dessus.
    echo afficher_form_link(1, $erreurs_form);
    echo '<div id="list-link">'."\n";
    foreach ($tableau as $link) {
        afficher_lien($link);
    }
    if (!isset($_GET['ajout'])) {
        echo '<a id="fab" class="add-link" href="links.php?ajout" title="'.$GLOBALS['lang']['label_lien_ajout'].'">'.$GLOBALS['lang']['label_lien_ajout'].'</a>'."\n";
    }
    echo '</div>'."\n";
}

echo "\n".'<script src="style/javascript.js"></script>'."\n";
echo '<script>'."\n";
echo php_lang_to_js(0)."\n";

if ($step == 1) {
    echo 'document.getElementById(\'url\').addEventListener(\'focus\', hideFAB, false);'."\n";
    echo 'document.getElementById(\'url\').addEventListener(\'blur\', unHideFAB, false);'."\n";

    echo 'if (window.getComputedStyle(document.querySelector(\'#nav > ul\')).position != \'absolute\') {'."\n";
    echo '    document.getElementById(\'url\').focus();'."\n";
    echo '}'."\n";
}
echo 'var scrollPos = 0;'."\n";
echo 'window.addEventListener(\'scroll\', function(){ scrollingFabHideShow() });'."\n";
echo '</script>';

footer($begin);
