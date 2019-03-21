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


$vars = filter_input_array(INPUT_POST, array(
    'contenu' => FILTER_DEFAULT,
    'mots_cles' => FILTER_DEFAULT,
    'titre' => FILTER_DEFAULT,
    'chapo' => FILTER_DEFAULT,
    'categories' => FILTER_DEFAULT,
    'notes' => FILTER_DEFAULT,

    'token' => FILTER_SANITIZE_STRING,

    'annee' => FILTER_SANITIZE_NUMBER_INT,
    'mois' => FILTER_SANITIZE_NUMBER_INT,
    'jour' => FILTER_SANITIZE_NUMBER_INT,
    'heure' => FILTER_SANITIZE_NUMBER_INT,
    'minutes' => FILTER_SANITIZE_NUMBER_INT,
    'secondes' => FILTER_SANITIZE_NUMBER_INT,
    'statut' => FILTER_SANITIZE_NUMBER_INT,
    'allowcomment' => FILTER_SANITIZE_NUMBER_INT,
    'ID' => FILTER_SANITIZE_NUMBER_INT,
    'article_id' => FILTER_SANITIZE_NUMBER_INT,
));
$vars['enregistrer'] = (filter_input(INPUT_POST, 'enregistrer') !== null);
$vars['supprimer'] = (filter_input(INPUT_POST, 'supprimer') !== null);
$vars['_verif_envoi'] = (filter_input(INPUT_POST, '_verif_envoi') !== null);

/**
 *
 */
function extact_words($text)
{
    $text = str_replace(array("\r", "\n", "\t"), array('', ' ', ' '), $text);
    $text = strip_tags($text);
    $text = preg_replace('#[!"\#$%&\'()*+,./:;<=>?@\[\]^_`{|}~«»“”…]#', ' ', $text);
    $text = trim(preg_replace('# {2,}#', ' ', $text));

    $words = explode(' ', $text);
    foreach ($words as $i => $word) {
    // remove short words & words with numbers
        if (strlen($word) <= 4 or preg_match('#\d#', $word)) {
            unset($words[$i]);
        } elseif (preg_match('#\?#', utf8_decode(preg_replace('#&(.)(acute|grave|circ|uml|cedil|tilde|ring|slash|caron);#', '$1', $word)))) {
            unset($words[$i]);
        }
    }

    // keep only words that occure at least 3 times
    $words = array_unique($words);
    $keywords = array();
    foreach ($words as $i => $word) {
        if (substr_count($text, $word) >= 3) {
            $keywords[] = $word;
        }
    }
    $keywords = array_unique($keywords);

    natsort($keywords);
    return implode($keywords, ', ');
}


/**
 *
 */
