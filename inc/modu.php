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
 * addon as a config ?
 *
 * @return bool
 */
function addon_has_conf($addonName)
{
    $infos = addon_get_infos($addonName);
    if ($infos === false) {
        return false;
    }
    return (isset($infos['config']) && count($infos['config']) > 0);
}

/**
 * get the config of an addon
 *
 * @param string $addonName, the addon name
 * @return array
 */
function addon_get_conf($addonName)
{
    $infos = addon_get_infos($addonName);
    if ($infos === false) {
        return false;
    }
    $saved = array();
    $file_path = BT_ROOT.DIR_ADDONS.'/'.$addonName.'/params.ini';
    if (is_file($file_path) and is_readable($file_path)) {
        $t = parse_ini_file($file_path);
        foreach ($t as $option => $value) {
            $saved[$option] = $value;
        }
    }
    if (isset($infos['config'])) {
        if (!is_array($saved)) {
            return $infos['config'];
        } else {
            foreach ($infos['config'] as $key => $vals) {
                $infos['config'][$key]['value'] = (isset($saved[$key])) ? $saved[$key] : $vals['value'] ;
            }
            return $infos['config'];
        }
    }
    return array();
}

/**
 * get addon informations
 *
 * @param string $addonName, the addon name
 * @return array||false, false if addon not found/loaded...
 */
function addon_get_infos($addonName)
{
    foreach ($GLOBALS['addons'] as $k) {
        if ($k['tag'] == $addonName) {
            return $k;
        }
    }
    return false;
}

/**
 * process (check) the submited config change for an addon
 *
 * todo :
 *   - manage errors
 *
 * @param string $addonName, the addon name
 * @return bool
 */
function addon_edit_params_process($addonName)
{
    $errors = array();
    $addons_status = list_addons();
    $params = addon_get_conf($addonName);
    $datas = array();
    foreach ($params as $key => $param) {
        $datas[$key] = '';
        if ($param['type'] == 'bool') {
            $datas[$key] = (isset($_POST[$key]));
        } else if ($param['type'] == 'int') {
            if (isset($_POST[$key]) && is_numeric($_POST[$key])) {
                if (isset($param['value_min']) && $param['value_min'] >= $_POST[$key]) {
                    $errors[$key][] = 'Value is behind limit min.';
                } else if (isset($param['value_max']) && $param['value_max'] <= $_POST[$key]) {
                    $errors[$key][] = 'Value is reach limit max.';
                } else {
                    $datas[$key] = htmlentities($_POST[$key], ENT_QUOTES);
                }
            } else {
                // error
                $errors[$key][] = 'No data posted';
            }
        } else if ($param['type'] == 'text') {
            $datas[$key] = htmlentities($_POST[$key], ENT_QUOTES);
        } else if ($param['type'] == 'select') {
            if (isset($param['options'][$_POST[$key]])) {
                $datas[$key] = htmlentities($_POST[$key], ENT_QUOTES);
            } else {
                $errors[$key][] = 'not a valid type';
            }
        } else {
            // error
            $errors[$key][] = 'not a valid type';
        }
    }
    $conf  = '';
    $conf .= '; <?php die(); /*'."\n\n";
    $conf .= '; This file contains addons params, you can modify this file.'."\n\n";
    foreach ($datas as $key => $value) {
        $conf .= $key .' = \''. $value .'\''."\n";
    }
    $conf .= '; */ ?>'."\n";
    return (file_put_contents(BT_ROOT.DIR_ADDONS.'/'.$addonName.'/params.ini', $conf) !== false);
}

/**
 * Get the addon config form
 *
 * @param string $addonName, the addon name
 * @return string, the html form
 */
