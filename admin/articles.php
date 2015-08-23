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

$begin = microtime(TRUE);
$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();

$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);

$tableau = array();
if (!empty($_GET['q'])) {
	$arr = parse_search($_GET['q']);
	$sql_where = implode(array_fill(0, count($arr), '( bt_content || bt_title ) LIKE ? '), 'AND '); // AND operator between words
	$query = "SELECT * FROM articles WHERE ".$sql_where."ORDER BY bt_date DESC";
	$tableau = liste_elements($query, $arr, 'articles');
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


function afficher_liste_articles($tableau) {
	if (!empty($tableau)) {
		mb_internal_encoding('UTF-8');
		$i = 0;
		$out = '<ul id="billets">'."\n";
		foreach ($tableau as $article) {
			// ICONE SELON STATUT
			$out .= "\t".'<li>'."\n";
			// TITRE
			$out .= "\t\t".'<span class="'.( ($article['bt_statut'] == '1') ? 'on' : 'off').'">'.'<a href="ecrire.php?post_id='.$article['bt_id'].'" title="'.htmlspecialchars(trim(mb_substr(strip_tags($article['bt_abstract']), 0, 249)), ENT_QUOTES).'">'.$article['bt_title'].'</a>'.'</span>'."\n";
			// DATE
			$out .= "\t\t".'<span><a href="'.basename($_SERVER['PHP_SELF']).'?filtre='.substr($article['bt_date'],0,8).'">'.date_formate($article['bt_date']).'</a> @ '.heure_formate($article['bt_date']).'</span>'."\n";
			// NOMBRE COMMENTS
			$texte = $article['bt_nb_comments'];
			$out .= "\t\t".'<span><a href="commentaires.php?post_id='.$article['bt_id'].'">'.$texte.'</a></span>'."\n";
			// STATUT
			if ( $article['bt_statut'] == '1') {
				$out .= "\t\t".'<span><a href="'.$article['bt_link'].'">'.$GLOBALS['lang']['lien_article'].'</a></span>'."\n";
			} else {
				$out .= "\t\t".'<span><a href="'.$article['bt_link'].'">'.$GLOBALS['lang']['preview'].'</a></span>'."\n";
			}
			$out .= "\t".'</li>'."\n";
			$i++;
		}

		$out .= '</ul>'."\n\n";
		echo $out;
	} else {
		echo info($GLOBALS['lang']['note_no_article']);
	}
}


afficher_html_head($GLOBALS['lang']['mesarticles']);
echo '<div id="top">'."\n";
afficher_msg();
echo moteur_recherche($GLOBALS['lang']['search_in_articles']);
afficher_topnav(basename($_SERVER['PHP_SELF']), $GLOBALS['lang']['mesarticles']);
echo '</div>'."\n";

echo '<div id="axe">'."\n";

// SUBNAV
echo '<div id="subnav">'."\n";
		if (isset($_GET['filtre'])) {
			afficher_form_filtre('articles', htmlspecialchars($_GET['filtre']));
		} else {
			afficher_form_filtre('articles', '');
		}
		echo '<div class="nombre-elem">'."\n";
		echo ucfirst(nombre_objets(count($tableau), 'article')).' '.$GLOBALS['lang']['sur'].' '.liste_elements_count("SELECT count(*) AS nbr FROM articles", array());
		echo '</div>'."\n";
	echo '</div>'."\n";

echo '<div id="page">'."\n";

afficher_liste_articles($tableau);

echo "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
footer('', $begin);
?>
