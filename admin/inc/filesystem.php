<?php
# *** LICENSE ***
# This file is part of BlogoText.
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
 * Note :
 * About the storage of the datas related to the user files (db), all the datas
 * are stored in a text file, for performance issue (faster than a SQL DB), and
 * because the user files are managed independently of other contents.
 * The contents of the storage file use code from Shaarli (by Sebsauvage)
 *    base64_encode (serialize ($ array))
 */


/**
 * returns a size in bytes.
 *
 * @params $val string, the "readable" size, like "3Mo"
 * @return int
 */
function return_bytes($val)
{
    $val = trim($val);
    $prefix = strtolower($val[strlen($val)-1]);
    $val = substr($val, 0, -1);
    switch ($prefix) {
        case 'g':
            $val *= 1024*1024*1024;
            break;
        case 'm':
            $val *= 1024*1024;
            break;
        case 'k':
            $val *= 1024;
            break;
    }
    return $val;
}

/**
 * gère le filtre de recherche sur les images : recherche par chaine (GET[q]), par type, par statut ou par date.
 * pour le moment, il n’est utilisé que du côté Admin (pas de tests sur les statut, date, etc.).
 */
function liste_base_files($sort_by, $motif, $limit)
{
    $return = array();

    switch ($sort_by) {
        case 'statut':
            foreach ($GLOBALS['files_list'] as $id => $file) {
                if ($file['bt_statut'] == $motif) {
                    $return[$id] = $file;
                }
            }
            break;

        case 'date':
            foreach ($GLOBALS['files_list'] as $id => $file) {
                if (($pos = strpos($file['bt_id'], $motif)) !== false and $pos == 0) {
                    $return[$id] = $file;
                }
            }
            break;

        case 'type':
            foreach ($GLOBALS['files_list'] as $id => $file) {
                if ($file['bt_type'] == $motif) {
                    $return[$id] = $file;
                }
            }
            break;

        case 'extension':
            foreach ($GLOBALS['files_list'] as $id => $file) {
                if (($file['bt_fileext'] == $motif)) {
                    $return[$id] = $file;
                }
            }
            break;

        case 'dossier':
            foreach ($GLOBALS['files_list'] as $id => $file) {
                if (in_array($motif, explode(',', $file['bt_dossier']))) {
                    $return[$id] = $file;
                }
            }
            break;

        case 'recherche':
            $GLOBALS['files_list'] = file_get_array(FILE_VHOST_FILES_DB);
            foreach ($GLOBALS['files_list'] as $id => $file) {
                if (strpos($file['bt_content'].' '.$file['bt_filename'], $motif)) {
                    $return[$id] = $file;
                }
            }
            break;

        default:
            $return = $GLOBALS['files_list'];
    }

    if (isset($limit) and is_numeric($limit) and $limit > 0) {
        $return = array_slice($return, 0, $limit);
    }

    return $return;
}

/**
 * get a uniq filename
 *
 * @params $folder string, the target folder
 * @params $filename string, the current filename
 * @return string
 *     a new filename like $filename_[0-9].ext
 *       or the submitted filename if no file exists
 */
function file_uniq_filename($folder, $filename)
{
    // no file with same name in this folder
    if (!file_exists($folder.$filename)) {
        return $filename;
    }

    $t_filename = pathinfo($filename, PATHINFO_FILENAME);
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $i = 1;

    while(file_exists($folder.$t_filename.'_'.$i.'.'.$extension)) {
        $i++;
    }

    return $t_filename.'_'.$i.'.'.$extension;
}

