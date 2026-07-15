# Intégration Brevo — Module Audience / Relation client Maracuja

## Objectif

Intégrer Brevo au module `Audience` de Maracuja pour gérer les campagnes d’envoi ciblé depuis l’interface Maracuja, sans obliger le client à jongler entre Brevo et Maracuja.

Brevo doit être utilisé comme infrastructure technique d’envoi, de délivrabilité, de bounces, de désinscriptions et de statistiques.

Maracuja doit rester le cockpit métier : contacts, segments, campagnes, contenu, lancement, suivi, rapport.

## Contexte actuel

Le module existe déjà sous le nom `Audience`.

Côté interface, il est rangé dans “Relation client”.

Tables actuelles :

* `audience_contacts`
* `audience_segments`
* `audience_contact_segment`
* `segment_messages`
* `segment_message_deliveries`

L’envoi actuel passe par Laravel Mail avec SMTP LWS.

Il existe déjà une logique d’envoi progressif applicatif :

* `QueueSegmentMessage`
* `SendPendingSegmentMessages`
* commande Artisan `audience:send-pending`
* prévue pour cron / tâche planifiée

Cette logique reste utile pour le canal SMTP/LWS, mais les campagnes de masse doivent désormais pouvoir passer par Brevo.

## Problème à résoudre

Avec LWS mutualisé, certains envois massifs sont bloqués ou dégradés à cause de la réputation du serveur mutualisé, notamment vers les domaines Microsoft.

Le module actuel donne une vision trop applicative : `sent`, `failed`, `skipped`, etc. Ces statuts ne disent pas clairement si l’email est réellement délivré, refusé, bounced ou désinscrit.

Objectif : sortir du “SMTP aveugle” pour les campagnes et récupérer des statuts exploitables via Brevo.

## Principe produit

Maracuja garde la relation client et la donnée.

Brevo gère l’envoi massif, la délivrabilité, les bounces, les désinscriptions et les statistiques.

LWS reste utilisé uniquement pour les emails simples ou individuels :

* formulaire de contact ;
* notification admin ;
* accusé de réception ;
* email ponctuel ;
* facture ou message individuel ;
* emails système.

Les campagnes de masse doivent passer par Brevo.

## Règle UX fondamentale

Le client ne doit pas utiliser Brevo comme back-office quotidien.

Le client doit seulement :

1. créer/configurer son compte Brevo ;
2. récupérer sa clé API ;
3. coller cette clé API dans Maracuja ;
4. travailler ensuite dans Maracuja.

Le client ne doit pas avoir besoin de créer manuellement ses listes ou campagnes dans Brevo.

## Périmètre V1

La V1 Brevo doit couvrir le flux complet, mais en séquence pragmatique :

1. Synchroniser les contacts et segments Maracuja vers Brevo.
2. Créer une campagne Brevo depuis un `segment_message`.
3. Envoyer la campagne Brevo depuis Maracuja.
4. Recevoir les statuts Brevo via webhook.
5. Mettre à jour les contacts, livraisons et rapports dans Maracuja.

La V1 ne doit pas gérer l’envoi scandé par domaine Microsoft. Brevo gère l’envoi normalement.

La V1 ne doit pas remplacer les emails simples SMTP/LWS.

## Architecture cible

```text
Client
  ↓
Interface Maracuja — Relation client / Audience
  ↓
Contacts + segments + message + campagne
  ↓
Service Brevo Maracuja
  ↓
Brevo API
  ↓
Envoi réel des campagnes
  ↓
Brevo webhooks
  ↓
Maracuja met à jour les statuts, bounces, désinscriptions et rapports
```

## Canal d’envoi

Ajouter une notion de canal d’envoi sur les campagnes.

Canaux possibles :

* `smtp_lws`
* `brevo`

Pour les campagnes de masse, le canal recommandé est `brevo`.

Pour les emails simples, garder Laravel Mail + SMTP LWS.

## Configuration Brevo dans Filament

Créer un écran de réglage dans le module Relation client / Audience.

Nom possible :

