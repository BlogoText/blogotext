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

/// formulaires GENERIQUES //////////

function form_select($id, $choix, $defaut, $label) {
	$form = '<label for="'.$id.'">'.$label.'</label>'."\n";
	$form .= "\t".'<select id="'.$id.'" name="'.$id.'">'."\n";
	foreach ($choix as $valeur => $mot) {
		$form .= "\t\t".'<option value="'.$valeur.'"'.(($defaut == $valeur) ? ' selected="selected" ' : '').'>'.$mot.'</option>'."\n";
	}
	$form .= "\t".'</select>'."\n";
	$form .= "\n";
	return $form;
}

function form_select_no_label($id, $choix, $defaut) {
	$form = '<select id="'.$id.'" name="'.$id.'">'."\n";
	foreach ($choix as $valeur => $mot) {
		$form .= "\t".'<option value="'.$valeur.'"'.(($defaut == $valeur) ? ' selected="selected" ' : '').'>'.$mot.'</option>'."\n";
	}
	$form .= '</select>'."\n";
	return $form;
}

function hidden_input($nom, $valeur, $id=0) {
	$id = ($id === 0) ? '' : ' id="'.$nom.'"';
	$form = '<input type="hidden" class="nodisplay" name="'.$nom.'"'.$id.' value="'.$valeur.'" />'."\n";
	return $form;
}

/// formulaires PREFERENCES //////////

function select_yes_no($name, $defaut, $label) {
	$choix = array(
		'1' => $GLOBALS['lang']['oui'],
		'0' => $GLOBALS['lang']['non']
	);
	$form = '<label for="'.$name.'" >'.$label.'</label>';
	$form .= '<select id="'.$name.'" name="'.$name.'">'."\n" ;
	foreach ($choix as $option => $label) {
		$form .= "\t".'<option value="'.htmlentities($option).'"'.(($option == $defaut) ? ' selected="selected" ' : '').'>'.htmlentities($label).'</option>';
	}
	$form .= '</select>'."\n";
	return $form;
}

function form_format_date($defaut) {
	$jour_l = jour_en_lettres(date('d'), date('m'), date('Y'));
	$mois_l = mois_en_lettres(date('m'));
	$formats = array (
		'0' => date('d').'/'.date('m').'/'.date('Y'),             // 05/07/2011
		'1' => date('m').'/'.date('d').'/'.date('Y'),             // 07/05/2011
		'2' => date('d').' '.$mois_l.' '.date('Y'),               // 05 juillet 2011
		'3' => $jour_l.' '.date('d').' '.$mois_l.' '.date('Y'),   // mardi 05 juillet 2011
		'4' => $mois_l.' '.date('d').', '.date('Y'),              // juillet 05, 2011
		'5' => $jour_l.', '.$mois_l.' '.date('d').', '.date('Y'), // mardi, juillet 05, 2011
		'6' => date('Y').'-'.date('m').'-'.date('d'),             // 2011-07-05
	);
	$form = "\t".'<label>'.$GLOBALS['lang']['pref_format_date'].'</label>'."\n";
	$form .= "\t".'<select name="format_date">'."\n";
	foreach ($formats as $option => $label) {
		$form .= "\t\t".'<option value="'.htmlentities($option).'"'.(($defaut == $option) ? ' selected="selected" ' : '').'>'.$label.'</option>'."\n";
	}
	$form .= "\t".'</select>'."\n";
	return $form;
}

 // this test with version compare allows PHP 5.1.2 to run BT. The function timezone_…() is only present in 5.2.0+
 // we could make BT require 5.2.0 to run, but the function in the only one that uses PHP 5.2.
function form_fuseau_horaire($defaut) {
	if (version_compare(PHP_VERSION, '5.2.0', '>=')) {

	$all_timezones = timezone_identifiers_list();
	$liste_fuseau = array();
	$cities = array();
	foreach($all_timezones as $tz) {
		$spos = strpos($tz, '/');
		if ($spos !== FALSE) {
			$continent = substr($tz, 0, $spos);
			$city = substr($tz, $spos+1);
			$liste_fuseau[$continent][] = array('tz_name' => $tz, 'city' => $city);
		}
		if ($tz == 'UTC') {
			$liste_fuseau['UTC'][] = array('tz_name' => 'UTC', 'city' => 'UTC');
		}
	}
	$form = '<label>'.$GLOBALS['lang']['pref_fuseau_horaire'].'</label>'."\n";
	$form .= '<select name="fuseau_horaire">'."\n";
	foreach ($liste_fuseau as $continent => $zone) {
		$form .= "\t".'<optgroup label="'.ucfirst(strtolower($continent)).'">'."\n";
		foreach ($zone as $fuseau) {
			$form .= "\t\t".'<option value="'.htmlentities($fuseau['tz_name']).'"';
			$form .= ($defaut == $fuseau['tz_name']) ? ' selected="selected"' : '';
				$timeoffset = date_offset_get(date_create('now', timezone_open($fuseau['tz_name'])) );
				$formated_toffset = '(UTC'.(($timeoffset < 0) ? '–' : '+').str2(floor((abs($timeoffset)/3600))) .':'.str2(floor((abs($timeoffset)%3600)/60)) .') ';
			$form .= '>'.$formated_toffset.' '.htmlentities($fuseau['city']).'</option>'."\n";
		}
		$form .= "\t".'</optgroup>'."\n";
	}
	$form .= '</select>'."\n";
	return $form;
	}
}

