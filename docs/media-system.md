# Maracuja Media System

Le Media System encadre les images du starter : upload, métadonnées, accessibilité, performance et affichage public.

## Objectifs

- Éviter les images sans `alt`, sans dimensions ou sans convention de stockage.
- Rendre les galeries compatibles PhotoSwipe.
- Garder un rendu responsive simple en V1.
- Préparer plus tard les conversions WebP/AVIF et thumbnails.

## Stockage V1

```txt
public/storage/galleries/{gallery-slug}
public/storage/news
public/storage/pages
public/storage/settings
```

Le dossier `public/storage` fait partie de la structure du projet, mais son
contenu reste hors Git. Il doit être accessible en écriture par PHP. Aucun
lien symbolique ni `php artisan storage:link` n'est nécessaire.

Le document root du domaine doit idéalement pointer vers le dossier `public`
du projet. Sur un hébergement LWS qui impose la racine du dépôt, le fichier
`.htaccess` et le `index.php` situés à la racine adaptent le routage. Laravel
sert alors `/storage/...` depuis `public/storage/...` grâce au contrôleur de
secours, tandis que `.htaccess` bloque les dossiers internes. Ces fichiers font
partie du code et doivent donc être déployés avec le reste du projet.

Tous les uploads visibles sur le site doivent utiliser le disque Laravel `public`. Le chemin stocké en base reste relatif au disque, par exemple:

```txt
galleries/home/photo.webp
news/actualite.webp
pages/hero.webp
site/logo.webp
```

Le front les sert ensuite via `/storage/...`.

Ne pas stocker les médias publics dans `storage/app/private`: ces fichiers peuvent apparaître dans l’admin, mais ils ne sont pas accessibles par le site public.

## Champs recommandés

Pour une image administrable :

```txt
image_path
alt_text
caption
credit
width
height
position
is_published
```

Le module Galerie utilise déjà cette structure.

## Règles alt

- Image informative : renseigner `alt_text`.
- Image décorative : `alt=""`.
- Si `alt_text` est vide dans Galerie, le starter utilise le titre de l’image comme fallback.

## Upload admin

Règles V1 :

```txt
disk: public
visibility: public
types: jpg, jpeg, png, webp
max: 5 MB
dossier: par module
```

Filament peut renommer les fichiers uploadés. C’est normal: la source de vérité est le chemin enregistré en base, pas le nom original du fichier.

Chaque champ image public suit une logique contextualisée:

- Pages: `pages`
- Galerie: `galleries/{gallery-slug}`
- Actualités: `news`
- Articles: `articles`
- Blocs image d’articles: `articles/blocks`
- Paramètres du site: `site`

L’admin propose deux gestes:

- choisir une image déjà présente dans le dossier du contexte;
- uploader une nouvelle image dans ce même dossier.

Il n’y a pas de médiathèque globale en V1. Le client choisit seulement parmi les images utiles au contexte en cours.

## Galeries

Le module Galerie utilise deux niveaux:

- `Gallery`: collection publiée, par exemple `home`;
- `GalleryImage`: photos rattachées à une collection.

Le template choisit la galerie à afficher par slug:

```env
MARACUJA_GALLERY_SLUG=home
```

Les galeries système comme `home` ne sont pas supprimables depuis l’admin, afin de protéger les templates qui les utilisent. Les photos se gèrent dans la galerie, via l’onglet `Photos`.

## Dimensions et moules d’affichage

La galerie renseigne automatiquement `width` et `height` quand l’image est enregistrée et accessible sur le disque public. Ces dimensions alimentent la lightbox PhotoSwipe.

Le rendu front reste cadré par les composants:

- hero: image de fond en `cover`;
- galerie: ratios définis par le preset `grid`, `featured` ou `carousel`;
- article: image contrainte par la largeur du contenu;
- cartes média: image en `cover`.

La V1 ne génère pas encore de thumbnails, WebP/AVIF ou recadrages. Ces optimisations viendront seulement si le besoin projet le justifie.

## Composants Blade

```blade
<x-site.image />
<x-site.figure />
<x-site.gallery />
<x-site.lightbox-gallery />
```

Image :

```blade
<x-site.image
    src="galleries/home/photo.webp"
    alt="Détail d'un archet"
    width="1200"
    height="800"
/>
```

Figure :

```blade
<x-site.figure
    src="galleries/home/photo.webp"
    alt="Détail d'un archet"
    caption="Détail de finition"
    credit="Atelier Ivo Incidit"
    width="1200"
    height="800"
/>
```

Galerie avec lightbox :

```blade
<x-site.lightbox-gallery :images="$galleryImages" />
```

## Presets galerie vendus

Le client gère seulement les images, textes, crédits et ordre dans le module Galerie.

Le type de rendu est une décision de structure vendue avec le site :

```env
MARACUJA_GALLERY_LAYOUT=grid
```

Valeurs possibles :

```txt
grid      Galerie simple en grille.
featured  Portfolio avec première image mise en avant.
carousel  Carousel horizontal avec Embla.
```

Le template utilise :

```blade
<x-site.gallery
    :images="$galleryImages"
    :layout="config('maracuja.gallery.layout')"
    :lightbox="config('maracuja.gallery.lightbox')"
/>
```

Le client ne choisit pas `grid`, `featured` ou `carousel` dans l’admin. Il administre les contenus du module Galerie.

## Configuration

```env
MARACUJA_GALLERY_SLUG=home
MARACUJA_GALLERY_LAYOUT=featured
MARACUJA_GALLERY_LIGHTBOX=true
```

Les textes visibles de la section galerie ne sont pas en config. Ils viennent de la galerie elle-même (`title`, `intro`) ou des `Content Slots` de secours `gallery.title` et `gallery.intro`.

## PhotoSwipe

Pour fonctionner au mieux, chaque image de lightbox doit avoir :

```txt
data-pswp-width
data-pswp-height
```

Si les dimensions sont absentes, le composant utilise un fallback. En production, les dimensions doivent être renseignées.

## Prochaines évolutions

- Lire automatiquement largeur/hauteur après upload.
- Générer thumbnails.
- Générer WebP/AVIF.
- Ajouter un champ `is_decorative`.
- Ajouter un media picker réutilisable pour Pages, Actualités et modules métier.
