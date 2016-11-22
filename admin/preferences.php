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

$begin = microtime(true);
define('BT_ROOT', '../');

require_once '../inc/inc.php';
require_once '../inc/auth.php';

auth_ttl();

$erreurs_form = array();


function form_format_date($defaut)
{
    $jour_l = jour_en_lettres(date('d'), date('m'), date('Y'));
    $mois_l = mois_en_lettres(date('m'));
    $formats = array (
        date('d').'/'.date('m').'/'.date('Y'),              // 05/07/2011
        date('m').'/'.date('d').'/'.date('Y'),              // 07/05/2011
        date('d').' '.$mois_l.' '.date('Y'),                // 05 juillet 2011
        $jour_l.' '.date('d').' '.$mois_l.' '.date('Y'),    // mardi 05 juillet 2011
        $jour_l.' '.date('d').' '.$mois_l,                  // mardi 05 juillet
        $mois_l.' '.date('d').', '.date('Y'),               // juillet 05, 2011
        $jour_l.', '.$mois_l.' '.date('d').', '.date('Y'),  // mardi, juillet 05, 2011
        date('Y').'-'.date('m').'-'.date('d'),              // 2011-07-05
        substr($jour_l, 0, 3).'. '.date('d').' '.$mois_l,   // ven. 14 janvier
    );
    $form = "\t".'<label>'.$GLOBALS['lang']['pref_format_date'].'</label>'."\n";
    $form .= "\t".'<select name="format_date">'."\n";
    foreach ($formats as $option => $label) {
        $form .= "\t\t".'<option value="'.htmlentities($option).'"'.(($defaut == $option) ? ' selected="selected" ' : '').'>'.$label.'</option>'."\n";
    }
    $form .= "\t".'</select>'."\n";
    return $form;
}

function form_fuseau_horaire($defaut)
{
    $all_timezones = timezone_identifiers_list();
    $liste_fuseau = array();
    $cities = array();
    foreach ($all_timezones as $tz) {
        $spos = strpos($tz, '/');
        if ($spos !== false) {
            $continent = substr($tz, 0, $spos);
            $city = substr($tz, $spos+1);
            $liste_fuseau[$continent][] = array('tz_name' => $tz, 'city' => $city);
        }
        if ($tz == 'UTC') {
            $liste_fuseau['UTC'][] = array('tz_name' => 'UTC', 'city' => 'UTC');
        }
    }
    $form = '<label>'.$GLOBALS['lang']['pref_fuseau_horaire'].'</label>'."\n";
    $form .= '<select name="fuseau_horaire">'."\n";
    foreach ($liste_fuseau as $continent => $zone) {
        $form .= "\t".'<optgroup label="'.ucfirst(strtolower($continent)).'">'."\n";
        foreach ($zone as $fuseau) {
            $form .= "\t\t".'<option value="'.htmlentities($fuseau['tz_name']).'"';
            $form .= ($defaut == $fuseau['tz_name']) ? ' selected="selected"' : '';
                $timeoffset = date_offset_get(date_create('now', timezone_open($fuseau['tz_name'])));
                $formated_toffset = '(UTC'.(($timeoffset < 0) ? '–' : '+').str2(floor((abs($timeoffset)/3600))) .':'.str2(floor((abs($timeoffset)%3600)/60)) .') ';
            $form .= '>'.$formated_toffset.' '.htmlentities($fuseau['city']).'</option>'."\n";
        }
        $form .= "\t".'</optgroup>'."\n";
    }
    $form .= '</select>'."\n";
    return $form;
}

function form_format_heure($defaut)
{
    $formats = array (
        date('H:i:s'),    // 23:56:04
        date('H:i'),       // 23:56
        date('h:i:s A'),  // 11:56:04 PM
        date('h:i A'),     // 11:56 PM
    );
    $form = '<label>'.$GLOBALS['lang']['pref_format_heure'].'</label>'."\n";
    $form .= '<select name="format_heure">'."\n";
    foreach ($formats as $option => $label) {
        $form .= "\t".'<option value="'.htmlentities($option).'"'.(($defaut == $option) ? ' selected="selected" ' : '').'>'.htmlentities($label).'</option>'."\n";
    }
    $form .= "\t".'</select>'."\n";
    return $form;
}

