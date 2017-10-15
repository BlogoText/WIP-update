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


// addons -> LIST

/**
 * return the list of disabled addons
 * { 'addon_2' }
 */
function addons_list_disabled()
{
    $addons = array();

    foreach (addons_list_all(false) as $addon) {
        if (!addon_test_enabled($addon)) {
            $addons[] = $addon;
        }
    }

    return $addons;
}


// addon -> set

/**
 * set the enabled file for an addon
 */
function addon_set_enabled($addon_id)
{
    $success = (file_put_contents(addon_path_enabled($addon_id, true), '', LOCK_EX) !== false);
    if ($success === true && isset($GLOBALS['addons'][$addon_id])) {
        $GLOBALS['addons'][$addon_id]['enabled'] = true;
    }
    return $success;
}

/**
 * remove the enabled file og an addon
 */
function addon_set_disabled($addon_id)
{
    $file = addon_path_enabled($addon_id, false);
    if (!is_file($file)) {
        return true;
    }
    $success = unlink($file);
    if ($success === true && isset($GLOBALS['addons'][$addon_id])) {
        $GLOBALS['addons'][$addon_id]['enabled'] = false;
    }
    return $success;
}

/**
 * set settings for 1 addon
 * related to /admin/addon-settings.php
 */
function addon_set_settings($addon_id, $settings)
{
    $file = addon_path_vhost($addon_id, true).'settings.php';
    $vars = preg_replace('/\s$/m', '', var_export($settings, true));
    $to_file = '<?php return '.$vars.';'."\n";
    $is_writed = file_put_contents($file, $to_file, LOCK_EX);
    return ($is_writed !== false);
}


// addons -> html

/**
 * return the main list of addon
 */
function addons_html_get_list_addons($addons, $filtre)
{
    if (!empty($addons)) {
        // $out = '<ul id="modules" class="block_medium block-white">';
        $out = '<div id="addons_list" class="block_medium block-white">';
        foreach ($addons as $i => $addon) {
            if (!isset($GLOBALS['addons'][$addon])) {
                // var_dump('Addon "'.$addon.'" not loaded');
                continue;
            }
            $addon_path = addon_path($addon);
            $addon = $GLOBALS['addons'][$addon];

            // addon
            $out .= '<div class="showhide">';
                $out .= '<div class="header">';
                    // addon checkbox activation
                    $out .= '<span><input type="checkbox" class="checkbox-toggle" name="module_'.$addon['tag'].'" id="module_'.$addon['tag'].'" '.(($addon['enabled']) ? 'checked' : '').' onchange="addon_switch_enabled(this);" /><label for="module_'.$addon['tag'].'"></label></span>';
                    // addon name
                    $out .= '<span>'.addon_get_translation($addon['name']).'</span>';
                    // addon version
                    $out .= '<span>v'.$addon['version'].'</span>';
                    $out .= '<a href="#" class="btn btn-flat ico-chevronLeft" onclick="showhide(this);return false"></a>';
                $out .= '</div>';
                // other infos and params
                $out .= '<div class="content">';

                    // description
                    $out .= '<p>'.addon_get_translation($addon['desc']).'</p>';

                    $out .= '<p><strong>Using this addon</strong></p>';
                    // tag
                    $out .= '<p>';
                    if (function_exists('a_'.$addon['tag'])) {
                        $out .= $GLOBALS['lang']['addons_insert_code'];
                        $out .= '<code title="'.$GLOBALS['lang']['label_code_theme'].'">'.'{addon_'.$addon['tag'].'}'.'</code>';
                    } else {
                        $out .= '<small>'.$GLOBALS['lang']['label_no_code_theme'].'</small>';
                    }
                    $out .= '</p>';

                    // public / admin / admin-page
                    $out .= '<p>';
                        $out .= '<input type="checkbox" readonly disabled class="checkbox" '. ((file_exists($addon_path.'public.php')) ? 'checked' : '') .' />';
                        $out .= '<label>is public</label>';

                        $out .= '<input type="checkbox" readonly disabled class="checkbox" '. ((file_exists($addon_path.'admin.php')) ? 'checked' : '') .' />';
                        $out .= '<label>is admin</label>';
                        
                        $out .= '<input type="checkbox" readonly disabled class="checkbox" '. ((file_exists($addon_path.'admin-home.php')) ? 'checked' : '') .' />';
                        $out .= '<label>is admin home</label>';
                    $out .= '<p>';

                    $out .= '<h4>About</h4>';
                    $out .= '<p>';
                        // author URL
                        if (!empty($addon['url'])) {
                            $out .= '<a href="'.$addon['url'].'">'.$GLOBALS['lang']['label_owner_url'].'</a>';
                        }
                    $out .= '</p>';
                $out .= '</div>';
                // buttons
                $out .= '<div class="footer">';
                    if (file_exists($addon_path.'admin-page.php')) {
                        $out .= '<a class="btn btn-blue btn-flat" href="addon-page.php?addon='.$addon['tag'].'">advanced</a>';
                    }
                    // addon params or buttons
                    if (isset($addon['settings']) || isset($addon['buttons']) || (is_dir(addon_path_vhost_cache($addon['tag'], false)))) {
                        $out .= '<a class="btn btn-blue btn-flat" href="addon-settings.php?addon='. $addon['tag'] .'">'.$GLOBALS['lang']['addons_settings_link_title'].'</a>';
                    }
                $out .= '</div>';
            $out .= '</div>';
        }
        $out .= '</div>';
    } else {
        $out = info($GLOBALS['lang']['note_no_module']);
    }

    return $out;
}


