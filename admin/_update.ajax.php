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

exit();



/**
 * Complete the process, even if the client stops it
 * (cron : wget --spider ...)
 */
ignore_user_abort(true);
// set at 30 minutes, but maybe need some adjustments
set_time_limit(1800);

require_once 'inc/boot.php';
require_once BT_ROOT_ADMIN.'/inc/update.php';

// get infos
$version_infos = update_get_last_release_infos();
if (!is_array($version_infos)) {
    echo 'Fail to get update informations<br />',"\r\n";
    exit();
}

$update_temp_dir = DIR_TEMP.$version_infos['tag_name'].'/';
$zip_file = $update_temp_dir.'release.zip';
$unzip_dir = $update_temp_dir.'release/';

$upd_paths = array(
    // the .zip file
    'release_zip' => DIR_TEMP.$version_infos['tag_name'].'.zip',
    // the folder of unzip file
    'release_zip_dir' => DIR_TEMP.$version_infos['tag_name'].'/',
    // the path of BT inside the folder of unzip file (github ...)
    'release_dir' => '',
);

// check if update available
if (!update_is_available()) {
    echo 'No update available<br />',"\r\n";
    exit();
}

// download the last version_compare
echo 'Downloading the latest version from '.$version_infos['zipball_url'].'<br />',"\r\n";
if (!folder_create(DIR_TEMP, true, true)) {
    echo 'Fail to create temp dir !<br />',"\r\n";
}
if (update_core_dwn_release($version_infos['zipball_url'], $upd_paths['release_zip'])) {
    echo 'Downloaded !<br />',"\r\n";
    // print_r($http_response_header);
} else {
    echo 'Fail to download the update<br />',"\r\n";
    exit();
}

echo 'Unzipping release<br />',"\r\n";
$zip = new ZipArchive;
if ($zip->open($upd_paths['release_zip']) !== true) {
    echo 'Fail to unzip release archive<br />',"\r\n";
    exit();
}

$zip->extractTo($upd_paths['release_zip_dir']);
$zip->close();

// search for the dir
$scanned = rm_dots_dir(scandir($upd_paths['release_zip_dir']));
if (count($scanned) !== 1) {
    echo 'The release seem\'s not valid<br />',"\r\n";
    exit();
}
$upd_paths['release_dir'] = $upd_paths['release_zip_dir'].'/'.array_values($scanned)[0].'/';
echo 'Done !<br />',"\r\n";


// put BlogoText in maintenance mode


// create backup


// remove folder on major and minor update
// to do : add test if new release is '-dev' or x.x change (not x.x.X)
$folder_to_remove = array('admin', 'inc');
foreach ($folder_to_remove as $fold) {
    
}


// overwrite files & folders
echo 'Overwrite BlogoText<br />',"\r\n";
if (!update_core_files($upd_paths['release_dir'], BT_ROOT)) {
    echo 'Fail !';
    exit();
}

// check for the updater file


// apply update(s) file(s)


// done !
echo 'Done !';
