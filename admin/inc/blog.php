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
 * action on db, delete, update, add blog article
 *
 * @params $post array, the datas
 * @params $action string, action type
 *               delete || insert || update
 * @return bool
 */
function blog_db($post, $action)
{
    $id = (string)filter_input(INPUT_POST, 'ID', FILTER_VALIDATE_INT);

    $datas = array_merge(
        array(
            // 'ID' => (is_int($id)) ? $id : '',   // INT
            'bt_type' => 'article',             // CHAR(20)
            'bt_id' => '',                      // BIGINT
            'bt_date' => '',                    // BIGINT
            'bt_title' => '',                   // TEXT
            'bt_abstract' => '',                // TEXT
            'bt_notes' => '',                   // TEXT
            'bt_link' => '',                    // TEXT
            'bt_content' => '',                 // TEXT
            'bt_wiki_content' => '',            // TEXT
            'bt_tags' => '',                    // TEXT
            'bt_keywords' => '',                // TEXT
            'bt_nb_comments' => 0,              // INTEGER
            'bt_allow_comments' => '',          // TINYINT
            'bt_statut' => '',                  // TINYINT
        ),
        $post
    );

    // add
    if ($action == 'enregistrer-nouveau') {
        $req = $GLOBALS['db_handle']->prepare('
            INSERT INTO articles
                    (   bt_type, bt_id, bt_date, bt_title,
                        bt_abstract, bt_notes, bt_link,
                        bt_content, bt_wiki_content, bt_tags, 
                        bt_keywords, bt_nb_comments,
                        bt_allow_comments, bt_statut
                    )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    // update
    } else if ($action == 'modifier-existant' && $id !== null) {
        $req = $GLOBALS['db_handle']->prepare('
            UPDATE articles
               SET bt_date = ?,
                   bt_title = ?,
                   bt_abstract = ?,
                   bt_notes = ?,
                   bt_link = ?,
                   bt_content = ?,
                   bt_wiki_content = ?,
                   bt_tags = ?,
                   bt_keywords = ?,
                   bt_allow_comments = ?,
                   bt_statut = ?
             WHERE ID = ?');
        // set correct datas
        unset($datas['bt_type'], $datas['bt_id'], $datas['bt_nb_comments']);
        $datas['ID'] = $id;

    // delete
    } else if ($action == 'supprimer-existant' && $id !== null) {
        $req = $GLOBALS['db_handle']->prepare('DELETE FROM articles WHERE ID = ?');
        // reformat datas array
        $datas = array('ID' => $id);
    } else {
        // log error
        log_error('Unknow action type');
        return false;
    }

    $result = $req->execute(array_values($datas));
    if (!$result) {
        log_error('Fail to write in db');
    }
    return $result;
}
