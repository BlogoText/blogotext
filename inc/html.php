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


function afficher_html_head($titre) {
	$html = '<!DOCTYPE html>'."\n";
	$html .= '<html>'."\n";
	$html .= '<head>'."\n";
	$html .= "\t".'<meta charset="UTF-8" />'."\n";
	$html .= "\t".'<link type="text/css" rel="stylesheet" href="style/style.css.php" />'."\n";
	$html .= "\t".'<meta name="viewport" content="initial-scale=1.0, user-scalable=yes" />'."\n";
	$html .= "\t".'<title>'.$titre.' | '.BLOGOTEXT_NAME.'</title>'."\n";
	$html .= '</head>'."\n";
	$html .= '<body id="body">'."\n\n";
	echo $html;
}

function footer($begin_time='') {
	$msg = '';
	if ($begin_time != '') {
		$dt = round((microtime(TRUE) - $begin_time),6);
		$msg = ' - '.$GLOBALS['lang']['rendered'].' '.$dt.' s '.$GLOBALS['lang']['using'].' '.DBMS;
	}

	$html = '</div>'."\n";
	$html .= '</div>'."\n";
	$html .= '<p id="footer"><a href="'.BLOGOTEXT_SITE.'">'.BLOGOTEXT_NAME.' '.BLOGOTEXT_VERSION.'</a>'.$msg.'</p>'."\n";
	$html .= '</body>'."\n";
	$html .= '</html>'."\n";
	echo $html;
}

