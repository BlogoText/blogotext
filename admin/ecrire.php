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

require_once 'inc/boot.php';


$GLOBALS['db_handle'] = open_base();


// POST ARTICLE
function markup_articles($texte)
{
    $texte = preg_replace("/(\r\n|\r\n\r|\n|\n\r|\r)/", "\r", $texte);
    $tofind = array(
        // Replace \r with \n when following HTML elements
        '#<(.*?)>\r#',

        // Jusitifications
        /* left    */ '#\[left\](.*?)\[/left\]#s',
        /* center  */ '#\[center\](.*?)\[/center\]#s',
        /* right   */ '#\[right\](.*?)\[/right\]#s',
        /* justify */ '#\[justify\](.*?)\[/justify\]#s',

        // Misc
        /* regex URL     */ '#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s]+))#i',
        /* a href        */ '#\[([^[]+)\|([^[]+)\]#',
        /* url           */ '#\[(https?://)([^[]+)\]#',
        /* [img]         */ '#\[img\](.*?)(\|(.*?))?\[/img\]#s',
        /* strong        */ '#\[b\](.*?)\[/b\]#s',
        /* italic        */ '#\[i\](.*?)\[/i\]#s',
        /* strike        */ '#\[s\](.*?)\[/s\]#s',
        /* underline     */ '#\[u\](.*?)\[/u\]#s',
        /* ul/li         */ '#\*\*(.*?)(\r|$)#s',  // br because of prev replace
        /* ul/li         */ '#</ul>\r<ul>#s',
        /* ol/li         */ '#\#\#(.*?)(\r|$)#s',  // br because of prev replace
        /* ol/li         */ '#</ol>\r<ol>#s',
        /* quote         */ '#\[quote\](.*?)\[/quote\]#s',
        /* code          */ '#\[code\]\[/code\]#s',
        /* code=language */ '#\[code=(\w+)\]\[/code\]#s',
        /* color         */ '#\[color=(?:")?(\w+|\#(?:[0-9a-fA-F]{3}){1,2})(?:")?\](.*?)\[/color\]#s',
        /* size          */ '#\[size=(\\\?")?([0-9]{1,})(\\\?")?\](.*?)\[/size\]#s',

        // Adding some &nbsp;
        '# (»|!|:|\?|;)#',
        '#« #',
    );
    $toreplace = array(
        // Replace \r with \n
        '<$1>'."\n",

        // Jusitifications
        /* left    */ '<div style="text-align:left;">$1</div>',
        /* center  */ '<div style="text-align:center;">$1</div>',
        /* right   */ '<div style="text-align:right;">$1</div>',
        /* justify */ '<div style="text-align:justify;">$1</div>',

        // Misc
        /* regex URL     */ '$1<a href="$2">$2</a>',
        /* a href        */ '<a href="$2">$1</a>',
        /* url           */ '<a href="$1$2">$2</a>',
        /* [img]         */ '<img src="$1" alt="$3" />',
        /* strong        */ '<b>$1</b>',
        /* italic        */ '<em>$1</em>',
        /* strike        */ '<del>$1</del>',
        /* underline     */ '<u>$1</u>',
        /* ul/li         */ '<ul><li>$1</li></ul>'."\r",
        /* ul/li         */ "\r",
        /* ol/li         */ '<ol><li>$1</li></ol>'."\r",
        /* ol/li         */ '',
        /* quote         */ '<blockquote>$1</blockquote>'."\r",
        /* code          */ '<prebtcode></prebtcode>'."\r",
        /* code=language */ '<prebtcode data-language="$1"></prebtcode>'."\r",
        /* color         */ '<span style="color:$1;">$2</span>',
        /* size          */ '<span style="font-size:$2pt;">$4</span>',

        // Adding some &nbsp;
        ' $1',
        '« ',
    );

    // memorizes [code] tags contents before bbcode being appliyed
    preg_match_all('#\[code(=(\w+))?\](.*?)\[/code\]#s', $texte, $code_contents, PREG_SET_ORDER);
    // empty the [code] tags (content is in memory)
    $texte_formate = preg_replace('#\[code(=(\w+))?\](.*?)\[/code\]#s', '[code$1][/code]', $texte);
    // apply bbcode filter
    $texte_formate = preg_replace($tofind, $toreplace, $texte_formate);
    // apply <p>paragraphe</p> filter
    $texte_formate = parse_texte_paragraphs($texte_formate);
    // replace [code] elements with theire initial content
    $texte_formate = parse_texte_code($texte_formate, $code_contents);

    return $texte_formate;
}

