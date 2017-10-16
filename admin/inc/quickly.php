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
 * push a quickly into db
 *
 * @params $quickly array
 * @return bool
 */
function quickly_db_push($quickly)
{
    $req = $GLOBALS['db_handle']->prepare('
        INSERT INTO links
                (
                    bt_type,
                    bt_id,
                    bt_content,
                    bt_wiki_content,
                    bt_title,
                    bt_link,
                    bt_tags,
                    bt_statut
                )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)');

    $quickly = pdo_mb4_cleaner($quickly);

    return $req->execute(
        array(
            $quickly['bt_type'],
            $quickly['bt_id'],
            $quickly['bt_content'],
            $quickly['bt_wiki_content'],
            $quickly['bt_title'],
            $quickly['bt_link'],
            $quickly['bt_tags'],
            $quickly['bt_statut']
        )
    );
}

/**
 * upd a quickly into db
 *
 * @params $quickly array
 * @return bool
 */
function quickly_db_upd($quickly)
{
    $req = $GLOBALS['db_handle']->prepare('
         UPDATE links
            SET bt_content = ?,
                bt_wiki_content = ?,
                bt_title = ?,
                bt_link = ?,
                bt_tags = ?,
                bt_statut = ?
          WHERE ID = ?');

    $quickly = pdo_mb4_cleaner($quickly);

    return $req->execute(
        array(
            $quickly['bt_content'],
            $quickly['bt_wiki_content'],
            $quickly['bt_title'],
            $quickly['bt_link'],
            $quickly['bt_tags'],
            $quickly['bt_statut'],
            $quickly['ID']
        )
    );
}

/**
 * delete a quickly
 *
 * @params $id int
 * @return bool
 */
function quickly_db_del($id)
{
    $sql = '
        DELETE FROM links
         WHERE ID = ?';
    $req = $GLOBALS['db_handle']->prepare($sql);

    return $req->execute(array($id));
}

/**
 * To add a lik in two steps:
 *   1) a link is given => display form (link + title)
 *   2) then, a desription and add to the DTB
 */
/*
function quickly_form_proceed($link)
{
    if (filter_input(INPUT_POST, 'enregistrer') !== null) {
        $result = quickly_db_push($link);
        $redir = basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_link_ajout';
    } elseif (filter_input(INPUT_POST, 'editer') !== null) {
        $result = quickly_db_upd($link);
        $redir = basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_link_edit';
    } elseif (filter_input(INPUT_POST, 'supprimer') !== null) {
        $result = quickly_db_del($link['ID']);
        $redir = basename($_SERVER['SCRIPT_NAME']).'?msg=confirm_link_suppr';
    }

    if (isset($result) && $result === true) {
        file_cache_lv1_refresh();
        redirection($redir);
    }

    die($result);
}
*/

/**
 * default form keyz
 *
 * @return array
 */
function quickly_form_default()
{
    return array(
        'ID' => '',
        'bt_id' => '',
        'bt_type' => '',
        'bt_wiki_content' => '',
        'bt_title' => '',
        'bt_link' => '',
        'bt_tags' => '',
        'bt_statut' => '',
        'action' => '',
        // for 1st step
        'quickly' => '',
    );
}

/**
 * sanitize a quickly form datas
 *
 * @params array $posted
 * @return array
 */
function quickly_form_sanitize($posted)
{
    $default = quickly_form_default();
    $return = form_basic_check($default, $posted);

    // var_dump($return);

    if (!empty($return['values']['ID'])) {
        if (!preg_match('#^\d+$#', $return['values']['ID'])) {
            $return['errors']['ID'] = 'filter';
        }
    }
    if (!empty($return['values']['bt_id'])) {
        if (!preg_match('#^\d{14}$#', $return['values']['bt_id'])) {
            $return['errors']['bt_id'] = 'filter';
        }
    }

    // need htmlspecialchars
    $keys = array(
                'bt_type', 'bt_wiki_content', 'bt_title',
                'bt_link', 'bt_tags'
            );
    foreach ($keys as $key) {
        if (!empty($return['values'][$key])) {
            $return['values'][$key] = htmlspecialchars($return['values'][$key]);
        }
    }

    $action_allowed = array('edit', 'create', 'delete', 'step-1');
    if (!empty($return['values']['action'])) {
        if (!in_array($return['values']['action'], $action_allowed)) {
            $return['errors']['action'] = 'not allowed';
        }
    }

    // if some case, missings are not really an error
    if (!isset($return['errors']['action'])) {
        if ($return['values']['action'] == 'step-1') {
            $errors_allowed = array(
                                'ID', 'bt_id', 'bt_type', 'bt_wiki_content',
                                'bt_title', 'bt_link', 'bt_tags', 'bt_statut'
                            );
        } else if (
            $return['values']['action'] == 'edit'
         || $return['values']['action'] == 'step-2'
        ) {
            $errors_allowed = array('quickly', 'bt_wiki_content', 'bt_tags');
        }
    }

    // handle bt_status (private share)
    $return['values']['bt_statut'] = (int)(isset($return['errors']['bt_statut']) && $return['errors']['bt_statut'] = 'empty');
    $errors_allowed[] = 'bt_statut';

    if (isset($errors_allowed)) {
        // clean up
        foreach ($errors_allowed as $error) {
            if (isset($return['errors'][$error])) {
                unset($return['errors'][$error]);
            }
        }
    }

    // there is really any error ?
    if (isset($return['errors']) && count($return['errors']) === 0) {
        unset($return['errors']);
    }

    return $return;
}

/**
 * Add a link from BO
 */
function quickly_form($datas)
{
    // define form action
    $action = '';
    // where the HTML form will focus
    $focus = '';
    // var_dump($datas);

    if (!empty($datas['values']['ID'])) {
        $action = 'edit';
        $focus = 'bt_title';
    } else if (
        !empty($datas['values']['bt_title'])
     || !empty($datas['values']['bt_wiki_content'])
    ) {
        $focus = empty($datas['values']['bt_title']) ? 'bt_title' : 'bt_wiki_content';
        $action = 'step-2';
    } else {
        $action = 'step-1';
    }

    $html = '<form method="post" class="form form-condensed quicky-type-'.$datas['values']['bt_type'].' block-white block_medium block_legend" onsubmit="return moveTag();" id="post-lien" action="links.php?id='.$datas['values']['bt_id'].'">';

    if ($action == 'step-1') {
        $html .= '<legend>Quickly</legend>';
        $html .= '<div id="quickly_first_step">';
            $html .= '<div class="input">';
                $html .= '<textarea id="testttt" class="text" rows="1" name="quickly">'.$datas['values']['quickly'].'</textarea>';
                $html .= '<div class="tips" id="testtttt"></div>';
            $html .= '</div>';
        $html .= '</div>';

    } else {
        $html .= '<legend>Quickly</legend>';
        if ($datas['values']['bt_type'] == 'image') {
            $html .= '<div id="quickly-preview">
                        <img src="'.$datas['values']['bt_link'].'" />
                    </div>';
        }

        $html .= '<div id="quickly-form">';

        // is a url share ?
        if (!empty($datas['values']['bt_link'])) {
            $html .= '<div class="input">';
                $html .= '<input type="text" name="bt_link" placeholder="URL" value="'.$datas['values']['bt_link'].'" size="70" class="text" readonly />';
                $html .= '<label>Url To share</label>';
            $html .= '</div>';
        }

        $html .= '<div class="input">';
            $html .= '<input type="text" name="bt_title" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_title']).'" value="'.$datas['values']['bt_title'].'" size="70" class="text" '. (($focus == 'bt_title') ? 'autofocus' : '') .' />';
            $html .= '<label>Title</label>';
        $html .= '</div>';

        $html .= '<div class="input">';
            $html .= '<div id="description-box">';
                $html .= '<textarea class="description text" name="bt_wiki_content" cols="70" rows="7" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_description']).'" '. (($focus == 'bt_wiki_content') ? 'autofocus ' : '') .'>'.$datas['values']['bt_wiki_content'].'</textarea>';
            $html .= '</div>';
        $html .= '</div>';

        // $html .= '<div id="tag_bloc" class="input input-tags">';
            $html .= tags_form('links', $datas['values']['bt_tags']);
            // $html .= '<input list="htmlListTags" type="text" class="text" id="type_tags" name="bt_tags" placeholder="'.ucfirst($GLOBALS['lang']['placeholder_tags']).'"/>';
            // $html .= '<input type="hidden" id="categories" name="categories" value="" />';
        // $html .= '</div>';

        $html .= '<div class="input">';
            $html .= '<input id="bt_statut" type="checkbox" name="bt_statut" class="checkbox" '.(($datas['values']['bt_statut'] == 0) ? 'checked' : '').'/>';
            $html .= '<label for="bt_statut">'.$GLOBALS['lang']['label_link_private'].'</label>';
        $html .= '</div>';
        $html .= '</div>';
    }

        $html .= hidden_input('ID', $datas['values']['ID']);
        $html .= hidden_input('bt_id', $datas['values']['bt_id']);
        $html .= hidden_input('token', token_set());
        $html .= hidden_input('bt_type', $datas['values']['bt_type']);
        $html .= hidden_input('action', $action);

        $html .= '<p class="btn-container">';
            if (!empty($datas['values']['ID'])) {
                $html .= '<button class="btn btn-delete" type="button" name="supprimer" onclick="rmArticle(this)">'.$GLOBALS['lang']['delete'].'</button>';
            }
            $html .= '<button class="btn btn-cancel" type="button" onclick="redirection(\'links.php\');">'.$GLOBALS['lang']['cancel'].'</button>';
            $html .= '<button class="btn btn-submit" type="submit" name="editer">'.$GLOBALS['lang']['send'].'</button>';
        $html .= '</p>';
    $html .= '</form>';

    return $html;
}

