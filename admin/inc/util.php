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
 * to do, run somewhere
 */

// Create robots.txt file
function fichier_robots()
{
    $robots = BT_ROOT.'robots.txt';
    $content = "User-agent: *\n";
    $content .= "Disallow: /admin\n";
    $content .= "Sitemap: ".$GLOBALS['racine']."sitemap.php\n";
    return (file_put_contents($robots, $content, LOCK_EX) !== false);
}

