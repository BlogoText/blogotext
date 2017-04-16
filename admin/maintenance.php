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

/**
 *
 */
function validate_form_maintenance()
{
    $errors = array();
    $token = (string)filter_input(INPUT_POST, 'token');
    if (!$token) {
        $token = (string)filter_input(INPUT_GET, 'token');
    }

    if (!check_token($token)) {
        $errors[] = $GLOBALS['lang']['err_wrong_token'];
    }
    return $errors;
}

/**
 *
 */
function select_yes_no($name, $default, $label)
{
    $choice = array(
        $GLOBALS['lang']['non'],
        $GLOBALS['lang']['oui']
    );
    $form = '<label for="'.$name.'" >'.$label.'</label>';
    $form .= '<select id="'.$name.'" name="'.$name.'">' ;
    foreach ($choice as $option => $label) {
        $form .= '<option value="'.htmlentities($option).'"'.(($option == $default) ? ' selected="selected" ' : '').'>'.htmlentities($label).'</option>';
    }
    $form .= '</select>';
    return $form;
}

/**
 * Rebuild file database file.
 */
function rebuilt_file_db()
{
    $idir = rm_dots_dir(scandir(DIR_IMAGES));
    // Scans also subdir of img/* (in one single array of paths)
    foreach ($idir as $i => $e) {
        $subelem = DIR_IMAGES.$e;
        if (is_dir($subelem)) {
            unset($idir[$i]);
            $subidir = rm_dots_dir(scandir($subelem));
            foreach ($subidir as $im) {
                $idir[] = $e.'/'.$im;
            }
        }
    }

    $fdir = rm_dots_dir(scandir(DIR_DOCUMENTS));

    // Remove thumbnails from the list
    $idir = array_filter($idir, function ($file) {
        return !(preg_match('#(-thb\.jpg|index.php)$#', $file));
    });

    $filesDisk = array_merge($idir, $fdir);
    $filesDtb = $filesDtbId = array();

    // Purge inexistant files on the disk
    foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
        if (!in_array($file['bt_path'].$file['bt_filename'], $filesDisk)) {
            unset($GLOBALS['liste_fichiers'][$id]);
        }
        $filesDtb[] = $file['bt_path'].$file['bt_filename'];
        $filesDtbId[] = $file['bt_id'];
    }

    // Add new pictures present on the disk but not in the DTB
    foreach ($idir as $file) {
        $filepath = DIR_IMAGES.$file;
        if (!in_array($file, $filesDtb)) {
            $time = filemtime($filepath);
            $id = date('YmdHis', $time);
            // Check the ID existance, if present we change it (to the past)
            while (array_key_exists($id, $filesDtbId)) {
                $time--;
                $id = date('YmdHis', $time);
            }
            $filesDtbId[] = $id;

            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            $newImg = array(
                'bt_id' => $id,
                'bt_type' => 'image',
                'bt_fileext' => $ext,
                'bt_filesize' => filesize($filepath),
                'bt_filename' => $file,
                'bt_content' => '',
                'bt_wiki_content' => '',
                'bt_dossier' => 'default',
                'bt_checksum' => sha1_file($filepath),
                'bt_statut' => 0,
                'bt_path' => (preg_match('#^/[0-9a-f]{2}/#', $file)) ? substr($file, 0, 3) : '',
            );
            list($newImg['bt_dim_w'], $newImg['bt_dim_h']) = getimagesize($filepath);
            $GLOBALS['liste_fichiers'][] = $newImg;
        }
        create_thumbnail($filepath);
    }

    // Same process for files into files/*
    foreach ($fdir as $file) {
        if (!in_array($file, $filesDtb)) {
            $filepath = DIR_DOCUMENTS.$file;
            $time = filemtime($filepath);
            $id = date('YmdHis', $time);
            while (array_key_exists($id, $filesDtbId)) {
                $time--;
                $id = date('YmdHis', $time);
            }
            $filesDtbId[] = $id;

            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            $newFile = array(
                'bt_id' => $id,
                'bt_type' => guess_file_type($ext),
                'bt_fileext' => $ext,
                'bt_filesize' => filesize($filepath),
                'bt_filename' => $file,
                'bt_content' => '',
                'bt_wiki_content' => '',
                'bt_dossier' => 'default',
                'bt_checksum' => sha1_file($filepath),
                'bt_statut' => 0,
                'bt_path' => '',
            );
            $GLOBALS['liste_fichiers'][] = $newFile;
        }
    }
    $GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
    create_file_dtb(FILES_DB, $GLOBALS['liste_fichiers']);
}

/*
 * Generate favorites HTML file.
 */
