<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden <ti-mo@myopera.com>
#
# BlogoText is free software, you can redistribute it under the terms of the
# Creative Commons Attribution-NonCommercial 2.0 France Licence
#
# Also, any distributors of non-official releases MUST warn the final user of it, by any visible way before the download.
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
$GLOBALS['liste_fichiers'] = open_file_db_fichiers($GLOBALS['fichier_liste_fichiers']);

foreach ($GLOBALS['liste_fichiers'] as $key => $file) {
	$liste_fileid[] = $file['bt_id'];
}
$time = time();


if (isset($_FILES['fichier'])) {
	$fichier = init_post_fichier();

	// avoid ID collisions
	while (in_array($fichier['bt_id'], $liste_fileid)) {
		$time--;
		$fichier['bt_id'] = date('YmdHis', $time);
	}
	$erreurs = valider_form_fichier($fichier);
	if (empty($erreurs)) {
		$new_fichier = bdd_fichier($fichier, 'ajout-nouveau', 'upload', $_FILES['fichier']);
		$fichier = (is_null($new_fichier)) ? $fichier : $new_fichier;
		echo '
		<div class="success">
			<p>
				Your file: '.$fichier['bt_filename'].' has been successfully received. (<a class="lien lien-edit" href="fichiers.php?file_id='.$fichier['bt_id'].'&amp;edit">Lien</a>)<br/>
				Type: '.$fichier['bt_type'].'<br/>
				Size: '.taille_formate($fichier['bt_filesize']).'
				<button class="nodisplay" id="token" value="'.new_token().'"></button>
			</p>
		</div>';

	}

	else {
		echo '<div class="failure">'.erreurs($erreurs).'</div>'."\n";
	}
exit;
} else { echo '<div class="failure">No file</div>'."\n"; }

