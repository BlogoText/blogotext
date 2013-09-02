<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden <ti-mo@myopera.com>
#
# BlogoText is free software, you can redistribute it under the terms of the
# Creative Commons Attribution-NonCommercial 2.0 France Licence
#
# Also, any distributors of non-official releases MUST warn the final user of it, by any visible way before the download.
# *** LICENSE ***

$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();
$begin = microtime(TRUE);

$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);


afficher_top('Mon Plugin');
echo '<div id="top">'."\n";
afficher_msg('Mon Plugin');
echo moteur_recherche('');
afficher_menu(pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME));
echo '</div>'."\n";


echo '<div id="axe">'."\n";

echo '<div id="subnav">'."\n";
echo '</div>'."\n";


echo '<div id="page">'."\n";















//  y o u r
//   c o d e 
//  h e r e























footer('', $begin);
?>
