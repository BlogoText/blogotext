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

// install or reinstall with same config ?
if ( file_exists('../config/mysql.ini') and file_get_contents('../config/mysql.ini') == '' ) {
	$step3 = TRUE;
} else {
	$step3 = FALSE;
}

// install is already done
if ( (file_exists('../config/user.ini')) and (file_exists('../config/prefs.php')) and $step3 === FALSE) {
	header('Location: auth.php');
	exit;
}

// some constants definition
define('BT_ROOT', '../');
define('DISPLAY_PHP_ERRORS', '-1');
$GLOBALS['fuseau_horaire'] = 'UTC';


if (isset($_GET['l'])) {
	$lang = $_GET['l'];
	if ($lang == 'fr' or $lang == 'en') {
		$GLOBALS['lang'] = $lang;
	} else {
		$GLOBALS['lang'] = 'fr';
	}

}

require_once BT_ROOT.'/inc/inc.php';

if (isset($_GET['s']) and is_numeric($_GET['s'])) {
	$GLOBALS['step'] = $_GET['s'];
} else {
	$GLOBALS['step'] = '1';
}

if ($GLOBALS['step'] == '1') {
	// LANGUE
	if (isset($_POST['verif_envoi_1'])) {
		if ($err_1 = valid_install_1()) {
				afficher_form_1($err_1);
		} else {
			redirection('install.php?s=2&l='.$_POST['langue']);
		}
	} else {
		afficher_form_1();
	}
}

elseif ($GLOBALS['step'] == '2') {
	// ID + MOT DE PASSE
	if (isset($_POST['verif_envoi_2'])) {
		if ($err_2 = valid_install_2()) {
				afficher_form_2($err_2);
		} else {
			$config_dir = '../config';
			creer_dossier($config_dir, 1);
			creer_dossier('../'.DIR_IMAGES, 0);
			creer_dossier('../'.DIR_DOCUMENTS, 0);
			creer_dossier('../'.DIR_DATABASES, 1);
			fichier_user();
			import_ini_file($config_dir.'/user.ini');

			traiter_install_2();
			redirection('install.php?s=3&l='.$_POST['langue']);
		}
	} else {
		afficher_form_2();
	}

} elseif ($GLOBALS['step'] == '3') {
	// CHOIX DB
	if (isset($_POST['verif_envoi_3'])) {
		if ($err_3 = valid_install_3()) {
			afficher_form_3($err_3);
		} else {
			if (isset($_POST['sgdb']) and $_POST['sgdb'] == 'mysql') {
				fichier_mysql('mysql');
			}
			else {
				fichier_mysql('sqlite');
			}
			traiter_install_3();
			if (!file_exists('../config/config-advanced.ini')) {
				fichier_adv_conf(); // is done right after DB init
			}
			redirection('auth.php');
		}
	} else {
		afficher_form_3();
	}
}

// affiche le form de choix de langue
function afficher_form_1($erreurs='') {
	afficher_html_head('Install');
	echo '<div id="axe">'."\n";
	echo '<div id="pageauth">'."\n";
	echo '<h1>'.BLOGOTEXT_NAME.'</h1>'."\n";
	echo '<h1 id="step">Bienvenue / Welcome</h1>'."\n";
	echo erreurs($erreurs);

	$conferrors = array();
	// check PHP version
	if (version_compare(PHP_VERSION, MINIMAL_PHP_REQUIRED_VERSION, '<')) {
		$conferrors[] = "\t".'<li>Your PHP Version is '.PHP_VERSION.'. BlogoText requires '.MINIMAL_PHP_REQUIRED_VERSION.'.</li>'."\n";
	}
	// pdo_sqlite and pdo_mysql (minimum one is required)
	if (!extension_loaded('pdo_sqlite') and !extension_loaded('pdo_mysql') ) {
		$conferrors[] = "\t".'<li>Neither <b>pdo_sqlite</b> or <b>pdo_mysql</b> PHP-modules are loaded. Blogotext needs at least one.</li>'."\n";
	}
	// check directory readability
	if (!is_writable('../') ) {
		$conferrors[] = "\t".'<li>Blogotext has no write rights (chmod of home folder must be 644 at least, 777 recommended).</li>'."\n";
	}
	if (!empty($conferrors)) {
		echo '<ol class="erreurs">'."\n";
		echo implode($conferrors, '');
		echo '</ol>'."\n";
		echo '<p classe="erreurs">Installation aborded.</p>'."\n";
		echo '</div>'."\n".'</div>'."\n".'</html>';
		die;
	}

	echo '<form method="post" action="install.php">'."\n";
	echo '<div id="install">'."\n";
	echo '<p>';
	form_langue_install('Choisissez votre langue / Choose your language: ');
	echo hidden_input('verif_envoi_1', '1');
	echo '</p>';
	echo '<button class="submit button-submit" type="submit" name="enregistrer">Ok</button>'."\n";
	echo '<div>'."\n";
	echo '</form>'."\n";
}