function create_html_favs($numberOfLinks)
{
    $path = 'backup-links-'.date('Ymd-His').'.html';
    $limit = (!empty($numberOfLinks)) ? 'LIMIT 0, '.$numberOfLinks : '';
    $sql = '
        SELECT *
          FROM links
         ORDER BY bt_id DESC '.
         $limit;
    $list = liste_elements($sql, array(), 'links');

    $html = '<!DOCTYPE NETSCAPE-Bookmark-file-1><META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">';
    $html .= '<!--This is an automatically generated file. Do Not Edit! -->';
    $html .= '<TITLE>BlogoText links export '.date('Y-M-D').'</TITLE><H1>BlogoText links export</H1>';
    foreach ($list as $link) {
        $dec = decode_id($link['bt_id']);
        $timestamp = mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee']);  // HISMDY : wtf!
        $html .= '<DT><A HREF="'.$link['bt_link'].'" ADD_DATE="'.$timestamp.'" PRIVATE="'.abs(1 - $link['bt_statut']).'" TAGS="'.$link['bt_tags'].'">'.$link['bt_title'].'</A>';
        $html .= '<DD>'.strip_tags($link['bt_wiki_content']);
    }
    return (file_put_contents(DIR_BACKUP.$path, $html, LOCK_EX) === false) ? false : URL_BACKUP.$path;
}

/*
 * liste une table (ex: les commentaires) et compare avec un tableau de commentaires trouvées dans l’archive
 * Retourne deux tableau : un avec les éléments présents dans la base, et un avec les éléments absents de la base
 */
function diff_trouve_base($table, $arrFind)
{
    $arrBasic = $arrAbsent = array();
    $req = $GLOBALS['db_handle']->prepare('SELECT bt_id FROM '.$table);
    $req->execute();
    while ($ligne = $req->fetch()) {
        $arrBasic[] = $ligne['bt_id'];
    }

    // remplit les deux tableaux, pour chaque élément trouvé dans l’archive, en fonction de ceux déjà dans la base
    foreach ($arrFind as $element) {
        if (!in_array($element['bt_id'], $arrBasic)) {
            $arrAbsent[] = $element;
        }
    }
    return $arrAbsent;
}

/**
 * Issert big arrays of data in DB.
 */
function insert_table_links($tableau)
{
    $arrDiff = diff_trouve_base('links', $tableau);
    $return = count($arrDiff);
    $GLOBALS['db_handle']->beginTransaction();
    foreach ($arrDiff as $f) {
        $query = '
            INSERT INTO links (bt_type, bt_id, bt_link, bt_content, bt_wiki_content, bt_statut, bt_title, bt_tags)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute(array($f['bt_type'], $f['bt_id'], $f['bt_link'], $f['bt_content'], $f['bt_wiki_content'], $f['bt_statut'], $f['bt_title'], $f['bt_tags']));
    }
    $GLOBALS['db_handle']->commit();
    return $return;
}

/**
 *
 */
function insert_table_articles($tableau)
{
    $arrDiff = diff_trouve_base('articles', $tableau);
    $return = count($arrDiff);
    $GLOBALS['db_handle']->beginTransaction();
    foreach ($arrDiff as $art) {
        $query = '
            INSERT INTO articles (bt_type, bt_id, bt_date, bt_title, bt_abstract, bt_notes, bt_link, bt_content, bt_wiki_content, bt_tags, bt_keywords, bt_nb_comments, bt_allow_comments, bt_statut)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute(array( $art['bt_type'], $art['bt_id'], $art['bt_date'], $art['bt_title'], $art['bt_abstract'], $art['bt_notes'], $art['bt_link'], $art['bt_content'], $art['bt_wiki_content'], ((isset($art['bt_tags'])) ? $art['bt_tags'] : $art['bt_categories']), $art['bt_keywords'], $art['bt_nb_comments'], $art['bt_allow_comments'], $art['bt_statut'] ));
    }
    $GLOBALS['db_handle']->commit();
    return $return;
}

/**
 *
 */
function insert_table_commentaires($tableau)
{
    $arrDiff = diff_trouve_base('commentaires', $tableau);
    $return = count($arrDiff);
    $GLOBALS['db_handle']->beginTransaction();
    foreach ($arrDiff as $com) {
        $query = '
            INSERT INTO commentaires (bt_type, bt_id, bt_article_id, bt_content, bt_wiki_content, bt_author, bt_link, bt_webpage, bt_email, bt_subscribe, bt_statut)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute(array($com['bt_type'], $com['bt_id'], $com['bt_article_id'], $com['bt_content'], $com['bt_wiki_content'], $com['bt_author'], $com['bt_link'], $com['bt_webpage'], $com['bt_email'], $com['bt_subscribe'], $com['bt_statut']));
    }
    $GLOBALS['db_handle']->commit();
    return $return;
}

/**
 * recompte les commentaires aux articles
 */
function recompte_commentaires()
{
    if (DBMS == 'sqlite') {
        $query = '
            UPDATE articles
               SET bt_nb_comments = COALESCE((SELECT count(a.bt_id)
                                                FROM articles a
                                               INNER JOIN commentaires c
                                                       ON c.bt_article_id = a.bt_id
                                               WHERE articles.bt_id = a.bt_id
                                                     AND c.bt_statut = 1
                                               GROUP BY a.bt_id), 0)';
    } elseif (DBMS == 'mysql') {
        $query = '
            UPDATE articles
               SET bt_nb_comments = COALESCE((SELECT count(articles.bt_id)
                                                FROM commentaires
                                               WHERE commentaires.bt_article_id = articles.bt_id), 0)';
    }
    $req = $GLOBALS['db_handle']->prepare($query);
    $req->execute();
}

