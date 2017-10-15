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
 *
 */
function valider_form_fichier($file)
{
    $errors = array();
    $is_update = (filter_input(INPUT_POST, 'is_it_edit') !== null);
    $url = filter_input(INPUT_POST, 'url');

    // if (TOKEN_CHECK !== true) {
        // $errors[] = $GLOBALS['lang']['err_wrong_token'];
    // }
    if (!$is_update) {
        // New file
        if (isset($_FILES['fichier'])) {
            if (($_FILES['fichier']['error'] == UPLOAD_ERR_INI_SIZE) || ($_FILES['fichier']['error'] == UPLOAD_ERR_FORM_SIZE)) {
                $errors[] = 'Fichier trop gros';
            } elseif ($_FILES['fichier']['error'] == UPLOAD_ERR_PARTIAL) {
                $errors[] = 'dépot interrompu';
            } elseif ($_FILES['fichier']['error'] == UPLOAD_ERR_NO_FILE) {
                $errors[] = 'aucun fichier déposé';
            }
        } elseif ($url !== null && empty($url)) {
            $errors[] = $GLOBALS['lang']['err_lien_vide'];
        }
    } elseif (!$file['bt_filename']) {
        // On edit
        $errors[] = 'nom de fichier invalide';
    }

    return $errors;
}

/**
 *
 */
function valider_form_rss()
{
    $errors = array();
    $markAsRead = filter_input(INPUT_POST, 'mark-as-read');
    $url = filter_input(INPUT_POST, 'add-feed');
    $isDeletion = (filter_input(INPUT_POST, 'delete_old') !== null);

    // Check unique-token only on critical actions (session ID check is still there)
    if (($url !== null || $isDeletion) && TOKEN_CHECK !== true) {
        $errors[] = $GLOBALS['lang']['err_wrong_token'];
    }
    // On feed add: URL needs to be valid, not empty, and must not already be in DB
    if ($url !== null) {
        if (empty($url)) {
            $errors[] = $GLOBALS['lang']['err_lien_vide'];
        }
        if (!preg_match('#^(https?://[\S]+)[a-z]{2,6}[-\#_\w?%*:.;=+\(\)/&~$,]*$#', trim($url))) {
            $errors[] = $GLOBALS['lang']['err_comm_webpage'];
        }
        if (array_key_exists($url, $GLOBALS['feeds_list'])) {
            $errors[] = $GLOBALS['lang']['err_feed_exists'];
        }
    } elseif ($markAsRead !== null && !in_array($markAsRead, array('all', 'site', 'post', 'folder', 'postlist'))) {
        $errors[] = $GLOBALS['lang']['err_feed_wrong_param'];
    }
    return $errors;
}

/**
 * return a HTML label + select
 *
 * @params $name string, the html name + id
 * @params $choix string, all the select > options
 * @params $selected string, the selected / default option
 * @params $label string
 * @return string
 */
function form_select($name, $choix, $selected, $label)
{
    $form = '';

    // $form .= '<div class="input">';
    $form .= '<select id="'.$name.'" class="text" name="'.$name.'">';
    foreach ($choix as $valeur => $mot) {
        $form .= '<option value="'.$valeur.'"'.(($selected == $valeur) ? ' selected="selected" ' : '').'>'.$mot.'</option>';
    }
    $form .= '</select>';
    $form .= '<label for="'.$name.'">'.$label.'</label>';
    // $form .= '</div>';

    return $form;
}

/**
 * return a HTML label + select for Material Design
 *  - invert label - select to select - label
 *  - must be wrapped in a <div class="input"></div>
 *
 * @params $name string, the html name + id
 * @params $choix string, all the select > options
 * @params $selected string, the selected / default option
 * @params $label string
 * @return string
 */
function form_MD_select($name, $choix, $selected, $label)
{
    $form = '<select id="'.$name.'" name="'.$name.'">';
    foreach ($choix as $valeur => $mot) {
        $form .= '<option value="'.$valeur.'"'.(($selected == $valeur) ? ' selected="selected" ' : '').'>'.$mot.'</option>';
    }
    $form .= '</select>';
    $form .= '<label for="'.$name.'">'.$label.'</label>';
    return $form;
}

