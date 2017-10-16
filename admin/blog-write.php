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

require_once 'inc/boot.php';
require_once BT_ROOT_ADMIN.'inc/blog.php';

/**
 *
 */
function extact_words($text)
{
    $text = str_replace(array("\r", "\n", "\t"), array('', ' ', ' '), $text);
    $text = strip_tags($text);
    $text = preg_replace('#[!"\#$%&\'()*+,./:;<=>?@\[\]^_`{|}~«»“”…]#', ' ', $text);
    $text = trim(preg_replace('# {2,}#', ' ', $text));

    $words = explode(' ', $text);
    foreach ($words as $i => $word) {
        // remove short words & words with numbers
        if (strlen($word) <= 4 or preg_match('#\d#', $word)) {
            unset($words[$i]);
        } elseif (preg_match('#\?#', utf8_decode(preg_replace('#&(.)(acute|grave|circ|uml|cedil|tilde|ring|slash|caron);#', '$1', $word)))) {
            unset($words[$i]);
        }
    }

    // keep only words that occure at least 3 times
    $words = array_unique($words);
    $keywords = array();
    foreach ($words as $i => $word) {
        if (substr_count($text, $word) >= 3) {
            $keywords[] = $word;
        }
    }
    $keywords = array_unique($keywords);

    natsort($keywords);
    return implode($keywords, ', ');
}

/**
 *
 */
function post_markup($text)
{
    $text = preg_replace("/(\r\n|\r\n\r|\n|\n\r|\r)/", "\r", $text);
    $toFind = array(
        // Replace \r with \n when following HTML elements
        '#<(.*?)>\r#',

        // Jusitifications
        /* left    */ '#\[left\](.*?)\[/left\]#s',
        /* center  */ '#\[center\](.*?)\[/center\]#s',
        /* right   */ '#\[right\](.*?)\[/right\]#s',
        /* justify */ '#\[justify\](.*?)\[/justify\]#s',

        // Misc
        /* regex URL     */ '#([^"\[\]|])((http|ftp)s?://([^"\'\[\]<>\s]+))#i',
        /* a href        */ '#\[([^[]+)\|([^[]+)\]#',
        /* url           */ '#\[(https?://)([^[]+)\]#',
        /* [img]         */ '#\[img\](.*?)(\|(.*?))?\[/img\]#s',
        /* strong        */ '#\[b\](.*?)\[/b\]#s',
        /* italic        */ '#\[i\](.*?)\[/i\]#s',
        /* strike        */ '#\[s\](.*?)\[/s\]#s',
        /* underline     */ '#\[u\](.*?)\[/u\]#s',
        /* ul/li         */ '#\*\*(.*?)(\r|$)#s',  // br because of prev replace
        /* ul/li         */ '#</ul>\r<ul>#s',
        /* ol/li         */ '#\#\#(.*?)(\r|$)#s',  // br because of prev replace
        /* ol/li         */ '#</ol>\r<ol>#s',
        /* quote         */ '#\[quote\](.*?)\[/quote\]#s',
        /* code          */ '#\[code\]\[/code\]#s',
        /* code=language */ '#\[code=(\w+)\]\[/code\]#s',
        /* color         */ '#\[color=(?:")?(\w+|\#(?:[0-9a-fA-F]{3}){1,2})(?:")?\](.*?)\[/color\]#s',
        /* size          */ '#\[size=(\\\?")?([0-9]{1,})(\\\?")?\](.*?)\[/size\]#s',

        // Adding some &nbsp;
        '# (»|!|:|\?|;)#',
        '#« #',
    );
    $toReplace = array(
        // Replace \r with \n
        '<$1>',

        // Jusitifications
        /* left    */ '<div style="text-align:left;">$1</div>',
        /* center  */ '<div style="text-align:center;">$1</div>',
        /* right   */ '<div style="text-align:right;">$1</div>',
        /* justify */ '<div style="text-align:justify;">$1</div>',

        // Misc
        /* regex URL     */ '$1<a href="$2">$2</a>',
        /* a href        */ '<a href="$2">$1</a>',
        /* url           */ '<a href="$1$2">$2</a>',
        /* [img]         */ '<img src="$1" alt="$3" />',
        /* strong        */ '<b>$1</b>',
        /* italic        */ '<em>$1</em>',
        /* strike        */ '<del>$1</del>',
        /* underline     */ '<u>$1</u>',
        /* ul/li         */ '<ul><li>$1</li></ul>'."\r",
        /* ul/li         */ "\r",
        /* ol/li         */ '<ol><li>$1</li></ol>'."\r",
        /* ol/li         */ '',
        /* quote         */ '<blockquote>$1</blockquote>'."\r",
        /* code          */ '<prebtcode></prebtcode>'."\r",
        /* code=language */ '<prebtcode data-language="$1"></prebtcode>'."\r",
        /* color         */ '<span style="color:$1;">$2</span>',
        /* size          */ '<span style="font-size:$2pt;">$4</span>',

        // Adding some &nbsp;
        ' $1',
        '« ',
    );

    // memorizes [code] tags contents before bbcode being appliyed
    preg_match_all('#\[code(=(\w+))?\](.*?)\[/code\]#s', $text, $codeContents, PREG_SET_ORDER);
    // empty the [code] tags (content is in memory)
    $textFormated = preg_replace('#\[code(=(\w+))?\](.*?)\[/code\]#s', '[code$1][/code]', $text);

    // apply bbcode filter
    $textFormated = preg_replace($toFind, $toReplace, $textFormated);
    // apply <p>paragraphe</p> filter
    $textFormated = parse_texte_paragraphs($textFormated);

    // replace [code] elements with theire initial content
    $textFormated = parse_texte_code($textFormated, $codeContents);

    return $textFormated;
}

