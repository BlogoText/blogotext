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

/**
 * handler for a selected module info / config ...
 */

define('BT_ROOT', '../');

require_once '../inc/inc.php';

operate_session();
$begin = microtime(TRUE);



$erreurs = array();
if (isset($_POST['_verif_envoi'])) {

	// on/off switch
	if ( isset($_POST['mod_activer']) ) {
		$module = init_post_module();
		$erreurs = valider_form_module($module);
		if (!empty($erreurs) ) {
			echo 'Error';
			echo implode("\n", $erreurs);
			die();
		}
		else {
			traiter_form_module($module); // FIXME: this should not return anything. Put a is_readable() in valider_form_module, or somewhere more appropriate.  Or simply die with error, since this is critical error that shouldn’t allow BT to run.
		}

	// update addon params
	} else if (
		isset($_POST['addon_action'])
	 && $_POST['addon_action'] == 'params'
	) {
		// $module = init_post_module();
		// $erreurs = valider_form_module($module);

		$erreurs = addon_edit_params_process( $_GET['addonName'] );
	} else {
		$erreurs = traiter_form_module($module); // FIXME: same here.
	}
}




afficher_html_head($GLOBALS['lang']['mesmodules']);

echo '<div id="header">'."\n";
	echo '<div id="top">'."\n";
		echo moteur_recherche();
		afficher_topnav($GLOBALS['lang']['mesmodules']);
	echo '</div>'."\n";
echo '</div>'."\n";

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

echo addon_edit_params_form( $_GET['addonName'] );

echo "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
echo '<script type="text/javascript">';
echo php_lang_to_js(0);
echo 'var csrf_token = \''.new_token().'\'';
echo '</script>';


footer($begin);
