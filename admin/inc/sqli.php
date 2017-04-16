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
function bdd_article($post, $what)
{
    if ($what == 'enregistrer-nouveau') {
        $req = $GLOBALS['db_handle']->prepare('
            INSERT INTO articles
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
        return $req->execute(array(
            'article',
            $post['bt_id'],
            $post['bt_date'],
            $post['bt_title'],
            $post['bt_abstract'],
            $post['bt_link'],
            $post['bt_notes'],
            $post['bt_content'],
            $post['bt_wiki_content'],
            $post['bt_tags'],
            $post['bt_keywords'],
            $post['bt_allow_comments'],
            0,
            $post['bt_statut']
        ));
    } elseif ($what == 'modifier-existant') {
        $req = $GLOBALS['db_handle']->prepare('
            UPDATE articles
               SET bt_date = ?,
                   bt_title = ?,
                   bt_link = ?,
                   bt_abstract = ?,
                   bt_notes = ?,
                   bt_content = ?,
                   bt_wiki_content = ?,
                   bt_tags = ?,
                   bt_keywords = ?,
                   bt_allow_comments = ?,
                   bt_statut = ?
             WHERE ID = ?');
        return $req->execute(array(
            $post['bt_date'],
            $post['bt_title'],
            $post['bt_link'],
            $post['bt_abstract'],
            $post['bt_notes'],
            $post['bt_content'],
            $post['bt_wiki_content'],
            $post['bt_tags'],
            $post['bt_keywords'],
            $post['bt_allow_comments'],
            $post['bt_statut'],
            (int)filter_input(INPUT_POST, 'ID')
        ));
    } elseif ($what == 'supprimer-existant') {
        $req = $GLOBALS['db_handle']->prepare('DELETE FROM articles WHERE ID = ?');
        return $req->execute(array((int)filter_input(INPUT_POST, 'ID')));
    }
}