function form_format_heure($defaut) {
	$formats = array (
		'0' => date('H\:i\:s'),		// 23:56:04
		'1' => date('H\:i'),			// 23:56
		'2' => date('h\:i\:s A'),	// 11:56:04 PM
		'3' => date('h\:i A'),		// 11:56 PM
	);
	$form = '<label>'.$GLOBALS['lang']['pref_format_heure'].'</label>'."\n";
	$form .= '<select name="format_heure">'."\n";
	foreach ($formats as $option => $label) {
		$form .= "\t".'<option value="'.htmlentities($option).'"'.(($defaut == $option) ? ' selected="selected" ' : '').'>'.htmlentities($label).'</option>'."\n";
	}
	$form .= "\t".'</select>'."\n";
	return $form;
}

function form_langue($defaut) {
	$form = '<label>'.$GLOBALS['lang']['pref_langue'].'</label>'."\n";
	$form .= '<select name="langue">'."\n";
	foreach ($GLOBALS['langs'] as $option => $label) {
		$form .= "\t".'<option value="'.htmlentities($option).'"'.(($defaut == $option) ? ' selected="selected" ' : '').'>'.$label.'</option>'."\n";
	}
	$form .= '</select>'."\n";
	return $form;
}

function form_langue_install($label) {
	echo '<label for="langue">'.$label;
	echo '<select id="langue" name="langue">'."\n";
	foreach ($GLOBALS['langs'] as $option => $label) {
		echo "\t".'<option value="'.htmlentities($option).'">'.$label.'</option>'."\n";
	}
	echo '</select></label>'."\n";
}

function liste_themes($chemin) {
	if ( $ouverture = opendir($chemin) ) {
		while ($dossiers = readdir($ouverture) ) {
			if ( file_exists($chemin.'/'.$dossiers.'/list.html') ) {
				$themes[$dossiers] = $dossiers;
			}
		}
		closedir($ouverture);
	}
	if (isset($themes)) {
		return $themes;
	}
}


// formulaires ARTICLES //////////

function afficher_form_filtre($type, $filtre) {
	echo '<form method="get" action="'.$_SERVER['PHP_SELF'].'" onchange="this.submit();">'."\n";
	echo '<div id="form-filtre">'."\n";
		filtre($type, $filtre);
	echo '</div>'."\n";
	echo '</form>'."\n";
}

