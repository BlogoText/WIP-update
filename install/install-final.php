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
