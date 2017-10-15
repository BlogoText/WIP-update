
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
        input.placeholder = "http\:\/\/example.com\/image.png";
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
    // formData.append('token', document.getElementById('token').value);
    xhr.setRequestHeader("X-CSRFToken", document.getElementById('token').value);

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
