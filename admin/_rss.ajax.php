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


/* Enregistre le flux dans une BDD.
   $flux est un Array avec les données dedans.
    $flux ne contient que les entrées qui doivent être enregistrées
     (la recherche de doublons est fait en amont)
*/
function bdd_rss($flux, $what)
{
    if ($what == 'enregistrer-nouveau') {
        try {
            $GLOBALS['db_handle']->beginTransaction();
            $req = $GLOBALS['db_handle']->prepare('INSERT INTO rss
                (  bt_id,
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
            foreach ($flux as $post) {
                $post['bt_title'] = preg_replace('/(([\xE0-\xEF][\x00-\xFF][\x00-\xFF])|([\xF0-\xF4][\x00-\xFF][\x00-\xFF][\x00-\xFF]))/','',$post['bt_title']);
                $post['bt_content'] = preg_replace('/(([\xE0-\xEF][\x00-\xFF][\x00-\xFF])|([\xF0-\xF4][\x00-\xFF][\x00-\xFF][\x00-\xFF]))/','',$post['bt_content']);
                $t = $req->execute(array(
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
                if ($t !== true){
                    log_error($post['bt_feed_url']);
                }
            }
            $GLOBALS['db_handle']->commit();
            return true;
        } catch (Exception $e) {
            return 'Erreur 5867-rss-add-sql : '.$e->getMessage();
        }
    }
}

/* retrieve all the feeds, returns the amount of new elements */
function refresh_rss($feeds)
{
    $new_feed_elems = array();
    $guid_in_db = rss_list_guid();
    $count_new = 0;
    $total_feed = count($feeds);

    $retrieved_elements = retrieve_new_feeds(array_keys($feeds));
    if (!$retrieved_elements) {
        return 0;
    }

    foreach ($retrieved_elements as $feed_url => $feed_elmts) {
        $new_feed_elems = array();
        if ($feed_elmts === false) {
            continue;
        } else {
            // there are new posts in the feed (md5 test on feed content file is positive). Now test on each post.
            // only keep new post that are not in DB (in $guid_in_db) OR that are newer than the last post ever retreived.
            foreach ($feed_elmts['items'] as $key => $item) {
                if ((in_array($item['bt_id'], $guid_in_db)) or ($item['bt_date'] <= $feeds[$feed_url]['time'])) {
                    unset($feed_elmts['items'][$key]);
                }
                // only save elements that are more recent
                // we save the date of the last element on that feed
                // we do not use the time of last retreiving, because it might not be correct due to different time-zones with the feeds date.
                if ($item['bt_date'] > $GLOBALS['liste_flux'][$feeds[$feed_url]['link']]['time']) {
                    $GLOBALS['liste_flux'][$feeds[$feed_url]['link']]['time'] = $item['bt_date'];
                }
            }
            // if (!empty($feed_elmts['items'])) {
                // populates the list of post we keep, to be saved in DB
                // $new_feed_elems = array_merge($new_feed_elems, $feed_elmts['items']);
            // }
            foreach ($feed_elmts['items'] as $key => $item) {
                $new_feed_elems[$key] = $item;
            }
                // if list of new elements is !empty, save new elements
                $count_new = count($new_feed_elems);
                if ($count_new > 0) {
                    $ret = bdd_rss($new_feed_elems, 'enregistrer-nouveau');
                    if ($ret !== true) {
                        echo $ret;
                    }
                }
        }
    }



    // save last success time in the feed list
    create_file_dtb(FEEDS_DB, $GLOBALS['liste_flux']);
    return $count_new;
}

// FOR RSS : RETUNS list of GUID in whole DB
function rss_list_guid()
{
    $result = array();
    $query = 'SELECT bt_id FROM rss';
    try {
        $result = $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);
        return $result;
    } catch (Exception $e) {
        die('Erreur 0329-rss-get_guid : '.$e->getMessage());
    }
}


function retrieve_new_feeds($feedlinks, $md5 = '')
{
    if (!$feeds = request_external_files($feedlinks, 25, true)) { // timeout = 25s
        return false;
    }
    $return = array();
    foreach ($feeds as $url => $response) {
        if (!empty($response['body'])) {
            $new_md5 = md5($response['body']);
            // if feed has changed: parse it (otherwise, do nothing: no need)
            if ($md5 != $new_md5 or '' == $md5) {
                $data_array = feed2array($response['body'], $url);
                if ($data_array !== false) {
                    $return[$url] = $data_array;
                    $data_array['infos']['md5'] = $md5;
                    // update RSS last successfull update MD5
                    $GLOBALS['liste_flux'][$url]['checksum'] = $new_md5;
                    $GLOBALS['liste_flux'][$url]['iserror'] = 0;
                } elseif (isset($GLOBALS['liste_flux'][$url])) { // error on feed update (else would be on adding new feed)
                    $GLOBALS['liste_flux'][$url]['iserror'] += 1;
                }
            }
        }
    }

    if (!empty($return)) {
        return $return;
    }
    return false;
}


# Based upon Feed-2-array, by bronco@warriordudimanche.net
function feed2array($feed_content, $feedlink)
{
    $flux = array('infos'=>array(),'items'=>array());

    if (preg_match('#<rss(.*)</rss>#si', $feed_content)) {  // RSS
        $flux['infos']['type'] = 'RSS';
    } elseif (preg_match('#<feed(.*)</feed>#si', $feed_content)) {  // ATOM
        $flux['infos']['type'] = 'ATOM';
    } else {  // the feed isn't RSS nor ATOM
        return false;
    }

    try {
        if (@$feed_obj = new SimpleXMLElement($feed_content, LIBXML_NOCDATA)) {
            $flux['infos']['version']=$feed_obj->attributes()->version;
            if (!empty($feed_obj->attributes()->version)) {
                $flux['infos']['version'] = (string)$feed_obj->attributes()->version;
            }
            if (!empty($feed_obj->channel->title)) {
                $flux['infos']['title'] = (string)$feed_obj->channel->title;
            }
            if (!empty($feed_obj->channel->subtitle)) {
                $flux['infos']['subtitle'] = (string)$feed_obj->channel->subtitle;
            }
            if (!empty($feed_obj->channel->link)) {
                $flux['infos']['link'] = (string)$feed_obj->channel->link;
            }
            if (!empty($feed_obj->channel->description)) {
                $flux['infos']['description'] = (string)$feed_obj->channel->description;
            }
            if (!empty($feed_obj->channel->language)) {
                $flux['infos']['language'] = (string)$feed_obj->channel->language;
            }
            if (!empty($feed_obj->channel->copyright)) {
                $flux['infos']['copyright'] = (string)$feed_obj->channel->copyright;
            }

            if (!empty($feed_obj->title)) {
                $flux['infos']['title'] = (string)$feed_obj->title;
            }
            if (!empty($feed_obj->subtitle)) {
                $flux['infos']['subtitle'] = (string)$feed_obj->subtitle;
            }
            if (!empty($feed_obj->link)) {
                $flux['infos']['link'] = (string)$feed_obj->link;
            }
            if (!empty($feed_obj->description)) {
                $flux['infos']['description'] = (string)$feed_obj->description;
            }
            if (!empty($feed_obj->language)) {
                $flux['infos']['language'] = (string)$feed_obj->language;
            }
            if (!empty($feed_obj->copyright)) {
                $flux['infos']['copyright'] = (string)$feed_obj->copyright;
            }

            if (!empty($feed_obj->channel->item)) {
                $items = $feed_obj->channel->item;
            }
            if (!empty($feed_obj->entry)) {
                $items = $feed_obj->entry;
            }
            if (empty($items)) {
                return $flux;
            }

            foreach ($items as $item) {
                $c = count($flux['items']);
                if (!empty($item->title)) {
                    //$flux['items'][$c]['bt_title'] = (string)$item->title;
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

                /*
                if (!empty($item->author->name)) {
                    $flux['items'][$c]['bt_author'] = (string)$item->author->name;
                }
                */

                if (!empty($item->guid)) {
                    $flux['items'][$c]['bt_id'] = (string)$item->guid;
                } elseif (!empty($item->id)) {
                    $flux['items'][$c]['bt_id'] = (string)$item->id;
                } else {
                    $flux['items'][$c]['bt_id'] = microtime();
                }
                // dirty fix
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

                // no content found ?
                if (!isset($flux['items'][$c]['bt_content'])) {
                    $flux['items'][$c]['bt_content'] = '';
                }

                // no date found ?
                if (!isset($flux['items'][$c]['bt_date'])) {
                    if (!empty($item->updated)) {
                        $flux['items'][$c]['bt_date'] = (string)$item->updated;
                    }
                }
                if (!isset($flux['items'][$c]['bt_date'])) {
                    if (!empty($item->children('dc', true)->date)) {
                        $flux['items'][$c]['bt_date'] = (string)$item->children('dc', true)->date;
                    }
                } // <dc:date>

                if (!empty($flux['items'][$c]['bt_date'])) {
                    $flux['items'][$c]['bt_date'] = strtotime($flux['items'][$c]['bt_date']);
                } else {
                    $flux['items'][$c]['bt_date'] = time();
                }

                // place le lien du flux (on a besoin de ça)
                $flux['items'][$c]['bt_feed_url'] = $feedlink;
                // place le dossier
                $flux['items'][$c]['bt_folder'] = (isset($GLOBALS['liste_flux'][$feedlink]['folder'])) ? $GLOBALS['liste_flux'][$feedlink]['folder'] : '';
            }
        } else {
            return false;
        }

        return $flux;
    } catch (Exception $e) {
        echo $e-> getMessage();
        echo ' '.$feedlink." \n";
        return false;
    }
}



// Update all RSS feeds using GET (for cron jobs).
// only test here is on install UID.
if (isset($_GET['refresh_all'], $_GET['guid']) and ($_GET['guid'] == BLOG_UID)) {
    if ($_GET['guid'] == BLOG_UID) {
        $GLOBALS['db_handle'] = open_base();
        $GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);
        refresh_rss($GLOBALS['liste_flux']);
        die('Success');
    } else {
        die('Error');
    }
}

$GLOBALS['db_handle'] = open_base();
$GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);

/*
    This file is called by the other files. It is an underground working script,
    It is not intended to be called directly in your browser.
*/

// retreive all RSS feeds from the sources, and save them in DB.
if (isset($_POST['refresh_all'])) {
    $erreurs = valider_form_rss();
    if (!empty($erreurs)) {
        die(erreurs($erreurs));
    }
    $nb_new = refresh_rss($GLOBALS['liste_flux']);
    echo 'Success';
    echo $nb_new;
    die;
}


// delete old entries
if (isset($_POST['delete_old'])) {
    $erreurs = valider_form_rss();
    if (!empty($erreurs)) {
        die(erreurs($erreurs));
    }

    $query = '
        DELETE
          FROM rss
         WHERE bt_statut = 0
               AND bt_bookmarked = 0';
    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute(array());
        die('Success');
    } catch (Exception $e) {
        die('Error : Rss RM old entries AJAX: '.$e->getMessage());
    }
}


// add new RSS link to serialized-DB
if (isset($_POST['add-feed'])) {
    $erreurs = valider_form_rss();
    if (!empty($erreurs)) {
        die(erreurs($erreurs));
    }

    $new_feed = trim($_POST['add-feed']);
    $new_feed_folder = htmlspecialchars(trim($_POST['add-feed-folder']));
    $feed_array = retrieve_new_feeds(array($new_feed), '');

    if (!($feed_array[$new_feed]['infos']['type'] == 'ATOM' or $feed_array[$new_feed]['infos']['type'] == 'RSS')) {
        die('Error: Invalid ressource (not an RSS/ATOM feed)');
    }

    // adding to serialized-db
    $GLOBALS['liste_flux'][$new_feed] = array(
        'link' => $new_feed,
        'title' => ucfirst($feed_array[$new_feed]['infos']['title']),
        'favicon' => 'style/rss-feed-icon.png',
        'checksum' => 42,
        'time' => 1,
        'folder' => $new_feed_folder
    );

    // sort list with title
    $GLOBALS['liste_flux'] = array_reverse(tri_selon_sous_cle($GLOBALS['liste_flux'], 'title'));
    // save to file
    create_file_dtb(FEEDS_DB, $GLOBALS['liste_flux']);

    // Update DB
    refresh_rss(array($new_feed => $GLOBALS['liste_flux'][$new_feed]));
    die('Success');
}

// mark some element(s) as read
if (isset($_POST['mark-as-read'])) {
    $erreurs = valider_form_rss();
    if (!empty($erreurs)) {
        die(erreurs($erreurs));
    }

    $what = $_POST['mark-as-read'];
    if ($what == 'all') {
        $query = '
            UPDATE rss
               SET bt_statut = 0';
        $array = array();
    } elseif ($what == 'site' and !empty($_POST['url'])) {
        $feedurl = $_POST['url'];
        $query = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_feed = ?';
        $array = array($feedurl);
    } elseif ($what == 'post' and !empty($_POST['url'])) {
        $postid = $_POST['url'];
        $query = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_id = ?';
        $array = array($postid);
    } elseif ($what == 'folder' and !empty($_POST['url'])) {
        $folder = $_POST['url'];
        $query = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_folder = ?';
        $array = array($folder);
    } elseif ($what == 'postlist' and !empty($_POST['url'])) {
        $list = json_decode($_POST['url']);
        $questionmarks = str_repeat("?,", count($list)-1)."?";
        $query = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_id IN ('.$questionmarks.')';
        $array = $list;
    }

    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute($array);
        die('Success');
    } catch (Exception $e) {
        die('Error : Rss mark as read: '.$e->getMessage());
    }
}

// mark some elements as fav
if (isset($_POST['mark-as-fav'])) {
    $erreurs = valider_form_rss();
    if (!empty($erreurs)) {
        die(erreurs($erreurs));
    }

    $url = $_POST['url'];
    $query = '
        UPDATE rss
           SET bt_bookmarked = (1 - bt_bookmarked)
         WHERE bt_id = ?';
    $array = array($url);

    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute($array);
        die('Success');
    } catch (Exception $e) {
        die('Error : Rss mark as fav: '.$e->getMessage());
    }
}

exit;
