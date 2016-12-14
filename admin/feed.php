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


function display_form_feed_conf($errors = '')
{
    if ($errors) {
        echo erreurs($errors);
    }

    $out = '<form id="form-rss-config" method="post" action="feed.php?config">';
    $out .= '<ul>';
    foreach ($GLOBALS['liste_flux'] as $flux) {
        $out .= '<li>';
        $out .= '<span'.( ($flux['iserror'] > 2) ? ' class="feed-error" title="('.$flux['iserror'].' last requests were errors.)" ' : ''  ).'>';
        $out .= '<label for="i_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_titre_flux'].'</label>';
        $out .= '<input id="i_'.$flux['checksum'].'" name="i_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['title']).'">';
        $out .= '</span>';
        $out .= '<span>';
        $out .= '<label for="j_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_url_flux'].'</label>';
        $out .= '<input id="j_'.$flux['checksum'].'" name="j_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['link']).'">';
        $out .= '</span>';
        $out .= '<span>';
        $out .= '<label for="l_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_dossier'].'</label>';
        $out .= '<input id="l_'.$flux['checksum'].'" name="l_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['folder']).'">';
        $out .= '<input class="remove-feed" name="k_'.$flux['checksum'].'" type="hidden" value="1">';
        $out .= '</span>';
        $out .= '<span>';
        $out .= '<button type="button" class="submit button-cancel" onclick="unMarkAsRemove(this)">'.$GLOBALS['lang']['annuler'].'</button>';
        $out .= '<button type="button" class="submit button-delete" onclick="markAsRemove(this)">'.$GLOBALS['lang']['supprimer'].'</button>';
        $out .= '</span>';
        $out .= '</li>';
    }
    $out .= '</ul>';
    $out .= '<p class="submit-bttns">';
    $out .= '<button class="submit button-submit" type="submit" name="send">'.$GLOBALS['lang']['envoyer'].'</button>';
    $out .= '</p>';
    $out .= hidden_input('token', new_token());
    $out .= hidden_input('verif_envoi', 1);
    $out .= '</form>';

    return $out;
}

function traitment_form_feed_conf()
{
    $msg = (string)filter_input(INPUT_GET, 'msg');
    $queryString = str_replace(($msg) ? '&msg='.$msg : '', '', $_SERVER['QUERY_STRING']);

    $GLOBALS['db_handle']->beginTransaction();
    foreach ($GLOBALS['liste_flux'] as $idx => $feed) {
        $title = (string)filter_input(INPUT_POST, 'i_'.$feed['checksum']);
        if ($title) {
            $link = (string)filter_input(INPUT_POST, 'j_'.$feed['checksum']);
            $folder = (string)filter_input(INPUT_POST, 'l_'.$feed['checksum']);
            $status = (int)filter_input(INPUT_POST, 'k_'.$feed['checksum']);

            if ($status == 0) {
                // Feed marked to be removed
                unset($GLOBALS['liste_flux'][$idx]);
                $req = $GLOBALS['db_handle']->prepare('DELETE FROM rss WHERE bt_feed = ?');
                $req->execute(array($feed['link']));
                continue;
            }

            // Title has change
            $GLOBALS['liste_flux'][$idx]['title'] = $title;

            // Folder has changed: update & change folder where it must be changed
            if ($folder != $GLOBALS['liste_flux'][$idx]['folder']) {
                $GLOBALS['liste_flux'][$idx]['folder'] = $folder;
                $req = $GLOBALS['db_handle']->prepare('UPDATE rss SET bt_folder = ? WHERE bt_feed = ?');
                $req->execute(array($folder, $feed['link']));
            }

            // URL has change
            if ($link != $GLOBALS['liste_flux'][$idx]['link']) {
                $newUrl = $GLOBALS['liste_flux'][$idx];
                $newUrl['link'] = $link;
                unset($GLOBALS['liste_flux'][$idx]);
                $GLOBALS['liste_flux'][$newUrl['link']] = $newUrl;

                $req = $GLOBALS['db_handle']->prepare('UPDATE rss SET bt_feed = ? WHERE bt_feed = ?');
                $req->execute(array($link, $feed['link']));
            }
        }
    }
    $GLOBALS['db_handle']->commit();

    // Sort list with title
    $GLOBALS['liste_flux'] = array_reverse(tri_selon_sous_cle($GLOBALS['liste_flux'], 'title'));
    create_file_dtb(FEEDS_DB, $GLOBALS['liste_flux']);

    $redir = basename($_SERVER['SCRIPT_NAME']).'?'.$queryString.'&msg=confirm_feeds_edit';
    redirection($redir);
}

