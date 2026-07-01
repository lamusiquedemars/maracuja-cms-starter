# Content Admin

Maracuja CMS sépare les pages codées des contenus vivants. Le client ne modifie pas la structure des pages, les CTA stratégiques ou les gabarits. Il gère seulement les contenus qui correspondent à une responsabilité claire.

## Règle de contenu

- Pages structurelles: Blade.
- Micro-contenus répétés ou variables: `Content Slots`.
- Contenus longs: `Articles`.
- Messages ponctuels: `Notices`.
- Actualités datées: `News`.
- Contenus métier: module dédié.

Le module `Pages` n’est pas un module client standard. Il peut rester disponible comme outil développeur, mais il ne doit pas devenir un page builder.

## Registre des pages

Le module `Pages` est le registre des pages connues du site. Il ne sert pas à créer librement l’arborescence publique.

Le développeur déclare les pages dans le starter ou le projet client. L’admin permet ensuite de modifier les champs autorisés selon le type de page.

### Page système

Une page système a une structure codée dans un template Blade ou un controller dédié. Exemple: `accueil` utilise `resources/views/site/home.blade.php`, `services` utilise `resources/views/site/pages/services.blade.php`, `contact` utilise `ContactController`.

La table `pages` porte son identité éditoriale:

- titre interne;
- slug public verrouillé;
- template verrouillé;
- résumé;
- titre et sous-titre du hero;
- image hero;
- titre et description SEO;
- statut de publication.

Les pages système ne portent pas la mise en page, les cartes, les grilles, les CTA internes ou les modules affichés. Ces éléments restent dans Blade jusqu’à ce qu’un contenu précis soit extrait en `Content Slot` ou dans un module dédié.

### Page texte

Une page texte est une exception contrôlée pour les contenus simples, comme `mentions-legales` ou `politique-confidentialite`.

Elle utilise le template générique `site.page` et expose un seul champ `content` dans un éditeur simple. Elle ne propose pas de blocs, de variantes de sections ou de choix de layout.

### Page module

Une page module représente l’entrée publique d’un module, comme `actualites`.

Son titre, son hero et son SEO peuvent être gérés dans `Pages`, mais le contenu réel vient du module: actualités, articles, images, produits, etc. La route publique reste celle du module.

`body_blocks` n’existe pas sur les pages. Les pages système utilisent leur Blade + les `Content Slots`. Les pages texte utilisent le champ `content`. Les blocs structurés sont réservés aux modules qui en ont vraiment besoin, comme les articles.

## Modules vivants

- `Content Slots`: petites valeurs nommées utilisées par les templates, comme un prix, une date ou un libellé court.
- `Notices`: annonce courte affichée sur la home si elle est publiee et dans sa période de visibilité.
- `News`: actualités publiées entre une date de début et une date de fin optionnelle.
- `Gallery`: images, textes courts, alt, credit, ordre et publication.

## Content Slots

Les `Content Slots` remplacent les anciens blocs libres rattachés aux pages. Ils permettent de modifier une petite valeur sans toucher à la page elle-même.

Un slot doit être défini avec le client quand une valeur change souvent ou régulièrement, ou quand elle relève vraiment de son autonomie éditoriale.

Le développeur crée le slot dans le code ou le seeder, puis le template Blade l’appelle par sa clé. L’admin sert ensuite à modifier la valeur visible, pas à inventer une nouvelle structure de page.

Exemples:

- `home.hero.cta_label`;
- `home.intro.title`;
- `services.essence.price`;
- `services.essence.description`;
- `gallery.title`;
- `articles.public_label`.

Le template Blade décide où le slot s’affiche. Le client ne choisit pas la section, la mise en page ou le type de composant.

Dans Filament, les slots fournis par le starter sont verrouillés:

- la clé technique reste fixe;
- le groupe, le type et le libellé restent stables;
- la liste est groupée par page ou module;
- la valeur reste modifiable;
- la suppression est masquée.

Cela permet de corriger un prix, un libellé de bouton ou un court texte d’introduction sans casser le lien entre la base de données et le template.

## Annonce courte

Le module `Annonce courte` sert aux messages ponctuels: horaires d’été, fermeture exceptionnelle, information temporaire.

Champs client:

- titre court optionnel;
- message court limité;
- lien optionnel;
- début et fin de publication;
- statut publié.

Si aucune annonce active n’existe, la section ne s’affiche pas.

## Actualités

Les actualités ont une fenêtre de publication:

- `published_at`: début de publication;
- `expires_at`: fin de publication;
- `is_published`: statut manuel.
- `is_pinned`: remonte l’actualité dans les listes.
- `has_detail_page`: active ou non une page détail publique.

Une actualité expirée est masquée sur la home, la liste publique et la page détail. Une actualité sans page détail reste visible en carte ou en liste, mais son URL détail retourne 404.

La durée proposée par défaut est configurée avec:

```env
MARACUJA_NEWS_DEFAULT_DURATION_DAYS=30
```

## Règle produit

Un contenu devient administrable quand il correspond à une responsabilité client claire. La mise en page, les titres structurants, les CTA et les variantes de composants restent dans le thème ou les templates Blade.
