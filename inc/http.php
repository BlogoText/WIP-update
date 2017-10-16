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


function http_get_headers($url)
{
    $opts = array(
        'http' => array(
            'method' => 'HEAD',
            'user_agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:34.0) Gecko/20100101 Firefox/34.0',
            // 'Referer: '.$opt['referer'],
            'timeout' => 5,
        )
    );
    stream_context_create($opts);
    $headers = get_headers($url, 1);
    if (!is_array($headers)) {
        return false;
    }
    // convert array key to lower case
    return array_change_key_case($headers, CASE_LOWER);
}

/**
 * download a file/page and save it in a local file
 * use curl or fopen or file_get_contents
 *
 * @params $url string, the target URL
 * @params $target string, the path to save it
 * @params $opt array, useragent, timeout, referer ...
 * @return bool
 */
function download_to($url, $target, $opt = array())
{
    $success = false;
    $opt = array_merge(
        array(
            'useragent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:34.0) Gecko/20100101 Firefox/34.0',
            'timeout' => 10,
            'referer' => '',
        ),
        $opt
    );

    // Open the target file for writing
    if (false == ($local_file = @fopen($target, 'w'))) {
        return false;
    }

    // Use curl
    if (is_callable('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $opt['referer']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $opt['timeout']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FILE, $local_file);

        if (curl_exec($ch)) {
            $success = true;
            fclose($local_file);
        }
        curl_close($ch);

        if ($success === true) {
            return true;
        }
    }

    $opts = array(
        'http' => array(
            'method' => 'GET',
            'header' => array(
                'User-Agent: '.$opt['useragent'],
                'Referer: '.$opt['referer']
            )
        )
    );
    $context = stream_context_create($opts);

    // try fopen
    $remote = @fopen($url, 'r', false, $context);
    if (!$remote) {
        fclose($local_file);
    } else {
        $success = true;
    }

    if ($success === true) {
        while (!feof($remote)) {
            fwrite($local_file, fread($remote, 8192));
        }

        fclose($remote);
        fclose($local_file);
    }

    // last try
    $content = file_get_contents($url, false, $context);
    if (!$content) {
        return false;
    }

    return (false !== file_put_contents($target, $content, LOCK_EX));
}

/**
 * $feeds is an array of URLs: Array( [http://…], [http://…], …)
 * Returns the same array: Array([http://…] [[headers]=> 'string', [body]=> 'string'], …)
 */
function download_get($feeds, $timeout, $echo_progress = false)
{
    // uses chunks of 30 feeds because Curl has problems with too big (~150) "multi" requests.
    $chunks = array_chunk($feeds, 30, true);
    $results = array();
    $total_feed = count($feeds);

    if ($echo_progress === true) {
        echo '0/'.$total_feed.' ';
        ob_flush();
        flush(); // for Ajax
    }

    foreach ($chunks as $chunk) {
        set_time_limit(30);
        $curl_arr = array();
        $master = curl_multi_init();
        $total_feed_chunk = count($chunk)+count($results);

        // init each url
        foreach ($chunk as $i => $url) {
            $curl_arr[$url] = curl_init(trim($url));
            curl_setopt_array($curl_arr[$url], array(
                CURLOPT_RETURNTRANSFER => true, // force Curl to return data instead of displaying it
                CURLOPT_FOLLOWLOCATION => true, // follow 302 ans 301 redirects
                CURLOPT_CONNECTTIMEOUT => 100, // 0 = indefinately ; no connection-timeout (ruled out by "set_time_limit" hereabove)
                CURLOPT_TIMEOUT => $timeout, // downloading timeout
                CURLOPT_USERAGENT => BLOGOTEXT_UA, // User-agent (uses the UA of browser)
                CURLOPT_SSL_VERIFYPEER => false, // ignore SSL errors
                CURLOPT_SSL_VERIFYHOST => false, // ignore SSL errors
                CURLOPT_ENCODING => 'gzip', // take into account gziped pages
                //CURLOPT_VERBOSE => 1,
                CURLOPT_HEADER => 1, // also return header
            ));
            curl_multi_add_handle($master, $curl_arr[$url]);
        }

        // exec connexions
        $running = $oldrunning = 0;

        do {
            curl_multi_exec($master, $running);

            if ($echo_progress === true) {
                // echoes the nb of feeds remaining
                echo ($total_feed_chunk-$running).'/'.$total_feed.' ';
                ob_flush();
                flush();
            }
            usleep(100000);
        } while ($running > 0);

        // multi select contents
        foreach ($chunk as $i => $url) {
            $response = curl_multi_getcontent($curl_arr[$url]);
            $header_size = curl_getinfo($curl_arr[$url], CURLINFO_HEADER_SIZE);
            if (empty($response) && $header_size === 0) {
                $results[$url] = false;
            } else {
                $results[$url]['headers'] = http_parse_headers(mb_strtolower(substr($response, 0, $header_size)));
                $results[$url]['body'] = substr($response, $header_size);
            }
        }
        // Ferme les gestionnaires
        curl_multi_close($master);
    }
    return $results;
}

if (!function_exists('http_parse_headers')) {
    function http_parse_headers($raw_headers)
    {
        $headers = array();
        $array_headers = ((is_array($raw_headers)) ? $raw_headers : explode("\n", $raw_headers));

        foreach ($array_headers as $i => $h) {
            $h = explode(':', $h, 2);
            if (isset($h[1])) {
                $headers[$h[0]] = trim($h[1]);
            }
        }
        return $headers;
    }
}
