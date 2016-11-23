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
 * Retrieve serialized data.
 */
function open_serialzd_file($file)
{
    if (!is_file($file)) {
        return array();
    }
    return unserialize(base64_decode(substr(file_get_contents($file), strlen('<?php /* '), -strlen(' */'))));
}


/**
 * Redirect to another URL, the right way.
 */
function redirection($url)
{
    // Prevent use hook on admin side
    if (!defined('DONT_USE_HOOK')) {
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
