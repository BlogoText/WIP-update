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

$version = filter_input(INPUT_GET, 'v', FILTER_SANITIZE_SPECIAL_CHARS);

// make sure of use ?v=... in URL
if ($version === null
 || !preg_match('/^\d{1}\.\d{1,2}\.\d{1,2}(\-dev)?$/', $version)
) {
    header('HTTP/1.1 400 BAD REQUEST');
    die;
}


// gzip compression
if (extension_loaded('zlib') and ob_get_length() > 0) {
    ob_end_clean();
    ob_start('ob_gzhandler');
} else {
    ob_start('ob_gzhandler');
}

header('Content-type: application/javascript; charset: UTF-8');

/**
 * put cache to /var/000_common/cache/
 */
$cache_path = '../../../var/000_common/cache/';
$cache_file = $cache_path.'admin-cached-'.$version.'.js.php';
// check if cache exists
if (file_exists($cache_file)) {
    $cached = file_get_contents($cache_file);
    if ($cached !== false && strpos($cached, '<?php die(); ?>') !== false) {
        echo substr($cached, 15);
        die;
    }
}

// js files
$files = glob('*.js');

// search => replace
$search_replace = array(
        // first, remove line break, tabs
        "\r\n"  => "\n",
        "\r"    => "\n",
        "\t"    => ' ',

        // handle ","
        ' ,'    => ',',
        ', '    => ',',
        "\n "   => "\n",
        ",\n"   => ',',

        // handle ":"
        ': '    => ':',
        ' :'    => ':',
        "\n "   => "\n",

        // handle "{" and "}"
        '} '    => '}',
        ' }'    => '}',
        "\n}"   => '}',
        '{ '    => '{',
        ' {'    => '{',
        "\n "   => "\n",
        "{\n"   => '{',
        "\n{"   => '{',

        // handle ";"
        '; '    => ';',
        ' ;'    => ';',
        "\n "   => "\n",
        ";\n"   => ';',
        "\n."   => '.',

        // handle "(" and ")"
        ' )'    => ')',
        '( '    => '(',

        // remove useless spaces
        '  '    => ' ',
        "\n "   => "\n",
        "\n\n"  => "\n",

        ' = ' => '=',
        ' + ' => '+',
        'if ('  => 'if(',
        'for ('  => 'for(',
        'function (' => 'function(',
    );

// comment regex
$pattern_comment = '#/\*.*?\*/#s';

// final js
$content = '';

// for each file found
foreach ($files as $js_file) {
    // insert note about the current file
    $content .= '/* '.basename($js_file).' */'."\n";
    // load file
    if (!is_file($js_file)) {
        continue;
    }
    $js_file = file_get_contents($js_file);
    if (!$js_file) {
        continue;
    }

    // prevent file hack
    if (strpos('<?', $js_file) !== false) {
        continue;
    }
    // $css_file .= strip_tags($css_file);
    $content .= $js_file;
}

// remove comments
$content = preg_replace($pattern_comment, '', $content);
$content = preg_replace('!/\*.*?\*/!s', '', $content);
$content = preg_replace('/\n\s*\n/', "\n", $content);
$content = preg_replace('![ \t]*//.*[ \t]*[\r\n]!', '', $content);


// search and replace
foreach ($search_replace as $s => $r) {
    while (mb_strpos($content, $s)) {
        $content = str_replace($s, $r, $content);
    }
}

// put in cache
if (is_dir($cache_path)) {
    file_put_contents(
            $cache_file,
            '<?php die(); ?>'
                '"use strict";'."\n"
                .$content
        );
}

echo $content;
