<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

// Sets timezone
if (!empty($GLOBALS['fuseau_horaire'])) {
	date_default_timezone_set($GLOBALS['fuseau_horaire']);
} else {
	date_default_timezone_set('UTC');
}

// BLOGOTEXT VERSION (do not change it)
$GLOBALS['version'] = '2.0.2.5';
$GLOBALS['last-online-file'] = '../config/version.txt';

// MINIMAL REQUIRED PHP VERSION
$GLOBALS['minimal_php_version'] = '5.3';

// GENERAL
$GLOBALS['nom_application']= 'BlogoText';
$GLOBALS['appsite']= 'http://lehollandaisvolant.net/blogotext/';
$GLOBALS['date_premier_message_blog'] = '199701';
$GLOBALS['salt']= '123456'; // if changed : delete /config/user.php file and proceed to a re-installation. No data loss.
$GLOBALS['show_errors'] = -1; // -1 = all (for dev) ; 0 = none (recommended)

// FOLDERS (change this only if you know what you are doing...)
$GLOBALS['dossier_admin'] = 'admin';
$GLOBALS['dossier_backup'] = 'bt_backup';
$GLOBALS['dossier_images'] = 'img';
$GLOBALS['dossier_fichiers'] = 'files';
$GLOBALS['dossier_themes'] = 'themes';
$GLOBALS['dossier_cache'] = 'cache';
$GLOBALS['dossier_db'] = 'databases';
$GLOBALS['dossier_config'] = 'config';

$GLOBALS['db_location'] = 'database.sqlite';    // data storage file (for sqlite)
$GLOBALS['fichier_liste_fichiers'] = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_db'].'/'.'files.php'; // files/image info storage.


// DATABASE 'sqlite' or 'mysql' are supported yet.
$mysql_file = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_config'].'/'.'mysql.php';
if (is_file($mysql_file) and is_readable($mysql_file) and file_get_contents($mysql_file) != '') {
	include($mysql_file);
} else {
	$GLOBALS['sgdb'] = 'sqlite';
}


// regenerate captcha (always)
if (!isset($GLOBALS['captcha'])) {
	$ua = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$GLOBALS['captcha']['x'] = rand(4, 9);
	$GLOBALS['captcha']['y'] = rand(1, 6);
	$GLOBALS['captcha']['hash'] = sha1($ua.($GLOBALS['captcha']['x']+$GLOBALS['captcha']['y']).$GLOBALS['salt']);
}

// THEMES
/*
 * the files that will be used.
 */

if ( isset($GLOBALS['theme_choisi']) ) {
	$GLOBALS['theme_style'] = $GLOBALS['dossier_themes'].'/'.$GLOBALS['theme_choisi'];
	$GLOBALS['theme_liste'] = $GLOBALS['theme_style'].'/list.html';
	$GLOBALS['theme_post_artc'] = $GLOBALS['theme_style'].'/template/article.html';
	$GLOBALS['theme_post_comm'] = $GLOBALS['theme_style'].'/template/commentaire.html';
	$GLOBALS['theme_post_link'] = $GLOBALS['theme_style'].'/template/link.html';
	$GLOBALS['theme_post_post'] = $GLOBALS['theme_style'].'/template/post.html';

	$GLOBALS['rss'] = $GLOBALS['racine'].'rss.php';
}

// table of recognized filetypes, for file-upload script.
$GLOBALS['files_ext'] = array(
	'archive'		=> array('zip', '7z', 'rar', 'tar', 'gz', 'bz', 'bz2', 'xz', 'lzma'), // more ?
	'executable'	=> array('exe', 'e', 'bin'),
	'html-xml'		=> array('html', 'htm', 'xml', 'mht'), // more ?
	'image'			=> array('png', 'gif', 'bmp', 'jpg', 'jpeg', 'ico', 'svg', 'tif', 'tiff'),
	'music'			=> array('mp3', 'wave', 'wav', 'ogg', 'wma', 'flac', 'aac', 'mid', 'midi'), // more ?
	'presentation'	=> array('ppt', 'pptx', 'pps', 'ppsx', 'odp'),
	'pdf'			=> array('pdf', 'ps', 'psd'),
	'spreadsheet'	=> array('xls', 'xlsx', 'xlt', 'xltx', 'ods', 'ots', 'csv'),
	'text_document'	=> array('doc', 'docx', 'rtf', 'odt', 'ott'),
	'text-code'		=> array('txt', 'css', 'py', 'c', 'cpp', 'dat', 'ini', 'inf', 'text', 'conf', 'sh'), // more ?
	'video'			=> array('mp4', 'ogv', 'avi', 'mpeg', 'mpg', 'flv', 'webm', 'mov', 'divx', 'rm', 'rmvb', 'wmv'), // more ?
	'other'			=> array(''), // par défaut
);