/**
 * importe un fichier json qui est au format de blogotext
 */
function importer_json($json)
{
    $data = json_decode($json, true);
    $return = array();
    // importer les liens
    if (!empty($data['liens'])) {
        $return['links'] = insert_table_links($data['liens']);
    }
    // importer les articles
    if (!empty($data['articles'])) {
        $return['articles'] = insert_table_articles($data['articles']);
    }
    // importer les commentaires
    if (!empty($data['commentaires'])) {
        $return['commentaires'] = insert_table_commentaires($data['commentaires']);
    }
    // recompter les commentaires
    if (!empty($data['commentaires']) or !empty($data['articles'])) {
        recompte_commentaires();
    }
    return $return;
}

/**
 * ajoute tous les dossiers du tableau $dossiers dans une archive zip
 */
function addFolder2zip($zip, $folder)
{
    $ignore = array('.', '..', '.htaccess', 'index.php', '.gitignore');
    if ($handle = opendir($folder)) {
        while ($entry = readdir($handle)) {
            $file = $folder.'/'.$entry;
            if (!in_array($entry, $ignore) && is_readable($file)) {
                if (is_dir($file)) {
                    addFolder2zip($zip, $file);
                    continue;
                }

                // Zip!
                $filename = str_replace(array(BT_ROOT, '//'), array('', '/'), $file);
                $zip->addFile($file, $filename);
            }
        }
        closedir($handle);
    }
}

/**
 *
 */
function creer_fichier_zip($folders)
{
    $zipfile = 'archive_site-'.date('Ymd').'-'.substr(md5(rand(10, 99)), 3, 5).'.zip';
    $zip = new ZipArchive;
    if ($zip->open(DIR_BACKUP.$zipfile, ZipArchive::CREATE) === true) {
        foreach ($folders as $folder) {
            addFolder2zip($zip, $folder);
        }
        $zip->close();
        if (is_file(DIR_BACKUP.$zipfile)) {
            return URL_BACKUP.$zipfile;
        }
    }
    return false;
}

/**
 * fabrique le fichier json (très simple en fait)
 */
function creer_fichier_json($arrData)
{
    $path = 'backup-data-'.date('Ymd-His').'.json';
    return (file_put_contents(DIR_BACKUP.$path, json_encode($arrData), LOCK_EX) === false) ? false : URL_BACKUP.$path;
}

/**
 * Crée la liste des RSS et met tout ça dans un fichier OPML
 */
function creer_fichier_opml()
{
    $path = 'backup-data-'.date('Ymd-His').'.opml';
    // sort feeds by folder
    $folders = array();
    foreach ($GLOBALS['liste_flux'] as $i => $feed) {
        $folders[$feed['folder']][] = $feed;
    }
    ksort($folders);

    $html  = '<?xml version="1.0" encoding="utf-8"?>'."\n";
    $html .= '<opml version="1.0">'."\n";
    $html .= '<head>'."\n";
    $html .= '<title>Newsfeeds '.BLOGOTEXT_NAME.' '.BLOGOTEXT_VERSION.' on '.date('Y/m/d').'</title>'."\n";
    $html .= '</head>'."\n";
    $html .= '<body>'."\n";

    function esc($str)
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    foreach ($folders as $i => $folder) {
        $outline = '';
        foreach ($folder as $feed) {
            $outline .= (($i) ? "\t" : '').'<outline text="'.esc($feed['title']).'" title="'.esc($feed['title']).'" type="rss" xmlUrl="'.esc($feed['link']).'" />'."\n";
        }
        if ($i != '') {
            $html .= '<outline text="'.esc($i).'" title="'.esc($i).'" >'."\n";
            $html .= $outline;
            $html .= '</outline>'."\n";
        } else {
            $html .= $outline;
        }
    }

    $html .= '</body>'."\n".'</opml>'."\n";

    return (file_put_contents(DIR_BACKUP.$path, $html, LOCK_EX) === false) ? false : URL_BACKUP.$path;
}

/**
 * converti un fichier au format xml de wordpress en un tableau (sans enregistrer le fichier bt)
 */
