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
 * Dev Note
 *
 * Don't waste to much time on the current code,
 * Will be rewrite for BT 4.0
 */

/*
 * Vars used in them files, aimed to get
 * replaced with some specific data
 */
$GLOBALS['boucles'] = array(
    'posts' => 'BOUCLE_posts',
    'comments' => 'BOUCLE_commentaires',
);

$GLOBALS['tpl_tags'] = array(
    'version' => '{version}',
    'app_name' => '{app_name}',
    'style' => '{style}',
    'racine_du_site' => '{racine_du_site}',
    'rss' => '{rss}',
    'rss_comments' => '{rss_comments}',
    // content type
    'tpl_class' => '{tpl_class}',
    // Navigation
    'pagination' => '{pagination}',
    // Blog
    'blog_nom' => '{blog_nom}',
    'blog_description' => '{blog_description}',
    'blog_author' => '{blog_auteur}',
    'blog_email' => '{blog_email}',
    'blog_motscles' => '{keywords}',
    // Formulaires
    'form_recherche' => '{recherche}',
    'form_comment' => '{formulaire_commentaire}',
    // Encarts
    'comm_encart' => '{commentaires_encart}',
    'cat_encart' => '{categories_encart}',

    // Article
    'article_title' => '{article_titre}',
    'article_title_page' => '{article_titre_page}',
    'article_title_echape' => '{article_titre_echape}',
    'article_chapo' => '{article_chapo}',
    'article_content' => '{article_contenu}',
    'article_hour' => '{article_heure}',
    'article_date' => '{article_date}',
    'article_date_iso' => '{article_date_iso}',
    'article_lien' => '{article_lien}',
    'article_tags' => '{article_tags}',
    'article_tags_plain' => '{article_tags_plain}',
    'nb_comments' => '{nombre_commentaires}',

    // comment
    'comment_author' => '{commentaire_auteur}',
    'comment_author_lien' => '{commentaire_auteur_lien}',
    'comment_content' => '{commentaire_contenu}',
    'comment_hour' => '{commentaire_heure}',
    'comment_date' => '{commentaire_date}',
    'comment_date_iso' => '{commentaire_date_iso}',
    'comment_email' => '{commentaire_email}',
    'comment_webpage' => '{commentaire_webpage}',
    'comment_anchor' => '{commentaire_ancre}', // the id="" content
    'comment_lien' => '{commentaire_lien}',
    'comment_md5email' => '{commentaire_md5email}',

    // Liens
    'lien_title' => '{lien_titre}',
    'lien_url' => '{lien_url}',
    'lien_date' => '{lien_date}',
    'lien_date_iso' => '{lien_date_iso}',
    'lien_hour' => '{lien_heure}',
    'lien_description' => '{lien_description}',
    'lien_permalink' => '{lien_permalink}',
    'lien_id' => '{lien_id}',
    'lien_tags' => '{lien_tags}',
);


/**
 *
 */
function date_formate_iso($id)
{
    $date = decode_id($id);
    $timestamp = mktime($date['hour'], $date['minutes'], $date['seconds'], $date['month'], $date['day'], $date['year']);
    $date_iso = date('c', $timestamp);
    return $date_iso;
}

/**
 *
 */
