<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2015 Timo Van Neerden.
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
function chemin_thb_img($filepath)
{
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    // prend le nom, supprime l’extension et le point, ajoute le " -thb.jpg ", puisque les miniatures de BT sont en JPG.
    $miniature = substr($filepath, 0, -(strlen($ext)+1)).'-thb.jpg'; // "+1" is for the "." between name and ext.
    return $miniature;
}

function chemin_thb_img_test($filepath)
{
    $thb = chemin_thb_img($filepath);
    if (is_file($thb)) {
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
function afficher_liste_images($images)
{
    $dossier = $GLOBALS['racine'].DIR_IMAGES;
    $dossier_relatif = BT_ROOT.DIR_IMAGES;
    $out = '';
    $i = 0;
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
        $out .= "\t\t\t".'<li><button id="slider-nav-dl"    class="slider-nav-button" onclick="triggerClick(document.getElementById(\'slider-nav-dl-link\'))" title="'.$GLOBALS['lang']['telecharger'].'"></button><a id="slider-nav-dl-link" download></a></li>'."\n";
        $out .= "\t\t\t".'<li><button id="slider-nav-share" class="slider-nav-button" onclick="triggerClick(document.getElementById(\'slider-nav-share-link\'))" title="'.$GLOBALS['lang']['partager'].   '"></button><a id="slider-nav-share-link"></a></li>'."\n";
        $out .= "\t\t\t".'<li><button id="slider-nav-infos" class="slider-nav-button" onclick="" title="'.$GLOBALS['lang']['infos'].      '"></button></li>'."\n";
        $out .= "\t\t\t".'<li><button id="slider-nav-edit"  class="slider-nav-button" onclick="triggerClick(document.getElementById(\'slider-nav-edit-link\'))" title="'.$GLOBALS['lang']['editer'].     '"></button><a id="slider-nav-edit-link"></a></li>'."\n";

        $out .= "\t\t\t".'<li><button id="slider-nav-suppr" class="slider-nav-button" title="'.$GLOBALS['lang']['supprimer'].  '"></button></li>'."\n";
        $out .= "\t\t".'</ul>'."\n";
        $out .= "\t\t".'<div id="slider-display">'."\n";
        $out .= "\t\t\t".'<img id="slider-img" src="" alt=""/>'."\n";
        $out .= "\t\t\t".'<div id="slider-box-buttons">'."\n";
        $out .= "\t\t\t\t".'<ul id="slider-buttons">'."\n";
        $out .= "\t\t\t\t\t".'<li><button id="slider-prev" onclick="slideshow(\'prev\');"></button></li>'."\n";
        $out .= "\t\t\t\t\t".'<li class="spacer"></li>'."\n";
        $out .= "\t\t\t\t\t".'<li><button id="slider-next" onclick="slideshow(\'next\');"></button></li>'."\n";
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
            $rel_thb_src = chemin_thb_img_test($dossier_relatif.$im['bt_path'].'/'.$im['bt_filename']);
            $out .= '
            {
                "index": "'.$i.'",
                "filename":
                    [
                    "'.$dossier.$im['bt_path'].'/'.$im['bt_filename'].'",
                    "'.$im['bt_filename'].'",
                    "'.$rel_thb_src.'",
                    "'.$dossier_relatif.$im['bt_path'].'/'.$im['bt_filename'].'"
                    ],
                "id": "'.$im['bt_id'].'",
                "desc": "'.addslashes(preg_replace('#(\n|\r|\n\r)#', '', nl2br($im['bt_content']))).'",
                "dossier": "'.(isset($im['bt_dossier']) ? $im['bt_dossier'] : '').'",
                "width": "'.$im['bt_dim_w'].'",
                "height": "'.$im['bt_dim_h'].'",
                "weight": "'.$im['bt_filesize'].'",
                "date":
                    [
                    "'.date_formate($im['bt_id']).'",
                    "'.heure_formate($im['bt_id']).'"
                    ]
            },';
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
                if (empty($fol)) {
                    break;
                } $i++;
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
function create_thumbnail($filepath)
{
    // if GD library is not loaded by PHP, abord. Thumbnails are not required.
    if (!extension_loaded('gd')) {
        return;
    }
    $maxwidth = '700';
    $maxheight = '200';
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

    // si l’image est petite (<200), pas besoin de miniature
    list($width_orig, $height_orig) = getimagesize($filepath);
    if ($width_orig <= 200 and $height_orig <= 200) {
        return;
    }
    // largeur et hauteur maximale
    // Cacul des nouvelles dimensions
    if ($width_orig == 0 or $height_orig == 0) {
        return;
    }
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
        case 'jpg':
            $image = imagecreatefromjpeg($filepath);
            break;
        case 'png':
            $image = imagecreatefrompng($filepath);
            break;
        case 'gif':
            $image = imagecreatefromgif($filepath);
            break;
        default:
            return;
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
function traiter_form_fichier($fichier)
{
    // ajout de fichier
    if (isset($_POST['upload'])) {
        // par $_FILES
        if (isset($_FILES['fichier'])) {
            $new_fichier = bdd_fichier($fichier, 'ajout-nouveau', 'upload', $_FILES['fichier']);
        }
        // par $_POST d’une url
        if (isset($_POST['fichier'])) {
            $new_fichier = bdd_fichier($fichier, 'ajout-nouveau', 'download', $_POST['fichier']);
        }
        $fichier = (is_null($new_fichier)) ? $fichier : $new_fichier;
        redirection(basename($_SERVER['SCRIPT_NAME']).'?file_id='.$fichier['bt_id'].'&msg=confirm_fichier_ajout');
    } // édition d’une entrée d’un fichier
    elseif (isset($_POST['editer']) and !isset($_GET['suppr'])) {
        $old_file_name = $_POST['filename']; // Name can be edited too. This is old name, the new one is in $fichier[].
        bdd_fichier($fichier, 'editer-existant', '', $old_file_name);
    } // suppression d’un fichier
    elseif ((isset($_POST['supprimer']) and preg_match('#^\d{14}$#', $_POST['file_id']))) {
        $response = bdd_fichier($fichier, 'supprimer-existant', '', $_POST['file_id']);
        if ($response == 'error_suppr_file_suppr_error') {
            redirection(basename($_SERVER['SCRIPT_NAME']).'?errmsg=error_fichier_suppr&what=file_suppr_error');
        } elseif ($response == 'no_such_file_on_disk') {
            redirection(basename($_SERVER['SCRIPT_NAME']).'?msg=error_fichier_suppr&what=but_no_such_file_on_disk2');
        } elseif ($response == 'success') {
            redirection(basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_fichier_suppr');
        }
    }
}

// TRAITEMENT DU FORMULAIRE DE FICHIER, CÔTÉ BDD
// Retourne le $fichier de l’entrée (après avoir possiblement changé des trucs, par ex si le fichier existait déjà, l’id retourné change)
function bdd_fichier($fichier, $quoi, $comment, $sup_var)
{
    if ($fichier['bt_type'] == 'image') {
        $dossier = BT_ROOT.DIR_IMAGES.$fichier['bt_path'];
    } else {
        $dossier = BT_ROOT.DIR_DOCUMENTS;
        $rand_dir = '';
    }
    if (!create_folder($dossier, 0)) {
        die($GLOBALS['lang']['err_file_write']);
    }
    // ajout d’un nouveau fichier
    if ($quoi == 'ajout-nouveau') {
            $prefix = '';
        foreach ($GLOBALS['liste_fichiers'] as $files) {
            if (($fichier['bt_checksum'] == $files['bt_checksum'])) {
                $fichier['bt_id'] = $files['bt_id'];
                return $fichier;
            }
        }

            // éviter d’écraser un fichier existant
        while (is_file($dossier.'/'.$prefix.$fichier['bt_filename'])) {
            $prefix .= rand(0, 9);
        }

            $dest = $prefix.$fichier['bt_filename'];
            $fichier['bt_filename'] = $dest; // redéfinit le nom du fichier.

            // copie du fichier physique
                // Fichier uploadé s’il y a (sinon fichier téléchargé depuis l’URL)
        if ($comment == 'upload') {
            $new_file = $sup_var['tmp_name'];
            if (move_uploaded_file($new_file, $dossier.'/'. $dest)) {
                $fichier['bt_checksum'] = sha1_file($dossier.'/'. $dest);
            } else {
                redirection(basename($_SERVER['SCRIPT_NAME']).'?errmsg=error_fichier_ajout_2');
                exit;
            }
        } // fichier spécifié par URL
        elseif ($comment == 'download' and copy($sup_var, $dossier.'/'. $dest)) {
            $fichier['bt_filesize'] = filesize($dossier.'/'. $dest);
        } else {
            redirection(basename($_SERVER['SCRIPT_NAME']).'?errmsg=error_fichier_ajout');
            exit;
        }

            // si fichier par POST ou par URL == OK, on l’ajoute à la base. (si pas OK, on serai déjà sorti par le else { redirection() }.
        if ($fichier['bt_type'] == 'image') { // miniature si c’est une image
            create_thumbnail($dossier.'/'. $dest);
            list($fichier['bt_dim_w'], $fichier['bt_dim_h']) = getimagesize($dossier.'/'. $dest);
        } // rm $path if not image
        else {
            $fichier['bt_path'] = '';
        }
            // ajout à la base.
            $GLOBALS['liste_fichiers'][] = $fichier;
            $GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
            file_put_contents(FILES_DB, '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers'])), 76, "\n").' */'."\n");
    } // modification d’un fichier déjà existant
    elseif ($quoi == 'editer-existant') {
            $new_filename = $fichier['bt_filename'];
            $old_filename = $sup_var;
        if ($new_filename != $old_filename) { // nom du fichier a changé ? on déplace le fichier.
            $prefix = '';
            while (is_file($dossier.'/'.$prefix.$new_filename)) { // évite d’avoir deux fichiers de même nom
                $prefix .= rand(0, 9);
            }
            $new_filename = $prefix.$fichier['bt_filename'];
            $fichier['bt_filename'] = $new_filename; // update file name in $fichier array(), with the new prefix.

            // rename file on disk
            if (rename($dossier.'/'.$old_filename, $dossier.'/'.$new_filename)) {
                // si c’est une image : renome la miniature si elle existe, sinon la crée
                if ($fichier['bt_type'] == 'image') {
                    if (($old_thb = chemin_thb_img_test($dossier.'/'.$old_filename)) != $dossier.'/'.$old_filename) {
                        rename($old_thb, chemin_thb_img($dossier.'/'.$new_filename));
                    } else {
                        create_thumbnail($dossier.'/'.$new_filename);
                    }
                }
                // error rename ficher
            } else {
                redirection(basename($_SERVER['SCRIPT_NAME']).'?file_id='.$fichier['bt_id'].'&errmsg=error_fichier_rename');
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
            file_put_contents(FILES_DB, '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers'])), 76, "\n").' */'."\n"); // écrit dans le fichier, la liste
            redirection(basename($_SERVER['SCRIPT_NAME']).'?file_id='.$fichier['bt_id'].'&edit&msg=confirm_fichier_edit');
    } // suppression d’un fichier (de la BDD et du disque)
    elseif ($quoi == 'supprimer-existant') {
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
            if (true === unlink($dossier.'/'.$fichier['bt_filename'])) { // fichier physique effacé
                if ($fichier['bt_type'] == 'image') { // supprimer aussi la miniature si elle existe.
                    @unlink(chemin_thb_img($dossier.'/'.$fichier['bt_filename'])); // supprime la thumbnail si y’a
                }
                unset($GLOBALS['liste_fichiers'][$tbl_id]); // efface le fichier dans la liste des fichiers.
                $GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
                file_put_contents(FILES_DB, '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers'])), 76, "\n").' */'."\n"); // enregistre la liste
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
            file_put_contents(FILES_DB, '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers'])), 76, "\n").' */'."\n"); // enregistre la liste
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
function init_post_fichier()
{
 //no $mode : it's always admin.
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
        } elseif (!empty($_POST['fichier'])) {
            $filename = pathinfo(parse_url($_POST['fichier'], PHP_URL_PATH), PATHINFO_FILENAME);
            $ext = strtolower(pathinfo(parse_url($_POST['fichier'], PHP_URL_PATH), PATHINFO_EXTENSION));
            $checksum = sha1_file($_POST['fichier']); // works with URL files
            $size = '';// same (even if we could use "filesize" with the URL, it would over-use data-transfer)
            $path = '';
            $type = detection_type_fichier($ext);
        } else {
            // ERROR
            redirection(basename($_SERVER['SCRIPT_NAME']).'?errmsg=error_image_add');
            return false;
        }
    }
        // nom du fichier : si nom donné, sinon nom du fichier inchangé
        $filename = diacritique(htmlspecialchars((!empty($_POST['nom_entree'])) ? $_POST['nom_entree'] : $filename)).'.'.$ext;
        $statut = (isset($_POST['statut']) and $_POST['statut'] == 'on') ? '0' : '1';
        $fichier = array (
            'bt_id' => $file_id,
            'bt_type' => $type,
            'bt_fileext' => $ext,
            'bt_filesize' => $size,
            'bt_filename' => $filename, // le nom du final du fichier peut changer à la fin, si le nom est déjà pris par exemple
            'bt_content' => clean_txt($_POST['description']),
            'bt_wiki_content' => clean_txt($_POST['description']),
            'bt_checksum' => $checksum,
            'bt_statut' => $statut,
            'bt_dossier' => (empty($dossier) ? 'default' : $dossier ), // tags
            'bt_path' => (empty($path) ? '/'.(substr($checksum, 0, 2)) : $path ), // path on disk (rand subdir to avoid too many files in same dir)
        );
        return $fichier;
}



