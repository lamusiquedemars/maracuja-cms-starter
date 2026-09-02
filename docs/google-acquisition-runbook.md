# Runbook — écosystème Google et acquisition Maracuja

Ce document est la procédure opérationnelle commune aux sites Maracuja. Il complète le plan directeur `acquisition-system-plan.md` : ce dernier décrit l’architecture ; celui-ci décrit la mise en œuvre et les contrôles.

> Référence opérationnelle unique. Cremona pilote l’agence et les campagnes ;
> le Starter porte la mécanique réutilisable de chaque site ; le fichier
> `docs/acquisition-status.md` de chaque client prouve son exécution. Aucun
> « agent par site » ni mémoire de conversation ne remplace ces trois éléments.

## Modèle opérationnel permanent

| Couche | Responsabilité durable | Ne contient jamais |
|---|---|---|
| Site client / Starter | consentement, GTM unique, événements anonymes, attribution, demande métier et tableau local | secret OAuth, developer token, données de formulaire dans `dataLayer` |
| Google du client | propriété GA4, conteneur GTM, Search Console, compte Ads et facturation | mots de passe communiqués à Maracuja |
| MCC Maracuja | accès gestionnaire aux comptes Ads acceptés par les clients | propriété de la facturation client |
| Cremona | organisations, campagnes préparées, publication explicitement validée, synchronisation, leads qualifiés et cockpit agence | secrets Google dans une organisation ou dans une migration |

### Accès et déploiement : règle de reprise

Avant de demander un accès ou une commande à Ivo, l’agent lit les documents de
déploiement du dépôt concerné et inspecte les accès déjà configurés. Les accès
distants existent normalement déjà : ils ne sont ni redemandés, ni inventés.

- **Cremona / LWS** : l’agent envoie les fichiers via le SFTP déjà configuré ;
  Ivo exécute uniquement les commandes Laravel dans le terminal LWS indiqué
  par `docs/deploiement-lws.md`.
- **Chaque site Starter** : l’agent lit d’abord son document de déploiement
  (Hostinger, LWS ou autre) et utilise sa cible réellement documentée. Il ne
  déploie jamais un site au moyen de la procédure Cremona par analogie.
- Les secrets restent dans les coffres, `.env` ignorés ou interfaces Google.
  Ils ne sont jamais demandés dans le chat, copiés dans Git, injectés dans une
  migration ni affichés pour « vérifier » une valeur.

### Source de vérité selon la question

- stratégie, validation client et préparation : **Cremona** ;
- diffusion, état d’examen, budget effectivement consommé et historique des
  changements : **Google Ads** ;
- comportement agrégé et événements : **GA4** ;
- visibilité organique : **Search Console** ;
- lead, qualification et revenu : **site puis Cremona**.

Un écart entre Cremona et Google Ads n’autorise pas à refaire la campagne à la
main : relever l’écart, consulter l’historique des modifications Google Ads,
puis corriger uniquement la valeur divergente avec une trace dans la fiche
client.

## Règles non négociables

1. Le client reste propriétaire de ses comptes Google. Maracuja reçoit les accès nécessaires.
2. Ne jamais créer de compte, lancer de campagne, accepter une facturation ou publier une balise sans une décision explicite et vérifiable.
3. Un site ne reçoit qu’un conteneur GTM. GA4 et Google Ads sont configurés dans Google, pas par empilement de snippets dans le site.
4. Aucun identifiant personnel, contenu de formulaire, dossier client ou donnée sensible ne va dans GA4, GTM ou le `dataLayer`.
5. Une étape n’est « terminée » que si sa preuve est inscrite dans `docs/acquisition-status.md` du client.
6. Les intitulés de l’interface Google changent : ne jamais les deviner. Relever le libellé réellement affiché, sa langue et sa capture textuelle avant de guider l’utilisateur ; utiliser la documentation Google officielle si nécessaire.
7. Avant toute dépense, comparer la campagne réelle Ads à la fiche Cremona :
   statut, budget, objectif de conversion, zones, option de présence, groupes,
   mots-clés, exclusions, annonces et URL finale.

## Démarrer un nouveau chat

Coller ce texte, puis joindre le fichier `docs/acquisition-status.md` du client :

> Je poursuis le chantier Acquisition/Google pour ce client. Lis d’abord `docs/acquisition-status.md`, puis ce runbook. Respecte les décisions déjà prises, ne crée aucun compte ni campagne sans mon accord explicite, ne modifie pas le site avant un audit ciblé et n’invente aucun libellé des interfaces Google. Indique l’étape courante, la preuve déjà obtenue et la seule prochaine action utile.

L’agent doit ensuite :

- vérifier le dépôt, l’hébergement et le statut Git indiqués dans la fiche client ;
- confirmer les faits avant de proposer une modification ;
- donner une seule action utilisateur à la fois dans les interfaces externes ;
- distinguer une configuration technique, une décision commerciale et une action de facturation ;
- mettre à jour la fiche d’état après une étape significative.

