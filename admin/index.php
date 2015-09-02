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

if ( !file_exists('../config/user.php') || !file_exists('../config/prefs.php') ) {
	header('Location: install.php');
	exit;
}

$begin = microtime(TRUE);
$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();

// open bases
$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);
$GLOBALS['liste_fichiers'] = open_serialzd_file($GLOBALS['fichier_liste_fichiers']);

// migration 2.1.0.0 => 2.1.0.1 FIXME : remove later
if (!isset($GLOBALS['liste_fichiers'][0]['bt_path'])) {
	foreach ($GLOBALS['liste_fichiers'] as $i => $file) {
		$GLOBALS['liste_fichiers'][$i]['bt_path'] = '';
	}
	file_put_contents($GLOBALS['fichier_liste_fichiers'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers']))).' */');
}

afficher_html_head($GLOBALS['lang']['label_resume']);
echo '<div id="top">'."\n";
afficher_msg();
echo moteur_recherche($GLOBALS['lang']['search_everywhere']);
afficher_topnav(pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME), ucfirst($GLOBALS['lang']['label_resume']));
echo '</div>'."\n";

$total_artic = liste_elements_count("SELECT count(ID) AS nbr FROM articles", array());
$total_links = liste_elements_count("SELECT count(ID) AS nbr FROM links", array());
$total_comms = liste_elements_count("SELECT count(ID) AS nbr FROM commentaires", array());

$total_nb_fichiers = sizeof($GLOBALS['liste_fichiers']);


echo '<div id="axe">'."\n";
echo '<div id="mainpage">'."\n";


// transforme les valeurs numériques d’un tableau pour les ramener la valeur max du tableau à $maximum. Les autres valeurs du tableau sont à l’échelle
function scaled_size($tableau, $maximum) {
	$ratio = max(array_values($tableau))/$maximum;

	$return = array();
	foreach ($tableau as $key => $value) {
		if ($ratio != 0) {
			$return[] = array('nb'=> $value , 'nb_scale' => floor($value/$ratio), 'date' => $key);
		} else {
			$return[] = array('nb'=> $value , 'nb_scale' => 0, 'date' => $key);
		}
	}
	return $return;
}

// compte le nombre d’éléments dans la base, pour chaque mois les 12 derniers mois. 
/*
 * retourne un tableau YYYYMM => nb;
 *
*
*/
function get_tableau_date($data_type) {
	$table_months = array();
	for ($i = 35 ; $i >= 0 ; $i--) {
		$table_months[date('Ym', mktime(0, 0, 0, date("m")-$i, 1, date("Y")))] = 0;
	}

	// met tout ça au format YYYYMMDDHHIISS où DDHHMMSS vaut 00000000 (pour correspondre au format de l’ID de BT qui est \d{14}
	$max = max(array_keys($table_months)).date('dHis');
	$min = min(array_keys($table_months)).'00000000';
	$bt_date = ($data_type == 'articles') ? 'bt_date' : 'bt_id';

	$query = "SELECT substr($bt_date, 1, 6) AS date, count(*) AS idbydate FROM $data_type WHERE $bt_date BETWEEN $min AND $max GROUP BY date ORDER BY date";

	try {
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute();
		$tab = $req-> fetchAll(PDO::FETCH_ASSOC);
		foreach ($tab as $i => $month) {
			if (isset($table_months[$month['date']])) {
				$table_months[$month['date']] = $month['idbydate'];
			}
		}
	} catch (Exception $e) {
		die('Erreur 86459: '.$e->getMessage());
	}
	return $table_months;
}






/* Une recherche a été faite : affiche la recherche */

if (!empty($_GET['q'])) {
	$q = htmlspecialchars($_GET['q']);
	$nb_commentaires = liste_elements_count("SELECT count(ID) AS nbr FROM commentaires WHERE bt_content LIKE ?", array('%'.$q.'%'));
	$nb_articles = liste_elements_count("SELECT count(ID) AS nbr FROM articles WHERE ( bt_content LIKE ? OR bt_title LIKE ? )", array('%'.$q.'%', '%'.$q.'%'));
	$nb_liens = liste_elements_count("SELECT count(ID) AS nbr FROM links WHERE ( bt_content LIKE ? OR bt_title LIKE ? OR bt_link LIKE ? )", array('%'.$q.'%','%'.$q.'%', '%'.$q.'%'));
	$nb_files = sizeof(liste_base_files('recherche', urldecode($_GET['q']), ''));

	echo '<h2>'.$GLOBALS['lang']['recherche'].' "<span style="font-style: italic">'.htmlspecialchars($_GET['q']).'</span>" :</h2>'."\n";
	echo '<ul id="resultat-recherche">';
	echo "\t".'<li><a href="commentaires.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_objets($nb_commentaires, 'commentaire').'</a></li>';
	echo "\t".'<li><a href="articles.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_objets($nb_articles, 'article').'</a></li>';
	echo "\t".'<li><a href="links.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_objets($nb_liens, 'link').'</a></li>';
	echo "\t".'<li><a href="fichiers.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_objets($nb_files, 'fichier').'</a></li>';
	echo '</ul>';
}

/* sinon, affiche les graphes. */

else {
	mb_internal_encoding('UTF-8');
	echo '<div id="graphs">'."\n";
	$nothingyet = 0;

	if (!$total_artic == 0) {
		// print sur chaque div pour les articles.
		echo '<p>'.ucfirst($GLOBALS['lang']['label_articles']).' :</p>'."\n";
		echo '<div class="graphique" id="articles">'."\n";
		$table = scaled_size(get_tableau_date('articles'), 150);
		while ($table[0]['nb'] === 0) {
			$first = array_shift($table);
		}
		foreach ($table as $i => $data) {
			echo '<div class="month"><div class="month-bar" style="height: '.$data['nb_scale'].'px; margin-top:'.max(20-$data['nb_scale'], 0).'px"></div><span class="month-nb">'.$data['nb'].'</span><a href="articles.php?filtre='.$data['date'].'"><span class="month-name">'.mb_substr(mois_en_lettres(substr($data['date'],4,2)),0,3)."\n".substr($data['date'],2,2).'</span></a></div>';
		}
		echo '</div>'."\n";
	} else {
		$nothingyet++;
	}

	if (!$total_comms == 0) {
		// print sur chaque div pour les com.
		echo '<p>'.ucfirst($GLOBALS['lang']['label_commentaires']).' :</p>'."\n";
		echo '<div class="graphique" id="commentaires">'."\n";
		$table = scaled_size(get_tableau_date('commentaires'), 150);
		while ($table[0]['nb'] === 0) {
			$first = array_shift($table);
		}
		foreach ($table as $i => $data) {
			echo '<div class="month"><div class="month-bar" style="height: '.$data['nb_scale'].'px; margin-top:'.max(20-$data['nb_scale'], 0).'px"></div><span class="month-nb">'.$data['nb'].'</span><a href="commentaires.php?filtre='.$data['date'].'"><span class="month-name">'.mb_substr(mois_en_lettres(substr($data['date'],4,2)),0,3)."\n".substr($data['date'],2,2).'</span></a></div>';
		}
		echo '</div>'."\n";
	} else {
		$nothingyet++;
	}

	if (!$total_links == 0) {
		// print sur chaque div pour les liens.
		echo '<p>'.ucfirst($GLOBALS['lang']['label_links']).' :</p>'."\n";
		echo '<div class="graphique" id="links">'."\n";
		$table = scaled_size(get_tableau_date('links'), 150);
		while ($table[0]['nb'] === 0) {
			$first = array_shift($table);
		}
		foreach ($table as $i => $data) {
			echo '<div class="month"><div class="month-bar" style="height: '.$data['nb_scale'].'px; margin-top:'.max(20-$data['nb_scale'], 0).'px"></div><span class="month-nb">'.$data['nb'].'</span><a href="links.php?filtre='.$data['date'].'"><span class="month-name">'.mb_substr(mois_en_lettres(substr($data['date'],4,2)),0,3).".\n".substr($data['date'],2,2).'</span></a></div>';
		}
		echo '</div>'."\n";
	} else {
		$nothingyet++;
	}

	echo '</div>'."\n";

	if ($nothingyet == 3) {
		echo '
	<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADoAAABwAgMAAAAyFouxAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3QMFDDAtgYm8UQAAAAxQTFRFGBEPg3ue872G////A0Y42AAAAWNJREFUOMul1KGOXDEMheFDQkLOq4WYDMmrXbIk5L6aSciQv2C222rH2YKafZYly7JsATkxr0gVdirVzk4ANA+OyeIzXTnXc93Ac9amf2jmaLdr7/W4rySfB9M7T4NPzs4N9JO3PCcSB8/Uo0vTB9vT6fmDw7T76GwM4KvfdyMWT3x0PqW9OZo+aT47NRk6OjuS7YOz36kbp2tLd0pi5yxtlAZvdmkZI7UgK2cbkiLahFk5x6BFeLNdeKadEWHmrOy0pQhvXHhLgmcLb7zfnWBSEZ443z2hQxs62CAj5+bk1AX5AUZvTkgJ8q6N1C+/3CvjtPw6rCsrQ5PGT37VR3fhvSdttOH92ud3J5s2Iv7L+oej/eyRZ88vj9I7Ph1ZGjKuDENpRD6uDJMundLDNEnUjlimRYyTH8u0WNfJsUxbGrWbWgxaRLi0JI1ckmbpTmLUf8/z5rUEuRa1U6+n+Nf8f/wL/hlQOQcLWpwAAAAASUVORK5CYII=" style="height:112px; width:58px;display:block;margin:30px auto;">

	<div style=" display: inline-block; border: 2px black inset; border-radius: 4px;"><div style="text-align: left;border: 1px white solid; border-radius: 4px;"><div style="border: 1px black inset; padding: 3px 5px; letter-spacing: 2px;">No data yet . . .<br/>
	Why not write <a href="ecrire.php" style="color: inherit">something</a> ?
	</div></div></div>
	';

	}
}

footer('show_last_ip', $begin);
?>
