<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden <ti-mo@myopera.com>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***


$GLOBALS['begin'] = microtime(TRUE);
$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

ini_set('pcre.backtrack_limit', 1000000); // pcre limit : limit of preg_* string sizes.
ini_set('pcre.recursion_limit', 500000);  // same

operate_session();

$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);
$GLOBALS['liste_fichiers'] = open_file_db_fichiers($GLOBALS['fichier_liste_fichiers']);

$GLOBALS['minimal_tags_comm'] = array(
	'bt_type',
	'bt_id',
	'bt_article_id',
	'bt_link',
	'bt_content',
	'bt_wiki_content',
	'bt_author',
	'bt_webpage',
	'bt_email',
	'bt_subscribe',
	'bt_statut'
);


$GLOBALS['minimal_tags_arts'] = array(
	'bt_type',
	'bt_id',
	'bt_date',
	'bt_title',
	'bt_abstract',
	'bt_notes',
	'bt_link',
	'bt_content',
	'bt_wiki_content',
	'bt_categories',
	'bt_keywords',
	'bt_nb_comments',
	'bt_allow_comments',
	'bt_statut'
);


$GLOBALS['minimal_tags_link'] = array(
	'bt_type',
	'bt_id',
	'bt_link',
	'bt_content',
	'bt_wiki_content',
	'bt_statut',
	'bt_author',
	'bt_title',
	'bt_tags'
);

$GLOBALS['minimal_tags_files'] = array(
	'bt_id',
	'bt_type',
	'bt_fileext',
	'bt_filesize',
	'bt_filename',
	'bt_content', 
	'bt_wiki_content',
	'bt_checksum',
	'bt_statut',
	'bt_backup_img_base64'
); // 'bt_backup_img_base64' is only for read the XML. It is not saved in the DB

// once the uploaded XML file is parsed, the user may have asked to destroy it server-side.
if (isset($_POST['supprimer_fichier_source']) and ($_POST['supprimer_fichier_source'] == '1' )) {
	$file = htmlspecialchars($_POST['filetodelete']);
	if (file_exists($file)) {
		$ouverture = fopen($file, 'w');
		fwrite($ouverture, 'erased'); // write smt to empty it…
		fclose($ouverture);
		if (unlink($file)) { // … then delete it.
			redirection($_SERVER['PHP_SELF'].'?msg=confirm_backupfile_suppr');
		} else {        		
			redirection($_SERVER['PHP_SELF'].'?msg=error_backupfile_suppr');
		}
	}
}


afficher_top($GLOBALS['lang']['titre_maintenance']);
echo '<div id="top">';
afficher_msg($GLOBALS['lang']['titre_maintenance']);
afficher_menu('preferences.php');
echo '</div>';

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

/* #################################################################### MAKE BACKUP FILE ####################################### */


// misc funtions

// create the backup folder
function creer_dossier_sauv() {
	$dossier_backup = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_backup'];
	if (creer_dossier($dossier_backup, 0) === FALSE) {
		echo $GLOBALS['lang']['err_file_write'];
	}
}

// gets a text/xml file and returns the content of a tag
function parse_xml($fichier, $balise) {
	if (is_file($fichier)) {
		if ($string = file_get_contents($fichier)) {
			return parse_xml_str($string, $balise);
		} else {
			erreur('Impossible de lire le fichier '.$fichier);
		}
	}
}

// this function "parse_xml_str" is the same as "parse_xml" 
// it only uses a string instead of a file as first parameter.
function parse_xml_str($string, $balise) {
	$sizeitem = strlen('<'.$balise.'>');
	$debut = strpos($string, '<'.$balise.'>') + $sizeitem;
	$fin = strpos($string, '</'.$balise.'>');
	if (($debut and $fin) !== FALSE) {
		$lenght = $fin - $debut;
		$return = substr($string, $debut, $lenght);
		return $return;
	} else {
		return '';
	}
}

// Base64 to file converter.
// only creat file, not add to DB
function base642file($file) {
	if ($file['bt_type'] == 'image') {
		$dossier = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'];
	} else {
		$dossier = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'];
	}
	if (creer_dossier($dossier, 0) === FALSE) return FALSE;
	$bin_data = base64_decode($file['bt_backup_img_base64']);
	$file_target_name = $dossier.'/'.$file['bt_filename'];
	if ($file_target_name != $dossier.'/' and FALSE !== file_put_contents($file_target_name, $bin_data) ) {
		if ($file['bt_checksum'] == sha1_file($file_target_name)) { // integrity test
			return TRUE;
		} else {
			unlink($file_target_name);
		}
	}
	return FALSE;
}

// file to base64 converter
function file2base64($source) {
	$bin_data = fread(fopen($source, "r"), filesize($source));
	$b64_data = base64_encode($bin_data);
	$b64_data_inline = preg_replace('#.{64}#', "$0\n", $b64_data);
	return $b64_data_inline;
}

// opens a file and put the XML content in it.
function creer_fich_xml() {
	$dossier_backup = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_backup'];
	$fichier = 'backup-'.date('Ymd').'-'.substr(md5(rand(100,999)),3,5).'.xml';
	$path = $dossier_backup.'/'.$fichier;

	$data = creer_xml(); // XML creation
	if (file_put_contents($path, $data) === FALSE) {
		echo $GLOBALS['lang']['err_file_write'];
		return FALSE;
	} else {
		chmod($path, 0666);
		echo '<form method="post" action="maintenance.php" class="bordered-formbloc"><div>'."\n";
			echo '<fieldset class="pref">';
			echo legend($GLOBALS['lang']['bak_succes_save'], 'legend-tic');
			echo '<p>'.$GLOBALS['lang']['bak_youcannowsave'].'</p>'."\n";
			echo '<p style="text-align: center;">';
			echo hidden_input('filetodelete',$path);
			echo '<a href="../'.$GLOBALS['dossier_backup'].'/'.$fichier.'">'.$fichier.'</a></p>'."\n";
			echo '</fieldset>'."\n";
			echo '<fieldset class="pref">';
			echo legend('&nbsp;', 'legend-question');
			echo '<p>';
			echo select_yes_no('supprimer_fichier_source', '0', $GLOBALS['lang']['bak_delete_source']);
			echo '</p>';
			echo '</fieldset>'."\n";
			echo '<input class="submit blue-square" type="submit" name="valider" value="'.$GLOBALS['lang']['valider'].'" />'."\n";
		echo '</div></form>'."\n";
		return TRUE;
	}
}

