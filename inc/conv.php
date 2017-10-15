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
function title_url($title)
{
    return trim(diacritique($title), '-');
}

/**
 *
 */
function diacritique($texte)
{
    $texte = strip_tags($texte);
    $texte = html_entity_decode($texte, ENT_QUOTES, 'UTF-8'); // &eacute => é ; é => é ; (uniformize)
    $texte = htmlentities($texte, ENT_QUOTES, 'UTF-8'); // é => &eacute;
    $texte = preg_replace('#&(.)(acute|grave|circ|uml|cedil|tilde|ring|slash|caron);#', '$1', $texte); // &eacute => e
    $texte = preg_replace('#(\t|\n|\r)#', ' ', $texte); // \n, \r => spaces
    $texte = preg_replace('#&([a-z]{2})lig;#i', '$1', $texte); // œ => oe ; æ => ae
    $texte = preg_replace('#&[\w\#]*;#U', '', $texte); // remove other entities like &quote, &nbsp.
    $texte = preg_replace('#[^\w -]#U', '', $texte); // keep only ciffers, letters, spaces, hyphens.
    $texte = strtolower($texte); // to lower case
    $texte = preg_replace('#[ ]+#', '-', $texte); // spaces => hyphens
    return $texte;
}

/**
 *
 */
function date_formate($id, $format_force = '')
{
    $retour = '';
    $date = decode_id($id);
        $day_l = day_en_lettres($date['day'], $date['month'], $date['year']);
        $month_l = month_en_lettres($date['month']);
        $format = array (
            $date['day'].'/'.$date['month'].'/'.$date['year'],          // 14/01/1983
            $date['month'].'/'.$date['day'].'/'.$date['year'],          // 01/14/1983
            $date['day'].' '.$month_l.' '.$date['year'],                // 14 janvier 1983
            $day_l.' '.$date['day'].' '.$month_l.' '.$date['year'],    // friday 14 janvier 1983
            $day_l.' '.$date['day'].' '.$month_l,                       // friday 14 janvier
            $month_l.' '.$date['day'].', '.$date['year'],               // janvier 14, 1983
            $day_l.', '.$month_l.' '.$date['day'].', '.$date['year'],  // friday, janvier 14, 1983
            $date['year'].'-'.$date['month'].'-'.$date['day'],          // 1983-01-14
            substr($day_l, 0, 3).'. '.$date['day'].' '.$month_l,        // ven. 14 janvier
        );

    if ($format_force != '') {
        $retour = $format[$format_force];
    } else {
        $retour = $format[$GLOBALS['format_date']];
    }
    return ucfirst($retour);
}

/**
 *
 */
function time_formate($id)
{
    $date = decode_id($id);
    $timestamp = mktime($date['hour'], $date['minutes'], $date['seconds'], $date['month'], $date['day'], $date['year']);
    $format = array (
        'H:i:s',    // 23:56:04
        'H:i',      // 23:56
        'h:i:s A',  // 11:56:04 PM
        'h:i A',    // 11:56 PM
    );
    return date($format[$GLOBALS['format_time']], $timestamp);
}

/**
 *
 */
function en_lettres($captchavalue)
{
    return $GLOBALS['lang'][strval($captchavalue)];
}

/**
 *
 */
function day_en_lettres($day, $month, $year)
{
    $date = date('w', mktime(0, 0, 0, $month, $day, $year));
    switch ($date) {
        case 0:
            return $GLOBALS['lang']['sunday'];
        break;
        case 1:
            return $GLOBALS['lang']['monday'];
        break;
        case 2:
            return $GLOBALS['lang']['tuesday'];
        break;
        case 3:
            return $GLOBALS['lang']['wednesday'];
        break;
        case 4:
            return $GLOBALS['lang']['thursday'];
        break;
        case 5:
            return $GLOBALS['lang']['friday'];
        break;
        case 6:
            return $GLOBALS['lang']['saturday'];
        break;
    }
    return $nom;
}

/**
 *
 */
function month_en_lettres($numero, $abbrv = 0)
{
    if ($abbrv == 1) {
        switch ($numero) {
            case '01':
                return $GLOBALS['lang']['jan.'];
            break;
            case '02':
                return $GLOBALS['lang']['feb.'];
            break;
            case '03':
                return $GLOBALS['lang']['mar.'];
            break;
            case '04':
                return $GLOBALS['lang']['apr.'];
            break;
            case '05':
                return $GLOBALS['lang']['may.'];
            break;
            case '06':
                return $GLOBALS['lang']['jun.'];
            break;
            case '07':
                return $GLOBALS['lang']['jul.'];
            break;
            case '08':
                return $GLOBALS['lang']['aug.'];
            break;
            case '09':
                return $GLOBALS['lang']['sept.'];
            break;
            case '10':
                return $GLOBALS['lang']['oct.'];
            break;
            case '11':
                return $GLOBALS['lang']['nov.'];
            break;
            case '12':
                return $GLOBALS['lang']['dec.'];
            break;
        }
    } else {
        switch ($numero) {
            case '01':
                return $GLOBALS['lang']['january'];
            break;
            case '02':
                return $GLOBALS['lang']['february'];
            break;
            case '03':
                return $GLOBALS['lang']['march'];
            break;
            case '04':
                return $GLOBALS['lang']['april'];
            break;
            case '05':
                return $GLOBALS['lang']['may'];
            break;
            case '06':
                return $GLOBALS['lang']['june'];
            break;
            case '07':
                return $GLOBALS['lang']['july'];
            break;
            case '08':
                return $GLOBALS['lang']['august'];
            break;
            case '09':
                return $GLOBALS['lang']['september'];
            break;
            case '10':
                return $GLOBALS['lang']['october'];
            break;
            case '11':
                return $GLOBALS['lang']['november'];
            break;
            case '12':
                return $GLOBALS['lang']['december'];
            break;
        }
    }
}

/**
 *
 */
function nombre_objets($nb, $type)
{
    switch ($nb) {
        case 0:
            return $GLOBALS['lang']['note_no_'.$type];
        case 1:
            return $nb.' '.$GLOBALS['lang']['label_'.$type];
        default:
            return $nb.' '.$GLOBALS['lang']['label_'.$type.'s'];
    }
}
