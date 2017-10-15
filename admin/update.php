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
require_once BT_ROOT_ADMIN.'/inc/update.php';

$version_infos = update_get_last_release_infos();

if (is_array($version_infos)) {
    require_once BT_ROOT.'/inc/lib/Parsedown.php';
}

echo tpl_get_html_head('Update', false);
echo '<div id="axe">';
echo '<div id="page">';



if (!is_array($version_infos)) {
    echo '<h1>:/</h1>';
    echo '<div class="main-white">';
    echo '<p>Fail to get releases informations</p>';
    echo '</div>';
} else {
    echo '<div class="block_medium block-white block_legend">';
        echo '<legend>About the lastest version : ',$version_infos['tag_name'],'</legend>';
        echo '<h3>Current version is ', BLOGOTEXT_VERSION ,', ',update_count_release_diff(),' version(s) behind the latest.</h3>';
        echo '<p>BT ',$version_infos['tag_name'],' was created the ',$version_infos['created_at'],' and published the ',$version_infos['published_at'],'</p>';
        echo '<p>Release page : <a href="',$version_infos['html_url'],'">Github / BlogoText / ',$version_infos['target_commitish'],' / releases / ',$version_infos['tag_name'],'</a></p>';
        if (update_is_available()) {
            echo '<p class="btn-container"><input type="button" class="btn" value="Apply" /></p>';
        }
    echo '</div>';

    echo '<div class="block_medium block-white block_legend">';
        echo '<legend>',$version_infos['tag_name'],' note</legend>';
            $Parsedown = new Parsedown();
            echo $Parsedown->text($version_infos['body']);
    echo '</div>';

}

?>
<div id=""></div>
<script>
var upd_running = false;
function update_BT(btn)
{
    if (upd_running) {
        return true;
    }
    upd_running = true;

    // prepare XMLHttpRequest
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '_update.ajax.php', true);

    var glLength = 0;
    // feeds update gradualy. This counts the feeds that have been updated yet

    xhr.onprogress = function () {
        if (glLength != this.responseText.length) {
            // var posSpace = (this.responseText.substr(0, this.responseText.length-1)).lastIndexOf("\n");
            notifNode.textContent = this.responseText.substr(posSpace);
            // glLength = this.responseText.length;
        }
    }
    xhr.onload = function () {
        var resp = this.responseText;
        return false;
    };

    xhr.onerror = function () {
        notifNode.textContent = document.createTextNode(this.responseText);
    };

    xhr.send();
}
</script>
<?php

echo '</div>';
echo '</div>';

echo '</div>';
echo '</div>';
echo tpl_get_footer();
