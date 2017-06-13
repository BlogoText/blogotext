// *** LICENSE ***
// This file is part of BlogoText.
// http://lehollandaisvolant.net/blogotext/
//
// 2006      Frederic Nassar.
// 2010-2016 Timo Van Neerden <timo@neerden.eu>
//
// BlogoText is free software.
// You can redistribute it under the terms of the MIT / X11 Licence.
//
// *** LICENSE ***

"use strict";

/*
    cancel button on forms.
*/

function annuler(pagecible)
{
    window.location = pagecible;
}


/*
    On article or comment writing: insert a BBCode Tag or a Unicode char.
*/

function insertTag(e, startTag, endTag)
{
    var seekField = e;
    while (!seekField.classList.contains('formatbut')) {
        seekField = seekField.parentNode;
    }
    while (!seekField.tagName || seekField.tagName != 'TEXTAREA') {
        seekField = seekField.nextSibling;
    }

    var field = seekField;
    var scroll = field.scrollTop;
    field.focus();
    var startSelection   = field.value.substring(0, field.selectionStart);
    var currentSelection = field.value.substring(field.selectionStart, field.selectionEnd);
    var endSelection     = field.value.substring(field.selectionEnd);
    if (currentSelection == "") {
        currentSelection = "TEXT"; }
    field.value = startSelection + startTag + currentSelection + endTag + endSelection;
    field.focus();
    field.setSelectionRange(startSelection.length + startTag.length, startSelection.length + startTag.length + currentSelection.length);
    field.scrollTop = scroll;
}

function insertChar(e, ch)
{
    var seekField = e;
    while (!seekField.classList.contains('formatbut')) {
        seekField = seekField.parentNode;
    }
    while (!seekField.tagName || seekField.tagName != 'TEXTAREA') {
        seekField = seekField.nextSibling;
    }

    var field = seekField;

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
    Used in file upload: converts bytes to kB, MB, GB…
*/
function humanFileSize(bytes)
{
    var e = Math.log(bytes)/Math.log(1e3)|0,
    nb = (e, bytes/Math.pow(1e3,e)).toFixed(1),
    unit = (e ? 'KMGTPEZY'[--e] : '') + 'B';
    return nb + ' ' + unit
}



/*
    in page maintenance : switch visibility of forms.
*/

function switch_form(activeForm)
{
    var form_export = document.getElementById('form_export'),
        form_import = document.getElementById('form_import'),
        form_optimi = document.getElementById('form_optimi');

    form_export.style.display = form_import.style.display = form_optimi.style.display = 'none';
    document.getElementById(activeForm).style.display = 'block';
}

function switch_export_type(activeForm)
{
    var e_json = document.getElementById('e_json'),
        e_html = document.getElementById('e_html'),
        e_zip = document.getElementById('e_zip'),
        e_active = document.getElementById(activeForm);

    e_json.style.display = e_html.style.display = e_zip.style.display = 'none';
    if (e_active) {
        e_active.style.display = 'block';
    }
}

function hide_forms(blocs)
{
    var radios = document.getElementsByName(blocs);
    var e_json = document.getElementById('e_json');
    var e_html = document.getElementById('e_html');
    var e_zip = document.getElementById('e_zip');
    var checked = false;
    for (var i = 0, length = radios.length; i < length; i++) {
        if (!radios[i].checked) {
            var cont = document.getElementById('e_'+radios[i].value);
            if (cont) {
                while (cont.firstChild) {
                    cont.removeChild(cont.firstChild);
                }
            }
        }
    }
}


function rmArticle(button)
{
    if (window.confirm(BTlang.questionSupprArticle)) {
        button.type= 'submit';
        return true;
    }
    return false;
}

function rmFichier(button)
{
    if (window.confirm(BTlang.questionSupprFichier)) {
        button.type='submit';
        return true;
    }
    return false;
}

/*
function annuler(pagecible)
{
    window.location = pagecible;
}
 */


// [POC]
class Notification {

    constructor() {
        // set box system
        this.container = document.createElement('div');
        this.box = document.createElement('div');
        this.content = document.createElement('div');
        this.container.classList.add('Notification');
        this.box.classList.add('Notification-box');
        this.content.classList.add('Notification-content');
        // Boxing boxes
        this.box.appendChild(this.content);
        this.container.appendChild(this.box);

        // init some vars
        this.btnCloseBar = null;
        this.btnClose = null;
        this.type = null;
        this.callbackOnClose = null;

        return this;
    }

    showLoadingBar(el)
    {
        if (typeof el == 'undefined') {
            el = this.box;
        } else {
            // dont break the target
            var bar = document.createElement('div');
            bar.classList.add('loading_bar_absolute');
            if (el.style.position == '') {
                el.style.position = 'relative';
            }
            el.appendChild(bar);
            var el = bar;
        }
        el.classList.add('loading_bar');
        el.classList.add('loadingOn');
        return this;
    }
    hideLoadingBar(el, ttl, callback)
    {
        if (typeof el == 'undefined') {
            el = this.box;
        } else {
            var bar = el.getElementsByClassName('loading_bar');
            if (bar.lenght == 0) {
                return this;
            }
            var el = bar[0];
        }

        if (typeof ttl == 'undefined') {
            el.classList.remove('loading_bar');
            el.classList.remove('loadingOn');
            if (this.type == 'dialog') {
                this.dialogSetPosition(self.box);
            }
            if (typeof callback === "function") {
                callback();
            }
            return this;
        }
        var self = this;
        setTimeout(function () {
            el.classList.remove('loading_bar');
            el.classList.remove('loadingOn');
            if (self.type == 'dialog') {
                self.dialogSetPosition(self.box);
            }
            if (typeof callback === "function") {
                callback();
            }
        }, ttl);
        return this;
    }

    setHtml(html)
    {
        this.content.innerHTML = html;
        return this;
    }
    setText(text)
    {
        this.content.textContent = text;
        return this;
    }
    /**
     * destroy
     */
    destroy(effect)
    {
        this.container.classList.add('Notification-destroy');
        if (typeof effect == 'undefined') {
            this.container.classList.add('Notification-destroy-'+effect);
        }
        var self = this;
        setTimeout(function () {
            self.container.parentNode.removeChild(self.container);
            if (self.btnClose != null) {
                self.btnClose.removeEventListener("click");
            }
        }, 1000);

        if (typeof self.callbackOnClose === "function") {
            self.callbackOnClose();
        }
    }

    addCloseTimer(ttl, effect, callback)
    {
        var self = this;
        setTimeout(function () {
            self.destroy(effect);
            if (typeof callback === "function") {
                callback();
            }
        }, ttl);
        return this;
    }
    addCloseButton(text)
    {
        this.btnCloseBar = document.createElement('div');
        this.btnCloseBtn = document.createElement('button');

        this.btnCloseBtn.innerHTML = text;
        this.btnCloseBtn.classList.add('submit');
        this.btnCloseBtn.classList.add('button-submit');
        this.btnCloseBar.classList.add('submit-bttns');
        this.btnCloseBar.classList.add('Notification-footer');

        this.btnCloseBar.appendChild(this.btnCloseBtn);
        this.box.appendChild(this.btnCloseBar);

        var self = this;
        this.btnCloseBtn.addEventListener("click", function (e) {
            e.preventDefault();
            self.destroy();
            return;
        }, false);
        return this;
    }

    merge(obj1, obj2)
    {
        var obj3 = {};
        for (var a in obj1) {
            obj3[a] = obj1[a];
        }
        for (var a in obj2) {
            obj3[a] = obj2[a];
        }
        return obj3;
    }

    offset(el)
    {
        // document.body.style.margin = 0;
        var rect = el.getBoundingClientRect();
        return {
            top: rect.top + (window.pageYOffset || document.documentElement.scrollTop),
            left: rect.left + (window.pageXOffset || document.documentElement.scrollLeft)
        }
    }

    // set the correct position
    dialogSetPosition(box)
    {
        var wW = window.innerWidth;
        // css style
        if (wW < 480) {
            return;
        }

        box.style.left  = ((wW - box.offsetWidth)/2) +'px';
        // box.style.right = ((wW - box.offsetWidth)/2) +'px';
    }

    onClose(callback)
    {
        if (typeof callback === "function") {
            this.callbackOnClose = callback;
        }
        return this;
    }

    // WIP
    insertAsBigToast()
    {
        this.type = 'bigtoast';
        this.BigToastContainer = document.getElementById('Notification-BigToast');
        if (this.BigToastContainer == null) {
            this.BigToastContainer = document.createElement('div');
            this.BigToastContainer.setAttribute("id", "Notification-BigToast");
            document.body.appendChild(this.BigToastContainer);
        }
        this.BigToastContainer.appendChild(this.container);
    }
    // WIP
    insertAsDialog()
    {
        var self = this;

        this.type = 'dialog';
        this.container.classList.add('Notification-dialog');
        document.body.appendChild(this.container);
        this.dialogSetPosition(this.box);

        window.addEventListener("scroll", function () {
            self.dialogSetPosition(self.box);
        }, false);
        window.addEventListener("resize", function () {
            self.dialogSetPosition(self.box);
        }, false);
    }
    // WIP
    insertAfter(insertAfter)
    {
        this.type = 'after';
        var parent = insertAfter.parentNode,
            next = insertAfter.nextSibling;
        if (next) {
            parent.insertBefore(this.container, next)
        } else {
            parent.appendChild(this.container)
        }
        return this;
    }
    // WIP
    insertSticker(stickTo, posCorrection)
    {
        this.type = 'sticker';
        var correction = this.merge({top:0,right:0,left:0,bottom:0,width:200,height:30}, posCorrection),
            stickToPosition = this.offset(stickTo);

        this.container.style.left = stickToPosition.left + stickTo.offsetWidth + correction.left + 'px';
        this.container.style.top = stickToPosition.top + correction.top + 'px';
        this.container.style.width = correction.width;
        this.container.classList.add('Notification-sticker');

        this.box.style.width = correction.width + 'px';
        this.content.style.width = correction.width + 'px';

        document.body.appendChild(this.container);
    }
}


// [POC] setTimeout for css animation
/**
 * si pas de 2nd timeout pour remettre la classe la checkbox "scintille" avant de réapparaitre
 */
function checkboxToggleReset(chk)
{
    setTimeout(function () {
        chk.classList.remove('checkbox-toggle');
        chk.removeAttribute('disabled');
        chk.removeAttribute('active');
        chk.removeAttribute('checked');
        chk.checked = false;
    }, 400);
    setTimeout(function () {
        chk.classList.add('checkbox-toggle');
    }, 400);
}


/**************************************************************************************************************************************
    COMM MANAGEMENT
**************************************************************************************************************************************/

/*
    on comment : reply link « @ » quotes le name.
*/

function reply(code)
{
    var field = document.querySelector('#form-commentaire textarea');
    field.focus();
    if (field.value !== '') {
        field.value += '\n';
    }
    field.value += code;
    field.scrollTop = 10000;
    field.focus();
}


/*
    unfold comment edition bloc.
*/

function unfold(button)
{
    var elemOnForground = document.querySelectorAll('.commentbloc.foreground');
    for (var i=0, len=elemOnForground.length; i<len; i++) {
        elemOnForground[i].classList.remove('foreground');
    }

    var elemToForground = button.parentNode.parentNode.parentNode.parentNode.parentNode;
    elemToForground.classList.toggle('foreground');

    elemToForground.getElementsByTagName('textarea')[0].focus();
}


// deleting a comment
function suppr_comm(button)
{
    var notifDiv = document.createElement('div');
    var reponse = window.confirm(BTlang.questionSupprComment);
    var div_bloc = button.parentNode.parentNode.parentNode.parentNode.parentNode;

    if (reponse == true) {
        div_bloc.classList.add('ajaxloading');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'commentaires.php', true);

        xhr.onprogress = function () {
            div_bloc.classList.add('ajaxloading');
        }

        xhr.onload = function () {
            var resp = this.responseText;
            if (resp.indexOf("Success") == 0) {
                csrf_token = resp.substr(7, 40);
                div_bloc.classList.add('deleteFadeOut');
                div_bloc.style.height = div_bloc.offsetHeight+'px';
                div_bloc.addEventListener('animationend', function (event) {
                    event.target.parentNode.removeChild(event.target);}, false);
                div_bloc.addEventListener('webkitAnimationEnd', function (event) {
                    event.target.parentNode.removeChild(event.target);}, false);
                // adding notif
                notifDiv.textContent = BTlang.confirmCommentSuppr;
                notifDiv.classList.add('confirmation');
                document.getElementById('top').appendChild(notifDiv);
            } else {
                // adding notif
                notifDiv.textContent = this.responseText;
                notifDiv.classList.add('no_confirmation');
                document.getElementById('top').appendChild(notifDiv);
            }
            div_bloc.classList.remove('ajaxloading');
        };
        xhr.onerror = function (e) {
            notifDiv.textContent = BTlang.errorCommentSuppr + e.target.status;
            notifDiv.classList.add('no_confirmation');
            document.getElementById('top').appendChild(notifDiv);
            div_bloc.classList.remove('ajaxloading');
        };

        // prepare and send FormData
        var formData = new FormData();
        formData.append('token', csrf_token);
        formData.append('_verif_envoi', 1);
        formData.append('com_supprimer', button.dataset.commId);
        formData.append('com_article_id', button.dataset.commArtId);

        xhr.send(formData);
    }
    return reponse;
}