function filtre($type, $filtre) { // cette fonction est très gourmande en ressources.
	$liste_des_types = array();
	echo "\n".'<select name="filtre">'."\n" ;
	// Articles
	if ($type == 'articles') {
		echo '<option value="">'.$GLOBALS['lang']['label_article_derniers'].'</option>'."\n";
		$query = "SELECT DISTINCT substr(bt_date, 1, 6) AS date FROM articles ORDER BY bt_id DESC";
		$tab_tags = list_all_tags('articles');
		$BDD = 'sqlite';
	// Commentaires
	} elseif ($type == 'commentaires') {
		echo '<option value="">'.$GLOBALS['lang']['label_comment_derniers'].'</option>'."\n";
		$tab_auteur = nb_entries_as('commentaires', 'bt_author');
		$query = "SELECT DISTINCT substr(bt_id, 1, 6) AS date FROM commentaires ORDER BY bt_id DESC";
		$BDD = 'sqlite';
	// Liens
	} elseif ($type == 'links') {
		echo '<option value="">'.$GLOBALS['lang']['label_link_derniers'].'</option>'."\n";
		// $tab_auteur = nb_entries_as('links', 'bt_author'); // uncomment when readers will be able to post links
		$tab_tags = list_all_tags('links');
		$query = "SELECT DISTINCT substr(bt_id, 1, 6) AS date FROM links ORDER BY bt_id DESC";
		$BDD = 'sqlite';
	// Fichiers
	} elseif ($type == 'fichiers') {

		// crée un tableau où les clé sont les types de fichiers et les valeurs, le nombre de fichiers de ce type.
		$files = $GLOBALS['liste_fichiers'];
		$tableau_mois = array();
		if (!empty($files)) {
			foreach ($files as $id => $file) {
				$type = $file['bt_type'];
				if (!array_key_exists($type, $liste_des_types)) {
					$liste_des_types[$type] = 1;
				} else {
					$liste_des_types[$type]++;
				}
			}
		}
		arsort($liste_des_types);

		echo '<option value="">'.$GLOBALS['lang']['label_fichier_derniers'].'</option>'."\n";
		$filtre_type = '';
		$BDD = 'fichier_txt_files';
	}

	if ($BDD == 'sqlite') {
		try {
			$req = $GLOBALS['db_handle']->prepare($query);
			$req->execute(array());
			while ($row = $req->fetch()) {
				$tableau_mois[$row['date']] = mois_en_lettres(substr($row['date'], 4, 2)).' '.substr($row['date'], 0, 4);
			}
		} catch (Exception $x) {
			die('Erreur affichage form_filtre() : '.$x->getMessage());
		}
	} elseif ($BDD == 'fichier_txt_files') {
		foreach ($GLOBALS['liste_fichiers'] as $e) {
			if (!empty($e['bt_id'])) {
				// mk array[201005] => "May 2010", uzw
				$tableau_mois[substr($e['bt_id'], 0, 6)] = mois_en_lettres(substr($e['bt_id'], 4, 2)).' '.substr($e['bt_id'], 0, 4);
			}
		}
		krsort($tableau_mois);
	}

	/// BROUILLONS
	echo '<option value="draft"';
	echo ($filtre == 'draft') ? ' selected="selected"' : '';
	echo '>'.$GLOBALS['lang']['label_invisibles'].'</option>'."\n";

	/// PUBLIES
	echo '<option value="pub"';
	echo ($filtre == 'pub') ? ' selected="selected"' : '';
	echo '>'.$GLOBALS['lang']['label_publies'].'</option>'."\n";

	/// PAR DATE
	if (!empty($tableau_mois)) {
		echo '<optgroup label="'.$GLOBALS['lang']['label_date'].'">'."\n";
		foreach ($tableau_mois as $mois => $label) {
			echo '<option value="' . htmlentities($mois) . '"';
			echo (substr($filtre, 0, 6) == $mois) ? ' selected="selected"' : '';
			echo '>'.$label.'</option>'."\n";
		}
		echo '</optgroup>'."\n";
	}

	/// PAR AUTEUR S'IL S'AGIT DES COMMENTAIRES OU DE LIENS
	if (!empty($tab_auteur)) {
		echo '<optgroup label="'.$GLOBALS['lang']['pref_auteur'].'">'."\n";
		foreach ($tab_auteur as $nom) {
			if (!empty($nom['nb']) ) {
				echo '<option value="auteur.'.$nom['bt_author'].'"';
				echo ($filtre == 'auteur.'.$nom['bt_author']) ? ' selected="selected"' : '';
				echo '>'.$nom['bt_author'].' ('.$nom['nb'].')'.'</option>'."\n";
			}
		}
		echo '</optgroup>'."\n";
	}

	/// PAR TYPE S'IL S'AGIT DES FICHIERS
	if (!empty($liste_des_types)) {
		echo '<optgroup label="'.'Type'.'">'."\n";
		foreach ($liste_des_types as $type => $nb) {
			if (!empty($type) ) {
				echo '<option value="type.'.$type.'"';
				echo ($filtre == 'type.'.$type) ? ' selected="selected"' : '';
				echo '>'.$type.' ('.$nb.')'.'</option>'."\n";
			}
		}
		echo '</optgroup>'."\n";
	}

	///PAR TAGS POUR LES LIENS & ARTICLES
	if (!empty($tab_tags)) {
		echo '<optgroup label="'.'Tags'.'">'."\n";
		foreach ($tab_tags as $tag) {
			echo '<option value="tag.'.$tag['tag'].'"';
			echo ($filtre == 'tag.'.$tag['tag']) ? ' selected="selected"' : '';
			echo '>'.$tag['tag'].' ('.$tag['nb'].')</option>'."\n";
		}
		echo '</optgroup>'."\n";
	}
	echo '</select> '."\n\n";
}




