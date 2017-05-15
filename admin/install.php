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

// Language
$lang = (string)filter_input(INPUT_GET, 'l');
$GLOBALS['lang'] = (in_array($lang, array('en', 'fr'))) ? $lang : 'fr';

define('BT_RUN_INSTALL', 1);
require_once 'inc/boot.php';

// update ?
if (is_file(DIR_CONFIG.'mysql.ini') || is_file(DIR_CONFIG.'prefs.php')) {
    require 'update/update-to-3.7.php';
    exit();
}

require_once BT_ROOT.'admin/inc/links.php';


// Install or reinstall with same config ?
$step3 = (is_file(FILE_MYSQL) && !empty(trim(file_get_contents(FILE_MYSQL))));

// Install already done
if (is_file(FILE_USER) && is_file(FILE_SETTINGS) && $step3) {
    redirection('auth.php');
}

// Some constants definition
$GLOBALS['fuseau_horaire'] = 'UTC';

/**
 * Create the advanced configuration file.
 */
function fichier_adv_conf()
{
    if (is_file(FILE_SETTINGS_ADV)) {
        return true;
    }
    $conf = '; <?php die; ?>'."\n";
    $conf .= '; This file contains some more advanced configuration features.'."\n\n";
    $conf .= 'BLOG_UID = \''.sha1(uniqid(mt_rand(), true)).'\''."\n";
    $conf .= 'USE_IP_IN_SESSION = 1'."\n";

    return (file_put_contents(FILE_SETTINGS_ADV, $conf, LOCK_EX) !== false);
}

/**
 * show the form for step 1 (language)
 * ! this function return nothing and use echo
 */
function install_form_1_echo($errors = '')
{
    echo tpl_get_html_head('Install');
    echo '<div id="axe">';
    echo '<div id="pageauth">';
    echo '<h1>'.BLOGOTEXT_NAME.'</h1>';
    echo '<h1 id="step">Bienvenue / Welcome</h1>';
    echo erreurs($errors);

    $confErrors = array();
    if (version_compare(PHP_VERSION, MINIMAL_PHP_REQUIRED_VERSION, '<')) {
        $confErrors[] = '<li>Your PHP Version is '.PHP_VERSION.'. BlogoText requires '.MINIMAL_PHP_REQUIRED_VERSION.'.</li>';
    }
    if (!extension_loaded('pdo_sqlite') && !extension_loaded('pdo_mysql')) {
        $confErrors[] = '<li>Neither <b>pdo_sqlite</b> or <b>pdo_mysql</b> PHP-modules are loaded. BlogoText needs at least one.</li>';
    }
    if (!is_writable('../')) {
        $confErrors[] = '<li>BlogoText has no write rights (chmod of home folder must be 644 at least, 777 recommended).</li>';
    }
    if ($confErrors) {
        echo '<div id="install"><h3>:(</h3>';
        echo '<ul class="erreurs">'.implode($confErrors, '').'</ul>';
        echo '<p classe="erreurs">Installation aborded.</p>';
        echo '</div></div></div></html>';
        return;
    }

    echo '<form method="post" action="install.php">';
    echo '<div id="install">';
    if (PHP_INTL === false) {
        echo '<p class="erreurs" style="color: #FFA726;">We recommend to use the INTL extension for PHP before installing BlogoText.</p>';
    }
    echo '<p>';
    echo '<label for="langue">Choisissez votre langue / Choose your language: ';
    echo '<select id="langue" name="langue">';
    foreach ($GLOBALS['langs'] as $option => $label) {
        echo '<option value="'.htmlentities($option).'">'.$label.'</option>';
    }
    echo '</select></label>';
    echo hidden_input('install_form_1_sended', 1);
    echo '</p>';
    echo '<button class="submit button-submit" type="submit" name="enregistrer">Ok</button>';
    echo '<div>';
    echo '</form>';
}

/**
 * show the form for step 2 (login + password + url)
 * ! this function return nothing and use echo
 */
