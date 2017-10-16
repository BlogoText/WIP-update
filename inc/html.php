<?php
/**
 * BlogoText
 * https://blogotext.org/
 * https://github.com/BlogoText/blogotext/
 *
 * 2006      Frederic Nassar
 * 2010-2016 Timo Van Neerden
 * 2016-.... Mickaël Schoentgen and the community
 *
 * Under MIT / X11 Licence
 * http://opensource.org/licenses/MIT
 */


/**
 * used by markup()
 * convert a BBCode link to HTML <a>
 * with a check on URL
 *
 * @params array $matches, array from preg_replace_callback
 * @return string
 */
function markup_clean_href($matches)
{
    // var_dump($matches);
    $allowed = array('http://', 'https://', 'ftp://');
    // if not a valid url, return the string
    if ((
            !filter_var($matches['2'], FILTER_VALIDATE_URL)
         || !preg_match('#^('.join('|', $allowed).')#i', $matches['2'])
        )
     && !preg_match('/^#[\w-_]+$/i', $matches['2']) // allowing [text|#look-at_this]
    ) {
        return $matches['0'];
    }
    // handle different case
    if (empty(trim($matches['1']))) {
        return $matches['1'].'<a href="'.$matches['2'].'">'.$matches['2'].'</a>';
    } else {
        return '<a href="'.$matches['2'].'">'.$matches['1'].'</a>';
    }
}

/**
 * convert text with BBCode (more or less BBCode) to HTML
 *
 * @params string $text
 * @return string
 */
function markup($text)
{
    $text = preg_replace('#\[([^|]+)\|(\s*javascript.*)\]#i', '$1', $text);
    $text = preg_replace("/(\r\n|\r\n\r|\n|\n\r|\r)/", "\r", $text);
    $tofind = array(
        // /* regex URL     */ '#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s\)\(]+))#i',
        // /* a href        */ '#\[([^[]+)\|([^[]+)\]#',
        /* strong        */ '#\[b\](.*?)\[/b\]#s',
        /* italic        */ '#\[i\](.*?)\[/i\]#s',
        /* strike        */ '#\[s\](.*?)\[/s\]#s',
        /* underline     */ '#\[u\](.*?)\[/u\]#s',
        /* quote         */ '#\[quote\](.*?)\[/quote\]#s',
        /* code          */ '#\[code\]\[/code\]#s',
        /* code=language */ '#\[code=(\w+)\]\[/code\]#s',
    );
    $toreplace = array(
        // /* regex URL     */ '$1<a href="$2">$2</a>',
        // /* a href        */ '<a href="$2">$1</a>',
        /* strong        */ '<b>$1</b>',
        /* italic        */ '<em>$1</em>',
        /* strike        */ '<del>$1</del>',
        /* underline     */ '<u>$1</u>',
        /* quote         */ '<blockquote>$1</blockquote>'."\r",
        /* code          */ '<prebtcode></prebtcode>'."\r",
        /* code=language */ '<prebtcode data-language="$1"></prebtcode>'."\r",
    );
    preg_match_all('#\[code(=(\w+))?\](.*?)\[/code\]#s', $text, $code_contents, PREG_SET_ORDER);
    $texte_formate = preg_replace('#\[code(=(\w+))?\](.*?)\[/code\]#s', '[code$1][/code]', $text);
    $texte_formate = preg_replace($tofind, $toreplace, $texte_formate);
    $texte_formate = preg_replace_callback('#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s\)\(]+))#i', 'markup_clean_href', $texte_formate);
    $texte_formate = preg_replace_callback('#\[([^[]+)\|([^[]+)\]#', 'markup_clean_href', $texte_formate);
    $texte_formate = parse_texte_paragraphs($texte_formate);
    $texte_formate = parse_texte_code($texte_formate, $code_contents);
    return $texte_formate;
}

/**
 *
 */
function parse_texte_code($texte, $code_before)
{
    if ($code_before) {
        preg_match_all('#<prebtcode( data-language="\w+")?></prebtcode>#s', $texte, $code_after, PREG_SET_ORDER);
        foreach ($code_before as $i => $code) {
            $pos = strpos($texte, $code_after[$i][0]);
            if ($pos !== false) {
                 $texte = substr_replace($texte, '<pre'.((isset($code_after[$i][1])) ? $code_after[$i][1] : '').'><code>'.htmlspecialchars(htmlspecialchars_decode($code_before[$i][3])).'</code></pre>', $pos, strlen($code_after[$i][0]));
            }
        }
    }
    return $texte;
}

/**
 *
 */
