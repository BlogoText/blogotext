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


$vars = array(
    'com_activer' => (filter_input(INPUT_POST, 'com_activer') !== null),
    'com_supprimer' => (filter_input(INPUT_POST, 'com_supprimer') !== null),
    '_verif_envoi' => (filter_input(INPUT_POST, '_verif_envoi') !== null),
    'comment_article_id' => (string)filter_input(INPUT_POST, 'comment_article_id'),
    'post_id' => (string)filter_input(INPUT_GET, 'post_id'),
    'filtre' => (string)filter_input(INPUT_GET, 'filtre'),
    'q' => (string)filter_input(INPUT_GET, 'q'),
);


/**
 * process
 */

$postTitle = '';
$errorsForm = array();
if ($vars['_verif_envoi']) {
    if ($vars['com_supprimer'] || $vars['com_activer']) {
        $commentAction = (($vars['com_supprimer']) ? $vars['com_supprimer'] : $vars['com_activer']);
        $errorsForm = valider_form_commentaire_ajax((int)$commentAction);
        if ($errorsForm) {
            die(implode("\n", $errorsForm));
        }
        traiter_form_commentaire($commentAction, 'admin');
    } else {
        $comment = init_post_comment($vars['comment_article_id'], 'admin');
        $errorsForm = valider_form_commentaire($comment, 'admin');
        if (!$errorsForm) {
            traiter_form_commentaire($comment, 'admin');
        }
    }
}

// if article ID is given in query string
if (preg_match('#\d{14}#', $vars['post_id'])) {
    $paramMakeup['menu_theme'] = 'for_article';
    $sql = '
        SELECT c.*, a.bt_title
          FROM commentaires AS c, articles AS a
         WHERE c.bt_article_id = ?
               AND c.bt_article_id = a.bt_id
         ORDER BY c.bt_id';
    $comments = liste_elements($sql, array($vars['post_id']), 'commentaires');
    $postTitle = ($comments) ? $comments[0]['bt_title'] : get_entry($GLOBALS['db_handle'], 'articles', 'bt_title', $vars['post_id'], 'return');
    $paramMakeup['show_links'] = 0;
} else {
    // else, no ID
    $paramMakeup['menu_theme'] = 'for_comms';
    if ($vars['filtre']) {
        // for "authors" the requests is "auteur.$search": here we split the type of search and what we search.
        $type = substr($vars['filtre'], 0, -strlen(strstr($vars['filtre'], '.')));
        $search = htmlspecialchars(ltrim(strstr($vars['filtre'], '.'), '.'));
        if (preg_match('#^\d{6}(\d{1,8})?$#', $vars['filtre'])) {
            $sql = '
                SELECT c.*, a.bt_title
                  FROM commentaires c
                  LEFT JOIN articles a
                         ON a.bt_id = c.bt_article_id
                 WHERE c.bt_id LIKE ?
                 ORDER BY c.bt_id DESC';
            $comments = liste_elements($sql, array($vars['filtre'].'%'), 'commentaires');
        } elseif ($vars['filtre'] == 'draft') {
            $sql = '
                SELECT c.*, a.bt_title
                  FROM commentaires c
                  LEFT JOIN articles a
                         ON a.bt_id = c.bt_article_id
                 WHERE c.bt_statut = 0
                 ORDER BY c.bt_id DESC';
            $comments = liste_elements($sql, array(), 'commentaires');
        } elseif ($vars['filtre'] == 'pub') {
            $sql = '
                SELECT c.*, a.bt_title
                  FROM commentaires c
                  LEFT JOIN articles a
                         ON a.bt_id = c.bt_article_id
                 WHERE c.bt_statut = 1
                 ORDER BY c.bt_id DESC';
            $comments = liste_elements($sql, array(), 'commentaires');
        } elseif ($type == 'auteur' && $search != '') {
            $sql = '
                SELECT c.*, a.bt_title
                  FROM commentaires c
                  LEFT JOIN articles a
                         ON a.bt_id = c.bt_article_id
                 WHERE c.bt_author = ?
                 ORDER BY c.bt_id DESC';
            $comments = liste_elements($sql, array($search), 'commentaires');
        } else {
            $sql = '
                SELECT c.*, a.bt_title
                  FROM commentaires c
                  LEFT JOIN articles a
                         ON a.bt_id = c.bt_article_id
                 ORDER BY c.bt_id DESC
                 LIMIT '.$GLOBALS['max_comm_admin'];
            $comments = liste_elements($sql, array(), 'commentaires');
        }
    } elseif ($vars['q']) {
        $arr = parse_search($vars['q']);
        $sqlWhere = implode(array_fill(0, count($arr), 'c.bt_content LIKE ?'), 'AND');
        $sql = '
            SELECT c.*, a.bt_title
              FROM commentaires c
              LEFT JOIN articles a
                     ON a.bt_id = c.bt_article_id
             WHERE '.$sqlWhere.'
             ORDER BY c.bt_id DESC';
        $comments = liste_elements($sql, $arr, 'commentaires');
    } else {
        // No filter, so list'em all
        $sql = '
            SELECT c.*, a.bt_title
              FROM commentaires c
              LEFT JOIN articles a
                     ON a.bt_id = c.bt_article_id
             ORDER BY c.bt_id DESC
             LIMIT '.$GLOBALS['max_comm_admin'];
        $comments = liste_elements($sql, array(), 'commentaires');
    }
    $numberOfComments = liste_elements_count('SELECT count(*) AS nbr FROM commentaires', array());
    $paramMakeup['show_links'] = 1;
}

