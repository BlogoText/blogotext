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
 * feed.php replace atom.php and rss.php
 *
 * _GET['format']
 * @param _GET['format'], string, rss||atom||json, default : rss
 * @echo atom, rss or json format
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

/**
 * comments for an article (ATOM)
 */
function flux_comments_for_article_atom($liste)
{
    global $data;
    if (!empty($liste)) {
        $data[] = '<title>'.$GLOBALS['lang']['feed_article_comments_title'].$liste[0]['bt_title'].' - '.$GLOBALS['nom_du_site'].'</title>';
        $data[] = '<link href="'.$liste[0]['bt_link'].'" />';
        $data[] = '<id>'.$liste[0]['bt_link'].'</id>';

        foreach ($liste as $comment) {
            $dec = decode_id($comment['bt_id']);
            $tag = 'tag:'.parse_url(URL_ROOT, PHP_URL_HOST).''.$dec['annee'].'-'.$dec['mois'].'-'.$dec['jour'].':'.$comment['bt_id'];
            $data[] = '<entry>';
                $data[] = '<title>'.$comment['bt_author'].'</title>';
                $data[] = '<link href="'.$comment['bt_link'].'"/>';
                $data[] = '<id>'.$tag.'</id>';
                $data[] = '<updated>'.date('c', mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee'])).'</updated>';
                $data[] = '<content type="html">'.htmlspecialchars($comment['bt_content']).'</content>';
            $data[] = '</entry>';
        }
    } else {
        $data[] = '<entry>';
            $data[] = '<title>'.$GLOBALS['lang']['note_no_commentaire'].'</title>';
            $data[] = '<id>'.URL_ROOT.'</id>';
            $data[] = '<link href="'.URL_ROOT.'" />';
            $data[] = '<updated>'.date('r').'</updated>';
            $data[] = '<content type="html">'.$GLOBALS['lang']['no_comments'].'</content>';
        $data[] = '</entry>';
    }
}

/**
 * comments for an article (RSS)
 */
function flux_comments_for_article_rss($liste)
{
    global $data;
    if (!empty($liste)) {
        $data[] = '<title>'.$GLOBALS['lang']['feed_article_comments_title'].$liste[0]['bt_title'].' - '.$GLOBALS['nom_du_site'].'</title>';
        $data[] = '<link>'.$liste[0]['bt_link'].'</link>';
        $data[] = '<description><![CDATA['.$GLOBALS['description'].']]></description>';
        $data[] = '<language>fr</language>';
        $data[] = '<copyright>'.$GLOBALS['auteur'].'</copyright>';
        foreach ($liste as $comment) {
            $dec = decode_id($comment['bt_id']);
            $data[] = '<item>';
                $data[] = '<title>'.$comment['bt_author'].'</title>';
                $data[] = '<guid isPermaLink="false">'.$comment['bt_link'].'</guid>';
                $data[] = '<link>'.$comment['bt_link'].'</link>';
                $data[] = '<pubDate>'.date('r', mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee'])).'</pubDate>';
                $data[] = '<description><![CDATA['.($comment['bt_content']).']]></description>';
            $data[] = '</item>';
        }
    } else {
        $data[] = '<item>';
            $data[] = '<title>'.$GLOBALS['lang']['note_no_commentaire'].'</title>';
            $data[] = '<guid isPermaLink="false">'.URL_ROOT.'</guid>';
            $data[] = '<link>'.URL_ROOT.'</link>';
            $data[] = '<pubDate>'.date('r').'</pubDate>';
            $data[] = '<description>'.$GLOBALS['lang']['no_comments'].'</description>';
        $data[] = '</item>';
    }
}

/**
 * comments for an article (JSON)
 */
function flux_comments_for_article_json($liste)
{
    global $data;
    $data['items'] = array();
    if (!empty($liste)) {
        $data['title'] = $GLOBALS['lang']['feed_article_comments_title'].$liste[0]['bt_title'].' - '.$GLOBALS['nom_du_site'];
        $data['feed_url'] = $liste[0]['bt_link'];
        foreach ($liste as $comment) {
            $dec = decode_id($comment['bt_id']);
            $date = new DateTime(date('r', mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee'])));
            $item = array(
                'id' => $comment['bt_link'],
                'title' => $comment['bt_author'],
                'content_html' => $comment['bt_content'],
                'date_published' =>  $date->format(DateTime::RFC3339),
            );
            $data['items'][] = $item;
        }
    } else {
        $date = new DateTime(date('r'));
        $item = array(
            'id' => URL_ROOT,
            'title' => $GLOBALS['lang']['note_no_commentaire'],
            'content_html' => $GLOBALS['lang']['no_comments'],
            'date_published' =>  $date->format(DateTime::RFC3339),
        );
        $data['items'][] = $item;
    }
}

/**
 * RSS feed
 */
function flux_all_kind_rss($list, $invert)
{
    global $data;
    foreach ($list as $elem) {
        $time = (isset($elem['bt_date'])) ? $elem['bt_date'] : $elem['bt_id'];
        if ($time > date('YmdHis')) {
            continue;
        }
        $title = (in_array($elem['bt_type'], array('article', 'link', 'note'))) ? $elem['bt_title'] : $elem['bt_author'];
        // normal code
        $data[] = '<item>';
        $data[] = '<title>'.$title.'</title>';
        $data[] = '<guid isPermaLink="false">'.$elem['bt_id'].'-'.$elem['bt_type'].'</guid>';
        $data[] = '<pubDate>'.date_create_from_format('YmdHis', $time)->format('r').'</pubDate>';
        if ($elem['bt_type'] == 'link') {
            if ($invert) {
                $data[] = '<link>'.URL_ROOT.'?id='.$elem['bt_id'].'</link>';
                $data[] = '<description><![CDATA['.rel2abs($elem['bt_content']). '<br/> — (<a href="'.$elem['bt_link'].'">link</a>)]]></description>';
            } else {
                $data[] = '<link>'.$elem['bt_link'].'</link>';
                $data[] = '<description><![CDATA['.rel2abs($elem['bt_content']).'<br/> — (<a href="'.URL_ROOT.'?id='.$elem['bt_id'].'">permalink</a>)]]></description>';
            }
        } else {
            $data[] = '<link>'.$elem['bt_link'].'</link>';
            $data[] = '<description><![CDATA['.rel2abs($elem['bt_content']).']]></description>';
        }
        if (isset($elem['bt_tags']) and !empty($elem['bt_tags'])) {
            $data[] = '<category>'.implode('</category>'.'<category>', explode(', ', $elem['bt_tags'])).'</category>';
        }
        $data[] = '</item>';
    }
}

/**
 * ATOM feed
 */
function flux_all_kind_atom($list, $invert)
{
    global $data;
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
        $data[] = '<entry>';
        $data[] = '<title>'.$title.'</title>';
        $data[] = '<id>'.$tag.'</id>';
        $data[] = '<updated>'.date_create_from_format('YmdHis', $time)->format('c').'</updated>';

        if ($elem['bt_type'] == 'link') {
            if ($invert) {
                $data[] = '<link href="'.URL_ROOT.'?id='.$elem['bt_id'].'"/>';
                $data[] = '<content type="html">'.htmlspecialchars(rel2abs($elem['bt_content']).'<br/> — (<a href="'.$elem['bt_link'].'">link</a>)').'</content>';
            } else {
                $data[] = '<link href="'.$elem['bt_link'].'"/>';
                $data[] = '<content type="html">'.htmlspecialchars(rel2abs($elem['bt_content']).'<br/> — (<a href="'.URL_ROOT.'?id='.$elem['bt_id'].'">permalink</a>)').'</content>';
            }
        } else {
            $data[] = '<link href="'.$elem['bt_link'].'"/>';
            $data[] = '<content type="html">'.htmlspecialchars(rel2abs($elem['bt_content'])).'</content>';
        }
        if (isset($elem['bt_tags']) and !empty($elem['bt_tags'])) {
            $data[] = '<category term="'.implode('" />'.'<category term="', explode(', ', $elem['bt_tags'])).'" />';
        }

        $data[] = '</entry>';
    }
}

/**
 * JSON feed
 */
function flux_all_kind_json($list, $invert)
{
    global $data;
    $data['items'] = array();
    foreach ($list as $elem) {
        $time = (isset($elem['bt_date'])) ? $elem['bt_date'] : $elem['bt_id'];
        if ($time > date('YmdHis')) {
            continue;
        }
        $title = (in_array($elem['bt_type'], array('article', 'link', 'note'))) ? $elem['bt_title'] : $elem['bt_author'];
        // normal code
        $item = array(
            'id' => $elem['bt_id'].'-'.$elem['bt_type'],
            'title' => $title,
            'date_published' => date_create_from_format('YmdHis', $time)->format(DateTime::RFC3339),
        );
        if ($elem['bt_type'] == 'link') {
            if ($invert) {
                $item['url'] = URL_ROOT.'?id='.$elem['bt_id'];
                $item['content_html'] = rel2abs($elem['bt_content']). '<br/> — (<a href="'.$elem['bt_link'].'">link</a>)';
            } else {
                $item['url'] = $elem['bt_link'];
                $item['content_html'] = rel2abs($elem['bt_content']).'<br/> — (<a href="'.URL_ROOT.'?id='.$elem['bt_id'].'">permalink</a>)';
            }
        } else {
            $item['url'] = $elem['bt_link'];
            $item['content_html'] = rel2abs($elem['bt_content']);
        }
        if (isset($elem['bt_tags']) and !empty($elem['bt_tags'])) {
            $item['tags'] = explode(', ', $elem['bt_tags']);
        }
        $data['items'][] = $item;
    }
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


require_once 'inc/boot.php';

// dependancy
require_once BT_ROOT.'inc/addons.php';

// launch addons
addons_init_public();


$format = (isset($format)) ? $format : (string)filter_input(INPUT_GET, 'format');
if (!in_array($format, array('rss', 'atom', 'json'))) {
    $format = 'rss';
}

if ($format == 'json') {
    $header = 'Content-Type: application/json; charset=UTF-8';
} else {
    $header = 'Content-Type: application/'. $format .'+xml; charset=UTF-8';
}
header($header);

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


// dependancy
require_once BT_ROOT.'inc/addons.php';
// launch hook
hook_trigger('system-start');


// Will contain all text lines that will be saved in cache
// and printed out to the browser.
$data = array();

if ($format == 'atom') {
    $data[] = '<?xml version="1.0" encoding="UTF-8"?>';
    $data[] = '<feed xmlns="http://www.w3.org/2005/Atom">';
    $data[] = '<author><name>'.$GLOBALS['auteur'].'</name></author>';
} elseif ($format == 'rss') {
    $data[] = '<?xml version="1.0" encoding="UTF-8"?>';
    $data[] = '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">';
    $data[] = '<channel>';
} else {
    $data['version'] = 'https://jsonfeed.org/version/1';
    $data['home_page_url'] = URL_ROOT;
    $data['description'] = $GLOBALS['description'];
    $data['author'] = array('name' => $GLOBALS['auteur']);
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
    call_user_func('flux_comments_for_article_'. $format, $liste);

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

    $invert = isset($_GET['invertlinks']);
    $mode_url = (trim($modes_url, '-') == '') ? '' : '&mode='.(trim($modes_url, '-'));

    if ($format == 'rss') {
        $data[] = '<title>'.$GLOBALS['nom_du_site'].'</title>';
        $data[] = '<link>'.URL_ROOT.'?format=rss'.$mode_url.'</link>';
        $data[] = '<description><![CDATA['.$GLOBALS['description'].']]></description>';
        $data[] = '<language>fr</language>';
        $data[] = '<copyright>'.$GLOBALS['auteur'].'</copyright>';
    } elseif ($format == 'atom') {
        $data[] = '<title>'.$GLOBALS['nom_du_site'].'</title>';
        $data[] = '<link href="'.URL_ROOT.'?format=atom'.$mode_url.'"/>';
        $data[] = '<id>'.URL_ROOT.'?format=atom&mode='.$modes_url.'</id>';
    } else {
        $data['title'] = $GLOBALS['nom_du_site'];
        $data['feed_url'] = URL_ROOT.'?format=json'.$mode_url;
    }
    call_user_func('flux_all_kind_'. $format, $liste_rss, $invert);
}


$end = microtime(true);

if ($format == 'rss') {
    $data[] = '</channel>';
    $data[] = '</rss>';
} elseif ($format == 'atom') {
    $data[] = '</feed>';
}

if ($format == 'json') {
    $output = json_encode($data, JSON_PRETTY_PRINT);
} else {
    $data[] = '<!-- cached file generated on '.date('r').' -->';
    $data[] = '<!-- generated in '.round(($end - $begin), 6).' seconds -->';
    $output = implode("\n", $data);
}
file_put_contents($flux_cache_lv2_path, $output, LOCK_EX);
echo $output;
