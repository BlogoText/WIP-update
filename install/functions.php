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



function lang_install_get()
{
    global $install_lang;

    $lang = 'fr';
    if (isset($_GET['l']) && ($_GET['l'] == 'fr' || $_GET['l'] == 'en')) {
        $lang = htmlspecialchars($_GET['l']);
        $_SESSION['install_lang'] = $lang;
    } else if (isset($_SESSION['install_lang'])) {
        $lang = $_SESSION['install_lang'];
    }

    require_once '../inc/lang/'.$lang.'_'.$lang.'/install.php';
    require_once '../inc/lang/'.$lang.'_'.$lang.'/admin.php';
    $GLOBALS['lang'] = array_merge($GLOBALS['lang'], $install_lang);
}

function lang_install_set()
{
    global $install_lang;

    if (!is_array($GLOBALS['lang'])) {
        $GLOBALS['lang'] = array();
    }

    // merge languages
    $GLOBALS['lang'] = array_merge($GLOBALS['lang'], $install_lang);
}


/**
 * find admin folder
 */
function folder_admin_get()
{
    $found = array();
    $bt_root = defined('BT_ROOT') ? BT_ROOT : dirname(dirname(__file__)).'/';
    foreach (glob($bt_root.'*/.adminfold') as $path) {
        $found[] = str_replace(array($bt_root, '/.adminfold'), '', $path);
    }
    if (count($found) === 0) {
        if (is_dir(BT_ROOT.'admin/')) {
            return array('admin');
        }
        die('no admin folder found');
    }
    return $found;
}


/**
 * light template system for installation
 */
function install_get_template($tpl, $lang = 'FR_fr', $datas = array())
{
    // load templates
    $main_tpl = file_get_contents('tpl/tpl.html');
    $part_tpl = file_get_contents('tpl/'.$tpl.'.html');

    // merge template
    $tpl = str_replace('{part_tpl}', $part_tpl, $main_tpl);

    // insert vars
    foreach ($datas as $key => $value) {
        if (isset($GLOBALS['lang'][$value])) {
            $tpl = str_replace('{'.$key.'}', $GLOBALS['lang'][$value], $tpl);
        } else {
            $tpl = str_replace('{'.$key.'}', $value, $tpl);
        }
    }

    // insert language
    foreach ($GLOBALS['lang'] as $tag => $words) {
        $tpl = str_replace('{'.$tag.'}', $words, $tpl);
    }

    // re-insert vars, in cas of $GLOBALS['lang'] contains others references
    foreach ($datas as $key => $value) {
        if (isset($GLOBALS['lang'][$value])) {
            $tpl = str_replace('{'.$key.'}', $GLOBALS['lang'][$value], $tpl);
        } else {
            $tpl = str_replace('{'.$key.'}', $value, $tpl);
        }
    }

    // some vars of bt
    $sys = array(
        'BLOGOTEXT_NAME' => BLOGOTEXT_NAME,
        'BLOGOTEXT_SITE' => BLOGOTEXT_SITE,
        'BLOGOTEXT_VERSION' => BLOGOTEXT_VERSION,
        'MINIMAL_PHP_REQUIRED_VERSION' => MINIMAL_PHP_REQUIRED_VERSION,
        'USER_PASS_MIN_STRLEN' => USER_PASS_MIN_STRLEN,
        // 'URL_ROOT' => (!empty(URL_ROOT)) ? URL_ROOT : '',
        'adminfold' => folder_admin_get()['0'],
    );
    foreach ($sys as $key => $value) {
        $tpl = str_replace('{'.$key.'}', $value, $tpl);
    }

    // remove unused tags
    if (strpos($tpl, '{') !== false) {
        $tpl = preg_replace('/\{[a-zA-Z0-9\-\_]*\}/', '', $tpl);
    }

    // return template
    return $tpl;
}

/**
 * tests for db connection
 *   - test settings for connexion (server, port, db ...)
 *   - test charset support (*)
 * (*) MySQL have a shitty support for UTF-8, for MySQL, UTF-8 is a max 3 bytes, don't cares about REAL UTF-8 oO
 *     With MySQL 5.5.3 utf8mb4 was added, supporting REAL UTF-8 \o/, now, we must test the real support ...
 *
 * @params $DBMS string, mysql or sqlite
 * @params $host string, ip of the mysql server or dir path to db file (sqlite)
 * @params 
 *
 * @return string || array()
 *    string : error message
 *    array : charsets
 */
