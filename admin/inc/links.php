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

/**
 *
 */
function links_db_push($link)
{
    $req = $GLOBALS['db_handle']->prepare('
        INSERT INTO links
                (
                    bt_type,
                    bt_id,
                    bt_content,
                    bt_wiki_content,
                    bt_title,
                    bt_link,
                    bt_tags,
                    bt_statut
                )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

    return $req->execute(array(
        $link['bt_type'],
        $link['bt_id'],
        $link['bt_content'],
        $link['bt_wiki_content'],
        $link['bt_title'],
        $link['bt_link'],
        $link['bt_tags'],
        $link['bt_statut']
    ));
}

/**
 *
 */
function links_db_upd($link)
{
    $req = $GLOBALS['db_handle']->prepare('
         UPDATE links
            SET bt_content = ?,
                bt_wiki_content = ?,
                bt_title = ?,
                bt_link = ?,
                bt_tags = ?,
                bt_statut = ?
          WHERE ID = ?');

    return $req->execute(array(
        $link['bt_content'],
        $link['bt_wiki_content'],
        $link['bt_title'],
        $link['bt_link'],
        $link['bt_tags'],
        $link['bt_statut'],
        $link['ID']
    ));
}

/**
 *
 */
function links_db_del($link)
{
    $sql = '
        DELETE FROM links
         WHERE ID = ?';
    $req = $GLOBALS['db_handle']->prepare($sql);

    return $req->execute(array($link['ID']));
}

/**
 * To add a lik in two steps:
 *   1) a link is given => display form (link + title)
 *   2) then, a desription and add to the DTB
 */
function traiter_form_link($link)
{
    if (filter_input(INPUT_POST, 'enregistrer') !== null) {
        $result = links_db_push($link);
        $redir = basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_link_ajout';
    } elseif (filter_input(INPUT_POST, 'editer') !== null) {
        $result = links_db_upd($link);
        $redir = basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_link_edit';
    } elseif (filter_input(INPUT_POST, 'supprimer') !== null) {
        $result = links_db_del($link);
        $redir = basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_link_suppr';
    }

    if (isset($result) && $result === true) {
        flux_refresh_cache_lv1();
        redirection($redir);
    }

    die($result);
}

/**
 * Post a link.
 */
function init_post_link2()
{
    // Second init: the whole link data needs to be stored
    $linkIdPost = filter_input(INPUT_POST, 'ID');
    $linkId = (string)filter_input(INPUT_POST, 'bt_id');
    $type = (string)filter_input(INPUT_POST, 'type');
    $desc = (string)filter_input(INPUT_POST, 'description');
    $title = (string)filter_input(INPUT_POST, 'title');
    $url = (string)filter_input(INPUT_POST, 'url');
    $categories = (string)filter_input(INPUT_POST, 'categories');
    $status = (int)(filter_input(INPUT_POST, 'statut') === null);

    $link = array (
        'bt_id' => $linkId,
        'bt_type' => htmlspecialchars($type),
        'bt_content' => markup(htmlspecialchars(clean_txt($desc), ENT_NOQUOTES)),
        'bt_wiki_content' => protect($desc),
        'bt_title' => protect($title),
        'bt_link' => (!$url) ? URL_ROOT.'?mode=links&amp;id='.$linkId : protect($url),
        'bt_tags' => htmlspecialchars(traiter_tags($categories)),
        'bt_statut' => $status
    );
    if (is_numeric($linkIdPost)) {
        // ID only added on edit
        $link['ID'] = $linkIdPost;
    }

    return $link;
}

/**
 * Add a link from BO
 */
function afficher_form_link($step, $errors, $linkEdited = '')
{
    if ($errors) {
        echo erreurs($errors);
    }

    $form = '';
    if ($step == 1) {
        $form .= '<form method="get" id="post-new-lien" action="'.basename($_SERVER['SCRIPT_NAME']).'">';
        $form .= '<fieldset>';
        $form .= '<div class="contain-input">';
        $form .= '<label for="url">'.$GLOBALS['lang']['label_nouv_lien'].'</label>';
        $form .= '<input type="text" name="url" id="url" value="" size="70" placeholder="http://www.example.com/" class="text" autocomplete="off" />';
        $form .= '</div>';
        $form .= '<p class="submit-bttns"><button type="submit" class="submit button-submit">'.$GLOBALS['lang']['envoyer'].'</button></p>';
        $form .= '</fieldset>';
        $form .= '</form>';
    } elseif ($step == 2) {
        $form .= '<form method="post" onsubmit="return moveTag();" id="post-lien" action="'.basename($_SERVER['SCRIPT_NAME']).'">';

        $url = (string)filter_input(INPUT_GET, 'url');
        $type = 'url';
        $title = $url;
        $charset = 'UTF-8';
        $newId = date('YmdHis');

        if (!$url || strpos($url, 'http') !== 0) {
            $type = 'note';
            $title = 'Note'.(($url) ? ' : '.html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '');
            $url = URL_ROOT.'?mode=links&amp;id='.$newId;
            $form .= hidden_input('url', $url);
            $form .= hidden_input('type', 'note');
        } else {
            // Find out type of file
            $response = request_external_files(array($url), 15, false);
            $extFile = $response[$url];
            $repHeaders = $extFile['headers'];
            $cntType = (isset($repHeaders['content-type'])) ? (is_array($repHeaders['content-type']) ? $repHeaders['content-type'][count($repHeaders['content-type']) - 1] : $repHeaders['content-type']) : 'text/';
            $cntType = (is_array($cntType)) ? $cntType[0] : $cntType;

            if (strpos($cntType, 'image/') === 0) {
                // Picture
                $title = $GLOBALS['lang']['label_image'];
                if (list($width, $height) = @getimagesize($url)) {
                    $fdata = $url;
                    $type = 'image';
                    $title .= ' - '.$width.'x'.$height.'px ';
                }
            } elseif (strpos($cntType, 'text/') !== 0 && strpos($cntType, 'xml') === false) {
                // Non-image NON-textual file (pdf…)
                if ($GLOBALS['dl_link_to_files'] == 2) {
                    $type = 'file';
                }
            } elseif ($extFile['body']) {
                // a textual document: parse it for any <title> element (+charset for title decoding ; fallback=UTF-8) ; fallback=$url
                // Search for charset in the headers
                if (preg_match('#charset=(.*);?#', $cntType, $headerCharset) && $headerCharset[1]) {
                    $charset = $headerCharset[1];
                } elseif (preg_match('#<meta .*charset=(["\']?)([^\s>"\']*)([\'"]?)\s*/?>#Usi', $extFile['body'], $metaCharset) && $metaCharset[2]) {
                    // If not found, search it in HTML
                    $charset = $metaCharset[2];
                }
                // get title in the proper encoding
                $extFile = html_entity_decode(((strtolower($charset) == 'iso-8859-1') ? utf8_encode($extFile['body']) : $extFile['body']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                preg_match('#<title ?[^>]*>(.*)</title>#Usi', $extFile, $titles);
                if ($titles[1]) {
                    $title = trim($titles[1]);
                }
            }

            $form .= '<input type="text" name="url" value="'.htmlspecialchars($url).'" placeholder="URL" size="50" class="text readonly-like" />';
            $form .= hidden_input('type', 'link');
        }

        $link = array('title' => htmlspecialchars($title), 'url' => htmlspecialchars($url));
        $form .= '<input type="text" name="title" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_titre']).'" required="" value="'.$link['title'].'" size="50" class="text" autofocus />';
        $form .= '<span id="description-box">';
        $form .= ($type == 'image') ? '<span id="img-container"><img src="'.$fdata.'" alt="img" class="preview-img" height="'.$height.'" width="'.$width.'"/></span>' : '';
        $form .= '<textarea class="text description" name="description" cols="40" rows="7" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_description']).'"></textarea>';
        $form .= '</span>';

        $form .= '<div id="tag_bloc">';
        $form .= form_categories_links('links', '');
        $form .= '<input list="htmlListTags" type="text" class="text" id="type_tags" name="tags" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'"/>';
        $form .= '<input type="hidden" id="categories" name="categories" value="" />';
        $form .= '</div>';

        $form .= '<input type="checkbox" name="statut" id="statut" class="checkbox" /><label class="forcheckbox" for="statut">'.$GLOBALS['lang']['label_lien_priv'].'</label>';
        if ($type == 'image' || $type == 'file') {
            // download of file is asked
            $form .= ($GLOBALS['dl_link_to_files'] == 2) ? '<input type="checkbox" name="add_to_files" id="add_to_files" class="checkbox" /><label class="forcheckbox" for="add_to_files">'.$GLOBALS['lang']['label_dl_fichier'].'</label>' : '';
            // download of file is systematic
            $form .= ($GLOBALS['dl_link_to_files'] == 1) ? hidden_input('add_to_files', 'on') : '';
        }
        $form .= '<p class="submit-bttns">';
        $form .= '<button class="submit button-cancel" type="button" onclick="annuler(\'links.php\');">'.$GLOBALS['lang']['annuler'].'</button>';
        $form .= '<button class="submit button-submit" type="submit" name="enregistrer" id="valid-link">'.$GLOBALS['lang']['envoyer'].'</button>';
        $form .= '</p>';
        $form .= hidden_input('_verif_envoi', 1);
        $form .= hidden_input('bt_id', $newId);
        $form .= hidden_input('token', new_token());
        $form .= hidden_input('dossier', '');
        $form .= '</form>';
    } elseif ($step == 'edit') {
        $form = '<form method="post" onsubmit="return moveTag();" id="post-lien" action="'.basename($_SERVER['SCRIPT_NAME']).'?id='.$linkEdited['bt_id'].'">';
        $form .= '<input type="text" name="url" placeholder="URL" required="" value="'.$linkEdited['bt_link'].'" size="70" class="text readonly-like" />';
        $form .= '<input type="text" name="title" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_titre']).'" required="" value="'.$linkEdited['bt_title'].'" size="70" class="text" autofocus />';
        $form .= '<div id="description-box">';
        $form .= '<textarea class="description text" name="description" cols="70" rows="7" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_description']).'" >'.$linkEdited['bt_wiki_content'].'</textarea>';
        $form .= '</div>';
        $form .= '<div id="tag_bloc">';
        $form .= form_categories_links('links', $linkEdited['bt_tags']);
        $form .= '<input list="htmlListTags" type="text" class="text" id="type_tags" name="tags" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'"/>';
        $form .= '<input type="hidden" id="categories" name="categories" value="" />';
        $form .= '</div>';
        $form .= '<input type="checkbox" name="statut" id="statut" class="checkbox" '.(($linkEdited['bt_statut'] == 0) ? 'checked' : '').'/><label class="forcheckbox" for="statut">'.$GLOBALS['lang']['label_lien_priv'].'</label>';

        $form .= '<p class="submit-bttns">';
        $form .= '<button class="submit button-delete" type="button" name="supprimer" onclick="rmArticle(this)">'.$GLOBALS['lang']['supprimer'].'</button>';
        $form .= '<button class="submit button-cancel" type="button" onclick="annuler(\'links.php\');">'.$GLOBALS['lang']['annuler'].'</button>';
        $form .= '<button class="submit button-submit" type="submit" name="editer">'.$GLOBALS['lang']['envoyer'].'</button>';
        $form .= '</p>';
        $form .= hidden_input('ID', $linkEdited['ID']);
        $form .= hidden_input('bt_id', $linkEdited['bt_id']);
        $form .= hidden_input('_verif_envoi', 1);
        $form .= hidden_input('is_it_edit', 'yes');
        $form .= hidden_input('token', new_token());
        $form .= hidden_input('type', $linkEdited['bt_type']);
        $form .= '</form>';
    }

    return $form;
}