function importer_wordpress($xml)
{
    /* transforms some HTML elements to BlogoText's BBCode */
    function reverse_wiki($texte)
    {
        $tofind = array(
            array('#<blockquote>(.*)</blockquote>#s', '[quote]$1[/quote]'),
            array('#<code>(.*)</code>#s', '[code]$1[/code]'),
            array('#<a href="(.*)">(.*)</a>#', '[$2|$1]'),
            array('#<strong>(.*)</strong>#', '[b]$1[/b]'),
            array('#<em>(.*)</em>#', '[i]$1[/i]'),
            array('#<u>(.*)</u>#', '[u]$1[/u]')
        );
        for ($i = 0, $length = sizeof($tofind); $i < $length; ++$i) {
            $texte = preg_replace($tofind[$i][0], $tofind[$i][1], $texte);
        }
        return $texte;
    }

    /* Transforms BlogoText's BBCode tags to HTML elements. */
    function wiki($texte)
    {
        $texte = ' '.$texte;
        $tofind = array(
            array('#\[quote\](.+?)\[/quote\]#s', '<blockquote>$1</blockquote>'),
            array('#\[code\](.+?)\[/code\]#s', '<code>$1</code>'),
            array('`\[([^[]+)\|([^[]+)\]`', '<a href="$2">$1</a>'),
            array('`\[b\](.*?)\[/b\]`s', '<span style="font-weight: bold;">$1</span>'),
            array('`\[i\](.*?)\[/i\]`s', '<span style="font-style: italic;">$1</span>'),
            array('`\[u\](.*?)\[/u\]`s', '<span style="text-decoration: underline;">$1</span>')
        );
        for ($i = 0, $length = sizeof($tofind); $i < $length; ++$i) {
            $texte = preg_replace($tofind[$i][0], $tofind[$i][1], $texte);
        }
        return $texte;
    }

    $xml = simplexml_load_string($xml);
    $xml = $xml->channel;

    $data = array('liens' => null, 'articles' => null, 'commentaires' => null);

    foreach ($xml->item as $value) {
        $newPost = array();
        $newPost['bt_type'] = 'article';
        $newPost['bt_date'] = date('YmdHis', strtotime($value->pubDate));
        $newPost['bt_id'] = $newPost['bt_date'];
        $newPost['bt_title'] = (string) $value[0]->title;
        $newPost['bt_notes'] = '';
        $newPost['bt_link'] = (string) $value[0]->link;
        $newPost['bt_wiki_content'] = reverse_wiki($value->children('content', true)->encoded);
        $newPost['bt_content'] = wiki($newPost['bt_wiki_content']);
        $newPost['bt_abstract'] = '';
        // get categories
        $newPost['bt_tags'] = '';
        foreach ($value->category as $tag) {
            $newPost['bt_tags'] .= (string) $tag.',';
        }
        $newPost['bt_tags'] = trim($newPost['bt_tags'], ',');
        $newPost['bt_keywords'] = '';
        $newPost['bt_nb_comments'] = 0;
        $newPost['bt_allow_comments'] = (int) $value->children('wp', true)->comment_status == 'open';
        $newPost['bt_statut'] = (int) $value->children("wp", true)->status == 'publish';
        // parse comments
        foreach ($value->children('wp', true)->comment as $comment) {
            $newComment = array();
            $newComment['bt_author'] = (string) $comment[0]->comment_author;
            $newComment['bt_link'] = '';
            $newComment['bt_webpage'] = (string) $comment[0]->comment_author_url;
            $newComment['bt_email'] = (string) $comment[0]->comment_author_email;
            $newComment['bt_subscribe'] = 0;
            $newComment['bt_type'] = 'comment';
            $newComment['bt_id'] = date('YmdHis', strtotime($comment->comment_date));
            $newComment['bt_article_id'] = $newPost['bt_id'];
            $newComment['bt_wiki_content'] = reverse_wiki($comment->comment_content);
            $newComment['bt_content'] = '<p>'.wiki($newComment['bt_wiki_content']).'</p>';
            $newComment['bt_statut'] = (int) $comment->comment_approved;
            $data['commentaires'][] = $newComment;
        }
        $data['articles'][] = $newPost;
    }

    $return = array();
    // importer les articles
    if (!empty($data['articles'])) {
        $return['articles'] = insert_table_articles($data['articles']);
    }
    // importer les commentaires
    if (!empty($data['commentaires'])) {
        $return['commentaires'] = insert_table_commentaires($data['commentaires']);
    }
    // recompter les commentaires
    if (!empty($data['commentaires']) or !empty($data['articles'])) {
        recompte_commentaires();
    }

    return $return;
}

/**
 * Parse et importe un fichier de liste de flux OPML
 */
function importer_opml($opmlContent)
{
    $GLOBALS['array_new'] = array();

    function parseOpmlRecursive($xmlObj)
    {
        // si c’est un sous dossier avec d’autres flux à l’intérieur : note le nom du dossier
        $folder = $xmlObj->attributes()->text;
        foreach ($xmlObj->children() as $child) {
            if (!empty($child['xmlUrl'])) {
                $url = (string)$child['xmlUrl'];
                $title = (!empty($child['text'])) ? (string) $child['text'] : (string) $child['title'];
                $GLOBALS['array_new'][$url] = array(
                    'link' => $url,
                    'title' => ucfirst($title),
                    'favicon' => 'style/rss-feed-icon.png',
                    'checksum' => 0,
                    'time' => 0,
                    'folder' => (string) $folder,
                    'iserror' => 0,
                );
            }
            parseOpmlRecursive($child);
        }
    }
    $opmlFile = new SimpleXMLElement($opmlContent);
    parseOpmlRecursive($opmlFile->body);

    $oldLen = count($GLOBALS['liste_flux']);
    $GLOBALS['liste_flux'] = array_reverse(tri_selon_sous_cle($GLOBALS['liste_flux'], 'title'));
    $GLOBALS['liste_flux'] = array_merge($GLOBALS['array_new'], $GLOBALS['liste_flux']);
    create_file_dtb(FEEDS_DB, $GLOBALS['liste_flux']);

    return (count($GLOBALS['liste_flux']) - $oldLen);
}

