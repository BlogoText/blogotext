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

function extraire_mots($texte)
{
    $texte = str_replace(array("\r", "\n", "\t"), array('', ' ', ' '), $texte); // removes \n, \r and tabs
    $texte = strip_tags($texte); // removes HTML tags
    $texte = preg_replace('#[!"\#$%&\'()*+,./:;<=>?@\[\]^_`{|}~«»“”…]#', ' ', $texte); // removes punctuation
    $texte = trim(preg_replace('# {2,}#', ' ', $texte)); // remove consecutive spaces

    $words = explode(' ', $texte);
    foreach ($words as $i => $word) {
        // remove short words & words with numbers
        if (strlen($word) <= 4 or preg_match('#\d#', $word)) {
            unset($words[$i]);
        } elseif (preg_match('#\?#', utf8_decode(preg_replace('#&(.)(acute|grave|circ|uml|cedil|tilde|ring|slash|caron);#', '$1', $word)))) {
            unset($words[$i]);
        }
    }

    // keep only words that occure at least 3 times
    $words = array_unique($words);
    $keywords = array();
    foreach ($words as $i => $word) {
        if (substr_count($texte, $word) >= 3) {
            $keywords[] = $word;
        }
    }
    $keywords = array_unique($keywords);

    natsort($keywords);
    return implode($keywords, ', ');
}

function titre_url($title)
{
    return trim(diacritique($title), '-');
}

// remove slashes if necessary
function clean_txt($text)
{
    if (!get_magic_quotes_gpc()) {
        return trim($text);
    } else {
        return trim(stripslashes($text));
    }
}

function protect($text)
{
    return htmlspecialchars(clean_txt($text));
}

function diacritique($texte)
{
    $texte = strip_tags($texte);
    $texte = html_entity_decode($texte, ENT_QUOTES, 'UTF-8'); // &eacute => é ; é => é ; (uniformize)
    $texte = htmlentities($texte, ENT_QUOTES, 'UTF-8'); // é => &eacute;
    $texte = preg_replace('#&(.)(acute|grave|circ|uml|cedil|tilde|ring|slash|caron);#', '$1', $texte); // &eacute => e
    $texte = preg_replace('#(\t|\n|\r)#', ' ', $texte); // \n, \r => spaces
    $texte = preg_replace('#&([a-z]{2})lig;#i', '$1', $texte); // œ => oe ; æ => ae
    $texte = preg_replace('#&[\w\#]*;#U', '', $texte); // remove other entities like &quote, &nbsp.
    $texte = preg_replace('#[^\w -]#U', '', $texte); // keep only ciffers, letters, spaces, hyphens.
    $texte = strtolower($texte); // to lower case
    $texte = preg_replace('#[ ]+#', '-', $texte); // spaces => hyphens
    return $texte;
}

function rel2abs_admin($article)
{
    // if relative URI in path, make absolute paths (since /admin/ panel is 1 lv deeper) for href/src.
    $article = preg_replace('#(src|href)=\"(?!(/|[a-z]+://))#i', '$1="../', $article);
    return $article;
}