function parse_texte_paragraphs($texte)
{
    // trims empty lines at begining and end of raw texte
    $texte_formate = preg_replace('#^(\r|\n|<br>|<br/>|<br />){0,}(.*?)(\r|<br>|<br/>|<br />){0,}$#s', '$2', $texte);
    // trick to make <hr/> elements be recognized by parser
    $texte_formate = preg_replace('#<hr */?>#is', '<hr></hr>', $texte);
    $block_elements = 'address|article|aside|audio|blockquote|canvas|dd|li|div|[oud]l|fieldset|fig(caption|ure)|footer|form|h[1-6]|header|hgroup|hr|main|nav|noscript|output|p|pre|prebtcode|section|table|thead|tbody|tfoot|tr|td|video';

    $texte_final = '';
    $finished = false;
    // if text begins with block-element, remove it and goes on
    while ($finished === false) {
        $matches = array();
        // we have a block element
        if (preg_match('#^<('.$block_elements.') ?.*?>(.*?)</(\1)>#s', $texte_formate, $matches)) {
            // extract the block element
            $texte_retire = $matches[0];
            // parses inner text for nl2br()
            $texte_nl2br = "\n".nl2br($texte_retire)."\n";
            // removes <br/> that follow a block (ie: <block><br> → <block>) and add it to the final text
            $texte_final .= preg_replace('#(</?('.$block_elements.') ?.*?>)(<br ?/?>)(\n?\r?)#s', '$1$3$5', $texte_nl2br);
            // saves the remaining text
            $texte_restant = preg_replace('#^<('.$block_elements.') ?.*?>(.*?)</(\1)>#s', '', $texte_formate, 1);
            // again, removes empty lines+spaces at begin or end TODO : save the lines to make multiple "<br/>" spaces (??)
            $texte_restant = preg_replace('#^(\r|\n|<br>|<br/>|<br />){0,}(.*?)(\r|<br>|<br/>|<br />){0,}$#s', '$2', $texte_restant);
            // if no matches for block elements, we are finished
            $finished = (strlen($texte_retire) === 0) ? true : false;
        } else {
            // we have an inline element (or text)
            // grep the text until newline OR new block element do AND set it in <p></p>
            $texte_restant = preg_replace('#^(.*?)(\r\r|<('.$block_elements.') ?.*?>)#s', '$2', $texte_formate, 1);
            // saves the text we just "greped"
            $texte_retire = trim(substr($texte_formate, 0, -strlen($texte_restant)));
            // IF greped text is empty: no text or no further block element (or new line)
            if (strlen($texte_retire) === 0) {
                // remaining text is NOT empty : keep it in a <p></p>
                if (strlen($texte_restant) !== 0) {
                    $texte_final .= "\n".'<p>'.nl2br($texte_restant).'</p>'."\n";
                }
                // since the entire remaining text is in a new <p></p>, we are finished
                $finished = true;

            // FI IF greped text is not empty: keep it in a new <p></p>.
            } else {
                $texte_final .= "\n".'<p>'.nl2br($texte_retire).'</p>'."\n";
            }
        }

        //  again, removes empty lines+spaces at begin or end
        $texte_restant = preg_replace('#^(\r|\n|<br>|<br/>|<br />){0,}(.*?)(\r|<br>|<br/>|<br />){0,}$#s', '$2', $texte_restant);
        // loops on the text, to find the next element.
        $texte_formate = $texte_restant;
    }
    // retransforms <hr/>
    $texte_final = preg_replace('#<hr></hr>#', '<hr/>', $texte_final);
    return $texte_final;
}


/**
 *
 */
function erreurs($erreurs)
{
    $html = '';
    if ($erreurs) {
        $html .= '<div id="erreurs">'.'<strong>'.$GLOBALS['lang']['erreurs'].'</strong> :' ;
        $html .= '<ul><li>';
        $html .= implode('</li><li>', $erreurs);
        $html .= '</li></ul></div>'."\n";
    }
    return $html;
}

/**
 *
 */
function erreur($message)
{
      echo '<p class="erreurs">'.$message.'</p>'."\n";
}

/**
 *
 */
function search_engine_form()
{
    $requete='';
    if (isset($_GET['q'])) {
        $requete = htmlspecialchars(stripslashes($_GET['q']));
    }
    $return = '<form action="?" method="get" id="search">'."\n";
    $return .= '<input id="q" name="q" type="search" size="20" value="'.$requete.'" placeholder="'.$GLOBALS['lang']['placeholder_search'].'" accesskey="f" />'."\n";
    $return .= '<button id="input-rechercher" type="submit">'.$GLOBALS['lang']['search'].'</button>'."\n";
    if (isset($_GET['mode'])) {
        $return .= '<input id="mode" name="mode" type="hidden" value="'.htmlspecialchars(stripslashes($_GET['mode'])).'"/>'."\n";
    }
    $return .= '</form>'."\n\n";
    return $return;
}


/**
 *
 */