// addon -> ajax

/**
 * proceed ajax submitted enabled/disabled
 */
function addon_ajax_switch_enabled_proceed($addon)
{
    $errors = array();

    $return = array(
        'success' => false,
        'token' => token_set(),
        'message' => ''
    );

    $is_enabled = addon_test_enabled($addon['addon_id']);
    $new_status = (bool)$addon['status'];

    if ($is_enabled != $new_status) {
        if ($new_status) {
            // Addon enabled: we create .enabled
            if (!addon_set_enabled($addon['addon_id'])) {
                $return['message'] = sprintf($GLOBALS['lang']['err_addon_enabled'], $addon['addon_id'], DIR_ADDONS);
            } else {
                $return['success'] = true;
            }
        } else {
            // Addon disabled: we delete .enabled
            if (!addon_set_disabled($addon['addon_id'])) {
                $return['message'] = sprintf($GLOBALS['lang']['err_addon_disabled'], $addon['addon_id']);
            } else {
                $return['success'] = true;
            }
        }

        if (!addons_db_refresh()) {
            $return['message'] = 'fail to refresh cache';
            // try to delete
            if (!addons_db_del()) {
                $return['success'] = false;
                $return['message'] .= ' and fail to delete cache, please check your file system rights.';
            } else {
                // return message
                $return['message'] .= ', but the cache have been deleted, it will recreate later.';
            }
        }
    } else {
        $return['success'] = false;
        $return['message'] = 'no change detected, please reload this page';
    }

    die(json_encode($return));
    if (isset($_POST['mod_activer'])) {
        if (empty($errors)) {
            die('Success'.token_set());
        } else {
            die('Error'.token_set().implode("\n", $errors));
        }
    }

    return $errors;
}

/**
 *
 */
function addon_ajax_check_request($addon_id, $check_key)
{
    $errors = array();
    // do not check token on ajax request
    if (TOKEN_CHECK !== true) {
        $errors[] = $GLOBALS['lang']['err_wrong_token'];
    }

    if (empty($addon_id) || preg_match('/^[\w\-]+$/', $addon_id) === false || !addon_test_exists($addon_id)) {
        $errors[] = $GLOBALS['lang']['err_addon_name'];
    }
    return $errors;
}

/**
 *
 */
