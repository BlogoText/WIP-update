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

$js = 'var BTlang = '.json_encode($datas).';';

echo $js;