`Relation client > Réglages > Brevo`

Champs :

* activation Brevo : oui/non ;
* clé API Brevo ;
* nom expéditeur par défaut ;
* email expéditeur par défaut ;
* email de réponse par défaut ;
* dossier Brevo par défaut, si nécessaire ;
* statut dernier test ;
* date dernier test ;
* message d’erreur du dernier test.

La clé API doit être stockée chiffrée.

Ne jamais afficher la clé API complète après sauvegarde.

Prévoir actions Filament :

* tester la connexion Brevo ;
* envoyer un email de test, si pertinent ;
* synchroniser un segment de test ;
* vérifier les webhooks configurés, si possible.

## Données à ajouter ou adapter

### Option simple recommandée pour V1

Ajouter une table dédiée :

`audience_brevo_settings`

Champs indicatifs :

* `id`
* `is_enabled`
* `api_key_encrypted`
* `sender_name`
* `sender_email`
* `reply_to_email`
* `default_folder_id`
* `webhook_secret`
* `last_connection_test_at`
* `last_connection_test_status`
* `last_connection_test_message`
* timestamps

Si l’application est multi-client ou multi-site, ajouter le champ de rattachement existant : `site_id`, `tenant_id` ou équivalent.

### Sur `audience_segments`

Ajouter :

* `brevo_list_id`
* `brevo_synced_at`
* `brevo_sync_status`
* `brevo_sync_error`

### Sur `audience_contacts`

Ajouter, si utile :

* `brevo_synced_at`
* `brevo_sync_status`
* `brevo_sync_error`
* `email_blacklisted_at`
* `unsubscribed_at`
* `hard_bounced_at`
* `last_bounce_reason`

Ne pas dépendre uniquement d’un ID Brevo si l’email reste la clé opérationnelle principale.

### Sur `segment_messages`

Ajouter :

* `provider`
* `brevo_campaign_id`
* `brevo_status`
* `brevo_created_at`
* `brevo_sent_at`
* `brevo_last_sync_at`
* `brevo_error`
* `content_snapshot_html`
* `subject_snapshot`
* `sender_snapshot`

Le contenu envoyé doit être figé au moment de l’envoi.

### Sur `segment_message_deliveries`

Clarifier les statuts et ajouter :

* `provider_status`
* `latest_event`
* `latest_event_at`
* `delivered_at`
* `opened_at`
* `clicked_at`
* `soft_bounced_at`
* `hard_bounced_at`
* `unsubscribed_at`
* `complained_at`
* `bounce_reason`
* `brevo_raw_event_id`, si disponible

### Nouvelle table recommandée

Créer :

`audience_brevo_events`

Objectif : garder l’historique brut des événements Brevo pour audit et debug.

Champs :

* `id`
* `segment_message_id`
* `segment_message_delivery_id`, nullable
* `audience_contact_id`, nullable
* `brevo_campaign_id`, nullable
* `email`
* `event_type`
* `event_date`
* `raw_payload`
* `processed_at`
* timestamps

Le payload brut ne doit pas être affiché tel quel au client.

## Synchronisation contacts vers Brevo

Maracuja doit synchroniser les contacts éligibles vers Brevo.

Règles :

Un contact est éligible si :

* il a un email valide ;
* il accepte les emails ;
* il n’est pas désinscrit ;
* il n’est pas en hard bounce connu ;
* il n’est pas dans une suppression list locale ;
* il n’est pas en doublon sur la campagne.

Mapping minimal :

* email → email Brevo
* prénom → attribut Brevo
* nom → attribut Brevo
* organisation → attribut Brevo, si disponible
* téléphone → attribut Brevo, si disponible

Codex doit vérifier les noms d’attributs Brevo existants ou prévoir leur création si nécessaire.

## Synchronisation segments vers listes Brevo

Chaque segment Maracuja utilisé pour une campagne doit correspondre à une liste Brevo.

Règle :

* `audience_segments` reste la source métier.
* Brevo reçoit une liste technique équivalente.
* Le client ne doit pas créer cette liste manuellement dans Brevo.

