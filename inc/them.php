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


/*
 * Vars used in them files, aimed to get
 * replaced with some specific data
 */
$GLOBALS['boucles'] = array(
    'posts' => 'BOUCLE_posts',
    'commentaires' => 'BOUCLE_commentaires',
);

$GLOBALS['balises'] = array(
    'version' => '{version}',
    'app_name' => '{app_name}',
    'style' => '{style}',
    'racine_du_site' => '{racine_du_site}',
    'rss' => '{rss}',
    'rss_comments' => '{rss_comments}',
    // content type
    'tpl_class' => '{tpl_class}',
    // Navigation
    'pagination' => '{pagination}',
    // Blog
    'blog_nom' => '{blog_nom}',
    'blog_description' => '{blog_description}',
    'blog_auteur' => '{blog_auteur}',
    'blog_email' => '{blog_email}',
    'blog_motscles' => '{keywords}',
    // Formulaires
    'form_recherche' => '{recherche}',
    'form_commentaire' => '{formulaire_commentaire}',
    // Encarts
    'comm_encart' => '{commentaires_encart}',
    'cat_encart' => '{categories_encart}',

    // Article
    'article_titre' => '{article_titre}',
    'article_titre_page' => '{article_titre_page}',
    'article_titre_echape' => '{article_titre_echape}',
    'article_chapo' => '{article_chapo}',
    'article_contenu' => '{article_contenu}',
    'article_heure' => '{article_heure}',
    'article_date' => '{article_date}',
    'article_date_iso' => '{article_date_iso}',
    'article_lien' => '{article_lien}',
    'article_tags' => '{article_tags}',
    'article_tags_plain' => '{article_tags_plain}',
    'nb_commentaires' => '{nombre_commentaires}',

    // Commentaire
    'commentaire_auteur' => '{commentaire_auteur}',
    'commentaire_auteur_lien' => '{commentaire_auteur_lien}',
    'commentaire_contenu' => '{commentaire_contenu}',
    'commentaire_heure' => '{commentaire_heure}',
    'commentaire_date' => '{commentaire_date}',
    'commentaire_date_iso' => '{commentaire_date_iso}',
    'commentaire_email' => '{commentaire_email}',
    'commentaire_webpage' => '{commentaire_webpage}',
    'commentaire_anchor' => '{commentaire_ancre}', // the id="" content
    'commentaire_lien' => '{commentaire_lien}',
    'commentaire_md5email' => '{commentaire_md5email}',

    // Liens
    'lien_titre' => '{lien_titre}',
    'lien_url' => '{lien_url}',
    'lien_date' => '{lien_date}',
    'lien_date_iso' => '{lien_date_iso}',
    'lien_heure' => '{lien_heure}',
    'lien_description' => '{lien_description}',
    'lien_permalink' => '{lien_permalink}',
    'lien_id' => '{lien_id}',
    'lien_tags' => '{lien_tags}',
);

/**
 * cleanup a string to use it in <meta />
 *
 * @params string $string,
 * @params null|int $limit, the limit size. null = no limit
 * @params null|string $addon,
 *                      null, do nothing
 *                      'encode', encode '{addon_' to '&#123;addon_'
 *                      'remove', remove the addon tag
 * @return string
 */
function html_clean_meta($string, $limit = 249, $addon = null)
{
    $string = str_replace(array("\r", "\n"), ' ', $string);
    $string = strip_tags($string);
    if ($limit !== null && mb_strlen($string) > $limit) {
        $string = mb_substr($string, 0, $limit).'…';
    }
    $string = trim($string);
    $string = htmlspecialchars($string, ENT_QUOTES);
    // proceed addon tag
    if ($addon !== null) {
        $string = html_addon_protect($string, $addon);
    }

    return $string;
}

/**
 * used to prevent addons tags in title, description, comments ...
 */