// form pour login + mdp + url
function afficher_form_2($erreurs='') {
	afficher_html_head('Install');
	echo '<div id="axe">'."\n";
	echo '<div id="pageauth">'."\n";
	echo '<h1>'.BLOGOTEXT_NAME.'</h1>'."\n";
	echo '<h1 id="step">'.$GLOBALS['lang']['install'].'</h1>'."\n";
	echo erreurs($erreurs);
	echo '<form method="post" action="install.php?s='.$GLOBALS['step'].'&amp;l='.$GLOBALS['lang']['id'].'">'."\n".'<div id="erreurs_js" class="erreurs"></div>'."\n";
	echo '<div id="install">'."\n";
	echo '<p>';
	echo '<label for="identifiant">'.$GLOBALS['lang']['install_id'].' </label><input type="text" name="identifiant" id="identifiant" size="30" value="" class="text" placeholder="John Doe" required />'."\n";
	echo '</p>'."\n";
	echo '<p>';
	echo '<label for="mdp">'.$GLOBALS['lang']['install_mdp'].' </label><input type="password" name="mdp" id="mdp" size="30" value="" class="text" autocomplete="off" placeholder="••••••••••••" required /><button type="button" class="unveilmdp" onclick="return revealpass(\'mdp\');"></button>'."\n";
	echo '</p>'."\n";
	$lien = str_replace(DIR_ADMIN.'/install.php', '', 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']);
	echo '<p>';
	echo '<label for="racine">'.$GLOBALS['lang']['pref_racine'].' </label><input type="text" name="racine" id="racine" size="30" value="'.$lien.'" class="text"  placeholder="'.$lien.'" required />'."\n";
	echo '</p>'."\n";
	echo hidden_input('comm_defaut_status', '1');
	echo hidden_input('langue', $GLOBALS['lang']['id']);
	echo hidden_input('verif_envoi_2', '1');
	echo '<button class="submit button-submit" type="submit" name="enregistrer">Ok</button>'."\n";
	echo '</div>'."\n";
	echo '</form>'."\n";
}


// form choix SGBD
function afficher_form_3($erreurs='') {

	afficher_html_head('Install');
	echo '<div id="axe">'."\n";
	echo '<div id="pageauth">'."\n";
	echo '<h1>'.BLOGOTEXT_NAME.'</h1>'."\n";
	echo '<h1 id="step">'.$GLOBALS['lang']['install'].'</h1>'."\n";
	echo erreurs($erreurs);
	echo '<form method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'?'.$_SERVER['QUERY_STRING'].'">'."\n";
	echo '<div id="install">'."\n";
	echo '<p><label>'.$GLOBALS['lang']['install_choose_sgdb'].'</label>';
	echo '<select id="sgdb" name="sgdb" onchange="show_mysql_form()">'."\n";
	if (extension_loaded('pdo_sqlite')) {
		echo "\t".'<option value="sqlite">SQLite</option>'."\n";
	}
	if (extension_loaded('pdo_mysql') ) {
		echo "\t".'<option value="mysql">MySQL</option>'."\n";
	}
	echo '</select></p>'."\n";

	echo '<div id="mysql_vars" style="display:none;">'."\n";
	if (extension_loaded('pdo_mysql') ) {
		echo '<p><label for="mysql_user">MySQL User: </label>
					<input type="text" id="mysql_user" name="mysql_user" size="30" value="" class="text" placeholder="mysql_user" /></p>'."\n";
		echo '<p><label for="mysql_password">MySQL Password: </label>
					<input type="password" id="mysql_password" name="mysql_passwd" size="30" value="" class="text" placeholder="••••••••••••" autocomplete="off" /><button type="button" class="unveilmdp" onclick="return revealpass(\'mysql_password\');"></button></p>'."\n";
		echo '<p><label for="mysql_db">MySQL Database: </label>
					<input type="text" id="mysql_db" name="mysql_db" size="30" value="" class="text" placeholder="db_blogotext" /></p>'."\n";
		echo '<p><label for="mysql_host">MySQL Host: </label>
					<input type="text" id="mysql_host" name="mysql_host" size="30" value="" class="text" placeholder="localhost" /></p>'."\n";
	}
	echo '</div>'."\n";

	echo hidden_input('langue', $GLOBALS['lang']['id']);
	echo hidden_input('verif_envoi_3', '1');
	echo '<button class="submit button-submit" type="submit" name="enregistrer">Ok</button>'."\n";

	echo '</div>'."\n";
	echo '</form>'."\n";

}

function traiter_install_2() {
	$config_dir = '../config';
	if (!is_file($config_dir.'/prefs.php')) fichier_prefs();
	fichier_mysql(FALSE); // create an empty file
}

function traiter_install_3() {
	import_ini_file(BT_ROOT.DIR_CONFIG.'/'.'mysql.ini');
	$GLOBALS['db_handle'] = open_base();
	$total_articles = liste_elements_count("SELECT count(ID) AS nbr FROM articles", array());
	if ($total_articles != 0) return;

	$time = time();
	if ($GLOBALS['db_handle']) {
		$first_post = array (
			'bt_id' => date('YmdHis', $time),
			'bt_date' => date('YmdHis', $time),
			'bt_title' => $GLOBALS['lang']['first_titre'],
			'bt_abstract' => $GLOBALS['lang']['first_edit'],
			'bt_content' => $GLOBALS['lang']['first_edit'],
			'bt_wiki_content' => $GLOBALS['lang']['first_edit'],
			'bt_keywords' => '',
			'bt_categories' => '',
			'bt_link' => '',
			'bt_notes' => '',
			'bt_statut' => '1',
			'bt_allow_comments' => '1'
		);
		$readme_post = array (
			'bt_notes' => '',
			'bt_link' => '',
			'bt_categories' => '',
			'bt_link' => '',
			'bt_id' => date('YmdHis', $time+2),
			'bt_date' => date('YmdHis', $time+2),
			'bt_title' => 'README / LISEZ-MOI',
			'bt_abstract' => 'Instructions / Instructions',
			'bt_content' => gzinflate(base64_decode('rVPLjtQwELzPV7T2MoDC7A+sVgIE0t5WYuHecXoSC8c9+JHs7NdwJELiB7htfoxuJwOzB+DCSGNZdruqq7qyuesoEmAgiNwTWB9TyCZZ9hH2HCB1coN7SkfgPRw5B6gdt7urOlxeb248cGhIyhgOgROZtNQcKET26EoxYIsKDJgSmk+xgtdyKtX3cuQcj1EfKcZVfR3IozSivBfY9NZfSB9OOK4u6+sdfIjWt+X23d0tGPaeSrf6ujCPVL/sOCYpqwps7Di7BgIJ1RH+CK8AY4eJBtnruxF92mlHN2kbwXNSPww1FdQ5gU0wWufEMCPIYmFHOFghKIYloWlTp5adSX3qQtGz2HjrFKIC3KeVfGmzKWhrf89s8R8afl7JfU99Tct8PI1QVNVkMEdVh2t7nkc5PYdRiA4HUr0t62rPhojGUIylvrgDB/TknujYbZamX/zH34K4rB/ZGgufMzn5Rx1xJOsiHHS6DiHOk8nBpnmChmBg8fo8kq/20rVcaBznqRVZv0sUTPfNdp4G8kk4nFDoQJTrPJcD56gp7ikp2hJM7nvBcwLAMVrZPn5bbXr8scTzrYecrLNRwqPNloDeaz41rvOXtRmKBzTaRzdPMsVWpulTtZA2NARLD/APPiCh8oBZ8aRSHVsAZGkCPZTsviHx0m9JYnfACFw722JiK296tOIuSZPYz5OzHCigau3R2/mr2hRQviPF/YvtJ0/PjdypGcs43m+tW810OH9XkZQlmUVgIm+LPs95IMxPdTTq3YMoPMmvwGAAU9SYk2jF36MoxtV5scmocb9MO33nJUAS8HnSsAtsyfZus/kJ')),
			'bt_wiki_content' => 'Once read, you should delete this post / Une fois que vous avez lu ceci, vous pouvez supprimer l\'article',
			'bt_keywords' => '',
			'bt_statut' => '0',
			'bt_allow_comments' => '0'
		);
		if (TRUE !== bdd_article($first_post, 'enregistrer-nouveau')) die('ERROR SQL posting first article..'); // billet "Mon premier article"
		bdd_article($readme_post, 'enregistrer-nouveau'); // billet "read me" avec les instructions // Assuming the 2nd possing will be good if the first was too.

		$link_ar = array(
				'bt_type' => 'link',
				'bt_id' => date('YmdHis', $time+1),
				'bt_content' => 'Blog du Hollandais Volant, développeur de Blogotext',
				'bt_wiki_content' => 'Blog du Hollandais Volant, développeur de BlogoText.',
				'bt_author' => 'BlogoText',
				'bt_title' => 'Le Hollandais Volant',
				'bt_link' => 'http://lehollandaisvolant.net/',
				'bt_tags' => 'blog, timo',
				'bt_statut' => '1'
			);
		$link_ar2 = array(
				'bt_type' => 'note',
				'bt_id' => date('YmdHis', $time+5),
				'bt_content' => 'Ceci est un lien privé. Vous seuls pouvez le voir.',
				'bt_wiki_content' => 'Ceci est un lien privé. Vous seul pouvez le voir.',
				'bt_author' => 'BlogoText',
				'bt_title' => 'Note : lien privé',
				'bt_link' => $GLOBALS['racine'].'index.php?mode=links&amp;id='.date('YmdHis', $time+5),
				'bt_tags' => '',
				'bt_statut' => '0'
			);
		bdd_lien($link_ar, 'enregistrer-nouveau'); // lien
		bdd_lien($link_ar2, 'enregistrer-nouveau'); // lien

		$comm_ar = array(
			'bt_type' => 'comment',
			'bt_id' => date('YmdHis', $time+6),
			'bt_article_id' => date('YmdHis', $time),
			'bt_content' => '<p>Ceci est un commentaire.</p>',
			'bt_wiki_content' => 'Ceci est un commentaire.',
			'bt_author' => 'Blogotext',
			'bt_link' => '',
			'bt_webpage' => 'http://lehollandaisvolant.net/blogotext/',
			'bt_email' => 'mail@example.com',
			'bt_subscribe' => '0',
			'bt_statut' => '1'
		);

		bdd_commentaire($comm_ar, 'enregistrer-nouveau'); // commentaire sur l’article

	}
}


function valid_install_1() {
	$erreurs = array();
	if (!strlen(trim($_POST['langue']))) {
		$erreurs[] = 'Vous devez choisir une langue / You have to choose a language';
	}
	return $erreurs;
}

function valid_install_2() {
	$erreurs = array();
	if (!strlen(trim($_POST['identifiant']))) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_identifiant'];
	}
	if (preg_match('#[=\'"\\\\]#iu', $_POST['identifiant'])) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_id_syntaxe'];
	}
	if ( (strlen($_POST['mdp']) < 6) ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_mdp'] ;
	}
	if ( !strlen(trim($_POST['racine'])) or !preg_match('#^(https?://).*/$#', $_POST['racine']) ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_racine'];
	} elseif (!preg_match('/^https?:\/\//', $_POST['racine'])) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_racine_http'];
	} elseif (!preg_match('/\/$/', $_POST['racine'])) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_racine_slash'];
	}
	return $erreurs;
}

