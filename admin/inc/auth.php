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

if (!defined('BT_ROOT')) {
    die('Requires BT_ROOT.');
}


/**
 * return user IP
 *
 * @return string
 */
function get_ip()
{
    $ipAddr = (string)$_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddr .= '_'.$_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    return htmlspecialchars($ipAddr);
}

/**
 * get a random salt
 *
 * @return string
 */
function random_salt($length = 32){
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length));
    }
    if (function_exists('mcrypt_create_iv')) {
        return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
    } 
    if (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }

    // hope we dont use this one, not secure ...
    return sha1(uniqid('', true).mt_rand());
}

/**
 * Generate a Uniq Session ID for cookie.
 *
 * Todo : transformer en Session ID
 *  - ajout d'un random (?)
 *  - _SESSION['SID']
 *  - stocker le SID dans le fichier user ou dans une db sessions
 *
 * @return array
*/
function auth_usid_get($salt = null)
{
    if ($salt === null) {
        $salt = random_salt();
    }
    $ipAddr = (AUTH_USE_IP) ? get_ip() : '';
    $hash = hash('sha256', $salt.$_SERVER['HTTP_USER_AGENT'].$ipAddr);

    return array('hash' => $hash, 'salt' => $salt);
}

/**
 * Password hashing process.
 * Inspired from https://blogs.dropbox.com/tech/2016/09/how-dropbox-securely-stores-your-passwords/
 *
 * @return string
*/
function auth_hash_pass($password, $checking = false)
{
    // Bypass 72 chars limitation instored by bcrypt
    $hash = hash('sha512', $password);

    if ($checking) {
        return $hash;
    }

    return password_hash($hash, PASSWORD_BCRYPT);
}

/**
 * proceed for auth_user_edit_form
 *
 * @return true|false|array
 *   true, form is ok and proceed was success
 *   false, wrong form type check
 *   array, contains errors and submitted values
 */
/*
function user_edit_form_proceed($posted)
{
    // check type of action
    $action = filter_input(INPUT_POST, 'form_action');
    if ($action != 'user_update' && $action != 'user_create') {
        return false;
    }

    // get form
    // $posted = users_form_sanitize($_POST);
    // auth check
    $auth_check = false;

    // check auth
    if (BT_RUN_INSTALL) {
        $auth_check = true;
    } else {
        $auth_check = auth_form_id_confirm_check(filter_input(INPUT_POST, 'auth_check_uid'), filter_input(INPUT_POST, 'auth_check_pass'));
        if ($auth_check !== true) {
            $posted['errors']['auth_form'] = $auth_check;
            return $posted;
        }
    }

    // update user
    if ($action == 'user_update') {
        // remove empty password error
        if (isset($posted['errors']['password'])) {
            if ($posted['errors']['password'] == 'empty') {
                unset($posted['errors']['password']);
                unset($posted['values']['password']);
            }
        }
        if (isset($posted['values']['password'])) {
            $posted['values']['password'] = auth_hash_pass($posted['values']['password']);
        }

        // update
        if (count($posted['errors']) === 0
         && !user_upd($uid, $posted['values'])
        ) {
            $posted['errors']['form'] = $GLOBALS['lang']['error_save'];
        }
    }

    // user create
    if ($action == 'user_create'
     && !isset($posted['errors'])
    ) {
        // add_user
        $add_user = user_add($posted['values']);
        if ($add_user === false) {
            $posted['errors']['form'] = 'error_save';
        }
    }

    // update settings
    if (!isset($posted['errors'])) {
        // todo
    }

    return (isset($posted['errors'])) ? $posted : true;
}
*/

/**
 *
 */
function auth_kill_session($usid = null)
{
    // log user usid
    if ($usid !== null) {
        $users_usid = file_get_array(FILE_VHOST_USER_SESSION);
        unset($users_usid[$_SESSION['usid']['hash']]);
        file_put_array(FILE_VHOST_USER_SESSION, $users_usid);
    }

    unset($_SESSION['usid'], $_SESSION['uid'], $_SESSION['tokens']);
    setcookie('BT-admin-stay-logged', null);
    session_destroy();

    // Saving server-side the possible lost data (writing article for example)
    session_start();
    session_regenerate_id(true);
    foreach ($_POST as $key => $value) {
        $_SESSION['BT-post-'.$key] = $value;
    }

    if (strrpos($_SERVER['REQUEST_URI'], '/logout.php') != strlen($_SERVER['REQUEST_URI']) - strlen('/logout.php')) {
        $_SESSION['BT-saved-url'] = $_SERVER['REQUEST_URI'];
    }
    redirection('auth.php');
}

