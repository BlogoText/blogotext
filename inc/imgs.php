<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2015 Timo Van Neerden <timo@neerden.eu>
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
		// liste les différents albums " logiques " des images.
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

		// HTML of the Slider.
		$out .= '<div id="slider">'."\n";

		$out .= "\t".'<div id="slider-main-content">'."\n";

		$out .= "\t\t".'<ul id="slider-nav-bar">'."\n";
		$out .= "\t\t\t".'<li><button id="slider-nav-close" class="slider-nav-button" onclick="slideshow(\'close\');"></button></li>'."\n";
		$out .= "\t\t\t".'<li><a id="slider-nav-dl" class="slider-nav-button" title="'.$GLOBALS['lang']['telecharger'].'" href=""></a></li>'."\n";
		$out .= "\t\t\t".'<li><a id="slider-nav-share" class="slider-nav-button" title="'.$GLOBALS['lang']['partager'].'" href=""></a></li>'."\n";
		$out .= "\t\t\t".'<li><button id="slider-nav-infos" class="slider-nav-button" title="'.$GLOBALS['lang']['infos'].'"></button></li>'."\n";
		$out .= "\t\t\t".'<li><a id="slider-nav-edit" class="slider-nav-button" title="'.$GLOBALS['lang']['editer'].'" href=""></a></li>'."\n";
		$out .= "\t\t\t".'<li><button id="slider-nav-suppr" class="slider-nav-button" title="'.$GLOBALS['lang']['supprimer'].'"></button></li>'."\n";
		$out .= "\t\t".'</ul>'."\n";
		$out .= "\t\t".'<div id="slider-display">'."\n";
		$out .= "\t\t\t".'<img id="slider-img" src="" alt=""/>'."\n";
		$out .= "\t\t\t".'<div id="slider-box-buttons">'."\n";
		$out .= "\t\t\t\t".'<ul id="slider-buttons">'."\n";
		//$out .= "\t\t\t\t\t".'<li><button id="slider-first" onclick="slideshow(\'first\');"></button></li>'."\n";
		$out .= "\t\t\t\t\t".'<li><button id="slider-prev" onclick="slideshow(\'prev\');"></button></li>'."\n";
		$out .= "\t\t\t\t\t".'<li class="spacer"></li>'."\n";
		$out .= "\t\t\t\t\t".'<li><button id="slider-next" onclick="slideshow(\'next\');"></button></li>'."\n";
		//$out .= "\t\t\t\t\t".'<li><button id="slider-last" onclick="slideshow(\'last\');"></button></li>'."\n";
		$out .= "\t\t\t\t".'</ul>'."\n";
		$out .= "\t\t\t".'</div>'."\n";
		$out .= "\t\t".'</div>'."\n";

		$out .= "\t".'</div>'."\n";

		$out .= "\t".'<div id="slider-infos">'."\n";
		$out .= "\t\t".'<div id="infos-title"><span>'.$GLOBALS['lang']['infos'].'</span><button onclick="document.getElementById(\'slider-main-content\').classList.remove(\'infos-on\');"></button></div>'."\n";
		$out .= "\t\t".'<div id="infos-content"></div>'."\n";
		$out .= "\t\t".'<div id="infos-details"></div>'."\n";
		$out .= "\t".'</div>'."\n";

		$out .= '</div><!--end slider-->'."\n";

		// send all the images their info in JSON
		$out .= '<script type="text/javascript">';
		$out .=  'var imgs = {"list": ['."\n";

		foreach ($images as $i => $im) {
			//debug($im);
			$img_src = chemin_thb_img_test($dossier_relatif.$im['bt_path'].'/'.$im['bt_filename']);
			$out .=  '{"index": "'.$i.'", "filename":'."\n".'["'.$dossier.$im['bt_path'].'/'.$im['bt_filename'].'", "'.$im['bt_filename'].'", "'.$img_src.'"], "id": "'.$im['bt_id'].'", "desc": "'.addslashes(preg_replace('#(\n|\r|\n\r)#', '', nl2br($im['bt_content']))).'", "dossier": "'.(isset($im['bt_dossier']) ? $im['bt_dossier'] : '').'", "width": "'.$im['bt_dim_w'].'", "height": "'.$im['bt_dim_h'].'", "weight": "'.$im['bt_filesize'].'", "date":'."\n".'["'.date_formate($im['bt_id']).'", "'.heure_formate($im['bt_id']).'"]},'."\n";
		}
			$out .= ']'."\n";
		$out .= '};'."\n";

		$out .=  '</script>';

		// the images
		$out .= '<div id="image-section">'."\n";
			// displays the buttons
			if (!empty($lsf_uniq)) {
				$out .= '<div class="list-buttons" id="list-albums">'."\n";
				$i = 0;
				$out .= "\t".'<button class="current" id="butId'.$i.'" onclick="folder_sort(\'\', \'butId'.$i.'\');">'.(count($images)).' '.$GLOBALS['lang']['label_images'].'</button>';
				foreach ($lsf_uniq as $fol => $nb) {
					if (empty($fol)) break; $i++;
					$out .= '<button id="butId'.$i.'" onclick="folder_sort(\''.$fol.'\', \'butId'.$i.'\');">'.$fol.' ('.$nb.')</button>';
				}
				$out .= "\n".'</div>'."\n";
			}
			$out .= '<div id="image-wall">'."\n";
			$out .= '</div>'."\n";

		$out .= '</div>'."\n";
	}
	echo $out;
}


