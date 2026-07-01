# Récap général du workspace

Date: 2026-06-01

Ce document sert de point d’entrée rapide pour reprendre le travail sur Maracuja CMS Starter et ses premiers usages réels, notamment Atelier Ivo Incidit.

## État général

Le starter est un Laravel + Filament destiné à produire des sites vitrines administrables sans page builder. La direction produit est maintenant claire: une installation par client, un front Blade codé, et une administration courte limitée aux responsabilités réelles du client.

Les profils commerciaux retenus décrivent le niveau de complexité vendu, pas une liste rigide de modules:

- `essence`: site vitrine simple, peu de contenus vivants.
- `signature`: vitrine éditoriale plus riche.
- `univers`: projet avec module métier cadré, comme un catalogue d’archets, un espace métier simple ou un outil connecté léger.

Les modules existants ou en cours sont:

- `SiteSettings`: identité, contact, SEO par défaut et options du formulaire.
- `Pages`: outil développeur pour métadonnées et templates protégés, pas module client standard.
- `ContentSlots`: petites valeurs administrables utilisées par les templates.
- `Notices`: annonces courtes avec fenêtre de publication.
- `News`: actualités publiées, épinglées ou expirables.
- `Articles`: contenus longs structurés en blocs.
- `Gallery`: galerie administrable avec preset de rendu choisi par le projet.
- `ContactForm`: formulaire public et emails associés.
- `Inquiries`: conservation et suivi des demandes entrantes.
- `Audience`: contacts, segments et messages ciblés, encore en phase laboratoire.
- `Campaigns`: prévu dans la configuration, pas encore implémenté.

## Travail récent

### Harmonisation éditoriale

Une passe de francisation a été menée sur l’admin, le code visible, les seeders, les emails et les textes publics. Les libellés anglais courants ont été remplacés quand le sens était sûr.

Les arbitrages éditoriaux ont été appliqués et l’ancien tableau `docs/textes-a-valider.md` a été supprimé.

### Contact et demandes entrantes

L’ancien module `ContactSubmission` a été remplacé par une séparation plus lisible:

- `ContactForm` pour le formulaire public et les emails.
- `Inquiries` pour stocker et piloter les demandes dans l’admin.

Le formulaire peut fonctionner avec ou sans stockage des demandes selon les modules activés.

### Audience et segments

Le module `Audience` introduit:

- des contacts;
- des segments;
- l’import depuis les demandes entrantes;
- l’ajout ou le retrait de contacts par actions groupées;
- l’envoi d’un message à un segment;
- le suivi basique des livraisons envoyées ou échouées.

La recommandation UX retenue pour les segments est de ne pas afficher une grosse liste figée de 100 ou 200 contacts dans la fiche segment. Les contacts se pilotent depuis la liste `Contacts`, avec filtres, recherche, sélection et actions groupées. La fiche segment sert surtout de vue de synthèse et de point d’accès filtré.

### Atelier Ivo Incidit

Atelier Ivo Incidit est classé en offre `univers`, car le besoin contient un vrai module métier: catalogue d’archets, gammes, mesures physiques, qualités de jeu, matériaux, statuts, prix et photos.

La migration Atelier a confirmé plusieurs principes:

- conserver autant que possible les URLs existantes;
- garder les pages métier hors du module `Pages`;
- traiter les archets comme un module Laravel dédié;
- séparer la galerie globale des photos propres à chaque archet;
- éviter de recopier l’ancien CSS fichier par fichier.

Une passe récente sur le module `Archets` d’Atelier a ajouté:

- le thumbnail dans la liste admin;
- l’affichage des chemins publics des images dans le détail admin;
- des attributs modèle pour rendre explicites le dossier attendu et les photos détectées.

Audit du 2026-06-22:

- le Laravel Atelier contient encore des scories ou archives historiques: `archive/`, `migration/`, `.DS_Store`, `.phpunit.result.cache`;
- `archive/` pèse environ 77 Mo et contient l’ancien site PHP complet, y compris anciens assets, SQL et fichiers privés;
- `migration/` contient des backups SQL et doit être traité comme trace de migration, pas comme runtime;
- le code actif ne référence pas directement `archive/`, mais la documentation Atelier s’en sert encore comme source historique;
- `public/incidit-vox` est actif car l’outil admin InciditVox charge ses scripts depuis ce dossier;
- `public/assets/images/archets/{code}` reste actif pour les photos d’archets détectées par le module Arcus;
- Atelier n’est pas encore aligné avec les dernières décisions du starter sur `Pages`, `ContentSlots`, `GalleryImages`, `body_blocks` et les textes éditoriaux sortis de `config/maracuja.php`.

### Catalogue métier

Le module `Archets` sert de référence pour un pattern de catalogue métier: liste, filtres, fiche détail, statut, prix optionnel, photo principale, galerie ou convention photo, SEO et admin dense.

Ce pattern ne doit pas devenir un module `Products` générique. Chaque catalogue important garde sa table et son vocabulaire métier.

## Reste à faire prioritaire

### Produit starter

