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

require_once 'inc/boot.php';



// RSS feeds form: allow changing feeds (title, url) or remove a feed
function afficher_form_rssconf($errors = '')
{
    if (!empty($errors)) {
        echo erreurs($errors);
    }
    $out = '';
    // form add new feed.
    $out .= '<form id="form-rss-add" method="post" action="feed.php?config">'."\n";
    $out .= '<fieldset class="pref">'."\n";
    $out .= '<legend class="legend-link">'.$GLOBALS['lang']['label_feed_ajout'].'</legend>'."\n";
    $out .= "\t\t\t".'<label for="new-feed">'.$GLOBALS['lang']['label_feed_new'].':</label>'."\n";
    $out .= "\t\t\t".'<input id="new-feed" name="new-feed" type="text" class="text" value="" placeholder="http://www.example.org/rss">'."\n";
    $out .= '<p class="submit-bttns">'."\n";
    $out .= "\t".'<button class="submit button-submit" type="submit" name="send">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
    $out .= '</p>'."\n";
    $out .= hidden_input('token', new_token());
    $out .= hidden_input('verif_envoi', 1);
    $out .= '</fieldset>'."\n";
    $out .= '</form>'."\n";

    // Form edit + list feeds.
    $out .= '<form id="form-rss-config" method="post" action="feed.php?config">'."\n";
    $out .= '<ul>'."\n";
    foreach ($GLOBALS['liste_flux'] as $i => $flux) {
        $out .= "\t".'<li>'."\n";
        $out .= "\t\t".'<span'.( ($flux['iserror'] > 2) ? ' class="feed-error" title="('.$flux['iserror'].' last requests were errors.)" ' : ''  ).'>'."\n";
        $out .= "\t\t\t".'<label for="i_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_titre_flux'].'</label>'."\n";
        $out .= "\t\t\t".'<input id="i_'.$flux['checksum'].'" name="i_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['title']).'">'."\n";
        $out .= "\t\t".'</span>'."\n";
        $out .= "\t\t".'<span>'."\n";
        $out .= "\t\t\t".'<label for="j_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_url_flux'].'</label>'."\n";
        $out .= "\t\t\t".'<input id="j_'.$flux['checksum'].'" name="j_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['link']).'">'."\n";
        $out .= "\t\t".'</span>'."\n";
        $out .= "\t\t".'<span>'."\n";
        $out .= "\t\t\t".'<label for="l_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_dossier'].'</label>'."\n";
        $out .= "\t\t\t".'<input id="l_'.$flux['checksum'].'" name="l_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['folder']).'">'."\n";
        $out .= "\t\t\t".'<input class="remove-feed" name="k_'.$flux['checksum'].'" type="hidden" value="1">'."\n";
        $out .= "\t\t".'</span>'."\n";
        $out .= "\t\t".'<span>'."\n";
        $out .= "\t\t\t".'<button type="button" class="submit button-cancel" onclick="unMarkAsRemove(this)">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
        $out .= "\t\t\t".'<button type="button" class="submit button-delete" onclick="markAsRemove(this)">'.$GLOBALS['lang']['supprimer'].'</button>'."\n";
        $out .= "\t\t".'</span>';
        $out .= "\t".'</li>'."\n";
    }
    $out .= '</ul>'."\n";
    $out .= '<p class="submit-bttns">'."\n";
    $out .= "\t".'<button class="submit button-submit" type="submit" name="send">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
    $out .= '</p>'."\n";
    $out .= hidden_input('token', new_token());
    $out .= hidden_input('verif_envoi', 1);
    $out .= '</form>'."\n";

    return $out;
}

