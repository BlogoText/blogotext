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

if (!defined('BT_ROOT')) {
    exit('Requires BT_ROOT.');
}

function auth_kill_session()
{
    unset($_SESSION['nom_utilisateur'], $_SESSION['user_id'], $_SESSION['tokens']);
    setcookie('BT-admin-stay-logged', null);
    session_destroy(); // destroy session
    // Saving server-side the possible lost data (writing article for example)
    session_start();
    session_regenerate_id(true); // change l'ID au cas ou
    foreach ($_POST as $key => $value) {
        $_SESSION['BT-post-'.$key] = $value;
    }

    if (strrpos($_SERVER['REQUEST_URI'], '/logout.php') != strlen($_SERVER['REQUEST_URI']) - strlen('/logout.php')) {
        $_SESSION['BT-saved-url'] = $_SERVER['REQUEST_URI'];
    }
    redirection('auth.php');
}

function get_ip()
{
    return (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']) : htmlspecialchars($_SERVER['REMOTE_ADDR']);
}

/**
 * check som stuff about the session
 *
 */
function auth_check_session()
{
    if (USE_IP_IN_SESSION == 1) {
        $ip = get_ip();
    } else {
        $ip = date('m');
    }
    @session_start();
    ini_set('session.cookie_httponly', true);

    // generate hash for cookie
    $newUID = hash('sha256', USER_PWHASH.USER_LOGIN.md5($_SERVER['HTTP_USER_AGENT'].$ip));

    // check old cookie  with newUID
    if (isset($_COOKIE['BT-admin-stay-logged']) and $_COOKIE['BT-admin-stay-logged'] == $newUID) {
        $_SESSION['user_id'] = md5($newUID);
        session_set_cookie_params(365*24*60*60); // set new expiration time to the browser
        session_regenerate_id(true);  // Send cookie
        // Still logged in, return
        return true;
    } else {
        return false;
    }

    // no "stay-logged" cookie : check session.
    if ((!isset($_SESSION['user_id'])) or ($_SESSION['user_id'] != USER_LOGIN.hash('sha256', USER_PWHASH.$_SERVER['HTTP_USER_AGENT'].$ip))) {
        return false;
    } else {
        return true;
    }
}

/**
 * This will look if session expired and kill it, otherwise restore it
 */
function auth_ttl()
{
    if (!auth_check_session()) {
        auth_kill_session();
    }

    // Restore data lost if possible
    foreach ($_SESSION as $key => $value) {
        if (substr($key, 0, 8) === 'BT-post-') {
            $_POST[substr($key, 8)] = $value;
            unset($_SESSION[$key]);
        }
    }
}

/**
 * format the uncrypted password
 *
 * @param string $pass, the _POSTed unhashed/crypted password
 * @return string, the formated unhashed/crypted password
 */
function auth_format_password($pass)
{
    return trim($pass);
}

/**
 * format the login
 *
 * @param string $login, the _POSTed login
 * @return string, the formated login
 */
function auth_format_login($login)
{
    return addslashes(clean_txt(htmlspecialchars($login)));
}

/**
 * check if login and password match with the registered
 *
 * @param string $login, the login, without auth_format_password()
 * @param string $pass, the pass, without auth_format_login()
 * @param return true;
 */
function auth_is_valid($login, $pass)
{
    return (password_verify(auth_format_password($pass), USER_PWHASH) && auth_format_login($login) == USER_LOGIN);
}


/**
 * write_user_login_file()
 * write /config/user.php which containt the login & password
 */
function auth_write_user_login_file($login, $pass)
{
    $pass = auth_format_password($pass);
    $login = auth_format_login($login);

    // unchanged password (need to rehash ?)
    if (empty($pass)) {
        $pass = USER_PWHASH;
    } else {
        $pass = password_hash($pass, PASSWORD_BCRYPT);
    }

    $content  = '; <?php die; ?>'."\n";
    $content .= '; This file contains user login + password hash.'."\n\n";
    $content .= 'USER_LOGIN = \''. $login .'\''."\n";
    $content .= 'USER_PWHASH = \''. $pass .'\''."\n";

    return (file_put_contents(FILE_USER, $content, LOCK_EX) !== false);
}
