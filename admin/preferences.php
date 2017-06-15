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


/**
 * Check SemVer validity.
 * source: https://github.com/morrisonlevi/SemVer/blob/master/src/League/SemVer/RegexParser.php
 */
function is_valid_version($version)
{
    $regex = '/^
        (?#major)(0|(?:[1-9][0-9]*))
        \\.
        (?#minor)(0|(?:[1-9][0-9]*))
        \\.
        (?#patch)(0|(?:[1-9][0-9]*))
        (?:
            -
            (?#pre-release)(
                (?:(?:0|(?:[1-9][0-9]*))|(?:[0-9]*[a-zA-Z-][a-zA-Z0-9-]*))
                (?:
                    \\.
                    (?:(?:0|(?:[1-9][0-9]*))|(?:[0-9]*[a-zA-Z-][a-zA-Z0-9-]*))
                )*
            )
        )?
        (?:
            \\+
            (?#build)(
                [0-9a-zA-Z-]+
                (?:\\.[a-zA-Z0-9-]+)*
            )
        )?
    $/x';
    return preg_match($regex, $version);
}

/**
 *
 */
function form_format_date($default)
{
    $day = jour_en_lettres(date('d'), date('m'), date('Y'));
    $month = mois_en_lettres(date('m'));
    $formats = array (
        date('d').'/'.date('m').'/'.date('Y'),              // 05/07/2011
        date('m').'/'.date('d').'/'.date('Y'),              // 07/05/2011
        date('d').' '.$month.' '.date('Y'),                // 05 juillet 2011
        $day.' '.date('d').' '.$month.' '.date('Y'),    // mardi 05 juillet 2011
        $day.' '.date('d').' '.$month,                  // mardi 05 juillet
        $month.' '.date('d').', '.date('Y'),               // juillet 05, 2011
        $day.', '.$month.' '.date('d').', '.date('Y'),  // mardi, juillet 05, 2011
        date('Y').'-'.date('m').'-'.date('d'),              // 2011-07-05
        substr($day, 0, 3).'. '.date('d').' '.$month,   // ven. 14 janvier
    );
    $form = '<label>'.$GLOBALS['lang']['pref_format_date'].'</label>';
    $form .= '<select name="format_date">';
    foreach ($formats as $option => $label) {
        $form .= '<option value="'.htmlentities($option).'"'.(($default == $option) ? ' selected="selected" ' : '').'>'.$label.'</option>';
    }
    $form .= '</select>';
    return $form;
}

/**
 *
 */
function form_timezone($default)
{
    $timezones = timezone_identifiers_list();
    $timezoneList = array();
    foreach ($timezones as $tz) {
        $spos = strpos($tz, '/');
        if ($spos !== false) {
            $continent = substr($tz, 0, $spos);
            $city = substr($tz, $spos + 1);
            $timezoneList[$continent][] = array('tz_name' => $tz, 'city' => $city);
        }
        if ($tz == 'UTC') {
            $timezoneList['UTC'][] = array('tz_name' => 'UTC', 'city' => 'UTC');
        }
    }
    $form = '<label>'.$GLOBALS['lang']['pref_fuseau_horaire'].'</label>';
    $form .= '<select name="fuseau_horaire">';
    foreach ($timezoneList as $continent => $zone) {
        $form .= '<optgroup label="'.ucfirst(strtolower($continent)).'">';
        foreach ($zone as $fuseau) {
            $form .= '<option value="'.htmlentities($fuseau['tz_name']).'"';
            $form .= ($default == $fuseau['tz_name']) ? ' selected="selected"' : '';
                $timeOffset = date_offset_get(date_create('now', timezone_open($fuseau['tz_name'])));
                $timeOffsetFormatted = sprintf(
                    '(UTC%s%02d:%02d) ',
                    ($timeOffset < 0) ? '–' : '+',
                    floor((abs($timeOffset) / 3600)),
                    floor((abs($timeOffset) % 3600) / 60)
                );
            $form .= '>'.$timeOffsetFormatted.' '.htmlentities($fuseau['city']).'</option>';
        }
        $form .= '</optgroup>';
    }
    $form .= '</select>';
    return $form;
}

/**
 *
 */
function form_format_hour($default)
{
    $formats = array (
        date('H:i:s'),    // 23:56:04
        date('H:i'),      // 23:56
        date('h:i:s A'),  // 11:56:04 PM
        date('h:i A'),    // 11:56 PM
    );
    $form = '<label>'.$GLOBALS['lang']['pref_format_heure'].'</label>';
    $form .= '<select name="format_heure">';
    foreach ($formats as $option => $label) {
        $form .= '<option value="'.htmlentities($option).'"'.(($default == $option) ? ' selected="selected" ' : '').'>'.htmlentities($label).'</option>';
    }
    $form .= '</select>';
    return $form;
}

/**
 *
 */
function form_language($default)
{
    $form = '<label>'.$GLOBALS['lang']['pref_langue'].'</label>';
    $form .= '<select name="langue">';
    foreach ($GLOBALS['langs'] as $option => $label) {
        $form .= '<option value="'.htmlentities($option).'"'.(($default == $option) ? ' selected="selected" ' : '').'>'.$label.'</option>';
    }
    $form .= '</select>';
    return $form;
}

/**
 *
 */
function list_themes($path)
{
    if ($handler = opendir($path)) {
        while ($folders = readdir($handler)) {
            if (is_dir($path.'/'.$folders) && is_file($path.'/'.$folders.'/list.html')) {
                $themes[$folders] = $folders;
            }
        }
        closedir($handler);
    }
    if (isset($themes)) {
        return $themes;
    }
}

/**
 *
 */
function validate_form_preferences()
{
    $errors = array();
    $token = (string)filter_input(INPUT_POST, 'token');
    $author = (string)filter_input(INPUT_POST, 'auteur');
    $email = (string)filter_input(INPUT_POST, 'email');
    $root = (string)filter_input(INPUT_POST, 'racine');
    $username = (string)filter_input(INPUT_POST, 'identifiant');
    $password = (string)filter_input(INPUT_POST, 'mdp');
    $newPassword = (string)filter_input(INPUT_POST, 'mdp_rep');

    if (!check_token($token)) {
        $errors[] = $GLOBALS['lang']['err_wrong_token'];
    }
    if (!strlen(trim($author))) {
        $errors[] = $GLOBALS['lang']['err_prefs_auteur'];
    }
    if ($GLOBALS['require_email'] == 1) {
        if (!preg_match('#^[\w.+~\'*-]+@[\w.-]+\.[a-zA-Z]{2,6}$#i', trim($email))) {
            $errors[] = $GLOBALS['lang']['err_prefs_email'] ;
        }
    }
    if (!preg_match('#^(https?://).*/$#', $root)) {
        $errors[] = $GLOBALS['lang']['err_prefs_racine_slash'];
    }
    if (!strlen(trim($username))) {
        $errors[] = $GLOBALS['lang']['err_prefs_identifiant'];
    }
    if ($username != USER_LOGIN && !strlen($password)) {
        $errors[] = $GLOBALS['lang']['err_prefs_id_mdp'];
    }
    if (preg_match('#[=\'"\\\\|]#iu', $username)) {
        $errors[] = $GLOBALS['lang']['err_prefs_id_syntaxe'];
    }
    if ($password && !password_verify(hash_pass($password, true), USER_PWHASH)) {
        $errors[] = $GLOBALS['lang']['err_prefs_oldmdp'];
    }
    if ($password && strlen($newPassword) < 6) {
        $errors[] = $GLOBALS['lang']['err_prefs_mdp'];
    }
    if (!$newPassword xor !$password) {
        $errors[] = $GLOBALS['lang']['err_prefs_newmdp'] ;
    }
    return $errors;
}


/**
 * v
 */
function display_form_prefs($errors = '')
{
    $submitBox = '<div class="submit-bttns">';
    $submitBox .= hidden_input('_verif_envoi', 1);
    $submitBox .= hidden_input('token', new_token());
    $submitBox .= '<button class="submit button-cancel" type="button" onclick="annuler(\'preferences.php\');" >'.$GLOBALS['lang']['annuler'].'</button>';
    $submitBox .= '<button class="submit button-submit" type="submit" name="enregistrer">'.$GLOBALS['lang']['enregistrer'].'</button>';
    $submitBox .= '</div>';

    echo '<form id="preferences" method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'" >' ;
        echo erreurs($errors);
        $fieldsetUser = '<div role="group" class="pref">';
        $fieldsetUser .= '<div class="form-legend"><legend class="legend-user">'.$GLOBALS['lang']['prefs_legend_utilisateur'].'</legend></div>';

        $fieldsetUser .= '<div class="form-lines">';
        $fieldsetUser .= '<p>';
        $fieldsetUser .= '<label for="auteur">'.$GLOBALS['lang']['pref_auteur'].'</label>';
        $fieldsetUser .= '<input type="text" id="auteur" name="auteur" size="30" value="'.((empty($GLOBALS['auteur'])) ? htmlspecialchars(USER_LOGIN) : $GLOBALS['auteur']).'" class="text" />';
        $fieldsetUser .= '</p>';

        $fieldsetUser .= '<p>';
        $fieldsetUser .= '<label for="email">'.$GLOBALS['lang']['pref_email'].'</label>';
        $fieldsetUser .= '<input type="text" id="email" name="email" size="30" value="'.$GLOBALS['email'].'" class="text" />';
        $fieldsetUser .= '</p>';

        $fieldsetUser .= '<p>';
        $fieldsetUser .= '<label for="nom_du_site">'.$GLOBALS['lang']['pref_nom_site'].'</label>';
        $fieldsetUser .= '<input type="text" id="nom_du_site" name="nom_du_site" size="30" value="'.$GLOBALS['nom_du_site'].'" class="text" />';
        $fieldsetUser .= '</p>';

        $fieldsetUser .= '<p>';
        $fieldsetUser .= '<label for="racine">'.$GLOBALS['lang']['pref_racine'].'</label>';
        $fieldsetUser .= '<input type="text" id="racine" name="racine" size="30" value="'.$GLOBALS['racine'].'" class="text" />';
        $fieldsetUser .= '</p>';

        $fieldsetUser .= '<p>';
        $fieldsetUser .= '<label for="description">'.$GLOBALS['lang']['label_dp_description'].'</label>';
        $fieldsetUser .= '<textarea id="description" name="description" cols="35" rows="2" class="text" >'.$GLOBALS['description'].'</textarea>';
        $fieldsetUser .= '</p>';

        $fieldsetUser .= '<p>';
        $fieldsetUser .= '<label for="keywords">'.$GLOBALS['lang']['pref_keywords'].'</label>';
        $fieldsetUser .= '<textarea id="keywords" name="keywords" cols="35" rows="2" class="text" >'.$GLOBALS['keywords'].'</textarea>';
        $fieldsetUser .= '</p>';
        $fieldsetUser .= '</div>';

        $fieldsetUser .= $submitBox;

        $fieldsetUser .= '</div>';
    echo $fieldsetUser;

        $fieldsetSecurity = '<div role="group" class="pref">';
        $fieldsetSecurity .= '<div class="form-legend"><legend class="legend-securite">'.$GLOBALS['lang']['prefs_legend_securite'].'</legend></div>';

        $fieldsetSecurity .= '<div class="form-lines">';
        $fieldsetSecurity .= '<p>';
        $fieldsetSecurity .= '<label for="identifiant">'.$GLOBALS['lang']['pref_identifiant'].'</label>';
        $fieldsetSecurity .= '<input type="text" id="identifiant" name="identifiant" size="30" value="'.htmlspecialchars(USER_LOGIN).'" class="text" />';
        $fieldsetSecurity .= '</p>';

        $fieldsetSecurity .= '<p>';
        $fieldsetSecurity .= '<label for="mdp">'.$GLOBALS['lang']['pref_mdp'].'</label>';
        $fieldsetSecurity .= '<input type="password" id="mdp" name="mdp" size="30" value="" class="text" autocomplete="off" />';
        $fieldsetSecurity .= '</p>';

        $fieldsetSecurity .= '<p>';
        $fieldsetSecurity .= '<label for="mdp_rep">'.$GLOBALS['lang']['pref_mdp_nouv'].'</label>';
        $fieldsetSecurity .= '<input type="password" id="mdp_rep" name="mdp_rep" size="30" value="" class="text" autocomplete="off" />';
        $fieldsetSecurity .= '</p>';
        $fieldsetSecurity .= '</div>';

        $fieldsetSecurity .= $submitBox;

        $fieldsetSecurity .= '</div>';
    echo $fieldsetSecurity;

        $fieldsetAppearance = '<div role="group" class="pref">';
        $fieldsetAppearance .= '<div class="form-legend"><legend class="legend-apparence">'.$GLOBALS['lang']['prefs_legend_apparence'].'</legend></div>';

        $fieldsetAppearance .= '<div class="form-lines">';
        $fieldsetAppearance .= '<p>';
        $fieldsetAppearance .= form_select('theme_choisi', list_themes(DIR_THEMES), $GLOBALS['theme_choisi'], $GLOBALS['lang']['pref_theme']);
        $fieldsetAppearance .= '</p>';

        $fieldsetAppearance .= '<p>';
        $fieldsetAppearance .= form_select('max_bill_acceuil', array(5 => 5, 10 => 10, 15 => 15, 20 => 20,  25 => 25, 50 => 50), $GLOBALS['max_bill_acceuil'], $GLOBALS['lang']['pref_nb_maxi']);
        $fieldsetAppearance .= '</p>';

        $nbs = array(10 => 10, 25 => 25, 50 => 50, 100 => 100, 300 => 300, -1 => $GLOBALS['lang']['pref_all']);
        $fieldsetAppearance .= '<p>';
        $fieldsetAppearance .= form_select('max_bill_admin', $nbs, $GLOBALS['max_bill_admin'], $GLOBALS['lang']['pref_nb_list']);
        $fieldsetAppearance .= '</p>';

        $fieldsetAppearance .= '<p>';
        $fieldsetAppearance .= form_select('max_comm_admin', $nbs, $GLOBALS['max_comm_admin'], $GLOBALS['lang']['pref_nb_list_com']);
        $fieldsetAppearance .= '</p>';

        $fieldsetAppearance .= '<p>';
        $fieldsetAppearance .= form_checkbox('afficher_rss', $GLOBALS['afficher_rss'], $GLOBALS['lang']['pref_afficher_rss']);
        $fieldsetAppearance .= '</p>';

        $fieldsetAppearance .= '<p>';
        $fieldsetAppearance .= form_checkbox('afficher_liens', $GLOBALS['afficher_liens'], $GLOBALS['lang']['pref_afficher_liens']);
        $fieldsetAppearance .= '</p>';
        $fieldsetAppearance .= '</div>';

        $fieldsetAppearance .= $submitBox;

        $fieldsetAppearance .= '</div>';
    echo $fieldsetAppearance;

        $fieldsetDateHour = '<div role="group" class="pref">';
        $fieldsetDateHour .= '<div class="form-legend"><legend class="legend-dateheure">'.$GLOBALS['lang']['prefs_legend_langdateheure'].'</legend></div>';

        $fieldsetDateHour .= '<div class="form-lines">';
        $fieldsetDateHour .= '<p>';
        $fieldsetDateHour .= form_language($GLOBALS['lang']['id']);
        $fieldsetDateHour .= '</p>';

        $fieldsetDateHour .= '<p>';
        $fieldsetDateHour .= form_format_date($GLOBALS['format_date']);
        $fieldsetDateHour .= '</p>';

        $fieldsetDateHour .= '<p>';
        $fieldsetDateHour .= form_format_hour($GLOBALS['format_heure']);
        $fieldsetDateHour .= '</p>';

        $fieldsetDateHour .= '<p>';
        $fieldsetDateHour .= form_timezone($GLOBALS['fuseau_horaire']);
        $fieldsetDateHour .= '</p>';
        $fieldsetDateHour .= '</div>';

        $fieldsetDateHour .= $submitBox;

        $fieldsetDateHour .= '</div>';
    echo $fieldsetDateHour;

        $fieldsetParameters = '<div role="group" class="pref">';
        $fieldsetParameters .= '<div class="form-legend"><legend class="legend-blogcomm">'.$GLOBALS['lang']['prefs_legend_configblog'].'</legend></div>';

        $fieldsetParameters .= '<div class="form-lines">';
        $fieldsetParameters .= '<p>';
        $fieldsetParameters .= form_checkbox('activer_categories', $GLOBALS['activer_categories'], $GLOBALS['lang']['pref_categories']);
        $fieldsetParameters .= '</p>';

        $fieldsetParameters .= '<p>';
        $fieldsetParameters .= form_checkbox('auto_keywords', $GLOBALS['automatic_keywords'], $GLOBALS['lang']['pref_automatic_keywords']);
        $fieldsetParameters .= '</p>';

        $fieldsetParameters .= '<p>';
        $fieldsetParameters .= form_checkbox('global_comments', $GLOBALS['global_com_rule'], $GLOBALS['lang']['pref_allow_global_coms']);
        $fieldsetParameters .= '</p>';

        $fieldsetParameters .= '<p>';
        $fieldsetParameters .= form_checkbox('require_email', $GLOBALS['require_email'], $GLOBALS['lang']['pref_force_email']);
        $fieldsetParameters .= '</p>';

        $fieldsetParameters .= '<p>';
        $fieldsetParameters .= form_checkbox('alert_author', $GLOBALS['alert_author'], $GLOBALS['lang']['pref_alert_author']);
        $fieldsetParameters .= '</p>';

        $fieldsetParameters .= '<p>';
        $fieldsetParameters .= form_select('comm_defaut_status', array($GLOBALS['lang']['pref_comm_white_list'], $GLOBALS['lang']['pref_comm_black_list']), $GLOBALS['comm_defaut_status'], $GLOBALS['lang']['pref_comm_BoW_list']);
        $fieldsetParameters .= '</p>';
        $fieldsetParameters .= '</div>';

        $fieldsetParameters .= $submitBox;

        $fieldsetParameters .= '</div>';
    echo $fieldsetParameters;

    if ($GLOBALS['afficher_liens']) {
        $fieldsetLinks = '<div role="group" class="pref">';
        $fieldsetLinks .= '<div class="form-legend"><legend class="legend-links">'.$GLOBALS['lang']['prefs_legend_configlinx'].'</legend></div>';

        $fieldsetLinks .= '<div class="form-lines">';
        // nb liens côté admin
        $nbs = array(50 => 50, 100 => 100, 200 => 200, 300 => 300, 500 => 500, -1 => $GLOBALS['lang']['pref_all']);

        $fieldsetLinks .= '<p>';
        $fieldsetLinks .= form_select('nb_list_linx', $nbs, $GLOBALS['nb_list_linx'], $GLOBALS['lang']['pref_nb_list_linx']);
        $fieldsetLinks .= '</p>';

        // partage de fichiers !pages : télécharger dans fichiers automatiquement ?
        $nbs = array($GLOBALS['lang']['non'], $GLOBALS['lang']['oui'], $GLOBALS['lang']['pref_ask_everytime']);

        $fieldsetLinks .= '<p>';
        $fieldsetLinks .= form_select('dl_link_to_files', $nbs, $GLOBALS['dl_link_to_files'], $GLOBALS['lang']['pref_linx_dl_auto']);
        $fieldsetLinks .= '</p>';

        // lien à glisser sur la barre des favoris
        $link = explode('/', dirname($_SERVER['SCRIPT_NAME']));
        $fieldsetLinks .= '<p>';
        $fieldsetLinks .= '<label>'.$GLOBALS['lang']['pref_label_bookmark_lien'].'</label>';
        $fieldsetLinks .= '<a class="dnd-to-favs" onclick="alert(\''.$GLOBALS['lang']['pref_label_bookmark_lien'].'\');return false;" href="javascript:javascript:(function(){window.open(\''.$GLOBALS['racine'].$link[count($link) - 1].'/links.php?url=\'+encodeURIComponent(location.href));})();">Save link</a>';
        $fieldsetLinks .= '</p>';
        $fieldsetLinks .= '</div>';

        $fieldsetLinks .= $submitBox;

        $fieldsetLinks .= '</div>';
        echo $fieldsetLinks;
    } else {
        echo hidden_input('nb_list_linx', 50);
        echo hidden_input('dl_link_to_files', 1);
    }

    if ($GLOBALS['afficher_rss']) {
        $fieldsetFeeds = '<div role="group" class="pref">';
        $fieldsetFeeds .= '<div class="form-legend"><legend class="legend-rss">'.$GLOBALS['lang']['prefs_legend_configrss'].'</legend></div>';
        $fieldsetFeeds .= '<div class="form-lines">';

        $nbs = array(10 => 10, 25 => 25, 50 => 50, 100 => 100, 300 => 300);
        $fieldsetFeeds .= '<p>';
        $fieldsetFeeds .= form_select('max_rss_admin', $nbs, $GLOBALS['max_rss_admin'], $GLOBALS['lang']['pref_nb_list']);
        $fieldsetFeeds .= '</p>';

        $fieldsetFeeds .= '<p>';
        $feed = explode('/', dirname($_SERVER['SCRIPT_NAME']));
        $fieldsetFeeds .= '<label>'.$GLOBALS['lang']['pref_label_crontab_rss'].'</label>';
        $fieldsetFeeds .= '<a onclick="prompt(\''.$GLOBALS['lang']['pref_alert_crontab_rss'].'\', \'0 *  *   *   *   wget --spider -qO- '.$GLOBALS['racine'].$feed[count($feed) - 1].'/_rss.ajax.php?guid='.BLOG_UID.'&refresh_all'.'\');return false;" href="#">Afficher ligne Cron</a>';
        $fieldsetFeeds .= '</p>';

        $fieldsetFeeds .= '<p>';
        $fieldsetFeeds .= '<label>'.$GLOBALS['lang']['pref_rss_go_to_imp-export'].'</label>';
        $fieldsetFeeds .= '<a href="maintenance.php">'.$GLOBALS['lang']['label_import-export'].'</a>';
        $fieldsetFeeds .= '</p>';

        $fieldsetFeeds .= '<p>';
        $fieldsetFeeds .= '</p>';

        $fieldsetFeeds .= '</div>';

        $fieldsetFeeds .= $submitBox;
        $fieldsetFeeds .= '</div>';
        echo $fieldsetFeeds;
    } else {
        echo hidden_input('max_rss_admin', 10);
    }

    $fieldsetMaintenance = '<div role="group" class="pref">';
    $fieldsetMaintenance .= '<div class="form-legend"><legend class="legend-sweep">'.$GLOBALS['lang']['titre_maintenance'].'</legend></div>';

    $fieldsetMaintenance .= '<div class="form-lines">';
    $fieldsetMaintenance .= '<p>';
    $fieldsetMaintenance .= form_checkbox('auto_check_updates', $GLOBALS['auto_check_updates'], $GLOBALS['lang']['pref_check_update']);
    $fieldsetMaintenance .= '</p>';

    $fieldsetMaintenance .= '<p>';
    $fieldsetMaintenance .= '<label>'.$GLOBALS['lang']['pref_go_to_maintenance'].'</label>';
    $fieldsetMaintenance .= '<a href="maintenance.php">Maintenance</a>';
    $fieldsetMaintenance .= '</p>';
    $fieldsetMaintenance .= '</div>';

    $fieldsetMaintenance .= $submitBox;

    $fieldsetMaintenance .= '</div>';
    echo $fieldsetMaintenance;

    // Check if a new BlogoText version is available (code from Shaarli, by Sebsauvage).
    // Get latest version number at most once a day.
    if ($GLOBALS['auto_check_updates'] == 1) {
        $versionFile = '../VERSION';
        if (!is_file($versionFile) or filemtime($versionFile) < time() - 24 * 60 * 60) {
            $versionHitUrl = 'https://raw.githubusercontent.com/BlogoText/blogotext/master/VERSION';
            $response = request_external_files(array($versionHitUrl), 6);
            $version = trim($response[$versionHitUrl]['body']);
            $lastVersion = (is_valid_version($version)) ? $version : BLOGOTEXT_VERSION;
            file_put_contents($versionFile, $lastVersion, LOCK_EX);
        }

        // Compare versions
        $newestVersion = file_get_contents($versionFile);
        if (version_compare($newestVersion, BLOGOTEXT_VERSION) == 1) {
            $fieldsetUpdate = '<div role="group" class="pref">';
            $fieldsetUpdate .= '<div class="form-legend"><legend class="legend-update">'.$GLOBALS['lang']['maint_chk_update'].'</legend></div>';
            $fieldsetUpdate .= '<div class="form-lines">';
            $fieldsetUpdate .= '<p>';
            $fieldsetUpdate .= '<label>'.$GLOBALS['lang']['maint_update_youisbad'].'</label>';
            $fieldsetUpdate .= '<b>'.$newestVersion.'</b>';
            $fieldsetUpdate .= '</p>';
            $fieldsetUpdate .= '<p>';
            $fieldsetUpdate .= '<label>'.$GLOBALS['lang']['maint_update_go_dl_it'].'</label>';
            $fieldsetUpdate .= '<a href="'.BLOGOTEXT_SITE.'">'.parse_url(BLOGOTEXT_SITE, 1).parse_url(BLOGOTEXT_SITE, 5).'</a>';
            $fieldsetUpdate .= '</p>';
            $fieldsetUpdate .= '</div>';
            $fieldsetUpdate .= '</div>';
            echo $fieldsetUpdate;
        }
    }

    echo '</form>';
}


/**
 * process
 */

$errorsForm = array();

if (filter_input(INPUT_POST, '_verif_envoi') !== null) {
    $errorsForm = validate_form_preferences();
    if (!$errorsForm) {
        // devnote : I suppose we take $_POST['mdp_rep'] (alt. $_POST['mdp'])
        $username = (string)filter_input(INPUT_POST, 'identifiant');
        $password = (string)filter_input(INPUT_POST, 'mdp_rep');
        if (auth_write_user_login_file($username, $password) && fichier_prefs()) {
            redirection(basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_prefs_maj');
        }
        $errorsForm[] = $GLOBALS['lang']['err_prefs_write'];
    }
}


/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['preferences']);

    echo '<div id="header">';
        echo '<div id="top">';
        tpl_show_msg();
        echo tpl_show_topnav($GLOBALS['lang']['preferences']);
        echo '</div>';
    echo '</div>';
echo '<div id="axe">';
echo '<div id="page">';

display_form_prefs($errorsForm);

echo '<script src="style/javascript.js"></script>';

echo tpl_get_footer($begin);
