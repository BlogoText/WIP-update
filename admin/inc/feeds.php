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
 * Save one feed into the database.
 */
function bdd_rss($flux, $what)
{
    if ($what == 'enregistrer-nouveau') {
        $GLOBALS['db_handle']->beginTransaction();
        $req = $GLOBALS['db_handle']->prepare('
            INSERT INTO rss
                    (   bt_id,
                        bt_date,
                        bt_title,
                        bt_link,
                        bt_feed,
                        bt_content,
                        bt_statut,
                        bt_bookmarked,
                        bt_folder
                    )
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');

        foreach ($flux as $post) {
            $ret = $req->execute(array(
                $post['bt_id'],
                $post['bt_date'],
                $post['bt_title'],
                $post['bt_link'],
                $post['bt_feed_url'],
                $post['bt_content'],
                1,
                0,
                $post['bt_folder']
            ));
            if (!$ret) {
                log_error($post['bt_feed_url']);
            }
        }
        return $GLOBALS['db_handle']->commit();
    }
}

/**
 * Retrieve all the feeds, returns the amount of new elements.
 */
function feeds_refresh_rss($feeds)
{
    $guids = feeds_list_guid();
    $numberOfNewItems = 0;

    $items = feeds_retrieve_new_feeds(array_keys($feeds));
    if (!$items) {
        return 0;
    }

    foreach ($items as $feedUrl => $feedItems) {
        $dump = (strpos($feedUrl, 'framablog.org') !== false);
        if (!$feedItems) {
            continue;
        }

        // there are new posts in the feed (md5 test on feed content file is positive). Now test on each post.
        // only keep new post that are not in DB (in $guids) OR that are newer than the last post ever retreived.
        foreach ($feedItems['items'] as $key => $item) {
            if ((in_array($item['bt_id'], $guids)) or ($item['bt_date'] <= $feeds[$feedUrl]['time'])) {
                unset($feedItems['items'][$key]);
            }
            // only save elements that are more recent
            // we save the date of the last element on that feed
            // we do not use the time of last retreiving, because it might not be correct due to different time-zones with the feeds date.
            if ($item['bt_date'] > $GLOBALS['feeds_list'][$feeds[$feedUrl]['link']]['time']) {
                $GLOBALS['feeds_list'][$feeds[$feedUrl]['link']]['time'] = $item['bt_date'];
            }
        }

        $newItems = array();
        foreach ($feedItems['items'] as $key => $item) {
            $newItems[$key] = $item;
        }
        // if list of new elements is !empty, save new elements
        $numberOfItems = count($newItems);
        if ($numberOfItems > 0) {
            $ret = bdd_rss($newItems, 'enregistrer-nouveau');
            if (!$ret) {
                log_error($newItems);
            } else {
                $numberOfNewItems += $numberOfItems;
            }
        }
    }

    file_put_array(FILE_VHOST_FEEDS_DB, $GLOBALS['feeds_list']);
    return $numberOfNewItems;
}

/**
 * Return the list of GUID in whole DTB.
 */
function feeds_list_guid()
{
    return $GLOBALS['db_handle']->query('SELECT bt_id FROM rss')->fetchAll(PDO::FETCH_COLUMN, 0);
}

/**
 *
 */
function feeds_retrieve_new_feeds($feedLinks, $md5 = '')
{
    if (!$feeds = download_get($feedLinks, 25, true)) {
        // Timeout = 25s
        return false;
    }
    $return = array();
    foreach ($feeds as $url => $response) {
        if (empty($response['body'])) {
            continue;
        }

        $newMd5 = md5($response['body']);
        // if feed has changed: parse it (otherwise, do nothing: no need)
        if ($md5 != $newMd5 || $md5 == '') {
            $data = feeds_feed2array($response['body'], $url);
            if ($data) {
                $return[$url] = $data;
                $data['infos']['md5'] = $md5;
                // update RSS last successfull update MD5
                $GLOBALS['feeds_list'][$url]['checksum'] = $newMd5;
                $GLOBALS['feeds_list'][$url]['iserror'] = 0;
            } elseif (isset($GLOBALS['feeds_list'][$url])) {
                // error on feed update (else would be on adding new feed)
                $GLOBALS['feeds_list'][$url]['iserror'] += 1;
            }
        }
    }

    if ($return) {
        return $return;
    }

    return false;
}

/**
 * Based upon Feed-2-array, by bronco@warriordudimanche.net
 */
function feeds_feed2array($feedContent, $feedlink)
{
    $flux = array('infos'=>array(),'items'=>array());

    if (preg_match('#<rss(.*)</rss>#si', $feedContent)) {
        // RSS
        $flux['infos']['type'] = 'RSS';
    } elseif (preg_match('#<feed(.*)</feed>#si', $feedContent)) {
        // ATOM
        $flux['infos']['type'] = 'ATOM';
    } else {
        return false;
    }

    try {
        @$feedObject = new SimpleXMLElement($feedContent, LIBXML_NOCDATA);
    } catch (Exception $e) {
        return false;
    }

    $flux['infos']['version'] = $feedObject->attributes()->version;
    if (!empty($feedObject->attributes()->version)) {
        $flux['infos']['version'] = (string)$feedObject->attributes()->version;
    }
    if (!empty($feedObject->channel->title)) {
        $flux['infos']['title'] = (string)$feedObject->channel->title;
    }
    if (!empty($feedObject->channel->subtitle)) {
        $flux['infos']['subtitle'] = (string)$feedObject->channel->subtitle;
    }
    if (!empty($feedObject->channel->link)) {
        $flux['infos']['link'] = (string)$feedObject->channel->link;
    }
    if (!empty($feedObject->channel->description)) {
        $flux['infos']['description'] = (string)$feedObject->channel->description;
    }
    if (!empty($feedObject->channel->language)) {
        $flux['infos']['language'] = (string)$feedObject->channel->language;
    }
    if (!empty($feedObject->channel->copyright)) {
        $flux['infos']['copyright'] = (string)$feedObject->channel->copyright;
    }

    if (!empty($feedObject->title)) {
        $flux['infos']['title'] = (string)$feedObject->title;
    }
    if (!empty($feedObject->subtitle)) {
        $flux['infos']['subtitle'] = (string)$feedObject->subtitle;
    }
    if (!empty($feedObject->link)) {
        $flux['infos']['link'] = (string)$feedObject->link;
    }
    if (!empty($feedObject->description)) {
        $flux['infos']['description'] = (string)$feedObject->description;
    }
    if (!empty($feedObject->language)) {
        $flux['infos']['language'] = (string)$feedObject->language;
    }
    if (!empty($feedObject->copyright)) {
        $flux['infos']['copyright'] = (string)$feedObject->copyright;
    }

    if (!empty($feedObject->channel->item)) {
        $items = $feedObject->channel->item;
    }
    if (!empty($feedObject->entry)) {
        $items = $feedObject->entry;
    }
    if (empty($items)) {
        return $flux;
    }

    foreach ($items as $item) {
        $c = count($flux['items']);
        if (!empty($item->title)) {
            $flux['items'][$c]['bt_title'] = html_entity_decode((string)$item->title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } else {
            $flux['items'][$c]['bt_title'] = '-';
        }
        if (!empty($item->link['href'])) {
            $flux['items'][$c]['bt_link'] = (string)$item->link['href'];
        }
        if (!empty($item->link)) {
            $flux['items'][$c]['bt_link'] = (string)$item->link;
        }

        if (!empty($item->guid)) {
            $flux['items'][$c]['bt_id'] = (string)$item->guid;
        } elseif (!empty($item->id)) {
            $flux['items'][$c]['bt_id'] = (string)$item->id;
        } else {
            $flux['items'][$c]['bt_id'] = microtime();
        }

        if (empty($flux['items'][$c]['bt_link'])) {
            $flux['items'][$c]['bt_link'] = $flux['items'][$c]['bt_id'];
        }

        if (!empty($item->pubDate)) {
            $flux['items'][$c]['bt_date'] = (string)$item->pubDate;
        }
        if (!empty($item->published)) {
            $flux['items'][$c]['bt_date'] = (string)$item->published;
        }

        if (!empty($item->subtitle)) {
            $flux['items'][$c]['bt_content'] = (string)$item->subtitle;
        }
        if (!empty($item->description)) {
            $flux['items'][$c]['bt_content'] = (string)$item->description;
        }
        if (!empty($item->summary)) {
            $flux['items'][$c]['bt_content'] = (string)$item->summary;
        }
        if (!empty($item->content)) {
            $flux['items'][$c]['bt_content'] = (string)$item->content;
        }

        if (!empty($item->children('content', true)->encoded)) {
            $flux['items'][$c]['bt_content'] = (string)$item->children('content', true)->encoded;
        }

        if (!isset($flux['items'][$c]['bt_content'])) {
            $flux['items'][$c]['bt_content'] = '';
        }

        if (!isset($flux['items'][$c]['bt_date'])) {
            if (!empty($item->updated)) {
                $flux['items'][$c]['bt_date'] = (string)$item->updated;
            }
        }
        if (!isset($flux['items'][$c]['bt_date'])) {
            if (!empty($item->children('dc', true)->date)) {
                $flux['items'][$c]['bt_date'] = (string)$item->children('dc', true)->date;
            }
        }

        if (!empty($flux['items'][$c]['bt_date'])) {
            $flux['items'][$c]['bt_date'] = strtotime($flux['items'][$c]['bt_date']);
        } else {
            $flux['items'][$c]['bt_date'] = time();
        }

        $flux['items'][$c]['bt_feed_url'] = $feedlink;
        $flux['items'][$c]['bt_folder'] = (isset($GLOBALS['feeds_list'][$feedlink]['folder'])) ? $GLOBALS['feeds_list'][$feedlink]['folder'] : '';
    }

    return $flux;
}
