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

/**
 * shortcut to display a succesful message
 */
function notifSuccess(msg){
    var notif = new Notification();
    notif
        .setText(msg)
        .addCloseTimer(4000)
        .insertAsBigToast();
}
/**
 * shortcut to display a fail message
 */
function notifFail(msg){
    var notif = new Notification();
    notif
        .setText(msg)
        .setClass("error")
        .addCloseButton("Ok")
        .insertAsBigToast();
}

/**
 * set ajax/json function
 */
function jsonRequest(params)
{
    console.log(csrf_token);
    var p = {};
        p.method = params.method || 'POST'; // "GET", "POST", "PUT", "DELETE"
        p.async = params.async || true;
        p.url = params.url || null;
        p.datas = params.datas || null;
        p.onLoad = params.onLoad || function(){};
        p.onError = params.onError || function(){};

    // set the request
    var xhr = new XMLHttpRequest();

    xhr.open(p.method, p.url, p.async);
    xhr.setRequestHeader("X-CSRFToken", csrf_token);
    xhr.setRequestHeader("Content-Type", "application/json");

    // console.log(auth_token_response_get());
    xhr.onreadystatechange = function (aEvt) {
        if (xhr.readyState == 4) {
            if(xhr.status == 200) {
                try {
                    var resp = JSON.parse(this.responseText);
                } catch (e) {
                    notifFail('Not a valid json format');
                    console.log('Not a valid json format');
                    return false;
                }

                if (resp.csrf_token != undefined) {
                    csrf_token = resp.BT_token;
                }
                if (typeof p.onLoad === "function") {
                    // console.log('function Ok');
                    p.onLoad(resp);
                } else {
                    // console.log('function Fail');
                    notifFail('No callback function !');
                }
                return;
            } else {
                console.log("http code response != 200");
            }
        }
    };

    xhr.onerror = function(e) {
        if (typeof p.onError === "function") {
            p.onError();
        }
    }

    xhr.send(JSON.stringify(p.datas));
}

function showhide(btn)
{
    var container = btn.closest('.showhide');
    container.classList.toggle('show');
}

/*
    cancel button on forms.
*/

function redirection(target)
{
    window.location = target;
}


/**
 * On article or comment writing: insert a BBCode Tag or a Unicode char.
*/
function editorInsertTag(e, startTag, endTag)
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

function editorInsertChar(e, ch)
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


/**
 * 2nd timeout prevent checkbox blink
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
    LINKS AND ARTICLE FORMS : TAGS HANDLING
**************************************************************************************************************************************/

/* Adds a tag to the list when we hit "enter" */
/* validates the tag and move it to the list */
/*
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
*/

/* remove a tag from the list */
/*
function removeTag(tag)
{
    tag.parentNode.removeChild(tag);
    return false;
}
*/




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
