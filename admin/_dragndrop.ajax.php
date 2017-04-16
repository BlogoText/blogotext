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
    This file is called by the drag'n'drop script. It is an underground working script,
    It is not intended to be called directly in your browser.
*/

$files = array();
$GLOBALS['liste_fichiers'] = open_serialzd_file(FILES_DB);
$token = (string)filter_input(INPUT_POST, 'token');
$json = '{ "url": "%s", "status": "%s", "token": "%s" }';

foreach ($GLOBALS['liste_fichiers'] as $key => $file) {
    $files[] = $file['bt_id'];
}

if (isset($_FILES['fichier'])) {
    $time = time();
    $file = init_post_fichier();

    // avoid ID collisions
    while (in_array($file['bt_id'], $files)) {
        $time--;
        $file['bt_id'] = date('YmdHis', $time);
    }
    $errors = valider_form_fichier($file);

    if (!$errors) {
        $newFile = bdd_fichier($file, 'ajout-nouveau', 'upload', $_FILES['fichier']);
        $file = ($newFile === null) ? $file : $newFile;
        die(printf($json, 'fichiers.php?file_id='.$file['bt_id'].'&amp;edit', 'success', new_token()));
    }
} elseif ($token && check_token($token)) {
    // If the file is not sent (size limit from the JS side for instance)
    // but the token is good, we keep going with next files.
    die(printf($json, 0, 'failure', new_token()));
}

// Problem with file and token: abort, Captain, my Captain!!
die(printf($json, 0, 'failure', 0));
