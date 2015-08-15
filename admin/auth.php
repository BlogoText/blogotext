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

if ( !file_exists('../config/user.php') || !file_exists('../config/prefs.php') ) {
	header('Location: install.php');
}

$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

$max_attemps = 10; // max attempts before blocking login page
$wait_time = 30;   // time to wait before unblocking login page, in minutes

// Acces LOG
if (isset($_POST['nom_utilisateur'])) {
	// IP
	$ip = htmlspecialchars($_SERVER["REMOTE_ADDR"]);
	// Proxy IPs, if exists.
	$ip .= (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? '_'.htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']) : '';
	$curent_time = date('r'); // heure : Wed, 18 Jan 2012 20:42:12 +0100
	$data = '<?php die(\'no.\'); // '.$curent_time.' - '.$ip.' - '.((check_session()===TRUE) ? 'login succes' : 'login fail') ."?> \n";
	file_put_contents($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_config'].'/'.'xauthlog.php', $data, FILE_APPEND);
}


if (check_session() === TRUE) { // return to index if session is already open.
	header('Location: index.php');
	exit;
}

// Auth checking :
if (isset($_POST['_verif_envoi']) and valider_form() === TRUE) { // OK : getting in.
	if ($GLOBALS['use_ip_in_session'] == 1) {
		$ip = get_real_ip();
	} else {
		$ip = date('m'); // make session expire at least once a month, disregarding IP changes.
	}
	$_SESSION['user_id'] = $_POST['nom_utilisateur'].hash_password($_POST['mot_de_passe'], $GLOBALS['salt']).md5($_SERVER['HTTP_USER_AGENT'].$ip); // set special hash
	usleep(100000); // 100ms sleep to avoid bruteforce

	if (!empty($_POST['stay_logged'])) { // if user wants to stay logged
		$user_id = hash_password($GLOBALS['mdp'].$GLOBALS['identifiant'].$GLOBALS['salt'], md5($_SERVER['HTTP_USER_AGENT'].$ip.$GLOBALS['salt']));
		setcookie('BT-admin-stay-logged', $user_id, time()+365*24*60*60, null, null, false, true);
		session_set_cookie_params(365*24*60*60); // set expiration time to the browser
	} else {
		$_SESSION['stay_logged_mode'] = 0;
		session_regenerate_id(true);
	}

	fichier_ip();

	// Handle saved data/URL redirect if POST request made
	$location = 'index.php';
	if(isset($_SESSION['BT-saved-url'])){
		$location = $_SESSION['BT-saved-url'];
		unset($_SESSION['BT-saved-url']);
	}
	if(isset($_SESSION['BT-post-token'])){
		// The login was right, so we give a token because the previous one expired with the session
		$_SESSION['BT-post-token'] = new_token();
	}

	header('Location: '.$location);

} else { // On sort…
		// …et affiche la page d'auth
		afficher_html_head('Identification');
		echo '<div id="axe">'."\n";
		echo '<div id="pageauth">'."\n";
		echo '<h1>'.$GLOBALS['nom_application'].'</h1>'."\n";
		echo '<form method="post" action="auth.php">'."\n";
		echo '<div id="auth">'."\n";
		echo '<p><label for="user">'.ucfirst($GLOBALS['lang']['label_dp_identifiant']).'</label><input class="text" type="text"  autocomplete="off" id="user" name="nom_utilisateur" placeholder="John Doe" value="" /></p>'."\n";
		echo '<p><label for="password">'.ucfirst($GLOBALS['lang']['label_dp_motdepasse']).'</label><input class="text" id="password" type="password" placeholder="••••••••••••" name="mot_de_passe" value="" /></p>'."\n";
		if (isset($GLOBALS['connexion_captcha']) and ($GLOBALS['connexion_captcha'] == "1")) {
			echo '<p><label for="word">'.ucfirst($GLOBALS['lang']['label_dp_word_captcha']).'</label><input class="text" type="text" id="word" name="word" value="" /></p>'."\n";
			echo '<p><a href="#" onclick="new_freecap();return false;" title="'.$GLOBALS['lang']['label_dp_changer_captcha'].'"><img src="../inc/freecap/freecap.php" id="freecap" alt="captcha"></a></p>'."\n";
		}

		echo '<p><label for="stay_logged">'.$GLOBALS['lang']['label_stay_logged'].'</label><input type="checkbox" id="stay_logged" name="stay_logged" checked /></p>'."\n";
		echo '<input class="blue-square" type="submit" name="submit" value="'.$GLOBALS['lang']['connexion'].'" />'."\n";
		echo '<input type="hidden" name="_verif_envoi" value="1" />'."\n";
		echo '</div>'."\n";
		echo '</form>'."\n";
}

function valider_form() {
	$mot_de_passe_ok = $GLOBALS['mdp'].$GLOBALS['identifiant'];
	$mot_de_passe_essai = hash_password($_POST['mot_de_passe'], $GLOBALS['salt']).$_POST['nom_utilisateur'];
	// first test password
	if ($mot_de_passe_essai != $mot_de_passe_ok) {
		return FALSE;
	}
	// then test captcha
	if (isset($GLOBALS['connexion_captcha']) and ($GLOBALS['connexion_captcha'] == "1")) { // si captcha activé
		if ( empty($_SESSION['freecap_word_hash']) or empty($_POST['word']) or (sha1(strtolower($_POST['word'])) != $_SESSION['freecap_word_hash']) ) {
			return FALSE;
		}
		$_SESSION['freecap_word_hash'] = FALSE; // reset captcha word
	}

	return TRUE;
}

echo "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
footer();
?>
