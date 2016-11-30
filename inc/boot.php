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

// sources :
// require_once 'inc/defines.php';
// require_once BT_ROOT.'inc/conf.php';
// require_once BT_ROOT.'inc/inc.php';

// it's not for 0.00000002 sec ...
$begin = microtime(true);

/**
 * reorder by needs/priority
 * todo: reorder when 3.7 freeze
 * todo: reorder when 4.0 dev
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
 * Constant for absolute PATH
 * Defined early for error logging purpose.
 */
define('BT_ROOT', dirname(dirname(__file__)).'/');


/**
 * No special need to edit under this line
 * Except if it's a dev core and working in a dev version or ...
 */


// if dev mod
ini_set('display_errors', (int) DEBUG);
if (DEBUG) {
    error_reporting(-1);
} else {
    error_reporting(0);
}



/** [POC] log system
 * What about logrotate ?
 *
 * enable log and set custom log path for PHP
 *
 * if you want to push an error message use with addionals informations use log_error('your message')
 *                                          without                       error_log('your message')
 *
 * if you want to remove this POC, make sure to remove all log_error() in BT
 */
// TODO dev: do we really need these two parameters?
ini_set('ignore_repeated_errors', 1);
ini_set('ignore_repeated_source', 1);


/** [POC]
 * like error_log($message) but with addionals informations
 * push in $GLOBALS['errors']
 * can be used to only push in $GLOBALS['errors']
 *
 * ! if removed, take care, used in [core/addon] and maybe in other process ...
 *
 * @param string $message
 * @param bool $write, write in log file
 */
function log_error($message, $write = true)
{
    if ($write === true) {
        $trace = debug_backtrace();
        $trace = $trace[1];
        $where = str_replace(BT_ROOT, '', $trace['file']);
        $log = sprintf(
            '[%s, v%s] %s in %s() at [%s:%d]',
            date('Y-m-d H:i:s T'),
            BLOGOTEXT_VERSION,
            $message,
            $trace['function'],
            $where,
            $trace['line']
        );

        if (DEBUG) {
            ob_start();
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $stack = ob_get_contents();
            ob_end_clean();

            // Remove the first item from backtrace as it's redundant.
            $stack = explode("\n", trim($stack));
            array_shift($stack);
            $stack = array_reverse($stack);
            $stack = implode("\n", $stack);

            // Remove numbers, not interesting
            $stack = preg_replace('/#\d+\s+/', '    -> ', $stack);

            // Anon paths (cleaner and smaller paths)
            $stack = str_replace(BT_ROOT, '', $stack);

            $log .= "\n".'Stack trace:'."\n".$stack;
        }

        error_log(addslashes($log)."\n", 3, BT_ROOT.'var/php-error.log');
    }
}
// END OF [POC] log system




/**
 * function to keep here
 */

