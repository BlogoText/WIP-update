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


// boot
require_once 'inc/boot.php';

// dependancy
require_once BT_ROOT.'inc/addons.php';
require_once BT_ROOT.'inc/them.php';

// launch addons
addons_init_public();

// GZip compression
if (!DEBUG && extension_loaded('zlib')) {
    if (ob_get_length() > 0) {
        ob_end_clean();
    }
    ob_start('ob_gzhandler');
}


// set content infos
content_infos_set('format', 'html');
// set at 404 by default
content_infos_set('http', 404);
header('Content-Type: text/html; charset=UTF-8');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$GLOBALS['db_handle'] = db_connect();
$GLOBALS['tpl_class'] = '';


// run hook 'system-start'
hook_trigger('system-start');


/**
 * get a random article
 */
if (isset($_GET['random'])) {
    try {
        // getting nb articles, gen random num, then select one article 
        // is much faster than "sql(order by rand limit 1)"
        $sql = '
            SELECT count(ID)
              FROM articles
             WHERE bt_statut = 1
                   AND bt_date <= '.date('YmdHis');
        $result = $GLOBALS['db_handle']->query($sql)->fetch();
        if ($result[0] == 0) {
            exit(header('Location: '.$_SERVER['SCRIPT_NAME']));
        }

        $rand = mt_rand(0, $result[0] - 1);
        $sql = '
            SELECT *
              FROM articles
             WHERE bt_statut = 1
                   AND bt_date <= '.date('YmdHis').'
             LIMIT '.($rand + 0).', 1';
        $tableau = db_items_list($sql, array(), 'articles');
    } catch (Exception $e) {
        die('Erreur rand: '.$e->getMessage());
    }

    redirection($tableau[0]['bt_link']);
}

/**
 * unsubscribe request from comments-newsletter and redirect on main page
 */
if (isset($_GET['unsub'], $_GET['mail'], $_GET['article']) and $_GET['unsub'] == 1) {
    // dependancy
    require_once BT_ROOT.'inc/comments.php';
    $res = comments_unsubscribe(htmlspecialchars($_GET['mail']), htmlspecialchars($_GET['article']), (isset($_GET['all']) ? 1 : 0));
    if ($res == true) {
        redirection(basename($_SERVER['SCRIPT_NAME']).'?unsubscriben=yes');
    }
    redirection(basename($_SERVER['SCRIPT_NAME']).'?unsubscriben=no');
}


/**
 * Show specific blog article + comments
 */