/**
 *
 */
function init_post_post()
{
    global $vars;

    $contentFormated = post_markup(clean_txt($vars['content']));
    $keywords = (!$GLOBALS['automatic_keywords']) ? protect($vars['mots_cles']) : extact_words($vars['title'].' '.$contentFormated);
    $date = sprintf(
        '%04d%02d%02d%02d%02d%02d',
        $vars['year'],
        $vars['month'] + 1,
        $vars['day'],
        $vars['hour'],
        $vars['minutes'],
        $vars['seconds']
    );

    $post = array (
        'bt_id' => (preg_match('#\d{14}#', $vars['article_id'])) ? $vars['article_id'] : $date,
        'bt_date' => $date,
        'bt_title' => protect($vars['title']),
        'bt_abstract' => clean_txt($vars['chapo']),
        'bt_notes' => protect($vars['notes']),
        'bt_content' => $contentFormated,
        'bt_wiki_content' => clean_txt($vars['content']),
        'bt_link' => '',  // this one is not needed yet. Maybe in the futur. I dunno why it is still in the DB…
        'bt_keywords' => $keywords,
        'bt_tags' => htmlspecialchars(tags_sort($vars['categories'])), // htmlSpecialChars() nedded to escape the (") since tags are put in a <input/>. (') are escaped in form_categories(), with addslashes – not here because of JS problems :/
        'bt_statut' => $vars['statut'],
        'bt_allow_comments' => $vars['allowcomment'],
    );

    if ($vars['ID'] > 0) {
        // ID only added on edit
        $post['ID'] = $vars['ID'];
    }
    return $post;
}

/**
 * once form is initiated, and no errors are found, treat it (save it to DB).
 */
function traitment_form_post($post)
{
    global $vars;
    if ($vars['enregistrer']) {
        $result = blog_db($post, (!empty($post['ID'])) ? 'modifier-existant' : 'enregistrer-nouveau');
        $redir = basename($_SERVER['SCRIPT_NAME']).'?post_id='.$post['bt_id'].'&msg='.((!empty($post['ID'])) ? 'confirm_article_maj' : 'confirm_article_ajout');
    } elseif ($vars['supprimer'] && $vars['ID']) {
        $result = blog_db($post, 'supprimer-existant');
        $redir = 'blog-articles.php?msg=confirm_article_suppr';
        $sql = '
            DELETE
              FROM commentaires
             WHERE bt_article_id = ?';
        $req = $GLOBALS['db_handle']->prepare($sql);
        $req->execute(array($vars['article_id']));
    }
    if (isset($result)) {
        file_cache_lv1_refresh();
        redirection($redir);
    }
    return false;
}

/**
 *
 */
function form_months($value)
{
    $months = array(
        $GLOBALS['lang']['january'],
        $GLOBALS['lang']['february'],
        $GLOBALS['lang']['march'],
        $GLOBALS['lang']['april'],
        $GLOBALS['lang']['may'],
        $GLOBALS['lang']['june'],
        $GLOBALS['lang']['july'],
        $GLOBALS['lang']['august'],
        $GLOBALS['lang']['september'],
        $GLOBALS['lang']['october'],
        $GLOBALS['lang']['november'],
        $GLOBALS['lang']['december']
    );

    $ret = '<select class="text" name="month">' ;
    foreach ($months as $option => $label) {
        $ret .= '<option value="'.htmlentities($option).'"'.(($value - 1 == $option) ? ' selected="selected"' : '').'>'.$label.'</option>';
    }
    $ret .= '</select>';
    return $ret;
}

