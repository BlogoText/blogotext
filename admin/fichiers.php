<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BlogoText/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***

require_once 'inc/boot.php';


$vars = array(
    'upload' => (filter_input(INPUT_POST, 'upload') !== null),
    'editer' => (filter_input(INPUT_POST, 'editer') !== null),
    'supprimer' => (filter_input(INPUT_POST, 'supprimer') !== null),

    'suppr' => (filter_input(INPUT_GET, 'suppr') !== null),
    'ajout' => (filter_input(INPUT_GET, 'ajout') !== null),

    '_verif_envoi' => (filter_input(INPUT_POST, '_verif_envoi') !== null),
    'is_it_edit' => (string)filter_input(INPUT_POST, 'is_it_edit'),
    'filename' => (string)filter_input(INPUT_POST, 'filename'),
    'sha1_file' => (string)filter_input(INPUT_POST, 'sha1_file'),
    'path' => (string)filter_input(INPUT_POST, 'path'),
    'filesize' => (string)filter_input(INPUT_POST, 'filesize'),
    'token' => (string)filter_input(INPUT_POST, 'token'),
    'fichier' => (string)filter_input(INPUT_POST, 'fichier'),
    'filename' => (string)filter_input(INPUT_POST, 'filename'),

    'file_id' => (string)filter_input(INPUT_GET, 'file_id'),
    'filtre' => (string)filter_input(INPUT_GET, 'filtre'),
    'q' => (string)filter_input(INPUT_GET, 'q'),
    'extension' => (string)filter_input(INPUT_GET, 'extension'),
);


/**
 *  From a filesize in bytes, returns computed size in kiB, MiB, GiB…
 */
function format_size($size)
{
    $prefix = array (
             $GLOBALS['lang']['byte_symbol'],
        'ki'.$GLOBALS['lang']['byte_symbol'],
        'Mi'.$GLOBALS['lang']['byte_symbol'],
        'Gi'.$GLOBALS['lang']['byte_symbol'],
        'Ti'.$GLOBALS['lang']['byte_symbol'],
    );
    $ten = 0;
    while ($size / (pow(2, 10 * $ten)) > 1024) {
        $ten++;
    }
    $size /= pow(2, 10 * $ten);
    if ($ten != 0) {
        $size = sprintf('%.1f', $size);
    }

    return $size.' '.$prefix[$ten];
}

/**
 * Add one file.
 */
