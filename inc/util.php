<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2015 Timo Van Neerden <timo@neerden.eu>
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

function hash_password($text, $salt) {
	$out = hash("sha512", $text.$salt);	// PHP 5
	return $out;
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



function check_session() {
	$ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']) : htmlspecialchars($_SERVER['REMOTE_ADDR']);
	@session_start();
	ini_set('session.cookie_httponly', TRUE);
	// use a cookie to remain logged in
	$user_id = hash_password($GLOBALS['mdp'].$GLOBALS['identifiant'].$GLOBALS['salt'], md5($_SERVER['HTTP_USER_AGENT'].$ip.$GLOBALS['salt']));

	if (isset($_COOKIE['BT-admin-stay-logged']) and $_COOKIE['BT-admin-stay-logged'] == $user_id) {
		$_SESSION['user_id'] = md5($user_id);
		session_set_cookie_params(365*24*60*60); // set new expiration time to the browser
		session_regenerate_id(true);  // Send cookie
		return TRUE;
	}
	if ( (!isset($_SESSION['user_id'])) or ($_SESSION['user_id'] != $GLOBALS['identifiant'].$GLOBALS['mdp'].md5($_SERVER['HTTP_USER_AGENT'].$ip)) ) {
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


// A partir d'un commentaire posté, détermine les emails
// à qui envoyer la notification de nouveau commentaire.
function send_emails($id_comment) {
	// disposant de l'email d'un commentaire, on détermine l'article associé, le titre, l’auteur du comm et l’email de l’auteur du com.
	$article = get_entry($GLOBALS['db_handle'], 'commentaires', 'bt_article_id', $id_comment, 'return');
	$article_title = get_entry($GLOBALS['db_handle'], 'articles', 'bt_title', $article, 'return');
	$comm_author = get_entry($GLOBALS['db_handle'], 'commentaires', 'bt_author', $id_comment, 'return');
	$comm_author_email = get_entry($GLOBALS['db_handle'], 'commentaires', 'bt_email', $id_comment, 'return');

	// puis la liste de tous les commentaires de cet article
	$liste_commentaires = array();
	try {
		$query = "SELECT bt_email,bt_subscribe,bt_id FROM commentaires WHERE bt_statut=1 AND bt_article_id=? ORDER BY bt_id";
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute(array($article));
		$liste_commentaires = $req->fetchAll(PDO::FETCH_ASSOC);
	} catch (Exception $e) {
		die('Erreur : '.$e->getMessage());
	}

	// Récupérre la liste (sans doublons) des emails des commentateurs, ainsi que leurs souscription à la notification d'email.
	// si plusieurs comm avec la même email, alors seul le dernier est pris en compte.
	// si l’auteur même du commentaire est souscrit, il ne recoit pas l’email de son propre commentaire.
	$emails = array();
	foreach ($liste_commentaires as $i => $comment) {
		if (!empty($comment['bt_email']) and ($comm_author_email != $comment['bt_email'])) {
			$emails[$comment['bt_email']] = $comment['bt_subscribe'].'-'.get_id($comment['bt_id']);
		}
	}
	// ne conserve que la liste des mails dont la souscription est demandée (= 1)
	$to_send_mail = array();
	foreach ($emails as $mail => $is_subscriben) {
		if ($is_subscriben[0] == '1') { // $is_subscriben is seen as a array of chars here, first char is 0 or 1 for subscription.
			$to_send_mail[$mail] = substr($is_subscriben, -14);
		}
	}
	$subject = 'New comment on "'.$article_title.'" - '.$GLOBALS['nom_du_site'];
	$headers  = 'MIME-Version: 1.0'."\r\n".'Content-type: text/html; charset="UTF-8"'."\r\n";
	$headers .= 'From: no.reply_'.$GLOBALS['email']."\r\n".'X-Mailer: BlogoText - PHP/'.phpversion();

	// for debug
	//header('Content-type: text/html; charset=UTF-8');
	//die(($to. $subject. $message. $headers));
	//echo '<pre>';print_r($emails);
	//echo '<pre>';print_r($to_send_mail);
	//die();
	// envoi les emails.
	foreach ($to_send_mail as $mail => $is_subscriben) {
		$comment = substr($is_subscriben, -14);
		$unsublink = get_blogpath($article, '').'&amp;unsub=1&amp;comment='.$comment.'&amp;mail='.sha1($mail);
		$message = '<html>';
		$message .= '<head><title>'.$subject.'</title></head>';
		$message .= '<body><p>A new comment by <b>'.$comm_author.'</b> has been posted on <b>'.$article_title.'</b> form '.$GLOBALS['nom_du_site'].'.<br/>';
		$message .= 'You can see it by following <a href="'.get_blogpath($article, '').'#'.article_anchor($id_comment).'">this link</a>.</p>';
		$message .= '<p>To unsubscribe from the comments on that post, you can follow this link: <a href="'.$unsublink.'">'.$unsublink.'</a>.</p>';
		$message .= '<p>To unsubscribe from the comments on all the posts, follow this link: <a href="'.$unsublink.'&amp;all=1">'.$unsublink.'&amp;all=1</a>.</p>';
		$message .= '<p>Also, do not reply to this email, since it is an automatic generated email.</p><p>Regards.</p></body>';
		$message .= '</html>';
		mail($mail, $subject, $message, $headers);
	}
	return TRUE;
}

// met à 0 la subscription d'un auteur à un article. (met à 0 celui dans le dernier commentaire qu'il a posté sur un article)
function unsubscribe($file_id, $email_sha, $all) {
	// récupération de quelques infos sur le commentaire
	try {
		$query = "SELECT bt_email,bt_subscribe,bt_id FROM commentaires WHERE bt_id=?";
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute(array($file_id));
		$result = $req->fetchAll(PDO::FETCH_ASSOC);

	} catch (Exception $e) {
		die ('Erreur BT #12725 : '. $e->getMessage());
	}
	try {
		if (!empty($result[0])) {
			$comment = $result[0];
			// (le test SHA1 sur l'email sert à vérifier que c'est pas un lien forgé pouvant désinscrire une email de force
			if ( ($email_sha == sha1($comment['bt_email'])) and ($comment['bt_subscribe'] == 1) ) {
				if ($all == 1) {
					// mettre à jour de tous les commentaire qui ont la même email.
					$query = "UPDATE commentaires SET bt_subscribe=0 WHERE bt_email=?";
					$array = $comment['bt_email'];
				} else {
					// mettre à jour le commentaire
					$query = "UPDATE commentaires SET bt_subscribe=0 WHERE bt_id=?";
					$array = $comment['bt_id'];
				}
				$req = $GLOBALS['db_handle']->prepare($query);
				$req->execute(array($array));
				return TRUE;
			}
			elseif ($comment['bt_subscribe'] == 0) {
				return TRUE;
			}
		}
	} catch (Exception $e) {
		die('Erreur BT 89867 : '.$e->getMessage());
	}
	return FALSE; // si il y avait été TRUE, on serait déjà sorti de la fonction
}

/* search query parsing (operators, exact matching, etc) */
function parse_search($q) {
	if (preg_match('#^\s?"[^"]*"\s?$#', $q)) { // exact match
		$txt_query = array('%'.str_replace('"', '', $q).'%'); 
	}
	else { // multiple words matchs
		$txt_query = explode(' ', trim($q));
		foreach ($txt_query as $i => $entry) {
			$txt_query[$i] = '%'.$entry.'%';
		}
	}
	return $txt_query;
}

/* for testing/dev purpose: shows a variable. */
function debug($data) {
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

