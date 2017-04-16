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


/*  Creates a new BlogoText base.
    if file does not exists, it is created, as well as the tables.
    if file does exists, tables are checked and created if not exists
*/
function create_tables()
{
    $auto_increment = (DBMS == 'mysql') ? 'AUTO_INCREMENT' : ''; // SQLite doesn't need this, but MySQL does.
    $index_limit_size = (DBMS == 'mysql') ? '(15)' : ''; // MySQL needs a limit for indexes on TEXT fields.
    $if_not_exists = (DBMS == 'sqlite') ? 'IF NOT EXISTS' : ''; // MySQL doesn’t know this statement for INDEXES

    $dbase_structure['links'] = "CREATE TABLE IF NOT EXISTS links
        (
            ID INTEGER PRIMARY KEY $auto_increment,
            bt_type CHAR(20),
            bt_id BIGINT,
            bt_content TEXT,
            bt_wiki_content TEXT,
            bt_title TEXT,
            bt_tags TEXT,
            bt_link TEXT,
            bt_statut TINYINT
        ); CREATE INDEX $if_not_exists dateL ON links ( bt_id );";

    $dbase_structure['commentaires'] = "CREATE TABLE IF NOT EXISTS commentaires
        (
            ID INTEGER PRIMARY KEY $auto_increment,
            bt_type CHAR(20),
            bt_id BIGINT,
            bt_article_id BIGINT,
            bt_content TEXT,
            bt_wiki_content TEXT,
            bt_author TEXT,
            bt_link TEXT,
            bt_webpage TEXT,
            bt_email TEXT,
            bt_subscribe TINYINT,
            bt_statut TINYINT
        ); CREATE INDEX $if_not_exists dateC ON commentaires ( bt_id );";


    $dbase_structure['articles'] = "CREATE TABLE IF NOT EXISTS articles
        (
            ID INTEGER PRIMARY KEY $auto_increment,
            bt_type CHAR(20),
            bt_id BIGINT,
            bt_date BIGINT,
            bt_title TEXT,
            bt_abstract TEXT,
            bt_notes TEXT,
            bt_link TEXT,
            bt_content TEXT,
            bt_wiki_content TEXT,
            bt_tags TEXT,
            bt_keywords TEXT,
            bt_nb_comments INTEGER,
            bt_allow_comments TINYINT,
            bt_statut TINYINT
        ); CREATE INDEX $if_not_exists dateidA ON articles ( bt_date, bt_id );";

    /* here bt_ID is a GUID, from the feed, not only a 'YmdHis' date string.*/
    $dbase_structure['rss'] = "CREATE TABLE IF NOT EXISTS rss
        (
            ID INTEGER PRIMARY KEY $auto_increment,
            bt_id TEXT,
            bt_date BIGINT,
            bt_title TEXT,
            bt_link TEXT,
            bt_feed TEXT,
            bt_content TEXT,
            bt_statut TINYINT,
            bt_bookmarked TINYINT,
            bt_folder TEXT
        ); CREATE INDEX $if_not_exists dateidR ON rss ( bt_date, bt_id$index_limit_size );";

    /**
     * SQLite
     */
    switch (DBMS) {
        case 'sqlite':
            if (!is_file(DIR_DATABASES.'/database.sqlite')) {
                if (!create_folder(DIR_DATABASES)) {
                    die('Impossible de creer le dossier databases (chmod?)');
                }
            }
            $file = DIR_DATABASES.'/database.sqlite';
            // open tables
            try {
                $db_handle = new PDO('sqlite:'.$file);
                $db_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $db_handle->query('PRAGMA temp_store=MEMORY; PRAGMA synchronous=OFF; PRAGMA journal_mode=WAL;');

                $wanted_tables = array('commentaires', 'articles', 'links', 'rss');
                foreach ($wanted_tables as $i => $name) {
                    $results = $db_handle->exec($dbase_structure[$name]);
                }
            } catch (Exception $e) {
                die('Erreur 1: '.$e->getMessage());
            }
            break;

        /**
         * MySQL
         */
        case 'mysql':
            try {
                $options_pdo[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
                $db_handle = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DB.";charset=utf8;", MYSQL_LOGIN, MYSQL_PASS, $options_pdo);
                $db_handle->query('SET sql_mode="PIPES_AS_CONCAT"');
                // check each wanted table
                $wanted_tables = array('commentaires', 'articles', 'links', 'rss');
                foreach ($wanted_tables as $i => $name) {
                    $results = $db_handle->query($dbase_structure[$name]."DEFAULT CHARSET=utf8");
                    $results->closeCursor();
                }
            } catch (Exception $e) {
                die('Erreur 2: '.$e->getMessage());
            }
            break;
    }

    return $db_handle;
}

