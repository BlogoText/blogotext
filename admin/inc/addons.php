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

/**
 * return the absolute and clean path
 * used for debug and for security
 *
 * @param string $path, the absolute path from your BT directory
 * @param bool $check, run some check, and correct if possible (recommended for dev/debug use only !)
 * @param bool $alert, show alert if something got wrong (recommended for dev/debug use only !)
 * @return bool|string, the absolute path for your host
 */
function get_path($path, $check = false, $alert = false)
{
    if ($check === true) {
        if (strpos($path, '/') !== 0) {
            if ($alert === true) {
                var_dump('get_path() : path not starting with "/" ('. $path .')');
            }
            return false;
        }
        if (strpos($path, BT_DIR) === 0) {
            if ($alert === true) {
                var_dump('get_path() : seem\'s already an absolute path ('. $path .')');
            }
            return false;
        }
        if (strpos($path, './') !== false) {
            if ($alert === true) {
                var_dump('get_path() : use of "./" or "../", try to hack ? ('. $path .')');
            }
            return false;
        }
    }

    $return = BT_DIR .'/'. $path;
    $return = str_replace(array('/', '\\', '/\\'), '/', $return);
    while (strstr($return, '\\\\')) {
        $return = str_replace('\\\\', '\\', $return);
    }
    while (strstr($return, '//')) {
        $return = str_replace('//', '/', $return);
    }
    return $return;
}


/**
 * Note: put here because no one else use it.
 *
 * Like rmdir, but recursive
 *
 * use of get_path(), try to prevent the end of the world...
 *
 * @params string $path, the relative path to BT_DIR
 */
function rmdir_recursive($path)
{
    // TODO FIX: use of get_path() ?
    $abs = get_path($path);
    $dir = opendir($abs);
    while (($file = readdir($dir)) !== false) {
        if (($file == '.') || ($file == '..')) {
            continue;
        }
        if (is_dir($abs.$file.'/')) {
            rmdir_recursive($path.$file.'/');
        } else {
            unlink($abs.$file);
        }
    }
    closedir($dir);
    rmdir($abs);
}


/**
 * list all addons
 */
function addon_list_addons()
{
    $addons = array();

    if (is_dir(DIR_ADDONS)) {
        // get the list of installed addons
        $addons_list = rm_dots_dir(scandir(DIR_ADDONS));

        // include the addons
        foreach ($addons_list as $addon) {
            $inc = sprintf('%s/%s/%s.php', DIR_ADDONS, $addon, $addon);
            if (is_file($inc)) {
                $addons[$addon] = addon_is_enabled($addon);
                require_once $inc;
            }
        }
    }

    return $addons;
}


/**
 *
 */
function addon_clean_cache_path($addon)
{
    $path = addon_get_cache_path($addon, false);
    if (!is_dir($path)) {
        return true;
    }
    $path = str_replace(array('../', './'), '', $path);
    return rmdir_recursive($path);
}

/**
 * Enables an addon.
 */
function addon_put_enable_file($addon)
{
    $file = sprintf('%s.enabled', addon_get_var_path($addon, true));

    return file_put_contents($file, '', LOCK_EX) !== false;
}

/**
 * Checks if an addon is enabled.
 */
function addon_is_enabled($addon)
{
    return is_file(sprintf('%s.enabled', addon_get_var_path($addon, true)));
}

/**
 * Returns a translated sentence.
 */
function addon_get_translation($info)
{
    if (is_array($info) && isset($info[$GLOBALS['lang']['id']])) {
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
        'status' => (isset($_POST['statut']) and $_POST['statut'] == 'on') ? 1 : 0,
    );
}

/**
 * process (check) the submited config change for an addon
 *
 * todo :
 *   - manage errors
 *
 * @param string $addon, the addon name
 * @return bool
 */
function addon_edit_settings_form_process($addon)
{
    $errors = array();
    $params = addon_get_conf($addon);
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
                    $datas[$key] = (int)$value;
                }
            } else {
                // error
                $errors[$key][] = 'No data posted';
            }
        } else if ($param['type'] == 'text') {
            $datas[$key] = '\''.htmlentities($_POST[$key], ENT_QUOTES).'\'';
        } else if ($param['type'] == 'select') {
            if (isset($param['options'][$_POST[$key]])) {
                $datas[$key] = '\''.htmlentities($_POST[$key], ENT_QUOTES).'\'';
            } else {
                $errors[$key][] = 'not a valid type';
            }
        } else {
            // error
            $errors[$key][] = 'not a valid type';
        }
    }
    $conf  = '; <?php die(); ?>'."\n";
    $conf .= '; This file contains addons params, you can modify this file.'."\n\n";
    foreach ($datas as $key => $value) {
        $conf .= $key .' = ' .$value ."\n";
    }
    return (file_put_contents((addon_get_var_path($addon, true).'settings.ini'), $conf, LOCK_EX) !== false);
}

/**
 * perform action from button
 */
function addon_buttons_action_process($addon)
{
    // get info for the addon
    $infos = addon_get_infos($addon);

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
        addon_clean_cache_path($addon);
    }
}

/**
 * Get the addon config form
 *
 * @param string $addon, the addon name
 * @return string, the html form
 */
function addon_edit_settings_form($addon)
{
    $inc = sprintf('%s/%s/%s.php', DIR_ADDONS, $addon, $addon);
    if (!is_file($inc)) {
        return;
    }
    require_once $inc;
    // get info for the addon
    $infos = addon_get_infos($addon);
    // addon is active ?
    $infos['status'] = addon_is_enabled($addon);
    // get addon params
    $params = addon_get_conf($addon);

    // button
    $out = '';
    $out .= '<form id="preferences" method="post" action="?addon='. $addon .'" onsubmit="return confirm(\''. addslashes($GLOBALS['lang']['addons_confirm_buttons_action']) .'\');" >';
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
    $out .= '<form id="preferences" method="post" action="?addon='. $addon .'" >';
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
function addon_have_settings($addon)
{
    $infos = addon_get_infos($addon);
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
            $out .= "\t\t".'<span>'.addon_get_translation($addon['name']).'</span>'."\n";

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
                $out .= '<a href="addon-settings.php?addon='. $addon['tag'] .'">'.$GLOBALS['lang']['addons_settings_link_title'].'</a>';
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

function addons_export_list($data)
{
    $data = '<?php return '.var_export($data, true).';';

    return file_put_contents(ADDONS_DB, $data, LOCK_EX) !== false;
}

/**
 * (?) proceed submitted form from addon_show_list_addons()
 */
function addon_show_list_addons_form_proceed($addon)
{
    $erreurs = array();
    $check_file = sprintf('%s.enabled', addon_get_var_path($addon['addon_id']));
    $is_enabled = is_file($check_file);
    $new_status = (bool) $addon['status'];

    if ($is_enabled != $new_status) {
        if ($new_status) {
            // Addon enabled: we create .enabled
            if (!addon_put_enable_file($addon['addon_id'])) {
                $erreurs[] = sprintf($GLOBALS['lang']['err_addon_enabled'], $addon['addon_id']);
            }
        } else {
            // Addon disabled: we delete .enabled
            if (!unlink($check_file)) {
                $erreurs[] = sprintf($GLOBALS['lang']['err_addon_disabled'], $addon['addon_id']);
            }
        }

        // Save addons list
        $addons_ = include ADDONS_DB;
        $addons_[$addon['addon_id']] = (int)$new_status;
        addons_export_list($addons_);
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
