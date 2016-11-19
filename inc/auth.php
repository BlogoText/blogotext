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

if (!defined('BT_ROOT')){
    exit('Require BT_ROOT for auth');
}

/**
 * format the uncrypted password
 * 
 * @param string $pass, the _POSTed unhashed/crypted password
 * @return string, the formated unhashed/crypted password
 */
function auth_format_password( $pass ){
    return trim( $pass );
}

/**
 * format the login
 * 
 * @param string $login, the _POSTed login
 * @return string, the formated login
 */
function auth_format_login( $login ){
	return addslashes(clean_txt(htmlspecialchars( $login )));
}

/**
 * check if login and password match with the registered
 * 
 * @param string $login, the login, without auth_format_password()
 * @param string $pass, the pass, without auth_format_login()
 * @param return true;
 */
function auth_is_valid( $login , $pass ){
    return (password_verify(auth_format_password($pass) ,USER_PWHASH )&& auth_format_login($login) == USER_LOGIN);
}


/**
 * write_user_login_file()
 * write /config/user.ini which containt the login & password
 * 
 */
function auth_write_user_login_file( $login , $pass ){
    $file = '../'. DIR_CONFIG .'/user.ini';
    $content = '';

    $pass = auth_format_password($pass);
    $login = auth_format_login($login);

    // unchanged password (need to rehash ?)
    if (empty($pass)){
        $pass = USER_PWHASH;
    } else {
        $pass = password_hash( $pass, PASSWORD_BCRYPT );
    }

    $content .= '; <?php die(); /*'."\n\n";
    $content .= '; This file contains user login + password hash.'."\n\n";
    $content .= 'USER_LOGIN = \''. $login .'\''."\n";
    $content .= 'USER_PWHASH = \''. $pass .'\''."\n";

    return (file_put_contents($file, $content) !== false);
}