- Clarifier dans la documentation le statut de `Audience`: laboratoire, option `univers`, ou futur module vendable.
- Décider si `Campaigns` reste une intention de configuration ou devient un vrai module.
- Ajouter une page de documentation dédiée au parcours contact: formulaire, emails, demandes, transformation en contact audience.
- Ajouter une page de documentation dédiée au module Audience si le laboratoire est conservé.
- Continuer la chasse aux textes non harmonisés dans les nouveaux modules uniquement.
- Améliorer l’outil développeur `Pages` si on le garde installé: clarté, verrouillage, absence de promesse client.

### UX admin

- Vérifier toutes les tables Filament avec beaucoup de lignes: pagination, recherche, filtres, actions groupées, colonnes masquables.
- Homogénéiser les libellés d’actions: créer, modifier, archiver, publier, envoyer, importer.
- Vérifier les messages de notification et les confirmations d’actions sensibles.
- Prévoir des états vides utiles, surtout pour Contacts, Segments, Messages et Demandes.
- Vérifier les pages de détail ou modales sur mobile et petit écran.

### Audience, segments et emails

- Décider le niveau d’ambition: mini-CRM interne, passerelle vers Brevo, ou les deux.
- Ajouter les garde-fous avant envoi réel: mode brouillon, confirmation claire, estimation du nombre de destinataires, prévention de double envoi.
- Ajouter un mécanisme de désinscription exploitable dans les emails de segment.
- Clarifier le consentement: accepté, refusé, inconnu, source de collecte.
- Étudier l’export CSV vers Brevo avant toute intégration API.
- Reporter l’intégration Brevo tant que le besoin reste inférieur à une vraie newsletter marketing.

### Atelier Ivo Incidit

- Aligner Atelier sur le starter pour:
  - `Pages` typées `system/text/module`;
  - `ContentSlots` visibles, groupés par page/module;
  - suppression de `body_blocks` côté pages;
  - suppression des textes éditoriaux visibles dans `config/maracuja.php`;
  - suppression de l’ancienne ressource plate `GalleryImages`.
- Décider le sort de `archive/`: déplacer hors repo, exporter, ou documenter comme archive non-runtime avant suppression.
- Décider le sort de `migration/`: conserver hors repo comme backup historique ou supprimer après archivage.
- Supprimer les `.DS_Store` et caches locaux suivis.
- Documenter les modules projet Atelier `InciditVox`, `Events`, `Venues` ou les retirer s’ils ne sont plus utilisés.
- Formaliser la convention des photos d’archets:
  - dossier `public/assets/images/archets/{code}`;
  - image principale `main.jpg`, `main.webp` ou première image par ordre alphabétique;
  - noms ordonnés si nécessaire: `01-...`, `02-...`;
  - formats web recommandés: JPG, PNG ou WebP;
  - éviter HEIC en affichage public.
- Décider si les photos d’archets restent en dossier automatique ou passent plus tard à une table dédiée.
- Ajouter une table ou un manifeste seulement si l’on veut gérer ordre manuel, légendes, textes alternatifs, recadrage ou import admin.
- Vérifier visuellement les fiches archets après chaque lot d’images.
- Continuer la comparaison SEO et URLs entre l’ancien site et la version Laravel.

### Qualité et livraison

- Lancer régulièrement:

```bash
php artisan test
php artisan maracuja:doctor
php artisan maracuja:doctor --production
```

- Ajouter des tests dès qu’un module devient vendable.
- Garder les modules désactivables sans erreur de routes, navigation ou migrations manquantes.
- Maintenir le changelog à chaque jalon stable.

## Points à valider

| Sujet | Décision proposée | À valider |
| --- | --- | --- |
| `Audience` | Module laboratoire pour `univers`, pas encore promesse commerciale standard. | Oui |
| Brevo | Export CSV d’abord, intégration API seulement si newsletter récurrente ou automatisations. | Oui |
| `Campaigns` | Garder en intention, ne pas exposer tant que non implémenté. | Oui |
| Photos Atelier | Garder le dossier par code à court terme, avec convention stricte. | Oui |
| Articles Atelier | Garder `Articles` comme libellé public définitif. | Validé |
| Vocabulaire admin | Garder `home`, `front system`, `Media System` et `CTA`; remplacer `back-office` par `administration` et `listing` par `liste`. | Validé |
| Pages | Réservé développeur, masqué de l’admin client par défaut. | Validé |
| Catalogue métier | Pattern réutilisable, mais modules spécifiques par métier. | Validé |

## Docs utiles

- `docs/installation.md`: installation et livraison.
- `docs/offer-profiles.md`: offres Essence, Signature et Univers.
- `docs/content-admin.md`: contenus modifiables par le client.
- `docs/contact-flow.md`: parcours formulaire, demandes et audience.
- `docs/audience.md`: module laboratoire pour contacts et segments.
- `docs/catalogue-metier.md`: pattern pour modules catalogue métier.
- `docs/front-system.md`: système Blade/CSS.
- `docs/media-system.md`: images, galeries et lightbox.
- `docs/js-system.md`: JavaScript progressif.
- `docs/seo-system.md`: SEO, sitemap et robots.
- `docs/theme-system.md`: thèmes et variantes client.
- `docs/atelier-ivo-audit.md`: audit et stratégie Atelier Ivo Incidit.
