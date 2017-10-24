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
 * create a vhost
 * set the settings files and create folders
 *
 * @params $settings array, settings
 * @return bool
 */
function settings_vhost_create($settings)
{
    return (folder_create(DIR_VHOST_ADDONS, true, true)
         && folder_create(DIR_VHOST_CACHE, true, true)
         && folder_create(DIR_VHOST_BACKUP, true, true)
         && folder_create(DIR_VHOST_DATABASES, true, true)
         && folder_create(DIR_VHOST_SETTINGS, true, true)
         && folder_create(DIR_DOCUMENTS, false, true)
         && folder_create(DIR_IMAGES, false, true)
         && folder_create(DIR_MUTUAL, true, true)
         && folder_create(DIR_LOG, true, true)
         && folder_create(DIR_CACHE, true, true)
         && folder_create(DIR_TEMP, true, true)
         && settings_vhost_write($settings)
        );
}

/**
 * Create the advanced configuration file.
 */
function settings_vhost_write($new_settings)
{
    if (!is_array($new_settings)) {
        return false;
    }

    $current = settings_vhost_get();
    if ($current === false) {
        $current = array();
    }

    $settings = array_merge(settings_vhost_default(), $current, $new_settings);

    return file_ini_write(
        FILE_VHOST_SETTINGS,
        $settings,
        'This file is part of BlogoText and contains some settings for a VHOST'
    );
}

function settings_vhost_form_sanitize($posted)
{
    $return = array();
    $default = settings_vhost_default();

    foreach ($default as $key => $val) {
        if (is_bool($val)) {
            $return['values'][$key] = (bool)(isset($posted[$key]));
        // when _POST[X] == 0
        } else if (is_int($val) && isset($posted[$key]) && $posted[$key] == '0') {
            $return['values'][$key] = 0;
        } else {
            if (!isset($posted[$key])) {
                $return['errors'][$key] = 'missing';
            } else if (empty($posted[$key])) {
                $return['errors'][$key] = 'empty';
            }
        }
    }

    // check input number (<input type="number" min="0" max="100" ...)
    $test_number_0_100 = array(
        'max_bill_acceuil', 'max_bill_admin', 'max_comm_admin', 'max_rss_admin', 'nb_list_linx'
    );

    foreach ($test_number_0_100 as $key) {
        if (!isset($posted[$key])) {
            continue;
        }
        if (filter_var($posted[$key], FILTER_VALIDATE_INT) === 0
         || filter_var($posted[$key], FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 100))
        ) {
            $return['values'][$key] = $posted[$key];
        } else {
            $return['errors'][$key] = 'filter';
        }
    }

    // check submited admin folder name
    if (isset($posted['adminfold'])) {
        if (preg_match('/^[A-Za-z0-9-_]{1,50}$/', $posted['adminfold'])) {
            $return['values']['adminfold'] = $posted['adminfold'];
        } else {
            $return['errors']['adminfold'] = 'filter';
        }
    }

    // check email
    if (isset($posted['email'])) {
        if (empty($posted['email'])) {
            $return['errors']['email'] = 'empty';
        } else if (!filter_var($posted['email'], FILTER_VALIDATE_EMAIL)) {
            $return['errors']['email'] = 'filter';
        }
    }

    // simply need htmlspecialchars
    $to_htmlspecialchars = array(
        'site_description', 'site_keywords', 'site_name', 'site_theme','site_lang',
        'adminfold', 'author', 'email', 'format_date', 'format_time',
        'MYSQL_LOGIN', 'MYSQL_PASS', 'MYSQL_DB', 'MYSQL_HOST', 'DBMS', 'MYSQL_PORT',
        'SITE_UID', 'URL_ROOT'
    );
    foreach ($to_htmlspecialchars as $key) {
        if (isset($posted[$key])) {
            $return['values'][$key] = htmlspecialchars(trim($posted[$key]), ENT_QUOTES);
        }
    }

    // simply need htmlentities
    $to_htmlentities = array(
        'timezone'
    );
    foreach ($to_htmlentities as $key) {
        if (isset($posted[$key])) {
            $return['values'][$key] = htmlentities($posted[$key], ENT_QUOTES);
        }
    }

    // DB type
    if (isset($posted['values']['DBMS'])
     && $posted['values']['DBMS'] != 'mysql'
     && $posted['values']['DBMS'] != 'sqlite'
    ) {
        $posted['values']['DBMS'] = $default['DBMS'];
        $return['errors']['DBMS'] = 'value';
    }

    // MySQL db clean in case of sqlite
    if (isset($return['values']['DBMS'])
     && $return['values']['DBMS'] == 'sqlite'
    ) {
        $to_remove = array('MYSQL_LOGIN', 'MYSQL_DB', 'MYSQL_HOST', 'MYSQL_PASS', 'MYSQL_PORT');
        foreach ($to_remove as $key) {
            $return['values'][$key] = $default[$key];
            if (isset($return['errors'][$key])) {
                unset($return['errors'][$key]);
            }
        }
    }

    // MySQL port
    if (isset($posted['MYSQL_PORT'])) {
        if (!filter_var($posted['MYSQL_PORT'], FILTER_VALIDATE_INT)) {
            $return['values']['MYSQL_PORT'] = $default['MYSQL_PORT'];
            $return['errors']['MYSQL_PORT'] = 'filter';
        }
    }

    // specific URL
    if (isset($posted['URL_ROOT'])) {
        if (!filter_var($posted['URL_ROOT'], FILTER_VALIDATE_URL)
         || !preg_match('#^https?://.+#', $posted['URL_ROOT'])
        ) {
            $return['values']['URL_ROOT'] = $default['URL_ROOT'];
            $return['errors']['URL_ROOT'] = 'filter';
        }
    } else {
        $return['errors']['URL_ROOT'] = 'missing';
    }

    // day format
    if (isset($posted['format_date'])) {
        // must add the filter_var() === 0
        if (!filter_var($posted['format_date'], FILTER_VALIDATE_INT) === 0
         && !filter_var($posted['format_date'], FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 9))
        ) {
            $return['values']['format_date'] = $default['format_date'];
            $return['errors']['format_date'] = 'filter';
        }
    }
    // hour format
    if (isset($posted['format_time'])) {
        // must add the filter_var() === 0
        if (!filter_var($posted['format_time'], FILTER_VALIDATE_INT) === 0
         && !filter_var($posted['format_time'], FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 4))) {
            $return['values']['format_time'] = $default['format_time'];
            $return['errors']['format_time'] = 'filter';
        }
    }

    return $return;
}

