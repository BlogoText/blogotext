<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2015 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***


/*  Creates a new BlogoText base.
    if file does not exists, it is created, as well as the tables.
    if file does exists, tables are checked and created if not exists
*/
function create_tables() {
	if (file_exists($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_config'].'/'.'mysql.php')) {
		include($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_config'].'/'.'mysql.php');
	}
	$if_not_exists = ($GLOBALS['sgdb'] == 'mysql') ? 'IF NOT EXISTS' : ''; // SQLite does'nt know these syntaxes.
	$auto_increment = ($GLOBALS['sgdb'] == 'mysql') ? 'AUTO_INCREMENT' : ''; // SQLite does'nt know these syntaxes, but MySQL needs it.

	$GLOBALS['dbase_structure']['links'] = "CREATE TABLE ".$if_not_exists." links
		(
			ID INTEGER PRIMARY KEY $auto_increment,
			bt_type CHAR(20),
			bt_id BIGINT,
			bt_content TEXT,
			bt_wiki_content TEXT,
			bt_author TEXT,
			bt_title TEXT,
			bt_tags TEXT,
			bt_link TEXT,
			bt_statut TINYINT
		); CREATE INDEX dateL ON links ( bt_id );";

	$GLOBALS['dbase_structure']['commentaires'] = "CREATE TABLE ".$if_not_exists." commentaires
		(
			ID INTEGER PRIMARY KEY $auto_increment,
			bt_type CHAR(20),
			bt_id BIGINT,
			bt_article_id BIGINT,
			bt_content TEXT,
			bt_wiki_content TEXT,
			bt_author TEXT,
			bt_link TEXT,
			bt_webpage TEXT,
			bt_email TEXT,
			bt_subscribe TINYINT,
			bt_statut TINYINT
		); CREATE INDEX dateC ON commentaires ( bt_id );";


	$GLOBALS['dbase_structure']['articles'] = "CREATE TABLE ".$if_not_exists." articles
		(
			ID INTEGER PRIMARY KEY $auto_increment,
			bt_type CHAR(20),
			bt_id BIGINT,
			bt_date BIGINT,
			bt_title TEXT,
			bt_abstract TEXT,
			bt_notes TEXT,
			bt_link TEXT,
			bt_content TEXT,
			bt_wiki_content TEXT,
			bt_categories TEXT,
			bt_keywords TEXT,
			bt_nb_comments INTEGER,
			bt_allow_comments TINYINT,
			bt_statut TINYINT
		); CREATE INDEX dateidA ON articles (bt_date, bt_id );";

	/* here bt_ID is a GUID, from the feed, not only a 'YmdHis' date string.*/
	$GLOBALS['dbase_structure']['rss'] = "CREATE TABLE ".$if_not_exists." rss
		(
			ID INTEGER PRIMARY KEY $auto_increment,
			bt_id TEXT,
			bt_date BIGINT,
			bt_title TEXT,
			bt_link TEXT,
			bt_feed TEXT,
			bt_content TEXT,
			bt_statut TINYINT,
			bt_folder TEXT
		); CREATE INDEX dateidR ON rss (bt_date, bt_id );";

	/*
	* SQLite : opens file, check tables by listing them, create the one that miss.
	*
	*/
	switch ($GLOBALS['sgdb']) {
		case 'sqlite':

				if (!creer_dossier($GLOBALS['BT_ROOT_PATH'].''.$GLOBALS['dossier_db'])) {
					die('Impossible de creer le dossier databases (chmod?)');
				}

				$file = $GLOBALS['BT_ROOT_PATH'].''.$GLOBALS['dossier_db'].'/'.$GLOBALS['db_location'];
				// open tables

				try {
					$db_handle = new PDO('sqlite:'.$file);
					$db_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db_handle->query("PRAGMA temp_store=MEMORY; PRAGMA synchronous=OFF; PRAGMA journal_mode=WAL;");
					// list tables
					$list_tbl = $db_handle->query("SELECT name FROM sqlite_master WHERE type='table'");
					// make an normal array, need for "in_array()"
					$tables = array();
					foreach($list_tbl as $j) {
						$tables[] = $j['name'];
					}

					// check each wanted table (this is because the "IF NOT EXISTS" condition doesn’t exist in lower versions of SQLite.
					$wanted_tables = array('commentaires', 'articles', 'links', 'rss');
					foreach ($wanted_tables as $i => $name) {
						if (!in_array($name, $tables)) {
							$results = $db_handle->query($GLOBALS['dbase_structure'][$name]);
						}
					}
				} catch (Exception $e) {
					die('Erreur 1: '.$e->getMessage());
				}
			break;

		/*
		* MySQL : create tables with the IF NOT EXISTS condition. Easy.
		*
		*/
		case 'mysql':
				try {

					$options_pdo[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
					$db_handle = new PDO('mysql:host='.$GLOBALS['mysql_host'].';dbname='.$GLOBALS['mysql_db'].";charset=utf8;sql_mode=PIPES_AS_CONCAT;", $GLOBALS['mysql_login'], $GLOBALS['mysql_passwd'], $options_pdo);
					// check each wanted table
					$wanted_tables = array('commentaires', 'articles', 'links', 'rss');
					foreach ($wanted_tables as $i => $name) {
							$results = $db_handle->query($GLOBALS['dbase_structure'][$name]."DEFAULT CHARSET=utf8");
							$results->closeCursor();
					}
				} catch (Exception $e) {
					die('Erreur 2: '.$e->getMessage());
				}
			break;
	}

	return $db_handle;
}


/* Open a base */
function open_base() {
	$handle = create_tables();
	$handle->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
	return $handle;
}


/* lists articles with search criterias given in $array. Returns an array containing the data*/
function liste_elements($query, $array, $data_type) {
	try {
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute($array);
		$return = array();

		switch ($data_type) {
			case 'articles':
				while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
					$return[] = init_list_articles($row);
				}
				break;
			case 'commentaires':
				while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
					$return[] = init_list_comments($row);
				}
				break;
			case 'links':
			case 'rss':
				while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
					$return[] = $row;
				}
				break;
			default:
				break;
		}

		return $return;
	} catch (Exception $e) {
		die('Erreur 89208 : '.$e->getMessage());
	}
}

