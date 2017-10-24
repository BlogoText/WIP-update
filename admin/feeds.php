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

require_once 'inc/boot.php';


$GLOBALS['max_rss_admin'] = 10;
$tableau = array();

// Show N items per page
$page = filter_input(INPUT_GET, 'p');

$arr = array();

// For a site?
$site = (string)filter_input(INPUT_GET, 'site');
$fold = (string)filter_input(INPUT_GET, 'fold');
$bookmarked = (filter_input(INPUT_GET, 'bookmarked') !== null);
$query = (string)filter_input(INPUT_GET, 'q');
$page_date = filter_input(INPUT_GET, 'date');
$item_id = filter_input(INPUT_GET, 'id');
$sqlWhere = '';
$sqlWhereDate = '';
$sqlWhereStatus = '';
$sqlOrder = 'DESC';
$paramUrl = '';
$btn_previous_page = '';
$btn_next_page = '';

if (!empty($page_date)) {
    if ($page == 'previous') {
        $search_sign = '<';
    } else if ($page == 'next') {
        $search_sign = '>=';
        $sqlOrder = 'ASC';
    }
    if (!empty($item_id)) {
        $sqlWhereDate = ' AND ((bt_date = '.$page_date.' AND ID '.$search_sign.' '.$item_id.') OR bt_date '.$search_sign.' '.$page_date.')';
    } else {
        $sqlWhereDate = ' AND bt_date '.$search_sign.' '.$page_date;
    }
}

if ($site) {
    $sqlWhere .= 'bt_feed LIKE ?';
    $arr[] = '%'.$site.'%';
    $paramUrl = 'site='.$site.'&';
} elseif ($fold) {
    $sqlWhere .= 'bt_folder LIKE ?';
    $arr[] = '%'.$fold.'%';
    $paramUrl = 'fold='.$fold.'&';
} elseif ($bookmarked) {
    if ($sqlWhere) {
        $sqlWhere .= ' AND ';
    }
    $sqlWhere .= 'bt_bookmarked = 1';
    $paramUrl = 'bookmarked&';
}

if ($query) {
    // Search "in:read"
    if (substr($query, -8) == ' in:read') {
        if ($sqlWhere) {
            $sqlWhere .= ' AND ';
        }
        $sqlWhereStatus = 'bt_statut = 0';
        $query = substr($query, 0, mb_strlen($query) - 8);
    }
    // Search "in:unread"
    if (substr($query, -10) == ' in:unread') {
        if ($sqlWhere) {
            $sqlWhere .= ' AND ';
        }
        $sqlWhereStatus = 'bt_statut = 1';
        $query = substr($query, 0, mb_strlen($query) - 10);
    }
    $criterias = search_engine_parse_query($query);
    if ($sqlWhere && $criterias) {
        $sqlWhere .= ' AND ';
    }
    // AND operator between words
    foreach ($criterias as $where) {
        $arr[] = $where;
        $sqlWhere .= '(bt_content || bt_title) LIKE ? AND ';
    }
    $sqlWhere = trim($sqlWhere, ' AND ');
} else {
    $sqlWhereStatus = ' AND (bt_statut = 1 OR bt_bookmarked = 1)';
}

$sql = '
    SELECT `ID`,`bt_id`,`bt_date`,`bt_title`,`bt_statut` 
      FROM rss 
      WHERE '. trim(trim($sqlWhereStatus, ' '), 'AND') .'
  ORDER BY bt_date '.$sqlOrder.', ID '.$sqlOrder;
$listBig = db_items_list($sql, $arr, 'rss');

// add 1 more than max_rss_admin, for detecting if there is a previous or next page
$sql = '
    SELECT * 
      FROM rss
     WHERE '. trim(trim($sqlWhere.$sqlWhereStatus.$sqlWhereDate, ' '), 'AND') .'
  ORDER BY bt_date '.$sqlOrder.', ID '.$sqlOrder.'
     LIMIT '.($GLOBALS['max_rss_admin'] + 1);

$tableau = db_items_list($sql, $arr, 'rss');

// using main SQL request, try to find previous/next page
$have_more = (count($tableau) === ($GLOBALS['max_rss_admin']+1));

if (isset($have_more)) {
    if ($sqlOrder == 'ASC') {
        unset($tableau['0']);
    } else {
        unset($tableau[$GLOBALS['max_rss_admin']]);
    }
}
// reverse order to respect time
if ($sqlOrder == 'ASC') {
    $tableau = array_reverse($tableau);
    $listBig = array_reverse($listBig);
}

if (is_array($tableau) && isset($tableau['0'])) {
    // get pagination
    $first_item = array_values($tableau)[0];
    $last_item = end($tableau);

    // detect previous / next page
    if ($sqlOrder == 'ASC') {
        if ($have_more) {
            $btn_next_page = '<li><a href="feeds.php?'.$paramUrl.'p=next&amp;date='.$first_item['bt_date'].'&amp;id='.$first_item['ID'].'">&gt;</a></li>';
        }
        $sql = '
            SELECT * FROM rss
             WHERE '. trim(trim($sqlWhere.$sqlWhereStatus, ' '), 'AND') .'
                    AND ((bt_date = '.$last_item['bt_date'].' AND ID < '.$last_item['ID'].') OR bt_date > '.$last_item['bt_date'].')
             ORDER BY bt_date DESC, ID DESC
             LIMIT 1';
        $t_sql = db_items_list($sql, $arr, 'rss');
        if (isset($t_sql['0'])) {
            $btn_previous_page = '<li><a href="feeds.php?'.$paramUrl.'p=previous&amp;date='.$last_item['bt_date'].'&amp;id='.$last_item['ID'].'">&lt;</a></li>';
        }
    } else {
        if ($have_more) {
            $btn_previous_page =
                '<li><a href="feeds.php?'.$paramUrl.'p=previous&amp;date='.$last_item['bt_date'].'&amp;id='.$last_item['ID'].'">&lt;</a></li>';
        }
        $sql = '
            SELECT * FROM rss
             WHERE '. trim(trim($sqlWhere.$sqlWhereStatus, ' '), 'AND') .'
                    AND ((bt_date = '.$first_item['bt_date'].' AND ID > '.$first_item['ID'].') OR bt_date > '.$first_item['bt_date'].')
             ORDER BY bt_date DESC, ID DESC
             LIMIT 1';
        $t_sql = db_items_list($sql, $arr, 'rss');
        if (isset($t_sql['0'])) {
            $btn_next_page = '<li><a href="feeds.php?'.$paramUrl.'p=next&amp;date='.$first_item['bt_date'].'&amp;id='.$first_item['ID'].'">&gt;</a></li>';
        }
    }
} else {
    // no datas ...
}

$ids = array_column($tableau, 'ID');

echo '<ul>'.$btn_previous_page.$btn_next_page.'</ul>';
/**
 * echo
 */
$i = 1;
echo '<table>';

foreach ($listBig as $item) {
    echo '<tr style="background-color:', (in_array($item['ID'], $ids) ? 'green' : '') ,'">';
        echo '<td>',$i,'</td>';
        echo '<td>',$item['ID'],'</td>';
        echo '<td>', (in_array($item['ID'], $ids) ? '+' : '') ,'</td>';
        echo '<td>',$item['ID'],'</td>';
        echo '<td>',$item['bt_date'],'</td>';
        echo '<td>',$item['bt_title'],'</td>';
        echo '<td>',$item['bt_statut'],'</td>';
    echo '</tr>';
    ++$i;
}
echo '</table>';