// filepath : image to create a thumbnail from
function create_thumbnail($filepath) {
	// if GD library is not loaded by PHP, abord. Thumbnails are not required.
	if (!extension_loaded('gd')) return;
	$maxwidth = '700';
	$maxheight = '200';
	$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

	// si l’image est petite (<200), pas besoin de miniature
	list($width_orig, $height_orig) = getimagesize($filepath);
	if ($width_orig <= 200 and $height_orig <= 200) return;
	// largeur et hauteur maximale
	// Cacul des nouvelles dimensions
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
		redirection(basename($_SERVER['PHP_SELF']).'?file_id='.$fichier['bt_id'].'&msg=confirm_fichier_ajout');
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
			redirection(basename($_SERVER['PHP_SELF']).'?errmsg=error_fichier_suppr&what=file_suppr_error');
		} elseif ($response == 'no_such_file_on_disk') {
			redirection(basename($_SERVER['PHP_SELF']).'?msg=error_fichier_suppr&what=but_no_such_file_on_disk2');
		} elseif ($response == 'success') {
			redirection(basename($_SERVER['PHP_SELF']).'?msg=confirm_fichier_suppr');
		}
	}
}

// TRAITEMENT DU FORMULAIRE DE FICHIER, CÔTÉ BDD
// Retourne le $fichier de l’entrée (après avoir possiblement changé des trucs, par ex si le fichier existait déjà, l’id retourné change)
function bdd_fichier($fichier, $quoi, $comment, $sup_var) {
	if ($fichier['bt_type'] == 'image') {
		$dossier = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'].$fichier['bt_path'];
	} else {
		$dossier = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'];
		$rand_dir = '';
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

			// éviter d’écraser un fichier existant
			while (file_exists($dossier.'/'.$prefix.$fichier['bt_filename'])) { $prefix .= rand(0,9); }

			$dest = $prefix.$fichier['bt_filename'];
			$fichier['bt_filename'] = $dest; // redéfinit le nom du fichier.

			// copie du fichier physique
				// Fichier uploadé s’il y a (sinon fichier téléchargé depuis l’URL)
			if ( $comment == 'upload' ) {
				$new_file = $sup_var['tmp_name'];
				if (move_uploaded_file($new_file, $dossier.'/'. $dest) ) {
					$fichier['bt_checksum'] = sha1_file($dossier.'/'. $dest);
				} else {
					redirection(basename($_SERVER['PHP_SELF']).'?errmsg=error_fichier_ajout_2');
					exit;
				}
			}
			// fichier spécifié par URL
			elseif ( $comment == 'download' and copy($sup_var, $dossier.'/'. $dest) ) {
				$fichier['bt_filesize'] = filesize($dossier.'/'. $dest);
			} else {
				redirection(basename($_SERVER['PHP_SELF']).'?errmsg=error_fichier_ajout');
				exit;
			}

			// si fichier par POST ou par URL == OK, on l’ajoute à la base. (si pas OK, on serai déjà sorti par le else { redirection() }.
			if ($fichier['bt_type'] == 'image') { // miniature si c’est une image
				create_thumbnail($dossier.'/'. $dest);
				list($fichier['bt_dim_w'], $fichier['bt_dim_h']) = getimagesize($dossier.'/'. $dest);
			}
			// rm $path if not image
			else {
				$fichier['bt_path'] = '';
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
					redirection(basename($_SERVER['PHP_SELF']).'?file_id='.$fichier['bt_id'].'&errmsg=error_fichier_rename');
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
			redirection(basename($_SERVER['PHP_SELF']).'?file_id='.$fichier['bt_id'].'&edit&msg=confirm_fichier_edit');
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
				$liste_fichiers = rm_dots_dir(scandir($dossier)); // liste les fichiers réels dans le dossier
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
				$path = htmlspecialchars($_POST['path']);
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
				$path = '';
			// ajout par une URL d’un fichier distant
			} elseif ( !empty($_POST['fichier']) ) {
				$filename = pathinfo(parse_url($_POST['fichier'], PHP_URL_PATH), PATHINFO_FILENAME);
				$ext = strtolower(pathinfo(parse_url($_POST['fichier'], PHP_URL_PATH), PATHINFO_EXTENSION));
				$checksum = sha1_file($_POST['fichier']); // works with URL files
				$size = '';// same (even if we could use "filesize" with the URL, it would over-use data-transfer)
				$path = '';
				$type = detection_type_fichier($ext);
			} else {
				// ERROR
				redirection(basename($_SERVER['PHP_SELF']).'?errmsg=error_image_add');
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
			'bt_dossier' => (empty($dossier) ? 'default' : $dossier ), // tags
			'bt_path' => (empty($path) ? '/'.(substr($checksum, 0, 2)) : $path ), // path on disk (rand subdir to avoid too many files in same dir)
		);
		return $fichier;
}



function afficher_form_fichier($erreurs, $fichiers, $what) { // ajout d’un fichier
	$max_file_size = taille_formate( min(return_bytes(ini_get('upload_max_filesize')), return_bytes(ini_get('post_max_size'))) );


	$max_file_nb = ini_get('max_file_uploads');
	if ($erreurs) {
		echo erreurs($erreurs);
	}
	$form = '<form id="form-image" class="bordered-formbloc" enctype="multipart/form-data" method="post" action="'.basename($_SERVER['PHP_SELF']).'" onsubmit="submitdnd(event);">'."\n";
	
	if (empty($fichiers)) { // si PAS fichier donnée : formulaire nouvel envoi.
		$form .= '<fieldset class="pref" >'."\n";

		$form .= '<div id="form-dragndrop">'."\n";
			$form .= '<div id="dragndrop-area" ondragover="event.preventDefault();" ondrop="handleDrop(event);" >'."\n";
			$form .= "\t".'<div id="dragndrop-title">'."\n";
			$form .= "\t\t".$GLOBALS['lang']['img_drop_files_here']."\n";
			$form .= "\t\t".'<div class="upload-info">('.$GLOBALS['lang']['label_jusqua'].$max_file_size.$GLOBALS['lang']['label_parfichier'].')</div>'."\n";
			$form .= "\t".'</div>'."\n";
			$form .= "\t".'<div id="file-input-wrapper"><input name="fichier" id="fichier" type="file" required="" /></div>'."\n";
			$form .= "\t".'<button type="button" class="specify-link white-square" id="click-change-form" onclick="return switchUploadForm();" data-lang-url="'.$GLOBALS['lang']['img_specifier_url'].'" data-lang-file="'.$GLOBALS['lang']['img_upload_un_fichier'].'">'.$GLOBALS['lang']['img_specifier_url'].'</button>'."\n";
			$form .= '</div>'."\n";
			$form .= '<div id="count"></div>'."\n";
			$form .= '<div id="result"></div>'."\n";
		$form .= '</div>'."\n";
	
		$form .= '<div id="img-others-infos">'."\n";

		$form .= "\t".'<p><label for="nom_entree">'.$GLOBALS['lang']['label_dp_nom'].'</label><input type="text" id="nom_entree" name="nom_entree" placeholder="'.$GLOBALS['lang']['placeholder_nom_fichier'].'" value="" size="60" class="text" /></p>'."\n";
		$form .= "\t".'<p><label for="description">'.$GLOBALS['lang']['label_dp_description'].'</label><textarea class="text" id="description" name="description" cols="60" rows="5" placeholder="'.$GLOBALS['lang']['placeholder_description'].'" ></textarea></p>'."\n";
		$form .= "\t".'<p><label for="dossier">'.$GLOBALS['lang']['label_dp_dossier'].'</label><input type="text" id="dossier" name="dossier" placeholder="'.$GLOBALS['lang']['placeholder_folder'].'" value="" size="60" class="text" /></p>'."\n";
		$form .= "\t".'<p><label for="statut">'.$GLOBALS['lang']['label_file_priv'].'<input type="checkbox" id="statut" name="statut"/></label></p>';
		$form .= hidden_input('token', new_token(), 'id');
		$form .= hidden_input('_verif_envoi', '1');

		$form .= "\t".'<p class="submit-bttns"><input class="submit blue-square" type="submit" name="upload" value="'.$GLOBALS['lang']['img_upload'].'" /></p>'."\n";
		$form .= '</div>'."\n";

		$form .= '</fieldset>'."\n";
	}
	// si ID dans l’URL, il s’agit également du seul fichier dans le tableau fichiers, d’où le [0]
	elseif (!empty($fichiers) and isset($_GET['file_id']) and preg_match('/\d{14}/',($_GET['file_id']))) {
		$myfile = $fichiers[0];
		if ($myfile['bt_type'] == 'image') {
			$dossier = $GLOBALS['racine'].$GLOBALS['dossier_images'].$myfile['bt_path'];
		} else {
			$dossier = $GLOBALS['racine'].$GLOBALS['dossier_fichiers'];
		}

		$form .= '<div class="edit-fichier">'."\n";

		// codes d’intégrations pour les médias
		// Video
		if ($myfile['bt_type'] == 'video') {
			$form .= '<div class="display-media"><video class="media" src="'.$dossier.'/'.$myfile['bt_filename'].'" type="video/'.$myfile['bt_fileext'].'" load controls="controls"></video></div>'."\n";
		}
		// image
		if ($myfile['bt_type'] == 'image') {
			$form .= '<div class="display-media"><a href="'.$dossier.'/'.$myfile['bt_filename'].'"><img class="media" src="'.$dossier.'/'.$myfile['bt_filename'].'" alt="'.$myfile['bt_filename'].'" width="'.$myfile['bt_dim_w'].'" height="'.$myfile['bt_dim_h'].'" /></a></div>'."\n";
		}
		// audio
		if ($myfile['bt_type'] == 'music') {
			$form .= '<div class="display-media"><audio class="media" src="'.$dossier.'/'.$myfile['bt_filename'].'" type="audio/'.$myfile['bt_fileext'].'" load controls="controls"></audio></div>'."\n";
		}

		// la partie listant les infos du fichier.
		$form .= '<ul id="fichier-meta-info">'."\n";
			$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_nom'].'</b> '.$myfile['bt_filename'].'</li>'."\n";
			$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_type'].'</b> '.$myfile['bt_type'].' (.'.$myfile['bt_fileext'].')</li>'."\n";
			if ($myfile['bt_type'] == 'image') { // si le fichier est une image, on ajout ses dimensions en pixels
				$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_dimensions'].'</b> '.$myfile['bt_dim_w'].'px × '.$myfile['bt_dim_h'].'px'.'</li>'."\n";
			}
			$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_date'].'</b>'.date_formate($myfile['bt_id']).', '.heure_formate($myfile['bt_id']).'</li>'."\n";
			$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_poids'].'</b>'.taille_formate($myfile['bt_filesize']).'</li>'."\n";
			$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_checksum'].'</b>'.$myfile['bt_checksum'].'</li>'."\n";
			$form .= "\t".'<li><b>'.$GLOBALS['lang']['label_dp_visibilite'].'</b>'.(($myfile['bt_statut'] == 1) ? 'Publique' : 'Privée').'</li>'."\n";
		$form .= '</ul>'."\n";

		// la partie des codes d’intégration (bbcode, etc.)
		$form .= '<div id="interg-codes">'."\n";
		$form .= '<p><strong>'.ucfirst('codes d’intégration :').'</strong></p>'."\n";
		$form .= '<input onfocus="this.select()" class="text" type="text" value=\''.$dossier.'/'.$myfile['bt_filename'].'\' />'."\n";
		if ($myfile['bt_type'] == 'image') { // si le fichier est une image, on ajout BBCode pour [IMG] et le code en <img/>
			$form .= '<input onfocus="this.select()" class="text" type="text" value=\'<img src="'.$dossier.'/'.$myfile['bt_filename'].'" alt="i" width="'.$myfile['bt_dim_w'].'" height="'.$myfile['bt_dim_h'].'" style="max-width: 100%; height: auto;" />\' />'."\n";
			$form .= '<input onfocus="this.select()" class="text" type="text" value=\'<img src="/'.$GLOBALS['dossier_images'].$myfile['bt_path'].'/'.$myfile['bt_filename'].'" alt="i" width="'.$myfile['bt_dim_w'].'" height="'.$myfile['bt_dim_h'].'" style="max-width: 100%; height: auto;" />\' />'."\n";
			$form .= '<input onfocus="this.select()" class="text" type="text" value=\'<img src="'.$GLOBALS['dossier_images'].$myfile['bt_path'].'/'.$myfile['bt_filename'].'" alt="i" width="'.$myfile['bt_dim_w'].'" height="'.$myfile['bt_dim_h'].'" style="max-width: 100%; height: auto;" />\' />'."\n";
			$form .= '<input onfocus="this.select()" class="text" type="text" value=\'[img]'.$dossier.'/'.$myfile['bt_filename'].'[/img]\' />'."\n";
			$form .= '<input onfocus="this.select()" class="text" type="text" value=\'[spoiler][img]'.$dossier.'/'.$myfile['bt_filename'].'[/img][/spoiler]\' />'."\n";
		} else {
			$form .= '<input onfocus="this.select()" class="text" type="text" value=\'<a href="'.$dossier.'/'.$myfile['bt_filename'].'" />'.$myfile['bt_filename'].'</a>\' />'."\n";
			$form .= '<input onfocus="this.select()" class="text" type="text" value=\'[url]'.$dossier.'/'.$myfile['bt_filename'].'[/url]\' />'."\n";
		}

		$form .= '</div>'."\n";

		// la partie avec l’édition du contenu.
		$form .= '<div id="img-others-infos">'."\n";
		$form .= "\t".'<p><label for="nom_entree">'.ucfirst($GLOBALS['lang']['label_dp_nom']).'</label><input type="text" id="nom_entree" name="nom_entree" placeholder="" value="'.pathinfo($myfile['bt_filename'], PATHINFO_FILENAME).'" size="60" class="text" /></p>'."\n";
		$form .= "\t".'<p><label for="description">'.$GLOBALS['lang']['label_dp_description'].'</label><textarea class="text" name="description" id="description" cols="60" rows="5" placeholder="'.$GLOBALS['lang']['placeholder_description'].'" >'.$myfile['bt_wiki_content'].'</textarea></p>'."\n";
		$form .= "\t".'<p><label for="dossier">'.$GLOBALS['lang']['label_dp_dossier'].'</label><input type="text" name="dossier" placeholder="'.$GLOBALS['lang']['placeholder_folder'].'" value="'.(!empty($myfile['bt_dossier']) ? $myfile['bt_dossier'] : '').'" size="60" class="text" /></p>'."\n";
		$checked = ($myfile['bt_statut'] == 0) ? 'checked ' : '';
		$form .= "\t".'<p><label for="statut">'.$GLOBALS['lang']['label_file_priv'].'<input type="checkbox" id="statut" name="statut" '.$checked.'/></label></p>';
		$form .= "\t".'<p class="submit-bttns">'."\n";
		$form .= "\t\t".'<input class="submit red-square" type="button" name="supprimer" value="'.$GLOBALS['lang']['supprimer'].'" onclick="rmFichier(this)" />'."\n";
		$form .= "\t\t".'<button class="submit white-square" type="button" onclick="annuler(\'fichiers.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
		$form .= "\t\t".'<input class="submit blue-square" type="submit" name="editer" value="'.$GLOBALS['lang']['envoyer'].'" />'."\n";
		$form .= "\t".'</p>'."\n";
		$form .= '</div>'."\n";

		$form .= hidden_input('_verif_envoi', '1');
		$form .= hidden_input('is_it_edit', 'yes');
		$form .= hidden_input('file_id', $myfile['bt_id']);
		$form .= hidden_input('filename', $myfile['bt_filename']);
		$form .= hidden_input('sha1_file', $myfile['bt_checksum']);
		$form .= hidden_input('path', $myfile['bt_path']);
		$form .= hidden_input('filesize', $myfile['bt_filesize']);
		$form .= hidden_input('token', new_token());
		$form .= '</div>';
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


		// liste les différents dossiers " logiques " des fichiers.
		$lstype = array();
		foreach ($tableau as $file) {
			$lstype[$file['bt_type']] = (isset($lstype[$file['bt_type']])) ? $lstype[$file['bt_type']]+1 : 1 ;
		}
		$lstype = array_unique($lstype);

		$out .= '<div id="files-section">'."\n";
			// buttons
			if (!empty($lstype)) {
				$out .= '<div class="list-buttons" id="list-types">'."\n";
				$i = 0;
				$out .= "\t".'<button class="current" id="butIdtype'.$i.'" onclick="type_sort(\'\', \'butIdtype'.$i.'\');">'.count($tableau).' '.$GLOBALS['lang']['label_fichiers'].'</button>'."\n";
				foreach ($lstype as $type => $amount) {
					if (empty($type)) break;
					$i++;
					$out .= "\t".'<button id="butIdtype'.$i.'" onclick="type_sort(\''.$type.'\', \'butIdtype'.$i.'\');">'.$type.' ('.$amount.')</button>'."\n";
				}
				$out .= '</div>'."\n";
			}
			$out .= '<div id="files-wall">'."\n";
			// the files
			foreach ($tableau as $file) {
				$out .= '<div class="file_bloc"  id="bloc_'.$file['bt_id'].'" data-type="'.$file['bt_type'].'">'."\n";
					$description = (empty($file['bt_content'])) ? '' : ' ('.$file['bt_content'].')';
					$out .= "\t".'<span class="spantop black">';
					$out .= '<a class="lien lien-edit" href="fichiers.php?file_id='.$file['bt_id'].'&amp;edit"></a>';
					$out .= '<a class="lien lien-supr" href="#" onclick="request_delete_form(\''.$file['bt_id'].'\'); return false;" ></a>';
					$out .= '</span>'."\n";
					$out .= "\t".'<a class="lien" href="'.$dossier.'/'.$file['bt_filename'].'" download><img src="style/filetypes/'.$file['bt_type'].'.png" id="'.$file['bt_id'].'" alt="'.$file['bt_filename'].'" /></a><br/><span class="description">'.$file['bt_filename']."</span>\n";
				$out .= '</div>'."\n\n";
			}
			$out .= '</div>'."\n";
		$out .= '</div>'."\n";

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