function display_form_file($errors, $files)
{
    global $vars;
    // This will not work on nginx when `client_max_body_size` directive is not updated accordingly to `upload_max_filesize` or `post_max_size` too.
    // See http://stackoverflow.com/a/35794955/1117028
    $maxFileSize = format_size(min(return_bytes(ini_get('upload_max_filesize')), return_bytes(ini_get('post_max_size'))));

    if ($errors) {
        echo erreurs($errors);
    }

    $form = '<form id="form-image" class="bordered-formbloc" enctype="multipart/form-data" method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'" onsubmit="submitdnd(event);">';

    if (!$files) {
        // No file, display the form to add a new one
        $form .= '<fieldset class="pref" >';

        $form .= '<div id="form-dragndrop">';
            $form .= '<div id="dragndrop-area" ondragover="event.preventDefault();" ondrop="handleDrop(event);" >';
            $form .= '<div id="dragndrop-title">';
            $form .= $GLOBALS['lang']['img_drop_files_here'];
            $form .= '<div class="upload-info">('.$GLOBALS['lang']['label_jusqua'].$maxFileSize.$GLOBALS['lang']['label_parfichier'].')</div>';
            $form .= '</div>';
            $form .= '<p>'.$GLOBALS['lang']['ou'].'</p>';
            $form .= '<div id="file-input-wrapper"><input name="fichier" id="fichier" class="text" type="file" required="" /><label for="fichier"></label></div>';
            $form .= '<button type="button" class="specify-link button-cancel" id="click-change-form" onclick="return switchUploadForm();" data-lang-url="'.$GLOBALS['lang']['img_specifier_url'].'" data-lang-file="'.$GLOBALS['lang']['img_upload_un_fichier'].'">'.$GLOBALS['lang']['img_specifier_url'].'</button>';
            $form .= '</div>';
            $form .= '<div id="count"></div>';
            $form .= '<div id="result"></div>';
        $form .= '</div>';

        $form .= '<div id="img-others-infos">';

        $form .= '<p><label for="nom_entree">'.$GLOBALS['lang']['label_dp_nom'].'</label><input type="text" id="nom_entree" name="nom_entree" placeholder="'.$GLOBALS['lang']['placeholder_nom_fichier'].'" value="" size="60" class="text" /></p>';
        $form .= '<p><label for="description">'.$GLOBALS['lang']['label_dp_description'].'</label><textarea class="text" id="description" name="description" cols="60" rows="5" placeholder="'.$GLOBALS['lang']['placeholder_description'].'" ></textarea></p>';
        $form .= '<p><label for="dossier">'.$GLOBALS['lang']['label_dp_dossier'].'</label><input type="text" id="dossier" name="dossier" placeholder="'.$GLOBALS['lang']['placeholder_folder'].'" value="" size="60" class="text" /></p>';
        $form .= hidden_input('token', new_token(), 'id');
        $form .= '<p><input type="checkbox" id="statut" name="statut" class="checkbox" /><label for="statut">'.$GLOBALS['lang']['label_file_priv'].'</label></p>';
        $form .= hidden_input('_verif_envoi', '1');

        $form .= '<p class="submit-bttns"><button class="submit button-submit" type="submit" name="upload">'.$GLOBALS['lang']['img_upload'].'</button></p>';
        $form .= '</div>';

        $form .= '</fieldset>';
    } elseif ($files && preg_match('#^\d{14}$#', $vars['file_id'])) {
        // If ID in URL, it coulb be only one file, this explains the [0]
        $myFile = $files[0];

        // Retrieve relative and absolute file paths
        $folderPath = ($myFile['bt_type'] == 'image') ? URL_IMAGES : URL_DOCUMENTS;
        $filePath = (empty($myFile['bt_path']) ? '' : $myFile['bt_path'].'/').$myFile['bt_filename'];
        $urlRelative = parse_url($folderPath, PHP_URL_PATH).$filePath;
        $urlAbsolute = $folderPath.$filePath;

        $form .= '<div class="edit-fichier">';

        // Display the media
        if ($myFile['bt_type'] == 'video') {
            $form .= '<div class="display-media"><video class="media" src="'.$urlRelative.'" type="video/'.$myFile['bt_fileext'].'" load controls="controls"></video></div>';
        } elseif ($myFile['bt_type'] == 'image') {
            $form .= '<div class="display-media"><a href="'.$urlRelative.'"><img class="media" src="'.$urlRelative.'" alt="'.$myFile['bt_filename'].'" width="'.$myFile['bt_dim_w'].'" height="'.$myFile['bt_dim_h'].'" /></a></div>';
        } elseif ($myFile['bt_type'] == 'music') {
            $form .= '<div class="display-media"><audio class="media" src="'.$urlRelative.'" type="audio/'.$myFile['bt_fileext'].'" load controls="controls"></audio></div>';
        }

        // File informations
        $form .= '<ul id="fichier-meta-info">';
            $form .= '<li><b>'.$GLOBALS['lang']['label_dp_nom'].'</b> '.$myFile['bt_filename'].'</li>';
            $form .= '<li><b>'.$GLOBALS['lang']['label_dp_type'].'</b> '.$myFile['bt_type'].' (.'.$myFile['bt_fileext'].')</li>';
        if ($myFile['bt_type'] == 'image') {
            $form .= '<li><b>'.$GLOBALS['lang']['label_dp_dimensions'].'</b> '.$myFile['bt_dim_w'].'px × '.$myFile['bt_dim_h'].'px'.'</li>';
        }
            $form .= '<li><b>'.$GLOBALS['lang']['label_dp_date'].'</b>'.date_formate($myFile['bt_id']).', '.heure_formate($myFile['bt_id']).'</li>';
            $form .= '<li><b>'.$GLOBALS['lang']['label_dp_poids'].'</b>'.format_size($myFile['bt_filesize']).'</li>';
            $form .= '<li><b>'.$GLOBALS['lang']['label_dp_checksum'].'</b>'.$myFile['bt_checksum'].'</li>';
            $form .= '<li><b>'.$GLOBALS['lang']['label_dp_visibilite'].'</b>'.(($myFile['bt_statut'] == 1) ? 'Publique' : 'Privée').'</li>';
        $form .= '</ul>';

        // Integration codes
        $form .= '<div id="interg-codes">';
        $form .= '<p><strong>'.$GLOBALS['lang']['label_codes'].'</strong></p>';
        $form .= '<input onfocus="this.select()" class="text" type="text" value=\''.$urlAbsolute.'\' />';
        $form .= '<input onfocus="this.select()" class="text" type="text" value=\'<a href="'.$urlAbsolute.'">'.$myFile['bt_filename'].'</a>\' />';
        if ($myFile['bt_type'] == 'image') {
            $form .= '<input onfocus="this.select()" class="text" type="text" value=\'<img src="'.$urlRelative.'" alt="i" width="'.$myFile['bt_dim_w'].'" height="'.$myFile['bt_dim_h'].'" />\' />';
            $form .= '<input onfocus="this.select()" class="text" type="text" value=\'[img]'.$urlRelative.'[/img]\' />';
            $form .= '<input onfocus="this.select()" class="text" type="text" value=\'[spoiler][img]'.$urlRelative.'[/img][/spoiler]\' />';
        } elseif ($myFile['bt_type'] == 'video') {
            $form .= '<input onfocus="this.select()" class="text" type="text" value=\'<video src="'.$urlRelative.'" type="video/'.$myFile['bt_fileext'].'" load="" controls="controls"></video>\' />';
        } elseif ($myFile['bt_type'] == 'music') {
            $form .= '<input onfocus="this.select()" class="text" type="text" value=\'<audio src="'.$urlRelative.'" type="audio/'.$myFile['bt_fileext'].'" load="" controls="controls"></audio>\' />';
        } else {
            $form .= '<input onfocus="this.select()" class="text" type="text" value=\'[url]'.$urlRelative.'[/url]\' />';
        }

        $form .= '</div>';

        // Edit the file
        $form .= '<div id="img-others-infos">';
        $form .= '<p><label for="nom_entree">'.ucfirst($GLOBALS['lang']['label_dp_nom']).'</label><input type="text" id="nom_entree" name="nom_entree" placeholder="" value="'.pathinfo($myFile['bt_filename'], PATHINFO_FILENAME).'" size="60" class="text" /></p>';
        $form .= '<p><label for="description">'.$GLOBALS['lang']['label_dp_description'].'</label><textarea class="text" name="description" id="description" cols="60" rows="5" placeholder="'.$GLOBALS['lang']['placeholder_description'].'" >'.$myFile['bt_wiki_content'].'</textarea></p>';
        $form .= '<p><label for="dossier">'.$GLOBALS['lang']['label_dp_dossier'].'</label><input type="text" name="dossier" placeholder="'.$GLOBALS['lang']['placeholder_folder'].'" value="'.((!empty($myFile['bt_dossier'])) ? $myFile['bt_dossier'] : '').'" size="60" class="text" /></p>';
        $checked = ($myFile['bt_statut'] == 0) ? 'checked ' : '';
        $form .= '<p><input type="checkbox" id="statut" name="statut" '.$checked.' class="checkbox" /><label for="statut">'.$GLOBALS['lang']['label_file_priv'].'</label></p>';
        $form .= '<p class="submit-bttns">';
        $form .= '<button class="submit button-delete" type="button" name="supprimer" onclick="rmFichier(this)">'.$GLOBALS['lang']['supprimer'].'</button>';
        $form .= '<button class="submit button-cancel" type="button" onclick="annuler(\'fichiers.php\');">'.$GLOBALS['lang']['annuler'].'</button>';
        $form .= '<button class="submit button-submit" type="submit" name="editer">'.$GLOBALS['lang']['envoyer'].'</button>';
        $form .= '</p>';
        $form .= '</div>';

        $form .= hidden_input('_verif_envoi', 1);
        $form .= hidden_input('is_it_edit', 'yes');
        $form .= hidden_input('file_id', $myFile['bt_id']);
        $form .= hidden_input('filename', $myFile['bt_filename']);
        $form .= hidden_input('sha1_file', $myFile['bt_checksum']);
        $form .= hidden_input('path', $myFile['bt_path']);
        $form .= hidden_input('filesize', $myFile['bt_filesize']);
        $form .= hidden_input('token', new_token());
        $form .= '</div>';
    }
    $form .= '</form>';

    echo $form;
}

