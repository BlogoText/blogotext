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
 * dev notes (RemRem)
 *
 * complete réécriture des addons :
 *  - les fonctions sont volontairement éclatées, le temps de faire le debug/tests
 *  - on fera le ménage/refactor/séparation public/admin aprés validation par la communauté
 *  - attention, beaucoup de fonction interdépendante entre les fonctions
 *  - pas optimisé !
 *
 * ré-organisation des noms tel que :
 *
 *   function addon_* -> pour 1 addon
 *   function addons_* -> pour un ensemble de addons
 *   function addon(s)_test_* // must return bool
 *   function addon(s)_get_*
 *   function addon(s)_set_*
 *   function addon(s)_load_* chargement d'addons(s)
 *   function addons_list_* retourne une liste d'addons(s)
 *   ...
 */




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
                // var_dump('get_path() : path not starting with "/" ('. $path .')');
            }
            return false;
        }
        if (strpos($path, BT_ROOT) === 0) {
            if ($alert === true) {
                // var_dump('get_path() : seem\'s already an absolute path ('. $path .')');
            }
            return false;
        }
        if (strpos($path, './') !== false) {
            if ($alert === true) {
                // var_dump('get_path() : use of "./" or "../", try to hack ? ('. $path .')');
            }
            return false;
        }
    }

    $return = BT_ROOT .'/'. str_replace(BT_ROOT, '', $path);
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






// addons -> INIT

/**
 * init the addon system for the public side
 *
 * return true||array
 *           true : ok, no problem
 *           array : list of error
 */
function addons_init_public()
{
    $db = addons_db_get();
    $errors = array();

    // dirty, if no cache
    // todo : refactor
    if ($db === false) {
        // no cache, delete threw ftp ?
        if (!addons_db_refresh()) {
            $errors[] = 'error on addons_db_refresh';
            return $errors;
        }

        $db = addons_db_get();
    }

    // no cache, and cache build fail, user must check
    // not a big deal, just no addons
    if ($db === false) {
        $errors[] = 'Fail to set cache or cache not valid, no addon will be loaded';
        return $errors;
    }

    $to_load = array();

    // filter the enabled
    foreach ($db as $id => $addon) {
        // if not enabled
        if (!$addon['enabled']) {
            continue;
        }
        // if user delete .enabled or the addon threw ftp
        if (!addon_test_enabled($id)) {
            continue;
        }
        // seem's good
        $to_load[$id] = $addon;
    }

    // load
    foreach ($to_load as $id => $addon) {
        if (($loaded = addon_load($id, $addon)) !== true) {
            $errors[] = $loaded;
        }
    }

    // push hook
    addon_hook_push();

    return (count($errors) !== 0) ? $errors : true ;
}


// addons -> LIST

/**
 * list all addons
 * { 'addon_1' , 'addon_2' , 'addon_3' }
 */
function addons_list_all($as_key = false)
{
    $addons = array();

    // if no addon, not a big deal
    if (!is_dir(DIR_ADDONS)) {
        return $addons;
    }

    if ($as_key === true) {
        foreach (glob(DIR_ADDONS.'*', GLOB_ONLYDIR) as $dir) {
            $name = basename($dir);
            if (addon_test_exists($name)) {
                $addons[$name] = array();
            }
        }
    } else {
        foreach (glob(DIR_ADDONS.'*', GLOB_ONLYDIR) as $dir) {
            $name = basename($dir);
            if (addon_test_exists($name)) {
                $addons[] = $name;
            }
        }
    }

    return $addons;
}

/**
 * return the list of disabled addons
 * { 'addon_2' }
 */
function addons_list_disabled()
{
    $addons = array();

    foreach (addons_list_all(false) as $addon) {
        if (!addon_test_enabled($addon)) {
            $addons[] = $addon;
        }
    }

    return $addons;
}

/**
 * return the list of enabled addons
 * { 'addon_1' , 'addon_3' }
 */
function addons_list_enabled()
{
    $addons = array();

    foreach (addons_list_all(false) as $addon) {
        if (addon_test_enabled($addon)) {
            $addons[] = $addon;
        }
    }

    return $addons;
}