function html_addon_protect($string, $addon)
{
    if (is_array($string)) {
        foreach ($string as &$v) {
            $v = html_addon_protect($v, $addon);
        }
        return $string;
    }

    if ($addon !== null && strpos($string, '{addon_') !== false) {
        if ($addon == 'encode') {
            $string = str_replace('{addon_', '&#123;addon_', $string);
        } else if ($addon == 'remove') {
            $string = preg_replace('/\{addon_[a-zA-Z0-9-_]*\}/', '', $string);
        }
    }
    return $string;
}

/**
 *
 */
function conversions_theme($texte, $solo_art, $cnt_mode)
{
    $texte = str_replace($GLOBALS['balises']['version'], BLOGOTEXT_VERSION, $texte);
    $texte = str_replace($GLOBALS['balises']['app_name'], BLOGOTEXT_NAME, $texte);
    $texte = str_replace($GLOBALS['balises']['style'], $GLOBALS['theme_style'], $texte);
    $texte = str_replace($GLOBALS['balises']['racine_du_site'], URL_ROOT, $texte);
    $texte = str_replace($GLOBALS['balises']['blog_auteur'], $GLOBALS['auteur'], $texte);
    $texte = str_replace($GLOBALS['balises']['blog_email'], $GLOBALS['email'], $texte);
    $texte = str_replace($GLOBALS['balises']['blog_nom'], $GLOBALS['nom_du_site'], $texte);
    $texte = str_replace($GLOBALS['balises']['tpl_class'], $GLOBALS['tpl_class'], $texte);

    if ($cnt_mode == 'post' and !empty($solo_art)) {
        $clean_title = html_clean_meta($solo_art['bt_title'], 999, 'encode');
        $texte = str_replace($GLOBALS['balises']['article_titre_page'], $clean_title.' - ', $texte);
        $texte = str_replace($GLOBALS['balises']['article_titre'], $clean_title, $texte);
        $texte = str_replace($GLOBALS['balises']['article_titre_echape'], urlencode($solo_art['bt_title']), $texte);
        $texte = str_replace($GLOBALS['balises']['article_lien'], $solo_art['bt_link'], $texte);
        if ($solo_art['bt_type'] == 'article') {
            $texte = str_replace($GLOBALS['balises']['article_chapo'], html_clean_meta(((empty($solo_art['bt_abstract'])) ? $solo_art['bt_content'] : $solo_art['bt_abstract']), 249, 'encode'), $texte);
            $texte = str_replace($GLOBALS['balises']['blog_motscles'], html_addon_protect($solo_art['bt_keywords'], 'encode'), $texte);
        }
        if ($solo_art['bt_type'] == 'link' or $solo_art['bt_type'] == 'note') {
            $texte = str_replace($GLOBALS['balises']['article_chapo'], html_clean_meta($solo_art['bt_content'], 149, 'encode'), $texte);
            $texte = str_replace($GLOBALS['balises']['article_titre_page'], $clean_title.' - ', $texte);
        }
    }

    // si remplacé, ceci n'a pas d'effet.
    $texte = str_replace($GLOBALS['balises']['blog_description'], $GLOBALS['description'], $texte);
    $texte = str_replace($GLOBALS['balises']['article_titre_page'], '', $texte);
    $texte = str_replace($GLOBALS['balises']['blog_motscles'], html_addon_protect($GLOBALS['keywords'], 'encode'), $texte);
    $texte = str_replace($GLOBALS['balises']['article_titre_echape'], '', $texte);
    $texte = str_replace($GLOBALS['balises']['article_lien'], URL_ROOT, $texte);
    $texte = str_replace($GLOBALS['balises']['article_chapo'], $GLOBALS['description'], $texte);

    $texte = str_replace($GLOBALS['balises']['pagination'], lien_pagination(), $texte);

    if (strpos($texte, $GLOBALS['balises']['form_recherche']) !== false) {
        $texte = str_replace($GLOBALS['balises']['form_recherche'], moteur_recherche(''), $texte) ;
    }

    // Formulaires
    $texte = str_replace($GLOBALS['balises']['rss'], $GLOBALS['rss'], $texte);
    $texte = str_replace($GLOBALS['balises']['comm_encart'], encart_commentaires(), $texte);
    $texte = str_replace($GLOBALS['balises']['cat_encart'], encart_categories((isset($_GET['mode']))?$_GET['mode']:''), $texte);
    if (isset($GLOBALS['rss_comments'])) {
        $texte = str_replace($GLOBALS['balises']['rss_comments'], $GLOBALS['rss_comments'], $texte);
    }

    // addons
    $texte = conversion_theme_addons($texte);

    // revert addon tag protection : &#123;addon_ > {addon_
    // %26%23123%3Baddon_ is for using {addon_ in a tag (for URL of the tag)
    $texte = str_replace(array('&#123;addon_', '%26%23123%3Baddon_'), '{addon_', $texte);

    return $texte;
}

