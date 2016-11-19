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
$begin = microtime(true);


// traitement d’une action sur le module
if (isset($_POST['_verif_envoi'])) {
    // $module = init_post_module();
    // $erreurs = valider_form_module($module);

    $form_process = addon_edit_params_process($_GET['addonName']);
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

// echo erreurs($erreurs);

echo addon_edit_params_form($_GET['addonName']);

echo "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
echo '<script type="text/javascript">';
echo php_lang_to_js(0);
echo 'var csrf_token = \''.new_token().'\'';
echo '</script>';
footer($begin);