function install_form_2_echo($errors = '')
{
    echo tpl_get_html_head('Install');
    echo '<div id="axe">';
    echo '<div id="pageauth">';
    echo '<h1>'.BLOGOTEXT_NAME.'</h1>';
    echo '<h1 id="step">'.$GLOBALS['lang']['install'].'</h1>';
    echo erreurs($errors);

    echo '<form method="post" action="install.php?s='.$GLOBALS['step'].'&amp;l='.$GLOBALS['lang']['id'].'">'.'<div id="erreurs_js" class="erreurs"></div>';
    echo '<div id="install">';
    echo '<p>';
        echo '<label for="identifiant">'.$GLOBALS['lang']['install_id'].' </label><input type="text" name="identifiant" id="identifiant" size="30" value="" class="text" placeholder="John Doe" required autofocus  />';
    echo '</p>';
    echo '<p>';
        echo '<label for="mdp">'.$GLOBALS['lang']['install_mdp'].' </label><input type="password" name="mdp" id="mdp" size="30" value="" class="text" autocomplete="off" placeholder="••••••••••••" required /><button type="button" class="unveilmdp" onclick="return revealpass(\'mdp\');"></button>';
    echo '</p>';

    $link = ((isset($_SERVER['HTTPS']) || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? 'https://' : 'http://');
    $link .= htmlentities($_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['REQUEST_URI'])));
    $link = str_replace('\\', '/', $link);  // bug fix for DIRECTORY_SEPARATOR
    $link = rtrim($link, '/').'/';  // ensure it ends with a slash

    echo '<p>';
    echo '<label for="racine">'.$GLOBALS['lang']['pref_racine'].' </label><input type="url" name="racine" id="racine" size="30" value="'.$link.'" class="text" required />';
    echo '</p>';
    echo hidden_input('comm_defaut_status', 1);
    echo hidden_input('langue', $GLOBALS['lang']['id']);
    echo hidden_input('install_form_2_sended', 1);
    echo '<button class="submit button-submit" type="submit" name="enregistrer">Ok</button>';
    echo '</div>';
    echo '</form>';
}

/**
 * show the form for step 3 (database)
 * ! this function return nothing and use echo
 */
function install_form_3_echo($errors = '')
{
    echo tpl_get_html_head('Install');
    echo '<div id="axe">';
    echo '<div id="pageauth">';
    echo '<h1>'.BLOGOTEXT_NAME.'</h1>';
    echo '<h1 id="step">'.$GLOBALS['lang']['install'].'</h1>';
    echo erreurs($errors);

    echo '<form method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'?'.$_SERVER['QUERY_STRING'].'">';
    echo '<div id="install">';
    echo '<p><label>'.$GLOBALS['lang']['install_choose_sgdb'].'</label>';
    echo '<select id="sgdb" name="sgdb" onchange="show_mysql_form()">';
    if (extension_loaded('pdo_sqlite')) {
        echo '<option value="sqlite">SQLite</option>';
    }
    if (extension_loaded('pdo_mysql')) {
        echo '<option value="mysql">MySQL</option>';
    }
    echo '</select></p>';

    echo '<div id="mysql_vars" style="display:none;">';
    if (extension_loaded('pdo_mysql')) {
        echo '<p><label for="mysql_user">MySQL User: </label>
                    <input type="text" id="mysql_user" name="mysql_user" size="30" value="" class="text" placeholder="mysql_user" autofocus /></p>';
        echo '<p><label for="mysql_password">MySQL Password: </label>
                    <input type="password" id="mysql_password" name="mysql_passwd" size="30" value="" class="text" placeholder="••••••••••••" autocomplete="off" /><button type="button" class="unveilmdp" onclick="return revealpass(\'mysql_password\');"></button></p>';
        echo '<p><label for="mysql_db">MySQL Database: </label>
                    <input type="text" id="mysql_db" name="mysql_db" size="30" value="" class="text" placeholder="db_blogotext" /></p>';
        echo '<p><label for="mysql_host">MySQL Host: </label>
                    <input type="text" id="mysql_host" name="mysql_host" size="30" value="" class="text" placeholder="localhost" /></p>';
    }
    echo '</div>';

    echo hidden_input('langue', $GLOBALS['lang']['id']);
    echo hidden_input('install_form_3_sended', 1);
    echo '<button class="submit button-submit" type="submit" name="enregistrer">Ok</button>';
    echo '</div>';
    echo '</form>';
}