## Statuts

- `à préparer` : aucune action engagée ;
- `en cours` : une action est ouverte et a un responsable ;
- `en attente` : dépend d’un client, d’une validation Google ou d’un délai de traitement ;
- `terminé` : preuve enregistrée ;
- `non applicable` : décision explicite de ne pas faire l’étape.

## Checklist complète

| N° | Étape | Preuve de fin |
|---:|---|---|
| 0 | Cadrage : domaine, propriétaire, pays/devise, objectif, leads à mesurer, consentement et personne qui peut valider la facturation. | Fiche client renseignée et validée. |
| 1 | Audit du site : production réelle, URL canonique HTTPS, routes publiques, formulaire, e-mail, sitemap, robots, `noindex`, redirections et ancien domaine. | Audit daté, anomalies corrigées ou consignées. |
| 2 | Préparer Search Console : propriété Domaine, vérification DNS, sitemap soumis. | Propriété validée et sitemap accepté. |
| 3 | Préparer GTM : un conteneur Web appartenant au client, accès administrateur Maracuja. | ID `GTM-…`, accès et propriétaire consignés. |
| 4 | Intégrer GTM dans le site : chargement unique, Consent Mode, consentement lisible et sans mesure avant accord selon la décision du client. | Code déployé, test du refus et de l’acceptation. |
| 5 | Préparer GA4 : propriété et flux Web du client, accès Maracuja. | ID `G-…` et propriété consignés. |
| 6 | Configurer GTM → GA4 : balise GA4 déclenchée seulement selon le consentement. | Tag Assistant et DebugView confirment un `page_view`. |
| 7 | Définir les événements : uniquement actions utiles (`generate_lead`, appel, WhatsApp, rendez-vous…), jamais de données personnelles. | Noms, conditions de succès et tests consignés. |
| 8 | Déclarer les événements clés GA4. | Chaque événement clé est visible dans GA4. |
| 9 | Associer Search Console ↔ GA4. | Association visible ; délai de données noté. |
| 10 | Préparer Google Ads : compte Ads client, rattachement au MCC Maracuja, facture/paiement laissé au client, taggage automatique. | ID Ads et rattachement MCC confirmés. |
| 11 | Importer les événements clés GA4 dans Ads seulement avant une campagne réellement approuvée. Ne pas créer une deuxième conversion avec une balise Ads directe sans décision d’architecture. | Conversion Ads avec source GA4, test documenté. |
| 12 | Préparer une campagne : objectif, zone, budget, mots-clés, exclusions, annonces, landing page, légalité et validation client. | Brouillon validé par le client. |
| 13 | Lancer : vérification de toutes les conversions, budget plafonné, date et responsable. | Campagne active et journal de lancement. |
| 14 | Exploiter : contrôle des dépenses, clics, leads, qualification et résultats commerciaux. | Rapport périodique et décisions consignées. |

## Fiches de saisie Google : valeurs et choix attendus

Les écrans varient ; les valeurs ci-dessous ne varient pas. Ne pas ajouter une
étape absente de l’écran et ne pas remplacer une valeur par une approximation.

### 1. Search Console

1. Créer une **propriété Domaine**, avec le domaine nu : `exemple.com`, sans
   `https://`, chemin ni `www`.
2. Avant d’ajouter le TXT, déterminer les DNS autoritaires (`dig NS exemple.com`
   ou information registrar). Ajouter le TXT là où le DNS public est réellement
   géré, pas forcément chez l’hébergeur web.
3. Pour un enregistrement racine, le champ peut être vide, `@`, ou un libellé
   propre au registrar : suivre la validation de ce registrar. Ne jamais
   remplacer SPF, DMARC ou un TXT existant.
4. Vérifier d’abord que le TXT est visible par les serveurs autoritaires ; si
   Google échoue et ne voit que d’anciens TXT, ce n’est pas un délai GA4 mais un
   enregistrement absent ou saisi dans la mauvaise zone DNS.
5. Valider, soumettre le sitemap, puis consigner le propriétaire et la date.

### 2. GA4

1. Maracuja peut créer la propriété et le flux Web au nom du client, puis
   ajoute le client administrateur dès que possible.
2. Créer le flux avec l’URL canonique HTTPS du site. Consigner l’ID de mesure
   `G-…` et l’ID de flux, jamais un secret.
3. Si GA4 propose l’installation manuelle `gtag.js`, **ne pas la coller dans le
   site** : choisir GTM comme unique chemin de balisage.
4. Pour un événement déjà envoyé par GTM (`generate_lead`), le déclarer comme
   événement clé via **Administration > Affichage des données > Événements** :
   créer/indiquer l’événement avec code, activer « Marquer comme événement
   clé », sans valeur par défaut, comptage **une fois par événement** sauf
   décision commerciale différente. Ne pas choisir « sans code », qui demande
   une URL et créerait un autre mécanisme.
