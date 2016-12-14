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

define('IS_IN_ADMIN', true);
require_once '../inc/boot.php';


/**
 * All file in /admin/inc/*.php must be included here (except boot.php).
 * TODO optimise: for the v4.0
 */
require_once BT_ROOT.'admin/inc/auth.php'; // Security, dont move !
require_once BT_ROOT.'admin/inc/filesystem.php';
require_once BT_ROOT.'admin/inc/form.php';
require_once BT_ROOT.'admin/inc/sqli.php';
require_once BT_ROOT.'admin/inc/tpl.php'; // no choice !

// Some actions are not required on install and login pages
if (!defined('BT_RUN_INSTALL') && !defined('BT_RUN_LOGIN')) {
    auth_ttl();
    $GLOBALS['db_handle'] = open_base();
}
