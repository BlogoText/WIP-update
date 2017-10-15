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


function indexGraphStat()
{
    for (var i = 0, clen = containers.length; i < clen; i += 1) {
        var months = containers[i].querySelectorAll('.month'),
            months_ct = months.length,
            month_to_show = containers[i].clientWidth / month_min_width;
        if (month_to_show > months_ct) {
            month_to_show = months_ct;
        }
        for (var j = 0; j < months_ct; j += 1) {
            months[j].style.width = (100 / month_to_show) + '%';
        }
    }
    respondCanvas();
}
function graphTabOnClick()
{
    indexGraphStat();
    respondCanvas();
}

function homeBoot()
{
    for (var i=0; i < tabs.length; i++){
        var thisTabs = tabs[i],
            tabsHead = thisTabs.querySelectorAll(".tabs-head li"),
            tabsContent = thisTabs.querySelectorAll(".tabs-content");
        for (var j=0; j < tabsHead.length; j++){
            tabsHead[j].onclick = function(){
                var el = this;
                for (var k=0; k < tabsHead.length; k++){
                    tabsHead[k].classList.remove('active');
                }
                for (var k=0; k < tabsContent.length; k++){
                    tabsContent[k].classList.remove('active');
                }
                el.classList.add('active');
                var target = this.dataset.target,
                    trigger = this.dataset.trigger;
                var tabsTarget = thisTabs.querySelectorAll(target);
                tabsTarget[0].classList.add('active');

                if (trigger != undefined && typeof window[trigger]() === "function") {
                    window[trigger]();
                }
            };
        }
    }

    window.addEventListener("resize", indexGraphStat);
    graphTabOnClick();

    document.addEventListener("DOMContentLoaded", function (event) {
        var cols = document.querySelectorAll('#grabOrder li');
        [].forEach.call(cols, grabHandlers);
    });
}