5. Le message GA4 « collecte non active » peut persister après publication.
   Il ne justifie pas de changer les tags si Tag Assistant, consentement et
   conteneur publié ont déjà été vérifiés ; consigner le délai et recontrôler.

### 3. GTM et consentement

1. Un conteneur Web `GTM-…` par site, appartenant au client ; Maracuja est
   administrateur.
2. Une seule balise Google/GA4, avec l’ID `G-…` exact du flux.
3. Pour chaque événement utile, créer une balise événement GA4 :

   | Nom d’événement GTM et GA4 | Nom interne conseillé | Nature |
   |---|---|---|
   | `generate_lead` | `GA4 — Generate lead` | conversion principale |
   | `whatsapp_click` | `GA4 — WhatsApp click` | micro-conversion |
   | `phone_click` | `GA4 — Phone click` | micro-conversion |
   | `appointment_request` | `GA4 — Appointment request` | seulement si le parcours est réellement instrumenté |

   Le nom d’événement et le déclencheur « Événement personnalisé » sont la
   même chaîne, exactement, avec underscore. Le nom interne est libre.
4. Prévisualiser avant publication : pour chaque événement, confirmer à la
   fois l’apparition dans `dataLayer` et la balise correspondante dans **Tags
   Fired**. Un événement visible sans balise ne part pas dans GA4.
5. Publier une seule version nommée, par exemple
   `Client — Conversões iniciais GA4`, après validation des événements. Noter
   nom et description dans la fiche client.
6. Tester le consentement dans une fenêtre privée : le refus ne charge pas
   GTM ; l’acceptation charge GTM. Une préférence mémorisée dans Safari peut
   masquer la bannière ; elle ne démontre ni un défaut de site ni un succès.

### 4. Google Ads et MCC

1. Le client crée/conserve son compte Ads et sa facturation. Maracuja demande
   l’accès depuis son MCC ; le client accepte la demande dans son compte Ads.
   Aucun mot de passe client n’est nécessaire.
2. Vérifier dans le MCC le **numéro client** et le statut `Ativo`, pas seulement
   le nom commercial qui peut être absent de certaines listes.
3. Lier GA4 au **compte client Ads**, jamais au MCC :
   `GA4 > Administração > Links com produtos > Google Ads`.
4. Dans la nouvelle interface Ads, l’import commence dans
   `Metas > Conversões` par la source **Google Analytics**, puis la propriété
   GA4 et l’événement `generate_lead` / « Enviar formulário de lead ».
   Si l’écran propose un snippet Google Ads, ne pas en installer un deuxième :
   l’événement provient déjà de GTM/GA4.
5. Les appels et WhatsApp restent observés dans GA4 mais ne sont pas importés
   comme objectifs d’enchères initiaux. Seul `generate_lead` est la conversion
   principale, sauf décision écrite contraire.

### 5. Campagne Google Ads

1. Cremona prépare et crée toute campagne en pause ; les groupes et annonces
   doivent aussi être en pause avant validation humaine.
2. Vérifier dans Ads, avant activation :
   - budget journalier correspondant au budget mensuel (`mensuel / 30,4`) ;
   - objectif spécifique à la campagne : `generate_lead` uniquement, pas les
     objectifs par défaut du compte ;
   - zones définies dans le cadrage et option
     « Présence : personnes qui sont ou habituellement sont dans les lieux
     ciblés » ;
   - groupes, mots-clés, négatifs, annonces et URL finale ;
   - absence de lieu inattendu. Toute anomalie est expliquée par l’historique
     Google Ads, non supposée.
3. Pour un compte neuf, « Maximiser les clics » est une phase initiale
   acceptable avec le plafond validé. Ne passer à « Maximiser les conversions »
   qu’après des conversions réelles suffisantes et une décision documentée.
4. L’activation exige une autorisation explicite, date, responsable et budget.
   Après activation, `Em aprendizado` / `BIDDING_STRATEGY_LEARNING` est normal.

### 6. API Google Ads et OAuth agence (Cremona uniquement)

1. C’est une infrastructure **Maracuja**, réutilisée pour tous les clients :
   developer token Google Ads, client OAuth Google Cloud, MCC et une
   autorisation OAuth d’agence. Aucun de ces secrets n’est créé par site.
2. Le developer token doit avoir l’accès réel `Basic` ou `Standard` dans
   l’API Center. Le niveau `Test account` ne permet pas les comptes clients
   réels. La demande Google décrit l’outil Cremona et son usage interne ; elle
   ne contient jamais de secrets.
