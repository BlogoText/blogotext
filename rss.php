<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2014 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

header('Content-Type: application/rss+xml; charset=UTF-8');
$xml = "<?".'xml version="1.0" encoding="UTF-8"'."?>"."\n";

$GLOBALS['BT_ROOT_PATH'] = '';
error_reporting(-1);
$begin = microtime(TRUE);

$GLOBALS['dossier_cache'] = 'cache';

require_once 'config/user.php';
require_once 'config/prefs.php';
date_default_timezone_set($GLOBALS['fuseau_horaire']);

function require_all() {
	require_once 'inc/lang.php';
	require_once 'inc/conf.php';
	require_once 'inc/fich.php';
	require_once 'inc/html.php';
	require_once 'inc/form.php';
	require_once 'inc/comm.php';
	require_once 'inc/conv.php';
	require_once 'inc/util.php';
	require_once 'inc/veri.php';
	require_once 'inc/sqli.php';
}

$xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">'."\n";
$xml .= '<channel>'."\n";
$xml .= '<atom:link href="'.$GLOBALS['racine'].'rss.php'.((!empty($_SERVER['QUERY_STRING'])) ? '?'.(htmlspecialchars($_SERVER['QUERY_STRING'])) : '').'" rel="self" type="application/rss+xml" />';

