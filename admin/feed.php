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
 *
 */
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

/**
 *
 */
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
            $status = (string)filter_input(INPUT_POST, 'k_'.$feed['checksum']);

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

/**
 *
 */
function rss_count_feed()
{
    $sql = '
        SELECT bt_feed, SUM(bt_statut) AS nbrun, SUM(bt_bookmarked) AS nbfav
          FROM rss
         GROUP BY bt_feed';
    return $GLOBALS['db_handle']->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 *
 */
function feed_list_html($selected = '')
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
            if ($feed['link'] == $selected) {
                $liHtml .= '<li class="active-site" data-nbrun="'.$feed['nbrun'].'" data-feedurl="'.$feed['link'].'" title="'.$feed['link'].'">';
            } else {
                $liHtml .= '<li class="" data-nbrun="'.$feed['nbrun'].'" data-feedurl="'.$feed['link'].'" title="'.$feed['link'].'">';
            }
            $liHtml .= '<a href="?site='.urlencode($feed['link']).'" '.(($feed['iserror'] > 2) ? 'class="feed-error" ': ' ' ).' data-feed-domain="'.parse_url($feed['link'], PHP_URL_HOST).'">'.$feed['title'].'</a>';
            $liHtml .= '<span>('.$feed['nbrun'].')</span>';
            $liHtml .= '</li>';
            $folderCount += $feed['nbrun'];
        }

        if ($idx != '') {
            if ($selected == $idx) {
                $html .= '<li class="feed-folder open" data-nbrun="'.$folderCount.'" data-folder="'.$idx.'">';
            } else {
                $html .= '<li class="feed-folder" data-nbrun="'.$folderCount.'" data-folder="'.$idx.'">';
            }
            $html .= '<span class="feed-folder-title">';
            $html .= '<a href="?fold='.$idx.'">'.$idx.'<span>('.$folderCount.')</span></a>';
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
    $to_json = array(
        'list' => array(),
        'count' => (count($feeds) - 1)
    );

    foreach ($feeds as $feed) {
        $to_json['list'][] = array(
            'id' => $feed['bt_id'],
            'date' => date_formate(date('YmdHis', $feed['bt_date'])),
            'time' => heure_formate(date('YmdHis', $feed['bt_date'])),
            'title' => $feed['bt_title'],
            'link' => $feed['bt_link'],
            'feed' => $feed['bt_feed'],
            'sitename' => $GLOBALS['liste_flux'][$feed['bt_feed']]['title'],
            'folder' => $GLOBALS['liste_flux'][$feed['bt_feed']]['folder'],
            'content' => $feed['bt_content'],
            'statut' => $feed['bt_statut'],
            'fav' => $feed['bt_bookmarked']
        );
    }

    return '<script>var rss_entries = '. json_encode($to_json) .';</script>';
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
$page = filter_input(INPUT_GET, 'p');

$arr = array();

// For a site?
$site = (string)filter_input(INPUT_GET, 'site');
$fold = (string)filter_input(INPUT_GET, 'fold');
$bookmarked = (filter_input(INPUT_GET, 'bookmarked') !== null);
$query = (string)filter_input(INPUT_GET, 'q');
$page_date = filter_input(INPUT_GET, 'date');
$item_id = filter_input(INPUT_GET, 'id');
$sqlWhere = '';
$sqlWhereDate = '';
$sqlWhereStatus = '';
$sqlOrder = 'DESC';
$paramUrl = '';
$btn_previous_page = '';
$btn_next_page = '';

if (!empty($page_date)) {
    if ($page == 'previous') {
        $search_sign = '<';
    } else if ($page == 'next') {
        $search_sign = '>=';
        $sqlOrder = 'ASC';
    }
    if (!empty($item_id)) {
        $sqlWhereDate = ' AND ((bt_date = '.$page_date.' AND ID '.$search_sign.' '.$item_id.') OR bt_date '.$search_sign.' '.$page_date.')';
    } else {
        $sqlWhereDate = ' AND bt_date '.$search_sign.' '.$page_date;
    }
}

if ($site) {
    $sqlWhere .= 'bt_feed LIKE ?';
    $arr[] = '%'.$site.'%';
    $paramUrl = 'site='.$site.'&';
} elseif ($fold) {
    $sqlWhere .= 'bt_folder LIKE ?';
    $arr[] = '%'.$fold.'%';
    $paramUrl = 'fold='.$fold.'&';
} elseif ($bookmarked) {
    $sqlWhere .= 'bt_bookmarked = 1';
    $paramUrl = 'bookmarked&';
}
if ($query) {
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
} else {
    $sqlWhereStatus = ' AND (bt_statut = 1 OR bt_bookmarked = 1)';
}

// add 1 more than max_rss_admin, for detecting if there is a previous or next page
$sql = '
    SELECT * FROM rss
     WHERE '. trim(trim($sqlWhere.$sqlWhereStatus.$sqlWhereDate, ' '), 'AND') .'
     ORDER BY bt_date '.$sqlOrder.', ID '.$sqlOrder.'
     LIMIT '.($GLOBALS['max_rss_admin'] + 1);

$tableau = liste_elements($sql, $arr, 'rss');

// using main SQL request, try to find previous/next page
$have_more = (count($tableau) === ($GLOBALS['max_rss_admin']+1));
if (isset($have_more)) {
    if ($sqlOrder == 'ASC') {
        unset($tableau['0']);
    } else {
        unset($tableau[$GLOBALS['max_rss_admin']]);
    }
}

// reverse order to respect time
if ($sqlOrder == 'ASC') {
    $tableau = array_reverse($tableau);
}

// get pagination
$btn_previous_page = '';
$btn_next_page = '';

if (is_array($tableau) && isset($tableau['0'])) {
    // get pagination
    $first_item = array_values($tableau)[0];
    $last_item = end($tableau);

    // detect previous / next page
    if ($sqlOrder == 'ASC') {
        if ($have_more) {
            $btn_next_page =
                '<li><button type="button" id="next_feeds" '
                .'onclick="location.href=\'feed.php?'.$paramUrl.'p=next&amp;date='.$first_item['bt_date'].'&amp;id='.$first_item['ID'].'\'"></button></li>';
        }
        $sql = '
            SELECT * FROM rss
             WHERE '. trim(trim($sqlWhere.$sqlWhereStatus, ' '), 'AND') .'
                    AND ((bt_date = '.$last_item['bt_date'].' AND ID < '.$last_item['ID'].') OR bt_date > '.$last_item['bt_date'].')
             ORDER BY bt_date DESC, ID DESC
             LIMIT 1';
        $t_sql = liste_elements($sql, $arr, 'rss');
        if (isset($t_sql['0'])) {
            $btn_previous_page = '<li><button type="button" id="prev_feeds" onclick="location.href=\'feed.php?'.$paramUrl.'p=previous&amp;date='.$last_item['bt_date'].'&amp;id='.$last_item['ID'].'\'"></button></li>';
        }
    } else {
        if ($have_more) {
            $btn_previous_page =
                '<li><button type="button" id="prev_feeds" '
                .'onclick="location.href=\'feed.php?'.$paramUrl.'p=previous&amp;date='.$last_item['bt_date'].'&amp;id='.$last_item['ID'].'\'"></button></li>';
        }
        $sql = '
            SELECT * FROM rss
             WHERE '. trim(trim($sqlWhere.$sqlWhereStatus, ' '), 'AND') .'
                    AND ((bt_date = '.$first_item['bt_date'].' AND ID > '.$first_item['ID'].') OR bt_date > '.$first_item['bt_date'].')
             ORDER BY bt_date DESC, ID DESC
             LIMIT 1';
        $t_sql = liste_elements($sql, $arr, 'rss');
        if (isset($t_sql['0'])) {
            $btn_next_page = '<li><button type="button" id="next_feeds" onclick="location.href=\'feed.php?'.$paramUrl.'p=next&amp;date='.$first_item['bt_date'].'&amp;id='.$first_item['ID'].'\'"></button></li>';
        }
    }
} else {
    // no datas ...
}

/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['mesabonnements']);

