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

// Set language
$lang = (string)filter_input(INPUT_GET, 'l');
$GLOBALS['lang'] = ($lang != 'en' && $lang != 'fr') ? 'fr' : $lang;

define('BT_RUN_INSTALL', 1);

require_once 'inc/boot.php';

// dependancy
require_once BT_ROOT.'admin/inc/links.php';

/**
 * DevNote
 * - all the functions in this file are only used in the install process
 *   no need to be in /inc/*
 */

// some constants definition
$GLOBALS['fuseau_horaire'] = 'UTC';


/**
 * if file is already set, return true
 * else return fail write success
 */
function fichier_adv_conf()
{
    if (is_file(FILE_SETTINGS_ADV)) {
        return true;
    }
    $conf  = '; <?php die; ?>'."\n";
    $conf .= '; This file contains some more advanced configuration features.'."\n\n";
    $conf .= 'BLOG_UID = \''.sha1(uniqid(mt_rand(), true)).'\''."\n";
    $conf .= 'USE_IP_IN_SESSION = 1'."\n";

    return file_put_contents(FILE_SETTINGS_ADV, $conf) !== false;
}

/**
 * show the form for step 1 (language)
 * ! this function return nothing and use echo
 */
function install_form_1_echo($erreurs = '')
{
    tpl_show_html_head('Install');
    echo '<div id="axe">'."\n";
    echo '<div id="pageauth">'."\n";
    echo '<h1>'.BLOGOTEXT_NAME.'</h1>'."\n";
    echo '<h1 id="step">Bienvenue / Welcome</h1>'."\n";
    echo erreurs($erreurs);

    $conferrors = array();
    // check PHP version
    if (version_compare(PHP_VERSION, MINIMAL_PHP_REQUIRED_VERSION, '<')) {
        $conferrors[] = "\t".'<li>Your PHP Version is '.PHP_VERSION.'. BlogoText requires '.MINIMAL_PHP_REQUIRED_VERSION.'.</li>'."\n";
    }
    // pdo_sqlite and pdo_mysql (minimum one is required)
    if (!extension_loaded('pdo_sqlite') and !extension_loaded('pdo_mysql')) {
        $conferrors[] = "\t".'<li>Neither <b>pdo_sqlite</b> or <b>pdo_mysql</b> PHP-modules are loaded. BlogoText needs at least one.</li>'."\n";
    }
    // check directory readability
    if (!is_writable('../')) {
        $conferrors[] = "\t".'<li>BlogoText has no write rights (chmod of home folder must be 644 at least, 777 recommended).</li>'."\n";
    }
    if (!empty($conferrors)) {
        echo '<ol class="erreurs">'."\n";
        echo implode($conferrors, '');
        echo '</ol>'."\n";
        echo '<p classe="erreurs">Installation aborded.</p>'."\n";
        echo '</div>'."\n".'</div>'."\n".'</html>';
        die;
    }

    echo '<form method="post" action="install.php">'."\n";
    echo '<div id="install">'."\n";
    echo '<p>';
    echo '<label for="langue">Choisissez votre langue / Choose your language: ';
    echo '<select id="langue" name="langue">'."\n";
    foreach ($GLOBALS['langs'] as $option => $label) {
        echo "\t".'<option value="'.htmlentities($option).'">'.$label.'</option>'."\n";
    }
    echo '</select></label>'."\n";
    echo hidden_input('install_form_1_sended', 1);
    echo '</p>';
    echo '<button class="submit button-submit" type="submit" name="enregistrer">Ok</button>'."\n";
    echo '<div>'."\n";
    echo '</form>'."\n";
}

/**
 * show the form for step 2 (login + password + url)
 * ! this function return nothing and use echo
 */
