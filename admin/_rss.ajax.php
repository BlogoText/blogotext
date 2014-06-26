<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2014 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();
$begin = microtime(TRUE);

$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);
$GLOBALS['liste_flux'] = open_serialzd_file($GLOBALS['fichier_liste_fluxrss']);

/*
	This file is called by the other files. It is an underground working script,
	It is not intended to be called directly in your browser.
*/

// retreive all RSS feeds from the source, and save them in DB.
if (isset($_POST['refresh_all'])) {
	$erreurs = valider_form_rss();
	if (!empty($erreurs)) {
		echo erreurs($erreurs);
		die;
	}

	$nb_new = refresh_rss($GLOBALS['liste_flux']);
	echo 'Success';
	echo new_token();
	echo $nb_new;
}

// delete old entries
if (isset($_POST['delete_old'])) {
	$erreurs = valider_form_rss();
	if (!empty($erreurs)) {
		echo erreurs($erreurs);
		die;
	}

	$query = 'DELETE FROM rss WHERE bt_statut=0';
	try {
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute(array());
		echo 'Success';
		echo new_token();
	} catch (Exception $e) {
		die('Error : Rss RM old entries AJAX: '.$e->getMessage());
	}

}

// add new RSS link to serialized-DB
if (isset($_POST['add-feed'])) {
	$erreurs = valider_form_rss();
	if (!empty($erreurs)) {
		echo erreurs($erreurs);
		die;
	}

	$new_feed = trim($_POST['add-feed']);
	$feed_array = get_new_feeds(array($new_feed => array()), '');

	if (!($feed_array[$new_feed]['infos']['type'] == 'ATOM' or $feed_array[$new_feed]['infos']['type'] == 'RSS')) {
		die('Error: Invalid ressource (not an RSS/ATOM feed)');
	}

	// adding to serialized-db
	$GLOBALS['liste_flux'][$new_feed] = array(
		'link' => $new_feed,
		'title' => ucfirst($feed_array[$new_feed]['infos']['title']),
		'favicon' => 'style/rss-feed-icon.png',
		'checksum' => '42',
		'time' => '1',
		'folder' => ''
	);

	// sort list with title
	$GLOBALS['liste_flux'] = array_reverse(tri_selon_sous_cle($GLOBALS['liste_flux'], 'title'));
	// save to file
	file_put_contents($GLOBALS['fichier_liste_fluxrss'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_flux']))).' */');

	// Update DB
	refresh_rss(array($new_feed => $GLOBALS['liste_flux'][$new_feed]));
	echo 'Success';
	echo new_token();
}

// mark some element(s) as read
if (isset($_POST['mark-as-read'])) {
	$erreurs = valider_form_rss();
	if (!empty($erreurs)) {
		echo erreurs($erreurs);
		die;
	}

	$what = $_POST['mark-as-read'];
	if ($what == 'all') {
		$query = 'UPDATE rss SET bt_statut=0';
		$array = array();
	}

	elseif ($what == 'site' and !empty($_POST['url'])) {
		$feedurl = $_POST['url'];
		$query = 'UPDATE rss SET bt_statut=0 WHERE bt_feed=?';
		$array = array($feedurl);
	}

	elseif ($what == 'post' and !empty($_POST['url'])) {
		$postid = $_POST['url'];
		$query = 'UPDATE rss SET bt_statut=0 WHERE bt_id=?';
		$array = array($postid);
	}

	elseif ($what == 'folder' and !empty($_POST['url'])) {
		$folder = $_POST['url'];
		$query = 'UPDATE rss SET bt_statut=0 WHERE bt_folder=?';
		$array = array($folder);
	}

	try {
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute($array);
		echo 'Success';
		echo new_token();
	} catch (Exception $e) {
		die('Error : Rss mark as read: '.$e->getMessage());
	}
}



exit;

