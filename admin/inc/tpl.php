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

/// menu haut panneau admin /////////
function tpl_show_topnav($titre)
{
    $tab = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME);
    if (strlen($titre) == 0) {
        $titre = BLOGOTEXT_NAME;
    }
    $html = '';
    $html .= '<div id="nav">'."\n";
    $html .=  "\t".'<ul>'."\n";
    $html .=  "\t\t".'<li><a href="index.php" id="lien-index"'.(($tab == 'index.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['label_resume'].'</a></li>'."\n";
    $html .=  "\t\t".'<li><a href="articles.php" id="lien-liste"'.(($tab == 'articles.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['mesarticles'].'</a></li>'."\n";
    $html .=  "\t\t".'<li><a href="ecrire.php" id="lien-nouveau"'.(($tab == 'ecrire.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['nouveau'].'</a></li>'."\n";
    $html .=  "\t\t".'<li><a href="commentaires.php" id="lien-lscom"'.(($tab == 'commentaires.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['titre_commentaires'].'</a></li>'."\n";
    $html .=  "\t\t".'<li><a href="fichiers.php" id="lien-fichiers"'.(($tab == 'fichiers.php') ? ' class="current"' : '').'>'.ucfirst($GLOBALS['lang']['label_fichiers']).'</a></li>'."\n";
    if ($GLOBALS['onglet_liens']) {
        $html .=  "\t\t".'<li><a href="links.php" id="lien-links"'.(($tab == 'links.php') ? ' class="current"' : '').'>'.ucfirst($GLOBALS['lang']['label_links']).'</a></li>'."\n";
    }
    if ($GLOBALS['onglet_rss']) {
        $html .=  "\t\t".'<li><a href="feed.php" id="lien-rss"'.(($tab == 'feed.php') ? ' class="current"' : '').'>'.ucfirst($GLOBALS['lang']['label_feeds']).'</a></li>'."\n";
    }
    $html .=  "\t".'</ul>'."\n";
    $html .=  '</div>'."\n";

    $html .=  '<h1>'.$titre.'</h1>'."\n";

    $html .=  '<div id="nav-acc">'."\n";
    $html .=  "\t".'<ul>'."\n";
    $html .=  "\t\t".'<li><a href="preferences.php" id="lien-preferences">'.$GLOBALS['lang']['preferences'].'</a></li>'."\n";
    $html .=  "\t\t".'<li><a href="addons.php" id="lien-modules">'.ucfirst($GLOBALS['lang']['label_modules']).'</a></li>'."\n";
    $html .=  "\t\t".'<li><a href="'.$GLOBALS['racine'].'" id="lien-site">'.$GLOBALS['lang']['blog_link'].'</a></li>'."\n";
    $html .=  "\t\t".'<li><a href="logout.php" id="lien-deconnexion">'.$GLOBALS['lang']['deconnexion'].'</a></li>'."\n";
    $html .=  "\t".'</ul>'."\n";
    $html .=  '</div>'."\n";
    echo $html;
}


function tpl_show_msg()
{
    // message vert
    if (isset($_GET['msg'])) {
        if (array_key_exists(htmlspecialchars($_GET['msg']), $GLOBALS['lang'])) {
            $suffix = (isset($_GET['nbnew'])) ? htmlspecialchars($_GET['nbnew']).' '.$GLOBALS['lang']['rss_nouveau_flux'] : ''; // nb new RSS
            confirmation($GLOBALS['lang'][$_GET['msg']].$suffix);
        }
    }
    // message rouge
    if (isset($_GET['errmsg'])) {
        if (array_key_exists($_GET['errmsg'], $GLOBALS['lang'])) {
            no_confirmation($GLOBALS['lang'][$_GET['errmsg']]);
        }
    }
}

function tpl_show_preview($article)
{
    if (isset($article)) {
        $apercu = '<h2>'.$article['bt_title'].'</h2>'."\n";
        if (empty($article['bt_abstract'])) {
            $article['bt_abstract'] = mb_substr(strip_tags($article['bt_content']), 0, 249).'…';
        }
        $apercu .= '<div><strong>'.$article['bt_abstract'].'</strong></div>'."\n";
        $apercu .= '<div>'.rel2abs_admin($article['bt_content']).'</div>'."\n";
        echo '<div id="apercu">'."\n".$apercu.'</div>'."\n\n";
    }
}