function install_form_2_echo($erreurs = '')
{
    tpl_show_html_head('Install');
    echo '<div id="axe">'."\n";
    echo '<div id="pageauth">'."\n";
    echo '<h1>'.BLOGOTEXT_NAME.'</h1>'."\n";
    echo '<h1 id="step">'.$GLOBALS['lang']['install'].'</h1>'."\n";
    echo erreurs($erreurs);
    echo '<form method="post" action="install.php?s='.$GLOBALS['step'].'&amp;l='.$GLOBALS['lang']['id'].'">'."\n".'<div id="erreurs_js" class="erreurs"></div>'."\n";
    echo '<div id="install">'."\n";
    echo '<p>';
    echo '<label for="identifiant">'.$GLOBALS['lang']['install_id'].' </label><input type="text" name="identifiant" id="identifiant" size="30" value="" class="text" placeholder="John Doe" required />'."\n";
    echo '</p>'."\n";
    echo '<p>';
    echo '<label for="mdp">'.$GLOBALS['lang']['install_mdp'].' </label><input type="password" name="mdp" id="mdp" size="30" value="" class="text" autocomplete="off" placeholder="••••••••••••" required /><button type="button" class="unveilmdp" onclick="return revealpass(\'mdp\');"></button>'."\n";
    echo '</p>'."\n";
    $lien = 'http://'.$_SERVER['SERVER_NAME'].dirname(dirname($_SERVER['SCRIPT_NAME'])).'/';
    // bug fix for DIRECTORY_SEPARATOR
    $lien = str_replace('\\', '/', $lien);
    echo '<p>';
    echo '<label for="racine">'.$GLOBALS['lang']['pref_racine'].' </label><input type="url" name="racine" id="racine" size="30" value="'.$lien.'" class="text"  placeholder="'.$lien.'" required />'."\n";
    echo '</p>'."\n";
    echo hidden_input('comm_defaut_status', 1);
    echo hidden_input('langue', $GLOBALS['lang']['id']);
    echo hidden_input('install_form_2_sended', 1);
    echo '<button class="submit button-submit" type="submit" name="enregistrer">Ok</button>'."\n";
    echo '</div>'."\n";
    echo '</form>'."\n";
}

/**
 * show the form for step 3 (database)
 * ! this function return nothing and use echo
 */
function install_form_3_echo($erreurs = '')
{
    tpl_show_html_head('Install');
    echo '<div id="axe">'."\n";
    echo '<div id="pageauth">'."\n";
    echo '<h1>'.BLOGOTEXT_NAME.'</h1>'."\n";
    echo '<h1 id="step">'.$GLOBALS['lang']['install'].'</h1>'."\n";
    echo erreurs($erreurs);
    echo '<form method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'?'.$_SERVER['QUERY_STRING'].'">'."\n";
    echo '<div id="install">'."\n";
    echo '<p><label>'.$GLOBALS['lang']['install_choose_sgdb'].'</label>';
    echo '<select id="sgdb" name="sgdb" onchange="show_mysql_form()">'."\n";
    if (extension_loaded('pdo_sqlite')) {
        echo "\t".'<option value="sqlite">SQLite</option>'."\n";
    }
    if (extension_loaded('pdo_mysql')) {
        echo "\t".'<option value="mysql">MySQL</option>'."\n";
    }
    echo '</select></p>'."\n";

    echo '<div id="mysql_vars" style="display:none;">'."\n";
    if (extension_loaded('pdo_mysql')) {
        echo '<p><label for="mysql_user">MySQL User: </label>
                    <input type="text" id="mysql_user" name="mysql_user" size="30" value="" class="text" placeholder="mysql_user" /></p>'."\n";
        echo '<p><label for="mysql_password">MySQL Password: </label>
                    <input type="password" id="mysql_password" name="mysql_passwd" size="30" value="" class="text" placeholder="••••••••••••" autocomplete="off" /><button type="button" class="unveilmdp" onclick="return revealpass(\'mysql_password\');"></button></p>'."\n";
        echo '<p><label for="mysql_db">MySQL Database: </label>
                    <input type="text" id="mysql_db" name="mysql_db" size="30" value="" class="text" placeholder="db_blogotext" /></p>'."\n";
        echo '<p><label for="mysql_host">MySQL Host: </label>
                    <input type="text" id="mysql_host" name="mysql_host" size="30" value="" class="text" placeholder="localhost" /></p>'."\n";
    }
    echo '</div>'."\n";

    echo hidden_input('langue', $GLOBALS['lang']['id']);
    echo hidden_input('install_form_3_sended', 1);
    echo '<button class="submit button-submit" type="submit" name="enregistrer">Ok</button>'."\n";

    echo '</div>'."\n";
    echo '</form>'."\n";
}

