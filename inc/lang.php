<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden <ti-mo@myopera.com>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

$GLOBALS['langs'] = array("fr" => 'Français', "en" => 'English', "nl" => 'Nederlands', "de" => 'Deutsch');

if (empty($GLOBALS['lang'])) $GLOBALS['lang'] = '';

switch ($GLOBALS['lang']) {
	case 'fr':
		include_once('lang/fr_FR.php');
		break;
	case 'de':
		include_once('lang/de_DE.php');
		break;
	case 'nl':
		include_once('lang/nl_NL.php');
		break;
	case 'en':
		include_once('lang/en_EN.php');
		break;
	default:
		include_once('lang/fr_FR.php');
}
