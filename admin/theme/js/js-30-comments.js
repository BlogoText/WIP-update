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
        xhr.open('POST', 'comments.php', true);

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
    xhr.open('POST', 'comments.php', true);

    xhr.onprogress = function () {
        div_bloc.classList.add('ajaxloading');
    }

    xhr.onload = function () {
        var resp = this.responseText;
        if (resp.indexOf("Success") == 0) {
            csrf_token = resp.substr(7, 40);
            button.textContent = ((button.textContent === BTlang.activer) ? BTlang.desactivate : BTlang.activate );
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
