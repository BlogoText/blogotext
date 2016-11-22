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

//
// This file contains functions relative to search and list data posts.
// It also contains functions about files : creating, deleting files, etc.


/**
 * return the absolute and clean path
 * used for debug and for security
 *
 * @param string $path, the absolute path from your BT directory
 * @param bool $check, run some check, and correct if possible (recommended for dev/debug use only !)
 * @param bool $alert, show alert if something got wrong (recommended for dev/debug use only !)
 * @return bool|string, the absolute path for your host
 */
function get_path($path, $check = false, $alert = false)
{
    if ($check === true) {
        if (strpos($path, '/') !== 0) {
            if ($alert === true) {
                var_dump('get_path() : path not starting with "/" ('. $path .')');
            }
            return false;
        }
        if (strpos($path, BT_DIR) === 0) {
            if ($alert === true) {
                var_dump('get_path() : seem\'s already an absolute path ('. $path .')');
            }
            return false;
        }
        if (strpos($path, './') !== false) {
            if ($alert === true) {
                var_dump('get_path() : use of "./" or "../", try to hack ? ('. $path .')');
            }
            return false;
        }
    }

    $return = BT_DIR .'/'. $path;
    $return = str_replace(array('/', '\\', '/\\'), '/', $return);
    while (strstr($return, '\\\\')) {
        $return = str_replace('\\\\', '\\', $return);
    }
    while (strstr($return, '//')) {
        $return = str_replace('//', '/', $return);
    }
    return $return;
}

/**
 * can be used by addon
 */
function create_folder($dossier, $make_htaccess = '', $recursive = false)
{
    if (is_dir($dossier)) {
        return true;
    }
    if (mkdir($dossier, 0777, $recursive) === true) {
        fichier_index($dossier); // file index.html to prevent directory listing
        if ($make_htaccess == 1) {
            fichier_htaccess($dossier); // to prevent direct access to files
        }
        return true;
    }
    return false;
}