// FOR RSS : get $_POST and update feeds (title, url…) for feeds.php?config
function traiter_form_rssconf()
{
    $msg_param_to_trim = (isset($_GET['msg'])) ? '&msg='.$_GET['msg'] : '';
    $query_string = str_replace($msg_param_to_trim, '', $_SERVER['QUERY_STRING']);
    // traitement
    $GLOBALS['db_handle']->beginTransaction();
    foreach ($GLOBALS['liste_flux'] as $i => $feed) {
        if (isset($_POST['i_'.$feed['checksum']])) {
            // feed marked to be removed
            if ($_POST['k_'.$feed['checksum']] == 0) {
                unset($GLOBALS['liste_flux'][$i]);
                try {
                    $req = $GLOBALS['db_handle']->prepare('DELETE FROM rss WHERE bt_feed=?');
                    $req->execute(array($feed['link']));
                } catch (Exception $e) {
                    die('Error : Rss?conf RM-from db: '.$e->getMessage());
                }
            } // title, url or folders have changed
            else {
                // title has change
                $GLOBALS['liste_flux'][$i]['title'] = $_POST['i_'.$feed['checksum']];
                // folder has changed : update & change folder where it must be changed
                if ($GLOBALS['liste_flux'][$i]['folder'] != $_POST['l_'.$feed['checksum']]) {
                    $GLOBALS['liste_flux'][$i]['folder'] = $_POST['l_'.$feed['checksum']];
                    try {
                        $req = $GLOBALS['db_handle']->prepare('UPDATE rss SET bt_folder=? WHERE bt_feed=?');
                        $req->execute(array($_POST['l_'.$feed['checksum']], $feed['link']));
                    } catch (Exception $e) {
                        die('Error : Rss?conf Update-feed db: '.$e->getMessage());
                    }
                }

                // URL has change
                if ($_POST['j_'.$feed['checksum']] != $GLOBALS['liste_flux'][$i]['link']) {
                    $a = $GLOBALS['liste_flux'][$i];
                    $a['link'] = $_POST['j_'.$feed['checksum']];
                    unset($GLOBALS['liste_flux'][$i]);
                    $GLOBALS['liste_flux'][$a['link']] = $a;
                    try {
                        $req = $GLOBALS['db_handle']->prepare('UPDATE rss SET bt_feed=? WHERE bt_feed=?');
                        $req->execute(array($_POST['j_'.$feed['checksum']], $feed['link']));
                    } catch (Exception $e) {
                        die('Error : Rss?conf Update-feed db: '.$e->getMessage());
                    }
                }
            }
        }
    }
    $GLOBALS['db_handle']->commit();

    // sort list with title
    $GLOBALS['liste_flux'] = array_reverse(tri_selon_sous_cle($GLOBALS['liste_flux'], 'title'));
    create_file_dtb(FEEDS_DB, $GLOBALS['liste_flux']);

    $redir = basename($_SERVER['SCRIPT_NAME']).'?'.$query_string.'&msg=confirm_feeds_edit';
    redirection($redir);
}

