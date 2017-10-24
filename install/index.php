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
 * dev note :
 *  - install/ will proceed installation and update
 */

/**
 * some things to define
 * need to put this somewhere else
 */
define('USER_PASS_MIN_STRLEN', 8);
define('MINIMAL_PHP_REQUIRED_VERSION', '5.5');

// this constant make BT boot differently
define('BT_RUN_INSTALL', true);



// load basic
require_once 'functions.php';

// check there is an admin fold
$admin_fold = folder_admin_get();
if ($admin_fold === false) {
    die('No admin fold found');
}

// boot
require_once '../'.$admin_fold.'/inc/boot.php';

// get lang
lang_install_get();
// language
lang_install_set();

// check compatibility
require 'compatibility.php';


require_once '../inc/filesystem.php';



// start session, can be useful
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * on first run of the installer, check if this is an update
 */
// if (!isset($_SESSION['install'])) {
    // $_SESSION['install'] = true;

    require_once BT_ROOT.'inc/sqli.php';

    // run update
    if (update_get_installed_version() !== false) {
        $update_proceed = update_proceed();
        var_dump($update_proceed);
        exit();
    } else {
        $_SESSION['install'] = true;
    }
// }
exit();



// some vars
$is_file_settings = is_file(FILE_VHOST_SETTINGS);
$is_file_user = is_file(FILE_VHOST_USER);
$install_type = (string)filter_input(INPUT_GET, 'type');
// some non blocking errors
$non_blocking_errors = array();


// settings install
if (!$is_file_settings) {
    require 'install-system.php';
}

// user install
if (!$is_file_user) {
    require 'install-user.php';
}


// cleanup and final page !
$template_final = install_get_template('tpl-install-final', 'FR_fr');

// CleanUp : remove sqlite db file
// if (DBMS == 'sqlite') {
    // $db_file = './db_test_charset.sqlite';
    // if (isset($db_file) && is_file($db_file)) {
        // @unlink($db_file);
    // }
// }

// delete install dir
if (!folder_rmdir_recursive(BT_ROOT.'install/')) {
    $template_final = str_replace('{final-error}', $GLOBALS['lang']['install_fail_to_delete_install_dir'], $template_final);
}

echo $template_final;

exit();
