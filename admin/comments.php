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
require_once BT_ROOT.'inc/comments.php';



$vars = array(
    'com_activer' => (filter_input(INPUT_POST, 'com_activer') !== null),
    'com_supprimer' => (filter_input(INPUT_POST, 'com_supprimer') !== null),
    '_verif_envoi' => (filter_input(INPUT_POST, '_verif_envoi') !== null),
    'comment_article_id' => (string)filter_input(INPUT_POST, 'comment_article_id'),
    'post_id' => (string)filter_input(INPUT_GET, 'post_id'),
    'filtre' => (string)filter_input(INPUT_GET, 'filtre'),
    'q' => (string)filter_input(INPUT_GET, 'q'),
);

/**
 *
 */
function comments_form_check_ajax($commentaire)
{
    $erreurs = array();
    if (!is_numeric($commentaire)) { // comment has to be on a valid ID
        $erreurs[] = $GLOBALS['lang']['err_comm_article_id'];
    }
    // test token
    if (TOKEN_CHECK !== true) {
        $erreurs[] = $GLOBALS['lang']['err_wrong_token'];
    }
    return $erreurs;
}


/**
 * process
 */

$postTitle = '';
$errorsForm = array();
if ($vars['_verif_envoi']) {
    if ($vars['com_supprimer'] || $vars['com_activer']) {
        $commentAction = (($vars['com_supprimer']) ? $vars['com_supprimer'] : $vars['com_activer']);
        $errorsForm = comments_form_check_ajax((int)$commentAction);
        if ($errorsForm) {
            die(implode("\n", $errorsForm));
        }
        comments_form_proceed($commentAction, 'admin');
    } else {
        $comment = init_post_comment($vars['comment_article_id'], 'admin');
        $errorsForm = comments_form_check($comment, 'admin');
        if (!$errorsForm) {
            comments_form_proceed($comment, 'admin');
        }
    }
}

// if article ID is given in query string
if (preg_match('#\d{14}#', $vars['post_id'])) {
    $paramMakeup['menu_theme'] = 'for_article';
    $sql = '
        SELECT c.*, a.bt_title
          FROM commentaires AS c, articles AS a
         WHERE c.bt_article_id = ?
               AND c.bt_article_id = a.bt_id
         ORDER BY c.bt_id';
    $comments = db_items_list($sql, array($vars['post_id']), 'commentaires');
    $postTitle = ($comments) ? $comments[0]['bt_title'] : get_entry('articles', 'bt_title', $vars['post_id']);
    $paramMakeup['show_links'] = 0;
} else {
    // else, no ID
    $paramMakeup['menu_theme'] = 'for_comms';
    if ($vars['filtre']) {
        // for "authors" the requests is "auteur.$search": here we split the type of search and what we search.
        $type = substr($vars['filtre'], 0, -strlen(strstr($vars['filtre'], '.')));
        $search = htmlspecialchars(ltrim(strstr($vars['filtre'], '.'), '.'));
        if (preg_match('#^\d{6}(\d{1,8})?$#', $vars['filtre'])) {
            $sql = '
                SELECT c.*, a.bt_title
                  FROM commentaires c
                  LEFT JOIN articles a
                         ON a.bt_id = c.bt_article_id
                 WHERE c.bt_id LIKE ?
                 ORDER BY c.bt_id DESC';
            $comments = db_items_list($sql, array($vars['filtre'].'%'), 'commentaires');
        } elseif ($vars['filtre'] == 'draft') {
            $sql = '
                SELECT c.*, a.bt_title
                  FROM commentaires c
                  LEFT JOIN articles a
                         ON a.bt_id = c.bt_article_id
                 WHERE c.bt_statut = 0
                 ORDER BY c.bt_id DESC';
            $comments = db_items_list($sql, array(), 'commentaires');
        } elseif ($vars['filtre'] == 'pub') {
            $sql = '
                SELECT c.*, a.bt_title
                  FROM commentaires c
                  LEFT JOIN articles a
                         ON a.bt_id = c.bt_article_id
                 WHERE c.bt_statut = 1
                 ORDER BY c.bt_id DESC';
            $comments = db_items_list($sql, array(), 'commentaires');
        } elseif ($type == 'author' && $search != '') {
            $sql = '
                SELECT c.*, a.bt_title
                  FROM commentaires c
                  LEFT JOIN articles a
                         ON a.bt_id = c.bt_article_id
                 WHERE c.bt_author = ?
                 ORDER BY c.bt_id DESC';
            $comments = db_items_list($sql, array($search), 'commentaires');
        } else {
            $sql = '
                SELECT c.*, a.bt_title
                  FROM commentaires c
                  LEFT JOIN articles a
                         ON a.bt_id = c.bt_article_id
                 ORDER BY c.bt_id DESC
                 LIMIT '.$GLOBALS['max_comm_admin'];
            $comments = db_items_list($sql, array(), 'commentaires');
        }
    } elseif ($vars['q']) {
        $arr = search_engine_parse_query($vars['q']);
        $sqlWhere = implode(array_fill(0, count($arr), 'c.bt_content LIKE ?'), 'AND');
        $sql = '
            SELECT c.*, a.bt_title
              FROM commentaires c
              LEFT JOIN articles a
                     ON a.bt_id = c.bt_article_id
             WHERE '.$sqlWhere.'
             ORDER BY c.bt_id DESC';
        $comments = db_items_list($sql, $arr, 'commentaires');
    } else {
        // No filter, so list'em all
        $sql = '
            SELECT c.*, a.bt_title
              FROM commentaires c
              LEFT JOIN articles a
                     ON a.bt_id = c.bt_article_id
             ORDER BY c.bt_id DESC
             LIMIT '.$GLOBALS['max_comm_admin'];
        $comments = db_items_list($sql, array(), 'commentaires');
    }
    $numberOfComments = db_items_list_count('SELECT count(*) AS counter FROM commentaires', array());
    $paramMakeup['show_links'] = 1;
}

