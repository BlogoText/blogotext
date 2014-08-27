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


/*
 * Notez que la liste des fichiers n’est pas intrégrée dans la base de données SQLITE.
 * Ceci pour une raison simple : les fichiers (contrairement aux articles et aux commentaires)
 *  sont indépendants. Utiliser une BDD pour les lister est beaucoup moins rapide
 *  qu’utiliser un fichier txt normal.
 * Pour le stockage, j’utilise un tableau PHP que j’enregistre directement dans un fichier :
 *  base64_encode(serialize($tableau)) # pompée sur Shaarli, by Sebsauvage.
 * 
 */


/* -----------------------------------------------------------------
   FONCTIONS POUR GESTION DES FICHIER, (ou images)
   ---------------------------------------------------------------*/

/*
   À partir du chemin vers une image, trouve la miniature correspondante.
   retourne le chemin de la miniature.
   le nom d’une image est " image.ext ", celui de la miniature sera " image-thb.ext "
*/
function chemin_thb_img($filepath) {
	$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
	// prend le nom, supprime l’extension et le point, ajoute le " -thb.jpg ", puisque les miniatures de BT sont en JPG.
	$miniature = substr($filepath, 0, -(strlen($ext)+1)).'-thb.jpg'; // "+1" is for the "." between name and ext.
	return $miniature;
}

function chemin_thb_img_test($filepath) {
	$thb = chemin_thb_img($filepath);
	if (file_exists($thb)) {
		return $thb;
	} else {
		return $filepath;
	}
}


/*
	Pour les vignettes dans le mur d’images.
	Avec en entrée le tableau contenant les images, retourne le HTML + JSON du mur d’image.
	Le JSON est parsé en JS du côté navigateur pour former le mur d’images.
*/
function afficher_liste_images($images) {
	$dossier = $GLOBALS['racine'].$GLOBALS['dossier_images'];
	$dossier_relatif = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'];
	$out = ''; $i = 0;
	if (!empty($images)) {
		// liste les différents dossiers " logiques " des images.
		$lsfolder = '';
		foreach ($images as $image) {
			if (!empty($image['bt_dossier'])) {
				$lsfolder .= $image['bt_dossier'].',';
			}
		}

		$lsf_arr = explode(',', $lsfolder);
		$lsf_arr = array_map('trim', $lsf_arr);
		$lsf_uniq = array();
		// array "folder" => "nb img per folder"
		foreach ($lsf_arr as $ii) {
			$lsf_uniq[$ii] = (isset($lsf_uniq[$ii])) ? $lsf_uniq[$ii]+1 : 1;
		}
		// displays the buttons
		if (!empty($lsf_uniq)) {
			$out .= '<div class="list-buttons" id="list-folders">'."\n";
			$i = 0;
			$out .= "\t".'<button class="current" id="butId'.$i.'" onclick="folder_sort(\'\', \'butId'.$i.'\');">'.(count($images)).' '.$GLOBALS['lang']['label_images'].'</button>';
			foreach ($lsf_uniq as $fol => $nb) {
				if (empty($fol)) break; $i++;
				$out .= '<button id="butId'.$i.'" onclick="folder_sort(\''.$fol.'\', \'butId'.$i.'\');">'.$fol.' ('.$nb.')</button>';
			}
			$out .= "\n".'</div>'."\n";
		}

		// HTML of the Slider.
		$out .= '<div id="slider">'."\n";
		$out .= "\t".'<div id="slider-box">'."\n";
		$out .= "\t\t".'<div id="slider-box-cnt">'."\n";
		$out .= "\t\t\t".'<div id="slider-box-img-wrap"><a id="slider-img-a" href="#"></a><img id="slider-img" src="" alt="#"/></div>'."\n";
		$out .= "\t\t\t".'<a href="#" onclick="slideshow(\'close\'); return false;" class="slider-quit"></a>'."\n";
		$out .= "\t\t".'</div>'."\n";
		$out .= "\t\t".'<div id="slider-box-inf">'."\n";
		$out .= "\t\t\t".'<p class="slider-buttons">'."\n";
		$out .= "\t".'<a href="#" onclick="slideshow(\'first\'); return false;" class="slider-first"></a>';
		$out .= '<a href="#" onclick="slideshow(\'prev\'); return false;" class="slider-prev" id="slider-prev"></a>';
		$out .= '<a href="#" onclick="slideshow(\'next\'); return false;" class="slider-next" id="slider-next"></a>';
		$out .= '<a href="#" onclick="slideshow(\'last\'); return false;" class="slider-last"></a>'."\n";
		$out .= "\t\t\t".'</p>'."\n";
		$out .= "\t\t\t".'<ul id="slider-img-infs">'."\n";
		$out .= "\t\t\t\t".'<li></li>'."\n";
		$out .= "\t\t\t\t".'<li></li>'."\n";
		$out .= "\t\t\t\t".'<li></li>'."\n";
		$out .= "\t\t\t".'</ul>'."\n";
		$out .= "\t\t".'</div>'."\n";
		$out .= "\t".'</div>'."\n";
		$out .= '</div>'."\n";

		// send all the images their info in JSON
		$out .= '<script type="text/javascript">';
		$out .=  'var imgs = {"list": ['."\n";

		foreach ($images as $i => $im) {
			$img_src = chemin_thb_img_test($dossier_relatif.'/'.$im['bt_filename']);
			$out .=  '{"index": "'.$i.'", "filename":'."\n".'["'.$dossier.'/'.$im['bt_filename'].'", "'.$im['bt_filename'].'", "'.$img_src.'"], "id": "'.$im['bt_id'].'", "desc": "'.addslashes(preg_replace('#(\n|\r|\n\r)#', ' ', $im['bt_content'])).'", "dossier": "'.(isset($im['bt_dossier']) ? $im['bt_dossier'] : '').'", "width": "'.$im['bt_dim_w'].'", "height": "'.$im['bt_dim_h'].'"},'."\n";
		}
			$out .= ']'."\n";
		$out .= '};'."\n";

		$out .=  '</script>';

		// the images
		$out .= '<div class="image-wall">'."\n";
		$out .= '</div>'."\n";
	}
	echo $out;
}


