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
 * menu haut panneau admin
 */
function tpl_show_topnav($titre)
{
    $tab = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME);
    if (strlen($titre) == 0) {
        $titre = BLOGOTEXT_NAME;
    }
    $html = '<div id="nav">';
    $html .=  '<ul>';
    $html .=  '<li><a href="index.php" id="lien-index"'.(($tab == 'index.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['label_resume'].'</a></li>';
    $html .=  '<li><a href="articles.php" id="lien-liste"'.(($tab == 'articles.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['mesarticles'].'</a></li>';
    $html .=  '<li><a href="ecrire.php" id="lien-nouveau"'.(($tab == 'ecrire.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['nouveau'].'</a></li>';
    $html .=  '<li><a href="commentaires.php" id="lien-lscom"'.(($tab == 'commentaires.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['titre_commentaires'].'</a></li>';
    $html .=  '<li><a href="fichiers.php" id="lien-fichiers"'.(($tab == 'fichiers.php') ? ' class="current"' : '').'>'.ucfirst($GLOBALS['lang']['label_fichiers']).'</a></li>';
    if ($GLOBALS['afficher_liens']) {
        $html .=  '<li><a href="links.php" id="lien-links"'.(($tab == 'links.php') ? ' class="current"' : '').'>'.ucfirst($GLOBALS['lang']['label_links']).'</a></li>';
    }
    if ($GLOBALS['afficher_rss']) {
        $html .=  '<li><a href="feed.php" id="lien-rss"'.(($tab == 'feed.php') ? ' class="current"' : '').'>'.ucfirst($GLOBALS['lang']['label_feeds']).'</a></li>';
    }
    $html .=  '</ul>';
    $html .=  '</div>';

    $html .=  '<h1>'.$titre.'</h1>';

    $html .=  '<div id="nav-acc">';
    $html .=  '<ul>';
    $html .=  '<li><a href="preferences.php" id="lien-preferences">'.$GLOBALS['lang']['preferences'].'</a></li>';
    $html .=  '<li><a href="addons.php" id="lien-modules">'.ucfirst($GLOBALS['lang']['label_modules']).'</a></li>';
    $html .=  '<li><a href="'.URL_ROOT.'" id="lien-site">'.$GLOBALS['lang']['blog_link'].'</a></li>';
    $html .=  '<li><a href="logout.php" id="lien-deconnexion">'.$GLOBALS['lang']['deconnexion'].'</a></li>';
    $html .=  '</ul>';
    $html .=  '</div>';
    return $html;
}

/**
 *
 */
function tpl_show_msg()
{
    // Success message
    $msg = (string)filter_input(INPUT_GET, 'msg');
    if ($msg) {
        if (array_key_exists(htmlspecialchars($msg), $GLOBALS['lang'])) {
            $nbnew = (string)filter_input(INPUT_GET, 'nbnew');
            $suffix = ($nbnew) ? htmlspecialchars($nbnew).' '.$GLOBALS['lang']['rss_nouveau_flux'] : ''; // nb new RSS
            confirmation($GLOBALS['lang'][$msg].$suffix);
        }
    }

    // Error message
    $errmsg = (string)filter_input(INPUT_GET, 'errmsg');
    if ($errmsg) {
        if (array_key_exists($errmsg, $GLOBALS['lang'])) {
            no_confirmation($GLOBALS['lang'][$errmsg]);
        }
    }
}

/**
 *
 */
function tpl_show_preview($article)
{
    if (isset($article)) {
        $apercu = '<h2>'.$article['bt_title'].'</h2>';
        if (empty($article['bt_abstract'])) {
            $article['bt_abstract'] = mb_substr(strip_tags($article['bt_content']), 0, 249).'…';
        }
        $apercu .= '<div><strong>'.$article['bt_abstract'].'</strong></div>';
        $apercu .= '<div>'.rel2abs_admin($article['bt_content']).'</div>';
        echo '<div id="apercu">'.$apercu.'</div>';
    }
}

/**
 *
 */
function tpl_get_html_head($title)
{
    $html = '<!DOCTYPE html>';
    $html .= '<html>';
    $html .= '<head>';
    $html .= '<meta charset="UTF-8" />';
    $html .= '<link type="text/css" rel="stylesheet" href="style/style.css.php?v='.BLOGOTEXT_VERSION.'" />';
    $html .= '<meta name="viewport" content="initial-scale=1.0, user-scalable=yes" />';
    $html .= '<title>'.$title.' | '.BLOGOTEXT_NAME.'</title>';
    $html .= '</head>';
    $html .= '<body id="body">';
    return $html;
}

/**
 *
 */
function tpl_get_footer($begin_time = '')
{
    $msg = '';
    if ($begin_time != '') {
        $dt = round((microtime(true) - $begin_time), 6);
        $msg = ' - '.$GLOBALS['lang']['rendered'].' '.$dt.' s '.$GLOBALS['lang']['using'].' '.DBMS;
    }

    $html = '</div>';
    $html .= '</div>';
    $html .= '<p id="footer"><a href="'.BLOGOTEXT_SITE.'">'.BLOGOTEXT_NAME.' '.BLOGOTEXT_VERSION.'</a>'.$msg.'</p>';
    $html .= '</body>';
    $html .= '</html>';
    return $html;
}

/**
 *
 */
function confirmation($message)
{
    echo '<div class="confirmation">'.$message.'</div>';
}

/**
 *
 */
function no_confirmation($message)
{
    echo '<div class="no_confirmation">'.$message.'</div>';
}

/**
 *
 */
function info($message)
{
    return '<p class="info">'.$message.'</p>';
}

/**
 *
 */
function question($message)
{
      echo '<p id="question">'.$message.'</p>';
}

/**
 *
 */
function php_lang_to_js($a)
{
    $frontend_str = array();
    $frontend_str['maxFilesSize'] = min(return_bytes(ini_get('upload_max_filesize')), return_bytes(ini_get('post_max_size')));
    $frontend_str['rssJsAlertNewLink'] = $GLOBALS['lang']['rss_jsalert_new_link'];
    $frontend_str['rssJsAlertNewLinkFolder'] = $GLOBALS['lang']['rss_jsalert_new_link_folder'];
    $frontend_str['confirmFeedClean'] = $GLOBALS['lang']['confirm_feed_clean'];
    $frontend_str['confirmCommentSuppr'] = $GLOBALS['lang']['confirm_comment_suppr'];
    $frontend_str['activer'] = $GLOBALS['lang']['activer'];
    $frontend_str['desactiver'] = $GLOBALS['lang']['desactiver'];
    $frontend_str['errorPhpAjax'] = $GLOBALS['lang']['error_phpajax'];
    $frontend_str['errorCommentSuppr'] = $GLOBALS['lang']['error_comment_suppr'];
    $frontend_str['errorCommentValid'] = $GLOBALS['lang']['error_comment_valid'];
    $frontend_str['questionQuitPage'] = $GLOBALS['lang']['question_quit_page'];
    $frontend_str['questionCleanRss'] = $GLOBALS['lang']['question_clean_rss'];
    $frontend_str['questionSupprComment'] = $GLOBALS['lang']['question_suppr_comment'];
    $frontend_str['questionSupprArticle'] = $GLOBALS['lang']['question_suppr_article'];
    $frontend_str['questionSupprFichier'] = $GLOBALS['lang']['question_suppr_fichier'];

    $sc = 'var BTlang = '.json_encode($frontend_str).';';

    if ($a == 1) {
        $sc = '<script>'.$sc.'</script>';
    }
    return $sc;
}

/**
 *
 */
function rel2abs_admin($article)
{
    // if relative URI in path, make absolute paths (since /admin/ panel is 1 lv deeper) for href/src.
    $article = preg_replace('#(src|href)=\"(?!(/|[a-z]+://))#i', '$1="../', $article);
    return $article;
}
