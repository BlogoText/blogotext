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



/*
 * JS to handle drag-n-drop : ondraging files on a <div> opens web request with POST individually for each file (in case many are draged-n-droped)
*
*/

function js_drag_n_drop_handle($a) {
	$max_file_size = min(return_bytes(ini_get('upload_max_filesize')), return_bytes(ini_get('post_max_size')));

	$sc = '

// upload next file
function uploadNext() {
	if (list.length) {
		document.getElementById(\'count\').classList.add(\'showed\');
		var nextFile = list.shift();
		if (nextFile.size >= '.$max_file_size.') {
			var respdiv = document.getElementById(nextFile.locId);
			respdiv.querySelector(\'.uploadstatus\').appendChild(document.createTextNode(\'File too big\'));
			respdiv.classList.remove(\'pending\');
			respdiv.classList.add(\'failure\');
			uploadNext();
		} else {
			var respdiv = document.getElementById(nextFile.locId);
			respdiv.querySelector(\'.uploadstatus\').textContent = \'Uploading\';
			uploadFile(nextFile);
		}
	} else {
		document.getElementById(\'count\').classList.remove(\'showed\');
		nbDraged = false;
		// reactivate the "required" attribute of file input
		document.getElementById(\'fichier\').required = true;
	}
}

';

	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}


// If article form has been changed, ask for confirmation before closing page/tab.


function js_alert_before_quit($a) {
$sc = '
var contenuLoad = document.getElementById("contenu").value;
window.addEventListener("beforeunload", function (e) {
	// From https://developer.mozilla.org/en-US/docs/Web/Reference/Events/beforeunload
	var confirmationMessage = \''.$GLOBALS['lang']['question_quit_page'].'\';
	if(document.getElementById("contenu").value == contenuLoad) { return true; };
	(e || window.event).returnValue = confirmationMessage || \'\' ;	//Gecko + IE
	return confirmationMessage;													// Webkit : ignore this.
});
';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}

/*
 *
 *
 *
 *
 *  Below: for RSS reader
 *
 *
 *
*/



function js_rss_add_feed($a) {
$sc = '
// show form for new rss feed
function addNewFeed() {
	var newLink = window.prompt(\''.$GLOBALS['lang']['rss_jsalert_new_link'].'\', \'\');
	var newFolder = window.prompt(\''.$GLOBALS['lang']['rss_jsalert_new_link_folder'].'\', \'\');
	var notifDiv = document.createElement(\'div\');
	// empty string : stops here
	if (!newLink) return false;

	// otherwise continu.
	var notifNode = document.getElementById(\'message-return\');
	loading_animation(\'on\');

	var xhr = new XMLHttpRequest();
	xhr.open(\'POST\', \'_rss.ajax.php\');
	xhr.onload = function() {

		var resp = this.responseText;
		// en cas d’erreur, arrête ; le message d’erreur est mis dans le #NotifNode
		if (resp.indexOf("Success") == -1) {
			loading_animation(\'off\');
			notifNode.innerHTML = resp;
			return false;
		}

		// recharge la page en cas de succès
		loading_animation(\'off\');
		notifNode.textContent = \'Success: please reload page.\';
		window.location.href = window.location.href.split("?")[0]+\'?msg=confirm_feed_ajout\';
		return false;

	};
	xhr.onerror = function(e) {
		loading_animation(\'off\');
		// adding notif
		notifDiv.textContent = \''.$GLOBALS['lang']['error_phpajax'].'\'+e.target.status;
		notifDiv.classList.add(\'no_confirmation\');
		document.getElementById(\'top\').appendChild(notifDiv);
	};
	// prepare and send FormData
	var formData = new FormData();
	formData.append(\'token\', token);
	formData.append(\'add-feed\', newLink);
	formData.append(\'add-feed-folder\', newFolder);
	xhr.send(formData);

	return false;

}
';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;

}



function js_rss_clean_db($a) {
	$sc = '
// demande confirmation pour supprimer les vieux articles.
function cleanList() {
	var notifDiv = document.createElement(\'div\');
	var reponse = window.confirm(\''.$GLOBALS['lang']['question_clean_rss'].'\');
	if (!reponse) return false;

	loading_animation(\'on\');

	var xhr = new XMLHttpRequest();
	xhr.open(\'POST\', \'_rss.ajax.php\', true);
	xhr.onload = function() {
		var resp = this.responseText;
		token = resp.substr(7, 40);
		if (resp.indexOf("Success") == 0) {
			// rebuilt array with only unread items
			var list = new Array();
			for (var i = 0, len = Rss.length ; i < len ; i++) {
				var item = Rss[i];
				if (!item.statut == 0) {
					list.push(item);
				}
			}
			Rss = list;
			rss_feedlist(Rss);

			// adding notif
			notifDiv.textContent = \''.$GLOBALS['lang']['confirm_feed_clean'].'\';
			notifDiv.classList.add(\'confirmation\');
			document.getElementById(\'top\').appendChild(notifDiv);

		} else {
			notifDiv.textContent = \'Error: \'+resp;
			notifDiv.classList.add(\'no_confirmation\');
			document.getElementById(\'top\').appendChild(notifDiv);
		}


		loading_animation(\'off\');
	};
	xhr.onerror = function(e) {
		loading_animation(\'off\');
		// adding notif
		notifDiv.textContent = \''.$GLOBALS['lang']['error_phpajax'].'\'+e.target.status;
		notifDiv.classList.add(\'confirmation\');
		document.getElementById(\'top\').appendChild(notifDiv);
	};

	// prepare and send FormData
	var formData = new FormData();
	formData.append(\'token\', token);
	formData.append(\'delete_old\', 1);
	xhr.send(formData);
	return false;
}
';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}


/*
 *
 *
 *
 * Below for comments processing
 *
*/

// deleting a comment
function js_comm_delete($a) {
$sc = '

function suppr_comm(button) {
	var notifDiv = document.createElement(\'div\');
	var reponse = window.confirm(\''.$GLOBALS['lang']['question_suppr_comment'].'\');
	var div_bloc = document.getElementById(button.parentNode.parentNode.parentNode.parentNode.id);
	div_bloc.classList.add(\'ajaxloading\');

	if (reponse == true) {
		var xhr = new XMLHttpRequest();
		xhr.open(\'POST\', \'commentaires.php\', true);

		xhr.onprogress = function() {
			div_bloc.classList.add(\'ajaxloading\');
		}

		xhr.onload = function() {
			var resp = this.responseText;
			if (resp.indexOf("Success") == 0) {
				csrf_token = resp.substr(7, 40);
				div_bloc.classList.add(\'deleteFadeOut\');
				div_bloc.style.height = div_bloc.offsetHeight+\'px\';
				div_bloc.addEventListener(\'animationend\', function(event){event.target.parentNode.removeChild(event.target);}, false);
				div_bloc.addEventListener(\'webkitAnimationEnd\', function(event){event.target.parentNode.removeChild(event.target);}, false);
				// adding notif
				notifDiv.textContent = \''.$GLOBALS['lang']['confirm_comment_suppr'].'\';
				notifDiv.classList.add(\'confirmation\');
				document.getElementById(\'top\').appendChild(notifDiv);
			} else {
				// adding notif
				notifDiv.textContent = this.responseText;
				notifDiv.classList.add(\'no_confirmation\');
				document.getElementById(\'top\').appendChild(notifDiv);
			}
			div_bloc.classList.remove(\'ajaxloading\');
		};
		xhr.onerror = function(e) {
			notifDiv.textContent = \''.$GLOBALS['lang']['error_comment_suppr'].'\'+e.target.status;
			notifDiv.classList.add(\'no_confirmation\');
			document.getElementById(\'top\').appendChild(notifDiv);
			div_bloc.classList.remove(\'ajaxloading\');
		};

		// prepare and send FormData
		var formData = new FormData();
		formData.append(\'token\', csrf_token);
		formData.append(\'_verif_envoi\', 1);
		formData.append(\'com_supprimer\', button.dataset.commId);
		formData.append(\'com_article_id\', button.dataset.commArtId);

		xhr.send(formData);

	}
	return reponse;
}

';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}

// hide/unhide a comm
function js_comm_activate($a) {
$sc = '

function activate_comm(button) {
	var notifDiv = document.createElement(\'div\');
	var div_bloc = document.getElementById(button.parentNode.parentNode.parentNode.parentNode.id);
	div_bloc.classList.toggle(\'ajaxloading\');

	var xhr = new XMLHttpRequest();
	xhr.open(\'POST\', \'commentaires.php\', true);

	xhr.onprogress = function() {
		div_bloc.classList.add(\'ajaxloading\');
	}

	xhr.onload = function() {
		var resp = this.responseText;
		if (resp.indexOf("Success") == 0) {
			csrf_token = resp.substr(7, 40);
			button.textContent = ((button.textContent === "'.$GLOBALS['lang']['activer'].'") ? "'.$GLOBALS['lang']['desactiver'].'" : "'.$GLOBALS['lang']['activer'].'" );
			div_bloc.classList.toggle(\'privatebloc\');

		} else {
			notifDiv.textContent = \''.$GLOBALS['lang']['error_comment_valid'].'\'+\' \'+resp;
			notifDiv.classList.add(\'no_confirmation\');
			document.getElementById(\'top\').appendChild(notifDiv);
		}
		div_bloc.classList.remove(\'ajaxloading\');
	};
	xhr.onerror = function(e) {
		notifDiv.textContent = \''.$GLOBALS['lang']['error_comment_valid'].'\'+e.target.status+\' (#com-activ-H28)\';
		notifDiv.classList.add(\'no_confirmation\');
		document.getElementById(\'top\').appendChild(notifDiv);
		div_bloc.classList.remove(\'ajaxloading\');
	};

	// prepare and send FormData
	var formData = new FormData();
	formData.append(\'token\', csrf_token);
	formData.append(\'_verif_envoi\', 1);


	formData.append(\'com_activer\', button.dataset.commId);
	formData.append(\'com_article_id\', button.dataset.commArtId);

	xhr.send(formData);

}

';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}





function js_red_button_event($a) {
$sc = '

function rmArticle(button) {
	if (window.confirm(\''.$GLOBALS['lang']['question_suppr_article'].'\')) {
		button.type=\'submit\';
		return true;
	}
	return false;
}

function rmFichier(button) {
	if (window.confirm(\''.$GLOBALS['lang']['question_suppr_fichier'].'\')) {
		button.type=\'submit\';
		return true;
	}
	return false;
}

function annuler(pagecible) {
	window.location = pagecible;
}

';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}