/**
 *
 */
function form_statut($etat)
{
    $choice = array(
        $GLOBALS['lang']['label_invisible'],
        $GLOBALS['lang']['label_published']
    );
    $label = '<label for="statut" class="ico-eye" title="'.$GLOBALS['lang']['label_dp_state'].'"></label>';
    return form_select_no_label('statut', $choice, $etat).$label;
}

/**
 *
 */
function form_allow_comment($state)
{
    $choice = array(
        $GLOBALS['lang']['closed'],
        $GLOBALS['lang']['open']
    );
    $label = '<label for="allowcomment" class="ico-forum" title="'.$GLOBALS['lang']['label_dp_comments'].'"></label>';
    return form_select_no_label('allowcomment', $choice, $state).$label;
}

/**
 * Post form
 */
function display_form_post($post, $errors)
{
    $defaultDay = date('d');
    $defaultMonth = date('m');
    $defaultYear = date('Y');
    $defaultHour = date('H');
    $defaultMinutes = date('i');
    $defaultSeconds = date('s');
    $defaultAbstract = '';
    $defaultContent = '';
    $defaultKeywords = '';
    $defaultTags = '';
    $defaultTitle = '';
    $defaultNotes = '';
    $defaultStatus = 1;
    $defaultAllowComment = 1;

    if ($post) {
        $defaultDay = $post['day'];
        $defaultMonth = $post['month'];
        $defaultYear = $post['year'];
        $defaultHour = $post['hour'];
        $defaultMinutes = $post['minutes'];
        $defaultSeconds = $post['seconds'];
        $defaultTitle = $post['bt_title'];
        // abstract: if empty, it is generated but not added to the DTB
        $defaultAbstract = get_entry( 'articles', 'bt_abstract', $post['bt_id']);
        $defaultNotes = $post['bt_notes'];
        $defaultTags = $post['bt_tags'];
        $defaultContent = htmlspecialchars($post['bt_wiki_content']);
        $defaultKeywords = $post['bt_keywords'];
        $defaultStatus = $post['bt_statut'];
        $defaultAllowComment = $post['bt_allow_comments'];
    }

    $html = '';
    if ($errors) {
        $html .= erreurs($errors);
    }
    $html .= sprintf(
        '<form class="form block_large block-white" id="blogwrite-editor" method="post" onsubmit="return moveTag();" action="%s%s" class="form">',
        basename($_SERVER['SCRIPT_NAME']),
        (isset($post['bt_id'])) ? '?post_id='.$post['bt_id'] : ''
    );

    // title
    $html .= '<div class="input">';
        $html .= '<input class="text" value="'.$defaultTitle.'" required placeholder="'.ucfirst($GLOBALS['lang']['placeholder_title']).'" tabindex="30" spellcheck="true" name="title" type="text" placeholder="Title" />';
    $html .= '</div>';

    // chapo and note
    $html .= '<div class="input chapo-and-note">';
        $html .= '<textarea placeholder="'.ucfirst($GLOBALS['lang']['placeholder_chapo']).'" tabindex="35" name="chapo" rows="5" cols="20" class="text">'.$defaultAbstract.'</textarea>';
        $html .= '<textarea tabindex="40" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_notes']).'" name="notes" rows="5" cols="20" class="text">'.$defaultNotes.'</textarea>';
    $html .= '</div>';

    // content
    $html .= '<div class="input content">';
        $html .= form_bb_toolbar(true);
        $html .= '<textarea name="content" rows="20" cols="60" required="" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_content']).'" tabindex="55" class="text">'.$defaultContent.'</textarea>' ;
    $html .= '</div>';

    if ($GLOBALS['use_tags']) {
        $html .= tags_form('articles', $defaultTags);
    }

    if (!$GLOBALS['automatic_keywords']) {
        $html .= '<div class="input">';
            $html .= '<input id="mots_cles" name="mots_cles" type="text" size="50" value="'.$defaultKeywords.'" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_keywords']).'" tabindex="67" class="text" />';
        $html .= '</div>';
    }

    $html .= '<div class="form-inline">';
        $html .= '<div class="input">';
            $html .= '<label class="ico-calendar"></label>';
            $html .= '<input class="input-4char" type="number" name="year" max="'.(date('Y') + 3).'" value="'.$defaultYear.'">';
            $html .= form_months($defaultMonth);
            $html .= '<input name="day" type="number" min="1" max="31" size="2" maxlength="2" value="'.$defaultDay.'" required />';
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= '<label class="ico-hour"></label>';
            $html .= '<input name="hour" type="number" min="0" max="23" size="2" maxlength="2" value="'.$defaultHour.'" required /> : ';
            $html .= '<input name="minutes" type="number" min="0" max="59" size="2" maxlength="2" value="'.$defaultMinutes.'" required /> : ';
            $html .= '<input name="seconds" type="number" min="0" max="59" size="2" maxlength="2" value="'.$defaultSeconds.'" required />';
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_statut($defaultStatus);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_allow_comment($defaultAllowComment);
        $html .= '</div>';
    $html .= '</div>';

    $html .= '<p class="btn-container">';
    if ($post) {
        $html .= hidden_input('article_id', $post['bt_id']);
        $html .= hidden_input('article_date', $post['bt_date']);
        $html .= hidden_input('ID', $post['ID']);
        $html .= '<button class="btn btn-delete" type="button" name="supprimer" onclick="contentLoad = document.getElementById(\'content\').value; rmArticle(this)" />'.$GLOBALS['lang']['delete'].'</button>';
    }
        $html .= '<button class="btn btn-cancel" type="button" onclick="redirection(\'blog-articles.php\');">'.$GLOBALS['lang']['cancel'].'</button>';
        $html .= '<button class="btn btn-submit" type="submit" name="enregistrer" onclick="contentLoad=document.getElementById(\'content\').value" tabindex="70">'.$GLOBALS['lang']['send'].'</button>';
    $html .= '</p>';

    $html .= hidden_input('_verif_envoi', 1);
    $html .= hidden_input('token', token_set());

    $html .= '</form>';

    return $html;
}