function afficher_form_fichier($erreurs, $fichiers, $what)
{
 // ajout d’un fichier
    $max_file_size = taille_formate(min(return_bytes(ini_get('upload_max_filesize')), return_bytes(ini_get('post_max_size'))));


    $max_file_nb = ini_get('max_file_uploads');
    if ($erreurs) {
        echo erreurs($erreurs);
    }
    $form = '<form id="form-image" class="bordered-formbloc" enctype="multipart/form-data" method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'" onsubmit="submitdnd(event);">'."\n";

    if (empty($fichiers)) { // si PAS fichier donnée : formulaire nouvel envoi.
        $form .= '<fieldset class="pref" >'."\n";

        $form .= '<div id="form-dragndrop">'."\n";
            $form .= '<div id="dragndrop-area" ondragover="event.preventDefault();" ondrop="handleDrop(event);" >'."\n";
            $form .= "\t".'<div id="dragndrop-title">'."\n";
            $form .= "\t\t".$GLOBALS['lang']['img_drop_files_here']."\n";
            $form .= "\t\t".'<div class="upload-info">('.$GLOBALS['lang']['label_jusqua'].$max_file_size.$GLOBALS['lang']['label_parfichier'].')</div>'."\n";
            $form .= "\t".'</div>'."\n";
            $form .= "\t".'<p>'.$GLOBALS['lang']['ou'].'</p>';
            $form .= "\t".'<div id="file-input-wrapper"><input name="fichier" id="fichier" class="text" type="file" required="" /><label for="fichier"></label></div>'."\n";
            $form .= "\t".'<button type="button" class="specify-link button-cancel" id="click-change-form" onclick="return switchUploadForm();" data-lang-url="'.$GLOBALS['lang']['img_specifier_url'].'" data-lang-file="'.$GLOBALS['lang']['img_upload_un_fichier'].'">'.$GLOBALS['lang']['img_specifier_url'].'</button>'."\n";
            $form .= '</div>'."\n";
            $form .= '<div id="count"></div>'."\n";
            $form .= '<div id="result"></div>'."\n";
        $form .= '</div>'."\n";

        $form .= '<div id="img-others-infos">'."\n";

        $form .= "\t".'<p><label for="nom_entree">'.$GLOBALS['lang']['label_dp_nom'].'</label><input type="text" id="nom_entree" name="nom_entree" placeholder="'.$GLOBALS['lang']['placeholder_nom_fichier'].'" value="" size="60" class="text" /></p>'."\n";
        $form .= "\t".'<p><label for="description">'.$GLOBALS['lang']['label_dp_description'].'</label><textarea class="text" id="description" name="description" cols="60" rows="5" placeholder="'.$GLOBALS['lang']['placeholder_description'].'" ></textarea></p>'."\n";
        $form .= "\t".'<p><label for="dossier">'.$GLOBALS['lang']['label_dp_dossier'].'</label><input type="text" id="dossier" name="dossier" placeholder="'.$GLOBALS['lang']['placeholder_folder'].'" value="" size="60" class="text" /></p>'."\n";
        $form .= hidden_input('token', new_token(), 'id');
        $form .= "\t".'<p><input type="checkbox" id="statut" name="statut" class="checkbox" /><label for="statut">'.$GLOBALS['lang']['label_file_priv'].'</label></p>';
        $form .= hidden_input('_verif_envoi', '1');

        $form .= "\t".'<p class="submit-bttns"><button class="submit button-submit" type="submit" name="upload">'.$GLOBALS['lang']['img_upload'].'</button></p>'."\n";
        $form .= '</div>'."\n";

        $form .= '</fieldset>'."\n";
    } // si ID dans l’URL, il s’agit également du seul fichier dans le tableau fichiers, d’où le [0]
    elseif (!empty($fichiers) and isset($_GET['file_id']) and preg_match('/\d{14}/', ($_GET['file_id']))) {
        $myfile = $fichiers[0];
        $absolute_URI = $GLOBALS['racine'].(($myfile['bt_type'] == 'image') ? DIR_IMAGES : DIR_DOCUMENTS).$myfile['bt_path'].'/'.$myfile['bt_filename'];
        $simple_URI = parse_url($absolute_URI)['path'];


        $form .= '<div class="edit-fichier">'."\n";

        // codes d’intégrations pour les médias
        // Video
        if ($myfile['bt_type'] == 'video') {
            $form .= '<div class="display-media"><video class="media" src="'.$simple_URI.'" type="video/'.$myfile['bt_fileext'].'" load controls="controls"></video></div>'."\n";
        }
        // image
        if ($myfile['bt_type'] == 'image') {
            $form .= '<div class="display-media"><a href="'.$simple_URI.'"><img class="media" src="'.$simple_URI.'" alt="'.$myfile['bt_filename'].'" width="'.$myfile['bt_dim_w'].'" height="'.$myfile['bt_dim_h'].'" /></a></div>'."\n";
        }
        // audio
        if ($myfile['bt_type'] == 'music') {
            $form .= '<div class="display-media"><audio class="media" src="'.$simple_URI.'" type="audio/'.$myfile['bt_fileext'].'" load controls="controls"></audio></div>'."\n";
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

        // Integration codes.
        $form .= '<div id="interg-codes">'."\n";
        $form .= '<p><strong>'.$GLOBALS['lang']['label_codes'].'</strong></p>'."\n";
        $form .= '<input onfocus="this.select()" class="text" type="text" value=\''.$absolute_URI.'\' />'."\n";
        $form .= '<input onfocus="this.select()" class="text" type="text" value=\'<a href="'.$absolute_URI.'" />'.$myfile['bt_filename'].'</a>\' />'."\n";
        // for images
        if ($myfile['bt_type'] == 'image') {
            $form .= '<input onfocus="this.select()" class="text" type="text" value=\'<img src="'.$simple_URI.'" alt="i" width="'.$myfile['bt_dim_w'].'" height="'.$myfile['bt_dim_h'].'" />\' />'."\n";
            $form .= '<input onfocus="this.select()" class="text" type="text" value=\'[img]'.$simple_URI.'[/img]\' />'."\n";
            $form .= '<input onfocus="this.select()" class="text" type="text" value=\'[spoiler][img]'.$simple_URI.'[/img][/spoiler]\' />'."\n";
        // video
        } elseif ($myfile['bt_type'] == 'video') {
            $form .= '<input onfocus="this.select()" class="text" type="text" value=\'<video src="'.$simple_URI.'" type="video/'.$myfile['bt_fileext'].'" load="" controls="controls"></video>\' />'."\n";
        // audio
        } elseif ($myfile['bt_type'] == 'music') {
            $form .= '<input onfocus="this.select()" class="text" type="text" value=\'<audio src="'.$simple_URI.'" type="audio/'.$myfile['bt_fileext'].'" load="" controls="controls"></audio>\' />'."\n";
        } else {
            $form .= '<input onfocus="this.select()" class="text" type="text" value=\'[url]'.$simple_URI.'[/url]\' />'."\n";
        }

        $form .= '</div>'."\n";

        // la partie avec l’édition du contenu.
        $form .= '<div id="img-others-infos">'."\n";
        $form .= "\t".'<p><label for="nom_entree">'.ucfirst($GLOBALS['lang']['label_dp_nom']).'</label><input type="text" id="nom_entree" name="nom_entree" placeholder="" value="'.pathinfo($myfile['bt_filename'], PATHINFO_FILENAME).'" size="60" class="text" /></p>'."\n";
        $form .= "\t".'<p><label for="description">'.$GLOBALS['lang']['label_dp_description'].'</label><textarea class="text" name="description" id="description" cols="60" rows="5" placeholder="'.$GLOBALS['lang']['placeholder_description'].'" >'.$myfile['bt_wiki_content'].'</textarea></p>'."\n";
        $form .= "\t".'<p><label for="dossier">'.$GLOBALS['lang']['label_dp_dossier'].'</label><input type="text" name="dossier" placeholder="'.$GLOBALS['lang']['placeholder_folder'].'" value="'.(!empty($myfile['bt_dossier']) ? $myfile['bt_dossier'] : '').'" size="60" class="text" /></p>'."\n";
        $checked = ($myfile['bt_statut'] == 0) ? 'checked ' : '';
        $form .= "\t".'<p><input type="checkbox" id="statut" name="statut" '.$checked.' class="checkbox" /><label for="statut">'.$GLOBALS['lang']['label_file_priv'].'</label></p>';
        $form .= "\t".'<p class="submit-bttns">'."\n";
        $form .= "\t\t".'<button class="submit button-delete" type="button" name="supprimer" onclick="rmFichier(this)">'.$GLOBALS['lang']['supprimer'].'</button>'."\n";
        $form .= "\t\t".'<button class="submit button-cancel" type="button" onclick="annuler(\'fichiers.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
        $form .= "\t\t".'<button class="submit button-submit" type="submit" name="editer">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
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
function afficher_liste_fichiers($tableau)
{
    $dossier = $GLOBALS['racine'].DIR_DOCUMENTS;
    $dossier_relatif = BT_ROOT.DIR_DOCUMENTS;
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
                if (empty($type)) {
                    break;
                }
                $i++;
                $out .= "\t".'<button id="butIdtype'.$i.'" onclick="type_sort(\''.$type.'\', \'butIdtype'.$i.'\');">'.$type.' ('.$amount.')</button>'."\n";
            }
            $out .= '</div>'."\n";
        }

            // the files
            $out .= '<table id="file-list">'."\n";
            $out .= "\t".'<thead>'."\n";
                $out .= "\t\t".'<tr><th></th><th>'.$GLOBALS['lang']['label_dp_nom'].'</th><th>'.$GLOBALS['lang']['label_dp_poids'].'</th><th>'.$GLOBALS['lang']['label_dp_date'].'</th><th></th><th></th></tr>'."\n";
            $out .= "\t".'</thead>'."\n";
            $out .= "\t".'<tbody>'."\n";

        foreach ($tableau as $file) {
            $out .= "\t".'<tr id="bloc_'.$file['bt_id'].'" data-type="'.$file['bt_type'].'">'."\n";
            $out .= "\t\t".'<td><img src="style/filetypes/'.$file['bt_type'].'.png" id="'.$file['bt_id'].'" alt="'.$file['bt_filename'].'" /></td>'."\n";
            $out .= "\t\t".'<td><a href="fichiers.php?file_id='.$file['bt_id'].'&amp;edit">'.$file['bt_filename'].'</a></td>'."\n";
            $out .= "\t\t".'<td>'.taille_formate($file['bt_filesize']).'</td>'."\n";
            $out .= "\t\t".'<td>'.date_formate($file['bt_id']).'</td>'."\n";
            $out .= "\t\t".'<td><a href="'.$dossier_relatif.'/'.$file['bt_filename'].'" download>DL</a></td>'."\n";
            $out .= "\t\t".'<td><a href="#" onclick="request_delete_form(\''.$file['bt_id'].'\'); return false;" >DEL</a></td>'."\n";
            $out .= "\t".'</tr>'."\n";
        }
            $out .= "\t".'</tbody>'."\n";
            $out .= '</table>'."\n";

        $out .= '</div>'."\n";
    }
    echo $out;
}


