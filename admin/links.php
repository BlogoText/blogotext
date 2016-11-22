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

define('BT_ROOT', '../');

require_once '../inc/inc.php';

auth_ttl();
$begin = microtime(true);

$GLOBALS['db_handle'] = open_base();
$step = 0;


// Add a link from BO
function afficher_form_link($step, $erreurs, $editlink = '')
{
    if ($erreurs) {
        echo erreurs($erreurs);
    }
    $form = '';
    if ($step == 1) { // postage de l'URL : un champ affiché en GET
        $form .= '<form method="get" id="post-new-lien" action="'.basename($_SERVER['SCRIPT_NAME']).'">'."\n";
        $form .= '<fieldset>'."\n";
        $form .= "\t".'<div class="contain-input">'."\n";
        $form .= "\t\t".'<label for="url">'.$GLOBALS['lang']['label_nouv_lien'].'</label>'."\n";
        $form .= "\t\t".'<input type="text" name="url" id="url" value="" size="70" placeholder="http://www.example.com/" class="text" autocomplete="off" />'."\n";
        $form .= "\t".'</div>'."\n";
        $form .= "\t".'<p class="submit-bttns"><button type="submit" class="submit button-submit">'.$GLOBALS['lang']['envoyer'].'</button></p>'."\n";
        $form .= '</fieldset>'."\n";
        $form .= '</form>'."\n\n";
    } elseif ($step == 2) { // Form de l'URL, avec titre, description, en POST cette fois, et qu'il faut vérifier avant de stoquer dans la BDD.
        $form .= '<form method="post" onsubmit="return moveTag();" id="post-lien" action="'.basename($_SERVER['SCRIPT_NAME']).'">'."\n";

        $url = $_GET['url'];
        $type = 'url';
        $title = $url;
        $charset = "UTF-8";
        $new_id = date('YmdHis');

        // URL is empty or no URI. It’s a note: we hide the URI field.
        if (empty($url) or (strpos($url, 'http') !== 0)) {
            $type = 'note';
            $title = 'Note'.(!empty($url) ? ' : '.html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '');
            $url = $GLOBALS['racine'].'?mode=links&amp;id='.$new_id;
            $form .= hidden_input('url', $url);
            $form .= hidden_input('type', 'note');
        // URL is not empty
        } else {
            // Find out type of file
            $response = request_external_files(array($url), 15, false);
            $ext_file = $response[$url];
            $rep_hdr = $ext_file['headers'];
            $cnt_type = (isset($rep_hdr['content-type'])) ? (is_array($rep_hdr['content-type']) ? $rep_hdr['content-type'][count($rep_hdr['content-type'])-1] : $rep_hdr['content-type']) : 'text/';
            $cnt_type = (is_array($cnt_type)) ? $cnt_type[0] : $cnt_type;

            // Image
            if (strpos($cnt_type, 'image/') === 0) {
                $title = $GLOBALS['lang']['label_image'];
                if (list($width, $height) = @getimagesize($url)) {
                    $fdata = $url;
                    $type = 'image';
                    $title .= ' - '.$width.'x'.$height.'px ';
                }
            } // Non-image NON-textual file (pdf…)
            elseif (strpos($cnt_type, 'text/') !== 0 and strpos($cnt_type, 'xml') === false) {
                if ($GLOBALS['dl_link_to_files'] == 2) {
                    $type = 'file';
                }
            } // a textual document: parse it for any <title> element (+charset for title decoding ; fallback=UTF-8) ; fallback=$url
            elseif (!empty($ext_file['body'])) {
                // Search for charset in the headers
                if (preg_match('#charset=(.*);?#', $cnt_type, $hdr_charset) and !empty($hdr_charset[1])) {
                    $charset = $hdr_charset[1];
                } // If not found, search it in HTML
                elseif (preg_match('#<meta .*charset=(["\']?)([^\s>"\']*)([\'"]?)\s*/?>#Usi', $ext_file['body'], $meta_charset) and !empty($meta_charset[2])) {
                    $charset = $meta_charset[2];
                }
                // get title in the proper encoding
                $ext_file = html_entity_decode(((strtolower($charset) == 'iso-8859-1') ? utf8_encode($ext_file['body']) : $ext_file['body']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                preg_match('#<title ?[^>]*>(.*)</title>#Usi', $ext_file, $titles);
                if (!empty($titles[1])) {
                    $title = trim($titles[1]);
                }
            }

            $form .= "\t".'<input type="text" name="url" value="'.htmlspecialchars($url).'" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_url']).'" size="50" class="text readonly-like" />'."\n";
            $form .= hidden_input('type', 'link');
        }

        $link = array('title' => htmlspecialchars($title), 'url' => htmlspecialchars($url));
        $form .= "\t".'<input type="text" name="title" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_titre']).'" required="" value="'.$link['title'].'" size="50" class="text" autofocus />'."\n";
        $form .= "\t".'<span id="description-box">'."\n";
        $form .= ($type == 'image') ? "\t\t".'<span id="img-container"><img src="'.$fdata.'" alt="img" class="preview-img" height="'.$height.'" width="'.$width.'"/></span>' : '';
        $form .= "\t\t".'<textarea class="text description" name="description" cols="40" rows="7" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_description']).'"></textarea>'."\n";
        $form .= "\t".'</span>'."\n";

        $form .= "\t".'<div id="tag_bloc">'."\n";
        $form .= form_categories_links('links', '');
        $form .= "\t".'<input list="htmlListTags" type="text" class="text" id="type_tags" name="tags" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'"/>'."\n";
        $form .= "\t".'<input type="hidden" id="categories" name="categories" value="" />'."\n";
        $form .= "\t".'</div>'."\n";

        $form .= "\t".'<input type="checkbox" name="statut" id="statut" class="checkbox" />'.'<label class="forcheckbox" for="statut">'.$GLOBALS['lang']['label_lien_priv'].'</label>'."\n";
        if ($type == 'image' or $type == 'file') {
            // download of file is asked
            $form .= ($GLOBALS['dl_link_to_files'] == 2) ? "\t".'<input type="checkbox" name="add_to_files" id="add_to_files" class="checkbox" />'.'<label class="forcheckbox" for="add_to_files">'.$GLOBALS['lang']['label_dl_fichier'].'</label>'."\n" : '';
            // download of file is systematic
            $form .= ($GLOBALS['dl_link_to_files'] == 1) ? hidden_input('add_to_files', 'on') : '';
        }
        $form .= "\t".'<p class="submit-bttns">'."\n";
        $form .= "\t\t".'<button class="submit button-cancel" type="button" onclick="annuler(\'links.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
        $form .= "\t\t".'<button class="submit button-submit" type="submit" name="enregistrer" id="valid-link">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
        $form .= "\t".'</p>'."\n";
        $form .= hidden_input('_verif_envoi', '1');
        $form .= hidden_input('bt_id', $new_id);
        $form .= hidden_input('token', new_token());
        $form .= hidden_input('dossier', '');
        $form .= '</form>'."\n\n";
    } elseif ($step == 'edit') { // Form pour l'édition d'un lien : les champs sont remplis avec le "wiki_content" et il y a les boutons suppr/activer en plus.
        $form = '<form method="post" onsubmit="return moveTag();" id="post-lien" action="'.basename($_SERVER['SCRIPT_NAME']).'?id='.$editlink['bt_id'].'">'."\n";
        $form .= "\t".'<input type="text" name="url" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_url']).'" required="" value="'.$editlink['bt_link'].'" size="70" class="text readonly-like" /></label>'."\n";
        $form .= "\t".'<input type="text" name="title" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_titre']).'" required="" value="'.$editlink['bt_title'].'" size="70" class="text" autofocus /></label>'."\n";
        $form .= "\t".'<div id="description-box">'."\n";
        $form .= "\t\t".'<textarea class="description text" name="description" cols="70" rows="7" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_description']).'" >'.$editlink['bt_wiki_content'].'</textarea>'."\n";
        $form .= "\t".'</div>'."\n";
        $form .= "\t".'<div id="tag_bloc">'."\n";
        $form .= form_categories_links('links', $editlink['bt_tags']);
        $form .= "\t\t".'<input list="htmlListTags" type="text" class="text" id="type_tags" name="tags" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'"/>'."\n";
        $form .= "\t\t".'<input type="hidden" id="categories" name="categories" value="" />'."\n";
        $form .= "\t".'</div>'."\n";
        $form .= "\t".'<input type="checkbox" name="statut" id="statut" class="checkbox" '.(($editlink['bt_statut'] == 0) ? 'checked ' : '').'/>'.'<label class="forcheckbox" for="statut">'.$GLOBALS['lang']['label_lien_priv'].'</label>'."\n";

        $form .= "\t".'<p class="submit-bttns">'."\n";
        $form .= "\t\t".'<button class="submit button-delete" type="button" name="supprimer" onclick="rmArticle(this)">'.$GLOBALS['lang']['supprimer'].'</button>'."\n";
        $form .= "\t\t".'<button class="submit button-cancel" type="button" onclick="annuler(\'links.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
        $form .= "\t\t".'<button class="submit button-submit" type="submit" name="editer">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
        $form .= "\t".'</p>'."\n";
        $form .= hidden_input('ID', $editlink['ID']);
        $form .= hidden_input('bt_id', $editlink['bt_id']);
        $form .= hidden_input('_verif_envoi', '1');
        $form .= hidden_input('is_it_edit', 'yes');
        $form .= hidden_input('token', new_token());
        $form .= hidden_input('type', $editlink['bt_type']);
        $form .= '</form>'."\n\n";
    }
    return $form;
}

