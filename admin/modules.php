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

define('BT_ROOT', '../');

require_once '../inc/inc.php';

operate_session();
$begin = microtime(true);

// traitement d’une action sur le module
$erreurs = array();
if (isset($_POST['_verif_envoi'])) {
    $module = init_post_module();
    $erreurs = valider_form_module($module);

    if (isset($_POST['mod_activer'])) {
        if (!empty($erreurs)) {
            echo 'Error';
            echo implode("\n", $erreurs);
            die();
        } else {
            traiter_form_module($module); // FIXME: this should not return anything. Put a is_readable() in valider_form_module, or somewhere more appropriate.  Or simply die with error, since this is critical error that shouldn’t allow BT to run.
        }
    } else {
        $erreurs = traiter_form_module($module); // FIXME: same here.
    }
}

$filtre = !empty($_GET['filtre']) ? htmlspecialchars($_GET['filtre']) : '';
$addons_status = list_addons();
// Filtrons la liste
$tableau = array();
foreach ($GLOBALS['addons'] as $addon) {
    $status = $addons_status[$addon['tag']];
    if (($filtre == 'disabled' && $status) || ($filtre == 'enabled' && !$status)) {
        continue;
    }
    $tableau[$addon['tag']] = $addon;
    $tableau[$addon['tag']]['status'] = $status;
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

echo erreurs($erreurs);
// SUBNAV
echo '<div id="subnav">'."\n";
    afficher_form_filtre_modules($filtre);
    echo '<div class="nombre-elem">'."\n";
    echo ucfirst(nombre_objets(count($tableau), 'module')).' '.$GLOBALS['lang']['sur'].' '.count($addons);
    echo '</div>'."\n";
echo '</div>'."\n";

afficher_liste_modules($tableau, $filtre);

echo "\n".'<script src="style/javascript.js"></script>'."\n";
echo '<script>';
echo php_lang_to_js(0);
echo 'var csrf_token = \''.new_token().'\'';
echo '</script>';

footer($begin);
