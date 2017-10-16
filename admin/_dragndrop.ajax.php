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

require_once 'inc/boot.php';


/**
 * This file is called by the drag'n'drop script. It is an underground working script,
 * It is not intended to be called directly in your browser.
 */

$files = array();
// $token = (string)filter_input(INPUT_POST, 'token');
$json = '{ "url": "%s", "status": "%s", "token": "%s" }';

if (TOKEN_CHECK !== true) {
    die(printf($json, 0, 'token '.TOKEN_CHECK, ''));
}

// no file send
if (!isset($_FILES['fichier'])) {
    die(printf($json, 0, 'no file send', 0));
}

$GLOBALS['files_list'] = file_get_array(FILE_VHOST_FILES_DB);
foreach ($GLOBALS['files_list'] as $key => $file) {
    $files[] = $file['bt_id'];
}


$time = time();
$file = init_post_fichier();

// avoid ID collisions
while (in_array($file['bt_id'], $files)) {
    $time--;
    $file['bt_id'] = date('YmdHis', $time);
}
$errors = valider_form_fichier($file);

if (!$errors) {
    $newFile = file_handler_add($file, 'upload', $_FILES['fichier']);
    $file = ($newFile === null) ? $file : $newFile;
    die(printf($json, 'files.php?file_id='.$file['bt_id'].'&amp;edit', 'success', token_set()));
}
