<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

$begin = microtime(TRUE);
$GLOBALS['BT_ROOT_PATH'] = '../';


require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();

$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);


// RECUP MAJ
$article_id='';
$article_title='';



// TRAITEMENT
$erreurs_form = array();
if (isset($_POST['_verif_envoi'])) {
	$comment = init_post_comment($_POST['comment_article_id'], 'admin');
	$erreurs_form = valider_form_commentaire($comment, 'admin');
	if (empty($erreurs_form)) {
		traiter_form_commentaire($comment, 'admin');
	}
}


$tableau = array();
// if article ID is given in query string

if ( isset($_GET['post_id']) and preg_match('#\d{14}#', $_GET['post_id']) )  {
	$param_makeup['menu_theme'] = 'for_article';
	$article_id = $_GET['post_id'];

	$article_title = get_entry($GLOBALS['db_handle'], 'articles', 'bt_title', $article_id, 'return');
	$query = "SELECT * FROM commentaires WHERE bt_article_id=? ORDER BY bt_id";

	$commentaires = liste_elements($query, array($article_id), 'commentaires');

	$param_makeup['show_links'] = '0';

}
// else, no ID 
else {
	$param_makeup['menu_theme'] = 'for_comms';
	if ( !empty($_GET['filtre']) ) {
		// for "authors" the requests is "auteur.$search" : here we split the type of search and what we search.
		$type = substr($_GET['filtre'], 0, -strlen(strstr($_GET['filtre'], '.')));
		$search = htmlspecialchars(ltrim(strstr($_GET['filtre'], '.'), '.'));
		// filter for date
		if (preg_match('#^\d{6}(\d{1,8})?$#', ($_GET['filtre'])) ) {
			$query = "SELECT c.*, a.bt_title FROM commentaires c LEFT JOIN articles a ON a.bt_id = c.bt_article_id WHERE c.bt_id LIKE ? ORDER BY c.bt_id DESC";
			$commentaires = liste_elements($query, array($_GET['filtre'].'%'), 'commentaires');
		}
		// filter for statut
		elseif ($_GET['filtre'] == 'draft') {
			$query = "SELECT c.*, a.bt_title FROM commentaires c LEFT JOIN articles a ON a.bt_id=c.bt_article_id WHERE c.bt_statut=0 ORDER BY c.bt_id DESC";
			$commentaires = liste_elements($query, array(), 'commentaires');
		}
		elseif ($_GET['filtre'] == 'pub') {
			$query = "SELECT c.*, a.bt_title FROM commentaires c LEFT JOIN articles a ON a.bt_id=c.bt_article_id WHERE c.bt_statut=1 ORDER BY c.bt_id DESC";
			$commentaires = liste_elements($query, array(), 'commentaires');
		}
		// filter for author
		elseif ($type == 'auteur' and $search != '') {
			$query = "SELECT c.*, a.bt_title FROM commentaires c LEFT JOIN articles a ON a.bt_id=c.bt_article_id WHERE c.bt_author=? ORDER BY c.bt_id DESC";
			$commentaires = liste_elements($query, array($search), 'commentaires');
		}
		// no filter
		else {
			$query = "SELECT c.*, a.bt_title FROM commentaires c LEFT JOIN articles a ON a.bt_id=c.bt_article_id ORDER BY c.bt_id DESC LIMIT ".$GLOBALS['max_comm_admin'];
			$commentaires = liste_elements($query, array(), 'commentaires');
		}
	}
	elseif (!empty($_GET['q'])) {
			$query = "SELECT c.*, a.bt_title FROM commentaires c LEFT JOIN articles a ON a.bt_id=c.bt_article_id WHERE c.bt_content LIKE ? ORDER BY c.bt_id DESC";
			$commentaires = liste_elements($query, array('%'.htmlspecialchars($_GET['q']).'%'), 'commentaires');
	}
	else { // no filter, so list'em all
			$query = "SELECT c.*, a.bt_title FROM commentaires c LEFT JOIN articles a ON a.bt_id=c.bt_article_id ORDER BY c.bt_id DESC LIMIT ".$GLOBALS['max_comm_admin'];
//			die($query);
			$commentaires = liste_elements($query, array(), 'commentaires');
	}
	$nb_total_comms = liste_elements_count("SELECT count(*) AS nbr FROM commentaires", array());
	$param_makeup['show_links'] = '1';
}

