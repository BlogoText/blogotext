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
		redirection(basename($_SERVER['PHP_SELF']).'?msg=confirm_prefs_maj');
		exit();
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
	afficher_html_head($GLOBALS['lang']['preferences']);
	echo '<div id="top">';
	afficher_msg();
	afficher_topnav(basename($_SERVER['PHP_SELF']), $GLOBALS['lang']['preferences']);
	echo '</div>';

	echo '<div id="axe">'."\n";
	echo '<div id="page">'."\n";
	echo erreurs($erreurs);

	echo '<form id="preferences" class="bordered-formbloc" method="post" action="'.basename($_SERVER['PHP_SELF']).'" >' ;
		$fld_user = '<div role="group" class="pref">'; /* no fieldset because browset can’t style them correctly */
		$fld_user .= '<div class="form-legend">'.legend($GLOBALS['lang']['prefs_legend_utilisateur'], 'legend-user').'</div>'."\n";

		$fld_user .= '<div class="form-lines">'."\n";
		$fld_user .= '<p>'."\n";
		$fld_user .= "\t".'<label for="auteur">'.$GLOBALS['lang']['pref_auteur'].'</label>'."\n";
		$fld_user .= "\t".'<input type="text" id="auteur" name="auteur" size="30" value="'.(empty($GLOBALS['auteur']) ? $GLOBALS['identifiant'] : $GLOBALS['auteur']).'" class="text" />'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '<p>'."\n";
		$fld_user .= "\t".'<label for="email">'.$GLOBALS['lang']['pref_email'].'</label>'."\n";
		$fld_user .= "\t".'<input type="text" id="email" name="email" size="30" value="'.$GLOBALS['email'].'" class="text" />'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '<p>'."\n";
		$fld_user .= "\t".'<label for="nomsite">'.$GLOBALS['lang']['pref_nom_site'].'</label>'."\n";
		$fld_user .= "\t".'<input type="text" id="nomsite" name="nomsite" size="30" value="'.$GLOBALS['nom_du_site'].'" class="text" />'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '<p>'."\n";
		$fld_user .= "\t".'<label for="racine">'.$GLOBALS['lang']['pref_racine'].'</label>'."\n";
		$fld_user .= "\t".'<input type="text" id="racine" name="racine" size="30" value="'.$GLOBALS['racine'].'" class="text" />'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '<p>'."\n";
		$fld_user .= "\t".'<label for="description">'.$GLOBALS['lang']['label_dp_description'].'</label>'."\n";
		$fld_user .= "\t".'<textarea id="description" name="description" cols="35" rows="2" class="text" >'.$GLOBALS['description'].'</textarea>'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '<p>'."\n";
		$fld_user .= "\t".'<label for="keywords">'.$GLOBALS['lang']['pref_keywords'].'</label>';
		$fld_user .= "\t".'<textarea id="keywords" name="keywords" cols="35" rows="2" class="text" >'.$GLOBALS['keywords'].'</textarea>'."\n";
		$fld_user .= '</p>'."\n";
		$fld_user .= '</div>'."\n";

		$fld_user .= '</div>';
	echo $fld_user;

		$fld_securite = '<div role="group" class="pref">';
		$fld_securite .= '<div class="form-legend">'.legend($GLOBALS['lang']['prefs_legend_securite'], 'legend-securite').'</div>'."\n";

		$fld_securite .= '<div class="form-lines">'."\n";
		$fld_securite .= '<p>'."\n";
		$fld_securite .= "\t".'<label for="identifiant">'.$GLOBALS['lang']['pref_identifiant'].'</label>'."\n";
		$fld_securite .= "\t".'<input type="text" id="identifiant" name="identifiant" size="30" value="'.$GLOBALS['identifiant'].'" class="text" />'."\n";
		$fld_securite .= '</p>'."\n";

		$fld_securite .= '<p>'."\n";
		$fld_securite .= "\t".'<label for="mdp">'.$GLOBALS['lang']['pref_mdp'].'</label>';
		$fld_securite .= "\t".'<input type="password" id="mdp" name="mdp" size="30" value="" class="text" autocomplete="off" />'."\n";
		$fld_securite .= '</p>'."\n";

		$fld_securite .= '<p>'."\n";
		$fld_securite .= "\t".'<label for="mdp_rep">'.$GLOBALS['lang']['pref_mdp_nouv'].'</label>';
		$fld_securite .= "\t".'<input type="password" id="mdp_rep" name="mdp_rep" size="30" value="" class="text" autocomplete="off" />'."\n";
		$fld_securite .= '</p>'."\n";

		if (in_array('gd', get_loaded_extensions())) { // captcha only possible if GD library is installed.
			$fld_securite .= '<p>'."\n";
			$fld_securite .= select_yes_no('connexion_captcha', $GLOBALS['connexion_captcha'], $GLOBALS['lang']['pref_connexion_captcha'] );
			$fld_securite .= '</p>'."\n";
		} else {
			$fld_securite .= hidden_input('connexion_captcha', '0');
		}
		$fld_securite .= '</div>';
		$fld_securite .= '</div>';
	echo $fld_securite;

		$fld_apparence = '<div role="group" class="pref">';
		$fld_apparence .= '<div class="form-legend">'.legend($GLOBALS['lang']['prefs_legend_apparence'], 'legend-apparence').'</div>'."\n";

		$fld_apparence .= '<div class="form-lines">'."\n";
		$fld_apparence .= '<p>'."\n";
		$fld_apparence .= form_select('theme', liste_themes($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_themes']), $GLOBALS['theme_choisi'],$GLOBALS['lang']['pref_theme']);
		$fld_apparence .= '</p>'."\n";

		$fld_apparence .= '<p>'."\n";
		$fld_apparence .= form_select('nb_maxi', array('5'=>'5', '10'=>'10', '15'=>'15', '20'=>'20', '25'=>'25', '50'=>'50'), $GLOBALS['max_bill_acceuil'],$GLOBALS['lang']['pref_nb_maxi']);
		$fld_apparence .= '</p>'."\n";

		$fld_apparence .= '<p>'."\n";
		$fld_apparence .= select_yes_no('aff_onglet_rss', $GLOBALS['onglet_rss'], $GLOBALS['lang']['pref_afficher_rss'] );
		$fld_apparence .= '</p>'."\n";
		$fld_apparence .= '<p>'."\n";
		$fld_apparence .= select_yes_no('aff_onglet_liens', $GLOBALS['onglet_liens'], $GLOBALS['lang']['pref_afficher_liens'] );
		$fld_apparence .= '</p>'."\n";
		$fld_apparence .= '</div>'."\n";
		$fld_apparence .= '</div>';
	echo $fld_apparence;

		$fld_dateheure = '<div role="group" class="pref">';
		$fld_dateheure .= '<div class="form-legend">'.legend($GLOBALS['lang']['prefs_legend_langdateheure'], 'legend-dateheure').'</div>'."\n";

		$fld_dateheure .= '<div class="form-lines">'."\n";
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
		$fld_dateheure .= '</div>'."\n";

		$fld_dateheure .= '</div>';
	echo $fld_dateheure;

		$fld_cfg_blog = '<div role="group" class="pref">';
		$fld_cfg_blog .= '<div class="form-legend">'.legend($GLOBALS['lang']['prefs_legend_configblog'], 'legend-config').'</div>'."\n";
		$fld_cfg_blog .= '<div class="form-lines">'."\n";
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
		$fld_cfg_blog .= '</div>'."\n";

		$fld_cfg_blog .= '</div>';
	echo $fld_cfg_blog;


		$fld_cfg_linx = '<div role="group" class="pref">';
		$fld_cfg_linx .= '<div class="form-legend">'.legend($GLOBALS['lang']['prefs_legend_configlinx'], 'legend-config').'</div>'."\n";

		$fld_cfg_linx .= '<div class="form-lines">'."\n";
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
		$fld_cfg_linx .= '<label>'.$GLOBALS['lang']['pref_label_bookmark_lien'].'</label>'."\n";
		$fld_cfg_linx .= '<a class="dnd-to-favs" onclick="alert(\''.$GLOBALS['lang']['pref_alert_bookmark_link'].'\');return false;" href="javascript:javascript:(function(){window.open(\''.$GLOBALS['racine'].$a[count($a)-1].'/links.php?url=\'+encodeURIComponent(location.href));})();"><b>Save link</b></a>';
		$fld_cfg_linx .= '</p>'."\n";
		$fld_cfg_linx .= '</div>'."\n";

		$fld_cfg_linx .= '</div>';
	echo $fld_cfg_linx;

		$fld_maintenance = '<div role="group" class="pref">';
		$fld_maintenance .= '<div class="form-legend">'.legend($GLOBALS['lang']['titre_maintenance'], 'legend-sweep').'</div>'."\n";

		$fld_maintenance .= '<div class="form-lines">'."\n";

		$fld_maintenance .= '<p>'."\n";
		$fld_maintenance .= select_yes_no('check_update', $GLOBALS['check_update'], $GLOBALS['lang']['pref_check_update'] );
		$fld_maintenance .= '</p>'."\n";

		$fld_maintenance .= '<p>'."\n";
		$fld_maintenance .= "\t".'<label>'.$GLOBALS['lang']['pref_go_to_maintenance'].'</label>'."\n";
		$fld_maintenance .= "\t".'<a href="maintenance.php">Maintenance</a>'."\n";
		$fld_maintenance .= '</p>'."\n";
		$fld_maintenance .= '</div>'."\n";

		$fld_maintenance .= '</div>';
	echo $fld_maintenance;

	// check if a new Blogotext version is available (code from Shaarli, by Sebsauvage).
	// Get latest version number at most once a day.
	if ($GLOBALS['check_update'] == 1) {
		if ( !is_file($GLOBALS['last-online-file']) or (filemtime($GLOBALS['last-online-file']) < time()-(24*60*60)) ) {
			$last_version = get_external_file('http://lehollandaisvolant.net/blogotext/version.php', 6);
			if (empty($last_version['body'])) { $last_version = $GLOBALS['version']; }
			// If failed, nevermind. We don't want to bother the user with that.
			file_put_contents($GLOBALS['last-online-file'], $last_version['body']); // touch file date
		}

		// Compare versions:
		$newestversion = file_get_contents($GLOBALS['last-online-file']);
		if (version_compare($newestversion, $GLOBALS['version']) == 1) {
				$fld_update = '<div role="group" class="pref">';
				$fld_update .= '<div class="form-legend">'.legend($GLOBALS['lang']['maint_chk_update'], 'legend-update').'</div>'."\n";
				$fld_update .= '<div class="form-lines">'."\n";
				$fld_update .= '<p>'."\n";
				$fld_update .= "\t".'<label>'.$GLOBALS['lang']['maint_update_youisbad'].' ('.$newestversion.'). '.$GLOBALS['lang']['maint_update_go_dl_it'].'</label>'."\n";
				$fld_update .= "\t".'<a href="http://lehollandaisvolant.net/blogotext/">lehollandaisvolant.net/blogotext</a>.';
				$fld_update .= '</p>'."\n";
				$fld_update .= '</div>'."\n";
				$fld_update .= '</div>'."\n";
			echo $fld_update;
		}
	}

	echo '<div class="submit-bttns">';
	echo hidden_input('_verif_envoi', '1');
	echo hidden_input('token', new_token());
	echo '<button class="submit white-square" type="button" onclick="annuler(\'preferences.php\');" >'.$GLOBALS['lang']['annuler'].'</button>'."\n";
	echo '<input class="submit blue-square" type="submit" name="enregistrer" value="'.$GLOBALS['lang']['enregistrer'].'" />'."\n";
	echo '</div>';
	echo '</form>';
}



/*
	FORMULAIRE DE TEST DU CAPTCHA
*/
function afficher_form_captcha() {
	afficher_html_head($GLOBALS['lang']['preferences']);
	echo '<div id="top">';
	afficher_msg();
	afficher_topnav(basename($_SERVER['PHP_SELF']), $GLOBALS['lang']['preferences']);
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
	echo '<form id="preferences-captcha" action="'.basename($_SERVER['PHP_SELF']).'?test_captcha" method="post" class="bordered-formbloc" >'."\n";
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


footer('', $begin);

