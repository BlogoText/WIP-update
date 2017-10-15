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

class Tabs {

    constructor(tab)
    {
        console.log('tabs');
        this.tab = tab;
        this.tabsHead = tab.querySelectorAll(".tabs-head > li");
        this.tabsContent = tab.querySelectorAll(".tabs-content");
        this.hasActive = false;

        var self = this;
        Array.prototype.forEach.call(this.tabsHead, function(el, i){
            if (el.classList.contains('active')) {
                this.hasActive = true;
            }
            el.onclick = function(){self.tabShow(i)};
        });

        // set the first one active if there is none
        if (!this.hasActive) {
            this.tabShow(0);
        }
    }

    tabShow(tab_eq)
    {
        var self = this,
            elHead = this.tabsHead[tab_eq],
            elContent = this.tabsContent[tab_eq];

            console.log(tab_eq);
            console.log(elHead);
        for (var k=0; k < this.tabsHead.length; k++){
            this.tabsHead[k].classList.remove('active');
        }
        for (var k=0; k < this.tabsContent.length; k++){
            this.tabsContent[k].classList.remove('active');
        }

        // add class active
        elHead.classList.add('active');
        elContent.classList.add('active');

        /*
        var trigger = elHead;
        if (trigger != undefined && typeof window[trigger]() === "function") {
            window[trigger]();
        }
        */
    }
}