/**
 * check som stuff about the session
 */
function auth_check_session()
{
    @session_start();
    ini_set('session.cookie_httponly', true);

    // check session
    
    $users_usid = file_get_array(FILE_VHOST_USER_SESSION);
    // unset($users_usid[$_SESSION['usid']['hash']]);
    // file_put_array(FILE_VHOST_USER_SESSION, $users_usid);

    // Check old cookie
    if (isset($_COOKIE['BT-admin-stay-logged'])) {
        $cookie_usid = htmlspecialchars($_COOKIE['BT-admin-stay-logged']);
        // session killed
        if (!isset($users_usid[$cookie_usid])) {
            return false;
        }
        $gen_usid = auth_usid_get($users_usid[$cookie_usid]['salt']);
        if ($gen_usid['hash'] != $cookie_usid) {
            return false;
        }
        // refresh cookie
        session_set_cookie_params(365 * 24 * 60 * 60);
        // to do, virer le @ et tester l'ajout d'un quickly
        @session_regenerate_id(true);
        // reset infos session
        $_SESSION['usid'] = $cookie_usid;
        $_SESSION['uid'] = $users_usid[$cookie_usid]['uid'];
        // refresh last use
        $users_usid[$cookie_usid]['date'] = date("Y-m-d H:i:s");
        file_put_array(FILE_VHOST_USER_SESSION, $users_usid);

        // seem's ok
        return true;
    }

    // check session
    if (isset($_SESSION['usid'])) {
        if (!isset($users_usid[$_SESSION['usid']])) {
            return false;
        }

        $gen_usid = auth_usid_get($users_usid[$_SESSION['usid']]['salt']);
        if ($gen_usid['hash'] != $_SESSION['usid']) {
            return false;
        }
        // refresh last use
        $users_usid[$_SESSION['usid']]['date'] = date("Y-m-d H:i:s");
        file_put_array(FILE_VHOST_USER_SESSION, $users_usid);
        return true;
    }

    return false;
}

/**
 * This will look if session expired and kill it, otherwise restore it.
 */
function auth_ttl()
{
    if (!auth_check_session()) {
        auth_kill_session();
    }

    // Restore data lost if possible
    foreach ($_SESSION as $key => $value) {
        if (substr($key, 0, 8) === 'BT-post-') {
            $_POST[substr($key, 8)] = $value;
            unset($_SESSION[$key]);
        }
    }

    // tokens cleanup
    token_cleaner();
}

/**
 * Sanitinze the login.
 */
function auth_format_login($login)
{
    return addslashes(clean_txt(htmlspecialchars($login)));
}

/**
 * Check if login and password match with the registered ones.
 */
function auth_is_valid($login, $pass)
{
    // search login
    $user_id = user_search_id(auth_format_login($login), 'login');
    // user not found
    if (!$user_id) {
        return false;
    }

    // check password
    if (!isset($GLOBALS['users'][$user_id]['password'])
     || !password_verify(auth_hash_pass($pass, true), $GLOBALS['users'][$user_id]['password'])
    ) {
        return false;
    }

    return true;
}

/**
 * Write access log.
 */
function auth_write_access($status, $username = null)
{
    $content = '[security] Login ';
    $content .= ($status) ? 'successful ' : 'fail ';
    $content .= 'for '.htmlspecialchars($username, ENT_QUOTES).' - '.get_ip();
    return log_error($content);
}

/**
 * return form asking for current user password
 * useful for adding a little security for some action
 */
function auth_form_id_confirm($error = '')
{
    $rand = uniqid('id_');

    $return = '';
    $return .= '<div class="form-auth-check">';
        $return .= '<div class="input">';
            $return .= '<input required id="'.$rand.'" type="password" class="text" size="30" name="auth_check_pass" value="" />';
            $return .= '<label for="'.$rand.'">'.$GLOBALS['lang']['security_auth_label'].'</label>';
        $return .= '</div>';
        $return .= '<div class="tips">'.$GLOBALS['lang']['security_auth_tips'].'</div>';
        $return .= (!empty($error)) ? '<div class="error">'.$error.'</div>' : '';
    $return .= '</div>';
    $return .= '<input type="hidden" name="auth_check_uid" value="1" />';

    return $return;
}

/**
 * process form from auth_form_id_confirm()
 *
 * @params $uid int, the User ID
 * @params $pass string, password submitted
 * @return bool
 */
function auth_form_id_confirm_check($uid, $pass)
{

    if ($uid === null || $pass  === null
     || empty($uid) || empty($pass)
     || !is_numeric($uid)
    ) {
        return false;
    }

    $users = users_get();
    if (!isset($users[$uid])) {
        return false;
    }

    return auth_is_valid($users[$uid]['login'], $pass);
}

