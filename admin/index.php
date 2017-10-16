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
require BT_ROOT_ADMIN.'inc/update.php';

/**
 * Scale numeric values based on $maximum.
 */
function scaled_size($arr, $maximum)
{
    $return = array();
    if (!$arr) {
        return $return;
    }

    $ratio = max(array_values($arr)) / $maximum;
    if ($ratio <= 0) {
        $ratio = 1;
    }
    foreach ($arr as $key => $value) {
        $return[] = array('nb' => $value, 'nb_scale' => floor($value / $ratio), 'date' => $key);
    }

    return $return;
}

/**
 * Count the number of items into the DTB for the Nth last months.
 * Return an associated array: YYYYMM => number
 */
function get_tableau_date($dataType)
{
    $showMin = 12; // (int) minimal number of months to show
    $showMax = 24; // (int) maximal number of months to show
    $tableMonths = array();

    // Uniformize date format. YYYYMMDDHHIISS where DDHHMMSS is 00000000 (to match with the ID format which is \d{14})
    $min = date('Ym', mktime(0, 0, 0, date('m') - $showMax, 1, date('Y'))).'01000000';
    $max = date('Ymd').'235959';

    $btDate = ($dataType == 'articles') ? 'bt_date' : 'bt_id';

    $sql = '
        SELECT substr('.$btDate.', 1, 6) AS date, count(*) AS idbydate
          FROM '.$dataType.'
         WHERE '.$btDate.' BETWEEN '.$min.' AND '.$max.'
         GROUP BY date
         ORDER BY date';

    $req = $GLOBALS['db_handle']->prepare($sql);
    $req->execute();
    $tab = $req->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tab as $i => $month) {
        $tableMonths[$month['date']] = $month['idbydate'];
    }

    // Fill empty months
    for ($i = $showMin; $i >= 0; $i--) {
        $month = date('Ym', mktime(0, 0, 0, date('m') - $i, 1, date('Y')));
        if (!isset($tableMonths[$month])) {
            $tableMonths[$month] = 0;
        }
    }

    // order
    ksort($tableMonths);

    return $tableMonths;
}

/**
 * Display one graphic.
 */
function display_graph($arr, $title, $cls)
{
    $html = '<div class="graph">';
        $html .= '<div class="graph-container" id="graph-container-'.$cls.'">';
            $html .= '<canvas height="150" width="400"></canvas>';
            $html .= '<div class="graphique" id="'.$cls.'">';
                $html .= '<div class="month"><div class="month-bar"></div></div>';
                foreach ($arr as $data) {
                    $html .= '<div class="month">';
                        $html .= '<div class="month-bar" style="height:'.$data['nb_scale'].'px;margin-top:'.max(3 - $data['nb_scale'], 0).'px"></div>';
                        $html .= '<span class="month-nb">'.$data['nb'].'</span>';
                        $html .= '<a href="blog-articles.php?filtre='.$data['date'].'">';
                            $html .= '<span class="month-name">'.mb_substr(month_en_lettres(substr($data['date'], 4, 2)), 0, 3).'<br />'.substr($data['date'], 2, 2).'</span>';
                        $html .='</a>';
                    $html .= '</div>';
                }
            $html .= '</div>';
        $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function rss_count_feed()
{
    if (!$GLOBALS['use_feed_reader']) {
        return array();
    }

    $sql = '
        SELECT SUM(bt_statut) AS nbrun, SUM(bt_bookmarked) AS nbfav
          FROM rss';
    return $GLOBALS['db_handle']->query($sql)->fetchAll(PDO::FETCH_ASSOC)['0'];
}

/**
 * Process
 */
$query = (string)filter_input(INPUT_GET, 'q');
$searchCounter = array();
$this_user = user_get($_SESSION['uid']);

if ($query) {
    $query = htmlspecialchars($query);
    $searchCounter['post'] = db_items_list_count('SELECT count(ID) AS counter FROM articles WHERE ( bt_content || bt_title ) LIKE ?', array('%'.$query.'%'));
    $searchCounter['link'] = db_items_list_count('SELECT count(ID) AS counter FROM links WHERE ( bt_content || bt_title || bt_link ) LIKE ?', array('%'.$query.'%'));
    $searchCounter['comment'] = db_items_list_count('SELECT count(ID) AS counter FROM commentaires WHERE bt_content LIKE ?', array('%'.$query.'%'));
    $searchCounter['feed'] = db_items_list_count('SELECT count(ID) AS counter FROM rss WHERE ( bt_content || bt_title ) LIKE ?', array('%'.$query.'%'));
    $searchCounter['files'] = sizeof(liste_base_files('recherche', urldecode($query), ''));
} else {
    $numberOfPosts = db_items_list_count('SELECT count(ID) AS counter FROM articles', array());
    $numberOfLinks = db_items_list_count('SELECT count(ID) AS counter FROM links', array());
    $numberOfComments = db_items_list_count('SELECT count(ID) AS counter FROM commentaires', array());

    $posts = scaled_size(get_tableau_date('articles'), 150);
    $posts = array_reverse($posts);
    $links = scaled_size(get_tableau_date('links'), 150);
    $links = array_reverse($links);
    $comments = scaled_size(get_tableau_date('commentaires'), 150);
    $comments = array_reverse($comments);
}

/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['label_admin_home'], true);