// hide/unhide a comm
function activate_comm(button)
{
    var notifDiv = document.createElement('div');
    var div_bloc = button.parentNode.parentNode.parentNode.parentNode.parentNode;
    div_bloc.classList.toggle('ajaxloading');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'commentaires.php', true);

    xhr.onprogress = function () {
        div_bloc.classList.add('ajaxloading');
    }

    xhr.onload = function () {
        var resp = this.responseText;
        if (resp.indexOf("Success") == 0) {
            csrf_token = resp.substr(7, 40);
            button.textContent = ((button.textContent === BTlang.activer) ? BTlang.desactiver : BTlang.activer );
            div_bloc.classList.toggle('privatebloc');
        } else {
            notifDiv.textContent = BTlang.errorCommentValid + ' ' + resp;
            notifDiv.classList.add('no_confirmation');
            document.getElementById('top').appendChild(notifDiv);
        }
        div_bloc.classList.remove('ajaxloading');
    };
    xhr.onerror = function (e) {
        notifDiv.textContent = BTlang.errorCommentSuppr + ' ' + e.target.status + ' (#com-activ-H28)';
        notifDiv.classList.add('no_confirmation');
        document.getElementById('top').appendChild(notifDiv);
        div_bloc.classList.remove('ajaxloading');
    };

    // prepare and send FormData
    var formData = new FormData();
    formData.append('token', csrf_token);
    formData.append('_verif_envoi', 1);

    formData.append('com_activer', button.dataset.commId);
    formData.append('com_bt_id', button.dataset.commBtid);
    formData.append('com_article_id', button.dataset.commArtId);

    xhr.send(formData);
}


/**************************************************************************************************************************************
    ADD-ONS HANDLING
**************************************************************************************************************************************/

