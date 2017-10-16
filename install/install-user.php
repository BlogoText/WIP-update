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

// dependancy
require_once BT_ROOT.'inc/settings.php';
require_once BT_ROOT.'admin/inc/auth.php';


/**
 * handler for user installation
 * mangage form, form process ...
 *
 * @return true|string
 *   true, user is created, nothing here to handle
 *   string, the form for user creation
 */
function install_user_handler()
{
    $user = array();
    $user_default = user_default();

    // on form submit
    if (isset($_POST['form_action'])) {
        // check type of action
        $action = filter_input(INPUT_POST, 'form_action');

        // var_dump($action);
        if ($action != 'create') {
            return false;
        }

        // sanitize form/
        $posted = users_form_sanitize($_POST);
// var_dump($posted);
        $fail = (int)(isset($posted['errors'])) ? count($posted['errors']) : 0;

        // user create ?
        if ($fail === 0) {
            // add_user
            $add_user = user_add($posted['values']);
            if ($add_user === false) {
                $posted['errors']['form'] = 'error_save';
            }
// var_dump($add_user);
            // update settings for author & email
            settings_vhost_write(
                array(
                    'author' => $posted['values']['pseudo'],
                    'email' => $posted['values']['email'],
                )
            );

            return true;
        }

        // merge errors, values, default
        $user['values'] = array_merge($user_default, $posted['values']);
    } else {
        $user['values'] = $user_default;
    }

    if (!isset($user['errors'])) {
        $user['errors'] = array();
    }

    // get form
    $datas['form'] = user_edit_form($user, '', false);

    // return template
    return install_get_template('tpl-install-login', 'FR_fr', $datas);
}

$install_user = install_user_handler();

// if user is not installed, show html page and quit
if ($install_user !== true) {
    echo $install_user;
    exit();
}
