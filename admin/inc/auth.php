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

if (!defined('BT_ROOT')) {
    die('Requires BT_ROOT.');
}

/**
 *
 */
function auth_kill_session()
{
    unset($_SESSION['nom_utilisateur'], $_SESSION['user_id'], $_SESSION['tokens']);
    setcookie('BT-admin-stay-logged', null);
    session_destroy();

    // Saving server-side the possible lost data (writing article for example)
    session_start();
    session_regenerate_id(true);
    foreach ($_POST as $key => $value) {
        $_SESSION['BT-post-'.$key] = $value;
    }

    if (strrpos($_SERVER['REQUEST_URI'], '/logout.php') != strlen($_SERVER['REQUEST_URI']) - strlen('/logout.php')) {
        $_SESSION['BT-saved-url'] = $_SERVER['REQUEST_URI'];
    }
    redirection('auth.php');
}

/**
 *
 */
function get_ip()
{
    $ipAddr = (string)$_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddr .= '_'.$_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    return htmlspecialchars($ipAddr);
}

/**
 * check som stuff about the session
 */
function auth_check_session()
{
    // bug fix for PHP 7.2
    if (session_status() == PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', true);
        session_set_cookie_params(365 * 24 * 60 * 60);
        @session_start();
    }

    // Check old cookie
    $newUid = uuid();
    if (isset($_COOKIE['BT-admin-stay-logged'])) {
        if ($_COOKIE['BT-admin-stay-logged'] == $newUid) {
            $_SESSION['user_id'] = $newUid;
            // session_set_cookie_params(365 * 24 * 60 * 60);
            session_regenerate_id(true);
            return true;
        }
        return false;
    }

    // No "stay-logged" cookie: check session
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $newUid) {
        return true;
    }

    return false;
}

/**
 * This will look if session expired and kill it, otherwise restore it.
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
 * Sanitinze the login.
 */
function auth_format_login($login)
{
    return addslashes(clean_txt(htmlspecialchars($login)));
}

/**
 * Check if login and password match with the registered ones.
 */
function auth_is_valid($login, $pass)
{
    if (!password_verify(hash_pass($pass, true), USER_PWHASH)) {
        return false;
    }
    if (auth_format_login($login) !== USER_LOGIN) {
        return false;
    }

    return true;
}

/**
 * Save identification values to write /config/user.php.
 */
function auth_write_user_login_file($login, $pass)
{
    $login = auth_format_login($login);
    $pass = (empty($pass)) ? USER_PWHASH : hash_pass($pass);

    $content = '; <?php die; ?>'."\n";
    $content .= '; This file contains user login + password hash.'."\n\n";
    $content .= 'USER_LOGIN = \''.$login.'\''."\n";
    $content .= 'USER_PWHASH = \''.$pass.'\''."\n";

    return (file_put_contents(FILE_USER, $content, LOCK_EX) !== false);
}

/**
 * Password hashing process.
 * Inspired from https://blogs.dropbox.com/tech/2016/09/how-dropbox-securely-stores-your-passwords/
*/
function hash_pass($password, $checking = false)
{
    // Bypass 72 chars limitation instored by bcrypt
    $hash = hash('sha512', $password);

    if ($checking) {
        return $hash;
    }

    return password_hash($hash, PASSWORD_BCRYPT);
}

/**
 * Generate a uniq hash for cookie.
*/
function uuid()
{
    $ipAddr = (USE_IP_IN_SESSION) ? get_ip() : date('m');
    $hash = USER_LOGIN.hash('sha256', USER_PWHASH.$_SERVER['HTTP_USER_AGENT'].$ipAddr);

    return md5($hash);
}

/**
 * Write access log.
 */
function auth_write_access($status, $username = null)
{
    $output = DIR_CONFIG.'xauthlog.php';
    $content = '';
    if (!is_file($output)) {
        $content .= '<?php die;'."\n";
    }
    $content .= '# '.date('Y-m-d_H:i:s').' - '.get_ip().' - ';
    if ($status) {
        $content .= 'Login successful'."\n";
    } else {
        $content .= 'Login failed for user '.substr($username, 0, 32)."\n";
    }
    file_put_contents($output, $content, FILE_APPEND | LOCK_EX);
}