/**
 *
 */
function form_select_no_label($name, $choix, $defaut)
{
    $form = '<select id="'.$name.'" name="'.$name.'">';
    foreach ($choix as $valeur => $mot) {
        $form .= '<option value="'.$valeur.'"'.(($defaut == $valeur) ? ' selected="selected" ' : '').'>'.$mot.'</option>';
    }
    $form .= '</select>';
    return $form;
}

/**
 * convert a list of tags into a form
 *
 * @params string $where, articles || links ...
 * @params array $postTags, list of tags selected
 * @return string
 */
function tags_form($where, $postTags)
{
    $tags = tags_list_all($where, false);
    $listTags = explode(',', $postTags);
    // remove diacritics and reindexes so that "ééé" does not passe after "zzz"
    foreach ($listTags as $i => $tag) {
        $listTags[$i] = array('value' => trim($tag), 'key' => diacritique(trim($tag)));
    }
    $listTags = array_reverse(sort_by_subkey($listTags, 'key'));

    $html = '<div class="input">';
        $html .= '<div id="tag_bloc">';
        if ($tags) {
            $html .= '<datalist id="htmlListTags">';
            foreach ($tags as $tag => $i) {
                $html .= '<option value="'.addslashes($tag).'">';
            }
            $html .= '</datalist>';
        }
            $html .= '<ul id="selected">';
            foreach ($listTags as $i => $tag) {
                if ($tag['value']) {
                    $html .= '<li><span>'.$tag['value'].'</span><a href="javascript:void(0)" onclick="removeTag(this.parentNode)">×</a></li>';
                }
            }
            $html .= '</ul>';
            $html .= '<input list="htmlListTags" type="text" class="text" id="type_tags" name="tags" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'" />';
            $html .= '<input type="hidden" id="categories" name="bt_tags" value="" />';
        $html .= '</div>';
    $html .= '</div>';

    return $html;
}

/**
 * Posts forms
 */
function tpl_filter_form($type, $filtre)
{
    $html = '<form method="get" action="'.basename($_SERVER['SCRIPT_NAME']).'" onchange="this.submit();">';
    $html .= '<div id="form-filtre">';
    $html .= filtre($type, $filtre);
    $html .= '</div>';
    $html .= '</form>';
    return $html;
}

/**
 *
 */
function form_checkbox($name, $checked, $label)
{
    $checked = ($checked) ? 'checked ' : '';
    $form = '<input type="checkbox" id="'.$name.'" name="'.$name.'" '.$checked.' class="checkbox-toggle" />' ;
    $form .= '<label for="'.$name.'" >'.$label.'</label>';
    return $form;
}

/**
 * FOR COMMENTS : RETUNS nb_com per author
 */
