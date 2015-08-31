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


/* Transforms numbers in words */
function en_lettres($captchavalue) {
	switch($captchavalue) {
		case 0 : $lettres = $GLOBALS['lang']['0']; break;
		case 1 : $lettres = $GLOBALS['lang']['1']; break;
		case 2 : $lettres = $GLOBALS['lang']['2']; break;
		case 3 : $lettres = $GLOBALS['lang']['3']; break;
		case 4 : $lettres = $GLOBALS['lang']['4']; break;
		case 5 : $lettres = $GLOBALS['lang']['5']; break;
		case 6 : $lettres = $GLOBALS['lang']['6']; break;
		case 7 : $lettres = $GLOBALS['lang']['7']; break;
		case 8 : $lettres = $GLOBALS['lang']['8']; break;
		case 9 : $lettres = $GLOBALS['lang']['9']; break;
		default: $lettres = ""; break;
	}
	return $lettres;
}

function protect($text) {
	$return = htmlspecialchars(stripslashes(clean_txt($text)));
	return $return;
}


/* generates the comment form, with params from the admin-side and the visiter-side */
function afficher_form_commentaire($article_id, $mode, $erreurs='', $comm_id='') {
	$GLOBALS['form_commentaire'] = '';
	$p_auteur = (isset($_POST['auteur'])) ? protect($_POST['auteur']) : '';
	$p_email = (isset($_POST['email'])) ? protect($_POST['email']) : '';
	$p_webpage = (isset($_POST['webpage'])) ? protect($_POST['webpage']) : '';
	$p_comm = (isset($_POST['commentaire'])) ? protect($_POST['commentaire']) : '';

	if (isset($_POST['_verif_envoi']) and !empty($erreurs)) {
		$GLOBALS['form_commentaire'] = '<div id="erreurs"><strong>'.$GLOBALS['lang']['erreurs'].'</strong> :'."\n" ;
		$GLOBALS['form_commentaire'].= '<ul><li>'."\n";
		$GLOBALS['form_commentaire'].=  implode('</li><li>', $erreurs);
		$GLOBALS['form_commentaire'].=  '</li></ul></div>'."\n";
		$defaut = array(
			'auteur' => $p_auteur,
			'email' => $p_email,
			'webpage' => $p_webpage,
			'commentaire' => $p_comm,
		);

	} elseif (isset($mode) and $mode == 'admin') {
		if (empty($comm_id)) {
			$defaut = array(
				'auteur' => $GLOBALS['auteur'],
				'email' => $GLOBALS['email'],
				'webpage' => $GLOBALS['racine'],
				'commentaire' => '',
				);
		} else {
			$actual_comment = $comm_id;
			$defaut = array(
				'auteur' => protect($actual_comment['bt_author']),
				'email' => protect($actual_comment['bt_email']),
				'webpage' => protect($actual_comment['bt_webpage']),
				'commentaire' => htmlspecialchars($actual_comment['bt_wiki_content']),
				'status' => protect($actual_comment['bt_statut']),
				);
		}

	} elseif (isset($_POST['previsualiser'])) { // parses the comment, but does not save it in a file
		$defaut = array(
			'auteur' => $p_auteur,
			'email' => $p_email,
			'webpage' => $p_webpage,
			'commentaire' => $p_comm,
		);
		$comm['bt_content'] = formatage_commentaires($p_comm);
		$comm['bt_id'] = date('YmdHis');
		$comm['bt_author'] = $p_auteur;
		$comm['bt_email'] = $p_email;
		$comm['bt_webpage'] = $p_webpage;
		$comm['anchor'] = article_anchor($comm['bt_id']);
		$comm['bt_link'] = '';
		$comm['auteur_lien'] = ($comm['bt_webpage'] != '') ? '<a href="'.$comm['bt_webpage'].'" class="webpage">'.$comm['bt_author'].'</a>' : $comm['bt_author'];
		$GLOBALS['form_commentaire'] .= '<div id="erreurs"><ul><li>Prévisualisation&nbsp;:</li></ul></div>'."\n";
		$GLOBALS['form_commentaire'] .= '<div id="previsualisation">'."\n";
		$GLOBALS['form_commentaire'] .= conversions_theme_commentaire(file_get_contents($GLOBALS['theme_post_comm']), $comm);
		$GLOBALS['form_commentaire'] .= '</div>'."\n";
	} else {
		if (isset($_POST['_verif_envoi'])) {
			header('Location: ?'.$_SERVER['QUERY_STRING'].'#top'); // redirection anti repostage;
		}
		$auteur_c = (isset($_COOKIE['auteur_c'])) ? protect($_COOKIE['auteur_c']) : '' ;
		$email_c = (isset($_COOKIE['email_c'])) ? protect($_COOKIE['email_c']) : '' ;
		$webpage_c = (isset($_COOKIE['webpage_c'])) ? protect($_COOKIE['webpage_c']) : '' ;
		$defaut = array(
			'auteur' => $auteur_c,
			'email' => $email_c,
			'webpage' => $webpage_c,
			'commentaire' => '',
			'captcha' => '',
		);
	}

	// prelim vars for Generation of comment Form
	$required = ($GLOBALS['require_email'] == 1) ? 'required=""' : '';
	$cookie_checked = (isset($_COOKIE['cookie_c']) and $_COOKIE['cookie_c'] == 1) ? ' checked="checked"' : '';
	$subscribe_checked = (isset($_COOKIE['subscribe_c']) and $_COOKIE['subscribe_c'] == 1) ? ' checked="checked"' : '';

	// COMMENT FORM ON ADMIN SIDE : +always_open –captcha –previsualisation –verif
	if ($mode == 'admin') {
		$rand = '-'.substr(md5(rand(100,999)),0,5);
		// begin with some additional stuff on comment "edit".
		if (isset($actual_comment)) { // edit
			$form = "\n".'<form id="form-commentaire-'.$actual_comment['bt_id'].'" class="form-commentaire" method="post" action="'.basename($_SERVER['PHP_SELF']).'?'.$_SERVER['QUERY_STRING'].'#erreurs">'."\n";
			
			$form .= "\t".'<div class="comm-edit-hidden-bloc">'."\n";
			$form .= "\t".'<fieldset class="syst">'."\n";
			$form .= "\t\t".hidden_input('is_it_edit', 'yes');
			$form .= "\t\t".hidden_input('comment_id', $actual_comment['bt_id']);
			$form .= "\t\t".hidden_input('status', $actual_comment['bt_statut']);
			$form .= "\t\t".hidden_input('ID', $actual_comment['ID']);
			//$form .= "\t\t".hidden_input('token', $actual_comment['comm-token']);
			$form .= "\t".'</fieldset><!--end syst-->'."\n";
		} else {
			$form = "\n".'<form id="form-commentaire" class="form-commentaire" method="post" action="'.basename($_SERVER['PHP_SELF']).'?'.$_SERVER['QUERY_STRING'].'#erreurs" >'."\n";
		}
		$form .= "\t".'<fieldset class="field">'."\n";
		$form .= "\t\t".hidden_input('comment_article_id', $article_id);
		$form .= "\t".'<p class="formatbut">'."\n";
		$form .= "\t\t".'<button id="button01" class="but" type="button" title="'.$GLOBALS['lang']['bouton-gras'].'" onclick="insertTag(\'[b]\',\'[/b]\',\'commentaire'.$rand.'\');"><span></span></button>'."\n";
		$form .= "\t\t".'<button id="button02" class="but" type="button" title="'.$GLOBALS['lang']['bouton-ital'].'" onclick="insertTag(\'[i]\',\'[/i]\',\'commentaire'.$rand.'\');"><span></span></button>'."\n";
		$form .= "\t\t".'<button id="button03" class="but" type="button" title="'.$GLOBALS['lang']['bouton-soul'].'" onclick="insertTag(\'[u]\',\'[/u]\',\'commentaire'.$rand.'\');"><span></span></button>'."\n";
		$form .= "\t\t".'<button id="button04" class="but" type="button" title="'.$GLOBALS['lang']['bouton-barr'].'" onclick="insertTag(\'[s]\',\'[/s]\',\'commentaire'.$rand.'\');"><span></span></button>'."\n";
		$form .= "\t\t".'<span class="spacer"></span>'."\n";
		$form .= "\t\t".'<button id="button09" class="but" type="button" title="'.$GLOBALS['lang']['bouton-lien'].'" onclick="insertTag(\'[\',\'|http://]\',\'commentaire'.$rand.'\');"><span></span></button>'."\n";
		$form .= "\t\t".'<button id="button10" class="but" type="button" title="'.$GLOBALS['lang']['bouton-cita'].'" onclick="insertTag(\'[quote]\',\'[/quote]\',\'commentaire'.$rand.'\');"><span></span></button>'."\n";
		$form .= "\t\t".'<button id="button12" class="but" type="button" title="'.$GLOBALS['lang']['bouton-code'].'" onclick="insertTag(\'[code]\',\'[/code]\',\'commentaire'.$rand.'\');"><span></span></button>'."\n";
		$form .= "\t".'</p><!--end formatbut-->'."\n";
		$form .= "\t\t".'<textarea class="commentaire text" name="commentaire" required="" placeholder="Lorem Ipsum" id="commentaire'.$rand.'" cols="50" rows="10">'.$defaut['commentaire'].'</textarea>'."\n";
		$form .= "\t".'</fieldset>'."\n";

		$form .= "\t".'<fieldset class="infos">'."\n";

		$form .= "\t\t".'<span><label for="auteur'.$rand.'">'.$GLOBALS['lang']['label_dp_pseudo'].'</label><input type="text" name="auteur" id="auteur'.$rand.'" placeholder="John Doe" required value="'.$defaut['auteur'].'" size="25" class="text" /></span>'."\n";

		$form .= "\t\t".'<span><label for="email'.$rand.'">'.(($GLOBALS['require_email'] == 1) ? $GLOBALS['lang']['label_dp_email_required'] : $GLOBALS['lang']['label_dp_email']).'</label><input type="email" name="email" id="email'.$rand.'" placeholder="mail@example.com" '.$required.' value="'.$defaut['email'].'" size="25" class="text" /></span>'."\n";

		$form .= "\t\t".'<span><label for="webpage'.$rand.'">'.$GLOBALS['lang']['label_dp_webpage'].'</label><input type="url" name="webpage" id="webpage'.$rand.'" placeholder="http://www.example.com" value="'.$defaut['webpage'].'" size="25" class="text" /></span>'."\n";

		$form .= "\t\t".hidden_input('_verif_envoi', '1');
		$form .= "\t\t".hidden_input('token', new_token());
		if (isset($actual_comment)) { // edit
			$checked = ($actual_comment['bt_statut'] == '0') ? 'checked ' : '';

			$form .= "\t".'<label class="activercomm">'.$GLOBALS['lang']['label_comm_priv'].'<input type="checkbox" name="activer_comm" '.$checked.'/></label>'."\n";

			$form .= "\t".'</fieldset><!--end info-->'."\n";
			$form .= "\t".'<fieldset class="buttons">'."\n";
			$form .= "\t\t".hidden_input('ID', $actual_comment['ID']);
			$form .= "\t\t".'<p class="submit-bttns">';
			$form .= "\t\t\t".'<button class="submit white-square" type="button" onclick="unfold(this);">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
			$form .= "\t\t\t".'<input class="submit blue-square" type="submit" name="enregistrer" value="'.$GLOBALS['lang']['envoyer'].'" />'."\n";
			$form .= "\t\t".'</p>'."\n";
		} else {
			$form .= "\t".'</fieldset><!--end info-->'."\n";
			$form .= "\t".'<fieldset class="buttons">'."\n";
			$form .= "\t\t".'<p class="submit-bttns"><input class="submit blue-square" type="submit" name="enregistrer" value="'.$GLOBALS['lang']['envoyer'].'" /></p>'."\n";
		}
		$form .= "\t".'</fieldset><!--end buttons-->'."\n";
		$GLOBALS['form_commentaire'] .= $form;
		$GLOBALS['form_commentaire'] .= (isset($actual_comment)? "\t".'</div>'."\n": '').'</form>'."\n";

	// COMMENT ON PUBLIC SIDE
	} else {
		// ALLOW COMMENTS : OFF
		if (get_entry($GLOBALS['db_handle'], 'articles', 'bt_allow_comments', $article_id, 'return') == '0' or $GLOBALS['global_com_rule'] == '1') {
			$GLOBALS['form_commentaire'] .= '<p>'.$GLOBALS['lang']['comment_not_allowed'].'</p>'."\n";
		}

		// ALLOW COMMENTS : OFF
		else {
			// Formulaire commun
			$form = "\n".'<form id="form-commentaire" class="form-commentaire" method="post" action="'.'?'.$_SERVER['QUERY_STRING'].'#erreurs" >'."\n";

			$form .= "\t".'<fieldset class="field">'."\n";
			$form .= "\t".'<p class="formatbut">'."\n";
			$form .= "\t\t".'<button id="button01" type="button" title="'.$GLOBALS['lang']['bouton-gras'].'" onclick="insertTag(\'[b]\',\'[/b]\',\'commentaire\');"><span></span></button>'."\n";
			$form .= "\t\t".'<button id="button02" type="button" title="'.$GLOBALS['lang']['bouton-ital'].'" onclick="insertTag(\'[i]\',\'[/i]\',\'commentaire\');"><span></span></button>'."\n";
			$form .= "\t\t".'<button id="button03" type="button" title="'.$GLOBALS['lang']['bouton-soul'].'" onclick="insertTag(\'[u]\',\'[/u]\',\'commentaire\');"><span></span></button>'."\n";
			$form .= "\t\t".'<button id="button04" type="button" title="'.$GLOBALS['lang']['bouton-barr'].'" onclick="insertTag(\'[s]\',\'[/s]\',\'commentaire\');"><span></span></button>'."\n";
			$form .= "\t\t".'<span class="spacer"></span>'."\n";
			$form .= "\t\t".'<button id="button09" type="button" title="'.$GLOBALS['lang']['bouton-lien'].'" onclick="insertTag(\'[\',\'|http://]\',\'commentaire\');"><span></span></button>'."\n";
			$form .= "\t\t".'<button id="button10" type="button" title="'.$GLOBALS['lang']['bouton-cita'].'" onclick="insertTag(\'[quote]\',\'[/quote]\',\'commentaire\');"><span></span></button>'."\n";
			$form .= "\t\t".'<button id="button12" type="button" title="'.$GLOBALS['lang']['bouton-code'].'" onclick="insertTag(\'[code]\',\'[/code]\',\'commentaire\');"><span></span></button>'."\n";
			$form .= "\t".'</p><!--end formatbut-->'."\n";
			$form .= "\t\t".'<textarea class="commentaire" name="commentaire" required="" placeholder="'.$GLOBALS['lang']['label_commentaire'].'" id="commentaire" cols="50" rows="10">'.$defaut['commentaire'].'</textarea>'."\n";
			$form .= "\t".'</fieldset>'."\n";

			$form .= "\t".'<fieldset class="infos">'."\n";
			$form .= "\t\t".'<label>'.$GLOBALS['lang']['label_dp_pseudo'].'<input type="text" name="auteur" placeholder="John Doe" required="" value="'.$defaut['auteur'].'" size="25" class="text" /></label>'."\n";

			$form .= "\t\t".'<label>'.(($GLOBALS['require_email'] == 1) ? $GLOBALS['lang']['label_dp_email_required'] : $GLOBALS['lang']['label_dp_email']).'<input type="email" name="email" placeholder="mail@example.com" '.$required.' value="'.$defaut['email'].'" size="25" /></label>'."\n";

			$form .= "\t\t".'<label>'.$GLOBALS['lang']['label_dp_webpage'].'<input type="url" name="webpage" placeholder="http://www.example.com" value="'.$defaut['webpage'].'" size="25" /></label>'."\n";

			$form .= "\t\t".'<label>'.$GLOBALS['lang']['label_dp_captcha'].'<b>'.en_lettres($GLOBALS['captcha']['x']).'</b> &#x0002B; <b>'.en_lettres($GLOBALS['captcha']['y']).'</b> <input type="number" name="captcha" autocomplete="off" value="" class="text" /></label>'."\n";

			$form .= "\t\t".hidden_input('_token', $GLOBALS['captcha']['hash']);
			$form .= "\t\t".hidden_input('_verif_envoi', '1');
			$form .= "\t".'</fieldset><!--end info-->'."\n";
			$form .= "\t".'<fieldset class="cookie"><!--begin cookie asking -->'."\n";

			$form .= "\t\t".'<input class="check" type="checkbox" id="allowcookie" name="allowcookie"'.$cookie_checked.' />'.label('allowcookie', $GLOBALS['lang']['comment_cookie']).'<br/>'."\n";

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

