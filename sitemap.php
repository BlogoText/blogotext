<?php

require_once 'inc/boot.php';

// sitemap cache
$sitemap_cache = DIR_VHOST_CACHE.'sitemap.xml';

// set 2 hours cache, must be replace by an hook or something else...
if (file_exists($sitemap_cache) && filemtime($sitemap_cache) > time()-(7200)) {
    $cached = file_get_contents($sitemap_cache);
    if ($cached !== false) {
        header('Content-Type: text/xml; charset=UTF-8');
        echo $cached;
        exit();
    }
}


// dependancy
require_once BT_ROOT.'inc/addons.php';

// launch addons
addons_init_public();
// launch hook
hook_trigger('system-start');

$GLOBALS['db_handle'] = open_base();

header('Content-Type: text/xml; charset=UTF-8');

$xml = '';

$xml .= '<?xml version="1.0" encoding="utf-8" ?>'."\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

$priority = 1.0;

// Set main URL
$xml .= '  <url>'."\n";
$xml .= '    <loc>'.$GLOBALS['racine'].'</loc>'."\n";
$xml .= '    <changefreq>weekly</changefreq>'."\n";
$xml .= '    <priority>'.$priority.'</priority>'."\n";
$xml .= '  </url>'."\n";

$query = 'SELECT bt_date,bt_id,bt_title,bt_link FROM articles WHERE bt_date <= '.date('YmdHis').' AND bt_statut=1 ORDER BY bt_date DESC';
$tableau = liste_elements($query, array(), 'articles');

// set hook
$tmp_hook = hook_trigger_and_check('sitemap_datas', $tableau);
if ($tmp_hook !== false) {
    $tableau = $tmp_hook['1'];
}

if ($tableau) {
    foreach ($tableau as $e) {
        $short_date = substr($e['bt_date'], 0, 4).'/'.substr($e['bt_date'], 4, 2).'/'.substr($e['bt_date'], 6, 2);
        // Set main URL
        $xml .= '  <url>'."\n";
        $xml .= '    <loc>'.$e['bt_link'].'</loc>'."\n";
        $xml .= '    <lastmod>'.$short_date.'</lastmod>'."\n";
        $xml .= '    <changefreq>monthly</changefreq>'."\n";
        $xml .= '    <priority>'.$priority.'</priority>'."\n";
        $xml .= '  </url>'."\n";

        if ($priority > 0.5) {
            $priority -= 0.05;
        }
    }
}

$xml .= '</urlset>';

if (create_folder(DIR_VHOST_CACHE)) {
    file_put_contents($sitemap_cache, $xml);
}

echo $xml;