function db_test($DBMS, $host, $port, $db_name, $login, $pass)
{

    $support = array(
        'DB_CHARSET' => '',
        'DB_CHARSET_TABLE' => '',
        'DB_CHAR4B' => '',
    );
    // charsets to test (for mysql)
    $charsets_connexion = array('utf8mb4', 'UTF-8');
    // the last one is default (depends on db config
    $charsets_table = array('utf8mb4', 'UTF-8', '');

    /**
     * sqlite support UTF8/UTF16
     * test right now UTF8
     */
    if ($DBMS == 'sqlite') {
        // if (!folder_create(DIR_VHOST_DATABASES, true, true)) {
        if (!folder_create($host, true, true)) {
            die('Unable to create database folder for this VHOST, please check your files rights (chmod?)');
        }
        $db_file = './db_test_charset.sqlite';
        if (!BT_RUN_INSTALL && !is_file($db_file)) {
            die('Database file doesn\'t exists !');
        }

        try {
            // pdo seem's don't really care about charset ???
            $db_handler = new PDO('sqlite:'.$db_file, 'charset=UTF-8');
            $db_handler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db_handler->query('PRAGMA temp_store=MEMORY; PRAGMA synchronous=OFF; PRAGMA journal_mode=WAL;');
            if (is_object($db_handler)) {
                $support['DB_CHARSET'] = 'UTF-8';
            }
        } catch (Exception $e) {
            if (BT_RUN_INSTALL) {
                return $e->getMessage();
            } else {
                die('Error mysql: '.$e->getMessage());
            }
        }

    /**
     * test differents charsets to connect to mysql
     */
    } else if ($DBMS == 'mysql') {
        foreach ($charsets_connexion as $charset) {
            if (!empty($support['DB_CHARSET'])) {
                continue;
            }
            try {
                $db_handler = new PDO('mysql:host='.$host.';port='.$port.';dbname='.$db_name.';charset='.$charset.';', $login, $pass);
                $db_handler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $db_handler->query('SET sql_mode="PIPES_AS_CONCAT"');
                if (is_object($db_handler)) {
                    $support['DB_CHARSET'] = $charset;
                }
            } catch (Exception $e) {
                if (BT_RUN_INSTALL) {
                    return $e->getMessage();
                } else {
                    die('Error mysql: '.$e->getMessage());
                }
            }
        }
    }

    // var_dump($db_handler);

    /* Now, make sure for utf8mb4 support */

    // don't know what some char means, hoping it's not insulting oO
    $test_value = 'test föâFÖÂ # @ ❤ ☀ ☆ ☂ ☻ ♞ ☯ 𡃁 𨋢𠵱𥄫𠽌唧 𠱁 😸';
    $test_mb_strlen = 41;
    $test_strlen = 82;

    $db_req = array();

    foreach ($charsets_table as $charset) {
        // charset founded in last loop
        if (!empty($support['DB_CHAR4B']) && is_bool($support['DB_CHAR4B'])) {
            continue;
        }

        // make 1 table per charset test
        $table_name = 'test_charset_'.str_replace('-', '', $charset);

        $db_req['clean'] = 'DROP TABLE IF EXISTS `'.$table_name.'`;';
        $db_req['create'] = 'CREATE TABLE `'.$table_name.'` (`test` VARCHAR( 100 ) NOT NULL)';
        $db_req['insert'] = 'INSERT INTO `'.$table_name.'` (`test`) VALUES (?)';
        $db_req['select'] = 'SELECT * FROM `'.$table_name.'`';
        $db_req['drop'] = 'DROP TABLE `'.$table_name.'`';

        $fail = 0;
        foreach ($db_req as $req_type => $req) {
            if ($fail !== 0) {
                var_dump(__line__);
                break;
            }

            if ($req_type == 'create' || $req_type == 'drop' || $req_type == 'clean') {
                // set table charset for MySQL
                if ($req_type == 'create' && $DBMS == 'mysql' && !empty($charset)) {
                    $req .= ' DEFAULT CHARSET='.$charset;
                }
                try {
                    $test = $db_handler->exec($req);
                } catch (Exception $e) {
                    ++$fail;
                    var_dump($e->getMessage());
                }
            }
            if ($req_type == 'insert') {
                try {
                    $req_insert = $db_handler->prepare($req);
                    $test = $req_insert->execute(array($test_value));
                } catch (Exception $e) {
                    ++$fail;
                    var_dump($e->getMessage());
                }
            }
            // the charset test is here !
            if ($req_type == 'select') {
                $from_db = array();
                try {
                    $req_select = $db_handler->prepare($req);
                    $test = $req_select->execute();
                    while ($row = $req_select->fetch(PDO::FETCH_ASSOC)) {
                        $from_db[] = $row;
                    }
                } catch (Exception $e) {
                    ++$fail;
                    var_dump($e->getMessage());
                    continue;
                }

                // check the charset
                $support['DB_CHAR4B'] = (
                        count($from_db) === 1
                     && isset($from_db['0'])
                     && mb_strlen($from_db['0']['test']) == $test_mb_strlen // check the chat count
                     && strlen($from_db['0']['test']) == $test_strlen // check the bytes count
                     && $charset == 'utf8mb4'
                    );
            }
        }
    }

    // shutdown the db connexion
    $db_handler = null;

    // var_dump($support);
    if (empty($support['DB_CHAR4B']) && !is_bool($support['DB_CHAR4B'])) {
        return 'Fail on charset tests, please make sure your database is (really) utf8 compliant';
    }

    return $support;
}

