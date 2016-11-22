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


function open_serialzd_file($fichier)
{
    $liste  = (is_file($fichier)) ? unserialize(base64_decode(substr(file_get_contents($fichier), strlen('<?php /* '), -strlen(' */')))) : array();
    return $liste;
}


/**
 * like rmdir, but recursive
 *
 * use of get_path(), try to prevent the end of the world...
 *
 * @params string $path, the relative path to BT_DIR
 */
function rmdir_recursive($path)
{
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