// modèle d'affichage d'un div pour un lien (avec un formaulaire d'édition par lien).
function afficher_lien($link)
{
    $list = '';

    $list .= '<div class="linkbloc'.((!$link['bt_statut']) ? ' privatebloc' : '').'">'."\n";

    $list .= '<div class="link-header">'."\n";
    $list .= "\t".'<a class="titre-lien" href="'.$link['bt_link'].'">'.$link['bt_title'].'</a>'."\n";
    $list .= "\t".'<span class="date">'.date_formate($link['bt_id']).', '.heure_formate($link['bt_id']).'</span>'."\n";
    $list .= "\t".'<div class="link-options">';
    $list .= "\t\t".'<ul>'."\n";
    $list .= "\t\t\t".'<li class="ll-edit"><a href="'.basename($_SERVER['SCRIPT_NAME']).'?id='.$link['bt_id'].'">'.$GLOBALS['lang']['editer'].'</a></li>'."\n";
    $list .= ($link['bt_statut'] == '1') ? "\t\t\t".'<li class="ll-seepost"><a href="'.$GLOBALS['racine'].'?mode=links&amp;id='.$link['bt_id'].'">'.$GLOBALS['lang']['voir_sur_le_blog'].'</a></li>'."\n" : "";
    $list .= "\t\t".'</ul>'."\n";
    $list .= "\t".'</div>'."\n";
    $list .=  '</div>'."\n";

    $list .= (!empty($link['bt_content'])) ? "\t".'<div class="link-content">'.$link['bt_content'].'</div>'."\n" : '';

    $list .= "\t".'<div class="link-footer">'."\n";
    $list .= "\t\t".'<ul class="link-tags">'."\n";
    if (!empty($link['bt_tags'])) {
        $tags = explode(',', $link['bt_tags']);
        foreach ($tags as $tag) {
            $list .= "\t\t\t".'<li class="tag">'.'<a href="?filtre=tag.'.urlencode(trim($tag)).'">'.trim($tag).'</a>'.'</li>'."\n";
        }
    }
    $list .= "\t\t".'</ul>'."\n";
    $list .= "\t\t".'<span class="hard-link">'.$link['bt_link'].'</span>'."\n";
    $list .= "\t".'</div>'."\n";

    $list .= '</div>'."\n";
    echo $list;
}


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

afficher_html_head($GLOBALS['lang']['mesliens']);

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
