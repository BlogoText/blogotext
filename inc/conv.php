<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden <timo@neerden.eu>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

function extraire_mots($texte) {
	$texte = str_replace(array("\r", "\n", "\t"), array('', ' ', ' '), $texte); // removes \n, \r and tabs
	$texte = strip_tags($texte); // removes HTML tags
	$texte = preg_replace('#[!"\#$%&\'()*+,./:;<=>?@\[\]^_`{|}~«»“”…]#', ' ', $texte); // removes punctuation
	$texte = trim(preg_replace('# {2,}#', ' ', $texte)); // remove consecutive spaces

	$mots = explode(' ', $texte);
	foreach ($mots as $i => $mot) {
		// remove short words & words with numbers
		if (strlen($mot) <= 4 or preg_match('#\d#', $mot)) {
			unset($mots[$i]);
		}
		elseif ( preg_match('#\?#', utf8_decode(preg_replace('#&(.)(acute|grave|circ|uml|cedil|tilde|ring|slash|caron);#', '$1', $mot))) ) {
			unset($mots[$i]);
		}
	}

	// Ici on a une liste de mots avec doublons.
	// on recherche les mots trouvés plusieurs fois dans la liste, qui seront les mots clés en priorité
	$mots = array_unique($mots);

	$liste = array();
	// only keep words with 3 occurences or more
	foreach ($mots as $i => $mot) {
		if (substr_count($texte, $mot) >= 3) {
			$liste[] = $mot;
		}
	}
	$liste = array_unique($liste);

	natsort($liste);
	$liste = implode($liste, ', ');
	return $liste;
}

