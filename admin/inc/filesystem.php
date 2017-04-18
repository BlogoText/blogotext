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


/*
 * Notez que la liste des fichiers n’est pas intrégrée dans la base de données SQLITE.
 * Ceci pour une raison simple : les fichiers (contrairement aux articles et aux commentaires)
 * sont indépendants. Utiliser une BDD pour les lister est beaucoup moins rapide
 * qu’utiliser un fichier txt normal.
 * Pour le stockage, j’utilise un tableau PHP que j’enregistre directement dans un fichier :
 *   base64_encode(serialize($tableau)) # pompée sur Shaarli, by Sebsauvage.
 */

/**
 * From a filesize (like "20M"), returns a size in bytes.
 */
function return_bytes($val)
{
    $val = trim($val);
    $prefix = strtolower($val[strlen($val)-1]);
    $val = substr($val, 0, -1);
    switch ($prefix) {
        case 'g':
            $val *= 1024*1024*1024;
            break;
        case 'm':
            $val *= 1024*1024;
            break;
        case 'k':
            $val *= 1024;
            break;
    }
    return $val;
}

/**
 * gère le filtre de recherche sur les images : recherche par chaine (GET[q]), par type, par statut ou par date.
 * pour le moment, il n’est utilisé que du côté Admin (pas de tests sur les statut, date, etc.).
 */
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
            $GLOBALS['liste_fichiers'] = open_serialzd_file(FILES_DB);
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

/**
 *
 */
function fichier_prefs()
{
    $vars = array(
        'activer_categories' => 1,
        'afficher_liens' => 1,
        'afficher_rss' => 1,
        'alert_author' => 0,
        'auteur' => addslashes(clean_txt(htmlspecialchars(USER_LOGIN))),
        'auto_check_updates' => 1,
        'automatic_keywords' => 1,
        'comm_defaut_status' => 1,
        'description' => addslashes(clean_txt($GLOBALS['lang']['go_to_pref'])),
        'dl_link_to_files' => 0,
        'email' => 'mail@example.com',
        'format_date' => 0,
        'format_heure' => 0,
        'fuseau_horaire' => 'UTC',
        'global_com_rule' => 0,
        'keywords' => 'blog, blogotext',
        'max_bill_acceuil' => 10,
        'max_bill_admin' => 25,
        'max_comm_admin' => 50,
        'max_rss_admin' => 25,
        'nb_list_linx' => 50,
        'nom_du_site' => BLOGOTEXT_NAME,
        'require_email' => 0,
        'theme_choisi' => 'default',
    );

    if (filter_input(INPUT_POST, '_verif_envoi') !== null) {
        $string = FILTER_SANITIZE_STRING;
        $int = FILTER_VALIDATE_INT;

        $vars = filter_input_array(INPUT_POST, array(
            'auteur' => $string,
            'comm_defaut_status' => $int,
            'description' => $string,
            'dl_link_to_files' => $int,
            'email' => $string | FILTER_VALIDATE_EMAIL,
            'format_date' => $int,
            'format_heure' => $int,
            'fuseau_horaire' => $string,
            'keywords' => $string,
            'lang' => $string,
            'max_bill_acceuil' => $int,
            'max_bill_admin' => $int,
            'max_comm_admin' => $int,
            'max_rss_admin' => $int,
            'nb_list_linx' => $int,
            'nom_du_site' => $string,
            'theme_choisi' => $string,
        ));

        $vars['activer_categories'] = (filter_input(INPUT_POST, 'activer_categories') !== null);
        $vars['global_com_rule'] = (filter_input(INPUT_POST, 'global_com_rule') !== null);
        $vars['afficher_liens'] = (filter_input(INPUT_POST, 'afficher_liens') !== null);
        $vars['afficher_rss'] = (filter_input(INPUT_POST, 'afficher_rss') !== null);
        $vars['automatic_keywords'] = (filter_input(INPUT_POST, 'automatic_keywords') !== null);
        $vars['alert_author'] = (filter_input(INPUT_POST, 'alert_author') !== null);
        $vars['require_email'] = (filter_input(INPUT_POST, 'require_email') !== null);
        $vars['auto_check_updates'] = (filter_input(INPUT_POST, 'auto_check_updates') !== null);
    }

    // Always setted scalars
    $vars['lang'] = (string)filter_input(INPUT_POST, 'langue');
    $vars['racine'] = (string)filter_input(INPUT_POST, 'racine');

    // Some checks, then sort
    if (!preg_match('#^[a-z]{2}$#', $vars['lang'])) {
        $vars['lang'] = 'fr';
    }
    ksort($vars);

    $prefs = "<?php\n";
    foreach ($vars as $key => $value) {
        $prefs .= sprintf(
            "\$GLOBALS['%s'] = %s;\n",
            $key,
            (is_numeric($value) || is_bool($value) || empty($value)) ? (int)$value : '"'.$value.'"'
        );
    }

    return (file_put_contents(FILE_SETTINGS, $prefs, LOCK_EX) !== false);
}