3. Le client OAuth Google Cloud est de type Web, public externe si des comptes
   clients peuvent consentir, avec l’URI de redirection de production exacte :
   `https://cremona.maracujadigital.fr/integrations/google-ads/agency/callback`.
   Ne pas créer un deuxième client OAuth parce qu’un écran Google est confus.
4. Dans Cremona, l’autorisation se fait une fois sous
   **Plateforme > Infrastructure Google Ads**, avec le compte agence qui
   possède déjà l’accès MCC. Le refresh token est chiffré dans le coffre
   plateforme ; une organisation conserve uniquement son `customer_id` Ads.
5. Avant toute création/synchronisation client : vérifier l’accès MCC et lancer
   une synchronisation en lecture seule. Si `DEVELOPER_TOKEN_INVALID` apparaît,
   le problème est le token runtime, pas OAuth ni le compte client ; ne jamais
   le recopier depuis une conversation, une commande fragile ou une migration.

## Portes de contrôle et ordre réel de travail

| Porte | On peut passer seulement si | Action suivante |
|---|---|---|
| Fondation site | production, formulaire et consentement audités | créer Search Console, GTM et GA4 |
| Mesure | GTM Preview confirme page view et chaque événement/balise | marquer `generate_lead` clé, publier GTM |
| Publicité | GA4 ↔ Ads liée, `generate_lead` importé, MCC actif | comparer la campagne réelle à Cremona |
| Lancement | budget, zone de présence, annonces, exclusions, objectif spécifique et accord explicite contrôlés | activer une campagne en pause |
| Exploitation | 24–48 h après activation | contrôler impressions, dépenses, requêtes, leads et erreurs sans modifier dans la précipitation |

## Diagnostic : ne pas bricoler

| Symptôme | Vérification factuelle | Décision |
|---|---|---|
| Search Console ne voit pas le TXT | interroger les DNS autoritaires et comparer leur sortie au TXT demandé | corriger la zone DNS, ne pas attendre aveuglément |
| Événement Tag Assistant sans balise dans `Tags Fired` | chercher/ajouter la balise événement GA4 et le déclencheur du même nom | ne pas publier avant preuve |
| GA4 annonce une collecte inactive juste après publication | ID `G-…` du flux, GTM public, consentement, Tag Assistant/DebugView | attendre le traitement ; ne pas dupliquer `gtag.js` |
| Ads propose un snippet après import GA4 | vérifier que la source est GA4 et l’événement `generate_lead` | ne pas installer un tag Ads redondant |
| `DEVELOPER_TOKEN_INVALID` | vérifier la présence des variables sans les afficher ; comparer la configuration de runtime, pas les secrets dans le chat | corriger le secret dans le coffre `.env`, vider le cache, synchroniser en lecture seule |
| Google Ads cible un lieu hors plan | comparer campagne réelle/Cremona puis consulter l’historique des modifications | retirer seulement l’écart et le consigner |
| Campaign `Em aprendizado` | statut des groupes/annonces, budget, dépenses et durée depuis activation | laisser apprendre 24–48 h sauf erreur de diffusion ou dépense anormale |

## Répartition technique

### Site Maracuja

Le site charge GTM et gère le consentement. Il capture les données d’attribution autorisées et émet les événements standards après succès serveur. Il ne stocke jamais les secrets Google.

### Google

GTM gère les balises. GA4 mesure le comportement agrégé. Search Console mesure la visibilité organique. Google Ads diffuse et facture les campagnes. Les comptes restent au client.

### Cremona

Cremona est le cockpit métier multi-organisation : campagnes internes, dépenses, demandes attribuées, qualification, clients et indicateurs. Il prépare une campagne Google Ads entièrement en pause, peut la créer via l’API agence centralisée, synchronise les métriques et ne l’active qu’après confirmation explicite et journal d’audit. Google Ads reste la source de vérité de diffusion ; tout écart réel est relevé avant correction.

## Déploiement et sécurité

Consigner les particularités d’hébergement dans la fiche client. Exemple LWS : terminal dans `/htdocs`, déploiement de fichiers par SFTP vers `/htdocs`, puis commande Laravel explicitement validée. Ne jamais remplacer une base de production, des médias ou un `.env` global pour un sujet de mesure sans sauvegarde, cible résolue et accord explicite.

## Quand arrêter une branche

- Pas de campagne approuvée : ne pas forcer l’import Ads, les annonces ou le paiement.
- Une interface Google pousse vers une balise ou campagne différente de l’architecture décidée : revenir en arrière et vérifier la source de conversion.
- Les rapports GA4 sont vides juste après installation : attendre le traitement ; ne pas modifier la configuration qui a déjà été validée par DebugView.
- Une information propriétaire, juridique ou de paiement manque : demander au client, ne pas supposer.
- Une campagne est active : ne pas retoucher les réglages pendant la phase
  d’apprentissage, sauf anomalie de zone, de dépense, de conformité ou de
  conversion.
