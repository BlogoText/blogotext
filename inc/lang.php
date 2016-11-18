<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

$GLOBALS['langs'] = array('fr' => 'Français', 'en' => 'English');

if (empty($GLOBALS['lang'])) {
    $GLOBALS['lang'] = '';
}

switch ($GLOBALS['lang']) {
    case 'en':
        include_once('lang/en_en.php');
        break;
    case 'fr':
    default:
        include_once('lang/fr_fr.php');
}