// show/hide for addons list
function addons_showhide_list()
{
    if ("querySelector" in document && "addEventListener" in window) {
        [].forEach.call(document.querySelectorAll("#modules div"), function (el) {
            el.style.display = "none";
        });

        [].forEach.call(document.querySelectorAll("#modules li"), function (el) {
            el.addEventListener("click",function (e) {
                // e.preventDefault();
                this.nextElementSibling.style.display = (this.nextElementSibling.style.display === "none") ? "" : "none";
                return;
            }, false);
        });
    }
}

// enabled/disable an addon
function addon_switch_enabled(button)
{
    var notifDiv = document.createElement('div');
    // [POC] Notification close to the checkox
    var Notif = new Notification();
    var parent = button.parentNode.parentNode;
    Notif.showLoadingBar(parent);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'addons.php', true);

    xhr.onload = function () {
        var resp = JSON.parse(this.responseText);
        if (resp.success == true) {
            Notif
                .setText('Done!')
                .addCloseTimer(1000)
                .insertSticker(
                    button.parentNode.getElementsByTagName("label")[0], // stick to, <check box have a dirty css tricks to hide the reel one, so we use the label instead
                    {left:1,width:42,top:-36} // some correction
                );
            Notif.hideLoadingBar(parent, 500);
        } else {
            Notif.hideLoadingBar(parent, 500);
            notifDiv.textContent = resp.message;
            notifDiv.classList.add('no_confirmation');
            document.getElementById('top').appendChild(notifDiv);
            checkboxToggleReset(button);
        }
        // refresh the token
        csrf_token = resp.token;
    };
    xhr.onerror = function (e) {
        notifDiv.textContent = e.target.status + ' (#mod-activ-F38)';
        notifDiv.classList.add('no_confirmation');
        document.getElementById('top').appendChild(notifDiv);
    };

    // prepare and send FormData
    var formData = new FormData();
    formData.append('token', csrf_token);
    formData.append('_verif_envoi', 1);
    formData.append('mod_activer', button.id);

    formData.append('addon_id', button.id.substr(7));
    formData.append('statut', ((button.checked) ? 'on' : ''));
    formData.append('format', 'ajax');

    xhr.send(formData);
}





/**************************************************************************************************************************************
    LINKS AND ARTICLE FORMS : TAGS HANDLING
**************************************************************************************************************************************/

/* Adds a tag to the list when we hit "enter" */
/* validates the tag and move it to the list */
function moveTag()
{
    var iField = document.getElementById('type_tags');
    var oField = document.getElementById('selected');
    var fField = document.getElementById('categories');

    // if something in the input field : enter == add word to list of tags.
    if (iField.value.length != 0) {
        oField.innerHTML += '<li class="tag"><span>'+iField.value+'</span><a href="javascript:void(0)" onclick="removeTag(this.parentNode)">×</a></li>';
        iField.value = '';
        iField.blur(); // blur+focus needed in Firefox 48 for some reason…
        iField.focus();
        return false;
    } // else : real submit : seek in the list of tags, extract the tags and submit these.
    else {
        var liste = oField.getElementsByTagName('li');
        var len = liste.length;
        var iTag = '';
        for (var i = 0; i<len; i++) {
            iTag += liste[i].getElementsByTagName('span')[0].innerHTML+", "; }
        fField.value = iTag.substr(0, iTag.length-2);
        return true;
    }
}

/* remove a tag from the list */
function removeTag(tag)
{
    tag.parentNode.removeChild(tag);
    return false;
}





/* for links : hide the FAB button when focus on link field (more conveniant for mobile UX) */
function hideFAB()
{
    if (document.getElementById('fab')) {
        document.getElementById('fab').classList.add('hidden');
    }
}
function unHideFAB()
{
    if (document.getElementById('fab')) {
        document.getElementById('fab').classList.remove('hidden');
    }
}

/* for several pages: eventlistener to show/hide FAB on scrolling (avoids FAB from beeing in the way) */
function scrollingFabHideShow()
{
    if ((document.body.getBoundingClientRect()).top > scrollPos) {
        unHideFAB();
    } else {
        hideFAB();
    }
    scrollPos = (document.body.getBoundingClientRect()).top;
}



/**************************************************************************************************************************************
    FILE UPLOADING : DRAG-N-DROP
**************************************************************************************************************************************/

/* Drag and drop event handlers */
function handleDragEnd(e)
{
    document.getElementById('dragndrop-area').classList.remove('fullpagedrag');
}

function handleDragLeave(e)
{
    if ('WebkitAppearance' in document.documentElement.style) { // Chromium old bug #131325 since 2013.
        if (e.pageX > 0 && e.pageY > 0) {
            return false;
        }
    }
    document.getElementById('dragndrop-area').classList.remove('fullpagedrag');
}

function handleDragOver(e)
{
    if (document.getElementById('dragndrop-area').classList.contains('fullpagedrag')) {
        return false;
    }

    var isFiles = false;
    // detects if drag content is actually files (it might be text, url… but only files are relevant here)
    if (e.dataTransfer.types.contains) {
        var isFiles = e.dataTransfer.types.contains("application/x-moz-file");
    } else if (e.dataTransfer.types) {
        var isFiles = (e.dataTransfer.types == 'Files') ? true : false;
    }

    if (isFiles) {
        document.getElementById('dragndrop-area').classList.add('fullpagedrag');
    } else {
        document.getElementById('dragndrop-area').classList.remove('fullpagedrag');
    }
}



/* switches between the FILE upload, URL upload and Drag'n'Drop */
function switchUploadForm(where)
{
    var link = document.getElementById('click-change-form');
    var input = document.getElementById('fichier');

    if (input.type == "file") {
        link.innerHTML = link.dataset.langFile;
        input.placeholder = "http://example.com/image.png";
        input.type = "url";
        input.focus();
    } else {
        link.innerHTML = link.dataset.langUrl;
        input.type = "file";
        input.placeholder = null;
    }
    return false;
}

/* Onclick tag button, shows the images in that folder and build the wall from all JSON data. */

