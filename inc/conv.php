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

function extraire_mots($texte) {
	// supprime les retours à la ligne
	$texte = str_replace("\r", '', $texte);
	$texte = str_replace("\n", ' ', $texte);
	$texte = str_replace("\t", ' ', $texte);
	$texte = preg_replace('#<[^>]*>#', ' ', $texte); // supprime les balises
	$texte = preg_replace('#[[:punct:]]#', ' ', $texte); // supprime la pontuation
	$texte = trim(preg_replace('# {2,}#', ' ', $texte)); // supprime les espaces multiples
	$tableau = explode(' ', $texte);
	foreach ($tableau as $i => $mots) {
		if (strlen(trim($mots)) <= 4) {// supprime les mots trop courts (le, les, à, de…)
			unset($tableau[$i]);
		}
		elseif (preg_match('#\d#', $mots)) {// supprime les mots contenant des chiffres
			unset($tableau[$i]);
		}
		// supprime les caractères unicodes trop complexes
		elseif ( preg_match('#\?#', utf8_decode(preg_replace('#&(.)(acute|grave|circ|uml|cedil|tilde|ring|slash|caron);#', '$1', $mots))) ) {
			unset($tableau[$i]);
		}
	}

	natsort($tableau);
	// Ici on a une liste de mots avec doublons.
	// on recherche les mots trouvés plusieurs fois dans la liste, qui seront les mots clés en priorité
	$tableau = array_unique($tableau);

	$n = 3; // nb occurrences
	$liste = array();

	// on recherche les mots trouvés 3 fois. S’il y a plus de 7 mots, on s’arrête
	// si moins de 7 mots, on cherche les mots présents 2 fois (en plus des mots présents 3 fois), on les ajoute
	// si toujours moins de 7 mots, on les prends tous.
	//  Ceci permet de garder en prio les mots présents le plus de fois.
	while ($n > 0 and count($liste) < 7) {
		foreach($tableau as $i => $mot) {
			if (substr_count($texte, $mot) == $n) {
				$liste[] = $mot;
			}
		}
		$n--;
	}

	$retour = implode($liste, ', ');
	return $retour;
}

function titre_url($url) {
	$url = diacritique($url, 0, 0);
	$url = trim($url, '-');
	return $url;
}

function protect_markup($text) {
	$patterns = array(
		'`<bt_(.*?)>`',
		'`</bt_(.*?)>`'
	);
	$result = preg_replace($patterns, '', $text);
	return $result;
}

// remove slashes if necessary
function clean_txt($text) {
	if (!get_magic_quotes_gpc()) {
		$return = trim(addslashes($text));
	} else {
		$return = trim($text);
	}
return $return;
}

function clean_txt_array($array) {
	foreach ($array as $i => $key) {
		$array[$i] = clean_txt($key);
	}
	return $array;
}


function diacritique($texte, $majuscules, $espaces) {
	$texte = strip_tags($texte);
	$texte = html_entity_decode($texte, ENT_QUOTES, 'UTF-8'); // &eacute => é ; é => é ; (uniformise)
	$texte = htmlentities($texte, ENT_QUOTES, 'UTF-8'); // é => &eacute;
	$texte = preg_replace('#&(.)(acute|grave|circ|uml|cedil|tilde|ring|slash|caron);#', '$1', $texte); // &eacute => e
	$texte = preg_replace('#(\t|\n|\r)#', ' ' , $texte); // retours à la ligne => espaces
	$texte = preg_replace('#&([a-z]{2})lig;#i', '$1', $texte); // EX : œ => oe ; æ => ae
	$texte = preg_replace('#&[\w\#]*;#U', '', $texte); // les autres (&quote; par exemple) sont virés
	$texte = preg_replace('#[^\w -]#U', '', $texte); // on ne garde que chiffres, lettres _, -, et espaces.
	if ($majuscules == '0')
		$texte = strtolower($texte);
	if ($espaces == '0')
		$texte = preg_replace('#[ ]+#', '-', $texte); // les espaces deviennent des tirets.
	return $texte;
}

function rel2abs_admin($article) { // pour le panel admin : l’aperçu de l’article doit convertir les liens (vu que /admin est un sous dossier de /).
	// remplace tous les (src|href)="$i" ou $i ne contient pas "/" ni "[a-z]+://" (référence avant négative (avec le !))
	$article = preg_replace('#(src|href)=\"(?!(/|[a-z]+://))#i','$1="../', $article);
	return $article;
}


