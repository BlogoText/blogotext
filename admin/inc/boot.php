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

/**
 * All file in /admin/inc/*.php must be included here (except boot.php).
 * TODO optimise: for the v4.0
 */
// require_once BT_ROOT.'admin/inc/addons.php'; // Done !
require_once BT_ROOT.'admin/inc/auth.php'; // Security, dont move !
require_once BT_ROOT.'admin/inc/filesystem.php';
require_once BT_ROOT.'admin/inc/form.php';
// require_once BT_ROOT.'admin/inc/links.php'; // Done !
require_once BT_ROOT.'admin/inc/tpl.php'; // no choice !

// auth everywhere except for install and login page
if (!defined('BT_RUN_INSTALL') && !defined('BT_RUN_LOGIN')) {
    auth_ttl();
}
