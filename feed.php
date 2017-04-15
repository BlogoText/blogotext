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

// dependancy
require_once BT_ROOT.'inc/addons.php';

// launch addons
addons_init_public();

/**
 * feed.php replace atom.php and rss.php
 *
 * _GET['format']
 * @param _GET['format'], string, rss||atom, default : rss
 * @echo atom or rss format
 *
 * _GET['id']
 * @param _GET['id'], string, (^[0-9]{14}$), id of 1 article
 * @echo comments on 1 article
 *
 * _GET['mode']
 * @param _GET['mode'], string, blog||comments||links, default : blog
 * @echo the lastest of _GET['mode']
 *
 * _GET['tag'] (child of _GET['mode']) if available
 * @param _GET['tag']
 *
 * Rules
 * use of : _GET['id'] and _GET['mode'] => only _GET['id'] will be used
 * used of : _GET['format'] and _GET['id'] => _GET['id'] formatted with _GET['format']
 * used of : _GET['format'] and _GET['mode'] => _GET['mode'] formatted with _GET['format']
 */

$format = (string)filter_input(INPUT_GET, 'format');
if (!in_array($format, array('rss', 'atom'))) {
    $format = 'rss';
}

header('Content-Type: application/'. $format .'+xml; charset=UTF-8');

/**
 * second level caching file.
 *
 * if file exists and is valid, return the cache and die
 *  " !file exists, go for the full process
 */