/**
 * return an array with the settings
 * @params $get_extras bool, get extras datas (filter, flag, options ...)
 * @return array
 */
function settings_vhost_default()
{
    return array(
        // 'adminfold' => 'admin',

        'use_tags' => true,
        'use_feed_reader' => true,
        'max_bill_acceuil' => 10,
        'max_bill_admin' => 25,
        'max_comm_admin' => 50,
        'max_rss_admin' => 25,

        'alert_author' => true,
        'email' => '',
        'author' => '',

        'automatic_keywords' => true,

        'comments_defaut_status' => true,
        'comments_allowed' => true,
        'comments_require_email' => true,

        // items to show by page
        'quickly_items_per_page' => 50,
        // download external files (img, xml ...)
        'quickly_download_files' => false,
        // show in admin
        'quickly_enabled' => true,

        'format_date' => 0,
        'format_time' => 0,
        'timezone' => 'UTC',

        'site_lang' => 'en',
        'site_name' => BLOGOTEXT_NAME,
        'site_theme' => 'default',
        'site_description' => '',
        'site_keywords' => 'blog, blogotext',

        'URL_ROOT' => '',
        // 'description' => (isset($GLOBALS['lang']['go_to_settings']) ? addslashes(clean_txt($GLOBALS['lang']['go_to_settings'])) : ''),

        // db
        'DBMS' => 'sqlite',
        'DB_CHARSET' => '', // 'UTF-8' || 'utf8mb4'
        'DB_CHAR4B' => false, // allow 4 bytes char in db, DB_CHARSET must be utf8mb4 for MySQL
        'MYSQL_LOGIN' => '',
        'MYSQL_PASS' => '',
        'MYSQL_DB' => '',
        'MYSQL_HOST' => 'localhost',
        'MYSQL_PORT' => 3306,

        'auto_check_updates' => true,
        'auto_check_feeds' => true,
        'SITE_UID' => sha1(uniqid(mt_rand(), true)),

        'AUTH_USE_IP' => true,
        'TOKEN_TTL' => 14400, // in sec

        // add the version of BT when updating settings, used for update
        'BT_SETTINGS_VERSION' => BLOGOTEXT_VERSION,
    );
}
