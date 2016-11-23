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


// Constant is admin // dont trust _SERVER, need more security
define('IS_IN_ADMIN', true);

// use init and security of public side
require_once '../inc/boot.php';


// TODO FIX: for the v4.0 remove this line, if we can.
// require_once BT_ROOT.'inc/conf.php';

// require_once BT_ROOT.'admin/inc/inc.php';

/**
 * All file in /admin/inc/*.php must be included here.
 * TODO optimise: for the v4.0
 */
require_once BT_ROOT.'admin/inc/auth.php';
require_once BT_ROOT.'admin/inc/addons.php';
require_once BT_ROOT.'admin/inc/form.php';
require_once BT_ROOT.'admin/inc/sqli.php';
require_once BT_ROOT.'admin/inc/tpl.php';

// auth everywhere except for install and login page
if (!defined('BT_RUN_INSTALL') && !defined('BT_RUN_LOGIN')) {
    auth_ttl();
}
