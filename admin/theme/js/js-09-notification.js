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


// [POC]
class Notification {

    constructor()
    {
        // set box system
        this.container = document.createElement('div');
        this.box = document.createElement('div');
        this.content = document.createElement('div');
        this.container.classList.add('Notification');
        this.box.classList.add('Notification-box');
        this.content.classList.add('Notification-content');
        // Boxing boxes
        this.box.appendChild(this.content);
        this.container.appendChild(this.box);

        // init some vars
        this.btnCloseBar = null;
        this.btnClose = null;
        this.type = null;
        this.callbackOnClose = null;

        return this;
    }

    showLoadingBar(el)
    {
        if (typeof el == 'undefined') {
            el = this.box;
        } else {
            // dont break the target
            var bar = document.createElement('div');
            bar.classList.add('loading_bar_absolute');
            if (el.style.position == '') {
                el.style.position = 'relative';
            }
            el.appendChild(bar);
            var el = bar;
        }
        el.classList.add('loading_bar');
        el.classList.add('loadingOn');
        return this;
    }
    hideLoadingBar(el, ttl, callback)
    {
        if (typeof el == 'undefined') {
            el = this.box;
        } else {
            var bar = el.getElementsByClassName('loading_bar');
            if (bar.lenght == 0) {
                return this;
            }
            var el = bar[0];
        }

        if (typeof ttl == 'undefined') {
            el.classList.remove('loading_bar');
            el.classList.remove('loadingOn');
            if (this.type == 'dialog') {
                this.dialogSetPosition(self.box);
            }
            if (typeof callback === "function") {
                callback();
            }
            return this;
        }
        var self = this;
        setTimeout(function () {
            el.classList.remove('loading_bar');
            el.classList.remove('loadingOn');
            if (self.type == 'dialog') {
                self.dialogSetPosition(self.box);
            }
            if (typeof callback === "function") {
                callback();
            }
        }, ttl);
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
    /**
     * destroy
     */
    destroy(effect)
    {
        this.container.classList.add('Notification-destroy');
        if (typeof effect == 'undefined') {
            this.container.classList.add('Notification-destroy-'+effect);
        }
        var self = this;
        setTimeout(function () {
            self.container.parentNode.removeChild(self.container);
            if (self.btnClose != null) {
                self.btnClose.removeEventListener("click");
            }
            if (typeof self.callbackOnClose === "function") {
                self.callbackOnClose();
            }
        }, 1000);
    }

    addCloseTimer(ttl, effect, callback)
    {
        var self = this;
        setTimeout(function () {
            self.destroy(effect);
            if (typeof callback === "function") {
                callback();
            }
        }, ttl);
        return this;
    }

    addCloseButton(string)
    {
        this.btnCloseBar = document.createElement('div');
        this.btnCloseBtn = document.createElement('button');

        this.btnCloseBtn.innerHTML = string;
        this.btnCloseBtn.classList.add('btn');
        this.btnCloseBtn.classList.add('btn-dense');
        this.btnCloseBar.classList.add('Notification-footer');

        this.btnCloseBar.appendChild(this.btnCloseBtn);
        this.box.appendChild(this.btnCloseBar);

        var self = this;
        this.btnCloseBtn.addEventListener("click", function (e) {
            e.preventDefault();
            self.destroy();
            return;
        }, false);
        return this;
    }

    merge(obj1, obj2)
    {
        var obj3 = {};
        for (var a in obj1) {
            obj3[a] = obj1[a];
        }
        for (var a in obj2) {
            obj3[a] = obj2[a];
        }
        return obj3;
    }

    offset(el)
    {
        // document.body.style.margin = 0;
        var rect = el.getBoundingClientRect();
        return {
            top: rect.top + (window.pageYOffset || document.documentElement.scrollTop),
            left: rect.left + (window.pageXOffset || document.documentElement.scrollLeft)
        }
    }

    // set the correct position
    dialogSetPosition(box)
    {
        var wW = window.innerWidth;
        // css style
        if (wW < 480) {
            return;
        }

        box.style.left  = ((wW - box.offsetWidth)/2) +'px';
        // box.style.right = ((wW - box.offsetWidth)/2) +'px';
    }

    onClose(callback)
    {
        if (typeof callback === "function") {
            this.callbackOnClose = callback;
        }
        return this;
    }

    // WIP
    setClass(oneClass)
    {
        this.container.classList.add(oneClass);
        return this;
    }

    // WIP
    insertAsBigToast()
    {
        this.type = 'bigtoast';
        this.BigToastContainer = document.getElementById('Notification-BigToast');
        if (this.BigToastContainer == null) {
            this.BigToastContainer = document.createElement('div');
            this.BigToastContainer.setAttribute("id", "Notification-BigToast");
            document.body.appendChild(this.BigToastContainer);
        }
        this.BigToastContainer.appendChild(this.container);
    }
    // WIP
    insertAsDialog()
    {
        var self = this;

        this.type = 'dialog';
        this.container.classList.add('Notification-dialog');
        document.body.appendChild(this.container);
        this.dialogSetPosition(this.box);

        window.addEventListener("scroll", function () {
            self.dialogSetPosition(self.box);
        }, false);
        window.addEventListener("resize", function () {
            self.dialogSetPosition(self.box);
        }, false);
    }
    // WIP
    insertAfter(insertAfter)
    {
        this.type = 'after';
        var parent = insertAfter.parentNode,
            next = insertAfter.nextSibling;
        if (next) {
            parent.insertBefore(this.container, next)
        } else {
            parent.appendChild(this.container)
        }
        return this;
    }
}
