<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://blogotext.org/
# https://github.com/BlogoText/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***


/**
 *
 */
function get_safe_path($path)
{
    // no relative path allowed
    if (strpos($path, './') !== false) {
        return false;
    }

    // make sure of absolute url
    $path = BT_ROOT .'/'. str_replace(BT_ROOT, '', $path);

    // prevent var\\log\test.php -> var/log/test.php
    while (strstr($path, '\\\\')) {
        $path = str_replace('\\\\', '/', $path);
    }
    // prevent var//log/test.php -> var/log/test.php
    while (strstr($path, '//')) {
        $path = str_replace('//', '/', $path);
    }

    return $path;
}

/**
 * Like rmdir, but recursive
 *
 * use of get_path(), try to prevent the end of the world...
 *
 * @params string $path, the relative path to BT_DIR
 * @return bool
 */
function folder_rmdir_recursive($path)
{
    $error = 0;

    // secure and test the path
    // just trying to avoid to delete the world or cause a zombie apocalypse
    if (($path = get_safe_path($path)) === false) {
        return false;
    }

    $dir = opendir($path);
    while (($file = readdir($dir)) !== false) {
        if (($file == '.') || ($file == '..')) {
            continue;
        }
        if (is_dir($path.$file.'/')) {
            if (!folder_rmdir_recursive($path.$file.'/')) {
                ++$error;
            }
        } else {
            if (!@unlink($path.$file)) {
                ++$error;
            }
        }
    }
    closedir($dir);
    @rmdir($path);
    // check
    if (is_dir($path)) {
        ++$error;
    }

    return ($error === 0);
}

/**
 * create a folder
 * can be used by addon
 *
 * @params $path string, absolute path
 * @params $htaccess bool, add ".htaccess" file
 * @params $recursive bool
 * @return bool
 */
function folder_create($path, $htaccess = false, $recursive = true)
{
    if (is_dir($path)) {
        return folder_secure_parents($path, $htaccess);
    }

    $fail = 0;
    if (mkdir($path, 0755, $recursive)) {
        // "secure childs"
        if ($recursive === true) {
            if (!folder_secure_parents($path, $htaccess)) {
                $fail = 1;
            }
        }

        if (!file_htaccess_write($path)) {
            $fail = 1;
        }
        if (!file_htaccess_write($path)) {
            $fail = 1;
        }
        return true;
    }

    // log error
    log_error('FAIL to create folder');

    return false;
}

/**
 * secure a path and his parents by putting some files 
 * "index.php" and ".htaccess", if there is not a "index.php" in the folder
 *
 * @params $path string, absolute path
 * @params $htaccess bool, add ".htaccess" file
 * @return bool
 */
function folder_secure_parents($path, $htaccess = false)
{
    $ctrl = mb_strlen(BT_ROOT);
    $fail = 0;
    while (mb_strlen($path) > $ctrl) {
        if ($htaccess && !is_file($path.'.htaccess')) {
            if (!file_htaccess_write($path)) {
                $fail = 1;
            }
        }
        if (!is_file($path.'index.php')) {
            if (!file_index_write($path)) {
                $fail = 1;
            }
        }
        $path = dirname($path).'/';
    }

    return (bool)($fail === 0);
}

/**
 * convert an array to ini format and put the result in .ini.php file
 *
 * @params $filename string, the path to the file to write
 * @params $datas array, 1 or 2 level
 *                           array['key'] = 'value'
 *                        or array['section']['key'] = 'value'
 * @params $message string, a little message writen in the top of the file
 * @return bool
 */
function file_ini_write($filename, $datas = array(), $message = '')
{
    $to_file = '; <?php die; ?>'."\n";
    if (!empty($message)) {
        $to_file .= '; '.$message."\n";
    }
    $to_file .= "\n";

    foreach ($datas as $key => $val) {
        if (is_array($val)) {
            $to_file .= '['.$key.']'."\n";
            foreach ($val as $ke => $va) {
                if (is_int($va) || is_bool($va)) {
                    $to_file .= $ke .' = '. $va ."\n";
                } else {
                    $to_file .= $ke .' = "'. $va .'"'."\n";
                }
            }
        } else {
            if (is_bool($val)) {
                $to_file .= $key .' = '. (($val) ? 'true' : 'false') ."\n";
            } else if (is_int($val)) {
                $to_file .= $key .' = '. $val ."\n";
            } else {
                $to_file .= $key .' = "'. $val .'"'."\n";
            }
        }
    }

    return (file_put_contents($filename, $to_file, LOCK_EX) !== false);
}

/**
 * Prevent directory listing by adding "index.php" to a folder
 *
 * @params $folder string, absolute path
 * @return bool
 */
function file_index_write($folder)
{
    $content = "<?php\nexit(header('Location: ../'));\n";
    return (file_put_contents($folder.'/index.php', $content, LOCK_EX) !== false);
}

/**
 * Prevent direct access to files by adding ".htaccess" to a folder
 *
 * @params $folder string, absolute path
 * @return bool
 */
function file_htaccess_write($folder)
{
    $content = '<Files *>'."\n";
    $content .= 'Order allow,deny'."\n";
    $content .= 'Deny from all'."\n";
    $content .= '</Files>'."\n";
    $file = $folder.'/.htaccess';

    return (file_put_contents($file, $content, LOCK_EX) !== false);
}

/**
 * refresh level 1 cache
 * used for feed
 *
 * @return bool
 */
function file_cache_lv1_refresh()
{
    folder_create(DIR_VHOST_CACHE, 1);
    $arr_a = db_items_list("SELECT * FROM articles WHERE bt_statut=1 ORDER BY bt_date DESC LIMIT 0, 20", array(), 'articles');
    $arr_c = db_items_list("SELECT c.*, a.bt_title FROM commentaires AS c, articles AS a WHERE c.bt_statut=1 AND c.bt_article_id=a.bt_id ORDER BY c.bt_id DESC LIMIT 0, 20", array(), 'commentaires');
    $arr_l = db_items_list("SELECT * FROM links WHERE bt_statut=1 ORDER BY bt_id DESC LIMIT 0, 20", array(), 'links');
    return file_put_array(DIR_VHOST_CACHE.'cache1_feed.dat', array('c' => $arr_c, 'a' => $arr_a, 'l' => $arr_l));
}
