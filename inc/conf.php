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

/*
 * Some hard coded constants
 * Don’t change unless you know what you’re doing.
*/

// GENERAL
define('BLOGOTEXT_NAME', 'BlogoText');
define('BLOGOTEXT_SITE', 'https://github.com/BoboTiG/blogotext');
define('BLOGOTEXT_VERSION', '3.7.0-dev');
define('MINIMAL_PHP_REQUIRED_VERSION', '5.5');
define('BLOGOTEXT_UA', 'Mozilla/5.0 (Windows NT 10; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0');
mb_internal_encoding('UTF-8');



// IMPORT SEVERAL .ini CONFIG FILES
// with this function
function import_ini_file($file_path)
{
    if (is_file($file_path) and is_readable($file_path)) {
        $options = parse_ini_file($file_path);
        foreach ($options as $option => $value) {
            if (!defined($option)) {
                define($option, $value);
            }
        }
        return true;
    }
    return false;
}


/**
 * /var/
 * This part will be modified and used for the BT v4.0
 * I let the code aerate the time to fully validate the process
 *
 * - some security/test on _SERVER['HTTP_HOST'] which can be hacked client side
 * - vhost name based on $GLOBALS['racine'] for handle
 *    http://example.tld/blog1/
 *    http://example.tld/blog2/
 * - support idn tld (maybe at a test for server side support)
 *
 * pre-v4
 *  - basic check with $GLOBALS['racine'] which can be trusted
 *    (config/prefs.php must be loaded)
 *  - Create folders if they do not exist
 *    (must be not the case with BT v4)
 *
 * post-v4 - need some work
 *  - this code should be run as soon as possible as a security test
 *  - use valided HTTP_HOST to build the DIR_VAR path
 */

/**
 * @param string $http_host, like : example.tld || https://toto.example.tld/blog1/
 * @return string||array, if array, see array['message'] for more information
 *                        if string, safe potential file or folder name like :
 *                                  example-tld || toto-example-tld-blog1
 */
function secure_host_to_path($http_host)
{
    if (empty($http_host)) {
        return array(
            'success' => false,
            'message' => 'Your HTTP HOST seem\'s to be empty oO'
        );
    }

    // at least a.be
    if (strlen($http_host) < 3) {
        return array(
            'success' => false,
            'message' => 'Your HTTP HOST is not valid'
        );
    }

    $http_host = htmlentities($http_host, ENT_QUOTES);

    /**
     * for test purporse only !
     */
    // $http_host = $GLOBALS['racine'];
    // $http_host = 'example.com/blog1/';
    // $http_host = 'example.com/blog1/';
    // $http_host = 'blog.example.com/blog1/';
    // $http_host = '例如.中国';
    // $http_host = '例如.中国/blog/';
    // $http_host = 'سجل.السعودية/例如/admin';
    // $http_host = 'سجل.السعودية/admin/';

    // add 'http://' for a valid parse_url
    if (strpos($http_host, 'http://') !== 0 && strpos($http_host, 'https://') !== 0) {
        $http_host = 'http://'. $http_host;
    }

    $exploded = parse_url($http_host);
    if (empty($exploded['path'])) {
        $exploded['path'] = '';
    }

    // is admin url ? (remove the last "folder/")
    if (!empty($exploded['path']) && BT_ROOT == '../') {
        $tmp = trim($exploded['path'], '/');
        $tmp = explode('/', $tmp);
        array_pop($tmp);
        $exploded['path'] = '/'. implode('/', $tmp);
    }

    // domain can be idn
    foreach ($exploded as $type => &$val) {
        if ($type == 'path') {
            $tmp = explode('/', $val);
            foreach ($tmp as &$v) {
                $v = idn_to_ascii($v);
            }
            $val = implode('/', $tmp);
        } else {
            $val = idn_to_ascii($val);
        }
    }
    $path = $exploded['host'].$exploded['path'];

    // format, clean up, secure
    $path = strtolower($path);
    $path = trim($path);
    $path = preg_replace("/[^a-z0-9-_]/", '-', $path);
    // clean first and last char when -
    $path = trim($path, '-');


    // empty or
    if (empty($path) || strlen($path) < 3) {
        return array(
            'success' => false,
            'message' => 'Your HTTP HOST haven\'t survive our HTTP_HOST security test !'
        );
    }

    return $path;
}