/* Open a base */
function open_base()
{
    $handle = create_tables();
    $handle->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $handle;
}

/* lists articles with search criterias given in $array. Returns an array containing the data*/
function liste_elements($query, $array, $data_type)
{
    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute($array);
        $return = array();

        switch ($data_type) {
            case 'articles':
                while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
                    $return[] = init_list_articles($row);
                }
                break;
            case 'commentaires':
                while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
                    $return[] = init_list_comments($row);
                }
                break;
            case 'links':
            case 'rss':
                while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
                    $return[] = $row;
                }
                break;
            default:
                break;
        }

        // prevent use hook on admin side
        if (!defined('IS_IN_ADMIN')) {
            $tmp_hook = hook_trigger_and_check('list_items', $return, $data_type);
            if ($tmp_hook !== false) {
                $return = $tmp_hook['1'];
            }
        }

        return $return;
    } catch (Exception $e) {
        die('Erreur 89208 : '.$e->getMessage());
    }
}

/* same as above, but return the amount of entries */
function liste_elements_count($query, $array)
{
    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute($array);
        $result = $req->fetch();
        return $result['nbr'];
    } catch (Exception $e) {
        die('Erreur 0003: '.$e->getMessage());
    }
}

// returns or prints an entry of some element of some table (very basic)
function get_entry($base_handle, $table, $entry, $id, $retour_mode)
{
    $query = "SELECT $entry FROM $table WHERE bt_id=?";
    try {
        $req = $base_handle->prepare($query);
        $req->execute(array($id));
        $result = $req->fetch();
    } catch (Exception $e) {
        die('Erreur : '.$e->getMessage());
    }

    if ($retour_mode == 'return' and !empty($result[$entry])) {
        return $result[$entry];
    }
    if ($retour_mode == 'echo' and !empty($result[$entry])) {
        echo $result[$entry];
    }
    return '';
}

// from an array given by SQLite's requests, this function adds some more stuf to data stored by DB.
function init_list_articles($article)
{
    if ($article) {
        $dec_id = decode_id($article['bt_id']);
        $article = array_merge($article, decode_id($article['bt_date']));
        $article['bt_link'] = URL_ROOT.'?d='.$dec_id['annee'].'/'.$dec_id['mois'].'/'.$dec_id['jour'].'/'.$dec_id['heure'].'/'.$dec_id['minutes'].'/'.$dec_id['secondes'].'-'.titre_url($article['bt_title']);
    }
    return $article;
}

function init_list_comments($comment)
{
    $comment['auteur_lien'] = (!empty($comment['bt_webpage'])) ? '<a href="'.$comment['bt_webpage'].'" class="webpage">'.$comment['bt_author'].'</a>' : $comment['bt_author'] ;
    $comment['anchor'] = article_anchor($comment['bt_id']);
    $comment['bt_link'] = get_blogpath($comment['bt_article_id'], $comment['bt_title']).'#'.$comment['anchor'];
    $comment = array_merge($comment, decode_id($comment['bt_id']));
    return $comment;
}

// POST COMMENT
/*
 * Same as init_post_article()
 * but, this one can be used on admin side and on public side.
 */
function init_post_comment($id, $mode)
{
    $comment = array();
    if (isset($id)) {
        if (($mode == 'admin') and (isset($_POST['is_it_edit']))) {
            $status = (isset($_POST['activer_comm']) and $_POST['activer_comm'] == 'on' ) ? '0' : '1'; // c'est plus « désactiver comm en fait »
            $comment_id = $_POST['comment_id'];
        } elseif ($mode == 'admin' and !isset($_POST['is_it_edit'])) {
            $status = '1';
            $comment_id = date('YmdHis');
        } else {
            $status = $GLOBALS['comm_defaut_status'];
            $comment_id = date('YmdHis');
        }

        // verif url.
        if (!empty($_POST['webpage'])) {
            $url = protect((strpos($_POST['webpage'], 'http://') === 0 or strpos($_POST['webpage'], 'https://') === 0)? $_POST['webpage'] : 'http://'.$_POST['webpage']);
        } else {
            $url = $_POST['webpage'];
        }

        $comment = array (
            'bt_id'           => $comment_id,
            'bt_article_id'   => $id,
            'bt_content'      => markup(htmlspecialchars(clean_txt($_POST['commentaire']), ENT_NOQUOTES)),
            'bt_wiki_content' => clean_txt($_POST['commentaire']),
            'bt_author'       => protect($_POST['auteur']),
            'bt_email'        => protect($_POST['email']),
            'bt_link'         => '', // this is empty, 'cause bt_link is created on reading of DB, not written in DB (useful if we change server or site name some day).
            'bt_webpage'      => $url,
            'bt_subscribe'    => (isset($_POST['subscribe']) and $_POST['subscribe'] == 'on') ? '1' : '0',
            'bt_statut'       => $status,
        );
    }
    if (isset($_POST['ID']) and is_numeric($_POST['ID'])) { // ID only added on edit.
        $comment['ID'] = $_POST['ID'];
    }

    return $comment;
}

