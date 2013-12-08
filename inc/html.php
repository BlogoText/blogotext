<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***


/// menu haut panneau admin /////////
function afficher_menu($active) {
	echo '<div id="nav">'."\n";
	echo "\t".'<a href="index.php" id="lien-index"', ($active == 'index.php') ? ' class="current"' : '', '>'.$GLOBALS['lang']['label_resume'].'</a>'."\n";
	echo "\t".'<a href="articles.php" id="lien-liste"', ($active == 'articles.php') ? ' class="current"' : '', '>'.$GLOBALS['lang']['mesarticles'].'</a>'."\n";
	echo "\t".'<a href="ecrire.php" id="lien-nouveau"', ($active == 'ecrire.php') ? ' class="current"' : '', '>'.$GLOBALS['lang']['nouveau'].'</a>'."\n";
	echo "\t".'<a href="commentaires.php" id="lien-lscom"', ($active == 'commentaires.php') ? ' class="current"' : '', '>'.$GLOBALS['lang']['titre_commentaires'].'</a>'."\n";
	echo "\t".'<a href="fichiers.php" id="lien-fichiers"', ($active == 'fichiers.php') ? ' class="current"' : '', '>'.ucfirst($GLOBALS['lang']['label_fichiers']).'</a>'."\n";
	echo "\t".'<a href="links.php" id="lien-links"', ($active == 'links.php') ? ' class="current"' : '', '>'.ucfirst($GLOBALS['lang']['label_links']).'</a>'."\n";
	echo "\t".'<div id="nav-top">'."\n";
	echo "\t\t".'<a href="preferences.php" id="lien-preferences">'.$GLOBALS['lang']['preferences'].'</a>'."\n";
	echo "\t\t".'<a href="'.$GLOBALS['racine'].'" id="lien-site">'.$GLOBALS['lang']['lien_blog'].'</a>'."\n";
	echo "\t\t".'<a href="logout.php" id="lien-deconnexion">'.$GLOBALS['lang']['deconnexion'].'</a>'."\n";
	echo "\t".'</div>'."\n";
	echo '</div>'."\n";
}

function confirmation($message) {
	echo '<div class="confirmation"><span>'.$message.'</span></div>'."\n";
}

function no_confirmation($message) {
	echo '<div class="no_confirmation"><span>'.$message.'</span></div>'."\n";
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
	if ($erreurs) {
		$texte_erreur = '<div id="erreurs">'.'<strong>'.$GLOBALS['lang']['erreurs'].'</strong> :' ;
		$texte_erreur .= '<ul><li>';
		$texte_erreur .= implode('</li><li>', $erreurs);
		$texte_erreur .= '</li></ul></div>'."\n";
	} else {
		$texte_erreur = '';
	}
	return $texte_erreur;
}

function erreur($message) {
	  echo '<p class="erreurs">'.$message.'</p>'."\n";
}

function question($message) {
	  echo '<p id="question">'.$message.'</p>';
}

