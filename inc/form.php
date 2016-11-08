<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

/// formulaires GENERIQUES //////////

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

function hidden_input($nom, $valeur, $id = 0)
{
    $id = ($id === 0) ? '' : ' id="'.$nom.'"';
    $form = '<input type="hidden" name="'.$nom.'"'.$id.' value="'.$valeur.'" />'."\n";
    return $form;
}

/// formulaires PREFERENCES //////////

function select_yes_no($name, $defaut, $label)
{
    $choix = array(
        '1' => $GLOBALS['lang']['oui'],
        '0' => $GLOBALS['lang']['non']
    );
    $form = '<label for="'.$name.'" >'.$label.'</label>'."\n";
    $form .= '<select id="'.$name.'" name="'.$name.'">'."\n" ;
    foreach ($choix as $option => $label) {
        $form .= "\t".'<option value="'.htmlentities($option).'"'.(($option == $defaut) ? ' selected="selected" ' : '').'>'.htmlentities($label).'</option>'."\n";
    }
    $form .= '</select>'."\n";
    return $form;
}

function form_checkbox($name, $checked, $label)
{
    $checked = ($checked) ? "checked " : '';
    $form = '<input type="checkbox" id="'.$name.'" name="'.$name.'" '.$checked.' class="checkbox-toggle" />'."\n" ;
    $form .= '<label for="'.$name.'" >'.$label.'</label>'."\n";
    return $form;
}


function form_format_date($defaut)
{
    $jour_l = jour_en_lettres(date('d'), date('m'), date('Y'));
    $mois_l = mois_en_lettres(date('m'));
    $formats = array (
        '0' => date('d').'/'.date('m').'/'.date('Y'),             // 05/07/2011
        '1' => date('m').'/'.date('d').'/'.date('Y'),             // 07/05/2011
        '2' => date('d').' '.$mois_l.' '.date('Y'),               // 05 juillet 2011
        '3' => $jour_l.' '.date('d').' '.$mois_l.' '.date('Y'),   // mardi 05 juillet 2011
        '4' => $jour_l.' '.date('d').' '.$mois_l,                 // mardi 05 juillet
        '5' => $mois_l.' '.date('d').', '.date('Y'),              // juillet 05, 2011
        '6' => $jour_l.', '.$mois_l.' '.date('d').', '.date('Y'), // mardi, juillet 05, 2011
        '7' => date('Y').'-'.date('m').'-'.date('d'),             // 2011-07-05
        '8' => substr($jour_l, 0, 3).'. '.date('d').' '.$mois_l,    // ven. 14 janvier
    );
    $form = "\t".'<label>'.$GLOBALS['lang']['pref_format_date'].'</label>'."\n";
    $form .= "\t".'<select name="format_date">'."\n";
    foreach ($formats as $option => $label) {
        $form .= "\t\t".'<option value="'.htmlentities($option).'"'.(($defaut == $option) ? ' selected="selected" ' : '').'>'.$label.'</option>'."\n";
    }
    $form .= "\t".'</select>'."\n";
    return $form;
}

function form_fuseau_horaire($defaut)
{
    $all_timezones = timezone_identifiers_list();
    $liste_fuseau = array();
    $cities = array();
    foreach ($all_timezones as $tz) {
        $spos = strpos($tz, '/');
        if ($spos !== false) {
            $continent = substr($tz, 0, $spos);
            $city = substr($tz, $spos+1);
            $liste_fuseau[$continent][] = array('tz_name' => $tz, 'city' => $city);
        }
        if ($tz == 'UTC') {
            $liste_fuseau['UTC'][] = array('tz_name' => 'UTC', 'city' => 'UTC');
        }
    }
    $form = '<label>'.$GLOBALS['lang']['pref_fuseau_horaire'].'</label>'."\n";
    $form .= '<select name="fuseau_horaire">'."\n";
    foreach ($liste_fuseau as $continent => $zone) {
        $form .= "\t".'<optgroup label="'.ucfirst(strtolower($continent)).'">'."\n";
        foreach ($zone as $fuseau) {
            $form .= "\t\t".'<option value="'.htmlentities($fuseau['tz_name']).'"';
            $form .= ($defaut == $fuseau['tz_name']) ? ' selected="selected"' : '';
                $timeoffset = date_offset_get(date_create('now', timezone_open($fuseau['tz_name'])));
                $formated_toffset = '(UTC'.(($timeoffset < 0) ? '–' : '+').str2(floor((abs($timeoffset)/3600))) .':'.str2(floor((abs($timeoffset)%3600)/60)) .') ';
            $form .= '>'.$formated_toffset.' '.htmlentities($fuseau['city']).'</option>'."\n";
        }
        $form .= "\t".'</optgroup>'."\n";
    }
    $form .= '</select>'."\n";
    return $form;
}

function form_format_heure($defaut)
{
    $formats = array (
        '0' => date('H\:i\:s'),         // 23:56:04
        '1' => date('H\:i'),            // 23:56
        '2' => date('h\:i\:s A'),   // 11:56:04 PM
        '3' => date('h\:i A'),      // 11:56 PM
    );
    $form = '<label>'.$GLOBALS['lang']['pref_format_heure'].'</label>'."\n";
    $form .= '<select name="format_heure">'."\n";
    foreach ($formats as $option => $label) {
        $form .= "\t".'<option value="'.htmlentities($option).'"'.(($defaut == $option) ? ' selected="selected" ' : '').'>'.htmlentities($label).'</option>'."\n";
    }
    $form .= "\t".'</select>'."\n";
    return $form;
}

function form_langue($defaut)
{
    $form = '<label>'.$GLOBALS['lang']['pref_langue'].'</label>'."\n";
    $form .= '<select name="langue">'."\n";
    foreach ($GLOBALS['langs'] as $option => $label) {
        $form .= "\t".'<option value="'.htmlentities($option).'"'.(($defaut == $option) ? ' selected="selected" ' : '').'>'.$label.'</option>'."\n";
    }
    $form .= '</select>'."\n";
    return $form;
}

function form_langue_install($label)
{
    $ret = '<label for="langue">'.$label;
    $ret .= '<select id="langue" name="langue">'."\n";
    foreach ($GLOBALS['langs'] as $option => $label) {
        $ret .= "\t".'<option value="'.htmlentities($option).'">'.$label.'</option>'."\n";
    }
    $ret .= '</select></label>'."\n";
    echo $ret;
}

function liste_themes($chemin)
{
    if ($ouverture = opendir($chemin)) {
        while ($dossiers = readdir($ouverture)) {
            if (file_exists($chemin.'/'.$dossiers.'/list.html')) {
                $themes[$dossiers] = $dossiers;
            }
        }
        closedir($ouverture);
    }
    if (isset($themes)) {
        return $themes;
    }
}


// formulaires ARTICLES //////////

function afficher_form_filtre($type, $filtre)
{
    $ret = '<form method="get" action="'.basename($_SERVER['SCRIPT_NAME']).'" onchange="this.submit();">'."\n";
    $ret .= '<div id="form-filtre">'."\n";
    $ret .= filtre($type, $filtre);
    $ret .= '</div>'."\n";
    $ret .= '</form>'."\n";
    echo $ret;
}