/**
 * TRAITEMENT DU FORMULAIRE DE FICHIER, CÔTÉ BDD
 * Retourne le $fichier de l’entrée (après avoir possiblement changé des trucs, par ex si le fichier existait déjà, l’id retourné change)
 */
function bdd_fichier($fichier, $quoi, $comment, $sup_var)
{
    if ($fichier['bt_type'] == 'image') {
        $dossier = DIR_IMAGES.$fichier['bt_path'].'/';
    } else {
        $dossier = DIR_DOCUMENTS;
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
        while (is_file($dossier.$prefix.$fichier['bt_filename'])) {
            $prefix .= rand(0, 9);
        }

        $dest = $prefix.$fichier['bt_filename'];
        $fichier['bt_filename'] = $dest; // redéfinit le nom du fichier.

        // copie du fichier physique
        // Fichier uploadé s’il y a (sinon fichier téléchargé depuis l’URL)
        if ($comment == 'upload') {
            $new_file = $sup_var['tmp_name'];
            if (move_uploaded_file($new_file, $dossier.$dest)) {
                $fichier['bt_checksum'] = sha1_file($dossier.$dest);
            } else {
                redirection(basename($_SERVER['SCRIPT_NAME']).'?errmsg=error_fichier_ajout_2');
            }
        } // fichier spécifié par URL
        elseif ($comment == 'download' and copy($sup_var, $dossier.$dest)) {
            $fichier['bt_filesize'] = filesize($dossier.$dest);
        } else {
            redirection(basename($_SERVER['SCRIPT_NAME']).'?errmsg=error_fichier_ajout');
        }

        // si fichier par POST ou par URL == OK, on l’ajoute à la base. (si pas OK, on serai déjà sorti par le else { redirection() }.
        if ($fichier['bt_type'] == 'image') { // miniature si c’est une image
            create_thumbnail($dossier.$dest);
            list($fichier['bt_dim_w'], $fichier['bt_dim_h']) = getimagesize($dossier.$dest);
        } // rm $path if not image
        else {
            $fichier['bt_path'] = '';
        }
        // ajout à la base.
        $GLOBALS['liste_fichiers'][] = $fichier;
        $GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
        create_file_dtb(FILES_DB, $GLOBALS['liste_fichiers']);
    } // modification d’un fichier déjà existant
    elseif ($quoi == 'editer-existant') {
        $new_filename = $fichier['bt_filename'];
        $old_filename = $sup_var;
        if ($new_filename != $old_filename) { // nom du fichier a changé ? on déplace le fichier.
            $prefix = '';
            while (is_file($dossier.$prefix.$new_filename)) { // évite d’avoir deux fichiers de même nom
                $prefix .= rand(0, 9);
            }
            $new_filename = $prefix.$fichier['bt_filename'];
            $fichier['bt_filename'] = $new_filename; // update file name in $fichier array(), with the new prefix.

            // rename file on disk
            if (rename($dossier.$old_filename, $dossier.$new_filename)) {
                // si c’est une image : renome la miniature si elle existe, sinon la crée
                if ($fichier['bt_type'] == 'image') {
                    $old_thb = chemin_thb_img_test($dossier.$old_filename);
                    if ($old_thb != $dossier.$old_filename) {
                        rename($old_thb, chemin_thb_img($dossier.$new_filename));
                    } else {
                        create_thumbnail($dossier.$new_filename);
                    }
                }
                // error rename ficher
            } else {
                redirection(basename($_SERVER['SCRIPT_NAME']).'?file_id='.$fichier['bt_id'].'&errmsg=error_fichier_rename');
            }
        }
        list($fichier['bt_dim_w'], $fichier['bt_dim_h']) = getimagesize($dossier.$new_filename); // reupdate filesize.

        // modifie le fichier dans la BDD des fichiers.
        foreach ($GLOBALS['liste_fichiers'] as $key => $entry) {
            if ($entry['bt_id'] == $fichier['bt_id']) {
                $GLOBALS['liste_fichiers'][$key] = $fichier; // trouve la bonne entrée dans la base.
            }
        }

        $GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
        create_file_dtb(FILES_DB, $GLOBALS['liste_fichiers']);
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
        if (is_file($dossier.$fichier['bt_filename']) and isset($tbl_id)) {
            $liste_fichiers = rm_dots_dir(scandir($dossier)); // liste les fichiers réels dans le dossier
            if (unlink($dossier.$fichier['bt_filename'])) { // fichier physique effacé
                if ($fichier['bt_type'] == 'image') {
                    // Delete the preview picture if any
                    $img = chemin_thb_img($dossier.$fichier['bt_filename']);
                    if (is_file($img)) {
                        unlink($img);
                    }
                }
                unset($GLOBALS['liste_fichiers'][$tbl_id]); // efface le fichier dans la liste des fichiers.
                $GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
                create_file_dtb(FILES_DB, $GLOBALS['liste_fichiers']);
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
        create_file_dtb(FILES_DB, $GLOBALS['liste_fichiers']);
        return 'no_such_file_on_disk';
    }
}

/*
 * On post of a file (always on admin sides)
 * gets posted informations and turn them into
 * an array
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
        $size = (int) $_POST['filesize'];
        $type = guess_file_type($ext);
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
            $size = (int) $_FILES['fichier']['size'];
            $type = guess_file_type($ext);
            $path = '';
            // ajout par une URL d’un fichier distant
        } elseif (!empty($_POST['fichier'])) {
            $filename = pathinfo(parse_url($_POST['fichier'], PHP_URL_PATH), PATHINFO_FILENAME);
            $ext = strtolower(pathinfo(parse_url($_POST['fichier'], PHP_URL_PATH), PATHINFO_EXTENSION));
            $checksum = sha1_file($_POST['fichier']); // works with URL files
            $size = 0;// same (even if we could use "filesize" with the URL, it would over-use data-transfer)
            $path = '';
            $type = guess_file_type($ext);
        } else {
            // ERROR
            redirection(basename($_SERVER['SCRIPT_NAME']).'?errmsg=error_image_add');
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
        'bt_dossier' => ((empty($dossier)) ? 'default' : $dossier ), // tags
        'bt_path' => ((empty($path)) ? (substr($checksum, 0, 2)) : $path ), // path on disk (rand subdir to avoid too many files in same dir)
    );
    return $fichier;
}

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

/**
 *
 */
function chemin_thb_img_test($filepath)
{
    $thb = chemin_thb_img($filepath);
    if (is_file($thb)) {
        return $thb;
    }
    return $filepath;
}

/**
 * filepath : image to create a thumbnail from
 */
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

/**
 * Guess the filetype from file's externsion.
 */
function guess_file_type($extension)
{
    // Table of recognized filetypes
    $extensions = array(
        'archive' => array('zip', '7z', 'rar', 'tar', 'gz', 'bz', 'bz2', 'xz', 'lzma'),
        'executable' => array('exe', 'e', 'bin', 'run'),
        'android-apk' => array('apk'),
        'html-xml' => array('html', 'htm', 'xml', 'mht'),
        'image' => array('png', 'gif', 'bmp', 'jpg', 'jpeg', 'ico', 'svg', 'tif', 'tiff'),
        'music' => array('mp3', 'wave', 'wav', 'ogg', 'wma', 'flac', 'aac', 'mid', 'midi', 'm4a'),
        'presentation' => array('ppt', 'pptx', 'pps', 'ppsx', 'odp'),
        'pdf' => array('pdf', 'ps', 'psd'),
        'ebook' => array('epub', 'mobi'),
        'spreadsheet' => array('xls', 'xlsx', 'xlt', 'xltx', 'ods', 'ots', 'csv'),
        'text_document'=> array('doc', 'docx', 'rtf', 'odt', 'ott'),
        'text-code' => array('txt', 'css', 'py', 'c', 'cpp', 'dat', 'ini', 'inf', 'text', 'conf', 'sh'),
        'video' => array('mkv', 'mp4', 'ogv', 'avi', 'mpeg', 'mpg', 'flv', 'webm', 'mov', 'divx', 'rm', 'rmvb', 'wmv'),
        'other' => array(''),  // default
    );

    $goodType = 'other';
    foreach ($extensions as $type => $exts) {
        if (in_array($extension, $exts)) {
            $goodType = $type;
            break;
        }
    }
    return $goodType;
}

/**
 * $feeds is an array of URLs: Array( [http://…], [http://…], …)
 * Returns the same array: Array([http://…] [[headers]=> 'string', [body]=> 'string'], …)
 */
function request_external_files($feeds, $timeout, $echo_progress = false)
{
    // uses chunks of 30 feeds because Curl has problems with too big (~150) "multi" requests.
    $chunks = array_chunk($feeds, 30, true);
    $results = array();
    $total_feed = count($feeds);
    if ($echo_progress === true) {
        echo '0/'.$total_feed.' ';
        ob_flush();
        flush(); // for Ajax
    }

    foreach ($chunks as $chunk) {
        set_time_limit(30);
        $curl_arr = array();
        $master = curl_multi_init();
        $total_feed_chunk = count($chunk)+count($results);

        // init each url
        foreach ($chunk as $i => $url) {
            $curl_arr[$url] = curl_init(trim($url));
            curl_setopt_array($curl_arr[$url], array(
                CURLOPT_RETURNTRANSFER => true, // force Curl to return data instead of displaying it
                CURLOPT_FOLLOWLOCATION => true, // follow 302 ans 301 redirects
                CURLOPT_CONNECTTIMEOUT => 100, // 0 = indefinately ; no connection-timeout (ruled out by "set_time_limit" hereabove)
                CURLOPT_TIMEOUT => $timeout, // downloading timeout
                CURLOPT_USERAGENT => BLOGOTEXT_UA, // User-agent (uses the UA of browser)
                CURLOPT_SSL_VERIFYPEER => false, // ignore SSL errors
                CURLOPT_SSL_VERIFYHOST => false, // ignore SSL errors
                CURLOPT_ENCODING => 'gzip', // take into account gziped pages
                //CURLOPT_VERBOSE => 1,
                CURLOPT_HEADER => 1, // also return header
            ));
            curl_multi_add_handle($master, $curl_arr[$url]);
        }

        // exec connexions
        $running = $oldrunning = 0;

        do {
            curl_multi_exec($master, $running);

            if ($echo_progress === true) {
                // echoes the nb of feeds remaining
                echo ($total_feed_chunk-$running).'/'.$total_feed.' ';
                ob_flush();
                flush();
            }
            usleep(100000);
        } while ($running > 0);

        // multi select contents
        foreach ($chunk as $i => $url) {
            $response = curl_multi_getcontent($curl_arr[$url]);
            $header_size = curl_getinfo($curl_arr[$url], CURLINFO_HEADER_SIZE);
            $results[$url]['headers'] = http_parse_headers(mb_strtolower(substr($response, 0, $header_size)));
            $results[$url]['body'] = substr($response, $header_size);
        }
        // Ferme les gestionnaires
        curl_multi_close($master);
    }
    return $results;
}


if (!function_exists('http_parse_headers')) {
    function http_parse_headers($raw_headers)
    {
        $headers = array();
        $array_headers = ((is_array($raw_headers)) ? $raw_headers : explode("\n", $raw_headers));

        foreach ($array_headers as $i => $h) {
            $h = explode(':', $h, 2);
            if (isset($h[1])) {
                $headers[$h[0]] = trim($h[1]);
            }
        }
        return $headers;
    }
}