function rss_count_feed()
{
    $sql = '
        SELECT bt_feed, SUM(bt_statut) AS nbrun, SUM(bt_bookmarked) AS nbfav
          FROM rss
         GROUP BY bt_feed';
    return $GLOBALS['db_handle']->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function feed_list_html()
{
    // Counts unread feeds in DB
    $numberOfFeeds = rss_count_feed();
    $totalUnread = $totalFav = 0;
    foreach ($numberOfFeeds as $feed) {
        $totalUnread += $feed['nbrun'];
        $totalFav += $feed['nbfav'];
    }

    // First item: link all feeds
    $html = '<li class="all-feeds"><a href="feed.php">'.$GLOBALS['lang']['rss_label_all_feeds'].' <span id="global-post-counter" data-nbrun="'.$totalUnread.'">('.$totalUnread.')</span></a></li>';

    // Next item: favorites items
    $html .= '<li class="fav-feeds"><a href="feed.php?bookmarked">'.$GLOBALS['lang']['rss_label_favs_feeds'].' <span id="favs-post-counter" data-nbrun="'.$totalFav.'">('.$totalFav.')</span></a></li>';

    $feedUrls = array();
    foreach ($numberOfFeeds as $feed) {
        $feedUrls[$feed['bt_feed']] = $feed;
    }

    // Sort feeds by folder
    $folders = array();
    foreach ($GLOBALS['liste_flux'] as $feed) {
        $feed['nbrun'] = ((isset($feedUrls[$feed['link']]['nbrun'])) ? $feedUrls[$feed['link']]['nbrun'] : 0);
        $folders[$feed['folder']][] = $feed;
    }
    krsort($folders);

    // Creates HTML: lists RSS feeds without folder separately from feeds with a folder
    foreach ($folders as $idx => $folder) {
        $liHtml = '';
        $folderCount = 0;
        foreach ($folder as $feed) {
            $liHtml .= '<li class="" data-nbrun="'.$feed['nbrun'].'" data-feedurl="'.$feed['link'].'" title="'.$feed['link'].'">';
            $liHtml .= '<a href="?site='.parse_url($feed['link'], PHP_URL_HOST).'" '.(($feed['iserror'] > 2) ? 'class="feed-error" ': ' ' ).' data-feed-domain="'.parse_url($feed['link'], PHP_URL_HOST).'">'.$feed['title'].'</a>';
            $liHtml .= '<span>('.$feed['nbrun'].')</span>';
            $liHtml .= '</li>';
            $folderCount += $feed['nbrun'];
        }

        if ($idx != '') {
            $html .= '<li class="feed-folder" data-nbrun="'.$folderCount.'" data-folder="'.$idx.'">';
            $html .= '<span class="feed-folder-title">';
            $html .= '<a href="#" onclick="document.getElementById(\'markasread\').onclick=function(){sendMarkReadRequest(\'folder\', \''.$idx.'\', true);}; sortFolder(this);">'.$idx.'<span>('.$folderCount.')</span></a>';
            $html .= '<a href="#" onclick="return hideFolder(this)" class="unfold">unfold</a>';
            $html .= '</span>';
            $html .= '<ul>'."\n\t\t";
        }
        $html .= $liHtml;
        if ($idx != '') {
            $html .= '</ul>';
            $html .= '</li>';
        }
    }
    return $html;
}

/**
 * Send all RSS entries data in a JSON format.
 */
function send_rss_json($feeds)
{
    $out = '<script>';
    $out .= 'var rss_entries = { "list": [';
    $count = count($feeds) - 1;
    foreach ($feeds as $idx => $feed) {
        // Note: json_encode adds « " » on the data, so we use encode() and not '"'.encode().'"'
        $out .= '{'.
            '"id": '.json_encode($feed['bt_id']).','.
            '"date": '.json_encode(date_formate(date('YmdHis', $feed['bt_date']))).','.
            '"time": '.json_encode(heure_formate(date('YmdHis', $feed['bt_date']))).','.
            '"title": '.json_encode($feed['bt_title']).','.
            '"link": '.json_encode($feed['bt_link']).','.
            '"feed": '.json_encode($feed['bt_feed']).','.
            '"sitename": '.json_encode($GLOBALS['liste_flux'][$feed['bt_feed']]['title']).','.
            '"folder": '.json_encode($GLOBALS['liste_flux'][$feed['bt_feed']]['folder']).','.
            '"content": '.json_encode($feed['bt_content']).','.
            '"statut": '.$feed['bt_statut'].','.
            '"fav": '.$feed['bt_bookmarked'].
        '}'.(($count == $idx) ? '' : ',');
    }
    $out .= ']}';
    $out .= '</script>';

    return $out;
}


/**
 * Process
 */
$GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);