function folder_sort(folder, button)
{

    var newlist = new Array();
    for (var k in imgs.list) {
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
    var buttons = document.getElementById('list-albums').childNodes;
    for (var i = 0, nbbut = buttons.length; i < nbbut; i++) {
        if (buttons[i].nodeName=="BUTTON") {
            buttons[i].className = '';
        }
    }
    document.getElementById(button).className = 'current';
}

/* Same as folder_sort(), but for filetypes (.doc, .xls, etc.) */

function type_sort(type, button)
{
    // finds the matching files
    var files = document.querySelectorAll('#file-list tbody tr');
    for (var i=0, sz = files.length; i<sz; i++) {
        var file = files[i];
        if ((file.getAttribute('data-type') != null) && file.getAttribute('data-type').search(type) != -1) {
            file.style.display = '';
        } else {
            file.style.display = 'none';
        }
    }
    var buttons = document.getElementById('list-types').childNodes;
    for (var i = 0, nbbut = buttons.length; i < nbbut; i++) {
        if (buttons[i].nodeName=="BUTTON") {
            buttons[i].className = '';
        }
    }
    document.getElementById(button).className = 'current';
}


/* for slideshow : detects the → and ← keypress to change image. */
function checkKey(e)
{
    if (!document.getElementById('slider')) {
        return true;
    }
    if (document.getElementById('slider').style.display != 'block') {
        return true;
    }
    e = e || window.event;
    var evt = document.createEvent("MouseEvents"); // créer un évennement souris
    evt.initMouseEvent("click", true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
    if (e.keyCode == '37') {
        // left
        var button = document.getElementById('slider-prev');
        button.dispatchEvent(evt);
    } else if (e.keyCode == '39') {
        // right
        var button = document.getElementById('slider-next');
        //e.preventDefault(); // ???
        button.dispatchEvent(evt);
    }
    return true;
}


/*  Images slideshow */
function slideshow(action, image)
{
    if (action == 'close') {
        document.getElementById('slider').style.display = 'none';
        window.removeEventListener('keydown', checkKey);
        return false;
    }

    window.addEventListener('keydown', checkKey);
    var isSlide = false;

    var ElemImg = document.getElementById('slider-img');
    if (!ElemImg) {
        return;
    }

    var oldCounter = counter;
    switch (action) {
        case 'start':
            document.getElementById('slider').style.display = 'block';
            counter = parseInt(image);
            break;

        case 'prev':
            counter = Math.max(counter-1, 0);
            isSlide = (oldCounter == counter) ? false : 'animSlideToRight';
            break;

        case 'next':
            counter = Math.min(++counter, curr_max);
            isSlide = (oldCounter == counter) ? false : 'animSlideToLeft';
            break;
    }

    if (isSlide) {
        ElemImg.classList.add(isSlide);
    }


    var newImg = new Image();
    newImg.onload = function () {
        var im = curr_img[counter];
        ElemImg.height = im.height;
        ElemImg.width = im.width;
        // description
        var icont = document.getElementById('infos-content');
        while (icont.firstChild) {
            icont.removeChild(icont.firstChild);}
        icont.appendChild(document.createTextNode(im.desc));
        // details
        var idet = document.getElementById('infos-details');
        while (idet.firstChild) {
            idet.removeChild(idet.firstChild);}
        // details :: name + size + weight
        var idetnam = document.createElement('dl');
        var idetnamDl = idetnam.appendChild(document.createElement('dt'));
            // name
            idetnamDl.appendChild(document.createElement('div').appendChild(document.createTextNode(im.filename[1])).parentNode);
            // size
            var idetnamDiv2 = idetnamDl.appendChild(document.createElement('div'));
            idetnamDiv2.appendChild(document.createElement('span').appendChild(document.createTextNode(im.width+' × '+im.height)).parentNode);
            // weight
            idetnamDiv2.appendChild(document.createElement('span').appendChild(document.createTextNode(humanFileSize(im.weight))).parentNode);

        // details :: Date
        var idetnamDl2 = idetnam.appendChild(document.createElement('dt'));
            // Date
            idetnamDl2.appendChild(document.createElement('div').appendChild(document.createTextNode(im.date[0])).parentNode);
            // Day + hour
            var idetnamDiv2 = idetnamDl2.appendChild(document.createElement('div'));
            idetnamDiv2.appendChild(document.createElement('span').appendChild(document.createTextNode(im.date[1])).parentNode);

        idet.appendChild(idetnam);
        ElemImg.src = newImg.src;
        ElemImg.classList.remove('loading');
    };

    newImg.onerror = function () {
        ElemImg.src = '';
        ElemImg.alt = 'Error Loading File';
        ElemUlLi[0].innerHTML = ElemUlLi[1].innerHTML = ElemUlLi[2].innerHTML = 'Error Loading File';
        document.getElementById('slider-img-a').href = '#';
        ElemImg.style.marginTop = '0';
    };

    if (isSlide) {
        ElemImg.addEventListener('animationend', function () {
            ElemImg.src = '';
            newImg.src = curr_img[counter].filename[3];
            assingButtons(curr_img[counter]);
            ElemImg.classList.remove(isSlide);
        });
    } else {
        ElemImg.src = '';
        if (curr_img[counter]) {
            newImg.src = curr_img[counter].filename[3];
            assingButtons(curr_img[counter]);
        }
    }

}

/* Assigne the events on the buttons from the slideshow */
function assingButtons(file)
{
    // dl button/link
    var dl = document.getElementById('slider-nav-dl');
    document.getElementById('slider-nav-dl-link').href = file.filename[3];

    // share button
    document.getElementById('slider-nav-share-link').href = 'links.php?url='+file.filename[0];

    // infos button
    document.getElementById('slider-nav-infos').onclick = function () {
        document.getElementById('slider-main-content').classList.toggle('infos-on'); };

    // edit button
    document.getElementById('slider-nav-edit-link').href = '?file_id='+file.id;

    // suppr button
    document.getElementById('slider-nav-suppr').dataset.id = file.id;
    document.getElementById('slider-nav-suppr').onclick = currImageDelUpdate;
    function currImageDelUpdate(event)
    {
        request_delete_form(event.target.dataset.id);
        this.removeEventListener('click', currImageDelUpdate);
    };
}

function triggerClick(el)
{
    var evt = document.createEvent("MouseEvents");
    evt.initMouseEvent("click", true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
    el.dispatchEvent(evt);
}


/* JS AJAX for remove a file in the list directly, w/o reloading the whole page */

// create and send form
function request_delete_form(id)
{
    if (!window.confirm('Ce fichier sera supprimé définitivement')) {
        return false;
    }

    var slider = document.getElementById('slider-img');
    if (slider) {
        slider.classList.add('loading');
    }

    // prepare XMLHttpRequest
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '_rmfichier.ajax.php');
    xhr.onload = function () {
        if (this.responseText == 'success') {
            // remove tile of the deleted image
            document.getElementById('bloc_'.concat(id)).parentNode.removeChild(document.getElementById('bloc_'.concat(id)));
            // remove image from index
            var globalFlagRem = false, currentFlagRem = false;
            for (var i = 0, len = curr_img.length; i < len; i++) {
                if (id == imgs.list[i].id) {
                    imgs.list.splice(i , 1);
                    globalFlagRem = true;
                }
                if (id == curr_img[i].id) {
                    curr_img.splice(i , 1);
                    currentFlagRem = true;
                    curr_max--;
                }
                // if both lists have been updated, break to avoid useless loops.
                if (globalFlagRem && currentFlagRem) {
                    break;
                }
            }
            // rebuilt image wall
            image_vignettes();
            // go prev image in slideshow
            slideshow('prev', counter);
        } else {
            alert(this.responseText+' '+id);
        }
    };

    // prepare and send FormData
    var formData = new FormData();
    formData.append('supprimer', '1');
    formData.append('file_id', id);
    xhr.send(formData);
}



/* This builts the wall with image-blocks. The data is gathered from Json data. */
function image_vignettes()
{
    // empties the existing wall (using while() and removeChild is actually much faster than “innerHTML = ""”
    if (!document.getElementById('image-wall')) {
        return };
    var wall = document.getElementById('image-wall');
    while (wall.firstChild) {
        wall.removeChild(wall.firstChild);}
    // populates the wall with images in $curr_img (sorted by folder_sort())
    for (var i = 0, len = curr_img.length; i < len; i++) {
        var img = curr_img[i];
        var div = document.createElement('div');
        div.classList.add('image_bloc');
        div.id = 'bloc_'+img.id;

        var spanBottom = document.createElement('span');
            spanBottom.classList.add('spanbottom');

        var spanSlide = document.createElement('span');
            spanSlide.dataset.i = i;
            spanSlide.addEventListener('click', function (event) {
                slideshow('start', event.target.dataset.i);});
            spanBottom.appendChild(spanSlide);

            div.appendChild(spanBottom);

            var newImg = new Image();

            newImg.onload = function () {
                newImg.id = img.id;
                newImg.alt = img.filename[1];
            }
        div.appendChild(newImg);
        wall.appendChild(div);
        newImg.src = img.filename[2];
    }
}


// process bunch of files
function handleDrop(event)
{
    var result = document.getElementById('result');
    document.getElementById('dragndrop-area').classList.remove('fullpagedrag');
    if (nbDraged === false) {
        nbDone = 0; }
    // detects if drag contains files.
    if (event.dataTransfer.types.contains) {
        var isFiles = event.dataTransfer.types.contains("application/x-moz-file");
    } else if (event.dataTransfer.types) {
        var isFiles = (event.dataTransfer.types == 'Files') ? true : false;
    }

    if (!isFiles) {
        event.preventDefault(); return false; }

    var filelist = event.dataTransfer.files;
    if (!filelist || !filelist.length) {
        event.preventDefault(); return false; }

    for (var i = 0, nbFiles = filelist.length; i < nbFiles && i < 500; i++) { // limit is for not having an infinite loop
        var rand = 'i_'+Math.random()
        filelist[i].locId = rand;
        list.push(filelist[i]);
        var div = document.createElement('div');
        var fname = document.createElement('span');
            fname.classList.add('filename');
            fname.textContent = escape(filelist[i].name);
        var flink = document.createElement('a');
            flink.classList.add('filelink');
        var fsize = document.createElement('span');
            fsize.classList.add('filesize');
            fsize.textContent = '('+humanFileSize(filelist[i].size)+')';

        var fstat = document.createElement('span');
            fstat.classList.add('uploadstatus');
            fstat.textContent = 'Ready';

        div.appendChild(fname);
        div.appendChild(flink);
        div.appendChild(fsize);
        div.appendChild(fstat);
        div.classList.add('pending');
        div.classList.add('fileinfostatus');
        div.id = rand;

        result.appendChild(div);
    }
    nbDraged = list.length;
    // deactivate the "required" attribute of file (since no longer needed)
    document.getElementById('fichier').required = false;
    event.preventDefault();
}

// OnSubmit for files dragNdrop.
function submitdnd(event)
{
    // files have been dragged (means also that this is not a regulat file submission)
    if (nbDraged != 0) {
        // proceed to upload
        uploadNext();
        event.preventDefault();
    }
}

// upload file
function uploadFile(file)
{
    // prepare XMLHttpRequest
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '_dragndrop.ajax.php');

    xhr.onload = function () {
        var respdiv = document.getElementById(file.locId);
        // need "try/catch/finally" because of "JSON.parse", that might return errors (but should not, since backend is clean)
        try {
            var resp = JSON.parse(this.responseText);
            respdiv.classList.remove('pending');

            if (resp !== null) {
                // renew token
                document.getElementById('token').value = resp.token;

                respdiv.querySelector('.uploadstatus').innerHTML = resp.status;

                if (resp.status == 'success') {
                    respdiv.classList.add('success');
                    respdiv.querySelector('.filelink').href = resp.url;
                    respdiv.querySelector('.uploadstatus').innerHTML = 'Uploaded';
                    // replace file name with a link
                    respdiv.querySelector('.filelink').innerHTML = respdiv.querySelector('.filename').innerHTML;
                    respdiv.removeChild(respdiv.querySelector('.filename'));
                } else {
                    respdiv.classList.add('failure');
                    respdiv.querySelector('.uploadstatus').innerHTML = 'Upload failed';
                }

                nbDone++;
                document.getElementById('count').innerHTML = +nbDone+'/'+nbDraged;
            } else {
                respdiv.classList.add('failure');
                respdiv.querySelector('.uploadstatus').innerHTML = 'PHP or Session error';
            }
        } catch (e) {
            console.log(e);
        } finally {
            uploadNext();
        }

    };

    xhr.onerror = function () {
        uploadNext();
    };

    // prepare and send FormData
    var formData = new FormData();
    formData.append('token', document.getElementById('token').value);

    formData.append('fichier', file);
    formData.append('statut', ((document.getElementById('statut').checked === false) ? '' : 'on'));

    formData.append('description', document.getElementById('description').value);
    formData.append('nom_entree', document.getElementById('nom_entree').value);
    formData.append('dossier', document.getElementById('dossier').value);
    xhr.send(formData);
}


// upload next file
function uploadNext()
{
    if (list.length) {
        document.getElementById('count').classList.add('spinning');
        var nextFile = list.shift();
        if (nextFile.size >= BTlang.maxFilesSize) {
            var respdiv = document.getElementById(nextFile.locId);
            respdiv.querySelector('.uploadstatus').appendChild(document.createTextNode('File too big'));
            respdiv.classList.remove('pending');
            respdiv.classList.add('failure');
            uploadNext();
        } else {
            var respdiv = document.getElementById(nextFile.locId);
            respdiv.querySelector('.uploadstatus').textContent = 'Uploading';
            uploadFile(nextFile);
        }
    } else {
        document.getElementById('count').classList.remove('spinning');
        nbDraged = false;
        // reactivate the "required" attribute of file input
        document.getElementById('fichier').required = true;
    }
}


/**************************************************************************************************************************************
    RSS PAGE HANDLING
**************************************************************************************************************************************/

// animation loading (also used in images wall/slideshow)
function loading_animation(onoff)
{
    var notifNode = document.getElementById('counter');
    if (onoff == 'on') {
        notifNode.style.display = 'inline-block';
    } else {
        notifNode.style.display = 'none';
    }
    return false;
}

/* open-close rss-folder */
function hideFolder(btn)
{
    btn.parentNode.parentNode.classList.toggle('open');
    return false;
}

/* open rss-item */
function openItem(thisPostLink)
{
    var thisPost = thisPostLink.parentNode.parentNode;
    // on clic on open post : open link in new tab.
    if (thisPost.classList.contains('open-post')) {
        return true; }
    // on clic on item, close the previous opened item
    var open_post = document.querySelector('#post-list .open-post');
    if (open_post) {
        open_post.classList.remove('open-post');
    }

    // open this post
    thisPost.classList.add('open-post');

    // remove comments tag in content
    var content = thisPost.querySelector('.rss-item-content');
    if (content.childNodes[0].nodeType == 8) {
        content.innerHTML = content.childNodes[0].data;
    }

    // jump to post (anchor + 30px)
    var rect = thisPost.getBoundingClientRect();
    var isVisible = ( (rect.top < 0) || (rect.bottom > window.innerHeight) ) ? false : true ;
    if (!isVisible) {
        window.location.hash = thisPost.id;
        window.scrollBy(0, -120);
    }

    // mark as read in DOM and saves for mark as read in DB
    if (!thisPost.classList.contains('read')) {
        markAsRead('post', thisPost.id.substr(2));
        addToReadQueue(thisPost.id.substr(2));
    }

    return false;
}

function favPost(thisPostLink)
{
    var favCount = document.querySelector('#favs-post-counter');

    var thisPost = thisPostLink.parentNode.parentNode.parentNode;

    sendMarkFavRequest(thisPost.id);
    // mark as fav in DOM and on screen
    thisPostLink.dataset.isFav = 1 - parseInt(thisPostLink.dataset.isFav);
    favCount.dataset.nbrun = ( parseInt(favCount.dataset.nbrun) + ((thisPostLink.dataset.isFav == 1) ? 1 : -1 ) );
    favCount.firstChild.nodeValue = '('+favCount.dataset.nbrun+')';
    // mark as fav in var Rss
    for (var i = 0, len = Rss.length; i < len; i++) {
        if (Rss[i].id == thisPost.id.substr(2)) {
            Rss[i].fav = thisPostLink.dataset.isFav;
            break;
        }
    }
    return false;
}

/* adding an element to the queue of items that have been read (before syncing them) */
function addToReadQueue(elem)
{
    readQueue.count++;
    readQueue.urlList.push(elem);

    // if 10 items in queue, send XHR request and reset list to zero.
    if (readQueue.count == 10) {
        sendMarkReadRequest('postlist', JSON.stringify(readQueue.urlList), true);
        readQueue.urlList = [];
        readQueue.count = 0;
    }

}

/* Open all the items to make the visible, but does not mark them as read */
function openAllItems(button)
{
    var postlist = document.querySelectorAll('#post-list .li-post-bloc');
    if (openAllSwich == 'open') {
        for (var i=0, size=postlist.length; i<size; i++) {
            postlist[i].classList.add('open-post');
            // remove comments tag in content
            var content = postlist[i].querySelector('.rss-item-content');
            if (content.childNodes[0] && content.childNodes[0].nodeType == 8) {
                content.innerHTML = content.childNodes[0].data;
            }
        }
        openAllSwich = 'close';
        button.classList.add('unfold');
    } else {
        for (var i=0, size=postlist.length; i<size; i++) {
            postlist[i].classList.remove('open-post');
        }
        openAllSwich = 'open';
        button.classList.remove('unfold');
    }
    return false;
}

// Rebuilts the whole list of posts..
function rss_feedlist(RssPosts)
{
    if (Rss.length == 0) {
        return false;
    }
    // empties the actual list
    if (document.getElementById('post-list')) {
        var oldpostlist = document.getElementById('post-list');
        oldpostlist.parentNode.removeChild(oldpostlist);
    }

    var postlist = document.createElement('ul');
    postlist.id = 'post-list';

    // populates the new list
    for (var i = 0, unread = 0, len = RssPosts.length; i < len; i++) {
        var item = RssPosts[i];
        if (item.statut == 1) {
            unread++; }

        // new list element
        var li = document.createElement("li");
        li.id = 'i_'+item.id;
        li.classList.add('li-post-bloc');
        li.dataset.feedUrl = item.feed;
        if (item.statut == 0) {
            li.classList.add('read'); }

        // li-head: title-block
        var title = document.createElement("div");
        title.classList.add('post-title');

        // site name
        var site = document.createElement("div");
        site.classList.add('site');
        site.appendChild(document.createTextNode(item.sitename));
        title.appendChild(site);

        // post title
        var titleLink = document.createElement("a");
        titleLink.href = item.link;
        titleLink.title = item.title;
        titleLink.target = "_blank";
        titleLink.appendChild(document.createTextNode(item.title));
        titleLink.onclick = function () {
            return openItem(this); };
        title.appendChild(titleLink);

        // post date
        var date = document.createElement("div");
        date.classList.add('date');
        date.appendChild(document.createTextNode(item.date));
        var time = document.createElement("span");
        time.appendChild(document.createTextNode(', '+item.time));
        date.appendChild(time);
        title.appendChild(date);

        // post share link & fav link
        var share = document.createElement("div");
        share.classList.add('share');
        var shareLink = document.createElement("a");
        shareLink.href = 'links.php?url='+item.link;
        shareLink.target = "_blank";
        shareLink.classList.add("lien-share");
        share.appendChild(shareLink);
        var favLink = document.createElement("a");
        favLink.href = '#';
        favLink.target = "_blank";
        favLink.classList.add("lien-fav");
        favLink.dataset.isFav = item.fav;
        favLink.onclick = function () {
            favPost(this); return false; };
        share.appendChild(favLink);

        title.appendChild(share);


        // bloc with main content of feed in a comment (it’s uncomment when open, to defer media loading).
        var content = document.createElement("div");
        content.classList.add('rss-item-content');
        var comment = document.createComment(item.content);
        content.appendChild(comment);

        var hr = document.createElement("hr");
        hr.classList.add('clearboth');

        li.appendChild(title);
        li.appendChild(content);
        li.appendChild(hr);

        postlist.appendChild(li);
    }

    // displays the number of unread items (local counter)
    var count = document.querySelector('#post-counter');
    if (count.firstChild) {
        count.firstChild.nodeValue = unread;
        count.dataset.nbrun = unread;
    } else {
        count.appendChild(document.createTextNode(unread));
        count.dataset.nbrun = unread;
    }


    document.getElementById('post-list-wrapper').appendChild(postlist);

    return false;
}

/* Starts the refreshing process (AJAX) */
function refresh_all_feeds(refreshLink)
{
    // if refresh ongoing : abbord !
    if (refreshLink.dataset.refreshOngoing == 1) {
        return false;
    } else {
        refreshLink.dataset.refreshOngoing = 1;
    }
    var notifNode = document.getElementById('message-return');
    loading_animation('on');

    // prepare XMLHttpRequest
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '_rss.ajax.php', true);

    var glLength = 0;
    // feeds update gradualy. This counts the feeds that have been updated yet

    xhr.onprogress = function () {
        if (glLength != this.responseText.length) {
            var posSpace = (this.responseText.substr(0, this.responseText.length-1)).lastIndexOf(" ");
            notifNode.textContent = this.responseText.substr(posSpace);
            glLength = this.responseText.length;
        }
    }
    xhr.onload = function () {
        var resp = this.responseText;

        // update status
        var nbNewFeeds = resp.substr(resp.indexOf("Success")+7);
        notifNode.textContent = nbNewFeeds+' new feeds (please reload page)';

        // if new feeds, reload page.
        refreshLink.dataset.refreshOngoing = 0;
        loading_animation('off');
        window.location.href = (window.location.href.split("?")[0]).split("#")[0]+'?msg=confirm_feed_update&nbnew='+nbNewFeeds;
        return false;
    };

    xhr.onerror = function () {
        notifNode.textContent = document.createTextNode(this.responseText);
        loading_animation('off');
        refreshLink.dataset.refreshOngoing = 0;
    };

    // prepare and send FormData
    var formData = new FormData();
    formData.append('token', token);
    formData.append('refresh_all', 1);
    xhr.send(formData);
    return false;
}


