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

// Anti XSS : /index.php/%22onmouseover=prompt(971741)%3E or /index.php/ redirects all on index.php
// If there is a slash after the "index.php", the file is considered as a folder, but the code inside it still executed.
if (strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'].'/') === 0) {
    exit(header('Location: '.$_SERVER['SCRIPT_NAME']));
}

// chrono start here, it's not for 0.00000002 sec ...
$GLOBALS['BT_timer_start'] = microtime(true);


// Use UTF-8 for all
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

/**
 * Error reporting
 *  false : prod
 *  true : dev or testing
*/
define('DEBUG', true);


/**
 * Constant for absolute PATH
 * Defined early for error logging purpose.
 */
define('BT_ROOT', dirname(dirname(__file__)).DIRECTORY_SEPARATOR);

/**
 * check intl support
 */
define('PHP_INTL', function_exists('idn_to_ascii'));

/**
 * check if maintenance mode
 */
// if (file_exists(BT_ROOT.'/index-maintenance.php')) {
    // echo file_get_contents(BT_ROOT.'/index-maintenance.php');
    // exit();
// }


// if dev mod
ini_set('display_errors', (int)DEBUG);
if (DEBUG) {
    error_reporting(-1);
} else {
    error_reporting(0);
}

/**
 * set ignore repeat for same message except if it's come from different line/file
 */
ini_set('ignore_repeated_errors', 1);
ini_set('ignore_repeated_source', 0);

// main dependancys
require_once BT_ROOT.'inc/conv.php';
require_once BT_ROOT.'inc/filesystem.php';
require_once BT_ROOT.'inc/form.php';
require_once BT_ROOT.'inc/hook.php';
require_once BT_ROOT.'inc/html.php';
require_once BT_ROOT.'inc/sqli.php';
require_once BT_ROOT.'inc/util.php';

// secure some constants
secur_constant();


// Constants: general
define('BLOGOTEXT_NAME', 'BlogoText');
define('BLOGOTEXT_SITE', 'https://github.com/BlogoText/blogotext');
define('BLOGOTEXT_VERSION', '3.8.2-dev');
define('BLOGOTEXT_UA', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0');

// Constants: folders
define('DIR_ADDONS', BT_ROOT.'addons/');
define('DIR_CONFIG', BT_ROOT.'config/');
define('DIR_DOCUMENTS', BT_ROOT.'files/');
define('DIR_IMAGES', BT_ROOT.'img/');
define('DIR_THEMES', BT_ROOT.'themes/');
define('DIR_BACKUP', BT_ROOT.'backup/');

define('DIR_VAR', BT_ROOT.'var/');
define('DIR_MUTUAL', DIR_VAR.'000_common/');
define('DIR_LOG', DIR_MUTUAL.'log/');
define('DIR_CACHE', DIR_MUTUAL.'cache/');
define('DIR_TEMP', DIR_MUTUAL.'temp/');

// if system is not fully installed
if (!BT_RUN_INSTALL && is_dir(BT_ROOT.'install/')) {
    if (IS_IN_ADMIN === true) {
        exit(header('Location: ../install/'));
    } else {
        exit(header('Location: install/'));
    }
}


// get vhost path
$vhost = secure_host_to_path($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
if (is_array($vhost)) {
    die($vhost['message']);
}

// is it a valias ?
if (is_file(DIR_VAR.$vhost.'/settings/valias.php')) {
    include DIR_VAR.$vhost.'/settings/valias.php';
    if (!isset($vhost) || !isset($valias)) {
        log_error('Wrong VALIAS settings for '. $vhost);
        die('Wrong VALIAS settings');
    }
    if (!is_dir(DIR_VAR.'/'.$vhost.'/')) {
        log_error('VHOST handler for '. $vhost .' doesn\'t exists');
        die('VHOST declared for this VALIAS doesn\'t exists :/');
    }
}

define('VHOST', $vhost);
define('DIR_VHOST', DIR_VAR.VHOST.'/');
define('DIR_VHOST_ADDONS', DIR_VHOST.'addons/');
define('DIR_VHOST_BACKUP', DIR_BACKUP.VHOST.'/');
define('DIR_VHOST_CACHE', DIR_VHOST.'cache/');
define('DIR_VHOST_DATABASES', DIR_VHOST.'databases/');
define('DIR_VHOST_SETTINGS', DIR_VHOST.'settings/');
define('FILE_VHOST_SETTINGS', DIR_VHOST_SETTINGS.'settings.php');
define('FILE_VHOST_USER', DIR_VHOST_SETTINGS.'users.php');
define('FILE_VHOST_USER_SESSION', DIR_VHOST_DATABASES.'users_sessions.php');
define('FILE_VHOST_ADDONS_DB', DIR_VHOST_DATABASES.'addons.php');
define('FILE_VHOST_FILES_DB', DIR_VHOST_DATABASES.'files.php');
define('FILE_VHOST_FEEDS_DB', DIR_VHOST_DATABASES.'rss.php');
define('FILE_VHOST_DB', DIR_VHOST_DATABASES.'database.sqlite');

// load vhost settings
if (!BT_RUN_INSTALL && !settings_vhost_define()) {
    log_error('BlogoText cannot read the settings for '. $vhost);
    die('BlogoText cannot read the settings for this host :/');
}

// check token
token_boot();

// set URLs, depends on valias, install ...
if (isset($GLOBALS['URL_ROOT'])) {
    if (isset($valias)) {
        define('URL_ROOT', $valias.((strrpos($valias, '/', -1) === false) ? '/' : '' ));
    } else {
        define('URL_ROOT', $GLOBALS['URL_ROOT'].((strrpos($GLOBALS['URL_ROOT'], '/', -1) === false) ? '/' : '' ));
    }
} else {
    define('URL_ROOT', '');
}
define('URL_DOCUMENTS', URL_ROOT.'files/');
define('URL_IMAGES', URL_ROOT.'img/');
// define('URL_BACKUP', URL_ROOT.'/backup/000_common/');
define('URL_VHOST_BACKUP', URL_ROOT.'/backup/'.VHOST.'/');

// set timezone
if (isset($GLOBALS['timezone'])) {
    date_default_timezone_set($GLOBALS['timezone']);
}


// init some vars
$GLOBALS['addons'] = array();
// $GLOBALS['form_comment'] = '';

// regenerate captcha (always)
if (!isset($GLOBALS['captcha'])) {
    $ua = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $GLOBALS['captcha']['x'] = mt_rand(4, 9);
    $GLOBALS['captcha']['y'] = mt_rand(1, 6);
    $GLOBALS['captcha']['hash'] = sha1($ua.($GLOBALS['captcha']['x']+$GLOBALS['captcha']['y']));
}

// THEMES FILES and PATHS
if (isset($GLOBALS['site_theme'])) {
    $GLOBALS['theme_style'] = str_replace(BT_ROOT, '', DIR_THEMES).$GLOBALS['site_theme'];
    $GLOBALS['theme_container'] = $GLOBALS['theme_style'].'/list.html';
    $GLOBALS['theme_post_artc'] = $GLOBALS['theme_style'].'/template/article.html';
    $GLOBALS['theme_post_comm'] = $GLOBALS['theme_style'].'/template/commentaire.html';
    $GLOBALS['theme_post_link'] = $GLOBALS['theme_style'].'/template/link.html';
    $GLOBALS['theme_post_post'] = $GLOBALS['theme_style'].'/template/post.html';
    $GLOBALS['rss'] = URL_ROOT.'rss.php';
}


/**
 * init lang
 */
lang_set_list();
lang_load_land();
