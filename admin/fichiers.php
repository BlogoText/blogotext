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

$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();
$begin = microtime(TRUE);

$fichier = array();
$GLOBALS['liste_fichiers'] = open_serialzd_file($GLOBALS['fichier_liste_fichiers']);

// recherche / tri
if ( !empty($_GET['filtre']) ) {
	// for "type" the requests is "type.$search" : here we split the type of search and what we search.
	$type = substr($_GET['filtre'], 0, -strlen(strstr($_GET['filtre'], '.')));
	$search = htmlspecialchars(ltrim(strstr($_GET['filtre'], '.'), '.'));

	// selon date
	if ( preg_match('#^\d{6}(\d{1,8})?$#', $_GET['filtre']) ) {
		$fichiers = liste_base_files('date', $_GET['filtre'], '');
	// brouillons
	} elseif ($_GET['filtre'] == 'draft') {
		$fichiers = liste_base_files('statut', '0', '');
	// publiés
	} elseif ($_GET['filtre'] == 'pub') {
		$fichiers = liste_base_files('statut', '1', '');
	// liste selon type de fichier
	} elseif ($type == 'type' and $search != '') {
		$fichiers = liste_base_files('type', $search, '');
	} else {
		$fichiers = $GLOBALS['liste_fichiers'];
	}
// recheche par mot clé
} elseif (!empty($_GET['q'])) {
	$fichiers = liste_base_files('recherche', htmlspecialchars(urldecode($_GET['q'])), '');
// par extension
} elseif (!empty($_GET['extension'])) {
	$fichiers = liste_base_files('extension', htmlspecialchars($_GET['extension']), '');
// par fichier unique (id)
} elseif (isset($_GET['file_id']) and preg_match('/\d{14}/',($_GET['file_id']))) {
	foreach ($GLOBALS['liste_fichiers'] as $fich) {
		if ($fich['bt_id'] == $_GET['file_id']) {
			$fichier = $fich;
			break;
		}
	}
	if (!empty($fichier)) {
		$fichiers[$_GET['file_id']] = $fichier;
	}
// aucun filtre, les affiche tous
} else {
	$fichiers = $GLOBALS['liste_fichiers'];
}

// traitement d’une action sur le fichier
$erreurs = array();
if (isset($_POST['_verif_envoi'])) {
	$fichier = init_post_fichier();
	$erreurs = valider_form_fichier($fichier);
	if (empty($erreurs)) {
		traiter_form_fichier($fichier);
	}
}

afficher_top($GLOBALS['lang']['titre_fichier']);

echo '<div id="top">'."\n";
afficher_msg($GLOBALS['lang']['titre_fichier']);
echo moteur_recherche($GLOBALS['lang']['search_in_files']);
afficher_menu(pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME));
echo '</div>'."\n";

echo '<div id="axe">'."\n";


// SUBNAV
echo '<div id="subnav">'."\n";
	// Affichage formulaire filtrage liens
	if (isset($_GET['filtre'])) {
		afficher_form_filtre('fichiers', htmlspecialchars($_GET['filtre']));
	} else {
		afficher_form_filtre('fichiers', '');
	}
echo '</div>'."\n";


// SUBNAV
echo '<div id="page">'."\n";


// vérifie que les fichiers de la liste sont bien présents sur le disque dur
$real_fichiers = array();
if (!empty($fichiers)) {
	foreach ($fichiers as $i => $file) {
		$dossier = ($file['bt_type'] == 'image') ? $GLOBALS['dossier_images'] : $GLOBALS['dossier_fichiers'];
		if (is_file($GLOBALS['BT_ROOT_PATH'].'/'.$dossier.'/'.$file['bt_filename']) and ($file['bt_filename'] != 'index.html') ) {
			$real_fichiers[] = $file;
		}
	}
}

// ajout d'un nouveau fichier : affichage formulaire, pas des anciens.
if ( isset($_GET['ajout']) ) {
	afficher_form_fichier('', '', 'fichier');
}
// édition d'un fichier
elseif ( isset($_GET['file_id']) ) {
	afficher_form_fichier($erreurs, $real_fichiers, 'fichier');
}
// affichage de la liste des fichiers.
else {
	afficher_form_fichier($erreurs, '', 'fichier');

	// séparation des images des autres types de fichiers
	$fichiers = array(); $images = array();
	foreach ($real_fichiers as $file) {
		if ($file['bt_type'] == 'image') {
			$images[] = $file;
		}
		else {
			$fichiers[] = $file;
		}
	}

	afficher_liste_images($images);
	afficher_liste_fichiers($fichiers);
}

echo '<script type="text/javascript">'."\n";
echo js_drag_n_drop_handle(0);
echo js_folder_sort_img(0);
echo js_show_slideshow(0);
echo "\n".'</script>'."\n";

footer('', $begin);
?>
