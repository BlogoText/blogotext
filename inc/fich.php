<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2014 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

// THIS FILE
// 
// This file contains functions relative to search and list data posts.
// It also contains functions about files : creating, deleting files, etc.

function creer_dossier($dossier, $make_htaccess='') {
	if ( !is_dir($dossier) ) {
		if (mkdir($dossier, 0777) === TRUE) {
			fichier_index($dossier); // fichier index.html pour éviter qu'on puisse lister les fihciers du dossier
			if ($make_htaccess == 1) fichier_htaccess($dossier); // pour éviter qu'on puisse accéder aux fichiers du dossier directement
			return TRUE;
		} else {
			return FALSE;
		}
	}
	return TRUE; // si le dossier existe déjà.
}


function fichier_user() {
	$fichier_user = '../'.$GLOBALS['dossier_config'].'/user.php';
	$user='';
	if (strlen(trim($_POST['mdp'])) == 0) {
		$new_mdp = $GLOBALS['mdp']; 
	} else {
		$new_mdp = hash_password($_POST['mdp_rep'], $GLOBALS['salt']);
	}
	$user .= "<?php\n";
	$user .= "\$GLOBALS['identifiant'] = '".addslashes(clean_txt(htmlspecialchars($_POST['identifiant'])))."';\n";
	$user .= "\$GLOBALS['mdp'] = '".$new_mdp."';\n";
	$user .= "?>";
	if (file_put_contents($fichier_user, $user) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}

function fichier_adv_conf() {
	$fichier_advconf = '../'.$GLOBALS['dossier_config'].'/config-advanced.ini';
	$conf='';
	$conf .= '; <?php die(); /*'."\n\n";
	$conf .= '; This file contains some more advanced configuration features.'."\n\n";
	$conf .= 'date_premier_message_blog = \''.date('Ym').'\''."\n";
	$conf .= 'salt = \''.$salt = sha1(uniqid(mt_rand(), true)).'\''."\n";
	$conf .= 'show_errors = -1;'."\n";
	$conf .= 'gravatar_link = \'themes/default/gravatars/get.php?g=\''."\n";
	$conf .= 'use_ip_in_session = 0;'."\n\n\n";
	$conf .= '; */ ?>'."\n";

	if (file_put_contents($fichier_advconf, $conf) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}


function fichier_prefs() {
	$fichier_prefs = '../'.$GLOBALS['dossier_config'].'/prefs.php';
	if(!empty($_POST['_verif_envoi'])) {
		$lang = (isset($_POST['langue']) and preg_match('#^[a-z]{2}$#', $_POST['langue'])) ? $_POST['langue'] : 'fr';
		$auteur = clean_txt(htmlspecialchars($_POST['auteur']));
		$email = clean_txt(htmlspecialchars($_POST['email']));
		$nomsite = clean_txt(htmlspecialchars($_POST['nomsite']));
		$description = clean_txt(htmlspecialchars($_POST['description']));
		$keywords = clean_txt(htmlspecialchars($_POST['keywords']));
		$racine = addslashes(trim(htmlspecialchars($_POST['racine'])));
		$max_bill_acceuil = htmlspecialchars($_POST['nb_maxi']);
		$max_bill_admin = htmlspecialchars($_POST['nb_list']);
		$max_comm_admin = htmlspecialchars($_POST['nb_list_com']);
		$format_date = htmlspecialchars($_POST['format_date']);
		$format_heure = htmlspecialchars($_POST['format_heure']);
		$fuseau_horaire = addslashes(clean_txt(htmlspecialchars($_POST['fuseau_horaire'])));
		$global_com_rule = htmlspecialchars($_POST['global_comments']);
		$connexion_captcha = htmlspecialchars($_POST['connexion_captcha']);
		$activer_categories = htmlspecialchars($_POST['activer_categories']);
		$afficher_rss = htmlspecialchars($_POST['aff_onglet_rss']);
		$afficher_liens = htmlspecialchars($_POST['aff_onglet_liens']);
		$theme_choisi = addslashes(clean_txt(htmlspecialchars($_POST['theme'])));
		$comm_defaut_status = htmlspecialchars($_POST['comm_defaut_status']);
		$automatic_keywords = htmlspecialchars($_POST['auto_keywords']);
		$require_email = htmlspecialchars($_POST['require_email']);
		$auto_check_updates = htmlspecialchars($_POST['check_update']);
		// linx
//		$autoriser_liens_public = $_POST['allow_public_linx'];
//		$linx_defaut_status = $_POST['linx_defaut_status'];
		$auto_dl_liens_fichiers = htmlspecialchars($_POST['dl_link_to_files']);
		$nombre_liens_admin = htmlspecialchars($_POST['nb_list_linx']);
	} else {
		$lang = (isset($_POST['langue']) and preg_match('#^[a-z]{2}$#', $_POST['langue'])) ? $_POST['langue'] : 'fr';
		$auteur = clean_txt($GLOBALS['identifiant']);
		$email = 'mail@example.com';
		$nomsite = 'Blogotext';
		$description = clean_txt($GLOBALS['lang']['go_to_pref']);
		$keywords = 'blog, blogotext';
		$racine = clean_txt(trim(htmlspecialchars($_POST['racine'])));
		$max_bill_acceuil = '10';
//		$max_linx_accueil = '50';
//		$max_comm_encart = '5';
		$max_bill_admin = '25';
		$max_comm_admin = '50';
		$format_date = '0';
		$format_heure = '0';
		$fuseau_horaire = 'UTC';
		$global_com_rule = '0';
		$connexion_captcha = '0';
		$activer_categories = '1';
		$afficher_rss = '1';
		$afficher_liens = '1';
		$theme_choisi = 'default';
		$comm_defaut_status = '1';
		$automatic_keywords = '1';
		$require_email = '0';
		$auto_check_updates = 1;
		// linx
//		$autoriser_liens_public = '0';
//		$linx_defaut_status = '1';
		$auto_dl_liens_fichiers = '0';
		$nombre_liens_admin = '50';
	}
	$prefs = "<?php\n";
	$prefs .= "\$GLOBALS['lang'] = '".$lang."';\n";	
	$prefs .= "\$GLOBALS['auteur'] = '".$auteur."';\n";	
	$prefs .= "\$GLOBALS['email'] = '".$email."';\n";
	$prefs .= "\$GLOBALS['nom_du_site'] = '".$nomsite."';\n";
	$prefs .= "\$GLOBALS['description'] = '".$description."';\n";
	$prefs .= "\$GLOBALS['keywords'] = '".$keywords."';\n";
	$prefs .= "\$GLOBALS['racine'] = '".$racine."';\n";
	$prefs .= "\$GLOBALS['max_bill_acceuil'] = '".$max_bill_acceuil."';\n";
	$prefs .= "\$GLOBALS['max_bill_admin'] = '".$max_bill_admin."';\n";
//	$prefs .= "\$GLOBALS['max_comm_encart'] = '".$max_comm_encart."';\n";
	$prefs .= "\$GLOBALS['max_comm_admin'] = '".$max_comm_admin."';\n";
//	$prefs .= "\$GLOBALS['max_linx_acceuil'] = '".$max_linx_accueil."';\n";
	$prefs .= "\$GLOBALS['format_date'] = '".$format_date."';\n";
	$prefs .= "\$GLOBALS['format_heure'] = '".$format_heure."';\n";
	$prefs .= "\$GLOBALS['fuseau_horaire'] = '".$fuseau_horaire."';\n";
	$prefs .= "\$GLOBALS['connexion_captcha']= '".$connexion_captcha."';\n";
	$prefs .= "\$GLOBALS['activer_categories']= '".$activer_categories."';\n";
	$prefs .= "\$GLOBALS['onglet_rss']= '".$afficher_rss."';\n";
	$prefs .= "\$GLOBALS['onglet_liens']= '".$afficher_liens."';\n";
	$prefs .= "\$GLOBALS['theme_choisi']= '".$theme_choisi."';\n";
	$prefs .= "\$GLOBALS['global_com_rule']= '".$global_com_rule."';\n";
	$prefs .= "\$GLOBALS['comm_defaut_status']= '".$comm_defaut_status."';\n";
	$prefs .= "\$GLOBALS['automatic_keywords']= '".$automatic_keywords."';\n";
	$prefs .= "\$GLOBALS['require_email']= '".$require_email."';\n";
	$prefs .= "\$GLOBALS['check_update']= '".$auto_check_updates."';\n";
//	$prefs .= "\$GLOBALS['allow_public_linx']= '".$autoriser_liens_public."';\n";
//	$prefs .= "\$GLOBALS['linx_defaut_status']= '".$linx_defaut_status."';\n";
	$prefs .= "\$GLOBALS['max_linx_admin']= '".$nombre_liens_admin."';\n";
	$prefs .= "\$GLOBALS['dl_link_to_files']= '".$auto_dl_liens_fichiers."';\n";
	$prefs .= "?>";
	if (file_put_contents($fichier_prefs, $prefs) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}

function fichier_mysql($sgdb) {
	$fichier_mysql = '../config/mysql.php';
	$data = '';
	if ($sgdb !== FALSE) {
		$data .= "<?php\n";
		$data .= "\$GLOBALS['mysql_login'] = '".htmlentities($_POST['mysql_user'], ENT_QUOTES)."';\n";	
		$data .= "\$GLOBALS['mysql_passwd'] = '".htmlentities($_POST['mysql_passwd'], ENT_QUOTES)."';\n";
		$data .= "\$GLOBALS['mysql_db'] = '".htmlentities($_POST['mysql_db'], ENT_QUOTES)."';\n";
		$data .= "\$GLOBALS['mysql_host'] = '".htmlentities($_POST['mysql_host'], ENT_QUOTES)."';\n";
		$data .= "\n";
		$data .= "\$GLOBALS['sgdb'] = '".$sgdb."';\n";
	}

	if (file_put_contents($fichier_mysql, $data) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}

function fichier_index($dossier) {
	$content = '<html>'."\n";
	$content .= "\t".'<head>'."\n";
	$content .= "\t\t".'<title>Access denied</title>'."\n";
	$content .= "\t".'</head>'."\n";
	$content .= "\t".'<body>'."\n";
	$content .= "\t\t".'<a href="/">Retour a la racine du site</a>'."\n";
	$content .= "\t".'</body>'."\n";
	$content .= '</html>';
	$index_html = $dossier.'/index.html';

	if (file_put_contents($index_html, $content) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}


function fichier_htaccess($dossier) {
	$content = '<Files *>'."\n";
	$content .= 'Order allow,deny'."\n";
	$content .= 'Deny from all'."\n";
	$content .= '</Files>'."\n";
	$htaccess = $dossier.'/.htaccess';

	if (file_put_contents($htaccess, $content) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}


// dans le panel, l'IP de dernière connexion est affichée. Il est stoqué avec cette fonction.
function fichier_ip() {
	$new_ip = htmlspecialchars($_SERVER['REMOTE_ADDR']);
	$new_time = date('YmdHis');
	$content = "<?php\n";
	$content .= "\$GLOBALS['old_ip'] = '".$new_ip."';\n";	
	$content .= "\$GLOBALS['old_time'] = '".$new_time."';\n";	
	$content .= "?>";
	$fichier = '../config/ip.php';

	if (file_put_contents($fichier, $content) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}

function get_literal_chmod($file) {
	$perms = fileperms($file);
	if (($perms & 0xC000) == 0xC000) {
		$info = 's'; // Socket
	} elseif (($perms & 0xA000) == 0xA000) {
		$info = 'l'; // Lien symbolique
	} elseif (($perms & 0x8000) == 0x8000) {
		$info = '-'; // Régulier
	} elseif (($perms & 0x6000) == 0x6000) {
		$info = 'b'; // Block special
	} elseif (($perms & 0x4000) == 0x4000) {
		$info = 'd'; // Dossier
	} elseif (($perms & 0x2000) == 0x2000) {
		$info = 'c'; // Caractère spécial
	} elseif (($perms & 0x1000) == 0x1000) {
		$info = 'p'; // pipe FIFO
	} else {
		$info = 'u'; // Inconnu
	}
	// Autres
	$info .= (($perms & 0x0100) ? 'r' : '-');
	$info .= (($perms & 0x0080) ? 'w' : '-');
	$info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
	// Groupe
	$info .= (($perms & 0x0020) ? 'r' : '-');
	$info .= (($perms & 0x0010) ? 'w' : '-');
	$info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
	// Tout le monde
	$info .= (($perms & 0x0004) ? 'r' : '-');
	$info .= (($perms & 0x0002) ? 'w' : '-');
	$info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));

	return $info;
}


// à partir de l’extension du fichier, trouve le "type" correspondant.
// les "type" et le tableau des extensions est le $GLOBALS['files_ext'] dans conf.php
function detection_type_fichier($extension) {
	$good_type = 'other'; // par défaut
	foreach($GLOBALS['files_ext'] as $type => $exts) {
		if ( in_array($extension, $exts) ) {
			$good_type = $type;
			break; // sort du foreach au premier 'match'
		}
	}
	return $good_type;
}


function open_serialzd_file($fichier) {
	$liste  = (file_exists($fichier)) ? unserialize(base64_decode(substr(file_get_contents($fichier), strlen('<?php /* '), -strlen(' */')))) : array();
	return $liste;
}


function get_external_file($url, $timeout=10) {
	$headers = array(
		'user_agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:33.0 BlogoText-UA) Gecko/20100101 Firefox/33.0',
		'timeout' => $timeout,
		'header'=> "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n",
		'connection' => 'close',
		'ignore_errors' => TRUE);

	$context = stream_context_create(array('http'=> $headers));
	$data = @file_get_contents($url, false, $context, -1, 4000000); // We download at most 4 Mb from source.
	if (isset($data) and isset($http_response_header[0]) and ( strpos($http_response_header[0], '200 OK') | (strpos($http_response_header[0], '302 Found') ) | (strpos($http_response_header[0], '301 Moved') | (strpos($http_response_header[0], '302 Moved')) ) !== FALSE ) ) {

		// detect gzip data
		foreach($http_response_header as $i => $h) {
			// if gzip : decode it
			if(stristr($h, 'content-encoding') and stristr($h, 'gzip')) {
				$data = gzinflate( substr($data,10,-8) );
			}
		}
		return array('body' => $data, 'headers' => http_parse_headers($http_response_header));
	} else {
		return array();
	}
}



// TODO: unify get_external_file() with c_get_external_file() in one single Curl function, accepting 1 or many url and returning array().
function c_get_external_file($feeds) {
	// uses chunks of 40 feeds because Curl has problems with too big (~150) "multi" requests.
	// $feeds = array_splice($feeds, 60, 20);
	$chunks = array_chunk($feeds, 30, true);
	$results = array();
	$total_feed = count($feeds);
	echo '0/'.$total_feed.' '; ob_flush(); flush(); // for Ajax

	foreach ($chunks as $chunk) {
		set_time_limit (30);
		$curl_arr = array();
		$master = curl_multi_init();
		$total_feed_chunk = count($chunk)+count($results);

		// init each url
		foreach ($chunk as $i => $feed) {

			$curl_arr[$i] = curl_init(trim($i));
			curl_setopt_array($curl_arr[$i], array(
					CURLOPT_RETURNTRANSFER => TRUE,
					CURLOPT_FOLLOWLOCATION => TRUE,
					CURLOPT_CONNECTTIMEOUT => 0, // 0 = indefinately
					CURLOPT_TIMEOUT => 25,
					CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
					CURLOPT_SSL_VERIFYPEER => FALSE,
					CURLOPT_SSL_VERIFYHOST => FALSE,
					CURLOPT_ENCODING => "gzip",
				));
			curl_multi_add_handle($master, $curl_arr[$i]);
		}

		// exec connexions
		$running = $oldrunning = 0;

		do {
			curl_multi_exec($master, $running);
			echo ($total_feed_chunk-$running).'/'.$total_feed.' '; ob_flush(); flush();
			usleep(100000);
		} while ($running > 0);


		// multi select contents
		foreach ($chunk as $url => $feed) {
			$results[$url] = curl_multi_getcontent($curl_arr[$url]);
		}


		// Ferme les gestionnaires
		curl_multi_close($master);
	}

	return $results;
}


function rafraichir_cache() {
	creer_dossier($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_cache'], 1);
	$arr_a = liste_elements("SELECT * FROM articles WHERE bt_statut = 1 ORDER BY bt_date DESC LIMIT 0, 20", array(), 'articles');
	$arr_c = liste_elements("SELECT * FROM commentaires WHERE bt_statut = 1 ORDER BY bt_id DESC LIMIT 0, 20", array(), 'commentaires');
	$arr_l = liste_elements("SELECT * FROM links WHERE bt_statut = 1 ORDER BY bt_id DESC LIMIT 0, 20", array(), 'links');
	$file = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_cache'].'/'.'cache_rss_array.dat';
	return file_put_contents($file, '<?php /* '.chunk_split(base64_encode(serialize(array('c' => $arr_c, 'a' => $arr_a, 'l' => $arr_l)))).' */');
}


/* retrieve all the feeds, returns the amount of new elements */
function refresh_rss($feeds) {
	$all_flux = array();
	$guid_in_db = rss_list_guid();
	$count_new = 0;
	$total_feed = count($feeds);
	$new_feeds = get_new_feeds($feeds);

	if (!$new_feeds) return 0;

	foreach ($new_feeds as $url => $feed) {
		if ($feed === FALSE) {
			continue;
		} else {
			$items = $feed['items'];

			// if we are here, there are new posts in the feed (md5 test on rss file is positive). Now test on each post.
			// only keep new post that are not in DB (in $guid_in_db) OR that are newer than the last post ever retreived.
			foreach($items as $key => $item) {
				if ( (in_array($item['bt_id'], $guid_in_db)) or ($item['bt_date'] <= $feeds[$url]['time']) ) {
					unset($items[$key]);
				}
					// si le post est plus récent que le dernier post reçu de ce flux,
					// enregistre la date du post avec le flux
					// on n’enregistre pas la date de dernière vérification, car la date peut être à un mauvais fuseau.
					if ($item['bt_date'] > $GLOBALS['liste_flux'][$feeds[$url]['link']]['time']) {
						$GLOBALS['liste_flux'][$feeds[$url]['link']]['time'] = $item['bt_date'];
					}
			}
			if (!empty($items)) {
				$all_flux = array_merge($all_flux, $items);
			}
		}
	}

	// if list of new elements is !empty, save new elements
	if (!empty($all_flux)) {
		$count_new = count($all_flux);
		$ret = bdd_rss($all_flux, 'enregistrer-nouveau');
		if ($ret !== TRUE) {
			echo $ret;
		}
	}


	// save last success time
	file_put_contents($GLOBALS['fichier_liste_fluxrss'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_flux']))).' */');
	return $count_new;
}



function get_new_feeds($feedlink, $md5='') {
	if (!$feeds = c_get_external_file($feedlink)) {
		return FALSE;
	}
	$return = array();
	foreach ($feeds as $url => $content) {
		if (!empty($content)) {
			$new_md5 = md5($content);
			// if Feed has changed : parse it (otherwise, do nothing : no need)
			if ($md5 != $new_md5 or '' == $md5) {
				$data_array = feed2array($content, $url);
				if ($data_array !== FALSE) {
					$return[$url] = $data_array;
					$data_array['infos']['md5'] = $md5;
					// update RSS last successfull update MD5
					$GLOBALS['liste_flux'][$url]['checksum'] = $new_md5;
					$GLOBALS['liste_flux'][$url]['iserror'] = 0;
				} else {
					if (isset($GLOBALS['liste_flux'][$url])) { // error on feed update (else would be on adding new feed)
						$GLOBALS['liste_flux'][$url]['iserror'] += 1;
					}
				}
			}
		}
	}

	if (!empty($return)) return $return;
	return FALSE;
}


# Based upon Feed-2-array, by bronco@warriordudimanche.net
function feed2array($feed_content, $feedlink) {
	$flux = array('infos'=>array(),'items'=>array());

	if (preg_match('#<rss(.*)</rss>#si', $feed_content)) { $flux['infos']['type'] = 'RSS'; } //RSS ?
	elseif (preg_match('#<feed(.*)</feed>#si', $feed_content)) { $flux['infos']['type'] = 'ATOM'; } //ATOM ?
	else { return false; } // the feed isn't rss nor atom

	try {
		if (@$feed_obj = new SimpleXMLElement($feed_content, LIBXML_NOCDATA)) {
			$flux['infos']['version']=$feed_obj->attributes()->version;
			if (!empty($feed_obj->attributes()->version)) { $flux['infos']['version'] = (string)$feed_obj->attributes()->version; }
			if (!empty($feed_obj->channel->title)) {        $flux['infos']['title'] = (string)$feed_obj->channel->title; }
			if (!empty($feed_obj->channel->subtitle)) {     $flux['infos']['subtitle'] = (string)$feed_obj->channel->subtitle; }
			if (!empty($feed_obj->channel->link)) {         $flux['infos']['link'] = (string)$feed_obj->channel->link; }
			if (!empty($feed_obj->channel->description)) {  $flux['infos']['description'] = (string)$feed_obj->channel->description; }
			if (!empty($feed_obj->channel->language)) {     $flux['infos']['language'] = (string)$feed_obj->channel->language; }
			if (!empty($feed_obj->channel->copyright)) {    $flux['infos']['copyright'] = (string)$feed_obj->channel->copyright; }

			if (!empty($feed_obj->title)) {       $flux['infos']['title'] = (string)$feed_obj->title; }
			if (!empty($feed_obj->subtitle)) {    $flux['infos']['subtitle'] = (string)$feed_obj->subtitle; }
			if (!empty($feed_obj->link)) {        $flux['infos']['link'] = (string)$feed_obj->link; }
			if (!empty($feed_obj->description)) { $flux['infos']['description'] = (string)$feed_obj->description; }
			if (!empty($feed_obj->language)) {    $flux['infos']['language'] = (string)$feed_obj->language; }
			if (!empty($feed_obj->copyright)) {   $flux['infos']['copyright'] = (string)$feed_obj->copyright; }

			if (!empty($feed_obj->channel->item)){ $items = $feed_obj->channel->item; }
			if (!empty($feed_obj->entry)){ $items = $feed_obj->entry; }
			if (empty($items)) { return $flux; }

			//aff($feed_obj);
			foreach ($items as $item) {
				$c=count($flux['items']);
				if (!empty($item->title)) {         $flux['items'][$c]['bt_title'] = (string)$item->title; }
					else { $flux['items'][$c]['bt_title'] = "-"; }
				if (!empty($item->link['href'])) {  $flux['items'][$c]['bt_link'] = (string)$item->link['href']; }
				if (!empty($item->link)) {          $flux['items'][$c]['bt_link'] = (string)$item->link; }
				if (!empty($item->author->name)) {  $flux['items'][$c]['bt_author'] = (string)$item->author->name; }

				if (!empty($item->guid)) {          $flux['items'][$c]['bt_id'] = (string)$item->guid; }
				elseif (!empty($item->id)) {          $flux['items'][$c]['bt_id'] = (string)$item->id; }
					else { $flux['items'][$c]['bt_id'] = microtime(); }

				if (!empty($item->updated)) {       $flux['items'][$c]['bt_date'] = (string)$item->updated; }
				if (!empty($item->pubDate)) {       $flux['items'][$c]['bt_date'] = (string)$item->pubDate; }
				if (!empty($item->published)) {     $flux['items'][$c]['bt_date'] = (string)$item->published; }
				if (!empty($item->subtitle)) {      $flux['items'][$c]['bt_content'] = (string)$item->subtitle; }
				if (!empty($item->description)) {   $flux['items'][$c]['bt_content'] = (string)$item->description; }
				if (!empty($item->summary)) {       $flux['items'][$c]['bt_content'] = (string)$item->summary; }
				if (!empty($item->content)) {       $flux['items'][$c]['bt_content'] = (string)$item->content; }

				if (!empty($item->children('content', true)->encoded)) {       $flux['items'][$c]['bt_content'] = (string)$item->children('content', true)->encoded; }

				if (!isset($flux['items'][$c]['bt_content'])) $flux['items'][$c]['bt_content'] = '';
				if (!empty($flux['items'][$c]['bt_date'])) { $flux['items'][$c]['bt_date'] = strtotime($flux['items'][$c]['bt_date']); }
					else { $flux['items'][$c]['bt_date'] = time(); }

				// place le lien du flux (on a besoin de ça)
				$flux['items'][$c]['bt_feed_url'] = $feedlink;
				// place le statut
				$flux['items'][$c]['bt_statut'] = '1';
				// place le dossier
				$flux['items'][$c]['bt_folder'] = (isset($GLOBALS['liste_flux'][$feedlink]['folder']) ? $GLOBALS['liste_flux'][$feedlink]['folder'] : '' ) ;

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

/* From the data out of DB, creates JSON, to send to browser
*/
function send_rss_json($rss_entries) {
	// send all the entries data in a JSON format
	$out = '';
	$out .= '<script type="text/javascript">';

	// RSS entries
	$out .= 'var rss_entries = {"list": ['."\n";
	$count = count($rss_entries)-1;
	foreach ($rss_entries as $i => $entry) {
		// note : json_encode DOES add « " » on the data, so I use « encode() » and not '"'.encode().'"';
		$out .= '{'.
			'"id": "'.$entry['bt_id'].'",'.
			'"date": "'.date_formate(date('YmdHis', $entry['bt_date'])).' - '.heure_formate(date('YmdHis', $entry['bt_date'])).'",'.
			'"title": '.json_encode($entry['bt_title']).','.
			'"link": '.json_encode($entry['bt_link']).','.
			'"feed": '.json_encode($entry['bt_feed']).','.
			'"sitename": '.json_encode($GLOBALS['liste_flux'][$entry['bt_feed']]['title']).','.
			'"folder": '.json_encode($GLOBALS['liste_flux'][$entry['bt_feed']]['folder']).','.
			'"content": '.json_encode($entry['bt_content']).','.
			'"statut": "'.$entry['bt_statut'].'"'.
		'}'.(($count==$i) ? '' :',')."\n";
	}
	$out .= ']'."\n".'}';
	$out .=  '</script>'."\n";

	return $out;
}


if (!function_exists('http_parse_headers')) {
	function http_parse_headers($raw_headers) {
		$headers = array();

		foreach ($raw_headers as $i => $h) {
			$h = explode(':', $h, 2);

			if (isset($h[1])) {
				$headers[$h[0]] = trim($h[1]);
			}
		}

		return $headers;
	}
}
