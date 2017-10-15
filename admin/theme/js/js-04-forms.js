
// [POC] setTimeout for css animation
/**
 * si pas de 2nd timeout pour remettre la classe la checkbox "scintille" avant de réapparaitre
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


var calculateContentHeight = function( el, scanAmount ) {
    var origHeight = el.style.height,
        height = el.offsetHeight,
        scrollHeight = el.scrollHeight,
        overflow = el.style.overflow;
    /// only bother if the el is bigger than content
    if ( height >= scrollHeight ) {
        /// check that our browser supports changing dimension
        /// calculations mid-way through a function call...
        el.style.height = (height + scanAmount) + 'px';
        /// because the scrollbar can cause calculation problems
        el.style.overflow = 'hidden';
        /// by checking that scrollHeight has updated
        if ( scrollHeight < el.scrollHeight ) {
            /// now try and scan the el's height downwards
            /// until scrollHeight becomes larger than height
            while (el.offsetHeight >= el.scrollHeight) {
                el.style.height = (height -= scanAmount)+'px';
            }
            /// be more specific to get the exact height
            while (el.offsetHeight < el.scrollHeight) {
                el.style.height = (height++)+'px';
            }
            /// reset the el back to it's original height
            el.style.height = origHeight;
            /// put the overflow back
            el.style.overflow = overflow;
            return height;
        }
    } else {
        return scrollHeight;
    }
}

var calculateHeight = function(el) {
    var ta = el,
        style = (window.getComputedStyle) ?
            window.getComputedStyle(ta) : ta.currentStyle,

        // This will get the line-height only if it is set in the css,
        // otherwise it's "normal"
        taLineHeight = 22,
        // Get the scroll height of the textarea
        taHeight = calculateContentHeight(ta, taLineHeight),
        // calculate the number of lines
        numberOfLines = Math.ceil(taHeight / taLineHeight);
    return taLineHeight;
};

class textarea_advanced {
    /**
     * input (obj) dom element
     * lh    (int) line-height, in px
     */
    constructor(input)
    {
        this.input = input;
        this.defaultMinHeight = this.input.style.minHeigh;
        this.lineHeight = 22;
        this.resize = false;
    }

    setLineHeight(ln)
    {
        if (!Number.isInteger(ln)) {
            return false;
        }
        this.lineHeight = ln;
    }

    _resize()
    {
        if (!this.resize) {
            return false;
        }
        // reset min height
        this.input.style.minHeight = '10px';

        var currentHeight = this.input.scrollHeight,
            countLines = Math.ceil(currentHeight / this.lineHeight),
            newHeight = (countLines * this.lineHeight);
        if (newHeight <= this.defaultMinHeight) {
            newHeight = this.defaultMinHeight;
        }
        this.input.style.minHeight = newHeight+"px";
    }

    resizeStop()
    {
        if (this.resize) {
            return false;
        }
        var self = this;
        this.input.removeEventListener("mousedown", function(){self.resize = false;}, false);
    }

    resizeStart()
    {
        if (this.resize) {
            return false;
        }
        // run a first time, in case of default value
        this._resize();
        var self = this;
        this.input.addEventListener("keyup", function(){self._resize()}, true);
        this.resize = true;
    }

    restore()
    {
        this.input.style.minHeight = this.defaultMinHeight;
    }

    counter()
    {
        var letters = this.input.value.length;
        return {
            'letters' : letters
        };
    }
}

function test(e)
{
    var http = false;
    var string = textarea.value;

    var keyValue = e.keyCode ? e.keyCode : e.charCode;
    console.log(keyValue);

    textarea.style.minHeight = '10px';
    if (ValidURL(string)) {
        http = true;
    } else if (string.length > 8 || keyValue == 13) {
        var currentHeight = textarea.scrollHeight,
            countLines = Math.ceil(currentHeight / 22);
        textarea.style.minHeight = (countLines*22)+"px";
    }
    if (http) {
        textareainfo.innerHTML = '('+textarea.value.length+' chars) link detected';
    } else {
        textareainfo.innerHTML = '('+textarea.value.length+' chars) Note detected';
    }
    return;
}
// var textarea = document.getElementById("testttt");
// var textareainfo = document.getElementById("testtttt");
// var textareaMinHeight = textarea.style.minHeight;
// var textareaHeight = 
// textarea.style.minHeight = '10px';
// textarea.addEventListener("keyup", test);