function formatage_wiki($texte) {
	$texte = preg_replace("/(\r\n|\r\n\r|\n|\n\r|\r)/", "\r", /*"\r\r".*/$texte/*."\r\r"*/);
	$tofind = array(
		// transforme certains \r en \n
		'#<(.*?)>\r#',			// html (les <tag> suivi d’un \r ne prennent pas de <br/> (le <br> remplace un \r, pas un \n).
		'#(\[/?code\])\r#',	// idem pour les balises [code], qui sont converties un peu à part, en <pre>

		// css block elements
		'#\[left\](.*?)\[/left\]#s',			// aligner à gauche
		'#\[center\](.*?)\[/center\]#s',		// aligner au centre
		'#\[right\](.*?)\[/right\]#s',		// aligner à droite
		'#\[justify\](.*?)\[/justify\]#s',	// justifier

		// misc
		'#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s]+))#i',	// Regex URL
		'#(.*?)\r#',																// br : retour à la ligne sans saut de ligne
		'#\[([^[]+)\|([^[]+)\]#',												// a href
		'#\[(https?://)([^[]+)\]#',											// url
		'#\[img\](.*?)(\|(.*?))?\[/img\]#s',								// [img]
		'#\[b\](.*?)\[/b\]#s',													// strong
		'#\[i\](.*?)\[/i\]#s',													// italic
		'#\[s\](.*?)\[/s\]#s',													// strike
		'#\[u\](.*?)\[/u\]#s',													// souligne
		'#%%#',																		// br
		'#\*\*(.*?)(<br/>\n|$)#s',													// ul/li (br because of prev replace)
		'#</ul>\n<ul>#s',																// ul/li
		'#\#\#(.*?)(<br/>\n|$)#s',													// ol/li
		'#</ol>\n<ol>#s',																// ol/li
		'#\[quote\](.*?)\[/quote\]#s',										// citation
		'#\[color=(\\\?")?(\w*|\#[0-9a-fA-F]{3}|\#[0-9a-fA-F]{6})(\\\?")?\](.*?)\[/color\]#s',			// color
		'#\[size=(\\\?")?([0-9]{1,})(\\\?")?\](.*?)\[/size\]#s',			// size

		// quelques &nbsp; que j’ajoute
		'# »#',
		'#« #',
		'# !#',
		'# :#',
		'# \?#',
	);
	$toreplace = array(
		// transforme certains \r en \n
		'<$1>'."\n",		// html
		'$1'."\n",			// la balise code qui ne doit pas recevoir de <br/>

		// css block elements
		'<div style="text-align:left;">$1</div>',		// aligner à gauche
		'<div style="text-align:center;">$1</div>',	// aligner au centre
		'<div style="text-align:right;">$1</div>',	// aligner à droite
		'<div style="text-align:justify;">$1</div>',	// justifier

		// misc
		'$1<a href="$2">$2</a>',												// url regex
		'$1<br/>'."\n",															// br : retour à la ligne sans saut de ligne
		'<a href="$2">$1</a>',													// a href
		'<a href="$1$2">$2</a>',												// url
		'<img src="$1" alt="$3" />',														// img
		'<strong>$1</strong>',													// strong
		'<em>$1</em>',																// italic
		'<del>$1</del>',															// barre
		'<u>$1</u>',																// souligne
		'<br />',																	// br
		'<ul>'."\n".'<li>$1</li></ul>'."\n",									// ul/li
		'',																				// ul/li
		'<ol>'."\n".'<li>$1</li></ol>'."\n",									// ol/li
		'',																				// ol/li
		'<blockquote>$1</blockquote>'."\n",											// citation
		'<span style="color:$2;">$4</span>',								// color
		'<span style="font-size:$2pt;">$4</span>',								// text-size

		// quelques &nbsp; que j’ajoute
		'&nbsp;»',
		'«&nbsp;',
		'&nbsp;!',
		'&nbsp;:',
		'&nbsp;?',
	);

	// un array des balises [code] avant qu’ils ne soient modifiées par le preg_replace($tofind, $toreplace, $texte);
	// il met en mémoire le contenu des balises [code] tels quelles
	$nb_balises_code_avant = preg_match_all('#\[code\](.*?)\[/code\]#s', $texte, $balises_code, PREG_SET_ORDER);

	// formate tout sauf les [code]
	$texte_formate = preg_replace($tofind, $toreplace, $texte);

	// remplace les balises [codes] modifiées par la balise code non formatée et précédement mises en mémoire.
	// ceci permet de formater l’ensemble du message, sauf les balises [code],
	if ($nb_balises_code_avant) {
		$nb_balises_code_apres = preg_match_all('#\[code\](.*?)\[/code\]#s', $texte_formate, $balises_code_apres, PREG_SET_ORDER);
		foreach ($balises_code as $i => $code) {
			$texte_formate = str_replace($balises_code_apres[$i][0], '<pre>'.htmlspecialchars($balises_code[$i][1]).'</pre>', $texte_formate);

		}
	}

	$texte_formate = stripslashes($texte_formate);
	return $texte_formate;
}