function afficher_msg($titre) {
	if (strlen($titre) != 0) { echo '<h1>'.$titre.'</h1>'."\n";
	} else { echo '<h1>'.$GLOBALS['nom_application'].'</h1>'."\n"; }
	// message vert
	if (isset($_GET['msg'])) {
		if (array_key_exists(htmlspecialchars($_GET['msg']), $GLOBALS['lang'])) {
			confirmation($GLOBALS['lang'][$_GET['msg']]);
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

function moteur_recherche($placeholder) {
	$requete='';
	if (isset($_GET['q'])) {
		$requete = htmlspecialchars(stripslashes($_GET['q']));
	}
	$return = '<form action="'.$_SERVER['PHP_SELF'].'" method="get" id="search">'."\n";
	$return .= '<input id="q" name="q" type="search" size="20" value="'.$requete.'" class="text" placeholder="'.$placeholder.'" />'."\n";
	if (isset($_GET['mode'])) {
		$return .= '<input id="mode" name="mode" type="hidden" value="'.htmlspecialchars(stripslashes($_GET['mode'])).'" />'."\n";
	}
	$return .= '<input class="silver-square" id="input-rechercher" type="submit" value="'.$GLOBALS['lang']['rechercher'].'" />'."\n";
	$return .= '</form>'."\n\n";
	return $return;
}

function afficher_top($titre) {
	if (isset($GLOBALS['lang']['id'])) {
		$lang_id = $GLOBALS['lang']['id'];
	} else {
		$lang_id = 'fr';
	}
	$txt = '<!DOCTYPE html>'."\n";
	$txt .= '<head>'."\n";
	$txt .= '<meta charset="UTF-8" />'."\n";
	$txt .= '<link type="text/css" rel="stylesheet" href="style/style.css.php" />'."\n";
	$txt .= '<meta name="viewport" content="initial-scale=1.0, user-scalable=yes" />'."\n";
	$txt .= '<title> '.$GLOBALS['nom_application'].' | '.$titre.'</title>'."\n";
	$txt .= '</head>'."\n";
	$txt .= '<body id="body">'."\n\n";
	echo $txt;
}

function footer($index='', $begin_time='') {
	if ($index != '') {
		$file = '../config/ip.php';
		if (file_exists($file) and is_readable($file)) {
			include($file);
			$new_ip = htmlspecialchars($_SERVER['REMOTE_ADDR']);
			$last_time = strtolower(date_formate($GLOBALS['old_time'])).', '.heure_formate($GLOBALS['old_time']);
			if ($new_ip == $GLOBALS['old_ip']) {
				$msg = '<br/>'.$GLOBALS['lang']['derniere_connexion_le'].' '.$GLOBALS['old_ip'].' ('.$GLOBALS['lang']['cet_ordi'].'), '.$last_time;
			} else {
				$msg = '<br/>'.$GLOBALS['lang']['derniere_connexion_le'].' '.$GLOBALS['old_ip'].' '.$last_time;
			}
		} else {
			$msg = '';
		}
	} else {
		$msg = '';
	}
	if ($begin_time != ''){
		$end = microtime(TRUE);
		$dt = round(($end - $begin_time),6);
		$msg2 = ' - '.$GLOBALS['lang']['rendered'].' '.$dt.' s '.$GLOBALS['lang']['using'].' '.$GLOBALS['sgdb'];
	} else {
		$msg2 = '';
	}

	echo '</div>'."\n";
	echo '</div>'."\n";
	echo '<p id="footer"><a href="'.$GLOBALS['appsite'].'">'.$GLOBALS['nom_application'].' '.$GLOBALS['version'].'</a>'.$msg2.$msg.'</p>'."\n";
	echo '<script src="style/javascript.js"></script>'."\n";
	echo '</body>'."\n";
	echo '</html>'."\n";
}

// returns HTML <table> calender
function afficher_calendrier() {
	// article
	if ( isset($_GET['d']) and preg_match('#^\d{4}(/\d{2}){5}#', $_GET['d'])) {
		$id = substr(str_replace('/', '', $_GET['d']), 0, 14);
		$date = substr(get_entry($GLOBALS['db_handle'], 'articles', 'bt_date', $id, 'return'), 0, 8);
		$date = (preg_match('#^\d{4}(/\d{2}){5}#', $date) and $date <= date('Y/m/d/H/i/s')) ? $date : date('Ym');
	} elseif ( isset($_GET['d']) and preg_match('#^\d{4}/\d{2}(/\d{2})?#', $_GET['d']) ) {
		$date = str_replace('/', '', $_GET['d']);
		$date = (preg_match('#^\d{6}\d{2}#', $date)) ? substr($date, 0, 8) : substr($date, 0, 6); // avec jour ?
	} elseif (isset($_GET['id']) and preg_match('#^\d{14}#', $_GET['id']) ) {
		$date = substr($_GET['id'], 0, 8);
	} else {
		$date = date('Ym');
	}

	$annee = substr($date, 0, 4);
	$ce_mois = substr($date, 4, 2);
	$ce_jour = (strlen(substr($date, 6, 2)) == 2) ? substr($date, 6, 2) : '';

	$qstring = (isset($_GET['mode']) and !empty($_GET['mode'])) ? 'mode='.htmlspecialchars($_GET['mode']).'&amp;' : '';

	$jours_semaine = array(
		$GLOBALS['lang']['lu'],
		$GLOBALS['lang']['ma'],
		$GLOBALS['lang']['me'],
		$GLOBALS['lang']['je'],
		$GLOBALS['lang']['ve'],
		$GLOBALS['lang']['sa'],
		$GLOBALS['lang']['di']
	);
	$premier_jour = mktime('0', '0', '0', $ce_mois, '1', $annee);
	$jours_dans_mois = date('t', $premier_jour);
	$decalage_jour = date('w', $premier_jour-'1');
	$prev_mois =      $_SERVER['PHP_SELF'].'?'.$qstring.'d='.$annee.'/'.str2($ce_mois-1);
	if ($prev_mois == $_SERVER['PHP_SELF'].'?'.$qstring.'d='.$annee.'/'.'00') {
		$prev_mois =   $_SERVER['PHP_SELF'].'?'.$qstring.'d='.($annee-'1').'/'.'12';
	}
	$next_mois =      $_SERVER['PHP_SELF'].'?'.$qstring.'d='.$annee.'/'.str2($ce_mois+1);
	if ($next_mois == $_SERVER['PHP_SELF'].'?'.$qstring.'d='.$annee.'/'.'13') {
		$next_mois =   $_SERVER['PHP_SELF'].'?'.$qstring.'d='.($annee+'1').'/'.'01';
	}

	// On verifie si il y a un ou des articles/liens/commentaire du jour dans le mois courant
	$tableau = array();
	$mode = ( !empty($_GET['mode']) ) ? $_GET['mode'] : 'blog';
	switch($mode) {
		case 'comments':
			$where = 'commentaires'; break;
		case 'links':
			$where = 'links'; break;
		case 'blog':
		default:
			$where = 'articles'; break;
	}

	$tableau = table_list_date($annee.$ce_mois, 1, $where);

	$html = '<table id="calendrier">'."\n";
	$html .= '<caption>';
	if ( $annee.$ce_mois > $GLOBALS['date_premier_message_blog']) {
		$html .= '<a href="'.$prev_mois.'">&#171;</a>&nbsp;';
	}

	// Si on affiche un jour on ajoute le lien sur le mois
	$html .= '<a href="'.$_SERVER['PHP_SELF'].'?'.$qstring.'d='.$annee.'/'.$ce_mois.'">'.mois_en_lettres($ce_mois).' '.$annee.'</a>';
	// On ne peut pas aller dans le futur
	if ( ($ce_mois != date('m')) || ($annee != date('Y')) ) {
		$html .= '&nbsp;<a href="'.$next_mois.'">&#187;</a>';
	}
	$html .= '</caption>'."\n";
	$html .= '<tr><th><abbr>';
	$html .= implode('</abbr></th><th><abbr>', $jours_semaine);
	$html .= '</abbr></th></tr><tr>';
	if ($decalage_jour > 0) {
		for ($i = 0; $i < $decalage_jour; $i++) {
			$html .=  '<td></td>';
		}
	}
	// Indique le jour consulte
	for ($jour = 1; $jour <= $jours_dans_mois; $jour++) {
		if ($jour == $ce_jour) {
			$class = ' class="active"';
		} else {
			$class = '';
		}
		if ( in_array($jour, $tableau) ) {
			$lien = '<a href="'.$_SERVER['PHP_SELF'].'?'.$qstring.'d='.$annee.'/'.$ce_mois.'/'.str2($jour).'">'.$jour.'</a>';
		} else {
			$lien = $jour;
		}
		$html .= '<td'.$class.'>';
		$html .= $lien;
		$html .= '</td>';
		$decalage_jour++;
		if ($decalage_jour == 7) {
			$decalage_jour = 0;
			$html .=  '</tr>';
			if ($jour < $jours_dans_mois) {
				$html .= '<tr>';
			}
		}
	}
	if ($decalage_jour > 0) {
		for ($i = $decalage_jour; $i < 7; $i++) {
			$html .= '<td> </td>';
		}
		$html .= '</tr>';
	}
	$html .= '</table>';
	return $html;
}

function encart_commentaires() {
	$query = "SELECT c.bt_author, c.bt_id, c.bt_article_id, c.bt_content, a.bt_title FROM commentaires c LEFT JOIN articles a ON a.bt_id=c.bt_article_id WHERE c.bt_statut=1 AND a.bt_statut=1 ORDER BY c.bt_id DESC LIMIT 5";
	$tableau = liste_elements($query, array(), 'commentaires');
	if (isset($tableau)) {
		$listeLastComments = '<ul class="encart_lastcom">';
		foreach ($tableau as $i => $comment) {
			$comment['contenu_abbr'] = strip_tags($comment['bt_content']);
			if (strlen($comment['contenu_abbr']) >= 60) {
				$abstract = explode("|", wordwrap($comment['contenu_abbr'], 60, "|"), 2);
				$comment['contenu_abbr'] = $abstract[0]."…";
			}
			$listeLastComments .= '<li title="'.date_formate($comment['bt_id']).'"><b>'.$comment['bt_author'].'</b> '.$GLOBALS['lang']['sur'].' <b>'.$comment['bt_title'].'</b><br/><a href="'.$comment['bt_link'].'">'.$comment['contenu_abbr'].'</a>'.'</li>';
		}
		$listeLastComments .= '</ul>';
		return $listeLastComments;
	} else {
		return $GLOBALS['lang']['no_comments'];
	}
}

function encart_categories($mode) {
	if ($GLOBALS['activer_categories'] == '1') {
		$where = ($mode == 'links') ? 'links' : 'articles';
		$ampmode = ($mode == 'links') ? '&amp;mode=links' : '';

		$liste = list_all_tags($where);
		$uliste = '<ul>'."\n";
		foreach($liste as $tag) {
			$tagurl = urlencode(trim($tag['tag']));
			$uliste .= "\t".'<li><a href="'.$_SERVER['PHP_SELF'].'?tag='.$tagurl.$ampmode.'" rel="tag">'.ucfirst($tag['tag']).'</a></li>'."\n";
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

	if ($page_courante <=0) {
		$lien_precede = '';
		$lien_suivant = '<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?'.$qstring.'&amp;p=1" rel="next">'.$GLOBALS['lang']['label_suivant'].' &#8827;</a>';
		if ($nb < $nb_par_page) { // évite de pouvoir aller dans la passé s’il y a moins de 10 posts
			$lien_suivant = '';
		}
	}
	elseif ($nb < $nb_par_page) { // évite de pouvoir aller dans l’infini en arrière dans les pages, nottament pour les robots.
		$lien_precede = '<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?'.$qstring.'&amp;p='.($page_courante-1).'" rel="prev">&#8826; '.$GLOBALS['lang']['label_precedent'].'</a>';
		$lien_suivant = '';
	} else {
		$lien_precede = '<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?'.$qstring.'&amp;p='.($page_courante-1).'" rel="prev">&#8826; '.$GLOBALS['lang']['label_precedent'].'</a>';
		$lien_suivant = '<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?'.$qstring.'&amp;p='.($page_courante+1).'" rel="next">'.$GLOBALS['lang']['label_suivant'].' &#8827;</a>';
	}

	$glue = ' – ';
	if (empty($lien_precede) or empty($lien_suivant)) $glue = ' ';

	return '<p class="pagination">'.$lien_precede.$glue.$lien_suivant.'</p>';
}


function liste_tags($billet, $html_link) {
	$tags = ($billet['bt_type'] == 'article') ? $billet['bt_categories'] : $billet['bt_tags'];
	$mode = ($billet['bt_type'] == 'article') ? '' : '&amp;mode=links';
	if (!empty($tags)) {
		$tag_list = explode(',', $tags);
		$nb_tags = sizeof($tag_list);
		$liste = '';
		if ($html_link == 1) {
			foreach($tag_list as $tag) {
				$tag = trim($tag);
				$tagurl = urlencode(trim($tag));
				$liste .= '<a href="'.$_SERVER['PHP_SELF'].'?tag='.$tagurl.$mode.'" rel="tag">'.$tag.'</a>, ';
			}
			$liste = trim($liste, ', ');
		} else {
			foreach($tag_list as $tag) {
				$tag = trim($tag);
				$tag = diacritique($tag, 0, 0);
				$liste .= $tag.', ';
			}
			$liste = trim($liste, ', ');
		}
	} else {
		$liste = '';
	}
	return $liste;
}


// AFFICHE LA LISTE DES ARTICLES, DANS LA PAGE ADMIN
function afficher_liste_articles($tableau) {
	if (!empty($tableau)) {
		$i = 0;
		$out = '<ul id="billets">'."\n";
		foreach ($tableau as $article) {
			// ICONE SELON STATUT
			$out .= "\t".'<li>'."\n";
			// TITRE
			$out .= "\t\t".'<span><span class="'.( ($article['bt_statut'] == '1') ? 'on' : 'off').'"></span>'.'<a href="ecrire.php?post_id='.$article['bt_id'].'" title="'.trim($article['bt_abstract']).'">'.$article['bt_title'].'</a>'.'</span>'."\n";
			// DATE
			$out .= "\t\t".'<span><a href="'.$_SERVER['PHP_SELF'].'?filtre='.substr($article['bt_date'],0,8).'">'.date_formate($article['bt_date']).'</a> - '.heure_formate($article['bt_date']).'</span>'."\n";
			// NOMBRE COMMENTS
			if ($article['bt_nb_comments'] == 1) {
				$texte = $article['bt_nb_comments'].' '.$GLOBALS['lang']['label_commentaire'];
			} elseif ($article['bt_nb_comments'] > 1) {
				$texte = $article['bt_nb_comments'].' '.$GLOBALS['lang']['label_commentaires'];
			} else {
				$texte = '&nbsp;';
			}
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

