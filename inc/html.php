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
function label($for, $txt)
{
    return '<label for="'.$for.'">'.$txt.'</label>'."\n";
}

/**
 *
 */
function erreurs($erreurs)
{
    $html = '';
    if ($erreurs) {
        $html .= '<div id="erreurs">'.'<strong>'.$GLOBALS['lang']['erreurs'].'</strong> :' ;
        $html .= '<ul><li>';
        $html .= implode('</li><li>', $erreurs);
        $html .= '</li></ul></div>'."\n";
    }
    return $html;
}

/**
 *
 */
function erreur($message)
{
      echo '<p class="erreurs">'.$message.'</p>'."\n";
}

/**
 *
 */
function moteur_recherche()
{
    $requete='';
    if (isset($_GET['q'])) {
        $requete = htmlspecialchars(stripslashes($_GET['q']));
    }
    $return = '<form action="?" method="get" id="search">'."\n";
    $return .= '<input id="q" name="q" type="search" size="20" value="'.$requete.'" placeholder="'.$GLOBALS['lang']['placeholder_search'].'" accesskey="f" />'."\n";
    $return .= '<button id="input-rechercher" type="submit">'.$GLOBALS['lang']['rechercher'].'</button>'."\n";
    if (isset($_GET['mode'])) {
        $return .= '<input id="mode" name="mode" type="hidden" value="'.htmlspecialchars(stripslashes($_GET['mode'])).'"/>'."\n";
    }
    $return .= '</form>'."\n\n";
    return $return;
}

/**
 *
 */
function encart_commentaires()
{
    $query = '
        SELECT a.bt_title, c.bt_author, c.bt_id, c.bt_article_id, c.bt_content
          FROM commentaires c
               LEFT JOIN articles a
                 ON a.bt_id = c.bt_article_id
         WHERE c.bt_statut = 1
               AND a.bt_statut = 1
         ORDER BY c.bt_id DESC
         LIMIT 5';
    $tableau = liste_elements($query, array(), 'commentaires');
    if (isset($tableau)) {
        $tableau = html_addon_protect($tableau, 'encode');
        $listeLastComments = '<ul class="encart_lastcom">'."\n";
        foreach ($tableau as $i => $comment) {
            $comment['contenu_abbr'] = strip_tags($comment['bt_content']);
            // limits length of comment abbreviation and name
            if (strlen($comment['contenu_abbr']) >= 60) {
                $comment['contenu_abbr'] = mb_substr($comment['contenu_abbr'], 0, 59).'…';
            }
            if (strlen($comment['bt_author']) >= 30) {
                $comment['bt_author'] = mb_substr($comment['bt_author'], 0, 29).'…';
            }
            $listeLastComments .= '<li title="'.date_formate($comment['bt_id']).'"><strong>'.$comment['bt_author'].' : </strong><a href="'.$comment['bt_link'].'">'.$comment['contenu_abbr'].'</a>'.'</li>'."\n";
        }
        $listeLastComments .= '</ul>'."\n";
        return $listeLastComments;
    } else {
        return $GLOBALS['lang']['no_comments'];
    }
}

/**
 *
 */
function encart_categories($mode)
{
    if ($GLOBALS['activer_categories'] == '1') {
        $where = ($mode == 'links') ? 'links' : 'articles';
        $ampmode = ($mode == 'links') ? '&amp;mode=links' : '';

        $liste = list_all_tags($where, 1);

        // attach non-diacritic versions of tag, so that "é" does not pass after "z" and re-indexes
        foreach ($liste as $tag => $nb) {
            $liste[$tag] = array(diacritique(trim($tag)), $nb);
        }
        // sort tags according non-diacritics versions of tags
        $liste = array_reverse(tri_selon_sous_cle($liste, 0));
        $uliste = '<ul>'."\n";

        // create the <ul> with "tags (nb) "
        foreach ($liste as $tag => $nb) {
            if ($tag != '' and $nb[1] > 1) {
                $uliste .= "\t".'<li><a href="?tag='.urlencode(trim($tag)).$ampmode.'" rel="tag">'.ucfirst($tag).' ('.$nb[1].')</a><a href="rss.php?tag='.urlencode($tag).$ampmode.'" rel="alternate"></a></li>'."\n";
            }
        }
        $uliste .= '</ul>'."\n";

        $uliste = html_addon_protect($uliste, 'encode');
        return $uliste;
    }
}

