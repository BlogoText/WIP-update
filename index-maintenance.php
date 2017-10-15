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
 * set headers
 */
header('Content-Type: text/html; charset=UTF-8');
header('HTTP/1.1 503 Service Unavailable');
header('Retry-After: 180');

?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maintenance</title>
</head>
<body>
    <div style="max-width:460px;margin:20px auto;padding:10px;">
        <p>Ce site est en cours de maintenance, merci de réessayer dans quelques minutes. Merci.</p>
        <p>This site is undergoing maintenance, please try again in a few minutes. Thank you.</p>
    </div>
</body>
</html>