function fichier_mysql($sgdb)
{
    $data = '';
    if ($sgdb !== false) {
        $data .= '; <?php die; ?>'."\n";
        $data .= '; This file contains MySQL credentials and configuration.'."\n\n";
        $data .= 'MYSQL_LOGIN = \''.htmlentities($_POST['mysql_user'], ENT_QUOTES).'\''."\n";
        $data .= 'MYSQL_PASS = \''.htmlentities($_POST['mysql_passwd'], ENT_QUOTES).'\''."\n";
        $data .= 'MYSQL_DB = \''.htmlentities($_POST['mysql_db'], ENT_QUOTES).'\''."\n";
        $data .= 'MYSQL_HOST = \''.htmlentities($_POST['mysql_host'], ENT_QUOTES).'\''."\n\n";
        $data .= 'DBMS = \''.$sgdb.'\''."\n";
    }

    return file_put_contents(FILE_MYSQL, $data) !== false;
}

/**
 * proceed the submited form 2 (login + password + url)
 */
function install_form_2_proceed()
{
    create_folder(DIR_CONFIG, 1); // todo : change for v4
    create_folder(DIR_IMAGES, 0); // todo : change for v4
    create_folder(DIR_DOCUMENTS, 0); // todo : change for v4
    create_folder(DIR_DATABASES, 1); // todo : change for v4
    auth_write_user_login_file($_POST['identifiant'], $_POST['mdp']);
    import_ini_file(FILE_USER); // todo : change for v4
    if (!is_file(FILE_SETTINGS)) {
        fichier_prefs();
    }
    fichier_mysql(false); // create an empty file
}

/**
 * proceed the submited form 3 (database)
 */
function install_form_3_proceed()
{
    if (isset($_POST['sgdb']) and $_POST['sgdb'] == 'mysql') {
        fichier_mysql('mysql');
    } else {
        fichier_mysql('sqlite');
    }

    import_ini_file(FILE_MYSQL);
    $GLOBALS['db_handle'] = open_base();
    $total_articles = liste_elements_count('SELECT count(ID) AS nbr FROM articles', array());
    if ($total_articles != 0) {
        return;
    }

    $time = time();
    if ($GLOBALS['db_handle']) {
        $first_post = array (
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
        $readme_post = array (
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
        if (true !== bdd_article($first_post, 'enregistrer-nouveau')) {
            die('ERROR SQL posting first article..'); // billet "Mon premier article"
        }
        bdd_article($readme_post, 'enregistrer-nouveau'); // billet "read me" avec les instructions // Assuming the 2nd possing will be good if the first was too.

        $link_ar = array(
            'bt_type' => 'link',
            'bt_id' => date('YmdHis', $time + 1),
            'bt_content' => 'This domain is established to be used for illustrative examples in documents. You may use this domain in examples without prior coordination or asking for permission.',
            'bt_wiki_content' => 'This domain is established to be used for illustrative examples in documents. You may use this domain in examples without prior coordination or asking for permission.',
            'bt_title' => 'Example Domain',
            'bt_link' => 'http://www.example.org/',
            'bt_tags' => 'blog, example',
            'bt_statut' => 1
        );
        $link_ar2 = array(
            'bt_type' => 'note',
            'bt_id' => date('YmdHis', $time + 5),
            'bt_content' => 'Ceci est un lien privé. Vous seul pouvez le voir. / This is a private link. Only you can see it.',
            'bt_wiki_content' => 'Ceci est un lien privé. Vous seul pouvez le voir. / This is a private link. Only you can see it.',
            'bt_title' => 'Note : lien privé / private link',
            'bt_link' => 'index.php?mode=links&amp;id='.date('YmdHis', $time + 5),
            'bt_tags' => '',
            'bt_statut' => 0
        );
        links_db_push($link_ar); // lien
        links_db_push($link_ar2); // lien

        $comm_ar = array(
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

        bdd_commentaire($comm_ar, 'enregistrer-nouveau'); // commentaire sur l’article
    }

    fichier_adv_conf(); // is done right after DB init
}

/**
 * check the submited form 1 (language)
 * @return array
 */
function install_form_1_valid()
{
    $erreurs = array();
    $lang = (string)filter_input(INPUT_POST, 'langue');

    if (!$lang) {
        $erreurs[] = 'Vous devez choisir une langue / You have to choose a language';
    }

    return $erreurs;
}

/**
 * check the submited form 2 (login + password + url)
 * @return array
 */
function install_form_2_valid()
{
    $erreurs = array();
    $username = (string)filter_input(INPUT_POST, 'identifiant');
    $password = (string)filter_input(INPUT_POST, 'mdp');
    $url = (string)filter_input(INPUT_POST, 'racine', FILTER_VALIDATE_URL);

    if (!$username) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_identifiant'];
    } elseif (preg_match('#[=\'"\\\\|]#iu', $username)) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_id_syntaxe'];
    }

    if ((strlen($password) < 6)) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_mdp'] ;
    }

    if (!$url) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_racine'];
    } elseif (!preg_match('/\/$/', $url)) {
        $erreurs[] = $GLOBALS['lang']['err_prefs_racine_slash'];
    }

    return $erreurs;
}