function parse_texte_paragraphs($texte)
{
    // trims empty lines at begining and end of raw texte
    $texte_formate = preg_replace('#^(\r|\n|<br>|<br/>|<br />){0,}(.*?)(\r|<br>|<br/>|<br />){0,}$#s', '$2', $texte);
    // trick to make <hr/> elements be recognized by parser
    $texte_formate = preg_replace('#<hr */?>#is', '<hr></hr>', $texte);
    $block_elements = 'address|article|aside|audio|blockquote|canvas|dd|li|div|[oud]l|fieldset|fig(caption|ure)|footer|form|h[1-6]|header|hgroup|hr|main|nav|noscript|output|p|pre|prebtcode|section|table|thead|tbody|tfoot|tr|td|video';

    $texte_final = '';
    $finished = false;
    // if text begins with block-element, remove it and goes on
    while ($finished === false) {
        $matches = array();
        // we have a block element
        if (preg_match('#^<('.$block_elements.') ?.*?>(.*?)</(\1)>#s', $texte_formate, $matches)) {
            // extract the block element
            $texte_retire = $matches[0];
            // parses inner text for nl2br()
            $texte_nl2br = "\n".nl2br($texte_retire)."\n";
            // removes <br/> that follow a block (ie: <block><br> → <block>) and add it to the final text
            $texte_final .= preg_replace('#(</?('.$block_elements.') ?.*?>)(<br ?/?>)(\n?\r?)#s', '$1$3$5', $texte_nl2br);
            // saves the remaining text
            $texte_restant = preg_replace('#^<('.$block_elements.') ?.*?>(.*?)</(\1)>#s', '', $texte_formate, 1);
            // again, removes empty lines+spaces at begin or end TODO : save the lines to make multiple "<br/>" spaces (??)
            $texte_restant = preg_replace('#^(\r|\n|<br>|<br/>|<br />){0,}(.*?)(\r|<br>|<br/>|<br />){0,}$#s', '$2', $texte_restant);
            // if no matches for block elements, we are finished
            $finished = (strlen($texte_retire) === 0) ? true : false;
        } else {
            // we have an inline element (or text)
            // grep the text until newline OR new block element do AND set it in <p></p>
            $texte_restant = preg_replace('#^(.*?)(\r\r|<('.$block_elements.') ?.*?>)#s', '$2', $texte_formate, 1);
            // saves the text we just "greped"
            $texte_retire = trim(substr($texte_formate, 0, -strlen($texte_restant)));
            // IF greped text is empty: no text or no further block element (or new line)
            if (strlen($texte_retire) === 0) {
                // remaining text is NOT empty : keep it in a <p></p>
                if (strlen($texte_restant) !== 0) {
                    $texte_final .= "\n".'<p>'.nl2br($texte_restant).'</p>'."\n";
                }
                // since the entire remaining text is in a new <p></p>, we are finished
                $finished = true;

            // FI IF greped text is not empty: keep it in a new <p></p>.
            } else {
                $texte_final .= "\n".'<p>'.nl2br($texte_retire).'</p>'."\n";
            }
        }

        //  again, removes empty lines+spaces at begin or end
        $texte_restant = preg_replace('#^(\r|\n|<br>|<br/>|<br />){0,}(.*?)(\r|<br>|<br/>|<br />){0,}$#s', '$2', $texte_restant);
        // loops on the text, to find the next element.
        $texte_formate = $texte_restant;
    }
    // retransforms <hr/>
    $texte_final = preg_replace('#<hr></hr>#', '<hr/>', $texte_final);
    return $texte_final;
}

function parse_texte_code($texte, $code_before)
{
    if ($code_before) {
        preg_match_all('#<prebtcode( data-language="\w+")?></prebtcode>#s', $texte, $code_after, PREG_SET_ORDER);
        foreach ($code_before as $i => $code) {
            $pos = strpos($texte, $code_after[$i][0]);
            if ($pos !== false) {
                 $texte = substr_replace($texte, '<pre'.((isset($code_after[$i][1])) ? $code_after[$i][1] : '').'><code>'.htmlspecialchars(htmlspecialchars_decode($code_before[$i][3])).'</code></pre>', $pos, strlen($code_after[$i][0]));
            }
        }
    }
    return $texte;
}

