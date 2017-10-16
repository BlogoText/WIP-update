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
require_once BT_ROOT_ADMIN.'inc/maintenance.php';



function maintenance_handler_export()
{
    $format = (!empty($_GET['exp-format'])) ? htmlspecialchars($_GET['exp-format'], ENT_QUOTES) : '';

    // Export in JSON file
    if ($format == 'json') {
        $arrData = array('articles' => array(), 'liens' => array(), 'commentaires' => array());
        // list links (nth last)
        if ($_GET['incl-links'] == 1) {
            $nb = htmlspecialchars($_GET['nb-links']);
            $limit = (is_numeric($nb) and $nb != -1 ) ? 'LIMIT 0, ?' : '';
            $array = (empty($limit)) ? array() : array($nb);
            $sql = '
                SELECT *
                  FROM links
                 ORDER BY bt_id DESC '.
                 $limit;
            $arrData['liens'] = db_items_list($sql, $array, 'links');
        }
        // get articles (nth last)
        if ($_GET['incl-artic'] == 1) {
            $nb = htmlspecialchars($_GET['nb-artic']);
            $limit = (is_numeric($nb) and $nb != -1 ) ? 'LIMIT 0, ?' : '';
            $array = (empty($limit)) ? array() : array($nb);
            $sql = '
                SELECT *
                  FROM articles
                 ORDER BY bt_id DESC '.
                 $limit;
            $arrData['articles'] = db_items_list($sql, $array, 'articles');
            // get list of comments (comments that belong to selected articles only)
            if ($_GET['incl-comms'] == 1) {
                foreach ($arrData['articles'] as $article) {
                    $sql = '
                        SELECT c.*, a.bt_title
                          FROM commentaires AS c, articles AS a
                         WHERE c.bt_article_id = ?
                               AND c.bt_article_id = a.bt_id';
                    $comments = db_items_list($sql, array($article['bt_id']), 'commentaires');
                    if (!empty($comments)) {
                        $arrData['commentaires'] = array_merge($arrData['commentaires'], $comments);
                    }
                }
            }
        }
        $file_archive = creer_fichier_json($arrData);

    // Export links in HTML format
    } elseif ($format == 'html') {
        $nb = htmlspecialchars($_GET['nb-links2']);
        $limit = (is_numeric($nb) and $nb != -1 ) ? $nb : '';
        $file_archive = create_html_favs($limit);

    // Export a ZIP archive
    } elseif ($format == 'zip') {
        $folders = array();
        $sqlite = (!empty($_GET['incl-sqlit'])) ? $_GET['incl-sqlit'] + 0 : 0;
        if ($sqlite == 1) {
            $folders[] = DIR_VHOST_DATABASES;
        }
        if ($_GET['incl-files'] == 1) {
            $folders[] = DIR_DOCUMENTS;
            $folders[] = DIR_IMAGES;
        }
        if ($_GET['incl-confi'] == 1) {
            $folders[] = DIR_VHOST_SETTINGS;
        }
        if ($_GET['incl-theme'] == 1) {
            $folders[] = DIR_THEMES;
        }
        var_dump($folders);
        $file_archive = creer_fichier_zip($folders);

    // Export a OPML rss lsit
    } elseif ($format == 'opml') {
        $file_archive = creer_fichier_opml();
    } else {
        return 'nothing to do';
    }

    $return = '';
    // affiche le formulaire de téléchargement et de validation.
    if (!empty($file_archive)) {
        $return .= '<form action="maintenance.php" method="get" class="block_small block-white txt-center">';
            $return .= '<fieldset class="pref">';
            $return .= '<legend>'.$GLOBALS['lang']['bak_succes_save'].'</legend>';
                $return .= '<p><a href="'.$file_archive.'" download>'.$GLOBALS['lang']['bak_dl_file'].'</a></p>';
                $return .= '<div class="btn-container">';
                    $return .= '<button class="btn btn-submit" type="submit">'.$GLOBALS['lang']['submit'].'</button>';
                $return .= '</div>';
            $return .= '</fieldset>';
        $return .= '</form>';
    }

    return $return;
}

/**
 * process
 */

$GLOBALS['files_list'] = file_get_array(FILE_VHOST_FILES_DB);
$GLOBALS['feeds_list'] = file_get_array(FILE_VHOST_FEEDS_DB);