function file_handler_add($file, $method, $datas)
{
    // check ext allowed
    if (!file_is_allowed($file['bt_filename'])) {
        return 'file type not allowed';
    }

    $folder = ($file['bt_type'] == 'image') ? DIR_IMAGES.$file['bt_path'].'/' : DIR_DOCUMENTS;
    if (!folder_create($folder, 0)) {
        die($GLOBALS['lang']['err_file_write']);
    }

    // check already exists
    foreach ($GLOBALS['files_list'] as $files) {
        if (($file['bt_checksum'] == $files['bt_checksum'])) {
            $file['bt_id'] = $files['bt_id'];
            return $file;
        }
    }

    // prevent file overwrite
    $file['bt_filename'] = file_uniq_filename($folder, $file['bt_filename']);

    // move the uploaded file
    if ($method == 'upload') {
        $new_file = $sup_var['tmp_name'];
        if (move_uploaded_file($new_file, $folder.$file['bt_filename'])) {
            $file['bt_checksum'] = sha1_file($folder.$file['bt_filename']);
        } else {
            // fail
            redirection(basename($_SERVER['SCRIPT_NAME']).'?errmsg=error_fichier_ajout_2');
        }

    // file submitted threw URL
    } elseif ($method == 'download' and copy($sup_var, $folder.$file['bt_filename'])) {
        $file['bt_filesize'] = filesize($folder.$file['bt_filename']);
    } else {
        // fail
        redirection(basename($_SERVER['SCRIPT_NAME']).'?errmsg=error_fichier_ajout');
    }

    // manage file datas
    if ($file['bt_type'] == 'image') { // miniature si c’est une image
        file_thumbnail_create($folder.$file['bt_filename']);
        list($file['bt_dim_w'], $file['bt_dim_h']) = getimagesize($folder.$file['bt_filename']);
    } else {
        $file['bt_path'] = '';
    }

    // update the database
    $GLOBALS['files_list'][] = $file;
    $GLOBALS['files_list'] = sort_by_subkey($GLOBALS['files_list'], 'bt_id');
    if (!file_put_array(FILE_VHOST_FILES_DB, $GLOBALS['files_list'])) {
        return false;
    } else {
        return $file;
    }
}

function file_handler_upd($file, $current_filename)
{
    // if file renamed, need to move the file
    if ($file['bt_filename'] != $current_filename) {
        $folder = ($file['bt_type'] == 'image') ? DIR_IMAGES.$file['bt_path'].'/' : DIR_DOCUMENTS;
        if (!folder_create($folder, 0)) {
            die($GLOBALS['lang']['err_file_write']);
        }
        // prevent file overwrite
        $file['bt_filename'] = file_uniq_filename($folder, $file['bt_filename']);

        // rename file on disk
        if (rename($folder.$current_filename, $folder.$file['bt_filename'])) {
            // handle image's thumbnail
            if ($fichier['bt_type'] == 'image') {
                $old_thb = file_thumbnail_exists($folder.$current_filename);
                if ($old_thb != $folder.$current_filename) {
                    rename($old_thb, file_thumbnail_path($folder.$file['bt_filename']));
                } else {
                    file_thumbnail_create($folder.$file['bt_filename']);
                }
            }
            // error rename ficher
        } else {
            redirection(basename($_SERVER['SCRIPT_NAME']).'?file_id='.$file['bt_id'].'&errmsg=error_fichier_rename');
        }
    }
    // update file dimensions
    list($file['bt_dim_w'], $file['bt_dim_h']) = getimagesize($dossier.$file['bt_filename']);

    // update the DB
    $found = 0;
    foreach ($GLOBALS['files_list'] as $key => $entry) {
        // finds the right entry
        if ($entry['bt_id'] == $file['bt_id']) {
            $GLOBALS['files_list'][$key] = $file;
            $found = 1;
            break;
        }
    }
    if ($found === 0) {
        return false;
    }

    // save the db
    $GLOBALS['files_list'] = sort_by_subkey($GLOBALS['files_list'], 'bt_id');
    file_put_array(FILE_VHOST_FILES_DB, $GLOBALS['files_list']);
    redirection(basename($_SERVER['SCRIPT_NAME']).'?file_id='.$file['bt_id'].'&edit&msg=confirm_fichier_edit');
}

