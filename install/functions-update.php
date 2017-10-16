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
 * handler for db_connect()
 * this function must be updated to use db_connect()
 */
function update_db_connect($db_settings)
{
    $default = array(
                    'type' => '',
                    'file' => '',
                    'host' => '',
                    'port' => '',
                    'login' => '',
                    'password' => '',
                    'name' => '',
                );

    $settings = array_merge($default, $db_settings);

    define('DBMS', $settings['type']);
    define('FILE_VHOST_DB', $settings['file']);
    define('MYSQL_HOST', $settings['host']);
    define('MYSQL_DB', $settings['name']);
    define('MYSQL_PORT', $settings['port']);
    define('MYSQL_LOGIN', $settings['login']);
    define('MYSQL_PASS', $settings['password']);

    return db_connect();
}

/**
 * find BT version before this update
 * can be used to detect if BT is already installed
 *
 * return mixed,
 *            - string, BT installed version
 *            - false, BT not installed
 */
function update_get_installed_version()
{
    // detect < 3.7
    if (file_exists('../config/prefs.php')) {
        return '3.4.7';
    }
    // 3.7.X
    if (file_exists('../config/settings.php')) {
        return '3.7';
    }
    // can't keep this...
    // 3.8.X and >
    if (function_exists('settings_vhost_get')) {
        $settings = settings_vhost_get(false);
        if ($settings !== false && isset($settings['BT_SETTINGS_VERSION'])) {
            return $settings['BT_SETTINGS_VERSION'];
        }
    }
    // 3.8.X and >
    if (file_exists(BT_ROOT.'var/000_common/version.txt')) {
        return file_get_contents(BT_ROOT.'var/000_common/version.txt');
    }

    return false;
}

function update_proceed()
{
    $have_update = true;
    $reports = array();

    // clean cache
    if (!cache_clean_all()) {
        // to do : change to error message (and continue ?)
        die('can\'t to clean cache');
    }

    $i = 0;
    while ($have_update) {
        $version = update_get_installed_version();
        var_dump($version);
        $update_proceed = null;
        $update_vars = array();
        if (!$version) {
            $have_update = false;
        }
        $update_file = BT_ROOT.'install/update/from-'.$version.'.php';
        var_dump($update_file);
        if (file_exists($update_file)) {
            var_dump('file_exists');
            require_once($update_file);
            if ($update_proceed !== null) {
                var_dump(__line__);
                $reports[$version] = $update_proceed();
            }
        } else {
            $have_update = false;
            var_dump('!file_exists');
        }
        ++$i;
        if ($i == 10) {
            var_dump($reports);
            return false;
        }
    }

    var_dump($reports);
    return $reports;
}

/**
 * VHOST READY
 * to do : move to somewhere else
 */
function cache_clean_all()
{
    $error = 0;
    // clean vhost cache from 3.8.0
    foreach (glob(BT_ROOT."var/*/cache/", GLOB_ONLYDIR) as $path){
        if (!folder_rmdir_recursive($path)) {
            ++$error;
        }
    }

    return (bool)($error === 0);
}