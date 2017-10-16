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
 * remove 4 byte char in a string (thanks to db with no support for utf8mb4)
 * you can disable this function by adding 'define('UTF8MB4', true)' in your settings file
 * based on http://fluxbb.fr/aide/doku.php?id=mysql_et_utf8_quatre_octets
 *
 * @params $string string
 * @return string
 */
function db_charset_4b_remove($var)
{
    if (defined('UTF8MB4') && UTF8MB4 === true) {
        return $var;
    }
    if (!is_string($var)) {
        return $var;
    }

    return preg_replace(
        '%(?:
              \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}     # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16
          )%xs',
        '?',
        $var
    );
}

/**
 * the same as db_charset_4b_remove() but with arry support
 *
 * @params $var mixed, a string or an array or ...
 * @return mixed
 */
function pdo_mb4_cleaner($var)
{
    // if (defined('UTF8MB4') && UTF8MB4 === true) {
        // return $var;
    // }
    if (is_string($var)) {
        $var = db_charset_4b_remove($var);
    } else if (is_array($var)) {
        foreach ($var as $k => $data) {
            $var[$k] = db_charset_4b_remove($data);
        }
    }

    return $var;
}

/**
 * connect to database
 * if there is NO argument to this function, function will uses constants
 *
 * @params $type string, 'mysql' or 'sqlite'
 * @params $host string
 * @params $port int
 * @params $name string
 * @params $login string
 * @params $pass string
 * @return mixed or die()
 */
function db_connect()
{

    if (!defined('DBMS')) {
        exit('Undefine SQL settings.');
    }

    if (isset($GLOBALS['db_handle'])) {
        return $GLOBALS['db_handle'];
    }

    switch (DBMS) {
        case 'sqlite':
            if (!BT_RUN_INSTALL && !is_file(FILE_VHOST_DB)) {
                die('Database file doesn\'t exists !');
            }
            // open tables
            try {
                $db_handle = new PDO('sqlite:'.FILE_VHOST_DB, 'charset=UTF-8');
                $db_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $db_handle->query('PRAGMA temp_store=MEMORY; PRAGMA synchronous=OFF; PRAGMA journal_mode=WAL;');
            } catch (Exception $e) {
                if (BT_RUN_INSTALL) {
                    return $e->getMessage();
                } else {
                    die('Error sqlite: '.$e->getMessage());
                }
            }
            break;

        case 'mysql':
            try {
                $db_handle = new PDO('mysql:host='.MYSQL_HOST.';port='.MYSQL_PORT.';dbname='.MYSQL_DB.";charset=utf8;", MYSQL_LOGIN, MYSQL_PASS);
                $db_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $db_handle->query('SET sql_mode="PIPES_AS_CONCAT"');
            } catch (Exception $e) {
                if (BT_RUN_INSTALL) {
                    return $e->getMessage();
                } else {
                    die('Error mysql: '.$e->getMessage());
                }
            }
            break;
    }

    $db_handle->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    return $db_handle;
}

/**
 * lists articles with search criterias given in $array. Returns an array containing the data
 *
 * @params $query string, the db query
 * @params $array array, search criterias
 * @params $data_type string, search in specific content type
 * @return array
 */
function db_items_list($query, $array, $data_type)
{
    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute($array);
        $return = array();

        switch ($data_type) {
            case 'articles':
                while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
                    $return[] = init_list_articles($row);
                }
                break;
            case 'commentaires':
                while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
                    $return[] = init_list_comments($row);
                }
                break;
            case 'links':
            case 'rss':
                while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
                    $return[] = $row;
                }
                break;
            default:
                break;
        }

        // prevent use hook on admin side
        if (!IS_IN_ADMIN) {
            $tmp_hook = hook_trigger_and_check('list_items', $return, $data_type);
            if ($tmp_hook !== false) {
                $return = $tmp_hook['1'];
            }
        }

        return $return;
    } catch (Exception $e) {
        die('Erreur 89208 : '.$e->getMessage());
    }
}

/**
 * pretty much the same thing than db_items_list(),
 * but return the amount of entries
 *
 * $query must have a `count(key) as counter`
 *
 * @params $query string
 * @params $array array, search criterias
 * @return array
 */
function db_items_list_count($query, $array)
{
    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute($array);
        $result = $req->fetch();
        return $result['counter'];
    } catch (Exception $e) {
        die('Erreur 0003: '.$e->getMessage());
    }
}

// returns or prints an entry of some element of some table (very basic)
function get_entry($table, $entry, $id)
{
    $query = 'SELECT '.$entry.' FROM '.$table.' WHERE bt_id=?';
    try {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute(array($id));
        $result = $req->fetch();
    } catch (Exception $e) {
        die('Error : '.$e->getMessage());
    }

    if (!empty($result[$entry])) {
        return $result[$entry];
    }
    return '';
}

// from an array given by SQLite's requests, this function adds some more stuf to data stored by DB.
function init_list_articles($article)
{
    if ($article) {
        $dec_id = decode_id($article['bt_id']);
        $article = array_merge($article, decode_id($article['bt_date']));
        $article['bt_link'] = URL_ROOT.'?d='.$dec_id['year'].'/'.$dec_id['month'].'/'.$dec_id['day'].'/'.$dec_id['hour'].'/'.$dec_id['minutes'].'/'.$dec_id['seconds'].'-'.title_url($article['bt_title']);
    }
    return $article;
}

function init_list_comments($comment)
{
    $comment['author_lien'] = (!empty($comment['bt_webpage'])) ? '<a href="'.$comment['bt_webpage'].'" class="webpage">'.$comment['bt_author'].'</a>' : $comment['bt_author'] ;
    $comment['anchor'] = article_anchor($comment['bt_id']);
    $comment['bt_link'] = get_blogpath($comment['bt_article_id'], $comment['bt_title']).'#'.$comment['anchor'];
    $comment = array_merge($comment, decode_id($comment['bt_id']));
    return $comment;
}
