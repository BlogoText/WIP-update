<?php
# *** LICENSE ***
# This file is part of BlogoText.
# https://github.com/BlogoText/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
# 2016-.... Mickaël Schoentgen and the community.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
# *** LICENSE ***

$GLOBALS['lang'] = array(
'id' => 'fr',
// Navigation
'le' => 'le',
'notice' => 'Remarque',
'submit' => 'Valider',
'cancel' => 'Annuler',
'send' => 'Envoyer',
'preview' => 'Prévisualiser',
'edit' => 'Éditer',
'activate' => 'Activer',
'desactivate' => 'Désactiver',
'voir' => 'Voir',
'share' => 'Partager',
'infos' => 'Infos',
'search' => 'Rechercher',
'filtrer' => 'Filtrer',
'yes' => 'Oui',
'no' => 'Non',
'open' => 'Autorisés',
'closed' => 'Interdits',
'reply' => 'répondre',
'since' => 'depuis',
'sur' => 'sur',
'wait' => 'patientez',
'seconds' => 'secondes',
'du' => 'du',
'et' => 'et',
'ou' => 'ou',
'par' => 'par',
'ajoute' => 'ajouté(s)',
'trouve' => 'trouvé(s)',
'using' => 'avec',
'rendered' => 'généré en',
'search_results' => 'Les résultats de recherche pour',
// Boutons formatage commentaires
'bouton-gras' => 'gras',
'bouton-ital' => 'italique',
'bouton-soul' => 'souligné',
'bouton-barr' => 'barré',
'bouton-lien' => 'Insérer un lien',
'bouton-cita' => 'Insérer une citation',
'bouton-imag' => 'Insérer une image',
'bouton-code' => 'Insérer du code formaté',
'bouton-left' => 'Aligner le texte à gauche',
'bouton-center' => 'Aligner le texte au center',
'bouton-right' => 'Aligner le texte à droite',
'bouton-justify' => 'Justifier le texte (aligner des deux côtés)',
'bouton-liul' => 'Insérer une liste à puce',
'bouton-liol' => 'Insérer une liste numérotée',
// Labels
'label_article' => 'article',
'label_articles' => 'articles',
'label_comment' => 'commentaire',
'label_comments' => 'commentaires',
'label_link' => 'lien',
'label_links' => 'liens',
'label_feed_entry' => 'Entrée RSS',
'label_feed_entrys' => 'Entrées RSS',
'label_file' => 'fichier',
'label_files' => 'fichiers',
'label_previous' => 'éléments plus récents',
'label_next' => 'éléments plus anciens',
'label_dp_pseudo' => 'Pseudo : ',
'label_dp_webpage' => 'Site web (facultatif) : ',
'label_dp_captcha' => 'Somme de : ',
'label_dp_email' => 'E-mail (facultatif) : ',
'label_dp_email_required' => 'E-mail : ',
// Commentaire
'aucun' => 'aucun',
'comment_write' => 'Ajouter un commentaire',
'comment_cookie' => 'Retenir ces informations avec un cookie ? ',
'comment_subscribe' => 'Recevoir des notifications de nouveaux commentaires par email ? ',
'comment_not_allowed' => 'Les commentaires sont fermés pour cet article',
'no_comments' => 'Il n’y a aucun commentaire pour le moment.',
'comment_is_visible' => 'Le commentaire est visible',
'comment_is_invisible' => 'Le commentaire n’est pas visible',
'comment_need_validation' => 'Votre commentaire sera visible après validation par le webmaster.',
// links
'link_is_public' => 'Le lien est public',
'link_is_private' => 'Le lien est privée (invisible)',
// Titles - liens
'post_link' => 'Voir en ligne',
'post_share' => 'Partager',
'blog_link' => 'Voir le blog',
'go_to_settings' => 'Allez dans les préférences pour changer le titre.',
// Mois
'january' => 'janvier',
'february' => 'février',
'march' => 'mars',
'april' => 'avril',
'may' => 'mai',
'june' => 'juin',
'july' => 'juillet',
'august' => 'août',
'september' => 'septembre',
'october' => 'octobre',
'november' => 'novembre',
'december' => 'décembre',
'jan.' => 'jan.',
'feb.' => 'févr.',
'mar.' => 'mars',
'apr.' => 'avril',
'may.' => 'mai',
'jun.' => 'juin',
'jul.' => 'jul.',
'aug.' => 'août',
'sept.' => 'sept.',
'oct.' => 'oct.',
'nov.' => 'nov.',
'dec.' => 'déc.',
// Jours
'mo' => 'Lu',
'tu' => 'Ma',
'we' => 'Me',
'th' => 'Je',
'fr' => 'Ve',
'sa' => 'Sa',
'su' => 'Di',
// Jours
'monday' => 'lundi',
'tuesday' => 'mardi',
'wednesday' => 'mercredi',
'thursday' => 'jeudi',
'friday' => 'vendredi',
'saturday' => 'samedi',
'sunday' => 'dimanche',
'today' => 'aujourd’hui',
'yesterday' => 'hier',
// Erreurs
'erreurs' => 'Erreur(s)',
'err_theme_broken' => 'Le fichier thème est introuvable, incomplet ou illisible.',
'err_comm_author' => 'Le nom de l’auteur est vide',
'err_comm_email' => 'L’adresse e-mail n’est pas valide',
'err_comm_content' => 'Le commentaire est vide',
'err_comm_captcha' => 'La somme est incorrecte (utiliser des chiffres)',
'err_comm_webpage' => 'L’URL n’est pas valide',
'err_comm_article_id' => 'L’ID Article n’est pas valide',
'err_wrong_token' => 'Le jeton de sécurité est expiré ou invalide.',
'err_addon_name' => 'Nom du module invalide.',
'err_addon_status' => 'Statut du module non renseigné.',
'err_addon_enabled' => 'Impossible d\'activer le module "%s", vérifiez les droits du dossier "%s" et de ses sous-dossiers.',
'err_addon_disabled' => 'Impossible de désactiver le module "%s", vérifiez les droits du dossier "%s" et de ses sous-dossiers.',
// Redirections
'retour_liste' => '« Liste des articles',
// Titres des pages
'welcome' => 'Bienvenue',
// modules / addons
'addons_settings_legend' => 'Préférences du module : ',
'addons_settings_link_title' => 'Préférences du module',
'addons_settings_confirm_reset' => 'Êtes-vous sûr de vouloir effacer vos paramètres ?',
'addons_confirm_buttons_action' => 'Certaines actions peuvent prendre du temps à se terminer, veuillez ne pas fermer ou rafraîchir votre fenêtre jusqu\'au rechargement complet de la page.',
'addons_clean_cache_label' => 'Effacer le cache de ce module ?',
// feed
'feed_article_comments_title' => 'Commentaires sur ',
// Notes
'note_no_article' => 'Aucun article :/',
'note_no_module' => 'Aucun module',
'note_no_comment' => 'Aucun commentaire',
'note_comment_closed' => 'Commentaires fermés',
'note_no_link' => 'Aucun lien',
'note_no_image' => 'Aucune image',
'note_no_fichier' => 'Aucun fichier',
'note_no_feed' => 'Aucun flux RSS',
'note_no_feed_entry' => 'Aucune entrée RSS',
// placeholders
'placeholder_search' => 'Rechercher',
//Formulaire Images
'label_up_to' => 'Jusqu’à ',
'label_per_file' => ' par fichier',
'label_codes' => 'Codes d’intégration :',
// vérifier les mises à jours
// 'succes' => 'Succès',
// 'echec' => 'Échec',
// Chiffres 0 à 9 pour captcha
'0' => 'zéro',
'1' => 'un',
'2' => 'deux',
'3' => 'trois',
'4' => 'quatre',
'5' => 'cinq',
'6' => 'six',
'7' => 'sept',
'8' => 'huit',
'9' => 'neuf',

// Mail Notification
'mail_subject' => 'Nouveau commentaire sur "',
'mail_message1' => 'Un nouveau commentaire par ',
'mail_message2' => ' a été posté sur le génialissime article ',
'mail_message3' => ' depuis ',
'mail_see' => 'Vous pouvez le consulter via ',
'mail_link' => 'ce lien',
'mail_unsub' => 'Pour vous désinscrire des commentaires de ce billet, suivez ce lien: ',
'mail_unsuball' => 'Pour vous désinscrire des commentaires de tous les billets, suivez ce lien: ',
'mail_end' => 'Merci de ne pas répondre à ce message automatique.',
'mail_regards' => 'Cordialement.',
);
