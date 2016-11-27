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



function bdd_article($billet, $what)
{
    // l'article n'existe pas, on le crée
    if ($what == 'enregistrer-nouveau') {
        try {
            $req = $GLOBALS['db_handle']->prepare('INSERT INTO articles
                (   bt_type,
                    bt_id,
                    bt_date,
                    bt_title,
                    bt_abstract,
                    bt_link,
                    bt_notes,
                    bt_content,
                    bt_wiki_content,
                    bt_tags,
                    bt_keywords,
                    bt_allow_comments,
                    bt_nb_comments,
                    bt_statut
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $req->execute(array(
                'article',
                $billet['bt_id'],
                $billet['bt_date'],
                $billet['bt_title'],
                $billet['bt_abstract'],
                $billet['bt_link'],
                $billet['bt_notes'],
                $billet['bt_content'],
                $billet['bt_wiki_content'],
                $billet['bt_tags'],
                $billet['bt_keywords'],
                $billet['bt_allow_comments'],
                0,
                $billet['bt_statut']
            ));
            return true;
        } catch (Exception $e) {
            return 'Erreur ajout article: '.$e->getMessage();
        }
    // l'article existe, et il faut le mettre à jour alors.
    } elseif ($what == 'modifier-existant') {
        try {
            $req = $GLOBALS['db_handle']->prepare('UPDATE articles SET
                bt_date=?,
                bt_title=?,
                bt_link=?,
                bt_abstract=?,
                bt_notes=?,
                bt_content=?,
                bt_wiki_content=?,
                bt_tags=?,
                bt_keywords=?,
                bt_allow_comments=?,
                bt_statut=?
                WHERE ID=?');
            $req->execute(array(
                $billet['bt_date'],
                $billet['bt_title'],
                $billet['bt_link'],
                $billet['bt_abstract'],
                $billet['bt_notes'],
                $billet['bt_content'],
                $billet['bt_wiki_content'],
                $billet['bt_tags'],
                $billet['bt_keywords'],
                $billet['bt_allow_comments'],
                $billet['bt_statut'],
                $_POST['ID']
            ));
            return true;
        } catch (Exception $e) {
            return 'Erreur mise à jour de l’article: '.$e->getMessage();
        }
    // Suppression d'un article
    } elseif ($what == 'supprimer-existant') {
        try {
            $req = $GLOBALS['db_handle']->prepare('DELETE FROM articles WHERE ID=?');
            $req->execute(array($_POST['ID']));
            return true;
        } catch (Exception $e) {
            return 'Erreur 123456 : '.$e->getMessage();
        }
    }
}
