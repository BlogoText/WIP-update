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

// dependancies
require_once BT_ROOT.'inc/http.php';

/**
 * download a release
 *
 * @params string $url, the url of the release
 * @params string $target, the path (with filename)
 * @return bool
 */
function update_core_dwn_release($url, $target)
{
    $opts = array(
        'useragent' => 'PHP'
    );

    return download_to($url, $target, $opts);
}

/**
 * get the list of releases (github)
 *
 * @params bool $cached, use cache ?
 * @return false|array
 */
function update_core_get_releases_infos($cached = false)
{
    $cache_file = DIR_CACHE.'BT-github-releases.cache.php';

    // check cache
    if ($cached && file_exists($cache_file) && filemtime($cache_file) > time()-(60 * 60 * 6)) {
        $content = file_get_contents($cache_file);
        if ($content === false) {
            unset($content);
        }
    }

    // no cache, go source
    if (!isset($content)) {
        $opts = array(
            'http' => array(
                'method' => 'GET',
                'header' => array(
                    'User-Agent: PHP'
                )
            )
        );
        $context = stream_context_create($opts);
        // $content = file_get_contents('https://api.github.com/repos/BlogoText/WIP-update/releases', false, $context);
        $content = file_get_contents('https://api.github.com/repos/BlogoText/blogotext/releases', false, $context);
        if (!$content) {
            return false;
        }
    }

    // check JSON response
    $contents = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($contents)) {
        return false;
    }

    // sort by tag name
    $releases = array();
    foreach ($contents as $release) {
        $releases[$release['tag_name']] = $release;
    }
    ksort($releases);
    

    // put in cache
    if (folder_create(DIR_CACHE, false, true)) {
        file_put_contents($cache_file, json_encode($releases));
    }

    return $releases;
}

/**
 * get the last release infos
 *
 * @params bool $cached, use cache ?
 * @return false|array
 */
function update_get_last_release_infos($cached = true)
{
    $releases = update_core_get_releases_infos($cached);
    if (!$releases) {
        return false;
    }
    return array_pop($releases);
}

function update_is_available($cached = true)
{
    $release = update_get_last_release_infos($cached);
    if (!$release) {
        return null;
    }
    return (version_compare($release['tag_name'], BLOGOTEXT_VERSION) >= 0);
}


/**
 * get infos about the version next to the used one
 *
 * @params string $version, the used version
 * @return bool|array,
 *               false : fail to fetch datas
 *               true : the $version is the lastest
 *               array : datas of the next version
 */
function update_get_next_release_infos($version = BLOGOTEXT_VERSION)
{
    $releases = update_core_get_releases_infos();
    $keys = array_keys($releases);
    $keyIndexes = array_flip($keys);

    // WTF ?
    if (!isset($keyIndexes[$version])) {
        return false;
    }
    if (isset($keys[$keyIndexes[$version]+1])) {
        return $keys[$keyIndexes[$version]+1];
    }

    return true;
}

/**
 * count the number of release between a $version and the last
 *
 * @params string $version, the used version
 * @return false|int
 */
function update_count_release_diff($version = BLOGOTEXT_VERSION)
{
    $releases = update_core_get_releases_infos();
    if (!$releases) {
        return false;
    }
    $keys = array_keys($releases);
    $ct = count($releases);
    foreach ($keys as $k) {
        if (version_compare($k, $version) <= 0) {
            --$ct;
        }
    }

    return $ct;
}

/**
 * replace the files, folders from a unziped release to the BT installed
 *
 * @params string $source, the source (the unzipped folder)
 * @params string $target
 * @params bool $log, log errors ? (can be chatty)
 * @return bool
 */
function update_core_files($source, $target = BT_ROOT, $log = false)
{
    $scanned = rm_dots_dir(scandir($source));
    $fail = 0;
    foreach ($scanned as $item) {
        // check existing folders
        if (is_dir($source.$item) && is_dir(BT_ROOT.$item)) {
            $fail += update_core_files($source.$item.'/', BT_ROOT.$item);
        } else if (@rename($source.$item, BT_ROOT.$item) !== true) {
            ++$fail;
            if ($log === true) {
                log_error('Fail to update '.$item);
            }
        }
    }
    return ($fail === 0);
}


/**
 * cleanup after installation
 */
function update_cleanup()
{
    // to do
}
