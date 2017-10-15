<?php
// gzip compression
if (extension_loaded('zlib') and ob_get_length() > 0) {
    ob_end_clean();
    ob_start('ob_gzhandler');
} else {
    ob_start('ob_gzhandler');
}

header('Content-type: application/javascript; charset: UTF-8');

// css files
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
$js = '';

// for each file found
foreach ($files as $js_file) {
    // insert note about the current file
    $js .= '/* '.basename($js_file).' */'."\n";
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
    $js .= $js_file;
}

// remove comments
$js = preg_replace($pattern_comment, '', $js);
$js = preg_replace('!/\*.*?\*/!s', '', $js);
$js = preg_replace('/\n\s*\n/', "\n", $js);
$js = preg_replace('![ \t]*//.*[ \t]*[\r\n]!', '', $js);


// search and replace
foreach ($search_replace as $s => $r) {
    while (mb_strpos($js, $s)) {
        $js = str_replace($s, $r, $js);
    }
}

echo $js;
