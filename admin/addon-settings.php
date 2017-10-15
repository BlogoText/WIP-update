<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BlogoText/blogotext/
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
require_once BT_ROOT_ADMIN.'inc/addons.php';


/**
 * process
 */

$erreurs = array();
$form_process = null;

if (isset($_POST['format']) && $_POST['format'] == 'json') {
    $erreurs = addon_ajax_check_request(htmlspecialchars($_POST['addon_id']), 'addon_button_action');
    if ($erreurs) {
        die(json_encode(
            array(
                'success' => false,
                'message' => $erreurs['0'],
                'token' => token_set()
            )
        ));
    }

    $process = addon_ajax_button_action_process(htmlspecialchars($_POST['addon_id']), htmlspecialchars($_POST['button_id']));
    die(json_encode(
        array(
            'success' => false,
            'message' => $erreurs,
            'token' => token_set()
        )
    ));
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

echo tpl_get_html_head($GLOBALS['lang']['my_addons'], true);
echo '<div id="axe">';
echo '<div id="page">';
echo addon_form_buttons($_GET['addon']);
echo addon_form_edit_settings($_GET['addon']);

echo '<script>';
echo 'var csrf_token = "'.token_set().'";';
echo '</script>';
?>

<script>
function addon_button_action(button, addon_id, button_id)
{
    if (!button.checked){return false;}
    button.setAttribute('disabled', true);
    var notifDiv = document.createElement('div');

    // set the notification
    var notif = new Notification();

    // after in DOM
    notif.showLoadingBar()
        .onClose(function(){checkboxToggleReset(button);})
        .addCloseButton('Ok')
        .insertAfter(button.parentNode);

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
            notif
                .setHtml(resp.message);
        }
        notif.hideLoadingBar();
        // refresh the token
        csrf_token = resp.token;

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
    formData.append('format', 'json');
    formData.append('addon_id', addon_id);
    formData.append('button_id',button_id);

    xhr.send(formData);
}
</script>

<?php
echo '</div>';
echo '</div>';
echo tpl_get_footer();