function formatage_commentaires($texte) {
	$texte = " ".$texte;
	$texte = preg_replace('#\[([^|]+)\|(\s*javascript.*)\]#i', '$1', $texte);
	$tofindc = array(
		'#\[quote\](.+?)\[/quote\]#s',									// citation } les citation imbriquées marchent pour **deux niveaux** seulement,
		'#\[quote\](.+?)\[/quote\]#s',									//          } [quote][quote]bla[/quote][quote]bla[/quote][/quote] marchent et donnent le résultat attendu.
																					//				} !!!! : [quote*][quote**][quote]bla[/quote**][/quote*][/quote] fait que les balises avec *, ** matchent.
		'#<p>(\r|\n)+#s',										// code
		'#\[code\](.+?)\[/code\]#s',										// code
		'#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s\)\(]+))#i',	// Regex URL
		'#\[([^[]+)\|([^[]+)\]#',											// a href
		'#\[b\](.*?)\[/b\]#s',												// strong
		'#\[i\](.*?)\[/i\]#s',												// italic
		'#\[s\](.*?)\[/s\]#s',												// strike
		'#\[u\](.*?)\[/u\]#s',												// souligne
		'# »#',																	// close quote
		'#« #', 																	// open quote
		'# !#',																	// !
		'# :#',																	// :
		'# ;#',																	// ;
	);
	$toreplacec = array(
		'</p><blockquote>$1</blockquote><p>',		// citation (</p> and <p> needed for W3C)
		'</p><blockquote>$1</blockquote><p>',		// citation (</p> and <p> needed for W3C)
		'<p>',																// removes unwanted \n

		'<code>$1</code>',													// code
		'$1<a href="$2">$2</a>',												// url
		'<a href="$2">$1</a>',												// a href
		'<strong>$1</strong>',												// strong
		'<em>$1</em>',															// italic
		'<del>$1</del>',														// barre
		'<u>$1</u>',															// souligne
		'&thinsp;»',															// close quote
		'«&thinsp;',															// open quote
		'&thinsp;!',															// !
		'&nbsp;:',																// :
		'&thinsp;;',															// ;
	);

	$toreplaceArrayLength = sizeof($tofindc);
	for ($i=0; $i < $toreplaceArrayLength; $i++) {
		$texte = preg_replace($tofindc["$i"], $toreplacec["$i"], $texte);
	}

	$texte = stripslashes($texte);
	$texte = str_replace(array("\\"), array("&#92;"), $texte);
	$texte = '<p>'.trim(nl2br($texte)).'</p>';
	$texte = str_replace('<p></p>', '', $texte);
	return $texte;
}