/**
 * Comments
 */
function conversions_theme_commentaire($texte, $commentaire)
{
    // encode {addon_
    $commentaire = html_addon_protect($commentaire, 'encode');

    $texte = str_replace($GLOBALS['balises']['commentaire_contenu'], $commentaire['bt_content'], $texte);
    $texte = str_replace($GLOBALS['balises']['commentaire_date'], date_formate($commentaire['bt_id']), $texte);
    $texte = str_replace($GLOBALS['balises']['commentaire_date_iso'], date_formate_iso($commentaire['bt_id']), $texte);
    $texte = str_replace($GLOBALS['balises']['commentaire_heure'], heure_formate($commentaire['bt_id']), $texte);
    $texte = str_replace($GLOBALS['balises']['commentaire_email'], $commentaire['bt_email'], $texte);
    $texte = str_replace($GLOBALS['balises']['commentaire_md5email'], md5($commentaire['bt_email']), $texte);
    $texte = str_replace($GLOBALS['balises']['commentaire_auteur_lien'], $commentaire['auteur_lien'], $texte);
    $texte = str_replace($GLOBALS['balises']['commentaire_auteur'], str_replace("'", "\\'", $commentaire['bt_author']), $texte);
    $texte = str_replace($GLOBALS['balises']['commentaire_webpage'], $commentaire['bt_webpage'], $texte);
    $texte = str_replace($GLOBALS['balises']['commentaire_anchor'], $commentaire['anchor'], $texte);
    $texte = str_replace($GLOBALS['balises']['commentaire_lien'], $commentaire['bt_link'], $texte);
    return $texte;
}

/**
 * Article
 */
function conversions_theme_article($texte, $billet)
{
    $texte = str_replace($GLOBALS['balises']['form_commentaire'], $GLOBALS['form_commentaire'], $texte);
    $texte = str_replace($GLOBALS['balises']['rss_comments'], 'rss.php?id='.$billet['bt_id'], $texte);
    $texte = str_replace($GLOBALS['balises']['article_titre'], html_clean_meta($billet['bt_title'], null, 'encode'), $texte);
    $texte = str_replace($GLOBALS['balises']['article_chapo'], html_clean_meta(((empty($billet['bt_abstract'])) ? $billet['bt_content'] : $billet['bt_abstract']), 249, 'encode'), $texte);
    $texte = str_replace($GLOBALS['balises']['article_contenu'], $billet['bt_content'], $texte);
    $texte = str_replace($GLOBALS['balises']['article_date'], date_formate($billet['bt_date']), $texte);
    $texte = str_replace($GLOBALS['balises']['article_date_iso'], date_formate_iso($billet['bt_date']), $texte);
    $texte = str_replace($GLOBALS['balises']['article_heure'], heure_formate($billet['bt_date']), $texte);
    // comments closed (globally or only for this article) and no comments => say « comments closed »
    if (($billet['bt_allow_comments'] == 0 or $GLOBALS['global_com_rule'] == 1 ) and $billet['bt_nb_comments'] == 0) {
        $texte = str_replace($GLOBALS['balises']['nb_commentaires'], $GLOBALS['lang']['note_comment_closed'], $texte);
    }
    // comments open OR ( comments closed AND comments exists ) => say « nb comments ».
    if (!($billet['bt_allow_comments'] == 0 or $GLOBALS['global_com_rule'] == 1 ) or $billet['bt_nb_comments'] != 0) {
        $texte = str_replace($GLOBALS['balises']['nb_commentaires'], nombre_objets($billet['bt_nb_comments'], 'commentaire'), $texte);
    }
    $texte = str_replace($GLOBALS['balises']['article_lien'], $billet['bt_link'], $texte);
    $billet['bt_tags'] = html_clean_meta($billet['bt_tags'], null, 'encode');
    $texte = str_replace($GLOBALS['balises']['article_tags'], liste_tags($billet, '1'), $texte);
    $texte = str_replace($GLOBALS['balises']['article_tags_plain'], liste_tags($billet, '0'), $texte);
    return $texte;
}

