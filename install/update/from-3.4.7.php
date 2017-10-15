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
 * update from 3.4.7 to 3.7.0
 *
 * Note: 
 *   - BT 3.7.0 change the user password hash
 */





function update_3_4_7_file_write_conf_ini($file, $datas)
{
    if (is_file($file)) {
        return true;
    }

    $conf  = '; <?php die; ?>'."\n";
    $conf .= '; This file contains some more settings.'."\n\n";
    foreach ($datas as $key => $val) {
        if (is_int($val) || is_bool($val)) {
            $conf .= strtoupper($key) .' = '. $val ."\n";
        } else {
            $conf .= strtoupper($key) .' = \''. $val .'\''."\n";
        }
    }

    var_dump($file);
    var_dump($conf);
    return (file_put_contents($file, $conf, LOCK_EX) !== false);
}

function update_3_4_7_file_write_conf_php($file, $datas)
{
    $prefs = "<?php\n";
    foreach ($datas as $key => $value) {
        $prefs .= sprintf(
            "\$GLOBALS['%s'] = %s;\n",
            $key,
            (is_numeric($value) || is_bool($value) || empty($value)) ? (int)$value : '"'.$value.'"'
        );
    }

    return (file_put_contents($file, $prefs, LOCK_EX) !== false);
}

function update_3_4_7_convert_config_files()
{
    /**
     * config from settings
     */
    $update_vars['settings']['activer_categories'] = 1;
    $update_vars['settings']['afficher_liens'] = 1;
    $update_vars['settings']['afficher_rss'] = 1;
    $update_vars['settings']['alert_author'] = 0;
    $update_vars['settings']['auteur'] = '';
    $update_vars['settings']['auto_check_updates'] = 1;
    $update_vars['settings']['automatic_keywords'] = 1;
    $update_vars['settings']['comm_defaut_status'] = 1;
    $update_vars['settings']['description'] = '';
    $update_vars['settings']['dl_link_to_files'] = 0;
    $update_vars['settings']['email'] = '';
    $update_vars['settings']['format_date'] = 0;
    $update_vars['settings']['format_heure'] = 0;
    $update_vars['settings']['fuseau_horaire'] = 'UTC';
    $update_vars['settings']['global_com_rule'] = 0;
    $update_vars['settings']['keywords'] = '';
    $update_vars['settings']['lang'] = 'fr';
    $update_vars['settings']['max_bill_acceuil'] = 10;
    $update_vars['settings']['max_bill_admin'] = 25;
    $update_vars['settings']['max_comm_admin'] = 50;
    $update_vars['settings']['max_rss_admin'] = 25;
    $update_vars['settings']['nb_list_linx'] = 50;
    $update_vars['settings']['nom_du_site'] = 'BlogoText';
    $update_vars['settings']['racine'] = '';
    $update_vars['settings']['require_email'] = 0;
    $update_vars['settings']['theme_choisi'] = 'default';

    $update_vars['mysql']['MYSQL_LOGIN'] = '';
    $update_vars['mysql']['MYSQL_PASS'] = '';
    $update_vars['mysql']['MYSQL_DB'] = '';
    $update_vars['mysql']['MYSQL_HOST'] = '';
    $update_vars['mysql']['DBMS'] = '';

    $update_vars['settings-advanced']['BLOG_UID'] = '';
    $update_vars['settings-advanced']['USE_IP_IN_SESSION'] = 1;

    $update_vars['user']['USER_LOGIN'] = '';
    $update_vars['user']['USER_PWHASH'] = '';

    $files = array(
            array(
                    'old' => BT_ROOT.'config/prefs.php',
                    'new' => BT_ROOT.'config/settings.php',
                    'type' => 'php array',
                    'family' => 'settings'
                ),
            array(
                    'old' => BT_ROOT.'config/mysql.ini',
                    'new' => BT_ROOT.'config/mysql.php',
                    'type' => 'ini',
                    'family' => 'mysql'
                ),
            array(
                    'old' => BT_ROOT.'config/config-advanced.ini',
                    'new' => BT_ROOT.'config/settings-advanced.php',
                    'type' => 'ini',
                    'family' => 'settings-advanced'
                ),
            array(
                    'old' => BT_ROOT.'config/user.ini',
                    'new' => BT_ROOT.'config/user.php',
                    'type' => 'ini',
                    'family' => 'user'
                ),
        );

    $errors = array();

    foreach ($files as $file) {
        $vars = array();
        $success = false;

        if (!is_file($file['old']) or !is_readable($file['old'])) {
            $errors[$file['family']][] = 'can\'t find old file';
            continue;
        }

        // proceed ini file
        if ($file['type'] == 'ini') {
            $options = parse_ini_file($file['old']);
            foreach ($options as $option => $value) {
                $vars[$option] = $value;
            }
        // proceed php array file
        } else {
            $save = $GLOBALS;
            $GLOBALS = array();
            include $file['old'];
            $vars = $GLOBALS;
            $GLOBALS = array();
            $GLOBALS = $save;
        }

        // merge vars
        $temp = array_merge($update_vars[$file['family']], $vars);
        // get needed vars
        $commons = array_intersect_key($temp, $update_vars[$file['family']]);

        // write new file
        if ($file['type'] == 'ini') {
            $success = update_3_4_7_file_write_conf_ini($file['new'], $commons);
        } else {
            $success = update_3_4_7_file_write_conf_php($file['new'], $commons);
        }

        var_dump($success);
        // exit();
        if ($success !== true) {
            $errors[$file['family']][] = 'fail to write new file';
            continue;
        }

        if (!@unlink($file['old'])) {
            $errors[$file['family']][] = 'fail to delete old file';
            continue;
        }
    }

    return (count($errors) === 0) ? true : $errors;
}

