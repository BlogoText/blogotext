<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2013 Timo Van Neerden <timo@neerden.eu>
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