/// Formulaire pour ajouter un lien dans Links côté Admin
function afficher_form_link($step, $erreurs, $editlink='') {
	if ($erreurs) {
		echo erreurs($erreurs);
	}
	$form = '';
	if ($step == 1) { // postage de l'URL : un champ affiché en GET
		$form .= '<form method="get" class="bordered-formbloc" id="post-new-lien" action="'.'links.php'.'">'."\n"; // not using PHP_SELF because of if the form is loaded on index.php
		$form .= '<fieldset>'."\n";
		$form .= "\t".'<input type="text" name="url" value="" size="70" placeholder="http://www.example.com/" class="text" autofocus />'."\n";
		$form .= "\t".'<p class="centrer">'."\n";
		$form .= "\t\t".'<input type="submit" value="'.$GLOBALS['lang']['envoyer'].'" class="submit blue-square" />'."\n";
		$form .= "\t".'</p>';
		$form .= '</fieldset>'."\n";
		$form .= '</form>'."\n\n";

	} elseif ($step == 2) { // Form de l'URL, avec titre, description, en POST cette fois, et qu'il faut vérifier avant de stoquer dans la BDD.
		$form .= '<form method="post" onsubmit="return moveTag();" class="bordered-formbloc" id="post-lien" action="'.$_SERVER['PHP_SELF'].'">'."\n";
		$form .= '<fieldset>'."\n";

		$url = $_GET['url'];
		$type = 'url';
		$title = htmlspecialchars($url);
		$new_id = date('YmdHis');

		// URL vide : c’est une "note" et on masque le champ du lien
		if (empty($url)) {
			$type = 'note';
			$title = 'Note';
			$url = $GLOBALS['racine'].'?mode=links&amp;id='.$new_id;
			$form .= hidden_input('url', $url);
			$form .= hidden_input('type', 'note');
			$form .= "\t".'<div class="wrap-fields wrap-fields-note">'."\n";
		// URL non vide
		} else {
			// Test du type de fichier
			$rep_hdr = get_headers($url, 1);
			$cnt_type = (isset($rep_hdr['Content-Type'])) ? $rep_hdr['Content-Type'] : 'text/';
			$cnt_type = (is_array($cnt_type)) ? $cnt_type[0] : $cnt_type;

			// lien est une image
			if (strpos($cnt_type, 'image/') === 0) {
				$title = $GLOBALS['lang']['label_image'];
				if (list($width, $height) = @getimagesize($url)) {
					$fdata = $url;
					$type = 'image';
					$title .= ' - '.$width.'x'.$height.'px ';
				}
			}

			// lien est un fichier non textuel
			elseif (strpos($cnt_type, 'text/') !== 0) {
				if ($GLOBALS['dl_link_to_files'] == 2) {
					$type = 'file';
				}
			}

			// URL est un lien normal
			elseif ($ext_file = get_external_file($url, 15) ) {
				$charset = 'utf-8';
				// cherche le charset dans les headers
				if (preg_match('#charset=(.*);?#', $cnt_type, $hdr_charset) and !empty($hdr_charset[1])) {
					$charset = $hdr_charset[1];
				}
				// sinon cherche le charset dans le code HTML.
				else {
					// cherche la balise "meta charset"
					preg_match('#<meta .*charset=([^\s]*)\s+/?>#Usi', $ext_file, $meta);
					$charset = (!empty($meta[1])) ? strtolower(str_replace(array("'", '"'), array('', ''), $meta[1]) ) : 'utf-8';
				}
				// récupère le titre, dans le tableau $titles, rempli par preg_match()
				preg_match('#<title>(.*)</title>#Usi', $ext_file, $titles);

				if (!empty($titles[1])) {
					$html_title = trim($titles[1]);
					// ré-encode le titre en UTF-8 en fonction de son encodage.
					$title = html_entity_decode( (($charset == 'iso-8859-1') ? utf8_encode($html_title) : $html_title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
					$title = htmlspecialchars($title);
				// si pas de titre : on utilise l’URL.
				} else {
					$title = htmlspecialchars($url);
				}
			}

			$form .= "\t".'<div class="wrap-fields wrap-fields-'.$type.'">'."\n";
			$form .= "\t".'<input type="text" name="url" value="'.htmlspecialchars($url).'" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_url']).'" size="50" class="text readonly-like" />'."\n";
			$form .= hidden_input('type', 'link');
		}

		$link = array('title' => $title, 'url' => htmlspecialchars($url));
		$form .= "\t".'<input type="text" name="title" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_titre']).'" required="" value="'.$link['title'].'" size="50" class="text" autofocus />'."\n";
		if ($type == 'image') { // si le lien est une image, on ajoute une miniature de l’image;
			$form .= "\t".'<span id="description-box" class="space-left">'."\n";
			$form .= "\t\t".'<img src="'.$fdata.'" alt="img" class="preview-img" />';
		} else {
			$form .= "\t".'<span id="description-box">'."\n";
		}
		$form .= "\t\t".'<textarea class="text" name="description" cols="40" rows="7" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_description']).'"></textarea>'."\n";
		$form .= "\t".'</span>'."\n";

		$form .= form_categories_links('links', '');

		$form .= "\t".'<input list="htmlListTags" type="text" class="text" id="type_tags" name="tags" onkeydown="chkHit(event);" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'"/>'."\n";
		$form .= "\t".'<input type="hidden" id="categories" name="categories" value="" />'."\n";

		$form .= "\t".'<label>'.$GLOBALS['lang']['label_lien_priv'].'<input type="checkbox" name="statut" />'.'</label>';
		if ( ($type == 'image' or $type == 'file') and $GLOBALS['dl_link_to_files'] == 2 ) {
			$form .= "\t".'<label>'.$GLOBALS['lang']['label_dl_fichier'].'<input type="checkbox" name="add_to_files" /></label>'."\n";
		}
		$form .= "\t".'<p class="centrer">'."\n";
		$form .= "\t\t".'<input class="submit blue-square" type="submit" name="enregistrer" id="valid-link" value="'.$GLOBALS['lang']['envoyer'].'" />'."\n";
		$form .= "\t".'</p>'."\n";
		$form .= hidden_input('_verif_envoi', '1');
		$form .= hidden_input('bt_id', $new_id);
		$form .= hidden_input('bt_author', $GLOBALS['auteur']);
		$form .= hidden_input('token', new_token());
		$form .= '</div>'."\n";
		$form .= '</fieldset>'."\n";
		$form .= '</form>'."\n\n";

	} elseif ($step == 'edit') { // Form pour l'édition d'un lien : les champs sont remplis avec le "wiki_content" et il y a les boutons suppr/activer en plus.
		$form = '<form method="post" onsubmit="return moveTag();" class="bordered-formbloc" id="post-lien" action="'.$_SERVER['PHP_SELF'].'?id='.$editlink['bt_id'].'">'."\n";
		$form .= "\t".'<fieldset class="pref">'."\n";
		$form .= "\t".'<div class="wrap-fields wrap-fields-links">'."\n";
		$form .= "\t".'<input type="text" name="url" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_url']).'" required="" value="'.$editlink['bt_link'].'" size="70" class="text readonly-like" /></label>'."\n";
		$form .= "\t".'<input type="text" name="title" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_titre']).'" required="" value="'.$editlink['bt_title'].'" size="70" class="text" autofocus /></label>'."\n";
		$form .= "\t".'<span id="description-box">'."\n";
		$form .= "\t\t".'<textarea class="description text" name="description" cols="70" rows="7" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_description']).'" >'.$editlink['bt_wiki_content'].'</textarea>'."\n";
		$form .= "\t".'</span>'."\n";
		$form .= form_categories_links('links', $editlink['bt_tags']);
		$form .= "\t".'<input list="htmlListTags" type="text" class="text" id="type_tags" name="tags" onkeydown="chkHit(event);" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'"/>'."\n";
		$form .= "\t".'<input type="hidden" id="categories" name="categories" value="" />'."\n";
		$form .= "\t".'<label>'.$GLOBALS['lang']['label_lien_priv'].'<input type="checkbox" name="statut" '.(($editlink['bt_statut'] == 0) ? 'checked ' : '').'/>'.'</label>'."\n";
		$form .= "\t".'<p class="centrer">'."\n";
		$form .= "\t".'<input class="submit blue-square" type="submit" name="editer" value="'.$GLOBALS['lang']['envoyer'].'" />'."\n";
		$form .= "\t".'<input class="submit red-square" type="submit" name="supprimer" value="'.$GLOBALS['lang']['supprimer'].'" onclick="return window.confirm(\''.$GLOBALS['lang']['question_suppr_article'].'\')" />'."\n";
		$form .= "\t".'</p>'."\n";
		$form .= hidden_input('ID', $editlink['ID']);
		$form .= hidden_input('bt_id', $editlink['bt_id']);
		$form .= hidden_input('bt_author', $editlink['bt_author']);
		$form .= hidden_input('_verif_envoi', '1');
		$form .= hidden_input('is_it_edit', 'yes');
		$form .= hidden_input('token', new_token());
		$form .= hidden_input('type', $editlink['bt_type']);
		$form .= "\t".'</div>'."\n";
		$form .= "\t".'</fieldset>'."\n";
		$form .= '</form>'."\n\n";
	}
	return $form;
}