function post_markup($text)
{
    // var_dump(__line__);
    // var_dump($text);
    $text = preg_replace("/(\r\n|\r\n\r|\n|\n\r|\r)/", "\r", $text);
    $toFind = array(
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
    $toReplace = array(
    // Replace \r with \n
    '<$1>',
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
    ' $1',
    '« ',
    );

    // memorizes [code] tags contents before bbcode being appliyed
    preg_match_all('#\[code(=(\w+))?\](.*?)\[/code\]#s', $text, $codeContents, PREG_SET_ORDER);
    // empty the [code] tags (content is in memory)
    $textFormated = preg_replace('#\[code(=(\w+))?\](.*?)\[/code\]#s', '[code$1][/code]', $text);

    // memorizes <code><pre> tags contents before bbcode being appliyed
    preg_match_all('#<(code)([^>]*)>(.*?)</\1>#s', $textFormated, $codeHtmlContents, PREG_SET_ORDER);
    // empty the [code] tags (content is in memory)
    $textFormated = preg_replace('#<(code)([^>]*)>(.*?)</\1>#s', '<$1></$1>', $textFormated);

    // apply bbcode filter
    $textFormated = preg_replace($toFind, $toReplace, $textFormated);
    // apply <p>paragraphe</p> filter
    $textFormated = parse_texte_paragraphs($textFormated);
    // replace [code] elements with theire initial content
    $textFormated = parse_texte_code($textFormated, $codeContents);
    // replace <pre> and <code> elements with theire initial content
    $textFormated = parse_texte_code_html($textFormated, $codeHtmlContents);

    // var_dump($textFormated);
    // exit();
    return $textFormated;
}


/**
 *
 */
function parse_texte_code_html($texte, $code_before)
{
    // $i = count($code_before);
    // $j = 0;
    // var_dump($code_before);
    // exit();
    foreach ($code_before as $code) {
        $tag = '<'.$code['1'].'></'.$code['1'].'>';
        $pos = strpos($texte, $tag);
    // if ($code['1'] == 'code') {

    // }
        if ($pos !== false) {
            $code['3'] = htmlspecialchars(htmlspecialchars_decode($code['3']));
            $texte = substr_replace(
                $texte,
                '<'.$code['1'].$code['2'].'>'.$code['3'].'</'.$code['1'].'>',
                $pos,
                strlen($tag)
            );
        }
    }

    return $texte;
}

/*
function parse_texte_code_html($texte, $code_before)
{
    $codes = array_map('array_shift', $code_before);
    var_dump($code_before);
    $i = count($code_before);
    $j = 0;
    while ($j < $i) {
    $pos = strpos($texte, '<'.$codes[$j]['1'].'></'.$codes[$j]['1'].'>');
    var_dump('<'.$codes[$j]['1'].'></'.$codes[$j]['1'].'>');
    var_dump($pos);
    if ($pos !== false) {
        $texte = substr_replace($texte, $codes[$j], $pos, 11);
    }
    ++$j;
    }

    return $texte;
}
 */

/**
 *
 */
function init_post_post()
{
    global $vars;
    $contentFormated = post_markup(clean_txt($vars['contenu']));
    $keywords = (!$GLOBALS['automatic_keywords']) ? protect($vars['mots_cles']) : extact_words($vars['titre'].' '.$contentFormated);
    $date = sprintf(
        '%04d%02d%02d%02d%02d%02d',
        $vars['annee'],
        $vars['mois'] + 1,
        $vars['jour'],
        $vars['heure'],
        $vars['minutes'],
        $vars['secondes']
    );

    $post = array (
    'bt_id' => (preg_match('#\d{14}#', $vars['article_id'])) ? $vars['article_id'] : $date,
    'bt_date' => $date,
    'bt_title' => protect($vars['titre']),
    'bt_abstract' => clean_txt($vars['chapo']),
    'bt_notes' => protect($vars['notes']),
    'bt_content' => $contentFormated,
    'bt_wiki_content' => clean_txt($vars['contenu']),
    'bt_link' => '',  // this one is not needed yet. Maybe in the futur. I dunno why it is still in the DB…
    'bt_keywords' => $keywords,
    'bt_tags' => htmlspecialchars(traiter_tags($vars['categories'])), // htmlSpecialChars() nedded to escape the (") since tags are put in a <input/>. (') are escaped in form_categories(), with addslashes – not here because of JS problems :/
    'bt_statut' => $vars['statut'],
    'bt_allow_comments' => $vars['allowcomment'],
    );

    if ($vars['ID'] > 0) {
    // ID only added on edit
        $post['ID'] = $vars['ID'];
    }
    return $post;
}

/**
 * once form is initiated, and no errors are found, treat it (save it to DB).
 */
function traitment_form_post($post)
{
    global $vars;
    if ($vars['enregistrer']) {
        $result = bdd_article($post, (!empty($post['ID'])) ? 'modifier-existant' : 'enregistrer-nouveau');
        $redir = basename($_SERVER['SCRIPT_NAME']).'?post_id='.$post['bt_id'].'&msg='.((!empty($post['ID'])) ? 'confirm_article_maj' : 'confirm_article_ajout');
    } elseif ($vars['supprimer'] && $vars['ID']) {
        $result = bdd_article($post, 'supprimer-existant');
        $redir = 'articles.php?msg=confirm_article_suppr';
        $sql = '
	    DELETE FROM commentaires
	     WHERE bt_article_id = ?';
        $req = $GLOBALS['db_handle']->prepare($sql);
        $req->execute(array($vars['article_id']));
    }
    if (isset($result)) {
        flux_refresh_cache_lv1();
        redirection($redir);
    }
}

/**
 *
 */
function form_years($displayedYear)
{
    return '<input type="number" name="annee" max="'.(date('Y') + 3).'" value="'.$displayedYear.'">';
}

/**
 *
 */
function form_months($displayedMonth)
{
    $months = array(
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

    $ret = '<select name="mois">' ;
    foreach ($months as $option => $label) {
        $ret .= '<option value="'.htmlentities($option).'"'.(($displayedMonth - 1 == $option) ? ' selected="selected"' : '').'>'.$label.'</option>';
    }
    $ret .= '</select>';
    return $ret;
}

/**
 *
 */
function form_days($displayedDay)
{
    $ret = '<select name="jour">';
    for ($day = 1; $day <= 31; ++$day) {
        $ret .= sprintf(
            '<option value="%02d"%s>%s</option>',
            $day,
            ($displayedDay == $day) ? ' selected="selected"' : '',
            htmlentities($day)
        );
    }
    $ret .='</select>';
    return $ret;
}

/**
 *
 */
function form_statut($etat)
{
    $choix = array(
    $GLOBALS['lang']['label_invisible'],
    $GLOBALS['lang']['label_publie']
    );
    return form_select('statut', $choix, $etat, $GLOBALS['lang']['label_dp_etat']);
}

/**
 *
 */
function form_allow_comment($state)
{
    $choice = array(
    $GLOBALS['lang']['fermes'],
    $GLOBALS['lang']['ouverts']
    );
    return form_select('allowcomment', $choice, $state, $GLOBALS['lang']['label_dp_commentaires']);
}

/**
 * Post form
 */
function display_form_post($post, $errors)
{
    $defaultDay = date('d');
    $defaultMonth = date('m');
    $defaultYear = date('Y');
    $defaultHour = date('H');
    $defaultMinutes = date('i');
    $defaultSeconds = date('s');
    $defaultAbstract = '';
    $defaultContent = '';
    $defaultKeywords = '';
    $defaultTags = '';
    $defaultTitle = '';
    $defaultNotes = '';
    $defaultStatus = 1;
    $defaultAllowComment = 1;

    if ($post) {
        $defaultDay = $post['jour'];
        $defaultMonth = $post['mois'];
        $defaultYear = $post['annee'];
        $defaultHour = $post['heure'];
        $defaultMinutes = $post['minutes'];
        $defaultSeconds = $post['secondes'];
        $defaultTitle = $post['bt_title'];
    // abstract: if empty, it is generated but not added to the DTB
        $defaultAbstract = get_entry($GLOBALS['db_handle'], 'articles', 'bt_abstract', $post['bt_id'], 'return');
        $defaultNotes = $post['bt_notes'];
        $defaultTags = $post['bt_tags'];
        $defaultContent = htmlspecialchars($post['bt_wiki_content']);
        $defaultKeywords = $post['bt_keywords'];
        $defaultStatus = $post['bt_statut'];
        $defaultAllowComment = $post['bt_allow_comments'];
    }

    $html = '';
    if ($errors) {
        $html .= erreurs($errors);
    }
    $html .= sprintf(
        '<form id="form-ecrire" method="post" onsubmit="return moveTag();" action="%s%s">',
        basename($_SERVER['SCRIPT_NAME']),
        (isset($post['bt_id'])) ? '?post_id='.$post['bt_id'] : ''
    );
    $html .= '<div class="main-form">';
    $html .= '<input id="titre" name="titre" type="text" size="50" value="'.$defaultTitle.'" required="" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_titre']).'" tabindex="30" class="text" spellcheck="true" />' ;
    $html .= '<div id="chapo_note">';
    $html .= '<textarea id="chapo" name="chapo" rows="5" cols="20" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_chapo']).'" tabindex="35" class="text" >'.$defaultAbstract.'</textarea>' ;
    $html .= '<textarea id="notes" name="notes" rows="5" cols="20" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_notes']).'" tabindex="40" class="text" >'.$defaultNotes.'</textarea>' ;
    $html .= '</div>';

    $html .= form_formatting_toolbar(true);
    $html .= '<textarea id="contenu" name="contenu" rows="20" cols="60" required="" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_contenu']).'" tabindex="55" class="text">'.$defaultContent.'</textarea>' ;

    if ($GLOBALS['activer_categories']) {
        $html .= '<div id="tag_bloc">';
        $html .= form_categories_links('articles', $defaultTags);
        $html .= '<input list="htmlListTags" type="text" class="text" id="type_tags" name="tags" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'" tabindex="65"/>';
        $html .= '<input type="hidden" id="categories" name="categories" value="" />';
        $html .= '</div>';
    }

    if (!$GLOBALS['automatic_keywords']) {
        $html .= '<input id="mots_cles" name="mots_cles" type="text" size="50" value="'.$defaultKeywords.'" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_motscle']).'" tabindex="67" class="text" />';
    }
    $html .= '</div>';

    $html .= '<div id="date-and-opts">';
    $html .= '<div id="date">';
    $html .= '<span id="formdate">'.form_years($defaultYear).form_months($defaultMonth).form_days($defaultDay).'</span>';
    $html .= '<span id="formheure">';
        $html .= '<input name="heure" type="text" size="2" maxlength="2" value="'.$defaultHour.'" required="" /> : ';
        $html .= '<input name="minutes" type="text" size="2" maxlength="2" value="'.$defaultMinutes.'" required="" /> : ';
        $html .= '<input name="secondes" type="text" size="2" maxlength="2" value="'.$defaultSeconds.'" required="" />';
    $html .= '</span>';
    $html .= '</div>';
    $html .= '<div id="opts">';
        $html .= '<span id="formstatut">'.form_statut($defaultStatus).'</span>';
        $html .= '<span id="formallowcomment">'.form_allow_comment($defaultAllowComment).'</span>';
    $html .= '</div>';

    $html .= '</div>';
    $html .= '<p class="submit-bttns">';

    if ($post) {
        $html .= hidden_input('article_id', $post['bt_id']);
        $html .= hidden_input('article_date', $post['bt_date']);
        $html .= hidden_input('ID', $post['ID']);
        $html .= '<button class="submit button-delete" type="button" name="supprimer" onclick="contenuLoad = document.getElementById(\'contenu\').value; rmArticle(this)" />'.$GLOBALS['lang']['supprimer'].'</button>';
    }
    $html .= '<button class="submit button-cancel" type="button" onclick="annuler(\'articles.php\');">'.$GLOBALS['lang']['annuler'].'</button>';
    $html .= '<button class="submit button-submit" type="submit" name="enregistrer" onclick="contenuLoad=document.getElementById(\'contenu\').value" tabindex="70">'.$GLOBALS['lang']['envoyer'].'</button>';
    $html .= '</p>';
    $html .= hidden_input('_verif_envoi', 1);
    $html .= hidden_input('token', new_token());

    $html .= '</form>';
    echo $html;
}

