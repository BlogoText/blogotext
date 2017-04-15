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
 * generates the comment form, with params from the admin-side and the visiter-side
 */
function afficher_form_commentaire($article_id, $mode, $erreurs, $edit_comm)
{
    // TODO : why this still GLOBAL ?
    $GLOBALS['form_commentaire'] = '';
    // init default form fields contents
    $form_cont = array('author' => '', 'e_mail' => '', 'webpage' => '', 'comment' => '', 'statut' => '', 'bt_id' => '', 'db_id' => '');

    // FILL DEFAULT FORM DATA
    // admin mode
    if ($mode == 'admin') {
        if (!empty($edit_comm)) {
            // edit mode
            $form_cont['author'] = protect($edit_comm['bt_author']);
            $form_cont['e_mail'] = protect($edit_comm['bt_email']);
            $form_cont['webpage'] = protect($edit_comm['bt_webpage']);
            $form_cont['comment'] = htmlspecialchars($edit_comm['bt_wiki_content']);
            $form_cont['statut'] = protect($edit_comm['bt_statut']);
            $form_cont['bt_id'] = protect($edit_comm['bt_id']);
            $form_cont['db_id'] = protect($edit_comm['ID']);
        } else {
            // non-edit : new comment from admin
            $form_cont['author'] = $GLOBALS['auteur'];
            $form_cont['e_mail'] = $GLOBALS['email'];
            $form_cont['webpage'] = URL_ROOT;
        }
    } // public mode
    else {
        $form_cont['author'] = (isset($_COOKIE['auteur_c'])) ? protect($_COOKIE['auteur_c']) : '';
        $form_cont['e_mail'] = (isset($_COOKIE['email_c'])) ? protect($_COOKIE['email_c']) : '';
        $form_cont['webpage'] = (isset($_COOKIE['webpage_c'])) ? protect($_COOKIE['webpage_c']) : '';
    }

    // comment just submited (for submission OR for preview)
    if (isset($_POST['_verif_envoi'])) {
        $form_cont['author'] = protect($_POST['auteur']);
        $form_cont['e_mail'] = protect($_POST['email']);
        $form_cont['webpage'] = protect($_POST['webpage']);
        $form_cont['comment'] = protect($_POST['commentaire']);
    }

    // WORK ON REQUEST
    // preview ? submission ? validation ?

    // parses the comment, but does not save it
    if (isset($_POST['previsualiser'])) {
        $p_comm = (isset($_POST['commentaire'])) ? protect($_POST['commentaire']) : '';
        $comm['bt_content'] = markup($p_comm);
        $comm['bt_id'] = date('YmdHis');
        $comm['bt_author'] = $form_cont['author'];
        $comm['bt_email'] = $form_cont['e_mail'];
        $comm['bt_webpage'] = $form_cont['webpage'];
        $comm['anchor'] = article_anchor($comm['bt_id']);
        $comm['bt_link'] = '';
        $comm['auteur_lien'] = ($comm['bt_webpage'] != '') ? '<a href="'.$comm['bt_webpage'].'" class="webpage">'.$comm['bt_author'].'</a>' : $comm['bt_author'];
        $GLOBALS['form_commentaire'] .= '<div id="erreurs"><ul><li>Prévisualisation&nbsp;:</li></ul></div>'."\n";
        $GLOBALS['form_commentaire'] .= '<div id="previsualisation">'."\n";
        $GLOBALS['form_commentaire'] .= conversions_theme_commentaire(file_get_contents($GLOBALS['theme_post_comm']), $comm);
        $GLOBALS['form_commentaire'] .= '</div>'."\n";
    } // comm sent ; with errors
    elseif (isset($_POST['_verif_envoi']) and !empty($erreurs)) {
        $GLOBALS['form_commentaire'] .= '<div id="erreurs"><strong>'.$GLOBALS['lang']['erreurs'].'</strong> :'."\n" ;
        $GLOBALS['form_commentaire'] .= '<ul><li>'."\n";
        $GLOBALS['form_commentaire'] .=  implode('</li><li>', $erreurs);
        $GLOBALS['form_commentaire'] .=  '</li></ul></div>'."\n";
    }

    // prelim vars for Generation of comment Form
    $required = ($GLOBALS['require_email'] == 1) ? 'required=""' : '';
    $cookie_checked = (isset($_COOKIE['cookie_c']) and $_COOKIE['cookie_c'] == 1) ? ' checked="checked"' : '';
    $subscribe_checked = (isset($_COOKIE['subscribe_c']) and $_COOKIE['subscribe_c'] == 1) ? ' checked="checked"' : '';

    $form = "\n";

    // COMMENT FORM ON ADMIN SIDE : +always_open –captcha –previsualisation –verif
    if ($mode == 'admin') {
        $rand = '-'.substr(md5(rand(100, 999)), 0, 5);
        $form .= '<form id="form-commentaire'.$form_cont['bt_id'].'" class="form-commentaire" method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'?'.$_SERVER['QUERY_STRING'].'#erreurs">'."\n";
        $form .= '<div class="comm-edit-hidden-bloc">'."\n";
        // begin with some additional stuff on comment "edit".
        if (!empty($edit_comm)) { // edit
            $form .= "\t".'<fieldset class="syst">'."\n";
                $form .= "\t\t".hidden_input('is_it_edit', 'yes');
                $form .= "\t\t".hidden_input('comment_id', $form_cont['bt_id']);
                $form .= "\t\t".hidden_input('status', $form_cont['statut']);
                $form .= "\t\t".hidden_input('ID', $form_cont['db_id']);
            $form .= "\t".'</fieldset><!--end syst-->'."\n";
        }
        // main comm field
        $form .= "\t".'<fieldset class="field">'."\n";
            $form .= form_formatting_toolbar(false);
            $form .= "\t\t".'<textarea class="commentaire text" name="commentaire" required="" placeholder="Lorem Ipsum" id="commentaire'.$rand.'" cols="50" rows="10">'.$form_cont['comment'].'</textarea>'."\n";
        $form .= "\t".'</fieldset>'."\n";
        // info (name, url, email) field
        $form .= "\t".'<fieldset class="infos">'."\n";
            $form .= "\t\t".'<span><label for="auteur'.$rand.'">'.$GLOBALS['lang']['label_dp_pseudo'].'</label>';
            $form .= '<input type="text" name="auteur" id="auteur'.$rand.'" placeholder="John Doe" required value="'.$form_cont['author'].'" size="25" class="text" /></span>'."\n";
            $form .= "\t\t".'<span><label for="email'.$rand.'">'.(($GLOBALS['require_email'] == 1) ? $GLOBALS['lang']['label_dp_email_required'] : $GLOBALS['lang']['label_dp_email']).'</label>';
            $form .= '<input type="email" name="email" id="email'.$rand.'" placeholder="mail@example.com" '.$required.' value="'.$form_cont['e_mail'].'" size="25" class="text" /></span>'."\n";
            $form .= "\t\t".'<span><label for="webpage'.$rand.'">'.$GLOBALS['lang']['label_dp_webpage'].'</label>';
            $form .= '<input type="url" name="webpage" id="webpage'.$rand.'" placeholder="http://www.example.com" value="'.$form_cont['webpage'].'" size="25" class="text" /></span>'."\n";
            $form .= "\t\t".hidden_input('comment_article_id', $article_id);
            $form .= "\t\t".hidden_input('_verif_envoi', '1');
            $form .= "\t\t".hidden_input('token', new_token());
        $form .= "\t".'</fieldset><!--end info-->'."\n";
            // submit buttons
        $form .= "\t".'<fieldset class="buttons">'."\n";
            $form .= "\t\t".'<p class="submit-bttns">'."\n";
            $form .= "\t\t\t".'<button class="submit button-cancel" type="button" onclick="unfold(this);">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
            $form .= "\t\t\t".'<button class="submit button-submit" type="submit" name="enregistrer">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
            $form .= "\t\t".'</p>'."\n";
        $form .= "\t".'</fieldset><!--end buttons-->'."\n";
        $form .= '</div>'."\n";
        $form .= '</form>'."\n";
        $GLOBALS['form_commentaire'] .= $form;

    // COMMENT ON PUBLIC SIDE
    } else {
        // ALLOW COMMENTS : OFF
        if ($GLOBALS['global_com_rule'] == '1' or get_entry($GLOBALS['db_handle'], 'articles', 'bt_allow_comments', $article_id, 'return') == 0) {
            $GLOBALS['form_commentaire'] .= '<p>'.$GLOBALS['lang']['comment_not_allowed'].'</p>'."\n";
        } // ALLOW COMMENTS : ON
        else {
            // Formulaire commun
            $form .= '<form id="form-commentaire" class="form-commentaire" method="post" action="'.'?'.$_SERVER['QUERY_STRING'].'" >'."\n";
            $form .= "\t".'<fieldset class="field">'."\n";
                $form .= form_formatting_toolbar(false);
                $form .= "\t\t".'<textarea class="commentaire" name="commentaire" required="" placeholder="'.$GLOBALS['lang']['label_commentaire'].'" id="commentaire" cols="50" rows="10">'.$form_cont['comment'].'</textarea>'."\n";
            $form .= "\t".'</fieldset>'."\n";
            $form .= "\t".'<fieldset class="infos">'."\n";
                $form .= "\t\t".'<label>'.$GLOBALS['lang']['label_dp_pseudo'];
                $form .= '<input type="text" name="auteur" placeholder="John Doe" required="" value="'.$form_cont['author'].'" size="25" class="text" /></label>'."\n";
                $form .= "\t\t".'<label>'.(($GLOBALS['require_email'] == 1) ? $GLOBALS['lang']['label_dp_email_required'] : $GLOBALS['lang']['label_dp_email']);
                $form .= '<input type="email" name="email" placeholder="mail@example.com" '.$required.' value="'.$form_cont['e_mail'].'" size="25" /></label>'."\n";
                $form .= "\t\t".'<label>'.$GLOBALS['lang']['label_dp_webpage'];
                $form .= '<input type="url" name="webpage" placeholder="http://www.example.com" value="'.$form_cont['webpage'].'" size="25" /></label>'."\n";
                $form .= "\t\t".'<label>'.$GLOBALS['lang']['label_dp_captcha'].'<b>'.en_lettres($GLOBALS['captcha']['x']).'</b> &#x0002B; <b>'.en_lettres($GLOBALS['captcha']['y']).'</b> ';
                $form .= '<input type="number" name="captcha" autocomplete="off" value="" class="text" /></label>'."\n";
                $form .= "\t\t".hidden_input('_token', $GLOBALS['captcha']['hash']);
                $form .= "\t\t".hidden_input('_verif_envoi', '1');
            $form .= "\t".'</fieldset><!--end info-->'."\n";
            $form .= "\t".'<fieldset class="subsc"><!--begin cookie asking -->'."\n";
                $form .= "\t\t".'<input class="check" type="checkbox" id="allowcuki" name="allowcuki"'.$cookie_checked.' />'.label('allowcuki', $GLOBALS['lang']['comment_cookie']).'<br/>'."\n";
                $form .= "\t\t".'<input class="check" type="checkbox" id="subscribe" name="subscribe"'.$subscribe_checked.' />'.label('subscribe', $GLOBALS['lang']['comment_subscribe'])."\n";
            $form .= "\t".'</fieldset><!--end cookie asking-->'."\n";
            $form .= "\t".'<fieldset class="buttons">'."\n";
                $form .= "\t\t".'<input class="submit" type="submit" name="enregistrer" value="'.$GLOBALS['lang']['envoyer'].'" />'."\n";
                $form .= "\t\t".'<input class="submit" type="submit" name="previsualiser" value="'.$GLOBALS['lang']['preview'].'" />'."\n";
            $form .= "\t".'</fieldset><!--end buttons-->'."\n";
            $GLOBALS['form_commentaire'] .= $form;
            if ($GLOBALS['comm_defaut_status'] == '0') { // petit message en cas de moderation a-priori
                $GLOBALS['form_commentaire'] .= "\t\t".'<div class="need-validation">'.$GLOBALS['lang']['remarque'].' :'."\n" ;
                $GLOBALS['form_commentaire'] .= "\t\t\t".$GLOBALS['lang']['comment_need_validation']."\n";
                $GLOBALS['form_commentaire'] .= "\t\t".'</div>'."\n";
            }
            $GLOBALS['form_commentaire'] .= '</form>'."\n";
        }
    }
}