function file_handler_del($file)
{
    foreach ($GLOBALS['files_list'] as $fid => $fich) {
        if ($file['bt_id'] == $fich['bt_id']) {
            $tbl_id = $fid;
            break;
        }
    }
    if (!isset($tbl_id)) {
        return false;
    }
    $folder = ($file['bt_type'] == 'image') ? DIR_IMAGES.$file['bt_path'].'/' : DIR_DOCUMENTS;
    // remove physical file on disk if it exists
    if (is_file($dossier.$file['bt_filename']) and isset($tbl_id)) {
        $liste_fichiers = rm_dots_dir(scandir($dossier)); // liste les fichiers réels dans le dossier
        if (@unlink($dossier.$file['bt_filename'])) { // fichier physique effacé
            if ($file['bt_type'] == 'image') {
                // Delete the preview picture if any
                $img = file_thumbnail_path($dossier.$file['bt_filename']);
                if (is_file($img)) {
                    @unlink($img);
                }
            }
            unset($GLOBALS['files_list'][$tbl_id]); // efface le fichier dans la liste des fichiers.
            $GLOBALS['files_list'] = sort_by_subkey($GLOBALS['files_list'], 'bt_id');
            file_put_array(FILE_VHOST_FILES_DB, $GLOBALS['files_list']);
            return 'success';
        } else { // erreur effacement fichier physique
            return 'error_suppr_file_suppr_error';
        }
    }

    // the file in DB does not exists on disk => remove entry from DB
    if (isset($tbl_id)) {
        unset($GLOBALS['files_list'][$tbl_id]); // remove entry from files-list.
    }
    $GLOBALS['files_list'] = sort_by_subkey($GLOBALS['files_list'], 'bt_id');
    file_put_array(FILE_VHOST_FILES_DB, $GLOBALS['files_list']);
    return 'no_such_file_on_disk';
}

/**
 * TRAITEMENT DU FORMULAIRE DE FICHIER, CÔTÉ BDD
 * Retourne le $fichier de l’entrée (après avoir possiblement changé des trucs, par ex si le fichier existait déjà, l’id retourné change)
 */
function bdd_fichier($fichier, $quoi, $comment, $sup_var)
{
    if ($fichier['bt_type'] == 'image') {
        $dossier = DIR_IMAGES.$fichier['bt_path'].'/';
    } else {
        $dossier = DIR_DOCUMENTS;
        $rand_dir = '';
    }
    if (!folder_create($dossier, 0)) {
        die($GLOBALS['lang']['err_file_write']);
    }
    if ($quoi == 'supprimer-existant') {
        $id = $sup_var;
        // FIXME ajouter un test de vérification de session (security coin)
        foreach ($GLOBALS['files_list'] as $fid => $fich) {
            if ($id == $fich['bt_id']) {
                $tbl_id = $fid;
                break;
            }
        }
        // remove physical file on disk if it exists
        if (is_file($dossier.$fichier['bt_filename']) and isset($tbl_id)) {
            $liste_fichiers = rm_dots_dir(scandir($dossier)); // liste les fichiers réels dans le dossier
            if (@unlink($dossier.$fichier['bt_filename'])) { // fichier physique effacé
                if ($fichier['bt_type'] == 'image') {
                    // Delete the preview picture if any
                    $img = file_thumbnail_path($dossier.$fichier['bt_filename']);
                    if (is_file($img)) {
                        @unlink($img);
                    }
                }
                unset($GLOBALS['files_list'][$tbl_id]); // efface le fichier dans la liste des fichiers.
                $GLOBALS['files_list'] = sort_by_subkey($GLOBALS['files_list'], 'bt_id');
                file_put_array(FILE_VHOST_FILES_DB, $GLOBALS['files_list']);
                return 'success';
            } else { // erreur effacement fichier physique
                return 'error_suppr_file_suppr_error';
            }
        }

        // the file in DB does not exists on disk => remove entry from DB
        if (isset($tbl_id)) {
            unset($GLOBALS['files_list'][$tbl_id]); // remove entry from files-list.
        }
        $GLOBALS['files_list'] = sort_by_subkey($GLOBALS['files_list'], 'bt_id');
        file_put_array(FILE_VHOST_FILES_DB, $GLOBALS['files_list']);
        return 'no_such_file_on_disk';
    }
}

/*
 * On post of a file (always on admin sides)
 * gets posted informations and turn them into
 * an array
 */
