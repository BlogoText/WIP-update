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
 * Complete the process, even if the client stops it
 * (cron : wget --spider ...)
 */
ignore_user_abort(true);
// set at 30 minutes, but maybe need some adjustments
set_time_limit(1800);

// get _GET
$guid = (string)filter_input(INPUT_GET, 'guid');

// if this is a cron
if ($guid !== null) {
    define('BT_RUN_CRON', true);
} else {
    die();
}

require_once 'inc/boot.php';
require_once BT_ROOT.'inc/http.php';

// Update all RSS feeds using GET (for cron jobs).
// only test here is on install UID.

if ($guid != SITE_UID) {
    exit();
}


if ($GLOBALS['auto_check_feeds']) {
    $GLOBALS['feeds_list'] = file_get_array(FILE_VHOST_FEEDS_DB);
    feeds_refresh_rss($GLOBALS['feeds_list']);
    // die();
}

// check update
if ($GLOBALS['auto_check_updates']) {
    
}

die();