/**
 * echo
 */

echo tpl_get_html_head($GLOBALS['lang']['title_maintenance'], false);
echo '<div id="axe">';
echo '<div id="page">';

// création du dossier des backups
folder_create(DIR_VHOST_BACKUP, 0);


/*
 * Affiches les formulaires qui demandent quoi faire. (!isset($do))
 * Font le traitement dans les autres cas.
*/

// no $do nor $file : ask what to do
echo '<div id="maintenance-form">';
if (!isset($_GET['do']) and !isset($_FILES['file'])) {
    $token = token_set();
    $nbs = array(10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, -1 => $GLOBALS['lang']['settings_all']);

    echo '<form action="maintenance.php" method="get" class="form-inline block_small block-white txt-center" id="form_todo">';
        echo '<div class="input">';
            echo '<label for="select_todo">'.$GLOBALS['lang']['maintenance_ask_do_what'].' </label>';
                echo '<select id="select_todo" name="select_todo" onchange="switch_form(this.value)">';
                echo '<option selected disabled hidden value=""></option>';
                echo '<option value="form_export">'.$GLOBALS['lang']['maintenance_export'].'</option>';
                echo '<option value="form_import">'.$GLOBALS['lang']['maintenance_import'].'</option>';
                echo '<option value="form_optimi">'.$GLOBALS['lang']['maintenance_optim'].'</option>';
            echo '</select>';
        echo '</div>';
    echo '</form>';

    // Form export
    echo '<form action="maintenance.php" onsubmit="hide_forms(\'exp-format\')" method="get" class="hidden" id="form_export">';
    // choose export what ?
        echo '<fieldset class="form-inline block_legend block_small block-white">';
            echo '<legend>'.$GLOBALS['lang']['maintenance_export'].'</legend>';
            echo '<div class="input">';
                echo '<input type="radio" class="radio" name="exp-format" value="json" id="json" onchange="switch_export_type(\'e_json\')" />';
                echo '<label for="json">'.$GLOBALS['lang']['bak_export_json'].'</label>';
            echo '</div>';
            echo '<div class="input">';
                echo '<input type="radio" class="radio" name="exp-format" value="html" id="html" onchange="switch_export_type(\'e_html\')" />';
                echo '<label for="html">'.$GLOBALS['lang']['bak_export_netscape'].'</label>';
            echo '</div>';
            echo '<div class="input">';
                echo '<input type="radio" class="radio" name="exp-format" value="zip"  id="zip"  onchange="switch_export_type(\'e_zip\')" />';
                echo '<label for="zip">'.$GLOBALS['lang']['bak_export_zip'].'</label>';
            echo '</div>';
            echo '<div class="input">';
                echo '<input type="radio" class="radio" name="exp-format" value="opml"  id="opml"  onchange="switch_export_type(\'e_opml\')" />';
                echo '<label for="opml">'.$GLOBALS['lang']['bak_export_opml'].'</label>';
            echo '</div>';
        echo '</fieldset>';
        // export in JSON.
        echo '<fieldset class="form-inline block_legend block_small block-white hidden" id="e_json">';
            echo '<legend>'.$GLOBALS['lang']['maintenance_incl_quoi'].'</legend>';
            echo '<div class="input">'.select_yes_no('incl-artic', 0, $GLOBALS['lang']['bak_articles_do']).form_select_no_label('nb-artic', $nbs, 50).'</div>';
            echo '<div class="input">'.select_yes_no('incl-comms', 0, $GLOBALS['lang']['bak_comments_do']).'</div>';
            echo '<div class="input">'.select_yes_no('incl-links', 0, $GLOBALS['lang']['bak_links_do']).form_select_no_label('nb-links', $nbs, 50).'</div>';
        echo '</fieldset>';
        // export links in html
        echo '<fieldset class="form-inline block_legend block_small block-white hidden" id="e_html">';
            echo '<legend>'.$GLOBALS['lang']['bak_combien_linx'].'</legend>';
            echo '<div class="input">'.form_select('nb-links2', $nbs, 50, $GLOBALS['lang']['bak_combien_linx']).'</div>';
        echo '</fieldset>';
        // export data in zip
        echo '<fieldset class="form-inline block_legend block_small block-white hidden" id="e_zip">';
            echo '<legend>'.$GLOBALS['lang']['maintenance_incl_quoi'].'</legend>';
            if (DBMS == 'sqlite') {
                echo '<div class="input">'.select_yes_no('incl-sqlit', 0, $GLOBALS['lang']['bak_incl_sqlit']).'</div>';
            }
            echo '<div class="input">'.select_yes_no('incl-files', 0, $GLOBALS['lang']['bak_incl_files']).'</div>';
            echo '<div class="input">'.select_yes_no('incl-confi', 0, $GLOBALS['lang']['bak_incl_confi']).'</div>';
            echo '<div class="input">'.select_yes_no('incl-theme', 0, $GLOBALS['lang']['bak_incl_theme']).'</div>';
        echo '</fieldset>';
        echo '<div class="btn-container">';
            echo '<button class="btn btn-cancel" type="button" onclick="redirection(\'maintenance.php\');">'.$GLOBALS['lang']['cancel'].'</button>';
            echo '<button class="btn btn-submit" type="submit" name="do" value="export">'.$GLOBALS['lang']['submit'].'</button>';
        echo '</div>';
        echo hidden_input('token', $token);
    echo '</form>';

    // Form import
    $importformats = array(
        'jsonbak' => $GLOBALS['lang']['bak_import_btjson'],
        'xmlwp' => $GLOBALS['lang']['bak_import_wordpress'],
        'htmllinks' => $GLOBALS['lang']['bak_import_netscape'],
        'rssopml' => $GLOBALS['lang']['bak_import_rssopml']
    );
    echo '<form action="maintenance.php" method="post" enctype="multipart/form-data" class="block_legend block_small block-white hidden" id="form_import">';
        // echo '<fieldset class="pref">';
            echo '<legend>'.$GLOBALS['lang']['maintenance_import'].'</legend>';
            echo '<div class="input">';
            echo form_select_no_label('imp-format', $importformats, 'jsonbak');
            echo '</div>';
            echo '<div class="input">';
            echo '<input type="file" name="file" id="file" class="text" />';
            echo '</div>';
        // echo '</fieldset>';
        echo '<div class="btn-container">';
            echo '<button class="btn btn-cancel" type="button" onclick="redirection(\'maintenance.php\');">'.$GLOBALS['lang']['cancel'].'</button>';
            echo '<button class="btn btn-submit" type="submit" name="valider">'.$GLOBALS['lang']['submit'].'</button>';
        echo '</div>';

        echo hidden_input('token', $token);
    echo '</form>';

    // Form optimi
    echo '<form action="maintenance.php" method="get" class="form-inline block_legend block_small block-white hidden" id="form_optimi">';
        // echo '<fieldset class="pref">';
        echo '<legend>'.$GLOBALS['lang']['maintenance_optim'].'</legend>';

            echo '<div class="input">'.select_yes_no('opti-file', 0, $GLOBALS['lang']['bak_opti_miniature']).'</div>';
            if (DBMS == 'sqlite') {
                echo '<div class="input">'.select_yes_no('opti-vacu', 0, $GLOBALS['lang']['bak_opti_vacuum']).'</div>';
            } else {
                echo hidden_input('opti-vacu', 0);
            }
            echo '<div class="input">'.select_yes_no('opti-comm', 0, $GLOBALS['lang']['bak_opti_recountcomm']).'</div>';

            echo '<div class="input">'.select_yes_no('opti-rss', 0, $GLOBALS['lang']['bak_opti_supprreadrss']).'</div>';

        // echo '</fieldset>';
        echo '<div class="btn-container">';
            echo '<button class="btn btn-cancel" type="button" onclick="redirection(\'maintenance.php\');">'.$GLOBALS['lang']['cancel'].'</button>';
            echo '<button class="btn btn-submit" type="submit" name="do" value="optim">'.$GLOBALS['lang']['submit'].'</button>';
        echo '</div>';
        echo hidden_input('token', $token);
    echo '</form>';

// either $do or $file
// $do
} else {
    // vérifie Token
    if ($errorsForm = validate_form_maintenance()) {
        // echo '<div class="bordered-formbloc">';
            echo '<fieldset class="pref">';
                // echo '<legend>'.$GLOBALS['lang']['bak_restor_done'].'</legend>';
                echo erreurs($errorsForm);
                echo '<p class="btn-container">';
                    echo '<button class="btn btn-submit" type="button" onclick="redirection(\'maintenance.php\')">'.$GLOBALS['lang']['submit'].'</button>';
                echo '</p>';
            echo '</fieldset>';
        // echo '</div>';
    } else {
        // token : ok, go on !
        if (isset($_GET['do'])) {
            if ($_GET['do'] == 'export') {
                echo maintenance_handler_export();
            } elseif ($_GET['do'] == 'optim') {
                // recount files DB
                if ($_GET['opti-file'] == 1) {
                    rebuilt_file_db();
                }
                // vacuum SQLite DB
                if ($_GET['opti-vacu'] == 1) {
                    try {
                        $req = $GLOBALS['db_handle']->prepare('VACUUM');
                        $req->execute();
                    } catch (Exception $e) {
                        die('Erreur 1429 vacuum : '.$e->getMessage());
                    }
                }
                    // recount comms/articles
                if ($_GET['opti-comm'] == 1) {
                    recompte_comments();
                }
                    // delete old RSS entries
                if ($_GET['opti-rss'] == 1) {
                    try {
                        $req = $GLOBALS['db_handle']->prepare('DELETE FROM rss WHERE bt_statut = 0');
                        $req->execute(array());
                    } catch (Exception $e) {
                        die('Erreur : 7873 : rss delete old entries : '.$e->getMessage());
                    }
                }
                    echo '<form action="maintenance.php" method="get">';
                        echo '<fieldset class="pref">';
                            echo '<legend>'.$GLOBALS['lang']['bak_optim_done'].'</legend>';
                            echo '<p class="btn-container">';
                                echo '<button class="btn btn-submit" type="submit">'.$GLOBALS['lang']['submit'].'</button>';
                            echo '</p>';
                        echo '</fieldset>';
                    echo '</form>';
            } else {
                echo 'nothing to do.';
            }

        // $file
        } elseif (isset($_POST['valider']) and !empty($_FILES['file']['tmp_name'])) {
                $message = array();
            switch ($_POST['imp-format']) {
                case 'jsonbak':
                    $json = file_get_contents($_FILES['file']['tmp_name']);
                    $message = importer_json($json);
                    break;
                case 'htmllinks':
                    $html = file_get_contents($_FILES['file']['tmp_name']);
                    $message['links'] = insert_table_links(parse_html($html));
                    break;
                case 'xmlwp':
                    $xml = file_get_contents($_FILES['file']['tmp_name']);
                    $message = importer_wordpress($xml);
                    break;
                case 'rssopml':
                    $xml = file_get_contents($_FILES['file']['tmp_name']);
                    $message['feeds'] = importer_opml($xml);
                    break;
                default:
                    die('nothing');
                break;
            }
            if (!empty($message)) {
                echo '<form action="maintenance.php" method="get">';
                echo '<fieldset class="pref">';
                    echo '<legend>'.$GLOBALS['lang']['bak_restor_done'].'</legend>';
                    echo '<ul>';
                    foreach ($message as $type => $nb) {
                        echo '<li>'.$GLOBALS['lang']['label_'.$type].' : '.$nb.'</li>';
                    }
                    echo '</ul>';
                    echo '<p class="btn-container">';
                    echo '<button class="btn btn-submit" type="submit">'.$GLOBALS['lang']['submit'].'</button>';
                    echo '</p>';
                echo '</fieldset>';
                echo '</form>';
            }
        } else {
            echo 'nothing to do.';
        }
    }
}

echo '</div>';

echo <<<EOS
<script>
    var ia = document.getElementById("incl-artic");
    if (ia) ia.addEventListener("change", function() {
        document.getElementById("nb-artic").style.display = (ia.value == 1 ? "inline-block" : "none");
    });

    var il = document.getElementById("incl-links");
    if (il) il.addEventListener("change", function() {
        document.getElementById("nb-links").style.display = (il.value == 1 ? "inline-block" : "none");
    });
</script>
EOS;

echo '</div>';
echo '</div>';
echo tpl_get_footer();