function init_post_fichier()
{
    // no $mode : it's always admin.
    // on edit : get file info from form
    if (isset($_POST['is_it_edit']) and $_POST['is_it_edit'] == 'yes') {
        $file_id = htmlspecialchars($_POST['file_id']);
        $filename = pathinfo(htmlspecialchars($_POST['filename']), PATHINFO_FILENAME);
        $ext = strtolower(pathinfo(htmlspecialchars($_POST['filename']), PATHINFO_EXTENSION));
        $checksum = htmlspecialchars($_POST['sha1_file']);
        $size = (int) $_POST['filesize'];
        $type = file_guess_type($ext);
        $dossier = htmlspecialchars($_POST['dossier']);
        $path = htmlspecialchars($_POST['path']);
        // on new post, get info from the file itself
    } else {
        $file_id = date('YmdHis');
        $dossier = htmlspecialchars($_POST['dossier']);
        // ajout de fichier par upload
        if (!empty($_FILES['fichier']) and ($_FILES['fichier']['error'] == 0)) {
            $filename = pathinfo($_FILES['fichier']['name'], PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
            $checksum = sha1_file($_FILES['fichier']['tmp_name']);
            $size = (int) $_FILES['fichier']['size'];
            $type = file_guess_type($ext);
            $path = '';
            // ajout par une URL d’un fichier distant
        } elseif (!empty($_POST['fichier'])) {
            $filename = pathinfo(parse_url($_POST['fichier'], PHP_URL_PATH), PATHINFO_FILENAME);
            $ext = strtolower(pathinfo(parse_url($_POST['fichier'], PHP_URL_PATH), PATHINFO_EXTENSION));
            $checksum = sha1_file($_POST['fichier']); // works with URL files
            $size = 0;// same (even if we could use "filesize" with the URL, it would over-use data-transfer)
            $path = '';
            $type = file_guess_type($ext);
        } else {
            // ERROR
            redirection(basename($_SERVER['SCRIPT_NAME']).'?errmsg=error_image_add');
        }
    }
    // nom du fichier : si nom donné, sinon nom du fichier inchangé
    $filename = diacritique(htmlspecialchars((!empty($_POST['nom_entree'])) ? $_POST['nom_entree'] : $filename)).'.'.$ext;
    $statut = (isset($_POST['statut']) and $_POST['statut'] == 'on') ? '0' : '1';
    $fichier = array (
        'bt_id' => $file_id,
        'bt_type' => $type,
        'bt_fileext' => $ext,
        'bt_filesize' => $size,
        'bt_filename' => $filename, // le nom du final du fichier peut changer à la fin, si le nom est déjà pris par exemple
        'bt_content' => clean_txt($_POST['description']),
        'bt_wiki_content' => clean_txt($_POST['description']),
        'bt_checksum' => $checksum,
        'bt_statut' => $statut,
        'bt_dossier' => ((empty($dossier)) ? 'default' : $dossier ), // tags
        'bt_path' => ((empty($path)) ? (substr($checksum, 0, 2)) : $path ), // path on disk (rand subdir to avoid too many files in same dir)
    );
    return $fichier;
}

/**
 * Convert a image path into the thumbnail path
 * thumbnail will be in jpg
 *
 * @params $filepath string, "/var/home/toto.jpg"
 * @return string,           "/var/home/toto-thb.jpg"
 */
function file_thumbnail_path($filepath)
{
    $ext = pathinfo($filepath, PATHINFO_EXTENSION);
    return substr($filepath, 0, -(strlen($ext)+1)).'-thb.jpg'; // "+1" is for the "." between name and ext.
}

/**
 * Check if a thumbnail exists
 * ! return the thumbnail if it exists or the source path if thumbnail doesn't exists.
 *
 * @params $filepath string, "/var/home/toto.jpg"
 * @return string,
 *                           "/var/home/toto.jpg" if no thumbnails
 *                           "/var/home/toto-thb.jpg" if thumbnails
 */
function file_thumbnail_exists($filepath)
{
    $thb = file_thumbnail_path($filepath);
    if (is_file($thb)) {
        return $thb;
    }
    return $filepath;
}

/**
 * filepath : image to create a thumbnail from
 */
function file_thumbnail_create($filepath)
{
    $maxwidth = '700';
    $maxheight = '200';
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

    // si l’image est petite (<200), pas besoin de miniature
    list($width_orig, $height_orig) = getimagesize($filepath);
    if ($width_orig <= 200 and $height_orig <= 200) {
        return;
    }
    // largeur et hauteur maximale
    // Cacul des nouvelles dimensions
    if ($width_orig == 0 or $height_orig == 0) {
        return;
    }
    if ($maxwidth and ($width_orig < $height_orig)) {
        $maxwidth = ($maxheight / $height_orig) * $width_orig;
    } else {
        $maxheight = ($maxwidth / $width_orig) * $height_orig;
    }

    // open file with correct format
    $thumb = imagecreatetruecolor($maxwidth, $maxheight);
    imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
    switch ($ext) {
        case 'jpeg':
        case 'jpg':
            $image = imagecreatefromjpeg($filepath);
            break;
        case 'png':
            $image = imagecreatefrompng($filepath);
            break;
        case 'gif':
            $image = imagecreatefromgif($filepath);
            break;
        default:
            return;
    }

    // resize
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $maxwidth, $maxheight, $width_orig, $height_orig);
    imagedestroy($image);

    // enregistrement en JPG (meilleur compression) des miniatures
    $destination = file_thumbnail_path($filepath); // construit le nom de fichier de la miniature
    imagejpeg($thumb, $destination, 70); // compression à 70%
    imagedestroy($thumb);
}