function form_langue($defaut)
{
    $form = '<label>'.$GLOBALS['lang']['pref_langue'].'</label>'."\n";
    $form .= '<select name="langue">'."\n";
    foreach ($GLOBALS['langs'] as $option => $label) {
        $form .= "\t".'<option value="'.htmlentities($option).'"'.(($defaut == $option) ? ' selected="selected" ' : '').'>'.$label.'</option>'."\n";
    }
    $form .= '</select>'."\n";
    return $form;
}

function liste_themes($chemin)
{
    if ($ouverture = opendir($chemin)) {
        while ($dossiers = readdir($ouverture)) {
            if (is_file($chemin.'/'.$dossiers.'/list.html')) {
                $themes[$dossiers] = $dossiers;
            }
        }
        closedir($ouverture);
    }
    if (isset($themes)) {
        return $themes;
    }
}



// Preferences form
function afficher_form_prefs($erreurs = '')
{
    $submit_box = '<div class="submit-bttns">'."\n";
    $submit_box .= hidden_input('_verif_envoi', '1');
    $submit_box .= hidden_input('token', new_token());
    $submit_box .= '<button class="submit button-cancel" type="button" onclick="annuler(\'preferences.php\');" >'.$GLOBALS['lang']['annuler'].'</button>'."\n";
    $submit_box .= '<button class="submit button-submit" type="submit" name="enregistrer">'.$GLOBALS['lang']['enregistrer'].'</button>'."\n";
    $submit_box .= '</div>'."\n";

    echo '<form id="preferences" method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'" >' ;
        echo erreurs($erreurs);
        $fld_user = '<div role="group" class="pref">'; /* no fieldset because browset can’t style them correctly */
        $fld_user .= '<div class="form-legend"><legend class="legend-user">'.$GLOBALS['lang']['prefs_legend_utilisateur'].'</legend></div>'."\n";

        $fld_user .= '<div class="form-lines">'."\n";
        $fld_user .= '<p>'."\n";
        $fld_user .= "\t".'<label for="auteur">'.$GLOBALS['lang']['pref_auteur'].'</label>'."\n";
        $fld_user .= "\t".'<input type="text" id="auteur" name="auteur" size="30" value="'.((empty($GLOBALS['auteur'])) ? htmlspecialchars(USER_LOGIN) : $GLOBALS['auteur']).'" class="text" />'."\n";
        $fld_user .= '</p>'."\n";

        $fld_user .= '<p>'."\n";
        $fld_user .= "\t".'<label for="email">'.$GLOBALS['lang']['pref_email'].'</label>'."\n";
        $fld_user .= "\t".'<input type="text" id="email" name="email" size="30" value="'.$GLOBALS['email'].'" class="text" />'."\n";
        $fld_user .= '</p>'."\n";

        $fld_user .= '<p>'."\n";
        $fld_user .= "\t".'<label for="nomsite">'.$GLOBALS['lang']['pref_nom_site'].'</label>'."\n";
        $fld_user .= "\t".'<input type="text" id="nomsite" name="nomsite" size="30" value="'.$GLOBALS['nom_du_site'].'" class="text" />'."\n";
        $fld_user .= '</p>'."\n";

        $fld_user .= '<p>'."\n";
        $fld_user .= "\t".'<label for="racine">'.$GLOBALS['lang']['pref_racine'].'</label>'."\n";
        $fld_user .= "\t".'<input type="text" id="racine" name="racine" size="30" value="'.$GLOBALS['racine'].'" class="text" />'."\n";
        $fld_user .= '</p>'."\n";

        $fld_user .= '<p>'."\n";
        $fld_user .= "\t".'<label for="description">'.$GLOBALS['lang']['label_dp_description'].'</label>'."\n";
        $fld_user .= "\t".'<textarea id="description" name="description" cols="35" rows="2" class="text" >'.$GLOBALS['description'].'</textarea>'."\n";
        $fld_user .= '</p>'."\n";

        $fld_user .= '<p>'."\n";
        $fld_user .= "\t".'<label for="keywords">'.$GLOBALS['lang']['pref_keywords'].'</label>';
        $fld_user .= "\t".'<textarea id="keywords" name="keywords" cols="35" rows="2" class="text" >'.$GLOBALS['keywords'].'</textarea>'."\n";
        $fld_user .= '</p>'."\n";
        $fld_user .= '</div>'."\n";

        $fld_user .= $submit_box;

        $fld_user .= '</div>';
    echo $fld_user;

        $fld_securite = '<div role="group" class="pref">';
        $fld_securite .= '<div class="form-legend"><legend class="legend-securite">'.$GLOBALS['lang']['prefs_legend_securite'].'</legend></div>'."\n";

        $fld_securite .= '<div class="form-lines">'."\n";
        $fld_securite .= '<p>'."\n";
        $fld_securite .= "\t".'<label for="identifiant">'.$GLOBALS['lang']['pref_identifiant'].'</label>'."\n";
        $fld_securite .= "\t".'<input type="text" id="identifiant" name="identifiant" size="30" value="'.htmlspecialchars(USER_LOGIN).'" class="text" />'."\n";
        $fld_securite .= '</p>'."\n";

        $fld_securite .= '<p>'."\n";
        $fld_securite .= "\t".'<label for="mdp">'.$GLOBALS['lang']['pref_mdp'].'</label>';
        $fld_securite .= "\t".'<input type="password" id="mdp" name="mdp" size="30" value="" class="text" autocomplete="off" />'."\n";
        $fld_securite .= '</p>'."\n";

        $fld_securite .= '<p>'."\n";
        $fld_securite .= "\t".'<label for="mdp_rep">'.$GLOBALS['lang']['pref_mdp_nouv'].'</label>';
        $fld_securite .= "\t".'<input type="password" id="mdp_rep" name="mdp_rep" size="30" value="" class="text" autocomplete="off" />'."\n";
        $fld_securite .= '</p>'."\n";
        $fld_securite .= '</div>';

        $fld_securite .= $submit_box;

        $fld_securite .= '</div>';
    echo $fld_securite;

        $fld_apparence = '<div role="group" class="pref">';
        $fld_apparence .= '<div class="form-legend"><legend class="legend-apparence">'.$GLOBALS['lang']['prefs_legend_apparence'].'</legend></div>'."\n";

        $fld_apparence .= '<div class="form-lines">'."\n";
        $fld_apparence .= '<p>'."\n";
        $fld_apparence .= form_select('theme', liste_themes(BT_ROOT.DIR_THEMES), $GLOBALS['theme_choisi'], $GLOBALS['lang']['pref_theme']);
        $fld_apparence .= '</p>'."\n";

        $fld_apparence .= '<p>'."\n";
        $fld_apparence .= form_select('nb_maxi', array(5 => 5, 10 => 10, 15 => 15, 20 => 20,  25 => 25, 50 => 50), $GLOBALS['max_bill_acceuil'], $GLOBALS['lang']['pref_nb_maxi']);
        $fld_apparence .= '</p>'."\n";

        $nbs = array(10 => 10, 25 => 25, 50 => 50, 100 => 100, 300 => 300, -1 => $GLOBALS['lang']['pref_all']);
        $fld_apparence .= '<p>'."\n";
        $fld_apparence .= form_select('nb_list', $nbs, $GLOBALS['max_bill_admin'], $GLOBALS['lang']['pref_nb_list']);
        $fld_apparence .= '</p>'."\n";

        $fld_apparence .= '<p>'."\n";
        $fld_apparence .= form_select('nb_list_com', $nbs, $GLOBALS['max_comm_admin'], $GLOBALS['lang']['pref_nb_list_com']);
        $fld_apparence .= '</p>'."\n";

        $fld_apparence .= '<p>'."\n";
        $fld_apparence .= form_checkbox('aff_onglet_rss', $GLOBALS['onglet_rss'], $GLOBALS['lang']['pref_afficher_rss']);
        $fld_apparence .= '</p>'."\n";

        $fld_apparence .= '<p>'."\n";
        $fld_apparence .= form_checkbox('aff_onglet_liens', $GLOBALS['onglet_liens'], $GLOBALS['lang']['pref_afficher_liens']);
        $fld_apparence .= '</p>'."\n";
        $fld_apparence .= '</div>'."\n";

        $fld_apparence .= $submit_box;

        $fld_apparence .= '</div>';
    echo $fld_apparence;

        $fld_dateheure = '<div role="group" class="pref">';
        $fld_dateheure .= '<div class="form-legend"><legend class="legend-dateheure">'.$GLOBALS['lang']['prefs_legend_langdateheure'].'</legend></div>'."\n";

        $fld_dateheure .= '<div class="form-lines">'."\n";
        $fld_dateheure .= '<p>'."\n";
        $fld_dateheure .= form_langue($GLOBALS['lang']['id']);
        $fld_dateheure .= '</p>'."\n";

        $fld_dateheure .= '<p>'."\n";
        $fld_dateheure .= form_format_date($GLOBALS['format_date']);
        $fld_dateheure .= '</p>'."\n";

        $fld_dateheure .= '<p>'."\n";
        $fld_dateheure .= form_format_heure($GLOBALS['format_heure']);
        $fld_dateheure .= '</p>'."\n";

        $fld_dateheure .= '<p>'."\n";
        $fld_dateheure .= form_fuseau_horaire($GLOBALS['fuseau_horaire']);
        $fld_dateheure .= '</p>'."\n";
        $fld_dateheure .= '</div>'."\n";

        $fld_dateheure .= $submit_box;

        $fld_dateheure .= '</div>';
    echo $fld_dateheure;

        $fld_cfg_blog = '<div role="group" class="pref">';
        $fld_cfg_blog .= '<div class="form-legend"><legend class="legend-blogcomm">'.$GLOBALS['lang']['prefs_legend_configblog'].'</legend></div>'."\n";

        $fld_cfg_blog .= '<div class="form-lines">'."\n";
        $fld_cfg_blog .= '<p>'."\n";
        $fld_cfg_blog .= form_checkbox('activer_categories', $GLOBALS['activer_categories'], $GLOBALS['lang']['pref_categories']);
        $fld_cfg_blog .= '</p>'."\n";

        $fld_cfg_blog .= '<p>'."\n";
        $fld_cfg_blog .= form_checkbox('auto_keywords', $GLOBALS['automatic_keywords'], $GLOBALS['lang']['pref_automatic_keywords']);
        $fld_cfg_blog .= '</p>'."\n";

        $fld_cfg_blog .= '<p>'."\n";
        $fld_cfg_blog .= form_checkbox('global_comments', $GLOBALS['global_com_rule'], $GLOBALS['lang']['pref_allow_global_coms']);
        $fld_cfg_blog .= '</p>'."\n";

        $fld_cfg_blog .= '<p>'."\n";
        $fld_cfg_blog .= form_checkbox('require_email', $GLOBALS['require_email'], $GLOBALS['lang']['pref_force_email']);
        $fld_cfg_blog .= '</p>'."\n";

        $fld_cfg_blog .= '<p>'."\n";
        $fld_cfg_blog .= form_checkbox('alert_author', $GLOBALS['alert_author'], $GLOBALS['lang']['pref_alert_author']);
        $fld_cfg_blog .= '</p>'."\n";

        $fld_cfg_blog .= '<p>'."\n";
        $fld_cfg_blog .= form_select('comm_defaut_status', array($GLOBALS['lang']['pref_comm_white_list'], $GLOBALS['lang']['pref_comm_black_list']), $GLOBALS['comm_defaut_status'], $GLOBALS['lang']['pref_comm_BoW_list']);
        $fld_cfg_blog .= '</p>'."\n";
        $fld_cfg_blog .= '</div>'."\n";

        $fld_cfg_blog .= $submit_box;

        $fld_cfg_blog .= '</div>';
    echo $fld_cfg_blog;

    if ($GLOBALS['onglet_liens']) {
        $fld_cfg_linx = '<div role="group" class="pref">';
        $fld_cfg_linx .= '<div class="form-legend"><legend class="legend-links">'.$GLOBALS['lang']['prefs_legend_configlinx'].'</legend></div>'."\n";

        $fld_cfg_linx .= '<div class="form-lines">'."\n";
        // nb liens côté admin
        $nbs = array(50 => 50, 100 => 100, 200 => 200, 300 => 300, 500 => 500, -1 => $GLOBALS['lang']['pref_all']);

        $fld_cfg_linx .= '<p>'."\n";
        $fld_cfg_linx .= form_select('nb_list_linx', $nbs, $GLOBALS['max_linx_admin'], $GLOBALS['lang']['pref_nb_list_linx']);
        $fld_cfg_linx .= '</p>'."\n";

        // partage de fichiers !pages : télécharger dans fichiers automatiquement ?
        $nbs = array('0'=> $GLOBALS['lang']['non'], '1'=> $GLOBALS['lang']['oui'], '2' => $GLOBALS['lang']['pref_ask_everytime']);

        $fld_cfg_linx .= '<p>'."\n";
        $fld_cfg_linx .= form_select('dl_link_to_files', $nbs, $GLOBALS['dl_link_to_files'], $GLOBALS['lang']['pref_linx_dl_auto']);
        $fld_cfg_linx .= '</p>'."\n";

        // lien à glisser sur la barre des favoris
        $a = explode('/', dirname($_SERVER['SCRIPT_NAME']));
        $fld_cfg_linx .= '<p>';
        $fld_cfg_linx .= '<label>'.$GLOBALS['lang']['pref_label_bookmark_lien'].'</label>'."\n";
        $fld_cfg_linx .= '<a class="dnd-to-favs" onclick="alert(\''.$GLOBALS['lang']['pref_label_bookmark_lien'].'\');return false;" href="javascript:javascript:(function(){window.open(\''.$GLOBALS['racine'].$a[count($a)-1].'/links.php?url=\'+encodeURIComponent(location.href));})();">Save link</a>';
        $fld_cfg_linx .= '</p>'."\n";
        $fld_cfg_linx .= '</div>'."\n";

        $fld_cfg_linx .= $submit_box;

        $fld_cfg_linx .= '</div>';
        echo $fld_cfg_linx;
    }

    if ($GLOBALS['onglet_rss']) {
        /* TODO
        - Open=read ? + button to mark as read in HTML
        - Export OPML
        */
        $fld_cfg_rss = '<div role="group" class="pref">';
        $fld_cfg_rss .= '<div class="form-legend"><legend class="legend-rss">'.$GLOBALS['lang']['prefs_legend_configrss'].'</legend></div>'."\n";
        $fld_cfg_rss .= '<div class="form-lines">'."\n";

        $fld_cfg_rss .= '<p>'."\n";
        $a = explode('/', dirname($_SERVER['SCRIPT_NAME']));
        $fld_cfg_rss .= '<label>'.$GLOBALS['lang']['pref_label_crontab_rss'].'</label>'."\n";
        $fld_cfg_rss .= '<a onclick="prompt(\''.$GLOBALS['lang']['pref_alert_crontab_rss'].'\', \'0 *  *   *   *   wget --spider -qO- '.$GLOBALS['racine'].$a[count($a)-1].'/_rss.ajax.php?guid='.BLOG_UID.'&refresh_all'.'\');return false;" href="#">Afficher ligne Cron</a>';
        $fld_cfg_rss .= '</p>'."\n";

        $fld_cfg_rss .= '<p>'."\n";
        $fld_cfg_rss .= "\t".'<label>'.$GLOBALS['lang']['pref_rss_go_to_imp-export'].'</label>'."\n";
        $fld_cfg_rss .= "\t".'<a href="maintenance.php">'.$GLOBALS['lang']['label_import-export'].'</a>'."\n";
        $fld_cfg_rss .= '</p>'."\n";

        $fld_cfg_rss .= '<p>'."\n";
        $fld_cfg_rss .= '</p>'."\n";

        $fld_cfg_rss .= '</div>'."\n";

        $fld_cfg_rss .= $submit_box;
        $fld_cfg_rss .= '</div>';
        echo $fld_cfg_rss;
    }

    $fld_maintenance = '<div role="group" class="pref">';
    $fld_maintenance .= '<div class="form-legend"><legend class="legend-sweep">'.$GLOBALS['lang']['titre_maintenance'].'</legend></div>'."\n";

    $fld_maintenance .= '<div class="form-lines">'."\n";
    $fld_maintenance .= '<p>'."\n";
    $fld_maintenance .= form_checkbox('check_update', $GLOBALS['check_update'], $GLOBALS['lang']['pref_check_update']);
    $fld_maintenance .= '</p>'."\n";

    $fld_maintenance .= '<p>'."\n";
    $fld_maintenance .= "\t".'<label>'.$GLOBALS['lang']['pref_go_to_maintenance'].'</label>'."\n";
    $fld_maintenance .= "\t".'<a href="maintenance.php">Maintenance</a>'."\n";
    $fld_maintenance .= '</p>'."\n";
    $fld_maintenance .= '</div>'."\n";

    $fld_maintenance .= $submit_box;

    $fld_maintenance .= '</div>';
    echo $fld_maintenance;

    // Check if a new BlogoText version is available (code from Shaarli, by Sebsauvage).
    // Get latest version number at most once a day.
    if ($GLOBALS['check_update'] == 1) {
        $version_file = '../VERSION';
        if (!is_file($version_file) or filemtime($version_file) < time() - 24 * 60 * 60) {
            $version_hit_url = 'https://raw.githubusercontent.com/BoboTiG/blogotext/master/VERSION';
            $response = request_external_files(array($version_hit_url), 6);
            $version = trim($response[$version_hit_url]['body']);
            $last_version = (is_valid_version($version)) ? $version : BLOGOTEXT_VERSION;
            file_put_contents($version_file, $last_version);
        }

        // Compare versions
        $newest_version = file_get_contents($version_file);
        if (version_compare($newest_version, BLOGOTEXT_VERSION) == 1) {
            $fld_update = '<div role="group" class="pref">';
            $fld_update .= '<div class="form-legend"><legend class="legend-update">'.$GLOBALS['lang']['maint_chk_update'].'</legend></div>'."\n";
            $fld_update .= '<div class="form-lines">'."\n";
            $fld_update .= '<p>'."\n";
            $fld_update .= "\t".'<label>'.$GLOBALS['lang']['maint_update_youisbad'].'</label>'."\n";
            $fld_update .= "\t".'<b>'.$newest_version.'</b>';
            $fld_update .= '</p>'."\n";
            $fld_update .= '<p>'."\n";
            $fld_update .= "\t".'<label>'.$GLOBALS['lang']['maint_update_go_dl_it'].'</label>'."\n";
            $fld_update .= "\t".'<a href="'.BLOGOTEXT_SITE.'">'.parse_url(BLOGOTEXT_SITE, 1).parse_url(BLOGOTEXT_SITE, 5).'</a>';
            $fld_update .= '</p>'."\n";
            $fld_update .= '</div>'."\n";
            $fld_update .= '</div>'."\n";
            echo $fld_update;
        }
    }

    echo '</form>'."\n";
}


if (isset($_POST['_verif_envoi'])) {
    $erreurs_form = valider_form_preferences();
    if (empty($erreurs_form)) {
        // devnote : I suppose we take $_POST['mdp_rep'] (alt. $_POST['mdp'])
        if (auth_write_user_login_file($_POST['identifiant'], $_POST['mdp_rep']) && fichier_prefs()) {
            redirection(basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_prefs_maj');
        } else {
            $erreurs_form[] = $GLOBALS['lang']['err_prefs_write'];
        }
    }
}

afficher_html_head($GLOBALS['lang']['preferences']);
    echo '<div id="header">'."\n";
        echo '<div id="top">'."\n";
        tpl_show_msg();
        tpl_show_topnav($GLOBALS['lang']['preferences']);
        echo '</div>'."\n";
    echo '</div>'."\n";
echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

afficher_form_prefs($erreurs_form);

echo "\n".'<script src="style/javascript.js"></script>'."\n";

footer($begin);
