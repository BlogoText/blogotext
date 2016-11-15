<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2015 Timo Van Neerden.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

function download_avatar($avatar_url, $newfile)
{
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $avatar_url);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handle, CURLOPT_TIMEOUT, 5);
    $file_content = curl_exec($curl_handle);
    curl_close($curl_handle);

    $fp = fopen($newfile, 'w+');
    $ch = curl_init($avatar_url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_exec($ch);

    curl_close($ch);
    fclose($fp);
}

if (!isset($_GET['w'], $_GET['q'])) {
    header("HTTP/1.0 400 Bad Request");
    exit;
}

$expire = time() -60*60*24*7*365 ;  // default: 1 year

if ($_GET['w'] == 'favicon') {
    // target dir
    $target_dir = 'favicons';
    // source file
    $domain = parse_url($_GET['q'], PHP_URL_HOST); // full URL given?
    if ($domain === null) {
        $domain = parse_url($_GET['q'], PHP_URL_PATH);
    } // or only domain name?
    if ($domain === null) {
        header("HTTP/1.0 400 Bad Request");
        exit;
    } // or some unusable crap?
    $source_file = 'http://www.google.com/s2/favicons?domain='.$domain;
    // dest file
    $target_file = $target_dir.'/'.md5($domain).'.png';
    // expiration delay
    $expire = time() -60*60*24*7*365 ;  // default: 1 year
} elseif ($_GET['w'] == 'avatar') {
    // target dir
    $target_dir = 'avatars';
    // source file
    if (strlen($_GET['q']) !== 32) {
        header("HTTP/1.0 400 Bad Request");
        exit;
    }  // g is 32 character long ? if no, die.
    $hash = preg_replace("[^a-f0-9]", "", $_GET['q']);  // strip out anything that doesn't belong in a md5 hash
    if (strlen($hash) != 32) {
        header("HTTP/1.0 400 Bad Request");
        exit;
    }  // still 32 characters ? if no, given hash wasn't genuine. die.
    $target_file = $hash.'.png';
    $s = (isset($_GET['s']) and is_numeric($_GET['s'])) ? htmlspecialchars($_GET['s']) : 48; // try to get size
    $d = (isset($_GET['d'])) ? htmlspecialchars($_GET['d']) : 'monsterid'; // try to get substitute image
    // First try with libravatar
    $source_file = 'http://cdn.libravatar.org/avatar/'.$hash.'?s='.$s.'&d='.$d;
    // dest file
    $target_file = $target_dir.'/'.md5($hash).'.png';
    // expiration delay
    $expire = time() -60*60*24*30 ;  // default: 30 days
} else {
    // wrong request: returning error 400.
    header("HTTP/1.0 400 Bad Request");
    exit;
}

/* processing :
    - testing cache file
    - gathering source file
    - converting to PNG and saving
    - sending image to browser
*/

// cached file existing & expired : mark to remove it
$force_new = false;
if (is_file($target_file) and filemtime($target_file) < $expire) {
    $force_new = true;
}

// no cached file or expired
if (!is_file($target_file) or $force_new === true) {
    if (!is_dir($target_dir)) {
        mkdir($target_dir);
    }

    // request
    download_avatar($source_file, $target_file);

    if (!file_exists($target_file)) {
        // try with gravatar
        $source_file = 'http://www.gravatar.com/avatar/'.$hash.'?s='.$s.'&d='.$d;
        $success = download_avatar($source_file, $target_file);
    }
    if (!file_exists($target_file)) {
        // impossible request
        header("HTTP/1.0 404 Not Found");
        die('404');
        exit;
    }

    // testing format
    $imagecheck = getimagesize($target_file);
    if ($imagecheck['mime'] !== 'image/png') {
        imagepng(imagecreatefromjpeg($target_file), $target_file.'2');  // if not, creating PNG and replacing
        unlink($target_file);
        rename($target_file.'2', $target_file);
    }
}

// send file to browser
header('Content-Type: image/png');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($target_file)).' GMT');
header('Content-Length: ' . filesize($target_file));
header('Cache-Control: public, max-age=2628000');
readfile($target_file);
exit;