echo '<div id="axe">';
echo '<div id="page">';


if ($query) {
    // Show search form
    echo '<div 
            class="form-inline txt-center input-large block_small" 
            style="margin-top:3em;margin-bottom:6em;">';
    echo '<div class="input">';
    echo tpl_get_search_form(false);
    echo '</div>';
    echo '</div>';

    // Show search results
    echo '<div class="form block_medium">';
    echo '<div role="group" class="block-white">';
    // echo '<legend>'.array_sum($searchCounter).' result(s) for <span style="font-style: italic">'.$query.'</span></legend>';
    echo '<legend>'.$GLOBALS['lang']['search_results'].' <span style="font-style: italic">'.$query.'</span></legend>';

    echo '<div class="txt-center">';
        echo '<a class="btn btn-flat"href="blog-articles.php?q='.$query.'">'.nombre_objets($searchCounter['post'], 'article').'</a>';
        echo '<a class="btn btn-flat" href="links.php?q='.$query.'">'.nombre_objets($searchCounter['link'], 'link').'</a>';
        echo '<a class="btn btn-flat" href="comments.php?q='.$query.'">'.nombre_objets($searchCounter['comment'], 'comment').'</a>';
        echo '<a class="btn btn-flat" href="files.php?q='.$query.'">'.nombre_objets($searchCounter['files'], 'fichier').'</a>';
        echo '<a class="btn btn-flat" href="feed.php?q='.$query.'">'.nombre_objets($searchCounter['feed'], 'feed_entry').'</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
} else {
    // Main Dashboard
    $order_list = array();
    $order = json_decode($this_user['admin-home-settings'], true);

    echo '<div id="grabGrid">';

    // user
    if (isset($order['home-me'])) {
        $order_list[$order['home-me']] = '<li data-id="home-me" draggable="true" >me</li>';
        $t_order = $order['home-me'];
        
    } else {
        $order_list[] = '<li data-id="home-me" draggable="true" >me</li>';
        $t_order = '';
    }
    echo '<div id="home-me" class="home-item grabGrid-item-size-1" style="order:'.$t_order.';">';
    echo '  <div class="home-item-text txt-center">';
    echo '    <div class="home-desc">Loggued as</div>';
    echo '    <div class="home-text"><strong>'.$this_user['pseudo'].'</strong></div>';
    // echo '    <div class="home-action"><a href="update.php" class="btn btn-dense btn-info">Mettre à jour</a></div>';
    echo '  </div>';
    echo '</div>';

    // update
    if (isset($order['home-update'])) {
        $order_list[$order['home-update']] = '<li data-id="home-update" draggable="true" >BT update</li>';
        $t_order = $order['home-update'];
        
    } else {
        $order_list[] = '<li data-id="home-update" draggable="true" >BT update</li>';
        $t_order = '';
    }
    if (update_is_available()) {
        echo '<div id="home-update" class="home-item grabGrid-item-size-1" style="order:'.$t_order.';">';
        echo '  <div class="home-item-number">';
        echo '    <div class="home-desc">BlogoText version</div>';
        echo '    <div class="home-number">3.8.0</div>';
        echo '    <div class="home-desc">disponible !</div>';
        echo '    <div class="home-action"><a href="update.php" class="btn btn-dense btn-info">Mettre à jour</a></div>';
        echo '  </div>';
        echo '</div>';
    } else {
        echo '<div id="home-update" class="home-item grabGrid-item-size-1" style="order:'.$t_order.';">';
        echo '  <div class="home-item-number">';
        echo '    <div class="home-desc">BlogoText à jour</div>';
        echo '    <div class="home-number">'.BLOGOTEXT_VERSION.'</div>';
        // echo '    <div class="home-action"><a href="update.php" class="btn btn-dense btn-info">Mettre à jour</a></div>';
        echo '  </div>';
        echo '</div>';
    }

    // feeds
    $feeds = rss_count_feed();
    if (isset($feeds['nbrun'])) {
        if (isset($order['home-feeds-items'])) {
            $order_list[$order['home-feeds-items']] = '<li data-id="home-feeds-items" draggable="true">Feed > non lus</li>';
            $t_order = $order['home-feeds-items'];
        } else {
            $order_list[] = '<li data-id="home-feeds-items" draggable="true">Feed > non lus</li>';
            $t_order = '';
        }
        echo '<div id="home-feeds-items" class="home-item grabGrid-item-size-1" style="order:'.$t_order.';">';
        echo '  <div class="home-item-number">';
        echo '    <div class="home-number">'.$feeds['nbrun'].'</div>';
        echo '    <div class="home-desc">Feed non lu</div>';
        echo '    <div class="home-action"><a href="" class="btn btn-dense btn-info">Voir</a></div>';
        echo '  </div>';
        echo '</div>';
    }
    if (isset($feeds['nbfav'])) {
        if (isset($order['home-feeds-favs'])) {
            $order_list[$order['home-feeds-favs']] = '<li data-id="home-feeds-favs" draggable="true">Feed > favoris</li>';
            $t_order = $order['home-feeds-favs'];
        } else {
            $order_list[] = '<li data-id="home-feeds-favs" draggable="true">Feed > favoris</li>';
            $t_order = '';
        }
        echo '<div id="home-feeds-favs" class="home-item grabGrid-item-size-1" style="order:'.$t_order.';">';
        echo '  <div class="home-item-number">';
        echo '    <div class="home-number">'.$feeds['nbfav'].'</div>';
        echo '    <div class="home-desc">Feed favoris</div>';
        echo '    <div class="home-action"><a href="" class="btn btn-dense btn-info">Voir</a></div>';
        echo '  </div>';
        echo '</div>';
    }

    /**
     * Show Graph
     */
    $tabs_head = array();
    $tabs_content = array();

    if ($numberOfPosts) {
        $tabs_head[] = '<li data-trigger="graphTabOnClick" data-target="#tab-post" class="active">'.$GLOBALS['lang']['label_articles'].'</li>';
        $tabs_content[] = '<div id="tab-post" class="tabs-content block-white active">'. display_graph($posts, $GLOBALS['lang']['label_articles'], 'posts') .'</div>';
    }
    if ($numberOfComments) {
        $tabs_head[] = '<li data-trigger="graphTabOnClick" data-target="#tab-comments">'.$GLOBALS['lang']['label_comments'].'</li>';
        $tabs_content[] = '<div id="tab-comments" class="tabs-content block-white">'. display_graph($comments, $GLOBALS['lang']['label_comments'], 'comments') .'</div>';
    }
    if ($numberOfLinks) {
        $tabs_head[] = '<li data-trigger="graphTabOnClick" data-target="#tab-links">'.$GLOBALS['lang']['label_links'].'</li>';
        $tabs_content[] = '<div id="tab-links" class="tabs-content block-white">'. display_graph($links, $GLOBALS['lang']['label_links'], 'links') .'</div>';
    }

    if (count($tabs_head) > 0) {
        if (isset($order['home-graph'])) {
            $order_list[$order['home-graph']] = '<li data-id="home-graph" draggable="true">Graphs</li>';
            $t_order = $order['home-graph'];
        } else {
            $order_list[] = '<li data-id="home-graph" draggable="true">Graphs</li>';
            $t_order = '';
        }
    }

    if (!max($numberOfPosts, $numberOfComments, $numberOfLinks)) {
        echo info($GLOBALS['lang']['note_no_article']);
    } else {
        echo '
            <div id="home-graph" class="grabGrid-item-size-4" style="order:'.$t_order.';">
                <div class="tabs">
                    <ul class="tabs-head">';
        foreach ($tabs_head as $tab_head) {
            echo $tab_head;
        }
        echo '
                    </ul>
                    <div class="tabs-contents">';
        foreach ($tabs_content as $tab_content) {
            echo $tab_content;
        }
        echo '
                    </div>
                </div>
            </div>';
    }
    // end of graph

    // show grid order list
    if (!empty($order_list)) {
        // add one last item for allowing to push an item to the bottom
        $order_list['99999999'] = '<li></li>';
        echo '</div>';

        echo '<div class="btn-container" style="position: relative;">';
            echo '<div id="grabOrder">';
                echo '<ul>';
                    ksort($order_list);
                    echo implode('', $order_list);
                echo '</ul>';
                echo '<p><button id="grabSetOrder" class="btn btn-dense btn-green ico-check" onClick="grabChangeOrder()">'. $GLOBALS['lang']['apply'] .'</button></p>';
            echo '</div>';
            echo '<button id="grabDisplayOrderChanger" class="btn btn-blue ico-settings" onClick="grabDisplayOrderChanger();"></button>';
        echo '</div>';
    }
}

?>
<script>
    var containers = document.querySelectorAll(".graph-container"),
        month_min_width = 40, // in px
        tabs = document.querySelectorAll(".tabs");
    homeBoot();
</script>
<?php

echo '</div>';
echo '</div>';
echo tpl_get_footer();
