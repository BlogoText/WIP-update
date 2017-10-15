<?php
/**
 * This file is part of BlogoText.
 * https://github.com/BlogoText/blogotext/
 *
 * 2006      Frederic Nassar.
 * 2010-2016 Timo Van Neerden.
 * 2016-.... Mickaël Schoentgen and the community.
 * 2017-.... RemRem and the community.
 *
 * BlogoText is free software.
 * You can redistribute it under the terms of the MIT / X11 Licence.
 */

require_once 'inc/boot.php';


/**
 *
 */
function posts_list($arr)
{
    $out = '';
    if ($arr) {
        $out .= '<ul class="block-white" id="billets">';
        foreach ($arr as $post) {
            $title = str_replace(array("\r", "\n", "\t"), ' ' ,trim(htmlspecialchars(mb_substr(strip_tags(((empty($post['bt_abstract'])) ? $post['bt_content'] : $post['bt_abstract'])), 0, 249), ENT_QUOTES))).'…';
            $out .= '<li'.(($post['bt_date'] > date('YmdHis')) ? ' class="planned"' : '').'>';
                $out .= '<span class="'.(($post['bt_statut']) ? 'on' : 'off').'">';
                    $out .= '<a href="blog-write.php?post_id='.$post['bt_id'].'" title="'.$title.'">'.$post['bt_title'].'</a>';
                $out .= '</span>';
                $out .= '<span>';
                    $out .= '<a href="'.basename($_SERVER['SCRIPT_NAME']).'?filtre='.substr($post['bt_date'], 0, 8).'">';
                        $out .= date_formate($post['bt_date']);
                    $out .= '</a><span>, '.time_formate($post['bt_date']).'</span>';
                $out .= '</span>';
                $out .= '<span><a href="comments.php?post_id='.$post['bt_id'].'">'.$post['bt_nb_comments'].'</a></span>';
                $out .= '<span><a href="'.$post['bt_link'].'" title="'.$GLOBALS['lang'][(($post['bt_statut']) ? 'post_link' : 'preview')].'"></a></span>';
            $out .= '</li>';
        }
        $out .= '</ul>';
    } else {
        $out .= '<div class="txt-center">';
        $out .= info($GLOBALS['lang']['note_no_article']);
        $out .= '</div>';
    }
    $out .= '<a id="fab" class="add-article" href="blog-write.php" title="'.$GLOBALS['lang']['title_blog_write'].'">'.$GLOBALS['lang']['title_blog_write'].'</a>';

    return $out;
}


/**
 * process
 */

$tableau = array();
$query = (string)filter_input(INPUT_GET, 'q');
$filter = (string)filter_input(INPUT_GET, 'filtre');
$db_limit = ($GLOBALS['max_bill_admin'] > 0) ? 'LIMIT 0, '.$GLOBALS['max_bill_admin'] : '';

if ($query) {
    $arr = search_engine_parse_query($query);
    $sqlWhere = implode(array_fill(0, count($arr), '( bt_content || bt_title ) LIKE ?'), 'AND'); // AND operator between words
    $query = '
        SELECT *
          FROM articles
         WHERE '.$sqlWhere.'
         ORDER BY bt_date DESC';
    $tableau = db_items_list($query, $arr, 'articles');
} elseif ($filter) {
    // for "tags" the requests is "tag.$search" : here we split the type of search and what we search.
    $type = substr($filter, 0, -strlen(strstr($filter, '.')));
    $search = htmlspecialchars(ltrim(strstr($filter, '.'), '.'));

    if (preg_match('#^\d{6}(\d{1,8})?$#', $filter)) {
        $query = '
            SELECT *
              FROM articles
             WHERE bt_date LIKE ?
             ORDER BY bt_date DESC';
        $tableau = db_items_list($query, array($filter.'%'), 'articles');
    } elseif ($filter == 'draft' or $filter == 'pub') {
        $query = '
            SELECT *
              FROM articles
             WHERE bt_statut = ?
             ORDER BY bt_date DESC';
        $tableau = db_items_list($query, array((($filter == 'draft') ? 0 : 1)), 'articles');
    } elseif ($type == 'tag' and $search != '') {
        $query = '
            SELECT *
              FROM articles
             WHERE bt_tags LIKE ?
                   OR bt_tags LIKE ?
                   OR bt_tags LIKE ?
                   OR bt_tags LIKE ?
             ORDER BY bt_date DESC';
        $tableau = db_items_list($query, array($search, $search.',%', '%, '.$search, '%, '.$search.', %'), 'articles');
    } else {
        $query = '
            SELECT *
              FROM articles
             ORDER BY bt_date DESC '.$db_limit;
        $tableau = db_items_list($query, array(), 'articles');
    }
} else {
    $query = '
        SELECT *
          FROM articles
         ORDER BY bt_date DESC '.$db_limit;
    $tableau = db_items_list($query, array(), 'articles');
}


/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['my_articles'], true);

echo '<div id="axe">';
    echo '<div id="subnav">';
    echo tpl_filter_form('articles', htmlspecialchars($filter));
    echo tpl_items_counter('article', count($tableau), db_items_list_count('SELECT count(*) AS counter FROM articles', array()));
echo '</div>';

echo '<div id="page">';

echo posts_list($tableau);

echo <<<EOS
<script>
    var scrollPos = 0;
    window.addEventListener("scroll", function() { scrollingFabHideShow(); });
</script>
EOS;

echo '</div>';
echo '</div>';
echo tpl_get_footer();
