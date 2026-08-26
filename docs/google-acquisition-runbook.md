# Runbook — écosystème Google et acquisition Maracuja

Ce document est la procédure opérationnelle commune aux sites Maracuja. Il complète le plan directeur `acquisition-system-plan.md` : ce dernier décrit l’architecture ; celui-ci décrit la mise en œuvre et les contrôles.

## Règles non négociables

1. Le client reste propriétaire de ses comptes Google. Maracuja reçoit les accès nécessaires.
2. Ne jamais créer de compte, lancer de campagne, accepter une facturation ou publier une balise sans une décision explicite et vérifiable.
3. Un site ne reçoit qu’un conteneur GTM. GA4 et Google Ads sont configurés dans Google, pas par empilement de snippets dans le site.
4. Aucun identifiant personnel, contenu de formulaire, dossier client ou donnée sensible ne va dans GA4, GTM ou le `dataLayer`.
5. Une étape n’est « terminée » que si sa preuve est inscrite dans `docs/acquisition-status.md` du client.
6. Les intitulés de l’interface Google changent : ne jamais les deviner. Utiliser l’interface française réellement affichée ou la documentation Google officielle en français.

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

## Répartition technique

### Site Maracuja

Le site charge GTM et gère le consentement. Il capture les données d’attribution autorisées et émet les événements standards après succès serveur. Il ne stocke jamais les secrets Google.

### Google

GTM gère les balises. GA4 mesure le comportement agrégé. Search Console mesure la visibilité organique. Google Ads diffuse et facture les campagnes. Les comptes restent au client.

### Cremona

Cremona est le cockpit métier multi-organisation : campagnes internes, dépenses, demandes attribuées, qualification, clients et indicateurs. À l’état actuel, il peut préparer/suivre des campagnes et synchroniser des métriques Google Ads lorsque la connexion est prête. Il ne crée, ne modifie et ne publie pas encore de campagnes réelles dans Google Ads via API ; cette capacité est une évolution future, avec confirmation explicite et journal d’audit.

## Déploiement et sécurité

Consigner les particularités d’hébergement dans la fiche client. Exemple LWS : terminal dans `/htdocs`, déploiement de fichiers par SFTP vers `/htdocs`, puis commande Laravel explicitement validée. Ne jamais remplacer une base de production, des médias ou un `.env` global pour un sujet de mesure sans sauvegarde, cible résolue et accord explicite.

## Quand arrêter une branche

- Pas de campagne approuvée : ne pas forcer l’import Ads, les annonces ou le paiement.
- Une interface Google pousse vers une balise ou campagne différente de l’architecture décidée : revenir en arrière et vérifier la source de conversion.
- Les rapports GA4 sont vides juste après installation : attendre le traitement ; ne pas modifier la configuration qui a déjà été validée par DebugView.
- Une information propriétaire, juridique ou de paiement manque : demander au client, ne pas supposer.
