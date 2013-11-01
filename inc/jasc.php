<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden <ti-mo@myopera.com>
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***


function js_comm_reply($a) {
	$sc = 'function reply(code) {
	var field = document.getElementById(\'form-commentaire\').getElementsByTagName(\'textarea\')[0];
	field.focus();
	if (field.value !== \'\') {
		field.value += \'\n\';
	}
	field.value += code;
	field.scrollTop = 10000;
	field.focus();
}';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}


/*
 * On login captcha : if the captcha is unreadable, this helps you reload the captcha
 * without reloading the whole page (the other fields might been filed)
 *
*
*/
function js_reload_captcha($a) {
	$sc = '
function new_freecap() {
	if(document.getElementById) {
		thesrc = document.getElementById("freecap").src;
		thesrc = thesrc.substring(0,thesrc.lastIndexOf(".")+4);
		document.getElementById("freecap").src = thesrc+"?"+Math.round(Math.random()*100000);
	} else {
		alert("Sorry, cannot autoreload freeCap image\nSubmit the form and a new freeCap will be loaded");
	}
}';

	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

/*
 * inset BBCode tags into a form.
*
*/
function js_inserttag($a) {
	$sc = '
function insertTag(startTag, endTag, tag) {
	var field = document.getElementById(tag);
	var scroll = field.scrollTop;
	field.focus();
	var startSelection   = field.value.substring(0, field.selectionStart);
	var currentSelection = field.value.substring(field.selectionStart, field.selectionEnd);
	var endSelection     = field.value.substring(field.selectionEnd);
	if (currentSelection == "") { currentSelection = "TEXT"; }
	field.value = startSelection + startTag + currentSelection + endTag + endSelection;
	field.focus();
	field.setSelectionRange(startSelection.length + startTag.length, startSelection.length + startTag.length + currentSelection.length);
	field.scrollTop = scroll;
}

function insertChar(ch, tag) {
	var field = document.getElementById(tag);
	var scroll = field.scrollTop;
	field.focus();

	var bef_cur = field.value.substring(0, field.selectionStart);
	var aft_cur = field.value.substring(field.selectionEnd);
	field.value = bef_cur + ch + aft_cur;
	field.focus();
	field.setSelectionRange(bef_cur.length + ch.toString.length +1, bef_cur.length + ch.toString.length +1);
	field.scrollTop = scroll;
}';

	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}


/*
 * unfold blocs, sort of "spoiler" type button
*
*/
function js_unfold($a) { 
	$sc='
function unfold(button) {
	var elem2hide = button.parentNode.parentNode.getElementsByClassName(\'comm-edit-hidden-bloc\')[0];
	if (elem2hide.style.display !== \'block\') {
		elem2hide.style.display = \'block\';
	} else {
		elem2hide.style.display = \'none\';
	}
}';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}	

/* 
 * When a file is uploaded, the input containing the html/bbcode code is clicable.
 * On clic, all text is selected.
*
*/
function js_select_text_on_focus($a) {
	$sc = '
function SelectAllText(id) {
	document.getElementById(id).select();
}';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}


/*
 * JS : for image upload, switches between the FILE upload, URL upload and Drag'n'Drop
*/
function js_switch_upload_form($a) {
	$sc = '
function switchUploadForm(where) {
	var link = document.getElementById(\'click-change-form\');
	var input = document.getElementById(\'fichier\');

	if (input.type == "file") {
		link.innerHTML = link.dataset.langFile;
		input.placeholder = "http://example.com/image.png";
		input.type = "url";
		input.focus();
	}
	else {
		link.innerHTML = link.dataset.langUrl;
		input.type = "file";
	}
	return false;
}';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

/*
 * JS to add tags/labels ont links and articles.
*
*/

function js_addcategories($a) {
	$sc = '
function insertCatTag(inputId, tag) {
	var field = document.getElementById(inputId);
	if (field.value !== \'\') {
		field.value += \', \';
	}
	field.value += tag;
}';

	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

function js_addcategories_links($a) {
	$sc = '
function insertCatTagLink(inputId, tag) {
	var field = document.getElementById(inputId);
	if (field.value !== \'\') {
		field.value += \', \';
	}
	field.value += tag;
}

// globals
var iField = document.getElementById(\'type_tags\');
var oField = document.getElementById(\'selected\');
var fField = document.getElementById(\'categories\');

// si on presse Enter, on ajoute le tag courant à la liste <ul>

function chkHit(e) {
	var unicode = (e.keyCode) ? e.keyCode : e.charCode;
	if (unicode == 13) {
		moveTag;
		return false;
	}
}

function moveTag() {
	// if something in the input field : enter == add word to list of tags.
	if (iField.value.length != 0) {
		oField.innerHTML += \'<li class="tag"><span>\'+iField.value+\'</span><a href="javascript:void(0)" onclick="removeTag(this.parentNode)">×</a></li>\';
		iField.value = \'\';
		return false;
	}
	// else : real submit : seek in the list of tags, extract the tags and submit these.
	else {
		var liste = oField.getElementsByTagName(\'li\');
		var len = liste.length;
		var iTag = \'\';
		for (var i = 0 ; i<len ; i++) { iTag += liste[i].getElementsByTagName(\'span\')[0].innerHTML+", "; }
		fField.value = iTag.substr(0, iTag.length-2);
		return true;
	}
}

//	removes the <li> tag that is clicked
function removeTag(tag) {
	oField.removeChild(tag);
	return false;
}';


	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

/*
 * JS AJAX for remove a file in the list directly, w/o reloading the whole page
*
*/

function js_button_request_delete($a) {
	$sc = '

// create and send form
function request_delete_form(id) {
	// prepare XMLHttpRequest
	document.getElementById(id).src = \'style/loading.gif\';
	document.getElementById(id).style.borderColor = "transparent";
	var xhr = new XMLHttpRequest();
	xhr.open(\'POST\', \'_rmfichier.ajax.php\');
	xhr.onload = function() {
		if (this.responseText == \'success\') {
			document.getElementById(\'bloc_\'.concat(id)).parentNode.removeChild(document.getElementById(\'bloc_\'.concat(id)));
		} else {
			alert(this.responseText);
		}
	};

	// prepare and send FormData
	var formData = new FormData();  
	formData.append(\'supprimer\', \'1\');
	formData.append(\'file_id\', id);
	xhr.send(formData);
}
';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

/*
 * JS to handle drag-n-drop : ondraging files on a <div> opens web request with POST individualy for each file (in case many are draged-n-droped)
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

	if (nbDraged !== false) { nbDraged = false; nbDone = 0; }

	filelist = event.dataTransfer.files;
	if (!filelist || !filelist.length || list.length) return;
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
			document.getElementById(\'count\').innerHTML = \''.$GLOBALS['lang']['label_fichier'].': \'+nbDone+\'/\'+nbDraged;
		}
	}
}

';

	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

function js_lazyload_img($a) {
$sc = '
function lazy_load() {
	var inner = document.getElementById(\'hideblock\');
	inner.innerHTML = inner.innerHTML.slice(4, -3);
}
';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}

function js_show_slideshow($a) {
$sc = '

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
		ElemUlLi[0].innerHTML = \''.$GLOBALS['lang']['label_date'].' : \'+im.id.substring(0,4)+\'/\'+im.id.substring(4,6)+\'/\'+im.id.substring(6,8);
		ElemUlLi[1].innerHTML = \''.$GLOBALS['lang']['label_dim_img'].' : \'+img_width+\'×\'+img_height;
		ElemUlLi[2].innerHTML = \''.$GLOBALS['lang']['pref_desc'].' : \'+(im.desc||im.filename[1]);
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
curr_img = imgs.list.slice(0, 25);
var counter = 0;
var curr_max = curr_img.length-1;

// rebuilts the image wall.
function image_vignettes() {
	var wall = document.getElementsByClassName(\'image-wall\')[0];
	wall.innerHTML = \'\';
	for (var i = 0, len = curr_img.length ; i < len ; i++) {
		var img = curr_img[i];
		var div = document.createElement("div");
		div.className = \'image_bloc\';
		div.id = \'bloc_\'+img.id;
		div.innerHTML = \'<span class="spantop black"><a title="'.$GLOBALS['lang']['partager'].'" class="lien lien-shar" href="links.php?url=\'+img.filename[0]+\'">&nbsp;</a><a title="'.$GLOBALS['lang']['voir'].'" class="lien lien-voir" href="\'+img.filename[0]+\'">&nbsp;</a><a title="'.$GLOBALS['lang']['editer'].'" class="lien lien-edit" href="fichiers.php?file_id=\'+img.id+\'&amp;edit">&nbsp;</a><a title="'.$GLOBALS['lang']['supprimer'].'" class="lien lien-supr" href="#" onclick="request_delete_form(\'+img.id+\'); return false;" >&nbsp;</a></span><span class="spanbottom black"><span onclick="slideshow(\\\'start\\\', \'+i+\');"></span></span><img src="\'+img.filename[2]+\'" id="\'+img.id+\'" alt="\'+img.filename[1]+\'" />\';
		wall.appendChild(div);

	}
}
image_vignettes();

function folder_sort(folder, button) {

	var newlist = new Array();
	for(var k in imgs.list) {
		if (imgs.list[k].dossier.search(folder) != -1) {
			newlist.push(imgs.list[k]);
		}
	}
	// reattributes the new list (it’s a global)
	curr_img = newlist;
	curr_max = curr_img.length-1;

	// recreates the images wall with the new list
	image_vignettes();

	// styles on buttons
	var buttons = document.getElementById(\'list-folders\').childNodes;
	for (var i = 0, nbbut = buttons.length ; i < nbbut ; i++) {
		if (buttons[i].nodeName=="BUTTON") buttons[i].className = \'\';
	}
	document.getElementById(button).className = \'current\';
}


function type_sort(type, button) {
	// finds the matching images
	var wall = document.getElementsByClassName(\'file_bloc\');
	for (var i=0, sz = wall.length; i<sz; i++) {
		var file = wall[i];
		if ((file.getAttribute(\'data-type\') != null) && file.getAttribute(\'data-type\').search(type) != -1) {
			file.style.display = \'inline-block\';
		} else {
			file.style.display = \'none\';
		}
	}
	var buttons = document.getElementById(\'list-types\').childNodes;
	for (var i = 0, nbbut = buttons.length ; i < nbbut ; i++) {
		if (buttons[i].nodeName=="BUTTON") buttons[i].className = \'\';
	}
	document.getElementById(button).className = \'current\';
}


';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}



function js_detect_arrow_keys($a) {
$sc = '
document.onkeydown = checkKey;

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

}';
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

function js_switch_form_maintenant($a) {
$sc = '
function switch_form(activeForm, activeButton) {
	var buttons = document.getElementById(\'list-switch-buttons\').getElementsByTagName(\'button\');
	for (var i=0, l=buttons.length; i<l; i++) buttons[i].className = \'\';
	activeButton.className = \'current\';
	var form_export = document.getElementById(\'form_export\');
	var form_import = document.getElementById(\'form_import\');
	var form_optimi = document.getElementById(\'form_optimi\');
	form_export.style.display = form_import.style.display = form_optimi.style.display = \'none\';
	eval(activeForm).style.display = \'block\';
}


function switch_export_type(activeForm) {
	var e_json = document.getElementById(\'e_json\');
	var e_html = document.getElementById(\'e_html\');
	var e_zip = document.getElementById(\'e_zip\');
	e_json.style.display = e_html.style.display = e_zip.style.display = \'none\';
	eval(activeForm).style.display = \'block\';
}

function hide_forms(blocs) {
	var radios = document.getElementsByName(blocs);
	var e_json = document.getElementById(\'e_json\');
	var e_html = document.getElementById(\'e_html\');
	var e_zip = document.getElementById(\'e_zip\');
	var checked = false;
	for (var i = 0, length = radios.length; i < length; i++) {
		if (!radios[i].checked) {
			eval(\'e_\'+radios[i].value).innerHTML = \'\';
		}
	}
}

';
	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	} else {
		$sc = "\n".$sc."\n";
	}
	return $sc;
}