/**
 * generates the comment form, with params from the admin-side and the visiter-side
 */
function comments_form_admin($article_id, $erreurs, $datas)
{
    $html = '';

    // init default form fields contents
    $form_cont = comments_default();

    // edit mode
    if (!empty($edit_comm)) {
        $form_cont = array_merge($form_cont, $datas);
    // non-edit : new comment from admin
    } else {
        $this_user = user_get($_SESSION['uid']);
        // var_dump($this_user);
        $form_cont['bt_author'] = $this_user['pseudo'];
        $form_cont['bt_email'] = $this_user['email'];
        $form_cont['bt_webpage'] = URL_ROOT;
    }

    // comment just submited (for submission OR for preview)
    if (isset($_POST['_verif_envoi'])) {
        $form_cont['bt_author'] = protect($_POST['bt_author']);
        $form_cont['bt_email'] = protect($_POST['bt_email']);
        $form_cont['bt_webpage'] = protect($_POST['bt_webpage']);
        $form_cont['bt_wiki_content'] = protect($_POST['bt_wiki_content']);
    }

    // WORK ON REQUEST
    // preview ? submission ? validation ?
    // parses the comment, but does not save it
    if (isset($_POST['previsualiser'])) {
        $p_comm = (isset($_POST['bt_wiki_content'])) ? protect($_POST['bt_wiki_content']) : '';
        $comm['bt_wiki_content'] = markup($p_comm);
        $comm['bt_id'] = date('YmdHis');
        $comm['bt_author'] = $form_cont['bt_author'];
        $comm['bt_email'] = $form_cont['bt_email'];
        $comm['bt_webpage'] = $form_cont['bt_webpage'];
        $comm['anchor'] = article_anchor($comm['bt_id']);
        $comm['bt_link'] = '';
        $comm['author_lien'] = ($comm['bt_webpage'] != '') ? '<a href="'.$comm['bt_webpage'].'" class="webpage">'.$comm['bt_author'].'</a>' : $comm['bt_author'];

        $html .= '<div id="erreurs"><ul><li>Prévisualisation&nbsp;:</li></ul></div>';
        $html .= '<div id="previsualisation">'."\n";
            $html .= conversions_theme_commentaire(file_get_contents($GLOBALS['theme_post_comm']), $comm);
        $html .= '</div>';
    } // comm sent ; with errors
    elseif (isset($_POST['_verif_envoi']) and !empty($erreurs)) {
        $html .= '<div id="erreurs"><strong>'.$GLOBALS['lang']['erreurs'].'</strong> :';
        $html .= '<ul><li>';
        $html .=  implode('</li><li>', $erreurs);
        $html .=  '</li></ul></div>';
    }

    // prelim vars for Generation of comment Form
    $required = ($GLOBALS['comments_require_email'] == 1) ? 'required' : '';
    $cookie_checked = (isset($_COOKIE['cookie_c']) and $_COOKIE['cookie_c'] == 1) ? ' checked' : '';
    $subscribe_checked = (isset($_COOKIE['subscribe_c']) and $_COOKIE['subscribe_c'] == 1) ? ' checked' : '';


    $rand = '-'.substr(md5(mt_rand(100, 999)), 0, 5);

    // COMMENT FORM ON ADMIN SIDE : +always_open –captcha –previsualisation –verif
    $html .= '<form id="form-commentaire'.$form_cont['bt_id'].'" class="form-commentaire comm-edit-hidden-bloc block-white" method="post" action="?'.$_SERVER['QUERY_STRING'].'#erreurs">';
        if (empty($edit_comm)) {
            $html .= '<legend>'.$GLOBALS['lang']['comment_write'].'</legend>';
        }

    // main comm field
        $html .= '<div class="input">';
            $html .= form_bb_toolbar(false);
            $html .= '<textarea class="commentaire text" name="commentaire" required="" placeholder="Lorem Ipsum" id="commentaire'.$rand.'" cols="50" rows="10">'.$form_cont['bt_wiki_content'].'</textarea>';
        $html .= '</div>';
        $html .= '<div class="form-inline">';
            $html .= '<div class="input">';
                $html .= '<label for="author'.$rand.'">'.$GLOBALS['lang']['label_dp_pseudo'].'</label>';
                $html .= '<input type="text" name="author" id="author'.$rand.'" placeholder="John Doe" required value="'.$form_cont['bt_author'].'" size="25" class="text" />';
            $html .= '</div>';
            $html .= '<div class="input">';
                $html .= '<label for="email'.$rand.'">'.(($GLOBALS['comments_require_email'] == 1) ? $GLOBALS['lang']['label_dp_email_required'] : $GLOBALS['lang']['label_dp_email']).'</label>';
                $html .= '<input type="email" name="email" id="email'.$rand.'" placeholder="mail@example.com" '.$required.' value="'.$form_cont['bt_email'].'" size="25" class="text" /></span>';
            $html .= '</div>';
            $html .= '<div class="input">';
                $html .= '<label for="webpage'.$rand.'">'.$GLOBALS['lang']['label_dp_webpage'].'</label>';
                $html .= '<input type="url" name="webpage" id="webpage'.$rand.'" placeholder="http://www.example.com" value="'.$form_cont['bt_webpage'].'" size="25" class="text" /></span>';
            $html .= '</div>';
        $html .= '</div>';

        $html .= hidden_input('comment_article_id', $article_id);
        $html .= hidden_input('_verif_envoi', '1');
        $html .= hidden_input('token', token_set());
        // begin with some additional stuff on comment "edit".
        if (!empty($edit_comm)) {
            $html .= hidden_input('is_it_edit', 'yes');
            $html .= hidden_input('comment_id', $form_cont['bt_id']);
            $html .= hidden_input('status', $form_cont['bt_statut']);
            $html .= hidden_input('ID', $form_cont['ID']);
        }
        // submit buttons
        $html .= '<div class="btn-container">';
            $html .= '<button class="btn btn-cancel" type="button" onclick="unfold(this);">'.$GLOBALS['lang']['cancel'].'</button>';
            $html .= '<button class="btn btn-submit" type="submit" name="enregistrer">'.$GLOBALS['lang']['send'].'</button>';
        $html .= '</div>';
    $html .= '</form>';

    return $html;
}