/**
 * RSS : mark as read code.
 * "$what" is either "all"
 *   "site" for marking one feed as read
 *   "folder", or "post" for marking just one ID as read
 * "$url" contains id, folder or feed url
 */
function markAsRead(what, url)
{
    var notifDiv = document.createElement('div');
    var notifNode = document.getElementById('message-return');
    var gCount = document.querySelector('#global-post-counter');
    var count = document.querySelector('#post-counter');

    // if all data is charged to be marked as read, ask confirmation.
    if (what == 'all') {
        var retVal = confirm("Tous les éléments seront marqués comme lu ?");
        if (!retVal) {
            loading_animation('off');
            return false;
        }

        var liList = document.querySelectorAll('#post-list .li-post-bloc');
        for (var i = 0, len = liList.length; i < len; i++) {
            liList[i].classList.add('read'); }
        // mark feed list items as containing 0 unread
        for (var i = 0, liList = document.querySelectorAll('#feed-list li'), len = liList.length; i < len; i++) {
            liList[i].dataset.nbrun = 0;
            liList[i].querySelector('span').firstChild.nodeValue = '('+liList[i].dataset.nbrun+')';
        }

        // mark global counter
        gCount.dataset.nbrun = 0;
        gCount.firstChild.nodeValue = '(0)';
        count.dataset.nbrun = 0;
        count.firstChild.nodeValue = '0';

        // markitems as read in (var)Rss list.
        for (var i = 0, len = Rss.length; i < len; i++) {
            Rss[i].statut = 0; }

        loading_animation('off');
    } else if (what == 'site') {
        // mark all post from one url as read

        // mark all html items listed as "read"
        var liList = document.querySelectorAll('#post-list .li-post-bloc');
        for (var i = 0, len = liList.length; i < len; i++) {
            liList[i].classList.add('read');
        }
        var activeSite = document.querySelector('.active-site');
        // mark feeds in feed-list as containing (0) unread
        var liCount = activeSite.dataset.nbrun;
        activeSite.dataset.nbrun = 0;
        activeSite.querySelector('span').firstChild.nodeValue = '(0)';

        // mark global counter
        gCount.dataset.nbrun -= liCount;
        gCount.firstChild.nodeValue = '('+gCount.dataset.nbrun+')';
        count.dataset.nbrun = 0;
        count.firstChild.nodeValue = '0';

        // mark items as read in (var)Rss.list.
        for (var i = 0, len = Rss.length; i < len; i++) {
            if (Rss[i].feed == url) {
                Rss[i].statut = 0;
            }
        }

        // remove X feeds in folder-count (if site is in a folder)
        if (activeSite.parentNode.parentNode.dataset.folder) {
            var fCount = activeSite.parentNode.parentNode.getElementsByTagName('span')[1];

            activeSite.parentNode.parentNode.dataset.nbrun -= liCount;
            fCount.firstChild.nodeValue = '('+activeSite.parentNode.parentNode.dataset.nbrun+')';
        }

        loading_animation('off');
    } else if (what == 'folder') {
        /*
        // mark all post from one folder as read

        var activeSite = document.querySelector('.active-site');

        // mark all elements listed as class="read"
        var liList = document.querySelectorAll('#post-list .li-post-bloc');
        for (var i = 0, len = liList.length; i < len; i++) {
            liList[i].classList.add('read'); }

        // mark folder row in feeds-list as containing 0 unread
        var liCount = activeSite.dataset.nbrun;
        activeSite.dataset.nbrun = 0;
        activeSite.querySelector('span span').firstChild.nodeValue = '(0)';

        // mark global counter
        gCount.dataset.nbrun -= liCount;
        gCount.firstChild.nodeValue = '('+gCount.dataset.nbrun+')';
        count.dataset.nbrun = 0;
        count.firstChild.nodeValue = '0';


        // mark sites in folder as read aswell
        for (var i = 0, liList = activeSite.querySelectorAll('li'), len = liList.length; i < len; i++) {
            liList[i].dataset.nbrun = 0;
            liList[i].querySelector('span').firstChild.nodeValue = '(0)';
        }


        // mark items as read in (var)Rss list.
        for (var i = 0, len = Rss.length; i < len; i++) {
            if (Rss[i].folder == url) {
                Rss[i].statut = 0; } }

        loading_animation('off');
        */
    } else if (what == 'post') {
        // mark post with specific URL/ID as read

        // add read class on post that is open or read
        document.getElementById('i_'+url).classList.add('read');

        // remove "1" from feed counter
        var feedlink = document.getElementById('i_'+url).dataset.feedUrl;
        for (var i = 0, liList = document.querySelectorAll('#feed-list li'), len = liList.length; i < len; i++) {
            // remove 1 unread in url counter
            if (liList[i].dataset.feedurl == feedlink) {
                var liCount = liList[i].dataset.nbrun;
                liList[i].dataset.nbrun -= 1;
                liList[i].querySelector('span').firstChild.nodeValue = '('+liList[i].dataset.nbrun+')';

                // remove "1" from folder counter (if folder applies)
                if (liList[i].parentNode.parentNode.dataset.folder) {
                    var fCount = liList[i].parentNode.parentNode.getElementsByTagName('span')[1];

                    liList[i].parentNode.parentNode.dataset.nbrun -= 1;
                    fCount.firstChild.nodeValue = '('+liList[i].parentNode.parentNode.dataset.nbrun+')';
                }

                break;
            }
        }

        // mark global counter
        gCount.dataset.nbrun -= 1;
        gCount.firstChild.nodeValue = '('+gCount.dataset.nbrun+')';
        count.dataset.nbrun -= 1;
        count.firstChild.nodeValue = count.dataset.nbrun;

        // markitems as read in (var)Rss list.
        for (var i = 0, len = Rss.length; i < len; i++) {
            if (Rss[i].id == url) {
                Rss[i].statut = 0;
                break;
            }
        }
        loading_animation('off');
    }

    return false;
}

