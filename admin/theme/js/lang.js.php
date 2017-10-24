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

$version = filter_input(INPUT_GET, 'v', FILTER_SANITIZE_SPECIAL_CHARS);

// make sure of use ?v=... in URL
if ($version === null
 || !preg_match('/^\d{1}\.\d{1,2}\.\d{1,2}(\-dev)?$/', $version)
) {
    header('HTTP/1.1 400 BAD REQUEST');
    die;
}


// gzip compression
if (extension_loaded('zlib') and ob_get_length() > 0) {
    ob_end_clean();
    ob_start('ob_gzhandler');
} else {
    ob_start('ob_gzhandler');
}

/**
 * put cache to /var/000_common/cache/
 */
$cache = (bool)(strpos($version, '-dev') === false); // use cache ?
if ($cache) {
    $cache_path = '../../../var/000_common/cache/';
    $cache_file = $cache_path.'admin-lang-cached-'.$version.'.js.php';
    // check if cache exists
    if (file_exists($cache_file)) {
        $cached = file_get_contents($cache_file);
        if ($cached !== false && strpos($cached, '<?php die(); ?>') !== false) {
            echo substr($cached, 15);
            die;
        }
    }
}


// overwrite request_uri to get the correct vhost
$_SERVER['REQUEST_URI'] = '/admin/';

// boot
require_once '../../inc/boot.php';
require_once BT_ROOT_ADMIN.'inc\filesystem.php';

header('Content-type: application/javascript; charset: UTF-8');

$datas['maxFilesSize'] = min(return_bytes(ini_get('upload_max_filesize')), return_bytes(ini_get('post_max_size')));
$datas['rssJsAlertNewLink'] = $GLOBALS['lang']['rss_jsalert_new_link'];
$datas['rssJsAlertNewLinkFolder'] = $GLOBALS['lang']['rss_jsalert_new_link_folder'];
$datas['confirmFeedClean'] = $GLOBALS['lang']['confirm_feed_clean'];
$datas['confirmCommentSuppr'] = $GLOBALS['lang']['confirm_comment_suppr'];
$datas['activer'] = $GLOBALS['lang']['activate'];
$datas['desactiver'] = $GLOBALS['lang']['desactivate'];
$datas['errorPhpAjax'] = $GLOBALS['lang']['error_phpajax'];
$datas['errorCommentSuppr'] = $GLOBALS['lang']['error_comment_suppr'];
$datas['errorCommentValid'] = $GLOBALS['lang']['error_comment_valid'];
$datas['questionQuitPage'] = $GLOBALS['lang']['question_quit_page'];
$datas['questionCleanRss'] = $GLOBALS['lang']['question_clean_rss'];
$datas['questionSupprComment'] = $GLOBALS['lang']['question_suppr_comment'];
$datas['questionSupprArticle'] = $GLOBALS['lang']['question_suppr_article'];
$datas['questionSupprFichier'] = $GLOBALS['lang']['question_suppr_fichier'];

$content = 'var BTlang = '.json_encode($datas).';';


// put in cache
if ($cache) {
    if (is_dir($cache_path)) {
        file_put_contents(
            $cache_file,
            '<?php die(); ?>'
                    .'"use strict";'."\n"
                    .$content
        );
    }
}

echo $content;

