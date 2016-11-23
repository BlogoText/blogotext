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

//
//
// ULTRA TEMPORARY TODO FIX ÇAVAPASDUTOUT!
//
// This is to disable addon hooks on the admin part ...
//
// RemRem, tu voulais du compliqué alors qu'on peut faire simple ? :D
//
//
define('DONT_USE_HOOK', (bool)(array_pop(explode('/', current(parse_url(dirname($_SERVER['REQUEST_URI']))))) == 'admin'));

/**
 * Error reporting
 *   - false for prod
 *   - eventually true for dev or testing
*/
define('DEBUG', true);


// /!\ DOT NOT EDIT BELOW THIS LINE /!\

ini_set('display_errors', (int) DEBUG);
if (DEBUG) {
    error_reporting(-1);
} else {
    error_reporting(0);
}

// Constants: folders
define('BT_ROOT', dirname(dirname(__file__)).'/');
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