/**
 * Links
 */
function conversions_theme_lien($texte, $lien)
{
    // remove {addon_
    $clean_title = html_addon_protect($lien['bt_title'], 'encode');
    $texte = str_replace($GLOBALS['balises']['article_titre'], $clean_title, $texte);
    $texte = str_replace($GLOBALS['balises']['lien_titre'], $clean_title, $texte);
    $texte = str_replace($GLOBALS['balises']['lien_url'], $lien['bt_link'], $texte);
    $texte = str_replace($GLOBALS['balises']['lien_date'], date_formate($lien['bt_id']), $texte);
    $texte = str_replace($GLOBALS['balises']['lien_date_iso'], date_formate_iso($lien['bt_id']), $texte);
    $texte = str_replace($GLOBALS['balises']['lien_heure'], heure_formate($lien['bt_id']), $texte);
    $texte = str_replace($GLOBALS['balises']['lien_permalink'], $lien['bt_id'], $texte);
    $texte = str_replace($GLOBALS['balises']['lien_description'], $lien['bt_content'], $texte);
    $texte = str_replace($GLOBALS['balises']['lien_id'], $lien['ID'], $texte);
    $texte = str_replace($GLOBALS['balises']['lien_tags'], liste_tags($lien, '1'), $texte);
    return $texte;
}

/**
 * récupère le bout du fichier thème contenant une boucle comme {BOUCLE_commentaires}
 *   soit le morceau de HTML retourné est parsé à son tour pour crée le HTML de chaque commentaire ou chaque article.
 *   soit le morceau de HTML retourné sert à se faire remplacer par l’ensemble des commentaires constitués
 */
function extract_boucles($texte, $balise, $incl)
{
    $len_balise_d = 0 ;
    $len_balise_f = 0;
    if ($incl == 'excl') {
        // la $balise est exclue : bli{p}blabla{/p}blo => blabla
        $len_balise_d = strlen('{'.$balise.'}');
    } else {
        // la $balise est inclue : bli{p}blabla{/p}blo => {p}blabla{/p}
        $len_balise_f = strlen('{/'.$balise.'}');
    }

    $debut = strpos($texte, '{'.$balise.'}');
    $fin = strpos($texte, '{/'.$balise.'}');

    if ($debut !== false and $fin !== false) {
        $debut += $len_balise_d;
        $fin += $len_balise_f;

        $length = $fin - $debut;
        $return = substr($texte, $debut, $length);
        return $return;
    } else {
        // $balises n’est pas dans le texte : retourne le texte sans changements.
        return $texte;
    }
}

/**
 * only used by the main page of the blog (not on admin) : shows main blog page.
 */
