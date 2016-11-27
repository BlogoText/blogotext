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


// load all addons without cache
$t = addons_load_all(false);



// traitement d’une action sur le module
$erreurs = array();
if (isset($_POST['_verif_envoi'])) {
    $module = array (
            'addon_id' => htmlspecialchars($_POST['addon_id']),
            'status' => (isset($_POST['statut']) and $_POST['statut'] == 'on') ? 1 : 0,
        );
    $erreurs = valider_form_module($module);
    if (isset($_POST['mod_activer'])) {
        if (!empty($erreurs)) {
            echo 'Error';
            echo implode("\n", $erreurs);
            die;
        } else {
            addon_ajax_switch_enabled_proceed($module); // FIXME: this should not return anything. Put a is_readable() in valider_form_module, or somewhere more appropriate.  Or simply die with error, since this is critical error that shouldn’t allow BT to run.
        }
    } else {
        $erreurs = addon_ajax_switch_enabled_proceed($module); // FIXME: same here.
    }
}

$filtre = (!empty($_GET['filtre'])) ? htmlspecialchars($_GET['filtre']) : '';

if ($filtre == 'disabled') {
    $tableau = addons_list_disabled();
} else if ($filtre == 'enabled') {
    $tableau = addons_list_ensabled();
} else {
    $tableau = addons_list_all(true);
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
echo php_lang_to_js(0);
echo 'var csrf_token = \''.new_token().'\'';
echo '</script>';

// [POC] by RemRem, preview of an alternative view
echo '<script>'."\n";
echo'     // [POC] by RemRem, preview of an alternative view'."\n";
echo '    if ("querySelector" in document && "addEventListener" in window){'."\n";
echo '       [].forEach.call(document.querySelectorAll("#modules div"), function (el) {'."\n";
echo '        el.style.display = "none";'."\n";
echo '    });'."\n";
echo '    [].forEach.call(document.querySelectorAll("#modules li"), function (el) {'."\n";
echo '        el.addEventListener("click",function(e){'."\n";
echo '            // e.preventDefault();'."\n";
echo '            this.nextElementSibling.style.display = (this.nextElementSibling.style.display === "none") ? "" : "none";'."\n";
echo '            return;'."\n";
echo '        }, false);'."\n";
echo '    });'."\n";
echo '}'."\n";
echo '</script>'."\n";

footer($begin);