function nb_entries_as($table, $what)
{
    $query = "
        SELECT count($what) AS nb, $what
          FROM $table
         GROUP BY $what
         ORDER BY nb DESC";

    return $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 *
 */
function filtre($type, $filtre)
{
    // WARNING: this is a resources heavy consuming function.
    $listTypes = array();
    $ret = '<select name="filtre">' ;
    if ($type == 'articles') {
        $ret .= '<option value="">'.$GLOBALS['lang']['label_article_last'].'</option>';
        $query = '
            SELECT DISTINCT substr(bt_date, 1, 6) AS date
              FROM articles
             ORDER BY date DESC';
        $arrTags = tags_list_all('articles', false);
        $databaseType = 'sqlite';
    } elseif ($type == 'commentaires') {
        $ret .= '<option value="">'.$GLOBALS['lang']['label_comment_last'].'</option>';
        $arrAuthors = nb_entries_as('commentaires', 'bt_author');
        $query = '
            SELECT DISTINCT substr(bt_id, 1, 6) AS date
              FROM commentaires
             ORDER BY bt_id DESC';
        $databaseType = 'sqlite';
    } elseif ($type == 'links') {
        $ret .= '<option value="">'.$GLOBALS['lang']['label_link_last'].'</option>';
        $arrTags = tags_list_all('links', false);

        // FIX: sometimes this is an empty array. To investigate.
        $arrTags = array_filter($arrTags, 'strlen', ARRAY_FILTER_USE_KEY);

        $query = '
            SELECT DISTINCT substr(bt_id, 1, 6) AS date
              FROM links
             ORDER BY bt_id DESC';
        $databaseType = 'sqlite';
    } elseif ($type == 'fichiers') {
        // crée un tableau où les clé sont les types de fichiers et les valeurs, le nombre de fichiers de ce type.
        $files = $GLOBALS['files_list'];
        $arrMonths = array();
        foreach ($files as $file) {
            $type = $file['bt_type'];
            if (!array_key_exists($type, $listTypes)) {
                $listTypes[$type] = 0;
            }
            $listTypes[$type]++;
        }
        arsort($listTypes);

        $ret .= '<option value="">'.$GLOBALS['lang']['label_file_last'].'</option>';
        $databaseType = 'fichier_txt_files';
    }

    if ($databaseType == 'sqlite') {
        $req = $GLOBALS['db_handle']->prepare($query);
        $req->execute();
        while ($row = $req->fetch()) {
            $arrMonths[$row['date']] = month_en_lettres(substr($row['date'], 4, 2)).' '.substr($row['date'], 0, 4);
        }
    } elseif ($databaseType == 'fichier_txt_files') {
        foreach ($GLOBALS['files_list'] as $e) {
            if (!empty($e['bt_id'])) {
                // mk array[201005] => "May 2010", uzw
                $arrMonths[substr($e['bt_id'], 0, 6)] = month_en_lettres(substr($e['bt_id'], 4, 2)).' '.substr($e['bt_id'], 0, 4);
            }
        }
        krsort($arrMonths);
    }

    // Drafts
    $ret .= '<option value="draft"'.(($filtre == 'draft') ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_invisibles'].'</option>';

    // Public
    $ret .= '<option value="pub"'.(($filtre == 'pub') ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_publisheds'].'</option>';

    // By date
    if (!empty($arrMonths)) {
        $ret .= '<optgroup label="'.$GLOBALS['lang']['label_date'].'">';
        foreach ($arrMonths as $month => $label) {
            $ret .= '<option value="' . htmlentities($month) . '"'.((substr($filtre, 0, 6) == $month) ? ' selected="selected"' : '').'>'.$label.'</option>';
        }
        $ret .= '</optgroup>';
    }

    // By author (for comments)
    if (!empty($arrAuthors)) {
        $ret .= '<optgroup label="'.$GLOBALS['lang']['settings_author'].'">';
        foreach ($arrAuthors as $nom) {
            if (!empty($nom['nb'])) {
                $ret .= '<option value="author.'.$nom['bt_author'].'"'.(($filtre == 'author.'.$nom['bt_author']) ? ' selected="selected"' : '').'>'.$nom['bt_author'].' ('.$nom['nb'].')'.'</option>';
            }
        }
        $ret .= '</optgroup>';
    }

    // By type (for files)
    if (!empty($listTypes)) {
        $ret .= '<optgroup label="Type">';
        foreach ($listTypes as $type => $nb) {
            if ($type) {
                $ret .= '<option value="type.'.$type.'"'.(($filtre == 'type.'.$type) ? ' selected="selected"' : '').'>'.$type.' ('.$nb.')'.'</option>';
            }
        }
        $ret .= '</optgroup>';
    }

    // By tag (for posts and links)
    if (!empty($arrTags)) {
        $ret .= '<optgroup label="Tags">';
        foreach ($arrTags as $tag => $nb) {
            $ret .= '<option value="tag.'.$tag.'"'.(($filtre == 'tag.'.$tag) ? ' selected="selected"' : '').'>'.$tag.' ('.$nb.')</option>';
        }
        $ret .= '</optgroup>';
    }
    $ret .= '</select> ';

    return $ret;
}
