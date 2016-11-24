<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BoboTiG/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***

/**
 * can be used by addon
 *
 * used in : inc/addons.php
 *           inc/boot.php
 *           inc/filesystem.php
 *           inc/imgs.php
 *           inc/sql.php
 * ..
 */
function create_folder($dossier, $make_htaccess = false, $recursive = false)
{
    if (is_dir($dossier)) {
        return true;
    }
    if (mkdir($dossier, 0777, $recursive)) {
        fichier_index($dossier); // file index.html to prevent directory listing
        if ($make_htaccess) {
            fichier_htaccess($dossier); // to prevent direct access to files
        }
        return true;
    }
    return false;
}

function fichier_index($dossier)
{
    $content = '<html>'."\n";
    $content .= "\t".'<head>'."\n";
    $content .= "\t\t".'<title>Access denied</title>'."\n";
    $content .= "\t".'</head>'."\n";
    $content .= "\t".'<body>'."\n";
    $content .= "\t\t".'<a href="/">Retour a la racine du site</a>'."\n";
    $content .= "\t".'</body>'."\n";
    $content .= '</html>';
    $index_html = $dossier.'/index.html';

    return file_put_contents($index_html, $content) !== false;
}

function fichier_htaccess($dossier)
{
    $content = '<Files *>'."\n";
    $content .= 'Order allow,deny'."\n";
    $content .= 'Deny from all'."\n";
    $content .= '</Files>'."\n";
    $htaccess = $dossier.'/.htaccess';

    return file_put_contents($htaccess, $content) !== false;
}

function flux_refresh_cache_lv1()
{
    create_folder(DIR_CACHE, 1);
    $arr_a = liste_elements("SELECT * FROM articles WHERE bt_statut=1 ORDER BY bt_date DESC LIMIT 0, 20", array(), 'articles');
    $arr_c = liste_elements("SELECT c.*, a.bt_title FROM commentaires AS c, articles AS a WHERE c.bt_statut=1 AND c.bt_article_id=a.bt_id ORDER BY c.bt_id DESC LIMIT 0, 20", array(), 'commentaires');
    $arr_l = liste_elements("SELECT * FROM links WHERE bt_statut=1 ORDER BY bt_id DESC LIMIT 0, 20", array(), 'links');
    $file = DIR_CACHE.'/'.'cache_rss_array.dat';
    return file_put_contents($file, '<?php /* '.chunk_split(base64_encode(serialize(array('c' => $arr_c, 'a' => $arr_a, 'l' => $arr_l)))).' */');
}
