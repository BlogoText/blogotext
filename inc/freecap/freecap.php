<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

//error_reporting($GLOBALS['show_errors']);
session_start();

// the GDFontGenerator @ http://www.philiplb.de is excellent for convering ttf to GD
$font_locations = "ht_freecap_font1.gdf";

// used to calculate image width, for non-dictionary word generation
$width = 280;
$height = 90;

$im = ImageCreate($width, $height);
$im2 = ImageCreate($width, $height);


//////////////////////////////////////////////////////
////// Functions:
//////////////////////////////////////////////////////
function open_font($font) {
	$handle = fopen($font, "r");
	$c_wid = fread($handle, 12);
	fclose($handle);
	$font_widths = ord($c_wid[8])+ord($c_wid[9])+ord($c_wid[10])+ord($c_wid[11]);
	return $font_widths;
}


function rand_color() {
	return mt_rand(50,230);
}

function myImageBlur($im) {
	$width = imagesx($im);
	$height = imagesy($im);
	$temp_im = ImageCreateTrueColor($width,$height);
	$bg = ImageColorAllocate($temp_im,150,150,150);
	// preserves transparency if in orig image
	ImageColorTransparent($temp_im,$bg);
	// fill bg
	ImageFill($temp_im,0,0,$bg);
	$distance = 1;
	// blur by merging with itself at different x/y offsets:
	ImageCopyMerge($temp_im, $im, 0, 0, 0, $distance, $width, $height-$distance, 70);
	ImageCopyMerge($im, $temp_im, 0, 0, $distance, 0, $width-$distance, $height, 70);
	ImageCopyMerge($temp_im, $im, 0, $distance, 0, 0, $width, $height, 70);
	ImageCopyMerge($im, $temp_im, $distance, 0, 0, 0, $width, $height, 70);
	// remove temp image
	ImageDestroy($temp_im);
	return $im;
}

function sendImage($pic) {
	global $im,$im2,$im3;
	header(base64_decode("WC1DYXB0Y2hhOiBmcmVlQ2FwIDEuNCAtIHd3dy5wdXJlbWFuZ28uY28udWs="));
	header("Content-Type: image/png");
	ImagePNG($pic);

	// kill GD images (removes from memory)
	ImageDestroy($im);
	ImageDestroy($im2);
	ImageDestroy($pic);
	if (!empty($im3)) {
		ImageDestroy($im3);
	}
	exit();
}

//////////////////////////////////////////////////////
////// Choose Word:
//////////////////////////////////////////////////////

$consonants = 'bcdghklmnpqrsvwxyz';
$vowels = 'aeuo';
$word = "";
for ($i = 0; $i < 5; $i++) { // 5 ltr in wrd
	if ($i % 2 == 0) { // begin with consonant, then alterns
		$word .= $consonants[mt_rand(0,strlen($consonants)-1)];
	} else {
		$word .= $vowels[mt_rand(0,strlen($vowels)-1)];
	}
}

// save hash of word for comparison
$_SESSION['freecap_word_hash'] = sha1($word);

//////////////////////////////////////////////////////
////// Fill BGs and Allocate Colours:
//////////////////////////////////////////////////////

$tag_col = ImageColorAllocate($im,10,10,10);
$debug = ImageColorAllocate($im, 255, 0, 0);
$debug2 = ImageColorAllocate($im2, 255, 0, 0);
$bg = ImageColorAllocate($im, 254, 254, 254);
$bg2 = ImageColorAllocate($im2, 254, 254, 254);
ImageColorTransparent($im,$bg);
ImageColorTransparent($im2,$bg2);
// fill backgrounds
ImageFill($im,0,0,$bg);
ImageFill($im2,0,0,$bg2);

// generate noisy background, to be merged with CAPTCHA later
$im3 = ImageCreateTrueColor($width,$height);
$temp_bg = ImageCreateTrueColor($width*1.5,$height*1.5);
$bg3 = ImageColorAllocate($im3,255,255,255);
ImageFill($im3,0,0,$bg3);
$temp_bg_col = ImageColorAllocate($temp_bg,255,255,255);
ImageFill($temp_bg,0,0,$temp_bg_col);

$bg3 = ImageColorAllocate($im3,255,255,255);
ImageFill($im3,0,0,$bg3);
ImageSetThickness($temp_bg,4);

