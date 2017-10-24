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
 * check if string is utf8
 *
 * @params $string string, the string to test
 * @return bool
 */
function is_utf8($string)
{
    return (bool)mb_detect_encoding($string, 'UTF-8', true);
}

/**
 * get content infos,
 * can be used in addons
 *
 * @return array
 */
function content_infos_get()
{
    if (!isset($GLOBALS['content_infos'])) {
        content_infos_init();
    }
    return $GLOBALS['content_infos'];
}

function content_infos_init()
{
    $GLOBALS['content_infos'] = array(
        'type' => '',     // (string) content type : link, blog ...
        'list' => false,  // (bool) list of multiple content ?
        'id' => '',       // (mixed) (int) or (array) id, or ids of content
        'format' => '',   // (string) final exit format : html, xml, json
        'http' => '',     // (int) final http code : 200, 404
    );
}

function content_infos_set($key, $infos)
{
    if (!isset($GLOBALS['content_infos'])) {
        content_infos_init();
    }
    $GLOBALS['content_infos'][$key] = $infos;
    return true;
}

/**
 * define and put into $GLOBALS the VHOST settings
 *
 * @return bool
 */
function settings_vhost_define()
{
    $settings = settings_vhost_get();
    if (!$settings) {
        return false;
    }
    $to_define = array(
        'SITE_UID',
        'AUTH_USE_IP','AUTH_COOKIE','AUTH_SESSION_TTL',
        'TOKEN_TTL',
        'DBMS','MYSQL_LOGIN','MYSQL_PASS','MYSQL_DB','MYSQL_HOST',
        'MYSQL_PORT', 'DB_CHAR4B', 'DB_CHARSET'
    );

    foreach ($settings as $option => $value) {
        if (in_array($option, $to_define)) {
            if (!defined($option)) {
                define($option, $value);
            } else {
                die($option .' : already defined ... settings_vhost_get()');
            }
        } else {
            $GLOBALS[$option] = $value;
        }
    }

    return true;
}

/**
 * get settings for a vhost
 *
 * @params $define bool, define or return array
 * @return array|false
 */
function settings_vhost_get($define = true)
{
    if (!is_file(FILE_VHOST_SETTINGS)) {
        return false;
    }

    $settings = parse_ini_file(FILE_VHOST_SETTINGS);
    if (!$settings) {
        return false;
    }

    return $settings;
}

/**
 *
 */
function label($for, $txt)
{
    return '<label for="'.$for.'">'.$txt.'</label>'."\n";
}

/**
 * Store a PHP array into a file
 * almost like file_put_content, but with only 2 params and support
 * for multidimensional array
 *
 * @params string $filename, Path to the file where to write the array.
 * @params array $data, The array to write
 * @return bool
 */
function file_put_array($filename, $data)
{
    $data = '<?php /* '.chunk_split(base64_encode(serialize($data)), 76, "\n")."*/\n";
    return (file_put_contents($filename, $data, LOCK_EX) !== false);
}

/**
 * Retrieve serialized data used by file_put_array()
 * like file_get_content
 *
 * to do : add tests.
 *
 * @params string $filename, Path to the file where to get the array.
 * @return false||array
 */
function file_get_array($filename)
{
    if (!is_file($filename)) {
        return array();
    }
    return unserialize(base64_decode(substr(file_get_contents($filename), strlen('<?php /* '), -strlen('*/'))));
}

/**
 * Redirect to another URL, the right way.
 */
function redirection($url)
{
    // Prevent use hook on admin side
    if (!IS_IN_ADMIN) {
        $tmp_hook = hook_trigger_and_check('before_redirection', $url);
        if ($tmp_hook !== false) {
            $url = $tmp_hook['1'];
        }
    }

    exit(header('Location: '.$url));
}

/**
 * Remove the current (.) and parent (..) folders from the list of files returned by scandir().
 */
function rm_dots_dir($array)
{
    return array_diff($array, array('.', '..'));
}

/**
 * remove slashes if necessary
 */
function clean_txt($text)
{
    return (!get_magic_quotes_gpc()) ? trim($text) : trim(stripslashes($text));
}

/**
 * protect a string
 * (stripslashes > trim > htmlspecialchars)
 */
function protect($text)
{
    return htmlspecialchars(clean_txt($text));
}

// useless ?
function lang_set_list()
{
    $GLOBALS['langs'] = array('fr' => 'Français', 'en' => 'English');
}

/**
 * load lang
 *
 * $admin bool lang for admin side ?
 */
function lang_load_land()
{
    if (empty($GLOBALS['site_lang'])) {
        $GLOBALS['site_lang'] = '';
    }

    $file = (IS_IN_ADMIN) ? 'admin' : 'public';

    switch ($GLOBALS['site_lang']) {
        case 'en':
            $path = 'en_en';
            break;
        case 'fr':
        default:
            $path = 'fr_fr';
    }
    require_once BT_ROOT.'inc/lang/'.$path.'/'.$file.'.php';
}