/* sends the AJAX "mark as read" request */
function sendMarkReadRequest(what, url, async)
{
    loading_animation('on');
    var notifDiv = document.createElement('div');
    var notifNode = document.getElementById('message-return');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '_rss.ajax.php', async);

    // onload
    xhr.onload = function () {
        var resp = this.responseText;
        if (resp.indexOf("Success") == 0) {
            // dirty...
            if (what == 'folder' || what == 'all') {
                window.location.reload();
            }
            if (what !== 'postlist') {
                markAsRead(what, url);
            }
            loading_animation('off');
            return true;
        } else {
            loading_animation('off');
            notifNode.innerHTML = resp;
            return false;
        }
    };

    // onerror
    xhr.onerror = function (e) {
        loading_animation('off');
        // adding notif
        notifDiv.textContent = 'AJAX Error ' +e.target.status;
        notifDiv.classList.add('no_confirmation');
        document.getElementById('top').appendChild(notifDiv);
        notifNode.innerHTML = resp;
    };

    // prepare and send FormData
    var formData = new FormData();
    formData.append('token', token);
    formData.append('mark-as-read', what);
    formData.append('url', url);
    xhr.send(formData);
}

/* sends the AJAX "mark as read" request */
function sendMarkFavRequest(url)
{
    loading_animation('on');
    var notifDiv = document.createElement('div');
    var notifNode = document.getElementById('message-return');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '_rss.ajax.php', true);

    // onload
    xhr.onload = function () {
        var resp = this.responseText;
        if (resp.indexOf("Success") == 0) {
            loading_animation('off');
            return true;
        } else {
            loading_animation('off');
            notifNode.innerHTML = resp;
            return false;
        }
    };

    // onerror
    xhr.onerror = function (e) {
        loading_animation('off');
        // adding notif
        notifDiv.textContent = 'AJAX Error ' +e.target.status;
        notifDiv.classList.add('no_confirmation');
        document.getElementById('top').appendChild(notifDiv);
        notifNode.innerHTML = resp;
    };

    // prepare and send FormData
    var formData = new FormData();
    formData.append('token', token);
    formData.append('mark-as-fav', 1);
    formData.append('url', url.substr(2));
    xhr.send(formData);

}