// filepath : image to create a thumbnail from
function create_thumbnail($filepath) {
	// if GD library is not loaded by PHP, abord. Thumbnails are not required.
	if (!extension_loaded('gd')) return;
	$maxwidth = '160';
	$maxheight = '160';
	$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

	// largeur et hauteur maximale
	// Cacul des nouvelles dimensions
	list($width_orig, $height_orig) = getimagesize($filepath);
	if ($width_orig == 0 or $height_orig == 0) return;
	if ($maxwidth and ($width_orig < $height_orig)) {
		$maxwidth = ($maxheight / $height_orig) * $width_orig;
	} else {
		$maxheight = ($maxwidth / $width_orig) * $height_orig;
	}

	// open file with correct format
	$thumb = imagecreatetruecolor($maxwidth, $maxheight);
	imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
	switch ($ext) {
		case 'jpeg':
		case 'jpg': $image = imagecreatefromjpeg($filepath); break;
		case 'png': $image = imagecreatefrompng($filepath); break;
		case 'gif': $image = imagecreatefromgif($filepath); break;
		default : return;
	}

	// resize
	imagecopyresampled($thumb, $image, 0, 0, 0, 0, $maxwidth, $maxheight, $width_orig, $height_orig);
	imagedestroy($image);

	// enregistrement en JPG (meilleur compression) des miniatures
	$destination = chemin_thb_img($filepath); // construit le nom de fichier de la miniature
	imagejpeg($thumb, $destination, 70); // compression à 70%
	imagedestroy($thumb);

}

