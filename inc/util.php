<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

function redirection($url) {
	header('Location: '.$url);
	exit;
}

/// DECODAGES //////////

function get_id($file) {
	$retour = substr($file, 0, 14);
	return $retour;
}

function decode_id($id) {
	$retour = array(
		'annee' => substr($id, 0, 4),
		'mois' => substr($id, 4, 2),
		'jour' => substr($id, 6, 2),
		'heure' => substr($id, 8, 2),
		'minutes' => substr($id, 10, 2),
		'secondes' => substr($id, 12, 2)
		);
	return $retour;
}

// used sometimes, like in the email that is sent.
function get_blogpath($id, $titre) {
	$date = decode_id($id);
	$path = $GLOBALS['racine'].'?d='.$date['annee'].'/'.$date['mois'].'/'.$date['jour'].'/'.$date['heure'].'/'.$date['minutes'].'/'.$date['secondes'].'-'.titre_url($titre);
	return $path;
}

function article_anchor($id) {
	$anchor = 'id'.substr(md5($id), 0, 6);
	return $anchor;
}

function traiter_tags($tags) {
	$tags_array = explode(',' , trim($tags, ','));
	$tags_array = array_unique(array_map('trim', $tags_array));
	sort($tags_array);
	return implode(', ' , $tags_array);
}

// tri un tableau non pas comme "sort()" sur l’ID, mais selon une sous clé d’un tableau.
function tri_selon_sous_cle($table, $cle) {
	foreach ($table as $key => $item) {
		 $ss_cles[$key] = $item[$cle];
	}
	if (isset($ss_cles)) {
		array_multisort($ss_cles, SORT_DESC, $table);
	}
	return $table;
}


