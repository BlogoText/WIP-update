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

function comments_default()
{
    return array(
        'ID' => '',
        'bt_id' => '',
        'bt_article_id' => '',
        'bt_content' => '',
        'bt_wiki_content' => '',
        'bt_author' => '',
        'bt_link' => '',
        'bt_webpage' => '',
        'bt_email' => '',
        'bt_subscribe' => '',
        'bt_statut' => '',
    );
}

/**
 * push a comment to the database and update comments counter on article
 *
 * @params $comment array
 * @return true|string, string in cas of error
 */
function comments_db_push($comment)
{
    try {
        $req = $GLOBALS['db_handle']->prepare('INSERT INTO commentaires
            (   bt_type,
                bt_id,
                bt_article_id,
                bt_content,
                bt_wiki_content,
                bt_author,
                bt_link,
                bt_webpage,
                bt_email,
                bt_subscribe,
                bt_statut
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $req->execute(array(
            'comment',
            $comment['bt_id'],
            $comment['bt_article_id'],
            $comment['bt_content'],
            $comment['bt_wiki_content'],
            $comment['bt_author'],
            $comment['bt_link'],
            $comment['bt_webpage'],
            $comment['bt_email'],
            $comment['bt_subscribe'],
            $comment['bt_statut']
        ));

        // update counter
        comments_db_upd_counter($comment['bt_article_id']);

    } catch (Exception $e) {
        return $e->getMessage();
    }

    return true;
}

function comments_db_upd_counter($article_id)
{
    try {
        // get count of activated comments
        $sql = '
            SELECT count(ID) AS counter
              FROM commentaires
             WHERE bt_article_id = ?
                   AND bt_statut = 1';
        $nb_comments_art = db_items_list_count($sql, array($article_id));

        if (!is_numeric($nb_comments_art)) {
            return 'Fail to count comments';
        }

        // update counter for this article
        $sql2 = '
            UPDATE articles
               SET bt_nb_comments = ?
             WHERE bt_id = ?';
        $req2 = $GLOBALS['db_handle']->prepare($sql2);
        $req2->execute(array($nb_comments_art, $article_id));
    } catch (Exception $e) {
        return 'Error : '.$e->getMessage();
    }

    return true;
}

/**
 * proceed the comment form (public side)
 *
 * to do v4
 *   - upd form inputs key according to the db
 */
function comments_proceed_public($article_id)
{
    $errors = array();

    var_dump(TOKEN_CHECK);
    if (TOKEN_CHECK !== true) {
        $errors[] = $GLOBALS['lang']['err_wrong_token'];
    }

    $posted = filter_input_array(
                INPUT_POST,
                array(
                        'commentaire' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                        'author' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                        'email' => FILTER_VALIDATE_EMAIL,
                        'webpage' => FILTER_VALIDATE_URL,
                        'subscribe' => FILTER_VALIDATE_BOOLEAN, // FILTER_VALIDATE_INT
                        'token' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                        'captcha' => FILTER_VALIDATE_INT,
                        'allowcuki' => FILTER_VALIDATE_INT,
                        'subscribe' => FILTER_VALIDATE_INT,
                    )
            );

    // add some additionnal check
    $allowed_urls = array('http://', 'https://');
    if (!empty($posted['webpage'])
     && !preg_match('#^('.join('|', $allowed_urls).')#i', $posted['webpage'])
    ) {
        $errors[] = $GLOBALS['lang']['err_comm_email'] ;
    }

    if (empty(trim($posted['author']))) {
        $errors[] = $GLOBALS['lang']['err_comm_author'];
    }

    $ua = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if ($posted['token'] != sha1($ua.$posted['captcha'])) {
        $errors[] = $GLOBALS['lang']['err_comm_captcha'];
    }

    $comment = array (
            'ID' => '',
            'bt_id' => date('YmdHis'),
            'bt_article_id' => $article_id,
            'bt_content' => $posted['commentaire'],
            'bt_wiki_content' => $posted['commentaire'],
            'bt_author' => $posted['author'],
            'bt_email' => $posted['email'],
            'bt_link' => '',
            'bt_webpage' => $posted['webpage'],
            'bt_subscribe' => $posted['subscribe'],
            'bt_statut' => $GLOBALS['comments_defaut_status'],
        );

    var_dump($posted);
    var_dump($errors);
    var_dump($comment);
    return true;

    // COMMENT POST INIT
    // $comment = init_post_comment($id, 'public');
    if (isset($_POST['enregistrer'])) {
        $erreurs_form = comments_form_check($comment, 'public');
    }

    if (empty($erreurs_form) and isset($_POST['enregistrer'])) {
        comments_form_proceed($comment, 'public');
    }
}

/**
 * to do 4.0 : 
 *   - rewrite to allow custom form
 */
function comments_form($article_id, $datas = array(), $errors = array())
{
    $cookie = array();
    $cookie_checked = '';
    $subscribe_checked = '';

    // user reminder by cookie
    if (isset($_COOKIE['cookie_comment'])) {
        $cookie_checked = ' checked="checked"';
        $t = json_decode($_COOKIE['cookie_comment'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $cookie['author'] = (!empty($t['author'])) ? protect($t['author']) : '' ;
            $cookie['e_mail'] = (!empty($t['email'])) ? protect($t['email']) : '' ;
            $cookie['webpage'] = (!empty($t['webpage'])) ? protect($t['webpage']) : '' ;
            if (isset($t['subscribe']) && $t['subscribe'] == '1') {
                $subscribe_checked = ' checked="checked"';
            }
        }
    }

    $datas = array_merge(
        array(
            'author' => '',
            'e_mail' => '',
            'webpage' => '',
            'comment' => '',
            'statut' => '',
            'bt_id' => '',
            'ID' => ''
        ),
        $cookie,
        $datas
    );

    $required_email = ($GLOBALS['comments_require_email'] == 1) ? 'required' : '';
    // Formulaire commun
    $form = '
        <form id="form-commentaire" class="form-commentaire" method="post" action="?'.$_SERVER['QUERY_STRING'].'">
            <fieldset class="field">
                '.form_bb_toolbar(false).'<!-- required="" -->
                <textarea class="commentaire" name="commentaire" placeholder="'.$GLOBALS['lang']['label_comment'].'" id="commentaire" cols="50" rows="10">'.$datas['comment'].'</textarea>
            </fieldset>
            <fieldset class="infos">
                <label>
                    '.$GLOBALS['lang']['label_dp_pseudo'].'<!--  required="" -->
                    <input type="text" name="author" placeholder="John Doe" value="'.$datas['author'].'" size="25" class="text" />
                </label>
                <label>
                    '.(($GLOBALS['comments_require_email'] == 1) ? $GLOBALS['lang']['label_dp_email_required'] : $GLOBALS['lang']['label_dp_email']).'
                    <input type="email" name="email" placeholder="mail@example.com" '.$required_email.' value="'.$datas['e_mail'].'" size="25" />
                </label>
                <label>
                    '.$GLOBALS['lang']['label_dp_webpage'].'
                    <input type="url" name="webpage" placeholder="http://www.example.com" value="'.$datas['webpage'].'" size="25" />
                </label>
                <label>
                    '.$GLOBALS['lang']['label_dp_captcha'].'<b>'.en_lettres($GLOBALS['captcha']['x']).'</b> &#x0002B; <b>'.en_lettres($GLOBALS['captcha']['y']).'</b>
                    <input type="number" name="captcha" autocomplete="off" value="" class="text" />
                </label>
            </fieldset>
            <fieldset class="subsc">
                <input class="check" type="checkbox" id="allowcuki" name="allowcuki"'.$cookie_checked.' />'.label('allowcuki', $GLOBALS['lang']['comment_cookie']).'<br/>
                <input class="check" type="checkbox" id="subscribe" name="subscribe"'.$subscribe_checked.' />'.label('subscribe', $GLOBALS['lang']['comment_subscribe']).'
            </fieldset>
            '.hidden_input('_id', $article_id).'
            '.hidden_input('token', token_set()).'
            '.hidden_input('_captcha', $GLOBALS['captcha']['hash']).'
            '.hidden_input('_verif_envoi', '1').'
            '.hidden_input('_form', 'comment').'
            <fieldset class="buttons">
                <input class="submit" type="submit" name="enregistrer" value="'.$GLOBALS['lang']['send'].'" />
                <input class="submit" type="submit" name="previsualiser" value="'.$GLOBALS['lang']['preview'].'" />
            </fieldset>
        </form>';
    // petit message en cas de moderation a-priori
    if ($GLOBALS['comments_defaut_status'] == '0') {
        $form .= '
            <div class="need-validation">
                '.$GLOBALS['lang']['notice'].' : '.$GLOBALS['lang']['comment_need_validation'].'
            </div>';
    }

    return $form;
}

/**
 *
 */
function comments_form_check($comment, $mode)
{
    $erreurs = array();
    if (!strlen(trim($comment['bt_author']))) {
        $erreurs[] = $GLOBALS['lang']['err_comm_author'];
    }
    if (!empty($comment['bt_email']) or $GLOBALS['comments_require_email'] == 1) { // if email is required, or is given, it must be valid
        if (!preg_match('#^[-\w!%+~\'*"\[\]{}.=]+@[\w.-]+\.[a-zA-Z]{2,6}$#i', trim($comment['bt_email']))) {
            $erreurs[] = $GLOBALS['lang']['err_comm_email'] ;
        }
    }
    if (!strlen(trim($comment['bt_content'])) or $comment['bt_content'] == "<p></p>") { // comment may not be empty
        $erreurs[] = $GLOBALS['lang']['err_comm_content'];
    }
    if (!preg_match('/\d{14}/', $comment['bt_article_id'])) { // comment has to be on a valid article_id
        $erreurs[] = $GLOBALS['lang']['err_comm_article_id'];
    }

    if (trim($comment['bt_webpage']) != "") { // given url has to be valid
        if (!preg_match('#^(https?://[\S]+)[a-z]{2,6}[-\#_\w?%*:.;=+\(\)/&~$,]*$#', trim($comment['bt_webpage']))) {
            $erreurs[] = $GLOBALS['lang']['err_comm_webpage'];
        }
    }
    if ($mode != 'admin') { // if public : tests captcha as well
        $ua = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if ($_POST['_token'] != sha1($ua.$_POST['captcha'])) {
            $erreurs[] = $GLOBALS['lang']['err_comm_captcha'];
        }
    } else { // mode admin : test token
        if (TOKEN_CHECK !== true) {
            $erreurs[] = $GLOBALS['lang']['err_wrong_token'];
        }
    }
    return $erreurs;
}


// Called when a new comment is posted (public side or admin side) or on edit/activating/removing
//  when adding, redirects with message after processing
//  when edit/activating/removing, dies with message after processing (message is then caught with AJAX)
// function traiter_form_commentaire($commentaire, $admin)
/**
 * proceed comment submission
 */
function comments_form_proceed($comment, $admin)
{
    $msg_param_to_trim = (isset($_GET['msg'])) ? '&msg='.$_GET['msg'] : '';
    $query_string = str_replace($msg_param_to_trim, '', $_SERVER['QUERY_STRING']);
    // add new comment (admin + public)
    if (isset($_POST['enregistrer']) and empty($_POST['is_it_edit'])) {
        $result = comments_db_push($comment);
        if ($result === true) {
            if ($GLOBALS['comments_defaut_status'] == 1) { // send subscribe emails only if comments are not hidden
                comment_send_emails($comment['bt_id']);
            }
            if ($admin == 'admin') {
                $query_string .= '&msg=confirm_comment_edit';
            }
            $redir = basename($_SERVER['SCRIPT_NAME']).'?'.$query_string.'#'.article_anchor($comment['bt_id']);
        } else {
            die($result);
        }
    } // admin operations
    elseif ($admin == 'admin') {
        // edit
        if (isset($_POST['enregistrer']) and isset($_POST['is_it_edit'])) {
            $result = comments_db_update($comment);
            $redir = basename($_SERVER['SCRIPT_NAME']).'?'.$query_string.'&msg=confirm_comment_edit';
        } // remove OR change status (ajax call)
        elseif (isset($_POST['com_supprimer']) or isset($_POST['com_activer'])) {
            $ID = (isset($_POST['com_supprimer']) ? htmlspecialchars($_POST['com_supprimer']) : htmlspecialchars($_POST['com_activer']));
            // $action = (isset($_POST['com_supprimer']) ? 'supprimer-existant' : 'activer-existant');
            $comm = array('ID' => $ID, 'bt_article_id' => htmlspecialchars($_POST['com_article_id']));
            if (isset($_POST['com_supprimer'])) {
                $result = comments_db_del($comm);
            } else {
                $result = comments_db_set_active($comm);
            }
            // Ajax response
            if ($result === true) {
                if (isset($_POST['com_activer']) and $GLOBALS['comments_defaut_status'] == 0) {
                    // send subscribe emails if comments just got activated
                    comment_send_emails(htmlspecialchars($_POST['com_bt_id']));
                }
                file_cache_lv1_refresh();
                echo 'Success'.token_set();
            } else {
                echo 'Error'.token_set();
            }
            exit;
        }
    } // do nothing & die (admin + public)
    else {
        redirection(basename($_SERVER['SCRIPT_NAME']).'?'.$query_string.'&msg=nothing_happend_oO');
    }
    if ($result === true) {
        file_cache_lv1_refresh();
        redirection($redir);
    }
    die($result);
}

/**
 * Same as init_post_article()
 * but, this one can be used on admin side and on public side.
 */
function init_post_comment($id, $mode)
{
    $comment = array();
    if (isset($id)) {
        if (($mode == 'admin') and (isset($_POST['is_it_edit']))) {
            $status = (isset($_POST['activer_comm']) and $_POST['activer_comm'] == 'on' ) ? '0' : '1'; // c'est plus « désactiver comm en fait »
            $comment_id = $_POST['comment_id'];
        } elseif ($mode == 'admin' and !isset($_POST['is_it_edit'])) {
            $status = '1';
            $comment_id = date('YmdHis');
        } else {
            $status = $GLOBALS['comments_defaut_status'];
            $comment_id = date('YmdHis');
        }

        // verif url.
        if (!empty($_POST['webpage'])) {
            $url = protect((strpos($_POST['webpage'], 'http://') === 0 or strpos($_POST['webpage'], 'https://') === 0)? $_POST['webpage'] : 'http://'.$_POST['webpage']);
        } else {
            $url = $_POST['webpage'];
        }

        $comment = array (
            'bt_id'           => $comment_id,
            'bt_article_id'   => $id,
            'bt_content'      => markup(htmlspecialchars(clean_txt($_POST['commentaire']), ENT_NOQUOTES)),
            'bt_wiki_content' => clean_txt($_POST['commentaire']),
            'bt_author'       => protect($_POST['author']),
            'bt_email'        => protect($_POST['email']),
            'bt_link'         => '', // this is empty, 'cause bt_link is created on reading of DB, not written in DB (useful if we change server or site name some day).
            'bt_webpage'      => $url,
            'bt_subscribe'    => (isset($_POST['subscribe']) and $_POST['subscribe'] == 'on') ? '1' : '0',
            'bt_statut'       => $status,
        );
    }
    if (isset($_POST['ID']) and is_numeric($_POST['ID'])) { // ID only added on edit.
        $comment['ID'] = $_POST['ID'];
    }
    return $comment;
}

/**
 * return a HTML list of the last 5 comments
 *
 * @return string
 */
function comments_aside_preview()
{
    $query = '
        SELECT a.bt_title, c.bt_author, c.bt_id, c.bt_article_id, c.bt_content
          FROM commentaires c
               LEFT JOIN articles a
                 ON a.bt_id = c.bt_article_id
         WHERE c.bt_statut = 1
               AND a.bt_statut = 1
         ORDER BY c.bt_id DESC
         LIMIT 5';
    $tableau = db_items_list($query, array(), 'commentaires');
    if (isset($tableau)) {
        $listeLastComments = '<ul class="encart_lastcom">'."\n";
        foreach ($tableau as $i => $comment) {
            $comment['content_abbr'] = strip_tags($comment['bt_content']);
            // limits length of comment abbreviation and name
            if (strlen($comment['content_abbr']) >= 60) {
                $comment['content_abbr'] = mb_substr($comment['content_abbr'], 0, 59).'…';
            }
            if (strlen($comment['bt_author']) >= 30) {
                $comment['bt_author'] = mb_substr($comment['bt_author'], 0, 29).'…';
            }
            $listeLastComments .= '<li title="'.date_formate($comment['bt_id']).'"><strong>'.$comment['bt_author'].' : </strong><a href="'.$comment['bt_link'].'">'.$comment['content_abbr'].'</a>'.'</li>'."\n";
        }
        $listeLastComments .= '</ul>'."\n";
        return $listeLastComments;
    } else {
        return $GLOBALS['lang']['no_comments'];
    }
}

/**
 * remove all cookies of comment system for BT < 3.8
 */
function comments_remove_old_cookies()
{
    $cookies = array('author_c', 'email_c', 'webpage_c', 'subscribe_c', 'cookie_c');
    foreach ($cookies as $cookie) {
        if (isset($_COOKIE[$cookie])) {
            setcookie($cookie, '', time() - 3600, null, null, false, true);
        }
    }
}

/**
 * add a cookie to remember user's form informations
 */
function comments_put_cookies()
{
    $cookie = array(
            'author' => isset($_POST['author']) ? htmlspecialchars($_POST['author'], ENT_QUOTES) : '',
            'email' => isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES) : '',
            'webpage' => isset($_POST['webpage']) ? htmlspecialchars($_POST['webpage'], ENT_QUOTES) : '',
            'subscribe' => (isset($_POST['subscribe']) && $_POST['subscribe'] == 'on') ? 1 : 0,
        );
    setcookie('cookie_comment', json_encode($cookie), (time() + 365*24*3600), null, null, false, true);
}

/**
 * Unsubscribe from comments subscription via email
 */
function comments_unsubscribe($email_b64, $article_id, $all)
{
    $email = base64_decode($email_b64);
    try {
        if ($all == 1) {
            // update all comments having $email
            $query = '
                UPDATE commentaires
                   SET bt_subscribe = 0
                 WHERE bt_email = ?';
            $array = array($email);
        } else {
            // update all comments having $email on $article
            $query = '
                UPDATE commentaires
                   SET bt_subscribe = 0
                 WHERE bt_email = ?
                       AND bt_article_id = ?';
            $array = array($email, $article_id);
        }
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute($array);
        return true;
    } catch (Exception $e) {
        die('Erreur BT 89867 : '.$e->getMessage());
    }
    return false;
}


/**
 * Having a comment ID, sends emails to the other comments that are subscriben
 * to the same article.
 */
function comment_send_emails($id_comment)
{
    // retreive from DB: article_id, article_title, author_name, author_email
    $article_id = get_entry('commentaires', 'bt_article_id', $id_comment);
    $article_title = get_entry('articles', 'bt_title', $article_id);
    $comm_author = get_entry('commentaires', 'bt_author', $id_comment);
    $comm_author_email = get_entry('commentaires', 'bt_email', $id_comment);

    // retreiving all subscriben email, except that has just been posted.
    $liste_comments = array();
    try {
        $query = '
            SELECT DISTINCT bt_email
              FROM commentaires
             WHERE bt_statut = 1
                   AND bt_article_id = ?
                   AND bt_email != ?
                   AND bt_subscribe = 1
             ORDER BY bt_id';
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute(array($article_id, $comm_author_email));
        $liste_comments = $req->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die('Error : '.$e->getMessage());
    }

    // filter empty emails
    $to_send_mail = array();
    foreach ($liste_comments as $comment) {
        if (!empty($comment['bt_email'])) {
            $to_send_mail[] = $comment['bt_email'];
        }
    }

    // Add the article author email
    if ($GLOBALS['alert_author']) {
        if ($GLOBALS['email'] != $comm_author_email) {
            $to_send_mail[] = $GLOBALS['email'];
        }
    }

    unset($liste_comments);
    if (!$to_send_mail) {
        return true;
    }

    // multipart mail
    // stolen from https://kevinjmcmahon.net/articles/22/html-and-plain-text-multipart-email-/
    //create a boundary for the email.
    $boundary = uniqid('blogotext');
    $subject = $GLOBALS['lang']['mail_subject'].$article_title.'" - '.$GLOBALS['nom_du_site'];

    // send emails
    foreach ($to_send_mail as $mail) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= 'From: no.reply_'.$GLOBALS['email']."\r\n".'X-Mailer: BlogoText - PHP/'.phpversion();
        $headers .= 'To: '.$mail."\r\n";
        $headers .= 'Content-Type: multipart/alternative;boundary=' . $boundary . "\r\n";

        $unsublink = get_blogpath($article_id, '').'&amp;unsub=1&amp;mail='.base64_encode($mail).'&amp;article='.$article_id;

        $message = 'This is a MIME encoded message.';
        $message .= "\r\n\r\n--" . $boundary . "\r\n";
        $message .= 'Content-type: text/plain;charset=utf-8'."\r\n\r\n";

        // Plain text message
        $message .= $subject."\r\n";
        $message .= str_repeat('=', strlen($subject));
        $message .= "\r\n\n";
        $message .= $GLOBALS['lang']['mail_message1'].$comm_author.$GLOBALS['lang']['mail_message2'];
        $message .= $article_title.$GLOBALS['lang']['mail_message3'].$GLOBALS['nom_du_site'];
        $message .= "\r\n";
        $message .= $GLOBALS['lang']['mail_see'].$GLOBALS['lang']['mail_link'].': '.get_blogpath($article_id, '').'#'.article_anchor($id_comment);
        $message .= "\r\n\n---\r\n\n";
        $message .= $GLOBALS['lang']['mail_unsub']."\r\n".$unsublink.'">'.$unsublink;
        $message .= "\r\n\n";
        $message .= $GLOBALS['lang']['mail_unsuball']."\r\n".$unsublink.'&amp;all=1">'.$unsublink.'&amp;all=1';
        $message .= "\r\n\r\n";
        $message .= $GLOBALS['lang']['mail_end'];
        $message .= "\r\n";
        $message .= $GLOBALS['lang']['mail_regards'];

        $message .= "\r\n\r\n--" . $boundary . "\r\n";
        $message .= "Content-type: text/html;charset=utf-8\r\n\r\n";

        // Html message
        $message .= '<html>';
        $message .= '<head><title>'.$subject.'</title></head>';
        $message .= '<body><p>'.$GLOBALS['lang']['mail_message1'].'<b>'.$comm_author.'</b>'.$GLOBALS['lang']['mail_message2'].'<b>'.$article_title.'</b>'.$GLOBALS['lang']['mail_message3'].$GLOBALS['nom_du_site'].'.<br/>';
        $message .= $GLOBALS['lang']['mail_see'].'<a href="'.get_blogpath($article_id, '').'#'.article_anchor($id_comment).'">'.$GLOBALS['lang']['mail_link'].'</a>.</p>';
        $message .= '<p>'.$GLOBALS['lang']['mail_unsub'].'<br/><a href="'.$unsublink.'">'.$unsublink.'</a>.</p>';
        $message .= '<p>'.$GLOBALS['lang']['mail_unsuball'].'<br/> <a href="'.$unsublink.'&amp;all=1">'.$unsublink.'&amp;all=1</a>.</p>';
        $message .= '<p>'.$GLOBALS['lang']['mail_link'].'</p><p>'.$GLOBALS['lang']['mail_regards'].'</p></body>';
        $message .= '</html>';

        $message .= "\r\n\r\n--" . $boundary . "--";

        mail($mail, $subject, $message, $headers);
    }
    return true;
}
