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

require_once 'inc/boot.php';

header('Content-Type: image/png');

/**
 * test, if something go wrong, display a 10x10px red png
 */
DEFINE('WRONG_PNG', 'iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAFUlEQVR42mP8zsHxn4EIwDiqkL4KAas0FEc2dAhHAAAAAElFTkSuQmCC');
// cache for 1 year
DEFINE('EXPIRE_PNG', 60 * 60 * 24 * 365);



function download($url, $target, $referer = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:34.0) Gecko/20100101 Firefox/34.0')
{
    $success = false;

    // Open the target file for writing
    if (false == ($local_file = @fopen($target, 'w'))) {
        return false;
    }

    // Use curl to download, if available
    if (is_callable('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FILE, $local_file);
        if (curl_exec($ch)) {
            $success = true;
            fclose($local_file);
        }
        curl_close($ch);
    }

    if ($success === true) {
        return true;
    }
    
    // use fopen as fallback
    $opts = array(
        'http' => array(
            'method' => "GET",
            'header' => "Referer: $referer\r\n"
        )
    );

    $context = stream_context_create($opts);
    $remote = @fopen($url, 'r', false, $context);
    if (!$remote) {
        fclose($local_file);
        return false;
    }

    while (!feof($remote)) {
        fwrite($local_file, fread($remote, 8192));
    }
    fclose($remote);
    fclose($local_file);

    return true;
}


/**
 * Download an avatar or a favicon.
 */
function favatar()
{

    $what = (string)filter_input(INPUT_GET, 'w');
    $query = (string)filter_input(INPUT_GET, 'q');

    if (!$query && !in_array($what, array('avatar', 'favicon'))) {
        exit(base64_decode(WRONG_PNG));
    }


    if ($what == 'favicon') {
        // Full URL given?
        $domain = parse_url($query, PHP_URL_HOST);
        // Or only domain name?
        if ($domain === null) {
            $domain = parse_url($query, PHP_URL_PATH);
        }
        // Or some unusable crap?
        if ($domain === null) {
            exit(base64_decode(WRONG_PNG));
        }

        $targetDir = DIR_CACHE.'favicons/';
        $sourceFile = 'https://www.google.com/s2/favicons?domain='.$domain;
        $targetFile = $targetDir.md5($domain).'.png';
    } else {
        // Strip out anything that doesn't belong in a MD5 hash.
        // Still 32 characters? If no, given hash wasn't genuine. die.
        $hash = preg_replace('[^a-f0-9]', '', $query);
        if (strlen($hash) != 32) {
            exit(base64_decode(WRONG_PNG));
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

        $targetDir = DIR_CACHE.'avatars/';
        // We use the Libravatar service which will reditect to Gravatar if not found
        $sourceFile = 'http://cdn.libravatar.org/avatar/'.$hash.'?s='.$size.'&d='.$service;
        $targetFile = $targetDir.md5($hash).'.png';
    }

    // No cached file or expired?
    if (!is_file($targetFile) || (time() - filemtime($targetFile)) > EXPIRE_PNG) {
        if (!is_dir($targetDir) && !create_folder($targetDir, true, true)) {
            exit(base64_decode(WRONG_PNG));
        }

        // need a test/return false
        if (!download($sourceFile, $targetFile)) {
            exit(base64_decode(WRONG_PNG));
        }
    }

    // Send file to browser
    header('Content-Length: '.filesize($targetFile));
    header('Cache-Control: public, max-age='.EXPIRE_PNG);
    header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($targetFile)).' GMT');
    exit(readfile($targetFile));
}

favatar();