function conversions_theme($text, $solo_art, $cnt_mode)
{
    $text = str_replace($GLOBALS['tpl_tags']['version'], BLOGOTEXT_VERSION, $text);
    $text = str_replace($GLOBALS['tpl_tags']['app_name'], BLOGOTEXT_NAME, $text);
    $text = str_replace($GLOBALS['tpl_tags']['style'], $GLOBALS['theme_style'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['racine_du_site'], URL_ROOT, $text);
    $text = str_replace($GLOBALS['tpl_tags']['blog_author'], $GLOBALS['author'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['blog_email'], $GLOBALS['email'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['blog_nom'], $GLOBALS['site_name'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['tpl_class'], $GLOBALS['tpl_class'], $text);

    if ($cnt_mode == 'post' and !empty($solo_art)) {
        $text = str_replace($GLOBALS['tpl_tags']['article_title_page'], $solo_art['bt_title'].' - ', $text);
        $text = str_replace($GLOBALS['tpl_tags']['article_title'], $solo_art['bt_title'], $text);
        $text = str_replace($GLOBALS['tpl_tags']['article_title_echape'], urlencode($solo_art['bt_title']), $text);
        $text = str_replace($GLOBALS['tpl_tags']['article_lien'], $solo_art['bt_link'], $text);
        if ($solo_art['bt_type'] == 'article') {
            $text = str_replace($GLOBALS['tpl_tags']['article_chapo'], htmlspecialchars(str_replace(array("\r", "\n"), ' ', ((empty($solo_art['bt_abstract'])) ? mb_substr(strip_tags($solo_art['bt_content']), 0, 249).'…' : $solo_art['bt_abstract'])), ENT_QUOTES), $text);
            $text = str_replace($GLOBALS['tpl_tags']['blog_motscles'], $solo_art['bt_keywords'], $text);
        }
        if ($solo_art['bt_type'] == 'link' or $solo_art['bt_type'] == 'note') {
            $text = str_replace($GLOBALS['tpl_tags']['article_chapo'], htmlspecialchars(trim(str_replace(array("\r", "\n"), ' ', mb_substr(strip_tags($solo_art['bt_content']), 0, 149))), ENT_QUOTES).'…', $text);
            $text = str_replace($GLOBALS['tpl_tags']['article_title_page'], $solo_art['bt_title'].' - ', $text);
        }
    }

    // si remplacé, ceci n'a pas d'effet.
    $text = str_replace($GLOBALS['tpl_tags']['blog_description'], $GLOBALS['site_description'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['article_title_page'], '', $text);
    $text = str_replace($GLOBALS['tpl_tags']['blog_motscles'], $GLOBALS['site_keywords'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['article_title_echape'], '', $text);
    $text = str_replace($GLOBALS['tpl_tags']['article_lien'], URL_ROOT, $text);
    $text = str_replace($GLOBALS['tpl_tags']['article_chapo'], $GLOBALS['site_description'], $text);

    $text = str_replace($GLOBALS['tpl_tags']['pagination'], lien_pagination(), $text);

    if (strpos($text, $GLOBALS['tpl_tags']['form_recherche']) !== false) {
        $text = str_replace($GLOBALS['tpl_tags']['form_recherche'], search_engine_form(''), $text) ;
    }

    // dependancies
    require_once BT_ROOT.'inc/comments.php';

    // Formulaires
    $text = str_replace($GLOBALS['tpl_tags']['rss'], $GLOBALS['rss'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['comm_encart'], comments_aside_preview(), $text);
    $text = str_replace($GLOBALS['tpl_tags']['cat_encart'], tags_aside((isset($_GET['mode']))?$_GET['mode']:''), $text);
    if (isset($GLOBALS['rss_comments'])) {
        $text = str_replace($GLOBALS['tpl_tags']['rss_comments'], $GLOBALS['rss_comments'], $text);
    }

    // addons
    $text = conversion_theme_addons($text);

    return $text;
}

/**
 * Comments
 */
function conversions_theme_comment($text, $comment)
{
    $text = str_replace($GLOBALS['tpl_tags']['comment_content'], $comment['bt_content'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['comment_date'], date_formate($comment['bt_id']), $text);
    $text = str_replace($GLOBALS['tpl_tags']['comment_date_iso'], date_formate_iso($comment['bt_id']), $text);
    $text = str_replace($GLOBALS['tpl_tags']['comment_hour'], time_formate($comment['bt_id']), $text);
    $text = str_replace($GLOBALS['tpl_tags']['comment_email'], $comment['bt_email'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['comment_md5email'], md5($comment['bt_email']), $text);
    $text = str_replace($GLOBALS['tpl_tags']['comment_author_lien'], $comment['author_lien'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['comment_author'], str_replace("'", "\\'", $comment['bt_author']), $text);
    $text = str_replace($GLOBALS['tpl_tags']['comment_webpage'], $comment['bt_webpage'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['comment_anchor'], $comment['anchor'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['comment_lien'], $comment['bt_link'], $text);
    return $text;
}

/**
 * Article
 */
function conversions_theme_article($text, $article)
{
    // $text = str_replace($GLOBALS['tpl_tags']['form_comment'], $GLOBALS['form_comment'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['form_comment'], comments_form($article['bt_id'], array(), array()), $text);
    $text = str_replace($GLOBALS['tpl_tags']['rss_comments'], 'rss.php?id='.$article['bt_id'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['article_title'], $article['bt_title'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['article_chapo'], ((empty($article['bt_abstract'])) ? 
                mb_substr(strip_tags($article['bt_content']), 0, 249).'…'
              : $article['bt_abstract']), $text);
    $text = str_replace($GLOBALS['tpl_tags']['article_content'], $article['bt_content'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['article_date'], date_formate($article['bt_date']), $text);
    $text = str_replace($GLOBALS['tpl_tags']['article_date_iso'], date_formate_iso($article['bt_date']), $text);
    $text = str_replace($GLOBALS['tpl_tags']['article_hour'], time_formate($article['bt_date']), $text);
    // comments closed (globally or only for this article) and no comments => say « comments closed »
    if (($article['bt_allow_comments'] == 0 or $GLOBALS['comments_allowed'] == 1 ) and $article['bt_nb_comments'] == 0) {
        $text = str_replace($GLOBALS['tpl_tags']['nb_comments'], $GLOBALS['lang']['note_comment_closed'], $text);
    }
    // comments open OR ( comments closed AND comments exists ) => say « nb comments ».
    if (!($article['bt_allow_comments'] == 0 or $GLOBALS['comments_allowed'] == 1 ) or $article['bt_nb_comments'] != 0) {
        $text = str_replace($GLOBALS['tpl_tags']['nb_comments'], nombre_objets($article['bt_nb_comments'], 'comment'), $text);
    }
    $text = str_replace($GLOBALS['tpl_tags']['article_lien'], $article['bt_link'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['article_tags'], liste_tags($article, '1'), $text);
    $text = str_replace($GLOBALS['tpl_tags']['article_tags_plain'], liste_tags($article, '0'), $text);
    return $text;
}

/**
 * Links
 */
function conversions_theme_lien($text, $link)
{
    $text = str_replace($GLOBALS['tpl_tags']['article_title'], $link['bt_title'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['lien_title'], $link['bt_title'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['lien_url'], $link['bt_link'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['lien_date'], date_formate($link['bt_id']), $text);
    $text = str_replace($GLOBALS['tpl_tags']['lien_date_iso'], date_formate_iso($link['bt_id']), $text);
    $text = str_replace($GLOBALS['tpl_tags']['lien_hour'], time_formate($link['bt_id']), $text);
    $text = str_replace($GLOBALS['tpl_tags']['lien_permalink'], $link['bt_id'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['lien_description'], $link['bt_content'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['lien_id'], $link['ID'], $text);
    $text = str_replace($GLOBALS['tpl_tags']['lien_tags'], liste_tags($link, '1'), $text);
    return $text;
}

/**
 * récupère le bout du fichier thème contenant une boucle comme {BOUCLE_commentaires}
 *   soit le morceau de HTML retourné est parsé à son tour pour crée le HTML de chaque commentaire ou chaque article.
 *   soit le morceau de HTML retourné sert à se faire remplacer par l’ensemble des commentaires constitués
 */
function extract_boucles($text, $balise, $incl)
{
    $len_balise_d = 0 ;
    $len_balise_f = 0;
    if ($incl == 'excl') {
        // if tag excluded : bli{p}blabla{/p}blo => blabla
        $len_balise_d = strlen('{'.$balise.'}');
    } else {
        // if tag included : bli{p}blabla{/p}blo => {p}blabla{/p}
        $len_balise_f = strlen('{/'.$balise.'}');
    }

    $debut = strpos($text, '{'.$balise.'}');
    $fin = strpos($text, '{/'.$balise.'}');

    if ($debut !== false and $fin !== false) {
        $debut += $len_balise_d;
        $fin += $len_balise_f;

        $length = $fin - $debut;
        $return = substr($text, $debut, $length);
        return $return;
    } else {
        // if no tag, return content without any modification
        return $text;
    }
}

/**
 * only used by the main page of the blog (not on admin) : shows main blog page.
 */
function afficher_index($tableau, $type)
{
    $html = '';
    if (!($theme_page = file_get_contents($GLOBALS['theme_container']))) {
        die($GLOBALS['lang']['err_theme_broken']);
    }
    if (!($theme_post = file_get_contents($GLOBALS['theme_post_post']))) {
        die($GLOBALS['lang']['err_theme_broken']);
    }

    if ($type == 'list') {
        $HTML_elmts = '';
        $data = array();
        if (!empty($tableau)) {
            // if (count($tableau)==1 and !empty($tableau[0]['bt_title']) and $tableau[0]['bt_type'] == 'article') {
                // redirection($tableau[0]['bt_link']);
            // } else {
            if (count($tableau) == 1 && ($tableau[0]['bt_type'] == 'link' or $tableau[0]['bt_type'] == 'note')) {
                $data = $tableau[0];
            }
            if ($tableau[0]['bt_type'] == 'article') {
                if (!($theme_article = file_get_contents($GLOBALS['theme_post_artc']))) {
                    die($GLOBALS['lang']['err_theme_broken']);
                }
                $conversion_theme_fonction = 'conversions_theme_article';
            }
            if ($tableau[0]['bt_type'] == 'comment') {
                if (!($theme_article = file_get_contents($GLOBALS['theme_post_comm']))) {
                    die($GLOBALS['lang']['err_theme_broken']);
                }
                $conversion_theme_fonction = 'conversions_theme_comment';
            }
            if ($tableau[0]['bt_type'] == 'link' or $tableau[0]['bt_type'] == 'note') {
                if (!($theme_article = file_get_contents($GLOBALS['theme_post_link']))) {
                    die($GLOBALS['lang']['err_theme_broken']);
                }
                $conversion_theme_fonction = 'conversions_theme_lien';
            }
            foreach ($tableau as $element) {
                $HTML_elmts .=  $conversion_theme_fonction($theme_article, $element);
            }
            $html = str_replace(extract_boucles($theme_page, $GLOBALS['boucles']['posts'], 'incl'), $HTML_elmts, $theme_page);
            $html = conversions_theme($html, $data, 'post');
            // }
        } else {
            $HTML_article = conversions_theme($theme_page, $data, 'list');
            $html = str_replace(extract_boucles($theme_page, $GLOBALS['boucles']['posts'], 'incl'), $GLOBALS['lang']['note_no_article'], $HTML_article);
        }
    } elseif ($type == 'post') {
        $billet = $tableau;
        // parse & apply template article
        $html_article = conversions_theme_article($theme_post, $billet);

        // parse & apply templace comments
        $html_comments = '';
        // get list comments
        if ($billet['bt_nb_comments'] != 0) {
            $query = '
                    SELECT c.*, a.bt_title 
                      FROM commentaires AS c, articles AS a 
                     WHERE c.bt_article_id=? 
                       AND c.bt_article_id=a.bt_id 
                       AND c.bt_statut=1 
                     ORDER BY c.bt_id 
                     LIMIT ?';
            $comments = db_items_list($query, array($billet['bt_id'], $billet['bt_nb_comments']), 'commentaires');
            $template_comments = extract_boucles($theme_post, $GLOBALS['boucles']['comments'], 'excl');
            foreach ($comments as $comment) {
                $html_comments .=  conversions_theme_comment($template_comments, $comment);
            }
        }

        // in $article : pastes comments
        $v = extract_boucles($theme_post, $GLOBALS['boucles']['comments'], 'incl');
        $html_article = str_replace($v, $html_comments, $html_article);

        // in global page : pastes article and comms
        $html = str_replace(extract_boucles($theme_page, $GLOBALS['boucles']['posts'], 'incl'), $html_article, $theme_page);

        // in global page : remplace remaining tags
        $html = conversions_theme($html, $billet, 'post');
    }

    $tmp_hook = hook_trigger_and_check('show_index', $html);
    if ($tmp_hook !== false) {
        $html = $tmp_hook['1'];
    }

    return $html;
}

/**
 * Affiche la liste des articles, avec le &liste dans l’url
 */
function afficher_liste($tableau)
{
    $HTML_elmts = '';
    if (!($theme_page = file_get_contents($GLOBALS['theme_container']))) {
        die($GLOBALS['lang']['err_theme_broken']);
    }
    $HTML_article = conversions_theme($theme_page, array(), 'list');
    if ($tableau) {
        $HTML_elmts .= '<ul id="liste-all-articles">'."\n";
        foreach ($tableau as $e) {
            $short_date = substr($e['bt_date'], 0, 4).'/'.substr($e['bt_date'], 4, 2).'/'.substr($e['bt_date'], 6, 2);
            $HTML_elmts .= "\t".'<li><time datetime="'.date_formate_iso($e['bt_id']).'">'.$short_date.'</time><a href="'.$e['bt_link'].'">'.$e['bt_title'].'</a></li>'."\n";
        }
        $HTML_elmts .= '</ul>'."\n";
        $HTML = str_replace(extract_boucles($theme_page, $GLOBALS['boucles']['posts'], 'incl'), $HTML_elmts, $HTML_article);
    } else {
        $HTML = str_replace(extract_boucles($theme_page, $GLOBALS['boucles']['posts'], 'incl'), $GLOBALS['lang']['note_no_article'], $HTML_article);
    }
    return $HTML;
}

/**
 * Include Addons and converts {tags} to HTML (specified in addons)
 */
function conversion_theme_addons($text)
{

    // Parse the $text and replace {tags} with html generated in addon.
    // Generate CSS and JS includes too.
    $css = "<style>\n\t\t@charset 'utf-8';";
    $js = '';
    $hasStyle = false;

    // proceed addons tags
    foreach ($GLOBALS['addons'] as $addon) {
        if (!$addon['enabled'] || !isset($addon['tag'])) {
            continue;
        };

        $lookFor = '{addon_'.$addon['tag'].'}';

        if (strpos($text, $lookFor) !== false) {
            $callback = 'a_'.$addon['tag'];
            if (function_exists($callback)) {
                while (($pos = strpos($text, $lookFor)) !== false) {
                    $text = substr_replace($text, call_user_func($callback), $pos, strlen($lookFor));
                }
            } else {
                $text = str_replace($lookFor, '', $text);
            }
        }

        if (isset($addon['css'])) {
            if (!is_array($addon['css'])) {
                $addon['css'] = array($addon['css']);
            }
            foreach ($addon['css'] as $inc_file) {
                $inc = sprintf('%s%s/%s', DIR_ADDONS, $addon['tag'], $inc_file);
                if (is_file($inc)) {
                    $hasStyle = true;
                    $inc = sprintf('%saddons/%s/%s', URL_ROOT, $addon['tag'], $inc_file);
                    $css .= sprintf("\n\t\t@import url('%s');", addslashes($inc));
                }
            }
        }

        if (isset($addon['js'])) {
            if (!is_array($addon['js'])) {
                $addon['js'] = array($addon['js']);
            }
            foreach ($addon['js'] as $inc_file) {
                $inc = sprintf('%s%s/%s', DIR_ADDONS, $addon['tag'], $inc_file);
                if (is_file($inc)) {
                    $inc = sprintf('%saddons/%s/%s', URL_ROOT, $addon['tag'], $inc_file);
                    $js .= sprintf("<script src=\"%s\"></script>\n", $inc);
                }
            }
        }
    }

    // CSS and JS inclusions
    $css .= "\n\t</style>";
    if (!$hasStyle) {
        $css = '';
    }
    $text = str_replace('{includes.css}', $css, $text);
    $text = str_replace('{includes.js}', $js, $text);

    // remove useless tag in case of tag for nonexistent addon
    // strpos for perfomance : no tag = no regex
    if (strpos($text, '{addon_') !== false) {
        $text = preg_replace('/\{addon_[a-zA-Z0-9-_]*\}/', '', $text);
    }

    // hook
    $tmp_hook = hook_trigger_and_check('conversion_theme_addons_end', $text);
    if ($tmp_hook !== false) {
        $text = $tmp_hook['1'];
    }

    return $text;
}