// invoqued in function above : create the whote XML data : articles + comms + files + …
function creer_xml() {
	$limite = (is_numeric($_POST['combien_articles'])) ? $_POST['combien_articles'] : '' ;
	$limite = ($limite == '-1') ? '' : "DESC LIMIT 0, $limite";

	$query = "SELECT * FROM articles ORDER BY bt_date $limite";
	$tableau = liste_elements($query, array(), 'articles');


	$data = '<bt_backup_database>'."\n\n";
	if (!empty($tableau)) { // pour chaque article…
		$data .= '<bt_backup_items>'."\n";
		foreach ($tableau as $key => $article) {
			$data .= '<bt_backup_item>'."\n";
			$data .= xml_billet($article);
			$query = "SELECT * FROM commentaires WHERE bt_article_id=? ORDER BY bt_id";
			$commentaires = liste_elements($query, array($article['bt_id']), 'commentaires');
			if (!empty($commentaires)) { // pour chaque commentaire par articles.
				foreach ($commentaires as $id => $content) {
					$comment = xml_comment($content);
					$data .= $comment."\n";
				}
			}
			$data .= '</bt_backup_item>'."\n\n";
		}
		$data .= '</bt_backup_items>'."\n\n";
	}
	// restaurer aussi les liens ?
	if (!empty($_POST['restore_linx']) and ($_POST['restore_linx'] == 1)) {
		$data .= '<bt_backup_linxs>'."\n";
		$nb_max_liens = (is_numeric($_POST['combien_liens']) and $_POST['combien_liens'] > 0) ? 'LIMIT 0, '.$_POST['combien_liens'] : ''; // nombre de liens (tous par défaut)

		$query = "SELECT * FROM links ORDER BY bt_id DESC $nb_max_liens";
		$list_liens = liste_elements($query, array(), 'links');

		foreach ($list_liens as $lien) {
			$data .= xml_link($lien)."\n";
		}
		$data .= '</bt_backup_linxs>'."\n";
	}

	// restaurer aussi les images sous forme de base64 ?
	if (!empty($_POST['restore_imgs']) and ($_POST['restore_imgs'] == 1)) {
		$data .= '<bt_backup_imgs>'."\n";
		$list_fichiers = liste_base_files('type', 'image', $_POST['combien_images']);
		foreach ($list_fichiers as $fichier) { // encode les images en base64 et les met dans le fichier
			if (is_file($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'].'/'.$fichier['bt_filename'])) {
				$fichier['bt_backup_img_base64'] = file2base64($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'].'/'.$fichier['bt_filename']);
				$data .= xml_file($fichier)."\n";
			}
		}
		$data .= '</bt_backup_imgs>'."\n";
	}
	$data .= "\n".'</bt_backup_database>';
	return $data;
}

/* génère le fichier HTML au format de favoris utilisés par tous les navigateurs. */
function creer_fich_html() {
	// nom du fichier de sortie
	$dossier_backup = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_backup'];
	$fichier = 'backup-links-'.date('Ymd').'-'.substr(md5(rand(100,999)),3,5).'.html';
	$path = $dossier_backup.'/'.$fichier;

	// génération du code HTML.
	$final_html = '<!DOCTYPE NETSCAPE-Bookmark-file-1>'."\n";
	$final_html .= '<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">'."\n";
	$final_html .= '<!--This is an automatically generated file.'."\n";
	$final_html .= 'It will be read and overwritten.'."\n";
	$final_html .= 'Do Not Edit! -->'."\n";
	$final_html .= '<TITLE>Blogotext links export '.date('Y-M-D').'</TITLE>'."\n";
	$final_html .= '<H1>Blogotext links export</H1>'."\n";

	$nb_max_liens = (is_numeric($_POST['nb']) and $_POST['nb'] > 0) ? 'LIMIT 0, '.$_POST['nb'] : ''; // nombre de liens (tous par défaut)
	$query = "SELECT * FROM links ORDER BY bt_id DESC $nb_max_liens";
	$list = liste_elements($query, array(), 'links');

	foreach ($list as $n => $link) {
		$dec = decode_id($link['bt_id']);
		$timestamp = mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee']); // H I S M D Y : wtf americans...

		$final_html .= '<DT><A HREF="'.$link['bt_link'].'" ADD_DATE="'.$timestamp.'" PRIVATE="'.abs(1-$link['bt_statut']).'" TAGS="'.$link['bt_tags'].'" AUTHOR="'.$link['bt_author'].'">'.$link['bt_title'].'</A>'."\n";
		$final_html .= '<DD>'.strip_tags($link['bt_wiki_content'])."\n";
	}

	// écriture du fichier
	if (file_put_contents($new_file, $final_html) === FALSE) {
		echo $GLOBALS['lang']['err_file_write'];
		return FALSE;
	} else {
		// affichage d’un formulaire indiquant que tout s’est bien passé, et demande si (une fois le fichier sauvé par l’user) il faut le supprimer du server.
		echo '<form method="post" action="maintenance.php" class="bordered-formbloc"><div>'."\n";
			echo '<fieldset class="pref">';
			echo legend($GLOBALS['lang']['bak_succes_save'], 'legend-tic');
			echo '<p>'.$GLOBALS['lang']['bak_youcannowsave'].'</p>'."\n";
			echo '<p style="text-align: center;">';
			echo hidden_input('filetodelete',$path);
			echo '<a href="../'.$GLOBALS['dossier_backup'].'/'.$fichier.'">'.$fichier.'</a></p>'."\n";
			echo '</fieldset>'."\n";
			echo '<fieldset class="pref">';
			echo legend('&nbsp;', 'legend-question');
			echo '<p>';
			echo select_yes_no('supprimer_fichier_source', '0', $GLOBALS['lang']['bak_delete_source']);
			echo '</p>';
			echo '</fieldset>'."\n";
			echo '<input class="submit blue-square" type="submit" name="valider" value="'.$GLOBALS['lang']['valider'].'" />'."\n";
		echo '</div></form>'."\n";
		return TRUE;
	}

}


// tranforms one article to XML data
function xml_billet($article) {
	$billet = '<bt_backup_article>'."\n";
	foreach ($GLOBALS['minimal_tags_arts'] as $bt_tag) {
		$billet .= "\t".'<'.$bt_tag.'>'.$article[$bt_tag].'</'.$bt_tag.'>'."\n";
	}
	$billet .= '</bt_backup_article>'."\n";
	return $billet;
}

// tranforms one comment to XML data
function xml_comment($commentaire) {
	$comment = '<bt_backup_comment>'."\n";
	foreach ($GLOBALS['minimal_tags_comm'] as $bt_tag) {
		$comment .= "\t".'<'.$bt_tag.'>'.$commentaire[$bt_tag].'</'.$bt_tag.'>'."\n";
	}
	$comment .= '</bt_backup_comment>'."\n";
	return $comment;
}

// tranforms one link to XML data
function xml_link($lien) {
	$link = '<bt_backup_link>'."\n";
	foreach ($GLOBALS['minimal_tags_link'] as $bt_tag) {
		$link .= "\t".'<'.$bt_tag.'>'.$lien[$bt_tag].'</'.$bt_tag.'>'."\n";
	}
	$link .= '</bt_backup_link>'."\n";
	return $link;
}

// tranforms one file to XML data
function xml_file($file) {
	$fichier = '<bt_backup_img>'."\n";
	foreach ($GLOBALS['minimal_tags_files'] as $bt_tag) {
		$fichier .= "\t".'<'.$bt_tag.'>'.$file[$bt_tag].'</'.$bt_tag.'>'."\n";
	}
	$fichier .= '</bt_backup_img>'."\n";
	return $fichier;
}

/*
 * liste une table (ex: les commentaires) et comparre avec un tableau de commentaires trouvées dans le XML
 * nous sort un tableau contenant uniquement les commentaires qui ne sont pas déjà dans la base.
 *
 * Utilisé dans importer_blogotext()
 */
function diff_trouve_base($table, $tableau_trouve) {
		// pour chaque élément : vérification qu’il ne se trouve pas déjà dans la base de données.
		// pour celà on liste les ID se trouvant dans la BDD
		// et on compare à la liste des élements trouvées dans le XML.
		try {
			$req = $GLOBALS['db_handle']->prepare('SELECT bt_id FROM '.$table);
			$req->execute();
			$tableau_base = array();
			while ($ligne = $req->fetch()) {
				$tableau_base[] = $ligne['bt_id'];
			}
		} catch (Exception $e) {
			die('Erreur diff_trouve_base avec les '.$table.' : '.$e->getMessage());
		}

		// fait le tableau avec les commentaires qui sont retenus pour l’insertion dans la base
		$tableau_retenus = array();
		$tableau_non_retenus = array();
		foreach ($tableau_trouve as $key => $element) {
			if (!in_array($element['bt_id'], $tableau_base)) {
				$tableau_retenus[] = $element; // si pas dans le tableau, on l’ajoute, sinon si dans tableau, on oubli.
			} else {
				$tableau_non_retenus[] = $element; // si pas dans le tableau, on l’ajoute, sinon si dans tableau, on oubli.
			}
		}
	return array('retenus' => $tableau_retenus, 'non_retenus' => $tableau_non_retenus);
}




/* IMPORTE UN FICHIER xml QUI EST AU FORMAT DE BLOGOTEXT */
function importer_blogotext($content_xml) {
		$restore_text = parse_xml_str($content_xml, 'bt_backup_items');
		$restore_imgs = parse_xml_str($content_xml, 'bt_backup_imgs');
		$restore_linx = parse_xml_str($content_xml, 'bt_backup_linxs');

		$nb_comments_to_store = 0;
		$nb_links_to_store = 0;
		$nb_articles_to_store = 0;
		$nb_files_to_store = 0;

		$tableau_fichier_trouves = array();
		/* PARSAGE ======================================
		 *
		 */
		// TRAITEMENT DES FICHIERS (dont les images)
		// ajout de ceux qui s’y trouvent déjà, la base est reconstituée dnas une autre section.
		if (!empty($restore_imgs)) {
			$images = explode('</bt_backup_img>', $restore_imgs); // la balise "<bt_backup_img>" est historiquement comme ça
			$nb_files = sizeof($images);
			if ($nb_files > 1) {
				for ($file = 0; $file < ($nb_files -1); $file++) { // parsage des fichiers.
					$data_content = preg_replace('#(.*)<bt_backup_article>(.+)</bt_backup_article>(.*)#is', "$2", $images[$file]);
					foreach ($GLOBALS['minimal_tags_files'] as $tag) {
						$tableau_fichier_trouves[$file][$tag] = parse_xml_str($data_content, $tag);
					}
				}
			}

			$tableau_nouveau = array();
			$liste_fichiers_id = array();

			foreach ($GLOBALS['liste_fichiers'] as $fichier) {
				$liste_fichiers_id[$fichier['bt_id']] = 1;
			}

			foreach ($tableau_fichier_trouves as $fichier) {
				if ( !array_key_exists($fichier['bt_id'], $liste_fichiers_id) ) {

					if (base642file($fichier) === TRUE) {
						if ($fichier['bt_type'] == 'image') {
							create_thumbnail($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'].'/'.$fichier['bt_filename']);
						}

						unset($fichier['bt_backup_img_base64']);
						$tableau_nouveau[$fichier['bt_id']] = $fichier;
					}
				}
			}

			$tableau_final = array_merge($GLOBALS['liste_fichiers'], $tableau_nouveau);
			$tableau_final = tri_selon_sous_cle($tableau_final, 'bt_id');

			file_put_contents($GLOBALS['fichier_liste_fichiers'], '<?php /* '.chunk_split(base64_encode(serialize($tableau_final))).' */');
			//print_r($tableau_final);

		}

		// TRAITEMENT DES ARTICLES ET DES COMMENTAIRES
		$c=0; // counter on comments array()
		$items = explode('</bt_backup_item>', $restore_text); // découpage en articles uniques.
		$nb_msg = sizeof($items);
		if ($nb_msg > 1) {

			// la totalité du fichier XML est placé dans ces deux ARRAY.
			$tableau_articles_trouves = array();
			$tableau_comments_trouves = array();

			for ($msg = 0; $msg < $nb_msg -1; $msg++) { // parsage des articles.
					$msg_content = preg_replace('#(.*)<bt_backup_article>(.+)</bt_backup_article>(.*)#is', "$2", $items[$msg]);

				// parsage du contenu XML, et placement dans le ARRAY (le cœur du precessus de parsage)
				foreach ($GLOBALS['minimal_tags_arts'] as $tag) {
					$tableau_articles_trouves[$msg][$tag] = parse_xml_str($msg_content, $tag);

					if ($tag == 'bt_date' and $tableau_articles_trouves[$msg][$tag] == '') {
						// compatibilité anciennes versions, qui n’ont pas de bt_date (on met le bt_id à la place.
						$tableau_articles_trouves[$msg][$tag] = $tableau_articles_trouves[$msg]['bt_id']; 
					}
					elseif ($tag == 'bt_type' and $tableau_articles_trouves[$msg][$tag] == '') {
						// compatibilité anciennes versions.
						$tableau_articles_trouves[$msg][$tag] = 'article'; 
					}
				}
				// pour chaque article : traitement des Commentaires
				if (preg_match("#<bt_backup_comment>#", $items[$msg])) {
					$comms = explode('<bt_backup_comment>', $items[$msg]);
					$nb_comms = sizeof($comms);
					for ($com = 1; $com < $nb_comms; $com++) {
						$comm_content = preg_replace('#</bt_backup_comment>#', '', $comms[$com]);
						// parsage du contenu XML
						foreach ($GLOBALS['minimal_tags_comm'] as $tag) {
							$tableau_comments_trouves[$c][$tag] = parse_xml_str($comm_content, $tag);
							if ($tag == 'bt_type' and $tableau_comments_trouves[$c][$tag] == '') {
								//compatibilité anciennes versions
								$tableau_comments_trouves[$c][$tag] = 'comment'; 
							}
						}
						$c++; // increase comment counter, for array index
					}
				}
			}

			/*
			 * STOCKAGE
			 */
			// compare la liste des commentaires du XML à ceux trouvés dans la BDD et ne conserve que ceux qui doivent être ajoutés dans la base.
			// FIXME : est-ce bien utile, ça ? Surtout pour les articles, car les articles présents mais mis à jours ne sont pas mis à jour lors de la ré-importation, du coup…
			$ta = diff_trouve_base('commentaires', $tableau_comments_trouves);
			$tableau_comments_retenus = $ta['retenus'];
			// génération de la requête SQL, et du tableau (celui qu’on met dans le $req->execute(<<<ICI>>>).
			// On utilise ici le SQLite pour de multiples insertions en même temps : BEAUCOUP plus rapide que de les faire une à une.
			$nb_comments_to_store = count($tableau_comments_retenus);
			if (!empty($tableau_comments_retenus)) {
				try {
					$GLOBALS['db_handle']->beginTransaction();

					foreach($tableau_comments_retenus as $com) {
						$query = 'INSERT INTO commentaires (bt_type, bt_id, bt_article_id, bt_content, bt_wiki_content, bt_author,
									bt_link, bt_webpage, bt_email, bt_subscribe, bt_statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
						$req = $GLOBALS['db_handle']->prepare($query);
						$req->execute(array($com['bt_type'], $com['bt_id'], $com['bt_article_id'], $com['bt_content'], $com['bt_wiki_content'],
													$com['bt_author'], $com['bt_link'], $com['bt_webpage'], $com['bt_email'], $com['bt_subscribe'], $com['bt_statut']));
					}
					$GLOBALS['db_handle']->commit();
				} catch (Exception $e) {
					$req->rollBack();
					die('Erreur 1150 : '.$e->getMessage());
				}

			}


			// compare la liste des articls du XML à ceux trouvés dans la BDD et ne conserve que ceux qui doivent être ajoutés dans la base.
			$a = diff_trouve_base('articles', $tableau_articles_trouves);
			$tableau_articles_retenus = $a['retenus'];

			// on libère un peu de mémoire (les array() son assez gros : plusieurs dizaines de Mo.
			unset($tableau_articles_trouves);

			// génération des requêtes SQL
			$nb_articles_to_store = count($tableau_articles_retenus);
			if (!empty($tableau_articles_retenus)) {

				try {
					$GLOBALS['db_handle']->beginTransaction();

					foreach($tableau_articles_retenus as $art) {
						$query = 'INSERT INTO articles (
												bt_type, bt_id, bt_date, bt_title, bt_abstract, bt_notes, bt_link, bt_content, bt_wiki_content,
												bt_categories, bt_keywords, bt_nb_comments, bt_allow_comments, bt_statut
											) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )';
						$req = $GLOBALS['db_handle']->prepare($query);
						$req->execute(array(
										$art['bt_type'], $art['bt_id'], $art['bt_date'], $art['bt_title'], $art['bt_abstract'], $art['bt_notes'], $art['bt_link'],
										$art['bt_content'], $art['bt_wiki_content'], $art['bt_categories'], $art['bt_keywords'], $art['bt_nb_comments'], $art['bt_allow_comments'], $art['bt_statut']
									));
					}
					$GLOBALS['db_handle']->commit();
				} catch (Exception $e) {
					$req->rollBack();
					die('Erreur 1530 : '.$e->getMessage());
				}

			}

			/*
			 * POUR CHAQUE ARTICLE : COMPTE LE NOMBRE DE COMMENTAIRES ASSOCIÉS
			*/

			if ($GLOBALS['sgdb'] == 'sqlite') {
				$query = "UPDATE articles SET bt_nb_comments = COALESCE((SELECT count(a.bt_id) FROM articles a INNER JOIN commentaires c ON (c.bt_article_id = a.bt_id) WHERE articles.bt_id = a.bt_id GROUP BY a.bt_id))";
			}
			if ($GLOBALS['sgdb'] == 'mysql') {
				$query = "UPDATE articles SET bt_nb_comments = COALESCE((SELECT count(articles.bt_id) FROM commentaires WHERE commentaires.bt_article_id = articles.bt_id))";
			}
			try {
				$req = $GLOBALS['db_handle']->prepare($query);
				$req->execute();
			}
			catch(Exception $e) {
				die('Erreur 11111: '.$e->getMessage());
			}
		}

		// TRAITEMENT DES LIENS
		$items = explode('</bt_backup_link>', $restore_linx); // découpage en articles uniques.
		$nb_links_to_store = 0;
		$nb_lnk = sizeof($items);
		if ($nb_lnk > 1) {
			// la totalité du fichier XML est placé dans un ARRAY.
			$tableau_liens_trouves = array();
			for ($msg = 0; $msg < $nb_lnk -1; $msg++) { // parsage des liens.
				$msg_content = preg_replace('#(.*)<bt_backup_linx>(.+)</bt_backup_linx>(.*)#is', "$2", $items[$msg]);
				// parsage du contenu XML, et placement dans le ARRAY (le cœur du parsage)
				foreach ($GLOBALS['minimal_tags_link'] as $tag) {
					$tableau_liens_trouves[$msg][$tag] = parse_xml_str($msg_content, $tag);
				}
			}

			// STOCKAGE, et comparaisons de la liste des liens à ceux trouvés dans la BDD et ne conserve que ceux qui doivent être ajoutés dans la base.
			$ta = diff_trouve_base('links', $tableau_liens_trouves);
			$tableau_liens_retenus = $ta['retenus'];

			if (!empty($tableau_liens_retenus)) {
				$nb_links_to_store = count($tableau_liens_retenus);
				try {
					$GLOBALS['db_handle']->beginTransaction();
					foreach($tableau_liens_retenus as $f) {
						$query = 'INSERT INTO links (bt_type, bt_id, bt_link, bt_content, bt_wiki_content, bt_statut, bt_author, bt_title, bt_tags ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ? ) ';
						$req = $GLOBALS['db_handle']->prepare($query);
						$req->execute(array($f['bt_type'], $f['bt_id'], $f['bt_link'], $f['bt_content'], $f['bt_wiki_content'], $f['bt_statut'], $f['bt_author'], $f['bt_title'], $f['bt_tags']));
					}
					$GLOBALS['db_handle']->commit();
				} catch (Exception $e) {
					$req->rollBack();
					die('Erreur 1123 : '.$e->getMessage());
				}
			}
		}

		$toprint = '<div>'."\n";
		$toprint .= '<ul>'."\n";

		$toprint .= "\t".'<li>'.nombre_articles($nb_msg-1).' '.$GLOBALS['lang']['et'].' '.nombre_commentaires($c).' '.$GLOBALS['lang']['trouve'].'</li>'."\n";
		$toprint .= "\t".'<li>'.nombre_liens($nb_lnk-1).' '.$GLOBALS['lang']['trouve'].'.</li>'."\n";
		$toprint .= "\t".'<li>'.nombre_articles($nb_articles_to_store).' '.$GLOBALS['lang']['ajoute'].'.</li>'."\n";
		$toprint .= "\t".'<li>'.nombre_commentaires($nb_comments_to_store).' '.$GLOBALS['lang']['ajoute'].'.</li>'."\n";
		$toprint .= "\t".'<li>'.nombre_liens($nb_links_to_store).' '.$GLOBALS['lang']['ajoute'].'.</li>'."\n";

		$toprint .= '</ul>'."\n";
		return $toprint;

}


/* AJOUTE TOUS LES DOSSIERS DU TALEAU $dossiers DANS UNE ARCHIVE ZIP */
function addFolder2zip($zip, $folder) {
	if ($handle = opendir($folder)) {
		while (FALSE !== ($entry = readdir($handle))) {
			if ($entry != "." and $entry != ".." and is_readable($folder.'/'.$entry)) {
				if (is_dir($folder.'/'.$entry)) {
					addFolder2zip($zip, $folder.'/'.$entry);
				} else {
					$zip->addFile($folder.'/'.$entry, preg_replace('#^\.\./#', '', $folder.'/'.$entry));
				}
			}
		}
		closedir($handle);
	}
}

function creer_fichier_zip($dossiers) {
	$dossier_backup = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_backup'];
	$zipfile = $dossier_backup.'/'.'archive_site-'.date('Ymd').'-'.substr(md5(rand(10,99)),3,5).'.zip';
	$zip = new ZipArchive;
	if ($zip->open($zipfile, ZipArchive::CREATE) === TRUE) {

		foreach ($dossiers as $dossier) {
			addFolder2zip($zip, $dossier);
		}
		$zip->close();

		if (is_file($zipfile)) {
			return $zipfile;
		}
	}
	else {
		return FALSE;
	}
}



/* CONVERTIE UN FICHIER AU FORMAT xml DE WORDPRESS AU FORMAT DE BLOGOTEXT (sans enregistrer le fichier BT) */
function convert_wp2bt($xml_content) {

	/* transforms some HTML elements to Blogotext's BBCode */
	function reverse_wiki($texte) {
		$tofind = array(
			'#<blockquote>(.*)</blockquote>#s',	// citation
			'#<code>(.*)</code>#s',					// code
			'#<a href="(.*)">(.*)</a>#',			// url
			'#<strong>(.*)</strong>#',				// strong
			'#<em>(.*)</em>#',						// italic
			'#<u>(.*)</u>#',							// souligne
		);
		$toreplace = array(
			'[quote]$1[/quote]',	// citation
			'[code]$1[/code]',	// code
			'[$2|$1]',				// a href
			'[b]$1[/b]',			// strong
			'[i]$1[/i]',			// italic
			'[u]$1[/u]',			// souligne
		);

		$length = sizeof($tofind);
		for ($i=0; $i < $length; $i++) {
			$texte = preg_replace($tofind["$i"], $toreplace["$i"], $texte);
		}
		return $texte;
	}

	/* Transforms Blogotext's BBCode tags to HTML elements. */
	function wiki($texte) {
		$texte = " ".$texte;
		$tofindc = array(
			'#\[quote\](.+?)\[/quote\]#s',// citation
			'#\[code\](.+?)\[/code\]#s',	// code
			'`\[([^[]+)\|([^[]+)\]`',		// a href
			'`\[b\](.*?)\[/b\]`s',			// strong
			'`\[i\](.*?)\[/i\]`s',			// italic
			'`\[u\](.*?)\[/u\]`s',			// souligne
		);
		$toreplacec = array(
			'<blockquote>$1</blockquote>',								// citation
			'<code>$1</code>',												// code
			'<a href="$2">$1</a>',											// a href
			'<span style="font-weight: bold;">$1</span>',			// strong
			'<span style="font-style: italic;">$1</span>',			// italic
			'<span style="text-decoration: underline;">$1</span>',// souligne
		);

		$toreplaceArrayLength = sizeof($tofindc);
		for ($i=0; $i < $toreplaceArrayLength; $i++) {
			$texte = preg_replace($tofindc["$i"], $toreplacec["$i"], $texte);
		}
		return $texte;
	}
		$final_XML = '<bt_backup_database>'."\n";
		$final_XML .= '<bt_backup_items>'."\n";

		// prend uniquement le "data" et non le blabla au haut du fichier.
		$restore_text = parse_xml_str($xml_content, 'channel');

		$items = explode('<item>', $restore_text);
		array_shift($items); // removes first element (Wordpress's configuration information, not articles nor comments data)
		$array_id_art = array(); // un tableau avec les ID au format BlogoText, WP permettant d’avoir des articles publiés à une même date, mais pas BT.
		$array_id_com = array(); // un tableau avec les ID des commentaires.

		foreach ($items as $item) {
			// tags
			preg_match_all("#<category.*><!\[CDATA\[(.+)\]\]></category>#", $item, $matches, PREG_PATTERN_ORDER); // get all tags individualy in an array
			$i_tags = implode($matches[1], ','); // make one string of the tags
			// keywords // get keywords in array
			preg_match("#<wp:meta_key>_aioseop_keywords</wp:meta_key>\s*<wp:meta_value><!\[CDATA\[(.+)\]\]></wp:meta_value>#sU", $item, $matches); 
			$i_keywords = (isset($matches[1])) ? $matches[1] : ''; // extract keywords
			// title
			$i_title = parse_xml_str($item,'title');
			// date/id  // convertit la date "rss" en timestamp puis au format 'YmdHis'
			$i_date = date('YmdHis', strtotime(parse_xml_str($item, 'pubDate'))); 
			// sees if another article has the same BT_ID, if yes, increments it and checks again, and so on.
			while (in_array($i_date, $array_id_art)) {
				$i_date += 1;
			}
			$array_id_art[] = $i_date; 

			// chapo
			$i_chapo = parse_xml_str($item, 'description'); // description d’un article
			// content
			$i_content = parse_xml_str($item, 'content:encoded'); // contenu
				$i_content = preg_replace('#^(<!\[CDATA\[)#', '', $i_content); // supprime le " <!\[CDATA\[ " au début
				$i_content = preg_replace('#(\]\]>$)#', '', $i_content); // supprime le " ]]> " à la fin
				$i_content = trim($i_content); // supprime les espaces au début et à la fin.
			// wiki content
			$i_wiki_content = $i_content;
			// statut article
			$i_status = ('publish' === parse_xml_str($item, 'wp:status')) ? '1' : '0'; // statut d’un article : brouillon ou publié
			// statut comments
			$i_comment_status = ('open' === parse_xml_str($item, 'wp:comment_status')) ? '1' : '0'; // statut d’un article : brouillon ou publié


			$final_XML .= '<bt_backup_item>'."\n"; // article + comment
				$final_XML .= '<bt_backup_article>'."\n"; // article
					$final_XML .= "\t".'<bt_type>article</bt_type>'."\n";
					$final_XML .= "\t".'<bt_id>'.$i_date.'</bt_id>'."\n";
					$final_XML .= "\t".'<bt_title>'.$i_title.'</bt_title>'."\n";
					$final_XML .= "\t".'<bt_abstract>'.$i_chapo.'</bt_abstract>'."\n";
					$final_XML .= "\t".'<bt_notes></bt_notes>'."\n";
					$final_XML .= "\t".'<bt_link></bt_link>'."\n";
					$final_XML .= "\t".'<bt_content>'."\n".nl2br($i_content).'</bt_content>'."\n";
					$final_XML .= "\t".'<bt_wiki_content>'.$i_content.'</bt_wiki_content>'."\n";
					$final_XML .= "\t".'<bt_categories>'.$i_tags.'</bt_categories>'."\n";
					$final_XML .= "\t".'<bt_keywords>'.$i_keywords.'</bt_keywords>'."\n";
					$final_XML .= "\t".'<bt_allow_comments>'.$i_comment_status.'</bt_allow_comments>'."\n";
					$final_XML .= "\t".'<bt_statut>'.$i_status.'</bt_statut>'."\n";
				$final_XML .= '</bt_backup_article>'."\n"; // end article

				// dans chaque bloc <item> se trouvent aussi les commentaires, qu’on va traiter :
				if (preg_match("#<wp:comment>#", $item)) {
					$comms = explode('<wp:comment>', $item);
					$nb_comms = sizeof($comms);
					for ($com = 1; $com < $nb_comms; $com++) {
						$comms[$com] = preg_replace('#</wp:comment>#', '', $comms[$com]);

						// auteur
						$j_author = preg_replace('#(\]\]>$)#', '', preg_replace('#^(<!\[CDATA\[)#', '', parse_xml_str($comms[$com], 'wp:comment_author'))); 
						// contenu
						$j_wiki_content = preg_replace('#(\]\]>$)#','', preg_replace('#^(<!\[CDATA\[)#','', parse_xml_str($comms[$com], 'wp:comment_content'))); 
						$j_wiki_content = strip_tags(reverse_wiki($j_wiki_content)); 
						$j_content = wiki($j_wiki_content);
						// statut
						$j_statut = parse_xml_str($comms[$com], 'wp:comment_approved');
						// email
						$j_email = parse_xml_str($comms[$com], 'wp:comment_author_email');
						// webpage : site/lien
						$j_webpage = parse_xml_str($comms[$com], 'wp:comment_author_url');
						// date/id
						$j_date = date('YmdHis', strtotime(parse_xml_str($comms[$com], 'wp:comment_date'))); //converti la date au format 'YmdHis'
						// sees if another comment has the same ID
						while (in_array($j_date, $array_id_com)) {
							$j_date += 1;
						}
						$array_id_com[] = $j_date; 

						// création du XML
						$final_XML .= '<bt_backup_comment>'."\n";
							$final_XML .= "\t".'<bt_type>comment</bt_type>'."\n";
							$final_XML .= "\t".'<bt_id>'.$j_date.'</bt_id>'."\n";
							$final_XML .= "\t".'<bt_article_id>'.$i_date.'</bt_article_id>'."\n";
							$final_XML .= "\t".'<bt_link></bt_link>'."\n";
							$final_XML .= "\t".'<bt_content><p>'.nl2br($j_content).'</p></bt_content>'."\n";
							$final_XML .= "\t".'<bt_wiki_content>'.$j_wiki_content.'</bt_wiki_content>'."\n";
							$final_XML .= "\t".'<bt_author>'.$j_author.'</bt_author>'."\n";
							$final_XML .= "\t".'<bt_webpage>'.$j_webpage.'</bt_webpage>'."\n";
							$final_XML .= "\t".'<bt_email>'.$j_email.'</bt_email>'."\n";
							$final_XML .= "\t".'<bt_subscribe>0</bt_subscribe>'."\n";
							$final_XML .= "\t".'<bt_statut>'.$j_statut.'</bt_statut>'."\n";
						$final_XML .= '</bt_backup_comment>'."\n";
					}
				}
			$final_XML .= '</bt_backup_item>'."\n\n";
		}
		$final_XML .= '</bt_backup_items>'."\n";
		$final_XML .= '</bt_backup_database>';
		return $final_XML;
}

// based on the importFile() of Shaarli program, by Sebsauvage http://sebsauvage.net/wiki/doku.php?id=php:shaarli
function convert_netscape2bt($content) {
	$final_xml = '';
	$import_count=0;
	if (strcmp(substr($content, 0, strlen('<!DOCTYPE NETSCAPE-Bookmark-file-1>')), '<!DOCTYPE NETSCAPE-Bookmark-file-1>') === 0) { // Netscape bookmark file (Firefox).
		// This is a standard Netscape-style bookmark file.
		// This format is supported by all browsers (except IE, of course), also delicious, diigo and others.
		$tab1_DT = explode('<DT>',$content);
		foreach ($tab1_DT as $html) {
			$link = array('linkdate' => '', 'title' => '', 'auteur' => $GLOBALS['auteur'], 'url' => '', 'description' => '', 'tags' => '', 'statut' => 1);
			$d = explode('<DD>',$html);

			if (strcmp(substr($d[0], 0, strlen('<A ')), '<A ') === 0) {
				$link['description'] = (isset($d[1]) ? html_entity_decode(trim($d[1]),ENT_QUOTES,'UTF-8') : '');  // Get description (optional)
				preg_match('!<A .*?>(.*?)</A>!i',$d[0],$matches); $link['title'] = (isset($matches[1]) ? trim($matches[1]) : '');  // Get title
				$link['title'] = html_entity_decode($link['title'],ENT_QUOTES,'UTF-8');
				preg_match_all('! ([A-Z_]+)=\"(.*?)"!i',$html,$matches,PREG_SET_ORDER); // Get all other attributes
				$raw_add_date=0;
				foreach($matches as $m) {
					$attr = $m[1];
					$value = $m[2];
	
					if ($attr == 'HREF') {
						$link['url'] = html_entity_decode($value,ENT_QUOTES,'UTF-8');
					} elseif ($attr=='ADD_DATE') {
						$raw_add_date=intval($value);
					} elseif ($attr=='AUTHOR') {
						$link['auteur']=$value;
					} elseif ($attr=='PRIVATE') {
						$link['statut'] = ($value == '1') ? '0' : '1'; // value=1 =>> statut=0 (it’s reversed)
					} elseif ($attr=='TAGS') {
						//$link['tags'] = html_entity_decode(str_replace(',',' ',$value),ENT_QUOTES,'UTF-8');
						$link['tags'] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
					}
				}
				if ($link['url'] != '') {
					$raw_add_date = (empty($raw_add_date)) ? time() : $raw_add_date; // In case of shitty bookmark file with no ADD_DATE

					$link['linkdate'] = date('YmdHis',$raw_add_date); // converts date to YmdHis format

					$import_count++;
					// each file is put in BT XML format (I know it’s silly, but easier for me :p)
					$final_xml .= '<bt_backup_link>'."\n";
						$final_xml .= '<bt_type>link</bt_type>'."\n";
						$final_xml .= '<bt_id>'.$link['linkdate'].'</bt_id>'."\n";
						$final_xml .= '<bt_link>'.$link['url'].'</bt_link>'."\n";
						$final_xml .= '<bt_content>'.$link['description'].'</bt_content>'."\n";
						$final_xml .= '<bt_wiki_content>'.$link['description'].'</bt_wiki_content>'."\n";
						$final_xml .= '<bt_statut>'.$link['statut'].'</bt_statut>'."\n";
						$final_xml .= '<bt_author>'.$link['auteur'].'</bt_author>'."\n";
						$final_xml .= '<bt_title>'.$link['title'].'</bt_title>'."\n";
						$final_xml .= '<bt_tags>'.$link['tags'].'</bt_tags>'."\n";
					$final_xml .= '</bt_backup_link>'."\n";

				}
			}
		}
	}
	$final_xml = '<bt_backup_linxs>'."\n".$final_xml."\n".'</bt_backup_linxs>';
	return $final_xml;
}



function is_file_error() {
	$erreurs = array();

	if (!is_dir($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'])) {
		$erreurs[] = $GLOBALS['lang']['prob_no_img_folder'];
	} elseif (!is_readable($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images']) or !is_writable($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'])) {
		$erreurs[] = $GLOBALS['lang']['prob_img_folder_chmod'].' (chmod : '.get_literal_chmod($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images']).')';
	}
	if (!is_dir($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_db'])) {
		$erreurs[] = $GLOBALS['lang']['prob_no_db_folder'];
	} elseif (!is_readable($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_db']) or !is_writable($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_db'])) {
		$erreurs[] = $GLOBALS['lang']['prob_db_folder_chmod'].' (chmod : '.get_literal_chmod($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_db']).')';
	}
	if (!is_writable('../config/prefs.php')) {
		$erreurs[] = $GLOBALS['lang']['prob_pref_file_chmod'].' (chmod : '.get_literal_chmod('../config/prefs.php').')';
	}
	if (!is_writable('../config/user.php')) {
		$erreurs[] = $GLOBALS['lang']['prob_user_file_chmod'].' (chmod : '.get_literal_chmod('../config/user.php').')';
	}
	return $erreurs;
}


// GET DO : rien n'est spécifié, on demande quoi faire.
if (!isset($_GET['quefaire'])) {
	// 1 : choix de ce qu'on va faire dans backup
	echo '<form method="get" action="maintenance.php" class="bordered-formbloc">';
		echo '<fieldset class="pref valid-center">';
		echo legend($GLOBALS['lang']['legend_what_doyouwant'], 'legend-backup');
		echo form_radio('quefaire', 'sauvegarde', 'sauvegarde', $GLOBALS['lang']['bak_save2xml']);
		echo form_radio('quefaire', 'restore', 'restore', $GLOBALS['lang']['bak_restorefromxml']);
		echo form_radio('quefaire', 'optimise', 'optimise', $GLOBALS['lang']['legend_what_doyouwant_optim']);
		echo form_radio('quefaire', 'rien', 'rien', $GLOBALS['lang']['bak_nothing'], TRUE);
		echo '</fieldset>';

		echo '<p><input class="submit blue-square" type="submit" value="'.$GLOBALS['lang']['valider'].'" /></p>'."\n";
	echo '</form>'."\n";

	// 2 : infos
	$erreurs = is_file_error();
	if (!empty($erreurs)) {
		echo '<div class="bordered-formbloc"><fieldset class="pref valid-center">';
			echo legend($GLOBALS['lang']['erreurs'], 'legend-tic');
			echo erreurs($erreurs);
		echo '</fieldset></div>'."\n";
	}

}

// GET DO : ok.
/* --------------------------------------------------------------------  SYSTEME BACKUPAGE ------------------------------------- */
else {

	// S A U V E G A R D E
	if (($_GET['quefaire'] == 'sauvegarde')) {
		// création du dossier de sauvegarde
		creer_dossier_sauv();
		// on demande le type de sauvegarde
		if (!isset($_GET['type'])) {

			echo '<form method="get" action="maintenance.php" class="bordered-formbloc"><div>'."\n";
			echo '<fieldset class="pref">'."\n";
			  echo legend($GLOBALS['lang']['bak_number_articles'], 'legend-question');

				echo form_radio('type', 'xml',      'xml',      $GLOBALS['lang']['bak_output_xml'], TRUE);
				echo form_radio('type', 'netscape', 'netscape', $GLOBALS['lang']['bak_output_netscape']);
				echo form_radio('type', 'zip',      'zip',      $GLOBALS['lang']['bak_output_zip']);

				echo hidden_input('quefaire', 'sauvegarde');
				echo '<input class="submit blue-square" type="submit" value="'.$GLOBALS['lang']['valider'].'" />'."\n";
			echo '</fieldset>'."\n";
			echo '</div></form>'."\n";

		}
		// le type de sauvegarde est déjà donné
		else {
			// sauvegarde XML
			if ($_GET['type'] == 'xml') {
				// on demande les informations : nombre de liens/articles/fichiers à inclure...
				if (!isset($_POST['create'])) {
						echo '<form method="post" action="maintenance.php?quefaire=sauvegarde&amp;type=xml" class="bordered-formbloc"><div>'."\n";
						// articles + commentaires
						echo '<fieldset class="pref">'."\n";
						  echo legend($GLOBALS['lang']['bak_number_articles'], 'legend-question');
						  $nbs= array('1'=>'1', '2'=>'2', '5'=>'5', '10'=>'10', '20'=>'20', '50'=>'50', '100'=>'100', '-1'=> $GLOBALS['lang']['pref_all']);
						  echo '<p>'.form_select('combien_articles', $nbs, '-1',$GLOBALS['lang']['bak_combien_articles']).'</p>';
						echo '</fieldset>'."\n";
						// images et fichiers
						echo '<fieldset class="pref">'."\n";
						  echo legend(ucfirst($GLOBALS['lang']['label_images']), 'legend-question');
						  echo select_yes_no('restore_imgs', 1, $GLOBALS['lang']['bak_imgs_too']);
						  $nbs= array('5'=>'5', '10'=>'10', '20'=>'20', '30'=>'30', '50'=>'50', '100'=>'100', '-1'=> $GLOBALS['lang']['pref_all']);
						  echo '<p>'.form_select('combien_images', $nbs, '1',$GLOBALS['lang']['bak_combien_images']).'</p>';
						echo '</fieldset>'."\n";
						// liens
						echo '<fieldset class="pref">'."\n";
						  echo legend(ucfirst($GLOBALS['lang']['label_links']), 'legend-question');
						  echo select_yes_no('restore_linx', 1, $GLOBALS['lang']['bak_linx_too']);
						  $nbs= array('10'=>'10', '20'=>'20', '50'=>'50', '100'=>'100', '300'=>'300', '500'=>'500', '-1'=> $GLOBALS['lang']['pref_all']);
						  echo '<p>'.form_select('combien_liens', $nbs, '1',$GLOBALS['lang']['bak_combien_linx']).'</p>';
						echo '</fieldset>'."\n";
						// bouton valider
						// echo hidden_input('create','');
						echo '<input class="submit blue-square" type="submit" name="create" value="'.$GLOBALS['lang']['valider'].'" />'."\n";
						echo '</div></form>'."\n";
				}
				// on génère le fichier
				else {
					creer_fich_xml();
				}

			}
			// sauvegarde des liens au format HTML de netscape
			elseif ($_GET['type'] == 'netscape') {

				if (!(isset($_POST['nb']) and is_numeric($_POST['nb']) ) ) {
						echo '<form method="post" action="maintenance.php?quefaire=sauvegarde&amp;type=netscape" class="bordered-formbloc"><div>'."\n";
						// nombre de liens ?
						echo '<fieldset class="pref">'."\n";
						  echo legend(ucfirst($GLOBALS['lang']['label_links']), 'legend-question');
						  $nbs = array('10'=>'10', '20'=>'20', '50'=>'50', '100'=>'100', '200'=>'200', '500'=>'500', '1000'=>'1000', '2000'=>'2000', '-1'=> $GLOBALS['lang']['pref_all']);
						  echo '<p>'.form_select('nb', $nbs, '1', $GLOBALS['lang']['bak_combien_linx']).'</p>';
						echo '</fieldset>'."\n";
						// bouton valider
						echo '<input class="submit blue-square" type="submit" value="'.$GLOBALS['lang']['valider'].'" />'."\n";
						echo '</div></form>'."\n";
				}
				// on génère le fichier
				else {
					creer_fich_html();
				}

			}

			// sauvegarde des fichiers dans une archive ZIP
			elseif ($_GET['type'] == 'zip') {
				// on demande ce qu’on met dans le fichier
				if (!isset($_POST['create'])) {

					echo '<form method="post" action="maintenance.php?quefaire=sauvegarde&amp;type=zip" class="bordered-formbloc"><div>'."\n";
					echo "\t".'<fieldset class="pref">'."\n";
					echo legend(ucfirst('Que mettre dans le fichier ZIP ?'), 'legend-question');
					// articles + commentaires + liens
					echo "\t\t".'<p>'.select_yes_no('db_sql', 1, $GLOBALS['lang']['bak_incl_sql']).'</p>';
					echo "\t\t".'<p>'.select_yes_no('imgs', 1, $GLOBALS['lang']['bak_incl_imgs']).'</p>';
					echo "\t\t".'<p>'.select_yes_no('files', 1, $GLOBALS['lang']['bak_incl_files']).'</p>';

					// bouton valider
					echo "\t\t".'<input class="submit blue-square" name="create" type="submit" value="'.$GLOBALS['lang']['valider'].'" />'."\n";
					echo "\t".'</fieldset>'."\n";
					echo '</div></form>'."\n";

				}
				
				// on génère le fichier
				else {
					$dossiers = array();
					$text = '';
					if ($_POST['db_sql'] == 1 and $GLOBALS['sgdb'] == 'sqlite') {
						$dossiers[] = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_db'];
						$text .= 'dossier SQL, ';
					}
					if ($_POST['imgs'] == 1) {
						$dossiers[] = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'];
						$dossiers[] = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'];
						$text .= 'dossier images &amp; dossiers fichiers, ';
					}
					if ($_POST['files'] == 1) {
						$dossiers[] = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_themes'];
						$dossiers[] = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_config'];
						$text .= 'dossier thèmes &amp; dossiers config.';
					}
					
					echo '<form method="post" action="maintenance.php" class="bordered-formbloc"><div>'."\n";
					echo "\t".'<fieldset class="pref">'."\n";
					if (($fichier = creer_fichier_zip($dossiers)) !== FALSE) {
						echo "\t\t".'<p>'.$text.'</p>';
						echo "\t\t".'<p><a href="'.$fichier.'">'.$GLOBALS['lang']['bak_dl_fichier_zip'].' ('.taille_formate(filesize($fichier)).')</a></p>';

					} else {
						echo '<p>ERROR during file creating or empty file (maintenace).</p>';
					}
					echo "\t\t".'<input class="submit blue-square" name="create" type="submit" value="'.$GLOBALS['lang']['valider'].'" />'."\n";
					echo "\t".'</fieldset>'."\n";
					echo '</div></form>'."\n";
				}
			}

			// sinon rien, retour en arrière.
			else {
				redirection($_SERVER['PHP_SELF'].'?quefaire=sauvegarde');
			}
		}
	}

	// R E S T A U R A T I O N
	elseif (($_GET['quefaire'] == 'restore')) {

		// fichier present : on l'analyse, et on ne reaffiche pas le formulaire d'envoie
		if (isset($_FILES['xml_file'])) {
			switch ($_FILES['xml_file']['error']) {
				case 3: $erreurs[] = $GLOBALS['lang']['img_phperr_partial']; break;
				case 4: $erreurs[] = $GLOBALS['lang']['img_phperr_nofile']; break;
				case 6: $erreurs[] = $GLOBALS['lang']['img_phperr_tempfolder']; break;
				case 7: $erreurs[] = $GLOBALS['lang']['img_phperr_DiskWrite']; break;
			}
			// si erreurs
			if (!empty($erreurs)) {
				echo '<div id="erreurs"><strong>'.$GLOBALS['lang']['erreurs'].'</strong> :<ul>'."\n";
				foreach($erreurs as $erreur) {
					echo '<li>'.$erreur.'</li>'."\n";
				}
				echo '</ul></div>'."\n";
			}
			// si pas erreurs, on enregistre chaque entrée (art. ou comm. ou image) du fichier, et on affiche des remarques à l'écran
			else {
				$content_xml = file_get_contents($_FILES['xml_file']['tmp_name']);

				// ON A DONNÉ UN FICHIER BLOGOTEXT
				if ($_POST['format'] == 'blogotext') {
					$content_xml = str_replace('bt_status>', 'bt_statut>', $content_xml); // old BT files compatibility.
					$content_xml = str_replace('bt_backup_img_hash>', 'bt_checksum>', $content_xml); // old BT xml files update…
					$content_xml = str_replace('bt_backup_img_name>', 'bt_filename>', $content_xml); // old BT xml files update…

					$liste_importe = importer_blogotext($content_xml);
				}

				// ON A DONNÉ UN FICHIER WORDPRESS et il faut le convertir avant...
				elseif ($_POST['format'] == 'wordpress') {
					$new_file = convert_wp2bt($content_xml); // on convertit en fichier BT
					$liste_importe = importer_blogotext($new_file); // on importe le fichier BT obtenu
				}

				// ON A DONNÉ UN FICHIER SHAARLI (qui est aussi - en principe - le format d’export utilisé par tous les navigateurs)
				elseif ($_POST['format'] == 'shaarli') {
					$new_file = convert_netscape2bt($content_xml);
					$liste_importe = importer_blogotext($new_file);
				}

				else {
					$liste_importe = '';
				}

				echo '<form action="maintenance.php" method="post" enctype="multipart/form-data" class="bordered-formbloc">'."\n";
					echo '<fieldset class="pref valid-center">';
						echo legend($GLOBALS['lang']['bak_restor_done'], 'legend-tic');
						echo '<p>';
						echo $GLOBALS['lang']['bak_restor_done_mesage'];
						echo '</p>'."\n".'<p>'."\n";
						echo $liste_importe;
						echo '<input class="submit blue-square" type="submit" name="valider" value="'.$GLOBALS['lang']['valider'].'" />'."\n";
						echo '</p>'."\n";
					echo '</fieldset>'."\n";
				echo '</form>'."\n";

			}
		}
		// page restore : si aucun fichier spécié, on en demande un
		if ( !isset($_FILES['xml_file']) ) {
			echo '<form action="maintenance.php?quefaire=restore" method="post" enctype="multipart/form-data" class="bordered-formbloc"><fieldset class="pref">';
			echo legend($GLOBALS['lang']['bak_choosefile'], 'legend-user');
			echo form_radio('format', 'blogotext', 'blogotext', $GLOBALS['lang']['bak_format_blogotext'], TRUE);
			echo form_radio('format', 'wordpress', 'wordpress', $GLOBALS['lang']['bak_format_wordpress']);
			echo form_radio('format', 'shaarli', 'shaarli', $GLOBALS['lang']['bak_format_shaarli']); // soon...

			echo '<p>'."\n";
			echo '<input type="file" name="xml_file" id="xml_file" /><br />'."\n";

			echo '<input class="submit blue-square" type="submit" name="upload" value="'.$GLOBALS['lang']['img_upload'].'" />'."\n";
			echo '</p>'."\n";
			echo '</fieldset>'."\n";
			echo '</form>'."\n";
		}
	}

	// O P T I M I S A T I O N
	elseif (($_GET['quefaire'] == 'optimise')) {

		// effectue ce qui est demandé
		if (isset($_POST['opt'])) {
			echo '<form method="get" action="maintenance.php" class="bordered-formbloc"><div>'."\n";
			echo '<fieldset class="pref">'."\n";

			// recréation des miniatures
			if ($_POST['miniature'] == 1) {
				$images_sur_disque = scandir($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images']);
				// supprime "." et ".."
				unset($images_sur_disque[0], $images_sur_disque[1]);
				foreach ($images_sur_disque as $i => $image) {
					// ignore les images qui sont manifestement déjà des miniatures.
					if (!preg_match('#-thb\.jpg$#', $image) and ! ($image == 'index.html') ) {// les miniatures terminent par -thb.jpg
						// crée une miniature de l’image
						create_thumbnail($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'].'/'.$image);
					}
				}
				echo '<p>'.$GLOBALS['lang']['bak_opti_miniature'].' : OK.'.'<p>'."\n";
			}

			// recomptage des commentaires
			if ($_POST['recount']) {
				if ($GLOBALS['sgdb'] == 'sqlite') {
					$query = "UPDATE articles SET bt_nb_comments = COALESCE((SELECT count(a.bt_id) FROM articles a INNER JOIN commentaires c ON (c.bt_article_id = a.bt_id) WHERE articles.bt_id = a.bt_id GROUP BY a.bt_id), 0)";
				}
				if ($GLOBALS['sgdb'] == 'mysql') {
					$query = "UPDATE articles SET bt_nb_comments = COALESCE((SELECT count(articles.bt_id) FROM commentaires WHERE commentaires.bt_article_id = articles.bt_id), 0)";
				}
				try {
					$req = $GLOBALS['db_handle']->prepare($query);
					$req->execute();
				}
				catch(Exception $e) {
					die('Erreur 11111: '.$e->getMessage());
				}

				echo '<p>'.$GLOBALS['lang']['bak_opti_recountcomm'].' : OK.'.'<p>'."\n";
			}

			// reconstruction de la BDD (ceci la défragmente, etc). Également reconstruit la BDD des fichiers
			if ($_POST['vacuum'] == 1) {
				// SQLite
				try {
					$req = $GLOBALS['db_handle']->prepare('VACUUM');
					$req->execute();
				} catch (Exception $e) {
					die('Erreur vacuum (Maintenance) : '.$e->getMessage());
				}
				echo '<p>Vacuum : OK.'.'<p>'."\n";

				// Fichiers
				// vérification que les fichiers dans la base sont bien sur le disque.
				$new_table = array();
				$liste_files = array();
				$liste_images = array();
				foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
					if ($file['bt_type'] == 'image') {
						$dossier = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'];
					} else {
						$dossier = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'];
					}

					if (is_file($dossier.'/'.$file['bt_filename'])) {
						if ($file['bt_type'] == 'image') {
							$liste_images[] = $file['bt_filename'];
						} else {
							$liste_files[] = $file['bt_filename'];
						}

						$new_table[$id] = $file;
						$new_table[$id]['bt_checksum'] = sha1_file($dossier.'/'.$file['bt_filename']);
					}
				}

				// vérification que les fichiers sur le disque sont bien dans la base.
				// s’ils n’y sont pas, on l’ajoute en prennant comme bt_id la date de création du fichier


					// Cas des images
					$images_sur_disque = scandir($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images']);
					// supprime les entrés "." et ".." de la liste.
					unset($images_sur_disque[0], $images_sur_disque[1]);
					// supprime les miniatures de la liste...
					foreach ($images_sur_disque as $i => $image) {
						if ( (preg_match('#-thb\.jpg$#', $image)) or ($image == 'index.html') ) {
							unset($images_sur_disque[$i]);
						}
					}

					// Cas des fichiers 
					$fichiers_sur_disque = scandir($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers']);
					// supprime les entrés "." et ".." de la liste.
					unset($fichiers_sur_disque[0], $fichiers_sur_disque[1]);

					foreach ($images_sur_disque as $i => $image) {
						// si l’image du disque n’est pas dans la base, on l’ajoute.
						if (!in_array($image, $liste_images)) {
							$filepath = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'].'/'.$image;
							$time = filemtime($filepath);
							$id = date('YmdHis', $time);
							// vérifie que l’ID ne se trouve pas déjà dans le tableau. Sinon, modifie la date (en allant dans le passé)
							while (array_key_exists($id, $new_table)) {
								$time--;
								$id = date('YmdHis', $time);
							}
							$new_img = array(
								'bt_id' => $id,
								'bt_type' => 'image',
								'bt_fileext' => strtolower(pathinfo($filepath, PATHINFO_EXTENSION)),
								'bt_filesize' => filesize($filepath),
								'bt_filename' => $image,
								'bt_content' => '',
								'bt_wiki_content' => '',
								'bt_dossier' => 'default',
								'bt_checksum' => sha1_file($filepath),
								'bt_statut' => 0,
							);
							list($new_img['bt_dim_w'], $new_img['bt_dim_h']) = getimagesize($dossier.'/'.$image);
							// l’ajoute au tableau
							$new_table[$id] = $new_img;
							// crée une miniature de l’image
							create_thumbnail($filepath);
						}
					}

					// update for 2.0.2.0 => 2.0.2.1
					foreach ($new_table as $i => $fichier) {
						if ($fichier['bt_type'] == 'image') {
							list($new_table[$i]['bt_dim_w'], $new_table[$i]['bt_dim_h']) = getimagesize($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'].'/'.$fichier['bt_filename']);
						}
					}

					foreach ($fichiers_sur_disque as $i => $fichier) {
						// si le fichier du disque n’est pas dans la base, on l’ajoute.
						if (!in_array($fichier, $liste_files)) {
							$time = filemtime($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'].'/'.$fichier);
							$id = date('YmdHis', $time);
							// vérifie que l’ID ne se trouve pas déjà dans le tableau. Sinon, modifie la date (en allant dans le passé)
							while (array_key_exists($id, $new_table)) {
								$time--;
								$id = date('YmdHis', $time);
							}

							$new_file = array(
								'bt_id' => $id,
								'bt_fileext' => strtolower(pathinfo($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'].'/'.$fichier, PATHINFO_EXTENSION)),
								'bt_type' => detection_type_fichier(strtolower(pathinfo($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'].'/'.$fichier, PATHINFO_EXTENSION))),
								'bt_filesize' => filesize($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'].'/'.$fichier),
								'bt_filename' => $fichier,
								'bt_content' => '',
								'bt_wiki_content' => '',
								'bt_checksum' => sha1_file($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'].'/'.$fichier),
								'bt_statut' => 0,
							);
							// l’ajoute au tableau
							$new_table[$id] = $new_file;
						}
					}

				// tri le tableau fusionné selon les bt_id (selon une des clés d'un sous tableau).
				$new_table = tri_selon_sous_cle($new_table, 'bt_id');

				// finalement enregistre la liste des fichiers.
				file_put_contents($GLOBALS['fichier_liste_fichiers'], '<?php /* '.chunk_split(base64_encode(serialize($new_table))).' */');
				echo '<p>'.$GLOBALS['lang']['bak_opti_vacuum'].' : OK.'.'<p>'."\n";
			}

			echo '<input class="submit blue-square" type="submit" value="'.$GLOBALS['lang']['valider'].'" />'."\n";
			echo '</fieldset>'."\n";
			echo '</div></form>'."\n";
		}

		// demande ce qu’il faut optimiser
		else {
			echo '<form action="maintenance.php?quefaire=optimise" method="post" enctype="multipart/form-data" class="bordered-formbloc"><fieldset class="pref">';
			echo legend($GLOBALS['lang']['bak_choosefile'], 'legend-user');
			echo '<p>'.select_yes_no('miniature', 1, $GLOBALS['lang']['bak_opti_miniature']).'</p>'."\n";
			if ($GLOBALS['sgdb'] == 'sqlite') {
				echo '<p>'.select_yes_no('vacuum', 1, $GLOBALS['lang']['bak_opti_vacuum']).'</p>'."\n";
			} else {
				echo '<p>'.hidden_input('vacuum', 0).'</p>'."\n";
			}
			echo '<p>'.select_yes_no('recount', 1, $GLOBALS['lang']['bak_opti_recountcomm']).'</p>'."\n";
			echo '<p>'."\n";
			echo '<input class="submit blue-square" type="submit" name="opt" value="'.$GLOBALS['lang']['valider'].'" />'."\n";
			echo '</p>'."\n";
			echo '</fieldset>'."\n";
			echo '</form>'."\n";
		}
	}

	//  N E    R I E N    F A I R E
	else {
		echo '<pre style="text-align:center; font-family: monospace;">'.gzinflate(base64_decode('rVJBDoAwCLvzij3Xswcf6EvURO2KRWsywmUdbaAQ7Yx1mT7zqg2mzEaCHn+pSmaUiBrzKIIroLAT
iewamEQYbin4N7gIL4U/2tOE15n6Mvinj6WbxbNCdzdGpN6r2meF357kS/NFzXOtBIVIvSGF7qQN')).'
<div style="border: 2px black inset; border-radius: 4px; display:inline-block; margin: 0 auto; text-align: left;"><div style="border: 1px white solid; border-radius: 4px;"><div style="border: 1px black inset; padding: 3px 5px; letter-spacing: 2px;">Hello, and welcome in
the world of Pokemon!
</div></div></div>

</pre>';
	}

}


footer('', $GLOBALS['begin']);

