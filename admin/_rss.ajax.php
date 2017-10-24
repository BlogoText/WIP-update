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

// if this is a cron, use the new file
if ($guid !== null) {
    require '../_cron.php';
    exit();
}

require_once 'inc/boot.php';
require_once '../inc/http.php';

$GLOBALS['feeds_list'] = file_get_array(FILE_VHOST_FEEDS_DB);

if (isset($GLOBALS['feeds_list']['https://framablog.org/comments/feed/'])) {
    $GLOBALS['feeds_list']['https://framablog.org/comments/feed/']['time'] = 1;
}


/*
    This file is called by the other files. It is an underground working script,
    It is not intended to be called directly in your browser.
*/


// Retreive all RSS feeds from the sources, and save them in DB.
if (filter_input(INPUT_POST, 'refresh_all') !== null) {
    $errors = valider_form_rss();
    if ($errors) {
        die(erreurs($errors));
    }
    $nb_new = feeds_refresh_rss($GLOBALS['feeds_list']);
    echo 'Success';
    echo $nb_new;
    die;
}



// delete old entries
if (filter_input(INPUT_POST, 'delete_old') !== null) {
    $errors = valider_form_rss();
    if ($errors) {
        die(erreurs($errors));
    }

    $sql = '
        DELETE
          FROM rss
         WHERE bt_statut = 0
               AND bt_bookmarked = 0';
    $req = $GLOBALS['db_handle']->prepare($sql);
    die(($req->execute(array())) ? 'Success' : 'Fail');
}


// Add new RSS link to serialized-DB
if (filter_input(INPUT_POST, 'add-feed') !== null) {
    $errors = valider_form_rss();
    if ($errors) {
        die(erreurs($errors));
    }

    $newFeed = trim($_POST['add-feed']);
    $newFeedFolder = htmlspecialchars(trim($_POST['add-feed-folder']));
    $feed = feeds_retrieve_new_feeds(array($newFeed), '');

    if (!($feed[$newFeed]['infos']['type'] == 'ATOM' || $feed[$newFeed]['infos']['type'] == 'RSS')) {
        die('Error: Invalid ressource (not an RSS/ATOM feed)');
    }

    // Adding to serialized-db
    $GLOBALS['feeds_list'][$newFeed] = array(
        'link' => $newFeed,
        'title' => ucfirst($feed[$newFeed]['infos']['title']),
        'favicon' => 'style/rss-feed-icon.png',
        'checksum' => 42,
        'time' => 1,
        'folder' => $newFeedFolder
    );

    // Sort list with title
    $GLOBALS['feeds_list'] = array_reverse(sort_by_subkey($GLOBALS['feeds_list'], 'title'));
    file_put_array(FILE_VHOST_FEEDS_DB, $GLOBALS['feeds_list']);

    // Update DB
    feeds_refresh_rss(array($newFeed => $GLOBALS['feeds_list'][$newFeed]));
    die('Success');
}

// Mark some element(s) as read
$markAsRead = filter_input(INPUT_POST, 'mark-as-read');
if ($markAsRead !== null) {
    $errors = valider_form_rss();
    if ($errors) {
        die(erreurs($errors));
    }

    $what = $markAsRead;
    if ($what == 'all') {
        $sql = '
            UPDATE rss
               SET bt_statut = 0';
        $array = array();
    } elseif ($what == 'site' and !empty($_POST['url'])) {
        $feedurl = $_POST['url'];
        $sql = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_feed = ?';
        $array = array($feedurl);
    } elseif ($what == 'post' and !empty($_POST['url'])) {
        $postid = $_POST['url'];
        $sql = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_id = ?';
        $array = array($postid);
    } elseif ($what == 'folder' and !empty($_POST['url'])) {
        $folder = $_POST['url'];
        $sql = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_folder = ?';
        $array = array($folder);
    } elseif ($what == 'postlist' and !empty($_POST['url'])) {
        $list = json_decode($_POST['url']);
        $questionmarks = str_repeat('?,', count($list)-1).'?';
        $sql = '
            UPDATE rss
               SET bt_statut = 0
             WHERE bt_id IN ('.$questionmarks.')';
        $array = $list;
    }

    $req = $GLOBALS['db_handle']->prepare($sql);
    $db_process = ($req->execute($array));
    die($db_process ? 'Success' : 'Fail');
}

// Mark some elements as fav
$url = (string)filter_input(INPUT_POST, 'url');
if (filter_input(INPUT_POST, 'mark-as-fav') !== null && $url) {
    $errors = valider_form_rss();
    if ($errors) {
        die(erreurs($errors));
    }

    $sql = '
        UPDATE rss
           SET bt_bookmarked = (1 - bt_bookmarked)
         WHERE bt_id = ?';
    $array = array($url);

    $req = $GLOBALS['db_handle']->prepare($sql);
    die(($req->execute($array)) ? 'Success' : 'Fail');
}

exit;