/// menu haut panneau admin /////////
function afficher_topnav($titre) {
	$tab = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME);
	if (strlen($titre) == 0) $titre = BLOGOTEXT_NAME;
	$html = '';
	$html .= '<div id="nav">'."\n";
	$html .=  "\t".'<ul>'."\n";
	$html .=  "\t\t".'<li><a href="index.php" id="lien-index"'.(($tab == 'index.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['label_resume'].'</a></li>'."\n";
	$html .=  "\t\t".'<li><a href="articles.php" id="lien-liste"'.(($tab == 'articles.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['mesarticles'].'</a></li>'."\n";
	$html .=  "\t\t".'<li><a href="ecrire.php" id="lien-nouveau"'.(($tab == 'ecrire.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['nouveau'].'</a></li>'."\n";
	$html .=  "\t\t".'<li><a href="commentaires.php" id="lien-lscom"'.(($tab == 'commentaires.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['titre_commentaires'].'</a></li>'."\n";
	$html .=  "\t\t".'<li><a href="fichiers.php" id="lien-fichiers"'.(($tab == 'fichiers.php') ? ' class="current"' : '').'>'.ucfirst($GLOBALS['lang']['label_fichiers']).'</a></li>'."\n";
	if ($GLOBALS['onglet_liens'])
	$html .=  "\t\t".'<li><a href="links.php" id="lien-links"'.(($tab == 'links.php') ? ' class="current"' : '').'>'.ucfirst($GLOBALS['lang']['label_links']).'</a></li>'."\n";
	if ($GLOBALS['onglet_rss'])
	$html .=  "\t\t".'<li><a href="feed.php" id="lien-rss"'.(($tab == 'feed.php') ? ' class="current"' : '').'>'.ucfirst($GLOBALS['lang']['label_feeds']).'</a></li>'."\n";
	$html .=  "\t".'</ul>'."\n";
	$html .=  '</div>'."\n";

	$html .=  '<h1>'.$titre.'</h1>'."\n";

	$html .=  '<div id="nav-acc">'."\n";
	$html .=  "\t".'<ul>'."\n";
	$html .=  "\t\t".'<li><a href="preferences.php" id="lien-preferences">'.$GLOBALS['lang']['preferences'].'</a></li>'."\n";
	$html .=  "\t\t".'<li><a href="'.$GLOBALS['racine'].'" id="lien-site">'.$GLOBALS['lang']['lien_blog'].'</a></li>'."\n";
	$html .=  "\t\t".'<li><a href="logout.php" id="lien-deconnexion">'.$GLOBALS['lang']['deconnexion'].'</a></li>'."\n";
	$html .=  "\t".'</ul>'."\n";
	$html .=  '</div>'."\n";
	echo $html;
}

function confirmation($message) {
	echo '<div class="confirmation">'.$message.'</div>'."\n";
}

function no_confirmation($message) {
	echo '<div class="no_confirmation">'.$message.'</div>'."\n";
}

function legend($legend, $class='') {
	return '<legend class="'.$class.'">'.$legend.'</legend>'."\n";
}

function label($for, $txt) {
	return '<label for="'.$for.'">'.$txt.'</label>'."\n";
}

function info($message) {
	return '<p class="info">'.$message.'</p>'."\n";
}

function erreurs($erreurs) {
	$html = '';
	if ($erreurs) {
		$html .= '<div id="erreurs">'.'<strong>'.$GLOBALS['lang']['erreurs'].'</strong> :' ;
		$html .= '<ul><li>';
		$html .= implode('</li><li>', $erreurs);
		$html .= '</li></ul></div>'."\n";
	}
	return $html;
}

function erreur($message) {
	  echo '<p class="erreurs">'.$message.'</p>'."\n";
}

function question($message) {
	  echo '<p id="question">'.$message.'</p>';
}

function afficher_msg() {
	// message vert
	if (isset($_GET['msg'])) {
		if (array_key_exists(htmlspecialchars($_GET['msg']), $GLOBALS['lang'])) {
			$suffix = (isset($_GET['nbnew'])) ? htmlspecialchars($_GET['nbnew']).' '.$GLOBALS['lang']['rss_nouveau_flux'] : ''; // nb new RSS
			confirmation($GLOBALS['lang'][$_GET['msg']].$suffix);
		}
	}
	// message rouge
	if (isset($_GET['errmsg'])) {
		if (array_key_exists($_GET['errmsg'], $GLOBALS['lang'])) {
			no_confirmation($GLOBALS['lang'][$_GET['errmsg']]);
		}
	}
}

function apercu($article) {
	if (isset($article)) {
		$apercu = '<h2>'.$article['bt_title'].'</h2>'."\n";
		$apercu .= '<div><strong>'.$article['bt_abstract'].'</strong></div>'."\n";
		$apercu .= '<div>'.rel2abs_admin($article['bt_content']).'</div>'."\n";
		echo '<div id="apercu">'."\n".$apercu.'</div>'."\n\n";
	}
}

function moteur_recherche() {
	$requete='';
	if (isset($_GET['q'])) {
		$requete = htmlspecialchars(stripslashes($_GET['q']));
	}
	$return = '<form action="?" method="get" id="search">'."\n";
	$return .= '<input id="q" name="q" type="search" size="20" value="'.$requete.'" placeholder="'.$GLOBALS['lang']['placeholder_search'].'" accesskey="f" />'."\n";
//	$return .= '<label for="q">'.'</label>'."\n";
	$return .= '<button id="input-rechercher" type="submit">'.$GLOBALS['lang']['rechercher'].'</button>'."\n";
	if (isset($_GET['mode'])) {
		$return .= '<input id="mode" name="mode" type="hidden" value="'.htmlspecialchars(stripslashes($_GET['mode'])).'"/>'."\n";
	}
	$return .= '</form>'."\n\n";
	return $return;
}

function encart_commentaires() {
	mb_internal_encoding('UTF-8');
	$query = "SELECT a.bt_title, c.bt_author, c.bt_id, c.bt_article_id, c.bt_content FROM commentaires c LEFT JOIN articles a ON a.bt_id=c.bt_article_id WHERE c.bt_statut=1 AND a.bt_statut=1 ORDER BY c.bt_id DESC LIMIT 5";
	$tableau = liste_elements($query, array(), 'commentaires');
	if (isset($tableau)) {
		$listeLastComments = '<ul class="encart_lastcom">'."\n";
		foreach ($tableau as $i => $comment) {
			$comment['contenu_abbr'] = strip_tags($comment['bt_content']);
			// limits length of comment abbreviation and name 
			if (strlen($comment['contenu_abbr']) >= 60) {
				$comment['contenu_abbr'] = mb_substr($comment['contenu_abbr'], 0, 59).'…';
			}
			if (strlen($comment['bt_author']) >= 30) {
				$comment['bt_author'] = mb_substr($comment['bt_author'], 0, 29).'…';
			}
//			$listeLastComments .= '<li title="'.date_formate($comment['bt_id']).'"><b>'.$comment['bt_author'].'</b> '.$GLOBALS['lang']['sur'].' <b>'.$comment['article_title'].'</b><br/><a href="'.$comment['bt_link'].'">'.$comment['contenu_abbr'].'</a>'.'</li>'."\n";
			$listeLastComments .= '<li title="'.date_formate($comment['bt_id']).'"><strong>'.$comment['bt_author'].' : </strong><a href="'.$comment['bt_link'].'">'.$comment['contenu_abbr'].'</a>'.'</li>'."\n";
		}
		$listeLastComments .= '</ul>'."\n";
		return $listeLastComments;
	} else {
		return $GLOBALS['lang']['no_comments'];
	}
}

function encart_categories($mode) {
	if ($GLOBALS['activer_categories'] == '1') {
		$where = ($mode == 'links') ? 'links' : 'articles';
		$ampmode = ($mode == 'links') ? '&amp;mode=links' : '';

		$liste = list_all_tags($where, '1');

		// attach non-diacritic versions of tag, so that "é" does not pass after "z" and re-indexes
		foreach ($liste as $tag => $nb) {
			$liste[$tag] = array(diacritique(trim($tag)), $nb);
		}
		// sort tags according non-diacritics versions of tags
		$liste = array_reverse(tri_selon_sous_cle($liste, 0));
		$uliste = '<ul>'."\n";

		// create the <UL> with "tags (nb) "
		foreach($liste as $tag => $nb) {
			$uliste .= "\t".'<li><a href="?tag='.urlencode(trim($tag)).$ampmode.'" rel="tag">'.ucfirst($tag).' ('.$nb[1].')</a></li>'."\n";
		}
		$uliste .= '</ul>'."\n";
		return $uliste;
	}
}

function lien_pagination() {
	if (!isset($GLOBALS['param_pagination']) or isset($_GET['d']) or isset($_GET['liste']) or isset($_GET['id']) ) {
		return '';
	}
	else {
		$nb = $GLOBALS['param_pagination']['nb'];
		$nb_par_page = $GLOBALS['param_pagination']['nb_par_page'];
	}
	$page_courante = (isset($_GET['p']) and is_numeric($_GET['p'])) ? $_GET['p'] : 0;
	$qstring = remove_url_param('p');
//	debug($qstring);

	if ($page_courante <=0) {
		$lien_precede = '';
		$lien_suivant = '<a href="?'.$qstring.'&amp;p=1" rel="next">'.$GLOBALS['lang']['label_suivant'].'</a>';
		if ($nb < $nb_par_page) { // évite de pouvoir aller dans la passé s’il y a moins de 10 posts
			$lien_suivant = '';
		}
	}
	elseif ($nb < $nb_par_page) { // évite de pouvoir aller dans l’infini en arrière dans les pages, nottament pour les robots.
		$lien_precede = '<a href="?'.$qstring.'&amp;p='.($page_courante-1).'" rel="prev">'.$GLOBALS['lang']['label_precedent'].'</a>';
		$lien_suivant = '';
	} else {
		$lien_precede = '<a href="?'.$qstring.'&amp;p='.($page_courante-1).'" rel="prev">'.$GLOBALS['lang']['label_precedent'].'</a>';
		$lien_suivant = '<a href="?'.$qstring.'&amp;p='.($page_courante+1).'" rel="next">'.$GLOBALS['lang']['label_suivant'].'</a>';
	}

	return '<p class="pagination">'.$lien_precede.$lien_suivant.'</p>';
}


function liste_tags($billet, $html_link) {
	$tags = ($billet['bt_type'] == 'article') ? $billet['bt_categories'] : $billet['bt_tags'];
	$mode = ($billet['bt_type'] == 'article') ? '' : '&amp;mode=links';
	$liste = '';
	if (!empty($tags)) {
		$tag_list = explode(', ', $tags);
		// remove diacritics, so that "ééé" does not passe after "zzz" and re-indexes
		foreach ($tag_list as $i => $tag) {
			$tag_list[$i] = array('t' => trim($tag), 'tt' => diacritique(trim($tag)));
		}
		$tag_list = array_reverse(tri_selon_sous_cle($tag_list, 'tt'));

		foreach($tag_list as $tag) {
			$tag = trim($tag['t']);
			if ($html_link == 1) {
				$liste .= '<a href="?tag='.urlencode($tag).$mode.'" rel="tag">'.$tag.'</a>';
			} else {
				$liste .= $tag.' ';
			}
		}
	}
	return $liste;
}


/* From DB : returns a HTML list with the feeds (the left panel) */
function feed_list_html() {
	// counts unread feeds in DB
	$feeds_nb = rss_count_feed();
	$total_unread = 0;
	foreach ($feeds_nb as $feed) {
		$total_unread += $feed['nbrun'];
	}

	// First item : link all feeds
	$html = "\t\t".'<li class="all-feeds"><a href="#" onclick="document.getElementById(\'markasread\').onclick=function(){sendMarkReadRequest(\'all\',\'\', true);}; return sortAll();">'.$GLOBALS['lang']['rss_label_all_feeds'].' <span id="global-post-counter" data-nbrun="'.$total_unread.'">('.$total_unread.')</span></a></li>'."\n";

	$feed_urls = array();
	foreach ($feeds_nb as $i => $feed) {
		$feed_urls[$feed['bt_feed']] = $feed;
	}

	// sort feeds by folder
	$folders = array();
	foreach ($GLOBALS['liste_flux'] as $i => $feed) {
		$feed['nbrun'] = (isset($feed_urls[$feed['link']]['nbrun']) ? $feed_urls[$feed['link']]['nbrun'] : 0);
		$folders[$feed['folder']][] = $feed;
	}
	krsort($folders);

	// creates html : lists RSS feeds without folder separately from feeds with a folder
	foreach ($folders as $i => $folder) {
		//$folder = tri_selon_sous_cle($folder, 'nbrun');
		$li_html = "";
		$folder_count = 0;
		foreach ($folder as $j => $feed) {
			$js = 'onclick="document.getElementById(\'markasread\').onclick=function(){sendMarkReadRequest(\'site\', \''.$feed['link'].'\', true);}; sortSite(this);"';
				$li_html .= "\t\t".'<li class="" data-nbrun="'.$feed['nbrun'].'" data-feedurl="'.$feed['link'].'" title="'.$feed['link'].'">';
				$li_html .= '<a href="#" '.(($feed['iserror'] > 2) ? 'class="feed-error" ': ' ' ).$js.' data-feed-domain="'.parse_url($feed['link'], PHP_URL_HOST).'">'.$feed['title'].'</a>';
				$li_html .= '<span>('.$feed['nbrun'].')</span>';
				$li_html .= '</li>'."\n";
				$folder_count += $feed['nbrun'];
		}

		if ($i != '') {
			$html .= "\t\t".'<li class="feed-folder" data-nbrun="'.$folder_count.'" data-folder="'.$i.'">'."\n";
			$html .= "\t\t\t".'<span class="feed-folder-title">'."\n";
			$html .= "\t\t\t\t".'<a href="#" onclick="document.getElementById(\'markasread\').onclick=function(){sendMarkReadRequest(\'folder\', \''.$i.'\', true);}; sortFolder(this);">'.$i.'<span>('.$folder_count.')</span></a>'."\n";
			$html .= "\t\t\t\t".'<a href="#" onclick="return hideFolder(this)" class="unfold">unfold</a>'."\n";
			$html .= "\t\t\t".'</span>'."\n";
			$html .= "\t\t\t".'<ul>'."\n\t\t";
		}
		$html .= $li_html;
		if ($i != '') {
			$html .= "\t\t\t".'</ul>'."\n";
			$html .= "\t\t".'</li>'."\n";
		}

	}
	return $html;
}


function php_lang_to_js($a) {
	$frontend_str = array();
	$frontend_str['maxFilesSize'] = min(return_bytes(ini_get('upload_max_filesize')), return_bytes(ini_get('post_max_size')));
	$frontend_str['rssJsAlertNewLink'] = $GLOBALS['lang']['rss_jsalert_new_link'];
	$frontend_str['rssJsAlertNewLinkFolder'] = $GLOBALS['lang']['rss_jsalert_new_link_folder'];
	$frontend_str['confirmFeedClean'] = $GLOBALS['lang']['confirm_feed_clean'];
	$frontend_str['confirmCommentSuppr'] = $GLOBALS['lang']['confirm_comment_suppr'];
	$frontend_str['activer'] = $GLOBALS['lang']['activer'];
	$frontend_str['desactiver'] = $GLOBALS['lang']['desactiver'];
	$frontend_str['errorPhpAjax'] = $GLOBALS['lang']['error_phpajax'];
	$frontend_str['errorCommentSuppr'] = $GLOBALS['lang']['error_comment_suppr'];
	$frontend_str['errorCommentValid'] = $GLOBALS['lang']['error_comment_valid'];
	$frontend_str['questionQuitPage'] = $GLOBALS['lang']['question_quit_page'];
	$frontend_str['questionCleanRss'] = $GLOBALS['lang']['question_clean_rss'];
	$frontend_str['questionSupprComment'] = $GLOBALS['lang']['question_suppr_comment'];
	$frontend_str['questionSupprArticle'] = $GLOBALS['lang']['question_suppr_article'];
	$frontend_str['questionSupprFichier'] = $GLOBALS['lang']['question_suppr_fichier'];

	$sc = 'var BTlang = '.json_encode($frontend_str).';';

	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

