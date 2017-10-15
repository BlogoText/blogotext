<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BlogoText/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***

/**
 *
 */
function titre_url($title)
{
    return trim(diacritique($title), '-');
}

/**
 *
 */
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

/**
 *
 */
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

/**
 *
 */
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

/**
 * used by markup()
 * convert a BBCode link to HTML <a>
 * with a check on URL
 *
 * @params array $matches, array from preg_replace_callback
 * @return string
 */
function markup_clean_href($matches)
{
    // var_dump($matches);
    $allowed = array('http://', 'https://', 'ftp://');
    // if not a valid url, return the string
    if ((
            !filter_var($matches['2'], FILTER_VALIDATE_URL)
         || !preg_match('#^('.join('|', $allowed).')#i', $matches['2'])
        )
     && !preg_match('/^#[\w-_]+$/i', $matches['2']) // allowing [text|#look-at_this]
    ) {
        return $matches['0'];
    }
    // handle different case
    if (empty(trim($matches['1']))) {
        return $matches['1'].'<a href="'.$matches['2'].'">'.$matches['2'].'</a>';
    } else {
        return '<a href="'.$matches['2'].'">'.$matches['1'].'</a>';
    }
}

/**
 * convert text with BBCode (more or less BBCode) to HTML
 *
 * @params string $text
 * @return string
 */
function markup($text)
{
    $text = preg_replace('#\[([^|]+)\|(\s*javascript.*)\]#i', '$1', $text);
    $text = preg_replace("/(\r\n|\r\n\r|\n|\n\r|\r)/", "\r", $text);
    $tofind = array(
        // /* regex URL     */ '#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s\)\(]+))#i',
        // /* a href        */ '#\[([^[]+)\|([^[]+)\]#',
        /* strong        */ '#\[b\](.*?)\[/b\]#s',
        /* italic        */ '#\[i\](.*?)\[/i\]#s',
        /* strike        */ '#\[s\](.*?)\[/s\]#s',
        /* underline     */ '#\[u\](.*?)\[/u\]#s',
        /* quote         */ '#\[quote\](.*?)\[/quote\]#s',
        /* code          */ '#\[code\]\[/code\]#s',
        /* code=language */ '#\[code=(\w+)\]\[/code\]#s',
    );
    $toreplace = array(
        // /* regex URL     */ '$1<a href="$2">$2</a>',
        // /* a href        */ '<a href="$2">$1</a>',
        /* strong        */ '<b>$1</b>',
        /* italic        */ '<em>$1</em>',
        /* strike        */ '<del>$1</del>',
        /* underline     */ '<u>$1</u>',
        /* quote         */ '<blockquote>$1</blockquote>'."\r",
        /* code          */ '<prebtcode></prebtcode>'."\r",
        /* code=language */ '<prebtcode data-language="$1"></prebtcode>'."\r",
    );

    preg_match_all('#\[code(=(\w+))?\](.*?)\[/code\]#s', $text, $code_contents, PREG_SET_ORDER);
    $texte_formate = preg_replace('#\[code(=(\w+))?\](.*?)\[/code\]#s', '[code$1][/code]', $text);
    $texte_formate = preg_replace($tofind, $toreplace, $texte_formate);
    $texte_formate = preg_replace_callback('#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s\)\(]+))#i', 'markup_clean_href', $texte_formate);
    $texte_formate = preg_replace_callback('#\[([^[]+)\|([^[]+)\]#', 'markup_clean_href', $texte_formate);
    $texte_formate = parse_texte_paragraphs($texte_formate);
    $texte_formate = parse_texte_code($texte_formate, $code_contents);

    return $texte_formate;
}

/**
 *
 */
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

/**
 *
 */
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

/**
 *
 */
function date_formate_iso($id)
{
    $date = decode_id($id);
    $timestamp = mktime($date['heure'], $date['minutes'], $date['secondes'], $date['mois'], $date['jour'], $date['annee']);
    $date_iso = date('c', $timestamp);
    return $date_iso;
}

/**
 *
 */
function en_lettres($captchavalue)
{
    return $GLOBALS['lang'][strval($captchavalue)];
}

/**
 *
 */
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

/**
 *
 */
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

/**
 *
 */
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
