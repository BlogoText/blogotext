<?php
/*
	How to use it:
	call it from your website like this:
	http://www.yoursite.com/favicon_caching_folder/get.php?g={domain_name}

	You can (and should) use it in a IMG/SRC link :

	<img src="http://www.yoursite.com/favicon_caching_folder/get.php?g={domain_name}">
	or <img src="/favicon_caching_folder/get.php?g={domain_name}">

*/
$expire = time() -60*60*24*7*365 ;  // default: 1 year
$folder = 'icons';

// domain name given? YES.
if ( isset($_GET['g']) and !empty($_GET['g']) ) {
	// does storage dir exists?
	if (!is_dir($folder)) { mkdir($folder, 0744); }

	// full URL given?
	$domain = parse_url($_GET['g'], PHP_URL_HOST);

	// or only domain name?
	if ($domain === NULL) $domain = parse_url($_GET['g'], PHP_URL_PATH);
	// or some unusable crap?
	if ($domain === NULL) { header("HTTP/1.0 404 Not Found"); exit; }

	// target file
	$newfile = $folder.'/'.md5($domain).'.png';

	// new file URL
	$file = 'http://www.google.com/s2/favicons?domain='.$domain;

	// expired favicon? Get rid if it
	if (file_exists($newfile) and filemtime($newfile) < $expire) {
		unlink($newfile);
	}
	// no local favicon or deleted because expired? getting new.
	if (!file_exists($newfile)) {
		copy($file, $newfile);
		$imagecheck = getimagesize($newfile);
		if ($imagecheck['mime']!=='image/png') { // is it a PNG?
			imagepng(imagecreatefromjpeg($newfile),$newfile.'2');  // if not, creating PNG and replacing
			unlink($newfile);
			rename($newfile.'2', $newfile);
		}
	}
	// and finally send the cached image.
	header('Content-Type: image/png');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($newfile)).' GMT');
   header('Content-Length: ' . filesize($newfile));
   header('Cache-Control: public, max-age=86400');
   readfile($newfile);
	exit;

}

// domain name given? NO, returning 404.
else {
	header("HTTP/1.0 404 Not Found");
	die('error');
	exit;
}

