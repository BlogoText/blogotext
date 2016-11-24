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


function valider_form_billet($billet)
{
    $date = decode_id($billet['bt_id']);
    $erreurs = array();
    if (isset($_POST['supprimer']) and !(isset($_POST['token']) and check_token($_POST['token']))) {
        $erreurs[] = $GLOBALS['lang']['err_wrong_token'];
    }
    if (!strlen(trim($billet['bt_title']))) {
        $erreurs[] = $GLOBALS['lang']['err_titre'];
    }
    if (!strlen(trim($billet['bt_content']))) {
        $erreurs[] = $GLOBALS['lang']['err_contenu'];
    }
    if (!preg_match('/\d{4}/', $date['annee'])) {
        $erreurs[] = $GLOBALS['lang']['err_annee'];
    }
    if ((!preg_match('/\d{2}/', $date['mois'])) or ($date['mois'] > '12')) {
        $erreurs[] = $GLOBALS['lang']['err_mois'];
    }
    if ((!preg_match('/\d{2}/', $date['jour'])) or ($date['jour'] > date('t', mktime(0, 0, 0, $date['mois'], 1, $date['annee'])))) {
        $erreurs[] = $GLOBALS['lang']['err_jour'];
    }
    if ((!preg_match('/\d{2}/', $date['heure'])) or ($date['heure'] > 23)) {
        $erreurs[] = $GLOBALS['lang']['err_heure'];
    }
    if ((!preg_match('/\d{2}/', $date['minutes'])) or ($date['minutes'] > 59)) {
        $erreurs[] = $GLOBALS['lang']['err_minutes'];
    }
    if ((!preg_match('/\d{2}/', $date['secondes'])) or ($date['secondes'] > 59)) {
        $erreurs[] = $GLOBALS['lang']['err_secondes'];
    }
    return $erreurs;
}

function valider_form_preferences()
{
    $erreurs = array();
    if (!( isset($_POST['token']) and check_token($_POST['token']))) {
        $erreurs[] = $GLOBALS['lang']['err_wrong_token'];
    }
    if (!strlen(trim($_POST['auteur']))) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_auteur'];
    }
    if ($GLOBALS['require_email'] == 1) {
        if (!preg_match('#^[\w.+~\'*-]+@[\w.-]+\.[a-zA-Z]{2,6}$#i', trim($_POST['email']))) {
            $erreurs[] = $GLOBALS['lang']['err_prefs_email'] ;
        }
    }
    if (!preg_match('#^(https?://).*/$#', $_POST['racine'])) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_racine_slash'];
    }
    if (!strlen(trim($_POST['identifiant']))) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_identifiant'];
    }
    if ($_POST['identifiant'] != USER_LOGIN and (!strlen($_POST['mdp']))) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_id_mdp'];
    }
    if (preg_match('#[=\'"\\\\|]#iu', $_POST['identifiant'])) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_id_syntaxe'];
    }
    if ((!empty($_POST['mdp'])) and (!password_verify($_POST['mdp'], USER_PWHASH))) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_oldmdp'];
    }
    if ((!empty($_POST['mdp'])) and (strlen($_POST['mdp_rep']) < '6')) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_mdp'];
    }
    if ((empty($_POST['mdp_rep'])) xor (empty($_POST['mdp']))) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_newmdp'] ;
    }
    return $erreurs;
}

function valider_form_fichier($fichier)
{
    $erreurs = array();
    if (!( isset($_POST['token']) and check_token($_POST['token']))) {
        $erreurs[] = $GLOBALS['lang']['err_wrong_token'];
    }
    if (!isset($_POST['is_it_edit'])) { // si nouveau fichier, test sur fichier entrant

        if (isset($_FILES['fichier'])) {
            if (($_FILES['fichier']['error'] == UPLOAD_ERR_INI_SIZE) or ($_FILES['fichier']['error'] == UPLOAD_ERR_FORM_SIZE)) {
                $erreurs[] = 'Fichier trop gros';
            } elseif ($_FILES['fichier']['error'] == UPLOAD_ERR_PARTIAL) {
                $erreurs[] = 'dépot interrompu';
            } elseif ($_FILES['fichier']['error'] == UPLOAD_ERR_NO_FILE) {
                $erreurs[] = 'aucun fichier déposé';
            }
        } elseif (isset($_POST['url'])) {
            if (empty($_POST['url'])) {
                $erreurs[] = $GLOBALS['lang']['err_lien_vide'];
            }
        }
    } else { // on edit
        if ('' == $fichier['bt_filename']) {
            $erreurs[] = 'nom de fichier invalide';
        }
    }
    return $erreurs;
}