function titre_url($title) {
	$title = diacritique($title);
	$title = trim($title, '-');
	return $title;
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

function protect($text) {
	$return = htmlspecialchars(stripslashes(clean_txt($text)));
	return $return;
}

function diacritique($texte) {
	$texte = strip_tags($texte);
	$texte = html_entity_decode($texte, ENT_QUOTES, 'UTF-8'); // &eacute => é ; é => é ; (uniformize)
	$texte = htmlentities($texte, ENT_QUOTES, 'UTF-8'); // é => &eacute;
	$texte = preg_replace('#&(.)(acute|grave|circ|uml|cedil|tilde|ring|slash|caron);#', '$1', $texte); // &eacute => e
	$texte = preg_replace('#(\t|\n|\r)#', ' ' , $texte); // \n, \r => spaces
	$texte = preg_replace('#&([a-z]{2})lig;#i', '$1', $texte); // œ => oe ; æ => ae
	$texte = preg_replace('#&[\w\#]*;#U', '', $texte); // remove other entities like &quote, &nbsp.
	$texte = preg_replace('#[^\w -]#U', '', $texte); // keep only ciffers, letters, spaces, hyphens.
	$texte = strtolower($texte); // to lower case
	$texte = preg_replace('#[ ]+#', '-', $texte); // spaces => hyphens
	return $texte;
}

function rel2abs_admin($article) { // pour le panel admin : l’aperçu de l’article doit convertir les liens (vu que /admin est un sous dossier de /).
	// remplace tous les (src|href)="$i" ou $i ne contient pas "/" ni "[a-z]+://" (référence avant négative (avec le !))
	$article = preg_replace('#(src|href)=\"(?!(/|[a-z]+://))#i','$1="../', $article);
	return $article;
}

function parse_texte_paragraphs($texte) {
	// trims empty lines at begining and end of raw texte
	$texte_formate = preg_replace('#^(\r|\n|<br>|<br/>|<br />){0,}(.*?)(\r|<br>|<br/>|<br />){0,}$#s', '$2', $texte);
	$block_elements = 'address|article|aside|audio|blockquote|canvas|dd|li|div|[oud]l|fieldset|fig(caption|ure)|footer|form|h[1-6]|header|hgroup|hr|main|nav|noscript|output|p|pre|section|table|thead|tfoot|tr|td|video';

	$texte_final = '';
	$finished = false;
	// if text begins with block-element, remove it and goes on
	while ($finished === false) {
		$matches = array();
		// we have a block element
		if ( preg_match('#^<('.$block_elements.') ?.*?>(.*?)</(\1)>#s', $texte_formate, $matches) ) {
			// extract the block element
			$texte_retire = $matches[0];
			// parses inner text for nl2br(), but removes <br/> tha follow a block (ie: <block><br> → <block>)
			$texte_nl2br = "\n".nl2br($texte_retire)."\n";
			// add it to the final text
			$texte_final .= preg_replace('#(</?('.$block_elements.') ?.*?>)(<br ?/?>)(\n?\r?)#s', '$1$3$5', $texte_nl2br);
			// saves the remaining text
			$texte_restant = preg_replace('#^<('.$block_elements.') ?.*?>(.*?)</(\1)>#s', '', $texte_formate, 1);
			// again, removes empty lines+spaces at begin or end TODO : save the lines to make multiple "<br/>" spaces (??)
			$texte_restant = preg_replace('#^(\r|\n|<br>|<br/>|<br />){0,}(.*?)(\r|<br>|<br/>|<br />){0,}$#s', '$2', $texte_restant);
			// if no matches for block elements, we are finished
			$finished = (strlen($texte_retire) === 0) ? TRUE : FALSE;
		}
		else {
			// we have an inline element (or text) : do set it in <p></p>
			// grep the text until newline OR new block element
			$texte_restant = preg_replace('#^(.*?)(\r\r|<('.$block_elements.') ?.*?>)#s', '$2', $texte_formate, 1);
			// saves the text we just "greped"
			$texte_retire = trim(substr($texte_formate, 0, -strlen($texte_restant)));

			// greped text is empty: no text or no further block element (or new line)
			if (strlen($texte_retire) === 0) {
				// remaining text is NOT empty : keep it in a <p></p>
				if (strlen($texte_restant) !== 0) {
					$texte_final .= "\n".'<p>'.nl2br($texte_restant).'</p>'."\n";
				}
				// since the entire remaining text is in a new <p></p>, we are finished
				$finished = true;

			// greped text is not empty: keep it in a new <p></p>.
			} else {
				$texte_final .= "\n".'<p>'.nl2br($texte_retire).'</p>'."\n";
			}
		}

		//  again, removes empty lines+spaces at begin or end
		$texte_restant = preg_replace('#^(\r|\n|<br>|<br/>|<br />){0,}(.*?)(\r|<br>|<br/>|<br />){0,}$#s', '$2', $texte_restant);
		// loops on the text, to find the next element.
		$texte_formate = $texte_restant;
	}

	return $texte_final;
}

function formatage_codes($texte, $tofind, $toreplace) {
	// Formater l’ensemble du message, sauf les balises [code] et [code=langage]
        $nb_balises_code_avant = preg_match_all('#\[code\](.+?)\[/code\]#s', $texte, $balises_code, PREG_SET_ORDER);
        $nb_balises_code_spec_avant = preg_match_all('#\[code=([a-z]{1,16})\](.+?)\[/code\]#s', $texte, $balises_code_spec, PREG_SET_ORDER);
        $texte_formate = nl2br(trim($texte_formate));
        $texte_formate = preg_replace($tofind, $toreplace, $texte);
        $texte_formate = parse_texte_paragraphs($texte_formate);
        if ($nb_balises_code_avant || $nb_balises_code_spec_avant) {
                $nb_balises_code_apres = preg_match_all('#\[code\](.*?)\[/code\]#s', $texte_formate, $balises_code_apres, PREG_SET_ORDER);
                foreach ($balises_code as $i => $code) {
                        $texte_formate = str_replace($balises_code_apres[$i][0], '<code>'.htmlspecialchars($balises_code[$i][1]).'</code>', $texte_formate);
                }

                $nb_balises_code_spec_apres = preg_match_all('#\[code=([a-z]{1,16})\](.*?)\[/code\]#s', $texte_formate, $balises_code_spec_apres, PREG_SET_ORDER);
                foreach ($balises_code_spec as $i => $code) {
                        $texte_formate = str_replace($balises_code_spec_apres[$i][0], sprintf(CODE_HIGHTLIGHT_FMT, $balises_code_spec[$i][1], htmlspecialchars($balises_code_spec[$i][2])), $texte_formate);
                }
        }

	$texte_formate = stripslashes($texte_formate);
	return $texte_formate;
}

function formatage_wiki($texte) {
	$texte = preg_replace("/(\r\n|\r\n\r|\n|\n\r|\r)/", "\r", $texte);
	$tofind = array(
		// transforme certains \r en \n
		'#<(.*?)>\r#',			// html (les <tag> suivi d’un \r ne prennent pas de <br/> (le <br> remplace un \r, pas un \n).

		// css block elements
		'#\[left\](.*?)\[/left\]#s',			// aligner à gauche
		'#\[center\](.*?)\[/center\]#s',		// aligner au centre
		'#\[right\](.*?)\[/right\]#s',		// aligner à droite
		'#\[justify\](.*?)\[/justify\]#s',	// justifier

		// misc
		'#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s]+))#i',			// Regex URL
		'#\[([^[]+)\|([^[]+)\]#',												// a href
		'#\[(https?://)([^[]+)\]#',											// url
		'#\[img\](.*?)(\|(.*?))?\[/img\]#s',								// [img]
		'#\[b\](.*?)\[/b\]#s',													// strong
		'#\[i\](.*?)\[/i\]#s',													// italic
		'#\[s\](.*?)\[/s\]#s',													// strike
		'#\[u\](.*?)\[/u\]#s',													// souligne
		'#\*\*(.*?)(\r|$)#s',													// ul/li (br because of prev replace)
		'#</ul>\r<ul>#s',															// ul/li
		'#\#\#(.*?)(\r|$)#s',													// ol/li
		'#</ol>\r<ol>#s',															// ol/li
		'#\[quote\](.*?)\[/quote\]#s',										// citation
		'#\[color=(\\\?")?(\w*|\#[0-9a-fA-F]{3}|\#[0-9a-fA-F]{6})(\\\?")?\](.*?)\[/color\]#s',			// color
		'#\[size=(\\\?")?([0-9]{1,})(\\\?")?\](.*?)\[/size\]#s',		// size

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

		// css block elements
		'<div style="text-align:left;">$1</div>',		// aligner à gauche
		'<div style="text-align:center;">$1</div>',	// aligner au centre
		'<div style="text-align:right;">$1</div>',	// aligner à droite
		'<div style="text-align:justify;">$1</div>',	// justifier

		// misc
		'$1<a href="$2">$2</a>',												// url regex
		'<a href="$2">$1</a>',													// a href
		'<a href="$1$2">$2</a>',												// url
		'<img src="$1" alt="$3" />',											// img
		'<strong>$1</strong>',													// strong
		'<em>$1</em>',																// italic
		'<del>$1</del>',															// barre
		'<u>$1</u>',																// souligne
		'<ul><li>$1</li></ul>'."\r",											// ul/li
		"\r",																			// ul/li
		'<ol><li>$1</li></ol>'."\r",											// ol/li
		'',																			// ol/li
		'<blockquote>$1</blockquote>'."\r",									// citation
		'<span style="color:$2;">$4</span>',								// color
		'<span style="font-size:$2pt;">$4</span>',						// text-size

		// quelques &nbsp; que j’ajoute
		'&nbsp;»',
		'«&nbsp;',
		'&nbsp;!',
		'&nbsp;:',
		'&nbsp;?',
	);

	return formatage_codes($texte, $tofind, $toreplace);
}

function formatage_commentaires($texte) {
	$texte = " ".$texte;
	$texte = preg_replace('#\[([^|]+)\|(\s*javascript.*)\]#i', '$1', $texte);
	$tofind = array(
		'#\[quote\](.+?)\[/quote\]#s',									// citation } les citation imbriquées marchent pour **deux niveaux** seulement,
		'#\[quote\](.+?)\[/quote\]#s',									//          } [quote][quote]bla[/quote][quote]bla[/quote][/quote] marchent et donnent le résultat attendu.
																					//				} !!!! : [quote*][quote**][quote]bla[/quote**][/quote*][/quote] fait que les balises avec *, ** matchent.
		'#<p>(\r|\n)+#s',
		'#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s\)\(]+))#i',	// Regex URL
		'#\[([^[]+)\|([^[]+)\]#',											// a href
		'#\[b\](.*?)\[/b\]#s',												// strong
		'#\[i\](.*?)\[/i\]#s',												// italic
		'#\[s\](.*?)\[/s\]#s',												// strike
		'#\[u\](.*?)\[/u\]#s',												// souligne
	);
	$toreplace = array(
		'<blockquote>$1</blockquote>',		// citation
		'<blockquote>$1</blockquote>',		// citation
		'<p>',										// removes unwanted \n

		'$1<a href="$2">$2</a>',				// url
		'<a href="$2">$1</a>',					// a href
		'<strong>$1</strong>',					// strong
		'<em>$1</em>',								// italic
		'<del>$1</del>',							// barre
		'<u>$1</u>',								// souligne
	);

	$texte = formatage_codes($texte, $tofind, $toreplace);
	$texte = str_replace(array("\\"), array("&#92;"), $texte);
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
	);
	$toreplace = array(
		'$1<a href="$2">$2</a>',												// url  '$1<a href="$2">$2</a>'
		'<a href="$2">$1</a>',												// a href
		'<strong>$1</strong>',												// strong
		'<em>$1</em>',															// italic
		'<del>$1</del>',														// barre
		'<u>$1</u>',															// souligne
	);

	return formatage_codes($texte, $tofind, $toreplace);
}


