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


/**
 *
 */
function get_safe_path($path)
{
    // no relative path allowed
    if (strpos($path, './') !== false) {
        return false;
    }

    // make sure of absolute url
    $path = BT_ROOT .'/'. str_replace(BT_ROOT, '', $path);

    // prevent var\\log\test.php -> var/log/test.php
    while (strstr($path, '\\\\')) {
        $path = str_replace('\\\\', '/', $path);
    }
    // prevent var//log/test.php -> var/log/test.php
    while (strstr($path, '//')) {
        $path = str_replace('//', '/', $path);
    }

    return $path;
}

/**
 * Like rmdir, but recursive
 *
 * use of get_path(), try to prevent the end of the world...
 *
 * @params string $path, the relative path to BT_DIR
 * @return bool
 */
function rmdir_recursive($path)
{
    $error = 0;

    // secure and test the path
    // just trying to avoid to delete the world or cause a zombie apocalypse
    if (($path = get_safe_path($path)) === false) {
        return false;
    }

    $dir = opendir($path);
    while (($file = readdir($dir)) !== false) {
        if (($file == '.') || ($file == '..')) {
            continue;
        }
        if (is_dir($path.$file.'/')) {
            if (!rmdir_recursive($path.$file.'/')) {
                ++$error;
            }
        } else {
            if (!unlink($path.$file)) {
                ++$error;
            }
        }
    }
    closedir($dir);
    rmdir($path);
    if (is_dir($path)) {
        ++$error;
    }

    return ($error === 0);
}




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

    // if no cache
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

    // filter the enabled
    foreach ($db as $addon_id => $addon) {
        // if not enabled or user delete .enabled or the addon threw ftp
        if (!$addon['enabled'] || !addon_test_enabled($addon_id) || !addon_test_exists($addon_id)) {
            continue;
        }

        // seem's good, load addon
        require_once addon_get_addon_file_path($addon_id);

        // check version
        if (!addon_test_versions($declaration['version'], $addon['version'])) {
            log_error('Addon updated, new version of addon '.$addon_id.' have been detected, please update check addons in admin');
            // check compliancy
            if (!addon_test_compliancy($declaration['compliancy'])) {
                log_error('Addon updated, '.$addon_id.' is not compliant with this');
                // delete db
                addons_db_del();
                continue;
            }
        }
        $GLOBALS['addons'][$addon_id] = $addon;
        $GLOBALS['addons'][$addon_id]['enabled'] = true;
        $GLOBALS['addons'][$addon_id]['_loaded'] = true; // mark it loaded
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
    return (is_file(addon_get_enabled_file_path($addon_id, false)));
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

    $bt = explode('.', BLOGOTEXT_VERSION, 4);
    return (version_compare($addon_compliancy, ($bt['0'].'.'.$bt['1']), '>=') && version_compare($addon_compliancy, ($bt['0'].'.'.($bt['1']+1)), '<'));
}

/**
 * test if this is the same version as in the db
 *
 * @return bool, true : same, false : different
 */
function addon_test_versions($version_1, $version_2)
{
    return version_compare($version_1, $version_2, '==');
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
        log_error('addon '. $addon_id .' fail on tag test');
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
        $settings = addon_get_settings($addon_id, $declaration);
        if (is_array($settings)) {
            $GLOBALS['addons'][$addon_id]['settings'] = $settings;
        }
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
    if (!isset($GLOBALS['addons'][$addon_id]['_loaded'])
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
    if ($declaration === null && isset($GLOBALS['addons'][$addon_id]) && isset($GLOBALS['addons'][$addon_id]['_loaded'])) {
        $declaration = $GLOBALS['addons'][$addon_id];
    }

    // addon dont have $GLOBALS['addons'][]['setting']
    if (!isset($declaration['settings']) || $declaration['settings'] === null || !is_array($declaration['settings'])) {
        return null;
    }

    // if user have saved settings
    $user_file_path = addon_get_vhost_path($addon_id, false).'settings.php';

    if (is_file($user_file_path)) {
        $saved_settings = array();
        $t = include $user_file_path;

        foreach ($t as $option => $value) {
            $saved_settings[$option] = htmlspecialchars($value, ENT_QUOTES);
        }

        if (is_array($saved_settings)) {
            foreach ($declaration['settings'] as $key => &$vals) {
                // if saved setting, overwrite the default
                if (isset($saved_settings[$key])) {
                    $vals['value'] = $saved_settings[$key];
                }
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
    if (is_array($info)) {
        if (isset($info[$GLOBALS['lang']['id']])) {
            return $info[$GLOBALS['lang']['id']];
        } elseif (isset($info['en'])) {
            return $info['en'];
        }
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
    if ($check_create === true && !create_folder($path, true, true)) {
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

    // remove useless datas
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

    $vars = preg_replace('/\s$/m', '', var_export($to_store, true));
    $to_file = '<?php return '.$vars.';'."\n";

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