function display_comment($comment, $withLink)
{
    $html = '';

    $html .= '<div class="commentbloc'.((!$comment['bt_statut']) ? ' privatebloc' : '').'" id="'.article_anchor($comment['bt_id']).'">';
        $html .= '<div class="comm-side-icon">';
            $html .= '<div class="comm-title">';
                $html .= '<img class="author-icon" width="48" height="48" src="'.URL_ROOT.'favatar.php?q='.md5(((!empty($comment['bt_email'])) ? $comment['bt_email'] : $comment['bt_author'] )).'"/>';
                $html .= '<span class="date">'.date_formate($comment['bt_id']).'<span>'.time_formate($comment['bt_id']).'</span></span>' ;

                $html .= '<span class="reply" onclick="reply(\'[b]@['.str_replace('\'', '\\\'', $comment['bt_author']).'|#'.article_anchor($comment['bt_id']).'] :[/b] \'); ">Reply</span> ';
                $html .= (!empty($comment['bt_webpage'])) ? '<span class="webpage"><a href="'.$comment['bt_webpage'].'" title="'.$comment['bt_webpage'].'">'.$comment['bt_webpage'].'</a></span>' : '';
                $html .= (!empty($comment['bt_email'])) ? '<span class="email"><a href="mailto:'.$comment['bt_email'].'" title="'.$comment['bt_email'].'">'.$comment['bt_email'].'</a></span>' : '';
            $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="comm-main-frame block-white">';
            $html .= '<div class="comm-header">';

                $html .= '<div class="comm-title">';
                    $html .= '<span class="author"><a href="?filtre=author.'.$comment['bt_author'].'" title="'.$GLOBALS['lang']['label_all_comm_by_author'].'">'.$comment['bt_author'].'</a> :</span>';
                $html .= '</div>';

                if ($withLink == 1 && !empty($comment['bt_title'])) {
                    $html .= '<span class="link-article">';
                        $html .= $GLOBALS['lang']['sur'];
                        $html .= ' <a href="'.basename($_SERVER['SCRIPT_NAME']).'?post_id='.$comment['bt_article_id'].'">'.$comment['bt_title'].'</a>';
                    $html .= '</span>';
                }

                $html .= '<div class="comm-options">';
                    $html .= '<ul>';
                        $html .= '<li class="cl-edit" onclick="unfold(this);">'.$GLOBALS['lang']['edit'].'</li>';
                        $html .= '<li class="cl-activ" onclick="activate_comm(this);" data-comm-id="'.$comment['ID'].'" data-comm-btid="'.$comment['bt_id'].'" data-comm-art-id="'.$comment['bt_article_id'].'">'.$GLOBALS['lang'][((!$comment['bt_statut']) ? '' : 'des').'activate'].'</li>';
                        $html .= '<li class="cl-suppr" onclick="suppr_comm(this);" data-comm-id="'.$comment['ID'].'" data-comm-art-id="'.$comment['bt_article_id'].'">'.$GLOBALS['lang']['delete'].'</li>';
                    $html .= '</ul>';
                $html .= '</div>';

            $html .= '</div>';

            $html .= '<div class="comm-content">';
                $html .= $comment['bt_content'];
            $html .= '</div>';

            $html .= comments_form_admin($comment['bt_article_id'], '', $comment);

        $html .= '</div>';
    $html .= '</div>';

    return $html;
}