function fichier_prefs()
{
    $fichier_prefs = '../'.DIR_CONFIG.'/prefs.php';
    if (!empty($_POST['_verif_envoi'])) {
        $lang = (isset($_POST['langue']) and preg_match('#^[a-z]{2}$#', $_POST['langue'])) ? $_POST['langue'] : 'fr';
        $auteur = addslashes(clean_txt(htmlspecialchars($_POST['auteur'])));
        $email = addslashes(clean_txt(htmlspecialchars($_POST['email'])));
        $nomsite = addslashes(clean_txt(htmlspecialchars($_POST['nomsite'])));
        $description = addslashes(clean_txt(htmlspecialchars($_POST['description'])));
        $keywords = addslashes(clean_txt(htmlspecialchars($_POST['keywords'])));
        $racine = addslashes(trim(htmlspecialchars($_POST['racine'])));
        $max_bill_acceuil = htmlspecialchars($_POST['nb_maxi']);
        $max_bill_admin = (int) $_POST['nb_list'];
        $max_comm_admin = (int) $_POST['nb_list_com'];
        $format_date = (int) $_POST['format_date'];
        $format_heure = (int) $_POST['format_heure'];
        $fuseau_horaire = addslashes(clean_txt(htmlspecialchars($_POST['fuseau_horaire'])));
        $global_com_rule = (int) isset($_POST['global_comments']);
        $activer_categories = (int) isset($_POST['activer_categories']);
        $afficher_rss = (int) isset($_POST['aff_onglet_rss']);
        $afficher_liens = (int) isset($_POST['aff_onglet_liens']);
        $theme_choisi = addslashes(clean_txt(htmlspecialchars($_POST['theme'])));
        $comm_defaut_status = (int) $_POST['comm_defaut_status'];
        $automatic_keywords = (int) isset($_POST['auto_keywords']);
        $alert_author = (int) isset($_POST['alert_author']);
        $require_email = (int) isset($_POST['require_email']);
        $auto_check_updates = (int) isset($_POST['check_update']);
        $auto_dl_liens_fichiers = (int) $_POST['dl_link_to_files'];
        $nombre_liens_admin = (int) $_POST['nb_list_linx'];
    } else {
        $lang = (isset($_POST['langue']) and preg_match('#^[a-z]{2}$#', $_POST['langue'])) ? $_POST['langue'] : 'fr';
        $auteur = addslashes(clean_txt(htmlspecialchars(USER_LOGIN)));
        $email = 'mail@example.com';
        $nomsite = 'BlogoText';
        $description = addslashes(clean_txt($GLOBALS['lang']['go_to_pref']));
        $keywords = 'blog, blogotext';
        $racine = addslashes(clean_txt(trim(htmlspecialchars($_POST['racine']))));
        $max_bill_acceuil = 10;
        $max_bill_admin = 25;
        $max_comm_admin = 50;
        $format_date = 0;
        $format_heure = 0;
        $fuseau_horaire = 'UTC';
        $global_com_rule = 0;
        $activer_categories = 1;
        $afficher_rss = 1;
        $afficher_liens = 1;
        $theme_choisi = 'default';
        $comm_defaut_status = 1;
        $automatic_keywords = 1;
        $alert_author = 0;
        $require_email = 0;
        $auto_check_updates = 1;
        $auto_dl_liens_fichiers = 0;
        $nombre_liens_admin = 50;
    }
    $prefs = "<?php\n";
    $prefs .= "\$GLOBALS['lang'] = '".$lang."';\n";
    $prefs .= "\$GLOBALS['auteur'] = '".$auteur."';\n";
    $prefs .= "\$GLOBALS['email'] = '".$email."';\n";
    $prefs .= "\$GLOBALS['nom_du_site'] = '".$nomsite."';\n";
    $prefs .= "\$GLOBALS['description'] = '".$description."';\n";
    $prefs .= "\$GLOBALS['keywords'] = '".$keywords."';\n";
    $prefs .= "\$GLOBALS['racine'] = '".$racine."';\n";
    $prefs .= "\$GLOBALS['max_bill_acceuil'] = ".$max_bill_acceuil.";\n";
    $prefs .= "\$GLOBALS['max_bill_admin'] = ".$max_bill_admin.";\n";
    $prefs .= "\$GLOBALS['max_comm_admin'] = ".$max_comm_admin.";\n";
    $prefs .= "\$GLOBALS['format_date'] = ".$format_date.";\n";
    $prefs .= "\$GLOBALS['format_heure'] = ".$format_heure.";\n";
    $prefs .= "\$GLOBALS['fuseau_horaire'] = '".$fuseau_horaire."';\n";
    $prefs .= "\$GLOBALS['activer_categories'] = ".$activer_categories.";\n";
    $prefs .= "\$GLOBALS['onglet_rss'] = ".$afficher_rss.";\n";
    $prefs .= "\$GLOBALS['onglet_liens'] = ".$afficher_liens.";\n";
    $prefs .= "\$GLOBALS['theme_choisi'] = '".$theme_choisi."';\n";
    $prefs .= "\$GLOBALS['global_com_rule'] = ".$global_com_rule.";\n";
    $prefs .= "\$GLOBALS['comm_defaut_status'] = ".$comm_defaut_status.";\n";
    $prefs .= "\$GLOBALS['automatic_keywords'] = ".$automatic_keywords.";\n";
    $prefs .= "\$GLOBALS['alert_author'] = ".$alert_author.";\n";
    $prefs .= "\$GLOBALS['require_email'] = ".$require_email.";\n";
    $prefs .= "\$GLOBALS['check_update'] = ".$auto_check_updates.";\n";
    $prefs .= "\$GLOBALS['max_linx_admin'] = ".$nombre_liens_admin.";\n";
    $prefs .= "\$GLOBALS['dl_link_to_files'] = ".$auto_dl_liens_fichiers.";\n";

    return file_put_contents($fichier_prefs, $prefs) !== false;
}

function fichier_index($dossier)
{
    $content = '<html>'."\n";
    $content .= "\t".'<head>'."\n";
    $content .= "\t\t".'<title>Access denied</title>'."\n";
    $content .= "\t".'</head>'."\n";
    $content .= "\t".'<body>'."\n";
    $content .= "\t\t".'<a href="/">Retour a la racine du site</a>'."\n";
    $content .= "\t".'</body>'."\n";
    $content .= '</html>';
    $index_html = $dossier.'/index.html';

    return file_put_contents($index_html, $content) !== false;
}

function fichier_htaccess($dossier)
{
    $content = '<Files *>'."\n";
    $content .= 'Order allow,deny'."\n";
    $content .= 'Deny from all'."\n";
    $content .= '</Files>'."\n";
    $htaccess = $dossier.'/.htaccess';

    return file_put_contents($htaccess, $content) !== false;
}

// à partir de l’extension du fichier, trouve le "type" correspondant.
// les "type" et le tableau des extensions est le $GLOBALS['files_ext'] dans conf.php
function detection_type_fichier($extension)
{
    $good_type = 'other'; // par défaut
    foreach ($GLOBALS['files_ext'] as $type => $exts) {
        if (in_array($extension, $exts)) {
            $good_type = $type;
            break; // sort du foreach au premier 'match'
        }
    }
    return $good_type;
}


