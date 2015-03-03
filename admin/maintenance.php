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
$GLOBALS['liste_fichiers'] = open_serialzd_file($GLOBALS['fichier_liste_fichiers']);
$GLOBALS['liste_flux'] = open_serialzd_file($GLOBALS['fichier_liste_fluxrss']);

afficher_top($GLOBALS['lang']['titre_maintenance']);
echo '<div id="top">'."\n";
afficher_msg($GLOBALS['lang']['titre_maintenance']);
afficher_menu('preferences.php');
echo '</div>'."\n";

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

// création du dossier des backups
creer_dossier($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_backup'], 0);

/*
 * reconstruit la BDD des fichiers (qui n’est pas dans SQL, mais un fichier serializé à côte)
*/
function rebuilt_file_db() {
	$idir = rm_dots_dir(scandir($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images']));
	// scans also subdir of img/* (in one single array of paths)
	foreach ($idir as $i => $e) {
		$subelem = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'].'/'.$e;
		if (is_dir($subelem)) {
			unset($idir[$i]); // rm folder entry itself
			$subidir = rm_dots_dir(scandir($subelem));
			foreach ($subidir as $j => $im) {
				$idir[] = $e.'/'.$im;
			}
		}
	}
	foreach ($idir as $i => $e) {
		$idir[$i] = '/'.$e;
	}

	
	$fdir = rm_dots_dir(scandir($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers']));

	// supprime les miniatures de la liste...
	$idir = array_filter($idir, function($file){return (!((preg_match('#-thb\.jpg$#', $file)) or (strpos($file, 'index.html') == 4))); });

	$files_disk = array_merge($idir, $fdir);
	$files_db = $files_db_id = array();

	// supprime les fichiers dans la DB qui ne sont plus sur le disque
	foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
		if (!in_array($file['bt_path'].'/'.$file['bt_filename'], $files_disk)) { unset($GLOBALS['liste_fichiers'][$id]); }
		$files_db[] = $file['bt_path'].'/'.$file['bt_filename'];
		$files_db_id[] = $file['bt_id'];
	}

	// ajoute les images/* du disque qui ne sont pas encore dans la DB.
	foreach ($idir as $file) {
		if (!in_array($file, $files_db)) {
			$filepath = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'].'/'.$file;
			$time = filemtime($filepath);
			$id = date('YmdHis', $time);
			// vérifie que l’ID ne se trouve pas déjà dans le tableau. Sinon, modifie la date (en allant dans le passé)
			while (array_key_exists($id, $files_db_id)) { $time--; $id = date('YmdHis', $time); } $files_db_id[] = $id;

			$new_img = array(
				'bt_id' => $id,
				'bt_type' => 'image',
				'bt_fileext' => strtolower(pathinfo($filepath, PATHINFO_EXTENSION)),
				'bt_filesize' => filesize($filepath),
				'bt_filename' => $file,
				'bt_content' => '',
				'bt_wiki_content' => '',
				'bt_dossier' => 'default',
				'bt_checksum' => sha1_file($filepath),
				'bt_statut' => 0,
				'bt_path' => (preg_match('#^/[0-9a-f]{2}/#', $file)) ? (substr($file, 0, 3)) : '',
			);
			list($new_img['bt_dim_w'], $new_img['bt_dim_h']) = getimagesize($filepath);
			// crée une miniature de l’image
			create_thumbnail($filepath);
			// l’ajoute au tableau
			$GLOBALS['liste_fichiers'][] = $new_img;
		}
	}

	// fait pareil pour les files/*
	foreach ($fdir as $file) {
		if (!in_array($file, $files_db)) {
			$filepath = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'].'/'.$file;
			$time = filemtime($filepath);
			$id = date('YmdHis', $time);
			// vérifie que l’ID ne se trouve pas déjà dans le tableau. Sinon, modifie la date (en allant dans le passé)
			while (array_key_exists($id, $files_db_id)) { $time--; $id = date('YmdHis', $time); } $files_db_id[] = $id;
			$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
			$new_file = array(
				'bt_id' => $id,
				'bt_type' => detection_type_fichier($ext),
				'bt_fileext' => $ext,
				'bt_filesize' => filesize($filepath),
				'bt_filename' => $file,
				'bt_content' => '',
				'bt_wiki_content' => '',
				'bt_dossier' => 'default',
				'bt_checksum' => sha1_file($filepath),
				'bt_statut' => 0,
				'bt_path' => '',
			);
			// l’ajoute au tableau
			$GLOBALS['liste_fichiers'][] = $new_file;
		}
	}
	// tri le tableau fusionné selon les bt_id (selon une des clés d'un sous tableau).
	$GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
	// finalement enregistre la liste des fichiers.
	file_put_contents($GLOBALS['fichier_liste_fichiers'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers']))).' */');
}

/*
 * génère le fichier HTML au format de favoris utilisés par tous les navigateurs.
*/
function creer_fich_html($nb_links) {
	// nom du fichier de sortie
	$path = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_backup'].'/backup-links-'.date('Ymd-His').'.html';
	// récupère les liens
	$query = "SELECT * FROM links ORDER BY bt_id DESC ".((!empty($nb_links)) ? 'LIMIT 0, '.$nb_links : '');
	$list = liste_elements($query, array(), 'links');
	// génération du code HTML.
	$html = '<!DOCTYPE NETSCAPE-Bookmark-file-1><META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">'."\n";
	$html .= '<!--This is an automatically generated file. Do Not Edit! -->'."\n";
	$html .= '<TITLE>Blogotext links export '.date('Y-M-D').'</TITLE><H1>Blogotext links export</H1>'."\n";
	foreach ($list as $n => $link) {
		$dec = decode_id($link['bt_id']);
		$timestamp = mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee']); // HISMDY : wtf!
		$html .= '<DT><A HREF="'.$link['bt_link'].'" ADD_DATE="'.$timestamp.'" PRIVATE="'.abs(1-$link['bt_statut']).'" TAGS="'.$link['bt_tags'].'" AUTHOR="'.$link['bt_author'].'">'.$link['bt_title'].'</A>'."\n";
		$html .= '<DD>'.strip_tags($link['bt_wiki_content'])."\n";
	}
	return (file_put_contents($path, $html) === FALSE) ? FALSE : $path; // écriture du fichier
}


/*
 * liste une table (ex: les commentaires) et comparre avec un tableau de commentaires trouvées dans l’archive
 * Retourne deux tableau : un avec les éléments présents dans la base, et un avec les éléments absents de la base
 */
function diff_trouve_base($table, $tableau_trouve) {
	$tableau_base = $tableau_absents = array();
	try {
		$req = $GLOBALS['db_handle']->prepare('SELECT bt_id FROM '.$table);
		$req->execute();
		while ($ligne = $req->fetch()) {
			$tableau_base[] = $ligne['bt_id'];
		}
	} catch (Exception $e) {
		die('Erreur 20959 : diff_trouve_base avec les "'.$table.'" : '.$e->getMessage());
	}

	// remplit les deux tableaux, pour chaque élément trouvé dans l’archive, en fonction de ceux déjà dans la base
	foreach ($tableau_trouve as $key => $element) {
		if (!in_array($element['bt_id'], $tableau_base)) $tableau_absents[] = $element;
	}
	return $tableau_absents;
}

// Issert big arrays of data in DB.
function insert_table_links($tableau) {
	$table_diff = diff_trouve_base('links', $tableau);
	$return = count($table_diff);
	try {
		$GLOBALS['db_handle']->beginTransaction();
		foreach($table_diff as $f) {
			$query = 'INSERT INTO links (bt_type, bt_id, bt_link, bt_content, bt_wiki_content, bt_statut, bt_author, bt_title, bt_tags ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ? ) ';
			$req = $GLOBALS['db_handle']->prepare($query);
			$req->execute(array($f['bt_type'], $f['bt_id'], $f['bt_link'], $f['bt_content'], $f['bt_wiki_content'], $f['bt_statut'], $f['bt_author'], $f['bt_title'], $f['bt_tags']));
		}
		$GLOBALS['db_handle']->commit();
	} catch (Exception $e) {
		$req->rollBack();
		die('Erreur 1123 on import JSON insert-links : '.$e->getMessage());
	}
	return $return;
}

function insert_table_articles($tableau) {
	$table_diff = diff_trouve_base('articles', $tableau);
	$return = count($table_diff);
	try {
		$GLOBALS['db_handle']->beginTransaction();
		foreach($table_diff as $art) {
			$query = 'INSERT INTO articles ( bt_type, bt_id, bt_date, bt_title, bt_abstract, bt_notes, bt_link, bt_content, bt_wiki_content, bt_categories, bt_keywords, bt_nb_comments, bt_allow_comments, bt_statut ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )';
			$req = $GLOBALS['db_handle']->prepare($query);
			$req->execute(array( $art['bt_type'], $art['bt_id'], $art['bt_date'], $art['bt_title'], $art['bt_abstract'], $art['bt_notes'], $art['bt_link'], $art['bt_content'], $art['bt_wiki_content'], $art['bt_categories'], $art['bt_keywords'], $art['bt_nb_comments'], $art['bt_allow_comments'], $art['bt_statut'] ));
		}
		$GLOBALS['db_handle']->commit();
	} catch (Exception $e) {
		$req->rollBack();
		die('Erreur 6880 on import JSON insert-articles : '.$e->getMessage());
	}
	return $return;
}

function insert_table_commentaires($tableau) {
	$table_diff = diff_trouve_base('commentaires', $tableau);
	$return = count($table_diff);
	try {
		$GLOBALS['db_handle']->beginTransaction();
		foreach($table_diff as $com) {
			$query = 'INSERT INTO commentaires (bt_type, bt_id, bt_article_id, bt_content, bt_wiki_content, bt_author, bt_link, bt_webpage, bt_email, bt_subscribe, bt_statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
			$req = $GLOBALS['db_handle']->prepare($query);
			$req->execute(array($com['bt_type'], $com['bt_id'], $com['bt_article_id'], $com['bt_content'], $com['bt_wiki_content'], $com['bt_author'], $com['bt_link'], $com['bt_webpage'], $com['bt_email'], $com['bt_subscribe'], $com['bt_statut']));
		}
		$GLOBALS['db_handle']->commit();
	} catch (Exception $e) {
		$req->rollBack();
		die('Erreur 8942 on import JSON insert-commentaires : '.$e->getMessage());
	}
	return $return;
}

/* RECOMPTE LES COMMENTAIRES AUX ARTICLES */
function recompte_commentaires() {
	try {
		if ($GLOBALS['sgdb'] == 'sqlite') {
			$query = "UPDATE articles SET bt_nb_comments = COALESCE((SELECT count(a.bt_id) FROM articles a INNER JOIN commentaires c ON (c.bt_article_id = a.bt_id) WHERE articles.bt_id = a.bt_id AND c.bt_statut=1 GROUP BY a.bt_id), 0)";
		} elseif ($GLOBALS['sgdb'] == 'mysql') {
			$query = "UPDATE articles SET bt_nb_comments = COALESCE((SELECT count(articles.bt_id) FROM commentaires WHERE commentaires.bt_article_id = articles.bt_id), 0)";
		}
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute();
	} catch (Exception $e) {
		$req->rollBack();
		die('Erreur 8942 on recount-commentaires : '.$e->getMessage());
	}
}

/* IMPORTE UN FICHIER json QUI EST AU FORMAT DE BLOGOTEXT */
function importer_json($json) {
	$data = json_decode($json, true);
	$return = array();
	// importer les liens
	if (!empty($data['liens'])) {
		$return['links'] = insert_table_links($data['liens']);
	}
	// importer les articles
	if (!empty($data['articles'])) {
		$return['articles'] = insert_table_articles($data['articles']);
	}
	// importer les commentaires
	if (!empty($data['commentaires'])) {
		$return['commentaires'] = insert_table_commentaires($data['commentaires']);
	}
	// recompter les commentaires
	if (!empty($data['commentaires']) or !empty($data['articles'])) {
		recompte_commentaires();
	}
	return $return;
}


/* AJOUTE TOUS LES DOSSIERS DU TABLEAU $dossiers DANS UNE ARCHIVE ZIP */
function addFolder2zip($zip, $folder) {
	if ($handle = opendir($folder)) {
		while (FALSE !== ($entry = readdir($handle))) {
			if ($entry != "." and $entry != ".." and is_readable($folder.'/'.$entry)) {
				if (is_dir($folder.'/'.$entry)) addFolder2zip($zip, $folder.'/'.$entry);
				else $zip->addFile($folder.'/'.$entry, preg_replace('#^\.\./#', '', $folder.'/'.$entry));
		}	}
		closedir($handle);
	}
}

function creer_fichier_zip($dossiers) {
	$zipfile = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_backup'].'/'.'archive_site-'.date('Ymd').'-'.substr(md5(rand(10,99)),3,5).'.zip';
	$zip = new ZipArchive;
	if ($zip->open($zipfile, ZipArchive::CREATE) === TRUE) {
		foreach ($dossiers as $dossier) {
			addFolder2zip($zip, $dossier);
		}
		$zip->close();
		if (is_file($zipfile)) return $zipfile;
	}
	else return FALSE;
}

/* FABRIQUE LE FICHIER JSON (très simple en fait) */
function creer_fichier_json($data_array) {
	$path = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_backup'].'/backup-data-'.date('Ymd-His').'.json';
	return (file_put_contents($path, json_encode($data_array)) === FALSE) ? FALSE : $path;
}

/* Crée la liste des RSS et met tout ça dans un fichier OPML */
function creer_fichier_opml() {
	$path = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_backup'].'/backup-data-'.date('Ymd-His').'.opml';
	// sort feeds by folder
	$folders = array();
	foreach ($GLOBALS['liste_flux'] as $i => $feed) {
		$folders[$feed['folder']][] = $feed;
	}
	ksort($folders);

	$html  = '<?xml version="1.0" encoding="utf-8"?>'."\n";
	$html .= '<opml version="1.0">'."\n";
	$html .= "\t".'<head>'."\n";
	$html .= "\t\t".'<title>Newsfeeds '.$GLOBALS['nom_application'].' '.$GLOBALS['version'].' on '.date('Y/m/d').'</title>'."\n";
	$html .= "\t".'</head>'."\n";
	$html .= "\t".'<body>'."\n";

	function esc($a) {
		return htmlspecialchars($a, ENT_QUOTES, 'UTF-8');
	}

	foreach ($folders as $i => $folder) {
		$outline = '';
		foreach ($folder as $j => $feed) {
			$outline .= ($i ? "\t" : '')."\t\t".'<outline text="'.esc($feed['title']).'" title="'.esc($feed['title']).'" type="rss" xmlUrl="'.esc($feed['link']).'" />'."\n";
		}
		if ($i != '') {
			$html .= "\t\t".'<outline text="'.esc($i).'" title="'.esc($i).'" >'."\n";
			$html .= $outline;
			$html .= "\t\t".'</outline>'."\n";	
		} else {
			$html .= $outline;
		}
	}

	$html .= "\t".'</body>'."\n".'</opml>';

	return (file_put_contents($path, $html) === FALSE) ? FALSE : $path;
}

/* CONVERTI UN FICHIER AU FORMAT xml DE WORDPRESS en un tableau (sans enregistrer le fichier BT) */
function importer_wordpress($xml) {

	/* transforms some HTML elements to Blogotext's BBCode */
	function reverse_wiki($texte) {
		$tofind = array(
			array('#<blockquote>(.*)</blockquote>#s', '[quote]$1[/quote]'),
			array('#<code>(.*)</code>#s', '[code]$1[/code]'),
			array('#<a href="(.*)">(.*)</a>#', '[$2|$1]'),
			array('#<strong>(.*)</strong>#', '[b]$1[/b]'),
			array('#<em>(.*)</em>#', '[i]$1[/i]'),
			array('#<u>(.*)</u>#', '[u]$1[/u]')
		);
		for ($i=0, $length = sizeof($tofind); $i < $length; $i++) {
			$texte = preg_replace($tofind["$i"][0], $tofind["$i"][1], $texte);
		}
		return $texte;
	}

	/* Transforms Blogotext's BBCode tags to HTML elements. */
	function wiki($texte) {
		$texte = " ".$texte;
		$tofind = array(
			array('#\[quote\](.+?)\[/quote\]#s', '<blockquote>$1</blockquote>'),
			array('#\[code\](.+?)\[/code\]#s', '<code>$1</code>'),
			array('`\[([^[]+)\|([^[]+)\]`', '<a href="$2">$1</a>'),
			array('`\[b\](.*?)\[/b\]`s', '<span style="font-weight: bold;">$1</span>'),
			array('`\[i\](.*?)\[/i\]`s', '<span style="font-style: italic;">$1</span>'),
			array('`\[u\](.*?)\[/u\]`s', '<span style="text-decoration: underline;">$1</span>')
		);
		for ($i=0, $length = sizeof($tofind); $i < $length; $i++) {
			$texte = preg_replace($tofind["$i"][0], $tofind["$i"][1], $texte);
		}
		return $texte;
	}

	$xml = simplexml_load_string($xml);
	$xml = $xml->channel;

	$data = array('liens' => NULL, 'articles' => NULL, 'commentaires' => NULL);

	foreach ($xml->item as $value) {
		$new_article = array();
		$new_article['bt_type'] = 'article';
		$new_article['bt_date'] = date('YmdHis', strtotime($value->pubDate));
		$new_article['bt_id'] = $new_article['bt_date'];
		$new_article['bt_title'] = (string) $value[0]->title;
		$new_article['bt_notes'] = '';
		$new_article['bt_link'] = (string) $value[0]->link;
		$new_article['bt_wiki_content'] = reverse_wiki($value->children("content", true)->encoded);
		$new_article['bt_content'] = wiki($new_article['bt_wiki_content']);
		$new_article['bt_abstract'] = '';
		// get categories
		$new_article['bt_categories'] = '';
			foreach($value->category as $tag) $new_article['bt_categories'] .= (string) $tag . ',';
			$new_article['bt_categories'] = trim($new_article['bt_categories'], ',');
		$new_article['bt_keywords'] = '';
		$new_article['bt_nb_comments'] = 0;
		$new_article['bt_allow_comments'] = ( ($value->children("wp", true)->comment_status) == 'open' ) ? 1 : 0;
		$new_article['bt_statut'] = ( ($value->children("wp", true)->status) == 'publish' ) ? 1 : 0;
		// parse comments
		foreach ($value->children('wp', true)->comment as $comment) {
			$new_comment = array();
			$new_comment['bt_author'] = (string) $comment[0]->comment_author;
			$new_comment['bt_link'] = '';
			$new_comment['bt_webpage'] = (string) $comment[0]->comment_author_url;
			$new_comment['bt_email'] = (string) $comment[0]->comment_author_email;
			$new_comment['bt_subscribe'] = '0';
			$new_comment['bt_type'] = 'comment';
			$new_comment['bt_id'] = date('YmdHis', strtotime($comment->comment_date));
			$new_comment['bt_article_id'] = $new_article['bt_id'];
			$new_comment['bt_wiki_content'] = reverse_wiki($comment->comment_content);
			$new_comment['bt_content'] = '<p>'.wiki($new_comment['bt_wiki_content']).'</p>';
			$new_comment['bt_statut'] = ( ($comment->comment_approved) == '1' ) ?: '0';
			$data['commentaires'][] = $new_comment;
		}
		$data['articles'][] = $new_article;
	}

	$return = array();
	// importer les articles
	if (!empty($data['articles'])) {
		$return['articles'] = insert_table_articles($data['articles']);
	}
	// importer les commentaires
	if (!empty($data['commentaires'])) {
		$return['commentaires'] = insert_table_commentaires($data['commentaires']);
	}
	// recompter les commentaires
	if (!empty($data['commentaires']) or !empty($data['articles'])) {
		recompte_commentaires();
	}

	return $return;
}

// Parse et importe un fichier de liste de flux OPML
function importer_opml($opml_content) {
	$GLOBALS['array_new'] = array();

	function parseOpmlRecursive($xmlObj) {
		// si c’est un sous dossier avec d’autres flux à l’intérieur : note le nom du dossier
		$folder = $xmlObj->attributes()->text;
		foreach($xmlObj->children() as $child) {
			if (!empty($child['xmlUrl'])) {
				$url = (string)$child['xmlUrl'];
				$title = ( !empty($child['text']) ) ? (string)$child['text'] : (string)$child['title'];
				$GLOBALS['array_new'][$url] = array(
					'link' => $url,
					'title' => ucfirst($title),
					'favicon' => 'style/rss-feed-icon.png',
					'checksum' => '0',
					'time' => '0',
					'folder' => (string)$folder,
					'iserror' => 0,
				);
			}
	 		parseOpmlRecursive($child);
		}
	}
	$opmlFile = new SimpleXMLElement($opml_content);
	parseOpmlRecursive($opmlFile->body);

	$old_len = count($GLOBALS['liste_flux']);
	$GLOBALS['liste_flux'] = array_reverse(tri_selon_sous_cle($GLOBALS['liste_flux'], 'title'));
	$GLOBALS['liste_flux'] = array_merge($GLOBALS['array_new'], $GLOBALS['liste_flux']);
	file_put_contents($GLOBALS['fichier_liste_fluxrss'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_flux']))).' */');


	return (count($GLOBALS['liste_flux']) - $old_len);
}





// based on Shaarli by Sebsauvage
function parse_html($content) {
	$out_array = array();
	// Netscape bookmark file (Firefox).
	if (strcmp(substr($content, 0, strlen('<!DOCTYPE NETSCAPE-Bookmark-file-1>')), '<!DOCTYPE NETSCAPE-Bookmark-file-1>') === 0) {
		// This format is supported by all browsers (except IE, of course), also delicious, diigo and others.
		$ids_array = array();
		$tab1_DT = explode('<DT>',$content);
		foreach ($tab1_DT as $dt) {
			$link = array('bt_id' => '', 'bt_title' => '', 'bt_author' => $GLOBALS['auteur'], 'bt_link' => '', 'bt_content' => '', 'bt_wiki_content' => '', 'bt_tags' => '', 'bt_statut' => 1, 'bt_type' => 'link');
			$d = explode('<DD>', $dt);
			if (strcmp(substr($d[0], 0, strlen('<A ')), '<A ') === 0) {
				$link['bt_content'] = (isset($d[1]) ? html_entity_decode(trim($d[1]), ENT_QUOTES,'utf-8') : '');  // Get description (optional)
				$link['bt_wiki_content'] = $link['bt_content'];
				preg_match('!<A .*?>(.*?)</A>!i',$d[0],$matches); $link['bt_title'] = (isset($matches[1]) ? trim($matches[1]) : '');  // Get title
				$link['bt_title'] = html_entity_decode($link['bt_title'], ENT_QUOTES, 'utf-8');
				preg_match_all('# ([A-Z_]+)=\"(.*?)"#i', $dt, $matches, PREG_SET_ORDER); // Get all other attributes
				$raw_add_date = 0;
				foreach($matches as $m) {
					$attr = $m[1]; $value = $m[2];
					if ($attr == 'HREF') { $link['bt_link'] = html_entity_decode($value, ENT_QUOTES, 'utf-8'); }
					elseif ($attr == 'ADD_DATE') { $raw_add_date = intval($value); }
					elseif ($attr == 'AUTHOR') { $link['bt_author'] = $value; }
					elseif ($attr == 'PRIVATE') { $link['bt_statut'] = ($value == '1') ? '0' : '1'; } // value=1 =>> statut=0 (it’s reversed)
					elseif ($attr == 'TAGS') { $link['bt_tags'] = html_entity_decode($value, ENT_QUOTES, 'utf-8'); }
				}
				if ($link['bt_link'] != '') {
					$raw_add_date = (empty($raw_add_date)) ? time() : $raw_add_date; // In case of shitty bookmark file with no ADD_DATE
					while (in_array(date('YmdHis', $raw_add_date), $ids_array)) $raw_add_date--; // avoids duplicate IDs
					$ids_array[] = $link['bt_id'] = date('YmdHis', $raw_add_date); // converts date to YmdHis format
					$out_array[] = $link;
				}
			}
		}
	}
	return $out_array;
}

/*
 * Affiches les formulaires qui demandent quoi faire. (!isset($do))
 * Font le traitement dans les autres cas.
*/

// no $do nor $file : ask what to do
if (!isset($_GET['do']) and !isset($_FILES['file'])) {
	$token = new_token();
	$nbs = array('10'=>'10', '20'=>'20', '50'=>'50', '100'=>'100', '200'=>'200', '500'=>'500', '-1' => $GLOBALS['lang']['pref_all']);

	echo '<div id="list-switch-buttons" class="list-buttons centrer">'."\n";
	echo '<button class="" onclick="switch_form(\'form_export\', this)">'.$GLOBALS['lang']['maintenance_export'].'</button>';
	echo '<button class="" onclick="switch_form(\'form_import\', this)">'.$GLOBALS['lang']['maintenance_import'].'</button>';
	echo '<button class="" onclick="switch_form(\'form_optimi\', this)">'.$GLOBALS['lang']['maintenance_optim'].'</button>';
	echo '</div>'."\n";

	// Form export
	echo '<form action="maintenance.php" onsubmit="hide_forms(\'exp-format\')" method="get" class="bordered-formbloc" id="form_export">'."\n";
	// choose export what ?
		echo '<fieldset class="">'."\n";
		echo legend($GLOBALS['lang']['maintenance_export'], 'legend-backup');
		echo "\t".'<p><label for="json">'.$GLOBALS['lang']['bak_export_json'].'</label>'.
			'<input type="radio" name="exp-format" value="json" id="json" onchange="switch_export_type(\'e_json\')" /></p>'."\n";
		echo "\t".'<p><label for="html">'.$GLOBALS['lang']['bak_export_netscape'].'</label>'.
			'<input type="radio" name="exp-format" value="html" id="html" onchange="switch_export_type(\'e_html\')" /></p>'."\n";
		echo "\t".'<p><label for="zip">'.$GLOBALS['lang']['bak_export_zip'].'</label>'.
			'<input type="radio" name="exp-format" value="zip"  id="zip"  onchange="switch_export_type(\'e_zip\')"  /></p>'."\n";
		echo "\t".'<p><label for="opml">'.$GLOBALS['lang']['bak_export_opml'].'</label>'.
			'<input type="radio" name="exp-format" value="opml"  id="opml"  onchange="switch_export_type(\'e_opml\')"  /></p>'."\n";
		echo '</fieldset>'."\n";

		// export in JSON.
		echo '<fieldset class="" id="e_json">';
		echo legend($GLOBALS['lang']['maintenance_incl_quoi'], 'legend-backup');
		echo "\t".'<p>'.select_yes_no('incl-artic', 0, $GLOBALS['lang']['bak_articles_do']).form_select_no_label('nb-artic', $nbs, 50).'</p>'."\n";
		echo "\t".'<p>'.select_yes_no('incl-comms', 0, $GLOBALS['lang']['bak_comments_do']).'</p>'."\n";
		echo "\t".'<p>'.select_yes_no('incl-links', 0, $GLOBALS['lang']['bak_links_do']).form_select_no_label('nb-links', $nbs, 50).'</p>'."\n";
		echo '</fieldset>'."\n";

		// export links in html
		echo '<fieldset class="" id="e_html">'."\n";
		echo legend($GLOBALS['lang']['bak_combien_linx'], 'legend-backup');
		echo "\t".'<p>'.form_select('nb-links', $nbs, 50, $GLOBALS['lang']['bak_combien_linx']).'</p>'."\n";
		echo '</fieldset>'."\n";

		// export data in zip
		echo '<fieldset class="" id="e_zip">';
		echo legend($GLOBALS['lang']['maintenance_incl_quoi'], 'legend-backup');
		if ($GLOBALS['sgdb'] == 'sqlite')
		echo "\t".'<p>'.select_yes_no('incl-sqlit', 0, $GLOBALS['lang']['bak_incl_sqlit']).'</p>'."\n";
		echo "\t".'<p>'.select_yes_no('incl-files', 0, $GLOBALS['lang']['bak_incl_files']).'</p>'."\n";
		echo "\t".'<p>'.select_yes_no('incl-confi', 0, $GLOBALS['lang']['bak_incl_confi']).'</p>'."\n";
		echo "\t".'<p>'.select_yes_no('incl-theme', 0, $GLOBALS['lang']['bak_incl_theme']).'</p>'."\n";
		echo '</fieldset>'."\n";

		echo '<p><button class="submit blue-square" type="submit" name="do" value="export">'.$GLOBALS['lang']['valider'].'</button></p>'."\n";
		echo hidden_input('token', $token);
	echo '</form>'."\n";

	// Form import
	$importformats = array(
			'jsonbak' => $GLOBALS['lang']['bak_import_btjson'],
			'xmlwp' => $GLOBALS['lang']['bak_import_wordpress'],
			'htmllinks' => $GLOBALS['lang']['bak_import_netscape'],
			'rssopml' => $GLOBALS['lang']['bak_import_rssopml'] );

	echo '<form action="maintenance.php" method="post" enctype="multipart/form-data" class="bordered-formbloc" id="form_import">'."\n";
		echo '<fieldset class="pref valid-center">';
		echo legend($GLOBALS['lang']['maintenance_import'], 'legend-backup');
		echo "\t".'<p>'.form_select_no_label('imp-format', $importformats, 'jsonbak');
		echo '<input type="file" name="file" id="file" class="text" /></p>'."\n";
		echo '<p><input class="submit blue-square" type="submit" name="valider" value="'.$GLOBALS['lang']['valider'].'" /></p>'."\n";
		echo '</fieldset>'."\n";
		echo hidden_input('token', $token);
	echo '</form>'."\n";

	// Form optimi
	echo '<form action="maintenance.php" metЬ or ь hod="get" class="bordered-formbloc" id="form_optimi">'."\n";
		echo '<fieldset class="pref valid-center">';
		echo legend($GLOBALS['lang']['maintenance_optim'], 'legend-sweep');

		echo "\t".'<p>'.select_yes_no('opti-file', 0, $GLOBALS['lang']['bak_opti_miniature']).'</p>'."\n";
		if ($GLOBALS['sgdb'] == 'sqlite') {
			echo "\t".'<p>'.select_yes_no('opti-vacu', 0, $GLOBALS['lang']['bak_opti_vacuum']).'</p>'."\n";
		} else {
			echo hidden_input('opti-vacu', 0);
		}
		echo "\t".'<p>'.select_yes_no('opti-comm', 0, $GLOBALS['lang']['bak_opti_recountcomm']).'</p>'."\n";

		echo "\t".'<p>'.select_yes_no('opti-rss', 0, $GLOBALS['lang']['bak_opti_supprreadrss']).'</p>'."\n";

	echo '<p><button class="submit blue-square" type="submit" name="do" value="optim">'.$GLOBALS['lang']['valider'].'</button></p>'."\n";
		echo '</fieldset>'."\n";
		echo hidden_input('token', $token);
	echo '</form>'."\n";

// either $do or $file
// $do
} else {
	// vérifie Token
	if ($erreurs_form = valider_form_maintenance()) {
		echo '<div class="bordered-formbloc">'."\n";
		echo '<fieldset class="pref valid-center">'."\n";
		echo legend($GLOBALS['lang']['bak_restor_done'], 'legend-backup');
		echo erreurs($erreurs_form);
		echo '<p><button class="submit blue-square" type="button" onclick="window.location = \'maintenance.php\' ">'.$GLOBALS['lang']['valider'].'</button></p>'."\n";
		echo '</fieldset>'."\n";
		echo '</div>'."\n";

	} else {
		// token : ok, go on !
		if (isset($_GET['do'])) {
			if ($_GET['do'] == 'export') {
				// Export in JSON file
				if (@$_GET['exp-format'] == 'json') {
					$data_array = array('articles' => array(), 'liens' => array(), 'commentaires' => array());
					// list links (nth last)
					if ($_GET['incl-links'] == 1) {
						$nb = htmlspecialchars($_GET['nb-links']);
						$limit = (is_numeric($nb) and $nb != -1 ) ? 'LIMIT 0, ?' : '';
						$array = (empty($limit)) ? array() : array($nb);
						$data_array['liens'] = liste_elements('SELECT * FROM links ORDER BY bt_id DESC '.$limit, $array, 'links');
					}
					// get articles (nth last)
					if ($_GET['incl-artic'] == 1) {
						$nb = htmlspecialchars($_GET['nb-artic']);
						$limit = (is_numeric($nb) and $nb != -1 ) ? 'LIMIT 0, ?' : '';
						$array = (empty($limit)) ? array() : array($nb);
						$data_array['articles'] = liste_elements('SELECT * FROM articles ORDER BY bt_id DESC '.$limit, $array, 'articles');
						// get list of comments (comments that belong to selected articles only)
						if ($_GET['incl-comms'] == 1) {
							foreach ($data_array['articles'] as $article) {
								$comments = liste_elements('SELECT * FROM commentaires WHERE bt_article_id = ? ', array($article['bt_id']), 'commentaires');
								if (!empty($comments)) {
									$data_array['commentaires'] = array_merge($data_array['commentaires'], $comments);
								}
							}
						}
					}
					$file_archive = creer_fichier_json($data_array);

				// Export links in HTML format
				} elseif (@$_GET['exp-format'] == 'html') {
					$nb = htmlspecialchars($_GET['nb-links']);
					$limit = (is_numeric($nb) and $nb != -1 ) ? $nb : '';
					$file_archive = creer_fich_html($limit);

				// Export a ZIP archive
				} elseif (@$_GET['exp-format'] == 'zip') {
					$dossiers = array();
					if (@$_GET['incl-sqlit'] == 1) {
						$dossiers[] = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_db'];
					}
					if ($_GET['incl-files'] == 1) {
						$dossiers[] = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'];
						$dossiers[] = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'];
					}
					if ($_GET['incl-confi'] == 1) {
						$dossiers[] = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_config'];
					}
					if ($_GET['incl-theme'] == 1) {
						$dossiers[] = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_themes'];
					}
					$file_archive = creer_fichier_zip($dossiers);

				// Export a OPML rss lsit
				} elseif (@$_GET['exp-format'] == 'opml') {
					$file_archive = creer_fichier_opml();
				} else {
					echo 'nothing to do';
				}

				// affiche le formulaire de téléchargement et de validation.
				if (!empty($file_archive)) {
					echo '<form action="maintenance.php" method="get" class="bordered-formbloc">'."\n";
					echo '<fieldset class="pref valid-center">';
					echo legend($GLOBALS['lang']['bak_succes_save'], 'legend-backup');
					echo '<p><a href="'.$file_archive.'" download>'.$GLOBALS['lang']['bak_dl_fichier'].'</a></p>'."\n";
					echo '<p><button class="submit blue-square" type="submit">'.$GLOBALS['lang']['valider'].'</button></p>'."\n";
					echo '</fieldset>'."\n";
					echo '</form>'."\n";
				}

			} elseif ($_GET['do'] == 'optim') {
					// recount files DB
					if ($_GET['opti-file'] == 1) {
						rebuilt_file_db();
					}
					// vacuum SQLite DB
					if ($_GET['opti-vacu'] == 1) {
						try {
							$req = $GLOBALS['db_handle']->prepare('VACUUM');
							$req->execute();
						} catch (Exception $e) {
							die('Erreur 1429 vacuum : '.$e->getMessage());
						}
					}
					// recount comms/articles
					if ($_GET['opti-comm'] == 1) {
						recompte_commentaires();
					}
					// delete old RSS entries
					if ($_GET['opti-rss'] == 1) { 
						try {
							$req = $GLOBALS['db_handle']->prepare('DELETE FROM rss WHERE bt_statut=0');
							$req->execute(array());
						} catch (Exception $e) {
							die('Erreur : 7873 : rss delete old entries : '.$e->getMessage());
						}
					}
					echo '<form action="maintenance.php" method="get" class="bordered-formbloc">'."\n";
					echo '<fieldset class="pref valid-center">';
					echo legend($GLOBALS['lang']['bak_optim_done'], 'legend-backup');
					echo '<p><button class="submit blue-square" type="submit">'.$GLOBALS['lang']['valider'].'</button></p>'."\n";
					echo '</fieldset>'."\n";
					echo '</form>'."\n";

			} else {
				echo 'nothing to do.';
			}

		// $file
		} elseif (isset($_POST['valider']) and !empty($_FILES['file']['tmp_name']) ) {
				$message = array();
				switch($_POST['imp-format']) {
					case 'jsonbak':
						$json = file_get_contents($_FILES['file']['tmp_name']);
						$message = importer_json($json);
					break;
					case 'htmllinks':
						$html = file_get_contents($_FILES['file']['tmp_name']);
						$message['links'] = insert_table_links(parse_html($html));
					break;
					case 'xmlwp':
						$xml = file_get_contents($_FILES['file']['tmp_name']);
						$message = importer_wordpress($xml);
					break;
					case 'rssopml':
						$xml = file_get_contents($_FILES['file']['tmp_name']);
						$message['feeds'] = importer_opml($xml);
					break;
					default: die('nothing'); break;
				}
				if (!empty($message)) {
					echo '<form action="maintenance.php" method="get" class="bordered-formbloc">'."\n";
					echo '<fieldset class="pref valid-center">';
					echo legend($GLOBALS['lang']['bak_restor_done'], 'legend-backup');
					echo '<ul>';
					foreach ($message as $type => $nb) echo '<li>'.$GLOBALS['lang']['label_'.$type].' : '.$nb.'</li>'."\n";
					echo '</ul>';
					echo '<p><button class="submit blue-square" type="submit">'.$GLOBALS['lang']['valider'].'</button></p>'."\n";
					echo '</fieldset>'."\n";
					echo '</form>'."\n";
				}

		} else {
			echo 'nothing to do.';
		}
	}
}

footer('', $begin);