function valider_form_module($module)
{
    $erreurs = array();
    // do not check token on ajax request
    if (!(isset($_POST['mod_activer']))) {
        if (!( isset($_POST['token']) and check_token($_POST['token']))) {
            $erreurs[] = $GLOBALS['lang']['err_wrong_token'];
        }
    }
    if (!isset($module['addon_id']) || preg_match('/^[\w\-]+$/', $module['addon_id']) === false) {
        $erreurs[] = $GLOBALS['lang']['err_addon_name'];
    }
    if (!isset($module['status'])) {
        $erreurs[] = $GLOBALS['lang']['err_addon_status'];
    }
    return $erreurs;
}

function valider_form_rss()
{
    $erreurs = array();
    // check unique-token only on critical actions (session ID check is still there)
    if (isset($_POST['add-feed']) or isset($_POST['delete_old'])) {
        if (!( isset($_POST['token']) and check_token($_POST['token']))) {
            $erreurs[] = $GLOBALS['lang']['err_wrong_token'];
        }
    }
    // on feed add: URL needs to be valid, not empty, and must not already be in DB
    if (isset($_POST['add-feed'])) {
        if (empty($_POST['add-feed'])) {
            $erreurs[] = $GLOBALS['lang']['err_lien_vide'];
        }
        if (!preg_match('#^(https?://[\S]+)[a-z]{2,6}[-\#_\w?%*:.;=+\(\)/&~$,]*$#', trim($_POST['add-feed']))) {
            $erreurs[] = $GLOBALS['lang']['err_comm_webpage'];
        }
        if (array_key_exists($_POST['add-feed'], $GLOBALS['liste_flux'])) {
            $erreurs[] = $GLOBALS['lang']['err_feed_exists'];
        }
    } elseif (isset($_POST['mark-as-read'])) {
        if (!(in_array($_POST['mark-as-read'], array('all', 'site', 'post', 'folder', 'postlist')))) {
            $erreurs[] = $GLOBALS['lang']['err_feed_wrong_param'];
        }
    }
    return $erreurs;
}

function valider_form_link()
{
    $erreurs = array();
    if (!( isset($_POST['token']) and check_token($_POST['token']))) {
        $erreurs[] = $GLOBALS['lang']['err_wrong_token'];
    }

    if (!preg_match('#^\d{14}$#', $_POST['bt_id'])) {
        $erreurs[] = 'Erreur id.';
    }
    return $erreurs;
}

function valider_form_maintenance()
{
    $erreurs = array();
    $token = (isset($_POST['token'])) ? $_POST['token'] : (isset($_GET['token']) ? $_GET['token'] : 'false');
    if (!check_token($token)) {
        $erreurs[] = $GLOBALS['lang']['err_wrong_token'];
    }
    return $erreurs;
}


function form_select($id, $choix, $defaut, $label)
{
    $form = '<label for="'.$id.'">'.$label.'</label>'."\n";
    $form .= "\t".'<select id="'.$id.'" name="'.$id.'">'."\n";
    foreach ($choix as $valeur => $mot) {
        $form .= "\t\t".'<option value="'.$valeur.'"'.(($defaut == $valeur) ? ' selected="selected" ' : '').'>'.$mot.'</option>'."\n";
    }
    $form .= "\t".'</select>'."\n";
    $form .= "\n";
    return $form;
}

function form_select_no_label($id, $choix, $defaut)
{
    $form = '<select id="'.$id.'" name="'.$id.'">'."\n";
    foreach ($choix as $valeur => $mot) {
        $form .= "\t".'<option value="'.$valeur.'"'.(($defaut == $valeur) ? ' selected="selected" ' : '').'>'.$mot.'</option>'."\n";
    }
    $form .= '</select>'."\n";
    return $form;
}

