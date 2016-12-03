<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BoboTiG/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***

require_once 'inc/boot.php';

// dependancy
require_once BT_ROOT.'inc/addons.php';
// require_once BT_ROOT.'admin/inc/addons.php'; // dont remove, just the time to clean the rewrited addon's function



/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['mesmodules']);

echo '<div id="header">'."\n";
    echo '<div id="top">'."\n";
        echo moteur_recherche();
        tpl_show_topnav($GLOBALS['lang']['mesmodules']);
    echo '</div>'."\n";
echo '</div>'."\n";

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

?>

<div style="max-width:720px;margin:0 auto">
<h1>Template > test script notification &amp; some impovement...</h1>
<p>This page is about the [POC] Notification.<br /><strong>THIS PAGE MUST BE NOT PUSH IN PROD</strong></p>

<hr />

<p>And why not a <span style="color: #555;"><strong>"smoother"</strong> color ?</span> <button onclick="color_test()" class="submit">set #555</button></p>

<hr />

<h2>Dialog</h2>

    <p class="submit-bttns" style="background-color:white;padding:20px 12px;">
        dialog without button and 3s TTL <button onclick="dialog_without_close()" class="submit">dialog</button>
    </p>

    <p class="submit-bttns" style="background-color:white;padding:20px 12px;">
        dialog with exit button <button onclick="dialog_with_close()" class="submit">dialog</button>
    </p>

    
    <p class="submit-bttns" style="background-color:white;padding:20px 12px;">
        dialog with exit button and a lot of content <button onclick="dialog_with_close_content()" class="submit">dialog</button>
    </p>

    <p class="submit-bttns" style="background-color:white;padding:20px 12px;">
        dialog with exit button and 3s TTL <button onclick="dialog_with_close_and_ttl()" class="submit">dialog</button>
    </p>

    <p class="submit-bttns" style="background-color:white;padding:20px 12px;">
        dialog with loading bar <button onclick="dialog_with_loadingBar()" class="submit">dialog</button>
    </p>

    <p class="submit-bttns" style="background-color:white;padding:20px 12px;">
        dialog with fallback on close <button onclick="dialog_with_fallback()" class="submit">dialog</button>
    </p>


<hr />

<h2>insertAfter</h2>
    <div>
        <p class="submit-bttns" style="background-color:white;padding:20px 12px;">
            Insert after this parent (behind this) <button onclick="insert_after(this)" class="submit">insertAfter</button>
        </p>
        <p class="submit-bttns"  style="background-color:white;">Must be show up.</p>
    </p>

    <div>
        <p class="submit-bttns" style="background-color:white;padding:20px 12px;">
            Insert after this parent (behind this) with button, TTL, loadBar on parent, callback <button onclick="insert_after_total(this)" class="submit">insertAfter</button>
        </p>
        <p class="submit-bttns" style="background-color:white;">Must be show up. The loading will continue, not call for <code>hideLoadingBar()</code> ;)</p>
    </p>


<hr />

<h2>Sticker</h2>

    <div>
        <p class="submit-bttns" style="background-color:white;padding:20px 12px;">
            Currently, need some work ... <button onclick="stiker_btn(this)" class="submit">insert a sticker</button>
        </p>
    </p>


<hr />

<h2>BigToast</h2>

    <p class="submit-bttns" style="background-color:white;padding:20px 12px;">
        Insert a bigToast on click : <button onclick="BigToast_insert()" class="submit">Click me, Harder, Better, Faster ...</button>
    </p>


<hr />

<h2>Set unChecked checkbox threw JS</h2>

    <div>
        <div class="submit-bttns" style="background-color:white;padding:20px 12px;">
            <p><input type="checkbox" class="checkbox-toggle" id="chk1" /><label for="chk1">Play with me</label></p>
            <p><button onclick="uncheck_the_check_button()" class="submit">unckeck threw JS function</button></p>
        </div>
    </p>
</div>



<?php

echo "\n".'<script src="style/javascript.js"></script>'."\n";
echo '<script>';
echo php_lang_to_js(0);
echo 'var csrf_token = \''.new_token().'\'';
echo '</script>';
?>

<script>

function color_test()
{
    var page = document.getElementById('page');
    page.style.color = '#555';
}

function dialog_without_close()
{
    var notif = new Notification();
    notif
        .setHtml('<p>This is a <strong>dialog</strong> notification. With a timeOut of 3 sec</p>')
        .addCloseTimer(3000)
        .insertAsDialog();
}

function dialog_with_close()
{
    var notif = new Notification();
    notif
        .addCloseButton('Close Me')
        .setHtml('<p>This is a <strong>dialog</strong> notification.</p>')
        .insertAsDialog();
}