/// formulaires BILLET //////////
function afficher_form_billet($article, $erreurs) {
	function s_color($color) {
		return '<button type="button" onclick="insertTag(\'[color='.$color.']\',\'[/color]\',\'contenu\');"><span style="background:'.$color.';"></span></button>';
	}
	function s_size($size) {
		return '<button type="button" onclick="insertTag(\'[size='.$size.']\',\'[/size]\',\'contenu\');"><span style="font-size:'.$size.'pt;">'.$size.'. Ipsum</span></button>';
	}
	function s_u($char) {
		return '<button type="button" onclick="insertChar(\''.$char.'\', \'contenu\');"><span>'.$char.'</span></button>';
	}

	if ($article != '') {
		$defaut_jour = $article['jour'];
		$defaut_mois = $article['mois'];
		$defaut_annee = $article['annee'];
		$defaut_heure = $article['heure'];
		$defaut_minutes = $article['minutes'];
		$defaut_secondes = $article['secondes'];
		$titredefaut = $article['bt_title'];
		// abstract : s’il est vide, il est regénéré à l’affichage, mais reste vide dans la BDD)
		$chapodefaut = get_entry($GLOBALS['db_handle'], 'articles', 'bt_abstract', $article['bt_id'], 'return');
		$notesdefaut = $article['bt_notes'];
		$categoriesdefaut = $article['bt_categories'];
		$contenudefaut = htmlspecialchars($article['bt_wiki_content']);
		$motsclesdefaut = $article['bt_keywords'];
		$statutdefaut = $article['bt_statut'];
		$allowcommentdefaut = $article['bt_allow_comments'];
	} else {
		$defaut_jour = date('d');
		$defaut_mois = date('m');
		$defaut_annee = date('Y');
		$defaut_heure = date('H');
		$defaut_minutes = date('i');
		$defaut_secondes = date('s');
		$chapodefaut = '';
		$contenudefaut = '';
		$motsclesdefaut = '';
		$categoriesdefaut = '';
		$titredefaut = '';
		$notesdefaut = '';
		$statutdefaut = '1';
		$allowcommentdefaut = '1';
	}
	if ($erreurs) {
		echo erreurs($erreurs);
	}
	if (isset($article['bt_id'])) {
		echo '<form id="form-ecrire" method="post" onsubmit="return moveTag();" action="'.$_SERVER['PHP_SELF'].'?post_id='.$article['bt_id'].'" >'."\n";
	} else {
		echo '<form id="form-ecrire" method="post" onsubmit="return moveTag();" action="'.$_SERVER['PHP_SELF'].'" >'."\n";
	}
		echo '<input id="titre" name="titre" type="text" size="50" value="'.$titredefaut.'" required="" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_titre']).'" tabindex="30" class="text" spellcheck="true" />'."\n" ;
	echo '<div id="chapo_note">'."\n";
	echo '<div id="blocchapo">'."\n";
		echo '<textarea id="chapo" name="chapo" rows="5" cols="60" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_chapo']).'" tabindex="35" class="text" >'.$chapodefaut.'</textarea>'."\n" ;
	echo '</div>'."\n";
	echo '<div id="blocnote">'."\n";
		echo '<textarea id="notes" name="notes" rows="5" cols="30" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_notes']).'" tabindex="40" class="text" >'.$notesdefaut.'</textarea>'."\n" ;
	echo '</div>'."\n";
	echo '</div>'."\n";

	echo '<p class="formatbut">'."\n";
	echo "\t".'<button id="button01" class="but" type="button" title="'.$GLOBALS['lang']['bouton-gras'].'" onclick="insertTag(\'[b]\',\'[/b]\',\'contenu\');"><span></span></button>'."\n";
	echo "\t".'<button id="button02" class="but" type="button" title="'.$GLOBALS['lang']['bouton-ital'].'" onclick="insertTag(\'[i]\',\'[/i]\',\'contenu\');"><span></span></button>'."\n";
	echo "\t".'<button id="button03" class="but" type="button" title="'.$GLOBALS['lang']['bouton-soul'].'" onclick="insertTag(\'[u]\',\'[/u]\',\'contenu\');"><span></span></button>'."\n";
	echo "\t".'<button id="button04" class="but" type="button" title="'.$GLOBALS['lang']['bouton-barr'].'" onclick="insertTag(\'[s]\',\'[/s]\',\'contenu\');"><span></span></button>'."\n";

	echo "\t".'<span class="spacer"></span>'."\n";
	// bouton des couleurs
	echo "\t".'<span id="button13" class="but but-dropdown" title=""><span></span><span class="list list-color">'
			.s_color('black').s_color('gray').s_color('silver').s_color('white')
			.s_color('blue').s_color('green').s_color('red').s_color('yellow')
			.s_color('fuchsia').s_color('lime').s_color('aqua').s_color('maroon')
			.s_color('purple').s_color('navy').s_color('teal').s_color('olive')
			.s_color('#ff7000').s_color('#ff9aff').s_color('#a0f7ff').s_color('#ffd700')
			.'</span></span>'."\n";

	// boutons de la taille de caractère
	echo "\t".'<span id="button14" class="but but-dropdown" title=""><span></span><span class="list list-size">'
			.s_size('9').s_size('12').s_size('16').s_size('20')
			.'</span></span>'."\n";

	// quelques caractères unicode
	echo "\t".'<span id="button15" class="but but-dropdown" title=""><span></span><span class="list list-spechr">'
			.s_u('æ').s_u('Æ').s_u('œ').s_u('Œ').s_u('é').s_u('É').s_u('è').s_u('È').s_u('ç').s_u('Ç').s_u('ù').s_u('Ù').s_u('à').s_u('À').s_u('ö').s_u('Ö')
			.s_u('…').s_u('«').s_u('»').s_u('±').s_u('≠').s_u('×').s_u('÷').s_u('ß').s_u('®').s_u('©').s_u('↓').s_u('↑').s_u('←').s_u('→').s_u('ø').s_u('Ø')
			.s_u('☠').s_u('☣').s_u('☢').s_u('☮').s_u('★').s_u('☯').s_u('☑').s_u('☒').s_u('☐').s_u('♫').s_u('♬').s_u('♪').s_u('♣').s_u('♠').s_u('♦').s_u('❤')
			.s_u('♂').s_u('♀').s_u('☹').s_u('☺').s_u('☻').s_u('♲').s_u('⚐').s_u('⚠').s_u('☂').s_u('√').s_u('∑').s_u('λ').s_u('π').s_u('Ω').s_u('№').s_u('∞')
			.'</span></span>'."\n";

	echo "\t".'<span class="spacer"></span>'."\n";
	echo "\t".'<button id="button05" class="but" type="button" title="'.$GLOBALS['lang']['bouton-left'].'" onclick="insertTag(\'[left]\',\'[/left]\',\'contenu\');"><span></span></button>'."\n";
	echo "\t".'<button id="button06" class="but" type="button" title="'.$GLOBALS['lang']['bouton-center'].'" onclick="insertTag(\'[center]\',\'[/center]\',\'contenu\');"><span></span></button>'."\n";
	echo "\t".'<button id="button07" class="but" type="button" title="'.$GLOBALS['lang']['bouton-right'].'" onclick="insertTag(\'[right]\',\'[/right]\',\'contenu\');"><span></span></button>'."\n";
	echo "\t".'<button id="button08" class="but" type="button" title="'.$GLOBALS['lang']['bouton-justify'].'" onclick="insertTag(\'[justify]\',\'[/justify]\',\'contenu\');"><span></span></button>'."\n";

	echo "\t".'<span class="spacer"></span>'."\n";
	echo "\t".'<button id="button09" class="but" type="button" title="'.$GLOBALS['lang']['bouton-lien'].'" onclick="insertTag(\'[\',\'|http://]\',\'contenu\');"><span></span></button>'."\n";
	echo "\t".'<button id="button10" class="but" type="button" title="'.$GLOBALS['lang']['bouton-cita'].'" onclick="insertTag(\'[quote]\',\'[/quote]\',\'contenu\');"><span></span></button>'."\n";
	echo "\t".'<button id="button11" class="but" type="button" title="'.$GLOBALS['lang']['bouton-imag'].'" onclick="insertTag(\'[img]\',\'|alt[/img]\',\'contenu\');"><span></span></button>'."\n";
	echo "\t".'<button id="button12" class="but" type="button" title="'.$GLOBALS['lang']['bouton-code'].'" onclick="insertTag(\'[code]\',\'[/code]\',\'contenu\');"><span></span></button>'."\n";

	echo "\t".'<span class="spacer"></span>'."\n";
	echo "\t".'<button id="button16" class="but" type="button" title="'.$GLOBALS['lang']['bouton-liul'].'" onclick="insertChar(\'\n\n** element 1\n** element 2\n\',\'contenu\');"><span></span></button>'."\n";
	echo "\t".'<button id="button17" class="but" type="button" title="'.$GLOBALS['lang']['bouton-liol'].'" onclick="insertChar(\'\n\n## element 1\n## element 2\n\',\'contenu\');"><span></span></button>'."\n";

	echo '</p>';

	echo '<textarea id="contenu" name="contenu" rows="20" cols="60" required="" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_contenu']).'" tabindex="55" class="text">'.$contenudefaut.'</textarea>'."\n" ;

	echo form_categories_links('articles', $categoriesdefaut);
	echo "\t".'<input list="htmlListTags" type="text" class="text" id="type_tags" name="tags" onkeydown="chkHit(event);" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'" tabindex="65"/>'."\n";
	echo "\t".'<input type="hidden" id="categories" name="categories" value="" />'."\n";

	if ($GLOBALS['automatic_keywords'] == '0') {
		echo '<div><input id="mots_cles" name="mots_cles" type="text" size="50" value="'.$motsclesdefaut.'" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_motscle']).'" tabindex="67" class="text" /></div>'."\n";
	}

	echo '<div id="date-and-opts">'."\n";
	echo '<div id="date">'."\n";
		echo '<span id="formdate">'."\n";
			form_annee($defaut_annee);
			form_mois($defaut_mois);
			form_jour($defaut_jour);
		echo '</span>'."\n\n";
		echo '<span id="formheure">';
			form_heure($defaut_heure, $defaut_minutes, $defaut_secondes);
		echo '</span>'."\n";
		echo '</div>'."\n";
		echo '<div id="opts">'."\n";
			echo '<span id="formstatut">'."\n";
				form_statut($statutdefaut);
			echo '</span>'."\n";
			echo '<span id="formallowcomment">'."\n";
				form_allow_comment($allowcommentdefaut);
			echo '</span>'."\n";
		echo '</div>'."\n";

    echo '</div>'."\n";
	echo '<p class="centrer">'."\n";
	echo '<input class="submit blue-square" type="submit" name="enregistrer" onclick="contenuLoad=document.getElementById(\'contenu\').value" value="'.$GLOBALS['lang']['envoyer'].'" tabindex="70" />'."\n";
	if ($article) {
		echo '<input class="submit red-square" type="submit" name="supprimer" value="'.$GLOBALS['lang']['supprimer'].'" onclick="contenuLoad = document.getElementById(\'contenu\').value; return window.confirm(\''.$GLOBALS['lang']['question_suppr_article'].'\')" />'."\n";
		echo hidden_input('article_id', $article['bt_id']);
		echo hidden_input('article_date', $article['bt_date']);
		echo hidden_input('ID', $article['ID']);
	}
	echo '</p>'."\n";
	echo hidden_input('_verif_envoi', '1');
	echo hidden_input('token', new_token());

	echo '</form>'."\n";
}
// FIN AFFICHER_FORM_BILLET