function addon_ajax_button_action_process($addon_id, $button_id)
{
    $loaded = addon_load($addon_id);
    if ($loaded === false) {
        // to do
        return $loaded;
    }

    $return = array(
        'success' => false,
        'token' => token_set(),
        'message' => ''
    );

    if ($button_id == 'addon_clean_cache') {
        $cleaner = addon_clean_cache($addon_id); // must be tested
        if ($cleaner === true) {
            $return['message'] = 'Cache for this addon has been clean !';
            $return['success'] = true;
        } else {
            $return['message'] = 'Fail to clean all the cache :/';
        }
        die(json_encode($return));
    }

    if (!isset($GLOBALS['addons'][$addon_id]['buttons'])) {
        $return['message'] = 'this addon don\'t have button';
        die(json_encode($return));
    }
    if (!isset($GLOBALS['addons'][$addon_id]['buttons'][$button_id])) {
        $return['message'] = 'this addon don\'t have this button';
        die(json_encode($return));
    }
    if (!isset($GLOBALS['addons'][$addon_id]['buttons'][$button_id]['callback'])) {
        $return['message'] = 'this addon doesn\'t have callback function';
        die(json_encode($return));
    }
    if (!function_exists($GLOBALS['addons'][$addon_id]['buttons'][$button_id]['callback'])) {
        $return['message'] = 'the callback to this button doesn\'t not exists';
        die(json_encode($return));
    }

    // prevent echo() ...
    ob_start();
    $fn = call_user_func($GLOBALS['addons'][$addon_id]['buttons'][$button_id]['callback']);
    ob_end_clean();

    if ($fn === false) {
        $return['message'] = 'The action fail :/';
    } elseif ($fn === true) {
        $return['message'] = 'The action is done with success !';
        $return['success'] = true;
    } elseif (is_array($fn)) {
        $return['success'] = $fn['success'];
        $return['message'] = htmlspecialchars(strip_tags($fn['message']), ENT_QUOTES);
    }

    die(json_encode($return));
}


// addon -> form

/**
 * process (check) the submited config change for an addon
 *
 * todo :
 *   - manage errors
 *
 * @param string $addon_id, the addon name
 * @return bool
 */
function addon_form_edit_settings_proceed($addon_id)
{
    $errors = array();
    $datas = array();

    $loaded = addon_load($addon_id);
    if ($loaded === false) {
        echo $loaded;
    }

    if (!isset($GLOBALS['addons'][$addon_id]['settings'])) {
        return true;
    }

    foreach ($GLOBALS['addons'][$addon_id]['settings'] as $key => $param) {
        $datas[$key] = '';
        if ($param['type'] == 'bool') {
            $datas[$key] = (int) (isset($_POST[$key]));
        } else if ($param['type'] == 'int') {
            if (isset($_POST[$key]) && is_numeric($_POST[$key])) {
                $value = (int) $_POST[$key];
                if (isset($param['value_min']) && $value < $param['value_min']) {
                    $errors[$key][] = 'Value is behind limit min.';
                } else if (isset($param['value_max']) && $value > $param['value_max']) {
                    $errors[$key][] = 'Value is reach limit max.';
                } else {
                    $datas[$key] = (int)$value;
                }
            } else {
                // error
                $errors[$key][] = 'No data posted';
            }
        } else if ($param['type'] == 'text') {
            $datas[$key] = htmlentities($_POST[$key], ENT_QUOTES);
        } else if ($param['type'] == 'select') {
            if (isset($param['options'][$_POST[$key]])) {
                $datas[$key] = htmlentities($_POST[$key], ENT_QUOTES);
            } else {
                $errors[$key][] = 'not a valid type';
            }
        } else {
            // error
            $errors[$key][] = 'not a valid type';
        }
    }

    if (count($errors) !== 0) {
        // reset default
        return $errors;
    }

    $is_saved = addon_set_settings($addon_id, $datas);
    if ($is_saved !== true) {
        $errors['form'] = 'fail to save settings';
        return $errors;
    }

    // saved, refresh globals
    foreach ($datas as $k => $v) {
        $GLOBALS['addons'][$addon_id]['settings'][$k]['value'] = $v;
    }

    // refresh addon's database
    if (!addons_db_refresh()) {
        $errors['info'] = 'fail to refresh addon\'s database';
        // try to delete
        if (!addons_db_del()) {
            $errors['info'] = ' and fail to delete addon\'s database, please check your file system rights.';
        } else {
            // return message
            $errors['info'] = ', but delete the addon\'s database, it will recreate later.';
        }
    }

    return true;
}

