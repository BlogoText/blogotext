<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BoboTiG/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***

require_once 'inc/boot.php';

// dependancy
require_once BT_ROOT.'inc/addons.php';
// require_once BT_ROOT.'admin/inc/addons.php'; // dont remove, just the time to clean the rewrited addon's function


// traitement d’une action sur le module
if (isset($_POST['_verif_envoi']) && isset($_POST['action_type'])) {
    if ($_POST['action_type'] == 'settings') {
        $form_process = addon_form_edit_settings_proceed($_GET['addon']);
    } else if ($_POST['action_type'] == 'buttons') {
        $form_process = addon_buttons_action_process($_GET['addon']);
    }
}

tpl_show_html_head($GLOBALS['lang']['mesmodules']);

echo '<div id="header">'."\n";
    echo '<div id="top">'."\n";
        echo moteur_recherche();
        tpl_show_topnav($GLOBALS['lang']['mesmodules']);
    echo '</div>'."\n";
echo '</div>'."\n";

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

// echo erreurs($erreurs);

echo addon_form_edit_settings($_GET['addon']);

echo "\n".'<script src="style/javascript.js"></script>'."\n";
echo '<script>';
echo php_lang_to_js(0);
echo 'var csrf_token = \''.new_token().'\'';
echo '</script>';
footer($begin);
