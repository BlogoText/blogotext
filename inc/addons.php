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


function addon_get_var_path($addonTag)
{
    $path = DIR_VAR_ADDONS.$addonTag.'/';
    if (!is_dir($path) && !create_folder($path)) {
        return false;
    }
    return $path;
}

/**
 * get addon cache path
 *
 * @param string $addonTag
 * @param bool $create, create folder if doesn't exists
 * @return string|false
 */
function addon_get_cache_path($addonTag, $create = true)
{
    $path = DIR_VAR_ADDONS.$addonTag.'/cache/';
    if ($create === true && !is_dir($path) && !create_folder($path)) {
        return false;
    }
    return $path;
}

/**
 * go throught addons $GLOBALS['addons']
 * and push all hook
 */
function addon_boot_hook_push()
{
    $activated = addon_list_addons();

    foreach ($GLOBALS['addons'] as $addon) {
        if (!isset($addon['hook-push'])) {
            continue;
        }
        if (!isset($activated[$addon['tag']]) || $activated[$addon['tag']] !== true) {
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
 * get the config of an addon
 * can be used by addons
 *
 * @param string $addonTag, the addon tag
 * @return array
 */
function addon_get_conf($addonTag)
{
    $infos = addon_get_infos($addonTag);
    if ($infos === false) {
        return false;
    }
    $saved = array();
    $file_path = addon_get_var_path($addonTag).'settings.ini';
    if (is_file($file_path) and is_readable($file_path)) {
        $t = parse_ini_file($file_path);
        foreach ($t as $option => $value) {
            $saved[$option] = $value;
        }
    }
    if (isset($infos['settings'])) {
        if (!is_array($saved)) {
            return $infos['settings'];
        } else {
            foreach ($infos['settings'] as $key => $vals) {
                $infos['settings'][$key]['value'] = (isset($saved[$key])) ? $saved[$key] : $vals['value'] ;
            }
            return $infos['settings'];
        }
    }
    return array();
}

/**
 * get addon informations
 *
 * @param string $addonTag, the addon tag
 * @return array||false, false if addon not found/loaded...
 */
function addon_get_infos($addonTag)
{
    foreach ($GLOBALS['addons'] as $k) {
        if ($k['tag'] == $addonTag) {
            return $k;
        }
    }
    return false;
}

/**
 * list all addons
 */
function addon_list_addons()
{
    $addons = array();
    $path = BT_ROOT.DIR_ADDONS;

    if (is_dir($path)) {
        // get the list of installed addons
        $addons_list = rm_dots_dir(scandir($path));

        // include the addons
        foreach ($addons_list as $addon) {
            $inc = sprintf('%s/%s/%s.php', $path, $addon, $addon);
            $is_enabled = is_file(sprintf('%s.enabled', addon_get_var_path($addon)));
            if (is_file($inc)) {
                $addons[$addon] = $is_enabled;
                include_once $inc;
            }
        }
    }

    return $addons;
}
