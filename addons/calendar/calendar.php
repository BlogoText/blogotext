<?php
# *** LICENSE ***
# This file is a addon for BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2016 Timo Van Neerden.
#
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

/*
 * =================================================================================
 *
 * /!\ PLEASE READ THIS BEFORE WRITING YOUR OWN ADDONS /!\
 *
 * 1. You can use this official addon to see how it works and get inspired.
 *
 * 2. Ideally, write all in english.
 *
 * 3. Before spreading the world with your addon, make sure it is PSR-2 compliant.
 *    You can download this useful tool to help you:
 *        $ curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar
 *        $ php phpcs.phar --standard=PSR2 -n --colors "<path to the addon>/<addon>.php"
 *
 *        Example with this addon:
 *        $ php phpcs.phar --standard=PSR2 -n --colors addons/calendar/calendar.php
 *
 *    Source of the tool: https://github.com/squizlabs/PHP_CodeSniffer
 *
 * That's it! Enjoy and good luck :)
 *
 * =================================================================================
 */

/*
 * First, you must add a new entry to the global $addons.
 * Keywords accepted:
 *
 * 'tag' => 'calendar'
 * (required)
 * This is the addon ID. It must have the same name of this file without ".php".
 * The directory tree has to be "${addon ID}/${addon ID}.php".
 *
 * 'name' => 'Calendar'
 * (required)
 * This is the displayed name into back office. You can add translations using an associative array.
 *
 * 'desc' => 'Display a navigable HTML calendar.'
 * (required)
 * This is the addon description. You can add translations using an associative array.
 *
 * 'version' => '1.0.0'
 * (required)
 * Addon version that should follow the SemVer notation: http://semver.org/.
 *
 * 'url' => 'http://example.org/bt-addons/calendar'
 * (optional but highly recommended)
 * Addon developer website, for addon support.
 *
 * 'css' => 'style.css'
 * 'css' => array('style1.css', 'style1.css')
 * (optional)
 * CSS files to include. You can specifiy several files using an array of filenames.
 *
 * 'js' => 'script.js'
 * 'js' => array('script1.js', 'script.js')
 * (optional)
 * JS files to include. You can specifiy several files using an array of filenames.
 */
$GLOBALS['addons'][] = array(
    'tag' => 'calendar',
    'name' => array(
        'en' => 'Calendar',
        'fr' => 'Calendrier',
    ),
    'desc' => array(
        'en' => 'Display a navigable HTML calendar.',
        'fr' => 'Affiche un calendrier navigable.',
    ),
    'url' => 'http://www.example.org',
    'version' => '1.0.0',
    'css' => 'style.css',
);

/*
 * Second and last, you must declare the callback function as follow:
 *     function addon_${addon ID}() { ... }
 *
 * Note: if your addon contains only CSS/JS files, no need to define such a function.
 *
 * The function has to return valid HTML data.
 * In this addon, it returns a HTML <table> calendar.
 *
 * If you need more functions, you can declare how many as needed below this one.
 * You should suffix theme with an underscore.
 *
 * You can use BlogoText function and defines.
 */