function filtre($type, $filtre)
{
 // cette fonction est très gourmande en ressources.
    $liste_des_types = array();
    $ret = '';
    $ret .= "\n".'<select name="filtre">'."\n" ;
    // Articles
    if ($type == 'articles') {
        $ret .= '<option value="">'.$GLOBALS['lang']['label_article_derniers'].'</option>'."\n";
        $query = "SELECT DISTINCT substr(bt_date, 1, 6) AS date FROM articles ORDER BY date DESC";
        $tab_tags = list_all_tags('articles', false);
        $BDD = 'sqlite';
    // Commentaires
    } elseif ($type == 'commentaires') {
        $ret .= '<option value="">'.$GLOBALS['lang']['label_comment_derniers'].'</option>'."\n";
        $tab_auteur = nb_entries_as('commentaires', 'bt_author');
        $query = "SELECT DISTINCT substr(bt_id, 1, 6) AS date FROM commentaires ORDER BY bt_id DESC";
        $BDD = 'sqlite';
    // Liens
    } elseif ($type == 'links') {
        $ret .= '<option value="">'.$GLOBALS['lang']['label_link_derniers'].'</option>'."\n";
        $tab_tags = list_all_tags('links', false);
        $query = "SELECT DISTINCT substr(bt_id, 1, 6) AS date FROM links ORDER BY bt_id DESC";
        $BDD = 'sqlite';
    // Fichiers
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
            die('Erreur affichage form_filtre() : '.$x->getMessage());
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

    /// BROUILLONS
    $ret .= '<option value="draft"'.(($filtre == 'draft') ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_invisibles'].'</option>'."\n";

    /// PUBLIES
    $ret .= '<option value="pub"'.(($filtre == 'pub') ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_publies'].'</option>'."\n";

    /// PAR DATE
    if (!empty($tableau_mois)) {
        $ret .= '<optgroup label="'.$GLOBALS['lang']['label_date'].'">'."\n";
        foreach ($tableau_mois as $mois => $label) {
            $ret .= "\t".'<option value="' . htmlentities($mois) . '"'.((substr($filtre, 0, 6) == $mois) ? ' selected="selected"' : '').'>'.$label.'</option>'."\n";
        }
        $ret .= '</optgroup>'."\n";
    }

    /// PAR AUTEUR S'IL S'AGIT DES COMMENTAIRES
    if (!empty($tab_auteur)) {
        $ret .= '<optgroup label="'.$GLOBALS['lang']['pref_auteur'].'">'."\n";
        foreach ($tab_auteur as $nom) {
            if (!empty($nom['nb'])) {
                $ret .= "\t".'<option value="auteur.'.$nom['bt_author'].'"'.(($filtre == 'auteur.'.$nom['bt_author']) ? ' selected="selected"' : '').'>'.$nom['bt_author'].' ('.$nom['nb'].')'.'</option>'."\n";
            }
        }
        $ret .= '</optgroup>'."\n";
    }

    /// PAR TYPE S'IL S'AGIT DES FICHIERS
    if (!empty($liste_des_types)) {
        $ret .= '<optgroup label="'.'Type'.'">'."\n";
        foreach ($liste_des_types as $type => $nb) {
            if (!empty($type)) {
                $ret .= "\t".'<option value="type.'.$type.'"'.(($filtre == 'type.'.$type) ? ' selected="selected"' : '').'>'.$type.' ('.$nb.')'.'</option>'."\n";
            }
        }
        $ret .= '</optgroup>'."\n";
    }

    ///PAR TAGS POUR LES LIENS & ARTICLES
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




/// Formulaire pour ajouter un lien dans Links côté Admin
function afficher_form_link($step, $erreurs, $editlink = '')
{
    if ($erreurs) {
        echo erreurs($erreurs);
    }
    $form = '';
    if ($step == 1) { // postage de l'URL : un champ affiché en GET
        $form .= '<form method="get" id="post-new-lien" action="'.basename($_SERVER['SCRIPT_NAME']).'">'."\n";
        $form .= '<fieldset>'."\n";
        $form .= "\t".'<div class="contain-input">'."\n";
        $form .= "\t\t".'<label for="url">'.$GLOBALS['lang']['label_nouv_lien'].'</label>'."\n";
        $form .= "\t\t".'<input type="text" name="url" id="url" value="" size="70" placeholder="http://www.example.com/" class="text" autocomplete="off" />'."\n";
        $form .= "\t".'</div>'."\n";
        $form .= "\t".'<p class="submit-bttns"><button type="submit" class="submit button-submit">'.$GLOBALS['lang']['envoyer'].'</button></p>'."\n";
        $form .= '</fieldset>'."\n";
        $form .= '</form>'."\n\n";
    } elseif ($step == 2) { // Form de l'URL, avec titre, description, en POST cette fois, et qu'il faut vérifier avant de stoquer dans la BDD.
        $form .= '<form method="post" onsubmit="return moveTag();" id="post-lien" action="'.basename($_SERVER['SCRIPT_NAME']).'">'."\n";

        $url = $_GET['url'];
        $type = 'url';
        $title = $url;
        $charset = "UTF-8";
        $new_id = date('YmdHis');

        // URL is empty or no URI. It’s a note: we hide the URI field.
        if (empty($url) or (strpos($url, 'http') !== 0)) {
            $type = 'note';
            $title = 'Note'.(!empty($url) ? ' : '.html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '');
            $url = $GLOBALS['racine'].'?mode=links&amp;id='.$new_id;
            $form .= hidden_input('url', $url);
            $form .= hidden_input('type', 'note');
        // URL is not empty
        } else {
            // Find out type of file
            $response = request_external_files(array($url), 15, false);
            $ext_file = $response[$url];
            $rep_hdr = $ext_file['headers'];
            $cnt_type = (isset($rep_hdr['content-type'])) ? (is_array($rep_hdr['content-type']) ? $rep_hdr['content-type'][count($rep_hdr['content-type'])-1] : $rep_hdr['content-type']) : 'text/';
            $cnt_type = (is_array($cnt_type)) ? $cnt_type[0] : $cnt_type;

            // Image
            if (strpos($cnt_type, 'image/') === 0) {
                $title = $GLOBALS['lang']['label_image'];
                if (list($width, $height) = @getimagesize($url)) {
                    $fdata = $url;
                    $type = 'image';
                    $title .= ' - '.$width.'x'.$height.'px ';
                }
            } // Non-image NON-textual file (pdf…)
            elseif (strpos($cnt_type, 'text/') !== 0 and strpos($cnt_type, 'xml') === false) {
                if ($GLOBALS['dl_link_to_files'] == 2) {
                    $type = 'file';
                }
            } // a textual document: parse it for any <title> element (+charset for title decoding ; fallback=UTF-8) ; fallback=$url
            elseif (!empty($ext_file['body'])) {
                // Search for charset in the headers
                if (preg_match('#charset=(.*);?#', $cnt_type, $hdr_charset) and !empty($hdr_charset[1])) {
                    $charset = $hdr_charset[1];
                } // If not found, search it in HTML
                elseif (preg_match('#<meta .*charset=(["\']?)([^\s>"\']*)([\'"]?)\s*/?>#Usi', $ext_file['body'], $meta_charset) and !empty($meta_charset[2])) {
                    $charset = $meta_charset[2];
                }
                // get title in the proper encoding
                $ext_file = html_entity_decode(((strtolower($charset) == 'iso-8859-1') ? utf8_encode($ext_file['body']) : $ext_file['body']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                preg_match('#<title ?[^>]*>(.*)</title>#Usi', $ext_file, $titles);
                if (!empty($titles[1])) {
                    $title = trim($titles[1]);
                }
            }

            $form .= "\t".'<input type="text" name="url" value="'.htmlspecialchars($url).'" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_url']).'" size="50" class="text readonly-like" />'."\n";
            $form .= hidden_input('type', 'link');
        }

        $link = array('title' => htmlspecialchars($title), 'url' => htmlspecialchars($url));
        $form .= "\t".'<input type="text" name="title" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_titre']).'" required="" value="'.$link['title'].'" size="50" class="text" autofocus />'."\n";
        $form .= "\t".'<span id="description-box">'."\n";
        $form .= ($type == 'image') ? "\t\t".'<span id="img-container"><img src="'.$fdata.'" alt="img" class="preview-img" height="'.$height.'" width="'.$width.'"/></span>' : '';
        $form .= "\t\t".'<textarea class="text description" name="description" cols="40" rows="7" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_description']).'"></textarea>'."\n";
        $form .= "\t".'</span>'."\n";

        $form .= "\t".'<div id="tag_bloc">'."\n";
        $form .= form_categories_links('links', '');
        $form .= "\t".'<input list="htmlListTags" type="text" class="text" id="type_tags" name="tags" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'"/>'."\n";
        $form .= "\t".'<input type="hidden" id="categories" name="categories" value="" />'."\n";
        $form .= "\t".'</div>'."\n";

        $form .= "\t".'<input type="checkbox" name="statut" id="statut" class="checkbox" />'.'<label class="forcheckbox" for="statut">'.$GLOBALS['lang']['label_lien_priv'].'</label>'."\n";
        if ($type == 'image' or $type == 'file') {
            // download of file is asked
            $form .= ($GLOBALS['dl_link_to_files'] == 2) ? "\t".'<input type="checkbox" name="add_to_files" id="add_to_files" class="checkbox" />'.'<label class="forcheckbox" for="add_to_files">'.$GLOBALS['lang']['label_dl_fichier'].'</label>'."\n" : '';
            // download of file is systematic
            $form .= ($GLOBALS['dl_link_to_files'] == 1) ? hidden_input('add_to_files', 'on') : '';
        }
        $form .= "\t".'<p class="submit-bttns">'."\n";
        $form .= "\t\t".'<button class="submit button-cancel" type="button" onclick="annuler(\'links.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
        $form .= "\t\t".'<button class="submit button-submit" type="submit" name="enregistrer" id="valid-link">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
        $form .= "\t".'</p>'."\n";
        $form .= hidden_input('_verif_envoi', '1');
        $form .= hidden_input('bt_id', $new_id);
        $form .= hidden_input('token', new_token());
        $form .= hidden_input('dossier', '');
        $form .= '</form>'."\n\n";
    } elseif ($step == 'edit') { // Form pour l'édition d'un lien : les champs sont remplis avec le "wiki_content" et il y a les boutons suppr/activer en plus.
        $form = '<form method="post" onsubmit="return moveTag();" id="post-lien" action="'.basename($_SERVER['SCRIPT_NAME']).'?id='.$editlink['bt_id'].'">'."\n";
        $form .= "\t".'<input type="text" name="url" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_url']).'" required="" value="'.$editlink['bt_link'].'" size="70" class="text readonly-like" /></label>'."\n";
        $form .= "\t".'<input type="text" name="title" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_titre']).'" required="" value="'.$editlink['bt_title'].'" size="70" class="text" autofocus /></label>'."\n";
        $form .= "\t".'<div id="description-box">'."\n";
        $form .= "\t\t".'<textarea class="description text" name="description" cols="70" rows="7" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_description']).'" >'.$editlink['bt_wiki_content'].'</textarea>'."\n";
        $form .= "\t".'</div>'."\n";
        $form .= "\t".'<div id="tag_bloc">'."\n";
        $form .= form_categories_links('links', $editlink['bt_tags']);
        $form .= "\t\t".'<input list="htmlListTags" type="text" class="text" id="type_tags" name="tags" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'"/>'."\n";
        $form .= "\t\t".'<input type="hidden" id="categories" name="categories" value="" />'."\n";
        $form .= "\t".'</div>'."\n";
        $form .= "\t".'<input type="checkbox" name="statut" id="statut" class="checkbox" '.(($editlink['bt_statut'] == 0) ? 'checked ' : '').'/>'.'<label class="forcheckbox" for="statut">'.$GLOBALS['lang']['label_lien_priv'].'</label>'."\n";

        $form .= "\t".'<p class="submit-bttns">'."\n";
        $form .= "\t\t".'<button class="submit button-delete" type="button" name="supprimer" onclick="rmArticle(this)">'.$GLOBALS['lang']['supprimer'].'</button>'."\n";
        $form .= "\t\t".'<button class="submit button-cancel" type="button" onclick="annuler(\'links.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
        $form .= "\t\t".'<button class="submit button-submit" type="submit" name="editer">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
        $form .= "\t".'</p>'."\n";
        $form .= hidden_input('ID', $editlink['ID']);
        $form .= hidden_input('bt_id', $editlink['bt_id']);
        $form .= hidden_input('_verif_envoi', '1');
        $form .= hidden_input('is_it_edit', 'yes');
        $form .= hidden_input('token', new_token());
        $form .= hidden_input('type', $editlink['bt_type']);
        $form .= '</form>'."\n\n";
    }
    return $form;
}


