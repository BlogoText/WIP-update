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

require_once 'inc/boot.php';

/**
 *
 */
function form_format_date($default)
{
    $day = day_en_lettres('31', '12', date('Y'));
    $month = month_en_lettres('12');
    $Y = date('Y');
    $formats = array (
        '31/12/'.$Y,                       // 05/07/2011
        '12/31/'.$Y,                       // 07/05/2011
        '31 '.$month.' '.$Y,               // 05 july 2011
        $day.' 31 '.$month.' '.$Y,         // tuesday 05 july 2011
        $day.' 31 '.$month,                // tuesday 05 july
        $month.' 31, '.$Y,                 // july 05, 2011
        $day.', '.$month.' 31, '.$Y,       // tuesday, july 05, 2011
        $Y.'-12-31',                       // 2011-07-05
        substr($day, 0, 3).'. 31 '.$month, // ven. 14 janvier
    );
    $form = '<label>'.$GLOBALS['lang']['settings_format_date'].'</label>';
    $form .= '<select name="format_date">';
    foreach ($formats as $option => $label) {
        $form .= '<option value="'.htmlentities($option).'"'.(($default == $option) ? ' selected="selected" ' : '').'>'.$label.'</option>';
    }
    $form .= '</select>';
    return $form;
}

/**
 *
 */
function form_timezone($default)
{
    $timezones = timezone_identifiers_list();
    $timezoneList = array();
    foreach ($timezones as $tz) {
        $spos = strpos($tz, '/');
        if ($spos !== false) {
            $continent = substr($tz, 0, $spos);
            $city = substr($tz, $spos + 1);
            $timezoneList[$continent][] = array('tz_name' => $tz, 'city' => $city);
        }
        if ($tz == 'UTC') {
            $timezoneList['UTC'][] = array('tz_name' => 'UTC', 'city' => 'UTC');
        }
    }
    $form = '<label>'.$GLOBALS['lang']['settings_timezone_label'].'</label>';
    $form .= '<select name="timezone">';
    foreach ($timezoneList as $continent => $zone) {
        $form .= '<optgroup label="'.ucfirst(strtolower($continent)).'">';
        foreach ($zone as $fuseau) {
            $form .= '<option value="'.htmlentities($fuseau['tz_name']).'"';
            $form .= ($default == $fuseau['tz_name']) ? ' selected="selected"' : '';
                $timeOffset = date_offset_get(date_create('now', timezone_open($fuseau['tz_name'])));
                $timeOffsetFormatted = sprintf(
                    '(UTC%s%02d:%02d) ',
                    ($timeOffset < 0) ? '–' : '+',
                    floor((abs($timeOffset) / 3600)),
                    floor((abs($timeOffset) % 3600) / 60)
                );
            $form .= '>'.$timeOffsetFormatted.' '.htmlentities($fuseau['city']).'</option>';
        }
        $form .= '</optgroup>';
    }
    $form .= '</select>';
    return $form;
}

/**
 *
 */
function form_format_time($default)
{
    $formats = array (
        '23:59:59',    // date('H:i:s')
        '23:59',       // date('H:i')
        '11:59:59 PM', // date('h:i:s A')
        '11:59 PM'     // date('h:i A')
    );

    $form = '<label>'.$GLOBALS['lang']['settings_format_time'].'</label>';
    $form .= '<select name="format_time">';
    foreach ($formats as $option => $label) {
        $form .= '<option value="'.htmlentities($option).'"'.(($default == $option) ? ' selected="selected" ' : '').'>'.htmlentities($label).'</option>';
    }
    $form .= '</select>';
    return $form;
}

/**
 *
 */
function form_language($default)
{
    $form = '<label>'.$GLOBALS['lang']['settings_language_label'].'</label>';
    $form .= '<select name="site_lang">';
    foreach ($GLOBALS['langs'] as $option => $label) {
        $form .= '<option value="'.htmlentities($option).'"'.(($default == $option) ? ' selected="selected" ' : '').'>'.$label.'</option>';
    }
    $form .= '</select>';
    return $form;
}

/**
 *
 */