/**
 * Get the addon button form
 */
function addon_form_buttons($addon_id)
{
    $loaded = addon_load($addon_id);
    if ($loaded === false) {
        echo $loaded;
    }
    $return_form = false;

    // button
    $out = '';
    $out .= '<form method="post" action="?addon='. $addon_id .'" onsubmit="return confirm(\''. addslashes($GLOBALS['lang']['addons_confirm_buttons_action']) .'\');" >';
    $out .= '<legend class="legend-user">'.$GLOBALS['lang']['addons_buttons_action_legend'].addon_get_translation($GLOBALS['addons'][$addon_id]['name']).'</legend>';

    $out .= '<div class="form-lines">';
    if (isset($GLOBALS['addons'][$addon_id]['buttons'])) {
        $return_form = true;
        foreach ($GLOBALS['addons'][$addon_id]['buttons'] as $btnId => $btn) {
            $out .= '<p><input type="checkbox" class="checkbox-toggle" name="'.$btnId.'" id="addon_'.$btnId.'" onchange="addon_button_action(this,\''.$addon_id.'\',\''.$btnId.'\');" />';
            $out .= '<label for="addon_'.$btnId.'">'. addon_get_translation($btn['label']) .'';
            if (isset($btn['desc'])) {
                $out .= '<br /><small>'. addon_get_translation($btn['desc']) .'</small>';
            }
            $out .= '</label></p>';
        }
    }
    if (is_dir(addon_path_vhost_cache($addon_id, false))) {
        $return_form = true;
        $out .= '<p><input type="checkbox" class="checkbox-toggle" name="addon_clean_cache" id="addon_clean_cache" onchange="addon_button_action(this,\''.$addon_id.'\',\'addon_clean_cache\');" /><label for="addon_clean_cache">'. $GLOBALS['lang']['addons_clean_cache_label'] .'</label></p>';
    }
    $out .= '</div">';
    // submit box
    $out .= '<div class="btn-container">';
        $out .= hidden_input('_verif_envoi', '1');
        $out .= hidden_input('token', token_set());
        $out .= hidden_input('action_type', 'buttons');
        $out .= '<input type="hidden" name="addon_action" value="buttons" />';
        $out .= '<button class="btn btn-cancel" type="button" onclick="redirection(\'addons.php\');" >'.$GLOBALS['lang']['cancel'].'</button>';
        $out .= '<button class="btn btn-submit" type="submit" name="enregistrer">'.$GLOBALS['lang']['submit'].'</button>';
    $out .= '</div>';
    // END submit box
    $out .= '</div>';
    $out .= '</form>';

    if ($return_form === true) {
        return $out;
    }
    return '';
}

/**
 * Get the addon config form
 *
 * @param string $addon, the addon name
 * @return string, the html form
 */