/**
 * echo
 */
$tpl_head_title = $GLOBALS['lang']['title_comments']. (($postTitle) ?' | '.$postTitle : '');
echo tpl_get_html_head($tpl_head_title, true);
echo '<div id="axe">';

// Subnav
echo '<div id="subnav">';
echo tpl_filter_form('commentaires', htmlspecialchars($vars['filtre']));
echo '<div class="ct-items">';
if ($paramMakeup['menu_theme'] == 'for_article') {
    $decodedId = decode_id($vars['post_id']);
    $postLink = URL_ROOT.'?d='.implode('/', $decodedId).'-'.title_url($postTitle);
    echo '<ul>';
    echo '<li><a href="blog-write.php?post_id='.$vars['post_id'].'">'.$GLOBALS['lang']['ecrire'].$postTitle.'</a></li>';
    echo '<li><a href="'.$postLink.'">'.$GLOBALS['lang']['post_link'].'</a></li>';
    echo '</ul>';
    echo '– &nbsp; '.ucfirst(nombre_objets(count($comments), 'comment'));
} elseif ($paramMakeup['menu_theme'] == 'for_comms') {
    echo tpl_items_counter('comment', count($comments), $numberOfComments);
}
echo '</div>';
echo '</div>';

echo '<div id="page">';

// Comments
if ($comments) {
    echo '<div id="liste-commentaires">';
    $token = token_set();
    foreach ($comments as $comment) {
        $comment['comm-token'] = $token;
        echo display_comment($comment, $paramMakeup['show_links']);
    }
    echo '</div>';
} else {
    echo info($GLOBALS['lang']['note_no_comment']);
}

if ($paramMakeup['menu_theme'] == 'for_article') {
    echo comments_form_admin($vars['post_id'], $errorsForm, '');
}

echo '<script>';
    echo 'var csrf_token = "'.token_set().'";';
echo '</script>';

echo '</div>';
echo '</div>';
echo tpl_get_footer();