/* in RSS config : mark a feed as "to remove" */
function markAsRemove(link)
{
    var li = link.parentNode.parentNode;
    li.classList.add('to-remove');
    li.getElementsByClassName('remove-feed')[0].value = 0;
}
function unMarkAsRemove(link)
{
    var li = link.parentNode.parentNode;
    li.classList.remove('to-remove');
    li.getElementsByClassName('remove-feed')[0].value = 1;
}


/* Detects keyboad shorcuts for RSS reading */
function keyboardNextPrevious(e)
{
    // no elements showed
    if (!document.querySelector('.li-post-bloc')) {
        return true;
    }

    // no element selected : selects the first.
    if (!document.querySelector('.open-post')) {
        var openPost = document.querySelector('.li-post-bloc');
        var first = true;
    } // an element is selected, get it
    else {
        var openPost = document.querySelector('.open-post');
        var first = false;
    }

    e = e || window.event;
    var evt = document.createEvent("MouseEvents"); // créer un évennement souris
    evt.initMouseEvent("click", true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
    if (e.keyCode == '38' && e.ctrlKey && openPost.previousElementSibling != null) {
        // up
        var elmt = openPost.previousElementSibling.querySelector('a');
        elmt.dispatchEvent(evt);
        e.preventDefault();
        window.location.hash = elmt.parentNode.parentNode.id;
        window.scrollBy(0,-120);
    } else if (e.keyCode == '40' && e.ctrlKey && openPost.nextElementSibling != null) {
        // down
        if (first) {
            var elmt = openPost.querySelector('a');
        } else {
            var elmt = openPost.nextElementSibling.querySelector('a');
        }
        elmt.dispatchEvent(evt);
        e.preventDefault();
        window.location.hash = elmt.parentNode.parentNode.id;
        window.scrollBy(0,-120);
    }
    return true;
}

// show form for new rss feed
function addNewFeed()
{
    var newLink = window.prompt(BTlang.rssJsAlertNewLink, '');
    // empty string : stops here
    if (!newLink) {
        return false;
    }

    var newFolder = window.prompt(BTlang.rssJsAlertNewLinkFolder, '');
    var notifDiv = document.createElement('div');

    // otherwise continu.
    var notifNode = document.getElementById('message-return');
    loading_animation('on');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '_rss.ajax.php');
    xhr.onload = function () {

        var resp = this.responseText;
        // en cas d’erreur, arrête ; le message d’erreur est mis dans le #NotifNode
        if (resp.indexOf("Success") == -1) {
            loading_animation('off');
            notifNode.innerHTML = resp;
            return false;
        }

        // recharge la page en cas de succès
        loading_animation('off');
        notifNode.textContent = 'Success: please reload page.';
        window.location.href = window.location.href.split("?")[0]+'?msg=confirm_feed_ajout';
        return false;

    };
    xhr.onerror = function (e) {
        loading_animation('off');
        // adding notif
        notifDiv.textContent = 'Une erreur PHP/Ajax s’est produite :'+e.target.status;
        notifDiv.classList.add('no_confirmation');
        document.getElementById('top').appendChild(notifDiv);
    };
    // prepare and send FormData
    var formData = new FormData();
    formData.append('token', token);
    formData.append('add-feed', newLink);
    formData.append('add-feed-folder', newFolder);
    xhr.send(formData);

    return false;

}