// TRAITEMENT DU FORMAULAIRE D’ENVOIE DE FICHIER (ENVOI, ÉDITION, SUPPRESSION)
function traiter_form_fichier($fichier) {
	// ajout de fichier
	if ( isset($_POST['upload']) ) {
		// par $_FILES
		if (isset($_FILES['fichier'])) {
			$new_fichier = bdd_fichier($fichier, 'ajout-nouveau', 'upload', $_FILES['fichier']);
		}
		// par $_POST d’une url
		if (isset($_POST['fichier'])) {
			$new_fichier = bdd_fichier($fichier, 'ajout-nouveau', 'download', $_POST['fichier']);
		}
		$fichier = (is_null($new_fichier)) ? $fichier : $new_fichier;
		redirection($_SERVER['PHP_SELF'].'?file_id='.$fichier['bt_id'].'&msg=confirm_fichier_ajout');
	}
	// édition d’une entrée d’un fichier
	elseif ( isset($_POST['editer']) and !isset($_GET['suppr']) ) {
		$old_file_name = $_POST['filename']; // Name can be edited too. This is old name, the new one is in $fichier[].
		bdd_fichier($fichier, 'editer-existant', '', $old_file_name);
	}
	// suppression d’un fichier
	elseif ( (isset($_POST['supprimer']) and preg_match('#^\d{14}$#', $_POST['file_id'])) ) {
		$response = bdd_fichier($fichier, 'supprimer-existant', '', $_POST['file_id']);
		if ($response == 'error_suppr_file_suppr_error') {
			redirection($_SERVER['PHP_SELF'].'?errmsg=error_fichier_suppr&what=file_suppr_error');
		} elseif ($response == 'no_such_file_on_disk') {
			redirection($_SERVER['PHP_SELF'].'?msg=error_fichier_suppr&what=but_no_such_file_on_disk2');
		} elseif ($response == 'success') {
			redirection($_SERVER['PHP_SELF'].'?msg=confirm_fichier_suppr');
		}
	}
}