$flux_cache_lv2_path = DIR_VHOST_CACHE.'cache2_'. $format .'_'.substr(md5((isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : ''), 0, 8).'.dat';

// if cache file exists
if (is_file($flux_cache_lv2_path)) {
    // if cache not too old
    if (filemtime($flux_cache_lv2_path) > time()-(3600)) {
        die(readfile($flux_cache_lv2_path));
    }
    // file too old: delete it and go on (and create new file)
    unlink($flux_cache_lv2_path);
}


/**
 * comments for an article (ATOM)
 */
function flux_comments_for_article_atom($liste)
{
    $xml = '';
    if (!empty($liste)) {
        $xml .= '<title>'.$GLOBALS['lang']['feed_article_comments_title'].$liste[0]['bt_title'].' - '.$GLOBALS['nom_du_site'].'</title>'."\n";
        $xml .= '<link href="'.$liste[0]['bt_link'].'" />'."\n";
        $xml .= '<id>'.$liste[0]['bt_link'].'</id>';

        foreach ($liste as $comment) {
            $dec = decode_id($comment['bt_id']);
            $tag = 'tag:'.parse_url(URL_ROOT, PHP_URL_HOST).''.$dec['annee'].'-'.$dec['mois'].'-'.$dec['jour'].':'.$comment['bt_id'];
            $xml .= '<entry>'."\n";
                $xml .= '<title>'.$comment['bt_author'].'</title>'."\n";
                $xml .= '<link href="'.$comment['bt_link'].'"/>'."\n";
                $xml .= '<id>'.$tag.'</id>'."\n";
                $xml .= '<updated>'.date('c', mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee'])).'</updated>'."\n";
                $xml .= '<content type="html">'.htmlspecialchars($comment['bt_content']).'</content>'."\n";
            $xml .= '</entry>'."\n";
        }
    } else {
        $xml .= '<entry>'."\n";
            $xml .= '<title>'.$GLOBALS['lang']['note_no_commentaire'].'</title>'."\n";
            $xml .= '<id>'.URL_ROOT.'</id>'."\n";
            $xml .= '<link href="'.URL_ROOT.'" />'."\n";
            $xml .= '<updated>'.date('r').'</updated>'."\n";
            $xml .= '<content type="html">'.$GLOBALS['lang']['no_comments'].'</content>'."\n";
        $xml .= '</entry>'."\n";
    }
    return $xml;
}

/**
 * comments for an article (ATOM)
 */
function flux_comments_for_article_rss($liste)
{
    $xml = '';
    if (!empty($liste)) {
        $xml .= '<title>'.$GLOBALS['lang']['feed_article_comments_title'].$liste[0]['bt_title'].' - '.$GLOBALS['nom_du_site'].'</title>'."\n";
        $xml .= '<link>'.$liste[0]['bt_link'].'</link>'."\n";
        $xml .= '<description><![CDATA['.$GLOBALS['description'].']]></description>'."\n";
        $xml .= '<language>fr</language>'."\n";
        $xml .= '<copyright>'.$GLOBALS['auteur'].'</copyright>'."\n";
        foreach ($liste as $comment) {
            $dec = decode_id($comment['bt_id']);
            $xml .= '<item>'."\n";
                $xml .= '<title>'.$comment['bt_author'].'</title>'."\n";
                $xml .= '<guid isPermaLink="false">'.$comment['bt_link'].'</guid>'."\n";
                $xml .= '<link>'.$comment['bt_link'].'</link>'."\n";
                $xml .= '<pubDate>'.date('r', mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee'])).'</pubDate>'."\n";
                $xml .= '<description><![CDATA['.($comment['bt_content']).']]></description>'."\n";
            $xml .= '</item>'."\n";
        }
    } else {
        $xml .= '<item>'."\n";
            $xml .= '<title>'.$GLOBALS['lang']['note_no_commentaire'].'</title>'."\n";
            $xml .= '<guid isPermaLink="false">'.URL_ROOT.'</guid>'."\n";
            $xml .= '<link>'.URL_ROOT.'</link>'."\n";
            $xml .= '<pubDate>'.date('r').'</pubDate>'."\n";
            $xml .= '<description>'.$GLOBALS['lang']['no_comments'].'</description>'."\n";
        $xml .= '</item>'."\n";
    }
}

/**
 *
 */
function flux_all_kind_rss($list, $invert)
{
    $xml = '';
    foreach ($list as $elem) {
        $time = (isset($elem['bt_date'])) ? $elem['bt_date'] : $elem['bt_id'];
        if ($time > date('YmdHis')) {
            continue;
        }
        $title = (in_array($elem['bt_type'], array('article', 'link', 'note'))) ? $elem['bt_title'] : $elem['bt_author'];
        // normal code
        $xml_post = '<item>'."\n";
        $xml_post .= '<title>'.$title.'</title>'."\n";
        $xml_post .= '<guid isPermaLink="false">'.$elem['bt_id'].'-'.$elem['bt_type'].'</guid>'."\n";
        $xml_post .= '<pubDate>'.date_create_from_format('YmdHis', $time)->format('r').'</pubDate>'."\n";
        if ($elem['bt_type'] == 'link') {
            if ($invert) {
                $xml_post .= '<link>'.URL_ROOT.'?id='.$elem['bt_id'].'</link>'."\n";
                $xml_post .= '<description><![CDATA['.rel2abs($elem['bt_content']). '<br/> — (<a href="'.$elem['bt_link'].'">link</a>)]]></description>'."\n";
            } else {
                $xml_post .= '<link>'.$elem['bt_link'].'</link>'."\n";
                $xml_post .= '<description><![CDATA['.rel2abs($elem['bt_content']).'<br/> — (<a href="'.URL_ROOT.'?id='.$elem['bt_id'].'">permalink</a>)]]></description>'."\n";
            }
        } else {
            $xml_post .= '<link>'.$elem['bt_link'].'</link>'."\n";
            $xml_post .= '<description><![CDATA['.rel2abs($elem['bt_content']).']]></description>'."\n";
        }
        if (isset($elem['bt_tags']) and !empty($elem['bt_tags'])) {
            $xml_post .= '<category>'.implode('</category>'."\n".'<category>', explode(', ', $elem['bt_tags'])).'</category>'."\n";
        }
        $xml_post .= '</item>'."\n";
        $xml .= $xml_post;
    }
    return $xml;
}

/**
 *
 */
function flux_all_kind_atom($list, $invert)
{
    $xml_post = '';
    $main_updated = 0; // useless ?
    foreach ($list as $elem) {
        $time = (isset($elem['bt_date'])) ? $elem['bt_date'] : $elem['bt_id'];
        $main_updated = max($main_updated, $time);
        if ($time > date('YmdHis')) {
            continue;
        }
        $title = (in_array($elem['bt_type'], array('article', 'link', 'note'))) ? $elem['bt_title'] : $elem['bt_author'];
        $tag = 'tag:'.parse_url(URL_ROOT, PHP_URL_HOST).','.date_create_from_format('YmdHis', $time)->format('Y-m-d').':'.$elem['bt_type'].'-'.$elem['bt_id'];


        // normal code
        $xml_post .= '<entry>'."\n";
        $xml_post .= '<title>'.$title.'</title>'."\n";
        $xml_post .= '<id>'.$tag.'</id>'."\n";
        $xml_post .= '<updated>'.date_create_from_format('YmdHis', $time)->format('c').'</updated>'."\n";

        if ($elem['bt_type'] == 'link') {
            if ($invert) {
                $xml_post .= '<link href="'.URL_ROOT.'?id='.$elem['bt_id'].'"/>'."\n";
                $xml_post .= '<content type="html">'.htmlspecialchars(rel2abs($elem['bt_content']).'<br/> — (<a href="'.$elem['bt_link'].'">link</a>)').'</content>'."\n";
            } else {
                $xml_post .= '<link href="'.$elem['bt_link'].'"/>'."\n";
                $xml_post .= '<content type="html">'.htmlspecialchars(rel2abs($elem['bt_content']).'<br/> — (<a href="'.URL_ROOT.'?id='.$elem['bt_id'].'">permalink</a>)').'</content>'."\n";
            }
        } else {
            $xml_post .= '<link href="'.$elem['bt_link'].'"/>'."\n";
            $xml_post .= '<content type="html">'.htmlspecialchars(rel2abs($elem['bt_content'])).'</content>'."\n";
        }
        if (isset($elem['bt_tags']) and !empty($elem['bt_tags'])) {
            $xml_post .= '<category term="'.implode('" />'."\n".'<category term="', explode(', ', $elem['bt_tags'])).'" />'."\n";
        }

        $xml_post .= '</entry>'."\n";
    }
    return $xml_post;
}

/**
 * convert relativ URL to absolute URL
 */
function rel2abs($article)
{
    $article = str_replace(' src="/', ' src="http://'.URL_ROOT.'/', $article);
    $article = str_replace(' href="/', ' href="http://'.URL_ROOT.'/', $article);
    $base = URL_ROOT;
    $article = preg_replace('#(src|href)=\"(?!http)#i', '$1="'.$base, $article);
    return $article;
}


// dependancy
require_once BT_ROOT.'inc/addons.php';
// launch hook
hook_trigger('system-start');



$xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
if ($format == 'atom') {
    $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">'."\n";
    $xml .= '<author><name>'.$GLOBALS['auteur'].'</name></author>'."\n";
    $xml .= '<link rel="self" href="'.URL_ROOT.'atom.php'.((!empty($_SERVER['QUERY_STRING'])) ? '?'.(htmlspecialchars($_SERVER['QUERY_STRING'])) : '').'" />'."\n";
} else {
    $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">'."\n";
    $xml .= '<channel>'."\n";
    $xml .= '<atom:link href="'.URL_ROOT.'rss.php'.((!empty($_SERVER['QUERY_STRING'])) ? '?'.(htmlspecialchars($_SERVER['QUERY_STRING'])) : '').'" rel="self" type="application/rss+xml" />';
}

/**
 * if _GET['id'] have a param (^[0-9]{14}$), get the comments for the wanted article
 *
 * @param _GET['id'], string, (^[0-9]{14}$), id of 1 article
 * @echo comments on 1 article
 */
$postId = (string)filter_input(INPUT_GET, 'id');
if (preg_match('#^[0-9]{14}$#', $postId)) {
    $GLOBALS['db_handle'] = open_base();
    $db_req = '
            SELECT c.*, a.bt_title
              FROM commentaires AS c,
                   articles AS a
             WHERE c.bt_article_id = ?
               AND c.bt_article_id = a.bt_id
               AND c.bt_statut = 1
          ORDER BY c.bt_id
              DESC';
    $liste = liste_elements($db_req, array((int)$postId), 'commentaires');

    if ($format == 'atom') {
        echo flux_comments_for_article_atom($liste);
    } else {
        echo flux_comments_for_article_rss($liste);
    }
/**
 * return latest article, links..
 *
 * @param _GET['mode'], string, blog||comments||links, default : blog
 * @echo the lastest of _GET['mode']
 */
} else {
    $fcache = DIR_VHOST_CACHE.'cache1_feed.dat';
    $liste = open_serialzd_file($fcache);
    if (!is_file($fcache) or !is_array($liste)) {
        $GLOBALS['db_handle'] = open_base();
        flux_refresh_cache_lv1();
        // if file exists but reading it does not give an array: try again
        if (is_file($fcache)) {
            $liste = open_serialzd_file($fcache);
        }
    }

    // if cache file does not work: delete it.
    if (!is_array($liste)) {
        $liste = array('a' => array(), 'c' => array(), 'l' => array());
        unlink($fcache);
    }

    $liste_rss = array();
    $modes_url = '';
    $mode = (string)filter_input(INPUT_GET, 'mode');
    if ($mode) {
        $found = 0;
        // 1 = articles
        if (strpos($mode, 'blog') !== false) {
            $liste_rss = array_merge($liste_rss, $liste['a']);
            $found = 1;
            $modes_url .= 'blog-';
        }
        // 2 = commentaires
        if (strpos($mode, 'comments') !== false) {
            $liste_rss = array_merge($liste_rss, $liste['c']);
            $found = 1;
            $modes_url .= 'comments-';
        }
        // 4 = links
        if (strpos($mode, 'links') !== false) {
            $liste_rss = array_merge($liste_rss, $liste['l']);
            $found = 1;
            $modes_url .= 'links-';
        }
        // default, blog
        if ($found == 0) {
            $liste_rss = $liste['a'];
        }

    // default, blog
    } else {
        $liste_rss = array_merge($liste_rss, $liste['a']);
        // $found = 1;
        $modes_url .= 'blog-';
    }

    // tri selon tags (si il y a)
    $tag = (string)filter_input(INPUT_GET, 'tag');
    if ($tag) {
        foreach ($liste_rss as $i => $entry) {
            if (isset($entry['bt_tags'])) {
                if ((strpos($entry['bt_tags'], htmlspecialchars($tag.',')) === false) and
                     (strpos($entry['bt_tags'], htmlspecialchars(', '.$tag)) === false) and
                     ($entry['bt_tags'] != htmlspecialchars($tag))) {
                    unset($liste_rss[$i]);
                }
            }
        }
    }

    // tri selon la date (qui est une sous-clé du tableau, d’où cette manœuvre)
    foreach ($liste_rss as $key => $item) {
        $bt_id[$key] = (isset($item['bt_date'])) ? $item['bt_date'] : $item['bt_id'];
    }
    if (!empty($liste_rss)) {
        array_multisort($bt_id, SORT_DESC, $liste_rss);
    }

    // ne garde que les 20 premières entrées
    $liste_rss = array_slice($liste_rss, 0, 20);

    $tmp_hook = hook_trigger_and_check('before_show_'. $format .'_no_cache', $liste_rss);
    if ($tmp_hook !== false) {
        $liste_rss = $tmp_hook['1'];
    }

    $invert = (isset($_GET['invertlinks'])) ? true : false;

    if ($format == 'rss') {
        $xml .= '<title>'.$GLOBALS['nom_du_site'].'</title>'."\n";
        $xml .= '<link>'.URL_ROOT.((trim($modes_url, '-') == '') ? '' : '?mode='.(trim($modes_url, '-'))).'</link>'."\n";
        $xml .= '<description><![CDATA['.$GLOBALS['description'].']]></description>'."\n";
        $xml .= '<language>fr</language>'."\n";
        $xml .= '<copyright>'.$GLOBALS['auteur'].'</copyright>'."\n";
        $xml .= flux_all_kind_rss($liste_rss, $invert);
    } else {
        $xml .= '<title>'.$GLOBALS['nom_du_site'].'</title>'."\n";
        $xml .= '<link href="'.URL_ROOT.'?mode='.(trim($modes_url, '-')).'"/>'."\n";
        $xml .= '<id>'.URL_ROOT.'?mode='.$modes_url.'</id>'."\n";
        $xml .= flux_all_kind_atom($liste_rss, $invert);
    }
}


$end = microtime(true);

$xml .= '<!-- cached file generated on '.date('r').' -->'."\n";
$xml .= '<!-- generated in '.round(($end - $begin), 6).' seconds -->'."\n";
if ($format == 'rss') {
    $xml .= '</channel>'."\n";
    $xml .= '</rss>';
} else {
    $xml .= '</feed>';
}

file_put_contents($flux_cache_lv2_path, $xml, LOCK_EX);
echo $xml;
