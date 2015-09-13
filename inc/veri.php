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

function valider_form_commentaire($commentaire, $mode) {
	$erreurs = array();
	if (!strlen(trim($commentaire['bt_author']))) {
		$erreurs[] = $GLOBALS['lang']['err_comm_auteur'];
	}
	if (!empty($commentaire['bt_email']) or $GLOBALS['require_email'] == 1) { // if email is required, or is given, it must be valid
		if (!preg_match('#^[-\w!%+~\'*"\[\]{}.=]+@[\w.-]+\.[a-zA-Z]{2,6}$#i', trim($commentaire['bt_email'])) ) {
			$erreurs[] = $GLOBALS['lang']['err_comm_email'] ;
		}
	}
	if (!strlen(trim($commentaire['bt_content'])) or $commentaire['bt_content'] == "<p></p>") { // comment may not be empty
		$erreurs[] = $GLOBALS['lang']['err_comm_contenu'];
	}
	if ( !preg_match('/\d{14}/',$commentaire['bt_article_id']) ) { // comment has to be on a valid article_id
		$erreurs[] = $GLOBALS['lang']['err_comm_article_id'];
	}

	if (trim($commentaire['bt_webpage']) != "") { // given url has to be valid
		if (!preg_match('#^(https?://[\S]+)[a-z]{2,6}[-\#_\w?%*:.;=+\(\)/&~$,]*$#', trim($commentaire['bt_webpage'])) ) {
			$erreurs[] = $GLOBALS['lang']['err_comm_webpage'];
		}
	}
	if ($mode != 'admin') { // if public : tests captcha as well
		$ua = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
		if ($_POST['_token'] != sha1($ua.$_POST['captcha'].$GLOBALS['salt']) ) {
			$erreurs[] = $GLOBALS['lang']['err_comm_captcha'];
		}
	} else { // mode admin : test token
		if (!( isset($_POST['token']) and check_token($_POST['token'])) ) {
			$erreurs[] = $GLOBALS['lang']['err_wrong_token'];
		}
	}
	return $erreurs;
}

function valider_form_commentaire_ajax($commentaire) {
	$erreurs = array();
	if ( !is_numeric($commentaire) ) { // comment has to be on a valid ID
		$erreurs[] = $GLOBALS['lang']['err_comm_article_id'];
	}
	// test token
	if (!( isset($_POST['token']) and check_token($_POST['token'])) ) {
		$erreurs[] = $GLOBALS['lang']['err_wrong_token'];
	}
	return $erreurs;
}

function valider_form_billet($billet) {
	$date = decode_id($billet['bt_id']);
	$erreurs = array();
	if (isset($_POST['supprimer']) and !(isset($_POST['token']) and check_token($_POST['token'])) ) {
		$erreurs[] = $GLOBALS['lang']['err_wrong_token'];
	}
	if (!strlen(trim($billet['bt_title']))) {
		$erreurs[] = $GLOBALS['lang']['err_titre'];
	}
	if (!strlen(trim($billet['bt_content']))) {
		$erreurs[] = $GLOBALS['lang']['err_contenu'];
	}
	if (!preg_match('/\d{4}/',$date['annee'])) {
		$erreurs[] = $GLOBALS['lang']['err_annee'];
	}
	if ( (!preg_match('/\d{2}/',$date['mois'])) or ($date['mois'] > '12') ) {
		$erreurs[] = $GLOBALS['lang']['err_mois'];
	}
	if ( (!preg_match('/\d{2}/',$date['jour'])) or ($date['jour'] > date('t', mktime(0, 0, 0, $date['mois'], 1, $date['annee'])))  ) {
		$erreurs[] = $GLOBALS['lang']['err_jour'];
	}
	if ( (!preg_match('/\d{2}/',$date['heure'])) or ($date['heure'] > 23) ) {
		$erreurs[] = $GLOBALS['lang']['err_heure'];
	}
	if ( (!preg_match('/\d{2}/',$date['minutes'])) or ($date['minutes'] > 59) ) {
		$erreurs[] = $GLOBALS['lang']['err_minutes'];
	}
	if ( (!preg_match('/\d{2}/',$date['secondes'])) or ($date['secondes'] > 59) ) {
		$erreurs[] = $GLOBALS['lang']['err_secondes'];
	}
	return $erreurs;
}

