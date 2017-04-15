<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

define('BT_ROOT', '../');

require_once '../inc/inc.php';

operate_session();
$begin = microtime(TRUE);

$GLOBALS['db_handle'] = open_base();
$GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);

$erreurs = array();
if (isset($_POST['verif_envoi'])) {
	$erreurs = valider_form_rss();
	if (empty($erreurs)) {
		traiter_form_rssconf();
	}
}

$tableau = array();
if (!empty($_GET['q'])) {
	$sql_where_status = '';
	$q_query = $_GET['q'];
	// search "in:read"
	if (substr($_GET['q'], -8) === ' in:read') {
		$sql_where_status = 'AND bt_statut=0 ';
		$q_query = substr($_GET['q'], 0, strlen($_GET['q'])-8);
	}
	// search "in:unread"
	if (substr($_GET['q'], -10) === ' in:unread') {
		$sql_where_status = 'AND bt_statut=1 ';
		$q_query = substr($_GET['q'], 0, strlen($_GET['q'])-10);
	}
	$arr = parse_search($q_query);


	$sql_where = implode(array_fill(0, count($arr), '( bt_content || bt_title ) LIKE ? '), 'AND '); // AND operator between words
	$query = "SELECT * FROM rss WHERE ".$sql_where.$sql_where_status."ORDER BY bt_date DESC";
	//debug($query);
	$tableau = liste_elements($query, $arr, 'rss');
} else {
	$tableau = liste_elements('SELECT * FROM rss WHERE bt_statut=1 ORDER BY bt_date DESC', array(), 'rss');
}


afficher_html_head($GLOBALS['lang']['mesabonnements']);

echo '<div id="header">'."\n";
	echo '<div id="top">'."\n";
	afficher_msg();
	echo moteur_recherche();
	afficher_topnav($GLOBALS['lang']['mesabonnements']);
	echo '</div>'."\n";

	if (!isset($_GET['config'])) {
		echo "\t".'<div id="rss-menu">'."\n";
		echo "\t\t".'<span id="count-posts"><span id="counter"></span></span>'."\n";
		echo "\t\t".'<span id="message-return"></span>'."\n";
		echo "\t\t".'<ul class="rss-menu-buttons">'."\n";
		echo "\t\t\t".'<li><button type="button" onclick="refresh_all_feeds(this);" title="'.$GLOBALS['lang']['rss_label_refresh'].'"></button></li>'."\n";
//		echo "\t\t\t".'<li><button type="button" onclick="sendMarkReadRequest(\'all\', \'\', true);" title="'.$GLOBALS['lang']['rss_label_markasread'].'"></button></li>'."\n";
//		echo "\t\t\t".'<li><button type="button" onclick="openAllItems(this);" title="'.$GLOBALS['lang']['rss_label_unfoldall'].'"></button></li>'."\n";
//		echo "\t\t\t".'<li><button type="button" onclick="addNewFeed();" title="'.$GLOBALS['lang']['rss_label_addfeed'].'"></button></li>'."\n";
		echo "\t\t\t".'<li><button type="button" onclick="window.location= \'?config\';" title="'.$GLOBALS['lang']['rss_label_config'].'"></button></li>'."\n";
		echo "\t\t\t".'<li><button type="button" onclick="window.location.href=\'maintenance.php#form_import\'" title="Import/export"></button></li>'."\n";
		echo "\t\t\t".'<li><button type="button" onclick="return cleanList();" title="'.$GLOBALS['lang']['rss_label_clean'].'"></button></li>'."\n";
		echo "\t\t".'</ul>'."\n";
		echo "\t".'</div>'."\n";
		echo '<button type="button" id="fab" class="add-feed" onclick="addNewFeed();" title="'.$GLOBALS['lang']['rss_label_config'].'">'.$GLOBALS['lang']['label_lien_ajout'].'</button>'."\n";
	}

echo '</div>'."\n";

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

if (isset($_GET['config'])) {
	echo afficher_form_rssconf($erreurs);
	echo "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
}

else {
	// get list of posts from DB
	// send to browser
	$out_html = send_rss_json($tableau);
	$out_html .= '<div id="rss-list">'."\n";
	$out_html .= "\t".'<div id="posts-wrapper">'."\n";
	$out_html .= "\t\t".'<ul id="feed-list">'."\n";
	$out_html .= feed_list_html();
	$out_html .= "\t\t".'</ul>'."\n";
	$out_html .= "\t\t".'<div id="post-list-wrapper">'."\n";
	$out_html .= "\t\t\t".'<div id="post-list-title">'."\n";
	$out_html .= "\t\t\t".'<ul class="rss-menu-buttons">'."\n";
	$out_html .= "\t\t\t\t".'<li><button type="button" onclick="sendMarkReadRequest(\'all\', \'\', true);" id="markasread" title="'.$GLOBALS['lang']['rss_label_markasread'].'"></button></li>'."\n";
	$out_html .= "\t\t\t\t".'<li><button type="button" onclick="openAllItems(this);" id="openallitemsbutton" title="'.$GLOBALS['lang']['rss_label_unfoldall'].'"></button></li>'."\n";
	$out_html .= "\t\t\t".'</ul>'."\n";
	$out_html .= "\t\t\t".'<p><span id="post-counter"></span> '.$GLOBALS['lang']['label_elements'].'</p>'."\n";

	$out_html .= "\t\t\t".'</div>'."\n";
	
	
	/* here comes (in JS) the <ul id="post-list"></ul> */

	if (empty($GLOBALS['liste_flux'])) {
		$out_html .= $GLOBALS['lang']['rss_nothing_here_note'].'<a href="maintenance.php#form_import">import OPML</a>.';
	}
	$out_html .= "\t\t".'</div>'."\n";
	$out_html .= "\t".'</div>'."\n";
	$out_html .= "\t".'<div class="keyshortcut">'.$GLOBALS['lang']['rss_raccourcis_clavier'].'</div>'."\n";
	$out_html .= '</div>'."\n";

	echo $out_html;

	echo "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
	echo "\n".'<script type="text/javascript">'."\n";
	echo 'var token = \''.new_token().'\';'."\n";
	echo 'var openAllSwich = \'open\';'."\n";
	echo 'var readQueue = {"count": "0", "urlList": []};'."\n";
	echo 'var Rss = rss_entries.list;'."\n";
	echo 'window.addEventListener(\'load\', function(){
				rss_feedlist(Rss);
				window.addEventListener(\'keydown\', keyboardNextPrevious);
			});'."\n";

	echo 'window.addEventListener("beforeunload", function (e) {
			if (readQueue.count != 0) {
				sendMarkReadRequest(\'postlist\', JSON.stringify(readQueue.urlList), false);
				readQueue.urlList = [];
				readQueue.count = 0;
			}
			else { return true; }
		});'."\n";

	echo 'var scrollPos = 0;'."\n";
	echo 'window.addEventListener(\'scroll\', function(){ scrollingFabHideShow() });'."\n";

	echo 'var list = document.querySelectorAll("a[data-feed-domain]");'."\n";
	echo 'for (var i = 0, len=list.length; i < len; i++) {'."\n";
	echo '	list[i].style.backgroundImage="url(\'" + "cache/get.php?w=favicon&q="+ list[i].getAttribute(\'data-feed-domain\') + "\')";'."\n";
	echo '}'."\n\n";


	echo php_lang_to_js(0);
	echo "\n".'</script>'."\n";
}

footer($begin);