/* same as above, but return the amount of entries */
function liste_elements_count($query, $array) {
	try {
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute($array);
		$result = $req->fetch();
		return $result['nbr'];
	} catch (Exception $e) {
		die('Erreur 0003: '.$e->getMessage());
	}
}

// returns or prints an entry of some element of some table (very basic)
function get_entry($base_handle, $table, $entry, $id, $retour_mode) {
	$query = "SELECT $entry FROM $table WHERE bt_id=?";
	try {
		$req = $base_handle->prepare($query);
		$req->execute(array($id));
		$result = $req->fetch();
		//echo '<pre>';print_r($result);
	} catch (Exception $e) {
		die('Erreur : '.$e->getMessage());
	}

	if ($retour_mode == 'return' and !empty($result[$entry])) {
		return $result[$entry];
	}
	if ($retour_mode == 'echo' and !empty($result[$entry])) {
		echo $result[$entry];
	}
	return '';
}

function traiter_form_billet($billet) {
	if ( isset($_POST['enregistrer']) and !isset($billet['ID']) ) {
		$result = bdd_article($billet, 'enregistrer-nouveau');
		$redir = basename($_SERVER['PHP_SELF']).'?post_id='.$billet['bt_id'].'&msg=confirm_article_maj';
	}
	elseif ( isset($_POST['enregistrer']) and isset($billet['ID']) ) {
		$result = bdd_article($billet, 'modifier-existant');
		$redir = basename($_SERVER['PHP_SELF']).'?post_id='.$billet['bt_id'].'&msg=confirm_article_ajout';
	}
	elseif ( isset($_POST['supprimer']) and isset($_POST['ID']) and is_numeric($_POST['ID']) ) {
		$result = bdd_article($billet, 'supprimer-existant');
		try {
			$req = $GLOBALS['db_handle']->prepare('DELETE FROM commentaires WHERE bt_article_id=?');
			$req->execute(array($_POST['article_id']));
		} catch (Exception $e) {
			die('Erreur Suppr Comm associés: '.$e->getMessage());
		}

		$redir = 'articles.php?msg=confirm_article_suppr';
	}
	if ($result === TRUE) {
		rafraichir_cache();
		redirection($redir);
	}
	else { die($result); }
}