/**
 * return a basic list of addon with the status
 * { 'addon_1' => true , 'addon_2' => false }
 */
function addons_list_status()
{
    $addons = array();
    foreach (addons_list_all() as $addon) {
        $addons[$addon] = addon_test_enabled($addon);
    }
}


// addons -> test

/**
 * like function_exists but for addon
 * check the /var/{vhost}/addons/{$addon_id}/{$addon_id}.php addon file is here
 */
function addon_test_exists($addon_id)
{
    return is_file(addon_get_addon_file_path($addon_id));
}

/**
 * is enabled ?
 * check if enabled file of an addon is present
 */
function addon_test_enabled($addon_id)
{
    return (is_file(addon_get_enabled_file_path($addon_id)));
}

/**
 * dev note
 * - se base sur la version majeure de BT
 *
 * Test the compatibility between blogotext and an addon
 *
 * @param string $addon_version_addon, la version venant de l'addon
 * @return bool, is compliant ?
 */
function addon_test_compliancy($addon_compliancy)
{
    // test if BT is a dev version
    if (strpos(BLOGOTEXT_VERSION, '-dev') !== false) {
        // it's dev, so ok, dev
        return true;
    }

    $bt_major_version = explode('.', BLOGOTEXT_VERSION, 2);
    return ((int)version_compare($addon_compliancy, $bt_major_version['0']) !== 1);
}

/**
 * test if this is the same version as in the db
 *
 * @return bool, true : same, false : different
 */
function addon_test_versions($version_1, $version_2)
{
    return version_compare($version_1, $version_2, '==');
    // return ($version_1 != $version_2);
}

/**
 * test the declaration of an addon
 *
 * @return true||string, true : ok, addon loaded
 *                       string : fail, message error
 */
function addon_test_declaration($addon_id, $declaration)
{
    // array ?
    if (!is_array($declaration)) {
        log_error('addon '. $addon_id .' not a valid decalaration');
        return 'undefined declaration ('. $addon_id .')';
    }
    // test tag
    if (!isset($declaration['tag']) || empty($declaration['tag']) || $addon_id != $declaration['tag']) {
        log_error('addon '. $addon_id .' fail on tag test '. $declaration['tag']);
        return 'undefined tag or not valid ('. $addon_id .')';
    }
    // check if has title
    if (!isset($declaration['name']) || !isset($declaration['name']['en']) || empty(trim($declaration['name']['en']))) {
        log_error('addon '. $addon_id .' fail on name test');
        return 'addon require a name (at least in english) ('. $addon_id .')';
    }
    // check if has description
    if (!isset($declaration['desc']) || !isset($declaration['desc']['en']) || empty(trim($declaration['desc']['en']))) {
        log_error('addon '. $addon_id .' fail on desc test');
        return 'addon require a desc (at least in english) ('. $addon_id .')';
    }
    // test version
    if (!isset($declaration['version']) || empty($declaration['version'])) {
        log_error('addon '. $addon_id .' fail on version test');
        return 'undefined version ('. $addon_id .')';
    }
    // test version
    if (!isset($declaration['compliancy']) || empty($declaration['compliancy'])) {
        log_error('addon '. $addon_id .' fail on compliancy test');
        return 'undefined compliancy ('. $addon_id .')';
    }

    return true;
}


// addon -> load

/**
 * load a specific addon
 *
 * this run some tests to be sure to load a well formated addon
 *
 * USE THIS FUNCTION TO LOAD AN ADDON
 *
 * @return true||string, true : ok, addon loaded
 *                       string : fail, message error
 */