/**
 *  Link template.
 */
function afficher_lien($link)
{
    $list = '<div class="linkbloc'.((!$link['bt_statut']) ? ' privatebloc' : '').'">';
    $list .= '<div class="link-header">';
    $list .= '<a class="titre-lien" href="'.$link['bt_link'].'">'.$link['bt_title'].'</a>';
    $list .= '<span class="date">'.date_formate($link['bt_id']).', '.heure_formate($link['bt_id']).'</span>';
    $list .= '<div class="link-options">';
    $list .= '<ul>';
    $list .= '<li class="ll-edit"><a href="'.basename($_SERVER['SCRIPT_NAME']).'?id='.$link['bt_id'].'">'.$GLOBALS['lang']['editer'].'</a></li>';
    $list .= ($link['bt_statut'] == 1) ? '<li class="ll-seepost"><a href="'.URL_ROOT.'?mode=links&amp;id='.$link['bt_id'].'">'.$GLOBALS['lang']['voir_sur_le_blog'].'</a></li>' : '';
    $list .= '</ul>';
    $list .= '</div>';
    $list .=  '</div>';

    $list .= ($link['bt_content']) ? '<div class="link-content">'.$link['bt_content'].'</div>' : '';

    $list .= '<div class="link-footer">';
    $list .= '<ul class="link-tags">';
    if ($link['bt_tags']) {
        $tags = explode(',', $link['bt_tags']);
        foreach ($tags as $tag) {
            $list .= '<li class="tag"><a href="?filtre=tag.'.urlencode(trim($tag)).'">'.trim($tag).'</a></li>';
        }
    }
    $list .= '</ul>';
    $list .= '<span class="hard-link">'.$link['bt_link'].'</span>';
    $list .= '</div>';
    $list .= '</div>';

    echo $list;
}
