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

// Update all RSS feeds using GET (for cron jobs).
// only test here is on install UID.
if (isset($_GET['refresh_all'], $_GET['guid']) and ($_GET['guid'] == BLOG_UID)) {
    if ($_GET['guid'] == BLOG_UID) {
        $GLOBALS['db_handle'] = open_base();
        $GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);

        refresh_rss($GLOBALS['liste_flux']);
        die('Success');
    } else {
        die('Error');
    }
}

operate_session();

$GLOBALS['db_handle'] = open_base();
$GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);

/*
    This file is called by the other files. It is an underground working script,
    It is not intended to be called directly in your browser.
*/

// retreive all RSS feeds from the sources, and save them in DB.
if (isset($_POST['refresh_all'])) {
    $erreurs = valider_form_rss();
    if (!empty($erreurs)) {
        die(erreurs($erreurs));
    }
    $nb_new = refresh_rss($GLOBALS['liste_flux']);
    echo 'Success';
    echo $nb_new;
    die;
}


// delete old entries
if (isset($_POST['delete_old'])) {
    $erreurs = valider_form_rss();
    if (!empty($erreurs)) {
        die(erreurs($erreurs));
    }

    $query = '
        DELETE
          FROM rss
         WHERE bt_statut = 0
               AND bt_bookmarked = 0';
    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute(array());
        die('Success');
    } catch (Exception $e) {
        die('Error : Rss RM old entries AJAX: '.$e->getMessage());
    }
}


// add new RSS link to serialized-DB
if (isset($_POST['add-feed'])) {
    $erreurs = valider_form_rss();
    if (!empty($erreurs)) {
        die(erreurs($erreurs));
    }

    $new_feed = trim($_POST['add-feed']);
    $new_feed_folder = htmlspecialchars(trim($_POST['add-feed-folder']));
    $feed_array = retrieve_new_feeds(array($new_feed), '');

    if (!($feed_array[$new_feed]['infos']['type'] == 'ATOM' or $feed_array[$new_feed]['infos']['type'] == 'RSS')) {
        die('Error: Invalid ressource (not an RSS/ATOM feed)');
    }

    // adding to serialized-db
    $GLOBALS['liste_flux'][$new_feed] = array(
        'link' => $new_feed,
        'title' => ucfirst($feed_array[$new_feed]['infos']['title']),
        'favicon' => 'style/rss-feed-icon.png',
        'checksum' => 42,
        'time' => 1,
        'folder' => $new_feed_folder
    );

    // sort list with title
    $GLOBALS['liste_flux'] = array_reverse(tri_selon_sous_cle($GLOBALS['liste_flux'], 'title'));
    // save to file
    file_put_contents(FEEDS_DB, '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_flux']))).' */');

    // Update DB
    refresh_rss(array($new_feed => $GLOBALS['liste_flux'][$new_feed]));
    die('Success');
}

// mark some element(s) as read
if (isset($_POST['mark-as-read'])) {
    $erreurs = valider_form_rss();
    if (!empty($erreurs)) {
        die(erreurs($erreurs));
    }

    $what = $_POST['mark-as-read'];
    if ($what == 'all') {
        $query = '
            UPDATE rss
               SET bt_statut = 0';
        $array = array();
    } elseif ($what == 'site' and !empty($_POST['url'])) {
        $feedurl = $_POST['url'];
        $query = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_feed = ?';
        $array = array($feedurl);
    } elseif ($what == 'post' and !empty($_POST['url'])) {
        $postid = $_POST['url'];
        $query = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_id = ?';
        $array = array($postid);
    } elseif ($what == 'folder' and !empty($_POST['url'])) {
        $folder = $_POST['url'];
        $query = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_folder = ?';
        $array = array($folder);
    } elseif ($what == 'postlist' and !empty($_POST['url'])) {
        $list = json_decode($_POST['url']);
        $questionmarks = str_repeat("?,", count($list)-1)."?";
        $query = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_id IN ('.$questionmarks.')';
        $array = $list;
    }

    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute($array);
        die('Success');
    } catch (Exception $e) {
        die('Error : Rss mark as read: '.$e->getMessage());
    }
}

// mark some elements as fav
if (isset($_POST['mark-as-fav'])) {
    $erreurs = valider_form_rss();
    if (!empty($erreurs)) {
        die(erreurs($erreurs));
    }

    $url = $_POST['url'];
    $query = '
        UPDATE rss
           SET bt_bookmarked = (1 - bt_bookmarked)
         WHERE bt_id = ?';
    $array = array($url);

    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute($array);
        die('Success');
    } catch (Exception $e) {
        die('Error : Rss mark as fav: '.$e->getMessage());
    }
}

exit;
