<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BlogoText/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***


/**
 *
 */
function hidden_input($nom, $valeur, $id = 0)
{
    $id = ($id === 0) ? '' : ' id="'.$nom.'"';
    $form = '<input type="hidden" name="'.$nom.'"'.$id.' value="'.$valeur.'" />'."\n";
    return $form;
}

/**
 *
 */
function s_color($color)
{
    return '<button type="button" onclick="editorInsertTag(this, \'[color='.$color.']\',\'[/color]\');"><span style="background:'.$color.';"></span></button>';
}

/**
 *
 */
function s_size($size)
{
    return '<button type="button" onclick="editorInsertTag(this, \'[size='.$size.']\',\'[/size]\');"><span style="font-size:'.$size.'pt;">'.$size.'. Ipsum</span></button>';
}

/**
 *
 */
function s_u($char)
{
    return '<button type="button" onclick="editorInsertChar(this, \''.$char.'\');"><span>'.$char.'</span></button>';
}

/**
 *
 */
function form_bb_toolbar($extended = false)
{
    $html = '';
    $html .= '<p class="formatbut">';
    $html .= '<button id="button01" class="but" type="button" title="'.$GLOBALS['lang']['bouton-gras'].'" onclick="editorInsertTag(this, \'[b]\',\'[/b]\');"><span></span></button>';
    $html .= '<button id="button02" class="but" type="button" title="'.$GLOBALS['lang']['bouton-ital'].'" onclick="editorInsertTag(this, \'[i]\',\'[/i]\');"><span></span></button>';
    $html .= '<button id="button03" class="but" type="button" title="'.$GLOBALS['lang']['bouton-soul'].'" onclick="editorInsertTag(this, \'[u]\',\'[/u]\');"><span></span></button>';
    $html .= '<button id="button04" class="but" type="button" title="'.$GLOBALS['lang']['bouton-barr'].'" onclick="editorInsertTag(this, \'[s]\',\'[/s]\');"><span></span></button>';

    if ($extended) {
        $html .= '<span class="spacer"></span>';
        // bouton des couleurs
        $html .= '<span id="button13" class="but but-dropdown" title=""><span></span><span class="list list-color">'
                .s_color('black').s_color('gray').s_color('silver').s_color('white')
                .s_color('blue').s_color('green').s_color('red').s_color('yellow')
                .s_color('fuchsia').s_color('lime').s_color('aqua').s_color('maroon')
                .s_color('purple').s_color('navy').s_color('teal').s_color('olive')
                .s_color('#ff7000').s_color('#ff9aff').s_color('#a0f7ff').s_color('#ffd700')
                .'</span></span>';

        // boutons de la taille de caractère
        $html .= '<span id="button14" class="but but-dropdown" title=""><span></span><span class="list list-size">'
                .s_size('9').s_size('12').s_size('16').s_size('20')
                .'</span></span>';

        // quelques caractères unicode
        $html .= '<span id="button15" class="but but-dropdown" title=""><span></span><span class="list list-spechr">'
                .s_u('æ').s_u('Æ').s_u('œ').s_u('Œ').s_u('é').s_u('É').s_u('è').s_u('È').s_u('ç').s_u('Ç').s_u('ù').s_u('Ù').s_u('à').s_u('À').s_u('ö').s_u('Ö')
                .s_u('…').s_u('«').s_u('»').s_u('±').s_u('≠').s_u('×').s_u('÷').s_u('ß').s_u('®').s_u('©').s_u('↓').s_u('↑').s_u('←').s_u('→').s_u('ø').s_u('Ø')
                .s_u('☠').s_u('☣').s_u('☢').s_u('☮').s_u('★').s_u('☯').s_u('☑').s_u('☒').s_u('☐').s_u('♫').s_u('♬').s_u('♪').s_u('♣').s_u('♠').s_u('♦').s_u('❤')
                .s_u('♂').s_u('♀').s_u('☹').s_u('☺').s_u('☻').s_u('♲').s_u('⚐').s_u('⚠').s_u('☂').s_u('√').s_u('∑').s_u('λ').s_u('π').s_u('Ω').s_u('№').s_u('∞')
                .'</span></span>';

        $html .= '<span class="spacer"></span>';
        $html .= '<button id="button05" class="but" type="button" title="'.$GLOBALS['lang']['bouton-left'].'" onclick="editorInsertTag(this, \'[left]\',\'[/left]\');"><span></span></button>';
        $html .= '<button id="button06" class="but" type="button" title="'.$GLOBALS['lang']['bouton-center'].'" onclick="editorInsertTag(this, \'[center]\',\'[/center]\');"><span></span></button>';
        $html .= '<button id="button07" class="but" type="button" title="'.$GLOBALS['lang']['bouton-right'].'" onclick="editorInsertTag(this, \'[right]\',\'[/right]\');"><span></span></button>';
        $html .= '<button id="button08" class="but" type="button" title="'.$GLOBALS['lang']['bouton-justify'].'" onclick="editorInsertTag(this, \'[justify]\',\'[/justify]\');"><span></span></button>';

        $html .= '<span class="spacer"></span>';
        $html .= '<button id="button11" class="but" type="button" title="'.$GLOBALS['lang']['bouton-imag'].'" onclick="editorInsertTag(this, \'[img]\',\'|alt[/img]\');"><span></span></button>';
        $html .= '<button id="button16" class="but" type="button" title="'.$GLOBALS['lang']['bouton-liul'].'" onclick="editorInsertChar(this, \'\n\n** element 1\n** element 2\n\');"><span></span></button>';
        $html .= '<button id="button17" class="but" type="button" title="'.$GLOBALS['lang']['bouton-liol'].'" onclick="editorInsertChar(this, \'\n\n## element 1\n## element 2\n\');"><span></span></button>';
    }

    $html .= '<span class="spacer"></span>';
    $html .= '<button id="button09" class="but" type="button" title="'.$GLOBALS['lang']['bouton-lien'].'" onclick="editorInsertTag(this, \'[\',\'|http://]\');"><span></span></button>';
    $html .= '<button id="button10" class="but" type="button" title="'.$GLOBALS['lang']['bouton-cita'].'" onclick="editorInsertTag(this, \'[quote]\',\'[/quote]\');"><span></span></button>';
    $html .= '<button id="button12" class="but" type="button" title="'.$GLOBALS['lang']['bouton-code'].'" onclick="editorInsertTag(this, \'[code]\',\'[/code]\');"><span></span></button>';

    $html .= '<button title="Go in full screen" onclick="editor_fullscreen(this);" class="but ico-screen" type="button"></button>';

    $html .= '</p>';

    $html .= '
        <script>
            function findAncestor (el, cls) {
                while ((el = el.parentElement) && !el.classList.contains(cls));
                return el;
            }
            function editor_fullscreen(btn)
            {
                var elem = btn.closest(".input");
                var fullScreen = document.fullscreenEnabled || document.mozFullscreenEnabled || document.webkitIsFullScreen ? true : false;

                if (!fullScreen) {
                    if (elem.requestFullscreen) {
                      elem.requestFullscreen();
                    } else if (elem.mozRequestFullScreen) {
                      elem.mozRequestFullScreen();
                    } else if (elem.webkitRequestFullscreen) {
                      elem.webkitRequestFullscreen();
                    }
                } else {
                   if(document.cancelFullScreen) {
                       document.cancelFullScreen();
                    } else if(document.mozCancelFullScreen) {
                        document.mozCancelFullScreen();
                    } else if(document.webkitCancelFullScreen) {
                        document.webkitCancelFullScreen();
                    }
                }
                return false;
            }
        </script>';
    return $html;
}