/**
 * Display the list of files.
 */
function display_files_list($arr)
{
    if (!$arr) {
        return;
    }

    // Sort files into "logical folders"
    $lstype = array();
    $arr = tri_selon_sous_cle($arr, 'bt_type');
    foreach ($arr as $file) {
        $lstype[$file['bt_type']] = (isset($lstype[$file['bt_type']])) ? $lstype[$file['bt_type']]+1 : 1 ;
    }

    $out = '<div id="files-section">';
    // Buttons
    if ($lstype) {
        $out .= '<div class="list-buttons" id="list-types">';
        $idx = 0;
        $out .= '<button class="current" id="butIdtype'.$idx.'" onclick="type_sort(\'\', \'butIdtype'.$idx.'\');">'.count($arr).' '.$GLOBALS['lang']['label_fichiers'].'</button>';
        foreach ($lstype as $type => $amount) {
            if (!$type) {
                break;
            }
            ++$idx;
            $out .= '<button id="butIdtype'.$idx.'" onclick="type_sort(\''.$type.'\', \'butIdtype'.$idx.'\');">'.$type.' ('.$amount.')</button>';
        }
        $out .= '</div>';
    }

    // Files
    $out .= '<table id="file-list">';
    $out .= '<thead>';
        $out .= '<tr><th></th><th>'.$GLOBALS['lang']['label_dp_nom'].'</th><th>'.$GLOBALS['lang']['label_dp_poids'].'</th><th>'.$GLOBALS['lang']['label_dp_date'].'</th><th></th><th></th></tr>';
    $out .= '</thead>';
    $out .= '<tbody>';

    foreach ($arr as $file) {
        $out .= '<tr id="bloc_'.$file['bt_id'].'" data-type="'.$file['bt_type'].'">';
        $out .= '<td><img src="style/filetypes/'.$file['bt_type'].'.png" id="'.$file['bt_id'].'" alt="'.$file['bt_filename'].'" /></td>';
        $out .= '<td><a href="fichiers.php?file_id='.$file['bt_id'].'&amp;edit">'.$file['bt_filename'].'</a></td>';
        $out .= '<td>'.format_size($file['bt_filesize']).'</td>';
        $out .= '<td>'.date_formate($file['bt_id']).'</td>';
        $out .= '<td><a href="'.URL_DOCUMENTS.$file['bt_filename'].'" download>DL</a></td>';
        $out .= '<td><a href="#" onclick="request_delete_form(\''.$file['bt_id'].'\'); return false;" >DEL</a></td>';
        $out .= '</tr>';
    }
        $out .= '</tbody>';
        $out .= '</table>';

    $out .= '</div>';

    echo $out;
}