function list_themes($path)
{
    if ($handler = opendir($path)) {
        while ($folders = readdir($handler)) {
            if (is_dir($path.'/'.$folders) && is_file($path.'/'.$folders.'/list.html')) {
                $themes[$folders] = $folders;
            }
        }
        closedir($handler);
    }
    if (isset($themes)) {
        return $themes;
    }
}


/**
 * v
 */
function settings_form($errors = '')
{
    $values = settings_vhost_get();

    $html = '<form class="form-inline block_medium" id="settings-form" method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'" >';
    $html .= erreurs($errors);

    // site global public
    $html .= '<div role="group" class="block-white block_legend">';
        $html .= '<legend>'.$GLOBALS['lang']['settings_legend_utilisateur'].'</legend>';

        $html .= '<div class="input">';
            $html .= '<input type="text" id="site_name" name="site_name" size="30" value="'.$values['site_name'].'" class="text" />';
            $html .= '<label for="site_name">'.$GLOBALS['lang']['settings_site_name_label'].'</label>';
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= '<input type="text" id="racine" name="URL_ROOT" size="30" value="'.$values['URL_ROOT'].'" class="text" />';
            $html .= '<label for="racine">'.$GLOBALS['lang']['settings_site_url_label'].'</label>';
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= '<textarea id="site_description" name="site_description" cols="35" rows="2" class="text">'.$values['site_description'].'</textarea>';
            $html .= '<label for="site_description">'.$GLOBALS['lang']['label_dp_description'].'</label>';
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= '<textarea id="site_keywords" name="site_keywords" cols="35" rows="2" class="text">'.$values['site_keywords'].'</textarea>';
            $html .= '<label for="site_keywords">'.$GLOBALS['lang']['settings_keywords'].'</label>';
        $html .= '</div>';
    $html .= '</div>';

    // admin template
    $html .= '<div role="group" class="block-white block_legend">';
        $html .= '<legend>'.$GLOBALS['lang']['settings_legend_apparence'].'</legend>';

        $nbs = array(10 => 10, 25 => 25, 50 => 50, 100 => 100, 300 => 300, -1 => $GLOBALS['lang']['settings_all']);
        $html .= '<div class="input">';
            $html .= form_MD_select('max_bill_admin', $nbs, $values['max_bill_admin'], $GLOBALS['lang']['settings_nb_list']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_MD_select('max_comm_admin', $nbs, $values['max_comm_admin'], $GLOBALS['lang']['settings_nb_list_com']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_checkbox('use_feed_reader', $values['use_feed_reader'], $GLOBALS['lang']['settings_afficher_rss']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_checkbox('quickly_enabled', $values['quickly_enabled'], $GLOBALS['lang']['settings_quickly_enabled']);
        $html .= '</div>';
    $html .= '</div>';

    // public template
    $html .= '<div role="group" class="block-white block_legend">';
        $html .= '<legend>'.$GLOBALS['lang']['settings_legend_apparence'].'</legend>';

        $html .= '<div class="input">';
            $html .= form_MD_select('site_theme', list_themes(DIR_THEMES), $values['site_theme'], $GLOBALS['lang']['settings_theme']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_language($GLOBALS['site_lang']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_format_date($values['format_date']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_format_time($values['format_time']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_timezone($values['timezone']);
        $html .= '</div>';
    $html .= '</div>';

    // blog
    $html .= '<div role="group" class="block-white block_legend">';
        $html .= '<legend>'.$GLOBALS['lang']['settings_legend_blog'].'</legend>';

        $html .= '<div class="input">';
            $html .= form_MD_select('max_bill_acceuil', array(5 => 5, 10 => 10, 15 => 15, 20 => 20,  25 => 25, 50 => 50), $values['max_bill_acceuil'], $GLOBALS['lang']['settings_nb_maxi']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_checkbox('use_tags', $values['use_tags'], $GLOBALS['lang']['settings_categories']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_checkbox('auto_keywords', $values['automatic_keywords'], $GLOBALS['lang']['settings_automatic_keywords']);
        $html .= '</div>';
    $html .= '</div>';

    // comments
    $html .= '<div role="group" class="block-white block_legend">';
        $html .= '<legend>'.$GLOBALS['lang']['settings_legend_blog'].'</legend>';

        $html .= '<div class="input">';
            $html .= form_checkbox('global_comments', $values['comments_allowed'], $GLOBALS['lang']['settings_close_comments']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_checkbox('comments_require_email', $values['comments_require_email'], $GLOBALS['lang']['settings_comments_require_email']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_checkbox('alert_author', $values['alert_author'], $GLOBALS['lang']['settings_mail_on_comment']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_MD_select('comments_defaut_status', array($GLOBALS['lang']['settings_comm_white_list'], $GLOBALS['lang']['settings_comm_black_list']), $GLOBALS['comments_defaut_status'], $GLOBALS['lang']['settings_comm_BoW_list']);
        $html .= '</div>';
    $html .= '</div>';


    if ($GLOBALS['quickly_enabled']) {
        $html .= '<div role="group" class="block-white block_legend">';
            $html .= '<legend>'.$GLOBALS['lang']['settings_legend_links'].'</legend>';

            // nb liens côté admin
            $nbs = array(50 => 50, 100 => 100, 200 => 200, 300 => 300, 500 => 500, -1 => $GLOBALS['lang']['settings_all']);
            $html .= '<div class="input">';
                $html .= form_MD_select('quickly_items_per_page', $nbs, $values['quickly_items_per_page'], $GLOBALS['lang']['settings_quickly_items_per_page']);
            $html .= '</div>';

            // partage de fichiers !pages : télécharger dans fichiers automatiquement ?
            $nbs = array($GLOBALS['lang']['no'], $GLOBALS['lang']['yes'], $GLOBALS['lang']['settings_ask_everytime']);
            $html .= '<div class="input">';
                $html .= form_MD_select('quickly_download_files', $nbs, $values['quickly_download_files'], $GLOBALS['lang']['settings_linx_dl_auto']);
            $html .= '</div>';

            // lien à glisser sur la barre des favoris
            $link = explode('/', dirname($_SERVER['SCRIPT_NAME']));
            $html .= '<p class="txt-center">';
                $html .= $GLOBALS['lang']['settings_label_bookmark_lien'];
                $html .= '<a class="dnd-to-favs btn btn-info btn-dense" onclick="alert(\''.$GLOBALS['lang']['settings_label_bookmark_lien'].'\');return false;" href="javascript:javascript:(function(){window.open(\''.$GLOBALS['URL_ROOT'].$link[count($link) - 1].'/links.php?url=\'+encodeURIComponent(location.href));})();">Save link</a>';
            $html .= '</p>';
        $html .= '</div>';
    } else {
        $html .= hidden_input('quickly_items_per_page', 50);
        $html .= hidden_input('quickly_download_files', 1);
    }

    if ($GLOBALS['use_feed_reader']) {
        $html .= '<div role="group" class="block-white block_legend">';
            $html .= '<legend>'.$GLOBALS['lang']['settings_legend_feedreader'].'</legend>';

            $nbs = array(10 => 10, 25 => 25, 50 => 50, 100 => 100, 300 => 300);
            $html .= '<div class="input">';
                $html .= form_MD_select('max_rss_admin', $nbs, $values['max_rss_admin'], $GLOBALS['lang']['settings_nb_list']);
            $html .= '</div>';

            $html .= '<p class="txt-center">';
                $html .= $GLOBALS['lang']['settings_rss_go_to_imp-export'];
                $html .= '<a class="btn btn-blue btn-flat btn-dense" href="maintenance.php">'.$GLOBALS['lang']['label_import-export'].'</a>';
            $html .= '</p>';

        $html .= '</div>';
    } else {
        $html .= hidden_input('max_rss_admin', 10);
    }

    // cron
    $html .= '<div role="group" class="block-white block_legend">';
        $html .= '<legend>'.$GLOBALS['lang']['title_maintenance'].'</legend>';

        $html .= '<div class="input">';
            $html .= form_checkbox('auto_check_updates', $GLOBALS['auto_check_updates'], $GLOBALS['lang']['settings_check_update']);
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= form_checkbox('auto_check_feeds', $GLOBALS['auto_check_feeds'], $GLOBALS['lang']['settings_check_feeds']);
        $html .= '</div>';

        $feed = explode('/', dirname($_SERVER['SCRIPT_NAME']));
        $html .= '<div class="input">';
            $html .= '<label>'.$GLOBALS['lang']['settings_label_crontab_rss'].'</label>';
            // $html .= '<a class="btn btn-info btn-dense" onclick="prompt(\''.$GLOBALS['lang']['settings_alert_crontab_rss'].'\', \'0 *  *   *   *   wget --spider -qO- '.$GLOBALS['URL_ROOT'].$feed[count($feed) - 1].'/_rss.ajax.php?guid='.SITE_UID.'&refresh_all'.'\');return false;" href="#">Afficher ligne Cron</a>';
            // $html .= '<textarea class="text" readonly>0 *  *   *   *   wget --spider -qO- '.$GLOBALS['URL_ROOT'].'_cron.php?guid='.SITE_UID.'</textarea>';
            $html .= '<textarea class="text" readonly>'.$GLOBALS['URL_ROOT'].'_cron.php?guid='.SITE_UID.'</textarea>';
        $html .= '</div>';
        $html .= '<div class="tips">';
            $html .= 'Use Cron or other service to call this URL. eg.<pre>0 *  *   *   *   wget --spider -qO- '.$GLOBALS['URL_ROOT'].'_cron.php?guid='.SITE_UID.'</pre>';
        $html .= '</div>';
    $html .= '</div>';

    // maintenance
    $html .= '<div role="group" class="block-white block_legend">';
        $html .= '<legend>'.$GLOBALS['lang']['title_maintenance'].'</legend>';

        $html .= '<p class="txt-center">';
            $html .= $GLOBALS['lang']['settings_go_to_maintenance'];
            $html .= '<a class="btn btn-blue btn-flat btn-dense" href="maintenance.php">Maintenance</a>';
        $html .= '</p>';
    $html .= '</div>';


    $html .= '<div class="block-white" id="settings-submit">';
        $html .= hidden_input('_verif_envoi', 1);
        $html .= hidden_input('token', token_set());
        $html .= '<a class="btn btn-cancel" href="settings.php" >'.$GLOBALS['lang']['cancel'].'</a>';
        $html .= '<button class="btn btn-submit" type="submit" name="enregistrer">'.$GLOBALS['lang']['save'].'</button>';
    $html .= '</div>';

    $html .= '</form>';
 
    return $html;
}


/**
 * process
 */

require_once BT_ROOT.'inc/settings.php';

$errorsForm = array();
$message = '';

// proceed form
if (filter_input(INPUT_POST, '_verif_envoi') !== null) {
    // tests and sanitize
    $posted = settings_vhost_form_sanitize($_POST);

    // remove some datas
    $to_remove = array(
        'email', 'author', 'SITE_UID', 'site_description',
        'DBMS', 'DB_CHARSET', 'MYSQL_LOGIN', 'MYSQL_PASS', 'MYSQL_DB', 'MYSQL_HOST', 'MYSQL_PORT',
        'TOKEN_TTL', 'BT_SETTINGS_VERSION',
    );
    foreach ($to_remove as $key) {
        if (isset($posted['values'][$key])) {
            unset($posted['values'][$key]);
        }
        if (isset($posted['errors'][$key])) {
            unset($posted['errors'][$key]);
        }
    }

    // count errors
    $errors = count($posted['errors']);
    if ($errors === 0) {
        // to do show errors / success message
        if (settings_vhost_write($posted['values'])) {
            $message = 'Save';
        } else {
            $message = 'Fail to save new settings';
        }
    } else {
        $errorsForm = $posted['errors'];
        $message = 'Errors with the form datas';
    }
}


/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['settings'], false);
echo '<div id="axe">';
    echo '<div id="page">';
        echo settings_form($errorsForm);
    echo '</div>';
echo '</div>';
echo tpl_get_footer();