function valid_install_3() {
	$erreurs = array();
	if ($_POST['sgdb'] == 'mysql') {

		if (!strlen(trim($_POST['mysql_user']))) {
			$erreurs[] = $GLOBALS['lang']['install_err_mysql_usr_empty'];
		}	
		if (!strlen(trim($_POST['mysql_passwd']))) {
			$erreurs[] = $GLOBALS['lang']['install_err_mysql_pss_empty'];
		}
		if (!strlen(trim($_POST['mysql_db']))) {
			$erreurs[] = $GLOBALS['lang']['install_err_mysql_dba_empty'];
		}	
		if (!strlen(trim($_POST['mysql_host']))) {
			$erreurs[] = $GLOBALS['lang']['install_err_mysql_hst_empty'];
		}

		if ( test_connection_mysql() == FALSE ) {
			$erreurs[] = $GLOBALS['lang']['install_err_mysql_connect'];
		}
	}
	return $erreurs;
}

function test_connection_mysql() {
	try {
		$options_pdo[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
		$db_handle = new PDO('mysql:host='.htmlentities($_POST['mysql_host'], ENT_QUOTES).';dbname='.htmlentities($_POST['mysql_db'], ENT_QUOTES), htmlentities($_POST['mysql_user'], ENT_QUOTES), htmlentities($_POST['mysql_passwd'], ENT_QUOTES), $options_pdo);
		return TRUE;
	} catch (Exception $e) {
		return FALSE;
	}
}


echo '<script type="text/javascript">
function getSelectSgdb() {
	var selectElmt = document.getElementById("sgdb");
	if (!selectElmt) return false;
	return selectElmt.options[selectElmt.selectedIndex].value;
}
function show_mysql_form() {
	var selected = getSelectSgdb();
	if (selected) {
		if (selected == "mysql") {
			document.getElementById("mysql_vars").style.display = "block";
		} else {
			document.getElementById("mysql_vars").style.display = "none";
		}
	}
}
show_mysql_form(); // needed if MySQL is only option.

function revealpass(fieldId) {
	var field = document.getElementById(fieldId);
	if (field.type == "password") { field.type = "text"; }
	else { field.type = "password"; }
	field.focus();
	field.setSelectionRange(field.value.length, field.value.length);
	return false;
}

</script>'."\n";

footer();

