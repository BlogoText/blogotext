<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BlogoText/blogotext/
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
require_once BT_ROOT_ADMIN.'inc/addons.php';


/**
 * process
 */

// load all addons without cache
$t = addons_load_all(false);


// traitement d’une action sur le module
$erreurs = array();
if (isset($_POST['_verif_envoi'])) {
    $module = array (
            'addon_id' => htmlspecialchars($_POST['addon_id']),
            'status' => (isset($_POST['statut']) and $_POST['statut'] == 'on') ? 1 : 0,
        );
    $erreurs = addon_ajax_check_request($module['addon_id'], 'mod_activer');
    if (!isset($module['status'])) {
        $erreurs[] = $GLOBALS['lang']['err_addon_status'];
    }
    if (isset($_POST['mod_activer'])) {
        if ($erreurs) {
            die('Error'.implode("\n", $erreurs));
        }
        addon_ajax_switch_enabled_proceed($module);
    } else {
        $erreurs = addon_ajax_switch_enabled_proceed($module);
    }
}


$filtre = (!empty($_GET['filtre'])) ? htmlspecialchars($_GET['filtre']) : '';

if ($filtre == 'disabled') {
    $tableau = addons_list_disabled();
} else if ($filtre == 'enabled') {
    $tableau = addons_list_enabled();
} else {
    $tableau = addons_list_all();
}


/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['mesmodules']);

echo '<div id="header">';
    echo '<div id="top">';
        echo moteur_recherche();
        echo tpl_show_topnav($GLOBALS['lang']['mesmodules']);
    echo '</div>';
echo '</div>';

echo '<div id="axe">';
echo '<div id="page">';

echo erreurs($erreurs);
// Subnav
echo '<div id="subnav">';
    echo addon_form_list_addons_filter($filtre);
    echo '<div class="nombre-elem">';
    echo ucfirst(nombre_objets(count($tableau), 'module')).' '.$GLOBALS['lang']['sur'].' '.count($addons);
    echo '</div>';
echo '</div>';

echo addons_html_get_list_addons($tableau, $filtre);

echo '<script src="style/javascript.js"></script>';
echo '<script>';
echo 'addons_showhide_list();';
echo php_lang_to_js(0);
echo 'var csrf_token = "'.new_token().'";';
echo '</script>';

echo tpl_get_footer($begin);
