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


function links_db_push($link)
{
    try {
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
        $req->execute(
            array(
                $link['bt_type'],
                $link['bt_id'],
                $link['bt_content'],
                $link['bt_wiki_content'],
                $link['bt_title'],
                $link['bt_link'],
                $link['bt_tags'],
                $link['bt_statut']
            )
        );
        return true;
    } catch (Exception $e) {
        return 'Erreur 5867 : '.$e->getMessage();
    }
}

function links_db_upd($link)
{
    try {
        $req = $GLOBALS['db_handle']->prepare('
             UPDATE links
                SET
                    bt_content=?,
                    bt_wiki_content=?,
                    bt_title=?,
                    bt_link=?,
                    bt_tags=?,
                    bt_statut=?
              WHERE ID=?');
        $req->execute(
            array(
                $link['bt_content'],
                $link['bt_wiki_content'],
                $link['bt_title'],
                $link['bt_link'],
                $link['bt_tags'],
                $link['bt_statut'],
                $link['ID']
            )
        );
        return true;
    } catch (Exception $e) {
        return 'Erreur 435678 : '.$e->getMessage();
    }
}

function links_db_del($link)
{
    try {
        $sql = '
            DELETE FROM links
             WHERE ID=?';
        $req = $GLOBALS['db_handle']->prepare($sql);
        $req->execute(array($link['ID']));
        return true;
    } catch (Exception $e) {
        return 'Erreur 97652 : '.$e->getMessage();
    }
}

// traiter un ajout de lien prend deux étapes :
//  1) on donne le lien > il donne un form avec lien+titre
//  2) après ajout d'une description, on clic pour l'ajouter à la bdd.
// une fois le lien donné (étape 1) et les champs renseignés (étape 2) on traite dans la BDD
function traiter_form_link($link)
{
    $query_string = str_replace(((isset($_GET['msg'])) ? '&msg='.$_GET['msg'] : ''), '', $_SERVER['QUERY_STRING']);
    if (isset($_POST['enregistrer'])) {
        $result = links_db_push($link);
        $redir = basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_link_ajout';
    } elseif (isset($_POST['editer'])) {
        $result = links_db_upd($link);
        $redir = basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_link_edit';
    } elseif (isset($_POST['supprimer'])) {
        $result = links_db_del($link);
        $redir = basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_link_suppr';
    }

    if ($result === true) {
        flux_refresh_cache_lv1();
        redirection($redir);
    } else {
        die($result);
    }
}

// POST LINK
function init_post_link2()
{
    // second init : the whole link data needs to be stored
    $id = protect($_POST['bt_id']);
    $link = array (
        'bt_id'           => $id,
        'bt_type'         => htmlspecialchars($_POST['type']),
        'bt_content'      => markup(htmlspecialchars(clean_txt($_POST['description']), ENT_NOQUOTES)),
        'bt_wiki_content' => protect($_POST['description']),
        'bt_title'        => protect($_POST['title']),
        'bt_link'         => (empty($_POST['url'])) ? $GLOBALS['racine'].'?mode=links&amp;id='.$id : protect($_POST['url']),
        'bt_tags'         => htmlspecialchars(traiter_tags($_POST['categories'])),
        'bt_statut'       => (isset($_POST['statut'])) ? 0 : 1
    );
    if (isset($_POST['ID']) and is_numeric($_POST['ID'])) { // ID only added on edit.
        $link['ID'] = $_POST['ID'];
    }

    return $link;
}

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