/**
 * search the id by an user info
 *
 * @params $search string, the email || uid || pseudo (...) to search
 * @params $search_by string, where to search
 * @return false|int, the uid or false
 */
function user_search_id($search, $search_by = 'login')
{
    $users = users_get();

    if ($search_by == 'uid') {
        return (isset($users[$search])) ? $search : false;
    }

    foreach ($users as $key => $data) {
        if (!is_array($data)) {
            continue;
        }
        if (strtolower($data[$search_by]) == strtolower($search)) {
            return $key;
        }
    }

    return false;
}

/**
 * get all users
 *
 * @params $define bool, set in globals
 * @return array
 */
function users_get($define = true)
{
    // get from globals
    if ($define === true && isset($GLOBALS['users'])) {
        return $GLOBALS['users'];
    }

    if (!file_exists(FILE_VHOST_USER)) {
        return false;
    }

    // get from file
    $temp = parse_ini_file(FILE_VHOST_USER, true, INI_SCANNER_RAW);
    if (!$temp) {
        return false;
    }
    if (!isset($temp['last_id'])) {
        return false;
    }

    // set in globals
    if ($define === true) {
        $GLOBALS['users'] = $temp;
    }

    return $temp;
}

function user_get($uid)
{
    $users = users_get(false);
    return (isset($users[$uid])) ? $users[$uid] : false;
}

/**
 * add user
 * password will be hashed in this function
 *
 * @params $datas array, the user datas
 * @return bool||int, the user ID or false in case of error in file write
 */
function user_add($datas)
{
    $users = users_get();

    // file doesn't exists
    if ($users === false) {
        $users['last_id'] = 0;
    }

    // check if user exists
    // if (users_user_search_id($datas['login'], 'login') !== false) {
        // return false;
    // }

    // hash password
    // $datas['password'] = password_hash($datas['password'], PASSWORD_DEFAULT);
    $datas['password'] = auth_hash_pass($datas['password']);

    $users['last_id'] += 1;
    $users[$users['last_id']] = array_merge(
        user_default(),
        $datas
    );
    if (!file_ini_write(FILE_VHOST_USER, $users, 'This file contains users for a BlogoText VHOST')) {
        return false;
    }
    // refresh globals
    users_get(true);
    return $users['last_id'];
}

/**
 * update user
 * password must be hashed before this function
 *
 * @params $id int, the user ID
 * @params $datas array, the user datas
 * @return bool
 */
function user_upd($uid, $datas)
{
    $users = users_get(false);
    // if user doesn't exists ...
    if (!isset($users[$uid])) {
        return false;
    }
    // hash password
    if (!empty($datas['password'])) {
        $datas['password'] = auth_hash_pass($datas['password']);
    }
    $users[$uid] = array_merge(
        user_default(),
        $users[$uid],
        $datas
    );
    return file_ini_write(FILE_VHOST_USER, $users, 'This file contains users for a VHOST');
}

/**
 * get user default "template"
 *
 * @return array
 */
function user_default()
{
    return array(
        'login' => '',
        'password' => '',
        'pseudo' => '',
        'email' => '',
        'admin-home-settings' => '',
    );
}

/**
 * check posted datas from user form
 */
function users_form_sanitize($posted)
{
    $default = user_default();

    $return = form_basic_check($default, $posted);

    // format not handled
    unset($return['values']['admin-home-settings']);
    unset($return['errors']['admin-home-settings']);

    // email
    if (!empty($return['values']['email'])) {
        if (!filter_var($return['values']['email'], FILTER_VALIDATE_EMAIL)) {
            $return['errors']['email'] = 'filter';
        }
        $return['values']['email'] = htmlspecialchars($return['values']['email'], ENT_QUOTES);
    }

    // test login
    if (!empty($return['values']['login'])) {
        if (!preg_match('/^[A-Za-z0-9-_]{1,50}$/', $return['values']['login'])) {
            $return['errors']['login'] = 'filter';
        }
        $return['values']['login'] = auth_format_login($return['values']['login']);
    }

    // test pseudo
    if (!empty($return['values']['pseudo'])) {
        if (preg_match('#[=\'"\\\\|]#iu', $return['values']['pseudo'])) {
            $return['errors']['pseudo'] = 'pseudo';
        }
        $return['values']['pseudo'] = htmlspecialchars($return['values']['pseudo'], ENT_QUOTES);
    }

    // test password
    if (!empty($return['values']['password'])) {
        if (!is_string($posted['password']) || mb_strlen($posted['password']) < USER_PASS_MIN_STRLEN) {
            $return['errors']['password'] = 'min_strlen';
        }
        $return['values']['password'] = $posted['password'];
    }

    return $return;
}