/**
 * Guess the filetype from file's externsion.
 *
 * @params $extension string, "exe", "jpg" ...
 * @return string
 */
function file_guess_type($extension)
{
    // Table of recognized filetypes
    $extensions = array(
        'archive' => array('zip', '7z', 'rar', 'tar', 'gz', 'bz', 'bz2', 'xz', 'lzma'),
        'executable' => array('exe', 'e', 'bin', 'run'),
        'android-apk' => array('apk'),
        'html-xml' => array('html', 'htm', 'xml', 'mht'),
        'image' => array('png', 'gif', 'bmp', 'jpg', 'jpeg', 'ico', 'svg', 'tif', 'tiff'),
        'music' => array('mp3', 'wave', 'wav', 'ogg', 'wma', 'flac', 'aac', 'mid', 'midi', 'm4a'),
        'presentation' => array('ppt', 'pptx', 'pps', 'ppsx', 'odp'),
        'pdf' => array('pdf', 'ps', 'psd'),
        'ebook' => array('epub', 'mobi'),
        'spreadsheet' => array('xls', 'xlsx', 'xlt', 'xltx', 'ods', 'ots', 'csv'),
        'text_document'=> array('doc', 'docx', 'rtf', 'odt', 'ott'),
        'text-code' => array('txt', 'css', 'py', 'c', 'cpp', 'dat', 'ini', 'inf', 'text', 'conf', 'sh'),
        'video' => array('mkv', 'mp4', 'ogv', 'avi', 'mpeg', 'mpg', 'flv', 'webm', 'mov', 'divx', 'rm', 'rmvb', 'wmv'),
        'other' => array(''),  // default
    );

    // default
    $return = 'other';
    $extension = strtolower($extension);
    foreach ($extensions as $type => $exts) {
        if (in_array($extension, $exts)) {
            $return = $type;
            break;
        }
    }
    return $return;
}

/**
 * try to find mime
 *
 * @params $filepath string
 * @return string
 */
function file_get_mime($filepath) {
    $mime = false;

    if (function_exists('finfo_file')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filepath);
        finfo_close($finfo);
    }

    if (!$mime && function_exists('mime_content_type')) {
        $mime = mime_content_type($filepath);
    }

    if (!$type || in_array($mime, array('application/octet-stream', 'text/plain'))) {
        require_once 'upgradephp/ext/mime.php';
        $exifImageType = exif_imagetype($filepath);
        if ($exifImageType !== false) {
            $mime = image_type_to_mime_type($exifImageType);
        }
    }

    return $mime;
}

/**
 * check if file extension is allowed
 *
 * @params $filename string, the file name
 * @return bool
 */
function file_is_allowed($filename)
{
    /**
     * allowed extension to download,
     * try to prevent BT downloading server side script
     */
    $allowed_ext = array(
            'png', 'gif', 'bmp', 'jpg', 'jpeg', 'ico', 'svg', 'tif', 'tiff', 'psd',
            'mp3', 'wave', 'wav', 'ogg', 'wma', 'flac', 'aac', 'mid', 'midi', 'm4a',
            'ppt', 'pptx', 'pps', 'ppsx', 'odp',
            'pdf', 'epub', 'mobi',
            'xls', 'xlsx', 'xlt', 'xltx', 'ods', 'ots', 'csv',
            'doc', 'docx', 'rtf', 'odt', 'ott', 'txt',
            'mkv', 'mp4', 'ogv', 'avi', 'mpeg', 'mpg', 'flv', 'webm', 'mov', 'divx', 'rm', 'rmvb', 'wmv',
        );

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowed_ext);
}
