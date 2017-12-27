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
 * Complete the process, even if the client stops it
 * (cron : wget --spider ...)
 */
ignore_user_abort(true);
// set at 30 minutes, but maybe need some adjustments
set_time_limit(1800);

// get _GET
$guid = (string)filter_input(INPUT_GET, 'guid');
$isRefreshing = (filter_input(INPUT_GET, 'refresh_all') !== null);

// if this is a cron
if ($isRefreshing && $guid !== null) {
    define('BT_RUN_CRON', true);
}

require_once 'inc/boot.php';


/**
 * Save one feed into the database.
 */
function bdd_rss($flux, $what)
{
    if ($what == 'enregistrer-nouveau') {
        $GLOBALS['db_handle']->beginTransaction();
        $req = $GLOBALS['db_handle']->prepare('
            INSERT INTO rss
                    (   bt_id,
                        bt_date,
                        bt_title,
                        bt_link,
                        bt_feed,
                        bt_content,
                        bt_statut,
                        bt_bookmarked,
                        bt_folder
                    )
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $regex = '%(?:
                  \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
                | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
                | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )%xs';
        foreach ($flux as $post) {
            $post['bt_title'] = preg_replace($regex, '?', $post['bt_title']);
            $post['bt_content'] = preg_replace($regex, '?', $post['bt_content']);
            $ret = $req->execute(array(
                $post['bt_id'],
                $post['bt_date'],
                $post['bt_title'],
                $post['bt_link'],
                $post['bt_feed_url'],
                $post['bt_content'],
                1,
                0,
                $post['bt_folder']
            ));
            if (!$ret) {
                log_error($post['bt_feed_url']);
            }
        }
        return $GLOBALS['db_handle']->commit();
    }
}

/**
 * Retrieve all the feeds, returns the amount of new elements.
 */
function refresh_rss($feeds)
{
    $guids = rss_list_guid();
    $numberOfNewItems = 0;

    $items = retrieve_new_feeds(array_keys($feeds));
    if (!$items) {
        return 0;
    }

    foreach ($items as $feedUrl => $feedItems) {
        if (!$feedItems) {
            continue;
        }

        // there are new posts in the feed (md5 test on feed content file is positive). Now test on each post.
        // only keep new post that are not in DB (in $guids) OR that are newer than the last post ever retreived.
        foreach ($feedItems['items'] as $key => $item) {
            if ((in_array($item['bt_id'], $guids)) or ($item['bt_date'] <= $feeds[$feedUrl]['time'])) {
                unset($feedItems['items'][$key]);
            }
            // only save elements that are more recent
            // we save the date of the last element on that feed
            // we do not use the time of last retreiving, because it might not be correct due to different time-zones with the feeds date.
            if ($item['bt_date'] > $GLOBALS['liste_flux'][$feeds[$feedUrl]['link']]['time']) {
                $GLOBALS['liste_flux'][$feeds[$feedUrl]['link']]['time'] = $item['bt_date'];
            }
        }

        $newItems = array();
        foreach ($feedItems['items'] as $key => $item) {
            $newItems[$key] = $item;
        }
        // if list of new elements is !empty, save new elements
        $numberOfItems = count($newItems);
        if ($numberOfItems > 0) {
            $ret = bdd_rss($newItems, 'enregistrer-nouveau');
            if (!$ret) {
                log_error($newItems);
            } else {
                $numberOfNewItems += $numberOfItems;
            }
        }
    }

    create_file_dtb(FEEDS_DB, $GLOBALS['liste_flux']);
    return $numberOfNewItems;
}

/**
 * Return the list of GUID in whole DTB.
 */
function rss_list_guid()
{
    return $GLOBALS['db_handle']->query('SELECT bt_id FROM rss')->fetchAll(PDO::FETCH_COLUMN, 0);
}

/**
 *
 */
function retrieve_new_feeds($feedLinks, $md5 = '')
{
    if (!$feeds = request_external_files($feedLinks, 25, true)) {
        // Timeout = 25s
        return false;
    }
    $return = array();
    foreach ($feeds as $url => $response) {
        if (empty($response['body'])) {
            continue;
        }

        $newMd5 = md5($response['body']);
        // if feed has changed: parse it (otherwise, do nothing: no need)
        if ($md5 != $newMd5 || $md5 == '') {
            $data = feed2array($response['body'], $url);
            if ($data) {
                $return[$url] = $data;
                $data['infos']['md5'] = $md5;
                // update RSS last successfull update MD5
                $GLOBALS['liste_flux'][$url]['checksum'] = $newMd5;
                $GLOBALS['liste_flux'][$url]['iserror'] = 0;
            } elseif (isset($GLOBALS['liste_flux'][$url])) {
                // error on feed update (else would be on adding new feed)
                $GLOBALS['liste_flux'][$url]['iserror'] += 1;
            }
        }
    }

    if ($return) {
        return $return;
    }

    return false;
}

/**
 * Based upon Feed-2-array, by bronco@warriordudimanche.net
 */