function is_img($path)
{
    $ext = substr($path, -4, 4);
    $ext = strtolower($ext);
    return (bool)(
        $ext == '.jpg'
     || $ext == '.gif'
     || $ext == '.png'
     || $ext == '.svg'
    );
}

/**
 * Link template.
 */
function quickly_show_item($link)
{
    $html = '<div class="linkbloc block-white '.((!$link['bt_statut']) ? ' privatebloc' : '').' linktype-'.$link['bt_type'].'">';
        $html .= '<div class="link-header">';
            $html .= '<a class="title-lien" href="'.$link['bt_link'].'">'.$link['bt_title'].'</a>';
            $html .= '<span class="date">';
                $html .= date_formate($link['bt_id']).', '.time_formate($link['bt_id']);
                if (!$link['bt_statut']) {
                    $html .= ' <span title="Private">&#x1F512;</span>';
                } else {
                    $html .= ' <span title="Public">&#x1F513;</span>';
                }
            $html .= '</span>';
            $html .= '<div class="link-options">';
                $html .= '<ul>';
                    $html .= '<li class="ll-edit"><a href="'.basename($_SERVER['SCRIPT_NAME']).'?id='.$link['bt_id'].'">'.$GLOBALS['lang']['edit'].'</a></li>';
                    $html .= ($link['bt_statut'] == 1) ? '<li class="ll-seepost"><a href="'.URL_ROOT.'?mode=links&amp;id='.$link['bt_id'].'">'.$GLOBALS['lang']['see_online'].'</a></li>' : '';
                $html .= '</ul>';
            $html .= '</div>';
        $html .= '</div>';

        if ($link['bt_type'] == 'link'
         && is_img($link['bt_link'])
        ) {
            $html .= '<div class="link-content">';
                $html .= '<img src="'.$link['bt_link'].'" />';
            $html .= '</div>';
        }
        $html .= ($link['bt_content']) ? '<div class="link-content">'.$link['bt_content'].'</div>' : '';

        $html .= '<div class="link-footer">';
            $html .= '<ul class="link-tags">';
                if ($link['bt_tags']) {
                    $tags = explode(',', $link['bt_tags']);
                    foreach ($tags as $tag) {
                        $html .= '<li class="tag"><a href="?filtre=tag.'.urlencode(trim($tag)).'">'.trim($tag).'</a></li>';
                    }
                }
            $html .= '</ul>';
            $html .= '<span class="hard-link">'.$link['bt_link'].'</span>';
        $html .= '</div>';
    $html .= '</div>';

    return $html;
}
