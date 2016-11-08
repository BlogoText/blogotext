<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

$GLOBALS['langs'] = array("fr" => 'Français', "en" => 'English');

if (empty($GLOBALS['lang'])) $GLOBALS['lang'] = '';

switch ($GLOBALS['lang']) {
	case 'fr':
		include_once('lang/fr_FR.php');
		break;
	case 'en':
		include_once('lang/en_EN.php');
		break;
	default:
		include_once('lang/fr_FR.php');
}