/**
 * Import several .ini config files with this function
 * and make ini var as a php constant
 *
 * @param string $file_path, the ini absolute path
 * @return bool
 */
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
 * https://example.com to /var/example.com/
 * This part will be modified and used for the v3.7 and v4.0
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

    $http_host = htmlspecialchars($http_host, ENT_QUOTES);

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
    if (defined('IS_IN_ADMIN') && !empty($exploded['path'])) {
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
    $path = preg_replace("/[^a-z0-9-_\.\~]/", '-', $path);
    // clean first and last char when -
    $path = trim($path, '-');
    // clean first and last char when . (prevent toto.onion./addons)
    $path = trim($path, '.');


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
 * todo : preparation v4
 */
// Constants: folders
define('DIR_ADDONS', BT_ROOT.'addons/');
define('DIR_ADMIN', BT_ROOT.'admin/');
define('DIR_BACKUP', BT_ROOT.'bt_backup/');
define('DIR_CONFIG', BT_ROOT.'config/');
define('DIR_DATABASES', BT_ROOT.'databases/');
define('DIR_DOCUMENTS', BT_ROOT.'files/');
define('DIR_IMAGES', BT_ROOT.'img/');
define('DIR_THEMES', BT_ROOT.'themes/');
define('DIR_VAR', BT_ROOT.'var/');

// Constants: databases
define('FILES_DB', DIR_DATABASES.'files.php');
define('FEEDS_DB', DIR_DATABASES.'rss.php');

// Constants: installation configurations
define('FILE_USER', DIR_CONFIG.'user.php');
define('FILE_SETTINGS', DIR_CONFIG.'settings.php');
define('FILE_SETTINGS_ADV', DIR_CONFIG.'settings-advanced.php');
define('FILE_MYSQL', DIR_CONFIG.'mysql.php');

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
if (!is_file(FILE_USER) || !is_file(FILE_SETTINGS)) {
    // if this is install script, dont redirect
    if (!defined('BT_RUN_INSTALL')) {
        if (defined('IS_IN_ADMIN')) {
            exit(header('Location: install.php'));
        } else {
            exit(header('Location: admin/install.php'));
        }
    }
}

/**
 * must run if :
 *   - user will reset is password
 *   - install after the set of FILE_SETTINGS
 *   - normal use
 */
if (is_file(FILE_SETTINGS)) {
    $vhost = secure_host_to_path($_SERVER['HTTP_HOST']);

    if (is_array($vhost)) {
        die($vhost['message']);
    }

    // POC VALIAS
    // valias
    if (is_file(DIR_VAR.$vhost.'/settings/valias.php')) {
        include DIR_VAR.$vhost.'/settings/valias.php';
        /**
         * for the poc, just put an
         *  <?php
         *  $valias = 'http://l-url-de-l-alias/';
         *  $vhost = 'the-name-of-the-vhost-dir'; // /var/the-name-of-the-vhost-dir/
         *  ?>
         *
         * alt. on pourrais aussi jouer avec $vhost = 'http://url-du-vost/ et le passer dans secure_host_to_path()
         */

        // petit test
        if (!is_dir(DIR_VAR.'/'.$vhost.'/')) {
            die('VHOST declared for this VALIAS doesn\'t exists :/');
        }
    }

    // boot 3.7
    if (version_compare(BLOGOTEXT_VERSION, '4.0', '<')) {
        // load prefs.php
        require_once FILE_SETTINGS;

        // check the http_host with $GLOBALS['racine']
        if (strpos($GLOBALS['racine'], $_SERVER['HTTP_HOST']) === false) {
            die('Your HTTP HOST doesn\'t match the config of this BlogoText');
        }

        define('DIR_VHOST', DIR_VAR.$vhost.'/');
        define('DIR_VHOST_ADDONS', DIR_VHOST.'addons/');
        // check the var/domain.tld/ exits
        // must create it, ready for v4
        if (!is_dir(DIR_VHOST_ADDONS)) {
            require_once BT_ROOT.'inc/filesystem.php';
            if (!create_folder(DIR_VHOST_ADDONS, true, true)) {
                die('BlogoText can\'t create '. DIR_VHOST_ADDONS .', please check your file system rights for this folder.');
            }
        }

        if (isset($valias)) { // [POC] valias
            define('URL_ROOT', $valias . ((strrpos($valias, '/', -1) === false) ? '/' : '' ));
        } else {
            define('URL_ROOT', $GLOBALS['racine'] . ((strrpos($GLOBALS['racine'], '/', -1) === false) ? '/' : '' ));
        }
        define('URL_VAR', URL_ROOT); // $GLOBALS['racine'] must end with '/'

    /**
     * boot 4.X
     *
     * this part must be remove during 3.7 freeze
     * Cette partie ne sert qu'à dessiner les contours du boot de la v4
     * afin de réfléchir sur le long terme au refactor du boot qui sera nécessaire (sûr a 99%)
     */
    } else if (version_compare(BLOGOTEXT_VERSION, '4.0', '>=')) {
        // check for folder
        if (!is_dir(DIR_VAR.$vhost.'/')) {
            die('BlogoText can\'t find the var fold for your HTTP HOST');
        }
        // check for prefs.php
        if (!is_file(DIR_VAR.$vhost.'/settings/prefs.php')) {
            die('BlogoText can\'t find or read your prefs.ini');
        }
        require_once DIR_VAR.$vhost.'/settings/prefs.php';

        if (strpos($GLOBALS['racine'], $_SERVER['HTTP_HOST']) === false) {
            die('Your HTTP HOST doesn\'t match the config of this BlogoText');
        }
        // seem's good ;)
        if (isset($valias)) { // [POC] valias
            define('URL_ROOT', $valias . ((strrpos($valias, '/', -1) === false) ? '/' : '' ));
        } else {
            define('URL_ROOT', $GLOBALS['racine'] . ((strrpos($GLOBALS['racine'], '/', -1) === false) ? '/' : '' ));
        }
        define('URL_VAR', URL_ROOT .'var/'.$vhost.'/'); // URL_ROOT must end with '/'

        define('DIR_VHOST', DIR_VAR.$vhost.'/');
        define('DIR_VHOST_ADDONS', DIR_VHOST.'addons/');
    }

    // Timezone
    date_default_timezone_set($GLOBALS['fuseau_horaire']);

    /**
     * defines [vhost ready]
     */

    // Constants: folders
    define('DIR_VHOST_CACHE', DIR_VHOST.'cache/');
    // we can break cache safely for 3.7, it's just cache
    define('DIR_CACHE', DIR_VAR.'cache/');

    // Constants: databases
    define('DIR_VHOST_DATABASES', DIR_VHOST.'databases/');
    define('ADDONS_DB', DIR_VHOST_DATABASES.'addons.php'); // added in 3.7, must be [vhost ready]

    // Constants: HTTP URL
    // define('URL_DATABASES', URL_VAR.'databases/'); // useless ?
    define('URL_DOCUMENTS', URL_VAR.'files/');
    define('URL_IMAGES', URL_VAR.'img/');
    // define('URL_THEMES', URL_VAR.'themes/');// not already used + see issues #155
}
/**
 * END OF /var/ part
 */


// INIT SOME VARS
$GLOBALS['addons'] = array();
$GLOBALS['form_commentaire'] = '';

// ADVANCED CONFIG OPTIONS
import_ini_file(FILE_SETTINGS_ADV);

// DATABASE OPTIONS + MySQL DB PARAMS
import_ini_file(FILE_MYSQL);

// USER LOGIN + PW HASH
import_ini_file(FILE_USER);


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
    $GLOBALS['rss'] = URL_ROOT.'rss.php';
}




/**
 * All file in /inc/*.php must be included here (except boot.php).
 * TODO optimise: for the v4.0
 */
// require_once BT_ROOT.'inc/addons.php'; // push in file who need it
require_once BT_ROOT.'inc/common.php';
require_once BT_ROOT.'inc/conv.php';
require_once BT_ROOT.'inc/filesystem.php';
require_once BT_ROOT.'inc/form.php';
require_once BT_ROOT.'inc/hook.php';
require_once BT_ROOT.'inc/html.php';
require_once BT_ROOT.'inc/sqli.php';
require_once BT_ROOT.'inc/them.php';
require_once BT_ROOT.'inc/tpl.php';
require_once BT_ROOT.'inc/util.php';

/**
 * init lang
 */
lang_set_list();
lang_load_land(defined('IS_IN_ADMIN'));