// ELEMENTS FORM ECRIRE //////////

function form_jour($jour_affiche) {
	$jours = array(
		"01" => '1',  "02" => '2',  "03" => '3',  "04" => '4',  "05" => '5',  "06" => '6',  "07" => '7',
		"08" => '8',  "09" => '9',  "10" => '10', "11" => '11', "12" => '12', "13" => '13', "14" => '14',
		"15" => '15', "16" => '16', "17" => '17', "18" => '18', "19" => '19', "20" => '20', "21" => '21',
		"22" => '22', "23" => '23', "24" => '24', "25" => '25', "26" => '26', "27" => '27', "28" => '28',
		"29" => '29', "30" => '30', "31" => '31'
	);
	echo '<select name="jour">'."\n";
	foreach ($jours as $option => $label) {
		echo '<option value="'.htmlentities($option).'"';
		echo ($jour_affiche == $option) ? ' selected="selected"' : '';
		echo '>'.htmlentities($label).'</option>'."\n";
	}
	echo '</select>'."\n";
}

function form_mois($mois_affiche) {
	$mois = array(
		"01" => $GLOBALS['lang']['janvier'],	"02" => $GLOBALS['lang']['fevrier'],
		"03" => $GLOBALS['lang']['mars'],		"04" => $GLOBALS['lang']['avril'],
		"05" => $GLOBALS['lang']['mai'],			"06" => $GLOBALS['lang']['juin'],
		"07" => $GLOBALS['lang']['juillet'],	"08" => $GLOBALS['lang']['aout'],
		"09" => $GLOBALS['lang']['septembre'],	"10" => $GLOBALS['lang']['octobre'],
		"11" => $GLOBALS['lang']['novembre'],	"12" => $GLOBALS['lang']['decembre']
	);
	echo '<select name="mois">'."\n" ;
	foreach ($mois as $option => $label) {
		echo '<option value="'.htmlentities($option).'"';
		echo ($mois_affiche == $option) ? ' selected="selected"' : '';
		echo '>'.$label.'</option>'."\n";
	}
	echo '</select>'."\n";
}