function addon_load($addon_id, $db_declaration = null)
{
    $declaration = array();
    $new_version = false;
    $already_loaded = (isset($GLOBALS['addons'][$addon_id]) && isset($GLOBALS['addons'][$addon_id]['_loaded']));
    $message = '';

    // already loaded ?
    if ($already_loaded === true) {
        // but no forced declaration, so nothing new ? no need to continue
        if ($db_declaration === null) {
            return true;
        }

        // push the current declaration as the addon declaration
        $declaration = $GLOBALS['addons'][$addon_id];
        // this is version !== than in db ?
        if (!addon_test_versions($declaration['version'], $db_declaration['version'])
        ) {
            // not really an error...
            log_error('[Addon updated] new version of addon '.$addon_id.' have been detected');
            $message = '[Addon updated] ';
            $new_version = true;
        }
    } else {
        // load the addon et get addon declaration
        require_once addon_get_addon_file_path($addon_id);
    }

    // test declaration
    if (($test = addon_test_declaration($addon_id, $declaration)) !== true) {
        $message .= $test;
        log_error($message);
        return $message;
    }

    // test compliancy
    if (!addon_test_compliancy($declaration['compliancy'])) {
        $message .= 'Addon not valid compliancy ('.$addon_id.')';
        log_error($message);
        return $message;
    }

    // set declaration in global
    $GLOBALS['addons'][$addon_id] = $declaration;
    $GLOBALS['addons'][$addon_id]['enabled'] = addon_test_enabled($addon_id);
    $GLOBALS['addons'][$addon_id]['_loaded'] = true; // mark it loaded
    if ($already_loaded !== true) {
        $GLOBALS['addons'][$addon_id]['settings'] = addon_get_settings($addon_id, $declaration);
    }

    // new version of an addon
    if ($new_version === true) {
        // not really an error...
        log_error('[Addon updated] The new version of addon '.$addon_id.' seem\'s valid.');
        // refresh cache
        if (addons_db_refresh() !== true) {
            log_error('[Addon updated] Fail to refresh the Addon db');
        }
    }

    return true;
}


// addons -> load

/**
 * load only enabled addons
 *
 * @return int the counter of loaded addons (for dev purpose)
 */
function addons_load_enabled()
{
    $errors = array();

    foreach (addons_list_enabled() as $addon) {
        if (($loaded = addon_load($addon)) !== true) {
            $errors[] = $loaded;
        }
    }
    return (count($errors) === 0) ? true : $errors;
}

/**
 * load all addons
 *
 * @return int the counter of loaded addons (for dev purpose)
 */
function addons_load_all()
{
    $i = 0;
    foreach (addons_list_all() as $addon) {
        if (addon_load($addon)) {
            ++$i;
        }
    }

    return $i;
}


// addon -> get

/**
 * get 1 setting, addon must be loaded
 * if addon not loaded or setting do not exist return null
 *
 * return mixed, null : fail, other (bool,string...) can be considered as valid value
 */
function addon_get_setting($addon_id, $setting_id)
{
    if (!isset($GLOBALS['addons'][$addon_id])
     || !isset($GLOBALS['addons'][$addon_id]['_loaded'])
     || !isset($GLOBALS['addons'][$addon_id]['settings'][$setting_id])
     || !isset($GLOBALS['addons'][$addon_id]['settings'][$setting_id]['value'])
    ) {
        return null;
    }
    return $GLOBALS['addons'][$addon_id]['settings'][$setting_id]['value'];
}

/**
 * get all settings for an addon
 *
 * TODO : Need to be more desc
 */
function addon_get_settings($addon_id, $declaration = null)
{
    if (is_null($declaration) && isset($GLOBALS['addons'][$addon_id]) && isset($GLOBALS['addons'][$addon_id]['_loaded'])) {
        $declaration = $GLOBALS['addons'][$addon_id];
    }

    // addon dont have $GLOBALS['addons'][]['setting']
    if (!isset($declaration['settings']) || is_null($declaration['settings']) || !is_array($declaration['settings'])) {
        return array();
    }

    // if user have saved settings
    // todo : replace the old ini files (no data in .ini)
    $user_file_path = addon_get_vhost_path($addon_id).'settings.php';

    if (is_file($user_file_path)) {
        $saved_settings = array();
        $t = include $user_file_path;

        foreach ($t as $option => $value) {
            $saved_settings[$option] = htmlspecialchars($value, ENT_QUOTES);
        }

        if (is_array($saved_settings)) {
            foreach ($declaration['settings'] as $key => &$vals) {
                // if saved setting, overwrite the default
                // if ($vals['type'] == 'bool') {
                    // $vals['value'] = (isset($saved_settings[$key]) || $saved_settings[$key] == 1);
                // } else {
                    $vals['value'] = $saved_settings[$key];
                // }
            }
        }
    }

    return $declaration['settings'];
}