function fichier_mysql($sgdb)
{
    $data = '';
    if ($sgdb !== false) {
        $username = (string)filter_input(INPUT_POST, 'mysql_user', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $password = (string)filter_input(INPUT_POST, 'mysql_passwd', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $database = (string)filter_input(INPUT_POST, 'mysql_db', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $hostname = (string)filter_input(INPUT_POST, 'mysql_host', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $data .= '; <?php die; ?>'."\n";
        $data .= '; This file contains MySQL credentials and configuration.'."\n\n";
        $data .= 'MYSQL_LOGIN = \''.$username.'\''."\n";
        $data .= 'MYSQL_PASS = \''.$password.'\''."\n";
        $data .= 'MYSQL_DB = \''.$database.'\''."\n";
        $data .= 'MYSQL_HOST = \''.$hostname.'\''."\n\n";
        $data .= 'DBMS = \''.$sgdb.'\''."\n";
    }

    return (file_put_contents(FILE_MYSQL, $data, LOCK_EX) !== false);
}

/**
 * proceed the submited form 2 (login + password + url)
 */
function install_form_2_proceed()
{
    $username = (string)filter_input(INPUT_POST, 'identifiant');
    $password = (string)filter_input(INPUT_POST, 'mdp');

    create_folder(DIR_CONFIG, true);
    create_folder(DIR_IMAGES, false);
    create_folder(DIR_DOCUMENTS, false);
    create_folder(DIR_DATABASES, true);
    create_folder(DIR_LOG, true, true);
    auth_write_user_login_file($username, $password);
    import_ini_file(FILE_USER); // todo : change for v4
    if (!is_file(FILE_SETTINGS)) {
        fichier_prefs();
    }
}

/**
 * proceed the submited form 3 (database)
 */
function install_form_3_proceed()
{
    $sgdb = (string)filter_input(INPUT_POST, 'sgdb');
    if (!in_array($sgdb, array('sqlite', 'mysql'))) {
        $sgdb = 'sqlite';
    }
    fichier_mysql($sgdb);

    import_ini_file(FILE_MYSQL);
    $GLOBALS['db_handle'] = open_base();
    $totalPosts = liste_elements_count('SELECT count(ID) AS nbr FROM articles', array());

    fichier_adv_conf();

    if ($totalPosts != 0) {
        return;
    }

    $time = time();
    if ($GLOBALS['db_handle']) {
        $post1 = array (
            'bt_id' => date('YmdHis', $time),
            'bt_date' => date('YmdHis', $time),
            'bt_title' => $GLOBALS['lang']['first_titre'],
            'bt_abstract' => $GLOBALS['lang']['first_edit'],
            'bt_content' => $GLOBALS['lang']['first_edit'],
            'bt_wiki_content' => $GLOBALS['lang']['first_edit'],
            'bt_keywords' => '',
            'bt_tags' => '',
            'bt_link' => '',
            'bt_notes' => '',
            'bt_statut' => 1,
            'bt_allow_comments' => 1
        );
        $post2 = array (
            'bt_notes' => '',
            'bt_link' => '',
            'bt_tags' => '',
            'bt_link' => '',
            'bt_id' => date('YmdHis', $time + 2),
            'bt_date' => date('YmdHis', $time + 2),
            'bt_title' => 'README / LISEZ-MOI',
            'bt_abstract' => 'Instructions / Instructions',
            'bt_content' => gzinflate(base64_decode('rVPLjtQwELzPV7T2MoDC7A+sVgIE0t5WYuHecXoSC8c9+JHs7NdwJELiB7htfoxuJwOzB+DCSGNZdruqq7qyuesoEmAgiNwTWB9TyCZZ9hH2HCB1coN7SkfgPRw5B6gdt7urOlxeb248cGhIyhgOgROZtNQcKET26EoxYIsKDJgSmk+xgtdyKtX3cuQcj1EfKcZVfR3IozSivBfY9NZfSB9OOK4u6+sdfIjWt+X23d0tGPaeSrf6ujCPVL/sOCYpqwps7Di7BgIJ1RH+CK8AY4eJBtnruxF92mlHN2kbwXNSPww1FdQ5gU0wWufEMCPIYmFHOFghKIYloWlTp5adSX3qQtGz2HjrFKIC3KeVfGmzKWhrf89s8R8afl7JfU99Tct8PI1QVNVkMEdVh2t7nkc5PYdRiA4HUr0t62rPhojGUIylvrgDB/TknujYbZamX/zH34K4rB/ZGgufMzn5Rx1xJOsiHHS6DiHOk8nBpnmChmBg8fo8kq/20rVcaBznqRVZv0sUTPfNdp4G8kk4nFDoQJTrPJcD56gp7ikp2hJM7nvBcwLAMVrZPn5bbXr8scTzrYecrLNRwqPNloDeaz41rvOXtRmKBzTaRzdPMsVWpulTtZA2NARLD/APPiCh8oBZ8aRSHVsAZGkCPZTsviHx0m9JYnfACFw722JiK296tOIuSZPYz5OzHCigau3R2/mr2hRQviPF/YvtJ0/PjdypGcs43m+tW810OH9XkZQlmUVgIm+LPs95IMxPdTTq3YMoPMmvwGAAU9SYk2jF36MoxtV5scmocb9MO33nJUAS8HnSsAtsyfZus/kJ')),
            'bt_wiki_content' => 'Once read, you should delete this post / Une fois que vous avez lu ceci, vous pouvez supprimer l\'article',
            'bt_keywords' => '',
            'bt_statut' => 0,
            'bt_allow_comments' => 0
        );
        if (!bdd_article($post1, 'enregistrer-nouveau')) {
            die('ERROR SQL posting first article.');
        }
        bdd_article($post2, 'enregistrer-nouveau');

        $link1 = array(
            'bt_type' => 'link',
            'bt_id' => date('YmdHis', $time + 1),
            'bt_content' => 'This domain is established to be used for illustrative examples in documents. You may use this domain in examples without prior coordination or asking for permission.',
            'bt_wiki_content' => 'This domain is established to be used for illustrative examples in documents. You may use this domain in examples without prior coordination or asking for permission.',
            'bt_title' => 'Example Domain',
            'bt_link' => 'http://www.example.org/',
            'bt_tags' => 'blog, example',
            'bt_statut' => 1
        );
        $link2 = array(
            'bt_type' => 'note',
            'bt_id' => date('YmdHis', $time + 5),
            'bt_content' => 'Ceci est un lien privé. Vous seul pouvez le voir. / This is a private link. Only you can see it.',
            'bt_wiki_content' => 'Ceci est un lien privé. Vous seul pouvez le voir. / This is a private link. Only you can see it.',
            'bt_title' => 'Note : lien privé / private link',
            'bt_link' => 'index.php?mode=links&amp;id='.date('YmdHis', $time + 5),
            'bt_tags' => '',
            'bt_statut' => 0
        );
        links_db_push($link1);
        links_db_push($link2);

        $comment = array(
            'bt_type' => 'comment',
            'bt_id' => date('YmdHis', $time + 6),
            'bt_article_id' => date('YmdHis', $time),
            'bt_content' => '<p>Ceci est un commentaire / This is a comment.</p>',
            'bt_wiki_content' => 'Ceci est un commentaire / This is a comment.',
            'bt_author' => 'BlogoText',
            'bt_link' => '',
            'bt_webpage' => 'http://www.example.org',
            'bt_email' => 'mail@example.com',
            'bt_subscribe' => 0,
            'bt_statut' => 1
        );

        bdd_commentaire($comment, 'enregistrer-nouveau');
    }
}

/**
 * check the submited form 1 (language)
 * @return array
 */
function install_form_1_valid()
{
    $errors = array();
    $lang = (string)filter_input(INPUT_POST, 'langue');

    if (!$lang) {
        $errors[] = 'Vous devez choisir une langue / You have to choose a language';
    }

    return $errors;
}

/**
 * check the submited form 2 (login + password + url)
 * @return array
 */
function install_form_2_valid()
{
    $errors = array();
    $username = (string)filter_input(INPUT_POST, 'identifiant');
    $password = (string)filter_input(INPUT_POST, 'mdp');
    /**
     * FILTER_VALIDATE_URL se base sur la rfc2396 : ftp:, ssh: (...) if faut vérifier que c'est
     * bien du http(s)
     */
    $url = (string)filter_input(INPUT_POST, 'racine', FILTER_VALIDATE_URL);

    if (!$username) {
        $errors[] = $GLOBALS['lang']['err_prefs_identifiant'];
    } elseif (preg_match('#[=\'"\\\\|]#iu', $username)) {
        $errors[] = $GLOBALS['lang']['err_prefs_id_syntaxe'];
    }

    if ((strlen($password) < 6)) {
        $errors[] = $GLOBALS['lang']['err_prefs_mdp'] ;
    }

    $url = trim($url);
    if (empty($url) || !preg_match('#^https?://.+#', $url)) {
        $errors[] = $GLOBALS['lang']['err_prefs_racine'];
    } elseif (!preg_match('/^https?:\/\//', $url)) {
        $errors[] = $GLOBALS['lang']['err_prefs_racine_http'];
    } elseif (!preg_match('/\/$/', $url)) {
        $errors[] = $GLOBALS['lang']['err_prefs_racine_slash'];
    }

    return $errors;
}

/**
 * check the submited form 3 (database)
 * @return array
 */
function install_form_3_valid()
{
    $errors = array();
    $sgdb = (string)filter_input(INPUT_POST, 'sgdb');

    if ($sgdb == 'mysql') {
        $user = (string)filter_input(INPUT_POST, 'mysql_user');
        $password = (string)filter_input(INPUT_POST, 'mysql_passwd');
        $database = (string)filter_input(INPUT_POST, 'mysql_db');
        $host = (string)filter_input(INPUT_POST, 'mysql_host');

        if (!$user) {
            $errors[] = $GLOBALS['lang']['install_err_mysql_usr_empty'];
        }
        if (!$password) {
            $errors[] = $GLOBALS['lang']['install_err_mysql_pss_empty'];
        }
        if (!$database) {
            $errors[] = $GLOBALS['lang']['install_err_mysql_dba_empty'];
        }
        if (!$host) {
            $errors[] = $GLOBALS['lang']['install_err_mysql_hst_empty'];
        }

        if (!test_connection_mysql()) {
            $errors[] = $GLOBALS['lang']['install_err_mysql_connect'];
        }
    }

    return $errors;
}

/**
 * test the MySQL connection
 * @return bool
 */
function test_connection_mysql()
{
    $username = (string)filter_input(INPUT_POST, 'mysql_user', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = (string)filter_input(INPUT_POST, 'mysql_passwd', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $database = (string)filter_input(INPUT_POST, 'mysql_db', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $hostname = (string)filter_input(INPUT_POST, 'mysql_host', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    try {
        $dtb = new PDO('mysql:host='.$hostname.';dbname='.$database, $username, $password);
        $dtb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        return false;
    }

    return true;
}


$GLOBALS['step'] = (int)filter_input(INPUT_GET, 's');
if ($GLOBALS['step'] == 0) {
    // Set language
    if (filter_input(INPUT_POST, 'install_form_1_sended') !== null) {
        if ($err_1 = install_form_1_valid()) {
            install_form_1_echo($err_1);
        } else {
            $language = (string)filter_input(INPUT_POST, 'langue');
            redirection('install.php?s=2&l='.$language);
        }
    } else {
        install_form_1_echo();
    }
} elseif ($GLOBALS['step'] == 2) {
    // Set login + password + url
    if (filter_input(INPUT_POST, 'install_form_2_sended') !== null) {
        if ($err_2 = install_form_2_valid()) {
            install_form_2_echo($err_2);
        } else {
            install_form_2_proceed();
            $language = (string)filter_input(INPUT_POST, 'langue');
            redirection('install.php?s=3&l='.$language);
        }
    } else {
        install_form_2_echo();
    }
} elseif ($GLOBALS['step'] == 3) {
    // Set db choice
    if (filter_input(INPUT_POST, 'install_form_3_sended') !== null) {
        if ($err_3 = install_form_3_valid()) {
            install_form_3_echo($err_3);
        } else {
            install_form_3_proceed();
            redirection('auth.php');
        }
    } else {
        install_form_3_echo();
    }
}

echo '<script>
function getSelectSgdb() {
    var selectElmt = document.getElementById("sgdb");
    if (!selectElmt) {
        return false;
    }
    return selectElmt.options[selectElmt.selectedIndex].value;
}
function show_mysql_form() {
    var selected = getSelectSgdb();
    if (selected) {
        if (selected == "mysql") {
            document.getElementById("mysql_vars").style.display = "block";
        } else {
            document.getElementById("mysql_vars").style.display = "none";
        }
    }
}
show_mysql_form();  // needed if MySQL is only option.

function revealpass(fieldId) {
    var field = document.getElementById(fieldId);
    if (field.type == "password") {
        field.type = "text";
    } else {
        field.type = "password";
    }
    field.focus();
    field.setSelectionRange(field.value.length, field.value.length);
    return false;
}

</script>';
echo tpl_get_footer();