/**
 *
 */
function validate_form_post($post)
{
    global $vars;
    $date = decode_id($post['bt_id']);
    $errors = array();
    if ($vars['supprimer'] && !check_token($vars['token'])) {
        $errors[] = $GLOBALS['lang']['err_wrong_token'];
    }
    if (!strlen(trim($post['bt_title']))) {
        $errors[] = $GLOBALS['lang']['err_titre'];
    }
    if (!strlen(trim($post['bt_content']))) {
        $errors[] = $GLOBALS['lang']['err_contenu'];
    }
    if (!preg_match('/\d{4}/', $date['annee'])) {
        $errors[] = $GLOBALS['lang']['err_annee'];
    }
    if ((!preg_match('/\d{2}/', $date['mois'])) || ($date['mois'] > '12')) {
        $errors[] = $GLOBALS['lang']['err_mois'];
    }
    if ((!preg_match('/\d{2}/', $date['jour'])) || ($date['jour'] > date('t', mktime(0, 0, 0, $date['mois'], 1, $date['annee'])))) {
        $errors[] = $GLOBALS['lang']['err_jour'];
    }
    if ((!preg_match('/\d{2}/', $date['heure'])) || ($date['heure'] > 23)) {
        $errors[] = $GLOBALS['lang']['err_heure'];
    }
    if ((!preg_match('/\d{2}/', $date['minutes'])) || ($date['minutes'] > 59)) {
        $errors[] = $GLOBALS['lang']['err_minutes'];
    }
    if ((!preg_match('/\d{2}/', $date['secondes'])) || ($date['secondes'] > 59)) {
        $errors[] = $GLOBALS['lang']['err_secondes'];
    }
    return $errors;
}


