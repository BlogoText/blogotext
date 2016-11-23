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