function update_3_4_7_import_ini_file($file_path) {
	if (is_file($file_path) and is_readable($file_path)) {
		$options = parse_ini_file($file_path);
		return $options;
	}
	return false;
}

function update_3_4_7_db_update()
{
    // import db
    $db_settings = update_3_4_7_import_ini_file(BT_ROOT.'config/mysql.php');

    // init db
    $db_handler = update_db_connect(
            array(
                    'login' => $db_settings['MYSQL_LOGIN'],
                    'password' => $db_settings['MYSQL_PASS'],
                    'type' => $db_settings['DBMS'],
                    'file' => BT_ROOT.'databases/database.sqlite',
                    'host' => $db_settings['MYSQL_HOST'],
                    'name' => $db_settings['MYSQL_DB'],
                )
        );

    $errors = array();

    // querys
    $querys = array(
        'links' => 'ALTER TABLE `links` DROP `bt_author`;',
        'articles' => 'ALTER TABLE `articles` CHANGE `bt_categories` `bt_tags` TEXT;',
        'rss' => 'ALTER TABLE `rss` ADD `bt_bookmarked` TINYINT AFTER `bt_statut`;'
    );

    // SQLite doesn't need this, but MySQL does.
    $auto_increment = ($db_settings['DBMS'] == 'mysql') ? 'AUTO_INCREMENT' : '';
    // MySQL needs a limit for indexes on TEXT fields.
    $index_limit_size = ($db_settings['DBMS'] == 'mysql') ? '(15)' : '';
    // MySQL doesn’t know this statement for INDEXES
    $if_not_exists = ($db_settings['DBMS'] == 'sqlite') ? 'IF NOT EXISTS' : '';

    // MySQL
    if ($db_settings['DBMS'] == 'mysql') {
        foreach ($querys as $key => $query) {
            $errors[$key] = $db_handler->query($query);
        }
        return (count($errors) === 0) ? true : $errors;
    }

    // sqLite
    // drop in links
    try {
        $db_handler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db_handler->beginTransaction();
        $db_handler->exec(
            'CREATE TEMPORARY TABLE links_backup
            (
                ID INTEGER PRIMARY KEY '.$auto_increment.',
                bt_type CHAR(20),
                bt_id BIGINT,
                bt_content TEXT,
                bt_wiki_content TEXT,
                bt_title TEXT,
                bt_tags TEXT,
                bt_link TEXT,
                bt_statut TINYINT
            )'
        );
        $db_handler->exec(
            'INSERT INTO links_backup 
                SELECT ID,bt_type,bt_id,bt_content,bt_wiki_content,bt_title,bt_tags,bt_link,bt_statut
                FROM links'
        );
        $db_handler->exec('DROP TABLE links');
        $db_handler->exec(
            'CREATE TABLE IF NOT EXISTS links
            (
                ID INTEGER PRIMARY KEY '.$auto_increment.',
                bt_type CHAR(20),
                bt_id BIGINT,
                bt_content TEXT,
                bt_wiki_content TEXT,
                bt_title TEXT,
                bt_tags TEXT,
                bt_link TEXT,
                bt_statut TINYINT
            ); CREATE INDEX '.$if_not_exists.' dateL ON links ( bt_id );'
        );
        $db_handler->exec(
            'INSERT INTO links 
                SELECT ID,bt_type,bt_id,bt_content,bt_wiki_content,bt_title,bt_tags,bt_link,bt_statut
                FROM links_backup'
        );
        $db_handler->exec('DROP TABLE links_backup');
        $db_handler->commit();
    } catch (Exception $e) {
        $db_handler->rollBack();
        $errors['links'] = $e->getMessage();
    }
    // change in articles
    try {
        $db_handler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db_handler->beginTransaction();
        $db_handler->exec(
            'CREATE TEMPORARY TABLE articles_backup
            (
                ID INTEGER PRIMARY KEY '.$auto_increment.',
                bt_type CHAR(20),
                bt_id BIGINT,
                bt_date BIGINT,
                bt_title TEXT,
                bt_abstract TEXT,
                bt_notes TEXT,
                bt_link TEXT,
                bt_content TEXT,
                bt_wiki_content TEXT,
                bt_tags TEXT,
                bt_keywords TEXT,
                bt_nb_comments INTEGER,
                bt_allow_comments TINYINT,
                bt_statut TINYINT
            );'
        );
        $db_handler->exec(
            'INSERT INTO articles_backup 
                SELECT ID,bt_type,bt_id,bt_date,bt_title,bt_abstract,
                    bt_notes,bt_link,bt_content,bt_wiki_content,bt_categories,
                    bt_keywords,bt_nb_comments,bt_allow_comments,bt_statut
                FROM articles'
        );
        $db_handler->exec('DROP TABLE articles');
        $db_handler->exec(
            'CREATE TABLE IF NOT EXISTS articles
            (
                ID INTEGER PRIMARY KEY '.$auto_increment.',
                bt_type CHAR(20),
                bt_id BIGINT,
                bt_date BIGINT,
                bt_title TEXT,
                bt_abstract TEXT,
                bt_notes TEXT,
                bt_link TEXT,
                bt_content TEXT,
                bt_wiki_content TEXT,
                bt_tags TEXT,
                bt_keywords TEXT,
                bt_nb_comments INTEGER,
                bt_allow_comments TINYINT,
                bt_statut TINYINT
            ); CREATE INDEX '.$if_not_exists.' dateidA ON articles ( bt_date, bt_id );'
        );
        $db_handler->exec(
                'INSERT INTO articles 
                    SELECT ID,bt_type,bt_id,bt_date,bt_title,bt_abstract,
                        bt_notes,bt_link,bt_content,bt_wiki_content,bt_tags,
                        bt_keywords,bt_nb_comments,bt_allow_comments,bt_statut
                    FROM articles_backup');
        $db_handler->exec('DROP TABLE articles_backup');
        $db_handler->commit();
    } catch (Exception $e) {
        $db_handler->rollBack();
        $errors['articles'] = $e->getMessage();
    }

    // add in rss
    $query = 'ALTER TABLE rss ADD COLUMN bt_bookmarked TINYINT';
    if (!($db_handler->exec($query))) {
        $errors['rss'] = 'Fail to update database > rss';
    }

    return (count($errors) === 0) ? true : $errors;
}

$update_proceed = function ()
{
    var_dump(__line__);
    $return = array(
        'success' => false,
        'messages' => array(),
        'errors' => array(),
    );
    $success = false;

    if (($errors = update_3_4_7_convert_config_files()) === true) {
        $return['messages'][] = 'Config files have been updated';
        $success = true;
    } else {
        $return['errors'][] = $errors;
    }

    if ($success === true) {
        if (($errors = update_3_4_7_db_update()) === true) {
            $return['messages'][] = 'Database have been updated';
            $success = true;
        } else {
            $return['errors'][] = $errors;
        }
    } else {
        $return['messages'][] = 'Can\'t work on database until config files are updated';
    }

    if ($success === true) {
        $return['messages'][] = 'Your password have been deleted';
        $return['success'] = true;
    }

    return $return;
};