function dialog_with_close_content()
{
    var t = '<p>This is a <strong>dialog</strong> notification. Test with a long string. Test with a long string. Test with a long string. Test with a long string. Test with a long string. Test with a long string. Test with a long string.</p>',
        c = '';
    for (i=0 ; i < 10; i++){
        c += t;
    }
    var notif = new Notification();
    notif
        .addCloseButton('Close Me')
        .setHtml(c)
        .insertAsDialog();
}

function dialog_with_close_and_ttl()
{
    var notif = new Notification();
    notif
        .addCloseButton('Close Me')
        .setHtml('<p>This is a <strong>dialog</strong> notification with button and a timeOut of 3 sec.</p>')
        .addCloseTimer(3000)
        .insertAsDialog();
}

function dialog_with_loadingBar()
{
    var notif = new Notification();
    notif
        .showLoadingBar()
        .setHtml('<p>This is a <strong>dialog</strong> notification with loading bar.</p>')
        .addCloseButton('Ok')
        .insertAsDialog();

    // show the dialog after 3 sec
    setTimeout(function() {
        notif
            .hideLoadingBar()
    }, 3000);
}

function dialog_with_fallback()
{
    var notif = new Notification();
    notif
        .onClose(
            function()
            {
                alert('Closed');
            }
        )
        .addCloseButton('Close Me')
        .setHtml('<p>This is a <strong>dialog</strong> notification. Callback work with button or TTL or both</p>')
        .insertAsDialog();
}



function insert_after(trigger)
{
    var notif = new Notification();
    notif
        .setHtml('<p>This is a <strong>insertAfter</strong> notification. But without TTL and close button, deal with it ;)</p>')
        .insertAfter(trigger.parentNode);
}

function insert_after_total(trigger)
{
    var notif = new Notification();
    var parent = trigger.parentNode;
    notif
        .onClose(
                function()
                {
                    alert('Closed');
                }
            )
        .showLoadingBar(parent)
        .addCloseButton('Ok !')
        .setHtml('<p>This is a <strong>insertAfter</strong> notification.</p>')
        .insertAfter(parent);
}

function stiker_btn(btn)
{
    var notif = new Notification();
    notif
        .setText('Yeahhhhh, but need some improvements ...')
        .addCloseTimer(2000)
        .insertSticker(btn);
}

var i = 0;
function BigToast_insert()
{
    i++;
    var notif = new Notification();
    notif
        .setText( '('+i+') Yeahhhhh, but need some improvements ... (autoclose in 3s)')
        .addCloseTimer(2000)
        .addCloseButton('Click me for close')
        .insertAsBigToast();
}



function uncheck_the_check_button()
{
    var THE_checkbox = document.getElementById('chk1');
    checkboxToggleReset(THE_checkbox);
}






// [POC] trigger an action
function addon_button_action(button, addon_id, button_id)
{
    if (!button.checked){return false;}
    button.setAttribute('disabled', true);
    var notifDiv = document.createElement('div');

    // set the notification
    var notif = new Notification();

    // sticker
    /*
    notif
        .showLoadingBar(button.parentNode.getElementsByTagName("label")[0])
        .onClose(function(){checkboxToggleReset(button);})
        .insertSticker(
            button.parentNode.getElementsByTagName("label")[0], // stick to, <check box have a dirty css tricks to hide the reel one, so we use the label instead
            {left:60,width:210} // some correction
        );
    */

    // dialog
    /**/
    notif.showLoadingBar()
        .onClose(function(){checkboxToggleReset(button);})
        .addCloseButton('Ok')
        .insertAsDialog();
    /**/

    // after in DOM
    /*
    notif.showLoadingBar()
        .onClose(function(){checkboxToggleReset(button);})
        .addCloseButton('Ok')
        .insertAfter(button.parentNode);
    */

    // set the request
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'addon-settings.php', true);

    xhr.onload = function () {
        var resp = JSON.parse(this.responseText);

        if (resp.success == true){
            notif
                .setHtml(resp.message)
                .addCloseTimer(3000);
        } else {
            notif.setHtml(resp.message);
            // document.getElementById('top').appendChild(notifDiv);
        }
        notif.hideLoadingBar();
        // refresh the token
        var csrf_token = resp.token;

        return;
    };

    xhr.onerror = function(e) {
        notifDiv.textContent = e.target.status + ' (#?)';
        notifDiv.classList.add('no_confirmation');
        document.getElementById('top').appendChild(notifDiv);
        infosDiv.classList.remove('loading_bar');
        infosDiv.classList.add('addons-button-confirm');
    }

    // prepare and send FormData
    var formData = new FormData();
    formData.append('token', csrf_token);
    formData.append('_verif_envoi', 1);
    formData.append('format', 'ajax');
    formData.append('addon_id', addon_id);
    formData.append('button_id',button_id);

    xhr.send(formData);
}
</script>

<?php
echo tpl_get_footer($begin);
