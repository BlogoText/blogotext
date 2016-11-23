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


// temp, put this include somewhere else
include 'filesystem.php';

/**
 *
 */
function addon_clean_cache_path($addonTag)
{
    $path = addon_get_cache_path($addonTag, false);
    if (!is_dir($path)) {
        return true;
    }
    $path = str_replace(array('../', './'), '', $path);
    return rmdir_recursive($path);
}

/**
 * put a .enabled file to an addon
 */
function addon_put_enable_file($file)
{
    return file_put_contents($file, '') !== false;
}

/**
 * (?) return a translated sentence
 */
function addon_get_translation($info)
{
    if (is_array($info)) {
        return $info[$GLOBALS['lang']['id']];
    }
    return $info;
}

/**
 * retrieve and return basic datas about a addon based on
 * _POST[addon_id] and _POST[statut]
 */
function addon_retrieve_posted_addon()
{
    return array (
        'addon_id' => htmlspecialchars($_POST['addon_id']),
        'status' => (isset($_POST['statut']) and $_POST['statut'] == 'on') ? '1' : '0',
    );
}

/**
 * process (check) the submited config change for an addon
 *
 * todo :
 *   - manage errors
 *
 * @param string $addonTag, the addon name
 * @return bool
 */
function addon_edit_settings_form_process($addonTag)
{
    $errors = array();
    $addons_status = addon_list_addons();
    $params = addon_get_conf($addonTag);
    $datas = array();
    foreach ($params as $key => $param) {
        $datas[$key] = '';
        if ($param['type'] == 'bool') {
            $datas[$key] = (int) (isset($_POST[$key]));
        } else if ($param['type'] == 'int') {
            if (isset($_POST[$key]) && is_numeric($_POST[$key])) {
                $value = (int) $_POST[$key];
                if (isset($param['value_min']) && $value < $param['value_min']) {
                    $errors[$key][] = 'Value is behind limit min.';
                } else if (isset($param['value_max']) && $value > $param['value_max']) {
                    $errors[$key][] = 'Value is reach limit max.';
                } else {
                    $datas[$key] = $value;
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
    return (file_put_contents((addon_get_var_path($addonTag).'settings.ini'), $conf) !== false);
}

/**
 * perform action from button
 */
function addon_buttons_action_process($addonTag)
{

    // get info for the addon
    $infos = addon_get_infos($addonTag);

    if (isset($infos['buttons'])) {
        foreach ($infos['buttons'] as $btnId => $btn) {
            if (!isset($_POST[$btnId]) || !function_exists($btn['callback'])) {
                continue;
            }
            call_user_func($btn['callback']);
        }
    }

    // clean module cache ?
    if (isset($_POST['addon_clean_cache'])) {
        addon_clean_cache_path($addonTag);
    }
}

/**
 * Get the addon config form
 *
 * @param string $addonTag, the addon name
 * @return string, the html form
 */
function addon_edit_settings_form($addonTag)
{
    // load addons
    $addons_status = addon_list_addons();
    // get info for the addon
    $infos = addon_get_infos($addonTag);
    // addon is active ?
    $infos['status'] = $addons_status[$addonTag];
    // get addon params
    $params = addon_get_conf($addonTag);

    // button
    $out = '';
    $out .= '<form id="preferences" method="post" action="?addonTag='. $addonTag .'" onsubmit="return confirm(\''. addslashes($GLOBALS['lang']['addons_confirm_buttons_action']) .'\');" >';
    $out .= '<div role="group" class="pref">'; /* no fieldset because browset can’t style them correctly */
    $out .= '<div class="form-legend"><legend class="legend-user">'.$GLOBALS['lang']['addons_settings_legend'].addon_get_translation($infos['name']).'</legend></div>'."\n";
    
    $out .= '<div class="form-lines">'."\n";
    if (isset($infos['buttons'])) {
        foreach ($infos['buttons'] as $btnId => $btn) {
            $out .= '<p>'. form_checkbox($btnId, false, $btn['label'][$GLOBALS['lang']['id']]) .'</p>'."\n";
        }
    }
    $out .= '<p>'. form_checkbox('addon_clean_cache', false, $GLOBALS['lang']['addons_clean_cache_label']) .'</p>'."\n";
    $out .= '</div">'."\n";
        // submit box
    $out .= '<div class="submit-bttns">'."\n";
    $out .= hidden_input('_verif_envoi', '1');
    $out .= hidden_input('token', new_token());
    $out .= hidden_input('action_type', 'buttons');
    $out .= '<input type="hidden" name="addon_action" value="params" />';
    $out .= '<button class="submit button-submit" type="submit" name="enregistrer">'.$GLOBALS['lang']['valider'].'</button>'."\n";
    $out .= '</div>'."\n";
    // END submit box
    $out .= '</div>'."\n";
    $out .= '</div>'."\n";
    $out .= '</form>';

    // settings
    $out .= '<form id="preferences" method="post" action="?addonTag='. $addonTag .'" >';
    $out .= '<div role="group" class="pref">'; /* no fieldset because browset can’t style them correctly */
    $out .= '<div class="form-legend"><legend class="legend-user">'.$GLOBALS['lang']['addons_settings_legend'].addon_get_translation($infos['name']).'</legend></div>'."\n";

    // build the config form
    $out .= '<div class="form-lines">'."\n";
    foreach ($params as $key => $param) {
        $out .= '<p>';
        if ($param['type'] == 'bool') {
            $out .= form_checkbox($key, ($param['value'] === true || $param['value'] == 1), $param['label'][ $GLOBALS['lang']['id'] ]);
        } else if ($param['type'] == 'int') {
            $val_min = (isset($param['value_min'])) ? ' min="'.$param['value_min'].'" ' : '' ;
            $val_max = (isset($param['value_max'])) ? ' max="'.$param['value_max'].'" ' : '' ;
            $out .= "\t".'<label for="'.$key.'">'.$param['label'][ $GLOBALS['lang']['id'] ].'</label>'."\n";
            $out .= "\t".'<input type="number" id="'.$key.'" name="'.$key.'" size="30" '. $val_min . $val_max .' value="'.$param['value'].'" class="text" />'."\n";
        } else if ($param['type'] == 'text') {
            $out .= "\t".'<label for="'.$key.'">'.$param['label'][ $GLOBALS['lang']['id'] ].'</label>'."\n";
            $out .= "\t".'<input type="text" id="'.$key.'" name="'.$key.'" size="30" value="'.$param['value'].'" class="text" />'."\n";
        } else if ($param['type'] == 'select') {
            $out .= "\t".'<label for="'.$key.'">'.$param['label'][ $GLOBALS['lang']['id'] ].'</label>'."\n";
            $out .= "\t".'<select id="'.$key.'" name="'.$key.'">'."\n";
            foreach ($param['options'] as $opt_key => $label_lang) {
                $selected = ($opt_key == $param['value']) ? ' selected' : '';
                $out .= "\t\t".'<option value="'. $opt_key .'"'. $selected .'>'. $label_lang[ $GLOBALS['lang']['id'] ] .'</option>';
            }
            $out .= "\t".'</select>'."\n";
        }
        $out .= '</p>';
    }
    $out .= '</div>';
    // submit box
    $out .= '<div class="submit-bttns">'."\n";
    $out .= hidden_input('_verif_envoi', '1');
    $out .= hidden_input('token', new_token());
    $out .= hidden_input('action_type', 'settings');
    $out .= '<input type="hidden" name="addon_action" value="params" />';
    $out .= '<button class="submit button-cancel" type="button" onclick="annuler(\'addons.php\');" >'.$GLOBALS['lang']['annuler'].'</button>'."\n";
    $out .= '<button class="submit button-submit" type="submit" name="enregistrer">'.$GLOBALS['lang']['enregistrer'].'</button>'."\n";
    $out .= '</div>'."\n";
    // END submit box
    $out .= '</div>'."\n";
    $out .= '</div>'."\n";
    $out .= '</form>';

    return $out;
}

/**
 * addon as a config ?
 *
 * @return bool
 */
function addon_have_settings($addonTag)
{
    $infos = addon_get_infos($addonTag);
    if ($infos === false) {
        return false;
    }
    return (isset($infos['settings']) && count($infos['settings']) > 0);
}

/**
 * return the main list of addon
 */
function addon_show_list_addons($tableau, $filtre)
{
    if (!empty($tableau)) {
        $out = '<ul id="modules">'."\n";
        foreach ($tableau as $i => $addon) {
            // addon
            $out .= "\t".'<li>'."\n";
            // addon checkbox activation
            $out .= "\t\t".'<span><input type="checkbox" class="checkbox-toggle" name="module_'.$i.'" id="module_'.$i.'" '.(($addon['status']) ? 'checked' : '').' onchange="activate_mod(this);" /><label for="module_'.$i.'"></label></span>'."\n";

            // addon name
            $out .= "\t\t".'<span><a href="addons.php?addon_id='.$addon['tag'].'">'.addon_get_translation($addon['name']).'</a></span>'."\n";

            // addon version
            $out .= "\t\t".'<span>'.$addon['version'].'</span>'."\n";

            $out .= "\t".'</li>'."\n";

            // other infos and params
            $out .= "\t".'<div>'."\n";

            // addon tag
            if (function_exists('addon_'.$addon['tag'])) {
                $out .= "\t\t".'<p><code title="'.$GLOBALS['lang']['label_code_theme'].'">'.'{addon_'.$addon['tag'].'}'.'</code>'.addon_get_translation($addon['desc']).'</p>'."\n";
            } else {
                $out .= "\t\t".'<p>'.$GLOBALS['lang']['label_no_code_theme'].'</p>';
            }
            $out .= "\t\t".'<p>';

            // addon params
            if (addon_have_settings($addon['tag'])) {
                $out .= '<a href="addon.php?addonTag='. $addon['tag'] .'">'.$GLOBALS['lang']['addons_settings_link_title'].'</a>';
                if (!empty($addon['url'])) {
                    $out .= ' | ';
                }
            }

            // author URL
            if (!empty($addon['url'])) {
                $out .= '<a href="'.$addon['url'].'">'.$GLOBALS['lang']['label_owner_url'].'</a>';
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

/**
 * (?) proceed submitted form from addon_show_list_addons()
 */
function addon_show_list_addons_form_proceed($module)
{
    $erreurs = array();
    $path = BT_ROOT.DIR_ADDONS;
    $check_file = sprintf('%s.enabled', addon_get_var_path($module['addon_id']));
    $is_enabled = is_file($check_file);
    $new_status = (bool) $module['status'];

    if ($is_enabled != $new_status) {
        if ($new_status) {
            // Addon enabled: we create .enabled
            if (!addon_put_enable_file($check_file)) {
                $erreurs[] = sprintf($GLOBALS['lang']['err_addon_enabled'], $module['addon_id']);
            }
        } else {
            // Addon disabled: we delete .enabled
            if (!unlink($check_file)) {
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

// TODO: at the end, put this in "afficher_form_filtre()"
/**
 *
 */
function addon_show_list_addons_form_filters($filtre)
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