function markup_articles($texte)
{
    $texte = preg_replace("/(\r\n|\r\n\r|\n|\n\r|\r)/", "\r", $texte);
    $tofind = array(
        // Replace \r with \n when following HTML elements
        '#<(.*?)>\r#',

        // Jusitifications
        /* left    */ '#\[left\](.*?)\[/left\]#s',
        /* center  */ '#\[center\](.*?)\[/center\]#s',
        /* right   */ '#\[right\](.*?)\[/right\]#s',
        /* justify */ '#\[justify\](.*?)\[/justify\]#s',

        // Misc
        /* regex URL     */ '#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s]+))#i',
        /* a href        */ '#\[([^[]+)\|([^[]+)\]#',
        /* url           */ '#\[(https?://)([^[]+)\]#',
        /* [img]         */ '#\[img\](.*?)(\|(.*?))?\[/img\]#s',
        /* strong        */ '#\[b\](.*?)\[/b\]#s',
        /* italic        */ '#\[i\](.*?)\[/i\]#s',
        /* strike        */ '#\[s\](.*?)\[/s\]#s',
        /* underline     */ '#\[u\](.*?)\[/u\]#s',
        /* ul/li         */ '#\*\*(.*?)(\r|$)#s',  // br because of prev replace
        /* ul/li         */ '#</ul>\r<ul>#s',
        /* ol/li         */ '#\#\#(.*?)(\r|$)#s',  // br because of prev replace
        /* ol/li         */ '#</ol>\r<ol>#s',
        /* quote         */ '#\[quote\](.*?)\[/quote\]#s',
        /* code          */ '#\[code\]\[/code\]#s',
        /* code=language */ '#\[code=(\w+)\]\[/code\]#s',
        /* color         */ '#\[color=(?:")?(\w+|\#(?:[0-9a-fA-F]{3}){1,2})(?:")?\](.*?)\[/color\]#s',
        /* size          */ '#\[size=(\\\?")?([0-9]{1,})(\\\?")?\](.*?)\[/size\]#s',

        // Adding some &nbsp;
        '# (»|!|:|\?|;)#',
        '#« #',
    );
    $toreplace = array(
        // Replace \r with \n
        '<$1>'."\n",

        // Jusitifications
        /* left    */ '<div style="text-align:left;">$1</div>',
        /* center  */ '<div style="text-align:center;">$1</div>',
        /* right   */ '<div style="text-align:right;">$1</div>',
        /* justify */ '<div style="text-align:justify;">$1</div>',

        // Misc
        /* regex URL     */ '$1<a href="$2">$2</a>',
        /* a href        */ '<a href="$2">$1</a>',
        /* url           */ '<a href="$1$2">$2</a>',
        /* [img]         */ '<img src="$1" alt="$3" />',
        /* strong        */ '<b>$1</b>',
        /* italic        */ '<em>$1</em>',
        /* strike        */ '<del>$1</del>',
        /* underline     */ '<u>$1</u>',
        /* ul/li         */ '<ul><li>$1</li></ul>'."\r",
        /* ul/li         */ "\r",
        /* ol/li         */ '<ol><li>$1</li></ol>'."\r",
        /* ol/li         */ '',
        /* quote         */ '<blockquote>$1</blockquote>'."\r",
        /* code          */ '<prebtcode></prebtcode>'."\r",
        /* code=language */ '<prebtcode data-language="$1"></prebtcode>'."\r",
        /* color         */ '<span style="color:$1;">$2</span>',
        /* size          */ '<span style="font-size:$2pt;">$4</span>',

        // Adding some &nbsp;
        ' $1',
        '« ',
    );

    // memorizes [code] tags contents before bbcode being appliyed
    preg_match_all('#\[code(=(\w+))?\](.*?)\[/code\]#s', $texte, $code_contents, PREG_SET_ORDER);
    // empty the [code] tags (content is in memory)
    $texte_formate = preg_replace('#\[code(=(\w+))?\](.*?)\[/code\]#s', '[code$1][/code]', $texte);
    // apply bbcode filter
    $texte_formate = preg_replace($tofind, $toreplace, $texte_formate);
    // apply <p>paragraphe</p> filter
    $texte_formate = parse_texte_paragraphs($texte_formate);
    // replace [code] elements with theire initial content
    $texte_formate = parse_texte_code($texte_formate, $code_contents);

    return $texte_formate;
}