// if this request is about install or reset password
if (is_file(BT_ROOT.'config/prefs.php')) {
    $supposed_path = secure_host_to_path($_SERVER['HTTP_HOST']);

    if (is_array($supposed_path)) {
        die($supposed_path['message']);
    }

    if (version_compare(BLOGOTEXT_VERSION, '4.0', '<')) {
        // check the http_host with $GLOBALS['racine']
        if (strpos($GLOBALS['racine'], $_SERVER['HTTP_HOST']) === false) {
            die('Your HTTP HOST doesn\'t match the config of this BlogoText');
        }
        // seem's good ;)
        define('DIR_VAR', BT_ROOT.'var/'.$supposed_path.'/');
        define('DIR_VAR_ADDONS', DIR_VAR.'addons/');
        // check the var/domain.tld/ exits
        if (!is_dir(DIR_VAR_ADDONS)) {
            require_once BT_ROOT.'/inc/filesystem.php';
            if (!create_folder(DIR_VAR_ADDONS, true, true)) {
                die('BlogoText can\'t create '. DIR_VAR_ADDONS .', please check your file system rights for this folder.');
            }
        }
    // need testing
    } else if (version_compare(BLOGOTEXT_VERSION, '4.0', '>=')) {
        // check for folder
        if (!is_dir(BT_ROOT.'var/'.$supposed_path.'/')) {
            die('BlogoText can\'t find the var fold for your HTTP HOST');
        }
        // check for prefs.php
        if (!import_ini_file(BT_ROOT.'var/'.$supposed_path.'/settings/prefs.ini')) {
            die('BlogoText can\'t find or read your prefs.ini');
        }
        if (strpos($GLOBALS['racine'], $_SERVER['HTTP_HOST']) === false) {
            die('Your HTTP HOST doesn\'t match the config of this BlogoText');
        }
        // seem's good ;)
        define('DIR_VAR', BT_ROOT.'var/'.$supposed_path.'/');
        define('DIR_VAR_ADDONS', DIR_VAR.'addons/');
    }
}
/**
 * END OF /var/ part
 */


// FOLDERS (change this only if you know what you are doing...)
DEFINE('BT_DIR', dirname(__file__, 2) . '/');// define absolute path, tired of working with relatives...
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
$GLOBALS['form_commentaire'] = '';

// ADVANCED CONFIG OPTIONS
import_ini_file(BT_ROOT.DIR_CONFIG.'/'.'config-advanced.ini');

// Error reporting
ini_set('display_errors', (bool) DISPLAY_PHP_ERRORS);
error_reporting((int) DISPLAY_PHP_ERRORS);
// ini_set('display_errors', (bool) true);
// error_reporting((int) true);

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
if (isset($GLOBALS['theme_choisi'])) {
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
    'archive' => array('zip', '7z', 'rar', 'tar', 'gz', 'bz', 'bz2', 'xz', 'lzma'),
    'executable' => array('exe', 'e', 'bin', 'run'),
    'android-apk' => array('apk'),
    'html-xml' => array('html', 'htm', 'xml', 'mht'),
    'image' => array('png', 'gif', 'bmp', 'jpg', 'jpeg', 'ico', 'svg', 'tif', 'tiff'),
    'music' => array('mp3', 'wave', 'wav', 'ogg', 'wma', 'flac', 'aac', 'mid', 'midi', 'm4a'),
    'presentation' => array('ppt', 'pptx', 'pps', 'ppsx', 'odp'),
    'pdf' => array('pdf', 'ps', 'psd'),
    'spreadsheet' => array('xls', 'xlsx', 'xlt', 'xltx', 'ods', 'ots', 'csv'),
    'text_document'=> array('doc', 'docx', 'rtf', 'odt', 'ott'),
    'text-code' => array('txt', 'css', 'py', 'c', 'cpp', 'dat', 'ini', 'inf', 'text', 'conf', 'sh'),
    'video' => array('mp4', 'ogv', 'avi', 'mpeg', 'mpg', 'flv', 'webm', 'mov', 'divx', 'rm', 'rmvb', 'wmv'),
    'other' => array(''), // par défaut
);