function init_post_article()
{
    //no $mode : it's always admin.
    $formated_contenu = markup_articles(clean_txt($_POST['contenu']));
    if ($GLOBALS['automatic_keywords'] == '0') {
        $keywords = protect($_POST['mots_cles']);
    } else {
        $keywords = extraire_mots($_POST['titre'].' '.$formated_contenu);
    }

    $date = str4($_POST['annee']).str2($_POST['mois']).str2($_POST['jour']).str2($_POST['heure']).str2($_POST['minutes']).str2($_POST['secondes']);
    $id = (isset($_POST['article_id']) and preg_match('#\d{14}#', $_POST['article_id'])) ? $_POST['article_id'] : $date;

    $article = array (
        'bt_id'             => $id,
        'bt_date'           => $date,
        'bt_title'          => protect($_POST['titre']),
        'bt_abstract'       => (empty($_POST['chapo'])) ? '' : clean_txt($_POST['chapo']),
        'bt_notes'          => protect($_POST['notes']),
        'bt_content'        => $formated_contenu,
        'bt_wiki_content'   => clean_txt($_POST['contenu']),
        'bt_link'           => '', // this one is not needed yet. Maybe in the futur. I dunno why it is still in the DB…
        'bt_keywords'       => $keywords,
        'bt_tags'           => (isset($_POST['categories'])) ? htmlspecialchars(traiter_tags($_POST['categories'])) : '', // htmlSpecialChars() nedded to escape the (") since tags are put in a <input/>. (') are escaped in form_categories(), with addslashes – not here because of JS problems :/
        'bt_statut'         => $_POST['statut'],
        'bt_allow_comments' => $_POST['allowcomment'],
    );

    if (isset($_POST['ID']) and is_numeric($_POST['ID'])) { // ID only added on edit.
        $article['ID'] = $_POST['ID'];
    }
    return $article;
}

// once form is initiated, and no errors are found, treat it (save it to DB).
function traiter_form_billet($billet)
{
    if (isset($_POST['enregistrer']) and !isset($billet['ID'])) {
        $result = bdd_article($billet, 'enregistrer-nouveau');
        $redir = basename($_SERVER['SCRIPT_NAME']).'?post_id='.$billet['bt_id'].'&msg=confirm_article_maj';
    } elseif (isset($_POST['enregistrer']) and isset($billet['ID'])) {
        $result = bdd_article($billet, 'modifier-existant');
        $redir = basename($_SERVER['SCRIPT_NAME']).'?post_id='.$billet['bt_id'].'&msg=confirm_article_ajout';
    } elseif (isset($_POST['supprimer']) and isset($_POST['ID']) and is_numeric($_POST['ID'])) {
        $result = bdd_article($billet, 'supprimer-existant');
        try {
            $sql = '
                DELETE FROM commentaires
                 WHERE bt_article_id=?';
            $req = $GLOBALS['db_handle']->prepare($sql);
            $req->execute(array($_POST['article_id']));
        } catch (Exception $e) {
            die('Erreur Suppr Comm associés: '.$e->getMessage());
        }

        $redir = 'articles.php?msg=confirm_article_suppr';
    }
    if ($result === true) {
        flux_refresh_cache_lv1();
        redirection($redir);
    } else {
        die($result);
    }
}

function form_annee($year_shown)
{
    return '<input type="number" name="annee" max="'.(date('Y') + 3).'" value="'.$year_shown.'">'."\n";
}

