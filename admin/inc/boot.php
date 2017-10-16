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

define('BT_ROOT_ADMIN', dirname(dirname(__file__)).DIRECTORY_SEPARATOR);
define('IS_IN_ADMIN', true);

// use realpath to get absolute path to prevent false path from other boot call (like with lang.js.php)
require_once realpath(BT_ROOT_ADMIN . '../') .'/inc/boot.php';


if (!defined('USER_PASS_MIN_STRLEN')) {
    define('USER_PASS_MIN_STRLEN', 8);
}

require_once BT_ROOT_ADMIN.'inc/auth.php'; // Security, dont move !
require_once BT_ROOT_ADMIN.'inc/filesystem.php';
require_once BT_ROOT_ADMIN.'inc/form.php';
require_once BT_ROOT_ADMIN.'inc/tpl.php'; // no choice !
require_once BT_ROOT_ADMIN.'inc/util.php';

// Some actions are not required on install or login or cron
if (!BT_RUN_INSTALL && !BT_RUN_LOGIN && !BT_RUN_CRON) {
    // define('URL_BACKUP', URL_ROOT.'bt_backup/');
    auth_ttl();
}

if (!BT_RUN_INSTALL && !BT_RUN_LOGIN) {
    $GLOBALS['db_handle'] = db_connect();
}
