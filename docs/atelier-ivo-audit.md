# Audit Atelier Ivo Incidit

Date: 2026-05-26  
Dernière vérification: 2026-06-22

## Verdict offre

Atelier Ivo Incidit correspond à l’offre **Univers**.

Ce n’est pas une Signature simple, car le site ne se limite pas à des pages, une galerie et quelques actualités. Il contient un vrai module métier : catalogue d’archets, gammes, fiches détaillées, mesures physiques, qualités de jeu, matériaux, statuts, prix et photos par archet.

Ce n’est pas non plus du sur-mesure illimité: le besoin est cadrable en un module métier propre, réutilisable pour d’autres artisans, catalogues ou ateliers.

## État historique

Architecture actuelle:

- site PHP maison avec routeur simple;
- pages dans `app/pages`;
- composants dans `app/components`;
- configuration dans `config`;
- assets publics dans `public/assets`;
- admin PHP maison dans `app/admin`;
- base MySQL exportée dans `storage/private/ivoin2573774.sql`;
- images d’archets dans `public/assets/images/archets/{code}`.

## État Laravel actuel au 2026-06-22

Le projet `/Users/ivocorreiademelo/Sites/atelierivoincidit` est déjà un Laravel + Filament, mais il garde encore plusieurs traces de l’ancien site et il n’est pas aligné avec les dernières décisions du starter.

### Dossiers racine

À conserver:

