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

define('BT_RUN_LOGIN', 1);
require_once 'inc/boot.php';


/**
 * process
 */

if (auth_check_session()) {
    // Return to index if session already opened
    redirection('index.php');
}

usleep(200000);  // avoid bruteforce
$username = (string)filter_input(INPUT_POST, 'nom_utilisateur');
$password = (string)filter_input(INPUT_POST, 'mot_de_passe');
$check = (filter_input(INPUT_POST, '_verif_envoi') !== null);
$stayLogged = (filter_input(INPUT_POST, 'stay_logged') !== null);
//$maxAttempts = 6;  // max attempts before blocking login page
//$banTime = 30;  // time to wait before unblocking login page, in minutes

// Auth checking
if ($check) {
    if (auth_is_valid($username, $password)) {
        $userId = uuid();
        $_SESSION['user_id'] = $userId;

        if ($stayLogged) {
            $session_restart = (session_status() === PHP_SESSION_ACTIVE);
            if ($session_restart) {
                $saved = $_SESSION;
                session_destroy();
            }
            // If user wants to stay logged
            session_set_cookie_params(365 * 24 * 60 * 60);
            setcookie('BT-admin-stay-logged', $userId, time() + 365 * 24 * 60 * 60, null, null, false, true);
            if ($session_restart) {
                session_start();
                $_SESSION = $saved;
            }
        } else {
            $_SESSION['stay_logged_mode'] = 0;
            session_regenerate_id(true);
        }

        // Handle saved data/URL redirect if POST request made
        $location = 'index.php';
        if (isset($_SESSION['BT-saved-url'])) {
            $location = $_SESSION['BT-saved-url'];
            unset($_SESSION['BT-saved-url']);
        }
        if (isset($_SESSION['BT-post-token'])) {
            // The login was right, so we give a token because the previous one expired with the session
            $_SESSION['BT-post-token'] = new_token();
        }
        auth_write_access(true);
        redirection($location);
    }
    auth_write_access(false, $username);
}


/**
 * echo
 */

echo tpl_get_html_head('Identification');
echo '<div id="axe">';
echo '<div id="pageauth">';
echo '<h1>'.BLOGOTEXT_NAME.'</h1>';
echo '<form method="post" action="auth.php">';
echo '<div id="auth">';
echo '<p><label for="user">'.ucfirst($GLOBALS['lang']['label_dp_identifiant']).'</label><input class="text" type="text" autocomplete="off" id="user" name="nom_utilisateur" placeholder="John Doe" value="" required autofocus /></p>';
echo '<p><label for="password">'.ucfirst($GLOBALS['lang']['label_dp_motdepasse']).'</label><input class="text" id="password" type="password" placeholder="••••••••••••" name="mot_de_passe" value="" required /></p>';
echo '<p><input type="checkbox" id="stay_logged" name="stay_logged" checked class="checkbox" /><label for="stay_logged">'.$GLOBALS['lang']['label_stay_logged'].'</label></p>';
echo '<button class="submit button-submit" type="submit" name="submit">'.$GLOBALS['lang']['connexion'].'</button>';
echo '<input type="hidden" name="_verif_envoi" value="1" />';
echo '</div>';
echo '</form>';

echo '<script src="style/javascript.js"></script>';
echo tpl_get_footer();
