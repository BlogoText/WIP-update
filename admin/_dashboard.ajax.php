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

include 'inc/boot.php';

header('Content-Type: application/json');

$posted = filter_var($HTTP_RAW_POST_DATA);
if ($posted === null) {
    $posted = file_get_contents('php://input');
}

if (empty($posted)) {
    echo json_encode(
            array(
                'success' => false,
                'message' => 'no datas'
            )
        );
    exit();
}

$datas = json_decode($posted, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(
            array(
                'success' => false,
                'message' => 'wrong format'
            )
        );
    exit();
}

// load dependancy
require_once BT_ROOT_ADMIN.'inc/auth.php';

$upd = user_upd($_SESSION['uid'], array('admin-home-settings' => json_encode(array_flip($datas))));

if ($upd) {
    echo json_encode(
        array(
            'success' => true,
            'message' => 'saved !',
            
        )
    );
    exit();
}

echo json_encode(
    array(
        'success' => false,
        'message' => 'Fail to save !',
        
    )
);
exit();