// FOR RSS : RETUNS nb of articles per feed
function rss_count_feed()
{
    $result = array();
    $query = '
        SELECT bt_feed, SUM(bt_statut) AS nbrun, SUM(bt_bookmarked) AS nbfav
          FROM rss
         GROUP BY bt_feed';
    try {
        $result = $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (Exception $e) {
        die('Erreur 0329-rss-count_per_feed : '.$e->getMessage());
    }
}


/* From DB : returns a HTML list with the feeds (the left panel) */
function feed_list_html()
{
    // counts unread feeds in DB
    $feeds_nb = rss_count_feed();
    $total_unread = $total_favs = 0;
    foreach ($feeds_nb as $feed) {
        $total_unread += $feed['nbrun'];
        $total_favs += $feed['nbfav'];
    }

    // First item : link all feeds
    $html = "\t\t".'<li class="all-feeds"><a href="#" onclick="document.getElementById(\'markasread\').onclick=function(){markAsRead(\'all\', true);}; sortAll(); return false;">'.$GLOBALS['lang']['rss_label_all_feeds'].' <span id="global-post-counter" data-nbrun="'.$total_unread.'">('.$total_unread.')</span></a></li>'."\n";

    // Next item : favorites items
    $html .= "\t\t".'<li class="fav-feeds"><a href="#" onclick="document.getElementById(\'markasread\').onclick=function(){markAsRead(\'favs\', true);}; return sortFavs(); return false;">'.$GLOBALS['lang']['rss_label_favs_feeds'].' <span id="favs-post-counter" data-nbrun="'.$total_favs.'">('.$total_favs.')</span></a></li>'."\n";

    $feed_urls = array();
    foreach ($feeds_nb as $i => $feed) {
        $feed_urls[$feed['bt_feed']] = $feed;
    }

    // sort feeds by folder
    $folders = array();
    foreach ($GLOBALS['liste_flux'] as $i => $feed) {
        $feed['nbrun'] = ((isset($feed_urls[$feed['link']]['nbrun'])) ? $feed_urls[$feed['link']]['nbrun'] : 0);
        $folders[$feed['folder']][] = $feed;
    }
    krsort($folders);

    // creates html : lists RSS feeds without folder separately from feeds with a folder
    foreach ($folders as $i => $folder) {
        //$folder = tri_selon_sous_cle($folder, 'nbrun');
        $li_html = "";
        $folder_count = 0;
        foreach ($folder as $j => $feed) {
            $js = 'onclick="document.getElementById(\'markasread\').onclick=function(){sendMarkReadRequest(\'site\', \''.$feed['link'].'\', true);}; sortSite(this);"';
                $li_html .= "\t\t".'<li class="" data-nbrun="'.$feed['nbrun'].'" data-feedurl="'.$feed['link'].'" title="'.$feed['link'].'">';
                $li_html .= '<a href="#" '.(($feed['iserror'] > 2) ? 'class="feed-error" ': ' ' ).$js.' data-feed-domain="'.parse_url($feed['link'], PHP_URL_HOST).'">'.$feed['title'].'</a>';
                $li_html .= '<span>('.$feed['nbrun'].')</span>';
                $li_html .= '</li>'."\n";
                $folder_count += $feed['nbrun'];
        }

        if ($i != '') {
            $html .= "\t\t".'<li class="feed-folder" data-nbrun="'.$folder_count.'" data-folder="'.$i.'">'."\n";
            $html .= "\t\t\t".'<span class="feed-folder-title">'."\n";
            $html .= "\t\t\t\t".'<a href="#" onclick="document.getElementById(\'markasread\').onclick=function(){sendMarkReadRequest(\'folder\', \''.$i.'\', true);}; sortFolder(this);">'.$i.'<span>('.$folder_count.')</span></a>'."\n";
            $html .= "\t\t\t\t".'<a href="#" onclick="return hideFolder(this)" class="unfold">unfold</a>'."\n";
            $html .= "\t\t\t".'</span>'."\n";
            $html .= "\t\t\t".'<ul>'."\n\t\t";
        }
        $html .= $li_html;
        if ($i != '') {
            $html .= "\t\t\t".'</ul>'."\n";
            $html .= "\t\t".'</li>'."\n";
        }
    }
    return $html;
}

/* From the data out of DB, creates JSON, to send to browser */
function send_rss_json($rss_entries)
{
    // send all the entries data in a JSON format
    $out = '';
    $out .= '<script>';

    // RSS entries
    $out .= 'var rss_entries = {"list": ['."\n";
    $count = count($rss_entries)-1;
    foreach ($rss_entries as $i => $entry) {
        // Note: json_encode adds « " » on the data, so we use encode() and not '"'.encode().'"';
        $out .= '{'.
            '"id": '.json_encode($entry['bt_id']).','.
            '"date": '.json_encode(date_formate(date('YmdHis', $entry['bt_date']))).','.
            '"time": '.json_encode(heure_formate(date('YmdHis', $entry['bt_date']))).','.
            '"title": '.json_encode($entry['bt_title']).','.
            '"link": '.json_encode($entry['bt_link']).','.
            '"feed": '.json_encode($entry['bt_feed']).','.
            '"sitename": '.json_encode($GLOBALS['liste_flux'][$entry['bt_feed']]['title']).','.
            '"folder": '.json_encode($GLOBALS['liste_flux'][$entry['bt_feed']]['folder']).','.
            '"content": '.json_encode($entry['bt_content']).','.
            '"statut": '.$entry['bt_statut'].','.
            '"fav": '.$entry['bt_bookmarked'].''.
        '}'.(($count == $i) ? '' :',')."\n";
    }
    $out .= ']'."\n".'}';
    $out .=  '</script>'."\n";

    return $out;
}


$GLOBALS['db_handle'] = open_base();
$GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);