if (isset($_GET['d']) && preg_match('#^\d{4}/\d{2}/\d{2}/\d{2}/\d{2}/\d{2}#', $_GET['d'])) {
    $tab = explode('/', htmlspecialchars($_GET['d'], ENT_QUOTES));
    $id = substr($tab['0'].$tab['1'].$tab['2'].$tab['3'].$tab['4'].$tab['5'], '0', '14');
    content_infos_set('type', 'blog');
    content_infos_set('list', false);
    content_infos_set('id', $id);

    // 'admin' connected is allowed to see draft articles, but not 'public'.
    // Same for article posted with a date in the future.
    // Same for shared drafts or private pages (append '&share' to the URL.
    if (empty($_SESSION['user_id']) && !isset($_GET['share'])) {
        $query = 'SELECT * FROM articles WHERE bt_id=? AND bt_date <=? AND bt_statut=1 LIMIT 1';
        $billets = db_items_list($query, array($id, date('YmdHis')), 'articles');
    } else {
        $query = 'SELECT * FROM articles WHERE bt_id=? LIMIT 1';
        $billets = db_items_list($query, array($id), 'articles');
    }

    // var_dump($billets[0]);

    // blog article found
    if (!empty($billets[0])) {
        content_infos_set('http', 200);

        if ($billets[0]['bt_allow_comments'] == '1') {
            // dependancy
            require_once BT_ROOT.'inc/comments.php';

            // TRAITEMENT new commentaire
            $erreurs_form = array();
            if (isset($_POST['_form'])
             && $_POST['_form'] == 'comment'
            ) {
                // 
                comments_proceed_public($billets[0]['bt_id']);
            }
        } else {
            unset($_POST['enregistrer']);
        }

        // dependancy
        // comments_get_form($id, 'public', $erreurs_form, '');
        // $GLOBALS['form_commentaire'] = comments_form($id, array(), $erreurs_form);

        $GLOBALS['tpl_class'] = 'content-blog content-item';
        echo afficher_index($billets[0], 'post');
    } else {
        $GLOBALS['tpl_class'] = 'content-blog content-item content-404';
        http_response_code(404);
        echo afficher_index(null, 'list');
    }

/**
 * request about a quick share
 */
} elseif (isset($_GET['id']) and preg_match('#\d{14}#', $_GET['id'])) {
    $id = htmlspecialchars($_GET['id'], ENT_QUOTES);
    content_infos_set('type', 'link');
    content_infos_set('list', false);
    content_infos_set('id', $id);

    $tableau = db_items_list('
            SELECT *
              FROM `links`
             WHERE `bt_id` = ?
               AND `bt_statut` = 1',
        array($id),
        'links'
    );
    if (!empty($tableau[0])) {
        content_infos_set('http', 200);
        echo afficher_index($tableau, 'list');
    } else {
        // to do 404
        $GLOBALS['tpl_class'] = 'content-links content-item content-404';
        http_response_code(404);
        echo afficher_index($tableau, 'list');
    }

/**
 * list all articles
 */
} elseif (isset($_GET['liste'])) {
    content_infos_set('list', true);
    $query = '
          SELECT `bt_date`, `bt_id`, `bt_title`, `bt_nb_comments`, `bt_link`
            FROM `articles`
           WHERE `bt_date` <= '.date('YmdHis').'
             AND `bt_statut` = 1
        ORDER BY `bt_date` DESC';
    $tableau = db_items_list($query, array(), 'articles');
    if (!empty($tableau[0])) {
        $GLOBALS['tpl_class'] = 'content-blog content-list content-list-all';
        content_infos_push(
                array(
                    'type' => 'links',
                    'list' => false,
                    'id' => $_GET['id'],
                    'type' => 'html',
                    'http' => 404
                )
            );
    } else {
        $GLOBALS['tpl_class'] = 'content-blog content-list content-list-all content-404';
        http_response_code(404);
        content_infos_set('http', 404);
    }
    echo afficher_liste($tableau);

/**
 * show by lists of more than one post
 */
} else {
    $GLOBALS['tpl_class'] = '';
    $year = date('Y');
    $month = date('m');
    $day = '';
    $array = array();
    $query = 'SELECT * FROM ';

    // paramètre mode : quelle table "mode" ? (comment/links/article)
    if (isset($_GET['mode'])) {
        switch ($_GET['mode']) {
            case 'comments':
                $query = "SELECT c.*, a.bt_title FROM ";
                $where = 'commentaires';
                $GLOBALS['tpl_class'] .= 'content-comments ';
                break;
            case 'links':
                $where = 'links';
                $GLOBALS['tpl_class'] .= 'content-links ';
                break;
            case 'blog':
            default:
                $where = 'articles';
                $GLOBALS['tpl_class'] .= 'content-blog ';
                break;
        }
    } else {
        $where = 'articles';
        $GLOBALS['tpl_class'] .= 'content-blog ';
    }

    switch ($where) {
        case 'commentaires':
            $query .= 'commentaires AS c, articles AS a ';
            break;
        default:
            $query .= $where.' ';
            break;
    }

    // paramètre de recherche uniquement dans les éléments publiés :
    switch ($where) {
        case 'commentaires':
            $query .= 'WHERE c.bt_statut=1 ';
            break;
        default:
            $query .= 'WHERE bt_statut=1 ';
            break;
    }

    // paramètre de date "d"
    if (isset($_GET['d']) and preg_match('#^\d{4}(/\d{2})?(/\d{2})?#', $_GET['d'])) {
        $date = '';
        $dates = array();
        $tab = explode('/', $_GET['d']);
        if (isset($tab['0']) and preg_match('#\d{4}#', ($tab['0']))) {
            $date .= $tab['0'];
            $year = $tab['0'];
        }
        if (isset($tab['1']) and preg_match('#\d{2}#', ($tab['1']))) {
            $date .= $tab['1'];
            $month = $tab['1'];
        }
        if (isset($tab['2']) and preg_match('#\d{2}#', ($tab['2']))) {
            $date .= $tab['2'];
            $day = $tab['2'];
        }

        if (!empty($date)) {
            switch ($where) {
                case 'articles':
                    $sql_date = 'bt_date LIKE ? ';
                    break;
                case 'commentaires':
                    $sql_date = 'c.bt_id LIKE ? ';
                    break;
                default:
                    $sql_date = 'bt_id LIKE ? ';
                    break;
            }
            $array[] = $date.'%';
        } else {
            $sql_date = '';
        }
    }

    // paramètre de recherche "q"
    if (isset($_GET['q'])) {
        $GLOBALS['tpl_class'] .= 'content-search ';
        $arr = search_engine_parse_query($_GET['q']);
        $array = array_merge($array, $arr);
        switch ($where) {
            case 'articles':
                $sql_q = implode(array_fill(0, count($arr), '( bt_content || bt_title ) LIKE ? '), 'AND ');
                break;
            case 'links':
                $sql_q = implode(array_fill(0, count($arr), '( bt_content || bt_title || bt_link ) LIKE ? '), 'AND ');
                break;
            case 'commentaires':
                $sql_q = implode(array_fill(0, count($arr), 'c.bt_content LIKE ? '), 'AND ');
                break;
            default:
                $sql_q = '';
                break;
        }
    }

    // paramètre de tag "tag"
    if (isset($_GET['tag'])) {
        $GLOBALS['tpl_class'] .= 'content-tag ';
        switch ($where) {
            case 'articles':
                $sql_tag = '( bt_tags LIKE ? OR bt_tags LIKE ? OR bt_tags LIKE ? OR bt_tags LIKE ? ) ';
                break;
            case 'links':
                $sql_tag = '( bt_tags LIKE ? OR bt_tags LIKE ? OR bt_tags LIKE ? OR bt_tags LIKE ? ) ';
                break;
            default:
                $sql_tag = ' ';
                break;
        }
        if (!empty($sql_tag)) {
            $array[] = $_GET['tag'];
            $array[] = $_GET['tag'].', %';
            $array[] = '%, '.$_GET['tag'].', %';
            $array[] = '%, '.$_GET['tag'];
        }
    }

    // paramètre d’auteur "author" FIXME !

    // paramètre ORDER BY (pas un paramètre, mais ajouté à la $query quand même)
    switch ($where) {
        case 'articles':
            $sql_order = 'ORDER BY bt_date DESC ';
            break;
        case 'commentaires':
            $sql_order = 'ORDER BY c.bt_id DESC ';
            break;
        default:
            $sql_order = 'ORDER BY bt_id DESC ';
            break;
    }

    // paramètre de filtrage date (pas un paramètre, mais ajouté quand même)
    switch ($where) {
        case 'articles':
            $sql_a_p = 'bt_date <= '.date('YmdHis').' ';
            break;
        case 'commentaires':
            $sql_a_p = 'c.bt_id <= '.date('YmdHis').' AND c.bt_article_id=a.bt_id ';
            break;
        default:
            $sql_a_p = 'bt_id <= '.date('YmdHis').' ';
            break;
    }

    // paramètre de page "p"
    if (isset($_GET['p']) and is_numeric($_GET['p']) and $_GET['p'] >= 1) {
        $GLOBALS['tpl_class'] .= 'content-list ';
        $sql_p = 'LIMIT '.$GLOBALS['max_bill_acceuil'] * $_GET['p'].', '.$GLOBALS['max_bill_acceuil'];
    } elseif (empty($sql_date)) {
        $GLOBALS['tpl_class'] .= 'content-list ';
        $sql_p = 'LIMIT '.$GLOBALS['max_bill_acceuil']; // no limit for $date param, is param is valid
    } else {
        $sql_p = '';
    }

    // Concaténation de tout ça.
    $glue = 'AND ';
    if (!empty($sql_date)) {
        $query .= $glue.$sql_date;
    }
    if (!empty($sql_q)) {
        $query .= $glue.$sql_q;
    }
    if (!empty($sql_tag)) {
        $query .= $glue.$sql_tag;
    }

    $query .= $glue.$sql_a_p.$sql_order.$sql_p;
    $tableau = db_items_list($query, $array, $where);
    $GLOBALS['param_pagination'] = array('nb' => count($tableau), 'nb_par_page' => $GLOBALS['max_bill_acceuil']);
    echo afficher_index($tableau, 'list');
}

$end = microtime(true);
echo ' <!-- Rendered in '.round(($end - $GLOBALS['BT_timer_start']), 6).' seconds -->';
