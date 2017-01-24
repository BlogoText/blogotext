<?php
// gzip compression
if (extension_loaded('zlib') and ob_get_length() > 0) {
    ob_end_clean();
    ob_start('ob_gzhandler');
} else {
    ob_start('ob_gzhandler');
}

header('Content-type: text/css; charset: UTF-8');

/* FOR MAINTENANCE : CSS FILES ARE SPLITED IN MULTIPLE FILES
-------------------------------------------------------------*/

echo '/* General styles (layout, forms, multi-pages elements…) */'."\n";
readfile('style-style.css');

echo '/* Auth page */'."\n";
readfile('style-auth.css');

echo '/* Home page, with graphs */'."\n";
readfile('style-graphs.css');

echo '/* Article lists page */'."\n";
readfile('style-articles.css');

echo '/* Write page: new article form */'."\n";
readfile('style-ecrire.css');

echo '/* Comments page: forms+comm list */'."\n";
readfile('style-commentaires.css');

echo '/* Images and files: form + listing */'."\n";
readfile('style-miniatures-files.css');

echo '/* Links page: form + listing. */'."\n";
readfile('style-liens.css');

echo '/* RSS page: listing + forms */'."\n";
readfile('style-rss.css');

echo '/* Prefs + maintainance pages */'."\n";
readfile('style-preferences.css');

echo '/* Add-ons managing page */'."\n";
readfile('style-addons.css');

echo '/* Media-queries < 1100px */'."\n";
readfile('style-mobile-lt1100px.css');

echo '/* Media-queries < 850px */'."\n";
readfile('style-mobile-lt850px.css');

echo '/* Media-queries < 700px */'."\n";
readfile('style-mobile-lt700px.css');

if (is_file('../../config/custom-styles.css')) {
    echo '/* User-Custom CSS */'."\n";
    readfile('../../config/custom-styles.css');
}