for ($i=0 ; $i<strlen($word)+1 ; $i++) {
	$text_r = mt_rand(100,150);
	$text_g = mt_rand(100,150);
	$text_b = mt_rand(100,150);
	$text_colour3 = ImageColorAllocate($temp_bg, $text_r, $text_g, $text_b);
	$points = array();
	for ($j = 1; $j < mt_rand(5,10); $j++) {
		$points[] = mt_rand(1*(20*($i+1)),1*(50*($i+1)));
		$points[] = mt_rand(30,$height+30);
	}
	ImagePolygon($temp_bg,$points,intval(sizeof($points)/2),$text_colour3);
}

$morph_chunk = mt_rand(1,5);
$morph_y = 0;
for ($x = 0; $x < $width; $x += $morph_chunk) {
	$morph_chunk = mt_rand(1,5);
	$morph_y += mt_rand(-1,1);
	ImageCopy($im3, $temp_bg, $x, 0, $x+30, 30+$morph_y, $morph_chunk, $height*2);
}

ImageCopy($temp_bg, $im3, 0, 0, 0, 0, $width, $height);

$morph_x = 0;
for ($y = 0; $y <= $height; $y += $morph_chunk) {
	$morph_chunk = mt_rand(1,5);
	$morph_x += mt_rand(-1,1);
	ImageCopy($im3, $temp_bg, $morph_x, $y, 0, $y, $width, $morph_chunk);
}

ImageDestroy($temp_bg);
myImageBlur($im3);

//////////////////////////////////////////////////////
////// Write Word
//////////////////////////////////////////////////////

$word_start_x = mt_rand(5,32);
$word_start_y = 15;

// write each char in different color
$font = ImageLoadFont($font_locations);
$font_width = open_font($font_locations);

for ($i=0 ; $i < strlen($word) ; $i++) {
	$text_r = rand_color();
	$text_g = rand_color();
	$text_b = rand_color();
	$text_colour2 = ImageColorAllocate($im2, $text_r, $text_g, $text_b);
	ImageString($im2, $font, $word_start_x+($font_width*$i), $word_start_y, $word{$i}, $text_colour2);
}
$font_pixelwidth = $font_width;


//////////////////////////////////////////////////////
////// Morph Image:
//////////////////////////////////////////////////////


$word_pix_size = $word_start_x+(strlen($word)*$font_pixelwidth);

// firstly move each character up or down a bit:
$y_pos = 0;
for ($i = $word_start_x; $i < $word_pix_size; $i += $font_pixelwidth) {
	$prev_y = $y_pos;
	do {
		$y_pos = mt_rand(-5,5);
	} while ($y_pos < $prev_y+2 and $y_pos > $prev_y-2);
	ImageCopy($im, $im2, $i, $y_pos, $i, 0, $font_pixelwidth, $height);
}

ImageFilledRectangle($im2,0,0,$width,$height,$bg2);

$morph_x = 0;
for ($j=0; $j < strlen($word); $j++) {
	$y_pos = 0;
	for ($i = 0; $i <= $height; $i += 1) {
		$orig_x = $word_start_x+($j*$font_pixelwidth);
		$morph_x += mt_rand(-1,1);
		ImageCopyMerge($im2, $im, $orig_x+$morph_x, $i+$y_pos, $orig_x, $i, $font_pixelwidth, 1, 100);
	}
}

ImageFilledRectangle($im,0,0,$width,$height,$bg);
// now do the same on the y-axis
$y_pos = 0;
$x_chunk = 1;
for ($i = 0; $i <= $width; $i += $x_chunk) {
	$y_pos += mt_rand(-1,1);
	ImageCopy($im, $im2, $i, $y_pos, $i, 0, $x_chunk, $height);
}
myImageBlur($im);
ImageFilledRectangle($im2, 0, 0, $width, $height, $bg2);
ImageCopyMerge($im2, $im, 0, 0, 0, 0, $width, $height, 80);
ImageCopy($im, $im2, 0, 0, 0, 0, $width, $height);

//////////////////////////////////////////////////////
////// Merge with obfuscated background
//////////////////////////////////////////////////////

$temp_im = ImageCreateTrueColor($width,$height);
$white = ImageColorAllocate($temp_im,255,255,255);
ImageFill($temp_im,0,0,$white);
ImageCopyMerge($im3,$temp_im,0,0,0,0,$width,$height,70);
ImageDestroy($temp_im);
$c_fade_pct = 50;

// captcha over bg:
ImageCopyMerge($im3, $im, 0, 0, 0, 0, $width, $height, 100);
ImageCopy($im, $im3, 0, 0, 0, 0, $width, $height);

unset($word, $bg_images);

sendImage($im);