function form_mois($mois_affiche)
{
    $mois = array(
        $GLOBALS['lang']['janvier'],
        $GLOBALS['lang']['fevrier'],
        $GLOBALS['lang']['mars'],
        $GLOBALS['lang']['avril'],
        $GLOBALS['lang']['mai'],
        $GLOBALS['lang']['juin'],
        $GLOBALS['lang']['juillet'],
        $GLOBALS['lang']['aout'],
        $GLOBALS['lang']['septembre'],
        $GLOBALS['lang']['octobre'],
        $GLOBALS['lang']['novembre'],
        $GLOBALS['lang']['decembre']
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
    for ($jour = 1; $jour <= 31; ++$jour) {
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
    $choix = array(
        $GLOBALS['lang']['label_invisible'],
        $GLOBALS['lang']['label_publie']
    );
    return form_select('statut', $choix, $etat, $GLOBALS['lang']['label_dp_etat']);
}

function form_allow_comment($etat)
{
    $choix= array(
        $GLOBALS['lang']['fermes'],
        $GLOBALS['lang']['ouverts']
    );
    return form_select('allowcomment', $choix, $etat, $GLOBALS['lang']['label_dp_commentaires']);
}

// Post form
function afficher_form_billet($article, $erreurs)
{
    $html = '';

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
        $statutdefaut = 1;
        $allowcommentdefaut = 1;
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

    if ($GLOBALS['activer_categories'] == 1) {
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


// Traitment
$erreurs_form = array();
if (isset($_POST['_verif_envoi'])) {
    $billet = init_post_article();
    $erreurs_form = valider_form_billet($billet);
    if (empty($erreurs_form)) {
        traiter_form_billet($billet);
    }
}

// Retrieve post's informations if ID
$post = '';
$article_id = '';
if (isset($_GET['post_id'])) {
    $article_id = htmlspecialchars($_GET['post_id']);
    $query = 'SELECT * FROM articles WHERE bt_id LIKE ?';
    $posts = liste_elements($query, array($article_id), 'articles');
    if (isset($posts[0])) {
        $post = $posts[0];
    }
}

// Page's title
if (!empty($post)) {
    $titre_ecrire_court = $GLOBALS['lang']['titre_maj'];
    $titre_ecrire = $titre_ecrire_court.' : '.$post['bt_title'];
} else {
    $post = '';
    $titre_ecrire_court = $GLOBALS['lang']['titre_ecrire'];
    $titre_ecrire = $titre_ecrire_court;
}

// Start page
tpl_show_html_head($titre_ecrire);

echo '<div id="header">'."\n";
    echo '<div id="top">'."\n";
    tpl_show_msg();
    tpl_show_topnav($titre_ecrire_court);
    echo '</div>'."\n";
echo '</div>'."\n";

// Subnav
echo '<div id="axe">'."\n";
if ($post != '') {
    echo '<div id="subnav">'."\n";
        echo '<div class="nombre-elem">';
        echo '<a href="'.$post['bt_link'].'">'.$GLOBALS['lang']['post_link'].'</a> &nbsp; – &nbsp; ';
        echo '<a href="'.$post['bt_link'].'&share">'.$GLOBALS['lang']['post_share'].'</a> &nbsp; – &nbsp; ';
        echo '<a href="commentaires.php?post_id='.$article_id.'">'.ucfirst(nombre_objets($post['bt_nb_comments'], 'commentaire')).'</a>';
        echo '</div>'."\n";
    echo '</div>'."\n";
}

echo '<div id="page">'."\n";

// Show the post
if ($post != '') {
    tpl_show_preview($post);
}
afficher_form_billet($post, $erreurs_form);

echo "\n".'<script src="style/javascript.js"></script>'."\n";
echo '<script>';
echo php_lang_to_js(0);
echo 'var contenuLoad = document.getElementById("contenu").value;
window.addEventListener("beforeunload", function (e) {
    // From https://developer.mozilla.org/en-US/docs/Web/Reference/Events/beforeunload
    var confirmationMessage = BTlang.questionQuitPage;
    if(document.getElementById("contenu").value == contenuLoad) { return true; };
    (e || window.event).returnValue = confirmationMessage || \'\' ; //Gecko + IE
    return confirmationMessage;                                                 // Webkit : ignore this.
});';

echo '</script>';

footer($begin);
