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

function comments_db_set_active($comment)
{
    try {
        $sql = '
            UPDATE commentaires
               SET bt_statut = ABS(bt_statut - 1)
             WHERE ID = ?';
        $req = $GLOBALS['db_handle']->prepare($sql);
        $req->execute(array($comment['ID']));
        // update counter
        comments_db_upd_counter($comment['bt_article_id']);
    } catch (Exception $e) {
        return 'Error : '.$e->getMessage();
    }
    return true;
}

/**
 * update a comment in database
 *
 * @params $comment array
 * @return true|string
 */
function comments_db_update($comment)
{
    try {
        $req = $GLOBALS['db_handle']->prepare('
            UPDATE commentaires
               SET bt_article_id = ?,
                   bt_content = ?,
                   bt_wiki_content = ?,
                   bt_author = ?,
                   bt_link = ?,
                   bt_webpage = ?,
                   bt_email = ?,
                   bt_subscribe = ?,
                   bt_statut = ?
             WHERE ID = ?');
        $req->execute(array(
            $comment['bt_article_id'],
            $comment['bt_content'],
            $comment['bt_wiki_content'],
            $comment['bt_author'],
            $comment['bt_link'],
            $comment['bt_webpage'],
            $comment['bt_email'],
            $comment['bt_subscribe'],
            $comment['bt_statut'],
            $comment['ID'],
        ));
        // update counter
        comments_db_upd_counter($comment['bt_article_id']);
    } catch (Exception $e) {
        return 'Error : '.$e->getMessage();
    }
    return true;
}

function comments_db_del($comment)
{
    try {
        $req = $GLOBALS['db_handle']->prepare('DELETE FROM commentaires WHERE ID=?');
        $req->execute(array($comment['ID']));
        // update counter
        comments_db_upd_counter($comment['bt_article_id']);
    } catch (Exception $e) {
        return 'Error : '.$e->getMessage();
    }
    return true;
}
