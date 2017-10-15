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

require_once 'inc/boot.php';

// dependancy
require_once BT_ROOT.'inc/addons.php';
require_once BT_ROOT_ADMIN.'inc/addons.php';


/**
 * process
 */
if (!empty($_GET['addon'])) {
    $addon = htmlspecialchars($_GET['addon'], ENT_QUOTES);
    if (!addon_test_exists($addon)) {
        $content = 'Addon doesn\'t exists';
    } else if (($path = addon_path_addon_admin($addon)) === false) {
        $content = 'Addon doesn\'t have an admin page';
    } else {
        include $path;
        $content = a_page_render();
    }
} else {
    $content = 'No addon selected !';
}


/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['my_addons'], false);
echo '<div id="axe">';
echo '<div id="page">';

echo '<div class="breadcrumb block-white">
        <ul>
            <li><a href="">Addons</a></li>
            <li><a href="">pages</a></li>
            <li><a href="">advanced</a></li>
        </ul>
    </div>';

echo $content;

echo '</div>';
echo '</div>';
echo tpl_get_footer();