/**
 * decode id
 *
 * @params $id int, the bt_id of an article
 * @return array
 */
function decode_id($id)
{
    return array(
        'year' => substr($id, 0, 4),
        'month' => substr($id, 4, 2),
        'day' => substr($id, 6, 2),
        'hour' => substr($id, 8, 2),
        'minutes' => substr($id, 10, 2),
        'seconds' => substr($id, 12, 2)
    );
}

/**
 * get 'url' (?id=) for an article
 *
 * @params $id int, the bt_id of an article
 * @params $title string
 * @return string
 */
function get_blogpath($id, $title)
{
    return URL_ROOT.'?d='.implode('/', decode_id($id)).'-'.title_url($title);
}

/**
 *
 */
function article_anchor($id)
{
    return 'id'.substr(md5($id), 0, 6);
}

/**
 * list all tags used by content type
 *
 * @params $table string, the db table to look (article, link...)
 * @params $status ?, the status of the content
 * @return array
 */
function tags_list_all($table, $statut)
{
    try {
        $sql = 'SELECT bt_tags FROM '.$table;
        if ($statut !== false) {
            $sql .= ' WHERE bt_statut = '.$statut;
        }
        $res = $GLOBALS['db_handle']->query($sql);
        $tags_list = '';
        // add tag to string tag1,tag2,tag3 ...
        while ($entry = $res->fetch()) {
            if (trim($entry['bt_tags']) != '') {
                $tags_list .= $entry['bt_tags'].',';
            }
        }
        $res->closeCursor();
        $tags_list = rtrim($tags_list, ',');
    } catch (Exception $e) {
        die('Erreur 4354768 : '.$e->getMessage());
    }

    $tags_list = str_replace(array(', ', ' ,'), ',', $tags_list);
    $tab_tags = explode(',', $tags_list);
    sort($tab_tags);
    unset($tab_tags['']);
    return array_count_values($tab_tags);
}

/**
 * order and cleanup a string list, separator is ","
 *
 * @params $tags string, ex : 'ships, blog, news'
 * @return string, ex : 'blog, ships, news'
 */
function tags_sort($tags)
{
    $tags_array = explode(',', trim($tags, ','));
    $tags_array = array_unique(array_map('trim', $tags_array));
    sort($tags_array);
    return implode(', ', $tags_array);
}

/**
 * sort an array by a subkey
 *
 * @params $array array, the array to sort
 * @params $subkey string, the subkey
 * @return array
 */
function sort_by_subkey($array, $subkey)
{
    foreach ($array as $key => $item) {
        $subkeyz[$key] = $item[$subkey];
    }
    if (isset($subkeyz)) {
        array_multisort($subkeyz, SORT_DESC, $array);
    }
    return $array;
}

/**
 * basic check on datas from form submition
 *
 * @params $default array, the default datas
 * @params $posted array, the datas from form
 * @return array
 *           ['values'][$data_key] = the submitted data
 *           ['errors'][$data_key] = the error type or not isset(no error)
 */
function form_basic_check($default, $posted)
{
    $return = array();

    foreach ($default as $key => $val) {
        if (is_bool($val)) {
            $return['values'][$key] = (bool)(isset($posted[$key]));
        } else {
            if (!isset($posted[$key])) {
                $return['values'][$key] = $val;
                $return['errors'][$key] = 'missing';
            } else if (empty($posted[$key])) {
                $return['values'][$key] = $val;
                $return['errors'][$key] = 'empty';
            } else {
                $return['values'][$key] = $posted[$key];
            }
        }
    }

    return $return;
}

/**
 * Code from Shaarli. Generate an unique sess_id, usable only once.
 * token have a 14400 sec (4 hours) TTL
 *
 * @params $action string, optional, token action
 * @return string
 */
function token_set($action = '')
{
    // a pseudo random string
    $token = sha1(uniqid('', true).mt_rand());

    // Store it on the server side
    $_SESSION['tokens'][$token] = time();

    return $token;
}

