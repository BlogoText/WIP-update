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

require_once 'inc/boot.php';
require_once BT_ROOT_ADMIN.'inc/quickly.php';
require_once BT_ROOT.'inc/http.php';

$vars = array(
    // from bookmarklet
    'url' => (string)filter_input(INPUT_GET, 'url'),
    // edit
    'id' => (string)filter_input(INPUT_GET, 'id'),
    // filtre
    'filtre' => (string)filter_input(INPUT_GET, 'filtre'),
    // search
    'q' => (string)filter_input(INPUT_GET, 'q'),
    // formulaire
    'action' => (string)filter_input(INPUT_POST, 'action'),

    'form' => 'min',
);

// make ?url= working
if (!empty($vars['url'])) {
    $vars['action'] = 'step-1';
    $_POST = array(
        'action' => 'step-1',
        'quickly' => urldecode($vars['url']),
    );
}

$form_submission = null;

function quickly_handle_url($url, $save_file = false)
{
    // Download file
    $downloaded = download_get(array($url), 15, false);
    if (!is_array($downloaded) || !isset($downloaded[$url])) {
        return false;
    }
    $downloaded = $downloaded[$url];
    var_dump($downloaded);

    $return = array(
        'title' => '',
        'path' => '',
        'url' => '',
    );

    if (!isset($downloaded['headers']['content-type'])) {
        $downloaded['headers']['content-type'] = '';
    } else if (!is_string($downloaded['headers']['content-type'])) {
        if (is_array($downloaded['headers']['content-type'])) {
            $downloaded['headers']['content-type'] = $downloaded['headers']['content-type'][count($downloaded['headers']['content-type']) - 1];
        } else {
            $downloaded['headers']['content-type'] = '';
        }
    }
    var_dump($downloaded['headers']['content-type']);
    /*
    $cntType = (isset($repHeaders['content-type'])) ?
                    (is_array($repHeaders['content-type']) ?
                        $repHeaders['content-type'][count($repHeaders['content-type']) - 1]
                      : $repHeaders['content-type'])
                  : 'text/';
    $cntType = (is_array($cntType)) ? $cntType[0] : $cntType;
    */

    // picture
    if (strpos($downloaded['headers']['content-type'], 'image/') === 0) {
        $return['title'] = $GLOBALS['lang']['label_image'];
        if ($downloaded['headers']['content-type'] == 'image/svg+xml') {
            $return['title'] .= ' - SVG';
            $return['type'] = 'image';
            $return['url'] = $url;
        // handle jpg, png ...
        } else if (list($width, $height) = @getimagesize($url)) {
            $return['url'] = $url;
            $return['type'] = 'image';
            $return['title'] .= ' - '.$width.'x'.$height.'px ';
        }

    // Non-image NON-textual file (pdf…)
    } else if ($url_headers['content-type']($cntType, 'text/') !== 0 && strpos($cntType, 'xml') === false) {
        $return['type'] = 'file';

    // html
    } else if ($extFile['body']) {
        $return['type'] = 'html';
        $charset = '';

        // a textual document: parse it for any <title> element (+charset for title decoding ; fallback=UTF-8) ; fallback=$url
        // Search for charset in the headers
        if (preg_match('#charset=(.*);?#', $cntType, $headerCharset) && $headerCharset[1]) {
            $charset = $headerCharset[1];
        // If not found, search it in HTML
        } elseif (preg_match('#<meta .*charset=(["\']?)([^\s>"\']*)([\'"]?)\s*/?>#Usi', $extFile['body'], $metaCharset) && $metaCharset[2]) {
            $charset = $metaCharset[2];
        // try to find
        } else {
            // $charset = ((bool)preg_match('//u', $extFile['body'])) ? 'utf-8' : '';
            $charset = is_utf8($extFile['body']) ? 'utf-8' : '';
        }

        // convert body in UTF8 
        if (strtolower($charset) != 'utf-8') {
            $extFile['body'] = utf8_encode($extFile['body']);
        }
        // HTML decode it
        $extFile = html_entity_decode($extFile['body'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // search for <title>
        preg_match('#<title ?[^>]*>(.*)</title>#Usi', $extFile, $titles);
        if ($titles[1]) {
            $return['title'] = trim($titles[1]);
        }
    } else {
        return false;
    }

    // download file
    if ($GLOBALS['quickly_download_files'] == 2
     && ($return['type'] == 'file' || $return['type'] == 'image')
    ) {
        
    }

    return $return;
}

// form submited ?
if (empty($vars['action'])) {
    // Nop, so get default datas
    $form_datas['values'] = quickly_form_default();
} else {
    // sanitize
    $form_datas = quickly_form_sanitize($_POST);

    // if no error
    // step 1 submitted
    if ($form_datas['values']['action'] == 'step-1') {
        $newId = date('YmdHis');

        // quickly is a link ? (check URL filter and HTTP
        if (strpos($form_datas['values']['quickly'], 'http') === 0
         && filter_var($form_datas['values']['quickly'], FILTER_VALIDATE_URL)
        ) {
            $type = 'link';
            $url = $form_datas['values']['quickly'];
            $title = '';

            $test = quickly_handle_url($url);
            var_dump($test);

            // var_dump($type);
            // set values for step 2
            $form_datas['values']['bt_title'] = $title;
            $form_datas['values']['bt_type'] = $type;
            $form_datas['values']['bt_url'] = $url;
            $form_datas['values']['bt_link'] = $url;
        // this is a note
        } else {
            // if multiline or quickly > 100, this is a content, else this is a title
            if (strlen($form_datas['values']['quickly']) > 100
             || strpos($form_datas['values']['quickly'], "/n") !== false
             || strpos($form_datas['values']['quickly'], "/r") !== false
             || strpos($form_datas['values']['quickly'], PHP_EOL) !== false
            ) {
                $form_datas['values']['bt_wiki_content'] = $form_datas['values']['quickly'];
            } else {
                $form_datas['values']['bt_title'] = $form_datas['values']['quickly'];
            }
            // set values for step 2
            $form_datas['values']['bt_type'] = 'note';
        }

        // go to step 2
        $form_datas['values']['action'] == 'step-2';
    // step 2
    } else if ($form_datas['values']['action'] == 'step-2') {
        // unset not relevant error
        unset(
            $form_datas['errors']['ID'],
            $form_datas['errors']['bt_id'],
            $form_datas['errors']['bt_link'],
            $form_datas['errors']['bt_title'],
            $form_datas['errors']['bt_tags'],
            $form_datas['errors']['bt_status'],
            $form_datas['errors']['bt_wiki_content'],
            $form_datas['errors']['quickly'],
            $form_datas['errors']['action']
        );

        if (empty($form_datas['values']['bt_id'])) {
            $form_datas['values']['bt_id'] = date('YmdHis');
        }
        if (isset($form_datas['errors']) && count($form_datas['errors']) === 0) {
            unset($form_datas['errors']);
        }

        
        if (!isset($form_datas['errors'])) {
            $form_datas['values']['bt_content'] = markup(clean_txt($form_datas['values']['bt_wiki_content']));
            $form_submission = quickly_db_push($form_datas['values']);
            var_dump($form_submission);
        }
    } else if ($form_datas['values']['action'] == 'edit') {
        var_dump($form_datas);
        $form_datas['values']['bt_content'] = markup(clean_txt($form_datas['values']['bt_wiki_content']));
        // remove false errors
        // var_dump($_POST);
        // quickly_db_upd();
        $form_submission = quickly_db_upd($form_datas['values']);
        var_dump($form_submission);
    }

    if (1 == 2) {
        $link = init_post_link2();
        $errorsForm = quickly_form_check($link);
        $step = 'edit';
        if (!$errorsForm) {
            // URL est un fichier !html !js !css !php ![vide] && téléchargement de fichiers activé :
            if (!$vars['is_it_edit'] && $GLOBALS['quickly_download_files'] >= 1) {
                // quickly_download_files : 0 = never ; 1 = always ; 2 = ask with checkbox
                if ($vars['add_to_files']) {
                    $_POST['fichier'] = $link['bt_link'];
                    $file = init_post_fichier();
                    $errors = valider_form_fichier($file);

                    $GLOBALS['files_list'] = file_get_array(FILE_VHOST_FILES_DB);
                    file_handler_add($file, 'download', $link['bt_link']);
                }
            }
            exit();
            // quickly_form_proceed($link);
        }
    }
}

$arr = array();
if (empty($vars['action'])) {
    $db_request = 'SELECT * FROM links ';

    if ($vars['filtre']) {
        // for "tags" the requests is "tag.$search" : here we split the type of search and what we search.
        $type = substr($vars['filtre'], 0, -strlen(strstr($vars['filtre'], '.')));
        $search = htmlspecialchars(ltrim(strstr($vars['filtre'], '.'), '.'));

        // by month
        if (preg_match('#^\d{6}(\d{1,8})?$#', $vars['filtre'])) {
            $db_request .= '
                 WHERE bt_id LIKE ?
                 ORDER BY bt_id DESC';
            $arr = db_items_list($db_request, array($vars['filtre'].'%'), 'links');
        // publié ou brouillon
        } elseif ($vars['filtre'] == 'draft' || $vars['filtre'] == 'pub') {
            $db_request .= '
                 WHERE bt_statut = ?
                 ORDER BY bt_id DESC';
            $arr = db_items_list($db_request, array((int)($vars['filtre'] == 'draft')), 'links');
        // tag
        } elseif ($type == 'tag' && $search) {
            $db_request .= '
                 WHERE bt_tags LIKE ?
                       OR bt_tags LIKE ?
                       OR bt_tags LIKE ?
                       OR bt_tags LIKE ?
                 ORDER BY bt_id DESC';
            $arr = db_items_list($db_request, array($search, $search.',%', '%, '.$search, '%, '.$search.', %'), 'links');
        // default
        } else {
            $db_request .= '
                 ORDER BY bt_id DESC
                 LIMIT '.($GLOBALS['max_linx_admin'] + 0);
            $arr = db_items_list($db_request, array(), 'links');
        }
    // search request
    } elseif ($vars['q']) {
        $arr = search_engine_parse_query($vars['q']);
        $sqlWhere = implode(array_fill(0, count($arr), '(bt_content || bt_title || bt_link) LIKE ?'), 'AND');
        $db_request .= '
             WHERE '.$sqlWhere.'
             ORDER BY bt_id DESC';
        $arr = db_items_list($db_request, $arr, 'links');
    // specific
    } elseif ($vars['id']) {
        $db_request .= '
             WHERE bt_id = ?';
        $arr = db_items_list($db_request, array($vars['id']), 'links');
    // default
    } else {
        $db_request .= '
             ORDER BY bt_id DESC
             LIMIT '.($GLOBALS['quickly_items_per_page'] + 0);
        $arr = db_items_list($db_request, array(), 'links');
    }
}

// push datas to form
if (!empty($vars['id'])) {
    $db_request = '
        SELECT *
          FROM links
         WHERE bt_id = ?';
    $arr = db_items_list($db_request, array($vars['id']), 'links');
    // var_dump($arr);
    // $form_datas['values'] = quickly_item_db_to_form($arr[0]);
    $form_datas['values'] = $arr[0];
    $form_datas['values']['action'] = 'edit';
}


/**
 * echo
 */
echo tpl_get_html_head($GLOBALS['lang']['my_links'], true);
// var_dump($_POST);
echo '<div id="axe">';
echo '<div id="page">';

// show form
// var_dump($form_datas);
echo quickly_form($form_datas);

if ($form_submission !== null) {
    var_dump($form_submission);
}

// show list
if (empty($vars['ID'])) {

    // Subnav
    if (empty($vars['url'])) {
        echo '<div id="subnav">';
        echo tpl_filter_form('links', htmlspecialchars($vars['filtre']));
        echo '<button class="btn btn-flat ico-list" onclick="quicklySwitchStyle();" title="Switch style"></button>';
        if (empty($vars['action'])) {
            echo tpl_items_counter('link', count($arr), db_items_list_count('SELECT count(*) AS counter FROM links', array(), 'links'));
        }
        echo '</div>';
    }

    echo '<div class="vertical-axis" id="stream">';
    foreach ($arr as $link) {
        echo '<div class="item">';
        echo quickly_show_item($link);
        echo '</div>';
    }
    echo '</div>';
    // if (!$vars['ajout']) {
        echo '<a id="fab" class="add-link" href="links.php?ajout" title="'.$GLOBALS['lang']['label_link_add'].'">'.$GLOBALS['lang']['label_link_add'].'</a>';
    // }
}

echo '<script>';

?>
function ValidURL(str) {
  var pattern = new RegExp('^(https?:\/\/)','i');
  return (pattern.test(str) !== false);
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

function quicklySwitchStyle()
{
    var stream = document.getElementById("stream");
    var streamClass = '';

    if (stream.classList.contains("va1")) {
        streamClass = 'va1';
    } else if (stream.classList.contains("va2")) {
        streamClass = 'va2';
    }

    if (streamClass == 'va1') {
        stream.classList.remove("va1");
        stream.classList.add("va2");
    } else if (streamClass == 'va2') {
        stream.classList.remove("va2");
    } else {
        stream.classList.add("va1");
    }
    return false;
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
var textarea = document.getElementById("testttt");
var textareainfo = document.getElementById("testtttt");
var textareaMinHeight = textarea.style.minHeight;
var textareaHeight = 
textarea.style.minHeight = '10px';
textarea.addEventListener("keyup", test);
// textarea.addEventListener("keypress", test);

<?php
if (4 == 1) {
    echo 'document.getElementById("url").addEventListener("focus", hideFAB, false);';
    echo 'document.getElementById("url").addEventListener("blur", unHideFAB, false);';
    echo 'if (window.getComputedStyle(document.querySelector("#nav > ul")).position != "absolute") {';
    echo '    document.getElementById("url").focus();';
    echo '}';
}
echo 'var scrollPos = 0;';
// echo 'window.addEventListener("scroll", function(){ scrollingFabHideShow(); });';
echo '</script>';

echo '</div>';
echo '</div>';
echo tpl_get_footer();