/**
 *
 */
function show_preview($article)
{
    if (isset($article)) {
        $html = '<h2>'.$article['bt_title'].'</h2>';
        if (empty($article['bt_abstract'])) {
            $article['bt_abstract'] = mb_substr(strip_tags($article['bt_content']), 0, 249).'…';
        }
        $html .= '<hr />';
        $html .= '<div><strong>'.$article['bt_abstract'].'</strong></div>';
        $html .= '<hr />';
        $html .= '<div>';
        // if relative URI in path, make absolute paths (since /admin/ panel is 1 lv deeper) for href/src.
        $html .= preg_replace('#(src|href)=\"(?!(/|[a-z]+://))#i', '$1="../', $article['bt_content']);
        $html .= '</div>';
        return '<div class="main-white" id="apercu">'.$html.'</div>';
    }
    return '';
}

/**
 *
 */
function validate_form_post($post)
{
    global $vars;

    $date = decode_id($post['bt_id']);
    $errors = array();
    if ($vars['supprimer'] && TOKEN_CHECK !== true) {
        $errors[] = $GLOBALS['lang']['err_wrong_token'];
    }
    if (!strlen(trim($post['bt_title']))) {
        $errors[] = $GLOBALS['lang']['err_title'];
    }
    if (!strlen(trim($post['bt_content']))) {
        $errors[] = $GLOBALS['lang']['err_content'];
    }
    if (!preg_match('/\d{4}/', $date['year'])) {
        $errors[] = $GLOBALS['lang']['err_year'];
    }
    if (!preg_match('/\d{2}/', $date['month']) || $date['month'] > '12') {
        $errors[] = $GLOBALS['lang']['err_month'];
    }
    if (!preg_match('/\d{2}/', $date['day']) || $date['day'] > date('t', mktime(0, 0, 0, $date['month'], 1, $date['year']))) {
        $errors[] = $GLOBALS['lang']['err_day'];
    }
    if (!preg_match('/\d{2}/', $date['hour']) || $date['hour'] > 23) {
        $errors[] = $GLOBALS['lang']['err_hour'];
    }
    if (!preg_match('/\d{2}/', $date['minutes']) || $date['minutes'] > 59) {
        $errors[] = $GLOBALS['lang']['err_minutes'];
    }
    if (!preg_match('/\d{2}/', $date['seconds']) || $date['seconds'] > 59) {
        $errors[] = $GLOBALS['lang']['err_seconds'];
    }

    return $errors;
}


