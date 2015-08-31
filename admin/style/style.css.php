<?php
// gzip compression
if (extension_loaded('zlib') and ob_get_length() > 0) {
	ob_end_clean();
	ob_start("ob_gzhandler");
}
else {
	ob_start("ob_gzhandler");
}

header("Content-type: text/css; charset: UTF-8");

/* FOR MAINTENANCE : CSS FILES ARE SPLITED IN MULTIPLE FILES
-------------------------------------------------------------*/

echo '/* Page de styles plus généraux */'."\n";
readfile('style-style.css');

echo '/* Page d’authentification */'."\n";
readfile('style-auth.css');

echo '/* Résumé page : for the graphs and the thumbnails */'."\n";
readfile('style-graphs.css');

echo '/* Page des articles : liste sous la forme d’un tableau */'."\n";
readfile('style-articles.css');

echo '/* Écrire page : for the new article form page */'."\n";
readfile('style-ecrire.css');

echo '/* Page des commentaires : formualires + blocs */'."\n";
readfile('style-commentaires.css');

echo '/* Images and files : miniatures blocs + formulaires */'."\n";
readfile('style-miniatures-files.css');

echo '/* Page des liens : formulaire + blocs. */'."\n";
readfile('style-liens.css');

echo '/* Page des flux RSS */'."\n";
readfile('style-rss.css');

echo '/* Page des préférences et de maintenance */'."\n";
readfile('style-preferences.css');

echo '/* Page de styles plus mobile < 1100px de large */'."\n";
readfile('style-mobile-lt1100px.css');

echo '/* Page de styles plus mobile < 850px de large */'."\n";
readfile('style-mobile-lt850px.css');

echo '/* Page de styles plus mobile < 700px de large */'."\n";
readfile('style-mobile-lt700px.css');