// functions
function user_edit_form($datas, $uid = '', $ask_pass = false)
{
    $is_update = (bool)(!empty($uid));
    $pass_required = ($is_update) ? '' : 'required';
    $lang_error = isset($GLOBALS['lang']['error']) ? $GLOBALS['lang']['error'] : array();

    $form = '<form class="form-inline block_medium block-white block_legend" method="POST">';

        $form .= '<legend class="legend-user">'.$GLOBALS['lang']['users_legend'].'</legend>';

        $form .= '<div class="input">';
            $form .= '<input required type="text" name="pseudo" id="pseudo" size="30" value="'.$datas['values']['pseudo'].'" class="text" placeholder="'.$GLOBALS['lang']['users_pseudo_placeholder'].'" autofocus />';
            $form .= '<label for="pseudo">'.$GLOBALS['lang']['users_pseudo_label'].'</label>';
        $form .= '</div>';
        $form .= '<div class="tips">'.$GLOBALS['lang']['users_pseudo_tips'].'</div>';
        if (isset($datas['errors']['pseudo'])) {
            $form .= '<div class="error">'.$lang_error.$GLOBALS['lang']['error_'.$datas['errors']['pseudo']].'</div>';
        }

        $form .= '<div class="input">';
            $form .= '<input required type="text" name="login" id="login" size="30" value="'.$datas['values']['login'].'" class="text" placeholder="'.$GLOBALS['lang']['users_login_placeholder'].'" />';
            $form .= '<label for="login">'.$GLOBALS['lang']['users_login_label'].'</label>';
        $form .= '</div>';
        $form .= '<div class="tips">'.$GLOBALS['lang']['users_login_tips'].'</div>';
        if (isset($datas['errors']['login'])) {
            $form .= '<div class="error">'.$lang_error.$GLOBALS['lang']['error_'.$datas['errors']['login']].'</div>';
        }

        $form .= '<div class="input">';
            $form .= '<input type="password" pattern=".{'.USER_PASS_MIN_STRLEN.',}" name="password" id="password" size="30" value="" class="text" '.$pass_required.' />';
            $form .= '<label for="password">'.$GLOBALS['lang']['users_pass_label'].'</label>';
        $form .= '</div>';
        if ($is_update) {
            $form .= '<div class="tips">'.$GLOBALS['lang']['users_pass_tips_no_update'].'</div>';
        }
        $form .= '<div class="tips">'.$GLOBALS['lang']['users_pass_tips'].'</div>';
        if (isset($datas['errors']['password'])) {
            $form .= '<div class="error">'.$lang_error.$GLOBALS['lang']['error_'.$datas['errors']['password']].'</div>';
        }

        $form .= '<div class="input">';
            $form .= '<input required type="email" name="email" id="email" size="30" value="'.$datas['values']['email'].'" class="text" placeholder="'.$GLOBALS['lang']['users_email_placeholder'].'" />';
            $form .= '<label for="email">'.$GLOBALS['lang']['users_email_label'].'</label>';
        $form .= '</div>';
        $form .= '<div class="tips">'.$GLOBALS['lang']['users_email_tips'].'</div>';
        if (isset($datas['errors']['email'])) {
            $form .= '<div class="error">'.$lang_error.$GLOBALS['lang']['error_'.$datas['errors']['email']].'</div>';
        }

        // datas for action type
        if ($is_update) {
            $form .= '<input type="hidden" name="form_action" value="edit" />';
            $form .= '<input type="hidden" name="user_id" value="'.$uid.'" />';
        } else {
            $form .= '<input type="hidden" name="form_action" value="create" />';
        }
        $form .= hidden_input('token', token_set());

        // is not install, ask for password confirm
        if (!BT_RUN_INSTALL && $ask_pass) {
            $auth_error = (isset($datas['errors']['auth_check_pass'])) ? $datas['errors']['auth_check_pass'] : '';
            $form .= auth_form_id_confirm($auth_error);
        }

        $form .= '<div class="btn-container">';
        if ($is_update) {
            $form .= '<a class="btn btn-cancel" href="users.php">Cancel</a>';
            $form .= '<button class="btn btn-blue btn-submit" type="submit" name="proceed">'.$GLOBALS['lang']['users_btn_update_user'].'</button>';
        } else {
            $form .= '<button class="btn btn-blue btn-submit" type="submit" name="proceed">'.$GLOBALS['lang']['users_btn_create_user'].'</button>';
        }
        $form .= '</div>';

    $form .= '</form>';

    return $form;
}