/**
 * process
 */
$vars = filter_input_array(
    INPUT_POST,
    array(
        'content' => FILTER_DEFAULT,
        'mots_cles' => FILTER_DEFAULT,
        'title' => FILTER_DEFAULT,
        'chapo' => FILTER_DEFAULT,
        'categories' => FILTER_DEFAULT,
        'notes' => FILTER_DEFAULT,
        'year' => FILTER_SANITIZE_NUMBER_INT,
        'month' => FILTER_SANITIZE_NUMBER_INT,
        'day' => FILTER_SANITIZE_NUMBER_INT,
        'hour' => FILTER_SANITIZE_NUMBER_INT,
        'minutes' => FILTER_SANITIZE_NUMBER_INT,
        'seconds' => FILTER_SANITIZE_NUMBER_INT,
        'statut' => FILTER_SANITIZE_NUMBER_INT,
        'allowcomment' => FILTER_SANITIZE_NUMBER_INT,
        'ID' => FILTER_SANITIZE_NUMBER_INT,
        'article_id' => FILTER_SANITIZE_NUMBER_INT,
    )
);
$vars['enregistrer'] = (filter_input(INPUT_POST, 'enregistrer') !== null);
$vars['supprimer'] = (filter_input(INPUT_POST, 'supprimer') !== null);
$vars['_verif_envoi'] = (filter_input(INPUT_POST, '_verif_envoi') !== null);

$errorsForm = array();
if ($vars['_verif_envoi']) {
    $post = init_post_post();
    $errorsForm = validate_form_post($post);
    if (!$errorsForm) {
        traitment_form_post($post);
    }
}

// Retrieve post's informations on given ID
$post = null;
$postId = (string)filter_input(INPUT_GET, 'post_id');
if ($postId) {
    $postId = htmlspecialchars($postId);
    $query = 'SELECT * FROM articles WHERE bt_id LIKE ?';
    $posts = db_items_list($query, array($postId), 'articles');
    if (isset($posts[0])) {
        $post = $posts[0];
    }
}

// Page's title
$writeTitleLight = ($post) ? $GLOBALS['lang']['title_maj'] : $GLOBALS['lang']['title_blog_write'];
$writeTitle = ($post) ? $writeTitleLight.' : '.$post['bt_title'] : $writeTitleLight;


/**
 * echo
 */

echo tpl_get_html_head($writeTitle, false);
// echo tpl_show_topnav($writeTitleLight);

// Subnav
echo '<div id="axe">';
if ($post) {
    echo '<div class="btn-container">';
        echo '<a href="'.$post['bt_link'].'" class="btn btn-dense btn-flat">'.$GLOBALS['lang']['post_link'].'</a>';
        echo '<a href="'.$post['bt_link'].'&share" class="btn btn-dense btn-flat">'.$GLOBALS['lang']['post_share'].'</a>';
        echo '<a href="comments.php?post_id='.$postId.'" class="btn btn-dense btn-flat">'.ucfirst(nombre_objets($post['bt_nb_comments'], 'comment')).'</a>';
    echo '</div>';
}

echo '<div id="page">';

// Show the post
if ($post) {
    echo'
    <div class="tabs" id="tab">
        <ul class="tabs-head">
            <li data-target="#tab-saved">Saved</li>
            <li data-target="#tab-editor">Editor</li>
        </ul>
        <div class="tabs-contents">
            <div id="tab-saved" class="tabs-content block-white">';
                echo show_preview($post);
    echo '
            </div>
            <div id="tab-editor" class="tabs-content">';
                echo display_form_post($post, $errorsForm);
    echo '
            </div>
        </div>
    </div>
    <script>
        var tabsContainer = document.getElementById("tab");
        new Tabs(tabsContainer);
    </script>';
} else {
    echo display_form_post($post, $errorsForm);
}

echo '<script>';
echo 'var contentLoad = document.getElementById("content").value;
window.addEventListener("beforeunload", function (e) {
    // From https://developer.mozilla.org/en-US/docs/Web/Reference/Events/beforeunload
    var confirmationMessage = BTlang.questionQuitPage;
    if (document.getElementById("content").value == contentLoad) {
        return true;
    };
    (e || window.event).returnValue = confirmationMessage || ""   //Gecko + IE
    return confirmationMessage;  // Webkit: ignore this.
});';
echo '</script>';

echo '</div>';
echo '</div>';
echo tpl_get_footer();
