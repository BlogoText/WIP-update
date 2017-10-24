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

/**
 * update from 3.8.0-dev to 3.8.1-dev
 */


/**
 * this function is used by the update system.
 */
$update_proceed = function ()
{
    $return = array(
        'success' => false,
        'messages' => array(),
        'errors' => array(),
        'version' => '3.8.2-dev',
    );

    // do you stuff...
    

    // if everything is fine...
    $return['success'] = true;

    return $return;
};


