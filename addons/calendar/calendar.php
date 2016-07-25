<?php
# *** LICENSE ***
# This file is a addon for BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2016 Timo Van Neerden.
#
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

# This plugin replaces the {addon_calendar} tag in the public template with
# a navigable HTML calendar.

// include this addon
$GLOBALS['addons'][] = array(
	'tag' => 'calendar',  // the same name of this file without ".php"
	'name' => 'Calendar',  // the displayed name into back office
	'desc' => 'Display a navigable HTML calendar',  // a little description
	'version' => '1.0.0',  // SemVer notation: http://semver.org/
	'css' => 'style.css',  // CSS files to include
	//'js' => array('file1.js', 'file2.js'),  // JS files to include
);

// returns a list of days containing at least one post for a given month
function table_list_date($date, $table) {
	$return = array();
	$column = ($table == 'articles') ? 'bt_date' : 'bt_id';
	$and_date = 'AND '.$column.' <= '.date('YmdHis');

	$query = "SELECT DISTINCT SUBSTR($column, 7, 2) AS date FROM $table WHERE bt_statut = 1 AND $column LIKE '$date%' $and_date";

	try {
		$req = $GLOBALS['db_handle']->query($query);
		while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			$return[] = $row['date'];
		}
		return $return;
	} catch (Exception $e) {
		die('Erreur 21436 : '.$e->getMessage());
	}
}

// returns dates of the previous and next visible posts
function prev_next_posts($year, $month, $table) {
	$column = ($table == 'articles') ? 'bt_date' : 'bt_id';
	$and_date = 'AND '.$column.' <= '.date('YmdHis');

	$date = new DateTime();
	$date->setDate($year, $month, 1)->setTime(0, 0, 0);
	$date_min = $date->format('YmdHis');
	$date->modify('+1 month');
	$date_max = $date->format('YmdHis');

	$query = "SELECT
		(SELECT SUBSTR($column, 0, 7) FROM $table WHERE bt_statut = 1 AND $column < $date_min ORDER BY $column DESC LIMIT 1),
		(SELECT SUBSTR($column, 0, 7) FROM $table WHERE bt_statut = 1 AND $column > $date_max $and_date ORDER BY $column ASC LIMIT 1)";

	try {
		$req = $GLOBALS['db_handle']->query($query);
		return array_values($req->fetch(PDO::FETCH_ASSOC));
	} catch (Exception $e) {
		die('Erreur 21436 : '.$e->getMessage());
	}
}

// returns HTML <table> calendar
function addon_calendar() {
	// article
	if ( isset($_GET['d']) and preg_match('#^\d{4}(/\d{2}){5}#', $_GET['d'])) {
		$id = substr(str_replace('/', '', $_GET['d']), 0, 14);
		$date = substr(get_entry($GLOBALS['db_handle'], 'articles', 'bt_date', $id, 'return'), 0, 8);
		$date = ($date <= date('Ymd')) ? $date : date('Ym');
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
	$premier_jour = mktime(0, 0, 0, $ce_mois, 1, $annee);
	$jours_dans_mois = date('t', $premier_jour);
	$decalage_jour = date('w', $premier_jour-1);

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

	// On cherche les dates des articles précédent et suivant
	list($previous_post, $next_post) = prev_next_posts($annee, $ce_mois, $where);
	$prev_mois = '?'.$qstring.'d='.substr($previous_post, 0, 4).'/'.substr($previous_post, 4, 2);
	$next_mois = '?'.$qstring.'d='.substr($next_post, 0, 4).'/'.substr($next_post, 4, 2);

	$tableau = table_list_date($annee.$ce_mois, $where);

	$html = '<table id="calendrier">'."\n";
	$html .= '<caption>';
	if ($previous_post !== null) {
		$html .= '<a href="'.$prev_mois.'">&#171;</a>&nbsp;';
	}

	// Si on affiche un jour on ajoute le lien sur le mois
	$html .= '<a href="?'.$qstring.'d='.$annee.'/'.$ce_mois.'">'.mois_en_lettres($ce_mois).' '.$annee.'</a>';
	// On ne peut pas aller dans le futur
	if ($next_post !== null) {
		$html .= '&nbsp;<a href="'.$next_mois.'">&#187;</a>';
	}
	$html .= '</caption>'."\n".'<tr>'."\n";
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
			$lien = '<a href="?'.$qstring.'d='.$annee.'/'.$ce_mois.'/'.str2($jour).'">'.$jour.'</a>';
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
		$html .= '</tr>'."\n";
	}
	$html .= '</table>'."\n";
	return $html;

}