/// formulaires BILLET //////////
function afficher_form_billet($article, $erreurs)
{
    $html = '';

    function form_annee($year_shown)
    {
        return '<input type="number" name="annee" max="'.(date('Y') + 3).'" value="'.$year_shown.'">'."\n";
    }

    function form_mois($mois_affiche)
    {
        $mois = array(
            "01" => $GLOBALS['lang']['janvier'],    "02" => $GLOBALS['lang']['fevrier'],    "03" => $GLOBALS['lang']['mars'],
            "04" => $GLOBALS['lang']['avril'],      "05" => $GLOBALS['lang']['mai'],            "06" => $GLOBALS['lang']['juin'],
            "07" => $GLOBALS['lang']['juillet'],    "08" => $GLOBALS['lang']['aout'],       "09" => $GLOBALS['lang']['septembre'],
            "10" => $GLOBALS['lang']['octobre'],    "11" => $GLOBALS['lang']['novembre'],   "12" => $GLOBALS['lang']['decembre']
        );
        $ret = '<select name="mois">'."\n" ;
        foreach ($mois as $option => $label) {
            $ret .= "\t".'<option value="'.htmlentities($option).'"'.(($mois_affiche == $option) ? ' selected="selected"' : '').'>'.$label.'</option>'."\n";
        }
        $ret .= '</select>'."\n";
        return $ret;
    }

    function form_jour($jour_affiche)
    {
        for ($jour=1; $jour <=31;
        $jour++) {
            $jours[str2($jour)] = $jour;
        }
        $ret = '<select name="jour">'."\n";
        foreach ($jours as $option => $label) {
            $ret .= "\t".'<option value="'.htmlentities($option).'"'.(($jour_affiche == $option) ? ' selected="selected"' : '').'>'.htmlentities($label).'</option>'."\n";
        }
        $ret .= '</select>'."\n";
        return $ret;
    }

    function form_statut($etat)
    {
        $choix = array('1' => $GLOBALS['lang']['label_publie'], '0' => $GLOBALS['lang']['label_invisible']);
        return form_select('statut', $choix, $etat, $GLOBALS['lang']['label_dp_etat']);
    }

    function form_allow_comment($etat)
    {
        $choix= array('1' => $GLOBALS['lang']['ouverts'], '0' => $GLOBALS['lang']['fermes']);
        return form_select('allowcomment', $choix, $etat, $GLOBALS['lang']['label_dp_commentaires']);
    }

    if ($article != '') {
        $defaut_jour = $article['jour'];
        $defaut_mois = $article['mois'];
        $defaut_annee = $article['annee'];
        $defaut_heure = $article['heure'];
        $defaut_minutes = $article['minutes'];
        $defaut_secondes = $article['secondes'];
        $titredefaut = $article['bt_title'];
        // abstract : s’il est vide, il est regénéré à l’affichage, mais reste vide dans la BDD)
        $chapodefaut = get_entry($GLOBALS['db_handle'], 'articles', 'bt_abstract', $article['bt_id'], 'return');
        $notesdefaut = $article['bt_notes'];
        $tagsdefaut = $article['bt_tags'];
        $contenudefaut = htmlspecialchars($article['bt_wiki_content']);
        $motsclesdefaut = $article['bt_keywords'];
        $statutdefaut = $article['bt_statut'];
        $allowcommentdefaut = $article['bt_allow_comments'];
    } else {
        $defaut_jour = date('d');
        $defaut_mois = date('m');
        $defaut_annee = date('Y');
        $defaut_heure = date('H');
        $defaut_minutes = date('i');
        $defaut_secondes = date('s');
        $chapodefaut = '';
        $contenudefaut = '';
        $motsclesdefaut = '';
        $tagsdefaut = '';
        $titredefaut = '';
        $notesdefaut = '';
        $statutdefaut = '1';
        $allowcommentdefaut = '1';
    }
    if ($erreurs) {
        $html .= erreurs($erreurs);
    }
    if (isset($article['bt_id'])) {
        $html .= '<form id="form-ecrire" method="post" onsubmit="return moveTag();" action="'.basename($_SERVER['SCRIPT_NAME']).'?post_id='.$article['bt_id'].'" >'."\n";
    } else {
        $html .= '<form id="form-ecrire" method="post" onsubmit="return moveTag();" action="'.basename($_SERVER['SCRIPT_NAME']).'" >'."\n";
    }
    $html .= '<div class="main-form">';
    $html .= '<input id="titre" name="titre" type="text" size="50" value="'.$titredefaut.'" required="" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_titre']).'" tabindex="30" class="text" spellcheck="true" />'."\n" ;
    $html .= '<div id="chapo_note">'."\n";
    $html .= '<textarea id="chapo" name="chapo" rows="5" cols="20" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_chapo']).'" tabindex="35" class="text" >'.$chapodefaut.'</textarea>'."\n" ;
    $html .= '<textarea id="notes" name="notes" rows="5" cols="20" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_notes']).'" tabindex="40" class="text" >'.$notesdefaut.'</textarea>'."\n" ;
    $html .= '</div>'."\n";

    $html .= form_formatting_toolbar(true);

    $html .= '<textarea id="contenu" name="contenu" rows="20" cols="60" required="" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_contenu']).'" tabindex="55" class="text">'.$contenudefaut.'</textarea>'."\n" ;

    if ($GLOBALS['activer_categories'] == '1') {
        $html .= "\t".'<div id="tag_bloc">'."\n";
        $html .= form_categories_links('articles', $tagsdefaut);
        $html .= "\t\t".'<input list="htmlListTags" type="text" class="text" id="type_tags" name="tags" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'" tabindex="65"/>'."\n";
        $html .= "\t\t".'<input type="hidden" id="categories" name="categories" value="" />'."\n";
        $html .= "\t".'</div>'."\n";
    }

    if ($GLOBALS['automatic_keywords'] == '0') {
        $html .= '<input id="mots_cles" name="mots_cles" type="text" size="50" value="'.$motsclesdefaut.'" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_motscle']).'" tabindex="67" class="text" />'."\n";
    }
    $html .= '</div>';

    $html .= '<div id="date-and-opts">'."\n";
    $html .= '<div id="date">'."\n";
        $html .= '<span id="formdate">'."\n";
            $html .= form_annee($defaut_annee);
            $html .= form_mois($defaut_mois);
            $html .= form_jour($defaut_jour);
        $html .= '</span>'."\n\n";
        $html .= '<span id="formheure">';
            $html .= '<input name="heure" type="text" size="2" maxlength="2" value="'.$defaut_heure.'" required="" /> : ';
            $html .= '<input name="minutes" type="text" size="2" maxlength="2" value="'.$defaut_minutes.'" required="" /> : ';
            $html .= '<input name="secondes" type="text" size="2" maxlength="2" value="'.$defaut_secondes.'" required="" />';
        $html .= '</span>'."\n";
        $html .= '</div>'."\n";
        $html .= '<div id="opts">'."\n";
            $html .= '<span id="formstatut">'."\n";
                $html .= form_statut($statutdefaut);
            $html .= '</span>'."\n";
            $html .= '<span id="formallowcomment">'."\n";
                $html .= form_allow_comment($allowcommentdefaut);
            $html .= '</span>'."\n";
        $html .= '</div>'."\n";

    $html .= '</div>'."\n";
    $html .= '<p class="submit-bttns">'."\n";

    if ($article) {
        $html .= hidden_input('article_id', $article['bt_id']);
        $html .= hidden_input('article_date', $article['bt_date']);
        $html .= hidden_input('ID', $article['ID']);
        $html .= "\t".'<button class="submit button-delete" type="button" name="supprimer" onclick="contenuLoad = document.getElementById(\'contenu\').value; rmArticle(this)" />'.$GLOBALS['lang']['supprimer'].'</button>'."\n";
    }
    $html .= "\t".'<button class="submit button-cancel" type="button" onclick="annuler(\'articles.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
    $html .= "\t".'<button class="submit button-submit" type="submit" name="enregistrer" onclick="contenuLoad=document.getElementById(\'contenu\').value" tabindex="70">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
    $html .= '</p>'."\n";
    $html .= hidden_input('_verif_envoi', '1');
    $html .= hidden_input('token', new_token());

    $html .= '</form>'."\n";
    echo $html;
}
// FIN AFFICHER_FORM_BILLET