Nom de liste recommandé :

`Maracuja - {Nom du segment}`

Pour une campagne figée, on peut aussi créer une liste dédiée :

`Maracuja - Campagne #{id} - {titre}`

Décision V1 recommandée :

* utiliser une liste Brevo par segment ;
* mettre à jour la liste avant création/envoi campagne ;
* stocker `brevo_list_id` sur `audience_segments`.

## Création d’une campagne Brevo depuis Maracuja

À partir d’un `segment_message`, Maracuja doit pouvoir créer une campagne email Brevo.

Données à envoyer :

* nom de campagne ;
* sujet ;
* contenu HTML ;
* expéditeur ;
* reply-to ;
* liste(s) destinataire(s) ;
* éventuellement preview text ;
* tag Maracuja, par exemple `maracuja_segment_message_{id}`.

Règle importante :

Maracuja doit figer le contenu envoyé dans `content_snapshot_html`.

Après envoi, la campagne ne doit plus être modifiable directement.

Actions disponibles après envoi :

* voir le rapport ;
* dupliquer la campagne ;
* exporter le rapport ;
* éventuellement relancer une nouvelle campagne à partir d’une cible filtrée.

## Envoi de la campagne

Depuis Maracuja, action Filament :

`Envoyer via Brevo`

Flux :

1. Vérifier que Brevo est configuré.
2. Vérifier expéditeur et reply-to.
3. Vérifier le sujet.
4. Vérifier le contenu HTML.
5. Vérifier le segment choisi.
6. Synchroniser contacts et liste Brevo.
7. Créer la campagne Brevo.
8. Envoyer la campagne Brevo.
9. Mettre à jour le statut local.

Ne pas utiliser `QueueSegmentMessage` / `SendPendingSegmentMessages` pour les campagnes Brevo V1.

Ces jobs restent réservés au canal `smtp_lws`.

## Webhooks Brevo

Créer une route publique sécurisée pour recevoir les événements Brevo.

Nom possible :

`POST /webhooks/brevo/audience/{secret}`

Le secret doit être généré côté Maracuja et stocké dans les réglages Brevo.

À la réception d’un webhook :

1. valider le secret ;
2. stocker le payload brut dans `audience_brevo_events` ;
3. identifier la campagne via `brevo_campaign_id`, `camp_id`, tag ou autre champ disponible ;
4. identifier le contact via email ;
5. identifier la livraison correspondante ;
6. mettre à jour le statut ;
7. appliquer les règles de suppression si nécessaire.

Événements à gérer en V1 :

* `delivered`
* `opened`
* `click`
* `hardBounce`
* `softBounce`
* `unsubscribed`
* `spam`
* `blocked`, si disponible
* `contactUpdated`, si utile

Les événements doivent être idempotents. Si Brevo renvoie deux fois le même événement, Maracuja ne doit pas casser les compteurs.

## Statuts campagne

Remplacer les statuts ambigus par des statuts métier clairs.

Statuts possibles pour `segment_messages` :

* `draft`
* `ready`
* `syncing_to_brevo`
* `sync_failed`
* `created_in_brevo`
* `sending`
* `sent_to_provider`
* `completed`
* `archived`

Ne pas afficher “Envoyé” seul au client si cela signifie seulement “transmis à Brevo”.

Libellé client recommandé :

* `Envoyée à Brevo`
* `Délivrance en cours`
* `Campagne terminée`
* `Rapport disponible`

## Statuts destinataire / livraison

Statuts possibles pour `segment_message_deliveries` :

* `targeted`
* `excluded`
* `synced_to_brevo`
* `sent_to_provider`
* `delivered`
* `opened`
* `clicked`
* `soft_bounced`
* `hard_bounced`
* `unsubscribed`
* `complained`
* `blocked`
* `error`

Important :

`sent_to_provider` ne veut pas dire “délivré”.

`delivered` veut dire que Brevo remonte une livraison.

`opened` ne doit pas être présenté comme une preuve absolue de lecture.