function addon_form_edit_settings($addon_id)
{
    $loaded = addon_load($addon_id);
    if ($loaded === false) {
        echo $loaded;
    }

    $out = '';
    if (isset($GLOBALS['addons'][$addon_id]['settings']) && count($GLOBALS['addons'][$addon_id]['settings']) > 0) {
        // settings
        $out .= '<form class="form-inline block_medium block-white block_legend" method="post" action="?addon='. $addon_id .'" >';
        $out .= '<legend>'.$GLOBALS['lang']['addons_settings_legend'].addon_get_translation($GLOBALS['addons'][$addon_id]['name']).'</legend>';

        // build the config form

        foreach ($GLOBALS['addons'][$addon_id]['settings'] as $key => $param) {
            $out .= '<div class="input">';
            $t_desc = (isset($param['desc'])) ? '<div class="tips">'. addon_get_translation($param['desc']) .'</div>' : '';

            if ($param['type'] == 'bool') {
                $out .= form_checkbox($key, ($param['value'] === true || $param['value'] == 1), addon_get_translation($param['label']).$t_desc);
            } else if ($param['type'] == 'int') {
                $val_min = (isset($param['value_min'])) ? ' min="'.$param['value_min'].'" ' : '' ;
                $val_max = (isset($param['value_max'])) ? ' max="'.$param['value_max'].'" ' : '' ;
                $out .= '<input type="number" id="'.$key.'" name="'.$key.'" size="30" '. $val_min . $val_max .' value="'.$param['value'].'" class="text" />';
                $out .= '<label for="'.$key.'">'.addon_get_translation($param['label']).'</label>';
                $out .= $t_desc;
            } else if ($param['type'] == 'text') {
                $out .= '<input type="text" id="'.$key.'" name="'.$key.'" size="30" value="'.$param['value'].'" class="text" />';
                $out .= '<label for="'.$key.'">'.addon_get_translation($param['label']).'</label>';
                $out .= $t_desc;
            } else if ($param['type'] == 'select') {
                $out .= '<select id="'.$key.'" name="'.$key.'">';
                foreach ($param['options'] as $opt_key => $label_lang) {
                    $selected = ($opt_key == $param['value']) ? ' selected' : '';
                    $out .= '<option value="'. $opt_key .'"'. $selected .'>'. addon_get_translation($label_lang) .'</option>';
                }
                $out .= '</select>';
                $out .= '<label for="'.$key.'">'.addon_get_translation($param['label']).'</label>';
                $out .= $t_desc;
            }
            $out .= '</div>';
        }

        $out .= hidden_input('_verif_envoi', '1');
        $out .= hidden_input('token', token_set());
        $out .= hidden_input('action_type', 'settings');
        $out .= '<input type="hidden" name="addon_action" value="params" />';

        // submit box
        $out .= '<div class="btn-container">';
            $out .= '<button class="btn btn-cancel" type="button" onclick="redirection(\'addons.php\');" >'.$GLOBALS['lang']['cancel'].'</button>';
            $out .= '<button class="btn btn-submit" type="submit" name="enregistrer">'.$GLOBALS['lang']['enregistrer'].'</button>';
        $out .= '</div>';

        // END submit box
        $out .= '</form>';
    }

    return $out;
}

/**
 * show <select> to display list of enabled/disabled/all addons
 *
 * relative to addons_html_get_list_addons();
 */
function addon_form_list_addons_filter($filtre)
{
    $ret = '<div id="form-filtre">';
    $ret .= '<form method="get" action="'.basename($_SERVER['SCRIPT_NAME']).'" onchange="this.submit();">';
    $ret .= '<select name="filtre">' ;

    // All
    $ret .= '<option value="all"'.(($filtre == '') ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_all'].'</option>';

    // Enabled ones
    $ret .= '<option value="enabled"'.(($filtre == 'enabled') ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_enabled'].'</option>';

    // Disabled ones
    $ret .= '<option value="disabled"'.(($filtre == 'disabled') ? ' selected="selected"' : '').'>'.$GLOBALS['lang']['label_disabled'].'</option>';
    $ret .= '</select> ';
    $ret .= '</form>';
    $ret .= '</div>';
    return $ret;
}


// addon -> buttons

/**
 * perform action from button
 * must be call by the form in /admin/addon-settings.php
 */
function addon_buttons_action_process($addon_id)
{
    $loaded = addon_load($addon_id);
    if ($loaded === false) {
        echo $loaded;
    }

    $return = array();

    if (isset($GLOBALS['addons'][$addon_id]['buttons'])) {
        foreach ($GLOBALS['addons'][$addon_id]['buttons'] as $btnId => $btn) {
            if (!isset($_POST[$btnId]) || !function_exists($btn['callback'])) {
                $return['addon'][$btnId]['run'] = false;
                continue;
            }

            // prevent echo() ...
            ob_start();
            $return['addon'][$btnId]['return'] = call_user_func($btn['callback']);
            ob_end_clean();
            $return['addon'][$btnId]['run'] = true;
        }
    }

    // clean module cache ?
    if (isset($_POST['addon_clean_cache'])) {
        $return['addon_clean_cache'] = addon_clean_cache($addon_id); // TODO must be tested
    }

    // refresh the db
    $return['addons_db_refresh'] = addons_db_refresh();

    return $return;
}