- `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `storage/`, `tests/`;
- `public/incidit-vox`, car l’outil admin `InciditVox` charge encore `/incidit-vox/libs/fft.min.js`, `/incidit-vox/js/signal.js` et `/incidit-vox/js/main.js`;
- `public/assets/images/archets/{code}`, car le module `Arcus` détecte encore les photos par convention de dossier;
- `public/assets/images/*.jpeg` utilisés par le front et le thème.

À considérer comme scories ou archives à sortir du repo actif:

- `archive/`: ancien site PHP complet, environ 77 Mo, avec ancien admin, anciens templates, assets, fichiers SQL et fichiers privés. Le Laravel actif ne le référence pas directement. Il doit être déplacé hors repo ou transformé en archive externe documentée avant suppression.
- `migration/`: environ 324 Ko, contient `README.md` et deux backups SQL. Utile comme trace de migration, mais pas nécessaire au runtime Laravel.
- `.DS_Store` dans plusieurs dossiers: bruit macOS à supprimer du repo.
- `.phpunit.result.cache`: cache local, pas utile au suivi.

Attention: le `git status` Atelier montre déjà des changements dans `archive/public/assets/images/archets/a015`:

- anciennes images `IMG_4696.jpeg`, `IMG_4697.jpeg`, `IMG_4698.jpeg`, `IMG_4700.jpeg` supprimées;
- nouvelles images `IMG_5599.jpeg`, `IMG_5601.jpeg`, `IMG_5603.jpeg` non suivies.

Ces changements sont dans `archive/`, donc ils ne concernent normalement pas le Laravel actif, mais ils confirment que l’archive n’est pas une zone stable.

### Alignement contenu/admin avec le starter

Atelier n’est pas encore aligné avec les décisions prises dans le starter:

- `Pages` utilise encore `body_blocks` dans le modèle, la migration initiale, les tests et les seeders.
- Les migrations `add_type_and_content_to_pages_table` et `drop_body_blocks_from_pages_table` ne sont pas présentes dans Atelier.
- La page `methode` existe encore dans les tests et les seeders, alors que le starter l’a remplacée par une page texte `mentions-legales`.
- `ContentSlots` est encore masqué (`shouldRegisterNavigation(): false`) et groupé dans `Réglages`, alors que le starter l’expose dans `Contenus` avec groupement par page/module.
- `config/maracuja.php` contient encore du texte éditorial visible: `gallery.title`, `gallery.intro`, `articles.public_label`.
- `ArticleController` utilise encore `config('maracuja.articles.public_label')` au lieu de `ContentSlots`.
- L’ancienne ressource admin plate `GalleryImages` existe encore alors que le starter utilise désormais `Galleries > Photos`.

### Modules admin Atelier

Modules spécifiques Atelier à conserver:

- `Arcus`: module métier central, avec `ArcusBow`, `Bow`, `Wood`, `ArcusCatalog`, ressources admin `Arcus/Bows` et `Arcus/Woods`;
- `InciditVox`: outil admin spécifique, dépendant de `public/incidit-vox`;
- éventuellement les ressources `Events` et `Venues` si elles correspondent à un besoin réel du site Atelier. Elles n’existent pas dans le starter standard et doivent être documentées comme modules projet.

Modules du starter à réaligner:

- `Pages`;
- `ContentSlots`;
- `Gallery/Galleries`;
- `Articles`;
- `SiteSettings`;
- `Contact`.

Le module `Contact` Atelier utilise encore `ContactSubmission`, tandis que le starter actuel sépare `ContactForm` et `Inquiries`. Il faudra décider si Atelier garde son vocabulaire local ou s’aligne sur le duo starter `ContactForm` + `Inquiries`.

Pages publiques principales:

- accueil;
- archets;
- gammes: Ars Antiqua, Ars Classica, Ars Nova;
- détail archet: `/arcus/{code}`;
- essai;
- archetier;
- contact;
- mentions légales;
- CGV;
- articles partiellement présents.

## Données métier

Tables identifiées dans l’export SQL:

- `bow`;
- `range`;
- `instrument`;
- `style`;
- `shape`;
- `wood`;
- `color`;
- `material`;
- `origin`;
- `garnish`;
- `size`;
- `quality`;
- `quality_ranges`;
- `photo`;
- `article`;
- `tag`;
- `article_tag`;
- `contact`;
- `users`;
- `login_attempts`.

Le module central est `bow`.

Champs importants côté archet:

- code;
- nom;
- instrument;
- style;
- forme;
- bois;
- couleur;
- matériaux du bouton, de la hausse, de la coulisse, de la pointe;
- poids, longueur, équilibre, densité, vitesse, élasticité, fréquence, amortissement;
- qualités de jeu : flexibilité, réactivité, maniabilité, pression naturelle, timbre, projection, sustain, articulation;
- notes;
- trait court;
- gamme;
- statut;
- prix;
- remise;
- visible/non visible.

## Médias

Inventaire rapide:

- environ 47 dossiers d’archets;
- environ 157 fichiers image dans `public/assets/images/archets`;
- environ 14 documents PDF/DOC/pages/numbers dans `public/assets/docs`;
- galerie d’atelier dans `app/data/showcase.php`;
- images de contenu dans `public/assets/images`.

Mapping Maracuja CMS:

- images de galerie globale -> module Gallery;
- images par archet -> relation media propre dans le module Archets;
- documents techniques -> stockage public documenté ou module Documents plus tard.

## Front et CSS

CSS actuel:

- `base.css`;
- `theme.css`;
- `header.css`;
- `arcus-list.css`;
- `arcus-detail.css`;
- `article.css`;
- `contact.css`;
- `scripta.css`;
- `inciditvox.css`;
- `admin.css`.

Le starter doit reprendre l’intention visuelle via `resources/css/thèmes/atelier.css`, mais pas recopier la logique CSS fichier par fichier.

Mapping attendu:

- layout/header/footer -> layout Maracuja CMS;
- hero -> composant `x-site.hero`;
- cards/gammes -> `FeatureGrid` ou composant Blade dédié;
- galerie atelier -> preset gallery `carousel`;
- pages archets -> module Laravel dédié.

## JS

JS actuel:

- shrink header;
- burger menu;
- Fancyapps Carousel pour citations;
- Fancyapps Carousel pour showcase;
- Fancybox pour lightbox.

Mapping Maracuja CMS:

- navigation -> déjà couvert;
- carousel -> déjà couvert via Embla;
- lightbox -> déjà couvert via PhotoSwipe;
- reveal/back-to-top -> déjà couverts.

## Mapping Maracuja CMS

Modules existants réutilisables:

- Site Settings: identité, SEO, contact, réseaux;
- Pages: pages codées et protégées;
- Content Slots: petits textes/mentions modifiables;
- Gallery: galerie atelier;
- News: articles si conservé;
- Contact: formulaire;
- Theme: `atelier`.

Module à créer:

- `Archets`.

Sous-modules/tables probables:

- `Bow`;
- `BowRange`;
- `Instrument`;
- `BowStyle`;
- `BowShape`;
- `Wood`;
- `Material`;
- `Origin`;
- `Garnish`;
- `BowSize`;
- `Quality`;
- `BowPhoto`.

Pages publiques Laravel:

- `/arcus`;
- `/arcus/{range}`;
- `/arcus/{range}/{code}` ou garder `/arcus/{code}` si préservation SEO;
- page détail archet;
- éventuellement filtres par instrument.

Admin Filament:

- Archets;
- Gammes;
- Référentiels: instruments, bois, matériaux, qualités;
- Photos d’archets;
- statuts visible/disponible/vendu.

## Points à préserver

- URLs existantes autant que possible;
- textes principaux déjà travaillés;
- structure des gammes;
- photos par code d’archet;
- statut/prix/remise;
- SEO des pages importantes;
- philosophie: catalogue sans paiement.

## Points à éviter

- migrer tel quel le CSS fichier par fichier;
- refaire un admin spécial en PHP;
- transformer les gammes en page builder;
- mettre les fiches archets dans le module Pages;
- confondre galerie globale et photos d’un archet.

## Plan de migration recommandé

1. Profil `univers` confirmé dans le starter.
2. Créer le module `Archets` dans Maracuja CMS.
3. Reproduire les tables utiles en migrations Laravel.
4. Importer les référentiels depuis l’export SQL.
5. Importer les archets et leurs photos.
6. Créer les vues publiques : index, gamme, détail.
7. Adapter le thème `atelier`.
8. Vérifier les URLs et redirections.
9. Comparer visuellement l’actuel et la version Laravel.
10. Taguer le starter en `v0.2.0-lab` après migration réussie.

## Plan de nettoyage recommandé au 2026-06-22

1. Ne rien supprimer tant que `archive/` et `migration/` n’ont pas été exportés ou déplacés hors repo.
2. Ajouter une note de conservation externe pour `archive/`, car elle contient un ancien site complet et des fichiers privés/SQL.
3. Supprimer les `.DS_Store` et caches locaux du repo.
4. Aligner Atelier sur le starter pour `Pages`, `ContentSlots`, `Gallery`, `Articles` et `config/maracuja.php`.
5. Supprimer l’ancienne ressource `GalleryImages` après validation que toutes les photos passent par `Galleries > Photos`.
6. Remplacer `methode` par `mentions-legales` si la page n’a plus de justification projet.
7. Retirer `body_blocks` des pages Atelier et le laisser uniquement aux articles.
8. Revoir `ContactSubmission`: soit conserver comme choix projet documenté, soit migrer vers `Inquiries`.
9. Documenter `Events`, `Venues` et `InciditVox` comme modules Atelier spécifiques ou les retirer s’ils ne sont pas utilisés.
10. Lancer les tests Atelier après chaque étape d’alignement.

## Estimation

Migration technique sérieuse mais cadrable.

Pour obtenir une première version Laravel fonctionnelle :

- module Archets + migrations: 0.5 à 1 jour;
- import SQL/images: 0.5 jour;
- vues publiques index/gamme/détail: 1 jour;
- admin Filament propre: 0.5 à 1 jour;
- theme/polish/verification: 1 jour.

Total réaliste avec Codex: 3 à 4 jours de travail itératif, ou 1 à 2 jours pour une première version labo non définitive.
