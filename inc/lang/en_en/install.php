<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://blogotext.org/
# https://github.com/BlogoText/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***

$install_lang = array(
    /* globals */
    'is_ok' => 'Ok',
    'is_ko' => 'Fail',

    /* erreurs type */
    'empty' => 'This field can not be empty',
    'missing' => 'This field can not be empty',
    'fail_write_file' => 'Fail to write the user file',

    /* check system */
    'support_php' => 'Required PHP version',
    'support_intl' => 'Support extension INTL',
    'support_xml' => 'Support de l`extension XML',
    'support_curl' => 'Support de l`extension CURL',
    'support_db' => 'Support PDO et connecteurs MySQL et/ou SqLite',
    'support_file_rights' => 'Droits d`écriture de fichiers et de création de dossier',
    'support_mbstring' => 'Support de l`extension mbstring',
    'support_gd' => 'Support de l`extension GD',
    'support_refresh' => 'Rafraîchir',
    'support_force' => 'Forcer l`installation',

    /* installation système */
    'sys_url_label' => 'L`URL de votre BlogoText',
    'sys_url_tips' => '',
    'sys_adminfold_label' => 'Espace d`administration',
    'sys_adminfold_tips' => 'Changer l`URL de l`espace d`administration<br />{URL_ROOT}admin/',
    'sys_adminfold_already_exists' => 'Ce dossier existe déjà, veuillez spécifier un autre nom.',
    'sys_adminfold_fail_to_rename' => 'Impossible de renommer le dossier admin.',
    'sys_adminfold_fail_to_write' => 'Impossible de créer un fichier dans le dossier admin.',

    'sys_db_type_label' => 'Type de base de données',
    'sys_db_login_label' => 'Login',
    'sys_db_password_label' => 'Mot de passe',
    'sys_db_server_label' => 'Adresse du serveur',
    'sys_db_port_label' => 'Port du serveur',
    'sys_db_name_label' => 'Nom de la base de données',
    'sys_datas_label' => 'Peupler la base de données',
    'tips_sys_datas' => 'Ajoute du contenu (blog, liens...) en guise d`exemple.',

    /* création de l'utilisateur */
    'user_pseudo_label' => 'Votre pseudonyme',
    'user_pseudo_placeholder' => 'John Doe',
    'user_pseudo_tips' => 'Sera afficher publiquement.',
    'user_login_label' => 'Votre identifiant',
    'user_login_placeholder' => 'john-admin',
    'user_login_tips' => 'Identifiant privé',
    'user_password_label' => 'Votre mot de passe',
    'user_password_tips' => 'Au minimum {USER_PASS_MIN_STRLEN}.',
    'user_email_label' => 'Votre adresse email',
    'user_email_placeholder' => 'john-doe@example.com',
    'user_email_tips' => 'Doit être valide, sert pour les notifications, mot de passe perdu ...',
    

    /* page finale */
    'install_ok' => '',
    'install_links' => '',
    'install_fail_to_delete_install_dir' => 'Impossible de supprimer le dossier "install", veuillez le supprimer manuellement afin de pouvoir profiter de votre blog.',

    /* peuplé par le système, à garder vide */
    'form_errors' => '',

    /* content example (insertion en DB) */
    'ex_blog_1_title' => 'Mon premier article',
    'ex_blog_1_content' => 'Éditez-moi',

    'ex_blog_2_title' => 'Instructions',
    'ex_blog_2_wiki_content' => 'Une fois que vous avez lu ceci, vous pouvez supprimer l\'article.',

    'ex_link_1_type' => 'link',
    'ex_link_1_title' => 'BlogoText',
    'ex_link_1_content' => 'Le site officiel de BlogoText \o/',
    'ex_link_1_link' => 'https://blogotext.org/',
    'ex_link_1_tags' => 'blogotext, example',

    'ex_link_2_type' => 'note',
    'ex_link_2_title' => 'Note / lien privé',
    'ex_link_2_content' => 'Le site officiel de BlogoText \o/',
    'ex_link_2_link' => 'https://blogotext.org/',
    'ex_link_2_tags' => 'example',
);
