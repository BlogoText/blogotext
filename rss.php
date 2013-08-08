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

header('Content-Type: application/rss+xml; charset=UTF-8');
echo "<?".'xml version="1.0" encoding="UTF-8"'."?>"."\n";

$GLOBALS['BT_ROOT_PATH'] = '';
error_reporting(-1);
$begin = microtime(TRUE);

$GLOBALS['dossier_cache'] = 'cache';

// met dans une fonction pour accéllérer encore plus le chargement (les requires sont plutôt lents en fait…) !
require_once 'config/user.php';
require_once 'config/prefs.php';
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

echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">'."\n";
echo '<channel>'."\n";
echo '<atom:link href="'.$GLOBALS['racine'].'rss.php'.((!empty($_SERVER['QUERY_STRING'])) ? '?'.(htmlspecialchars($_SERVER['QUERY_STRING'])) : '').'" rel="self" type="application/rss+xml" />';

// RSS DU BLOG
/* si y'a un ID en paramètre : rss sur fil commentaires de l'article "ID" */
if (isset($_GET['id']) and preg_match('#^[0-9]{14}$#', $_GET['id'])) {
	require_all();
	$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);
	$article_id = htmlspecialchars($_GET['id']);

	$liste = liste_elements("SELECT * FROM commentaires WHERE bt_article_id=? AND bt_statut=1 ORDER BY bt_id", array($article_id), 'commentaires');

	if (!empty($liste)) {
		$query = "SELECT * FROM articles WHERE bt_id=? AND bt_date<=".date('YmdHis')." AND bt_statut=1";
		$billet = liste_elements($query, array($article_id), 'articles');
		echo '<title>Commentaires sur '.$billet[0]['bt_title'].' - '.$GLOBALS['nom_du_site'].'</title>'."\n";
		echo '<link>'.$billet[0]['bt_link'].'</link>'."\n"; 
		echo '<description><![CDATA['.$GLOBALS['description'].']]></description>'."\n";
		echo '<language>fr</language>'."\n"; 
		echo '<copyright>'.$GLOBALS['auteur'].'</copyright>'."\n";
		foreach ($liste as $comment) {
			$dec = decode_id($comment['bt_id']);
			echo '<item>'."\n";
				echo '<title>'.$comment['bt_author'].'</title>'."\n";
				echo '<guid isPermaLink="false">'.$comment['bt_link'].'</guid>'."\n";
				echo '<link>'.$comment['bt_link'].'</link>'."\n";
				echo '<pubDate>'.date('r', mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee'])).'</pubDate>'."\n";
				echo '<description><![CDATA['.($comment['bt_content']).']]></description>'."\n";
			echo '</item>'."\n";
		}
	} else {
		echo '<item>'."\n";
			echo '<title>'.$GLOBALS['lang']['note_no_comment'].'</title>'."\n";
			echo '<guid isPermaLink="false">'.$GLOBALS['racine'].'index.php</guid>'."\n";
			echo '<link>'.$GLOBALS['racine'].'index.php</link>'."\n";
			echo '<pubDate>'.date('r').'</pubDate>'."\n";
			echo '<description>'.$GLOBALS['lang']['no_comments'].'</description>'."\n";
		echo '</item>'."\n";
	}
}
/* sinon, fil rss sur les articles (par défaut) */
/* Ceci se fait toujours à partir d'un fichier que l'on place en cache. */
else {
	$int_code = 1;
	if (!empty($_GET['mode'])) {

		$int_code = 0;   // chmod-like CODE
		// 1 = articles
		if ( strpos($_GET['mode'], 'blog') !== FALSE ) {
			$int_code += 1;
		}
		// 2 = commentaires
		if ( strpos($_GET['mode'], 'comments') !== FALSE ) {
			$int_code += 2;
		}
		// 4 = links
		if (strpos($_GET['mode'], 'links') !== FALSE) {
			$int_code += 4;
		}
		// si rien de bon dans l'url, on prend le blog, à défaut
		if ($int_code == 0) {
			$int_code = 1;
		}
	}

	$invert = (isset($_GET['invertlinks'])) ? '_I' : '';

	// if no file, reload them (typically on first use or on cache-purge)
	$filename = $GLOBALS['dossier_cache'].'/'.'cache_rss_'.$int_code.$invert.'.dat';
	if (!file_exists($filename)) {
		require_all();
		$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);
		rafraichir_cache('article');
		rafraichir_cache('commentaire');
		rafraichir_cache('link');
	}
	if (readfile($filename) === FALSE) echo 'Error creating cache data';
}

$end = microtime(TRUE);
echo '<!-- generated in '.round(($end - $begin),6).' seconds -->'."\n";
echo '</channel>'."\n";
echo '</rss>';
?>
