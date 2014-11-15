<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2014 Timo Van Neerden <timo@neerden.eu>
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
	$max_file_size = return_bytes(ini_get('upload_max_filesize'));
	$sc = '

// variables
var result = document.getElementById(\'result\'); // text zone where informations about uploaded files are displayed
var list = []; // file list

// pour le input des fichiers publics ou privés.
function statut_image() {
	var val = document.getElementById(\'statut\').checked;
	return (val === false) ? \'\' : \'on\';
}
function folder_image() {
	return document.getElementById(\'dossier\').value;
}


var nbDraged = false;
var nbDone = 0;

// process bunch of files
function handleDrop(event) {

	document.getElementById(\'count\').style.paddingTop = \'20px\';
	document.getElementById(\'count\').style.background = \'url(style/loading.gif) center top no-repeat\';

	if (nbDraged !== false) { nbDraged = false; nbDone = 0; }

	var filelist = event.dataTransfer.files;
	if (!filelist || !filelist.length || list.length) return false;
	result.innerHTML += \'\';
	for (var i = 0; i < filelist.length && i < 500; i++) { // limit is for not having an infinite loop
		list.push(filelist[i]);
	}

	nbDraged = list.length;

	uploadNext();
	return false;
}

// upload file
function uploadFile(file) {
	// prepare XMLHttpRequest
	var xhr = new XMLHttpRequest();
	xhr.open(\'POST\', \'_dragndrop.ajax.php\');
	xhr.onload = function() {
		result.innerHTML = this.responseText+result.innerHTML;
		uploadNext();
	};
	xhr.onerror = function() {
		result.innerHTML = this.responseText+result.innerHTML;
		uploadNext();
	};
	// prepare and send FormData
	var formData = new FormData();
	formData.append(\'fichier\', file);
	formData.append(\'token\', document.getElementById(\'token\').value);
	document.getElementById(\'token\').parentNode.removeChild(document.getElementById(\'token\'));
	formData.append(\'statut\', statut_image());
	formData.append(\'description\', \'\');
	formData.append(\'nom_entree\', document.getElementById(\'nom_entree\').value);
	formData.append(\'dossier\', folder_image());
	xhr.send(formData);
}

// upload next file
function uploadNext() {
	nbDone++;
	if (list.length) {
		var nextFile = list.shift();
		if (nextFile.size >= '.$max_file_size.') {
			result.innerHTML += \'<div class="f">File too big</div>\';
			uploadNext();
		} else {
			uploadFile(nextFile);
			document.getElementById(\'count\').innerHTML = \''.$GLOBALS['lang']['label_dp_fichier'].'\'+nbDone+\'/\'+nbDraged;
		}
	} else {
		document.getElementById(\'count\').style.background = \'\';
	}
}

';

	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}


function js_show_slideshow($a) {
$sc = '
document.onkeydown = checkKey;
function slideshow(action, image) {
	if (action == \'close\') {
		document.getElementById(\'slider\').style.display = \'none\';
	}

	var ElemImg = document.getElementById(\'slider-img\');
	var ElemUlLi = document.getElementById(\'slider-img-infs\').getElementsByTagName(\'li\');

	var newImg = new Image();
	if (action == \'start\') { document.getElementById(\'slider\').style.display = \'block\'; counter = image; }
	if (action == \'first\') counter = 0;
	if (action == \'prev\') counter = Math.max(--counter, 0);
	if (action == \'next\') counter = Math.min(++counter, curr_max);
	if (action == \'last\') counter = curr_max;

	var box_height = document.getElementById(\'slider-box-img-wrap\').clientHeight;
	var box_width = document.getElementById(\'slider-box-img-wrap\').clientWidth;
	var img_height = curr_img[counter].height;
	var img_width = curr_img[counter].width;
	var ratio_w = Math.max(1, img_width/box_width);

	newImg.onload = function() {
		ElemImg.src = newImg.src;
		var im = curr_img[counter];
		ElemUlLi[0].innerHTML = \''.$GLOBALS['lang']['label_dp_date'].'\'+im.id.substring(0,4)+\'/\'+im.id.substring(4,6)+\'/\'+im.id.substring(6,8);
		ElemUlLi[1].innerHTML = \''.$GLOBALS['lang']['label_dp_dimensions'].'\'+img_width+\'×\'+img_height;
		ElemUlLi[2].innerHTML = \''.$GLOBALS['lang']['label_dp_description'].'\'+(im.desc||im.filename[1]);
		document.getElementById(\'slider-img-a\').href = \'?file_id=\'+im.id;
		ElemImg.style.marginTop = (Math.round((box_height - Math.min(img_height/ratio_w, box_height))/2))+\'px\';
	};

	newImg.onerror = function() {
		ElemImg.src = \'\';
		ElemImg.alt = \'Error Loading File\';
		ElemUlLi[0].innerHTML = ElemUlLi[1].innerHTML = ElemUlLi[2].innerHTML = \'Error Loading File\';
		document.getElementById(\'slider-img-a\').href = \'#\';
		ElemImg.style.marginTop = \'0\';
	};
	newImg.src = curr_img[counter].filename[0];
}

function checkKey(e) {
	if (document.getElementById(\'slider\').style.display != \'block\') return true;

	e = e || window.event;

	var evt = document.createEvent("MouseEvents"); // créer un évennement souris
	evt.initMouseEvent("click", true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);

	if (e.keyCode == \'37\') {
		// left
		var button = document.getElementById(\'slider-prev\');
	}
	else if (e.keyCode == \'39\') {
		// right
		var button = document.getElementById(\'slider-next\');
		e.preventDefault();
	}

	button.dispatchEvent(evt);
	e.preventDefault();

}
';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}


function js_folder_sort_img($a) {
$sc = '

// begins with the first 25 images
var curr_img = imgs.list.slice(0, 25);
var counter = 0;
var curr_max = curr_img.length-1;

// rebuilts the image wall.
function image_vignettes() {
	var wall = document.getElementsByClassName(\'image-wall\')[0];
	wall.innerHTML = \'\';
	for (var i = 0, len = curr_img.length ; i < len ; i++) {
		var img = curr_img[i];
		var div = document.createElement("div");
		div.classList.add(\'image_bloc\');
		div.id = \'bloc_\'+img.id;
		div.innerHTML = \'<span class="spantop black"><a title="'.$GLOBALS['lang']['partager'].'" class="lien lien-shar" href="links.php?url=\'+img.filename[0]+\'">&nbsp;</a><a title="'.$GLOBALS['lang']['voir'].'" class="lien lien-voir" href="\'+img.filename[0]+\'">&nbsp;</a><a title="'.$GLOBALS['lang']['editer'].'" class="lien lien-edit" href="fichiers.php?file_id=\'+img.id+\'&amp;edit">&nbsp;</a><a title="'.$GLOBALS['lang']['supprimer'].'" class="lien lien-supr" href="#" onclick="request_delete_form(\'+img.id+\'); return false;" >&nbsp;</a></span><span class="spanbottom black"><span onclick="slideshow(\\\'start\\\', \'+i+\');"></span></span><img src="\'+img.filename[2]+\'" id="\'+img.id+\'" alt="\'+img.filename[1]+\'" />\';
		wall.appendChild(div);

	}
}
image_vignettes();
';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}



function js_comm_question_suppr($a) {
$sc = '
function ask_suppr(button) {
	var reponse = window.confirm(\''.$GLOBALS['lang']['question_suppr_comment'].'\');
	if (reponse == true) {
		var form = button.parentNode.parentNode.getElementsByTagName(\'form\')[0];
		var submitButtons = form.getElementsByTagName(\'input\');
		for (var i = 0, nb = submitButtons.length ; i<nb ; i++) {
			if (submitButtons[i].name === \'enregistrer\') {
				submitButtons[i].name = \'supprimer_comm\';
				submitButtons[i].type = \'text\';
				break;
			}
		}
		form.submit();
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


function js_alert_before_quit($a) {
$sc = '
var contenuLoad = document.getElementById("contenu").value;
window.addEventListener("beforeunload", function (e) {
	// From https://developer.mozilla.org/en-US/docs/Web/Reference/Events/beforeunload
	var confirmationMessage = \''.$GLOBALS['lang']['question_quit_page'].'\';
	if(document.getElementById("contenu").value == contenuLoad) { confirmationMessage = null };
	(e || window.event).returnValue = confirmationMessage || \'\' ;	//Gecko + IE ; Gecko show popup if "null" but not if empty str,
	return confirmationMessage;													// Webkit.
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
 Below: for RSS */

function js_rss_json_list($a) {
$sc = '
var Rss = rss_entries.list;

// rebuilts the list.
function rss_feedlist(RssPosts) {
	if (Rss.length == 0) return false;
	var unread = 0;
	var postlist = document.getElementById(\'post-list\');
	postlist.innerHTML = \'\';
	for (var i = 0, len = RssPosts.length ; i < len ; i++) {
		var item = RssPosts[i];
		if (item.statut == 1) { unread++; }

		// new list element
		var li = document.createElement("li");
		li.id = \'i_\'+item.id;
		li.classList.add(\'li-post-bloc\');
		li.dataset.feedUrl = item.feed;
		li.onclick = function(){ return openItem(this); };
		if (item.statut == 0) { li.classList.add(\'read\'); }

		// new line with the title
		var title = document.createElement("p");
		title.innerHTML = \'<a href="\'+item.link+\'" target="_blank">\'+item.title+\'</a>\';
		title.title = item.title;
		title.classList.add(\'post-title\');

		// bloc with date + site name
		var date = document.createElement("div");
		date.classList.add(\'date\');
		date.innerHTML = item.date;

		var site = document.createElement("div");
		site.classList.add(\'site\');
		site.innerHTML = item.sitename;
	
		var datesite = document.createElement("div");
		datesite.classList.add(\'datesite\');
		datesite.appendChild(site);
		datesite.appendChild(date);

		// bloc with main content of feed in a comment (it’s uncomment when open, to defer media loading).
		var content = document.createElement("div");
		content.classList.add(\'rss-item-content\');
		var comment = document.createComment(item.content);
		content.appendChild(comment);

		var hr = document.createElement("hr");
		hr.classList.add(\'clearboth\');

		li.appendChild(title);
		li.appendChild(content);
		li.appendChild(hr);
		li.appendChild(datesite);

		postlist.appendChild(li);
	}

	document.getElementById(\'count-posts\').getElementsByTagName(\'button\')[0].innerHTML = unread+\' '.$GLOBALS['lang']['rss_label_unread'].'\';
	return false;

}
rss_feedlist(Rss);

';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}

function js_rss_sort_from_site($a) {
$sc = '
function sortSite(origine) {
	var listpost = Rss;
	var newList = new Array();
	var choosensite = origine.parentNode.dataset.feedurl;

	// create list of items matching the selected site
	for (var i = 0, len = listpost.length ; i < len ; i++) {
		var item = listpost[i];
		if (listpost[i].feed == choosensite) {
			newList.push(item);
		}
	}

	// highlight selected site
	document.getElementsByClassName(\'active-site\')[0].classList.remove(\'active-site\');
	for (var i = 0, liList = document.getElementById(\'feed-list\').getElementsByTagName(\'li\'), len = liList.length ; i < len ; i++) {
		if (liList[i].dataset.feedurl == choosensite) {
			liList[i].classList.add(\'active-site\');
			break;
		}
	}
	rss_feedlist(newList);
	window.location.hash = \'rss-list\';
	return false;
}

function sortFolder(origine) {
	var listpost = Rss;
	var newList = new Array();
	var choosenfolder = origine.parentNode.parentNode.dataset.folder;

	for (var i = 0, len = listpost.length ; i < len ; i++) {
		var item = listpost[i];
		if (listpost[i].folder == choosenfolder) {
			newList.push(item);
		}
	}
	// highlight selected folder
	if (document.getElementsByClassName(\'active-site\')[0]) document.getElementsByClassName(\'active-site\')[0].classList.remove(\'active-site\');
	for (var i = 0, liList = document.getElementById(\'feed-list\').getElementsByTagName(\'li\'), len = liList.length ; i < len ; i++) {
		if (liList[i].dataset.folder == choosenfolder) {
			liList[i].classList.add(\'active-site\');
			break;
		}
	}


	rss_feedlist(newList);
	window.location.hash = \'rss-list\';
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


function js_rss_refresh($a) {
$sc = '
function refresh_all_feeds(refreshLink) {
	// if refresh ongoing : abbord !
	if (refreshLink.dataset.refreshOngoing == 1) {
		return false;
	} else {
		refreshLink.dataset.refreshOngoing = 1;
	}

	var notifNode = document.getElementById(\'message-return\');
	loading_animation(\'on\');

	// prepare XMLHttpRequest
	var xhr = new XMLHttpRequest();
	xhr.open(\'POST\', \'_rss.ajax.php\', true);

	var glLength = 0;
	// feeds update gradualy. This counts the feeds that have been updated yet

	xhr.onprogress = function() {
		if (glLength != this.responseText.length) {
			
			var posSpace = (this.responseText.substr(0, this.responseText.length-1)).lastIndexOf(" ");


			notifNode.innerHTML = this.responseText.substr(posSpace);//+" "+glLength+" "+posSpace;
			glLength = this.responseText.length;
		}
	}
	xhr.onload = function() {
		var resp = this.responseText;

		// update status
		var nbNewFeeds = resp.substr(resp.indexOf("Success")+40+7);
		notifNode.innerHTML = nbNewFeeds+\' new feeds (please reload page)\';
		token = resp.substr(resp.indexOf("Success")+7, 40);

		// if new feeds, reload page.
		refreshLink.dataset.refreshOngoing = 0;
		loading_animation(\'off\');
		window.location.href = window.location.href.split("?")[0]+\'?msg=confirm_feed_update&nbnew=\'+nbNewFeeds;
		return false;
	};

	xhr.onerror = function() {
		notifNode.innerHTML = this.responseText;
		loading_animation(\'off\');
		refreshLink.dataset.refreshOngoing = 0;
	};

	// prepare and send FormData
	var formData = new FormData();
	formData.append(\'token\', token);
	formData.append(\'refresh_all\', 1);
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


function js_rss_openitem($a) {
$sc = '
function openItem(thisPost) {
	// on clic on open post : open link in new tab.
	if (thisPost.classList.contains(\'open-post\')) { return true; }
	// on clic on item, close the previous opened item
	var open_post = document.getElementById(\'post-list\').getElementsByClassName(\'open-post\')[0];
	if (open_post) open_post.classList.remove(\'open-post\');

	// open this post
	thisPost.classList.add(\'open-post\');

	// remove comments tag in content
	var content = thisPost.getElementsByClassName(\'rss-item-content\')[0];
	if (content.childNodes[0].nodeType == 8) {
		content.innerHTML = content.childNodes[0].data;
	}

	// jump to post (anchor + 50px)
	var rect = thisPost.getBoundingClientRect();
	var isVisible = ( (rect.top < 0) || (rect.bottom > window.innerHeight) ) ? false : true ;
	if (!isVisible) {
		window.location.hash = thisPost.id;
		window.scrollBy(0,-50);
	}

	markAsRead(\'post\', thisPost.id.substr(2));
	return false;
}

function openAllItems(button) {
	var postlist = document.getElementById(\'post-list\').getElementsByClassName(\'li-post-bloc\');
	if (openAllSwich == \'open\') {
		for (var i=0, size=postlist.length ; i<size ; i++) {
			postlist[i].classList.add(\'open-post\');
			// remove comments tag in content
			var content = postlist[i].getElementsByClassName(\'rss-item-content\')[0];
			if (content.childNodes[0] && content.childNodes[0].nodeType == 8) {
				content.innerHTML = content.childNodes[0].data;
			}
		}
		openAllSwich = \'close\';
		button.classList.add(\'unfold\');
	} else {
		for (var i=0, size=postlist.length ; i<size ; i++) {
			postlist[i].classList.remove(\'open-post\');
		}
		openAllSwich = \'open\';
		button.classList.remove(\'unfold\');
	}
	
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

function js_rss_show_unread_only($a) {
$sc = '
/* action for button « show only unread elements */
function showUnRead() {
	for (var i = 0, liList = document.getElementById(\'post-list\').getElementsByTagName(\'li\'), len = liList.length ; i < len ; i++) {
		var item = liList[i];
		if (item.classList.contains(\'read\')) {
			item.parentNode.removeChild(item); 
			i--;
		}
	}
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

function js_rss_loading_animation($a) {
$sc = '
// gif loading
function loading_animation(onoff) {
	var notifNode = document.getElementById(\'count-posts\');
	if (onoff == \'on\') {
		notifNode.style.background = \'url(style/loading.gif) no-repeat left center\';
		notifNode.style.paddingLeft = \'20px\';
	}
	else {
		notifNode.style.background = \'\';
		notifNode.style.paddingLeft = \'0px\';
	}
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

function js_rss_add_feed($a) {
$sc = '
// show form for new rss feed
function addNewFeed() {
	var newLink = window.prompt(\''.$GLOBALS['lang']['rss_jsalert_new_link'].'\', \'\');

	// empty string : stops here
	if (!newLink) return false;

	// otherwise continu.
	var notifNode = document.getElementById(\'message-return\');
	loading_animation(\'on\');

	var xhr = new XMLHttpRequest();
	xhr.open(\'POST\', \'_rss.ajax.php\');
	xhr.onload = function() {

		var resp = this.responseText;
		// en cas d’erreur, arrête ; le message d’erreur est mis dans le #count-posts
		if (resp.indexOf("Success") == -1) {
			loading_animation(\'off\');
			notifNode.innerHTML = resp;
			return false;
		}

		// recharge la page en cas de succès
		loading_animation(\'off\');
		notifNode.innerHTML = \'Success: please reload page.\';
		window.location.href = window.location.href.split("?")[0]+\'?msg=confirm_feed_ajout\';
		return false;

	};
	xhr.onerror = function(e) {
		loading_animation(\'off\');
		alert(\'Some JSON/AJAX error happened: \'+e.target.status);
	};
	// prepare and send FormData
	var formData = new FormData();
	formData.append(\'token\', token);
	formData.append(\'add-feed\', newLink);
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

function js_rss_open_folder($a) {
$sc = '
function hideFolder(btn) {
	btn.parentNode.parentNode.classList.toggle(\'open\');
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

function js_rss_mark_as_read($a) {

$sc = '
// mark as read code.
// $what is either "all", "site" for marking one feed as read, "folder", or "post" for marking just one ID as read, $url contains id, folder or feed url
function markAsRead(what, url) {
	var notifNode = document.getElementById(\'message-return\');
	var gCount = document.getElementById(\'count-posts\').getElementsByTagName(\'button\')[0];

	var xhr = new XMLHttpRequest();
	xhr.open(\'POST\', \'_rss.ajax.php\', true);

	// if all data is charged to be marked as read, ask confirmation.
	if (what == \'all\') {
		var retVal = confirm("Tous les éléments seront marqués comme lu ?");
		if (!retVal) {
			return false;
		}

		xhr.onload = function() {
			var resp = this.responseText;
			if (resp.indexOf("Success") == 0) {
				token = resp.substr(7, 40);

				var liList = document.getElementById(\'post-list\').getElementsByClassName(\'li-post-bloc\');
				for (var i = 0, len = liList.length ; i < len ; i++) { liList[i].classList.add(\'read\'); }
				// mark feed list items as containing 0 unread
				for (var i = 0, liList = document.getElementById(\'feed-list\').getElementsByTagName(\'li\'), len = liList.length ; i < len ; i++) {
					liList[i].classList.remove(\'feed-not-null\');
					liList[i].getElementsByTagName(\'span\')[0].innerHTML = \'0\';
				}

				gCount.innerHTML = gCount.innerHTML.replace(/^(\d+)( .+)$/, function(all,p1,p2){return \'0\'+p2;});
				// markitems as read in (var)Rss list.
				for (var i = 0, len = Rss.length ; i < len ; i++) { Rss[i].statut = 0; }
			} else { notifNode.innerHTML = resp; }
		};
	}

	else if (what == \'site\') {
		// mark all post from one url as read

		xhr.onload = function() {
			var resp = this.responseText;
			if (resp.indexOf("Success") == 0) {
				token = resp.substr(7, 40);
				// mark all items listed as "read"
				var liList = document.getElementById(\'post-list\').getElementsByClassName(\'li-post-bloc\');
				for (var i = 0, len = liList.length ; i < len ; i++) { liList[i].classList.add(\'read\'); }
				// mark row in feeds-list as containing 0 unread
				document.getElementsByClassName(\'active-site\')[0].classList.remove(\'feed-not-null\');
				var oldSiteCount = document.getElementsByClassName(\'active-site\')[0].getElementsByTagName(\'span\')[0].innerHTML;
				document.getElementsByClassName(\'active-site\')[0].getElementsByTagName(\'span\')[0].innerHTML = \'(0)\';
				gCount.innerHTML = gCount.innerHTML.replace(/^(\d+)( .+)$/, function(all,p1,p2){return \'0\'+p2;});

				// mark items as read in (var)Rss list.
				for (var i = 0, len = Rss.length ; i < len ; i++) { if (Rss[i].feed == url) { Rss[i].statut = 0; } }


				var liFolder = document.getElementsByClassName(\'active-site\')[0];
				var liCount = oldSiteCount.replace(/[()]/g, \'\');

				// remove X feeds in folder-count
				if (liFolder.parentNode.parentNode.dataset.folder) {
					var fCount = liFolder.parentNode.parentNode.getElementsByTagName(\'span\')[1];
					fCount.innerHTML = fCount.innerHTML.replace(/^\((\d+)\)$/, function(all,p1){ if (p1-liCount == 0){fCount.parentNode.classList.remove(\'feed-not-null\');} return \'(\'+(p1-liCount)+\')\';});
				}


			} else { notifNode.innerHTML = resp; }
		};
	}

	else if (what == \'folder\') {
		// mark all post from one folder as read
		xhr.onload = function() {
			var resp = this.responseText;
			if (resp.indexOf("Success") == 0) {
				token = resp.substr(7, 40);
				// mark all items listed as "read"
				var liList = document.getElementById(\'post-list\').getElementsByClassName(\'li-post-bloc\');
				for (var i = 0, len = liList.length ; i < len ; i++) { liList[i].classList.add(\'read\'); }
				// mark row in feeds-list as containing 0 unread
				document.getElementsByClassName(\'active-site\')[0].classList.remove(\'feed-not-null\');
				document.getElementsByClassName(\'active-site\')[0].getElementsByTagName(\'span\')[1].innerHTML = \'(0)\';
				gCount.innerHTML = gCount.innerHTML.replace(/^(\d+)( .+)$/, function(all,p1,p2){return \'0\'+p2;});

				// mark items as read in (var)Rss list.
				for (var i = 0, len = Rss.length ; i < len ; i++) { if (Rss[i].folder == url) { Rss[i].statut = 0; } }
			} else { notifNode.innerHTML = resp; }
		};
	}

	else if (what == \'post\') {

		// if post already as read but has been closed meanwhile, do not recount
		if (document.getElementById(\'i_\'+url).classList.contains(\'read\')) {
			xhr.abort();
			return false;
		}

		// mark post with specific URL/ID as read
		xhr.onload = function() {
			var resp = this.responseText;
			if (resp.indexOf("Success") == 0) {
				token = resp.substr(7, 40);
				// add read class on post
				document.getElementById(\'i_\'+url).classList.add(\'read\');
				document.getElementById(\'i_\'+url).classList.add(\'read\');
				var feedlink = document.getElementById(\'i_\'+url).dataset.feedUrl;
				for (var i = 0, liList = document.getElementById(\'feed-list\').getElementsByTagName(\'li\'), len = liList.length ; i < len ; i++) {
					// remove 1 unread in url list
					if (liList[i].dataset.feedurl == feedlink) {
						var sCount = liList[i].getElementsByTagName(\'span\')[0];
						sCount.innerHTML = sCount.innerHTML.replace(/^\((\d+)\)$/, function(all,p1){ if (p1-1 == 0){sCount.parentNode.classList.remove(\'feed-not-null\');} return \'(\'+(p1-1)+\')\';});
						// remove 1 unread in folder list
						if (liList[i].parentNode.parentNode.dataset.folder) {

							var fCount = liList[i].parentNode.parentNode.getElementsByTagName(\'span\')[1];
							fCount.innerHTML = fCount.innerHTML.replace(/^\((\d+)\)$/, function(all,p1){ if (p1-1 == 0){fCount.parentNode.classList.remove(\'feed-not-null\');} return \'(\'+(p1-1)+\')\';});
						}
						break;
					}
				}

				gCount.innerHTML = gCount.innerHTML.replace(/^(\d+)( .+)$/, function(all,p1,p2){return (p1-1)+p2;});

				// markitems as read in (var)Rss list.
				for (var i = 0, len = Rss.length ; i < len ; i++) {
					if (Rss[i].id == url) {
						Rss[i].statut = 0;
						break;
					}
				}
			} else { notifNode.innerHTML = resp; }
		};
	}

	xhr.onerror = function(e) {
		loading_animation(\'off\');
		alert(\'Some JSON/AJAX error happened: \'+e.target.status);
	};

	// prepare and send FormData
	var formData = new FormData();
	formData.append(\'token\', token);
	formData.append(\'mark-as-read\', what);
	formData.append(\'url\', url);
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
	var reponse = window.confirm(\'Tous les articles lus seront supprimés de la BDD ?\');
	if (!reponse) return false;

	loading_animation(\'on\');

	var xhr = new XMLHttpRequest();
	xhr.open(\'POST\', \'_rss.ajax.php\', true);
	xhr.onload = function() {
		var resp = this.responseText;
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

		} else { alert(resp); }


		loading_animation(\'off\');
	};
	xhr.onerror = function(e) {
		loading_animation(\'off\');
		alert(\'Some JSON/AJAX error happened: \'+e.target.status);
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




/* in feed config */
function js_rsscnf_marktoremove($a) {
$sc = '
function markAsRemove(link) {
	var li = link.parentNode.parentNode.parentNode;
	li.classList.add(\'to-remove\');
	li.getElementsByClassName(\'remove-feed\')[0].value = 0;
}

';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}

/* use key strokes to go to next item in list */

function js_rss_use_keyboard_shortcuts($a) {
$sc = '

document.onkeydown = testKey;

function testKey(e) {
	// no elements showed
	if (!document.getElementsByClassName(\'li-post-bloc\')[0]) return true;

	// no element selected : selects the first.
	if (!document.getElementsByClassName(\'open-post\')[0]) {
		var openPost = document.getElementsByClassName(\'li-post-bloc\')[0];
		var first = true;
	}
	// an element is selected, get it
	else {
		var openPost = document.getElementsByClassName(\'open-post\')[0];
		var first = false;
	}

	e = e || window.event;
	var evt = document.createEvent("MouseEvents"); // créer un évennement souris
	evt.initMouseEvent("click", true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
	if (e.keyCode == \'38\' && e.ctrlKey && openPost.previousElementSibling != null) {
		// up
		var elmt = openPost.previousElementSibling.getElementsByTagName(\'a\')[0];
		elmt.dispatchEvent(evt);
		e.preventDefault();
		window.location.hash = elmt.parentNode.parentNode.id;
		window.scrollBy(0,-50);
	}
	else if (e.keyCode == \'40\' && e.ctrlKey && openPost.nextElementSibling != null) {
		// down
		if (first) var elmt = openPost.getElementsByTagName(\'a\')[0];
		else var elmt = openPost.nextElementSibling.getElementsByTagName(\'a\')[0];
		elmt.dispatchEvent(evt);
		e.preventDefault();
		window.location.hash = elmt.parentNode.parentNode.id;
		window.scrollBy(0,-50);
	}
	return true;
}

';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}



