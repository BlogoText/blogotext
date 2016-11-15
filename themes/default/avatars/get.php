<?php
/*
    Local-gravatar cacher
    http://www.yoursite.com/gravatar_caching_folder/get.php?g={md5_from_email}&s={size}&d={substitute}
    returns icon and saves is localy
*/

$expire = time() -60*60*24*30 ;  // default: 30 days

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

// g given ? if yes...
if (isset($_GET['g'])) {
    if (strlen($_GET['g']) !== 32) {
        die;
    }  // g is 32 character long ? if no, die.
    $hash = preg_replace("[^a-f0-9]", "", $_GET['g']);  // strip out anything that doesn't belong in a md5 hash
    if (strlen($hash) != 32) {
        die;
    }  // still 32 characters ? if no, given hash wasn't genuine. die.
    $newfile = $hash.'.png';

    // expired gravatar, out!
    if (is_file($newfile) and filemtime($newfile) < $expire) {
        unlink($newfile);
    }
    // the gravatar doesn’t exists or has been removed : it needs refetching
    if (!is_file($newfile)) {
        // try to get size (s param)
        $s = (isset($_GET['s']) and is_numeric($_GET['s'])) ? htmlspecialchars($_GET['s']) : 48;
        // try to get substitute image (d param)
        $d = (isset($_GET['d'])) ? htmlspecialchars($_GET['d']) : 'monsterid';
        // First try with libravatar
        $avatar_url = 'http://cdn.libravatar.org/avatar/'.$hash.'?s='.$s.'&d='.$d;

        // request
        download_avatar($avatar_url, $newfile);

        if (!file_exists($newfile)) {
            // try with gravatar
            $gravatar_url = 'http://www.gravatar.com/avatar/'.$hash.'?s='.$s.'&d='.$d;
            $success = download_avatar($avatar_url, $newfile);
        }
            
        if (!file_exists($newfile)) {
            // impossible request
            header("HTTP/1.0 404 Not Found");
            die('404');
            exit;
        }


        $imagecheck = getimagesize($newfile);
        if ($imagecheck['mime']!=='image/png') { // is it a PNG ?
            imagepng(imagecreatefromjpeg($newfile), $newfile.'2');  // if no, creating PNG and replacing
            unlink($newfile);
            rename($newfile.'2', $newfile);
        }
    }
    // and finally send the cached image.
    header('Content-Type: image/png');
    header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($newfile)).' GMT');
    header('Content-Length: ' . filesize($newfile));
    header('Cache-Control: public, max-age=2592000');
    readfile($newfile);
    exit;
} // g not given, return error.
else {
    header("HTTP/1.0 404 Not Found");
    exit;
}