$erreurs = array();
if (isset($_POST['verif_envoi'])) {
    $erreurs = valider_form_rss();
    if (empty($erreurs)) {
        traiter_form_rssconf();
    }
}

$tableau = array();

/**
 * add $ttl for POC
 * It's dirty ...
 * remrem
 */
$ttl = time();
$sql_where = '';
if (!empty($_GET['ttl']) && is_numeric($_GET['ttl'])) {
    $ttl = $_GET['ttl'];
}

$sql_where = 'bt_date < "'. $ttl .'" AND bt_date > "'. ($ttl - 86400) .'" ';


if (!empty($_GET['q'])) {
    $sql_where_status = '';
    $q_query = (isset($_GET['q'])) ? $_GET['q'] : '';

    if (!empty($q_query)) {
        // search "in:read"
        if (substr($_GET['q'], -8) === ' in:read') {
            $sql_where_status = 'AND bt_statut=0 ';
            $q_query = substr($_GET['q'], 0, strlen($_GET['q'])-8);
        }
        // search "in:unread"
        if (substr($_GET['q'], -10) === ' in:unread') {
            $sql_where_status = 'AND bt_statut=1 ';
            $q_query = substr($_GET['q'], 0, strlen($_GET['q'])-10);
        }
        $arr = parse_search($q_query);
        $sql_where .= 'AND '. implode(array_fill(0, count($arr), '( bt_content || bt_title ) LIKE ?'), 'AND'); // AND operator between words
    }

    $query = '
        SELECT * FROM rss
         WHERE '.$sql_where.$sql_where_status.'
         ORDER BY bt_date DESC';
    $tableau = liste_elements($query, $arr, 'rss');
} else {
    $sql = '
        SELECT * FROM rss
         WHERE (
                   bt_statut = 1
                OR bt_bookmarked = 1
               )
               AND '. $sql_where .'
      ORDER BY bt_date DESC';
    $tableau = liste_elements($sql, array(), 'rss');
}

tpl_show_html_head($GLOBALS['lang']['mesabonnements']);

echo '<div id="header">'."\n";
    echo '<div id="top">'."\n";
    tpl_show_msg();
    echo moteur_recherche();
    tpl_show_topnav($GLOBALS['lang']['mesabonnements']);
    echo '</div>'."\n";

if (!isset($_GET['config'])) {
    echo "\t".'<div id="rss-menu">'."\n";
    echo "\t\t".'<span id="count-posts"><span id="counter"></span></span>'."\n";
    echo "\t\t".'<span id="message-return"></span>'."\n";
    echo "\t\t".'<ul class="rss-menu-buttons">'."\n";
    echo "\t\t\t".'<li><button type="button" onclick="refresh_all_feeds(this);" title="'.$GLOBALS['lang']['rss_label_refresh'].'"></button></li>'."\n";
    echo "\t\t\t".'<li><button type="button" onclick="window.location= \'?config\';" title="'.$GLOBALS['lang']['rss_label_config'].'"></button></li>'."\n";
    echo "\t\t\t".'<li><button type="button" onclick="window.location.href=\'maintenance.php#form_import\'" title="Import/export"></button></li>'."\n";
    echo "\t\t\t".'<li><button type="button" onclick="return cleanList();" title="'.$GLOBALS['lang']['rss_label_clean'].'"></button></li>'."\n";
    echo "\t\t".'</ul>'."\n";
    echo "\t".'</div>'."\n";
    echo '<button type="button" id="fab" class="add-feed" onclick="addNewFeed();" title="'.$GLOBALS['lang']['rss_label_config'].'">'.$GLOBALS['lang']['label_lien_ajout'].'</button>'."\n";
}

echo '</div>'."\n";

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

