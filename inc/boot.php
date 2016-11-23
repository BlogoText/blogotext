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

// sources :
// require_once 'inc/defines.php';
// require_once BT_ROOT.'inc/conf.php';
// require_once BT_ROOT.'inc/inc.php';

// it's not for 0.00000002 sec ...
$begin = microtime(true);

/**
 * reorder by needs/priority
 */

 // Use UTF-8 for all
mb_internal_encoding('UTF-8');

/**
 * Error reporting
 *   - false for prod
 *   - eventually true for dev or testing
*/
define('DEBUG', true);



/**
 * No special need to edit under this line
 * Except if it's a dev core
 */

/**
 * function to keep here
 */

// Import several .ini config files with this function
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
    if (!empty($exploded['path'])) {
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
                if (!empty($v)) {
                    $v = idn_to_ascii($v);
                }
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



/**
 * dev mod
 */
ini_set('display_errors', (int) DEBUG);
if (DEBUG) {
    error_reporting(-1);
} else {
    error_reporting(0);
}



//
//
// ULTRA TEMPORARY TODO FIX ÇAVAPASDUTOUT!
// Ca me parait bon maintenant ;)
//
// This is to disable addon hooks on the admin part ...
//
// RemRem, tu voulais du compliqué alors qu'on peut faire simple ? :D
// Méhhh
//



// constant for absolute PATH
define('BT_ROOT', dirname(dirname(__file__)).'/');

/**
 * todo : preparation v4
 */
// Constants: folders
define('DIR_ADDONS', BT_ROOT.'addons/');
define('DIR_ADMIN', BT_ROOT.'admin/');
define('DIR_BACKUP', BT_ROOT.'bt_backup/');
define('DIR_CACHE', BT_ROOT.'.cache/');
define('DIR_CONFIG', BT_ROOT.'config/');
define('DIR_DATABASES', BT_ROOT.'databases/');
define('DIR_DOCUMENTS', BT_ROOT.'files/');
define('DIR_IMAGES', BT_ROOT.'img/');
define('DIR_THEMES', BT_ROOT.'themes/');

// Constants: databases
define('FILES_DB', DIR_DATABASES.'files.php');
define('FEEDS_DB', DIR_DATABASES.'rss.php');

// Constants: general
define('BLOGOTEXT_NAME', 'BlogoText');
define('BLOGOTEXT_SITE', 'https://github.com/BoboTiG/blogotext');
define('BLOGOTEXT_VERSION', '3.7.0-dev');
define('MINIMAL_PHP_REQUIRED_VERSION', '5.5');
define('BLOGOTEXT_UA', 'Mozilla/5.0 (Windows NT 10; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0');

/**
 * more constants after advanced boot
 */



// system is installed 
if (!is_file(DIR_CONFIG.'user.ini') || !is_file(DIR_CONFIG.'prefs.php')) {
    // if this is install script, dont redirect
    if (!defined('BT_RUN_INSTALL')) {
        if (defined('IS_IN_ADMIN')) {
            exit(header('Location: install.php'));
        } else {
            exit(header('Location: admin/install.php'));
        }
    }
}

// if this request is about install or reset password
if (is_file(DIR_CONFIG.'prefs.php')) {
    $supposed_path = secure_host_to_path($_SERVER['HTTP_HOST']);

    if (is_array($supposed_path)) {
        die($supposed_path['message']);
    }

    if (version_compare(BLOGOTEXT_VERSION, '4.0', '<')) {
        // load prefs.php
        require_once DIR_CONFIG.'prefs.php';

        // check the http_host with $GLOBALS['racine']
        if (strpos($GLOBALS['racine'], $_SERVER['HTTP_HOST']) === false) {
            die('Your HTTP HOST doesn\'t match the config of this BlogoText');
        }
        // seem's good ;)
        define('DIR_VAR', BT_ROOT.'var/'.$supposed_path.'/');
        define('DIR_VAR_ADDONS', DIR_VAR.'addons/');
        // check the var/domain.tld/ exits
        // must create it, ready for v4
        if (!is_dir(DIR_VAR_ADDONS)) {
            require_once BT_ROOT.'inc/filesystem.php';
            if (!create_folder(DIR_VAR_ADDONS, true, true)) {
                die('BlogoText can\'t create '. DIR_VAR_ADDONS .', please check your file system rights for this folder.');
            }
        }

        define('URL_ROOT', $GLOBALS['racine'] . ((strrpos($GLOBALS['racine'], '/', -1) === false) ? '/' : '' ));
        define('URL_VAR', URL_ROOT); // $GLOBALS['racine'] must end with '/'

    // need testing
    } else if (version_compare(BLOGOTEXT_VERSION, '4.0', '>=')) {
        // check for folder
        if (!is_dir(BT_ROOT.'var/'.$supposed_path.'/')) {
            die('BlogoText can\'t find the var fold for your HTTP HOST');
        }
        // check for prefs.php
        if (!is_file(BT_ROOT.'var/'.$supposed_path.'/settings/prefs.php')) {
            die('BlogoText can\'t find or read your prefs.ini');
        }
        require_once BT_ROOT.'var/'.$supposed_path.'/settings/prefs.php';

        if (strpos($GLOBALS['racine'], $_SERVER['HTTP_HOST']) === false) {
            die('Your HTTP HOST doesn\'t match the config of this BlogoText');
        }
        // seem's good ;)
        define('URL_ROOT', $GLOBALS['racine'] . ((strrpos($GLOBALS['racine'], '/', -1) === false) ? '/' : '' ));
        define('DIR_VAR', BT_ROOT.'var/'.$supposed_path.'/');
        define('URL_VAR', URL_ROOT .'var/'.$supposed_path.'/'); // $GLOBALS['racine'] must end with '/'
        define('DIR_VAR_ADDONS', DIR_VAR.'addons/');
    }

    // Timezone
    date_default_timezone_set($GLOBALS['fuseau_horaire']);

    define('URL_DATABASES', URL_VAR.'databases/');
    define('URL_DOCUMENTS', URL_VAR.'files/');
    define('URL_IMAGES', URL_VAR.'img/');
    define('ULR_THEMES', URL_VAR.'themes/');
}
/**
 * END OF /var/ part
 */

// Constant for HTTP URL


// INIT SOME VARS
$GLOBALS['addons'] = array();
$GLOBALS['form_commentaire'] = '';

// ADVANCED CONFIG OPTIONS
import_ini_file(DIR_CONFIG.'config-advanced.ini');

// DATABASE OPTIONS + MySQL DB PARAMS
import_ini_file(DIR_CONFIG.'mysql.ini');

// USER LOGIN + PW HASH
import_ini_file(DIR_CONFIG.'user.ini');

// regenerate captcha (always)
if (!isset($GLOBALS['captcha'])) {
    $ua = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $GLOBALS['captcha']['x'] = rand(4, 9);
    $GLOBALS['captcha']['y'] = rand(1, 6);
    $GLOBALS['captcha']['hash'] = sha1($ua.($GLOBALS['captcha']['x']+$GLOBALS['captcha']['y']));
}

// THEMES FILES and PATHS
if (isset($GLOBALS['theme_choisi'])) {
    $GLOBALS['theme_style'] = str_replace(BT_ROOT, '', DIR_THEMES).$GLOBALS['theme_choisi'];
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


/**
 * todo: reduce at strict minimal require (common, utils, lang...)
 *  and require in process file when specific needs
 */
// if (is_file(DIR_CONFIG.'/prefs.php')) {
    // require_once DIR_CONFIG.'/prefs.php';
// }
require_once BT_ROOT.'inc/common.php';
// require_once BT_ROOT.'inc/conf.php'; // already merged in boot
require_once BT_ROOT.'inc/hook.php';
require_once BT_ROOT.'inc/lang.php';
require_once BT_ROOT.'inc/util.php';
require_once BT_ROOT.'inc/filesystem.php';
require_once BT_ROOT.'inc/them.php';
require_once BT_ROOT.'inc/html.php';
require_once BT_ROOT.'inc/form.php';
require_once BT_ROOT.'inc/conv.php';
require_once BT_ROOT.'inc/veri.php';
require_once BT_ROOT.'inc/imgs.php';
require_once BT_ROOT.'inc/sqli.php';
require_once BT_ROOT.'inc/addons.php';
