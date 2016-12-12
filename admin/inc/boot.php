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


// Constant is admin // dont trust _SERVER, need more security
define('IS_IN_ADMIN', true);

// use init and security of public side
require_once '../inc/boot.php';


// table of recognized filetypes, for file-upload script.
$GLOBALS['files_ext'] = array(
    'archive' => array('zip', '7z', 'rar', 'tar', 'gz', 'bz', 'bz2', 'xz', 'lzma'),
    'executable' => array('exe', 'e', 'bin', 'run'),
    'android-apk' => array('apk'),
    'html-xml' => array('html', 'htm', 'xml', 'mht'),
    'image' => array('png', 'gif', 'bmp', 'jpg', 'jpeg', 'ico', 'svg', 'tif', 'tiff'),
    'music' => array('mp3', 'wave', 'wav', 'ogg', 'wma', 'flac', 'aac', 'mid', 'midi', 'm4a'),
    'presentation' => array('ppt', 'pptx', 'pps', 'ppsx', 'odp'),
    'pdf' => array('pdf', 'ps', 'psd'),
    'ebook' => array('epub', 'mobi'),
    'spreadsheet' => array('xls', 'xlsx', 'xlt', 'xltx', 'ods', 'ots', 'csv'),
    'text_document'=> array('doc', 'docx', 'rtf', 'odt', 'ott'),
    'text-code' => array('txt', 'css', 'py', 'c', 'cpp', 'dat', 'ini', 'inf', 'text', 'conf', 'sh'),
    'video' => array('mkv', 'mp4', 'ogv', 'avi', 'mpeg', 'mpg', 'flv', 'webm', 'mov', 'divx', 'rm', 'rmvb', 'wmv'),
    'other' => array(''), // par défaut
);



/**
 * All file in /admin/inc/*.php must be included here (except boot.php).
 * TODO optimise: for the v4.0
 */
require_once BT_ROOT.'admin/inc/auth.php'; // Security, dont move !
require_once BT_ROOT.'admin/inc/filesystem.php';
require_once BT_ROOT.'admin/inc/form.php';
require_once BT_ROOT.'admin/inc/sqli.php';
require_once BT_ROOT.'admin/inc/tpl.php'; // no choice !

// auth everywhere except for install and login page
if (!defined('BT_RUN_INSTALL') && !defined('BT_RUN_LOGIN')) {
    auth_ttl();
}