function valider_form_preferences() {
	$erreurs = array();
	if (!( isset($_POST['token']) and check_token($_POST['token'])) ) {
		$erreurs[] = $GLOBALS['lang']['err_wrong_token'];
	}
	if (!strlen(trim($_POST['auteur']))) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_auteur'];
	}
	if ($GLOBALS['require_email'] == 1) {
		if (!preg_match('#^[\w.+~\'*-]+@[\w.-]+\.[a-zA-Z]{2,6}$#i', trim($_POST['email']))) {
			$erreurs[] = $GLOBALS['lang']['err_prefs_email'] ;
		}
	}
	if (!preg_match('#^(https?://).*/$#', $_POST['racine'])) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_racine_slash'];
	}
	if (!strlen(trim($_POST['identifiant']))) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_identifiant'];
	}
	if ( ($_POST['identifiant']) !=$GLOBALS['identifiant'] and (!strlen($_POST['mdp'])) ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_id_mdp'];
	}
	if ( (!empty($_POST['mdp'])) and (hash_password($_POST['mdp'], $GLOBALS['salt']) != $GLOBALS['mdp']) ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_oldmdp'];
	}
	if ( (!empty($_POST['mdp'])) and (strlen($_POST['mdp_rep']) < '6') ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_mdp'];
	}
	if ( (empty($_POST['mdp_rep'])) xor (empty($_POST['mdp'])) ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_newmdp'] ;
	}
	return $erreurs;
}

function valider_form_fichier($fichier) {
	$erreurs = array();
	if (!( isset($_POST['token']) and check_token($_POST['token'])) ) {
		$erreurs[] = $GLOBALS['lang']['err_wrong_token'];
	}
	if (!isset($_POST['is_it_edit'])) { // si nouveau fichier, test sur fichier entrant

		if (isset($_FILES['fichier'])) {
			if (($_FILES['fichier']['error'] == UPLOAD_ERR_INI_SIZE) or ($_FILES['fichier']['error'] == UPLOAD_ERR_FORM_SIZE)) {
				$erreurs[] = 'Fichier trop gros';
			} elseif ($_FILES['fichier']['error'] == UPLOAD_ERR_PARTIAL) {
				$erreurs[] = 'dépot interrompu';
			} elseif ($_FILES['fichier']['error'] == UPLOAD_ERR_NO_FILE) {
				$erreurs[] = 'aucun fichier déposé';
			}
		}
		elseif (isset($_POST['url'])) {
			if ( empty($_POST['url']) ) {
				$erreurs[] = $GLOBALS['lang']['err_lien_vide'];
			}
		}

	} else { // on edit
		if ('' == $fichier['bt_filename']) {
			$erreurs[] = 'nom de fichier invalide';
		}
	}
	return $erreurs;
}

function valider_form_rss() {
	$erreurs = array();
	if (!( isset($_POST['token']) and check_token($_POST['token'])) ) {
		$erreurs[] = $GLOBALS['lang']['err_wrong_token'];
	}
	// on feed add: URL needs to be valid, not empty, and must not already be in DB
	if (isset($_POST['add-feed'])) {
		if (empty($_POST['add-feed'])) {
			$erreurs[] = $GLOBALS['lang']['err_lien_vide'];
		}
		if (!preg_match('#^(https?://[\S]+)[a-z]{2,6}[-\#_\w?%*:.;=+\(\)/&~$,]*$#', trim($_POST['add-feed'])) ) {
			$erreurs[] = $GLOBALS['lang']['err_comm_webpage'];
		}
		if (array_key_exists($_POST['add-feed'], $GLOBALS['liste_flux'])) {
			$erreurs[] = $GLOBALS['lang']['err_feed_exists'];
		}

	}

	elseif (isset($_POST['mark-as-read'])) {
		if ( !(in_array($_POST['mark-as-read'], array('all', 'site', 'post', 'folder', 'postlist'))) ) {
			$erreurs[] = $GLOBALS['lang']['err_feed_wrong_param'];
		}
	}

	return $erreurs;
}

function valider_form_link() {
	$erreurs = array();
	if (!( isset($_POST['token']) and check_token($_POST['token'])) ) {
		$erreurs[] = $GLOBALS['lang']['err_wrong_token'];
	}

	if (!preg_match('#^\d{14}$#', $_POST['bt_id'])) {
		$erreurs[] = 'Erreur id.';
	}
	return $erreurs;
}

function valider_form_maintenance() {
	$erreurs = array();
	$token = isset($_POST['token']) ? $_POST['token'] : (isset($_GET['token']) ? $_GET['token'] : 'false');
	if (!check_token($token)) {
		$erreurs[] = $GLOBALS['lang']['err_wrong_token'];
	}
	return $erreurs;
}

?>
