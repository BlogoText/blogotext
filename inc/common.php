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
 * Create database like file.
 */
function create_file_dtb($output, $data)
{
    $data = '<?php /* '.chunk_split(base64_encode(serialize($data)), 76, "\n")."*/\n";
    return file_put_contents($output, $data, LOCK_EX) !== false;
}

/**
 * Retrieve serialized data used by create_file_dtb().
 */
function open_serialzd_file($file)
{
    if (!is_file($file)) {
        return array();
    }
    return unserialize(base64_decode(substr(file_get_contents($file), strlen('<?php /* '), -strlen('*/'))));
}

/**
 * Redirect to another URL, the right way.
 */
function redirection($url)
{
    // Prevent use hook on admin side
    if (!defined('IS_IN_ADMIN')) {
        $tmp_hook = hook_trigger_and_check('before_redirection', $url);
        if ($tmp_hook !== false) {
            $url = $tmp_hook['1'];
        }
    }

    exit(header('Location: '.$url));
}

/**
 * Remove the current (.) and parent (..) folders from the list of files returned by scandir().
 */
function rm_dots_dir($array)
{
    return array_diff($array, array('.', '..'));
}

// remove slashes if necessary
function clean_txt($text)
{
    if (!get_magic_quotes_gpc()) {
        return trim($text);
    } else {
        return trim(stripslashes($text));
    }
}

function protect($text)
{
    return htmlspecialchars(clean_txt($text));
}

// useless ?
function lang_set_list()
{
    $GLOBALS['langs'] = array('fr' => 'Français', 'en' => 'English');
}

/**
 * load lang
 *
 * $admin bool lang for admin side ?
 */
function lang_load_land($admin)
{
    if (empty($GLOBALS['lang'])) {
        $GLOBALS['lang'] = '';
    }

    $path = '';
    if ($admin === true) {
        $path = 'admin/';
    }
    switch ($GLOBALS['lang']) {
        case 'en':
            require_once BT_ROOT.$path.'inc/lang/en_en.php';
            break;
        case 'fr':
        default:
            require_once BT_ROOT.$path.'inc/lang/fr_fr.php';
    }
}
