<?php

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

header('Content-type: text/css; charset: UTF-8');


$no_cache = (strpos($version, '-') !== false);
// check if cache exists
if (file_exists('cache/cached-'.$version.'.css') && !$no_cache) {
    echo file_get_contents('cache/cached-'.$version.'.css');
    die;
}

// css files
$files = glob('*.css');
// custom file
// $files[] = '../../../var/000_common/style/admin-custom-styles.css';

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

        // handle "(" and ")"
        ' )'    => ')',
        '( '    => '(',

        // convert "0px" => "0"
        ' 0px' => ' 0',
        ':0px ' => ':0 ',

        // remove useless spaces
        '  '    => ' ',
    );

// comment regex
$pattern_comment = '#/\*.*?\*/#s';

// final css
$content = '';

// for each file found
foreach ($files as $file) {
    // insert note about the current file
    $content .= '/* '.basename($file).' */'."\n";
    // load file
    if (!is_file($file)) {
        continue;
    }
    $t_content = file_get_contents($file);
    if (!$t_content) {
        continue;
    }

    // prevent file hack
    if (strpos('<?', $t_content) !== false
     || stripos($t_content, '@import') !== false
     || stripos($t_content, '@require') !== false
    ) {
        continue;
    }
    $content .= strip_tags($t_content);
}

// remove comments
$content = preg_replace($pattern_comment, '', $content);

// color #ffffff > #fff
$content = preg_replace("/#([0-9a-fA-F])\\1([0-9a-fA-F])\\2([0-9a-fA-F])\\3/", "#$1$2$3", $content);

// search and replace
if (function_exists('mb_strpos')) {
    foreach ($search_replace as $s => $r) {
        while (mb_strpos($content, $s)) {
            $content = str_replace($s, $r, $content);
        }
    }
}

// fix !important
$content = str_replace('!important', ' !important', $content);
$content = str_replace('  !important', ' !important', $content);

// last cleanup
$content = str_replace(array("\r", "\n"), '', $content);

// put in cache
if (!$no_cache) {
    file_put_contents('cache/cached-'.$version.'.css', '@charset "utf-8";'."\n".$content);
}

echo '@charset "utf-8";'."\n";
echo $content;
