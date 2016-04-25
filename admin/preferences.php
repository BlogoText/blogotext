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

$begin = microtime(TRUE);
define('BT_ROOT', '../');

require_once '../inc/inc.php';

operate_session();

$erreurs_form = array();

if (isset($_POST['_verif_envoi'])) {
	$erreurs_form = valider_form_preferences();
	if (empty($erreurs_form)) {
		if ( (fichier_user() === TRUE) and (fichier_prefs() === TRUE) ) {
			redirection(basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_prefs_maj');
			exit();
		}
	}
}



afficher_html_head($GLOBALS['lang']['preferences']);
	echo '<div id="header">'."\n";
		echo '<div id="top">'."\n";
		afficher_msg();
		afficher_topnav($GLOBALS['lang']['preferences']);
		echo '</div>'."\n";
	echo '</div>'."\n";
echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

if (isset($_GET['test_captcha'])) {
	afficher_form_captcha();
} else {
	afficher_form_prefs($erreurs_form);
}


/*
	FORMULAIRE DE TEST DU CAPTCHA
*/
function afficher_form_captcha() {
	if (!empty($_SESSION['freecap_word_hash']) and !empty($_POST['word'])) {
		if (sha1(strtolower($_POST['word'])) == $_SESSION['freecap_word_hash']) {
			$_SESSION['freecap_word_hash'] = false;
			$word_ok = "yes";
		} else {
			$word_ok = "no";
		}
	} else {
		$word_ok = FALSE;
	}
	echo '<form id="preferences-captcha" action="'.basename($_SERVER['SCRIPT_NAME']).'?test_captcha" method="post" class="bordered-formbloc" >'."\n";
	echo '<div role="group" class="pref">';
	echo '<div class="form-legend">'.legend('Captcha', 'legend-config').'</div>'."\n";
	echo '<p>';
	if ($word_ok !== FALSE) {
		if ($word_ok == "yes") {
			echo '<b style="color: green;">you got the word correct, rock on.</b>';
		} else {
			echo '<b style="color: red;">sorry, that\'s not the right word, try again.</b>';
		}
	}
	echo '</p>';
	echo '<p><img src="../inc/freecap/freecap.php" id="freecap" alt="freecap"/></p>'."\n";
	echo '<p>If you can\'t read the word, <a href="#" onclick="new_freecap();return false;">click here to change image</a></p>'."\n";
	echo '<p>word above : <input type="text" class="text" name="word" /></p>'."\n";
	echo '<input class="submit blue-square" type="submit" name="valider" value="'.$GLOBALS['lang']['envoyer'].'" />'."\n";
	echo '</div>';
	echo '</form>'."\n";

}


footer($begin);

