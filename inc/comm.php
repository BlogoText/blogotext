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

/* generates the comment form, with params from the admin-side and the visiter-side */
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
            $form_cont['webpage'] = $GLOBALS['racine'];
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

    // comm sent without errors and will be saved to DB
/* 	elseif (isset($_POST['_verif_envoi'])) {
		header('Location: ?'.$_SERVER['QUERY_STRING'].'#erreurs'); // redirection anti repostage;
	}*/

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