function addon_calendar()
{
    // Get post ID
    if (isset($_GET['d']) and preg_match('#^\d{4}(/\d{2}){5}#', $_GET['d'])) {
        $id = substr(str_replace('/', '', $_GET['d']), 0, 14);
        $date = substr(get_entry($GLOBALS['db_handle'], 'articles', 'bt_date', $id, 'return'), 0, 8);
        $date = ($date <= date('Ymd')) ? $date : date('Ym');
    } elseif (isset($_GET['d']) and preg_match('#^\d{4}/\d{2}(/\d{2})?#', $_GET['d'])) {
        $date = str_replace('/', '', $_GET['d']);
        $date = (preg_match('#^\d{6}\d{2}#', $date)) ? substr($date, 0, 8) : substr($date, 0, 6); // avec jour ?
    } elseif (isset($_GET['id']) and preg_match('#^\d{14}#', $_GET['id'])) {
        $date = substr($_GET['id'], 0, 8);
    } else {
        $date = date('Ym');
    }

    $year = substr($date, 0, 4);
    $this_month = substr($date, 4, 2);
    $this_day = (strlen(substr($date, 6, 2)) == 2) ? substr($date, 6, 2) : '';

    $qstring = isset($_GET['mode']) && !empty($_GET['mode']) ? 'mode='.htmlspecialchars($_GET['mode']).'&amp;' : '';

    $week_days = array(
        $GLOBALS['lang']['lu'],
        $GLOBALS['lang']['ma'],
        $GLOBALS['lang']['me'],
        $GLOBALS['lang']['je'],
        $GLOBALS['lang']['ve'],
        $GLOBALS['lang']['sa'],
        $GLOBALS['lang']['di']
    );
    $first_day = mktime(0, 0, 0, $this_month, 1, $year);
    $days_in_this_month = date('t', $first_day);
    $day_offset = date('w', $first_day - 1);

    // We check if there is one or more posts/links/comments in the current month
    $dates_list = array();
    $mode = ( !empty($_GET['mode']) ) ? $_GET['mode'] : 'blog';
    switch ($mode) {
        case 'comments':
            $where = 'commentaires';
            break;
        case 'links':
            $where = 'links';
            break;
        case 'blog':
        default:
            $where = 'articles';
            break;
    }

    // We look for previous and next post dates
    list($previous_post, $next_post) = prev_next_posts_($year, $this_month, $where);
    $prev_mois = '?'.$qstring.'d='.substr($previous_post, 0, 4).'/'.substr($previous_post, 4, 2);
    $next_mois = '?'.$qstring.'d='.substr($next_post, 0, 4).'/'.substr($next_post, 4, 2);

    // List of days containing at least one post for this month
    $dates_list = table_list_date_($year.$this_month, $where);

    // Calendar header
    $html = '<table id="calendrier">'."\n";
    $html .= '<caption>';
    if ($previous_post !== null) {
        $html .= '<a href="'.$prev_mois.'">&#171;</a>&nbsp;';
    }
    $html .= '<a href="?'.$qstring.'d='.$year.'/'.$this_month.'">'.mois_en_lettres($this_month).' '.$year.'</a>';
    if ($next_post !== null) {
        $html .= '&nbsp;<a href="'.$next_mois.'">&#187;</a>';
    }
    $html .= '</caption>'."\n".'<tr>'."\n";

    // Calendar days
    if ($day_offset > 0) {
        for ($i = 0; $i < $day_offset; $i++) {
            $html .=  '<td></td>';
        }
    }
    for ($day = 1; $day <= $days_in_this_month; $day++) {
        $class = $day == $this_day ? ' class="active"' : '';
        if (in_array($day, $dates_list)) {
            $link = '<a href="?'.$qstring.'d='.$year.'/'.$this_month.'/'.str2($day).'">'.$day.'</a>';
        } else {
            $link = $day;
        }
        $html .= '<td'.$class.'>'.$link.'</td>';
        $day_offset++;
        if ($day_offset == 7) {
            $day_offset = 0;
            $html .=  '</tr>';
            if ($day < $days_in_this_month) {
                $html .= '<tr>';
            }
        }
    }
    if ($day_offset > 0) {
        for ($i = $day_offset; $i < 7; $i++) {
            $html .= '<td> </td>';
        }
        $html .= '</tr>'."\n";
    }
    $html .= '</table>'."\n";

    return $html;
}

// Returns a list of days containing at least one post for a given month
function table_list_date_($date, $table)
{
    $return = array();
    $column = ($table == 'articles') ? 'bt_date' : 'bt_id';
    $and_date = 'AND '.$column.' <= '.date('YmdHis');

    $query = "SELECT DISTINCT SUBSTR($column, 7, 2) AS date FROM $table WHERE bt_statut = 1 AND $column LIKE '$date%' $and_date";

    try {
        $req = $GLOBALS['db_handle']->query($query);
        while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
            $return[] = $row['date'];
        }
        return $return;
    } catch (Exception $e) {
        die('Error addon_calendar:table_list_date_(): '.$e->getMessage());
    }
}

// Returns dates of the previous and next visible posts
function prev_next_posts_($year, $month, $table)
{
    $column = ($table == 'articles') ? 'bt_date' : 'bt_id';
    $and_date = 'AND '.$column.' <= '.date('YmdHis');

    $date = new DateTime();
    $date->setDate($year, $month, 1)->setTime(0, 0, 0);
    $date_min = $date->format('YmdHis');
    $date->modify('+1 month');
    $date_max = $date->format('YmdHis');

    $query = "SELECT
        (SELECT SUBSTR($column, 0, 7) FROM $table WHERE bt_statut = 1 AND $column < $date_min ORDER BY $column DESC LIMIT 1),
        (SELECT SUBSTR($column, 0, 7) FROM $table WHERE bt_statut = 1 AND $column > $date_max $and_date ORDER BY $column ASC LIMIT 1)";

    try {
        $req = $GLOBALS['db_handle']->query($query);
        return array_values($req->fetch(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        die('Error addon_calendar:prev_next_posts_(): '.$e->getMessage());
    }
}
