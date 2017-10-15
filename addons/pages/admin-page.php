<?php

/**
 * 
 */
function a_page_render()
{
    $html = '';
    $posted = array();

    // proceed page form
    if (isset($_POST['addon_submit'])) {
        
    }

    // get form
    $html = a_page_form($posted);

    return $html;
}

/**
 * get the form
 */
function a_page_form($datas = array())
{
    $html = '
        <form class="form block-white">';

    // page title
    $html .= '
            <div class="input">
                <input id="a_title" type="text" name="title" />
                <label for="a_title">Title</label>
            </div>
        ';

    // content
    $html .= '
            <div class="input">
                '.form_bb_toolbar(true).'
                <textarea id="a_content" rows="8" name="content"></textarea>
                <label for="a_content">Content</label>
            </div>
        ';

    $html .= '
            <div class="showhide">
                <div class="header">
                    SEO
                    <a href="#" class="btn btn-flat ico-bracketLeft" onclick="showhide(this);return false"></a>
                </div>
                <div class="content">';

        // custom url ?p=example-url
        $html .= '
                    <div class="input">
                        <input id="a_url" type="text" name="url" />
                        <label for="a_url">URL</label>
                    </div>
                    <div class="tips">
                        Keep it empty to let the addon convert your title to an URL.
                    </div>
        ';

        // SEO
        $html .= '
                    <div class="checkboxs-inline">
                        <label>Robots</label>
                        <div class="input">
                            <input class="checkbox" type="checkbox" name="index">
                            <label>index</label>
                        </div>
                        <div class="input">
                            <input class="checkbox" type="checkbox" name="follow">
                            <label>follow</label>
                        </div>
                        <div class="input">
                            <input class="checkbox" type="checkbox" name="archive">
                            <label>archive</label>
                        </div>
                        <div class="input">
                            <input class="checkbox" type="checkbox" name="snippet">
                            <label>snippet</label>
                        </div>
                        <div class="input">
                            <input class="checkbox" type="checkbox" name="noodp">
                            <label>noodp</label>
                        </div>
                    </div>
        ';

        // light description
        $html .= '
                    <div class="input">
                        <textarea id="a_description" name="description"></textarea>
                        <label for="a_description">Description</label>
                    </div>
                    <div class="tips">
                        Used for HTML &lt;meta description ...
                    </div>
        ';

    $html .= '
                </div>
            </div>';

    // buttons
    $html .= '
            <div class="btn-container">
                <a href="" class="btn btn-cancel">cancel</a>
                <input type="submit" value="Save" class="btn btn-submit" />
            </div>
        ';

    $html .= '
        </form>';

    return $html;
}
