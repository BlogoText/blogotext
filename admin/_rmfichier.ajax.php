<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2015 Timo Van Neerden.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

/*
    This file is called by the files. It is an underground working script,
    It is not intended to be called directly in your browser.
*/

define('BT_ROOT', '../');

require_once '../inc/inc.php';

operate_session();
$begin = microtime(true);

$GLOBALS['liste_fichiers'] = open_serialzd_file(FILES_DB);


if (isset($_POST['file_id']) and preg_match('#\d{14}#', ($_POST['file_id'])) and isset($_POST['supprimer'])) {
    foreach ($GLOBALS['liste_fichiers'] as $fich) {
        if ($fich['bt_id'] == $_POST['file_id']) {
            $fichier = $fich;
            break;
        }
    }
    if (!empty($fichier)) {
        $retour = bdd_fichier($fichier, 'supprimer-existant', '', $fichier['bt_id']);
        echo $retour;
        exit;
    }
}
echo 'failure';
exit;
