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
require_once BT_ROOT.'admin/inc/addons.php';



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
        if (!empty($erreurs)) {
            echo 'Error';
            echo implode("\n", $erreurs);
            die;
        } else {
            /**
             * FIXME: this should not return anything. Put a is_readable() in addon_ajax_check_request(),
             *        or somewhere more appropriate. Or simply die with error, since this is
             *        critical error that shouldn’t allow BT to run.
             */
            /**
             * From : RemRem
             * Depuis la maj de core/addon et les modifs que j'ai fait, c'est bon non ?
             */
            addon_ajax_switch_enabled_proceed($module);
        }
    } else {
        $erreurs = addon_ajax_switch_enabled_proceed($module); // FIXME: same here.
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

echo '<div id="header">'."\n";
    echo '<div id="top">'."\n";
        echo moteur_recherche();
        tpl_show_topnav($GLOBALS['lang']['mesmodules']);
    echo '</div>'."\n";
echo '</div>'."\n";

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

echo erreurs($erreurs);
// SUBNAV
echo '<div id="subnav">'."\n";
    echo addon_form_list_addons_filter($filtre);
    echo '<div class="nombre-elem">'."\n";
    echo ucfirst(nombre_objets(count($tableau), 'module')).' '.$GLOBALS['lang']['sur'].' '.count($addons);
    echo '</div>'."\n";
echo '</div>'."\n";

echo addons_html_get_list_addons($tableau, $filtre);

echo "\n".'<script src="style/javascript.js"></script>'."\n";
echo '<script>';
echo 'addons_showhide_list();';
echo php_lang_to_js(0);
echo 'var csrf_token = \''.new_token().'\'';
echo '</script>';

echo tpl_get_footer($begin);