$errors = array();
if (filter_input(INPUT_POST, 'verif_envoi') !== null) {
    $errors = valider_form_rss();
    if (!$errors) {
        traitment_form_feed_conf();
    }
}

$tableau = array();

// Show N items per page
$page = (int)filter_input(INPUT_GET, 'p');
if ($page < 0) {
    $page = 0;
}
$sqlLimit = $GLOBALS['max_rss_admin'].' OFFSET '.($page * $GLOBALS['max_rss_admin']);

$arr = array();

// For a site?
$site = (string)filter_input(INPUT_GET, 'site');
$bookmarked = (filter_input(INPUT_GET, 'bookmarked') !== null);
$sqlWhere = '';
$paramUrl = '';
if ($site) {
    $sqlWhere = 'bt_feed LIKE ?';
    $arr[] = '%'.$site.'%';
    $paramUrl = 'site='.$site.'&';
} elseif ($bookmarked) {
    $sqlWhere = 'bt_bookmarked = 1';
    $paramUrl = 'bookmarked&';
}

$query = (string)filter_input(INPUT_GET, 'q');
if ($query) {
    $sqlWhereStatus = '';

    // Search "in:read"
    if (substr($query, -8) == ' in:read') {
        if ($sqlWhere) {
            $sqlWhere .= ' AND ';
        }
        $sqlWhereStatus = 'bt_statut = 0';
        $query = substr($query, 0, strlen($query) - 8);
    }
    // Search "in:unread"
    if (substr($query, -10) == ' in:unread') {
        if ($sqlWhere) {
            $sqlWhere .= ' AND ';
        }
        $sqlWhereStatus = 'bt_statut = 1';
        $query = substr($query, 0, strlen($query) - 10);
    }
    $criterias = parse_search($query);
    if ($sqlWhere && $criterias) {
        $sqlWhere .= ' AND ';
    }
    // AND operator between words
    foreach ($criterias as $where) {
        $arr[] = $where;
        $sqlWhere .= '(bt_content || bt_title) LIKE ? AND ';
    }
    $sqlWhere = trim($sqlWhere, ' AND ');

    $sql = '
        SELECT * FROM rss
         WHERE '.trim(trim($sqlWhere.$sqlWhereStatus, ' '), 'AND').'
         ORDER BY bt_date DESC
         LIMIT '.$sqlLimit;
} else {
    if ($sqlWhere) {
        $sqlWhere .= ' AND ';
    }
    $sql = '
        SELECT * FROM rss
         WHERE '.$sqlWhere.'
             ( bt_statut = 1
               OR bt_bookmarked = 1
             )
         ORDER BY bt_date DESC
         LIMIT '.$sqlLimit;
}

$tableau = liste_elements($sql, $arr, 'rss');


echo tpl_get_html_head($GLOBALS['lang']['mesabonnements']);

echo '<div id="header">';
    echo '<div id="top">';
        tpl_show_msg();
        echo moteur_recherche();
        tpl_show_topnav($GLOBALS['lang']['mesabonnements']);
    echo '</div>';

$config = filter_input(INPUT_GET, 'config');
if ($config === null) {
    echo '<div id="rss-menu">';
    echo '<span id="count-posts"><span id="counter"></span></span>';
    echo '<span id="message-return"></span>';
    echo '<ul class="rss-menu-buttons">';
    echo '<li><button type="button" onclick="refresh_all_feeds(this);" title="'.$GLOBALS['lang']['rss_label_refresh'].'"></button></li>';
    echo '<li><button type="button" onclick="window.location= \'?config\';" title="'.$GLOBALS['lang']['rss_label_config'].'"></button></li>';
    echo '<li><button type="button" onclick="window.location.href=\'maintenance.php#form_import\'" title="Import/export"></button></li>';
    echo '<li><button type="button" onclick="return cleanList();" title="'.$GLOBALS['lang']['rss_label_clean'].'"></button></li>';
    echo '</ul>';
    echo '</div>';
    echo '<button type="button" id="fab" class="add-feed" onclick="addNewFeed();" title="'.$GLOBALS['lang']['rss_label_config'].'">'.$GLOBALS['lang']['label_lien_ajout'].'</button>';
}