/**
 *
 */
function lien_pagination()
{
    if (!isset($GLOBALS['param_pagination']) or isset($_GET['d']) or isset($_GET['liste']) or isset($_GET['id'])) {
        return '';
    } else {
        $nb_par_page = (int)$GLOBALS['param_pagination']['nb_par_page'];
    }

    $page_courante = (isset($_GET['p']) and is_numeric($_GET['p'])) ? (int)$_GET['p'] : 0;
    $qstring = remove_url_param('p');
    if (!empty($qstring)) {
        $qstring .= '&amp;';
    }

    $db_req = '';
    $db_params = array();
    if (isset($_GET['mode']) && $_GET['mode'] == 'links') {
        $db_req = 'SELECT count(ID) AS nbr FROM links WHERE bt_statut=1';
    } else {
        $db_req = 'SELECT count(ID) AS nbr FROM articles WHERE bt_date <= '.date('YmdHis').' and bt_statut=1';
    }
    if (isset($_GET['tag'])) {
        $db_req .= ' and ( bt_tags LIKE ? OR bt_tags LIKE ? OR bt_tags LIKE ? OR bt_tags LIKE ? )';
        $db_params = array( $_GET['tag'],$_GET['tag'].', %','%, '.$_GET['tag'].', %','%, '.$_GET['tag'] );
    }
    $nb = (int)liste_elements_count($db_req, $db_params);

    $lien_precede = '';
    $lien_suivant = '';
    // -1 because ?p=0 is the first
    $total_page = (int)ceil($nb / $nb_par_page) - 1;

    // page sup ?
    if ($page_courante < 0) {
        $lien_suivant = '<a href="?'.$qstring.'p=0" rel="next">'.$GLOBALS['lang']['label_suivant'].'</a>';
    } else if ($page_courante < $total_page) {
        $lien_suivant = '<a href="?'.$qstring.'p='.($page_courante+1).'" rel="next">'.$GLOBALS['lang']['label_suivant'].'</a>';
    }

    // page inf ?
    if ($page_courante > $total_page) {
        $lien_precede = '<a href="?'.$qstring.'p='.$total_page.'" rel="prev">'.$GLOBALS['lang']['label_precedent'].'</a>';
    } else if ($page_courante <= $total_page && $page_courante > 0) {
        $lien_precede = '<a href="?'.$qstring.'p='.($page_courante-1).'" rel="prev">'.$GLOBALS['lang']['label_precedent'].'</a>';
    }

    return '<p class="pagination">'.$lien_precede.$lien_suivant.'</p>';
}

/**
 *
 */
function liste_tags($billet, $html_link)
{
    $mode = ($billet['bt_type'] == 'article') ? '' : '&amp;mode=links';
    $liste = '';
    if (!empty($billet['bt_tags'])) {
        $tag_list = explode(', ', $billet['bt_tags']);
        // remove diacritics, so that "ééé" does not passe after "zzz" and re-indexes
        foreach ($tag_list as $i => $tag) {
            $tag_list[$i] = array('t' => trim($tag), 'tt' => diacritique(trim($tag)));
        }
        $tag_list = array_reverse(tri_selon_sous_cle($tag_list, 'tt'));

        foreach ($tag_list as $tag) {
            $tag = trim($tag['t']);
            if ($html_link == 1) {
                $liste .= '<a href="?tag='.urlencode($tag).$mode.'" rel="tag">'.$tag.'</a>';
            } else {
                $liste .= $tag.' ';
            }
        }
    }
    return $liste;
}