/**
 * return the path of .enabled file of an addon
 *
 * @param string $addon_id
 * @param bool $create_fold, create addon folder
 * @return string
 */
function addon_get_enabled_file_path($addon_id, $create_fold = false)
{
    return sprintf('%s.enabled', addon_get_vhost_path($addon_id, $create_fold));
}

/**
 * get the path of the /var/{vhost}/addons/{$addon_id}/{$addon_id}.php addon file
 */
function addon_get_addon_file_path($addon_id)
{
    return sprintf('%s%s/%s.php', DIR_ADDONS, $addon_id, $addon_id);
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
 * return the var path for an addon
 * /var/{vhost}/addon/{addon_id}/
 *
 * @param string $addon_id
 * @param bool $check_create, create folder if doesn't exists
 * @return string|false
 */
function addon_get_vhost_path($addon_id, $check_create = false)
{
    $path = DIR_VHOST_ADDONS.$addon_id.'/';
    if ($check_create === true && !create_folder($path)) {
        return false;
    }
    return $path;
}

/**
 * get addon cache path
 *
 * @param string $addon_id
 * @param bool $create, create folder if doesn't exists
 * @return string|false
 */
function addon_get_vhost_cache_path($addon_id, $create = true)
{
    $path = DIR_VHOST_CACHE.'addon-'.$addon_id.'/';
    if ($create === true && !create_folder($path, true, true)) {
        return false;
    }
    return $path;
}


// addon -> set

/**
 * set the enabled file og an addon
 */
function addon_set_enabled($addon_id)
{
    return (file_put_contents(addon_get_enabled_file_path($addon_id, true), '', LOCK_EX) !== false);
}

/**
 * remove the enabled file og an addon
 */
function addon_set_disabled($addon_id)
{
    $file = addon_get_enabled_file_path($addon_id);
    if (!is_file($file)) {
        return true;
    }
    return unlink($file);
}

/**
 * get 1 setting about 1 addon
 * can be use in the addon inside a_*()
 */
function addon_set_settings($addon_id, $settings)
{
    $file = addon_get_vhost_path($addon_id, true).'settings.php';
    $to_file = '<?php return '.var_export($settings, true).';';
    $is_writed = file_put_contents($file, $to_file, LOCK_EX);
    return ($is_writed !== false);
}


// addons -> db

/**
 * set (create/upd) db
 *
 * return bool
 */
function addons_db_refresh()
{
    // in case of ... dont break the other process after this function
    $used_global = $GLOBALS['addons'];

    // load
    // to do check errors
    addons_load_all();

    $to_store = $GLOBALS['addons'];

    // remove useless
    foreach ($to_store as &$addon) {
        // si disabled, tracking of version
        if (!$addon['enabled']) {
            $t = $addon['version'];
            $addon = array('version' => $t,'enabled' => false);
        } else {
            // remove useless for public
            unset($addon['name'], $addon['desc'], $addon['url']);
            if (isset($addon['buttons'])) {
                unset($addon['buttons']);
            }
        }
        // cleanup settings
        if (isset($addon['settings'])) {
            foreach ($addon['settings'] as &$s) {
                if (isset($s['value'])) {
                    $t = $s['value'];
                    $s = array('value' => $t);
                }
            }
        }
        // remove ['_loaded']
        if (isset($addon['_loaded'])) {
            unset($addon['_loaded']);
        }
    }

    $to_file = '<?php return '.var_export($to_store, true).';';

    // restore $GLOBALS['addons'] before this the function
    $GLOBALS['addons'] = $used_global;

    // check dir
    if (!is_dir(DIR_VHOST_DATABASES)) {
        if (!create_folder(DIR_VHOST_DATABASES, true, true)) {
            // todo : put an error message
            return false;
        }
    }

    return (file_put_contents(ADDONS_DB, $to_file, LOCK_EX) !== false);
}

/**
 * get the db
 */
function addons_db_get()
{
    if (!is_file(ADDONS_DB)) {
        return false;
    }

    $db = include ADDONS_DB;

    // test on db
    if (!is_array($db)) {
        return false;
    }

    return $db;
}

/**
 * delete db
 */
function addons_db_del()
{
    return unlink(ADDONS_DB);
}


// addons -> cache

/**
 * clean the cache fon addon
 * /var/{vhost}/cache/addon-{addon_id}
 */
function addon_clean_cache($addon_id)
{
    $path = addon_get_vhost_cache_path($addon_id, false);
    if (!is_dir($path)) {
        return true;
    }
    $path = str_replace(array('../', './'), '', $path);
    return rmdir_recursive($path);
}
 

// addons -> html

/**
 * return the main list of addon
 */
function addons_html_get_list_addons($tableau, $filtre)
{
    if (!empty($tableau)) {
        $out = '<ul id="modules">'."\n";
        foreach ($GLOBALS['addons'] as $i => $addon) {
            // addon
            $out .= "\t".'<li>'."\n";

            // addon checkbox activation
            $out .= "\t\t".'<span><input type="checkbox" class="checkbox-toggle" name="module_'.$i.'" id="module_'.$i.'" '.(($addon['enabled']) ? 'checked' : '').' onchange="activate_mod(this);" /><label for="module_'.$i.'"></label></span>'."\n";

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

            // addon params or buttons
            if (isset($addon['settings']) || isset($addon['buttons'])) {
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
        $out .= '</ul>'."\n";
    } else {
        $out = info($GLOBALS['lang']['note_no_module']);
    }

    return $out;
}


// addon -> ajax

/**
 * proceed ajax submitted enabled/disabled
 */
function addon_ajax_switch_enabled_proceed($addon)
{
    $erreurs = array();

    $is_enabled = addon_test_enabled($addon['addon_id']);
    $new_status = (bool)$addon['status'];

    if ($is_enabled != $new_status) {
        if ($new_status) {
            // Addon enabled: we create .enabled
            if (!addon_set_enabled($addon['addon_id'])) {
                $erreurs[] = sprintf($GLOBALS['lang']['err_addon_enabled'], $addon['addon_id']);
            }
        } else {
            // Addon disabled: we delete .enabled
            if (!addon_set_disabled($addon['addon_id'])) {
                $erreurs[] = sprintf($GLOBALS['lang']['err_addon_disabled'], $addon['addon_id']);
            }
        }

        if (!addons_db_refresh()) {
            $errors['info'] = 'fail to refresh cache';
            // try to delete
            if (!addons_db_del()) {
                $errors['info'] = ' and fail to delete cache, please check your file system rights.';
            } else {
                // return message
                $errors['info'] = ', but delete the cache, it will recreate later.';
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


// addon -> form

/**
 * process (check) the submited config change for an addon
 *
 * todo :
 *   - manage errors
 *
 * @param string $addon_id, the addon name
 * @return bool
 */
function addon_form_edit_settings_proceed($addon_id)
{
    $errors = array();
    $datas = array();

    // save if, fail
    // $save_global_addons = isset($GLOBALS['addons'][$addon_id]) ? $GLOBALS['addons'][$addon_id] : null;

    $loaded = addon_load($addon_id);
    if ($loaded === false) {
        echo $loaded;
    }

    if (!isset($GLOBALS['addons'][$addon_id]['settings'])) {
        return true;
    }

    foreach ($GLOBALS['addons'][$addon_id]['settings'] as $key => $param) {
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

    if (count($errors) !== 0) {
        // reset default
        return $errors;
    }

    $is_saved = addon_set_settings($addon_id, $datas);
    if ($is_saved !== true) {
        $errors['form'] = 'fail to save settings';
        return $errors;
    }

    // saved, refresh globals
    foreach ($datas as $k => $v) {
        $GLOBALS['addons'][$addon_id]['settings'][$k]['value'] = $v;
    }

    // refresh addon\'s database
    if (!addons_db_refresh()) {
        $errors['info'] = 'fail to refresh addon\'s database';
        // try to delete
        if (!addons_db_del()) {
            $errors['info'] = ' and fail to delete addon\'s database, please check your file system rights.';
        } else {
            // return message
            $errors['info'] = ', but delete the addon\'s database, it will recreate later.';
        }
    }

    return true;
}

/**
 * Get the addon config form
 *
 * @param string $addon, the addon name
 * @return string, the html form
 */
function addon_form_edit_settings($addon_id)
{
    $loaded = addon_load($addon_id);
    if ($loaded === false) {
        echo $loaded;
    }

    // button
    $out = '';
    $out .= '<form id="preferences" method="post" action="?addon='. $addon_id .'" onsubmit="return confirm(\''. addslashes($GLOBALS['lang']['addons_confirm_buttons_action']) .'\');" >';
    $out .= '<div role="group" class="pref">'; /* no fieldset because browset can’t style them correctly */
    $out .= '<div class="form-legend"><legend class="legend-user">'.$GLOBALS['lang']['addons_settings_legend'].addon_get_translation($GLOBALS['addons'][$addon_id]['name']).'</legend></div>'."\n";

    $out .= '<div class="form-lines">'."\n";
    if (isset($GLOBALS['addons'][$addon_id]['buttons'])) {
        foreach ($GLOBALS['addons'][$addon_id]['buttons'] as $btnId => $btn) {
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
    $out .= '<form id="preferences" method="post" action="?addon='. $addon_id .'" >';
    $out .= '<div role="group" class="pref">'; /* no fieldset because browset can’t style them correctly */
    $out .= '<div class="form-legend"><legend class="legend-user">'.$GLOBALS['lang']['addons_settings_legend'].addon_get_translation($GLOBALS['addons'][$addon_id]['name']).'</legend></div>'."\n";

    // build the config form
    $out .= '<div class="form-lines">'."\n";

    foreach ($GLOBALS['addons'][$addon_id]['settings'] as $key => $param) {
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
 * show <select> to display list of enabled/disabled/all addons
 *
 * relative to addons_html_get_list_addons();
 */
function addon_form_list_addons_filter($filtre)
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
    return $ret;
}


// addon -> hook

/**
 * go throught addons $GLOBALS['addons']
 * and push all hook for enabled addon
 * addons must be already loaded (for little security)
 */
function addon_hook_push()
{
    foreach ($GLOBALS['addons'] as $addon) {
        if (!$addon['enabled']) {
            continue;
        }
        if (!isset($addon['hook-push'])) {
            continue;
        }
        foreach ($addon['hook-push'] as $hook_id => $params) {
            if (empty($hook_id) || empty($params['callback'])) {
                continue;
            }
            $pri = (isset($params['priority']) && is_int($params['priority'])) ? $params['priority'] : '' ;
            hook_push($hook_id, $params['callback'], $pri);
        }
    }
}


/**
 * perform action from button
 * must be call by the form in /admin/addon-settings.php
 */
function addon_buttons_action_process($addon_id)
{
    $loaded = addon_load($addon_id);
    if ($loaded === false) {
        echo $loaded;
    }

    $return = array();

    if (isset($GLOBALS['addons'][$addon_id]['buttons'])) {
        foreach ($GLOBALS['addons'][$addon_id]['buttons'] as $btnId => $btn) {
            if (!isset($_POST[$btnId]) || !function_exists($btn['callback'])) {
                $return['addon'][$btnId]['run'] = false;
                continue;
            }

            // prevent echo() ...
            ob_start();
            call_user_func($btn['callback']);
            $return['addon'][$btnId]['return'] = ob_get_contents();
            ob_end_clean();
            $return['addon'][$btnId]['run'] = true;
        }
    }

    // clean module cache ?
    if (isset($_POST['addon_clean_cache'])) {
        $return['addon_clean_cache'] = addon_clean_cache($addon_id); // must be tested
    }

    // refresh the db
    $return['addons_db_refresh'] = addons_db_refresh();

    return $return;
}
