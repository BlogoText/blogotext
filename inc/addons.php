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
 * return the var path for an addon
 * /var/{domain.tld}/addon/{addonTag}/
 *
 * @param string $addonTag
 * @param bool $check_create, create folder if doesn't exists
 * @return string|false
 */
function addon_get_var_path($addonTag, $check_create = false)
{
    $path = DIR_VAR_ADDONS.$addonTag.'/';
    if ($check_create === true && !is_dir($path) && !create_folder($path)) {
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
    $addons_ = array();
    if (is_file(ADDONS_DB)) {
        $addons_ = include ADDONS_DB;
    }

    foreach ($addons_ as $addon => $state) {
        if (!$state) {
            continue;
        }
        $inc = sprintf('%s/%s/%s.php', DIR_ADDONS, $addon, $addon);
        if (!is_file($inc)) {
            continue;
        }
        require_once $inc;
        $addon = addon_get_infos($addon);
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
 * get the config of an addon
 * can be used by addons
 *
 * @param string $addonTag, the addon tag
 * @return array
 */
function addon_get_conf($addonTag)
{
    $inc = sprintf('%s/%s/%s.php', DIR_ADDONS, $addonTag, $addonTag);
    if (!is_file($inc)) {
        return false;
    }
    require_once $inc;
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