// Called when a new comment is posted (public side or admin side) or on edit/activating/removing
//  when adding, redirects with message after processing
//  when edit/activating/removing, dies with message after processing (message is then caught with AJAX)
function traiter_form_commentaire($commentaire, $admin)
{
    $msg_param_to_trim = (isset($_GET['msg'])) ? '&msg='.$_GET['msg'] : '';
    $query_string = str_replace($msg_param_to_trim, '', $_SERVER['QUERY_STRING']);

    // add new comment (admin + public)
    if (isset($_POST['enregistrer']) and empty($_POST['is_it_edit'])) {
        $result = bdd_commentaire($commentaire, 'enregistrer-nouveau');
        if ($result === true) {
            if ($GLOBALS['comm_defaut_status'] == 1) { // send subscribe emails only if comments are not hidden
                send_emails($commentaire['bt_id']);
            }
            if ($admin == 'admin') {
                $query_string .= '&msg=confirm_comment_edit';
            }
            $redir = basename($_SERVER['SCRIPT_NAME']).'?'.$query_string.'#'.article_anchor($commentaire['bt_id']);
        } else {
            die($result);
        }
    } // admin operations
    elseif ($admin == 'admin') {
        // edit
        if (isset($_POST['enregistrer']) and isset($_POST['is_it_edit'])) {
            $result = bdd_commentaire($commentaire, 'editer-existant');
            $redir = basename($_SERVER['SCRIPT_NAME']).'?'.$query_string.'&msg=confirm_comment_edit';
        } // remove OR change status (ajax call)
        elseif (isset($_POST['com_supprimer']) or isset($_POST['com_activer'])) {
            $ID = (isset($_POST['com_supprimer']) ? htmlspecialchars($_POST['com_supprimer']) : htmlspecialchars($_POST['com_activer']));
            $action = (isset($_POST['com_supprimer']) ? 'supprimer-existant' : 'activer-existant');
            $comm = array('ID' => $ID, 'bt_article_id' => htmlspecialchars($_POST['com_article_id']));
            $result = bdd_commentaire($comm, $action);
            // Ajax response
            if ($result === true) {
                if (isset($_POST['com_activer']) and $GLOBALS['comm_defaut_status'] == 0) {
                    // send subscribe emails if comments just got activated
                    send_emails(htmlspecialchars($_POST['com_bt_id']));
                }
                flux_refresh_cache_lv1();
                echo 'Success'.new_token();
            } else {
                echo 'Error'.new_token();
            }
            exit;
        }
    } // do nothing & die (admin + public)
    else {
        redirection(basename($_SERVER['SCRIPT_NAME']).'?'.$query_string.'&msg=nothing_happend_oO');
    }

    if ($result === true) {
        flux_refresh_cache_lv1();
        redirection($redir);
    }
    die($result);
}