function s_color($color)
{
    return '<button type="button" onclick="insertTag(this, \'[color='.$color.']\',\'[/color]\');"><span style="background:'.$color.';"></span></button>';
}
function s_size($size)
{
    return '<button type="button" onclick="insertTag(this, \'[size='.$size.']\',\'[/size]\');"><span style="font-size:'.$size.'pt;">'.$size.'. Ipsum</span></button>';
}
function s_u($char)
{
    return '<button type="button" onclick="insertChar(this, \''.$char.'\');"><span>'.$char.'</span></button>';
}
function form_formatting_toolbar($extended = false)
{
    $html = '';

    $html .= '<p class="formatbut">'."\n";
    $html .= "\t".'<button id="button01" class="but" type="button" title="'.$GLOBALS['lang']['bouton-gras'].'" onclick="insertTag(this, \'[b]\',\'[/b]\');"><span></span></button>'."\n";
    $html .= "\t".'<button id="button02" class="but" type="button" title="'.$GLOBALS['lang']['bouton-ital'].'" onclick="insertTag(this, \'[i]\',\'[/i]\');"><span></span></button>'."\n";
    $html .= "\t".'<button id="button03" class="but" type="button" title="'.$GLOBALS['lang']['bouton-soul'].'" onclick="insertTag(this, \'[u]\',\'[/u]\');"><span></span></button>'."\n";
    $html .= "\t".'<button id="button04" class="but" type="button" title="'.$GLOBALS['lang']['bouton-barr'].'" onclick="insertTag(this, \'[s]\',\'[/s]\');"><span></span></button>'."\n";

    if ($extended) {
        $html .= "\t".'<span class="spacer"></span>'."\n";
        // bouton des couleurs
        $html .= "\t".'<span id="button13" class="but but-dropdown" title=""><span></span><span class="list list-color">'
                .s_color('black').s_color('gray').s_color('silver').s_color('white')
                .s_color('blue').s_color('green').s_color('red').s_color('yellow')
                .s_color('fuchsia').s_color('lime').s_color('aqua').s_color('maroon')
                .s_color('purple').s_color('navy').s_color('teal').s_color('olive')
                .s_color('#ff7000').s_color('#ff9aff').s_color('#a0f7ff').s_color('#ffd700')
                .'</span></span>'."\n";

        // boutons de la taille de caractère
        $html .= "\t".'<span id="button14" class="but but-dropdown" title=""><span></span><span class="list list-size">'
                .s_size('9').s_size('12').s_size('16').s_size('20')
                .'</span></span>'."\n";

        // quelques caractères unicode
        $html .= "\t".'<span id="button15" class="but but-dropdown" title=""><span></span><span class="list list-spechr">'
                .s_u('æ').s_u('Æ').s_u('œ').s_u('Œ').s_u('é').s_u('É').s_u('è').s_u('È').s_u('ç').s_u('Ç').s_u('ù').s_u('Ù').s_u('à').s_u('À').s_u('ö').s_u('Ö')
                .s_u('…').s_u('«').s_u('»').s_u('±').s_u('≠').s_u('×').s_u('÷').s_u('ß').s_u('®').s_u('©').s_u('↓').s_u('↑').s_u('←').s_u('→').s_u('ø').s_u('Ø')
                .s_u('☠').s_u('☣').s_u('☢').s_u('☮').s_u('★').s_u('☯').s_u('☑').s_u('☒').s_u('☐').s_u('♫').s_u('♬').s_u('♪').s_u('♣').s_u('♠').s_u('♦').s_u('❤')
                .s_u('♂').s_u('♀').s_u('☹').s_u('☺').s_u('☻').s_u('♲').s_u('⚐').s_u('⚠').s_u('☂').s_u('√').s_u('∑').s_u('λ').s_u('π').s_u('Ω').s_u('№').s_u('∞')
                .s_u('✌').s_u('😃').s_u('😋').s_u('😕').s_u('😢').s_u('😮').s_u('😵').s_u('😇').s_u('😁').s_u('😘').s_u('😙').s_u('😴')
                .'</span></span>'."\n";

        $html .= "\t".'<span class="spacer"></span>'."\n";
        $html .= "\t".'<button id="button05" class="but" type="button" title="'.$GLOBALS['lang']['bouton-left'].'" onclick="insertTag(this, \'[left]\',\'[/left]\');"><span></span></button>'."\n";
        $html .= "\t".'<button id="button06" class="but" type="button" title="'.$GLOBALS['lang']['bouton-center'].'" onclick="insertTag(this, \'[center]\',\'[/center]\');"><span></span></button>'."\n";
        $html .= "\t".'<button id="button07" class="but" type="button" title="'.$GLOBALS['lang']['bouton-right'].'" onclick="insertTag(this, \'[right]\',\'[/right]\');"><span></span></button>'."\n";
        $html .= "\t".'<button id="button08" class="but" type="button" title="'.$GLOBALS['lang']['bouton-justify'].'" onclick="insertTag(this, \'[justify]\',\'[/justify]\');"><span></span></button>'."\n";

        $html .= "\t".'<span class="spacer"></span>'."\n";
        $html .= "\t".'<button id="button11" class="but" type="button" title="'.$GLOBALS['lang']['bouton-imag'].'" onclick="insertTag(this, \'[img]\',\'|alt[/img]\');"><span></span></button>'."\n";
        $html .= "\t".'<button id="button16" class="but" type="button" title="'.$GLOBALS['lang']['bouton-liul'].'" onclick="insertChar(this, \'\n\n** element 1\n** element 2\n\');"><span></span></button>'."\n";
        $html .= "\t".'<button id="button17" class="but" type="button" title="'.$GLOBALS['lang']['bouton-liol'].'" onclick="insertChar(this, \'\n\n## element 1\n## element 2\n\');"><span></span></button>'."\n";
    }

    $html .= "\t".'<span class="spacer"></span>'."\n";
    $html .= "\t".'<button id="button09" class="but" type="button" title="'.$GLOBALS['lang']['bouton-lien'].'" onclick="insertTag(this, \'[\',\'|http://]\');"><span></span></button>'."\n";
    $html .= "\t".'<button id="button10" class="but" type="button" title="'.$GLOBALS['lang']['bouton-cita'].'" onclick="insertTag(this, \'[quote]\',\'[/quote]\');"><span></span></button>'."\n";
    $html .= "\t".'<button id="button12" class="but" type="button" title="'.$GLOBALS['lang']['bouton-code'].'" onclick="insertTag(this, \'[code]\',\'[/code]\');"><span></span></button>'."\n";

    $html .= '</p>';

    return $html;
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

/* form config RSS feeds: allow changing feeds (title, url) or remove a feed */
function afficher_form_rssconf($errors = '')
{
    if (!empty($errors)) {
        echo erreurs($errors);
    }
    $out = '';
    // form add new feed.
    $out .= '<form id="form-rss-add" method="post" action="feed.php?config">'."\n";
    $out .= '<fieldset class="pref">'."\n";
    $out .= '<legend class="legend-link">'.$GLOBALS['lang']['label_feed_ajout'].'</legend>'."\n";
    $out .= "\t\t\t".'<label for="new-feed">'.$GLOBALS['lang']['label_feed_new'].':</label>'."\n";
    $out .= "\t\t\t".'<input id="new-feed" name="new-feed" type="text" class="text" value="" placeholder="http://www.example.org/rss">'."\n";
    $out .= '<p class="submit-bttns">'."\n";
    $out .= "\t".'<button class="submit button-submit" type="submit" name="send">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
    $out .= '</p>'."\n";
    $out .= hidden_input('token', new_token());
    $out .= hidden_input('verif_envoi', 1);
    $out .= '</fieldset>'."\n";
    $out .= '</form>'."\n";

    // Form edit + list feeds.
    $out .= '<form id="form-rss-config" method="post" action="feed.php?config">'."\n";
    $out .= '<ul>'."\n";
    foreach ($GLOBALS['liste_flux'] as $i => $flux) {
        $out .= "\t".'<li>'."\n";
        $out .= "\t\t".'<span'.( ($flux['iserror'] > 2) ? ' class="feed-error" title="('.$flux['iserror'].' last requests were errors.)" ' : ''  ).'>'."\n";
        $out .= "\t\t\t".'<label for="i_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_titre_flux'].'</label>'."\n";
        $out .= "\t\t\t".'<input id="i_'.$flux['checksum'].'" name="i_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['title']).'">'."\n";
        $out .= "\t\t".'</span>'."\n";
        $out .= "\t\t".'<span>'."\n";
        $out .= "\t\t\t".'<label for="j_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_url_flux'].'</label>'."\n";
        $out .= "\t\t\t".'<input id="j_'.$flux['checksum'].'" name="j_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['link']).'">'."\n";
        $out .= "\t\t".'</span>'."\n";
        $out .= "\t\t".'<span>'."\n";
        $out .= "\t\t\t".'<label for="l_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_dossier'].'</label>'."\n";
        $out .= "\t\t\t".'<input id="l_'.$flux['checksum'].'" name="l_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['folder']).'">'."\n";
        $out .= "\t\t\t".'<input class="remove-feed" name="k_'.$flux['checksum'].'" type="hidden" value="1">'."\n";
        $out .= "\t\t".'</span>'."\n";
        $out .= "\t\t".'<span>'."\n";
        $out .= "\t\t\t".'<button type="button" class="submit button-cancel" onclick="unMarkAsRemove(this)">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
        $out .= "\t\t\t".'<button type="button" class="submit button-delete" onclick="markAsRemove(this)">'.$GLOBALS['lang']['supprimer'].'</button>'."\n";
        $out .= "\t\t".'</span>';
        $out .= "\t".'</li>'."\n";
    }
    $out .= '</ul>'."\n";
    $out .= '<p class="submit-bttns">'."\n";
    $out .= "\t".'<button class="submit button-submit" type="submit" name="send">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
    $out .= '</p>'."\n";
    $out .= hidden_input('token', new_token());
    $out .= hidden_input('verif_envoi', 1);
    $out .= '</form>'."\n";

    return $out;
}