function date_formate($id, $format_force='') {
	$retour ='';
	$date= decode_id($id);
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

// From a filesize (like "20M"), returns a size in bytes.
// Syntaxe like "20M" is used for example with ini_get("max_upload_size") command
function return_bytes($val) {
	$val = trim($val);
	$prefix = strtolower($val[strlen($val)-1]);
	switch($prefix) {
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
		'3' => 'Gi'.$GLOBALS['lang']['byte_symbol'],
		'4' => 'Ti'.$GLOBALS['lang']['byte_symbol'],
	);
	$dix = 0;
	while ($taille / (pow(2, 10*$dix)) > 1024) {
		$dix++;
	}
	$taille = $taille / (pow(2, 10*$dix));
	if ($dix != 0) {
		$taille = sprintf("%.1f", $taille);
	}

	return $taille.' '.$prefixe[$dix];
}

function en_lettres($captchavalue) {
	switch($captchavalue) {
		case 0 : return $GLOBALS['lang']['0']; break;
		case 1 : return $GLOBALS['lang']['1']; break;
		case 2 : return $GLOBALS['lang']['2']; break;
		case 3 : return $GLOBALS['lang']['3']; break;
		case 4 : return $GLOBALS['lang']['4']; break;
		case 5 : return $GLOBALS['lang']['5']; break;
		case 6 : return $GLOBALS['lang']['6']; break;
		case 7 : return $GLOBALS['lang']['7']; break;
		case 8 : return $GLOBALS['lang']['8']; break;
		case 9 : return $GLOBALS['lang']['9']; break;
	}
}

function jour_en_lettres($jour, $mois, $annee) {
	$date = date('w', mktime(0, 0, 0, $mois, $jour, $annee));
	switch($date) {
		case 0: return $GLOBALS['lang']['dimanche']; break;
		case 1: return $GLOBALS['lang']['lundi']; break;
		case 2: return $GLOBALS['lang']['mardi']; break;
		case 3: return $GLOBALS['lang']['mercredi']; break;
		case 4: return $GLOBALS['lang']['jeudi']; break;
		case 5: return $GLOBALS['lang']['vendredi']; break;
		case 6: return $GLOBALS['lang']['samedi']; break;
	}
	return $nom;
}

function mois_en_lettres($numero, $abbrv=0) {
	if ($abbrv == 1) {
		switch($numero) {
			case '01': return $GLOBALS['lang']['janv.']; break;
			case '02': return $GLOBALS['lang']['fev.']; break;
			case '03': return $GLOBALS['lang']['mars.']; break;
			case '04': return $GLOBALS['lang']['avr.']; break;
			case '05': return $GLOBALS['lang']['mai.']; break;
			case '06': return $GLOBALS['lang']['juin.']; break;
			case '07': return $GLOBALS['lang']['juil.']; break;
			case '08': return $GLOBALS['lang']['aout.']; break;
			case '09': return $GLOBALS['lang']['sept.']; break;
			case '10': return $GLOBALS['lang']['oct.']; break;
			case '11': return $GLOBALS['lang']['nov.']; break;
			case '12': return $GLOBALS['lang']['dec.']; break;
		}
	}
	else {
		switch($numero) {
			case '01': return $GLOBALS['lang']['janvier']; break;
			case '02': return $GLOBALS['lang']['fevrier']; break;
			case '03': return $GLOBALS['lang']['mars']; break;
			case '04': return $GLOBALS['lang']['avril']; break;
			case '05': return $GLOBALS['lang']['mai']; break;
			case '06': return $GLOBALS['lang']['juin']; break;
			case '07': return $GLOBALS['lang']['juillet']; break;
			case '08': return $GLOBALS['lang']['aout']; break;
			case '09': return $GLOBALS['lang']['septembre']; break;
			case '10': return $GLOBALS['lang']['octobre']; break;
			case '11': return $GLOBALS['lang']['novembre']; break;
			case '12': return $GLOBALS['lang']['decembre']; break;
		}
	}
}

function nombre_objets($nb, $type) {
	switch ($nb) {
		case 0 : return $GLOBALS['lang']['note_no_'.$type];
		case 1 : return $nb.' '.$GLOBALS['lang']['label_'.$type];
		default: return $nb.' '.$GLOBALS['lang']['label_'.$type.'s'];
	}
}

function str2($nb) {
	return str_pad($nb, 2, "0", STR_PAD_LEFT);
}
function str4($nb) {
	return str_pad($nb, 4, "0", STR_PAD_LEFT);
}

