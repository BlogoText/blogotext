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

# Generic forms

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

# Preferences forms


function form_checkbox($name, $checked, $label)
{
    $checked = ($checked) ? "checked " : '';
    $form = '<input type="checkbox" id="'.$name.'" name="'.$name.'" '.$checked.' class="checkbox-toggle" />'."\n" ;
    $form .= '<label for="'.$name.'" >'.$label.'</label>'."\n";
    return $form;
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
