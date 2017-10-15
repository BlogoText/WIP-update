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

/**
 * to do :
 *   - add double tag detection (prevent 2 times the same tags)
 *   - better html integration ? (remove the necessary '<form onsubmit="return moveTag();">')
 */

/**
 * LINKS AND ARTICLE FORMS : TAGS HANDLING
 */

/* Adds a tag to the list when we hit "enter" */
/* validates the tag and move it to the list */
function moveTag()
{
    var iField = document.getElementById('type_tags');
    var oField = document.getElementById('selected');
    var fField = document.getElementById('categories');

    // if something in the input field : enter == add word to list of tags.
    if (iField.value.length != 0) {
        oField.innerHTML += '<li><span>'+iField.value+'</span><a href="javascript:void(0)" onclick="removeTag(this.parentNode)">×</a></li>';
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
            iTag += liste[i].getElementsByTagName('span')[0].innerHTML+",";
        }
        // fField.value = iTag.substr(0, iTag.length-2);
        // remove the last ","
        fField.value = iTag.substr(0, iTag.length-1);
        return true;
    }
}

/* remove a tag from the list */
function removeTag(tag)
{
    tag.parentNode.removeChild(tag);
    return false;
}