if (isset($_GET['config'])) {
    echo afficher_form_rssconf($erreurs);
    echo "\n".'<script src="style/javascript.js"></script>'."\n";
} else {
    // get list of posts from DB
    // send to browser
    $out_html = send_rss_json($tableau);
    $out_html .= '<div id="rss-list">'."\n";
    $out_html .= "\t".'<div id="posts-wrapper">'."\n";

    $out_html .= "\t\t".'<ul id="feed-list">'."\n";
    $out_html .= "\t\t\t".'<li><a style="text-align:center;font-weight:bold;font-size:1.8em;" href="feed.php?ttl='. ($ttl-86400) .'" title="Before"><</a></li>'."\n";
    if (($ttl+86400) > time()) {
        $out_html .= "\t\t\t".'<li><a style="text-align:center;font-weight:bold;font-size:1.8em;" disabled title="After">></a></li>'."\n";
    } else {
        $out_html .= "\t\t\t".'<li><a style="text-align:center;font-weight:bold;font-size:1.8em;" href="feed.php?ttl='. ($ttl+86400) .'" title="After">></a></li>'."\n";
    }
    $out_html .= feed_list_html();
    $out_html .= "\t\t".'</ul>'."\n";
    $out_html .= "\t\t".'<div id="post-list-wrapper">'."\n";
    $out_html .= "\t\t\t".'<div id="post-list-title">'."\n";
    $out_html .= "\t\t\t".'<ul class="rss-menu-buttons">'."\n";
    $out_html .= "\t\t\t\t".'<li><button type="button" onclick="markAsRead(\'all\', true);" id="markasread" title="'.$GLOBALS['lang']['rss_label_markasread'].'"></button></li>'."\n";
    $out_html .= "\t\t\t\t".'<li><button type="button" onclick="openAllItems(this);" id="openallitemsbutton" title="'.$GLOBALS['lang']['rss_label_unfoldall'].'"></button></li>'."\n";
    $out_html .= "\t\t\t".'</ul>'."\n";
    $out_html .= "\t\t\t".'<p><span id="post-counter"></span> '.$GLOBALS['lang']['label_elements'].'</p>'."\n";

    $out_html .= "\t\t\t".'</div>'."\n";

    /* here comes (in JS) the <ul id="post-list"></ul> */

    if (empty($GLOBALS['liste_flux'])) {
        $out_html .= $GLOBALS['lang']['rss_nothing_here_note'].'<a href="maintenance.php#form_import">import OPML</a>.';
    }
    $out_html .= "\t\t".'</div>'."\n";
    $out_html .= "\t".'</div>'."\n";
    $out_html .= "\t".'<div class="keyshortcut">'.$GLOBALS['lang']['rss_raccourcis_clavier'].'</div>'."\n";
    $out_html .= '</div>'."\n";

    echo $out_html;

    echo "\n".'<script src="style/javascript.js"></script>'."\n";
    echo "\n".'<script>'."\n";
    echo 'var token = \''.new_token().'\';'."\n";
    echo 'var openAllSwich = \'open\';'."\n";
    echo 'var readQueue = {"count": "0", "urlList": []};'."\n";
    echo 'var Rss = rss_entries.list;'."\n";
    echo 'window.addEventListener(\'load\', function(){
                rss_feedlist(Rss);
                window.addEventListener(\'keydown\', keyboardNextPrevious);
            });'."\n";

    echo 'window.addEventListener("beforeunload", function (e) {
            if (readQueue.count != 0) {
                sendMarkReadRequest(\'postlist\', JSON.stringify(readQueue.urlList), false);
                readQueue.urlList = [];
                readQueue.count = 0;
            }
            else { return true; }
        });'."\n";

    echo 'var scrollPos = 0;'."\n";
    echo 'window.addEventListener(\'scroll\', function(){ scrollingFabHideShow() });'."\n";

    echo 'var list = document.querySelectorAll("a[data-feed-domain]");'."\n";
    echo 'for (var i = 0, len=list.length; i < len; i++) {'."\n";
    echo '  list[i].style.backgroundImage="url(\'" + "'.$GLOBALS['racine'].'favatar.php?w=favicon&q="+ list[i].getAttribute(\'data-feed-domain\') + "\')";'."\n";
    echo '}'."\n\n";

    echo php_lang_to_js(0);
    echo "\n".'</script>'."\n";
}

footer($begin);
