<?php
// gzip compression
if (extension_loaded('zlib')) {
	ob_end_clean();
	ob_start("ob_gzhandler");
}
else {
	ob_start("ob_gzhandler");
}

header("Content-type: text/css; charset: UTF-8");

/* FOR MAINTENANCE : CSS FILES ARE SPLITED IN MULTIPLE FILES
-------------------------------------------------------------*/

/* Résumé page : for the graphs and the thumbnails */
readfile('style-graphs.css');

/* Écrire page : for the new article form page */
readfile('style-ecrire.css');

/* Images and files : miniatures blocs + formulaires */
readfile('style-miniatures-files.css');

/* Page des liens : formulaire + blocs. */
readfile('style-liens.css');

/* Page d’authentification */
readfile('style-auth.css');

/* Page des commentaires : formualires + blocs */
readfile('style-commentaires.css');

/* Page des articles : liste sous la forme d’un tableau */
readfile('style-articles.css');

/* Page des flux RSS */
readfile('style-rss.css');

/* Page de styles plus généraux */
readfile('style-style.css');

/* Page de styles plus mobile < 850px de large */
readfile('style-mobile-lt850px.css');

/* Page de styles plus mobile < 700px de large */
readfile('style-mobile-lt700px.css');

