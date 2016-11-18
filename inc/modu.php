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
function list_addons()
{
    $addons = array();
    $path = BT_ROOT.DIR_ADDONS;

    if (is_dir($path)) {
        // get the list of installed addons
        $addons_list = rm_dots_dir(scandir($path));

        // include the addons
        foreach ($addons_list as $addon) {
            $inc = sprintf('%s/%s/%s.php', $path, $addon, $addon);
            $is_enabled = !is_file(sprintf('%s/%s/.disabled', $path, $addon));
            if (is_file($inc)) {
                $addons[$addon] = $is_enabled;
                include_once $inc;
            }
        }
    }

    return $addons;
}

function addon_get_translation($info)
{
    if (is_array($info)) {
        return $info[$GLOBALS['lang']['id']];
    }
    return $info;
}

function afficher_liste_modules($tableau, $filtre)
{
    if (!empty($tableau)) {
        $out = '<ul id="modules">'."\n";
        foreach ($tableau as $i => $addon) {
            // DESCRIPTION
            $out .= "\t".'<li>'."\n";
            // CHECKBOX POUR ACTIVER
            $out .= "\t\t".'<span><input type="checkbox" class="checkbox-toggle" name="module_'.$i.'" id="module_'.$i.'" '.(($addon['status']) ? 'checked' : '').' onchange="activate_mod(this);" /><label for="module_'.$i.'"></label></span>'."\n";

            // NOM DU MODULE
            $out .= "\t\t".'<span><a href="modules.php?addon_id='.$addon['tag'].'">'.addon_get_translation($addon['name']).'</a></span>'."\n";

            // VERSION
            $out .= "\t\t".'<span>'.$addon['version'].'</span>'."\n";

            $out .= "\t".'</li>'."\n";

            // AUTRES INFOS
            $url = '';
            $out .= "\t".'<div>'."\n";
            $out .= "\t\t".'<p><code title="'.$GLOBALS['lang']['label_code_theme'].'">'.'{addon_'.$addon['tag'].'}'.'</code>'.addon_get_translation($addon['desc']).'</p>'."\n";
            if (!empty($addon['url'])) {
                $out .= "\t\t".'<p><a href="'.$addon['url'].'">'.$GLOBALS['lang']['label_owner_url'].'</a>';
            }
            $out .= '</div>'."\n";
        }
        $out .= '</ul>'."\n\n";
    } else {
        $out = info($GLOBALS['lang']['note_no_module']);
    }

    echo $out;
}

// TODO: at the end, put this in "afficher_form_filtre()"
function afficher_form_filtre_modules($filtre)
{
    $ret = '<div id="form-filtre">'."\n";
    $ret .= '<form method="get" action="'.basename($_SERVER['SCRIPT_NAME']).'" onchange="this.submit();">'."\n";
    $ret .= "\n".'<select name="filtre">'."\n" ;
    // TOUS
    $ret .= '<option value="all"'.(($filtre == '') ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_all'].'</option>'."\n";
    // ACTIVÉS
    $ret .= '<option value="enabled"'.(($filtre == 'enabled') ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_enabled'].'</option>'."\n";
    // DÉSACTIVÉS
    $ret .= '<option value="disabled"'.(($filtre == 'disabled') ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_disabled'].'</option>'."\n";
    $ret .= '</select> '."\n\n";
    $ret .= '</form>'."\n";
    $ret .= '</div>'."\n";
    echo $ret;
}

function traiter_form_module($module)
{
    $erreurs = array();
    $path = BT_ROOT.DIR_ADDONS;
    $check_file = sprintf('%s/%s/.disabled', $path, $module['addon_id']);
    $is_enabled = !is_file($check_file);
    $new_status = (bool) $module['status'];

    if ($is_enabled != $new_status) {
        if ($new_status) {
            // Module activé, on supprimer le fichier .disabled
            if (unlink($check_file) === false) {
                $erreurs[] = sprintf($GLOBALS['lang']['err_addon_enabled'], $module['addon_id']);
            }
        } else {
            // Module désactivé, on crée le fichier .disabled
            if (fichier_module_disabled($check_file) === false) {
                $erreurs[] = sprintf($GLOBALS['lang']['err_addon_disabled'], $module['addon_id']);
            }
        }
    }

    if (isset($_POST['mod_activer'])) {
        if (empty($erreurs)) {
            die('Success'.new_token());
        } else {
            die('Error'.new_token().implode("\n", $erreurs));
        }
    }

    return $erreurs;
}

function init_post_module()
{
    return array (
        'addon_id' => htmlspecialchars($_POST['addon_id']),
        'status' => (isset($_POST['statut']) and $_POST['statut'] == 'on') ? '1' : '0',
    );
}