// TRAITEMENT DU FORMULAIRE DE FICHIER, CÔTÉ BDD
// Retourne le $fichier de l’entrée (après avoir possiblement changé des trucs, par ex si le fichier existait déjà, l’id retourné change)
function bdd_fichier($fichier, $quoi, $comment, $sup_var) {
	if ($fichier['bt_type'] == 'image') {
		$dossier = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'];
	} else {
		$dossier = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'];
	}
	if (FALSE === creer_dossier($dossier, 0)) {
		die($GLOBALS['lang']['err_file_write']);
	}
	// ajout d’un nouveau fichier
	if ($quoi == 'ajout-nouveau') {
			$prefix = '';
			foreach($GLOBALS['liste_fichiers'] as $files) {
				if (($fichier['bt_checksum'] == $files['bt_checksum'])) {
					$fichier['bt_id'] = $files['bt_id'];
					return $fichier;
				}
			}
			while (file_exists($dossier.'/'.$prefix.$fichier['bt_filename'])) { // éviter d’écraser un fichier existant
				$prefix .= rand(0,9);
			}
			$dest = $prefix.$fichier['bt_filename'];
			$fichier['bt_filename'] = $dest; // redéfinit le nom du fichier.

			// copie du fichier physique
				// Fichier uploadé s’il y a (sinon fichier téléchargé depuis l’URL)
			if ( $comment == 'upload' ) {
				$new_file = $sup_var['tmp_name'];
				if (move_uploaded_file($new_file, $dossier.'/'. $dest) ) {
					$fichier['bt_checksum'] = sha1_file($dossier.'/'. $dest);
				} else {
					redirection($_SERVER['PHP_SELF'].'?errmsg=error_fichier_ajout_2');
					exit;
				}
			}
			// fichier spécifié par URL
			elseif ( $comment == 'download' and copy($sup_var, $dossier.'/'. $dest) ) {
				$fichier['bt_filesize'] = filesize($dossier.'/'. $dest);
			} else {
				redirection($_SERVER['PHP_SELF'].'?errmsg=error_fichier_ajout');
				exit;
			}

			// si fichier par POST ou par URL == OK, on l’ajoute à la base. (si pas OK, on serai déjà sorti par le else { redirection() }.
			if ($fichier['bt_type'] == 'image') { // miniature si c’est une image
				create_thumbnail($dossier.'/'. $dest);
				list($fichier['bt_dim_w'], $fichier['bt_dim_h']) = getimagesize($dossier.'/'. $dest);
			}
			// ajout à la base.
			$GLOBALS['liste_fichiers'][] = $fichier;
			$GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
			file_put_contents($GLOBALS['fichier_liste_fichiers'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers']))).' */');
	}

	// modification d’un fichier déjà existant
	elseif ($quoi == 'editer-existant') {
			$new_filename = $fichier['bt_filename'];
			$old_filename = $sup_var;
			if ($new_filename != $old_filename) { // nom du fichier a changé ? on déplace le fichier.
				$prefix = '';
				while (file_exists($dossier.'/'.$prefix.$new_filename)) { // évite d’avoir deux fichiers de même nom
					$prefix .= rand(0,9);
				}
				$new_filename = $prefix.$fichier['bt_filename'];
				$fichier['bt_filename'] = $new_filename; // update file name in $fichier array(), with the new prefix.

				// rename file on disk
				if (rename($dossier.'/'.$old_filename, $dossier.'/'.$new_filename)) {
					// si c’est une image : renome la miniature si elle existe, sinon la crée
					if ($fichier['bt_type'] == 'image') {
						if ( ($old_thb = chemin_thb_img_test($dossier.'/'.$old_filename)) != $dossier.'/'.$old_filename ) {
							rename($old_thb, chemin_thb_img($dossier.'/'.$new_filename));
						} else {
							create_thumbnail($dossier.'/'.$new_filename);
						}
					}
				// error rename ficher
				} else {
					redirection($_SERVER['PHP_SELF'].'?file_id='.$fichier['bt_id'].'&errmsg=error_fichier_rename');
				}
			}
			list($fichier['bt_dim_w'], $fichier['bt_dim_h']) = getimagesize($dossier.'/'.$new_filename); // reupdate filesize.

			// modifie le fichier dans la BDD des fichiers.
			foreach ($GLOBALS['liste_fichiers'] as $key => $entry) {
				if ($entry['bt_id'] == $fichier['bt_id']) { 
					$GLOBALS['liste_fichiers'][$key] = $fichier; // trouve la bonne entrée dans la base.
				}
			}

			$GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
			file_put_contents($GLOBALS['fichier_liste_fichiers'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers']))).' */'); // écrit dans le fichier, la liste
			redirection($_SERVER['PHP_SELF'].'?file_id='.$fichier['bt_id'].'&edit&msg=confirm_fichier_edit');
	}

	// suppression d’un fichier (de la BDD et du disque)
	elseif ( $quoi == 'supprimer-existant' ) {
			$id = $sup_var;
			// FIXME ajouter un test de vérification de session (security coin)
			foreach ($GLOBALS['liste_fichiers'] as $fid => $fich) {
				if ($id == $fich['bt_id']) {
					$tbl_id = $fid;
					break;
				}
			}
			// remove physical file on disk if it exists
			if (is_file($dossier.'/'.$fichier['bt_filename']) and isset($tbl_id)) {
				$liste_fichiers = scandir($dossier); // liste les fichiers réels dans le dossier
				if (in_array($fichier['bt_filename'], $liste_fichiers) and !($fichier['bt_filename'] == '..' or $fichier['bt_filename'] == '.')) {
					if (TRUE === unlink($dossier.'/'.$fichier['bt_filename'])) { // fichier physique effacé
						if ($fichier['bt_type'] == 'image') { // supprimer aussi la miniature si elle existe.
							@unlink(chemin_thb_img($dossier.'/'.$fichier['bt_filename'])); // supprime la thumbnail si y’a
						}
						unset($GLOBALS['liste_fichiers'][$tbl_id]); // efface le fichier dans la liste des fichiers.
						$GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
						file_put_contents($GLOBALS['fichier_liste_fichiers'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers']))).' */'); // enregistre la liste
						return 'success';

					} else { // erreur effacement fichier physique
						return 'error_suppr_file_suppr_error';
					}
				}
			}

			// the file in DB does not exists on disk => remove entry from DB
			if (isset($tbl_id)) {
				unset($GLOBALS['liste_fichiers'][$tbl_id]); // remove entry from files-list.
			}
			$GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
			file_put_contents($GLOBALS['fichier_liste_fichiers'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers']))).' */'); // enregistre la liste
			return 'no_such_file_on_disk';
	}
}


// POST FILE
/*
 * On post of a file (always on admin sides)
 * gets posted informations and turn them into
 * an array
 *
 */
function init_post_fichier() { //no $mode : it's always admin.
		// on edit : get file info from form
		if (isset($_POST['is_it_edit']) and $_POST['is_it_edit'] == 'yes') {
			$file_id = htmlspecialchars($_POST['file_id']);
				$filename = pathinfo(htmlspecialchars($_POST['filename']), PATHINFO_FILENAME);
				$ext = strtolower(pathinfo(htmlspecialchars($_POST['filename']), PATHINFO_EXTENSION));
				$checksum = htmlspecialchars($_POST['sha1_file']);
				$size = htmlspecialchars($_POST['filesize']);
				$type = detection_type_fichier($ext);
				$dossier = htmlspecialchars($_POST['dossier']);
		// on new post, get info from the file itself
		} else {
			$file_id = date('YmdHis');
			$dossier = htmlspecialchars($_POST['dossier']);
			// ajout de fichier par upload
			if (!empty($_FILES['fichier']) and ($_FILES['fichier']['error'] == 0)) {
				$filename = pathinfo($_FILES['fichier']['name'], PATHINFO_FILENAME);
				$ext = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
				$checksum = sha1_file($_FILES['fichier']['tmp_name']);
				$size = $_FILES['fichier']['size'];
				$type = detection_type_fichier($ext);
			// ajout par une URL d’un fichier distant
			} elseif ( !empty($_POST['fichier']) ) {
				$filename = pathinfo(parse_url($_POST['fichier'], PHP_URL_PATH), PATHINFO_FILENAME);
				$ext = strtolower(pathinfo(parse_url($_POST['fichier'], PHP_URL_PATH), PATHINFO_EXTENSION));
				$checksum = sha1_file($_POST['fichier']); // works with URL files
				$size = '';// same (even if we could use "filesize" with the URL, it would over-use data-transfer)
				$type = detection_type_fichier($ext);
			} else {
				// ERROR
				redirection($_SERVER['PHP_SELF'].'?errmsg=error_image_add');
				return FALSE;
			}
		}
		// nom du fichier : si nom donné, sinon nom du fichier inchangé
		$filename = diacritique(htmlspecialchars((!empty($_POST['nom_entree'])) ? $_POST['nom_entree'] : $filename), '' , '0').'.'.$ext;
		$statut = (isset($_POST['statut']) and $_POST['statut'] == 'on') ? '0' : '1';
		$fichier = array (
			'bt_id' => $file_id,
			'bt_type' => $type,
			'bt_fileext' => $ext,
			'bt_filesize' => $size,
			'bt_filename' => $filename, // le nom du final du fichier peut changer à la fin, si le nom est déjà pris par exemple 
			'bt_content' => stripslashes(protect_markup(clean_txt($_POST['description']))),
			'bt_wiki_content' => stripslashes(protect_markup(clean_txt($_POST['description']))),
			'bt_checksum' => $checksum,
			'bt_statut' => $statut,
			'bt_dossier' => (empty($dossier) ? 'default' : $dossier ),
		);
		return $fichier;
}



function afficher_form_fichier($erreurs, $fichiers, $what) { // ajout d’un fichier
	$max_file_size = taille_formate(return_bytes(ini_get('upload_max_filesize')));
	$max_file_nb = ini_get('max_file_uploads');
	if ($erreurs) {
		echo erreurs($erreurs);
	}
	$form = '<form id="form-image" class="bordered-formbloc" enctype="multipart/form-data" method="post" action="'.$_SERVER['PHP_SELF'].'">'."\n";
	
	if (empty($fichiers)) { // si PAS fichier donnée : formulaire nouvel envoi.
		$form .= '<fieldset class="pref" >'."\n";

		$form .= '<div id="form-dragndrop">'."\n";
			$form .= '<p class="gray-section" id="dragndrop-area" ondragenter="return false;" ondragover="return false;" ondrop="return handleDrop(event);" >'."\n";
			$form .= "\t".'<span id="dragndrop-mssg">'.$GLOBALS['lang']['img_drop_files_here']."\n";
			$form .= "\t\t".'<input name="fichier" id="fichier" type="file" required="" class="text" />'."\n";
			$form .= "\t".'</span>'."\n";
			$form .= "\t".'<span class="upload-info">'.$GLOBALS['lang']['max_file_size'].$max_file_size.'</span>'."\n";
			$form .= "\t".'<a class="specify-link" id="click-change-form" onclick="return switchUploadForm();" href="#" data-lang-url="'.$GLOBALS['lang']['img_specifier_url'].'" data-lang-file="'.$GLOBALS['lang']['img_upload_un_fichier'].'">'.$GLOBALS['lang']['img_specifier_url'].'</a>'."\n";
			$form .= '</p>'."\n";
			$form .= '<div id="count"></div>'."\n";
			$form .= '<div id="result"></div>'."\n";
		$form .= '</div>'."\n";
	
		$form .= '<div id="img-others-infos">'."\n";
			$form .= '<div class="gray-section">'."\n";
			$form .= "\t".'<label>'.$GLOBALS['lang']['label_dp_nom'].'<input type="text" id="nom_entree" name="nom_entree" placeholder="'.$GLOBALS['lang']['placeholder_nom_fichier'].'" value="" size="60" class="text" /></label>'."\n";
			$form .= "\t".'<label>'.$GLOBALS['lang']['label_dp_description'].'<textarea class="text" id="description" name="description" cols="60" rows="5" placeholder="'.$GLOBALS['lang']['placeholder_description'].'" ></textarea></label>'."\n";
			$form .= "\t".'<label>'.$GLOBALS['lang']['label_dp_dossier'].'<input type="text" id="dossier" name="dossier" placeholder="'.$GLOBALS['lang']['placeholder_folder'].'" value="" size="60" class="text" /></label>'."\n";
			$form .= "\t".'<label id="private-chkbox">'.$GLOBALS['lang']['label_file_priv'].'<input type="checkbox" id="statut" name="statut"/></label>';
			$form .= "\t".'<p class="centrer">'."\n";
			$form .= "\t".'<input class="submit blue-square" type="submit" name="upload" value="'.$GLOBALS['lang']['img_upload'].'" />'."\n";
			$form .= "\t".'</p>'."\n";
			$form .= hidden_input('token', new_token(), 'id');
			$form .= hidden_input('_verif_envoi', '1');
			$form .= '</div>'."\n";
		$form .= '</div>'."\n";

		$form .= '</fieldset>'."\n";
	}
	// si ID dans l’URL, il s’agit également du seul fichier dans le tableau fichiers, d’où le [0]
	elseif (!empty($fichiers) and isset($_GET['file_id']) and preg_match('/\d{14}/',($_GET['file_id']))) {

		if ($fichiers[0]['bt_type'] == 'image') {
			$dossier = $GLOBALS['racine'].$GLOBALS['dossier_images'];
		} else {
			$dossier = $GLOBALS['racine'].$GLOBALS['dossier_fichiers'];
		}

		$form .= '<fieldset class="edit-fichier">'."\n";

		// codes d’intégrations pour les médias
		// Video
		if ($fichiers[0]['bt_type'] == 'video') {
			$form .= '<div style="text-align: center;"><video src="'.$dossier.'/'.$fichiers[0]['bt_filename'].'" type="video/'.$fichiers[0]['bt_fileext'].'" load controls="controls"></video></div>'."\n";
		}
		// image
		if ($fichiers[0]['bt_type'] == 'image') {
			$form .= '<div style="text-align: center;"><a href="'.$dossier.'/'.$fichiers[0]['bt_filename'].'"><img src="'.$dossier.'/'.$fichiers[0]['bt_filename'].'" alt="'.$fichiers[0]['bt_filename'].'" style="max-width: 400px; width: 100%; border:1px dotted gray;" /></a></div>'."\n";
		}
		// audio
		if ($fichiers[0]['bt_type'] == 'music') {
			$form .= '<div style="text-align: center;"><audio src="'.$dossier.'/'.$fichiers[0]['bt_filename'].'" type="audio/'.$fichiers[0]['bt_fileext'].'" load controls="controls"></audio></div>'."\n";
		}

		// la partie listant les infos du fichier.
		$form .= '<ul id="fichier-meta-info">'."\n";
			$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_nom'].'</b> '.$fichiers[0]['bt_filename'].'</li>'."\n";
			$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_type'].'</b> '.$fichiers[0]['bt_type'].' (.'.$fichiers[0]['bt_fileext'].')</li>'."\n";
			if ($fichiers[0]['bt_type'] == 'image') { // si le fichier est une image, on ajout ses dimensions en pixels
				$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_dimensions'].'</b> '.$fichiers[0]['bt_dim_w'].'px × '.$fichiers[0]['bt_dim_h'].'px'.'</li>'."\n";
			}
			$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_date'].'</b>'.date_formate($fichiers[0]['bt_id']).', '.heure_formate($fichiers[0]['bt_id']).'</li>'."\n";
			$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_poids'].'</b>'.taille_formate($fichiers[0]['bt_filesize']).'</li>'."\n";
			$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_checksum'].'</b>'.$fichiers[0]['bt_checksum'].'</li>'."\n";
			$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_visibilite'].'</b>'.(($fichiers[0]['bt_statut'] == 1) ? 'Publique' : 'Privée').'</li>'."\n";
		$form .= '</ul>'."\n";

		// la partie des codes d’intégration (bbcode, etc.)
		$form .= '<p>'.ucfirst('codes d’intégration :').'</p>'."\n";
		$form .= '<p id="interg-codes">'."\n";
		$form .= '<input onfocus="SelectAllText(\'file_url\')" id="file_url" class="text" type="text" value=\''.$dossier.'/'.$fichiers[0]['bt_filename'].'\' />'."\n";
		if ($fichiers[0]['bt_type'] == 'image') { // si le fichier est une image, on ajout BBCode pour [IMG] et le code en <img/>
			$form .= '<input onfocus="SelectAllText(\'image_html\')" id="image_html" class="text" type="text" value=\'<img src="'.$dossier.'/'.$fichiers[0]['bt_filename'].'" alt="" width="'.$fichiers[0]['bt_dim_w'].'" height="'.$fichiers[0]['bt_dim_h'].'" style="max-width: 100%; height: auto;" />\' />'."\n";
			$form .= '<input onfocus="SelectAllText(\'image_bbcode_img\')" id="image_bbcode_img" class="text" type="text" value=\'[img]'.$dossier.'/'.$fichiers[0]['bt_filename'].'[/img]\' />'."\n";
			$form .= '<input onfocus="SelectAllText(\'image_bbcode_img_spl\')" id="image_bbcode_img_spl" class="text" type="text" value=\'[spoiler][img]'.$dossier.'/'.$fichiers[0]['bt_filename'].'[/img][/spoiler]\' />'."\n";
		} else {
			$form .= '<input onfocus="SelectAllText(\'file_html\')" id="file_html" class="text" type="text" value=\'<a href="'.$dossier.'/'.$fichiers[0]['bt_filename'].'" />'.$fichiers[0]['bt_filename'].'</a>\' />'."\n";
			$form .= '<input onfocus="SelectAllText(\'fichier_bbcode_url\')" id="fichier_bbcode_url" class="text" type="text" value=\'[url]'.$dossier.'/'.$fichiers[0]['bt_filename'].'[/url]\' />'."\n";
		}

		$form .= '</p>'."\n";

		// la partie avec l’édition du contenu.
		$form .= "\t".'<label>'.ucfirst($GLOBALS['lang']['label_dp_nom']).'<input type="text" id="nom_entree" name="nom_entree" placeholder="" value="'.pathinfo($fichiers[0]['bt_filename'], PATHINFO_FILENAME).'" size="60" class="text" /></label>'."\n";
		$form .= "\t".'<label>'.$GLOBALS['lang']['label_dp_description'].'<textarea class="text" name="description" cols="60" rows="5" placeholder="'.$GLOBALS['lang']['placeholder_description'].'" >'.$fichiers[0]['bt_wiki_content'].'</textarea></label>'."\n";
		$form .= "\t".'<label>'.$GLOBALS['lang']['label_dp_dossier'].'<input type="text" name="dossier" placeholder="'.$GLOBALS['lang']['placeholder_folder'].'" value="'.(!empty($fichiers[0]['bt_dossier']) ? $fichiers[0]['bt_dossier'] : '').'" size="60" class="text" /></label>'."\n";
		$checked = ($fichiers[0]['bt_statut'] == 0) ? 'checked ' : '';
		$form .= "\t".'<label for="statut">'.$GLOBALS['lang']['label_file_priv'].'<input type="checkbox" id="statut" name="statut" '.$checked.'/></label>';
		$form .= "\t".'<p class="centrer">'."\n";
		$form .= "\t\t".'<input class="submit blue-square" type="submit" name="editer" value="'.$GLOBALS['lang']['envoyer'].'" />'."\n";
		$form .= "\t\t".'<input class="submit red-square" type="submit" name="supprimer" value="'.$GLOBALS['lang']['supprimer'].'" onclick="return window.confirm(\''.$GLOBALS['lang']['question_suppr_fichier'].'\')" />'."\n";
		$form .= "\t".'</p>'."\n";

		$form .= hidden_input('_verif_envoi', '1');
		$form .= hidden_input('is_it_edit', 'yes');
		$form .= hidden_input('file_id', $fichiers[0]['bt_id']);
		$form .= hidden_input('filename', $fichiers[0]['bt_filename']);
		$form .= hidden_input('sha1_file', $fichiers[0]['bt_checksum']);
		$form .= hidden_input('filesize', $fichiers[0]['bt_filesize']);
		$form .= hidden_input('token', new_token());
		$form .= '</fieldset>';
	}
	$form .= '</form>'."\n";

	echo $form;
}


// affichage de la liste des fichiers
function afficher_liste_fichiers($tableau) {
	$dossier = $GLOBALS['racine'].$GLOBALS['dossier_fichiers'];
	$out = '';
	if (!empty($tableau)) {
		// affichage sous la forme d’icônes, comme les images.
		$old_filetype = '';
		$tableau = tri_selon_sous_cle($tableau, 'bt_type');


		// liste les différents dossiers " logiques " des images.
		$lstype = array();
		foreach ($tableau as $file) {
			$lstype[$file['bt_type']] = (isset($lstype[$file['bt_type']])) ? $lstype[$file['bt_type']]+1 : 1 ;
		}
		$lstype = array_unique($lstype);

		if (!empty($lstype)) {
			$out .= '<div class="list-buttons" id="list-types">'."\n";
			$i = 0;
			$out .= "\t".'<button class="current" id="butIdtype'.$i.'" onclick="type_sort(\'\', \'butIdtype'.$i.'\');">'.count($tableau).' '.$GLOBALS['lang']['label_fichiers'].'</button>';
			foreach ($lstype as $t => $n) {
				if (empty($t)) break;
				$i++;
				$out .= '<button id="butIdtype'.$i.'" onclick="type_sort(\''.$t.'\', \'butIdtype'.$i.'\');">'.$t.' ('.$n.')</button>';
			}
			$out .= "\n".'</div>'."\n";
		}

		$out .= '<div class="files-wall">'."\n";
		foreach ($tableau as $file) {
			$out .= '<div class="file_bloc"  id="bloc_'.$file['bt_id'].'" data-type="'.$file['bt_type'].'">'."\n";
				$description = (empty($file['bt_content'])) ? '' : ' ('.$file['bt_content'].')';
				$out .= "\t".'<span class="spantop black">';
				$out .= '<a class="lien lien-edit" href="fichiers.php?file_id='.$file['bt_id'].'&amp;edit">&nbsp;</a>';
				$out .= '<a class="lien lien-supr" href="#" onclick="request_delete_form(\''.$file['bt_id'].'\'); return false;" >&nbsp;</a>';
				$out .= '</span>'."\n";
				$out .= "\t".'<a class="lien" href="'.$dossier.'/'.$file['bt_filename'].'"><img src="style/filetypes/'.$file['bt_type'].'.png" id="'.$file['bt_id'].'" alt="'.$file['bt_filename'].'" /></a><br/><span class="description">'.$file['bt_filename']."</span>\n";
			$out .= '</div>'."\n\n";
		}
		$out .= '</div>';

	}
	echo $out;
}


// gère le filtre de recherche sur les images : recherche par chaine (GET[q]), par type, par statut ou par date.
// pour le moment, il n’est utilisé que du côté Admin (pas de tests sur les statut, date, etc.).
function liste_base_files($tri_selon, $motif, $nombre) {
	$tableau_sortie = array();

	switch($tri_selon) {
		case 'statut':
			foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
				if ($file['bt_statut'] == $motif) {
					$tableau_sortie[$id] = $file;
			}	}
			break;

		case 'date':
			foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
				if (($pos = strpos($file['bt_id'], $motif)) !== FALSE and $pos == 0) {
					$tableau_sortie[$id] = $file;
			}	}
			break;

		case 'type':
			foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
				if ($file['bt_type'] == $motif) {
					$tableau_sortie[$id] = $file;
			}	}
			break;

		case 'extension':
			foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
				if (($file['bt_fileext'] == $motif)) {
					$tableau_sortie[$id] = $file;
			}	}
			break;

		case 'dossier':
			foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
				if (in_array($motif, explode(',', $file['bt_dossier']))) {
					$tableau_sortie[$id] = $file;
			}	}
			break;

		case 'recherche':
			foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
				if (strpos($file['bt_content'].' '.$file['bt_filename'], $motif)) {
					$tableau_sortie[$id] = $file;
			}	}
			break;

		default :
			$tableau_sortie = $GLOBALS['liste_fichiers'];
	}

	if (isset($nombre) and is_numeric($nombre) and $nombre > 0) {
		$tableau_sortie = array_slice($tableau_sortie, 0, $nombre);
	}

	return $tableau_sortie;
}

