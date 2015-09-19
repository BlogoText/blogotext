<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2015 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();
$begin = microtime(TRUE);

$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);
$GLOBALS['liste_flux'] = open_serialzd_file($GLOBALS['fichier_liste_fluxrss']);

//foreach ($GLOBALS['liste_flux'] as $url => $arr) {
//	$GLOBALS['liste_flux'][$url]['time'] -= 80000;
//	$GLOBALS['liste_flux'][$url]['checksum'] = '42';
//	$GLOBALS['liste_flux'][$url]['iserror'] = 1;
//}
//file_put_contents($GLOBALS['fichier_liste_fluxrss'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_flux']))).' */');
//debug($GLOBALS['liste_flux']);

// TRAITEMENT

$erreurs = array();
if (isset($_POST['verif_envoi'])) {
	$erreurs = valider_form_rss();
	if (empty($erreurs)) {
		traiter_form_rssconf();
	}
}

afficher_html_head($GLOBALS['lang']['mesabonnements']);
echo '<div id="top">'."\n";
afficher_msg();
//echo moteur_recherche($GLOBALS['lang']['search_in_links']);
afficher_topnav(basename($_SERVER['PHP_SELF']), $GLOBALS['lang']['mesabonnements']);
echo '</div>'."\n";

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

if (isset($_GET['config'])) {
	echo afficher_form_rssconf($erreurs);
	echo "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
}

else {
	// get list of posts from DB
	$all_flux = liste_elements('SELECT * FROM rss WHERE bt_statut=1 ORDER BY bt_date DESC', array(), 'rss');
	// send to browser
	$out_html = send_rss_json($all_flux);
	$out_html .= '<div id="rss-list">'."\n";
	$out_html .= "\t".'<div id="posts-menu">'."\n";
	$out_html .= "\t\t".'<span id="count-posts"><span id="counter"></span></span>'."\n";
	$out_html .= "\t\t".'<span id="message-return"></span>'."\n";
	$out_html .= "\t\t".'<ul>'."\n";
	$out_html .= "\t\t\t".'<li><button type="button" onclick="refresh_all_feeds(this);" title="'.$GLOBALS['lang']['rss_label_refresh'].'"></button></li>'."\n";
	$out_html .= "\t\t\t".'<li><button type="button" onclick="sendMarkReadRequest(\'all\', \'\', true);" id="markasread" title="'.$GLOBALS['lang']['rss_label_markasread'].'"></button></li>'."\n";
	$out_html .= "\t\t\t".'<li><button type="button" onclick="openAllItems(this);" title="'.$GLOBALS['lang']['rss_label_unfoldall'].'"></button></li>'."\n";
	$out_html .= "\t\t\t".'<li><button type="button" onclick="addNewFeed();" title="'.$GLOBALS['lang']['rss_label_addfeed'].'"></button></li>'."\n";
	$out_html .= "\t\t\t".'<li><button type="button" onclick="window.location= \'?config\';" title="'.$GLOBALS['lang']['rss_label_config'].'"></button></li>'."\n";
	$out_html .= "\t\t\t".'<li><button type="button" onclick="window.location.href=\'maintenance.php#form_import\'" title="Import/export"></button></li>'."\n";
	$out_html .= "\t\t\t".'<li><button type="button" onclick="return cleanList();" title="'.$GLOBALS['lang']['rss_label_clean'].'"></button></li>'."\n";
	$out_html .= "\t\t".'</ul>'."\n";
	$out_html .= "\t".'</div>'."\n";
	$out_html .= "\t".'<div id="posts-wrapper">'."\n";
	$out_html .= "\t\t".'<ul id="feed-list">'."\n";
	$out_html .= feed_list_html();
	$out_html .= "\t\t".'</ul>'."\n";
	if (empty($GLOBALS['liste_flux'])) {
		$out_html .= $GLOBALS['lang']['rss_nothing_here_note'].'<a href="maintenance.php#form_import">import OPML</a>.';
	}
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
//				(e || window.event).returnValue = \'Sync?\' || \'\';
//				return \'Sync?\';
			}
			else { return true; }
		});'."\n";

	echo ''."\n";
	echo js_rss_add_feed(0);
	echo js_rss_clean_db(0);
	echo "\n".'</script>'."\n";
}

footer('', $begin);
