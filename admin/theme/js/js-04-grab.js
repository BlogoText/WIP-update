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

var dragSrcEl = null;

function grabDragStart(e)
{
    dragSrcEl = this;

    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.outerHTML);
    this.classList.add('dragElem');
}

function grabDragOver(e)
{
    if (e.preventDefault) {
        e.preventDefault();
    }
    if (this.classList.contains('over')) {
        return;
    }
    this.classList.add('over');
 
    e.dataTransfer.dropEffect = 'move';

    return false;
}

function grabDragEnter(e)
{
    /**
     * this / e.target is the current hover target.
     */
}


function grabDragLeave(e)
{
    this.classList.remove('over');
}

function grabDrop(e)
{
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    if (dragSrcEl != this) {
        this.parentNode.removeChild(dragSrcEl);
        var dropHTML = e.dataTransfer.getData('text/html');
        this.insertAdjacentHTML('beforebegin',dropHTML);
        var dropElem = this.previousSibling;
        grabHandlers(dropElem);
    }
    this.classList.remove('over');
    return false;
}

function grabDragEnd(e)
{
    this.classList.remove('over');
    this.classList.remove('dragElem');
}

function grabHandlers(elem)
{
    elem.addEventListener('dragstart', grabDragStart, false);
    //elem.addEventListener('dragenter', grabDragEnter, false)
    elem.addEventListener('dragover', grabDragOver, false);
    elem.addEventListener('dragleave', grabDragLeave, false);
    elem.addEventListener('drop', grabDrop, false);
    elem.addEventListener('dragend', grabDragEnd, false);
}

/**
 * Set graphs order
 */
function grabChangeOrder()
{
    var cols = document.querySelectorAll('#grabOrder li'),
        i = 1,
        toSave = {};
    [].forEach.call(cols, function (col) {
        if (col.dataset.id == undefined) {
            return;
        }
        var c = document.getElementById(col.dataset.id);
        c.style.order = i*4;
        toSave[i] = col.dataset.id;
        ++i;
    });

    jsonRequest(
        {
            url: '_dashboard.ajax.php',
            datas: toSave,
            onLoad: function (response) {
                console.log(response);
                if (response.success == 1) {
                    notifSuccess(response.message);
                } else {
                    notifFail(response.message);
                }
            }
        }
    );

    console.log(toSave);
}

/**
 * Print or hind the grab / swipe buttons at the bottom of the page
 */
function grabDisplayOrderChanger()
{
    var div = document.getElementById("grabOrder");

    if (div.style.display == 'block') {
        div.style.display = 'none';
    } else {
        div.style.display = 'block';
    }
}