function afficher_index($tableau, $type)
{
    $HTML = '';
    if (!($theme_page = file_get_contents($GLOBALS['theme_liste']))) {
        die($GLOBALS['lang']['err_theme_introuvable']);
    }
    if (!($theme_post = file_get_contents($GLOBALS['theme_post_post']))) {
        die($GLOBALS['lang']['err_theme_introuvable']);
    }

    if ($type == 'list') {
        $HTML_elmts = '';
        $data = array();
        if (!empty($tableau)) {
            // if (count($tableau)==1 and !empty($tableau[0]['bt_title']) and $tableau[0]['bt_type'] == 'article') {
                // redirection($tableau[0]['bt_link']);
            // } else {
            if (count($tableau)==1 and ($tableau[0]['bt_type'] == 'link' or $tableau[0]['bt_type'] == 'note')) {
                $data = $tableau[0];
            }
            if ($tableau[0]['bt_type'] == 'article') {
                if (!($theme_article = file_get_contents($GLOBALS['theme_post_artc']))) {
                    die($GLOBALS['lang']['err_theme_introuvable']);
                }
                $conversion_theme_fonction = 'conversions_theme_article';
            }
            if ($tableau[0]['bt_type'] == 'comment') {
                if (!($theme_article = file_get_contents($GLOBALS['theme_post_comm']))) {
                    die($GLOBALS['lang']['err_theme_introuvable']);
                }
                $conversion_theme_fonction = 'conversions_theme_commentaire';
            }
            if ($tableau[0]['bt_type'] == 'link' or $tableau[0]['bt_type'] == 'note') {
                if (!($theme_article = file_get_contents($GLOBALS['theme_post_link']))) {
                    die($GLOBALS['lang']['err_theme_introuvable']);
                }
                $conversion_theme_fonction = 'conversions_theme_lien';
            }
            foreach ($tableau as $element) {
                $HTML_elmts .=  $conversion_theme_fonction($theme_article, $element);
            }
            $HTML = str_replace(extract_boucles($theme_page, $GLOBALS['boucles']['posts'], 'incl'), $HTML_elmts, $theme_page);
            $HTML = conversions_theme($HTML, $data, 'post');
            // }
        } else {
            $HTML_article = conversions_theme($theme_page, $data, 'list');
            $HTML = str_replace(extract_boucles($theme_page, $GLOBALS['boucles']['posts'], 'incl'), $GLOBALS['lang']['note_no_article'], $HTML_article);
        }
    } elseif ($type == 'post') {
        $billet = $tableau;
        // parse & apply template article
        $HTML_article = conversions_theme_article($theme_post, $billet);

        // parse & apply templace commentaires
        $HTML_comms = '';
        // get list comments
        if ($billet['bt_nb_comments'] != 0) {
            $query = "SELECT c.*, a.bt_title FROM commentaires AS c, articles AS a WHERE c.bt_article_id=? AND c.bt_article_id=a.bt_id AND c.bt_statut=1 ORDER BY c.bt_id LIMIT ? ";
            $commentaires = liste_elements($query, array($billet['bt_id'], $billet['bt_nb_comments']), 'commentaires');
            $template_comments = extract_boucles($theme_post, $GLOBALS['boucles']['commentaires'], 'excl');
            foreach ($commentaires as $element) {
                $HTML_comms .=  conversions_theme_commentaire($template_comments, $element);
            }
        }

        // in $article : pastes comments
        $v = extract_boucles($theme_post, $GLOBALS['boucles']['commentaires'], 'incl');
        $HTML_article = str_replace($v, $HTML_comms, $HTML_article);

        // in global page : pastes article and comms
        $HTML = str_replace(extract_boucles($theme_page, $GLOBALS['boucles']['posts'], 'incl'), $HTML_article, $theme_page);

        // in global page : remplace remaining tags
        $HTML = conversions_theme($HTML, $billet, 'post');
    }

    $tmp_hook = hook_trigger_and_check('show_index', $HTML);
    if ($tmp_hook !== false) {
        $HTML = $tmp_hook['1'];
    }

    echo $HTML;
}

/**
 * Affiche la liste des articles, avec le &liste dans l’url
 */
