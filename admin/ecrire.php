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

define('BT_ROOT', '../');

require_once '../inc/inc.php';

operate_session();
$begin = microtime(true);
$GLOBALS['db_handle'] = open_base();

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
afficher_html_head($titre_ecrire);
echo '<div id="header">'."\n";
    echo '<div id="top">'."\n";
    afficher_msg();
    afficher_topnav($titre_ecrire_court);
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
    apercu($post);
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