echo '</div>';

echo '<div id="axe">';
echo '<div id="page">';

if ($config !== null) {
    echo display_form_feed_conf($errors);
    echo '<script src="style/javascript.js"></script>';
} else {
    // Get list of posts from DB
    $out = send_rss_json($tableau);
    $out .= '<div id="rss-list">';
    $out .= '<div id="posts-wrapper">';
    $out .= '<ul id="feed-list">';

    // Navigation: previous/next pages
    $out .= '<li class="feed-nav">';
    if ($page < 1) {
        $out .= '<a disabled>&lt;</a>';
    } else {
        $out .= '<a href="feed.php?'.$paramUrl.'p='.($page - 1).'" title="'.$GLOBALS['lang']['previous_page'].'">&lt;</a>';
    }
    if ($page >= 0 && count($tableau) == $GLOBALS['max_rss_admin']) {
        $out .= '<a href="feed.php?'.$paramUrl.'p='.($page + 1).'" title="'.$GLOBALS['lang']['next_page'].'">&gt;</a>';
    } else {
        $out .= '<a disabled>&gt;</a>';
    }
    $out .= '</li>';

    $out .= feed_list_html();
    $out .= '</ul>';
    $out .= '<div id="post-list-wrapper">';
    $out .= '<div id="post-list-title">';
    $out .= '<ul class="rss-menu-buttons">';
    $out .= '<li><button type="button" onclick="markAsRead(\'all\', true);" id="markasread" title="'.$GLOBALS['lang']['rss_label_markasread'].'"></button></li>';
    $out .= '<li><button type="button" onclick="openAllItems(this);" id="openallitemsbutton" title="'.$GLOBALS['lang']['rss_label_unfoldall'].'"></button></li>';
    $out .= '</ul>';
    $out .= '<p><span id="post-counter"></span> '.$GLOBALS['lang']['label_elements'].'</p>';
    $out .= '</div>';

    // Here comes (in JS) the <ul id="post-list"></ul>
    if (empty($GLOBALS['liste_flux'])) {
        $out .= $GLOBALS['lang']['rss_nothing_here_note'].'<a href="maintenance.php#form_import">import OPML</a>.';
    }
    $out .= '</div>';
    $out .= '</div>';
    $out .= '<div class="keyshortcut">'.$GLOBALS['lang']['rss_raccourcis_clavier'].'</div>';
    $out .= '</div>';

    echo $out;

    echo '<script src="style/javascript.js"></script>';
    echo '<script>';
    echo 'var token = "'.new_token().'";';
    echo 'var openAllSwich = "open";';
    echo 'var readQueue = { "count": 0, "urlList": [] };';
    echo 'var Rss = rss_entries.list;';
    echo 'window.addEventListener("load", function() {
                rss_feedlist(Rss);
                window.addEventListener("keydown", keyboardNextPrevious);
            });';

    echo 'window.addEventListener("beforeunload", function (e) {
            if (readQueue.count == 0) {
                return true;
            }
            sendMarkReadRequest("postlist", JSON.stringify(readQueue.urlList), false);
            readQueue.urlList = [];
            readQueue.count = 0;
        });';

    echo 'var scrollPos = 0;';
    echo 'window.addEventListener("scroll", function() { scrollingFabHideShow(); });';

    echo 'window.addEventListener("load", function() {';
    echo 'var list = document.querySelectorAll("a[data-feed-domain]");';
    echo 'for (var i = 0, len = list.length; i < len; i++) {';
    echo '  list[i].style.backgroundImage = "url(\'" + "'.URL_ROOT.'favatar.php?w=favicon&q="+ list[i].getAttribute("data-feed-domain") + "\')";';
    echo '}';
    echo '});';

    echo php_lang_to_js(0);
    echo '</script>';
}

echo tpl_get_footer($begin);
