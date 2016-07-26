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

$GLOBALS['addons'][] = array(
    'tag' => 'readmore',
    'name' => 'Read more',
    'desc' => 'List 3 "read-also like" thumbnails below each post.',
    'version' => '1.0.0',
);

// returns HTML <table> calender
function addon_readmore()
{
    $nb_art = 3;
    // lists IDs
    try {
        $result = $GLOBALS['db_handle']->query("SELECT ID FROM articles WHERE bt_statut=1 AND bt_date <= ".date('YmdHis'))->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die('Error rand addon_readmore(): '.$e->getMessage());
    }

    // clean array
    foreach ($result as $i => $art) {
        $result[$i] = $art['ID'];
    }
    // randomize array
    shuffle($result);

    // select nth entries (PHP does take care about "nb_arts > count($result)")
    $art = array_slice($result, 0, $nb_art);

    // get articles
    try {
        $array_qmark = str_pad('', count($art)*3-2, "?, ");
        $query = "SELECT bt_title, bt_id, bt_content FROM articles WHERE bt_statut=1 AND bt_date <= ".date('YmdHis')." AND ID IN (".$array_qmark.")";
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute($art);
        $articles = $req->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die('Error fetch content addon_readmore(): '.$e->getMessage());
    }

    foreach ($articles as $i => $article) {
        // extract image from $article[bt_content]
        preg_match('<img *.* src=(["|\']?)(([^\1 ])*)(\1).*>', $article['bt_content'], $matches);
        $articles[$i]['bt_img'] = '';
        if (!empty($matches)) {
            $articles[$i]['bt_img'] = chemin_thb_img_test($matches[2]);
        }
        unset($articles[$i]['bt_content']);
        // generates link
        $dec_id = decode_id($article['bt_id']);
        $articles[$i]['bt_link'] = $GLOBALS['racine'].'?d='.implode('/', $dec_id).'-'.titre_url($article['bt_title']);
    }

    // generates the UL/LI list.
    $html = '<ul>'."\n";
    foreach ($articles as $art) {
        $html .= "\t".'<li style="background-image: url('.$art['bt_img'].');"><a href="'.$art['bt_link'].'">'.$art['bt_title'].'</a></li>'."\n";
    }
    $html .= '</ul>'."\n";

    return $html;
}