function markup($texte)
{
    $texte = preg_replace('#\[([^|]+)\|(\s*javascript.*)\]#i', '$1', $texte);
    $texte = preg_replace("/(\r\n|\r\n\r|\n|\n\r|\r)/", "\r", $texte);
    $tofind = array(
        /* regex URL     */ '#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s\)\(]+))#i',
        /* a href        */ '#\[([^[]+)\|([^[]+)\]#',
        /* strong        */ '#\[b\](.*?)\[/b\]#s',
        /* italic        */ '#\[i\](.*?)\[/i\]#s',
        /* strike        */ '#\[s\](.*?)\[/s\]#s',
        /* underline     */ '#\[u\](.*?)\[/u\]#s',
        /* quote         */ '#\[quote\](.*?)\[/quote\]#s',
        /* code          */ '#\[code\]\[/code\]#s',
        /* code=language */ '#\[code=(\w+)\]\[/code\]#s',
    );
    $toreplace = array(
        /* regex URL     */ '$1<a href="$2">$2</a>',
        /* a href        */ '<a href="$2">$1</a>',
        /* strong        */ '<b>$1</b>',
        /* italic        */ '<em>$1</em>',
        /* strike        */ '<del>$1</del>',
        /* underline     */ '<u>$1</u>',
        /* quote         */ '<blockquote>$1</blockquote>'."\r",
        /* code          */ '<prebtcode></prebtcode>'."\r",
        /* code=language */ '<prebtcode data-language="$1"></prebtcode>'."\r",
    );

    preg_match_all('#\[code(=(\w+))?\](.*?)\[/code\]#s', $texte, $code_contents, PREG_SET_ORDER);
    $texte_formate = preg_replace('#\[code(=(\w+))?\](.*?)\[/code\]#s', '[code$1][/code]', $texte);
    $texte_formate = preg_replace($tofind, $toreplace, $texte_formate);
    $texte_formate = parse_texte_paragraphs($texte_formate);
    $texte_formate = parse_texte_code($texte_formate, $code_contents);

    return $texte_formate;
}

function date_formate($id, $format_force = '')
{
    $retour = '';
    $date = decode_id($id);
        $jour_l = jour_en_lettres($date['jour'], $date['mois'], $date['annee']);
        $mois_l = mois_en_lettres($date['mois']);
        $format = array (
            $date['jour'].'/'.$date['mois'].'/'.$date['annee'],          // 14/01/1983
            $date['mois'].'/'.$date['jour'].'/'.$date['annee'],          // 01/14/1983
            $date['jour'].' '.$mois_l.' '.$date['annee'],                // 14 janvier 1983
            $jour_l.' '.$date['jour'].' '.$mois_l.' '.$date['annee'],    // vendredi 14 janvier 1983
            $jour_l.' '.$date['jour'].' '.$mois_l,                       // vendredi 14 janvier
            $mois_l.' '.$date['jour'].', '.$date['annee'],               // janvier 14, 1983
            $jour_l.', '.$mois_l.' '.$date['jour'].', '.$date['annee'],  // vendredi, janvier 14, 1983
            $date['annee'].'-'.$date['mois'].'-'.$date['jour'],          // 1983-01-14
            substr($jour_l, 0, 3).'. '.$date['jour'].' '.$mois_l,        // ven. 14 janvier
        );

    if ($format_force != '') {
        $retour = $format[$format_force];
    } else {
        $retour = $format[$GLOBALS['format_date']];
    }
    return ucfirst($retour);
}

function heure_formate($id)
{
    $date = decode_id($id);
    $timestamp = mktime($date['heure'], $date['minutes'], $date['secondes'], $date['mois'], $date['jour'], $date['annee']);
    $format = array (
        'H:i:s',    // 23:56:04
        'H:i',      // 23:56
        'h:i:s A',  // 11:56:04 PM
        'h:i A',    // 11:56 PM
    );
    return date($format[$GLOBALS['format_heure']], $timestamp);
}

function date_formate_iso($id)
{
    $date = decode_id($id);
    $timestamp = mktime($date['heure'], $date['minutes'], $date['secondes'], $date['mois'], $date['jour'], $date['annee']);
    $date_iso = date('c', $timestamp);
    return $date_iso;
}

// From a filesize (like "20M"), returns a size in bytes.
function return_bytes($val)
{
    $val = trim($val);
    $prefix = strtolower($val[strlen($val)-1]);
    switch ($prefix) {
        case 'g':
            $val *= 1024;
            break;
        case 'm':
            $val *= 1024;
            break;
        case 'k':
            $val *= 1024;
            break;
    }
    return $val;
}