/**
 *  Traitment on one file (addition, modification and deletion).
 */
function traitment_form_file($file)
{
    global $vars;
    $fileId = (string)filter_input(INPUT_POST, 'file_id');
    if ($vars['upload']) {
        // Addition
        // via $_FILES
        if (isset($_FILES['fichier'])) {
            $newFile = bdd_fichier($file, 'ajout-nouveau', 'upload', $_FILES['fichier']);
        }
        // via $_POST d’une url
        if ($vars['fichier']) {
            $newFile = bdd_fichier($file, 'ajout-nouveau', 'download', $vars['fichier']);
        }
        $file = (is_null($newFile)) ? $file : $newFile;
        redirection(basename($_SERVER['SCRIPT_NAME']).'?file_id='.$file['bt_id'].'&msg=confirm_fichier_ajout');
    } elseif ($vars['editer'] && !$vars['suppr']) {
        // Edition
        $oldFileName = $vars['filename'];  // Name can be edited too. This is old name, the new one is in $file[].
        bdd_fichier($file, 'editer-existant', '', $oldFileName);
    } elseif ($vars['supprimer'] && preg_match('#^\d{14}$#', $fileId)) {
        // Deletion
        $response = bdd_fichier($file, 'supprimer-existant', '', $fileId);
        if ($response == 'error_suppr_file_suppr_error') {
            redirection(basename($_SERVER['SCRIPT_NAME']).'?errmsg=error_fichier_suppr&what=file_suppr_error');
        } elseif ($response == 'no_such_file_on_disk') {
            redirection(basename($_SERVER['SCRIPT_NAME']).'?msg=error_fichier_suppr&what=but_no_such_file_on_disk2');
        } elseif ($response == 'success') {
            redirection(basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_fichier_suppr');
        }
    }
}

/**
 * Pictures wall.
 *
 * Take an array containing pictures and return HTML + JSON.
 * The JSON is parsed directly into the navigator using JS.
 */
function display_pictures_list($images)
{
    if (!$images) {
        return;
    }

    // Sort pictures into "logical folders"
    $lsFolder = '';
    foreach ($images as $image) {
        if (!empty($image['bt_dossier'])) {
            $lsFolder .= $image['bt_dossier'].',';
        }
    }
    $arr = explode(',', $lsFolder);
    $arr = array_map('trim', $arr);
    $arrUniq = array();
    // array "folder" => "nb img per folder"
    foreach ($arr as $idx) {
        $arrUniq[$idx] = (isset($arrUniq[$idx])) ? $arrUniq[$idx] + 1 : 1;
    }

    // HTML of the Slider.
    $out = '<div id="slider">';
    $out .= '<div id="slider-main-content">';

    $out .= '<ul id="slider-nav-bar">';
    $out .= '<li><button id="slider-nav-close" class="slider-nav-button" onclick="slideshow(\'close\');"></button></li>';
    $out .= '<li><button id="slider-nav-dl"    class="slider-nav-button" onclick="triggerClick(document.getElementById(\'slider-nav-dl-link\'))" title="'.$GLOBALS['lang']['telecharger'].'"></button><a id="slider-nav-dl-link" download></a></li>';
    $out .= '<li><button id="slider-nav-share" class="slider-nav-button" onclick="triggerClick(document.getElementById(\'slider-nav-share-link\'))" title="'.$GLOBALS['lang']['partager'].   '"></button><a id="slider-nav-share-link"></a></li>';
    $out .= '<li><button id="slider-nav-infos" class="slider-nav-button" onclick="" title="'.$GLOBALS['lang']['infos'].      '"></button></li>';
    $out .= '<li><button id="slider-nav-edit"  class="slider-nav-button" onclick="triggerClick(document.getElementById(\'slider-nav-edit-link\'))" title="'.$GLOBALS['lang']['editer'].     '"></button><a id="slider-nav-edit-link"></a></li>';

    $out .= '<li><button id="slider-nav-suppr" class="slider-nav-button" title="'.$GLOBALS['lang']['supprimer'].  '"></button></li>';
    $out .= '</ul>';
    $out .= '<div id="slider-display">';
    $out .= '<img id="slider-img" src="" alt=""/>';
    $out .= '<div id="slider-box-buttons">';
    $out .= '<ul id="slider-buttons">';
    $out .= '<li><button id="slider-prev" onclick="slideshow(\'prev\');"></button></li>';
    $out .= '<li class="spacer"></li>';
    $out .= '<li><button id="slider-next" onclick="slideshow(\'next\');"></button></li>';
    $out .= '</ul>';
    $out .= '</div>';
    $out .= '</div>';
    $out .= '</div>';

    $out .= '<div id="slider-infos">';
    $out .= '<div id="infos-title"><span>'.$GLOBALS['lang']['infos'].'</span><button onclick="document.getElementById(\'slider-main-content\').classList.remove(\'infos-on\');"></button></div>';
    $out .= '<div id="infos-content"></div>';
    $out .= '<div id="infos-details"></div>';
    $out .= '</div>';
    $out .= '</div><!--end slider-->';

    // Send all the images info in JSON
    $out .= '<script>';
    $out .=  'var imgs = { list: [';
    // TODO: filename has 3 identical paths?!
    foreach ($images as $idx => $im) {
        $img_path_src = $im['bt_path'].'/'.$im['bt_filename'];

        // TODO: bricolage, we need to ensure the DTB consistency elsewhere
        if (!isset($im['bt_dim_w']) && $im['bt_fileext'] != 'svg') {
            list($im['bt_dim_w'], $im['bt_dim_h']) = getimagesize(DIR_IMAGES.$img_path_src);
        } else {
            // '""' for the PHP to JS
            $im['bt_dim_w'] = $im['bt_dim_h'] = '""';
        }

        $img_path_src = $im['bt_path'].'/'.$im['bt_filename'];
        $thumb_name = chemin_thb_img($img_path_src);
        $thumbnail = (is_file(DIR_IMAGES.$thumb_name)) ? $thumb_name : $img_path_src;

        $out .= '{
            index: '.(string)$idx.',
            filename: [
                "'.URL_IMAGES.$img_path_src.'",
                "'.$im['bt_filename'].'",
                "'.URL_IMAGES.$thumbnail.'",
                "'.URL_IMAGES.$img_path_src.'"
            ],
            id: '.(string)$im['bt_id'].',
            desc: "'.addslashes(preg_replace('#(\n|\r|\n\r)#', '', nl2br($im['bt_content']))).'",
            dossier: "'.(isset($im['bt_dossier']) ? $im['bt_dossier'] : '').'",
            width: '.(string)$im['bt_dim_w'].',
            height: '.(string)$im['bt_dim_h'].',
            weight: '.(string)$im['bt_filesize'].',
            date: [
                "'.date_formate($im['bt_id']).'",
                "'.heure_formate($im['bt_id']).'"
            ]
        }, ';
    }
    $out = rtrim($out, ', ');
    $out .= ']};';
    $out .= '</script>';

    // Pictures
    $out .= '<div id="image-section">';
    // Buttons
    if ($arrUniq) {
        $out .= '<div class="list-buttons" id="list-albums">';
        $idx = 0;
        $out .= '<button class="current" id="butId'.$idx.'" onclick="folder_sort(\'\', \'butId'.$idx.'\');">'.(count($images)).' '.$GLOBALS['lang']['label_images'].'</button>';
        foreach ($arrUniq as $fol => $nb) {
            if (!$fol) {
                break;
            }
            $idx++;
            $out .= '<button id="butId'.$idx.'" onclick="folder_sort(\''.$fol.'\', \'butId'.$idx.'\');">'.$fol.' ('.$nb.')</button>';
        }
        $out .= '</div>';
    }
    $out .= '<div id="image-wall">';
    $out .= '</div>';
    $out .= '</div>';

    echo $out;
}



