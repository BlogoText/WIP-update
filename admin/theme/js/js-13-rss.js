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
    // add class "main-white"

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
        titleLink.appendChild(document.createTextNode('#'+item.uid+' '+item.title));
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
