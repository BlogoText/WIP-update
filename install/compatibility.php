<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://blogotext.org/
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
 * test required extensions are loaded
 * double check with function_exists (DO NOT REMOVE !)
 */
$system = array(
    'test_php_version' => (int)(version_compare(PHP_VERSION, MINIMAL_PHP_REQUIRED_VERSION, '>')),
    'test_php_db' => (int)(extension_loaded('pdo_sqlite') || extension_loaded('pdo_mysql')),
    'test_path_write' => (int)(@is_writable('../')),
    'test_php_intl' => (int)(extension_loaded('intl') && function_exists('idn_to_utf8')),
    'test_php_xml' => (int)(extension_loaded('xml') && function_exists('simplexml_load_string')),
    'test_php_curl' => (int)extension_loaded('curl'),
    'test_php_gd' => (int)extension_loaded('gd'),
    'test_php_mbstring' => (int)(extension_loaded('mbstring') && function_exists('mb_internal_encoding')),
);

// var_dump($system);
$system_ct_test = count($system);
$system_ct_error = array_sum($system);

// if just intl fail, allow user to force installation
$system['can-be-forced'] = ($system_ct_error === ($system_ct_test-1) && $system['test_php_intl'] === 0);
// system ok, or intl forced
define('SYS_OK', (bool)($system_ct_error === $system_ct_test || ($system['can-be-forced'] && (isset($_REQUEST['force'])))));


// check system

if (!SYS_OK) {
    foreach ($system as $k => $v) {
        if (!is_int($v)) {
            continue;
        }
        if ($v === 0) {
            $system[$k] = 'is_ko';
            $system[$k.'_class'] = 'bck_is_ko';
        } else {
            $system[$k] = 'is_ok';
            $system[$k.'_class'] = '';
        }
    }

    if ($system['can-be-forced']) {
        $system['btn'] ='<a href="?force" class="btn btn-orange">{support_force}</a>';
    } else {
        $system['btn'] = '<a href="?" class="btn btn-blue">{support_refresh}</a>';
    }

    echo install_get_template('tpl-check-system', 'FR_fr', $system);
    exit();
}