function feed2array($feedContent, $feedlink)
{
    $flux = array('infos'=>array(),'items'=>array());

    if (preg_match('#<rss(.*)</rss>#si', $feedContent)) {
        // RSS
        $flux['infos']['type'] = 'RSS';
    } elseif (preg_match('#<feed(.*)</feed>#si', $feedContent)) {
        // ATOM
        $flux['infos']['type'] = 'ATOM';
    } else {
        return false;
    }

    try {
        @$feedObject = new SimpleXMLElement($feedContent, LIBXML_NOCDATA);
    } catch (Exception $e) {
        return false;
    }

    $flux['infos']['version'] = $feedObject->attributes()->version;
    if (!empty($feedObject->attributes()->version)) {
        $flux['infos']['version'] = (string)$feedObject->attributes()->version;
    }
    if (!empty($feedObject->channel->title)) {
        $flux['infos']['title'] = (string)$feedObject->channel->title;
    }
    if (!empty($feedObject->channel->subtitle)) {
        $flux['infos']['subtitle'] = (string)$feedObject->channel->subtitle;
    }
    if (!empty($feedObject->channel->link)) {
        $flux['infos']['link'] = (string)$feedObject->channel->link;
    }
    if (!empty($feedObject->channel->description)) {
        $flux['infos']['description'] = (string)$feedObject->channel->description;
    }
    if (!empty($feedObject->channel->language)) {
        $flux['infos']['language'] = (string)$feedObject->channel->language;
    }
    if (!empty($feedObject->channel->copyright)) {
        $flux['infos']['copyright'] = (string)$feedObject->channel->copyright;
    }

    if (!empty($feedObject->title)) {
        $flux['infos']['title'] = (string)$feedObject->title;
    }
    if (!empty($feedObject->subtitle)) {
        $flux['infos']['subtitle'] = (string)$feedObject->subtitle;
    }
    if (!empty($feedObject->link)) {
        $flux['infos']['link'] = (string)$feedObject->link;
    }
    if (!empty($feedObject->description)) {
        $flux['infos']['description'] = (string)$feedObject->description;
    }
    if (!empty($feedObject->language)) {
        $flux['infos']['language'] = (string)$feedObject->language;
    }
    if (!empty($feedObject->copyright)) {
        $flux['infos']['copyright'] = (string)$feedObject->copyright;
    }

    if (!empty($feedObject->channel->item)) {
        $items = $feedObject->channel->item;
    }
    if (!empty($feedObject->entry)) {
        $items = $feedObject->entry;
    }
    if (empty($items)) {
        return $flux;
    }

    foreach ($items as $item) {
        $c = count($flux['items']);
        if (!empty($item->title)) {
            $flux['items'][$c]['bt_title'] = html_entity_decode((string)$item->title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } else {
            $flux['items'][$c]['bt_title'] = "-";
        }
        if (!empty($item->link['href'])) {
            $flux['items'][$c]['bt_link'] = (string)$item->link['href'];
        }
        if (!empty($item->link)) {
            $flux['items'][$c]['bt_link'] = (string)$item->link;
        }

        if (!empty($item->guid)) {
            $flux['items'][$c]['bt_id'] = (string)$item->guid;
        } elseif (!empty($item->id)) {
            $flux['items'][$c]['bt_id'] = (string)$item->id;
        } else {
            $flux['items'][$c]['bt_id'] = microtime();
        }

        if (empty($flux['items'][$c]['bt_link'])) {
            $flux['items'][$c]['bt_link'] = $flux['items'][$c]['bt_id'];
        }

        if (!empty($item->pubDate)) {
            $flux['items'][$c]['bt_date'] = (string)$item->pubDate;
        }
        if (!empty($item->published)) {
            $flux['items'][$c]['bt_date'] = (string)$item->published;
        }

        if (!empty($item->subtitle)) {
            $flux['items'][$c]['bt_content'] = (string)$item->subtitle;
        }
        if (!empty($item->description)) {
            $flux['items'][$c]['bt_content'] = (string)$item->description;
        }
        if (!empty($item->summary)) {
            $flux['items'][$c]['bt_content'] = (string)$item->summary;
        }
        if (!empty($item->content)) {
            $flux['items'][$c]['bt_content'] = (string)$item->content;
        }

        if (!empty($item->children('content', true)->encoded)) {
            $flux['items'][$c]['bt_content'] = (string)$item->children('content', true)->encoded;
        }

        if (!isset($flux['items'][$c]['bt_content'])) {
            $flux['items'][$c]['bt_content'] = '';
        }

        if (!isset($flux['items'][$c]['bt_date'])) {
            if (!empty($item->updated)) {
                $flux['items'][$c]['bt_date'] = (string)$item->updated;
            }
        }
        if (!isset($flux['items'][$c]['bt_date'])) {
            if (!empty($item->children('dc', true)->date)) {
                $flux['items'][$c]['bt_date'] = (string)$item->children('dc', true)->date;
            }
        }

        if (!empty($flux['items'][$c]['bt_date'])) {
            $flux['items'][$c]['bt_date'] = strtotime($flux['items'][$c]['bt_date']);
        } else {
            $flux['items'][$c]['bt_date'] = time();
        }

        $flux['items'][$c]['bt_feed_url'] = $feedlink;
        $flux['items'][$c]['bt_folder'] = (isset($GLOBALS['liste_flux'][$feedlink]['folder'])) ? $GLOBALS['liste_flux'][$feedlink]['folder'] : '';
    }

    return $flux;
}


// Update all RSS feeds using GET (for cron jobs).
// only test here is on install UID.
if ($isRefreshing && $guid !== null) {
    if ($guid == BLOG_UID) {
        $GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);
        refresh_rss($GLOBALS['liste_flux']);
        die('Success');
    }
    die('Error');
}

$GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);

/*
    This file is called by the other files. It is an underground working script,
    It is not intended to be called directly in your browser.
*/

// Retreive all RSS feeds from the sources, and save them in DB.
if (filter_input(INPUT_POST, 'refresh_all') !== null) {
    $errors = valider_form_rss();
    if ($errors) {
        die(erreurs($errors));
    }
    $nb_new = refresh_rss($GLOBALS['liste_flux']);
    echo 'Success';
    echo $nb_new;
    die;
}


// delete old entries
if (filter_input(INPUT_POST, 'delete_old') !== null) {
    $errors = valider_form_rss();
    if ($errors) {
        die(erreurs($errors));
    }

    $sql = '
        DELETE
          FROM rss
         WHERE bt_statut = 0
               AND bt_bookmarked = 0';
    $req = $GLOBALS['db_handle']->prepare($sql);
    ;
    die(($req->execute(array())) ? 'Success' : 'Fail');
}


// Add new RSS link to serialized-DB
if (filter_input(INPUT_POST, 'add-feed') !== null) {
    $errors = valider_form_rss();
    if ($errors) {
        die(erreurs($errors));
    }

    $newFeed = trim($_POST['add-feed']);
    $newFeedFolder = htmlspecialchars(trim($_POST['add-feed-folder']));
    $feed = retrieve_new_feeds(array($newFeed), '');

    if (!($feed[$newFeed]['infos']['type'] == 'ATOM' || $feed[$newFeed]['infos']['type'] == 'RSS')) {
        die('Error: Invalid ressource (not an RSS/ATOM feed)');
    }

    // Adding to serialized-db
    $GLOBALS['liste_flux'][$newFeed] = array(
        'link' => $newFeed,
        'title' => ucfirst($feed[$newFeed]['infos']['title']),
        'favicon' => 'style/rss-feed-icon.png',
        'checksum' => 42,
        'time' => 1,
        'folder' => $newFeedFolder
    );

    // Sort list with title
    $GLOBALS['liste_flux'] = array_reverse(tri_selon_sous_cle($GLOBALS['liste_flux'], 'title'));
    create_file_dtb(FEEDS_DB, $GLOBALS['liste_flux']);

    // Update DB
    refresh_rss(array($newFeed => $GLOBALS['liste_flux'][$newFeed]));
    die('Success');
}

// Mark some element(s) as read
$markAsRead = filter_input(INPUT_POST, 'mark-as-read');
if ($markAsRead !== null) {
    $errors = valider_form_rss();
    if ($errors) {
        die(erreurs($errors));
    }

    $what = $markAsRead;
    if ($what == 'all') {
        $sql = '
            UPDATE rss
               SET bt_statut = 0';
        $array = array();
    } elseif ($what == 'site' and !empty($_POST['url'])) {
        $feedurl = $_POST['url'];
        $sql = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_feed = ?';
        $array = array($feedurl);
    } elseif ($what == 'post' and !empty($_POST['url'])) {
        $postid = $_POST['url'];
        $sql = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_id = ?';
        $array = array($postid);
    } elseif ($what == 'folder' and !empty($_POST['url'])) {
        $folder = $_POST['url'];
        $sql = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_folder = ?';
        $array = array($folder);
    } elseif ($what == 'postlist' and !empty($_POST['url'])) {
        $list = json_decode($_POST['url']);
        $questionmarks = str_repeat('?,', count($list)-1).'?';
        $sql = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_id IN ('.$questionmarks.')';
        $array = $list;
    }

    $req = $GLOBALS['db_handle']->prepare($sql);
    $db_process = ($req->execute($array));
    die($db_process ? 'Success' : 'Fail');
}

// Mark some elements as fav
$url = (string)filter_input(INPUT_POST, 'url');
if (filter_input(INPUT_POST, 'mark-as-fav') !== null && $url) {
    $errors = valider_form_rss();
    if ($errors) {
        die(erreurs($errors));
    }

    $sql = '
        UPDATE rss
           SET bt_bookmarked = (1 - bt_bookmarked)
         WHERE bt_id = ?';
    $array = array($url);

    $req = $GLOBALS['db_handle']->prepare($sql);
    die(($req->execute($array)) ? 'Success' : 'Fail');
}

exit;
