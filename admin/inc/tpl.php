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
 * return a text for counting items displayed
 * "5 Items on 10"
 *
 * @params $item_name string, the item name
 * @params $current int, the current count of displayed item
 * @params $max int, the max number of items
 * @return string
 */
function tpl_items_counter($item_name, $current, $max)
{
    $html = '';
    $html .= '<div class="ct-items">';
    $html .= ucfirst(nombre_objets($current, $item_name));
    if ($max > 0) {
        $html .= ' '.$GLOBALS['lang']['sur'].' '.$max;
    }
    $html .= '</div>';

    return $html;
}

/**
 * Admin top nav with subnav
 *
 * @params $title string
 * @return string, HTML
 */
function tpl_show_topnav($title)
{
    $tab = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME);
    if (strlen($title) == 0) {
        $title = BLOGOTEXT_NAME;
    }
    $html = '<div id="nav">';
        $html .= '<ul>';
            $html .= '<li><a href="index.php" id="lien-index"'.(($tab == 'index.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['label_admin_home'].'</a></li>';
            $html .= '<li><a href="blog-articles.php" id="lien-liste"'.(($tab == 'blog-articles.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['my_articles'].'</a></li>';
            $html .= '<li><a href="blog-write.php" id="lien-nouveau"'.(($tab == 'blog-write.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['article_new'].'</a></li>';
            $html .= '<li><a href="comments.php" id="lien-lscom"'.(($tab == 'comments.php') ? ' class="current"' : '').'>'.$GLOBALS['lang']['title_comments'].'</a></li>';
            $html .= '<li><a href="files.php" id="lien-fichiers"'.(($tab == 'files.php') ? ' class="current"' : '').'>'.ucfirst($GLOBALS['lang']['label_files']).'</a></li>';
    if ($GLOBALS['quickly_enabled']) {
        $html .= '<li><a href="links.php" id="lien-links"'.(($tab == 'links.php') ? ' class="current"' : '').'>'.ucfirst($GLOBALS['lang']['label_links']).'</a></li>';
    }
    if ($GLOBALS['use_feed_reader']) {
        $html .= '<li><a href="feed.php" id="lien-rss"'.(($tab == 'feed.php') ? ' class="current"' : '').'>'.ucfirst($GLOBALS['lang']['label_feeds']).'</a></li>';
    }
        $html .= '</ul>';
    $html .= '</div>';

    $html .= '<h1>'.$title.'</h1>'; // useless ?

    $html .= '<div id="nav-acc">';
        $html .= '<ul>';
            $html .= '<li><a href="settings.php" id="lien-preferences">'.$GLOBALS['lang']['settings'].'</a></li>';
            $html .= '<li><a href="users.php" id="lien-users">Utilisateurs</a></li>';
            $html .= '<li><a href="addons.php" id="lien-modules">'.ucfirst($GLOBALS['lang']['label_modules']).'</a></li>';
            $html .= '<li><a href="'.URL_ROOT.'" id="lien-site">'.$GLOBALS['lang']['blog_link'].'</a></li>';
            $html .= '<li><a href="logout.php" id="lien-deconnexion">'.$GLOBALS['lang']['logout'].'</a></li>';
        $html .= '</ul>';
    $html .= '</div>';
    return $html;
}

/**
 *
 */
function tpl_show_msg()
{
    // Success message
    $msg = (string)filter_input(INPUT_GET, 'msg');
    $html = '';

    /*
    function BigToast_insert_error()
    {
        var notif = new Notification();
        notif
            .setText('This is an error...')
            .setClass('error')
            .addCloseButton('Ok')
            .insertAsBigToast();
    }
    */

    // RemRem : I suppose this is a "good/info" message
    if ($msg
     && array_key_exists(htmlspecialchars($msg), $GLOBALS['lang'])
    ) {
        $nbnew = (string)filter_input(INPUT_GET, 'nbnew');
        $suffix = ($nbnew) ? htmlspecialchars($nbnew).' '.$GLOBALS['lang']['rss_new_feed'] : ''; // nb new RSS
        // $html .= '<div class="confirmation">'.$GLOBALS['lang'][$msg].$suffix.'</div>';
        $html .= '<script>';
        $html .= 'var notif = new Notification();';
        $html .= 'notif';
        $html .= '    .setText("'.$GLOBALS['lang'][$msg].$suffix.'")';
        $html .= '    .addCloseTimer(4000)';
        $html .= '    .insertAsBigToast();';
        $html .= '</script>';
    }

    // Error message
    $errmsg = (string)filter_input(INPUT_GET, 'errmsg');
    if ($errmsg
     && array_key_exists($errmsg, $GLOBALS['lang'])
    ) {
        // $html .= '<div class="no_confirmation">'.$GLOBALS['lang'][$errmsg].'</div>';
        $html .= '<script>';
        $html .= 'var notifError = new Notification();';
        $html .= 'notifError';
        $html .= '    .setText("'.$GLOBALS['lang'][$errmsg].'")';
        $html .= '    .setClass("error")';
        $html .= '    .addCloseButton("Ok")';
        $html .= '    .insertAsBigToast();';
        $html .= '</script>';
    }

    return $html;
}


/**
 *
 */
function tpl_get_search_form($topNav = true)
{
    $html = '';
    $requete = '';

    if (isset($_GET['q'])) {
        $requete = htmlspecialchars(stripslashes($_GET['q']));
    }

    if ($topNav) {
    } else {
        $attrs = array(
            'form_id' => 'search-2',
            'form_class' => 'block_full',
            'search_id' => 'q',
            'search_accesskey' => '',
            'search_class' => 'text',
            'btn_class' => 'btn btn-submit',
            'mode_id' => 'mode_', // useless ?
        );
    }

    $html .= '<form action="?" method="get" id="'.$attrs['form_id'].'" class="'.$attrs['form_class'].'">';
        $html .= '<input class="'.$attrs['search_class'].'" id="'.$attrs['search_id'].'" name="q" type="search" size="20" value="'.$requete.'" placeholder="'.$GLOBALS['lang']['placeholder_search'].'" accesskey="'.$attrs['search_accesskey'].'" />';
        $html .= '<button class="'.$attrs['btn_class'].'" type="submit">'.$GLOBALS['lang']['search'].'</button>';
    if (isset($_GET['mode'])) {
        $html .= '<input id="'.$attrs['mode_id'].'" name="mode" type="hidden" value="'.htmlspecialchars(stripslashes($_GET['mode'])).'"/>';
    }
    $html .= '</form>';

    return $html;
}

/**
 *
 */
function tpl_get_html_head($title, $show_search = false)
{
    $html = '<!DOCTYPE html>';
    $html .= '<html>';
    $html .= '<head>';
        $html .= '<meta charset="UTF-8" />';
        $html .= '<link type="text/css" rel="stylesheet" href="theme/css/style.css.php?v='.BLOGOTEXT_VERSION.'" />';
        // custom css & js
    if (isset($GLOBALS['tpl_admin_custom'])) {
        if (isset($GLOBALS['tpl_admin_custom']['css'])) {
            foreach ($GLOBALS['tpl_admin_custom']['css'] as $css) {
                $html .= '<link type="text/css" rel="stylesheet" href="'.$css.'?v='.BLOGOTEXT_VERSION.'" />';
            }
        }
        if (isset($GLOBALS['tpl_admin_custom']['js'])) {
            foreach ($GLOBALS['tpl_admin_custom']['js'] as $js) {
                $html .= '<script src="'.$js.'?v='.BLOGOTEXT_VERSION.'"></script>';
            }
        }
    }
        $html .= '<meta name="viewport" content="initial-scale=1.0, user-scalable=yes" />';
        $html .= '<meta name="robots" content="none" />';
        $html .= '<title>'.$title.' | '.BLOGOTEXT_NAME.'</title>';
        $html .= '<script>var csrf_token = "'.token_set().'";</script>';
    $html .= '</head>';

    $html .= '<body>';
    // login page
    if (!auth_check_session()) {
        return $html;
    }
    $html .= '<script src="theme/js/lang.js.php?v='.BLOGOTEXT_VERSION.'"></script>';
    $html .= '<script src="theme/js/script.js.php?v='.BLOGOTEXT_VERSION.'"></script>';
    $html .= '<div id="header">';
        $html .= '<div id="top">';
        $html .= tpl_show_msg();
    if ($show_search) {
        $requete = (isset($_GET['q'])) ? htmlspecialchars(stripslashes($_GET['q'])) : '';
        $mode = (isset($_GET['mode'])) ? htmlspecialchars(stripslashes($_GET['mode'])) : '';
        $html .= '<form action="?" method="get" id="search">';
            $html .= '<input id="q" name="q" type="search" size="20" value="'.$requete.'" accesskey="f" />';
            $html .= '<button class="btn-search" type="submit">'.$GLOBALS['lang']['search'].'</button>';
        if (isset($_GET['mode'])) {
            $html .= '<input id="mode" name="mode" type="hidden" value="'.$mode.'" />';
        }
            $html .= '</form>';
    }
        $html .= tpl_show_topnav($title);
        $html .= '</div>';
    $html .= '</div>';

    return $html;
}

/**
 *
 */
function tpl_get_footer()
{
    $msg = '';
    if (isset($GLOBALS['BT_timer_start'])) {
        $dt = round((microtime(true) - $GLOBALS['BT_timer_start']), 6);
        $msg = ' - '.$GLOBALS['lang']['rendered'].' '.$dt.' s '.$GLOBALS['lang']['using'].' '.DBMS;
    }

    $html = '<p id="footer"><a href="'.BLOGOTEXT_SITE.'">'.BLOGOTEXT_NAME.' '.BLOGOTEXT_VERSION.'</a>'.$msg.'</p>';
    $html .= '</body>';
    $html .= '</html>';
    return $html;
}

/**
 *
 */
function info($message)
{
    return '<p class="info">'.$message.'</p>';
}

/**
 *
 */
function question($message)
{
    echo '<p id="question">'.$message.'</p>';
}

/**
 * push some language into json format for front UI
 *
 * @params bool $script, add <script></script> tag
 * @return string
 */
function php_lang_to_js($script = false)
{
    return '';
    $datas = array();
    $datas['maxFilesSize'] = min(return_bytes(ini_get('upload_max_filesize')), return_bytes(ini_get('post_max_size')));
    $datas['rssJsAlertNewLink'] = $GLOBALS['lang']['rss_jsalert_new_link'];
    $datas['rssJsAlertNewLinkFolder'] = $GLOBALS['lang']['rss_jsalert_new_link_folder'];
    $datas['confirmFeedClean'] = $GLOBALS['lang']['confirm_feed_clean'];
    $datas['confirmCommentSuppr'] = $GLOBALS['lang']['confirm_comment_suppr'];
    $datas['activer'] = $GLOBALS['lang']['activate'];
    $datas['desactiver'] = $GLOBALS['lang']['desactivate'];
    $datas['errorPhpAjax'] = $GLOBALS['lang']['error_phpajax'];
    $datas['errorCommentSuppr'] = $GLOBALS['lang']['error_comment_suppr'];
    $datas['errorCommentValid'] = $GLOBALS['lang']['error_comment_valid'];
    $datas['questionQuitPage'] = $GLOBALS['lang']['question_quit_page'];
    $datas['questionCleanRss'] = $GLOBALS['lang']['question_clean_rss'];
    $datas['questionSupprComment'] = $GLOBALS['lang']['question_suppr_comment'];
    $datas['questionSupprArticle'] = $GLOBALS['lang']['question_suppr_article'];
    $datas['questionSupprFichier'] = $GLOBALS['lang']['question_suppr_fichier'];

    $sc = 'var BTlang = '.json_encode($datas).';';

    return (!$script) ? $sc : '<script>'.$sc.'</script>';
}