/* FORMULAIRE NORMAL DES PRÉFÉRENCES */
function afficher_form_prefs($erreurs = '')
{
    $submit_box = '<div class="submit-bttns">'."\n";
    $submit_box .= hidden_input('_verif_envoi', '1');
    $submit_box .= hidden_input('token', new_token());
    $submit_box .= '<button class="submit button-cancel" type="button" onclick="annuler(\'preferences.php\');" >'.$GLOBALS['lang']['annuler'].'</button>'."\n";
    $submit_box .= '<button class="submit button-submit" type="submit" name="enregistrer">'.$GLOBALS['lang']['enregistrer'].'</button>'."\n";
    $submit_box .= '</div>'."\n";


    echo '<form id="preferences" method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'" >' ;
        echo erreurs($erreurs);
        $fld_user = '<div role="group" class="pref">'; /* no fieldset because browset can’t style them correctly */
        $fld_user .= '<div class="form-legend"><legend class="legend-user">'.$GLOBALS['lang']['prefs_legend_utilisateur'].'</legend></div>'."\n";

        $fld_user .= '<div class="form-lines">'."\n";
        $fld_user .= '<p>'."\n";
        $fld_user .= "\t".'<label for="auteur">'.$GLOBALS['lang']['pref_auteur'].'</label>'."\n";
        $fld_user .= "\t".'<input type="text" id="auteur" name="auteur" size="30" value="'.(empty($GLOBALS['auteur']) ? htmlspecialchars(USER_LOGIN) : $GLOBALS['auteur']).'" class="text" />'."\n";
        $fld_user .= '</p>'."\n";

        $fld_user .= '<p>'."\n";
        $fld_user .= "\t".'<label for="email">'.$GLOBALS['lang']['pref_email'].'</label>'."\n";
        $fld_user .= "\t".'<input type="text" id="email" name="email" size="30" value="'.$GLOBALS['email'].'" class="text" />'."\n";
        $fld_user .= '</p>'."\n";

        $fld_user .= '<p>'."\n";
        $fld_user .= "\t".'<label for="nomsite">'.$GLOBALS['lang']['pref_nom_site'].'</label>'."\n";
        $fld_user .= "\t".'<input type="text" id="nomsite" name="nomsite" size="30" value="'.$GLOBALS['nom_du_site'].'" class="text" />'."\n";
        $fld_user .= '</p>'."\n";

        $fld_user .= '<p>'."\n";
        $fld_user .= "\t".'<label for="racine">'.$GLOBALS['lang']['pref_racine'].'</label>'."\n";
        $fld_user .= "\t".'<input type="text" id="racine" name="racine" size="30" value="'.$GLOBALS['racine'].'" class="text" />'."\n";
        $fld_user .= '</p>'."\n";

        $fld_user .= '<p>'."\n";
        $fld_user .= "\t".'<label for="description">'.$GLOBALS['lang']['label_dp_description'].'</label>'."\n";
        $fld_user .= "\t".'<textarea id="description" name="description" cols="35" rows="2" class="text" >'.$GLOBALS['description'].'</textarea>'."\n";
        $fld_user .= '</p>'."\n";

        $fld_user .= '<p>'."\n";
        $fld_user .= "\t".'<label for="keywords">'.$GLOBALS['lang']['pref_keywords'].'</label>';
        $fld_user .= "\t".'<textarea id="keywords" name="keywords" cols="35" rows="2" class="text" >'.$GLOBALS['keywords'].'</textarea>'."\n";
        $fld_user .= '</p>'."\n";
        $fld_user .= '</div>'."\n";

        $fld_user .= $submit_box;

        $fld_user .= '</div>';
    echo $fld_user;

        $fld_securite = '<div role="group" class="pref">';
        $fld_securite .= '<div class="form-legend"><legend class="legend-securite">'.$GLOBALS['lang']['prefs_legend_securite'].'</legend></div>'."\n";

        $fld_securite .= '<div class="form-lines">'."\n";
        $fld_securite .= '<p>'."\n";
        $fld_securite .= "\t".'<label for="identifiant">'.$GLOBALS['lang']['pref_identifiant'].'</label>'."\n";
        $fld_securite .= "\t".'<input type="text" id="identifiant" name="identifiant" size="30" value="'.htmlspecialchars(USER_LOGIN).'" class="text" />'."\n";
        $fld_securite .= '</p>'."\n";

        $fld_securite .= '<p>'."\n";
        $fld_securite .= "\t".'<label for="mdp">'.$GLOBALS['lang']['pref_mdp'].'</label>';
        $fld_securite .= "\t".'<input type="password" id="mdp" name="mdp" size="30" value="" class="text" autocomplete="off" />'."\n";
        $fld_securite .= '</p>'."\n";

        $fld_securite .= '<p>'."\n";
        $fld_securite .= "\t".'<label for="mdp_rep">'.$GLOBALS['lang']['pref_mdp_nouv'].'</label>';
        $fld_securite .= "\t".'<input type="password" id="mdp_rep" name="mdp_rep" size="30" value="" class="text" autocomplete="off" />'."\n";
        $fld_securite .= '</p>'."\n";
        $fld_securite .= '</div>';

        $fld_securite .= $submit_box;

        $fld_securite .= '</div>';
    echo $fld_securite;

        $fld_apparence = '<div role="group" class="pref">';
        $fld_apparence .= '<div class="form-legend"><legend class="legend-apparence">'.$GLOBALS['lang']['prefs_legend_apparence'].'</legend></div>'."\n";

        $fld_apparence .= '<div class="form-lines">'."\n";
        $fld_apparence .= '<p>'."\n";
        $fld_apparence .= form_select('theme', liste_themes(BT_ROOT.DIR_THEMES), $GLOBALS['theme_choisi'], $GLOBALS['lang']['pref_theme']);
        $fld_apparence .= '</p>'."\n";

        $fld_apparence .= '<p>'."\n";
        $fld_apparence .= form_select('nb_maxi', array('5'=>'5', '10'=>'10', '15'=>'15', '20'=>'20', '25'=>'25', '50'=>'50'), $GLOBALS['max_bill_acceuil'], $GLOBALS['lang']['pref_nb_maxi']);
        $fld_apparence .= '</p>'."\n";

        $nbs = array('10'=>'10', '25'=>'25', '50'=>'50', '100'=>'100', '300'=>'300', '-1' => $GLOBALS['lang']['pref_all']);
        $fld_apparence .= '<p>'."\n";
        $fld_apparence .= form_select('nb_list', $nbs, $GLOBALS['max_bill_admin'], $GLOBALS['lang']['pref_nb_list']);
        $fld_apparence .= '</p>'."\n";

        $fld_apparence .= '<p>'."\n";
        $fld_apparence .= form_select('nb_list_com', $nbs, $GLOBALS['max_comm_admin'], $GLOBALS['lang']['pref_nb_list_com']);
        $fld_apparence .= '</p>'."\n";

        $fld_apparence .= '<p>'."\n";
        $fld_apparence .= form_checkbox('aff_onglet_rss', $GLOBALS['onglet_rss'], $GLOBALS['lang']['pref_afficher_rss']);
        $fld_apparence .= '</p>'."\n";

        $fld_apparence .= '<p>'."\n";
        $fld_apparence .= form_checkbox('aff_onglet_liens', $GLOBALS['onglet_liens'], $GLOBALS['lang']['pref_afficher_liens']);
        $fld_apparence .= '</p>'."\n";
        $fld_apparence .= '</div>'."\n";

        $fld_apparence .= $submit_box;

        $fld_apparence .= '</div>';
    echo $fld_apparence;

        $fld_dateheure = '<div role="group" class="pref">';
        $fld_dateheure .= '<div class="form-legend"><legend class="legend-dateheure">'.$GLOBALS['lang']['prefs_legend_langdateheure'].'</legend></div>'."\n";

        $fld_dateheure .= '<div class="form-lines">'."\n";
        $fld_dateheure .= '<p>'."\n";
        $fld_dateheure .= form_langue($GLOBALS['lang']['id']);
        $fld_dateheure .= '</p>'."\n";

        $fld_dateheure .= '<p>'."\n";
        $fld_dateheure .= form_format_date($GLOBALS['format_date']);
        $fld_dateheure .= '</p>'."\n";

        $fld_dateheure .= '<p>'."\n";
        $fld_dateheure .= form_format_heure($GLOBALS['format_heure']);
        $fld_dateheure .= '</p>'."\n";

        $fld_dateheure .= '<p>'."\n";
        $fld_dateheure .= form_fuseau_horaire($GLOBALS['fuseau_horaire']);
        $fld_dateheure .= '</p>'."\n";
        $fld_dateheure .= '</div>'."\n";

        $fld_dateheure .= $submit_box;

        $fld_dateheure .= '</div>';
    echo $fld_dateheure;

        $fld_cfg_blog = '<div role="group" class="pref">';
        $fld_cfg_blog .= '<div class="form-legend"><legend class="legend-blogcomm">'.$GLOBALS['lang']['prefs_legend_configblog'].'</legend></div>'."\n";

        $fld_cfg_blog .= '<div class="form-lines">'."\n";
        $fld_cfg_blog .= '<p>'."\n";
        $fld_cfg_blog .= form_checkbox('activer_categories', $GLOBALS['activer_categories'], $GLOBALS['lang']['pref_categories']);
        $fld_cfg_blog .= '</p>'."\n";

        $fld_cfg_blog .= '<p>'."\n";
        $fld_cfg_blog .= form_checkbox('auto_keywords', $GLOBALS['automatic_keywords'], $GLOBALS['lang']['pref_automatic_keywords']);
        $fld_cfg_blog .= '</p>'."\n";

        $fld_cfg_blog .= '<p>'."\n";
        $fld_cfg_blog .= form_checkbox('global_comments', $GLOBALS['global_com_rule'], $GLOBALS['lang']['pref_allow_global_coms']);
        $fld_cfg_blog .= '</p>'."\n";

        $fld_cfg_blog .= '<p>'."\n";
        $fld_cfg_blog .= form_checkbox('require_email', $GLOBALS['require_email'], $GLOBALS['lang']['pref_force_email']);
        $fld_cfg_blog .= '</p>'."\n";

        $fld_cfg_blog .= '<p>'."\n";
        $fld_cfg_blog .= form_checkbox('alert_author', $GLOBALS['alert_author'], $GLOBALS['lang']['pref_alert_author']);
        $fld_cfg_blog .= '</p>'."\n";

        $fld_cfg_blog .= '<p>'."\n";
        $fld_cfg_blog .= form_select('comm_defaut_status', array('1' => $GLOBALS['lang']['pref_comm_black_list'], '0' => $GLOBALS['lang']['pref_comm_white_list']), $GLOBALS['comm_defaut_status'], $GLOBALS['lang']['pref_comm_BoW_list']);
        $fld_cfg_blog .= '</p>'."\n";
        $fld_cfg_blog .= '</div>'."\n";

        $fld_cfg_blog .= $submit_box;

        $fld_cfg_blog .= '</div>';
    echo $fld_cfg_blog;

    if ($GLOBALS['onglet_liens']) {
        $fld_cfg_linx = '<div role="group" class="pref">';
        $fld_cfg_linx .= '<div class="form-legend"><legend class="legend-links">'.$GLOBALS['lang']['prefs_legend_configlinx'].'</legend></div>'."\n";

        $fld_cfg_linx .= '<div class="form-lines">'."\n";
        // nb liens côté admin
        $nbs = array('50'=>'50', '100'=>'100', '200'=>'200', '300'=>'300', '500'=>'500', '-1' => $GLOBALS['lang']['pref_all']);

        $fld_cfg_linx .= '<p>'."\n";
        $fld_cfg_linx .= form_select('nb_list_linx', $nbs, $GLOBALS['max_linx_admin'], $GLOBALS['lang']['pref_nb_list_linx']);
        $fld_cfg_linx .= '</p>'."\n";

        // partage de fichiers !pages : télécharger dans fichiers automatiquement ?
        $nbs = array('0'=> $GLOBALS['lang']['non'], '1'=> $GLOBALS['lang']['oui'], '2' => $GLOBALS['lang']['pref_ask_everytime']);

        $fld_cfg_linx .= '<p>'."\n";
        $fld_cfg_linx .= form_select('dl_link_to_files', $nbs, $GLOBALS['dl_link_to_files'], $GLOBALS['lang']['pref_linx_dl_auto']);
        $fld_cfg_linx .= '</p>'."\n";

        // lien à glisser sur la barre des favoris
        $a = explode('/', dirname($_SERVER['SCRIPT_NAME']));
        $fld_cfg_linx .= '<p>';
        $fld_cfg_linx .= '<label>'.$GLOBALS['lang']['pref_label_bookmark_lien'].'</label>'."\n";
        $fld_cfg_linx .= '<a class="dnd-to-favs" onclick="alert(\''.$GLOBALS['lang']['pref_label_bookmark_lien'].'\');return false;" href="javascript:javascript:(function(){window.open(\''.$GLOBALS['racine'].$a[count($a)-1].'/links.php?url=\'+encodeURIComponent(location.href));})();">Save link</a>';
        $fld_cfg_linx .= '</p>'."\n";
        $fld_cfg_linx .= '</div>'."\n";

        $fld_cfg_linx .= $submit_box;

        $fld_cfg_linx .= '</div>';
        echo $fld_cfg_linx;
    }


    if ($GLOBALS['onglet_rss']) {
        /* TODO
        - Open=read ? + button to mark as read in HTML
        - Export OPML
        */
        $fld_cfg_rss = '<div role="group" class="pref">';
        $fld_cfg_rss .= '<div class="form-legend"><legend class="legend-rss">'.$GLOBALS['lang']['prefs_legend_configrss'].'</legend></div>'."\n";
        $fld_cfg_rss .= '<div class="form-lines">'."\n";

        $fld_cfg_rss .= '<p>'."\n";
        $a = explode('/', dirname($_SERVER['SCRIPT_NAME']));
        $fld_cfg_rss .= '<label>'.$GLOBALS['lang']['pref_label_crontab_rss'].'</label>'."\n";
        $fld_cfg_rss .= '<a onclick="prompt(\''.$GLOBALS['lang']['pref_alert_crontab_rss'].'\', \'0 *  *   *   *   wget --spider -qO- '.$GLOBALS['racine'].$a[count($a)-1].'/_rss.ajax.php?guid='.BLOG_UID.'&refresh_all'.'\');return false;" href="#">Afficher ligne Cron</a>';
        $fld_cfg_rss .= '</p>'."\n";

        $fld_cfg_rss .= '<p>'."\n";
        $fld_cfg_rss .= "\t".'<label>'.$GLOBALS['lang']['pref_rss_go_to_imp-export'].'</label>'."\n";
        $fld_cfg_rss .= "\t".'<a href="maintenance.php">'.$GLOBALS['lang']['label_import-export'].'</a>'."\n";
        $fld_cfg_rss .= '</p>'."\n";

        $fld_cfg_rss .= '<p>'."\n";
        $fld_cfg_rss .= '</p>'."\n";

        $fld_cfg_rss .= '</div>'."\n";

        $fld_cfg_rss .= $submit_box;
        $fld_cfg_rss .= '</div>';
        echo $fld_cfg_rss;
    }


        $fld_maintenance = '<div role="group" class="pref">';
        $fld_maintenance .= '<div class="form-legend"><legend class="legend-sweep">'.$GLOBALS['lang']['titre_maintenance'].'</legend></div>'."\n";

        $fld_maintenance .= '<div class="form-lines">'."\n";
        $fld_maintenance .= '<p>'."\n";
        $fld_maintenance .= form_checkbox('check_update', $GLOBALS['check_update'], $GLOBALS['lang']['pref_check_update']);
        $fld_maintenance .= '</p>'."\n";

        $fld_maintenance .= '<p>'."\n";
        $fld_maintenance .= "\t".'<label>'.$GLOBALS['lang']['pref_go_to_maintenance'].'</label>'."\n";
        $fld_maintenance .= "\t".'<a href="maintenance.php">Maintenance</a>'."\n";
        $fld_maintenance .= '</p>'."\n";
        $fld_maintenance .= '</div>'."\n";

        $fld_maintenance .= $submit_box;

        $fld_maintenance .= '</div>';
    echo $fld_maintenance;

    // check if a new Blogotext version is available (code from Shaarli, by Sebsauvage).
    // Get latest version number at most once a day.
    if ($GLOBALS['check_update'] == 1) {
        $version_file = '../config/version.txt';
        if (!is_file($version_file) or (filemtime($version_file) < time()-(24*60*60))) {
            $version_hit_url = 'http://lehollandaisvolant.net/blogotext/version.php';
            $response = request_external_files(array($version_hit_url), 6, false);
            $last_version = $response[$version_hit_url]['body'];
            // If failed, nevermind. We don't want to bother the user with that.
            if (empty($last_version)) {
                file_put_contents($version_file, BLOGOTEXT_VERSION); // touch
            } else {
                file_put_contents($version_file, $last_version); // rewrite file
            }
        }

        // Compare versions:
        $newestversion = file_get_contents($version_file);
        if (version_compare($newestversion, BLOGOTEXT_VERSION) == 1) {
                $fld_update = '<div role="group" class="pref">';
                $fld_update .= '<div class="form-legend"><legend class="legend-update">'.$GLOBALS['lang']['maint_chk_update'].'</legend></div>'."\n";
                $fld_update .= '<div class="form-lines">'."\n";
                $fld_update .= '<p>'."\n";
                $fld_update .= "\t".'<label>'.$GLOBALS['lang']['maint_update_youisbad'].' ('.$newestversion.'). '.$GLOBALS['lang']['maint_update_go_dl_it'].'</label>'."\n";
                $fld_update .= "\t".'<a href="http://lehollandaisvolant.net/blogotext/">lehollandaisvolant.net/blogotext</a>.';
                $fld_update .= '</p>'."\n";
                $fld_update .= '</div>'."\n";
                $fld_update .= '</div>'."\n";
            echo $fld_update;
        }
    }

    echo '</form>'."\n";
}