function formatage_links($texte) {
	$tofind = array(
		'#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s]+))#i',		// Regex URL 
		'#\[([^[]+)\|([^[]+)\]#',											// a href
		'#\[b\](.*?)\[/b\]#s',												// strong
		'#\[i\](.*?)\[/i\]#s',												// italic
		'#\[s\](.*?)\[/s\]#s',												// strike
		'#\[u\](.*?)\[/u\]#s',												// souligne
//		'#(.*?)\r#',															// br : retour à la ligne sans saut de ligne
	);
	$toreplace = array(
		'$1<a href="$2">$2</a>',												// url  '$1<a href="$2">$2</a>'
		'<a href="$2">$1</a>',												// a href
		'<strong>$1</strong>',												// strong
		'<em>$1</em>',															// italic
		'<del>$1</del>',														// barre
		'<u>$1</u>',															// souligne
//		'$1<br/>'."\n",														// br : retour à la ligne sans saut de ligne
	);

	// ceci permet de formater l’ensemble du message, sauf les balises [code],
	$nb_balises_code_avant = preg_match_all('#\[code\](.*?)\[/code\]#s', $texte, $balises_code, PREG_SET_ORDER);
	$texte_formate = preg_replace($tofind, $toreplace, ' '.$texte.' ');
	$texte_formate = nl2br(trim(($texte_formate)));
	if ($nb_balises_code_avant) {
		$nb_balises_code_apres = preg_match_all('#\[code\](.*?)\[/code\]#s', $texte_formate, $balises_code_apres, PREG_SET_ORDER);
		foreach ($balises_code as $i => $code) {
			$texte_formate = str_replace($balises_code_apres[$i][0], '<pre>'.$balises_code[$i][1].'</pre>', $texte_formate);
		}
	}
	return $texte_formate;
}


function date_formate($id, $format_force='') {
	$retour ='';
	$date= decode_id($id);
		$time_article = mktime(0, 0, 0, $date['mois'], $date['jour'], $date['annee']);
		$auj = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		$hier = mktime(0, 0, 0, date('m'), date('d')-'1', date('Y'));
		$jour_l = jour_en_lettres($date['jour'], $date['mois'], $date['annee']);
		$mois_l = mois_en_lettres($date['mois']);
			$format = array (
				'0' => $date['jour'].'/'.$date['mois'].'/'.$date['annee'],           // 14/01/1983
				'1' => $date['mois'].'/'.$date['jour'].'/'.$date['annee'],           // 01/14/1983
				'2' => $date['jour'].' '.$mois_l.' '.$date['annee'],                 // 14 janvier 1983
				'3' => $jour_l.' '.$date['jour'].' '.$mois_l.' '.$date['annee'],     // vendredi 14 janvier 1983
				'4' => $jour_l.' '.$date['jour'].' '.$mois_l,                        // vendredi 14 janvier
				'5' => $mois_l.' '.$date['jour'].', '.$date['annee'],                // janvier 14, 1983
				'6' => $jour_l.', '.$mois_l.' '.$date['jour'].', '.$date['annee'],   // vendredi, janvier 14, 1983
				'7' => $date['annee'].'-'.$date['mois'].'-'.$date['jour'],           // 1983-01-14
				'8' => substr($jour_l,0,3).'. '.$date['jour'].' '.$mois_l,           // ven. 14 janvier
			);

		if ($format_force != '') {
			$retour = $format[$format_force];
		} else {
			$retour = $format[$GLOBALS['format_date']];

			if ( $time_article == $auj ) {
				$retour = $GLOBALS['lang']['aujourdhui'].', '.$retour;
			} elseif ( $time_article == $hier ) {
				$retour = $GLOBALS['lang']['hier'].', '.$retour;
			}
		}
	return ucfirst($retour);
}

function heure_formate($id) {
	$date = decode_id($id);
	$ts = mktime($date['heure'], $date['minutes'], $date['secondes'], $date['mois'], $date['jour'], $date['annee']); // ts : timestamp
	$format = array (
		'0' => date('H\:i\:s',$ts),		// 23:56:04
		'1' => date('H\:i',$ts),			// 23:56
		'2' => date('h\:i\:s A',$ts),		// 11:56:04 PM
		'3' => date('h\:i A',$ts),			// 11:56 PM
	);
	$valeur = $format[$GLOBALS['format_heure']];
	return $valeur;
}

function date_formate_iso($id) {
	$date = decode_id($id);
	$ts = mktime($date['heure'], $date['minutes'], $date['secondes'], $date['mois'], $date['jour'], $date['annee']); // ts : timestamp
	$date_iso = date('c', $ts);
	return $date_iso;
}
// à partir d’une valeur en octets (par ex 20M) retourne la quantité en octect.
// le format « 20M » est par exemple retourné avec ini_get("max_upload_size").
function return_bytes($val) {
	$val = trim($val);
	$last = strtolower($val[strlen($val)-1]);
	switch($last) {
		case 'g': $val *= 1024;
		case 'm': $val *= 1024;
		case 'k': $val *= 1024;
	}
	return $val;
}