// demande confirmation pour supprimer les vieux articles.
function cleanList()
{
    var notifDiv = document.createElement('div');
    var reponse = window.confirm(BTlang.questionCleanRss);
    if (!reponse) {
        return false;
    }

    loading_animation('on');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '_rss.ajax.php', true);
    xhr.onload = function () {
        var resp = this.responseText;
        if (resp.indexOf("Success") == 0) {
            // rebuilt array with only unread items
            var list = new Array();
            for (var i = 0, len = Rss.length; i < len; i++) {
                var item = Rss[i];
                if (!item.statut == 0) {
                    list.push(item);
                }
            }
            Rss = list;
            rss_feedlist(Rss);

            // adding notif
            notifDiv.textContent = BTlang.confirmFeedClean;
            notifDiv.classList.add('confirmation');
            document.getElementById('top').appendChild(notifDiv);
        } else {
            notifDiv.textContent = 'Error: '+resp;
            notifDiv.classList.add('no_confirmation');
            document.getElementById('top').appendChild(notifDiv);
        }


        loading_animation('off');
    };
    xhr.onerror = function (e) {
        loading_animation('off');
        // adding notif
        notifDiv.textContent = BTlang.errorPhpAjax + e.target.status;
        notifDiv.classList.add('confirmation');
        document.getElementById('top').appendChild(notifDiv);
    };

    // prepare and send FormData
    var formData = new FormData();
    formData.append('token', token);
    formData.append('delete_old', 1);
    xhr.send(formData);
    return false;
}

/**************************************************************************************************************************************
    TOUCH EVENTS HANDLING (various pages)
**************************************************************************************************************************************/
function handleTouchEnd()
{
    doTouchBreak = null;
}

function handleTouchStart(evt)
{
    xDown = evt.touches[0].clientX;
    yDown = evt.touches[0].clientY;
}

/* Swipe on slideshow to change images */
function swipeSlideshow(evt)
{
    if (!xDown || !yDown || doTouchBreak || document.getElementById('slider').style.display != 'block') {
        return;
    }
    var xUp = evt.touches[0].clientX,
        xDiff = xDown - xUp;

    if (Math.abs(xDiff) > minDelta) {
        var newEvent = document.createEvent("MouseEvents");
        newEvent.initMouseEvent("click", true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);

        if (xDiff > minDelta) {
            // left swipe
            var button = document.getElementById('slider-next');
            evt.preventDefault();
            button.dispatchEvent(newEvent);
            doTouchBreak = true;
        } else if (xDiff < -minDelta) {
            // right swipe
            var button = document.getElementById('slider-prev');
            evt.preventDefault();
            button.dispatchEvent(newEvent);
            doTouchBreak = true;
        }
    }
    if (doTouchBreak) {
        xDown = null;
        yDown = null;
    }
}


/**************************************************************************************************************************************
    CANVAS FOR index.php GRAPHS
**************************************************************************************************************************************/
function respondCanvas()
{
    var containers = document.querySelectorAll(".graph-container");

    for (var i = 0, len = containers.length; i < len; i++) {
        var canvas = containers[i].querySelector("canvas");
        canvas.width = parseInt(containers[i].querySelector(".graphique").getBoundingClientRect().width);
        draw(containers[i], canvas);
    }
}

function draw(container, canvas)
{
    var months = container.querySelectorAll(".graphique .month");
    var ctx = canvas.getContext("2d");
    var cont = {
        x: container.getBoundingClientRect().left,
        y: container.getBoundingClientRect().top
    };

    // strokes the background lines at 0%, 25%, 50%, 75% and 100%.
    ctx.beginPath();
    for (var i = months.length - 1; i >= 0; i--) {
        if (months[i].getBoundingClientRect().top < months[0].getBoundingClientRect().bottom) {
            var topLeft = months[i].getBoundingClientRect().left -15;
            break;
        }
    }

    var coordScale = { x: topLeft, xx: months[1].getBoundingClientRect().left };
    for (var i = 0; i < 5; i++) {
        ctx.moveTo(coordScale.x, i * canvas.height / 4 +1);
        ctx.lineTo(coordScale.xx, i * canvas.height / 4 +1);
        ctx.strokeStyle = "rgba(0, 0, 0, .05)";
    }
    ctx.stroke();

    // strokes the lines of the chart
    ctx.beginPath();
    for (var i = 1, len = months.length; i < len; i++) {
        var coordsNew = months[i].getBoundingClientRect();
        if (i == 1) {
            ctx.moveTo(coordsNew.left - cont.x + coordsNew.width / 2, coordsNew.top - cont.y);
        } else {
            if (coordsNew.top - cont.y <= 150) {
                ctx.lineTo(coordsNew.left - cont.x + coordsNew.width / 2, coordsNew.top - cont.y);
            }
        }
    }
    ctx.lineWidth = 2;
    ctx.strokeStyle = "rgb(33, 150, 243)";
    ctx.stroke();
    ctx.closePath();

    // fills the chart
    ctx.beginPath();
    for (var i = 1, len = months.length; i < len; i++) {
        var coordsNew = months[i].getBoundingClientRect();
        if (i == 1) {
            ctx.moveTo(coordsNew.left - cont.x + coordsNew.width / 2, 150);
            ctx.lineTo(coordsNew.left - cont.x + coordsNew.width / 2, coordsNew.top - cont.y);
        } else {
            if (coordsNew.top - cont.y <= 150) {
                ctx.lineTo(coordsNew.left - cont.x + coordsNew.width / 2, coordsNew.top - cont.y);
                var coordsOld = coordsNew;
            }
        }
    }
    ctx.lineTo(coordsOld.left - cont.x + coordsOld.width / 2, 150);
    ctx.fillStyle = "rgba(33, 150, 243, .2)";
    ctx.fill();
    ctx.closePath();
}