## Compteurs à afficher

Sur le rapport campagne, afficher :

* Ciblés
* Exclus
* Envoyés à Brevo
* Délivrés
* Ouverts
* Cliqués
* Soft bounces
* Hard bounces
* Désinscrits
* Plaintes spam
* Erreurs techniques

Supprimer ou masquer le compteur `Éligibles` sauf si sa règle fonctionnelle est strictement définie.

Règle proposée :

`Éligibles = ciblés - exclus avant envoi`

Mais si ce compteur n’apporte pas de valeur client, ne pas l’afficher.

## Raisons d’exclusion

Créer des raisons lisibles :

* email manquant ;
* email invalide ;
* contact désinscrit ;
* contact en hard bounce ;
* doublon email ;
* contact refuse les emails ;
* domaine bloqué ;
* suppression manuelle ;
* erreur de synchronisation.

## Désinscriptions

Pour la V1, utiliser le lien de désinscription Brevo.

Quand Brevo remonte un événement de désinscription :

1. mettre à jour `audience_contacts` ;
2. mettre `accepts_email = false`, si ce champ existe ;
3. renseigner `unsubscribed_at` ;
4. exclure automatiquement ce contact des futures campagnes ;
5. afficher la désinscription dans l’historique du contact.

Maracuja garde l’état local, mais Brevo est la source opérationnelle pour les campagnes.

## Hard bounces

Quand Brevo remonte un hard bounce :

1. mettre à jour la livraison ;
2. mettre à jour le contact ;
3. renseigner `hard_bounced_at` ;
4. stocker la raison si disponible ;
5. exclure automatiquement l’email des futures campagnes.

Ne pas supprimer le contact. Le contact reste utile dans le CRM.

## Soft bounces

Quand Brevo remonte un soft bounce :

1. mettre à jour la livraison ;
2. stocker la raison ;
3. ne pas désactiver automatiquement le contact en V1 ;
4. afficher dans le rapport.

Une relance des soft bounces pourra être prévue plus tard.

## Interface Filament — liste des campagnes

Améliorer la liste existante.

Règle UX :

* clic sur le titre = ouvrir le détail / rapport campagne ;
* bouton modifier = éditer seulement si campagne non envoyée ;
* après envoi, proposer “Dupliquer” plutôt que modifier ;
* afficher le canal : SMTP/LWS ou Brevo ;
* afficher statut clair ;
* afficher compteurs principaux ;
* afficher date d’envoi.

Colonnes utiles :

* Titre
* Segment
* Canal
* Statut
* Ciblés
* Délivrés
* Bounces
* Désinscrits
* Date envoi
* Actions

## Interface Filament — rapport campagne

Créer une vraie page rapport.

Sections :

1. résumé campagne ;
2. compteurs ;
3. progression ;
4. liste des destinataires ;
5. événements récents ;
6. actions.

Actions :

* envoyer un test ;
* créer/envoyer via Brevo ;
* rafraîchir le rapport depuis Brevo ;
* exporter CSV ;
* dupliquer campagne.

## Table des destinataires

La liste de livraisons ne doit plus être une modale brute de log.

Créer une vraie table Filament triable et filtrable.

Colonnes :

* Email
* Contact
* Domaine
* Statut
* Raison
* Dernier événement
* Date dernier événement
* Délivré le
* Ouvert le
* Cliqué le
* Bounce le
* Désinscrit le

Filtres :

* statut ;
* domaine ;
* hard bounce ;
* soft bounce ;
* désinscrit ;
* livré ;
* ouvert ;
* cliqué ;
* exclu.

Tri :

* email ;
* domaine ;
* statut ;
* dernier événement ;
* date de livraison ;
* date de bounce.

## Aperçu HTML et images

Corriger la logique d’aperçu.

Règle :

L’aperçu doit afficher le même HTML que celui envoyé à Brevo.

Ne jamais ré-uploader une image déjà existante juste pour l’aperçu.

Ne jamais créer un doublon d’image avec un nouvel ID si l’image existe déjà.