echo '<div id="header">';
    echo '<div id="top">';
        tpl_show_msg();
        echo moteur_recherche();
        echo tpl_show_topnav($GLOBALS['lang']['mesabonnements']);
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

    if ($site) {
        $out .= feed_list_html($site);
    } elseif ($fold) {
        $out .= feed_list_html($fold);
    } else {
        $out .= feed_list_html();
    }
    $out .= '</ul>';
    $out .= '<div id="post-list-wrapper">';
    $out .= '<div id="post-list-title">';
    $out .= '<ul class="rss-menu-buttons">';
    if ($site) {
        $out .= "\r".'<li><button type="button" onclick="sendMarkReadRequest(\'site\', \''.$site.'\', false);" id="markasread" title="'.$GLOBALS['lang']['rss_label_markasread'].'"></button></li>';
    } elseif ($fold) {
        $out .= "\r".'<li><button type="button" onclick="sendMarkReadRequest(\'folder\', \''.$fold.'\', true);" id="markasread" title="'.$GLOBALS['lang']['rss_label_markasread'].'"></button></li>';
    } else {
        $out .= "\r".'<li><button type="button" onclick="sendMarkReadRequest(\'all\');" id="markasread" title="'.$GLOBALS['lang']['rss_label_markasread'].'"></button></li>';
    }
    $out .= '<li><button type="button" onclick="openAllItems(this);" id="openallitemsbutton" title="'.$GLOBALS['lang']['rss_label_unfoldall'].'"></button></li>';

    // Navigation: previous/next pages
    // Navigation: previous/next pages
    $out .= $btn_previous_page;
    $out .= $btn_next_page;

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

    echo '
    <script>
        var active_site = document.querySelectorAll(".active-site");
        if (active_site.length == 1){
            var s = active_site[0],
                p = s.parentNode.parentNode;
            p.classList.add("open");
        }

        var token = "'.new_token().'",
            openAllSwich = "open",
            readQueue = { "count": 0, "urlList": [] },
            Rss = rss_entries.list;

        window.addEventListener("load", function() {
                rss_feedlist(Rss);
                window.addEventListener("keydown", keyboardNextPrevious);
            });

        window.addEventListener("beforeunload", function (e) {
            if (readQueue.count == 0) {
                return true;
            }
            sendMarkReadRequest("postlist", JSON.stringify(readQueue.urlList), false);
            readQueue.urlList = [];
            readQueue.count = 0;
        });

        var scrollPos = 0;
        window.addEventListener("scroll", function() { scrollingFabHideShow(); });

        window.addEventListener("load", function() {
            var list = document.querySelectorAll("a[data-feed-domain]");
            for (var i = 0, len = list.length; i < len; i++) {
                list[i].style.backgroundImage = "url(\'" + "'.URL_ROOT.'favatar.php?w=favicon&q="+ list[i].getAttribute("data-feed-domain") + "\')";
            }
        });

        '.php_lang_to_js(0).'
    </script>';
}

echo tpl_get_footer($begin);
