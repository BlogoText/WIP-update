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


/*
    This file is called by the files. It is an underground working script,
    It is not intended to be called directly in your browser.
*/

$GLOBALS['files_list'] = file_get_array(FILE_VHOST_FILES_DB);
$fileId = filter_input(INPUT_POST, 'file_id');
$deletion = (filter_input(INPUT_POST, 'supprimer') !== null);

if ($fileId && preg_match('#^\d{14}$#', $fileId) && $deletion) {
    foreach ($GLOBALS['files_list'] as $file) {
        if ($file['bt_id'] == $fileId) {
            die(bdd_fichier($file, 'supprimer-existant', '', $file['bt_id']));
        }
    }
}
die('failure');
