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

/* list all addons */
function list_addons($is_admin=FALSE) {
	$addons = array();
	$path = $is_admin ? '../'.DIR_ADDONS : DIR_ADDONS;

	if (is_dir($path)) {
		// get the list of installed addons
		$addons_list = rm_dots_dir(scandir($path));

		// include the addons
		foreach ($addons_list as $addon) {
			$inc = sprintf('%s/%s/%s.php', $path, $addon, $addon);
			$is_enabled = !is_file(sprintf('%s/%s/.disabled', $path, $addon));
			if (is_file($inc)) {
				$addons[$addon] = $is_enabled;
				include $inc;
			}
		}
	}

	return $addons;
}

function afficher_liste_modules($tableau, $filtre) {
	if (!empty($tableau)) {
		$out = '<ul id="modules">'."\n";
		foreach ($tableau as $i => $addon) {
			// DESCRIPTION
			$title = trim(htmlspecialchars(mb_substr(strip_tags($addon['desc']), 0, 249), ENT_QUOTES));
			$out .= "\t".'<li title="'.$title.'">'."\n";
			// CHECKBOX POUR ACTIVER
			$out .= "\t\t".'<span><input type="checkbox" class="checkbox-toggle" name="module_'.$i.'" id="module_'.$i.'" '.(($addon['status']) ? 'checked' : '').' onchange="activate_mod(this);" /><label for="module_'.$i.'"></label></span>'."\n";

			// NOM DU MODULE
			$out .= "\t\t".'<span><a href="modules.php?addon_id='.$addon['tag'].'">'.$addon['name'].'</a></span>'."\n";

			// VERSION
			$out .= "\t\t".'<span>'.$addon['version'].'</span>'."\n";

			$out .= "\t".'</li>'."\n";
		}
		$out .= '</ul>'."\n\n";
	} else {
		$out = info($GLOBALS['lang']['note_no_module']);
	}

	echo $out;
}

// TODO: at the end, put this in "afficher_form_filtre()"
function afficher_form_filtre_modules($filtre) {
	$ret = '<div id="form-filtre">'."\n";
	$ret .= '<form method="get" action="'.basename($_SERVER['SCRIPT_NAME']).'" onchange="this.submit();">'."\n";
	$ret .= "\n".'<select name="filtre">'."\n" ;
	// TOUS
	$ret .= '<option value="all"'.($filtre == '' ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_all'].'</option>'."\n";
	// ACTIVÉS
	$ret .= '<option value="enabled"'.($filtre == 'enabled' ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_enabled'].'</option>'."\n";
	// DÉSACTIVÉS
	$ret .= '<option value="disabled"'.($filtre == 'disabled' ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_disabled'].'</option>'."\n";
	$ret .= '</select> '."\n\n";
	$ret .= '</form>'."\n";
	$ret .= '</div>'."\n";
	echo $ret;
}

function afficher_form_module($addons, $addon_id) { // affichage d'un module
	$form = '<form id="form-image" class="bordered-formbloc" method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'">'."\n";
	$form .= '<div class="edit-fichier">'."\n";

	// la partie listant les infos du module.
	$form .= '<ul id="fichier-meta-info">'."\n";
		$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_nom'].'</b> '.$addons[$addon_id]['name'].'</li>'."\n";
		$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_description'].'</b> '.$addons[$addon_id]['desc'].'</li>'."\n";
		$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_version'].'</b>'.$addons[$addon_id]['version'].'</li>'."\n";
		$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_etat'].'</b>'.($addons[$addon_id]['status'] ? $GLOBALS['lang']['label_enabled'] : $GLOBALS['lang']['label_disabled']).'</li>'."\n";
	$form .= '</ul>'."\n";

	// la partie des codes d’intégration (bbcode, etc.)
	$form .= '<div id="interg-codes">'."\n";
	$form .= '<p><strong>'.$GLOBALS['lang']['label_codes'].'</strong></p>'."\n";
	$form .= '<input onfocus="this.select()" class="text" type="text" value=\'{addon_'.$addon_id.'}\' />'."\n";

	$form .= '</div>'."\n";

	// la partie avec la possibilité de changer le statut du module.
	$form .= '<div id="img-others-infos">'."\n";
	$checked = $addons[$addon_id]['status'] ? 'checked ' : '';
	$form .= "\t".'<p><input type="checkbox" id="statut" name="statut" '.$checked.' class="checkbox" /><label for="statut">'.$GLOBALS['lang']['label_addon_enabled'].'</label></p>';
	$form .= "\t".'<p class="submit-bttns">'."\n";
	$form .= "\t\t".'<button class="submit button-cancel" type="button" onclick="annuler(\'modules.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
	$form .= "\t\t".'<button class="submit button-submit" type="submit" name="editer">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
	$form .= "\t".'</p>'."\n";
	$form .= '</div>'."\n";

	$form .= hidden_input('_verif_envoi', '1');
	$form .= hidden_input('addon_id', $addon_id);
	$form .= hidden_input('token', new_token());
	$form .= '</div>';
	$form .= '</form>'."\n";

	echo $form;
}

function traiter_form_module($module) {
	$erreurs = array();
	$path = BT_ROOT.DIR_ADDONS;
	$check_file = sprintf('%s/%s/.disabled', $path, $module['addon_id']);
	$is_enabled = !is_file($check_file);
	$new_status = $module['status'];

	if ($is_enabled != $new_status) {
		if ($new_status) {
			// Module activé, on supprimer le fichier .disabled
			if (unlink($check_file) === FALSE) {
				$erreurs[] = sprintf($GLOBALS['lang']['err_addon_enabled'], $module['addon_id']);
			}
		} else {
			// Module désactivé, on créée le fichier .disabled
			if (fichier_module_disabled(BT_ROOT.DIR_ADDONS.'/'.$module['addon_id']) === FALSE) {
				$erreurs[] = sprintf($GLOBALS['lang']['err_addon_disabled'], $module['addon_id']);
			}
		}
	}

	if (isset($_POST['mod_activer']) ) {
		if (empty($erreurs)) {
			die('Success'.new_token());
		}
		else {
			die ('Error'.new_token().implode("\n", $erreurs));
		}
	}

	return $erreurs;
}

function init_post_module() {
	return array (
		'addon_id' => htmlspecialchars($_POST['addon_id']),
		'status' => (isset($_POST['statut']) and $_POST['statut'] == 'on') ? '1' : '0',
	);
}
