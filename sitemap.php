<?php

require_once 'inc/boot.php';

$GLOBALS['db_handle'] = open_base();

header('Content-Type: text/xml; charset=UTF-8');

echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

$priority = 1.0;

// Set main URL
echo "    <url>\n";
echo "        <loc>".$GLOBALS['racine']."</loc>\n";
echo "        <changefreq>weekly</changefreq>\n";
echo "        <priority>".$priority."</priority>\n";
echo "    </url>\n";

$query = 'SELECT bt_date,bt_id,bt_title,bt_link FROM articles WHERE bt_date <= '.date('YmdHis').' AND bt_statut=1 ORDER BY bt_date DESC';
$tableau = liste_elements($query, array(), 'articles');

if ($tableau) {
    foreach ($tableau as $e) {
        $short_date = substr($e['bt_date'], 0, 4).'/'.substr($e['bt_date'], 4, 2).'/'.substr($e['bt_date'], 6, 2);
        // Set main URL
        echo "    <url>\n";
        echo "        <loc>".$e['bt_link']."</loc>\n";
        echo "        <lastmod>".$short_date."</lastmod>\n";
        echo "        <changefreq>monthly</changefreq>\n";
        echo "        <priority>".$priority."</priority>\n";
        echo "    </url>\n";
        
        if ($priority > 0.5) {
            $priority -= 0.05;
        }
    }
}

echo '</urlset>';

echo $XML;
