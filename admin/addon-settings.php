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



// traitement d’une action sur le module
$erreurs = array();

if (isset($_POST['_verif_envoi'])) {
    // [POC] to show loading bar
    sleep(1);
    $erreurs = addon_ajax_check_request(htmlspecialchars($_POST['addon_id']), 'addon_button_action');
    if (!empty($erreurs)) {
        echo json_encode(
            array(
                'success' => false,
                'message' => $erreurs['0'],
                'token'
            )
        );
        die;
    } else {
        $process = addon_ajax_button_action_process(htmlspecialchars($_POST['addon_id']), htmlspecialchars($_POST['button_id']));
        // if (!$process) {
            // die('Error'.new_token().'<p><strong>:/</strong> Fail to process</p>');
        // }
        echo json_encode(
            array(
                'success' => false,
                'message' => $erreurs,
                'token'
            )
        );
        die();
    }
}

// traitement d’une action sur le module
if (isset($_POST['_verif_envoi']) && isset($_POST['action_type'])) {
    if ($_POST['action_type'] == 'settings') {
        $form_process = addon_form_edit_settings_proceed($_GET['addon']);
    } elseif ($_POST['action_type'] == 'buttons') {
        $form_process = addon_buttons_action_process($_GET['addon']);
    }
}


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

// echo erreurs($erreurs);

echo addon_form_buttons($_GET['addon']);
echo addon_form_edit_settings($_GET['addon']);

echo "\n".'<script src="style/javascript.js"></script>'."\n";
echo '<script>';
echo php_lang_to_js(0);
echo 'var csrf_token = \''.new_token().'\'';
echo '</script>';
?>

<script>
// [POC]
class Notification {
    constructor() {
        this.container = document.createElement('div');
        this.box = document.createElement('div');
        this.content = document.createElement('div');
        this.btnCloseBar = null;
        this.btnClose = null;
        this.container.classList.add('Notification');
        this.box.classList.add('Notification-box');
        this.content.classList.add('Notification-content');

        this.box.appendChild(this.content);
        this.container.appendChild(this.box);
        return this;
    }

    showLoadingBar()
    {
        this.box.classList.add('loading_bar');
        this.box.classList.add('loadingOn');
        return this;
    }
    hideLoadingBar()
    {
        this.box.classList.remove('loading_bar');
        this.box.classList.remove('loadingOn');
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
    addCloseTimer(ttl, callback)
    {
        var cont = this.container;
        setTimeout(function() {
            cont.parentNode.removeChild(cont);
            if (typeof callback === "function") {
                callback();
            }
        }, ttl);
    }
    addCloseButton(text, callback)
    {
        this.btnCloseBar = document.createElement('div');
        this.btnCloseBtn = document.createElement('button');

        this.btnCloseBtn.innerHTML = text;
        this.btnCloseBtn.classList.add('submit');
        this.btnCloseBtn.classList.add('button-submit');
        this.btnCloseBar.classList.add('submit-bttns');
        this.btnCloseBar.classList.add('notification-footer');

        this.btnCloseBar.appendChild(this.btnCloseBtn);
        this.box.appendChild(this.btnCloseBar);

        var cont = this.container;
        this.btnCloseBtn.addEventListener("click",function(e) {
            e.preventDefault();
            // var p = this.parentNode.parentNode;
            // p.parentNode.removeChild(p);
            cont.parentNode.removeChild(cont);
            if (typeof callback === "function") {
                callback();
            }
            return;
        }, false);
        return this;
    }
    // WIP
    insertAsModal()
    {
        this.container.classList.add('Notification-modal');
        document.body.appendChild(this.container);
    }
    // WIP
    insertAfter(insertAfter)
    {
        var parent = insertAfter.parentNode,
            next = insertAfter.nextSibling;
        if (next) {
            return parent.insertBefore(this.container, next)
        } else {
            return parent.appendChild(this.container)
        }
        return this;
    }
}


// [POC] setTimeout for css animation
/**
 * si pas de 2nd timeout pour remettre la classe la chexkbox "scintille" avant de réapparaitre
 */
function checkboxToggleReset(chk)
{
    setTimeout(function(){
        chk.classList.remove('checkbox-toggle');
        chk.removeAttribute('disabled');
        chk.removeAttribute('active');
        chk.removeAttribute('checked');
        chk.checked = false;
    }, 400);
    setTimeout(function(){
        chk.classList.add('checkbox-toggle');
    }, 400);
}


// [POC] trigger an action
function addon_button_action(button, addon_id, button_id)
{
    if (!button.checked){return false;}
    button.setAttribute('disabled', true);
    var notifDiv = document.createElement('div');

    // set the notification
    var notif = new Notification();
    notif.showLoadingBar()
        .addCloseButton('Ok', function(){checkboxToggleReset(button);})
        // .insertAsModal();
        .insertAfter(button.parentNode);

    // set the request
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'addon-settings.php', true);

    xhr.onload = function () {
        var resp = JSON.parse(this.responseText);

        if (resp.success == true){
            notif.setHtml(resp.message);
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
