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

define('BT_RUN_LOGIN', true);
require_once 'inc/boot.php';


/**
 * Some explanation
 *
 * on successful auth login + remember me (cookie):
 *   - generate usid (usid + usid-hash)
 *   - store usid in cookie
 *   - store ip + user agent + datetime + usid + usid-hash on db file "sessions"
 *
 * on cookie connexion :
 *   - get usid from cookie
 *   - check if usid exists in db "sessions"
 *   - check if usid from cookie match a generated usid (using from usid-hash on db "sessions")
 *
 * user can check all sessions using db "sessions"
 *  and can kill a specific session (delete the usid)
 */

/**
 * process
 */

if (auth_check_session()) {
    // Return to index if session already opened
    redirection('index.php');
}

usleep(200000);  // avoid bruteforce
$login = (string)filter_input(INPUT_POST, 'login');
$password = (string)filter_input(INPUT_POST, 'password');
$check = (filter_input(INPUT_POST, 'form_submit') !== null);
$stayLogged = (filter_input(INPUT_POST, 'remember-me') !== null);
// $maxAttempts = 6;  // max attempts before blocking login page
// $banTime = 30;     // time to wait before unblocking login page, in minutes

// Auth checking
if ($check) {
    if (auth_is_valid($login, $password)) {
        // generate usid
        $usid = auth_usid_get();
        $uid = user_search_id(auth_format_login($login), 'login');
        $_SESSION['usid'] = $usid['hash'];
        $_SESSION['uid'] = $uid;

        // If user wants to stay logged
        if ($stayLogged) {
            setcookie('BT-admin-stay-logged', $usid['hash'], time() + 365 * 24 * 60 * 60, null, null, false, true);
            session_set_cookie_params(365 * 24 * 60 * 60);
        } else {
            // $_SESSION['stay_logged_mode'] = 0;
            session_regenerate_id(true);
        }

        // log user usid
        $users_usid = file_get_array(FILE_VHOST_USER_SESSION);
        $users_usid[$usid['hash']] = array(
                                'date' => date("Y-m-d H:i:s"),
                                'uid' => $uid,
                                'ip' => get_ip(),
                                'ua' => htmlspecialchars($_SERVER['HTTP_USER_AGENT']),
                                'cookie' => ($stayLogged),
                                'salt' => $usid['salt'],
                                'session_id' => session_id()
                            );
        file_put_array(FILE_VHOST_USER_SESSION, $users_usid);

        // Handle saved data/URL redirect if POST request made
        $location = 'index.php';
        if (isset($_SESSION['BT-saved-url'])) {
            $location = $_SESSION['BT-saved-url'];
            unset($_SESSION['BT-saved-url']);
        }
        if (isset($_SESSION['BT-post-token'])) {
            // The login was right, so we give a token because the previous one expired with the session
            $_SESSION['BT-post-token'] = token_set();
        }
        auth_write_access(true, $login);
        redirection($location);
    }
    auth_write_access(false, $login);
}


/**
 * echo
 */

echo tpl_get_html_head('Identification', false);
echo '<div id="axe">';

echo '<h1 class="txt-center">'.BLOGOTEXT_NAME.'</h1>';

echo '<form class="form-inline block_tiny block-white" method="post" action="auth.php">';
echo '  <div class="input input-large">';
echo '      <input autocomplete="off" id="login" name="login" placeholder="John Doe" value="" autofocus type="text" class="text" />';
echo '      <label for="login" class="ico-user" title="'.ucfirst($GLOBALS['lang']['label_dp_username']).'"></label>';
echo '  </div>';
echo '  <div class="input input-large">';
echo '      <input id="pass" type="password" placeholder="••••••••••••" name="password" value="" class="text" />';
echo '      <label for="pass" class="ico-locked" title="'.ucfirst($GLOBALS['lang']['label_dp_password']).'"></label>';
echo '  </div>';
echo '  <div class="input">';
echo '      <input type="checkbox" name="remember-me" id="remember-me" class="checkbox" />';
echo '      <label for="remember-me">Remember me ?</label>';
echo '  </div>';
echo '  <input type="hidden" name="form_submit" value="1" />';
echo '  <div class="btn-container">';
echo '      <a href="../" class="btn btn-cancel">Cancel</a>';
echo '      <input type="submit" class="btn btn-submit" value="'.$GLOBALS['lang']['connexion'].'" />';
echo '  </div>';
echo '</form>';

echo '</div>';
echo tpl_get_footer();
