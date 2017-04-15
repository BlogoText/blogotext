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


/**
 *
 */
function hidden_input($nom, $valeur, $id = 0)
{
    $id = ($id === 0) ? '' : ' id="'.$nom.'"';
    $form = '<input type="hidden" name="'.$nom.'"'.$id.' value="'.$valeur.'" />'."\n";
    return $form;
}


/**
 *
 */
function valider_form_commentaire($commentaire, $mode)
{
    $erreurs = array();
    if (!strlen(trim($commentaire['bt_author']))) {
        $erreurs[] = $GLOBALS['lang']['err_comm_auteur'];
    }
    if (!empty($commentaire['bt_email']) or $GLOBALS['require_email'] == 1) { // if email is required, or is given, it must be valid
        if (!preg_match('#^[-\w!%+~\'*"\[\]{}.=]+@[\w.-]+\.[a-zA-Z]{2,6}$#i', trim($commentaire['bt_email']))) {
            $erreurs[] = $GLOBALS['lang']['err_comm_email'] ;
        }
    }
    if (!strlen(trim($commentaire['bt_content'])) or $commentaire['bt_content'] == "<p></p>") { // comment may not be empty
        $erreurs[] = $GLOBALS['lang']['err_comm_contenu'];
    }
    if (!preg_match('/\d{14}/', $commentaire['bt_article_id'])) { // comment has to be on a valid article_id
        $erreurs[] = $GLOBALS['lang']['err_comm_article_id'];
    }

    if (trim($commentaire['bt_webpage']) != "") { // given url has to be valid
        if (!preg_match('#^(https?://[\S]+)[a-z]{2,6}[-\#_\w?%*:.;=+\(\)/&~$,]*$#', trim($commentaire['bt_webpage']))) {
            $erreurs[] = $GLOBALS['lang']['err_comm_webpage'];
        }
    }
    if ($mode != 'admin') { // if public : tests captcha as well
        $ua = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if ($_POST['_token'] != sha1($ua.$_POST['captcha'])) {
            $erreurs[] = $GLOBALS['lang']['err_comm_captcha'];
        }
    } else { // mode admin : test token
        if (!( isset($_POST['token']) and check_token($_POST['token']))) {
            $erreurs[] = $GLOBALS['lang']['err_wrong_token'];
        }
    }
    return $erreurs;
}

/**
 *
 */
function valider_form_commentaire_ajax($commentaire)
{
    $erreurs = array();
    if (!is_numeric($commentaire)) { // comment has to be on a valid ID
        $erreurs[] = $GLOBALS['lang']['err_comm_article_id'];
    }
    // test token
    if (!( isset($_POST['token']) and check_token($_POST['token']))) {
        $erreurs[] = $GLOBALS['lang']['err_wrong_token'];
    }
    return $erreurs;
}


/**
 *
 */
function s_color($color)
{
    return '<button type="button" onclick="insertTag(this, \'[color='.$color.']\',\'[/color]\');"><span style="background:'.$color.';"></span></button>';
}

/**
 *
 */
function s_size($size)
{
    return '<button type="button" onclick="insertTag(this, \'[size='.$size.']\',\'[/size]\');"><span style="font-size:'.$size.'pt;">'.$size.'. Ipsum</span></button>';
}

/**
 *
 */
function s_u($char)
{
    return '<button type="button" onclick="insertChar(this, \''.$char.'\');"><span>'.$char.'</span></button>';
}

/**
 *
 */
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
