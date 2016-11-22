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

if (is_file(BT_ROOT.'config/prefs.php')) {
    require_once BT_ROOT.'/config/prefs.php';
}
require_once BT_ROOT.'/inc/conf.php';
require_once BT_ROOT.'/inc/hook.php';
require_once BT_ROOT.'/inc/lang.php';
require_once BT_ROOT.'/inc/util.php';
require_once BT_ROOT.'/inc/file-system.php';
require_once BT_ROOT.'/inc/them.php';
require_once BT_ROOT.'/inc/html.php';
require_once BT_ROOT.'/inc/form.php';
require_once BT_ROOT.'/inc/conv.php';
require_once BT_ROOT.'/inc/veri.php';
require_once BT_ROOT.'/inc/imgs.php';
require_once BT_ROOT.'/inc/sqli.php';
require_once BT_ROOT.'/inc/addons.php';

// temp for admin
if (BT_ROOT == '../') {
    require_once BT_ROOT.'/admin/inc/inc.php';
}