function bdd_commentaire($commentaire, $what)
{
    // ENREGISTREMENT D'UN NOUVEAU COMMENTAIRE.
    if ($what == 'enregistrer-nouveau') {
        try {
            $req = $GLOBALS['db_handle']->prepare('INSERT INTO commentaires
                (   bt_type,
                    bt_id,
                    bt_article_id,
                    bt_content,
                    bt_wiki_content,
                    bt_author,
                    bt_link,
                    bt_webpage,
                    bt_email,
                    bt_subscribe,
                    bt_statut
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $req->execute(array(
                'comment',
                $commentaire['bt_id'],
                $commentaire['bt_article_id'],
                $commentaire['bt_content'],
                $commentaire['bt_wiki_content'],
                $commentaire['bt_author'],
                $commentaire['bt_link'],
                $commentaire['bt_webpage'],
                $commentaire['bt_email'],
                $commentaire['bt_subscribe'],
                $commentaire['bt_statut']
            ));
            // remet à jour le nombre de commentaires associés à l’article.
            $sql = '
                SELECT count(ID) AS nbr
                  FROM commentaires
                 WHERE bt_article_id = ?
                       AND bt_statut = 1';
            $nb_comments_art = liste_elements_count($sql, array($commentaire['bt_article_id']));

            $sql2 = '
                UPDATE articles
                   SET bt_nb_comments = ?
                 WHERE bt_id = ?';
            $req2 = $GLOBALS['db_handle']->prepare($sql2);
            $req2->execute(array($nb_comments_art, $commentaire['bt_article_id']));

            return true;
        } catch (Exception $e) {
            return 'Erreur : '.$e->getMessage();
        }
    } elseif ($what == 'editer-existant') {
    // ÉDITION D'UN COMMENTAIRE DÉJÀ EXISTANT. (ou activation)
        try {
            $req = $GLOBALS['db_handle']->prepare('
                UPDATE commentaires
                   SET bt_article_id = ?,
                       bt_content = ?,
                       bt_wiki_content = ?,
                       bt_author = ?,
                       bt_link = ?,
                       bt_webpage = ?,
                       bt_email = ?,
                       bt_subscribe = ?,
                       bt_statut = ?
                 WHERE ID = ?');
            $req->execute(array(
                $commentaire['bt_article_id'],
                $commentaire['bt_content'],
                $commentaire['bt_wiki_content'],
                $commentaire['bt_author'],
                $commentaire['bt_link'],
                $commentaire['bt_webpage'],
                $commentaire['bt_email'],
                $commentaire['bt_subscribe'],
                $commentaire['bt_statut'],
                $commentaire['ID'],
            ));

            // remet à jour le nombre de commentaires associés à l’article.
            $sql = '
                SELECT count(*) AS nbr
                  FROM commentaires
                 WHERE bt_article_id = ?
                       AND bt_statut = 1';
            $nb_comments_art = liste_elements_count($sql, array($commentaire['bt_article_id']));

            $sql2 = '
                UPDATE articles
                   SET bt_nb_comments = ?
                 WHERE bt_id = ?';
            $req2 = $GLOBALS['db_handle']->prepare($sql2);
            $req2->execute(array($nb_comments_art, $commentaire['bt_article_id']));
            return true;
        } catch (Exception $e) {
            return 'Erreur : '.$e->getMessage();
        }
    } // SUPPRESSION D'UN COMMENTAIRE
    elseif ($what == 'supprimer-existant') {
        try {
            $req = $GLOBALS['db_handle']->prepare('DELETE FROM commentaires WHERE ID=?');
            $req->execute(array($commentaire['ID']));

            // remet à jour le nombre de commentaires associés à l’article.
            $sql = '
                SELECT count(ID) AS nbr
                  FROM commentaires
                 WHERE bt_article_id = ?
                       AND bt_statut = 1';
            $nb_comments_art = liste_elements_count($sql, array($commentaire['bt_article_id']));

            $sql2 = '
                UPDATE articles
                   SET bt_nb_comments = ?
                 WHERE bt_id = ?';
            $req2 = $GLOBALS['db_handle']->prepare($sql2);
            $req2->execute(array($nb_comments_art, $commentaire['bt_article_id']));
            return true;
        } catch (Exception $e) {
            return 'Erreur : '.$e->getMessage();
        }
    } // CHANGEMENT STATUS COMMENTAIRE
    elseif ($what == 'activer-existant') {
        try {
            $sql = '
                UPDATE commentaires
                   SET bt_statut = ABS(bt_statut - 1)
                 WHERE ID = ?';
            $req = $GLOBALS['db_handle']->prepare($sql);
            $req->execute(array($commentaire['ID']));

            // remet à jour le nombre de commentaires associés à l’article.
            $sql = '
                SELECT count(ID) AS nbr
                  FROM commentaires
                 WHERE bt_article_id = ?
                       AND bt_statut = 1';
            $nb_comments_art = liste_elements_count($sql, array($commentaire['bt_article_id']));

            $sql2 = '
                UPDATE articles
                   SET bt_nb_comments = ?
                 WHERE bt_id = ?';
            $req2 = $GLOBALS['db_handle']->prepare($sql2);
            $req2->execute(array($nb_comments_art, $commentaire['bt_article_id']));
            return true;
        } catch (Exception $e) {
            return 'Erreur : '.$e->getMessage();
        }
    }
}

function list_all_tags($table, $statut)
{
    try {
        if ($statut !== false) {
            $sql = "
                SELECT bt_tags FROM $table
                 WHERE bt_statut = $statut";
        } else {
            $sql = "SELECT bt_tags FROM $table";
        }
        $res = $GLOBALS['db_handle']->query($sql);
        $liste_tags = '';
        // met tous les tags de tous les articles bout à bout
        while ($entry = $res->fetch()) {
            if (trim($entry['bt_tags']) != '') {
                $liste_tags .= $entry['bt_tags'].',';
            }
        }
        $res->closeCursor();
        $liste_tags = rtrim($liste_tags, ',');
    } catch (Exception $e) {
        die('Erreur 4354768 : '.$e->getMessage());
    }

    $liste_tags = str_replace(array(', ', ' ,'), ',', $liste_tags);
    $tab_tags = explode(',', $liste_tags);
    sort($tab_tags);
    unset($tab_tags['']);
    return array_count_values($tab_tags);
}
