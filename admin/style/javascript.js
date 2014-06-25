"use strict";

/*
	on comment : reply link « @ » quotes le name.
*/

function reply(code) {
	var field = document.getElementById('form-commentaire').getElementsByTagName('textarea')[0];
	field.focus();
	if (field.value !== '') {
		field.value += 'n';
	}
	field.value += code;
	field.scrollTop = 10000;
	field.focus();
}


/*
	On login captcha : if the captcha is unreadable, this helps you reload the captcha
	without reloading the whole page (the other fields might been filed)
*/

function new_freecap() {
	if(document.getElementById) {
		thesrc = document.getElementById("freecap").src;
		thesrc = thesrc.substring(0,thesrc.lastIndexOf(".")+4);
		document.getElementById("freecap").src = thesrc+"?"+Math.round(Math.random()*100000);
	} else {
		alert("Sorry, cannot autoreload freeCap image\nSubmit the form and a new freeCap will be loaded");
	}
}


/*
	On article or comment writing: insert a BBCode Tag or a Unicode char.
*/

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
}


/*
	unfold comment edition bloc.
*/

function unfold(button) {
	var elem2hide = button.parentNode.parentNode.getElementsByClassName('comm-edit-hidden-bloc')[0];
	if (elem2hide.style.display !== 'block') {
		elem2hide.style.display = 'block';
	} else {
		elem2hide.style.display = 'none';
	}
}


/* 
	When a file is uploaded, the input containing the html/bbcode code is clicable.
	On clic, all text is selected.
*/

function SelectAllText(id) {
	document.getElementById(id).select();
}


/*
	JS: for image upload, switches between the FILE upload, URL upload and Drag'n'Drop
*/
function switchUploadForm(where) {
	var link = document.getElementById('click-change-form');
	var input = document.getElementById('fichier');

	if (input.type == "file") {
		link.innerHTML = link.dataset.langFile;
		input.placeholder = "http://example.com/image.png";
		input.type = "url";
		input.focus();
	}
	else {
		link.innerHTML = link.dataset.langUrl;
		input.type = "file";
		input.placeholder = null;
	}
	return false;
}


/*
	JS to add tags/labels ont links and articles, with autocomplete HTML5/Datalist support
*/

function insertCatTag(inputId, tag) {
	var field = document.getElementById(inputId);
	if (field.value !== '') {
		field.value += ', ';
	}
	field.value += tag;
}

// globals
var iField = document.getElementById('type_tags');
var oField = document.getElementById('selected');
var fField = document.getElementById('categories');

// si on presse Enter, on ajoute le tag courant à la liste <ul>

function chkHit(e) {
	var unicode = (e.keyCode) ? e.keyCode : e.charCode;
	if (unicode == 13) {
		moveTag;
		return false;
	}
	return true;
}

function moveTag() {
	// if something in the input field : enter == add word to list of tags.
	if (iField.value.length != 0) {
		oField.innerHTML += '<li class="tag"><span>'+iField.value+'</span><a href="javascript:void(0)" onclick="removeTag(this.parentNode)">×</a></li>';
		iField.value = '';
		return false;
	}
	// else : real submit : seek in the list of tags, extract the tags and submit these.
	else {
		var liste = oField.getElementsByTagName('li');
		var len = liste.length;
		var iTag = '';
		for (var i = 0 ; i<len ; i++) { iTag += liste[i].getElementsByTagName('span')[0].innerHTML+", "; }
		fField.value = iTag.substr(0, iTag.length-2);
		return true;
	}
}

//	removes the <li> tag that is clicked
function removeTag(tag) {
	oField.removeChild(tag);
	return false;
}


/*
	JS : images : on click tag button, shows the images in that folder
	and build the wall from all JSON data.
*/

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
	var buttons = document.getElementById('list-folders').childNodes;
	for (var i = 0, nbbut = buttons.length ; i < nbbut ; i++) {
		if (buttons[i].nodeName=="BUTTON") buttons[i].className = '';
	}
	document.getElementById(button).className = 'current';
}


