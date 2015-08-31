<?php
/*
How to use it:
call it from your website like this:
http://www.yoursite.com/gravatar_caching_folder/get.php?g={md5_from_email}&s={size}&d={substitute}
*/
$expire = time() -60*60*24*7 ;  // default: 7 days

// g given ? if yes...
if (isset($_GET['g'])) {
	if (strlen($_GET['g']) !== 32) { die; }  // g is 32 character long ? if no, die.
	$hash = preg_replace("[^a-f0-9]", "", $_GET['g'] );  // strip out anything that doesn't belong in a md5 hash
	if (strlen($hash) != 32) { die; }  // still 32 characters ? if no, given hash wasn't genuine. die.
	$newfile = $hash.'.png';

	// try to get size (s param)
	if (isset($_GET['s']) and is_numeric($_GET['s'])) {
		$s = htmlspecialchars($_GET['s']);
	} else { $s = 48; }

	// try to get substitute image (d param)
	if (isset($_GET['d'])) {
		$d = htmlspecialchars($_GET['d']);
	} else { $d = 'monsterid'; }


	$file = 'https://secure.gravatar.com/avatar/'.$hash.'?s='.$s.'&d='.$d;

	// expired gravatar, out!
	if (file_exists($newfile) and filemtime($newfile) < $expire) {
		unlink($newfile);
	}
	if (file_exists($newfile)) { }  // the gravatar wasn't removed before: it's valid and doesn't need refreshment
	else {
		copy($file, $newfile);   // gravatar deleted, getting new.
		$imagecheck = getimagesize($newfile);
		if ($imagecheck['mime']!=='image/png') { // is it a PNG ?
			imagepng(imagecreatefromjpeg($newfile),$newfile.'2');  // if no, creating PNG and replacing
			unlink($newfile);
			rename($newfile.'2', $newfile);
		}
	}
	// and finally let's redirect to the cached gravatar.
	header('Location: '.$newfile.'');
}

// g not given, return error.
else {
	header("HTTP/1.0 404 Not Found");
	die('erreur');
}

?>
