<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

// disallow temporary auth_ttl()
define('BT_RUN_LOGIN',1);

require_once 'inc/boot.php';

$max_attemps = 6; // max attempts before blocking login page
$wait_time = 30;   // time to wait before unblocking login page, in minutes

// Acces LOG
if (isset($_POST['nom_utilisateur'])) {
    // IP
    $ip = htmlspecialchars($_SERVER["REMOTE_ADDR"]);
    // Proxy IPs, if exists.
    $ip .= (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? '_'.htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']) : '';
    $curent_time = date('r'); // heure : Wed, 18 Jan 2012 20:42:12 +0100
    $data = '<?php die(\'no.\'); // '.$curent_time.' - '.$ip.' - '.((auth_check_session()) ? 'login succes' : 'login fail') ." ?>\n";
    file_put_contents(DIR_CONFIG.'xauthlog.php', $data, FILE_APPEND);
}

if (auth_check_session()) { // return to index if session is already open.
    redirection('Location: index.php');
}

// Auth checking :
if (isset($_POST['_verif_envoi']) and auth_is_valid($_POST['nom_utilisateur'], $_POST['mot_de_passe'])) {
    // OK : getting in.
    if (USE_IP_IN_SESSION == 1) {
        $ip = get_ip();
    } else {
        $ip = date('m'); // make session expire at least once a month, disregarding IP changes.
    }
    $_SESSION['user_id'] = $_POST['nom_utilisateur'].hash('sha256', $_POST['mot_de_passe'].$_SERVER['HTTP_USER_AGENT'].$ip); // set special hash
    usleep(100000); // 100ms sleep to avoid bruteforce

    if (!empty($_POST['stay_logged'])) { // if user wants to stay logged
        $user_id = hash('sha256', USER_PWHASH.USER_LOGIN.md5($_SERVER['HTTP_USER_AGENT'].$ip));
        setcookie('BT-admin-stay-logged', $user_id, time()+365*24*60*60, null, null, false, true);
        session_set_cookie_params(365*24*60*60); // set expiration time to the browser
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

    exit(header('Location: '.$location));
} else { // On sort…
    // …et affiche la page d'auth
    tpl_show_html_head('Identification');
    echo '<div id="axe">'."\n";
    echo '<div id="pageauth">'."\n";
    echo '<h1>'.BLOGOTEXT_NAME.'</h1>'."\n";
    echo '<form method="post" action="auth.php">'."\n";
    echo '<div id="auth">'."\n";
    echo '<p><label for="user">'.ucfirst($GLOBALS['lang']['label_dp_identifiant']).'</label><input class="text" type="text"  autocomplete="off" id="user" name="nom_utilisateur" placeholder="John Doe" value="" /></p>'."\n";
    echo '<p><label for="password">'.ucfirst($GLOBALS['lang']['label_dp_motdepasse']).'</label><input class="text" id="password" type="password" placeholder="••••••••••••" name="mot_de_passe" value="" /></p>'."\n";
    echo '<p><input type="checkbox" id="stay_logged" name="stay_logged" checked class="checkbox" /><label for="stay_logged">'.$GLOBALS['lang']['label_stay_logged'].'</label></p>'."\n";
    echo '<button class="submit button-submit" type="submit" name="submit">'.$GLOBALS['lang']['connexion'].'</button>'."\n";
    echo '<input type="hidden" name="_verif_envoi" value="1" />'."\n";
    echo '</div>'."\n";
    echo '</form>'."\n";
}

echo "\n".'<script src="style/javascript.js"></script>'."\n";
footer();
