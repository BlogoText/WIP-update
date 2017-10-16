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

// retrieve posted datas
$uid = filter_input(INPUT_GET, 'uid', FILTER_VALIDATE_INT);
$action = (in_array(filter_input(INPUT_GET, 'action'), array('edit', 'session'))) ? filter_input(INPUT_GET, 'action') : null;
$form_action = filter_input(INPUT_POST, 'form_action');
$posted_usid = filter_input(INPUT_POST, 'usid', FILTER_DEFAULT , FILTER_REQUIRE_ARRAY);

$breadcrumb_1 = '';
if ($uid !== null) {
    $breadcrumb_1 = '<li><a href="users.php?uid='.$uid.'">User #'.$uid.'</a>';
}
$breadcrumb_2 = '';
if ($action !== null) {
    $breadcrumb_2 = '<li><a href="users.php?uid='.$uid.'&amp;action='.$action.'">'.$action.'</a>';
}

// init some vars
$users_info = users_get(false);
$ask_pass = false;

if ($action == 'session') {
    $all_sessions = file_get_array(FILE_VHOST_USER_SESSION);
}

// form datas
$form_success = null;

// proceed form
if ($form_action !== null && TOKEN_CHECK === true) {
    if (
        ($form_action == 'edit' && $uid !== null && isset($users_info[$uid]))
     || $form_action == 'create'
    ) {
        $form_success = false;

        // sanitize form
        $posted = users_form_sanitize($_POST);

        // proceed
        if ($form_action == 'edit' && $uid !== null && isset($users_info[$uid])) {
            // password change ?
            if (isset($posted['errors']['password']) && $posted['errors']['password'] == 'empty') {
                unset($posted['errors']['password']);
                unset($posted['values']['password']);
                if (count($posted['errors']) === 0) {
                    unset($posted['errors']);
                }
            }
            if (!isset($posted['errors'])) {
                $form_success = user_upd($uid, $posted['values']);
            }
        }

        $form_datas = $posted;
    } else if ($form_action == 'session') {
        $to_remove = count($posted_usid);
        $removed = 0;
        foreach ($posted_usid as $t) {
            if (isset($all_sessions[$t])) {
                unset($all_sessions[$t]);
                ++$removed;
            }
        }
        $form_success = ($to_remove == $removed);
    }
}

// proceed request
if ($action == 'edit' && $uid !== null && isset($users_info[$uid])) {
    // edit user
    if (!isset($form_datas)) {
        $form_datas['values'] = $users_info[$uid];
    }
} else if ($action == 'session' && $uid !== null && isset($users_info[$uid])) {
    // 
    $user_sessions = array();
    $i = 0;
    foreach ($all_sessions as $usid => $s) {
        if ($s['uid'] == $uid) {
            $user_sessions[$s['date'].$i] = $s;
            $user_sessions[$s['date'].$i]['usid'] = $usid;
            $user_sessions[$s['date'].$i]['current'] = (bool)($usid == $_SESSION['usid']);
        }
        ++$i;
    }
    ksort($user_sessions);
} else {
    $form_datas['values'] = user_default();
}


// display
echo tpl_get_html_head('Users', false);
echo '<div id="axe">';
echo '<div id="page">';

if (!empty($breadcrumb_1.$breadcrumb_2)) {
    echo '<div class="block_medium breadcrumb">';
        echo '<ul>';
            echo '<li><a href="users.php">Users</a></li>';
            echo $breadcrumb_1;
            echo $breadcrumb_2;
        echo '</ul>';
    echo '</div>';
}

if ($uid !== null && isset($users_info[$uid]) && $action === null) {
    
        echo '<div class="showhide show block-white block_medium">';
            echo '<div class="header">';
                echo '<span>'.$users_info[$uid]['pseudo'].'</span>';
            echo '</div>';
            echo '<div class="content">';
                echo '<p>'.$users_info[$uid]['login'].' &lt;'.$users_info[$uid]['email'].'&gt;</p>';
            echo '</div>';
            echo '<div class="btn-container">';
                echo '<a class="btn btn-flat" href="?uid='.$uid.'&amp;action=edit">Edit</a>';
                echo '<a class="btn btn-flat" href="?uid='.$uid.'&amp;action=session">Sessions</a>';
            echo '</div>';
        echo '</div>';
// edit
} else if ($uid !== null && isset($users_info[$uid]) && $action == 'edit') {
    echo '<div class="form-inline">';
    echo user_edit_form($form_datas, $uid, $ask_pass);
    echo '</div>';
// manage session
} else if ($uid !== null && isset($user_sessions) && $action == 'session') {
    echo '<form class="form" method="post" action="users.php?uid='.$uid.'&amp;action=session">';
    foreach ($user_sessions as $s) {
        $current_session_tips = ($s['current'] === true) ? 'This is the session you use currently !' : '';
        $current_session_disabled = ($s['current'] === true) ? 'disabled' : '';
        echo '<div class="block_medium block-white">
                <div>
                    <div class=""><span style="display:inline-block;width:100px">Last use</span><span style="display:inline-block;">'.$s['date'].'</span></div>
                    <div class=""><span style="display:inline-block;width:100px">ip</span><span style="display:inline-block;">'.$s['ip'].'</span></div>
                    <div class=""><span style="display:inline-block;width:100px;vertical-align:top;">UA</span><span style="display:inline-block;max-width:calc(100% - 120px);">'.$s['ua'].'</span></div>
                </div>
                <div class="input">
                    <input '.$current_session_disabled.' id="usid'.$s['usid'].'" type="checkbox" class="checkbox" name="usid[]" value="'.$s['usid'].'">
                    <label for="usid'.$s['usid'].'">Disconnect</label>
                    <div class="tips">
                        '.$current_session_tips.'
                    </div>
                </div>
            </div>';
    }
        echo hidden_input('_verif_envoi', 1);
        echo hidden_input('token', token_set());
        echo hidden_input('form_action', 'session');
        echo hidden_input('user_id', $uid);
        echo '<div class="block-white btn-container" id="settings-submit">';
            echo '<button class="btn btn-delete" type="submit" name="Delete">Delete</button>';
        echo '</div>';
    echo '</form>';
// Users list
} else {
    echo '<div class="block_medium block-white">';
    foreach ($users_info as $id => $datas) {
        if ($id == 'last_id') {
            continue;
        }
        echo '<div class="showhide">';
            echo '<div class="header">';
                echo '<span>'.$datas['pseudo'].'</span>';
                echo '<a href="#" class="btn btn-flat ico-chevronLeft" onclick="showhide(this);return false"></a>';
            echo '</div>';
            echo '<div class="content">';
                echo '<p>'.$datas['login'].' &lt;'.$datas['email'].'&gt;</p>';
            echo '</div>';
            echo '<div class="footer">';
                echo '<a class="btn btn-flat" href="?uid='.$id.'&amp;action=edit">Edit</a>';
                echo '<a class="btn btn-flat" href="?uid='.$id.'&amp;action=session">Sessions</a>';
            echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}

echo '</div>';
echo '</div>';
echo tpl_get_footer();