/**
 * Creates a new BlogoText base.
 * if file does not exists, it is created, as well as the tables.
 * if file does exists, tables are checked and created if not exists
 */
function db_create()
{
    // create the sql db folder
    if (DBMS == 'sqlite' && !folder_create(DIR_VHOST_DATABASES, true, true)) {
        die('Unable to create database folder for this VHOST, please check your files rights (chmod?)');
    }

    // SQLite doesn't need this, but MySQL does.
    $auto_increment = (DBMS == 'mysql') ? 'AUTO_INCREMENT' : '';
    // MySQL needs a limit for indexes on TEXT fields.
    $index_limit_size = (DBMS == 'mysql') ? '(15)' : '';
    // MySQL doesn’t know this statement for INDEXES
    $if_not_exists = (DBMS == 'sqlite') ? 'IF NOT EXISTS' : '';
    // $if_not_exists = 'IF NOT EXISTS';
    // set charset
    // $charset = (DBMS == 'mysql') ? 'DEFAULT CHARSET=utf8' : '';
    $charset = (DBMS == 'mysql') ? 'DEFAULT CHARSET='. DB_CHARSET : '';

    // links
    $db_request['links'] = '
        CREATE TABLE IF NOT EXISTS `links` (
          `ID` INTEGER PRIMARY KEY '.$auto_increment.',
          `bt_type` char(20) NOT NULL,
          `bt_id` BIGINT DEFAULT NULL,
          `bt_content` text NOT NULL,
          `bt_wiki_content` text NOT NULL,
          `bt_title` text NOT NULL,
          `bt_tags` text NOT NULL,
          `bt_link` text NOT NULL,
          `bt_statut` tinyint DEFAULT NULL
        ) '.$charset.'; CREATE INDEX '.$if_not_exists.' dateL ON links ( bt_id );';

    // comments
    $db_request['comments'] = '
        CREATE TABLE IF NOT EXISTS `commentaires` (
            `ID` INTEGER PRIMARY KEY '.$auto_increment.',
            `bt_type` CHAR(20) NOT NULL,
            `bt_id` BIGINT DEFAULT NULL,
            `bt_article_id` BIGINT DEFAULT NULL,
            `bt_content` TEXT NOT NULL,
            `bt_wiki_content` TEXT NOT NULL,
            `bt_author` TEXT NOT NULL,
            `bt_link` TEXT NOT NULL,
            `bt_webpage` TEXT NOT NULL,
            `bt_email` TEXT NOT NULL,
            `bt_subscribe` TINYINT DEFAULT NULL,
            `bt_statut` TINYINT DEFAULT NULL
        ) '.$charset.'; CREATE INDEX '.$if_not_exists.' dateC ON commentaires ( bt_id );';

    // articles
    $db_request['articles'] = '
        CREATE TABLE IF NOT EXISTS `articles` (
            `ID` INTEGER PRIMARY KEY '.$auto_increment.',
            `bt_type` CHAR(20) NOT NULL,
            `bt_id` BIGINT DEFAULT NULL,
            `bt_date` BIGINT DEFAULT NULL,
            `bt_title` text NOT NULL,
            `bt_abstract` text NOT NULL,
            `bt_notes` text NOT NULL,
            `bt_link` text NOT NULL,
            `bt_content` text NOT NULL,
            `bt_wiki_content` text NOT NULL,
            `bt_tags` text NOT NULL,
            `bt_keywords` text NOT NULL,
            `bt_nb_comments` INTEGER DEFAULT NULL,
            `bt_allow_comments` TINYINT DEFAULT NULL,
            `bt_statut` TINYINT DEFAULT NULL
        ) '.$charset.'; CREATE INDEX '.$if_not_exists.' dateidA ON articles ( bt_date, bt_id );';

    /* here bt_ID is a GUID, from the feed, not only a 'YmdHis' date string.*/
    $db_request['rss'] = '
        CREATE TABLE IF NOT EXISTS `rss` (
            `ID` INTEGER PRIMARY KEY '.$auto_increment.',
            `bt_id` BIGINT DEFAULT NULL,
            `bt_date` BIGINT DEFAULT NULL,
            `bt_title` text NOT NULL,
            `bt_link` text NOT NULL,
            `bt_feed` text NOT NULL,
            `bt_content` text NOT NULL,
            `bt_statut` TINYINT DEFAULT NULL,
            `bt_bookmarked` TINYINT DEFAULT NULL,
            `bt_folder` text NOT NULL
        ) '.$charset.'; CREATE INDEX '.$if_not_exists.' dateidR ON rss ( bt_date, bt_id );';


    // try to connect to db
    $db_handle = db_connect();
    if (!is_object($db_handle)) {
        // var_dump(__line__);
        return $db_handle;
    }

    // push create table
    $error = 0;
    foreach ($db_request as $id => $request) {
        try {
            $exec = $db_handle->exec($request);
            // var_dump($exec);
        } catch (Exception $e) {
            var_dump($e->getMessage());
            ++$error;
        }
    }

    return ($error === 0) ? true : 'Fail on database creation';
}
