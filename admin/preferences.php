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

$begin = microtime(true);
define('BT_ROOT', '../');

require_once '../inc/inc.php';
require_once '../inc/auth.php';

operate_session();

$erreurs_form = array();

if (isset($_POST['_verif_envoi'])) {
    $erreurs_form = valider_form_preferences();
    if (empty($erreurs_form)) {
		// devnote : I suppose we take $_POST['mdp_rep'] (alt. $_POST['mdp'])
        if (auth_write_user_login_file( $_POST['identifiant'] , $_POST['mdp_rep'] )
         && fichier_prefs()
		){
            redirection(basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_prefs_maj');
        } else {
            $erreurs_form[] = $GLOBALS['lang']['err_prefs_write'];
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

afficher_form_prefs($erreurs_form);

echo "\n".'<script src="style/javascript.js"></script>'."\n";

footer($begin);
