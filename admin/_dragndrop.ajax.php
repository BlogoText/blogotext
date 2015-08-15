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

/*
	This file is called by the drag'n'drop script. It is an underground working script,
	It is not intended to be called directly in your browser.

*/

$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();
$begin = microtime(TRUE);
$liste_fileid = array();
$GLOBALS['liste_fichiers'] = open_serialzd_file($GLOBALS['fichier_liste_fichiers']);

foreach ($GLOBALS['liste_fichiers'] as $key => $file) {
	$liste_fileid[] = $file['bt_id'];
}


if (isset($_FILES['fichier'])) {
	$time = time();
	$fichier = init_post_fichier();

	// avoid ID collisions
	while (in_array($fichier['bt_id'], $liste_fileid)) {
		$time--;
		$fichier['bt_id'] = date('YmdHis', $time);
	}
	$erreurs = valider_form_fichier($fichier);

	// on success
	if (empty($erreurs)) {
		$new_fichier = bdd_fichier($fichier, 'ajout-nouveau', 'upload', $_FILES['fichier']);
		$fichier = (is_null($new_fichier)) ? $fichier : $new_fichier;
		echo '{';
			echo '"url": "fichiers.php?file_id='.$fichier['bt_id'].'&amp;edit",';
			echo '"status": "success",';
			echo '"token": "'.new_token().'"';
		echo '}';
		exit;
	}
	// on error
	else {
		echo '{';
			echo '"url": "0",';
			echo '"status": "failure",';
			echo '"token": "0"';
		echo '}';
		exit;
	}
}
// si fichier n’est pas envoyé (limite JS sur la taille, par exemple)
// mais que le Token est bon, on continue les autres fichiers : ce
// serait dommage de bloquer tous les fichiers pour un fichier mauvais
elseif ( isset($_POST['token']) and check_token($_POST['token']) ) {
	echo '{';
		echo '"url": "0",';
		echo '"status": "failure",';
		echo '"token": "'.new_token().'"';
	echo '}';

}
// problem with file AND token : abord, Captain, my Captain! !
else {
	echo '{';
		echo '"url": "0",';
		echo '"status": "failure",';
		echo '"token": "0"';
	echo '}';
}	
