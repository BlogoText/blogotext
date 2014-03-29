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

$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();
$begin = microtime(TRUE);

$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);
$step = 0;

// modèle d'affichage d'un div pour un lien (avec un formaulaire d'édition par lien).
function afficher_liens($link) {
	$list = '';

	$list .= '<div class="linkbloc'.(!$link['bt_statut'] ? ' privatebloc' : '').'">'."\n";
	$list .= "\t".'<p class="lien_editer">'."\n";
	$list .= "\t\t"
		.(($link['bt_statut'] == '1') ? '<a href="'.$GLOBALS['racine'].'?mode=links&amp;id='.$link['bt_id'].'" class="links-link ll-see" title="'.$GLOBALS['lang']['voir_sur_le_blog'].'"></a> ' : '')
		.(empty($_GET['id']) ? '<a href="'.$_SERVER['PHP_SELF'].'?id='.$link['bt_id'].'" class="links-link ll-edit" title="'.$GLOBALS['lang']['editer'].'"></a> ' : '')
		.(!$link['bt_statut'] ? '<img src="style/lock.png" title="'.$GLOBALS['lang']['link_is_private'].'" alt="private-icon" />' : '');
	$list .= "\t".'</p>'."\n";
	$list .= "\t".'<h3 class="titre-lien"><a href="'.$link['bt_link'].'">'.$link['bt_title'].'</a></h3>'."\n";

	$list .= "\t".'<p>'.$link['bt_content'].'</p>'."\n";
	$list .= "\t".'<p class="date">'.date_formate($link['bt_id']).', '.heure_formate($link['bt_id']).' - '.$link['bt_link'].'</p>'."\n";

	if (!empty($link['bt_tags'])) {
		$tags = explode(',', $link['bt_tags']);
		$list .= '<p class="link-tags">';
			foreach ($tags as $tag) $list .= '<span class="tag">'.'<a href="?filtre=tag.'.urlencode(trim($tag)).'">'.trim($tag).'</a>'.'</span> ';
		$list .= '</p>'."\n";
	}

	$list .= '</div>'."\n";
	echo $list;
}


// TRAITEMENT
$erreurs_form = array();
if (!isset($_GET['url'])) { // rien : on affiche le premier FORM
	$step = 1;
} else { // URL donné dans le $_GET
	$step = 2;
}
if (isset($_GET['id']) and preg_match('#\d{14}#', $_GET['id'])) {
	$step = 'edit';
}

if (isset($_POST['_verif_envoi'])) {
	$link = init_post_link2();
	$erreurs_form = valider_form_link($link);
	$step = 'edit';
	if (empty($erreurs_form)) {

		// URL est un fichier !html !js !css !php ![vide] && téléchargement de fichiers activé :
		if (!isset($_POST['is_it_edit']) and $GLOBALS['dl_link_to_files'] >= 1) {

			// dl_link_to_files : 0 = never ; 1 = always ; 2 = ask with checkbox
			if ( isset($_POST['add_to_files']) ) {
				$_POST['fichier'] = $link['bt_link'];
				$fichier = init_post_fichier();
				$erreurs = valider_form_fichier($fichier);

				$GLOBALS['liste_fichiers'] = open_serialzd_file($GLOBALS['fichier_liste_fichiers']);
				bdd_fichier($fichier, 'ajout-nouveau', 'download', $link['bt_link']);
			}
		}
		traiter_form_link($link);
	}
}

// create link list.
$tableau = array();