/**
 * process
 */

$errorsForm = array();
if ($vars['_verif_envoi']) {
    $post = init_post_post();
    $errorsForm = validate_form_post($post);
    if (!$errorsForm) {
        traitment_form_post($post);
    }
}

// Retrieve post's informations on given ID
$post = null;
$postId = (string)filter_input(INPUT_GET, 'post_id');
if ($postId) {
    $postId = htmlspecialchars($postId);
    $query = 'SELECT * FROM articles WHERE bt_id LIKE ?';
    $posts = liste_elements($query, array($postId), 'articles');
    if (isset($posts[0])) {
        $post = $posts[0];
    }
}

// Page's title
$writeTitleLight = ($post) ? $GLOBALS['lang']['titre_maj'] : $GLOBALS['lang']['titre_ecrire'];
$writeTitle = ($post) ? $writeTitleLight.' : '.$post['bt_title'] : $writeTitleLight;



/**
 * echo
 */

echo tpl_get_html_head($writeTitle);

echo '<div id="header">';
    echo '<div id="top">';
    tpl_show_msg();
    echo tpl_show_topnav($writeTitleLight);
    echo '</div>';
echo '</div>';

// Subnav
echo '<div id="axe">';
if ($post) {
    echo '<div id="subnav">';
    echo '<div class="nombre-elem">';
    echo '<a href="'.$post['bt_link'].'">'.$GLOBALS['lang']['post_link'].'</a> &nbsp; – &nbsp; ';
    echo '<a href="'.$post['bt_link'].'&share">'.$GLOBALS['lang']['post_share'].'</a> &nbsp; – &nbsp; ';
    echo '<a href="commentaires.php?post_id='.$postId.'">'.ucfirst(nombre_objets($post['bt_nb_comments'], 'commentaire')).'</a>';
    echo '</div>';
    echo '</div>';
}
// var_dump(__line__);
echo '<div id="page">';

// Show the post
if ($post) {
    tpl_show_preview($post);
}
display_form_post($post, $errorsForm);

echo '<script src="style/javascript.js"></script>';
echo '<script>';
echo php_lang_to_js(0);
echo 'var contenuLoad = document.getElementById("contenu").value;
window.addEventListener("beforeunload", function (e) {
    // From https://developer.mozilla.org/en-US/docs/Web/Reference/Events/beforeunload
    var confirmationMessage = BTlang.questionQuitPage;
    if (document.getElementById("contenu").value == contenuLoad) {
	return true;
    };
    (e || window.event).returnValue = confirmationMessage || ""   //Gecko + IE
    return confirmationMessage;  // Webkit: ignore this.
});';
echo '</script>';

echo tpl_get_footer($begin);
