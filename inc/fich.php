<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden <timo@neerden.eu>
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
	$fichier_user = '../config/user.php';
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


function fichier_prefs() {
	$fichier_prefs = '../config/prefs.php';
	if(!empty($_POST['_verif_envoi'])) {
		$lang = (isset($_POST['langue']) and preg_match('#^[a-z]{2}$#', $_POST['langue'])) ? $_POST['langue'] : 'fr';
		$auteur = (clean_txt($_POST['auteur']));
		$email = (clean_txt($_POST['email']));
		$nomsite = (clean_txt($_POST['nomsite']));
		$description = (clean_txt($_POST['description']));
		$keywords = (clean_txt($_POST['keywords']));
		$racine = addslashes(trim($_POST['racine']));
		$max_bill_acceuil = $_POST['nb_maxi'];
//		$max_linx_accueil = $_POST['nb_maxi_linx'];
//		$max_comm_encart = $_POST['nb_maxi_comm'];
		$max_bill_admin = $_POST['nb_list'];
		$max_comm_admin = $_POST['nb_list_com'];
		$format_date = $_POST['format_date'];
		$format_heure = $_POST['format_heure'];
		$fuseau_horaire = addslashes(clean_txt($_POST['fuseau_horaire']));
		$global_com_rule = $_POST['global_comments'];
		$connexion_captcha = $_POST['connexion_captcha'];
		$activer_categories = $_POST['activer_categories'];
		$theme_choisi = addslashes(clean_txt($_POST['theme']));
		$comm_defaut_status = $_POST['comm_defaut_status'];
		$automatic_keywords = $_POST['auto_keywords'];
		$require_email = $_POST['require_email'];
		$auto_check_updates = $_POST['check_update'];
		// linx
//		$autoriser_liens_public = $_POST['allow_public_linx'];
//		$linx_defaut_status = $_POST['linx_defaut_status'];
		$auto_dl_liens_fichiers = $_POST['dl_link_to_files'];
		$nombre_liens_admin = $_POST['nb_list_linx'];
	} else {
		$lang = (isset($_POST['langue']) and preg_match('#^[a-z]{2}$#', $_POST['langue'])) ? $_POST['langue'] : 'fr';
		$auteur = clean_txt($GLOBALS['identifiant']);
		$email = 'mail@example.com';
		$nomsite = 'Blogotext';
		$description = clean_txt($GLOBALS['lang']['go_to_pref']);
		$keywords = 'blog, blogotext';
		$racine = clean_txt(trim($_POST['racine']));
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

function get_external_file($url, $timeout) {
	$context = stream_context_create(array('http'=>array(
			'user_agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:26.0 BlogoText-UA) Gecko/20100101 Firefox/26.0',
			'timeout' => $timeout
		))); // Timeout : time until we stop waiting for the response.
	$data = @file_get_contents($url, false, $context, -1, 4000000); // We download at most 4 Mb from source.
	if (isset($data) and isset($http_response_header[0]) and (strpos($http_response_header[0], '200 OK') !== FALSE) ) {
		//debug($http_response_header[0]);
		return $data;
	}
	else {
		return array();
	}
}

function rafraichir_cache() {
	creer_dossier($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_cache'], 1);
	$arr_a = liste_elements("SELECT * FROM articles WHERE bt_statut = 1 ORDER BY bt_date DESC LIMIT 0, 20", array(), 'articles');
	$arr_c = liste_elements("SELECT * FROM commentaires WHERE bt_statut = 1 ORDER BY bt_id DESC LIMIT 0, 20", array(), 'commentaires');
	$arr_l = liste_elements("SELECT * FROM links WHERE bt_statut = 1 ORDER BY bt_id DESC LIMIT 0, 20", array(), 'links');
	$file = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_cache'].'/'.'cache_rss_array.dat';
	return file_put_contents($file, '<?php /* '.chunk_split(base64_encode(serialize(array('c' => $arr_c, 'a' => $arr_a, 'l' => $arr_l)))).' */');
}