function addon_edit_params_form($addonName)
{
    // load addons
    $addons_status = list_addons();
    // get info for the addon
    $infos = addon_get_infos($addonName);
    // addon is active ?
    $infos['status'] = $addons_status[$addonName];
    // get addon params
    $params = addon_get_conf($addonName);

    $return .= '<form id="preferences" method="post" action="?addonName='. $addonName .'" >';
    $return .= '<div role="group" class="pref" style="margin-top:0;">';
    afficher_liste_modules(array($infos['tag'] => $infos), '');

    // build the config form
    $return .= '<div class="form-lines">'."\n";
    foreach ($params as $key => $param) {
        $return .= '<p>';
        if ($param['type'] == 'bool') {
            $return .= form_checkbox($key, ($param['value'] === true || $param['value'] == 1), $param['label'][ $GLOBALS['lang']['id'] ]);
        } else if ($param['type'] == 'int') {
            $val_min = (isset($param['value_min'])) ? ' min="'.$param['value_min'].'" ' : '' ;
            $val_max = (isset($param['value_max'])) ? ' max="'.$param['value_max'].'" ' : '' ;
            $return .= "\t".'<label for="'.$key.'">'.$param['label'][ $GLOBALS['lang']['id'] ].'</label>'."\n";
            $return .= "\t".'<input type="number" id="'.$key.'" name="'.$key.'" size="30" '. $val_min . $val_max .' value="'.$param['value'].'" class="text" />'."\n";
        } else if ($param['type'] == 'text') {
            $return .= "\t".'<label for="'.$key.'">'.$param['label'][ $GLOBALS['lang']['id'] ].'</label>'."\n";
            $return .= "\t".'<input type="text" id="'.$key.'" name="'.$key.'" size="30" value="'.$param['value'].'" class="text" />'."\n";
        } else if ($param['type'] == 'select') {
            $return .= "\t".'<label for="'.$key.'">'.$param['label'][ $GLOBALS['lang']['id'] ].'</label>'."\n";
            $return .= "\t".'<select id="'.$key.'" name="'.$key.'">'."\n";
            // var_dump( $param['value'] );
            foreach ($param['options'] as $opt_key => $label_lang) {
                $selected = ($opt_key == $param['value']) ? ' selected' : '';
                $return .= "\t\t".'<option value="'. $opt_key .'"'. $selected .'>'. $label_lang[ $GLOBALS['lang']['id'] ] .'</option>';
            }
            $return .= "\t".'</select>'."\n";
        }
        $return .= '</p>';
    }
    $return .= '</div>';
    // submit box
    $return .= '<div class="submit-bttns">'."\n";
    $return .= hidden_input('_verif_envoi', '1');
    $return .= hidden_input('token', new_token());
    $return .= '<input type="hidden" name="addon_action" value="params" />';
    $return .= '<button class="submit button-cancel" type="button" onclick="annuler(\'preferences.php\');" >'.$GLOBALS['lang']['annuler'].'</button>'."\n";
    $return .= '<button class="submit button-submit" type="submit" name="enregistrer">'.$GLOBALS['lang']['enregistrer'].'</button>'."\n";
    $return .= '</div>'."\n";
    // END submit box
    $return .= '</div>';
    $return .= '</form>';
    return $return;
}

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
            // addon
            $out .= "\t".'<li>'."\n";
            // addon checkbox activation
            $out .= "\t\t".'<span><input type="checkbox" class="checkbox-toggle" name="module_'.$i.'" id="module_'.$i.'" '.(($addon['status']) ? 'checked' : '').' onchange="activate_mod(this);" /><label for="module_'.$i.'"></label></span>'."\n";

            // addon name
            $out .= "\t\t".'<span><a href="modules.php?addon_id='.$addon['tag'].'">'.addon_get_translation($addon['name']).'</a></span>'."\n";

            // addon version
            $out .= "\t\t".'<span>'.$addon['version'].'</span>'."\n";

            $out .= "\t".'</li>'."\n";

            // other infos and params
            $url = '';
            $out .= "\t".'<div>'."\n";

            // addon tag
            if (function_exists('addon_'.$addon['tag'])){
                $out .= "\t\t".'<p><code title="'.$GLOBALS['lang']['label_code_theme'].'">'.'{addon_'.$addon['tag'].'}'.'</code>'.addon_get_translation($addon['desc']).'</p>'."\n";
            } else {
                $out .= "\t\t".'<p>'.$GLOBALS['lang']['label_no_code_theme'].'</p>';
            }
            $out .= "\t\t".'<p>';

            // addon params
            if (addon_has_conf($addon['tag'])) {
                $out .= '<a href="module.php?addonName='. $addon['tag'] .'">params</a>';
            }

            // author URL
            if (!empty($addon['url'])) {
                $out .= ' <a href="'.$addon['url'].'">'.$GLOBALS['lang']['label_owner_url'].'</a>';
            }
            $out .= '</p>'."\n";
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