/**
 * Process
 */

$file = array();
$GLOBALS['liste_fichiers'] = open_serialzd_file(FILES_DB);

// Search / Sort
if ($vars['filtre']) {
    // For "type" the requests is "type.$search" : here we split the type of search and what we search.
    $type = substr($vars['filtre'], 0, -strlen(strstr($vars['filtre'], '.')));
    $search = htmlspecialchars(ltrim(strstr($vars['filtre'], '.'), '.'));

    if (preg_match('#^\d{6}(\d{1,8})?$#', $vars['filtre'])) {
        $files = liste_base_files('date', $vars['filtre'], '');
    } elseif ($vars['filtre'] == 'draft') {
        $files = liste_base_files('statut', 0, '');
    } elseif ($vars['filtre'] == 'pub') {
        $files = liste_base_files('statut', 1, '');
    } elseif ($type == 'type' && $search) {
        $files = liste_base_files('type', $search, '');
    } else {
        $files = $GLOBALS['liste_fichiers'];
    }
} elseif ($vars['q']) {
    $files = liste_base_files('recherche', htmlspecialchars(urldecode($vars['q'])), '');
} elseif ($vars['extension']) {
    $files = liste_base_files('extension', htmlspecialchars($vars['extension']), '');
} elseif ($vars['file_id'] && preg_match('#^\d{14}$#', $vars['file_id'])) {
    foreach ($GLOBALS['liste_fichiers'] as $fich) {
        if ($fich['bt_id'] == $vars['file_id']) {
            $file = $fich;
            break;
        }
    }
    if ($file) {
        $files[$_GET['file_id']] = $file;
    }
} else {
    $files = $GLOBALS['liste_fichiers'];
}