function type_sort(type, button) {
	// finds the matching images
	var wall = document.getElementsByClassName('file_bloc');
	for (var i=0, sz = wall.length; i<sz; i++) {
		var file = wall[i];
		if ((file.getAttribute('data-type') != null) && file.getAttribute('data-type').search(type) != -1) {
			file.style.display = 'inline-block';
		} else {
			file.style.display = 'none';
		}
	}
	var buttons = document.getElementById('list-types').childNodes;
	for (var i = 0, nbbut = buttons.length ; i < nbbut ; i++) {
		if (buttons[i].nodeName=="BUTTON") buttons[i].className = '';
	}
	document.getElementById(button).className = 'current';
}







/*
	JS AJAX for remove a file in the list directly, w/o reloading the whole page
*/

// create and send form
function request_delete_form(id) {
	// prepare XMLHttpRequest
	document.getElementById(id).src = 'style/loading.gif';
	document.getElementById(id).style.borderColor = "transparent";
	var xhr = new XMLHttpRequest();
	xhr.open('POST', '_rmfichier.ajax.php');
	xhr.onload = function() {
		if (this.responseText == 'success') {
			document.getElementById('bloc_'.concat(id)).parentNode.removeChild(document.getElementById('bloc_'.concat(id)));
			for (var i = 0, len = curr_img.length ; i < len ; i++) {
				if (id == imgs.list[i].id) {
					imgs.list.splice( i , 1 );
					break;
				}
			}
			image_vignettes;

		} else {
			alert(this.responseText);
		}
	};

	// prepare and send FormData
	var formData = new FormData();  
	formData.append('supprimer', '1');
	formData.append('file_id', id);
	xhr.send(formData);
}




/*
	JS : Lazy load images (not sure if really needed in > 2.0.2.5)
*/

function lazy_load() {
	var inner = document.getElementById('hideblock');
	inner.innerHTML = inner.innerHTML.slice(4, -3);
}


/*
	JS : for slideshow : detects the → and ← keypress to change image.
*/

function checkKey(e) {
	if (!document.getElementById('slider')) return true;
	if (document.getElementById('slider').style.display != 'block') return true;
	e = e || window.event;
	var evt = document.createEvent("MouseEvents"); // créer un évennement souris
	evt.initMouseEvent("click", true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
	if (e.keyCode == '37') {
		// left
		var button = document.getElementById('slider-prev');
		button.dispatchEvent(evt);
	}
	else if (e.keyCode == '39') {
		// right
		var button = document.getElementById('slider-next');
		e.preventDefault();
		button.dispatchEvent(evt);
	}
	//e.preventDefault();
	return true;
}






/*
	in page maintenance : switch visibility of forms.
*/
if (window.location.hash == '#form_export' || window.location.hash == '#form_import' || window.location.hash == '#form_optimi') {
	var activeForm = window.location.hash.substr(1);
	document.getElementById(activeForm).style.display = 'block';
}

function switch_form(activeForm, activeButton) {
	var buttons = document.getElementById('list-switch-buttons').getElementsByTagName('button');
	for (var i=0, l=buttons.length; i<l; i++) buttons[i].className = '';
	activeButton.className = 'current';
	var form_export = document.getElementById('form_export');
	var form_import = document.getElementById('form_import');
	var form_optimi = document.getElementById('form_optimi');
	form_export.style.display = form_import.style.display = form_optimi.style.display = 'none';

	document.getElementById(activeForm).style.display = 'block';
}

function switch_export_type(activeForm) {
	var e_json = document.getElementById('e_json');
	var e_html = document.getElementById('e_html');
	var e_zip = document.getElementById('e_zip');
	e_json.style.display = e_html.style.display = e_zip.style.display = 'none';
	document.getElementById(activeForm).style.display = 'block';
}

function hide_forms(blocs) {
	var radios = document.getElementsByName(blocs);
	var e_json = document.getElementById('e_json');
	var e_html = document.getElementById('e_html');
	var e_zip = document.getElementById('e_zip');
	var checked = false;
	for (var i = 0, length = radios.length; i < length; i++) {
		if (!radios[i].checked) {
			document.getElementById('e_'+radios[i].value).innerHTML = '';
		}
	}
}