// $feeds is an array of URLs: Array( [http://…], [http://…], …)
// Returns the same array: Array([http://…] [[headers]=> 'string', [body]=> 'string'], …)
function request_external_files($feeds, $timeout, $echo_progress = false)
{
    // uses chunks of 30 feeds because Curl has problems with too big (~150) "multi" requests.
    $chunks = array_chunk($feeds, 30, true);
    $results = array();
    $total_feed = count($feeds);
    if ($echo_progress === true) {
        echo '0/'.$total_feed.' ';
        ob_flush();
        flush(); // for Ajax
    }

    foreach ($chunks as $chunk) {
        set_time_limit(30);
        $curl_arr = array();
        $master = curl_multi_init();
        $total_feed_chunk = count($chunk)+count($results);

        // init each url
        foreach ($chunk as $i => $url) {
            $curl_arr[$url] = curl_init(trim($url));
            curl_setopt_array($curl_arr[$url], array(
                CURLOPT_RETURNTRANSFER => true, // force Curl to return data instead of displaying it
                CURLOPT_FOLLOWLOCATION => true, // follow 302 ans 301 redirects
                CURLOPT_CONNECTTIMEOUT => 100, // 0 = indefinately ; no connection-timeout (ruled out by "set_time_limit" hereabove)
                CURLOPT_TIMEOUT => $timeout, // downloading timeout
                CURLOPT_USERAGENT => BLOGOTEXT_UA, // User-agent (uses the UA of browser)
                CURLOPT_SSL_VERIFYPEER => false, // ignore SSL errors
                CURLOPT_SSL_VERIFYHOST => false, // ignore SSL errors
                CURLOPT_ENCODING => 'gzip', // take into account gziped pages
                //CURLOPT_VERBOSE => 1,
                CURLOPT_HEADER => 1, // also return header
            ));
            curl_multi_add_handle($master, $curl_arr[$url]);
        }

        // exec connexions
        $running = $oldrunning = 0;

        do {
            curl_multi_exec($master, $running);

            if ($echo_progress === true) {
                // echoes the nb of feeds remaining
                echo ($total_feed_chunk-$running).'/'.$total_feed.' ';
                ob_flush();
                flush();
            }
            usleep(100000);
        } while ($running > 0);

        // multi select contents
        foreach ($chunk as $i => $url) {
            $response = curl_multi_getcontent($curl_arr[$url]);
            $header_size = curl_getinfo($curl_arr[$url], CURLINFO_HEADER_SIZE);
            $results[$url]['headers'] = http_parse_headers(mb_strtolower(substr($response, 0, $header_size)));
            $results[$url]['body'] = substr($response, $header_size);
        }
        // Ferme les gestionnaires
        curl_multi_close($master);
    }
    return $results;
}

function rafraichir_cache_lv1()
{
    create_folder(BT_ROOT.DIR_CACHE, 1);
    $arr_a = liste_elements("SELECT * FROM articles WHERE bt_statut=1 ORDER BY bt_date DESC LIMIT 0, 20", array(), 'articles');
    $arr_c = liste_elements("SELECT c.*, a.bt_title FROM commentaires AS c, articles AS a WHERE c.bt_statut=1 AND c.bt_article_id=a.bt_id ORDER BY c.bt_id DESC LIMIT 0, 20", array(), 'commentaires');
    $arr_l = liste_elements("SELECT * FROM links WHERE bt_statut=1 ORDER BY bt_id DESC LIMIT 0, 20", array(), 'links');
    $file = BT_ROOT.DIR_CACHE.'/'.'cache_rss_array.dat';
    return file_put_contents($file, '<?php /* '.chunk_split(base64_encode(serialize(array('c' => $arr_c, 'a' => $arr_a, 'l' => $arr_l)))).' */');
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
            if (!empty($feed_elmts['items'])) {
                // populates the list of post we keep, to be saved in DB
                $new_feed_elems = array_merge($new_feed_elems, $feed_elmts['items']);
            }
        }
    }

    // if list of new elements is !empty, save new elements
    if (!empty($new_feed_elems)) {
        $count_new = count($new_feed_elems);
        $ret = bdd_rss($new_feed_elems, 'enregistrer-nouveau');
        if ($ret !== true) {
            echo $ret;
        }
    }

    // save last success time in the feed list
    file_put_contents(FEEDS_DB, '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_flux']))).' */');
    return $count_new;
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

if (!function_exists('http_parse_headers')) {
    function http_parse_headers($raw_headers)
    {
        $headers = array();
        $array_headers = ((is_array($raw_headers)) ? $raw_headers : explode("\n", $raw_headers));

        foreach ($array_headers as $i => $h) {
            $h = explode(':', $h, 2);
            if (isset($h[1])) {
                $headers[$h[0]] = trim($h[1]);
            }
        }
        return $headers;
    }
}