/**
 * check the submited form 3 (database)
 * @return array
 */
function install_form_3_valid()
{
    $erreurs = array();
    $sgdb = (string)filter_input(INPUT_POST, 'sgdb');

    if ($sgdb == 'mysql') {
        $user = (string)filter_input(INPUT_POST, 'mysql_user');
        $password = (string)filter_input(INPUT_POST, 'mysql_passwd');
        $database = (string)filter_input(INPUT_POST, 'mysql_db');
        $host = (string)filter_input(INPUT_POST, 'mysql_host');

        if (!$user) {
            $erreurs[] = $GLOBALS['lang']['install_err_mysql_usr_empty'];
        }
        if (!$password) {
            $erreurs[] = $GLOBALS['lang']['install_err_mysql_pss_empty'];
        }
        if (!$database) {
            $erreurs[] = $GLOBALS['lang']['install_err_mysql_dba_empty'];
        }
        if (!$host) {
            $erreurs[] = $GLOBALS['lang']['install_err_mysql_hst_empty'];
        }

        if (!test_connection_mysql()) {
            $erreurs[] = $GLOBALS['lang']['install_err_mysql_connect'];
        }
    }

    return $erreurs;
}

/**
 * test the MySQL connection
 * @return bool
 */
function test_connection_mysql()
{
    try {
        $options_pdo[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $db_handle = new PDO('mysql:host='.htmlentities($_POST['mysql_host'], ENT_QUOTES).';dbname='.htmlentities($_POST['mysql_db'], ENT_QUOTES), htmlentities($_POST['mysql_user'], ENT_QUOTES), htmlentities($_POST['mysql_passwd'], ENT_QUOTES), $options_pdo);
        return true;
    } catch (Exception $e) {
        return false;
    }
}


// get the step of the install
$GLOBALS['step'] = (int)filter_input(INPUT_GET, 's');
if ($GLOBALS['step'] == 0) {
    // set language
    if (isset($_POST['install_form_1_sended'])) {
        if ($err_1 = install_form_1_valid()) {
            install_form_1_echo($err_1);
        } else {
            redirection('install.php?s=2&l='.$_POST['langue']);
        }
    } else {
        install_form_1_echo();
    }
} elseif ($GLOBALS['step'] == 2) {
    // set login + password + url
    if (isset($_POST['install_form_2_sended'])) {
        if ($err_2 = install_form_2_valid()) {
            install_form_2_echo($err_2);
        } else {
            install_form_2_proceed();
            redirection('install.php?s=3&l='.$_POST['langue']);
        }
    } else {
        install_form_2_echo();
    }
} elseif ($GLOBALS['step'] == 3) {
    // set db choice
    if (isset($_POST['install_form_3_sended'])) {
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
    if (!selectElmt) return false;
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
show_mysql_form(); // needed if MySQL is only option.

function revealpass(fieldId) {
    var field = document.getElementById(fieldId);
    if (field.type == "password") { field.type = "text"; }
    else { field.type = "password"; }
    field.focus();
    field.setSelectionRange(field.value.length, field.value.length);
    return false;
}

</script>'."\n";

footer();
