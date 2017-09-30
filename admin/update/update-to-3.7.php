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


/**
 * set vars
 */

/**
 * html head
 */
$html_head = '<!DOCTYPE html>
<html>
    <head>
        <title>Update</title>
        <meta charset="utf-8">
        <style>
            html,body{background-color:#eee;color: rgba(0, 0, 0, .87);font-family: Roboto, Verdana, Helvetica, Arial, sans-serif;}
            .center{text-align:center;}
            #content{max-width:640px;margin:40px auto 10px auto;padding: 15px;background: #fefefe;box-shadow: 0px 1px 4px rgba(0, 0, 0, .25);border-radius: 2px;}
            #content ul{max-width:320px;margin:0 auto;text-align:left;}
            .btn{background: #2196f3;color: white;font-weight: 500;vertical-align: middle;padding: 6px 12px;box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);border-radius: 2px;border: 0px solid transparent;min-width: 100px;text-transform: uppercase;text-decoration: none;}
        </style>
    </head>
    <body>
        <h1 class="center">BlogoText update to the 3.7.6 release</h1>
        <div id="content">';

/**
 * html foot
 */
$html_foot = '</div></body></html>';


/**
 * config from settings
 */
$upd_vars['settings']['activer_categories'] = 1;
$upd_vars['settings']['afficher_liens'] = 1;
$upd_vars['settings']['afficher_rss'] = 1;
$upd_vars['settings']['alert_author'] = 0;
$upd_vars['settings']['auteur'] = '';
$upd_vars['settings']['auto_check_updates'] = 1;
$upd_vars['settings']['automatic_keywords'] = 1;
$upd_vars['settings']['comm_defaut_status'] = 1;
$upd_vars['settings']['description'] = '';
$upd_vars['settings']['dl_link_to_files'] = 0;
$upd_vars['settings']['email'] = '';
$upd_vars['settings']['format_date'] = 0;
$upd_vars['settings']['format_heure'] = 0;
$upd_vars['settings']['fuseau_horaire'] = 'UTC';
$upd_vars['settings']['global_com_rule'] = 0;
$upd_vars['settings']['keywords'] = '';
$upd_vars['settings']['lang'] = 'fr';
$upd_vars['settings']['max_bill_acceuil'] = 10;
$upd_vars['settings']['max_bill_admin'] = 25;
$upd_vars['settings']['max_comm_admin'] = 50;
$upd_vars['settings']['max_rss_admin'] = 25;
$upd_vars['settings']['nb_list_linx'] = 50;
$upd_vars['settings']['nom_du_site'] = 'BlogoText';
$upd_vars['settings']['racine'] = '';
$upd_vars['settings']['require_email'] = 0;
$upd_vars['settings']['theme_choisi'] = 'default';

$upd_vars['mysql']['MYSQL_LOGIN'] = '';
$upd_vars['mysql']['MYSQL_PASS'] = '';
$upd_vars['mysql']['MYSQL_DB'] = '';
$upd_vars['mysql']['MYSQL_HOST'] = '';
$upd_vars['mysql']['DBMS'] = '';

$upd_vars['settings-advanced']['BLOG_UID'] = '';
$upd_vars['settings-advanced']['USE_IP_IN_SESSION'] = 1;


$upd_vars['user']['USER_LOGIN'] = '';
$upd_vars['user']['USER_PWHASH'] = '';








/**
 * running some test
 */

if (!defined('BT_RUN_INSTALL')) {
    echo $html_head;
    echo '
        <div class="center">
            <h3>Not allowed !</h3>
            <p>Please use the install url.</p>
        </div>';
    echo $html_foot;
    exit();
}


// check version
if (BLOGOTEXT_VERSION != '3.7.6') {
    echo $html_head;
    echo '
        <div class="center">
            <h3>Please update file</h3>
            <p>Please overwrite your current BlogoText system, you can go to 
                <a href="https://github.com/BlogoText/blogotext/tree/master">github.com/BlogoText/blogotext/master</a> to get the current stable version 
                or go to 
                <a href="https://github.com/BlogoText/blogotext/releases">github.com/BlogoText/blogotext/releases</a> to get another version.
            </p>
        </div>';
    echo $html_foot;
    exit();
}


// check == 3.7 config
if (is_file(FILE_MYSQL) || is_file(FILE_USER) || is_file(FILE_SETTINGS)) {
    echo $html_head;
    echo '
        <div class="center">
            <h3>Update already done ?</h3>
            <p>Some config\'s file are already set, <a href="../install.php">please finish the install</a>.</p>
        </div>';
    echo $html_foot;
    exit();
}


// check < 3.7 config
if (!is_file(DIR_CONFIG.'mysql.ini') && !is_file(DIR_CONFIG.'prefs.php')) {
    echo $html_head;
    echo '
        <div class="center">
            <h3>This is not an update ?</h3>
            <p>We can\'t find your last config, <a href="../install.php">please make a fresh install</a></p>
        </div>';
    echo $html_foot;
    exit();
}


// ask for proceed
if (!isset($_GET['proceed'])) {
    echo $html_head;
    echo '
        <div class="center">
            <h3>Update BlogoText to 3.7.6 ?</h3>
            <p style="margin:0.5em auto;max-width:400px;text-align:left;">Some recommendations</p>
            <ul>';
    if (PHP_INTL === false) {
        echo '
                <li style="color: #D40000;">We recommend to use the 
                    INTL extension for PHP before updating BlogoText to the 3.7 
                    version.</li>';
    }
        echo '
                <li>We recommand to make a backup of your file and database before 
                    proceeding the update.</li>
            </ul>
            <p style="margin:1em auto;max-width:400px;text-align:left;">This update will check and update 
                if necessary : </p>
            <ul>
                <li>Create the new config\'s files based on the old one</li>
                <li>Delete the old config\'s files</li>
                <li>Update the database scheme</li>
            </ul>
            <p><a class="btn" href="?proceed">Ok !</a></p>
        </div>';

    echo $html_foot;
    exit();
}




/**
 * functions
 */

function upd_file_write_conf_ini($file, $datas)
{
    if (is_file($file)) {
        return true;
    }
    $conf  = '; <?php die; ?>'."\n";
    $conf .= '; This file contains some more settings.'."\n\n";
    foreach ($datas as $key => $val) {
        if (is_int($val) || is_bool($val)) {
            $conf .= strtoupper($key) .' = '. $val ."\n";
        } else {
            $conf .= strtoupper($key) .' = \''. $val .'\''."\n";
        }
    }

    return (file_put_contents($file, $conf, LOCK_EX) !== false);
}

function upd_file_write_conf_php($file, $datas)
{
    $prefs = "<?php\n";
    foreach ($datas as $key => $value) {
        $prefs .= sprintf(
            "\$GLOBALS['%s'] = %s;\n",
            $key,
            (is_numeric($value) || is_bool($value) || empty($value)) ? (int)$value : '"'.$value.'"'
        );
    }

    return (file_put_contents($file, $prefs, LOCK_EX) !== false);
}

function upd_convert_config_files()
{
    global $upd_vars;

    $files = array(
            array(
                    'old' => DIR_CONFIG.'mysql.ini',
                    'new' => FILE_MYSQL,
                    'type' => 'ini',
                    'family' => 'mysql'
                ),
            array(
                    'old' => DIR_CONFIG.'prefs.php',
                    'new' => FILE_SETTINGS,
                    'type' => 'php array',
                    'family' => 'settings'
                ),
            array(
                    'old' => DIR_CONFIG.'config-advanced.ini',
                    'new' => FILE_SETTINGS_ADV,
                    'type' => 'ini',
                    'family' => 'settings-advanced'
                ),
        );

    $errors = array();

    foreach ($files as $file) {
        $vars = array();
        $success = false;

        if (!is_file($file['old']) or !is_readable($file['old'])) {
            $errors[$file['family']][] = 'can\'t find old file';
            continue;
        }

        // proceed ini file
        if ($file['type'] == 'ini') {
            $options = parse_ini_file($file['old']);
            foreach ($options as $option => $value) {
                $vars[$option] = $value;
            }
        // proceed php array file
        } else {
            include $file['old'];
            $vars = $GLOBALS;
        }

        // merge vars
        $temp = array_merge($upd_vars[$file['family']], $vars);
        // get needed vars
        $commons = array_intersect_key($temp, $upd_vars[$file['family']]);

        // write new file
        if ($file['type'] == 'ini') {
            $success = upd_file_write_conf_ini($file['new'], $commons);
        } else {
            $success = upd_file_write_conf_php($file['new'], $commons);
        }

        if ($success !== true) {
            $errors[$file['family']][] = 'fail to write new file';
            continue;
        }

        if (!@unlink($file['old'])) {
            $errors[$file['family']][] = 'fail to delete old file';
            continue;
        }
    }

    // new hash used for password
    if (count($errors) === 0) {
        $user_files = array(DIR_CONFIG.'user.ini', FILE_USER);
        foreach ($user_files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    return (count($errors) === 0) ? true : $errors;
}

function upd_db_update()
{
    // import db
    import_ini_file(FILE_MYSQL);

    // init db
    $GLOBALS['db_handle'] = open_base();

    $errors = array();

    // querys
    $querys = array(
        'links' => 'ALTER TABLE `links` DROP `bt_author`;',
        'articles' => 'ALTER TABLE `articles` CHANGE `bt_categories` `bt_tags` TEXT;',
        'rss' => 'ALTER TABLE `rss` ADD `bt_bookmarked` TINYINT AFTER `bt_statut`;'
    );

    $auto_increment = (DBMS == 'mysql') ? 'AUTO_INCREMENT' : ''; // SQLite doesn't need this, but MySQL does.
    $index_limit_size = (DBMS == 'mysql') ? '(15)' : ''; // MySQL needs a limit for indexes on TEXT fields.
    $if_not_exists = (DBMS == 'sqlite') ? 'IF NOT EXISTS' : ''; // MySQL doesn’t know this statement for INDEXES

    // MySQL
    if (DBMS == 'mysql') {
        foreach ($querys as $key => $query) {
            $errors[$key] = $GLOBALS['db_handle']->query($query);
        }
        return (count($errors) === 0) ? true : $errors;
    }

    // sqLite
    // drop in links
    try {
        $GLOBALS['db_handle']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $GLOBALS['db_handle']->beginTransaction();
        $GLOBALS['db_handle']->exec("CREATE TEMPORARY TABLE links_backup(
                ID INTEGER PRIMARY KEY $auto_increment,
                bt_type CHAR(20),
                bt_id BIGINT,
                bt_content TEXT,
                bt_wiki_content TEXT,
                bt_title TEXT,
                bt_tags TEXT,
                bt_link TEXT,
                bt_statut TINYINT
            )");
        $GLOBALS['db_handle']->exec(
            'INSERT INTO links_backup 
                SELECT ID,bt_type,bt_id,bt_content,bt_wiki_content,bt_title,bt_tags,bt_link,bt_statut
                FROM links'
        );
        $GLOBALS['db_handle']->exec('DROP TABLE links');
        $GLOBALS['db_handle']->exec("CREATE TABLE IF NOT EXISTS links
            (
                ID INTEGER PRIMARY KEY $auto_increment,
                bt_type CHAR(20),
                bt_id BIGINT,
                bt_content TEXT,
                bt_wiki_content TEXT,
                bt_title TEXT,
                bt_tags TEXT,
                bt_link TEXT,
                bt_statut TINYINT
            ); CREATE INDEX $if_not_exists dateL ON links ( bt_id );");
        $GLOBALS['db_handle']->exec(
            'INSERT INTO links 
                SELECT ID,bt_type,bt_id,bt_content,bt_wiki_content,bt_title,bt_tags,bt_link,bt_statut
                FROM links_backup'
        );
        $GLOBALS['db_handle']->exec('DROP TABLE links_backup');
        $GLOBALS['db_handle']->commit();
    } catch (Exception $e) {
        $GLOBALS['db_handle']->rollBack();
        $errors['links'] = $e->getMessage();
    }
    // change in articles
    try {
        $GLOBALS['db_handle']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $GLOBALS['db_handle']->beginTransaction();
        $GLOBALS['db_handle']->exec("
            CREATE TEMPORARY TABLE articles_backup
            (
                ID INTEGER PRIMARY KEY $auto_increment,
                bt_type CHAR(20),
                bt_id BIGINT,
                bt_date BIGINT,
                bt_title TEXT,
                bt_abstract TEXT,
                bt_notes TEXT,
                bt_link TEXT,
                bt_content TEXT,
                bt_wiki_content TEXT,
                bt_tags TEXT,
                bt_keywords TEXT,
                bt_nb_comments INTEGER,
                bt_allow_comments TINYINT,
                bt_statut TINYINT
            );");
        $GLOBALS['db_handle']->exec('
            INSERT INTO articles_backup 
                SELECT ID,bt_type,bt_id,bt_date,bt_title,bt_abstract,
                    bt_notes,bt_link,bt_content,bt_wiki_content,bt_categories,
                    bt_keywords,bt_nb_comments,bt_allow_comments,bt_statut
                FROM articles');
        $GLOBALS['db_handle']->exec('DROP TABLE articles');
        $GLOBALS['db_handle']->exec("CREATE TABLE IF NOT EXISTS articles
        (
            ID INTEGER PRIMARY KEY $auto_increment,
            bt_type CHAR(20),
            bt_id BIGINT,
            bt_date BIGINT,
            bt_title TEXT,
            bt_abstract TEXT,
            bt_notes TEXT,
            bt_link TEXT,
            bt_content TEXT,
            bt_wiki_content TEXT,
            bt_tags TEXT,
            bt_keywords TEXT,
            bt_nb_comments INTEGER,
            bt_allow_comments TINYINT,
            bt_statut TINYINT
        ); CREATE INDEX $if_not_exists dateidA ON articles ( bt_date, bt_id );");
        $GLOBALS['db_handle']->exec('
                INSERT INTO articles 
                    SELECT ID,bt_type,bt_id,bt_date,bt_title,bt_abstract,
                        bt_notes,bt_link,bt_content,bt_wiki_content,bt_tags,
                        bt_keywords,bt_nb_comments,bt_allow_comments,bt_statut
                    FROM articles_backup');
        $GLOBALS['db_handle']->exec('DROP TABLE articles_backup');
        $GLOBALS['db_handle']->commit();
    } catch (Exception $e) {
        $GLOBALS['db_handle']->rollBack();
        $errors['articles'] = $e->getMessage();
    }

    // add in rss
    $query = 'ALTER TABLE rss ADD COLUMN bt_bookmarked TINYINT';
    if (!($GLOBALS['db_handle']->exec($query))) {
        $errors['rss'] = 'Fail to update database > rss';
    }

    return (count($errors) === 0) ? true : $errors;
}

/**
 * proceed
 */

$success = true;
$message = '';

$message .= '<h3>Working on config\'s files</h3>';
if (($errors = upd_convert_config_files()) === true) {
    $message .= 'Config files have been updated';
} else {
    $success = false;
    foreach ($errors as $key => $errors) {
        $message .= '<h4>Fail with : '. $key .'</h4>';
        $message .= '<ul>';
        foreach ($errors as $error) {
            $message .= '<li>'. $error .'</li>';
        }
        $message .= '</ul>';
    }
}

$message .= '<h3>Working on database</h3>';
if ($success === true) {
    if (($errors = upd_db_update()) === true) {
        $message .= 'Database have been updated';
    } else {
        $success = false;
        $message .= '<h4>Fail on database</h4>';
        $message .= '<ul>';
        foreach ($errors as $key => $errors) {
            $message .= '<li>'. $key .' : '. $errors .'</li>';
        }
        $message .= '</ul>';
    }
} else {
    $message .= '<p>Can\'t work on database until config files are updated.</p>';
}

if ($success === true) {
    $message .= '<h3>Just for more step :</h3>';
    $message .= '
        <ul>
            <li>Use <a href="?">the install process for a new password</a></li>
            <li>Go on public side of your blog to check if everything is fine</li>
            <li>Go on admin side of your blog to check if everything is fine</li>
        </ul>';
    $message .= '<p>In case of errors, please make an issue at <a href="https://github.com/BlogoText/blogotext/issues">github / blogotext</a></p>';
}

echo $html_head;
echo $message;
echo $html_foot;


exit();
