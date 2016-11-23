<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***


// once form is initiated, and no errors are found, treat it (save it to DB).
function traiter_form_billet($billet)
{
    if (isset($_POST['enregistrer']) and !isset($billet['ID'])) {
        $result = bdd_article($billet, 'enregistrer-nouveau');
        $redir = basename($_SERVER['SCRIPT_NAME']).'?post_id='.$billet['bt_id'].'&msg=confirm_article_maj';
    } elseif (isset($_POST['enregistrer']) and isset($billet['ID'])) {
        $result = bdd_article($billet, 'modifier-existant');
        $redir = basename($_SERVER['SCRIPT_NAME']).'?post_id='.$billet['bt_id'].'&msg=confirm_article_ajout';
    } elseif (isset($_POST['supprimer']) and isset($_POST['ID']) and is_numeric($_POST['ID'])) {
        $result = bdd_article($billet, 'supprimer-existant');
        try {
            $sql = '
                DELETE FROM commentaires
                 WHERE bt_article_id=?';
            $req = $GLOBALS['db_handle']->prepare($sql);
            $req->execute(array($_POST['article_id']));
        } catch (Exception $e) {
            die('Erreur Suppr Comm associés: '.$e->getMessage());
        }

        $redir = 'articles.php?msg=confirm_article_suppr';
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


// traiter un ajout de lien prend deux étapes :
//  1) on donne le lien > il donne un form avec lien+titre
//  2) après ajout d'une description, on clic pour l'ajouter à la bdd.
// une fois le lien donné (étape 1) et les champs renseignés (étape 2) on traite dans la BDD
function traiter_form_link($link)
{
    $query_string = str_replace(((isset($_GET['msg'])) ? '&msg='.$_GET['msg'] : ''), '', $_SERVER['QUERY_STRING']);
    if (isset($_POST['enregistrer'])) {
        $result = bdd_lien($link, 'enregistrer-nouveau');
        $redir = basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_link_ajout';
    } elseif (isset($_POST['editer'])) {
        $result = bdd_lien($link, 'modifier-existant');
        $redir = basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_link_edit';
    } elseif (isset($_POST['supprimer'])) {
        $result = bdd_lien($link, 'supprimer-existant');
        $redir = basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_link_suppr';
    }

    if ($result === true) {
        flux_refresh_cache_lv1();
        redirection($redir);
    } else {
        die($result);
    }
}

function bdd_lien($link, $what)
{
    if ($what == 'enregistrer-nouveau') {
        try {
            $req = $GLOBALS['db_handle']->prepare('INSERT INTO links
            (   bt_type,
                bt_id,
                bt_content,
                bt_wiki_content,
                bt_title,
                bt_link,
                bt_tags,
                bt_statut
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $req->execute(array(
                $link['bt_type'],
                $link['bt_id'],
                $link['bt_content'],
                $link['bt_wiki_content'],
                $link['bt_title'],
                $link['bt_link'],
                $link['bt_tags'],
                $link['bt_statut']
            ));
            return true;
        } catch (Exception $e) {
            return 'Erreur 5867 : '.$e->getMessage();
        }
    } elseif ($what == 'modifier-existant') {
        try {
            $req = $GLOBALS['db_handle']->prepare('UPDATE links SET
                bt_content=?,
                bt_wiki_content=?,
                bt_title=?,
                bt_link=?,
                bt_tags=?,
                bt_statut=?
                WHERE ID=?');
            $req->execute(array(
                $link['bt_content'],
                $link['bt_wiki_content'],
                $link['bt_title'],
                $link['bt_link'],
                $link['bt_tags'],
                $link['bt_statut'],
                $link['ID']
            ));
            return true;
        } catch (Exception $e) {
            return 'Erreur 435678 : '.$e->getMessage();
        }
    } elseif ($what == 'supprimer-existant') {
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
}