// from an array given by SQLite's requests, this function adds some more stuf to data stored by DB.
function init_list_articles($article) {
	if (!empty($article)) {
		// pour ne plus rendre obligatoire le chapô : s'il est vide, on le recrée à partir du début du bt_content
		if (isset($article['bt_abstract']) and empty($article['bt_abstract'])) {
			mb_internal_encoding('UTF-8');
			$abstract = mb_substr(strip_tags($article['bt_content']), 0, 249);
			$article['bt_abstract'] = $abstract."…";
		}
		$dec_id = decode_id($article['bt_id']);
		$article = array_merge($article, decode_id($article['bt_date']));
		$article['lien'] = $_SERVER['PHP_SELF'].'?d='.$dec_id['annee'].'/'.$dec_id['mois'].'/'.$dec_id['jour'].'/'.$dec_id['heure'].'/'.$dec_id['minutes'].'/'.$dec_id['secondes'].'-'.titre_url($article['bt_title']);
		$article['bt_link'] = $GLOBALS['racine'].'?d='.$dec_id['annee'].'/'.$dec_id['mois'].'/'.$dec_id['jour'].'/'.$dec_id['heure'].'/'.$dec_id['minutes'].'/'.$dec_id['secondes'].'-'.titre_url($article['bt_title']);
	}
	return $article;
}

function init_list_comments($comment) {
		$comment['auteur_lien'] = (!empty($comment['bt_webpage'])) ? '<a href="'.$comment['bt_webpage'].'" class="webpage">'.$comment['bt_author'].'</a>' : $comment['bt_author'] ;
		$comment['anchor'] = article_anchor($comment['bt_id']);
		$comment['bt_link'] = get_blogpath($comment['bt_article_id'], '').'#'.$comment['anchor'];
		$comment = array_merge($comment, decode_id($comment['bt_id']));
	return $comment;
}


// POST ARTICLE
/*
 * On post of an article (always on admin sides)
 * gets posted informations and turn them into
 * an array
 *
 */

function init_post_article() { //no $mode : it's always admin.
	$formated = formatage_wiki(protect_markup(clean_txt($_POST['contenu'])));
	if ($GLOBALS['automatic_keywords'] == '0') {
		$keywords = htmlspecialchars(stripslashes(protect_markup(clean_txt($_POST['mots_cles']))));
	} else {
		$keywords = extraire_mots($_POST['titre'].' '.$formated);
	}

	$date = str4($_POST['annee']).str2($_POST['mois']).str2($_POST['jour']).str2($_POST['heure']).str2($_POST['minutes']).str2($_POST['secondes']);
	$id = (isset($_POST['article_id']) and preg_match('#\d{14}#', $_POST['article_id'])) ? $_POST['article_id'] : $date;

	$article = array (
		'bt_id'				=> $id,
		'bt_date'			=> $date,
		'bt_title'			=> htmlspecialchars(stripslashes(protect_markup(clean_txt($_POST['titre'])))),
		'bt_abstract'		=> htmlspecialchars(stripslashes(protect_markup(clean_txt($_POST['chapo'])))),
		'bt_notes'			=> htmlspecialchars(stripslashes(protect_markup(clean_txt($_POST['notes'])))),
		'bt_content'		=> $formated,
		'bt_wiki_content'	=> stripslashes(protect_markup(clean_txt($_POST['contenu']))),
		'bt_link'			=> '', // this one is not needed yet. Maybe in the futur. I dunno why it is still in the DB…
		'bt_keywords'		=> $keywords,
		'bt_categories'		=> htmlspecialchars(traiter_tags($_POST['categories'])), // htmlSpecialChars() nedded to escape the (") since tags are put in a <input/>. (') are escaped in form_categories(), with addslashes – not here because of JS problems :/
		'bt_statut'			=> $_POST['statut'],
		'bt_allow_comments'	=> $_POST['allowcomment'],
	);

	if ( isset($_POST['ID']) and is_numeric($_POST['ID']) ) { // ID only added on edit.
		$article['ID'] = $_POST['ID'];
	}
	return $article;
}