// RSS DU BLOG
/* si y'a un ID en paramètre : rss sur fil commentaires de l'article "ID" */
if (isset($_GET['id']) and preg_match('#^[0-9]{14}$#', $_GET['id'])) {
	require_all();
	$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);
	$article_id = htmlspecialchars($_GET['id']);

	$liste = liste_elements("SELECT * FROM commentaires WHERE bt_article_id=? AND bt_statut=1 ORDER BY bt_id DESC", array($article_id), 'commentaires');

	if (!empty($liste)) {
		$query = "SELECT * FROM articles WHERE bt_id=? AND bt_date<=".date('YmdHis')." AND bt_statut=1";
		$billet = liste_elements($query, array($article_id), 'articles');
		$xml .= '<title>Commentaires sur '.$billet[0]['bt_title'].' - '.$GLOBALS['nom_du_site'].'</title>'."\n";
		$xml .= '<link>'.$billet[0]['bt_link'].'</link>'."\n";
		$xml .= '<description><![CDATA['.$GLOBALS['description'].']]></description>'."\n";
		$xml .= '<language>fr</language>'."\n";
		$xml .= '<copyright>'.$GLOBALS['auteur'].'</copyright>'."\n";
		foreach ($liste as $comment) {
			$dec = decode_id($comment['bt_id']);
			$xml .= '<item>'."\n";
				$xml .= '<title>'.$comment['bt_author'].'</title>'."\n";
				$xml .= '<guid isPermaLink="false">'.$comment['bt_link'].'</guid>'."\n";
				$xml .= '<link>'.$comment['bt_link'].'</link>'."\n";
				$xml .= '<pubDate>'.date('r', mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee'])).'</pubDate>'."\n";
				$xml .= '<description><![CDATA['.($comment['bt_content']).']]></description>'."\n";
			$xml .= '</item>'."\n";
		}
	} else {
		$xml .= '<item>'."\n";
			$xml .= '<title>'.$GLOBALS['lang']['note_no_comment'].'</title>'."\n";
			$xml .= '<guid isPermaLink="false">'.$GLOBALS['racine'].'index.php</guid>'."\n";
			$xml .= '<link>'.$GLOBALS['racine'].'index.php</link>'."\n";
			$xml .= '<pubDate>'.date('r').'</pubDate>'."\n";
			$xml .= '<description>'.$GLOBALS['lang']['no_comments'].'</description>'."\n";
		$xml .= '</item>'."\n";
	}
}
/* sinon, fil rss sur les articles (par défaut) */
/* Ici, on utilise la petite BDD placée en cache. */
else {

	function rel2abs($article) { // convertit les URL relatives en absolues
		$article = str_replace(' src="/', ' src="http://'.$_SERVER['HTTP_HOST'].'/' , $article);
		$article = str_replace(' href="/', ' href="http://'.$_SERVER['HTTP_HOST'].'/' , $article);
		$base = $GLOBALS['racine'];
		$article = preg_replace('#(src|href)=\"(?!http)#i','$1="'.$base, $article);
		return $article;
	}


	$fcache = $GLOBALS['dossier_cache'].'/'.'cache_rss_array.dat';
	$liste = array();
	if (!file_exists($fcache)) {
		require_all();
		$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);
		rafraichir_cache();
	}
	// this function exists in SQLI.PHP. It is replaced here, because including sqli.php and the other files takes 10x more cpu load than this
	if (file_exists($fcache)) {
		$liste = unserialize(base64_decode(substr(file_get_contents($fcache), strlen('<?php /* '), -strlen(' */'))));
		if (!is_array($liste)) {
			$liste = array();
			unlink($fcache);
		}
	}

	$liste_rss = array();
	$modes_url = '';
	if (!empty($_GET['mode'])) {
		$found = 0;
		// 1 = articles
		if ( strpos($_GET['mode'], 'blog') !== FALSE ) {
			$liste_rss = array_merge($liste_rss, $liste['a']);
			$found = 1; $modes_url .= 'blog-';
		}
		// 2 = commentaires
		if ( strpos($_GET['mode'], 'comments') !== FALSE ) {
			$liste_rss = array_merge($liste_rss, $liste['c']);
			$found = 1; $modes_url .= 'comments-';
		}
		// 4 = links
		if (strpos($_GET['mode'], 'links') !== FALSE) {
			$liste_rss = array_merge($liste_rss, $liste['l']);
			$found = 1; $modes_url .= 'links-';
		}
		// si rien : prend blog
		if ($found == 0) { $liste_rss = $liste['a']; }

	// si pas de mode, on prend le blog.
	} else {
		$liste_rss = $liste['a'];
	}

	// trick : tri selon des sous-clés d'un tableau à plusieurs sous-niveaux (trouvé dans doc-PHP)
	foreach ($liste_rss as $key => $item) {
		 $bt_id[$key] = (isset($item['bt_date'])) ? $item['bt_date'] : $item['bt_id'];
	}
	if (!empty($liste_rss)) {
		array_multisort($bt_id, SORT_DESC, $liste_rss);
	}
	$liste_rss = array_slice($liste_rss, 0, 20);
	$invert = (isset($_GET['invertlinks'])) ? TRUE : FALSE;
	$xml .= '<title>'.$GLOBALS['nom_du_site'].'</title>'."\n";
	$xml .= '<link>'.$GLOBALS['racine'].((empty(trim($modes_url, '-'))) ? '' : '?mode='.(trim($modes_url, '-'))).'</link>'."\n"; 
	$xml .= '<description><![CDATA['.$GLOBALS['description'].']]></description>'."\n";
	$xml .= '<language>fr</language>'."\n";
	$xml .= '<copyright>'.$GLOBALS['auteur'].'</copyright>'."\n";
	foreach ($liste_rss as $elem) {
		$time = (isset($elem['bt_date'])) ? $elem['bt_date'] : $elem['bt_id'];
		if ($time > date('YmdHis')) { continue; }
		$title = (in_array($elem['bt_type'], array('article', 'link', 'note'))) ? $elem['bt_title'] : $elem['bt_author'];
		// normal code
		$xml_post = '<item>'."\n";
		$xml_post .= '<title>'.$title.'</title>'."\n";
		$xml_post .= '<guid isPermaLink="false">'.$GLOBALS['racine'].'?mode=links&amp;id='.$elem['bt_id'].'</guid>'."\n";
		$xml_post .= '<pubDate>'.date_create_from_format('YmdHis', $time)->format('r').'</pubDate>'."\n";
		if ($elem['bt_type'] == 'link') {
			if ($invert) {
				$xml_post .= '<link>'.$GLOBALS['racine'].'?id='.$elem['bt_id'].'</link>'."\n";
				$xml_post .= '<description><![CDATA['.rel2abs($elem['bt_content']). '<br/> — (<a href="'.$elem['bt_link'].'">link</a>)]]></description>'."\n";
			} else {
				$xml_post .= '<link>'.$elem['bt_link'].'</link>'."\n";
				$xml_post .= '<description><![CDATA['.rel2abs($elem['bt_content']).'<br/> — (<a href="'.$GLOBALS['racine'].'?id='.$elem['bt_id'].'">permalink</a>)]]></description>'."\n";
			}
		} else {
			$xml_post .= '<link>'.$elem['bt_link'].'</link>'."\n";
			$xml_post .= '<description><![CDATA['.rel2abs($elem['bt_content']).']]></description>'."\n";
		}
		$xml_post .= '</item>'."\n";
		$xml .= $xml_post;
	}
}

$end = microtime(TRUE);
$xml .= '<!-- generated in '.round(($end - $begin),6).' seconds -->'."\n";
$xml .= '</channel>'."\n";
$xml .= '</rss>';

echo $xml;
?>
