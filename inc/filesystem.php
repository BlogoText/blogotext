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
    if (mkdir($dossier, 0755, $recursive)) {
        create_index_file($dossier);
        if ($make_htaccess) {
            create_htaccess($dossier);
        }
        return true;
    }
    return false;
}

/**
 * Prevent directory listing.
 */
function create_index_file($folder)
{
    $content = "<?php\nexit(header('Location: ../'));\n";
    $file = $folder.'/index.php';

    return file_put_contents($file, $content) !== false;
}


/**
 * Prevent direct access to files.
 */
function create_htaccess($folder)
{
    $content = '<Files *>'."\n";
    $content .= 'Order allow,deny'."\n";
    $content .= 'Deny from all'."\n";
    $content .= '</Files>'."\n";
    $file = $folder.'/.htaccess';

    return file_put_contents($file, $content) !== false;
}


function flux_refresh_cache_lv1()
{
    create_folder(DIR_CACHE, 1);
    $arr_a = liste_elements("SELECT * FROM articles WHERE bt_statut=1 ORDER BY bt_date DESC LIMIT 0, 20", array(), 'articles');
    $arr_c = liste_elements("SELECT c.*, a.bt_title FROM commentaires AS c, articles AS a WHERE c.bt_statut=1 AND c.bt_article_id=a.bt_id ORDER BY c.bt_id DESC LIMIT 0, 20", array(), 'commentaires');
    $arr_l = liste_elements("SELECT * FROM links WHERE bt_statut=1 ORDER BY bt_id DESC LIMIT 0, 20", array(), 'links');
    return create_file_dtb(DIR_CACHE.'cache_rss_array.dat', array('c' => $arr_c, 'a' => $arr_a, 'l' => $arr_l));
}