function afficher_liste($tableau)
{
    $HTML_elmts = '';
    if (!($theme_page = file_get_contents($GLOBALS['theme_liste']))) {
        die($GLOBALS['lang']['err_theme_introuvable']);
    }
    $HTML_article = conversions_theme($theme_page, array(), 'list');
    if ($tableau) {
        $HTML_elmts .= '<ul id="liste-all-articles">'."\n";
        foreach ($tableau as $e) {
            $short_date = substr($e['bt_date'], 0, 4).'/'.substr($e['bt_date'], 4, 2).'/'.substr($e['bt_date'], 6, 2);
            $HTML_elmts .= "\t".'<li><time datetime="'.date_formate_iso($e['bt_id']).'">'.$short_date.'</time><a href="'.$e['bt_link'].'">'.$e['bt_title'].'</a></li>'."\n";
        }
        $HTML_elmts .= '</ul>'."\n";
        $HTML = str_replace(extract_boucles($theme_page, $GLOBALS['boucles']['posts'], 'incl'), $HTML_elmts, $HTML_article);
    } else {
        $HTML = str_replace(extract_boucles($theme_page, $GLOBALS['boucles']['posts'], 'incl'), $GLOBALS['lang']['note_no_article'], $HTML_article);
    }
    echo $HTML;
}

/**
 * Include Addons and converts {tags} to HTML (specified in addons)
 */
function conversion_theme_addons($texte)
{

    // Parse the $texte and replace {tags} with html generated in addon.
    // Generate CSS and JS includes too.
    $css = "<style>\n\t\t@charset 'utf-8';";
    $js = '';
    $hasStyle = false;

    // proceed addons tags
    foreach ($GLOBALS['addons'] as $addon) {
        if (!$addon['enabled'] || !isset($addon['tag'])) {
            continue;
        };

        $lookFor = '{addon_'.$addon['tag'].'}';

        if (strpos($texte, $lookFor) !== false) {
            $callback = 'a_'.$addon['tag'];
            if (function_exists($callback)) {
                while (($pos = strpos($texte, $lookFor)) !== false) {
                    $texte = substr_replace($texte, call_user_func($callback), $pos, strlen($lookFor));
                }
            } else {
                $texte = str_replace($lookFor, '', $texte);
            }
        }

        if (isset($addon['css'])) {
            if (!is_array($addon['css'])) {
                $addon['css'] = array($addon['css']);
            }
            foreach ($addon['css'] as $inc_file) {
                $inc = sprintf('%s%s/%s', DIR_ADDONS, $addon['tag'], $inc_file);
                if (is_file($inc)) {
                    $hasStyle = true;
                    $inc = sprintf('%saddons/%s/%s', URL_ROOT, $addon['tag'], $inc_file);
                    $css .= sprintf("\n\t\t@import url('%s');", addslashes($inc));
                }
            }
        }

        if (isset($addon['js'])) {
            if (!is_array($addon['js'])) {
                $addon['js'] = array($addon['js']);
            }
            foreach ($addon['js'] as $inc_file) {
                $inc = sprintf('%s%s/%s', DIR_ADDONS, $addon['tag'], $inc_file);
                if (is_file($inc)) {
                    $inc = sprintf('%saddons/%s/%s', URL_ROOT, $addon['tag'], $inc_file);
                    $js .= sprintf("<script src=\"%s\"></script>\n", $inc);
                }
            }
        }
    }

    // CSS and JS inclusions
    $css .= "\n\t</style>";
    if (!$hasStyle) {
        $css = '';
    }
    $texte = str_replace('{includes.css}', $css, $texte);
    $texte = str_replace('{includes.js}', $js, $texte);

    // remove useless tag in case of tag for nonexistent addon
    // strpos for perfomance : no tag = no regex
    if (strpos($texte, '{addon_') !== false) {
        $texte = preg_replace('/\{addon_[a-zA-Z0-9-_]*\}/', '', $texte);
    }

    // hook
    $tmp_hook = hook_trigger_and_check('conversion_theme_addons_end', $texte);
    if ($tmp_hook !== false) {
        $texte = $tmp_hook['1'];
    }

    return $texte;
}
