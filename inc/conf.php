<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

/*
 * Some hard coded constants
 * Don’t change unless you know what you’re doing.
*/

// GENERAL
define('BLOGOTEXT_NAME', 'BlogoText');
define('BLOGOTEXT_SITE', 'http://lehollandaisvolant.net/blogotext/');
define('BLOGOTEXT_VERSION', '3.3.10-2');
define('MINIMAL_PHP_REQUIRED_VERSION', '5.5');

// FOLDERS (change this only if you know what you are doing...)
define('DIR_ADMIN', 'admin');
define('DIR_BACKUP', 'bt_backup');
define('DIR_IMAGES', 'img');
define('DIR_DOCUMENTS', 'files');
define('DIR_THEMES', 'themes');
define('DIR_CACHE', 'cache');
define('DIR_DATABASES', 'databases');
define('DIR_CONFIG', 'config');
define('DIR_ADDONS', 'addons');
// DB FILES
define('FILES_DB', BT_ROOT.DIR_DATABASES.'/'.'files.php'); // files/image DB storage.
define('FEEDS_DB', BT_ROOT.DIR_DATABASES.'/'.'rss.php'); // RSS-feeds list info storage.

// TIMEZONES
date_default_timezone_set($GLOBALS['fuseau_horaire']);

// INIT SOME VARS
$GLOBALS['addons'] = array();

// IMPORT SEVERAL .ini CONFIG FILES
// with this function
function import_ini_file($file_path) {
	if (is_file($file_path) and is_readable($file_path)) {
		$options = parse_ini_file($file_path);
		foreach ($options as $option => $value) {
			if (!defined($option)) define($option, $value);
		}
		return TRUE;
	}
	return FALSE;
}

// ADVANCED CONFIG OPTIONS
import_ini_file(BT_ROOT.DIR_CONFIG.'/'.'config-advanced.ini');
	error_reporting(DISPLAY_PHP_ERRORS); // Error reporting

// DATABASE OPTIONS + MySQL DB PARAMS
import_ini_file(BT_ROOT.DIR_CONFIG.'/'.'mysql.ini');

// USER LOGIN + PW HASH
import_ini_file(BT_ROOT.DIR_CONFIG.'/'.'user.ini');






// regenerate captcha (always)
if (!isset($GLOBALS['captcha'])) {
	$ua = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$GLOBALS['captcha']['x'] = rand(4, 9);
	$GLOBALS['captcha']['y'] = rand(1, 6);
	$GLOBALS['captcha']['hash'] = sha1($ua.($GLOBALS['captcha']['x']+$GLOBALS['captcha']['y']));
}

// THEMES FILES and PATHS
if ( isset($GLOBALS['theme_choisi']) ) {
	$GLOBALS['theme_style'] = DIR_THEMES.'/'.$GLOBALS['theme_choisi'];
	$GLOBALS['theme_liste'] = $GLOBALS['theme_style'].'/list.html';
	$GLOBALS['theme_post_artc'] = $GLOBALS['theme_style'].'/template/article.html';
	$GLOBALS['theme_post_comm'] = $GLOBALS['theme_style'].'/template/commentaire.html';
	$GLOBALS['theme_post_link'] = $GLOBALS['theme_style'].'/template/link.html';
	$GLOBALS['theme_post_post'] = $GLOBALS['theme_style'].'/template/post.html';

	$GLOBALS['rss'] = $GLOBALS['racine'].'rss.php';
}

// table of recognized filetypes, for file-upload script.
$GLOBALS['files_ext'] = array(
	'archive'		=> array('zip', '7z', 'rar', 'tar', 'gz', 'bz', 'bz2', 'xz', 'lzma'),
	'executable'	=> array('exe', 'e', 'bin', 'run'),
	'android-apk'	=> array('apk'),
	'html-xml'		=> array('html', 'htm', 'xml', 'mht'),
	'image'			=> array('png', 'gif', 'bmp', 'jpg', 'jpeg', 'ico', 'svg', 'tif', 'tiff'),
	'music'			=> array('mp3', 'wave', 'wav', 'ogg', 'wma', 'flac', 'aac', 'mid', 'midi', 'm4a'),
	'presentation'	=> array('ppt', 'pptx', 'pps', 'ppsx', 'odp'),
	'pdf'				=> array('pdf', 'ps', 'psd'),
	'spreadsheet'	=> array('xls', 'xlsx', 'xlt', 'xltx', 'ods', 'ots', 'csv'),
	'text_document'=> array('doc', 'docx', 'rtf', 'odt', 'ott'),
	'text-code'		=> array('txt', 'css', 'py', 'c', 'cpp', 'dat', 'ini', 'inf', 'text', 'conf', 'sh'),
	'video'			=> array('mp4', 'ogv', 'avi', 'mpeg', 'mpg', 'flv', 'webm', 'mov', 'divx', 'rm', 'rmvb', 'wmv'),
	'other'			=> array(''), // par défaut
);