// Check SemVer validity
// source: https://github.com/morrisonlevi/SemVer/blob/master/src/League/SemVer/RegexParser.php
function is_valid_version($version)
{
    $regex = '/^
        (?#major)(0|(?:[1-9][0-9]*))
        \\.
        (?#minor)(0|(?:[1-9][0-9]*))
        \\.
        (?#patch)(0|(?:[1-9][0-9]*))
        (?:
            -
            (?#pre-release)(
                (?:(?:0|(?:[1-9][0-9]*))|(?:[0-9]*[a-zA-Z-][a-zA-Z0-9-]*))
                (?:
                    \\.
                    (?:(?:0|(?:[1-9][0-9]*))|(?:[0-9]*[a-zA-Z-][a-zA-Z0-9-]*))
                )*
            )
        )?
        (?:
            \\+
            (?#build)(
                [0-9a-zA-Z-]+
                (?:\\.[a-zA-Z0-9-]+)*
            )
        )?
    $/x';
    return preg_match($regex, $version);
}

function form_categories_links($where, $tags_post)
{
    $tags = list_all_tags($where, false);
    $html = '';
    if (!empty($tags)) {
        $html = '<datalist id="htmlListTags">'."\n";
        foreach ($tags as $tag => $i) {
            $html .= "\t".'<option value="'.addslashes($tag).'">'."\n";
        }
        $html .= '</datalist>'."\n";
    }
    $html .= '<ul id="selected">'."\n";
    $list_tags = explode(',', $tags_post);

    // remove diacritics and reindexes so that "ééé" does not passe after "zzz"
    foreach ($list_tags as $i => $tag) {
        $list_tags[$i] = array('t' => trim($tag), 'tt' => diacritique(trim($tag)));
    }
    $list_tags = array_reverse(tri_selon_sous_cle($list_tags, 'tt'));

    foreach ($list_tags as $i => $tag) {
        if (!empty($tag['t'])) {
            $html .= "\t".'<li><span>'.trim($tag['t']).'</span><a href="javascript:void(0)" onclick="removeTag(this.parentNode)">×</a></li>'."\n";
        }
    }
    $html .= '</ul>'."\n";
    return $html;
}

// Posts forms
function afficher_form_filtre($type, $filtre)
{
    $ret = '<form method="get" action="'.basename($_SERVER['SCRIPT_NAME']).'" onchange="this.submit();">'."\n";
    $ret .= '<div id="form-filtre">'."\n";
    $ret .= filtre($type, $filtre);
    $ret .= '</div>'."\n";
    $ret .= '</form>'."\n";
    echo $ret;
}

function form_checkbox($name, $checked, $label)
{
    $checked = ($checked) ? "checked " : '';
    $form = '<input type="checkbox" id="'.$name.'" name="'.$name.'" '.$checked.' class="checkbox-toggle" />'."\n" ;
    $form .= '<label for="'.$name.'" >'.$label.'</label>'."\n";
    return $form;
}


// FOR COMMENTS : RETUNS nb_com per author
function nb_entries_as($table, $what)
{
    $result = array();
    $query = "
        SELECT count($what) AS nb, $what
          FROM $table
         GROUP BY $what
         ORDER BY nb DESC";
    try {
        $result = $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (Exception $e) {
        die('Erreur 0349 : '.$e->getMessage());
    }
}


function filtre($type, $filtre)
{
    // WARNING: this is a resources heavy consuming function.
    $liste_des_types = array();
    $ret = '';
    $ret .= "\n".'<select name="filtre">'."\n" ;
    if ($type == 'articles') {
        $ret .= '<option value="">'.$GLOBALS['lang']['label_article_derniers'].'</option>'."\n";
        $query = '
            SELECT DISTINCT substr(bt_date, 1, 6) AS date
              FROM articles
             ORDER BY date DESC';
        $tab_tags = list_all_tags('articles', false);
        $BDD = 'sqlite';
    } elseif ($type == 'commentaires') {
        $ret .= '<option value="">'.$GLOBALS['lang']['label_comment_derniers'].'</option>'."\n";
        $tab_auteur = nb_entries_as('commentaires', 'bt_author');
        $query = '
            SELECT DISTINCT substr(bt_id, 1, 6) AS date
              FROM commentaires
             ORDER BY bt_id DESC';
        $BDD = 'sqlite';
    } elseif ($type == 'links') {
        $ret .= '<option value="">'.$GLOBALS['lang']['label_link_derniers'].'</option>'."\n";
        $tab_tags = list_all_tags('links', false);
        $query = '
            SELECT DISTINCT substr(bt_id, 1, 6) AS date
              FROM links
             ORDER BY bt_id DESC';
        $BDD = 'sqlite';
    } elseif ($type == 'fichiers') {
        // crée un tableau où les clé sont les types de fichiers et les valeurs, le nombre de fichiers de ce type.
        $files = $GLOBALS['liste_fichiers'];
        $tableau_mois = array();
        if (!empty($files)) {
            foreach ($files as $id => $file) {
                $type = $file['bt_type'];
                if (!array_key_exists($type, $liste_des_types)) {
                    $liste_des_types[$type] = 1;
                } else {
                    $liste_des_types[$type]++;
                }
            }
        }
        arsort($liste_des_types);

        $ret .= '<option value="">'.$GLOBALS['lang']['label_fichier_derniers'].'</option>'."\n";
        $filtre_type = '';
        $BDD = 'fichier_txt_files';
    }

    if ($BDD == 'sqlite') {
        try {
            $req = $GLOBALS['db_handle']->prepare($query);
            $req->execute(array());
            while ($row = $req->fetch()) {
                $tableau_mois[$row['date']] = mois_en_lettres(substr($row['date'], 4, 2)).' '.substr($row['date'], 0, 4);
            }
        } catch (Exception $x) {
            die('Erreur affichage filtre() : '.$x->getMessage());
        }
    } elseif ($BDD == 'fichier_txt_files') {
        foreach ($GLOBALS['liste_fichiers'] as $e) {
            if (!empty($e['bt_id'])) {
                // mk array[201005] => "May 2010", uzw
                $tableau_mois[substr($e['bt_id'], 0, 6)] = mois_en_lettres(substr($e['bt_id'], 4, 2)).' '.substr($e['bt_id'], 0, 4);
            }
        }
        krsort($tableau_mois);
    }

    // Drafts
    $ret .= '<option value="draft"'.(($filtre == 'draft') ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_invisibles'].'</option>'."\n";

    // Public
    $ret .= '<option value="pub"'.(($filtre == 'pub') ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_publies'].'</option>'."\n";

    // By date
    if (!empty($tableau_mois)) {
        $ret .= '<optgroup label="'.$GLOBALS['lang']['label_date'].'">'."\n";
        foreach ($tableau_mois as $mois => $label) {
            $ret .= "\t".'<option value="' . htmlentities($mois) . '"'.((substr($filtre, 0, 6) == $mois) ? ' selected="selected"' : '').'>'.$label.'</option>'."\n";
        }
        $ret .= '</optgroup>'."\n";
    }

    // By author (for comments)
    if (!empty($tab_auteur)) {
        $ret .= '<optgroup label="'.$GLOBALS['lang']['pref_auteur'].'">'."\n";
        foreach ($tab_auteur as $nom) {
            if (!empty($nom['nb'])) {
                $ret .= "\t".'<option value="auteur.'.$nom['bt_author'].'"'.(($filtre == 'auteur.'.$nom['bt_author']) ? ' selected="selected"' : '').'>'.$nom['bt_author'].' ('.$nom['nb'].')'.'</option>'."\n";
            }
        }
        $ret .= '</optgroup>'."\n";
    }

    // By type (for files)
    if (!empty($liste_des_types)) {
        $ret .= '<optgroup label="'.'Type'.'">'."\n";
        foreach ($liste_des_types as $type => $nb) {
            if (!empty($type)) {
                $ret .= "\t".'<option value="type.'.$type.'"'.(($filtre == 'type.'.$type) ? ' selected="selected"' : '').'>'.$type.' ('.$nb.')'.'</option>'."\n";
            }
        }
        $ret .= '</optgroup>'."\n";
    }

    // By tag (for posts and links)
    if (!empty($tab_tags)) {
        $ret .= '<optgroup label="'.'Tags'.'">'."\n";
        foreach ($tab_tags as $tag => $nb) {
            $ret .= "\t".'<option value="tag.'.$tag.'"'.(($filtre == 'tag.'.$tag) ? ' selected="selected"' : '').'>'.$tag.' ('.$nb.')</option>'."\n";
        }
        $ret .= '</optgroup>'."\n";
    }
    $ret .= '</select> '."\n\n";

    return $ret;
}