// gère le filtre de recherche sur les images : recherche par chaine (GET[q]), par type, par statut ou par date.
// pour le moment, il n’est utilisé que du côté Admin (pas de tests sur les statut, date, etc.).
function liste_base_files($tri_selon, $motif, $nombre)
{
    $tableau_sortie = array();

    switch ($tri_selon) {
        case 'statut':
            foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
                if ($file['bt_statut'] == $motif) {
                    $tableau_sortie[$id] = $file;
                }
            }
            break;

        case 'date':
            foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
                if (($pos = strpos($file['bt_id'], $motif)) !== false and $pos == 0) {
                    $tableau_sortie[$id] = $file;
                }
            }
            break;

        case 'type':
            foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
                if ($file['bt_type'] == $motif) {
                    $tableau_sortie[$id] = $file;
                }
            }
            break;

        case 'extension':
            foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
                if (($file['bt_fileext'] == $motif)) {
                    $tableau_sortie[$id] = $file;
                }
            }
            break;

        case 'dossier':
            foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
                if (in_array($motif, explode(',', $file['bt_dossier']))) {
                    $tableau_sortie[$id] = $file;
                }
            }
            break;

        case 'recherche':
            foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
                if (strpos($file['bt_content'].' '.$file['bt_filename'], $motif)) {
                    $tableau_sortie[$id] = $file;
                }
            }
            break;

        default:
            $tableau_sortie = $GLOBALS['liste_fichiers'];
    }

    if (isset($nombre) and is_numeric($nombre) and $nombre > 0) {
        $tableau_sortie = array_slice($tableau_sortie, 0, $nombre);
    }

    return $tableau_sortie;
}
