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

define('BT_ROOT_ADMIN', dirname(dirname(__file__)).'/');
define('IS_IN_ADMIN', true);

require_once '../inc/boot.php';

require_once BT_ROOT_ADMIN.'inc/auth.php'; // Security, dont move !
require_once BT_ROOT_ADMIN.'inc/filesystem.php';
require_once BT_ROOT_ADMIN.'inc/form.php';
require_once BT_ROOT_ADMIN.'inc/sqli.php';
require_once BT_ROOT_ADMIN.'inc/tpl.php'; // no choice !

// Some actions are not required on install and login pages
if (!defined('BT_RUN_INSTALL') && !defined('BT_RUN_LOGIN') && !defined('BT_RUN_CRON')) {
    define('URL_BACKUP', URL_ROOT.'bt_backup/');
    auth_ttl();
}
if (!defined('BT_RUN_INSTALL') && !defined('BT_RUN_LOGIN')) {
    $GLOBALS['db_handle'] = open_base();
}