function token_boot()
{
    // token submitted ?
    if (isset($_SERVER['X-CSRFToken'])) {
        $token = filter_input(INPUT_SERVER, 'X-CSRFToken', FILTER_SANITIZE_SPECIAL_CHARS);
    } else if (isset($_REQUEST['token'])) {
        $token = htmlentities($_REQUEST['token'], ENT_QUOTES);
    } else {
        define('TOKEN_CHECK', false);
        return false;
    }

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // remove expired
    token_cleaner();

    // token exists
    if (isset($_SESSION['tokens'][$token])) {
        define('TOKEN_CHECK', true);
        unset($_SESSION['tokens'][$token]);
        return true;
    }

    // token fail
    if (empty($token)) {
        define('TOKEN_CHECK', 'empty');
        log_error('[security] Request with token, but no token provided');
    } else if (!isset($_SESSION['tokens'][$token])) {
        define('TOKEN_CHECK', 'unregistered');
        log_error('[security] Request with token, but token unregistered');
    }

    // empty token
    if (!defined('TOKEN_CHECK')) {
        define('TOKEN_CHECK', 'missing');
    }

    return false;
}


/**
 * remove old token stored in session
 */
function token_cleaner()
{
    // to do, add TOKEN_TTL exception when install
    if (!isset($_SESSION['tokens']) || !defined('TOKEN_TTL')) {
        return true;
    }
    $ttl = time() - TOKEN_TTL;
    foreach ($_SESSION['tokens'] as $token => $t) {
        if ($ttl > $t) {
            unset($_SESSION['tokens'][$token]);
        }
    }
    return true;
}

/**
 * search query parsing (operators, exact matching, etc)
 */
function search_engine_parse_query($q)
{
    if (preg_match('#^\s?"[^"]*"\s?$#', $q)) { // exact match
        $array_q = array('%'.str_replace('"', '', $q).'%');
    } else { // multiple words matchs
        $array_q = explode(' ', trim($q));
        foreach ($array_q as $i => $entry) {
            $array_q[$i] = '%'.$entry.'%';
        }
    }
    // uniq + reindex
    return array_values(array_unique($array_q));
}

/**
 * return http header
 * the function may be not exist on some server ...
 * http://php.net/manual/fr/function.http-response-code.php
 */