// si on veut ajouter un lien : on n’affiche pas les anciens liens
if (!isset($_GET['url']) and !isset($_GET['ajout'])) {
	if ( !empty($_GET['filtre']) ) {
		// for "tags" & "author" the requests is "tag.$search" : here we split the type of search and what we search.
		$type = substr($_GET['filtre'], 0, -strlen(strstr($_GET['filtre'], '.')));
		$search = htmlspecialchars(ltrim(strstr($_GET['filtre'], '.'), '.'));
		if ( preg_match('#^\d{6}(\d{1,8})?$#', $_GET['filtre']) ) { // date
			$query = "SELECT * FROM links WHERE bt_id LIKE ? ORDER BY bt_id DESC";
			$tableau = liste_elements($query, array($_GET['filtre'].'%'), 'links');
		} elseif ($_GET['filtre'] == 'draft' or $_GET['filtre'] == 'pub') { // visibles & brouillons
			$query = "SELECT * FROM links WHERE bt_statut=? ORDER BY bt_id DESC";
			$tableau = liste_elements($query, array((($_GET['filtre'] == 'draft') ? 0 : 1)), 'links');
		} elseif ($type == 'tag' and $search != '') { // tags
			$query = "SELECT * FROM links WHERE bt_tags LIKE ? OR bt_tags LIKE ? OR bt_tags LIKE ? OR bt_tags LIKE ? ORDER BY bt_id DESC";
			$tableau = liste_elements($query, array($search, $search.',%', '%, '.$search, '%, '.$search.', %'), 'links');
		} elseif ($type == 'auteur' and $search != '') { // auteur
			$query = "SELECT * FROM links WHERE bt_author=? ORDER BY bt_id DESC";
			$tableau = liste_elements($query, array($search), 'links');
		} else {
			$query = "SELECT * FROM links ORDER BY bt_id DESC LIMIT 0, ".$GLOBALS['max_linx_admin'];
			$tableau = liste_elements($query, array(), 'links');
		}
	} elseif (!empty($_GET['q'])) { // mot clé
		$arr = parse_search($_GET['q']);
		$sql_where = implode(array_fill(0, count($arr), '( bt_content || bt_title || bt_link ) LIKE ? '), 'AND '); // AND operator between words
		$query = "SELECT * FROM links WHERE ".$sql_where."ORDER BY bt_id DESC";
		$tableau = liste_elements($query, $arr, 'links');
	} elseif (!empty($_GET['id']) and is_numeric($_GET['id'])) { // édition d’un lien spécifique
		$query = "SELECT * FROM links WHERE bt_id=?";
		$tableau = liste_elements($query, array($_GET['id']), 'links');
	} else { // aucun filtre : affiche TOUT
		$query = "SELECT * FROM links ORDER BY bt_id DESC LIMIT 0, ".$GLOBALS['max_linx_admin'];
		$tableau = liste_elements($query, array(), 'links');
	}
}

// count total nb of links
$nb_links_displayed = count($tableau);

afficher_top($GLOBALS['lang']['mesliens']);
echo '<div id="top">'."\n";
afficher_msg($GLOBALS['lang']['mesliens']);
echo moteur_recherche($GLOBALS['lang']['search_in_links']);
afficher_menu(pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME));
echo '</div>'."\n";


echo '<div id="axe">'."\n";

// SUBNAV
echo '<div id="subnav">'."\n";
	// Affichage formulaire filtrage liens
	if (isset($_GET['filtre'])) {
		afficher_form_filtre('links', htmlspecialchars($_GET['filtre']));
	} else {
		afficher_form_filtre('links', '');
	}
echo '</div>'."\n";


echo '<div id="page">'."\n";

if ($step == 'edit' and !empty($tableau[0]) ) { // edit un lien : affiche le lien au dessus du champ d’édit
	afficher_liens($tableau[0]);
	echo afficher_form_link($step, $erreurs_form, $tableau[0]);
}
elseif ($step == 2) { // lien donné dans l’URL
	echo afficher_form_link($step, $erreurs_form);
}
else { // aucun lien à ajouter ou éditer : champ nouveau lien + listage des liens en dessus.
	echo afficher_form_link(1, $erreurs_form);
	echo "\t".'<p class="nombre-elem">';
		echo "\t\t".ucfirst(nombre_liens($nb_links_displayed)).' '.$GLOBALS['lang']['sur'].' '.liste_elements_count("SELECT count(*) AS nbr FROM links", array(), 'links')."\n";
	echo "\t".'</p>'."\n";
	foreach ($tableau as $link) {
		afficher_liens($link);
	}
}

footer('', $begin);

