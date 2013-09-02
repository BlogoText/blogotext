<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2012 Timo Van Neerden <ti-mo@myopera.com>
#
# BlogoText is free software, you can redistribute it under the terms of the
# Creative Commons Attribution-NonCommercial 2.0 France Licence
#
# Also, any distributors of non-official releases MUST warn the final user of it, by any visible way before the download.
# *** LICENSE ***

$begin = microtime(TRUE);
$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();

$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);

$tableau = array();
if (!empty($_GET['q'])) {
	$query = "SELECT * FROM articles WHERE ( bt_content LIKE ? OR bt_title LIKE ? ) ORDER BY bt_date DESC";
	$tableau = liste_elements($query, array('%'.urldecode($_GET['q']).'%', '%'.urldecode($_GET['q']).'%'), 'articles');
}

elseif ( !empty($_GET['filtre']) ) {
	// for "tags" the requests is "tag.$search" : here we split the type of search and what we search.
	$type = substr($_GET['filtre'], 0, -strlen(strstr($_GET['filtre'], '.')));
	$search = htmlspecialchars(ltrim(strstr($_GET['filtre'], '.'), '.'));

	if ( preg_match('#^\d{6}(\d{1,8})?$#', $_GET['filtre']) ) {
		$query = "SELECT * FROM articles WHERE bt_date LIKE ? ORDER BY bt_date DESC";
		$tableau = liste_elements($query, array($_GET['filtre'].'%'), 'articles');
	}
	elseif ($_GET['filtre'] == 'draft' or $_GET['filtre'] == 'pub') {
		$query = "SELECT * FROM articles WHERE bt_statut=? ORDER BY bt_date DESC";
		$tableau = liste_elements($query, array((($_GET['filtre'] == 'draft') ? 0 : 1)), 'articles');
	}
	elseif ($type == 'tag' and $search != '') {
		$query = "SELECT * FROM articles WHERE bt_categories LIKE ? OR bt_categories LIKE ? OR bt_categories LIKE ? OR bt_categories LIKE ? ORDER BY bt_date DESC";

		$tableau = liste_elements($query, array($search, $search.',%', '%, '.$search, '%, '.$search.', %'), 'articles');
	}
	else {
		$query = "SELECT * FROM articles ORDER BY bt_date DESC LIMIT 0, ".$GLOBALS['max_bill_admin'];
		$tableau = liste_elements($query, array(), 'articles');
	}
}
else {
		$query = "SELECT * FROM articles ORDER BY bt_date DESC LIMIT 0, ".$GLOBALS['max_bill_admin'];
		$tableau = liste_elements($query, array(), 'articles');
}

afficher_top($GLOBALS['lang']['mesarticles']);
echo '<div id="top">'."\n";
afficher_msg($GLOBALS['lang']['mesarticles']);
echo moteur_recherche($GLOBALS['lang']['search_in_articles']);
afficher_menu(pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME));
echo '</div>'."\n";

echo '<div id="axe">'."\n";

// SUBNAV
echo '<div id="subnav">'."\n";

echo '<p id="mode">'."\n";
	echo '<span id="lien-comments">'.ucfirst(nombre_articles(count($tableau))).' '.$GLOBALS['lang']['sur'].' '.liste_elements_count("SELECT count(*) AS nbr FROM articles", array()).'</span>';
echo '</p>'."\n";


if (isset($_GET['filtre'])) {
	afficher_form_filtre('articles', htmlspecialchars($_GET['filtre']));
} else {
	afficher_form_filtre('articles', '');
}
echo '</div>'."\n";

echo '<div id="page">'."\n";

afficher_liste_articles($tableau);

footer('', $begin);
?>