function display_comment($comment, $withLink)
{
    afficher_form_commentaire($comment['bt_article_id'], 'admin', '', $comment);
    echo '<div class="commentbloc'.((!$comment['bt_statut']) ? ' privatebloc' : '').'" id="'.article_anchor($comment['bt_id']).'">';
    echo '<div class="comm-side-icon">';
        echo '<div class="comm-title">';
        echo '<img class="author-icon" width="48" height="48" src="'.URL_ROOT.'favatar.php?q='.md5(((!empty($comment['bt_email'])) ? $comment['bt_email'] : $comment['bt_author'] )).'"/>';
        echo '<span class="date">'.date_formate($comment['bt_id']).'<span>'.heure_formate($comment['bt_id']).'</span></span>' ;

        echo '<span class="reply" onclick="reply(\'[b]@['.str_replace('\'', '\\\'', $comment['bt_author']).'|#'.article_anchor($comment['bt_id']).'] :[/b] \'); ">Reply</span> ';
        echo (!empty($comment['bt_webpage'])) ? '<span class="webpage"><a href="'.$comment['bt_webpage'].'" title="'.$comment['bt_webpage'].'">'.$comment['bt_webpage'].'</a></span>' : '';
        echo (!empty($comment['bt_email'])) ? '<span class="email"><a href="mailto:'.$comment['bt_email'].'" title="'.$comment['bt_email'].'">'.$comment['bt_email'].'</a></span>' : '';
        echo '</div>';
    echo '</div>';

    echo '<div class="comm-main-frame">';

    echo '<div class="comm-header">';

    echo '<div class="comm-title">';
    echo '<span class="author"><a href="?filtre=auteur.'.$comment['bt_author'].'" title="'.$GLOBALS['lang']['label_all_comm_by_author'].'">'.$comment['bt_author'].'</a> :</span>';
    echo '</div>';

    echo ($withLink == 1 && !empty($comment['bt_title'])) ? '<span class="link-article"> '.$GLOBALS['lang']['sur'].' <a href="'.basename($_SERVER['SCRIPT_NAME']).'?post_id='.$comment['bt_article_id'].'">'.$comment['bt_title'].'</a></span>' : '';

    echo '<div class="comm-options">';
    echo '<ul>';
    echo '<li class="cl-edit" onclick="unfold(this);">'.$GLOBALS['lang']['editer'].'</li>';
    echo '<li class="cl-activ" onclick="activate_comm(this);" data-comm-id="'.$comment['ID'].'" data-comm-btid="'.$comment['bt_id'].'" data-comm-art-id="'.$comment['bt_article_id'].'">'.$GLOBALS['lang'][((!$comment['bt_statut']) ? '' : 'des').'activer'].'</li>';
    echo '<li class="cl-suppr" onclick="suppr_comm(this);" data-comm-id="'.$comment['ID'].'" data-comm-art-id="'.$comment['bt_article_id'].'">'.$GLOBALS['lang']['supprimer'].'</li>';
    echo '</ul>';
    echo '</div>';

    echo '</div>';

    echo '<div class="comm-content">';
    echo $comment['bt_content'];
    echo '</div>';
    echo $GLOBALS['form_commentaire'];

    echo '</div>';
    echo '</div>';
}


/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['titre_commentaires']. (($postTitle) ?' | '.$postTitle : ''));

echo '<div id="header">';
    echo '<div id="top">';
        tpl_show_msg();
        echo moteur_recherche();
        echo tpl_show_topnav($GLOBALS['lang']['titre_commentaires']);
    echo '</div>';
echo '</div>';

echo '<div id="axe">';

// Subnav
echo '<div id="subnav">';
afficher_form_filtre('commentaires', htmlspecialchars($vars['filtre']));
echo '<div class="nombre-elem">';
if ($paramMakeup['menu_theme'] == 'for_article') {
    $decodedId = decode_id($vars['post_id']);
    $postLink = URL_ROOT.'?d='.$decodedId['annee'].'/'.$decodedId['mois'].'/'.$decodedId['jour'].'/'.$decodedId['heure'].'/'.$decodedId['minutes'].'/'.$decodedId['secondes'].'-'.titre_url($postTitle);
    echo '<ul>';
    echo '<li><a href="ecrire.php?post_id='.$vars['post_id'].'">'.$GLOBALS['lang']['ecrire'].$postTitle.'</a></li>';
    echo '<li><a href="'.$postLink.'">'.$GLOBALS['lang']['post_link'].'</a></li>';
    echo '</ul>';
    echo '– &nbsp; '.ucfirst(nombre_objets(count($comments), 'commentaire'));
} elseif ($paramMakeup['menu_theme'] == 'for_comms') {
    echo ucfirst(nombre_objets(count($comments), 'commentaire')).' '.$GLOBALS['lang']['sur'].' '.$numberOfComments;
}
echo '</div>';
echo '</div>';

echo '<div id="page">';

// Comments
if ($comments) {
    echo '<div id="liste-commentaires">';
    $token = new_token();
    foreach ($comments as $comment) {
        $comment['comm-token'] = $token;
        display_comment($comment, $paramMakeup['show_links']);
    }
    echo '</div>';
} else {
    echo info($GLOBALS['lang']['note_no_commentaire']);
}

if ($paramMakeup['menu_theme'] == 'for_article') {
    echo '<div id="post-nv-commentaire">';
    afficher_form_commentaire($vars['post_id'], 'admin', $errorsForm, '');
    echo '<h2 class="poster-comment">'.$GLOBALS['lang']['comment_ajout'].'</h2>';
    echo $GLOBALS['form_commentaire'];
    echo '</div>';
}

echo '<script src="style/javascript.js"></script>';
echo '<script>';
    echo php_lang_to_js(0);
    echo 'var csrf_token = "'.new_token().'";';
echo '</script>';

echo tpl_get_footer($begin);