function tags_aside($mode)
{
    if ($GLOBALS['use_tags'] != '1') {
        return '';
    }

    $where = ($mode == 'links') ? 'links' : 'articles';
    $ampmode = ($mode == 'links') ? '&amp;mode=links' : '';

    $liste = tags_list_all($where, 1);

    // attach non-diacritic versions of tag, so that "é" does not pass after "z" and re-indexes
    foreach ($liste as $tag => $nb) {
        $liste[$tag] = array(diacritique(trim($tag)), $nb);
    }
    // sort tags according non-diacritics versions of tags
    $liste = array_reverse(sort_by_subkey($liste, 0));
    $uliste = '<ul>';

    // create the <ul> with "tags (nb) "
    foreach ($liste as $tag => $nb) {
        if ($tag != '' and $nb[1] > 1) {
            $uliste .= '<li><a href="?tag='.urlencode(trim($tag)).$ampmode.'" rel="tag">'.ucfirst($tag).' ('.$nb[1].')</a><a href="rss.php?tag='.urlencode($tag).$ampmode.'" rel="alternate"></a></li>';
        }
    }
    $uliste .= '</ul>';
    return $uliste;
}


/**
 * remove params from url
 *
 * @param string $param
 * @return string url
 */
function remove_url_param($param)
{
    if (isset($_GET[$param])) {
        return str_replace(
            array(
                '&'.$param.'='.$_GET[$param],
                '?'.$param.'='.$_GET[$param],
                '?&amp;',
                '?&',
                '?',
            ),
            array('','?','?','?',''),
            '?'.$_SERVER['QUERY_STRING']
        );
    } elseif (isset($_SERVER['QUERY_STRING'])) {
        return $_SERVER['QUERY_STRING'];
    }
    return '';
}

/**
 *
 */
function lien_pagination()
{
    if (!isset($GLOBALS['param_pagination']) or isset($_GET['d']) or isset($_GET['liste']) or isset($_GET['id'])) {
        return '';
    } else {
        $nb_par_page = (int)$GLOBALS['param_pagination']['nb_par_page'];
    }

    $page_courante = (isset($_GET['p']) and is_numeric($_GET['p'])) ? (int)$_GET['p'] : 0;
    $qstring = remove_url_param('p');
    if (!empty($qstring)) {
        $qstring .= '&amp;';
    }

    $db_req = '';
    $db_params = array();
    if (isset($_GET['mode']) && $_GET['mode'] == 'links') {
        $db_req = 'SELECT count(ID) AS counter FROM links WHERE bt_statut=1';
    } else {
        $db_req = 'SELECT count(ID) AS counter FROM articles WHERE bt_date <= '.date('YmdHis').' and bt_statut=1';
    }
    if (isset($_GET['tag'])) {
        $db_req .= ' and ( bt_tags LIKE ? OR bt_tags LIKE ? OR bt_tags LIKE ? OR bt_tags LIKE ? )';
        $db_params = array( $_GET['tag'],$_GET['tag'].', %','%, '.$_GET['tag'].', %','%, '.$_GET['tag'] );
    }
    $nb = (int)db_items_list_count($db_req, $db_params);

    $lien_precede = '';
    $lien_suivant = '';
    // -1 because ?p=0 is the first
    $total_page = (int)ceil($nb / $nb_par_page) - 1;

    // page sup ?
    if ($page_courante < 0) {
        $lien_suivant = '<a href="?'.$qstring.'p=0" rel="next">'.$GLOBALS['lang']['label_next'].'</a>';
    } else if ($page_courante < $total_page) {
        $lien_suivant = '<a href="?'.$qstring.'p='.($page_courante+1).'" rel="next">'.$GLOBALS['lang']['label_next'].'</a>';
    }

    // page inf ?
    if ($page_courante > $total_page) {
        $lien_precede = '<a href="?'.$qstring.'p='.$total_page.'" rel="prev">'.$GLOBALS['lang']['label_previous'].'</a>';
    } else if ($page_courante <= $total_page && $page_courante > 0) {
        $lien_precede = '<a href="?'.$qstring.'p='.($page_courante-1).'" rel="prev">'.$GLOBALS['lang']['label_previous'].'</a>';
    }

    return '<p class="pagination">'.$lien_precede.$lien_suivant.'</p>';
}

/**
 *
 */
function liste_tags($billet, $html_link)
{
    $mode = ($billet['bt_type'] == 'article') ? '' : '&amp;mode=links';
    $liste = '';
    if (!empty($billet['bt_tags'])) {
        $tag_list = explode(', ', $billet['bt_tags']);
        // remove diacritics, so that "ééé" does not passe after "zzz" and re-indexes
        foreach ($tag_list as $i => $tag) {
            $tag_list[$i] = array('t' => trim($tag), 'tt' => diacritique(trim($tag)));
        }
        $tag_list = array_reverse(sort_by_subkey($tag_list, 'tt'));

        foreach ($tag_list as $tag) {
            $tag = trim($tag['t']);
            if ($html_link == 1) {
                $liste .= '<a href="?tag='.urlencode($tag).$mode.'" rel="tag">'.$tag.'</a>';
            } else {
                $liste .= $tag.' ';
            }
        }
    }
    return $liste;
}
