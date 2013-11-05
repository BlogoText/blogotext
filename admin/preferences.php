<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2012 Timo Van Neerden <ti-mo@myopera.com>
#
# BlogoText is free software, you can redistribute it under the terms of the
# Creative Commons Attribution-NonCommercial 2.0 France Licence
#
# Also, any distributors of non-official releases MUST warn the final user of it, by any visible way before the download.
# *** LICENSE ***

$begin = microtime(TRUE);
$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();
if (isset($_POST['_verif_envoi'])) {
	if ($erreurs_form = valider_form_preferences()) {
		afficher_form_prefs($erreurs_form);
	} else {
		if ( (fichier_user() === TRUE) and (fichier_prefs() === TRUE) ) {
		redirection($_SERVER['PHP_SELF'].'?msg=confirm_prefs_maj');
		}
	}
} else {
	if (isset($_GET['test_captcha'])) {
		afficher_form_captcha();
	} else {
		afficher_form_prefs();
	}
}

/*
	FORMULAIRE NORMAL DES PRÉFÉRENCES
*/
function afficher_form_prefs($erreurs = '') {
	afficher_top($GLOBALS['lang']['preferences']);
	echo '<div id="top">';
	afficher_msg($GLOBALS['lang']['preferences']);
	afficher_menu(pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME));
	echo '</div>';

	echo '<div id="axe">'."\n";
	echo '<div id="page">'."\n";
	echo erreurs($erreurs);

	echo '<form id="preferences" class="bordered-formbloc" method="post" action="'.$_SERVER['PHP_SELF'].'" >' ;
		$fld_user = '<fieldset class="pref">';
		$fld_user .= legend($GLOBALS['lang']['prefs_legend_utilisateur'], 'legend-user');

		$fld_user .= '<p>'."\n";
		$fld_user .= '<label for="auteur">'.$GLOBALS['lang']['pref_auteur'].'</label>'."\n";
		$fld_user .= '<input type="text" id="auteur" name="auteur" size="30" value="'.(empty($GLOBALS['auteur']) ? $GLOBALS['identifiant'] : $GLOBALS['auteur']).'" class="text" />'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '<p>'."\n";
		$fld_user .= '<label for="email">'.$GLOBALS['lang']['pref_email'].'</label>'."\n";
		$fld_user .= '<input type="text" id="email" name="email" size="30" value="'.$GLOBALS['email'].'" class="text" />'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '<p>'."\n";
		$fld_user .= '<label for="nomsite">'.$GLOBALS['lang']['pref_nom_site'].'</label>'."\n";
		$fld_user .= '<input type="text" id="nomsite" name="nomsite" size="30" value="'.$GLOBALS['nom_du_site'].'" class="text" />'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '<p>'."\n";
		$fld_user .= '<label for="racine">'.$GLOBALS['lang']['pref_racine'].'</label>'."\n";
		$fld_user .= '<input type="text" id="racine" name="racine" size="30" value="'.$GLOBALS['racine'].'" class="text" />'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '<p>'."\n";
		$fld_user .= '<label for="description">'.$GLOBALS['lang']['label_dp_description'].'</label>'."\n";
		$fld_user .= '<textarea id="description" name="description" cols="35" rows="2" class="text" >'.$GLOBALS['description'].'</textarea>'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '<p>'."\n";
		$fld_user .= '<label for="keywords">'.$GLOBALS['lang']['pref_keywords'].'</label>';
		$fld_user .= '<textarea id="keywords" name="keywords" cols="35" rows="2" class="text" >'.$GLOBALS['keywords'].'</textarea>'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '</fieldset>';
	echo $fld_user;

		$fld_securite = '<fieldset class="pref">';
		$fld_securite .= legend($GLOBALS['lang']['prefs_legend_securite'], 'legend-securite');

		$fld_securite .= '<p>'."\n";
		$fld_securite .= '<label for="identifiant">'.$GLOBALS['lang']['pref_identifiant'].'</label>'."\n";
		$fld_securite .= '<input type="text" id="identifiant" name="identifiant" size="30" value="'.$GLOBALS['identifiant'].'" class="text" />'."\n";
		$fld_securite .= '</p>'."\n";

		$fld_securite .= '<p>'."\n";
		$fld_securite .= '<label for="mdp">'.$GLOBALS['lang']['pref_mdp'].'</label>';
		$fld_securite .= '<input type="password" id="mdp" name="mdp" size="30" value="" class="text" autocomplete="off" />'."\n";
		$fld_securite .= '</p>'."\n";

		$fld_securite .= '<p>'."\n";
		$fld_securite .= '<label for="mdp_rep">'.$GLOBALS['lang']['pref_mdp_nouv'].'</label>';
		$fld_securite .= '<input type="password" id="mdp_rep" name="mdp_rep" size="30" value="" class="text" autocomplete="off" />'."\n";
		$fld_securite .= '</p>'."\n";

		if (in_array('gd', get_loaded_extensions())) { // captcha only possible if GD library is installed.
			$fld_securite .= '<p>'."\n";
			$fld_securite .= select_yes_no('connexion_captcha', $GLOBALS['connexion_captcha'], $GLOBALS['lang']['pref_connexion_captcha'] );
			$fld_securite .= '</p>'."\n";
		} else {
			$fld_securite .= hidden_input('connexion_captcha', '0');
		}
		$fld_securite .= '</fieldset>';
	echo $fld_securite;

		$fld_apparence = '<fieldset class="pref">';
		$fld_apparence .= legend($GLOBALS['lang']['prefs_legend_apparence'], 'legend-apparence');

		$fld_apparence .= '<p>'."\n";
		$fld_apparence .= form_select('nb_maxi', array('5'=>'5', '10'=>'10', '15'=>'15', '20'=>'20', '25'=>'25', '50'=>'50'), $GLOBALS['max_bill_acceuil'],$GLOBALS['lang']['pref_nb_maxi']);
		$fld_apparence .= '</p>'."\n";

		$fld_apparence .= '<p>'."\n";
		$fld_apparence .= form_select('theme', liste_themes($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_themes']), $GLOBALS['theme_choisi'],$GLOBALS['lang']['pref_theme']);
		$fld_apparence .= '</p>'."\n";
		$fld_apparence .= '</fieldset>';
	echo $fld_apparence;

		$fld_dateheure = '<fieldset class="pref">';
		$fld_dateheure .= legend($GLOBALS['lang']['prefs_legend_langdateheure'], 'legend-dateheure');

		$fld_dateheure .= '<p>'."\n";
		$fld_dateheure .= form_langue($GLOBALS['lang']['id']);
		$fld_dateheure .= '</p>'."\n";

		$fld_dateheure .= '<p>'."\n";
		$fld_dateheure .= form_format_date($GLOBALS['format_date']);
		$fld_dateheure .= '</p>'."\n";

		$fld_dateheure .= '<p>'."\n";
		$fld_dateheure .= form_format_heure($GLOBALS['format_heure']);
		$fld_dateheure .= '</p>'."\n";

		$fld_dateheure .= '<p>'."\n";
		$fld_dateheure .= form_fuseau_horaire($GLOBALS['fuseau_horaire']);
		$fld_dateheure .= '</p>'."\n";

		$fld_dateheure .= '</fieldset>';
	echo $fld_dateheure;

		$fld_cfg_blog = '<fieldset class="pref">';
		$fld_cfg_blog .= legend($GLOBALS['lang']['prefs_legend_configblog'], 'legend-config');
		$nbs = array('10'=>'10', '25'=>'25', '50'=>'50', '100'=>'100', '300'=>'300', '-1' => $GLOBALS['lang']['pref_all']);

		$fld_cfg_blog .= '<p>'."\n";
		$fld_cfg_blog .= form_select('nb_list', $nbs, $GLOBALS['max_bill_admin'],$GLOBALS['lang']['pref_nb_list']);
		$fld_cfg_blog .= '</p>'."\n";

		$fld_cfg_blog .= '<p>'."\n";
		$fld_cfg_blog .= form_select('nb_list_com', $nbs, $GLOBALS['max_comm_admin'],$GLOBALS['lang']['pref_nb_list_com']);
		$fld_cfg_blog .= '</p>'."\n";

		$fld_cfg_blog .= '<p>'."\n";
		$fld_cfg_blog .= select_yes_no('activer_categories', $GLOBALS['activer_categories'], $GLOBALS['lang']['pref_categories'] );
		$fld_cfg_blog .= '</p>'."\n";

		$fld_cfg_blog .= '<p>'."\n";
		$fld_cfg_blog .= select_yes_no('auto_keywords', $GLOBALS['automatic_keywords'], $GLOBALS['lang']['pref_automatic_keywords'] );
		$fld_cfg_blog .= '</p>'."\n";

		$fld_cfg_blog .= '<p>'."\n";
		$fld_cfg_blog .= select_yes_no('global_comments', $GLOBALS['global_com_rule'], $GLOBALS['lang']['pref_allow_global_coms']);
		$fld_cfg_blog .= '</p>'."\n";

		$fld_cfg_blog .= '<p>'."\n";
		$fld_cfg_blog .= select_yes_no('require_email', $GLOBALS['require_email'], $GLOBALS['lang']['pref_force_email']);
		$fld_cfg_blog .= '</p>'."\n";

		$fld_cfg_blog .= '<p>'."\n";
		$fld_cfg_blog .= form_select('comm_defaut_status', array('1' => $GLOBALS['lang']['pref_comm_black_list'], '0' => $GLOBALS['lang']['pref_comm_white_list']), $GLOBALS['comm_defaut_status'],$GLOBALS['lang']['pref_comm_BoW_list']);
		$fld_cfg_blog .= '</p>'."\n";

		$fld_cfg_blog .= '</fieldset>';
	echo $fld_cfg_blog;


		$fld_cfg_linx = '<fieldset class="pref">';
		$fld_cfg_linx .= legend($GLOBALS['lang']['prefs_legend_configlinx'], 'legend-config');
		// nb liens côté admin
		$nbs = array('50'=>'50', '100'=>'100', '200'=>'200', '300'=>'300', '500'=>'500', '-1' => $GLOBALS['lang']['pref_all']);

		$fld_cfg_linx .= '<p>'."\n";
		$fld_cfg_linx .= form_select('nb_list_linx', $nbs, $GLOBALS['max_linx_admin'], $GLOBALS['lang']['pref_nb_list_linx']);
		$fld_cfg_linx .= '</p>'."\n";

		// partage de fichiers !pages : télécharger dans fichiers automatiquement ?
		$nbs = array('0'=> $GLOBALS['lang']['non'], '1'=> $GLOBALS['lang']['oui'], '2' => $GLOBALS['lang']['pref_ask_everytime']);

		$fld_cfg_linx .= '<p>'."\n";
		$fld_cfg_linx .= form_select('dl_link_to_files', $nbs, $GLOBALS['dl_link_to_files'], $GLOBALS['lang']['pref_linx_dl_auto']);
		$fld_cfg_linx .= '</p>'."\n";

		// lien à glisser sur la barre des favoris
		$a = explode('/', dirname($_SERVER['PHP_SELF']));
		$fld_cfg_linx .= '<p>';
		$fld_cfg_linx .= '<label>'.$GLOBALS['lang']['pref_label_bookmark_lien'].'</label><a class="dnd-to-favs" onclick="alert(\''.$GLOBALS['lang']['pref_alert_bookmark_link'].'\');return false;" href="javascript:javascript:(function(){window.open(\''.$GLOBALS['racine'].$a[count($a)-1].'/links.php?url=\'+encodeURIComponent(location.href));})();"><b>Save link</b></a>';
		$fld_cfg_linx .= '</p>'."\n";

		$fld_cfg_linx .= '</fieldset>';
	echo $fld_cfg_linx;

		$fld_maintenance = '<fieldset class="pref">';
		$fld_maintenance .= legend($GLOBALS['lang']['titre_maintenance'], 'legend-sweep');
		$fld_maintenance .= '<p><a href="maintenance.php">'.$GLOBALS['lang']['pref_go_to_mainteance'].'</a></p>';
		$fld_maintenance .= '</fieldset>';
	echo $fld_maintenance;

	// check if a new Blogotext version is available (code from Shaarli, by Sebsauvage).
	// Get latest version number at most once a day.
	if ( !is_file($GLOBALS['last-online-file']) or (filemtime($GLOBALS['last-online-file']) < time()-(24*60*60)) ) {
		$last_version = get_external_file('http://lehollandaisvolant.net/blogotext/version.php', 6);
		if (empty($last_version)) { $last_version = $GLOBALS['version']; }
		// If failed, nevermind. We don't want to bother the user with that.
		file_put_contents($GLOBALS['last-online-file'], $last_version); // touch file date
	}
	// Compare versions:
	$newestversion = file_get_contents($GLOBALS['last-online-file']);
	if (version_compare($newestversion, $GLOBALS['version']) == 1) {
			$fld_update = '<fieldset class="pref">';
			$fld_update .= legend($GLOBALS['lang']['maint_chk_update'], 'legend-update');
			$fld_update .= '<p>'."\n";
			$fld_update .= '<label>'.$GLOBALS['lang']['maint_update_youisbad'].' ('.$newestversion.'). '.$GLOBALS['lang']['maint_update_go_dl_it'].'</label>'."\n";
			$fld_update .= '<a href="http://lehollandaisvolant.net/blogotext/">lehollandaisvolant.net/blogotext/</a>.';
			$fld_update .= '<p>'."\n";
			$fld_update .= '</fieldset></div>'."\n";
		echo $fld_update;
	}

	echo '<div class="submit">';
	echo hidden_input('_verif_envoi', '1');
	echo hidden_input('token', new_token());
	echo '<input class="submit blue-square" type="submit" name="enregistrer" value="'.$GLOBALS['lang']['enregistrer'].'" />'."\n";
	echo '</div>';
	echo '</form>';
}



/*
	FORMULAIRE DE TEST DU CAPTCHA
*/
function afficher_form_captcha() {
	afficher_top($GLOBALS['lang']['preferences']);
	echo '<div id="top">';
	afficher_msg($GLOBALS['lang']['preferences']);
	afficher_menu(pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME));
	echo '</div>';

	echo '<div id="axe">'."\n";
	echo '<div id="page">'."\n";
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
	echo js_reload_captcha(1);
	echo '<form action="'.$_SERVER['PHP_SELF'].'?test_captcha" method="post" class="bordered-formbloc" >'."\n";
	echo '<fieldset class="pref">';
	echo legend('Captcha', 'legend-config');
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
	echo '</fieldset>';
	echo '</form>'."\n";

}


footer('', $begin);