/**
 * Parse and import HTML bookmarks (netscape/Firefox bookmarks export)
 */
function parse_html($content)
{
    $arrOut = array();
    // Netscape bookmark file (Firefox).
    if (strcmp(substr($content, 0, strlen('<!DOCTYPE NETSCAPE-Bookmark-file-1>')), '<!DOCTYPE NETSCAPE-Bookmark-file-1>') === 0) {
        // This format is supported by all browsers (except IE, of course), also delicious, diigo and others.
        $arrId = array();
        $allDtTags = explode('<DT>', $content);
        foreach ($allDtTags as $dt) {
            $link = array('bt_id' => '', 'bt_title' => '', 'bt_link' => '', 'bt_content' => '', 'bt_wiki_content' => '', 'bt_tags' => '', 'bt_statut' => 1, 'bt_type' => 'link');
            $d = explode('<DD>', $dt);
            if (strcmp(substr($d[0], 0, strlen('<A ')), '<A ') === 0) {
                $link['bt_content'] = (isset($d[1])) ? html_entity_decode(trim($d[1]), ENT_QUOTES, 'utf-8') : '';  // Get description (optional)
                $link['bt_wiki_content'] = $link['bt_content'];
                preg_match('!<A .*?>(.*?)</A>!i', $d[0], $matches);
                $link['bt_title'] = (isset($matches[1])) ? trim($matches[1]) : '';  // Get title
                $link['bt_title'] = html_entity_decode($link['bt_title'], ENT_QUOTES, 'utf-8');
                preg_match_all('# ([A-Z_]+)=\"(.*?)"#i', $dt, $matches, PREG_SET_ORDER); // Get all other attributes
                $rawAddDate = 0;
                foreach ($matches as $m) {
                    $attr = $m[1];
                    $value = $m[2];
                    if ($attr == 'HREF') {
                        $link['bt_link'] = html_entity_decode($value, ENT_QUOTES, 'utf-8');
                    } elseif ($attr == 'ADD_DATE') {
                        $rawAddDate = intval($value);
                    } elseif ($attr == 'PRIVATE') {
                        $link['bt_statut'] = ($value == 1) ? 0 : 1;
                    } // value=1 =>> statut=0 (it’s reversed)
                    elseif ($attr == 'TAGS') {
                        $link['bt_tags'] = str_replace('  ', ' ', str_replace(',', ', ', html_entity_decode($value, ENT_QUOTES, 'utf-8')));
                    }
                }
                if ($link['bt_link'] != '') {
                    $rawAddDate = (empty($rawAddDate)) ? time() : $rawAddDate; // In case of shitty bookmark file with no ADD_DATE
                    while (in_array(date('YmdHis', $rawAddDate), $arrId)) {
                        $rawAddDate--; // avoids duplicate IDs
                    }
                    $arrId[] = $link['bt_id'] = date('YmdHis', $rawAddDate); // converts date to YmdHis format
                    $arrOut[] = $link;
                }
            }
        }
    }
    return $arrOut;
}


/**
 * process
 */

$GLOBALS['liste_fichiers'] = open_serialzd_file(FILES_DB);
$GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);


/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['titre_maintenance']);

echo '<div id="header">';
    echo '<div id="top">';
    tpl_show_msg();
    echo tpl_show_topnav('preferences.php', $GLOBALS['lang']['titre_maintenance']);
    echo '</div>';
echo '</div>';

echo '<div id="axe">';
echo '<div id="page">';

// création du dossier des backups
create_folder(DIR_BACKUP, 0);


/*
 * Affiches les formulaires qui demandent quoi faire. (!isset($do))
 * Font le traitement dans les autres cas.
*/