// retourne une chaine en kio, Mio, Gio… d’un entier représentant une taille en octets
function taille_formate($taille) {
	$prefixe = array (
		'0' => $GLOBALS['lang']['byte_symbol'],   // 2^00 o
		'1' => 'ki'.$GLOBALS['lang']['byte_symbol'], // 2^10 o
		'2' => 'Mi'.$GLOBALS['lang']['byte_symbol'], // 2^20 o
		'3' => 'Gi'.$GLOBALS['lang']['byte_symbol'], // ...
		'4' => 'Ti'.$GLOBALS['lang']['byte_symbol'], // ...
	);
	$dix = 0;
	while ($taille / (pow(2, 10*$dix)) > 1024 /*or ($dix >= '40')*/) {
		$dix++;
	}
	$taille = $taille / (pow(2, 10*$dix));
	if ($dix != 0) {
		$taille = sprintf("%.1f", $taille);
	}

	return $taille.' '.$prefixe[$dix];
}

function jour_en_lettres($jour, $mois, $annee) {
	$date = date('w', mktime(0, 0, 0, $mois, $jour, $annee));
	switch($date) {
		case '0': $nom = $GLOBALS['lang']['dimanche']; break;
		case '1': $nom = $GLOBALS['lang']['lundi']; break;
		case '2': $nom = $GLOBALS['lang']['mardi']; break;
		case '3': $nom = $GLOBALS['lang']['mercredi']; break;
		case '4': $nom = $GLOBALS['lang']['jeudi']; break;
		case '5': $nom = $GLOBALS['lang']['vendredi']; break;
		case '6': $nom = $GLOBALS['lang']['samedi']; break;
		default: $nom = "(BT_ERROR_DAY)"; break;
	}
	return $nom;
}

function mois_en_lettres($numero, $abbrv='') {
	if ($abbrv == 1) {
		switch($numero) {
			case '01': $nom = $GLOBALS['lang']['janv.']; break;
			case '02': $nom = $GLOBALS['lang']['fev.']; break;
			case '03': $nom = $GLOBALS['lang']['mars.']; break;
			case '04': $nom = $GLOBALS['lang']['avr.']; break;
			case '05': $nom = $GLOBALS['lang']['mai.']; break;
			case '06': $nom = $GLOBALS['lang']['juin.']; break;
			case '07': $nom = $GLOBALS['lang']['juil.']; break;
			case '08': $nom = $GLOBALS['lang']['aout.']; break;
			case '09': $nom = $GLOBALS['lang']['sept.']; break;
			case '10': $nom = $GLOBALS['lang']['oct.']; break;
			case '11': $nom = $GLOBALS['lang']['nov.']; break;
			case '12': $nom = $GLOBALS['lang']['dec.']; break;
			default: $nom = "(BT_ERROR_MONTH)"; break;
		}
		return $nom;
	}
	else {
		switch($numero) {
			case '01': $nom = $GLOBALS['lang']['janvier']; break;
			case '02': $nom = $GLOBALS['lang']['fevrier']; break;
			case '03': $nom = $GLOBALS['lang']['mars']; break;
			case '04': $nom = $GLOBALS['lang']['avril']; break;
			case '05': $nom = $GLOBALS['lang']['mai']; break;
			case '06': $nom = $GLOBALS['lang']['juin']; break;
			case '07': $nom = $GLOBALS['lang']['juillet']; break;
			case '08': $nom = $GLOBALS['lang']['aout']; break;
			case '09': $nom = $GLOBALS['lang']['septembre']; break;
			case '10': $nom = $GLOBALS['lang']['octobre']; break;
			case '11': $nom = $GLOBALS['lang']['novembre']; break;
			case '12': $nom = $GLOBALS['lang']['decembre']; break;
			default: $nom = "(BT_ERROR_MONTH)"; break;
		}
		return $nom;
	}
}

function nombre_objets($nb, $type) {
	if ($nb == '0') {
		$retour = $GLOBALS['lang']['note_no_'.$type];
	} elseif ($nb == '1') {
		$retour = $nb.' '.$GLOBALS['lang']['label_'.$type];
	} elseif ($nb > '1') {
		$retour = $nb.' '.$GLOBALS['lang']['label_'.$type.'s'];
	}
	return $retour;
}

function str2($nb) {
	return str_pad($nb, 2, "0", STR_PAD_LEFT);
}
function str4($nb) {
	return str_pad($nb, 4, "0", STR_PAD_LEFT);
}