/**
 * Create database like file.
 */
function create_file_dtb($filename, $data)
{
    $data = '<?php /* '.chunk_split(base64_encode(serialize($data)), 76, "\n")."*/\n";
    return (file_put_contents($filename, $data, LOCK_EX) !== false);
}

/**
 * Retrieve serialized data used by create_file_dtb().
 */
function open_serialzd_file($file)
{
    if (!is_file($file)) {
        return array();
    }
    return unserialize(base64_decode(substr(file_get_contents($file), strlen('<?php /* '), -strlen('*/'))));
}

/**
 * Redirect to another URL, the right way.
 */
function redirection($url)
{
    // Prevent use hook on admin side
    if (!defined('IS_IN_ADMIN')) {
        $tmp_hook = hook_trigger_and_check('before_redirection', $url);
        if ($tmp_hook !== false) {
            $url = $tmp_hook['1'];
        }
    }

    exit(header('Location: '.$url));
}

/**
 * Remove the current (.) and parent (..) folders from the list of files returned by scandir().
 */
function rm_dots_dir($array)
{
    return array_diff($array, array('.', '..'));
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

// useless ?
function lang_set_list()
{
    $GLOBALS['langs'] = array('fr' => 'Français', 'en' => 'English');
}

/**
 * load lang
 *
 * $admin bool lang for admin side ?
 */
function lang_load_land($admin)
{
    if (empty($GLOBALS['lang'])) {
        $GLOBALS['lang'] = '';
    }

    if ($admin === true && defined('BT_ROOT_ADMIN')) {
        $path = BT_ROOT_ADMIN;
    } else {
        $path = BT_ROOT;
    }
    switch ($GLOBALS['lang']) {
        case 'en':
            require_once $path.'inc/lang/en_en.php';
            break;
        case 'fr':
        default:
            require_once $path.'inc/lang/fr_fr.php';
    }
}

/**
 * decode id
 *
 * @return array
 */
function decode_id($id)
{
    $retour = array(
        'annee' => substr($id, 0, 4),
        'mois' => substr($id, 4, 2),
        'jour' => substr($id, 6, 2),
        'heure' => substr($id, 8, 2),
        'minutes' => substr($id, 10, 2),
        'secondes' => substr($id, 12, 2)
        );
    return $retour;
}

/**
 * used sometimes, like in the email that is sent.
 */
function get_blogpath($id, $titre)
{
    $date = decode_id($id);
    $path = URL_ROOT.'?d='.$date['annee'].'/'.$date['mois'].'/'.$date['jour'].'/'.$date['heure'].'/'.$date['minutes'].'/'.$date['secondes'].'-'.titre_url($titre);
    return $path;
}

/**
 *
 */
function article_anchor($id)
{
    return 'id'.substr(md5($id), 0, 6);
}

/**
 * todo : move to admin
 */
function traiter_tags($tags)
{
    $tags_array = explode(',', trim($tags, ','));
    $tags_array = array_unique(array_map('trim', $tags_array));
    sort($tags_array);
    return implode(', ', $tags_array);
}

/**
 * tri un tableau non pas comme "sort()" sur l’ID, mais selon une sous clé d’un tableau.
 */
function tri_selon_sous_cle($table, $cle)
{
    foreach ($table as $key => $item) {
         $ss_cles[$key] = $item[$cle];
    }
    if (isset($ss_cles)) {
        array_multisort($ss_cles, SORT_DESC, $table);
    }
    return $table;
}

/**
 * Code from Shaarli. Generate an unique sess_id, usable only once.
 */
function new_token()
{
    $rnd = sha1(uniqid('', true).mt_rand());  // We generate a random string.
    $_SESSION['tokens'][$rnd] = 1;  // Store it on the server side.
    return $rnd;
}

/**
 * Tells if a token is ok. Using this function will destroy the token.
 * true=token is ok.
 */
function check_token($token)
{
    if (isset($_SESSION['tokens'][$token])) {
        unset($_SESSION['tokens'][$token]); // Token is used: destroy it.
        return true; // Token is ok.
    }
    return false; // Wrong token, or already used.
}

/**
 * remove params from url
 *
 * @param string $param
 * @return string url
 */
function remove_url_param($param)
{
    if (isset($_GET[$param])) {
        return str_replace(
            array(
                '&'.$param.'='.$_GET[$param],
                '?'.$param.'='.$_GET[$param],
                '?&amp;',
                '?&',
                '?',
            ),
            array('','?','?','?',''),
            '?'.$_SERVER['QUERY_STRING']
        );
    } elseif (isset($_SERVER['QUERY_STRING'])) {
        return $_SERVER['QUERY_STRING'];
    }
    return '';
}

/**
 * Having a comment ID, sends emails to the other comments that are subscriben to the same article.
 */
function send_emails($id_comment)
{
    // retreive from DB: article_id, article_title, author_name, author_email
    $article_id = get_entry($GLOBALS['db_handle'], 'commentaires', 'bt_article_id', $id_comment, 'return');
    $article_title = get_entry($GLOBALS['db_handle'], 'articles', 'bt_title', $article_id, 'return');
    $comm_author = get_entry($GLOBALS['db_handle'], 'commentaires', 'bt_author', $id_comment, 'return');
    $comm_author_email = get_entry($GLOBALS['db_handle'], 'commentaires', 'bt_email', $id_comment, 'return');

    // retreiving all subscriben email, except that has just been posted.
    $liste_comments = array();
    try {
        $query = '
            SELECT DISTINCT bt_email
              FROM commentaires
             WHERE bt_statut = 1
                   AND bt_article_id = ?
                   AND bt_email != ?
                   AND bt_subscribe = 1
             ORDER BY bt_id';
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute(array($article_id, $comm_author_email));
        $liste_comments = $req->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die('Erreur : '.$e->getMessage());
    }

    // filter empty emails
    $to_send_mail = array();
    foreach ($liste_comments as $comment) {
        if (!empty($comment['bt_email'])) {
            $to_send_mail[] = $comment['bt_email'];
        }
    }

    // Add the article author email
    if ($GLOBALS['alert_author']) {
        if ($GLOBALS['email'] != $comm_author_email) {
            $to_send_mail[] = $GLOBALS['email'];
        }
    }

    unset($liste_comments);
    if (!$to_send_mail) {
        return true;
    }

    $subject = 'New comment on "'.$article_title.'" - '.$GLOBALS['nom_du_site'];
    $headers  = 'MIME-Version: 1.0'."\r\n".'Content-type: text/html; charset="UTF-8"'."\r\n";
    $headers .= 'From: no.reply_'.$GLOBALS['email']."\r\n".'X-Mailer: BlogoText - PHP/'.phpversion();

    // send emails
    foreach ($to_send_mail as $mail) {
        $unsublink = get_blogpath($article_id, '').'&amp;unsub=1&amp;mail='.base64_encode($mail).'&amp;article='.$article_id;
        $message = '<html>';
        $message .= '<head><title>'.$subject.'</title></head>';
        $message .= '<body><p>A new comment by <b>'.$comm_author.'</b> has been posted on <b>'.$article_title.'</b> form '.$GLOBALS['nom_du_site'].'.<br/>';
        $message .= 'You can see it by following <a href="'.get_blogpath($article_id, '').'#'.article_anchor($id_comment).'">this link</a>.</p>';
        $message .= '<p>To unsubscribe from the comments on that post, you can follow this link:<br/><a href="'.$unsublink.'">'.$unsublink.'</a>.</p>';
        $message .= '<p>To unsubscribe from the comments on all the posts, follow this link:<br/> <a href="'.$unsublink.'&amp;all=1">'.$unsublink.'&amp;all=1</a>.</p>';
        $message .= '<p>Also, do not reply to this email, since it is an automatic generated email.</p><p>Regards</p></body>';
        $message .= '</html>';
        mail($mail, $subject, $message, $headers);
    }
    return true;
}

/**
 * Unsubscribe from comments subscription via email
 */
function unsubscribe($email_b64, $article_id, $all)
{
    $email = base64_decode($email_b64);
    try {
        if ($all == 1) {
            // update all comments having $email
            $query = '
                UPDATE commentaires
                   SET bt_subscribe = 0
                 WHERE bt_email = ?';
            $array = array($email);
        } else {
            // update all comments having $email on $article
            $query = '
                UPDATE commentaires
                   SET bt_subscribe = 0
                 WHERE bt_email = ?
                       AND bt_article_id = ?';
            $array = array($email, $article_id);
        }
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute($array);
        return true;
    } catch (Exception $e) {
        die('Erreur BT 89867 : '.$e->getMessage());
    }
    return false;
}

/**
 * search query parsing (operators, exact matching, etc)
 */
function parse_search($q)
{
    if (preg_match('#^\s?"[^"]*"\s?$#', $q)) { // exact match
        $array_q = array('%'.str_replace('"', '', $q).'%');
    } else { // multiple words matchs
        $array_q = explode(' ', trim($q));
        foreach ($array_q as $i => $entry) {
            $array_q[$i] = '%'.$entry.'%';
        }
    }
    // uniq + reindex
    return array_values(array_unique($array_q));
}

/**
 * return http header
 * the function may be not exist on some server ...
 * http://php.net/manual/fr/function.http-response-code.php
 */
if (!function_exists('http_response_code')) {
    function http_response_code($code = null)
    {

        if ($code !== null) {
            return (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
        }

        switch ($code) {
            case 100:
                $text = 'Continue';
                break;
            case 101:
                $text = 'Switching Protocols';
                break;
            case 200:
                $text = 'OK';
                break;
            case 201:
                $text = 'Created';
                break;
            case 202:
                $text = 'Accepted';
                break;
            case 203:
                $text = 'Non-Authoritative Information';
                break;
            case 204:
                $text = 'No Content';
                break;
            case 205:
                $text = 'Reset Content';
                break;
            case 206:
                $text = 'Partial Content';
                break;
            case 300:
                $text = 'Multiple Choices';
                break;
            case 301:
                $text = 'Moved Permanently';
                break;
            case 302:
                $text = 'Moved Temporarily';
                break;
            case 303:
                $text = 'See Other';
                break;
            case 304:
                $text = 'Not Modified';
                break;
            case 305:
                $text = 'Use Proxy';
                break;
            case 400:
                $text = 'Bad Request';
                break;
            case 401:
                $text = 'Unauthorized';
                break;
            case 402:
                $text = 'Payment Required';
                break;
            case 403:
                $text = 'Forbidden';
                break;
            case 404:
                $text = 'Not Found';
                break;
            case 405:
                $text = 'Method Not Allowed';
                break;
            case 406:
                $text = 'Not Acceptable';
                break;
            case 407:
                $text = 'Proxy Authentication Required';
                break;
            case 408:
                $text = 'Request Time-out';
                break;
            case 409:
                $text = 'Conflict';
                break;
            case 410:
                $text = 'Gone';
                break;
            case 411:
                $text = 'Length Required';
                break;
            case 412:
                $text = 'Precondition Failed';
                break;
            case 413:
                $text = 'Request Entity Too Large';
                break;
            case 414:
                $text = 'Request-URI Too Large';
                break;
            case 415:
                $text = 'Unsupported Media Type';
                break;
            case 500:
                $text = 'Internal Server Error';
                break;
            case 501:
                $text = 'Not Implemented';
                break;
            case 502:
                $text = 'Bad Gateway';
                break;
            case 503:
                $text = 'Service Unavailable';
                break;
            case 504:
                $text = 'Gateway Time-out';
                break;
            case 505:
                $text = 'HTTP Version not supported';
                break;
            default:
                $code = 200;
                $text = 'OK';
                break;
        }

        $protocol = ((isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

        header($protocol .' '. $code .' '. $text);

        $GLOBALS['http_response_code'] = $code;

        return $code;
    }
}