// no $do nor $file : ask what to do
echo '<div id="maintenance-form">';
if (!isset($_GET['do']) and !isset($_FILES['file'])) {
    $token = new_token();
    $nbs = array(10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, -1 => $GLOBALS['lang']['pref_all']);

    echo '<form action="maintenance.php" method="get" class="bordered-formbloc" id="form_todo">';
    echo '<label for="select_todo">'.$GLOBALS['lang']['maintenance_ask_do_what'].' </label>';
    echo '<select id="select_todo" name="select_todo" onchange="switch_form(this.value)">';
    echo '<option selected disabled hidden value=""></option>';
    echo '<option value="form_export">'.$GLOBALS['lang']['maintenance_export'].'</option>';
    echo '<option value="form_import">'.$GLOBALS['lang']['maintenance_import'].'</option>';
    echo '<option value="form_optimi">'.$GLOBALS['lang']['maintenance_optim'].'</option>';
    echo '</select>';
    echo '</form>';

    // Form export
    echo '<form action="maintenance.php" onsubmit="hide_forms(\'exp-format\')" method="get" class="bordered-formbloc" id="form_export">';
    // choose export what ?
        echo '<fieldset>';
        echo '<legend class="legend-backup">'.$GLOBALS['lang']['maintenance_export'].'</legend>';
        echo '<p><label for="json">'.$GLOBALS['lang']['bak_export_json'].'</label>'.
            '<input type="radio" name="exp-format" value="json" id="json" onchange="switch_export_type(\'e_json\')" /></p>';
        echo '<p><label for="html">'.$GLOBALS['lang']['bak_export_netscape'].'</label>'.
            '<input type="radio" name="exp-format" value="html" id="html" onchange="switch_export_type(\'e_html\')" /></p>';
        echo '<p><label for="zip">'.$GLOBALS['lang']['bak_export_zip'].'</label>'.
            '<input type="radio" name="exp-format" value="zip"  id="zip"  onchange="switch_export_type(\'e_zip\')"  /></p>';
        echo '<p><label for="opml">'.$GLOBALS['lang']['bak_export_opml'].'</label>'.
            '<input type="radio" name="exp-format" value="opml"  id="opml"  onchange="switch_export_type(\'e_opml\')"  /></p>';
        echo '</fieldset>';
        // export in JSON.
        echo '<fieldset id="e_json">';
        echo '<legend class="legend-backup">'.$GLOBALS['lang']['maintenance_incl_quoi'].'</legend>';
        echo '<p>'.select_yes_no('incl-artic', 0, $GLOBALS['lang']['bak_articles_do']).form_select_no_label('nb-artic', $nbs, 50).'</p>';
        echo '<p>'.select_yes_no('incl-comms', 0, $GLOBALS['lang']['bak_comments_do']).'</p>';
        echo '<p>'.select_yes_no('incl-links', 0, $GLOBALS['lang']['bak_links_do']).form_select_no_label('nb-links', $nbs, 50).'</p>';
        echo '</fieldset>';
        // export links in html
        echo '<fieldset id="e_html">';
        echo '<legend class="legend-backup">'.$GLOBALS['lang']['bak_combien_linx'].'</legend>';
        echo '<p>'.form_select('nb-links2', $nbs, 50, $GLOBALS['lang']['bak_combien_linx']).'</p>';
        echo '</fieldset>';
        // export data in zip
        echo '<fieldset id="e_zip">';
        echo '<legend class="legend-backup">'.$GLOBALS['lang']['maintenance_incl_quoi'].'</legend>';
    if (DBMS == 'sqlite') {
        echo '<p>'.select_yes_no('incl-sqlit', 0, $GLOBALS['lang']['bak_incl_sqlit']).'</p>';
    }
        echo '<p>'.select_yes_no('incl-files', 0, $GLOBALS['lang']['bak_incl_files']).'</p>';
        echo '<p>'.select_yes_no('incl-confi', 0, $GLOBALS['lang']['bak_incl_confi']).'</p>';
        echo '<p>'.select_yes_no('incl-theme', 0, $GLOBALS['lang']['bak_incl_theme']).'</p>';
        echo '</fieldset>';
        echo '<p class="submit-bttns">';
        echo '<button class="submit button-cancel" type="button" onclick="annuler(\'maintenance.php\');">'.$GLOBALS['lang']['annuler'].'</button>';
        echo '<button class="submit button-submit" type="submit" name="do" value="export">'.$GLOBALS['lang']['valider'].'</button>';
        echo '</p>';
        echo hidden_input('token', $token);
    echo '</form>';

    // Form import
    $importformats = array(
        'jsonbak' => $GLOBALS['lang']['bak_import_btjson'],
        'xmlwp' => $GLOBALS['lang']['bak_import_wordpress'],
        'htmllinks' => $GLOBALS['lang']['bak_import_netscape'],
        'rssopml' => $GLOBALS['lang']['bak_import_rssopml'] );
    echo '<form action="maintenance.php" method="post" enctype="multipart/form-data" class="bordered-formbloc" id="form_import">';
        echo '<fieldset class="pref valid-center">';
        echo '<legend class="legend-backup">'.$GLOBALS['lang']['maintenance_import'].'</legend>';
        echo '<p>'.form_select_no_label('imp-format', $importformats, 'jsonbak');
        echo '<input type="file" name="file" id="file" class="text" /></p>';
        echo '</fieldset>';
        echo '<p class="submit-bttns">';
        echo '<button class="submit button-cancel" type="button" onclick="annuler(\'maintenance.php\');">'.$GLOBALS['lang']['annuler'].'</button>';
        echo '<button class="submit button-submit" type="submit" name="valider">'.$GLOBALS['lang']['valider'].'</button>';
        echo '</p>';

        echo hidden_input('token', $token);
    echo '</form>';

    // Form optimi
    echo '<form action="maintenance.php" method="get" class="bordered-formbloc" id="form_optimi">';
        echo '<fieldset class="pref valid-center">';
        echo '<legend class="legend-sweep">'.$GLOBALS['lang']['maintenance_optim'].'</legend>';

        echo '<p>'.select_yes_no('opti-file', 0, $GLOBALS['lang']['bak_opti_miniature']).'</p>';
    if (DBMS == 'sqlite') {
        echo '<p>'.select_yes_no('opti-vacu', 0, $GLOBALS['lang']['bak_opti_vacuum']).'</p>';
    } else {
        echo hidden_input('opti-vacu', 0);
    }
        echo '<p>'.select_yes_no('opti-comm', 0, $GLOBALS['lang']['bak_opti_recountcomm']).'</p>';

        echo '<p>'.select_yes_no('opti-rss', 0, $GLOBALS['lang']['bak_opti_supprreadrss']).'</p>';

        echo '</fieldset>';
        echo '<p class="submit-bttns">';
        echo '<button class="submit button-cancel" type="button" onclick="annuler(\'maintenance.php\');">'.$GLOBALS['lang']['annuler'].'</button>';
        echo '<button class="submit button-submit" type="submit" name="do" value="optim">'.$GLOBALS['lang']['valider'].'</button>';
        echo '</p>';
        echo hidden_input('token', $token);
    echo '</form>';

// either $do or $file
// $do
} else {
    // vérifie Token
    if ($errorsForm = validate_form_maintenance()) {
        echo '<div class="bordered-formbloc">';
        echo '<fieldset class="pref valid-center">';
        echo '<legend class="legend-backup">'.$GLOBALS['lang']['bak_restor_done'].'</legend>';
        echo erreurs($errorsForm);
        echo '<p class="submit-bttns"><button class="submit button-submit" type="button" onclick="annuler(\'maintenance.php\')">'.$GLOBALS['lang']['valider'].'</button></p>';
        echo '</fieldset>';
        echo '</div>';
    } else {
        // token : ok, go on !
        if (isset($_GET['do'])) {
            if ($_GET['do'] == 'export') {
                $format = (!empty($_GET['exp-format'])) ? $_GET['exp-format'] : '';
                // Export in JSON file
                if ($format == 'json') {
                    $arrData = array('articles' => array(), 'liens' => array(), 'commentaires' => array());
                    // list links (nth last)
                    if ($_GET['incl-links'] == 1) {
                        $nb = htmlspecialchars($_GET['nb-links']);
                        $limit = (is_numeric($nb) and $nb != -1 ) ? 'LIMIT 0, ?' : '';
                        $array = (empty($limit)) ? array() : array($nb);
                        $sql = '
                            SELECT *
                              FROM links
                             ORDER BY bt_id DESC '.
                             $limit;
                        $arrData['liens'] = liste_elements($sql, $array, 'links');
                    }
                    // get articles (nth last)
                    if ($_GET['incl-artic'] == 1) {
                        $nb = htmlspecialchars($_GET['nb-artic']);
                        $limit = (is_numeric($nb) and $nb != -1 ) ? 'LIMIT 0, ?' : '';
                        $array = (empty($limit)) ? array() : array($nb);
                        $sql = '
                            SELECT *
                              FROM articles
                             ORDER BY bt_id DESC '.
                             $limit;
                        $arrData['articles'] = liste_elements($sql, $array, 'articles');
                        // get list of comments (comments that belong to selected articles only)
                        if ($_GET['incl-comms'] == 1) {
                            foreach ($arrData['articles'] as $article) {
                                $sql = '
                                    SELECT c.*, a.bt_title
                                      FROM commentaires AS c, articles AS a
                                     WHERE c.bt_article_id = ?
                                           AND c.bt_article_id = a.bt_id';
                                $comments = liste_elements($sql, array($article['bt_id']), 'commentaires');
                                if (!empty($comments)) {
                                    $arrData['commentaires'] = array_merge($arrData['commentaires'], $comments);
                                }
                            }
                        }
                    }
                    $file_archive = creer_fichier_json($arrData);

                // Export links in HTML format
                } elseif ($format == 'html') {
                    $nb = htmlspecialchars($_GET['nb-links2']);
                    $limit = (is_numeric($nb) and $nb != -1 ) ? $nb : '';
                    $file_archive = create_html_favs($limit);

                // Export a ZIP archive
                } elseif ($format == 'zip') {
                    $dossiers = array();
                    $sqlite = (!empty($_GET['incl-sqlit'])) ? $_GET['incl-sqlit'] + 0 : 0;
                    if ($sqlite == 1) {
                        $dossiers[] = DIR_DATABASES;
                    }
                    if ($_GET['incl-files'] == 1) {
                        $dossiers[] = DIR_DOCUMENTS;
                        $dossiers[] = DIR_IMAGES;
                    }
                    if ($_GET['incl-confi'] == 1) {
                        $dossiers[] = DIR_CONFIG;
                    }
                    if ($_GET['incl-theme'] == 1) {
                        $dossiers[] = DIR_THEMES;
                    }
                    $file_archive = creer_fichier_zip($dossiers);

                // Export a OPML rss lsit
                } elseif ($format == 'opml') {
                    $file_archive = creer_fichier_opml();
                } else {
                    echo 'nothing to do';
                }

                // affiche le formulaire de téléchargement et de validation.
                if (!empty($file_archive)) {
                    echo '<form action="maintenance.php" method="get" class="bordered-formbloc">';
                    echo '<fieldset class="pref valid-center">';
                    echo '<legend class="legend-backup">'.$GLOBALS['lang']['bak_succes_save'].'</legend>';

                    echo '<p><a href="'.$file_archive.'" download>'.$GLOBALS['lang']['bak_dl_fichier'].'</a></p>';
                    echo '<p class="submit-bttns"><button class="submit button-submit" type="submit">'.$GLOBALS['lang']['valider'].'</button></p>';
                    echo '</fieldset>';
                    echo '</form>';
                }
            } elseif ($_GET['do'] == 'optim') {
                    // recount files DB
                if ($_GET['opti-file'] == 1) {
                    rebuilt_file_db();
                }
                    // vacuum SQLite DB
                if ($_GET['opti-vacu'] == 1) {
                    try {
                        $req = $GLOBALS['db_handle']->prepare('VACUUM');
                        $req->execute();
                    } catch (Exception $e) {
                        die('Erreur 1429 vacuum : '.$e->getMessage());
                    }
                }
                    // recount comms/articles
                if ($_GET['opti-comm'] == 1) {
                    recompte_commentaires();
                }
                    // delete old RSS entries
                if ($_GET['opti-rss'] == 1) {
                    try {
                        $req = $GLOBALS['db_handle']->prepare('DELETE FROM rss WHERE bt_statut = 0');
                        $req->execute(array());
                    } catch (Exception $e) {
                        die('Erreur : 7873 : rss delete old entries : '.$e->getMessage());
                    }
                }
                    echo '<form action="maintenance.php" method="get" class="bordered-formbloc">';
                    echo '<fieldset class="pref valid-center">';
                    echo '<legend class="legend-backup">'.$GLOBALS['lang']['bak_optim_done'].'</legend>';
                    echo '<p class="submit-bttns"><button class="submit button-submit" type="submit">'.$GLOBALS['lang']['valider'].'</button></p>';
                    echo '</fieldset>';
                    echo '</form>';
            } else {
                echo 'nothing to do.';
            }

        // $file
        } elseif (isset($_POST['valider']) and !empty($_FILES['file']['tmp_name'])) {
                $message = array();
            switch ($_POST['imp-format']) {
                case 'jsonbak':
                    $json = file_get_contents($_FILES['file']['tmp_name']);
                    $message = importer_json($json);
                    break;
                case 'htmllinks':
                    $html = file_get_contents($_FILES['file']['tmp_name']);
                    $message['links'] = insert_table_links(parse_html($html));
                    break;
                case 'xmlwp':
                    $xml = file_get_contents($_FILES['file']['tmp_name']);
                    $message = importer_wordpress($xml);
                    break;
                case 'rssopml':
                    $xml = file_get_contents($_FILES['file']['tmp_name']);
                    $message['feeds'] = importer_opml($xml);
                    break;
                default:
                    die('nothing');
                break;
            }
            if (!empty($message)) {
                echo '<form action="maintenance.php" method="get" class="bordered-formbloc">';
                echo '<fieldset class="pref valid-center">';
                echo '<legend class="legend-backup">'.$GLOBALS['lang']['bak_restor_done'].'</legend>';
                echo '<ul>';
                foreach ($message as $type => $nb) {
                    echo '<li>'.$GLOBALS['lang']['label_'.$type].' : '.$nb.'</li>';
                }
                echo '</ul>';
                echo '<p class="submit-bttns"><button class="submit button-submit" type="submit">'.$GLOBALS['lang']['valider'].'</button></p>';
                echo '</fieldset>';
                echo '</form>';
            }
        } else {
            echo 'nothing to do.';
        }
    }
}

echo '</div>';

echo <<<EOS
<script src="style/javascript.js"></script>
<script>
    var ia = document.getElementById("incl-artic");
    if (ia) ia.addEventListener("change", function() {
        document.getElementById("nb-artic").style.display = (ia.value == 1 ? "inline-block" : "none");
    });

    var il = document.getElementById("incl-links");
    if (il) il.addEventListener("change", function() {
        document.getElementById("nb-links").style.display = (il.value == 1 ? "inline-block" : "none");
    });
</script>
EOS;

echo tpl_get_footer($begin);