if (!function_exists('http_response_code')) {
    function http_response_code($code = null)
    {

        if ($code !== null) {
            return (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
        }

        switch ($code) {
            case 100:
                $text = 'Continue';
                break;
            case 101:
                $text = 'Switching Protocols';
                break;
            case 200:
                $text = 'OK';
                break;
            case 201:
                $text = 'Created';
                break;
            case 202:
                $text = 'Accepted';
                break;
            case 203:
                $text = 'Non-Authoritative Information';
                break;
            case 204:
                $text = 'No Content';
                break;
            case 205:
                $text = 'Reset Content';
                break;
            case 206:
                $text = 'Partial Content';
                break;
            case 300:
                $text = 'Multiple Choices';
                break;
            case 301:
                $text = 'Moved Permanently';
                break;
            case 302:
                $text = 'Moved Temporarily';
                break;
            case 303:
                $text = 'See Other';
                break;
            case 304:
                $text = 'Not Modified';
                break;
            case 305:
                $text = 'Use Proxy';
                break;
            case 400:
                $text = 'Bad Request';
                break;
            case 401:
                $text = 'Unauthorized';
                break;
            case 402:
                $text = 'Payment Required';
                break;
            case 403:
                $text = 'Forbidden';
                break;
            case 404:
                $text = 'Not Found';
                break;
            case 405:
                $text = 'Method Not Allowed';
                break;
            case 406:
                $text = 'Not Acceptable';
                break;
            case 407:
                $text = 'Proxy Authentication Required';
                break;
            case 408:
                $text = 'Request Time-out';
                break;
            case 409:
                $text = 'Conflict';
                break;
            case 410:
                $text = 'Gone';
                break;
            case 411:
                $text = 'Length Required';
                break;
            case 412:
                $text = 'Precondition Failed';
                break;
            case 413:
                $text = 'Request Entity Too Large';
                break;
            case 414:
                $text = 'Request-URI Too Large';
                break;
            case 415:
                $text = 'Unsupported Media Type';
                break;
            case 500:
                $text = 'Internal Server Error';
                break;
            case 501:
                $text = 'Not Implemented';
                break;
            case 502:
                $text = 'Bad Gateway';
                break;
            case 503:
                $text = 'Service Unavailable';
                break;
            case 504:
                $text = 'Gateway Time-out';
                break;
            case 505:
                $text = 'HTTP Version not supported';
                break;
            default:
                $code = 200;
                $text = 'OK';
                break;
        }

        $protocol = ((isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header($protocol .' '. $code .' '. $text);
        $GLOBALS['http_response_code'] = $code;

        return $code;
    }
}

/**
 * like error_log($message) but with addionals informations
 * push in $GLOBALS['errors']
 * can be used to only push in $GLOBALS['errors']
 *
 * @param string $message
 * @param bool $write, write in log file
 */
function log_error($message)
{
    // folder_create(DIR_LOG, true, true);
    $logFile = DIR_LOG.'log-'.date('Ymd').'.log';
    $trace = debug_backtrace();
    $trace = (end($trace));

    $where = str_replace(BT_ROOT, '', $trace['file']);
    $log = sprintf(
        '[v%s, %s] %s %s at [%s:%d]',
        BLOGOTEXT_VERSION,
        date('H:i:s'),
        $message,
        (!empty($trace['function'])) ? 'in '.$trace['function'].'()' : '',
        $where,
        $trace['line']
    );

    if (DEBUG) {
        $spaces = '  ';
        ob_start();
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $stack = ob_get_contents();
        ob_end_clean();

        // Remove the first item from backtrace as it's redundant.
        $stack = explode("\n", trim($stack));
        array_shift($stack);
        $stack = array_reverse($stack);
        $stack = implode("\n", $stack);

        // Remove numbers, not interesting
        $stack = preg_replace('/#\d+\s+/', $spaces.$spaces.'-> ', $stack);

        // Anon paths (cleaner and smaller paths)
        $stack = str_replace(BT_ROOT, '', $stack);

        $log .= "\n".$spaces.'Stack trace:'."\n".$stack;
    }

    if (is_dir(DIR_LOG)) {
        error_log(addslashes($log)."\n", 3, $logFile);
    } else {
        error_log(addslashes($log)."\n", 0);
    }
}

/**
 * in case of intl not supported
 * ! no compatible with real idn_to_ascii()
 */
if (!PHP_INTL) {
    function idn_to_ascii($string)
    {
        // œ => oe ; æ => ae
        $sanitized = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
        $sanitized = htmlentities($sanitized, ENT_QUOTES, 'UTF-8'); // é => &eacute;
         // &eacute => e ...
        $sanitized = preg_replace('#&(.)(acute|grave|circ|uml|cedil|tilde|ring|slash|caron);#', '$1', $sanitized);
        $sanitized = preg_replace('#&([a-z]{2})lig;#i', '$1', $sanitized);
        $sanitized = preg_replace("/[^a-z0-9-_\.\~]/", '', $sanitized);
        if (empty(preg_replace("/[^a-z0-9]/", '', $sanitized))) {
            $sanitized = substr(md5($string), 0, 12);
        } else if ($string != $sanitized) {
            $sanitized .= '-'.substr(md5($string), 0, 6);
        }
        return $sanitized;
    }
}

/**
 * @param string $http_host, like : example.tld || https://toto.example.tld/blog1/
 * @return string||array
 *           array, see array['message'] for more informations
 *          string, safe potential file or folder name like : example-tld || toto-example-tld-blog1
 */
function secure_host_to_path($http_host)
{
    if (empty($http_host)) {
        return array(
            'success' => false,
            'message' => 'Your HTTP HOST seem\'s to be empty oO'
        );
    }

    // at least a.be
    if (strlen($http_host) < 3) {
        return array(
            'success' => false,
            'message' => 'Your HTTP HOST is not valid'
        );
    }

    $http_host = htmlspecialchars($http_host, ENT_QUOTES);

    // add 'http://' for a valid parse_url
    if (strpos($http_host, '://') === false) {
        $http_host = 'http://'. $http_host;
    }

    $exploded = parse_url($http_host);

    if (empty($exploded['path'])) {
        $exploded['path'] = '';
    }

    // work on path
    if (!empty($exploded['path'])) {
        $tmp = explode('/', trim(trim($exploded['path']), '/'));
        // url point to a PHP file ?
        if (strpos($exploded['path'], '.php') !== false) {
            array_pop($tmp);
        }
        // admin or install URL ?
        if (IS_IN_ADMIN || BT_RUN_INSTALL) {
            array_pop($tmp);
        }
        $exploded['path'] = implode('-', $tmp);
    }

    $exploded = array_map('idn_to_ascii', $exploded);

    $path = $exploded['host'].$exploded['path'];

    // format, clean up, secure
    $path = trim(strtolower($path));
    $path = preg_replace("/[^a-z0-9-_\.\~]/", '-', $path);
    // clean first and last char when -
    $path = trim($path, '-');
    // clean first and last char when . (prevent toto.onion./addons)
    $path = trim($path, '.');

    // empty or
    if (empty($path) || strlen($path) < 3) {
        return array(
            'success' => false,
            'message' => 'Your HTTP HOST haven\'t survive our HTTP_HOST security test !'
        );
    }

    return $path;
}

/**
 * a little security
 * try to prevent hack by reserved constant (threw addon ...)
 */
function secur_constant()
{
    $reserved = array('BT_ROOT_ADMIN', 'IS_IN_ADMIN', 'BT_RUN_INSTALL', 'BT_RUN_LOGIN', 'BT_RUN_CRON');
    foreach ($reserved as $const) {
        if (!defined($const)) {
            define($const, false);
        }
    }
}
