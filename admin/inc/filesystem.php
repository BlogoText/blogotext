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


/*
 * Notez que la liste des fichiers n’est pas intrégrée dans la base de données SQLITE.
 * Ceci pour une raison simple : les fichiers (contrairement aux articles et aux commentaires)
 * sont indépendants. Utiliser une BDD pour les lister est beaucoup moins rapide
 * qu’utiliser un fichier txt normal.
 * Pour le stockage, j’utilise un tableau PHP que j’enregistre directement dans un fichier :
 *   base64_encode(serialize($tableau)) # pompée sur Shaarli, by Sebsauvage.
 */

// From a filesize (like "20M"), returns a size in bytes.
function return_bytes($val)
{
    $val = trim($val);
    $prefix = strtolower($val[strlen($val)-1]);
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

function fichier_prefs()
{
    if (!empty($_POST['_verif_envoi'])) {
        $lang = (isset($_POST['langue']) and preg_match('#^[a-z]{2}$#', $_POST['langue'])) ? $_POST['langue'] : 'fr';
        $auteur = addslashes(clean_txt(htmlspecialchars($_POST['auteur'])));
        $email = addslashes(clean_txt(htmlspecialchars($_POST['email'])));
        $nomsite = addslashes(clean_txt(htmlspecialchars($_POST['nomsite'])));
        $description = addslashes(clean_txt(htmlspecialchars($_POST['description'])));
        $keywords = addslashes(clean_txt(htmlspecialchars($_POST['keywords'])));
        $racine = addslashes(trim(htmlspecialchars($_POST['racine'])));
        $max_bill_acceuil = htmlspecialchars($_POST['nb_maxi']);
        $max_bill_admin = (int) $_POST['nb_list'];
        $max_comm_admin = (int) $_POST['nb_list_com'];
        $max_rss_admin = (int) $_POST['nb_list_rss'];
        $format_date = (int) $_POST['format_date'];
        $format_heure = (int) $_POST['format_heure'];
        $fuseau_horaire = addslashes(clean_txt(htmlspecialchars($_POST['fuseau_horaire'])));
        $global_com_rule = (int) isset($_POST['global_comments']);
        $activer_categories = (int) isset($_POST['activer_categories']);
        $afficher_rss = (int) isset($_POST['aff_onglet_rss']);
        $afficher_liens = (int) isset($_POST['aff_onglet_liens']);
        $theme_choisi = addslashes(clean_txt(htmlspecialchars($_POST['theme'])));
        $comm_defaut_status = (int) $_POST['comm_defaut_status'];
        $automatic_keywords = (int) isset($_POST['auto_keywords']);
        $alert_author = (int) isset($_POST['alert_author']);
        $require_email = (int) isset($_POST['require_email']);
        $auto_check_updates = (int) isset($_POST['check_update']);
        $auto_dl_liens_fichiers = (int) $_POST['dl_link_to_files'];
        $nombre_liens_admin = (int) $_POST['nb_list_linx'];
    } else {
        $lang = (isset($_POST['langue']) and preg_match('#^[a-z]{2}$#', $_POST['langue'])) ? $_POST['langue'] : 'fr';
        $auteur = addslashes(clean_txt(htmlspecialchars(USER_LOGIN)));
        $email = 'mail@example.com';
        $nomsite = 'BlogoText';
        $description = addslashes(clean_txt($GLOBALS['lang']['go_to_pref']));
        $keywords = 'blog, blogotext';
        $racine = addslashes(clean_txt(trim(htmlspecialchars($_POST['racine']))));
        $max_bill_acceuil = 10;
        $max_bill_admin = 25;
        $max_comm_admin = 50;
        $max_rss_admin = 25;
        $format_date = 0;
        $format_heure = 0;
        $fuseau_horaire = 'UTC';
        $global_com_rule = 0;
        $activer_categories = 1;
        $afficher_rss = 1;
        $afficher_liens = 1;
        $theme_choisi = 'default';
        $comm_defaut_status = 1;
        $automatic_keywords = 1;
        $alert_author = 0;
        $require_email = 0;
        $auto_check_updates = 1;
        $auto_dl_liens_fichiers = 0;
        $nombre_liens_admin = 50;
    }
    $prefs = "<?php\n";
    $prefs .= "\$GLOBALS['lang'] = '".$lang."';\n";
    $prefs .= "\$GLOBALS['auteur'] = '".$auteur."';\n";
    $prefs .= "\$GLOBALS['email'] = '".$email."';\n";
    $prefs .= "\$GLOBALS['nom_du_site'] = '".$nomsite."';\n";
    $prefs .= "\$GLOBALS['description'] = '".$description."';\n";
    $prefs .= "\$GLOBALS['keywords'] = '".$keywords."';\n";
    $prefs .= "\$GLOBALS['racine'] = '".$racine."';\n";
    $prefs .= "\$GLOBALS['max_bill_acceuil'] = ".$max_bill_acceuil.";\n";
    $prefs .= "\$GLOBALS['max_bill_admin'] = ".$max_bill_admin.";\n";
    $prefs .= "\$GLOBALS['max_comm_admin'] = ".$max_comm_admin.";\n";
    $prefs .= "\$GLOBALS['max_rss_admin'] = ".$max_rss_admin.";\n";
    $prefs .= "\$GLOBALS['format_date'] = ".$format_date.";\n";
    $prefs .= "\$GLOBALS['format_heure'] = ".$format_heure.";\n";
    $prefs .= "\$GLOBALS['fuseau_horaire'] = '".$fuseau_horaire."';\n";
    $prefs .= "\$GLOBALS['activer_categories'] = ".$activer_categories.";\n";
    $prefs .= "\$GLOBALS['onglet_rss'] = ".$afficher_rss.";\n";
    $prefs .= "\$GLOBALS['onglet_liens'] = ".$afficher_liens.";\n";
    $prefs .= "\$GLOBALS['theme_choisi'] = '".$theme_choisi."';\n";
    $prefs .= "\$GLOBALS['global_com_rule'] = ".$global_com_rule.";\n";
    $prefs .= "\$GLOBALS['comm_defaut_status'] = ".$comm_defaut_status.";\n";
    $prefs .= "\$GLOBALS['automatic_keywords'] = ".$automatic_keywords.";\n";
    $prefs .= "\$GLOBALS['alert_author'] = ".$alert_author.";\n";
    $prefs .= "\$GLOBALS['require_email'] = ".$require_email.";\n";
    $prefs .= "\$GLOBALS['check_update'] = ".$auto_check_updates.";\n";
    $prefs .= "\$GLOBALS['max_linx_admin'] = ".$nombre_liens_admin.";\n";
    $prefs .= "\$GLOBALS['dl_link_to_files'] = ".$auto_dl_liens_fichiers.";\n";

    return (file_put_contents(FILE_SETTINGS, $prefs, LOCK_EX) !== false);
}

// TRAITEMENT DU FORMULAIRE DE FICHIER, CÔTÉ BDD
// Retourne le $fichier de l’entrée (après avoir possiblement changé des trucs, par ex si le fichier existait déjà, l’id retourné change)
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
        $size = (int) $_POST['filesize'];
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
            $size = (int) $_FILES['fichier']['size'];
            $type = detection_type_fichier($ext);
            $path = '';
            // ajout par une URL d’un fichier distant
        } elseif (!empty($_POST['fichier'])) {
            $filename = pathinfo(parse_url($_POST['fichier'], PHP_URL_PATH), PATHINFO_FILENAME);
            $ext = strtolower(pathinfo(parse_url($_POST['fichier'], PHP_URL_PATH), PATHINFO_EXTENSION));
            $checksum = sha1_file($_POST['fichier']); // works with URL files
            $size = 0;// same (even if we could use "filesize" with the URL, it would over-use data-transfer)
            $path = '';
            $type = detection_type_fichier($ext);
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

function chemin_thb_img_test($filepath)
{
    $thb = chemin_thb_img($filepath);
    if (is_file($thb)) {
        return $thb;
    }
    return $filepath;
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

// à partir de l’extension du fichier, trouve le "type" correspondant.
// les "type" et le tableau des extensions est le $GLOBALS['files_ext'] dans conf.php
function detection_type_fichier($extension)
{
    $good_type = 'other'; // par défaut
    foreach ($GLOBALS['files_ext'] as $type => $exts) {
        if (in_array($extension, $exts)) {
            $good_type = $type;
            break; // sort du foreach au premier 'match'
        }
    }
    return $good_type;
}


// $feeds is an array of URLs: Array( [http://…], [http://…], …)
// Returns the same array: Array([http://…] [[headers]=> 'string', [body]=> 'string'], …)
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
