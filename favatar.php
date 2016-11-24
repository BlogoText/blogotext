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

// Eventually, add a "DDOS" security check here, or if you do not want to
// see someone else using this service outside your web server.
// Open to discussion on the GitHub repos.

require_once __dir__.'/inc/boot.php';

/**
 * Download an avatar or a favicon.
 *
 * favatar = FAVicon + avATAR
 */
function favatar()
{
    function download($url, $output)
    {
        /*$curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $avatar_url);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, 5);
        $file_content = curl_exec($curl_handle);
        curl_close($curl_handle);*/

        $fp = fopen($output, 'wb');
        flock($fp, LOCK_EX);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    $what = (string)filter_input(INPUT_GET, 'w');
    $query = (string)filter_input(INPUT_GET, 'q');

    if (!$query && !in_array($what, array('avatar', 'favicon'))) {
        exit(header('HTTP/1.1 400 Bad Request'));
    }


    if ($what == 'favicon') {
        $targetDir = DIR_CACHE.'favicons/';

        // Full URL given?
        $domain = parse_url($query, PHP_URL_HOST);
        // Or only domain name?
        if ($domain === null) {
            $domain = parse_url($query, PHP_URL_PATH);
        }
        // Or some unusable crap?
        if ($domain === null) {
            exit(header('HTTP/1.1 400 Bad Request'));
        }

        $sourceFile = 'https://www.google.com/s2/favicons?domain='.$domain;
        $targetFile = $targetDir.md5($domain).'.png';
    } else {
        $targetDir = DIR_CACHE.'avatars/';

        // Strip out anything that doesn't belong in a MD5 hash.
        // Still 32 characters? If no, given hash wasn't genuine. die.
        $hash = preg_replace('[^a-f0-9]', '', $query);
        if (strlen($hash) != 32) {
            exit(header('HTTP/1.1 400 Bad Request'));
        }

        // Try to get size
        $size = (int)filter_input(INPUT_GET, 's');
        if (!$size) {
            $size = 48;
        }

        // Try to get substitute image
        $service = (string)filter_input(INPUT_GET, 'd');
        if (!$service) {
            $service = 'monsterid';
        }

        // We use the Libravatar service which will reditect to Gravatar if not found
        $sourceFile = 'http://cdn.libravatar.org/avatar/'.$hash.'?s='.$size.'&d='.$service;
        $targetFile = $targetDir.md5($hash).'.png';
    }

    // Expires in one year
    $expire = 60 * 60 * 24 * 7 * 365 ;

    // No cached file or expired?
    if (!is_file($targetFile) || (time() - filemtime($targetFile)) > $expire) {
        if (!is_dir($targetDir)) {
            mkdir($targetDir);
        }
        download($sourceFile, $targetFile);
    }

    // Send file to browser
    header('Content-Type: image/png');
    header('Content-Length: '.filesize($targetFile));
    header('Cache-Control: public, max-age='.$expire);
    header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($targetFile)).' GMT');
    exit(readfile($targetFile));
}

favatar();