// Traitment
$errors = array();
if (isset($_POST['_verif_envoi'])) {
    $file = init_post_fichier();
    $errors = valider_form_fichier($file);
    if (!$errors) {
        traitment_form_file($file);
    }
}



/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['titre_fichier']);

echo '<div id="header">';
    echo '<div id="top">';
        tpl_show_msg();
        echo moteur_recherche();
        echo tpl_show_topnav($GLOBALS['lang']['titre_fichier']);
    echo '</div>';
echo '</div>';

echo '<div id="axe">';

// Subnav
echo '<div id="subnav">';
afficher_form_filtre('fichiers', htmlspecialchars($vars['filtre']));
echo '</div>';

echo '<div id="page">';

// Check files existance
$realFiles = array();
foreach ($files as $i => $file) {
    if (!isset($file['bt_path'])) {
        $file['bt_path'] = '';
    }
    $folder = ($file['bt_type'] == 'image') ? DIR_IMAGES.$file['bt_path'] : DIR_DOCUMENTS;
    if ($file['bt_filename'] != 'index.php' && is_file($folder.'/'.$file['bt_filename'])) {
        $realFiles[] = $file;
    }
}

if ($vars['ajout']) {
    display_form_file('', '');
} elseif ($vars['file_id']) {
    display_form_file($errors, $realFiles);
} else {
    if (!$vars['filtre']) {
        display_form_file($errors, '');
    }

    // séparation des images des autres types de fichiers
    $files = array();
    $images = array();
    foreach ($realFiles as $file) {
        if ($file['bt_type'] == 'image') {
            $images[] = $file;
        } else {
            $files[] = $file;
        }
    }

    display_pictures_list($images);
    display_files_list($files);
}

echo '<script src="style/javascript.js"></script>';
echo <<<EOS
<script>
    var curr_img = (typeof imgs != "undefined") ? imgs.list.slice(0, 25) : "",
        counter = 0,
        nbDraged = false,
        nbDone = 0,
        curr_max = curr_img.length - 1,
        list = [],  // file list
        xDown = null,
        yDown = null,
        doTouchBreak = null,
        minDelta = 200;

    window.addEventListener("load", function() { image_vignettes(); });
    document.addEventListener("touchstart", handleTouchStart, false);
    document.addEventListener("touchmove", swipeSlideshow, false);
    document.addEventListener("touchend", handleTouchEnd, false);
    document.addEventListener("touchcancel", handleTouchEnd, false);
    document.body.addEventListener("dragover", handleDragOver, true);
    document.body.addEventListener("dragleave", handleDragLeave, false);
    document.body.addEventListener("dragend", handleDragEnd, false);
EOS;
echo php_lang_to_js(0);
echo '</script>';

echo tpl_get_footer($begin);