function get_ip() {
	return (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']) : htmlspecialchars($_SERVER['REMOTE_ADDR']);
}


function check_session() {
	if (USE_IP_IN_SESSION == 1) {
		$ip = get_ip();
	} else {
		$ip = date('m');
	}
	@session_start();
	ini_set('session.cookie_httponly', TRUE);

	// generate hash for cookie
	$newUID = hash('sha256', USER_PWHASH.USER_LOGIN.md5($_SERVER['HTTP_USER_AGENT'].$ip));

	// check old cookie  with newUID
	if (isset($_COOKIE['BT-admin-stay-logged']) and $_COOKIE['BT-admin-stay-logged'] == $newUID) {
		$_SESSION['user_id'] = md5($newUID);
		session_set_cookie_params(365*24*60*60); // set new expiration time to the browser
		session_regenerate_id(true);  // Send cookie
		// Still logged in, return
		return TRUE;
	} else {
		return FALSE;
	}

	// no "stay-logged" cookie : check session.
	if ( (!isset($_SESSION['user_id'])) or ($_SESSION['user_id'] != USER_LOGIN.hash('sha256', USER_PWHASH.$_SERVER['HTTP_USER_AGENT'].$ip)) ) {
		return FALSE;
	} else {
		return TRUE;
	}
}


// This will look if session expired and kill it, otherwise restore it
function operate_session() {
	if (check_session() === FALSE) { // session is not good
		fermer_session(); // destroy it
	} else {
		// Restore data lost if possible
		foreach($_SESSION as $key => $value){
			if(substr($key, 0, 8) === 'BT-post-'){
				$_POST[substr($key, 8)] = $value;
				unset($_SESSION[$key]);
			}
		}
		return TRUE;
	}
}

function fermer_session() {
	unset($_SESSION['nom_utilisateur'], $_SESSION['user_id']);
	setcookie('BT-admin-stay-logged', NULL);
	session_destroy(); // destroy session
	// Saving server-side the possible lost data (writing article for example)
	session_start();
	session_regenerate_id(true); // change l'ID au cas ou
	foreach($_POST as $key => $value){
		$_SESSION['BT-post-'.$key] = $value;
	}

	if (strrpos($_SERVER['REQUEST_URI'], '/logout.php') != strlen($_SERVER['REQUEST_URI']) - strlen('/logout.php')) {
		$_SESSION['BT-saved-url'] = $_SERVER['REQUEST_URI'];
	}
	redirection('auth.php');
	exit();
}

// Code from Shaarli. Generate an unique sess_id, usable only once.
function new_token() {
	$rnd = sha1(uniqid('',true).mt_rand());  // We generate a random string.
	$_SESSION['tokens'][$rnd]=1;  // Store it on the server side.
	return $rnd;
}

// Tells if a token is ok. Using this function will destroy the token.
// true=token is ok.
function check_token($token) {
	if (isset($_SESSION['tokens'][$token])) {
		unset($_SESSION['tokens'][$token]); // Token is used: destroy it.
		return true; // Token is ok.
	}
	return false; // Wrong token, or already used.
}


function remove_url_param($param) {
	$msg_param_to_trim = (isset($_GET[$param])) ? '&'.$param.'='.$_GET[$param] : '';
	$query_string = str_replace($msg_param_to_trim, '', $_SERVER['QUERY_STRING']);
	return $query_string;
}


// Having a comment ID, sends emails to the other comments that are subscriben to the same article.
function send_emails($id_comment) {
	// retreive from DB: article_id, article_title, author_name, author_email
	$article_id = get_entry($GLOBALS['db_handle'], 'commentaires', 'bt_article_id', $id_comment, 'return');
	$article_title = get_entry($GLOBALS['db_handle'], 'articles', 'bt_title', $article_id, 'return');
	$comm_author = get_entry($GLOBALS['db_handle'], 'commentaires', 'bt_author', $id_comment, 'return');
	$comm_author_email = get_entry($GLOBALS['db_handle'], 'commentaires', 'bt_email', $id_comment, 'return');

	// retreiving all subscriben email, except that has just been posted.
	$liste_comments = array();
	try {
		$query = "SELECT DISTINCT bt_email FROM commentaires WHERE bt_statut=1 AND bt_article_id=? AND bt_email!=? AND bt_subscribe=1 ORDER BY bt_id";
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute(array($article_id, $comm_author_email));
		$liste_comments = $req->fetchAll(PDO::FETCH_ASSOC);
	} catch (Exception $e) {
		die('Erreur : '.$e->getMessage());
	}

	// filter empty emails
	$to_send_mail = array();
	foreach ($liste_comments as $comment) {
		if (!empty($comment['bt_email'])) {
			$to_send_mail[] = $comment['bt_email'];
		}
	}
	unset($liste_comments);
	if (empty($to_send_mail)) {
		return TRUE;
	}

	$subject = 'New comment on "'.$article_title.'" - '.$GLOBALS['nom_du_site'];
	$headers  = 'MIME-Version: 1.0'."\r\n".'Content-type: text/html; charset="UTF-8"'."\r\n";
	$headers .= 'From: no.reply_'.$GLOBALS['email']."\r\n".'X-Mailer: BlogoText - PHP/'.phpversion();

	// send emails
	foreach ($to_send_mail as $mail) {
		$unsublink = get_blogpath($article_id, '').'&amp;unsub=1&amp;mail='.base64_encode($mail).'&amp;article='.$article_id;
		$message = '<html>';
		$message .= '<head><title>'.$subject.'</title></head>';
		$message .= '<body><p>A new comment by <b>'.$comm_author.'</b> has been posted on <b>'.$article_title.'</b> form '.$GLOBALS['nom_du_site'].'.<br/>';
		$message .= 'You can see it by following <a href="'.get_blogpath($article_id, '').'#'.article_anchor($id_comment).'">this link</a>.</p>';
		$message .= '<p>To unsubscribe from the comments on that post, you can follow this link:<br/><a href="'.$unsublink.'">'.$unsublink.'</a>.</p>';
		$message .= '<p>To unsubscribe from the comments on all the posts, follow this link:<br/> <a href="'.$unsublink.'&amp;all=1">'.$unsublink.'&amp;all=1</a>.</p>';
		$message .= '<p>Also, do not reply to this email, since it is an automatic generated email.</p><p>Regards</p></body>';
		$message .= '</html>';
		mail($mail, $subject, $message, $headers);
	}
	return TRUE;
}

// Unsubscribe from comments subscription via email
function unsubscribe($email_b64, $article_id, $all) {
	$email = base64_decode($email_b64);
	try {
		if ($all == 1) {
			// update all comments having $email
			$query = "UPDATE commentaires SET bt_subscribe=0 WHERE bt_email=?";
			$array = array($email);
		} else {
			// update all comments having $email on $article
			$query = "UPDATE commentaires SET bt_subscribe=0 WHERE bt_email=? AND bt_article_id=?";
			$array = array($email, $article_id);
		}
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute($array);
		return TRUE;
	} catch (Exception $e) {
		die('Erreur BT 89867 : '.$e->getMessage());
	}
	return FALSE;
}

/* search query parsing (operators, exact matching, etc) */
function parse_search($q) {
	if (preg_match('#^\s?"[^"]*"\s?$#', $q)) { // exact match
		$array_q = array('%'.str_replace('"', '', $q).'%');
	}
	else { // multiple words matchs
		$array_q = explode(' ', trim($q));
		foreach ($array_q as $i => $entry) {
			$array_q[$i] = '%'.$entry.'%';
		}
	}
	// uniq + reindex
	return array_values(array_unique($array_q));
}

/* for testing/dev purpose: shows a variable. */
function debug($data) {
	header('Content-Type: text/html; charset=utf-8');
	echo '<pre>';
	print_r($data);
	die;
}

/* remove the folders "." and ".." from the list of files returned by scandir(). */
function rm_dots_dir($array) {
	if (($key = array_search('..', $array)) !== FALSE) { unset($array[$key]); }
	if (($key = array_search('.', $array)) !== FALSE) { unset($array[$key]); }
	return ($array);
}

