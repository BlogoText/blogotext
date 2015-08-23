<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2015 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();
$begin = microtime(TRUE);

// OPEN BASE
$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);

// TRAITEMENT
$erreurs_form = array();

//

if (isset($_POST['_verif_envoi'])) {
	$billet = init_post_article();
	$erreurs_form = valider_form_billet($billet);
	if (empty($erreurs_form)) {
		traiter_form_billet($billet);
	}
}

// RECUP INFOS ARTICLE SI DONNÉE
$post = '';
$article_id = '';
if (isset($_GET['post_id'])) {
	$article_id = htmlspecialchars($_GET['post_id']);
	$query = "SELECT * FROM articles WHERE bt_id LIKE ?";
	$posts = liste_elements($query, array($article_id), 'articles');
	if (isset($posts[0])) $post = $posts[0];
}

// TITRE PAGE
if ( !empty($post) ) {
	$titre_ecrire_court = $GLOBALS['lang']['titre_maj'];
	$titre_ecrire = $titre_ecrire_court.' : '.$post['bt_title'];
} else {
	$post = '';
	$titre_ecrire_court = $GLOBALS['lang']['titre_ecrire'];
	$titre_ecrire = $titre_ecrire_court;
}

// DEBUT PAGE
afficher_html_head($titre_ecrire);
echo '<div id="top">'."\n";
afficher_msg();
afficher_topnav(basename($_SERVER['PHP_SELF']), $titre_ecrire_court);
echo '</div>'."\n";

echo '<div id="axe">'."\n";

// SUBNAV
if ($post != '') {
	echo '<div id="subnav">'."\n";
		echo '<div class="nombre-elem">';
		echo '<a href="'.$post['bt_link'].'">'.$GLOBALS['lang']['lien_article'].'</a> &nbsp; – &nbsp; ';
		echo '<a href="commentaires.php?post_id='.$article_id.'">'.ucfirst(nombre_objets($post['bt_nb_comments'], 'commentaire')).'</a>';
		echo '</div>'."\n";
	echo '</div>'."\n";
}

echo '<div id="page">'."\n";

// EDIT
if ($post != '') {
	apercu($post);
}
afficher_form_billet($post, $erreurs_form);

echo "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
echo '<script type="text/javascript">';
echo js_alert_before_quit(0);
echo js_red_button_event(0);
echo '</script>';


footer('', $begin);

