<?php
# *** LICENSE ***
# This file is a addon for BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2016 Timo Van Neerden <timo@neerden.eu>
#
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

# This plugin replaces the {calendrier} tag in the public template with
# a navigable HTML calendar. 

// include this addon
$GLOBALS['addons'][] = array('tag' => '{calendrier}', 'callback_function' => 'addon_calendrier');

// returns HTML <table> calender
function addon_calendrier() {
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
	$prev_mois =      '?'.$qstring.'d='.$annee.'/'.str2($ce_mois-1);
	if ($prev_mois == '?'.$qstring.'d='.$annee.'/'.'00') {
		$prev_mois =   '?'.$qstring.'d='.($annee-'1').'/'.'12';
	}
	$next_mois =      '?'.$qstring.'d='.$annee.'/'.str2($ce_mois+1);
	if ($next_mois == '?'.$qstring.'d='.$annee.'/'.'13') {
		$next_mois =   '?'.$qstring.'d='.($annee+'1').'/'.'01';
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
	if ( $annee.$ce_mois > DATE_PREMIER_MESSAGE_BLOG) {
		$html .= '<a href="'.$prev_mois.'">&#171;</a>&nbsp;';
	}

	// Si on affiche un jour on ajoute le lien sur le mois
	$html .= '<a href="?'.$qstring.'d='.$annee.'/'.$ce_mois.'">'.mois_en_lettres($ce_mois).' '.$annee.'</a>';
	// On ne peut pas aller dans le futur
	if ( ($ce_mois != date('m')) || ($annee != date('Y')) ) {
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



