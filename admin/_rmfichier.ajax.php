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


/*
    This file is called by the files. It is an underground working script,
    It is not intended to be called directly in your browser.
*/

$GLOBALS['liste_fichiers'] = open_serialzd_file(FILES_DB);
$fileId = filter_input(INPUT_POST, 'file_id');
$deletion = (filter_input(INPUT_POST, 'supprimer') !== null);

if ($fileId && preg_match('#^\d{14}$#', $fileId) && $deletion) {
    foreach ($GLOBALS['liste_fichiers'] as $file) {
        if ($file['bt_id'] == $fileId) {
            die(bdd_fichier($file, 'supprimer-existant', '', $file['bt_id']));
        }
    }
}
die('failure');