function bdd_article($billet, $what) {
	// l'article n'existe pas, on le crée
	if ( $what == 'enregistrer-nouveau' ) {
		try {
			$req = $GLOBALS['db_handle']->prepare('INSERT INTO articles
				(	bt_type,
					bt_id,
					bt_date,
					bt_title,
					bt_abstract,
					bt_link,
					bt_notes,
					bt_content,
					bt_wiki_content,
					bt_categories,
					bt_keywords,
					bt_allow_comments,
					bt_nb_comments,
					bt_statut
				)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
			$req->execute(array(
				'article',
				$billet['bt_id'],
				$billet['bt_date'],
				$billet['bt_title'],
				$billet['bt_abstract'],
				$billet['bt_link'],
				$billet['bt_notes'],
				$billet['bt_content'],
				$billet['bt_wiki_content'],
				$billet['bt_categories'],
				$billet['bt_keywords'],
				$billet['bt_allow_comments'],
				0,
				$billet['bt_statut']
			));
			return TRUE;
		} catch (Exception $e) {
			return 'Erreur ajout article: '.$e->getMessage();
		}
	// l'article existe, et il faut le mettre à jour alors.
	} elseif ( $what == 'modifier-existant' ) {
		try {
			$req = $GLOBALS['db_handle']->prepare('UPDATE articles SET
				bt_date=?,
				bt_title=?,
				bt_link=?,
				bt_abstract=?,
				bt_notes=?,
				bt_content=?,
				bt_wiki_content=?,
				bt_categories=?,
				bt_keywords=?,
				bt_allow_comments=?,
				bt_statut=?
				WHERE ID=?');
			$req->execute(array(
					$billet['bt_date'],
					$billet['bt_title'],
					$billet['bt_link'],
					$billet['bt_abstract'],
					$billet['bt_notes'],
					$billet['bt_content'],
					$billet['bt_wiki_content'],
					$billet['bt_categories'],
					$billet['bt_keywords'],
					$billet['bt_allow_comments'],
					$billet['bt_statut'],
					$_POST['ID']
			));
			return TRUE;
		} catch (Exception $e) {
			return 'Erreur mise à jour de l’article: '.$e->getMessage();
		}
	// Suppression d'un article
	} elseif ( $what == 'supprimer-existant' ) {
		try {
			$req = $GLOBALS['db_handle']->prepare('DELETE FROM articles WHERE ID=?');
			$req->execute(array($_POST['ID']));
			return TRUE;
		} catch (Exception $e) {
			return 'Erreur 123456 : '.$e->getMessage();
		}
	}
}



// traiter un ajout de lien prend deux étapes :
//  1) on donne le lien > il donne un form avec lien+titre
//  2) après ajout d'une description, on clic pour l'ajouter à la bdd.
// une fois le lien donné (étape 1) et les champs renseignés (étape 2) on traite dans la BDD
function traiter_form_link($link) {
	$query_string = str_replace(((isset($_GET['msg'])) ? '&msg='.$_GET['msg'] : ''), '', $_SERVER['QUERY_STRING']);
	if ( isset($_POST['enregistrer'])) {
		$result = bdd_lien($link, 'enregistrer-nouveau');
		$redir = basename($_SERVER['PHP_SELF']).'?msg=confirm_link_ajout';
	}

	elseif (isset($_POST['editer'])) {
		$result = bdd_lien($link, 'modifier-existant');
		$redir = basename($_SERVER['PHP_SELF']).'?msg=confirm_link_edit';
	}

	elseif ( isset($_POST['supprimer'])) {
		$result = bdd_lien($link, 'supprimer-existant');
		$redir = basename($_SERVER['PHP_SELF']).'?msg=confirm_link_suppr';
	}

	if ($result === TRUE) {
		rafraichir_cache();
		redirection($redir);
	} else { die($result); }

}


function bdd_lien($link, $what) {
	if ($what == 'enregistrer-nouveau') {
		try {
			$req = $GLOBALS['db_handle']->prepare('INSERT INTO links
			(	bt_type,
				bt_id,
				bt_content,
				bt_wiki_content,
				bt_author,
				bt_title,
				bt_link,
				bt_tags,
				bt_statut
			)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
			$req->execute(array(
				$link['bt_type'],
				$link['bt_id'],
				$link['bt_content'],
				$link['bt_wiki_content'],
				$link['bt_author'],
				$link['bt_title'],
				$link['bt_link'],
				$link['bt_tags'],
				$link['bt_statut']
			));
			return TRUE;
		} catch (Exception $e) {
			return 'Erreur 5867 : '.$e->getMessage();
		}

	} elseif ($what == 'modifier-existant') {
		try {
			$req = $GLOBALS['db_handle']->prepare('UPDATE links SET
				bt_content=?,
				bt_wiki_content=?,
				bt_author=?,
				bt_title=?,
				bt_link=?,
				bt_tags=?,
				bt_statut=?
				WHERE ID=?');
			$req->execute(array(
				$link['bt_content'],
				$link['bt_wiki_content'],
				$link['bt_author'],
				$link['bt_title'],
				$link['bt_link'],
				$link['bt_tags'],
				$link['bt_statut'],
				$link['ID']
			));
			return TRUE;
		} catch (Exception $e) {
			return 'Erreur 435678 : '.$e->getMessage();
		}
	}

	elseif ($what == 'supprimer-existant') {
		try {
			$req = $GLOBALS['db_handle']->prepare('DELETE FROM links WHERE ID=?');
			$req->execute(array($link['ID']));
			return TRUE;
		} catch (Exception $e) {
			return 'Erreur 97652 : '.$e->getMessage();
		}
	}
}

// Called when a new comment is posted (public side or admin side) or on edit/activating/removing
//  when adding, redirects with message after processing
//  when edit/activating/removing, dies with message after processing (message is then caught with AJAX)

function traiter_form_commentaire($commentaire, $admin) {
	$msg_param_to_trim = (isset($_GET['msg'])) ? '&msg='.$_GET['msg'] : '';
	$query_string = str_replace($msg_param_to_trim, '', $_SERVER['QUERY_STRING']);

	// add new comment (admin + public)
	if (isset($_POST['enregistrer']) and empty($_POST['is_it_edit'])) {
		$result = bdd_commentaire($commentaire, 'enregistrer-nouveau');
		if ($result === TRUE) {
			send_emails($commentaire['bt_id']); // send emails new comment posted to people that are subscriben
			$redir = basename($_SERVER['PHP_SELF']).'?'.$query_string.'&msg=confirm_comment_ajout';
		}
		else { die($result); }
	}
	// edit existing comment (admin)
	elseif (	isset($_POST['enregistrer']) and $admin == 'admin'
	  and isset($_POST['is_it_edit']) and $_POST['is_it_edit'] == 'yes'
	  and isset($commentaire['ID']) ) {
		$result = bdd_commentaire($commentaire, 'editer-existant');
		$redir = basename($_SERVER['PHP_SELF']).'?'.$query_string.'&msg=confirm_comment_edit';
	}
	// remove existing comment (admin) #ajax call
	elseif (isset($_POST['com_supprimer']) and $admin == 'admin' ) {
		$comm = array('ID' => htmlspecialchars($_POST['com_supprimer']), 'bt_article_id' => htmlspecialchars($_POST['com_article_id']));
		$result = bdd_commentaire($comm, 'supprimer-existant');
		// Ajax response
		if ($result === TRUE) {
			rafraichir_cache();
			//echo var_dump($comm);
			echo 'Success'.new_token();
		}
		else { echo 'Error'.new_token(); }
		exit;
	}
	// change status of comm (admin) #ajax call
	elseif (isset($_POST['com_activer']) and $admin == 'admin' ) {
		$comm = array('ID' => htmlspecialchars($_POST['com_activer']), 'bt_article_id' => htmlspecialchars($_POST['com_article_id']));
		$result = bdd_commentaire($comm, 'activer-existant');
		// Ajax response
		if ($result === TRUE) {
			rafraichir_cache();
			//echo var_dump($comm);
			echo 'Success'.new_token();
		}
		else { echo 'Error'.new_token(); }
		exit;
	}

	// do nothing & die (admin + public)
	else {
		redirection(basename($_SERVER['PHP_SELF']).'?'.$query_string.'&msg=nothing_happend_oO');
	}

	if ($result === TRUE) {
		rafraichir_cache();
		redirection($redir);
	}
	else { die($result); }
}

function bdd_commentaire($commentaire, $what) {

	// ENREGISTREMENT D'UN NOUVEAU COMMENTAIRE.
	if ($what == 'enregistrer-nouveau') {
		try {
			$req = $GLOBALS['db_handle']->prepare('INSERT INTO commentaires
				(	bt_type,
					bt_id,
					bt_article_id,
					bt_content,
					bt_wiki_content,
					bt_author,
					bt_link,
					bt_webpage,
					bt_email,
					bt_subscribe,
					bt_statut
				)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
			$req->execute(array(
				'comment',
				$commentaire['bt_id'],
				$commentaire['bt_article_id'],
				$commentaire['bt_content'],
				$commentaire['bt_wiki_content'],
				$commentaire['bt_author'],
				$commentaire['bt_link'],
				$commentaire['bt_webpage'],
				$commentaire['bt_email'],
				$commentaire['bt_subscribe'],
				$commentaire['bt_statut']
			));
			// remet à jour le nombre de commentaires associés à l’article.
			$nb_comments_art = liste_elements_count("SELECT count(*) AS nbr FROM commentaires WHERE bt_article_id=? and bt_statut=1", array($commentaire['bt_article_id']));
			$req2 = $GLOBALS['db_handle']->prepare('UPDATE articles SET bt_nb_comments=? WHERE bt_id=?');
			$req2->execute( array($nb_comments_art, $commentaire['bt_article_id']) );

			return TRUE;
		} catch (Exception $e) {
			return 'Erreur : '.$e->getMessage();
		}
	}
	elseif ($what == 'editer-existant') {
	// ÉDITION D'UN COMMENTAIRE DÉJÀ EXISTANT. (ou activation)
		try {
			$req = $GLOBALS['db_handle']->prepare('UPDATE commentaires SET
				bt_article_id=?,
				bt_content=?,
				bt_wiki_content=?,
				bt_author=?,
				bt_link=?,
				bt_webpage=?,
				bt_email=?,
				bt_subscribe=?,
				bt_statut=?
				WHERE ID=?');
			$req->execute(array(
				$commentaire['bt_article_id'],
				$commentaire['bt_content'],
				$commentaire['bt_wiki_content'],
				$commentaire['bt_author'],
				$commentaire['bt_link'],
				$commentaire['bt_webpage'],
				$commentaire['bt_email'],
				$commentaire['bt_subscribe'],
				$commentaire['bt_statut'],
				$commentaire['ID'],
			));

			// remet à jour le nombre de commentaires associés à l’article.
			$nb_comments_art = liste_elements_count("SELECT count(*) AS nbr FROM commentaires WHERE bt_article_id=? and bt_statut=1", array($commentaire['bt_article_id']));

			$req2 = $GLOBALS['db_handle']->prepare('UPDATE articles SET bt_nb_comments=? WHERE bt_id=?');
			$req2->execute( array($nb_comments_art, $commentaire['bt_article_id']) );
			return TRUE;
		} catch (Exception $e) {
			return 'Erreur : '.$e->getMessage();
		}
	}

	// SUPPRESSION D'UN COMMENTAIRE
	elseif ($what == 'supprimer-existant') {
		try {
			$req = $GLOBALS['db_handle']->prepare('DELETE FROM commentaires WHERE ID=?');
			$req->execute(array($commentaire['ID']));

			// remet à jour le nombre de commentaires associés à l’article.
			$nb_comments_art = liste_elements_count("SELECT count(*) AS nbr FROM commentaires WHERE bt_article_id=? and bt_statut=1", array($commentaire['bt_article_id']));
			$req2 = $GLOBALS['db_handle']->prepare('UPDATE articles SET bt_nb_comments=? WHERE bt_id=?');

			$req2->execute( array($nb_comments_art, $commentaire['bt_article_id']) );
			return TRUE;
		} catch (Exception $e) {
			return 'Erreur : '.$e->getMessage();
		}
	}

	// CHANGEMENT STATUS COMMENTAIRE
	elseif ($what == 'activer-existant') {
		try {
			$req = $GLOBALS['db_handle']->prepare('UPDATE commentaires SET bt_statut=ABS(bt_statut-1) WHERE ID=?');
			$req->execute(array($commentaire['ID']));

			// remet à jour le nombre de commentaires associés à l’article.
			$nb_comments_art = liste_elements_count("SELECT count(*) AS nbr FROM commentaires WHERE bt_article_id=? and bt_statut=1", array($commentaire['bt_article_id']));
			$req2 = $GLOBALS['db_handle']->prepare('UPDATE articles SET bt_nb_comments=? WHERE bt_id=?');
			$req2->execute( array($nb_comments_art, $commentaire['bt_article_id']) );
			return TRUE;
		} catch (Exception $e) {
			return 'Erreur : '.$e->getMessage();
		}
	}
}

/* FOR COMMENTS : RETUNS nb_com per author */
function nb_entries_as($table, $what) {
	$result = array();
	$query = "SELECT count($what) AS nb, $what FROM $table GROUP BY $what ORDER BY nb DESC";
	try {
		$result = $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	} catch (Exception $e) {
		die('Erreur 0349 : '.$e->getMessage());
	}
}


// retourne la liste les jours d’un mois que le calendrier doit afficher.
function table_list_date($date, $statut, $table) {
	$return = array();
	$and_statut = (!empty($statut)) ? 'AND bt_statut=\''.$statut.'\'' : '';
	$bt_ = ($table == 'articles') ? 'bt_date' : 'bt_id';
	$and_date = 'AND '.$bt_.' <= '.date('YmdHis');

	$query = "SELECT DISTINCT substr($bt_, 7, 2) AS date FROM $table WHERE $bt_ LIKE '$date%' $and_statut $and_date";

	try {
		$req = $GLOBALS['db_handle']->query($query);
		while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			$return[] = $row['date'];
		}
		return $return;
	} catch (Exception $e) {
		die('Erreur 21436 : '.$e->getMessage());
	}
}

function list_all_tags($table, $statut) {
	$col = ($table == 'articles') ? 'bt_categories' : 'bt_tags';
	try {
		if ($statut !== FALSE) {
			$res = $GLOBALS['db_handle']->query("SELECT $col FROM $table WHERE bt_statut = $statut");
		} else {
			$res = $GLOBALS['db_handle']->query("SELECT $col FROM $table");
		}
		$liste_tags = '';
		// met tous les tags de tous les articles bout à bout
		while ($entry = $res->fetch()) {
			if (trim($entry[$col]) != '') {
				$liste_tags .= $entry[$col].',';
			}
		}
		$res->closeCursor();
		$liste_tags = rtrim($liste_tags, ',');
	} catch (Exception $e) {
		die('Erreur 4354768 : '.$e->getMessage());
	}

	$liste_tags = str_replace(array(', ', ' ,'), ',', $liste_tags);
	$tab_tags = explode(',', $liste_tags);
	sort($tab_tags);
	unset($tab_tags['']);
	return array_count_values($tab_tags);
}


/* Enregistre le flux dans une BDD.
   $flux est un Array avec les données dedans.
	$flux ne contient que les entrées qui doivent être enregistrées
	 (la recherche de doublons est fait en amont)
*/
function bdd_rss($flux, $what) {
	if ($what == 'enregistrer-nouveau') {
		try {
			$GLOBALS['db_handle']->beginTransaction();
			foreach ($flux as $post) {
				$req = $GLOBALS['db_handle']->prepare('INSERT INTO rss
				(  bt_id,
					bt_date,
					bt_title,
					bt_link,
					bt_feed,
					bt_content,
					bt_statut,
					bt_folder
				)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
				$req->execute(array(
					$post['bt_id'],
					$post['bt_date'],
					$post['bt_title'],
					$post['bt_link'],
					$post['bt_feed_url'],
					$post['bt_content'],
					$post['bt_statut'],
					$post['bt_folder']
				));
			}
			$GLOBALS['db_handle']->commit();
			return TRUE;
		} catch (Exception $e) {
			return 'Erreur 5867-rss-add-sql : '.$e->getMessage();
		}
	}
}

/* FOR RSS : RETUNS list of GUID in whole DB */
function rss_list_guid() {
	$result = array();
	$query = "SELECT bt_id FROM rss";
	try {
		$result = $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);
		return $result;
	} catch (Exception $e) {
		die('Erreur 0329-rss-get_guid : '.$e->getMessage());
	}
}

/* FOR RSS : RETUNS nb of articles per feed */
function rss_count_feed() {
	$result = array();
	$query = "SELECT bt_feed, SUM(bt_statut) AS nbrun FROM rss GROUP BY bt_feed ORDER BY nbrun DESC";
	try {
		$result = $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	} catch (Exception $e) {
		die('Erreur 0329-rss-count_per_feed : '.$e->getMessage());
	}
}

/* FOR RSS : get $_POST and update feeds (title, url…) for feeds.php?config */
function traiter_form_rssconf() {
	$msg_param_to_trim = (isset($_GET['msg'])) ? '&msg='.$_GET['msg'] : '';
	$query_string = str_replace($msg_param_to_trim, '', $_SERVER['QUERY_STRING']);
	// traitement
	$GLOBALS['db_handle']->beginTransaction();
	foreach($GLOBALS['liste_flux'] as $i => $feed) {
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
			}
			// title, url or folders have changed
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
	file_put_contents($GLOBALS['fichier_liste_fluxrss'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_flux']))).' */');

	$redir = basename($_SERVER['PHP_SELF']).'?'.$query_string.'&msg=confirm_feeds_edit';
	redirection($redir);

}