Avant envoi à Brevo :

* normaliser le HTML ;
* convertir les URLs d’images relatives en URLs absolues publiques ;
* vérifier que l’image est accessible publiquement ;
* conserver la même référence média ;
* figer le HTML final dans `content_snapshot_html`.

Si une image n’est pas accessible publiquement, afficher une alerte avant envoi.

## Services Laravel recommandés

Créer ou compléter les services suivants :

### `BrevoAudienceService`

Responsabilités :

* tester la connexion ;
* créer ou mettre à jour un contact ;
* créer ou récupérer une liste ;
* synchroniser un segment ;
* créer une campagne ;
* envoyer une campagne ;
* récupérer un rapport campagne ;
* créer ou vérifier un webhook, si géré en API.

### `AudienceCampaignService`

Responsabilités :

* préparer une campagne ;
* calculer la cible ;
* exclure les contacts non éligibles ;
* figer le contenu ;
* choisir le canal d’envoi ;
* orchestrer Brevo ou SMTP selon canal.

### `BrevoWebhookController`

Responsabilités :

* recevoir les événements ;
* valider le secret ;
* stocker le payload brut ;
* router vers un handler.

### `BrevoWebhookEventHandler`

Responsabilités :

* interpréter les événements ;
* mettre à jour les livraisons ;
* mettre à jour les contacts ;
* recalculer les compteurs ;
* gérer l’idempotence.

## Jobs Laravel possibles

Créer des jobs séparés pour éviter les timeouts :

* `SyncAudienceSegmentToBrevo`
* `CreateBrevoCampaign`
* `SendBrevoCampaign`
* `RefreshBrevoCampaignReport`
* `ProcessBrevoWebhookEvent`

Ces jobs doivent être distincts de l’ancien flux SMTP progressif.

## Conservation de l’existant SMTP/LWS

Ne pas supprimer :

* `QueueSegmentMessage`
* `SendPendingSegmentMessages`
* `audience:send-pending`

Mais les clarifier comme flux SMTP/LWS.

Ajouter une condition :

* si canal `smtp_lws`, utiliser l’ancien envoi progressif ;
* si canal `brevo`, utiliser le nouveau service Brevo.

## Critères d’acceptation V1

La V1 est considérée prête si :

1. Un admin peut saisir une clé API Brevo dans Filament.
2. La clé est stockée chiffrée.
3. L’admin peut tester la connexion Brevo.
4. Un segment Maracuja peut être synchronisé en liste Brevo.
5. Les contacts éligibles sont synchronisés vers Brevo.
6. Une campagne Maracuja peut créer une campagne Brevo.
7. Une campagne peut être envoyée via Brevo depuis Maracuja.
8. Le client n’a pas besoin de créer manuellement la liste ou campagne dans Brevo.
9. Les webhooks Brevo sont reçus par Maracuja.
10. Les événements Brevo mettent à jour les livraisons.
11. Les hard bounces mettent à jour les contacts.
12. Les désinscriptions mettent `accepts_email = false` ou équivalent.
13. Le rapport campagne affiche des compteurs lisibles.
14. La table des destinataires est triable et filtrable.
15. L’aperçu HTML affiche les images existantes sans duplication d’upload.
16. Les emails simples continuent de passer par SMTP/LWS.

## Hors périmètre V1

Ne pas faire en V1 :

* throttling par domaine Microsoft ;
* IP dédiée LWS ;
* remplacement de tous les emails transactionnels par Brevo ;
* automation marketing avancée ;
* scoring ;
* A/B testing ;
* éditeur email complexe ;
* gestion complète des rendez-vous Brevo ;
* CRM Brevo comme source principale ;
* sous-comptes Brevo agence ;
* interface native Brevo complète dans Maracuja.

## Décision produit finale

Maracuja reste le cockpit relation client.

Brevo devient le moteur professionnel des campagnes.

LWS reste le canal simple pour les emails individuels et transactionnels.

Le client travaille dans Maracuja. Brevo est configuré une fois, puis utilisé en arrière-plan.
