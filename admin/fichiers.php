<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BoboTiG/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***

require_once 'inc/boot.php';



// from a filesize in bytes, returns computed size in kiB, MiB, GiB…
function taille_formate($taille)
{
    $prefixe = array (
             $GLOBALS['lang']['byte_symbol'],
        'ki'.$GLOBALS['lang']['byte_symbol'],
        'Mi'.$GLOBALS['lang']['byte_symbol'],
        'Gi'.$GLOBALS['lang']['byte_symbol'],
        'Ti'.$GLOBALS['lang']['byte_symbol'],
    );
    $dix = 0;
    while ($taille / (pow(2, 10*$dix)) > 1024) {
        $dix++;
    }
    $taille = $taille / (pow(2, 10*$dix));
    if ($dix != 0) {
        $taille = sprintf('%.1f', $taille);
    }

    return $taille.' '.$prefixe[$dix];
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

    if (empty($fichiers)) { // si PAS fichier donné : formulaire nouvel envoi.
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

        // image or doc ?
        if ($myfile['bt_type'] == 'image') {
            // dirty, todo : need a way to get relative url for images/documents
            $url_relative = str_replace(URL_ROOT, '/', URL_IMAGES);
            // $simple_URI = rtrim($url_relative, '/').$myfile['bt_path'].'/'.$myfile['bt_filename'];
            // $absolute_URI = rtrim(URL_IMAGES, '/').$myfile['bt_path'].'/'.$myfile['bt_filename'];
            $simple_URI = $url_relative.$myfile['bt_path'].'/'.$myfile['bt_filename'];
            $absolute_URI = URL_IMAGES.$myfile['bt_path'].'/'.$myfile['bt_filename'];
        } else {
            // dirty, todo : need a way to get relative url for images/documents
            $url_relative = str_replace(URL_ROOT, '/', URL_DOCUMENTS);
            // $simple_URI = rtrim($url_relative, '/').$myfile['bt_path'].'/'.$myfile['bt_filename'];
            // $absolute_URI = rtrim(URL_DOCUMENTS, '/').$myfile['bt_path'].'/'.$myfile['bt_filename'];
            $simple_URI = $url_relative.$myfile['bt_path'].'/'.$myfile['bt_filename'];
            $absolute_URI = URL_DOCUMENTS.$myfile['bt_path'].'/'.$myfile['bt_filename'];
        }

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
        $form .= "\t".'<p><label for="dossier">'.$GLOBALS['lang']['label_dp_dossier'].'</label><input type="text" name="dossier" placeholder="'.$GLOBALS['lang']['placeholder_folder'].'" value="'.((!empty($myfile['bt_dossier'])) ? $myfile['bt_dossier'] : '').'" size="60" class="text" /></p>'."\n";
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
    $dossier = DIR_DOCUMENTS;
    $out = '';
    if ($tableau) {
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
                ++$i;
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
            $out .= "\t\t".'<td><a href="'.$dossier.$file['bt_filename'].'" download>DL</a></td>'."\n";
            $out .= "\t\t".'<td><a href="#" onclick="request_delete_form(\''.$file['bt_id'].'\'); return false;" >DEL</a></td>'."\n";
            $out .= "\t".'</tr>'."\n";
        }
            $out .= "\t".'</tbody>'."\n";
            $out .= '</table>'."\n";

        $out .= '</div>'."\n";
    }
    echo $out;
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

/*
    Pour les vignettes dans le mur d’images.
    Avec en entrée le tableau contenant les images, retourne le HTML + JSON du mur d’image.
    Le JSON est parsé en JS du côté navigateur pour former le mur d’images.
*/
function afficher_liste_images($images)
{
    $dossier_http = URL_IMAGES;
    $dossier_relatif = DIR_IMAGES;
    $out = '';
    $i = 0;
    if ($images) {
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
        $out .= '<script>';
        $out .=  'var imgs = {"list": ['."\n";

        foreach ($images as $i => $im) {
            //debug($im);
            $rel_thb_src = chemin_thb_img_test($dossier_http.$im['bt_path'].'/'.$im['bt_filename']);
            $out .= '
            {
                "index": "'.$i.'",
                "filename":
                    [
                    "'.$dossier_http.$im['bt_path'].'/'.$im['bt_filename'].'",
                    "'.$im['bt_filename'].'",
                    "'.$rel_thb_src.'",
                    "'.$dossier_http.$im['bt_path'].'/'.$im['bt_filename'].'"
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
        if ($lsf_uniq) {
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


$fichier = array();
$GLOBALS['liste_fichiers'] = open_serialzd_file(FILES_DB);

// recherche / tri
if (!empty($_GET['filtre'])) {
    // for "type" the requests is "type.$search" : here we split the type of search and what we search.
    $type = substr($_GET['filtre'], 0, -strlen(strstr($_GET['filtre'], '.')));
    $search = htmlspecialchars(ltrim(strstr($_GET['filtre'], '.'), '.'));

    // selon date
    if (preg_match('#^\d{6}(\d{1,8})?$#', $_GET['filtre'])) {
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
} elseif (isset($_GET['file_id']) and preg_match('/\d{14}/', ($_GET['file_id']))) {
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

echo tpl_get_html_head($GLOBALS['lang']['titre_fichier']);

echo '<div id="header">'."\n";
    echo '<div id="top">'."\n";
    tpl_show_msg();
    echo moteur_recherche();
    tpl_show_topnav($GLOBALS['lang']['titre_fichier']);
    echo '</div>'."\n";
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

echo '<div id="page">'."\n";

// vérifie que les fichiers de la liste sont bien présents sur le disque dur
$real_fichiers = array();
if ($fichiers) {
    foreach ($fichiers as $i => $file) {
        $folder = ($file['bt_type'] == 'image') ? DIR_IMAGES.$file['bt_path'] : DIR_DOCUMENTS;
        if (is_file($folder.'/'.$file['bt_filename']) and ($file['bt_filename'] != 'index.html')) {
            $real_fichiers[] = $file;
        }
    }
}

// ajout d'un nouveau fichier : affichage formulaire, pas des anciens.
if (isset($_GET['ajout'])) {
    afficher_form_fichier('', '', 'fichier');
} // édition d'un fichier
elseif (isset($_GET['file_id'])) {
    afficher_form_fichier($erreurs, $real_fichiers, 'fichier');
} // affichage de la liste des fichiers.
else {
    if (!isset($_GET['filtre']) or empty($_GET['filtre'])) {
        afficher_form_fichier($erreurs, '', 'fichier');
    }

    // séparation des images des autres types de fichiers
    $fichiers = array();
    $images = array();
    foreach ($real_fichiers as $file) {
        if ($file['bt_type'] == 'image') {
            $images[] = $file;
        } else {
            $fichiers[] = $file;
        }
    }

    afficher_liste_images($images);
    afficher_liste_fichiers($fichiers);
}

echo "\n".'<script src="style/javascript.js"></script>'."\n";
echo "\n".'<script>'."\n";
echo 'var curr_img = (typeof imgs != \'undefined\') ? imgs.list.slice(0, 25) : \'\';'."\n";
echo 'var counter = 0;'."\n";
echo 'var nbDraged = false;'."\n";
echo 'var nbDone = 0;'."\n";
echo 'var curr_max = curr_img.length-1;'."\n";
echo 'window.addEventListener(\'load\', function(){ image_vignettes(); })'."\n";
echo 'var list = []; // file list'."\n";

echo 'document.addEventListener(\'touchstart\', handleTouchStart, false);'."\n";
echo 'document.addEventListener(\'touchmove\', swipeSlideshow, false);'."\n";
echo 'document.addEventListener(\'touchend\', handleTouchEnd, false);'."\n";
echo 'document.addEventListener(\'touchcancel\', handleTouchEnd, false);'."\n";
echo 'var xDown = null, yDown = null, doTouchBreak = null, minDelta = 200;'."\n";

echo 'document.body.addEventListener(\'dragover\', handleDragOver, true);'."\n";
echo 'document.body.addEventListener(\'dragleave\', handleDragLeave, false);'."\n";
echo 'document.body.addEventListener(\'dragend\', handleDragEnd, false);'."\n";

echo php_lang_to_js(0);
echo "\n".'</script>'."\n";

echo tpl_get_footer($begin);
