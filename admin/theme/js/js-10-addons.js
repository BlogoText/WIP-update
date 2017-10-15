/**
 * This file is part of BlogoText.
 * https://blogotext.org/
 * https://github.com/BlogoText/blogotext/
 *
 * 2006      Frederic Nassar.
 * 2010-2016 Timo Van Neerden.
 * 2016-.... Mickaël Schoentgen and the community.
 * 2017-.... RemRem and the community.
 *
 * BlogoText is free software.
 * You can redistribute it under the terms of the MIT / X11 Licence.
 */


/**************************************************************************************************
 * ADD-ONS HANDLING
 */

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