// POST COMMENT
/*
 * Same as init_post_article()
 * but, this one can be used on admin side and on public side.
 *
 */
function init_post_comment($id, $mode) {
	$comment = array();
	$edit_msg = '';
	if ( isset($id) ) {
		if ( ($mode == 'admin') and (isset($_POST['is_it_edit']) and $_POST['is_it_edit'] == 'yes') ) {
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
			$url = htmlspecialchars(stripslashes(clean_txt(  (strpos($_POST['webpage'], 'http://')===0 or strpos($_POST['webpage'], 'https://')===0)? $_POST['webpage'] : 'http://'.$_POST['webpage'] )));
		} else { $url = $_POST['webpage']; }

		$comment = array (
			'bt_id'				=> $comment_id,
			'bt_article_id'		=> $id,
			'bt_content'		=> formatage_commentaires(htmlspecialchars(clean_txt($_POST['commentaire'].$edit_msg), ENT_NOQUOTES)),
			'bt_wiki_content'	=> stripslashes(protect_markup(clean_txt($_POST['commentaire']))),
			'bt_author'			=> htmlspecialchars(stripslashes(clean_txt($_POST['auteur']))),
			'bt_email'			=> htmlspecialchars(stripslashes(clean_txt($_POST['email']))),
			'bt_link'			=> '', // this is empty, 'cause bt_link is created on reading of DB, not written in DB (useful if we change server or site name some day).
			'bt_webpage'		=> $url,
			'bt_subscribe'		=> (isset($_POST['subscribe']) and $_POST['subscribe'] == 'on') ? '1' : '0',
			'bt_statut'			=> $status,
		);
	}
	if ( isset($_POST['ID']) and is_numeric($_POST['ID']) ) { // ID only added on edit.
		$comment['ID'] = $_POST['ID'];
	}

	return $comment;
}

// POST LINK
function init_post_link2() { // second init : the whole link data needs to be stored
	$id = htmlspecialchars(stripslashes(protect_markup(clean_txt($_POST['bt_id']))));
	$author = htmlspecialchars(stripslashes(protect_markup(clean_txt($_POST['bt_author']))));
	if (empty($_POST['url'])) {
		$url = $GLOBALS['racine'].'index.php?mode=links&amp;id='.$id;
	} else {
		$url = htmlspecialchars(stripslashes(protect_markup(clean_txt($_POST['url']))));
	}
	$statut = (isset($_POST['statut'])) ? 0 : 1;
	$link = array (
		'bt_id'				=> $id,
		'bt_type'			=> htmlspecialchars($_POST['type']),
		'bt_content'		=> formatage_links(htmlspecialchars(stripslashes(protect_markup(clean_txt($_POST['description']))), ENT_NOQUOTES)), // formatage_wiki() ne parse que les tags BBCode. Le HTML est converti en texte.
		'bt_wiki_content'	=> htmlspecialchars(stripslashes(protect_markup(clean_txt($_POST['description'])))),
		'bt_author'			=> $author,
		'bt_title'			=> htmlspecialchars(stripslashes(protect_markup(clean_txt($_POST['title'])))),
		'bt_link'			=> $url,
		'bt_tags'			=> htmlspecialchars(traiter_tags($_POST['categories'])),
		'bt_statut'			=> $statut
	);
	if ( isset($_POST['ID']) and is_numeric($_POST['ID']) ) { // ID only added on edit.
		$link['ID'] = $_POST['ID'];
	}

	return $link;
}