// from a filesize in bytes, returns computed size in kiB, MiB, GiB…
function taille_formate($taille)
{
    $prefixe = array (
             $GLOBALS['lang']['byte_symbol'],
        'ki'.$GLOBALS['lang']['byte_symbol'],
        'Mi'.$GLOBALS['lang']['byte_symbol'],
        'Gi'.$GLOBALS['lang']['byte_symbol'],
        'Ti'.$GLOBALS['lang']['byte_symbol'],
    );
    $dix = 0;
    while ($taille / (pow(2, 10*$dix)) > 1024) {
        $dix++;
    }
    $taille = $taille / (pow(2, 10*$dix));
    if ($dix != 0) {
        $taille = sprintf('%.1f', $taille);
    }

    return $taille.' '.$prefixe[$dix];
}

function en_lettres($captchavalue)
{
    return $GLOBALS['lang'][strval($captchavalue)];
}

function jour_en_lettres($jour, $mois, $annee)
{
    $date = date('w', mktime(0, 0, 0, $mois, $jour, $annee));
    switch ($date) {
        case 0:
            return $GLOBALS['lang']['dimanche'];
        break;
        case 1:
            return $GLOBALS['lang']['lundi'];
        break;
        case 2:
            return $GLOBALS['lang']['mardi'];
        break;
        case 3:
            return $GLOBALS['lang']['mercredi'];
        break;
        case 4:
            return $GLOBALS['lang']['jeudi'];
        break;
        case 5:
            return $GLOBALS['lang']['vendredi'];
        break;
        case 6:
            return $GLOBALS['lang']['samedi'];
        break;
    }
    return $nom;
}

function mois_en_lettres($numero, $abbrv = 0)
{
    if ($abbrv == 1) {
        switch ($numero) {
            case '01':
                return $GLOBALS['lang']['janv.'];
            break;
            case '02':
                return $GLOBALS['lang']['fev.'];
            break;
            case '03':
                return $GLOBALS['lang']['mars.'];
            break;
            case '04':
                return $GLOBALS['lang']['avr.'];
            break;
            case '05':
                return $GLOBALS['lang']['mai.'];
            break;
            case '06':
                return $GLOBALS['lang']['juin.'];
            break;
            case '07':
                return $GLOBALS['lang']['juil.'];
            break;
            case '08':
                return $GLOBALS['lang']['aout.'];
            break;
            case '09':
                return $GLOBALS['lang']['sept.'];
            break;
            case '10':
                return $GLOBALS['lang']['oct.'];
            break;
            case '11':
                return $GLOBALS['lang']['nov.'];
            break;
            case '12':
                return $GLOBALS['lang']['dec.'];
            break;
        }
    } else {
        switch ($numero) {
            case '01':
                return $GLOBALS['lang']['janvier'];
            break;
            case '02':
                return $GLOBALS['lang']['fevrier'];
            break;
            case '03':
                return $GLOBALS['lang']['mars'];
            break;
            case '04':
                return $GLOBALS['lang']['avril'];
            break;
            case '05':
                return $GLOBALS['lang']['mai'];
            break;
            case '06':
                return $GLOBALS['lang']['juin'];
            break;
            case '07':
                return $GLOBALS['lang']['juillet'];
            break;
            case '08':
                return $GLOBALS['lang']['aout'];
            break;
            case '09':
                return $GLOBALS['lang']['septembre'];
            break;
            case '10':
                return $GLOBALS['lang']['octobre'];
            break;
            case '11':
                return $GLOBALS['lang']['novembre'];
            break;
            case '12':
                return $GLOBALS['lang']['decembre'];
            break;
        }
    }
}

function nombre_objets($nb, $type)
{
    switch ($nb) {
        case 0:
            return $GLOBALS['lang']['note_no_'.$type];
        case 1:
            return $nb.' '.$GLOBALS['lang']['label_'.$type];
        default:
            return $nb.' '.$GLOBALS['lang']['label_'.$type.'s'];
    }
}

function str2($nb)
{
    return str_pad($nb, 2, '0', STR_PAD_LEFT);
}

function str4($nb)
{
    return str_pad($nb, 4, '0', STR_PAD_LEFT);
}
