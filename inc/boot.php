<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BlogoText/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***

// it's not for 0.00000002 sec ...
$begin = microtime(true);

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


// if dev mod
ini_set('display_errors', (int) DEBUG);
if (DEBUG) {
    error_reporting(-1);
} else {
    error_reporting(0);
}

/**
 * set ignore repeat for same message except if it's come from diffent line/file
 */
ini_set('ignore_repeated_errors', 1);
ini_set('ignore_repeated_source', 0);


/**
 * like error_log($message) but with addionals informations
 * push in $GLOBALS['errors']
 * can be used to only push in $GLOBALS['errors']
 *
 * @param string $message
 * @param bool $write, write in log file
 */
function log_error($message, $write = true)
{
    if ($write === true && defined('DIR_LOG')) {
        create_folder(DIR_LOG, true);
        $logFile = DIR_LOG.'errors-'.date('Ymd').'.log';
        $trace = debug_backtrace();
        $trace = (end($trace));
        $where = str_replace(BT_ROOT, '', $trace['file']);
        $log = sprintf(
            '[v%s, %s] %s %s at [%s:%d]',
            BLOGOTEXT_VERSION,
            date('H:i:s'),
            $message,
            (!empty($trace['function'])) ? 'in '.$trace['function'].'()' : '',
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

        error_log(addslashes($log)."\n", 3, $logFile);
    }
}

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
 * dirty fix/message for install BT >= 3.7 && < 3.7.2
 */
define('PHP_INTL', function_exists('idn_to_ascii'));
if (!PHP_INTL) {
    function idn_to_ascii($string)
    {
        // œ => oe ; æ => ae
        $sanitized = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
        $sanitized = htmlentities($sanitized, ENT_QUOTES, 'UTF-8'); // é => &eacute;
        $sanitized = preg_replace('#&(.)(acute|grave|circ|uml|cedil|tilde|ring|slash|caron);#', '$1', $sanitized); // &eacute => e
        $sanitized = preg_replace('#&([a-z]{2})lig;#i', '$1', $sanitized);
        $sanitized = preg_replace("/[^a-z0-9-_\.\~]/", '', $sanitized);
        if (empty(preg_replace("/[^a-z0-9]/", '', $sanitized))) {
            $sanitized = substr(md5($string), 0, 12);
        } else if ($string != $sanitized) {
            $sanitized .= '-'.substr(md5($string), 0, 6);
        }
        return $sanitized;
    }
}


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
    // idn_to_ascii() fix found at https://github.com/thephpleague/uri/pull/106/files
    $bugged_idn = (PHP_VERSION_ID >= 70200);
    foreach ($exploded as $type => &$val) {
        if ($type == 'path') {
            $tmp = explode('/', $val);
            foreach ($tmp as &$v) {
                if (!empty($v)) {
                    $v = ($bugged_idn) ? @idn_to_ascii($v) : idn_to_ascii($v);
                }
            }
            $val = implode('/', $tmp);
        } else {
            $val = ($bugged_idn) ? @idn_to_ascii($val) : idn_to_ascii($val);
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

// Constants: folders
define('DIR_ADDONS', BT_ROOT.'addons/');
define('DIR_BACKUP', BT_ROOT.'bt_backup/');
define('DIR_CONFIG', BT_ROOT.'config/');
define('DIR_DATABASES', BT_ROOT.'databases/');
define('DIR_DOCUMENTS', BT_ROOT.'files/');
define('DIR_IMAGES', BT_ROOT.'img/');
define('DIR_THEMES', BT_ROOT.'themes/');
define('DIR_VAR', BT_ROOT.'var/');
define('DIR_LOG', DIR_VAR.'log/');

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
define('BLOGOTEXT_SITE', 'https://github.com/BlogoText/blogotext');
define('BLOGOTEXT_VERSION', '3.7.7');
define('MINIMAL_PHP_REQUIRED_VERSION', '5.5');
define('BLOGOTEXT_UA', 'Mozilla/5.0 (Windows NT 10; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0');

// more constants after advanced boot


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

    // is it a valias ?
    if (is_file(DIR_VAR.$vhost.'/settings/valias.php')) {
        include DIR_VAR.$vhost.'/settings/valias.php';
        if (!isset($vhost) || !isset($valias)) {
            log_error('Wrong VALIAS settings for '. $vhost, true);
            die('Wrong VALIAS settings');
        }
        if (!is_dir(DIR_VAR.'/'.$vhost.'/')) {
            log_error('VHOST handler for '. $vhost .' doesn\'t exists', true);
            die('VHOST declared for this VALIAS doesn\'t exists :/');
        }
    }

    // load prefs.php
    require_once FILE_SETTINGS;

    // check the http_host with $GLOBALS['racine']
    if (strpos($GLOBALS['racine'], $_SERVER['HTTP_HOST']) === false) {
        die('Your HTTP HOST doesn\'t match the config of this BlogoText');
    }

    define('DIR_VHOST', DIR_VAR.$vhost.'/');
    define('DIR_VHOST_ADDONS', DIR_VHOST.'addons/');
    // check the var/domain.tld/ exits
    if (!is_dir(DIR_VHOST_ADDONS)) {
        require_once BT_ROOT.'inc/filesystem.php';
        if (!create_folder(DIR_VHOST_ADDONS, true, true)) {
            die('BlogoText can\'t create '. DIR_VHOST_ADDONS .', please check your file system rights for this folder.');
        }
    }

    if (isset($valias)) {
        define('URL_ROOT', $valias . ((strrpos($valias, '/', -1) === false) ? '/' : '' ));
    } else {
        define('URL_ROOT', $GLOBALS['racine'] . ((strrpos($GLOBALS['racine'], '/', -1) === false) ? '/' : '' ));
    }

    // Timezone
    date_default_timezone_set($GLOBALS['fuseau_horaire']);

    // Constants: folders
    define('DIR_CACHE', DIR_VAR.'cache/');
    define('DIR_VHOST_CACHE', DIR_VHOST.'cache/');
    define('DIR_VHOST_DATABASES', DIR_VHOST.'databases/');

    // Constants: databases
    define('ADDONS_DB', DIR_VHOST_DATABASES.'addons.php');

    // Constants: HTTP URL
    define('URL_VAR', URL_ROOT);
    define('URL_DOCUMENTS', URL_VAR.'files/');
    define('URL_IMAGES', URL_VAR.'img/');
}

// init some vars
$GLOBALS['addons'] = array();
$GLOBALS['form_commentaire'] = '';

// advanced
import_ini_file(FILE_SETTINGS_ADV);

// db
import_ini_file(FILE_MYSQL);

// user
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
 * main dependancys
 */
require_once BT_ROOT.'inc/conv.php';
require_once BT_ROOT.'inc/filesystem.php';
require_once BT_ROOT.'inc/form.php';
require_once BT_ROOT.'inc/hook.php';
require_once BT_ROOT.'inc/html.php';
require_once BT_ROOT.'inc/sqli.php';
require_once BT_ROOT.'inc/them.php';
require_once BT_ROOT.'inc/util.php';

/**
 * init lang
 */
lang_set_list();
lang_load_land(defined('IS_IN_ADMIN'));