function afficher_commentaire($comment, $with_link) {
	afficher_form_commentaire($comment['bt_article_id'], 'admin', '', $comment);
	echo '<div class="commentbloc'.(!$comment['bt_statut'] ? ' privatebloc' : '').'" id="'.article_anchor($comment['bt_id']).'">'."\n";
	if ($comment['bt_statut'] == '0') {
		echo '<img class="img_inv_flag" src="style/deny.png" title="'.$GLOBALS['lang']['comment_is_invisible'].'" alt="icon"/>';
	}
	echo '<span onclick="reply(\'[b]@['.str_replace('\'', '\\\'', $comment['bt_author']).'|#'.article_anchor($comment['bt_id']).'] :[/b] \'); ">@</span> ';
	echo '<h3 class="titre-commentaire">'.$comment['auteur_lien'].'</h3>'."\n";
	echo '<p class="email"><a href="mailto:'.$comment['bt_email'].'">'.$comment['bt_email'].'</a></p>'."\n";
	echo $comment['bt_content'];
	echo '<p class="p-edit-button">'."\n";
	echo $GLOBALS['lang']['le'].' '.date_formate($comment['bt_id']).', '.heure_formate($comment['bt_id']);
	if ($with_link == 1 and !empty($comment['bt_title'])) {
		echo ' '.$GLOBALS['lang']['sur'].' <a href="'.$_SERVER['PHP_SELF'].'?post_id='.$comment['bt_article_id'].'">'.$comment['bt_title'].'</a>';
	}
	echo "\t".'<button class="comm-link cl-suppr" type="button" onclick="ask_suppr(this);" title="'.$GLOBALS['lang']['supprimer'].'"></button>'."\n";
	echo "\t".'<button class="comm-link cl-edit" type="button" onclick="unfold(this);" title="'.$GLOBALS['lang']['editer'].'"></button> ';
	echo '</p>'."\n";
	echo $GLOBALS['form_commentaire'];
	echo '</div>'."\n\n";
}

// DEBUT PAGE
$msgg = $GLOBALS['lang']['titre_commentaires']. ((!empty($article_title)) ?' | '.$article_title : '');
afficher_top($msgg);

echo '<div id="top">'."\n";
afficher_msg($GLOBALS['lang']['titre_commentaires']);
echo moteur_recherche($GLOBALS['lang']['search_in_comments']);
afficher_menu(pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME));
echo '</div>'."\n";

echo '<div id="axe">'."\n";

// SUBNAV
echo '<div id="subnav">'."\n";
	// Affichage formulaire filtrage commentaires
	if (isset($_GET['filtre'])) {
		afficher_form_filtre('commentaires', htmlspecialchars($_GET['filtre']));
	} else {
		afficher_form_filtre('commentaires', '');
	}
echo '</div>'."\n";

echo erreurs($erreurs_form);

echo '<div id="page">'."\n";

echo '<p class="nombre-elem">'."\n";
if ($param_makeup['menu_theme'] == 'for_article') {
	echo '<a href="ecrire.php?post_id='.$article_id.'">'.$GLOBALS['lang']['ecrire'].$article_title.'</a> &nbsp; – &nbsp; '.ucfirst(nombre_commentaires(count($commentaires)));
} elseif ($param_makeup['menu_theme'] == 'for_comms') {
	echo ucfirst(nombre_commentaires(count($commentaires))).' '.$GLOBALS['lang']['sur'].' '.$nb_total_comms;
}
echo '</p>'."\n";


// COMMENTAIRES
if (count($commentaires) > 0) {
	$token = new_token();
	foreach ($commentaires as $content) {
		$content['comm-token'] = $token;
		afficher_commentaire($content, $param_makeup['show_links']);
	}
} else {
	echo info($GLOBALS['lang']['note_no_comment']);
}

if ($param_makeup['menu_theme'] == 'for_article') {
	afficher_form_commentaire($article_id, 'admin', $erreurs_form);
	echo '<h2 class="poster-comment">'.$GLOBALS['lang']['comment_ajout'].'</h2>'."\n";
	echo $GLOBALS['form_commentaire'];
}
echo '<script type="text/javascript">';
echo js_comm_question_suppr(0);
echo '</script>';

footer('', $begin);