function form_annee($annee_affiche) {
	$annees = array();
	for ($annee = date('Y') -3, $annee_max = date('Y') +3; $annee <= $annee_max; $annee++) {
		$annees[$annee] = $annee;
	}
	echo '<select name="annee">'."\n" ;
	foreach ($annees as $option => $label) {
		echo '<option value="'.htmlentities($option).'"';
		echo ($annee_affiche == $option) ? ' selected="selected"' : '';
		echo '>'.htmlentities($label).'</option>'."\n";
	}
	echo '</select>'."\n";
}

function form_heure($heureaffiche, $minutesaffiche, $secondesaffiche) {
	echo '<input name="heure" type="text" size="2" maxlength="2" value="'.$heureaffiche.'" required="" class="text" /> : ';
	echo '<input name="minutes" type="text" size="2" maxlength="2" value="'.$minutesaffiche.'" required="" class="text" /> : ' ;
	echo '<input name="secondes" type="text" size="2" maxlength="2" value="'.$secondesaffiche.'" required="" class="text" />' ;
}

function form_statut($etat) {
	$choix= array(
		'1' => $GLOBALS['lang']['label_publie'],
		'0' => $GLOBALS['lang']['label_invisible']
	);
	echo form_select('statut', $choix, $etat, $GLOBALS['lang']['label_dp_etat']);
}

function form_allow_comment($etat) {
	$choix= array(
		'1' => $GLOBALS['lang']['ouverts'],
		'0' => $GLOBALS['lang']['fermes']
	);
	// Compatibilite version sans
	if ($etat == '') {
		$etat= '1';
	}
	echo form_select('allowcomment', $choix, $etat, $GLOBALS['lang']['label_dp_commentaires']);
}

function form_categories_links($where, $tags_post) {
	$tags = list_all_tags($where);
	$html = '';
	if (!empty($tags)) {
		$html = '<datalist id="htmlListTags">'."\n";
		foreach ($tags as $i => $tag) $html .= "\t".'<option value="'.addslashes($tag['tag']).'">'."\n";
		$html .= '</datalist>'."\n";
	}
	$html .= '<ul id="selected">'."\n";
	$list_tags = explode(',', $tags_post);
	foreach ($list_tags as $mytag => $mtag) {
		if (!empty($mtag)) {
			$html .= "\t".'<li><span>'.trim($mtag).'</span><a href="javascript:void(0)" onclick="removeTag(this.parentNode)">×</a></li>'."\n";
		}
	}
	$html .= '</ul>'."\n";
	return $html;
}

