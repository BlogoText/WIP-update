<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://blogotext.org/
# https://github.com/BlogoText/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***


require_once BT_ROOT.'inc/settings.php';
require_once BT_ROOT.folder_admin_get()['0'].'/inc/blog.php';

function install_proceed_form($datas)
{
    // tests and sanitize
    $t_posted = settings_vhost_form_sanitize($_POST);
    // some settings set by the core
    $settings_auto = array(
        'DB_CHARSET' => '',
        'DB_CHAR4B' => '',
    );

    // keep just some datas
    $to_keep = array(
        'DBMS', 'MYSQL_LOGIN', 'MYSQL_DB', 'MYSQL_HOST', 'MYSQL_PASS', 'MYSQL_PORT',
        'URL_ROOT', 'adminfold'
    );
    $posted = array();
    foreach ($t_posted as $type => $datas) {
        foreach ($datas as $key => $value) {
            if (in_array($key, $to_keep)) {
                $posted[$type][$key] = $value;
            }
        }
    }

    $fail = (int)(isset($posted['errors'])) ? count($posted['errors']) : 0;

    // Test and create DB
    if ($fail === 0) {

        // DB : try to connect and detect charset
        $db_test = db_test($posted['values']['DBMS'], $posted['values']['MYSQL_HOST'], $posted['values']['MYSQL_PORT'], $posted['values']['MYSQL_DB'], $posted['values']['MYSQL_LOGIN'], $posted['values']['MYSQL_PASS']);

        if (is_string($db_test)) {
            ++$fail;
            $datas['db_errors'] = $db_test;
        } else {
            // define db infos
            define('DB_CHARSET', $db_test['DB_CHARSET']);
            // define('DB_CHARSET', $db_test['DB_CHARSET']);
            define('DB_CHAR4B', $db_test['DB_CHAR4B']);
            // push to datas
            $settings_auto['DB_CHARSET'] = $db_test['DB_CHARSET'];
            // $settings_auto['DB_CHARSET'] = $db_test['DB_CHARSET'];
            $settings_auto['DB_CHAR4B'] = $db_test['DB_CHAR4B'];
        }

        // check db connexion & create db
        if ($fail === 0) {
            define('MYSQL_LOGIN', $posted['values']['MYSQL_LOGIN']);
            define('MYSQL_PASS', $posted['values']['MYSQL_PASS']);
            define('MYSQL_DB', $posted['values']['MYSQL_DB']);
            define('MYSQL_HOST', $posted['values']['MYSQL_HOST']);
            define('MYSQL_PORT', $posted['values']['MYSQL_PORT']);
            define('DBMS', $posted['values']['DBMS']);

            // 
            folder_create(DIR_VHOST_DATABASES, true, true);
            $GLOBALS['db_handle'] = db_connect();
            $db_create = db_create();
            if ($db_create !== true) {
                ++$fail;
                $datas['db_errors'] = $db_create;
            }
        }
    }

    // move admin folder
    if ($fail === 0 && !empty($posted['values']['adminfold']) && $posted['values']['adminfold'] != folder_admin_get()) {
        // check if folder name available
        if (is_dir($posted['values']['adminfold'])) {
            ++$fail;
            $posted['errors']['adminfold'] = 'sys_adminfold_already_exists';
        // rename admin folder
        } else {
            $cur_folder = rtrim(BT_ROOT.'/admin/', '/').'/';
            $new_folder = rtrim(BT_ROOT.$posted['values']['adminfold'], '/').'/';
            if ($cur_folder != $new_folder
             && !@rename($cur_folder, $new_folder)
            ) {
                ++$fail;
                $posted['errors']['adminfold'] = 'sys_adminfold_fail_to_rename';
            }
            // add file to detect adminfol
            if (file_put_contents($new_folder.'.adminfold', '', LOCK_EX) !== false) {
                ++$fail;
                $posted['errors']['adminfold'] = 'sys_adminfold_fail_to_write';
            }
        }
    }

    // write vhost settings and folders
    if ($fail === 0) {
        // VHOST
        if (settings_vhost_create(array_merge($settings_auto, $posted['values']))) {
            return true;
        } else {
            $datas['file_write_error'] = 'Fail to write vhost settings file';
        }
    }

    // merge value for form
    $datas = array_merge($datas, $posted['values']);

    // report posted errors to $datas
    if ($fail !== 0 && isset($posted['errors'])) {
        foreach ($posted['errors'] as $input => $error) {
            $datas[$input.'-error'] = $error;
        }
    }

    return $datas;
}

function install_system_handler()
{
    $datas = settings_vhost_default();

    if (isset($_POST['submit'])) {
        $datas = install_proceed_form($datas);
        if ($datas === true) {
            return true;
        }
    }

    $link = ((isset($_SERVER['HTTPS']) || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? 'https://' : 'http://');
    $link .= htmlentities($_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']));
    // bug fix for DIRECTORY_SEPARATOR
    $link = str_replace('\\', '/', $link);
    // ensure it ends with a slash
    $datas['URL_ROOT'] = rtrim($link, '/').'/';

    $datas['sys_db_settings_style'] = 'style="display:none"';
    $datas['db_errors'] = (isset($datas['db_errors'])) ? '<p class="error">'.$datas['db_errors'].'</p>' : '';

    // show/hide DB sub options
    $datas['sys_db_type_options'] = '';
    if (extension_loaded('pdo_sqlite')) {
        if ($datas['DBMS'] == 'sqlite') {
            $datas['sys_db_type_options'] .= '<option value="sqlite" selected>SQLite</option>';
        } else {
            $datas['sys_db_type_options'] .= '<option value="sqlite">SQLite</option>';
        }
    }
    if (extension_loaded('pdo_mysql')) {
        if ($datas['DBMS'] == 'mysql') {
            $datas['sys_db_type_options'] .= '<option value="mysql" selected>MySQL</option>';
            $datas['sys_db_settings_style'] = 'style="display:block"';
        } else {
            $datas['sys_db_type_options'] .= '<option value="mysql">MySQL</option>';
        }
    }

    $datas['form_hidden'] = '<input type="hidden" name="submit" value="1" />';

    return install_get_template('tpl-install-system', 'FR_fr', $datas);
}

$install_system = install_system_handler();

if ($install_system !== true) {
    echo $install_system;
    exit();
}
