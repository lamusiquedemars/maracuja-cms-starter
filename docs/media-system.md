# Maracuja Media System

Le Media System encadre les images du starter : upload, métadonnées, accessibilité, performance et affichage public.

## Objectifs

- Éviter les images sans `alt`, sans dimensions ou sans convention de stockage.
- Rendre les galeries compatibles PhotoSwipe.
- Garder un rendu responsive simple en V1.
- Préparer plus tard les conversions WebP/AVIF et thumbnails.

## Contrat de stockage Maracuja

```txt
public/storage/media/images/YYYY/MM/{ulid}.{extension}
public/storage/media/documents/YYYY/MM/{ulid}.{extension}
```

Ce contrat est identique pour tous les sites Maracuja, quel que soit
l'hébergeur. Le chemin absolu varie selon le serveur, mais le disque Laravel
`public` pointe toujours vers `public_path('storage')`. Aucun lien symbolique
ni `php artisan storage:link` n'est nécessaire.

Le dossier `public/storage` fait partie de la structure du projet, son contenu
reste hors Git et il doit être accessible en écriture par PHP. Tous les médias
publics ajoutés depuis l'administration sont catalogués par le module Media et
stockés exclusivement dans l'une des deux familles suivantes:

- `media/images`: images affichables sur le site, dans l'administration et
  dans les emails;
- `media/documents`: documents explicitement destinés au téléchargement
  public.

Le contexte métier n'apparaît pas dans le chemin physique. Une image utilisée
par une page, une actualité, une galerie et un message reste un seul média. Ses
usages sont enregistrés en base de données.

Le document root du domaine doit idéalement pointer vers le dossier `public`
du projet. Sur un hébergement LWS qui impose la racine du dépôt, le fichier
`.htaccess` et le `index.php` situés à la racine adaptent le routage. Laravel
sert alors `/storage/...` depuis `public/storage/...` grâce au contrôleur de
secours, tandis que `.htaccess` bloque les dossiers internes. Ces fichiers font
partie du code et doivent donc être déployés avec le reste du projet.

Tous les uploads visibles sur le site utilisent le disque Laravel `public`.
Le chemin stocké en base reste relatif au disque, par exemple:

```txt
media/images/2026/07/01KXYZ.webp
media/documents/2026/07/01KABC.pdf
```

Le front les sert ensuite via `/storage/...`.

Ne jamais enregistrer en base un chemin absolu, un chemin commençant par
`public/storage` ou une URL liée au domaine courant. Les URL sont calculées au
moment du rendu afin que les données restent portables entre local, LWS et les
autres hébergeurs.

### Stockage privé et exceptions techniques

`storage/app/private` est réservé aux fichiers non publics:

```txt
storage/app/private/imports
storage/app/private/exports
storage/app/private/temporary
storage/app/private/livewire-tmp
```

`livewire-tmp` est un emplacement transitoire géré par Livewire. Aucun fichier
métier permanent ne doit y rester. Les imports Audience ne sont pas des médias
publics et ne figurent pas dans la médiathèque.

Les assets livrés avec le code et suivis par Git, par exemple `public/build` ou
`public/demo`, ne sont pas des médias administrables et restent hors du module
Media.

### Emplacements interdits

Après migration, aucun média public administrable ne doit subsister dans:

```txt
storage/app/public
storage/app/private
public/storage/pages
public/storage/news
public/storage/articles
public/storage/events
public/storage/galleries
public/storage/site
public/storage/settings
public/storage/uploads
public/storage/attachments
public/storage/ (fichiers directement à la racine)
```

Tout dossier public non déclaré est une anomalie à auditer, migrer puis
supprimer. `storage/app/public` n'est pas utilisé par Maracuja et ne doit pas
être recréé par une installation ou un déploiement.

## Champs recommandés

Pour une image administrable :

```txt
media_id
position
is_published
```

Le média central porte son chemin, son texte alternatif, sa légende, son
crédit, ses dimensions et ses informations techniques. Le modèle métier ne
stocke que sa référence et les propriétés propres à son usage, comme la
position ou l'état de publication.

## Règles alt

- Image informative : renseigner `alt_text`.
- Image décorative : `alt=""`.
- Si `alt_text` est vide dans Galerie, le starter utilise le titre de l’image comme fallback.

## Upload admin

Règles pour les images publiques:

```txt
disk: public
visibility: public
types: jpg, jpeg, png, webp
max: 5 MB
dossier: media/images/YYYY/MM
```

Règles initiales pour les documents publics:

```txt
disk: public
visibility: public
types: pdf
max: 15 MB
dossier: media/documents/YYYY/MM
```

Le type MIME est vérifié à partir du contenu et pas seulement de l'extension.
Les formats exécutables ou actifs, dont PHP, HTML et SVG téléversé, sont
refusés. D'autres formats de documents ne sont ajoutés qu'en réponse à un
besoin explicite et avec leurs propres règles de validation.

Le nom physique est un ULID immuable et sûr. Le nom original et le nom
d'affichage sont conservés dans le catalogue Media. La source de vérité est
l'enregistrement du média, pas son nom physique.

Tous les uploads administrables passent par le module Media, y compris ceux
initiés depuis un champ métier ou un éditeur riche. Les composants Filament ne
doivent pas appeler directement `FileUpload` ou les pièces jointes natives du
`RichEditor` vers un autre dossier public.

Le Media Picker filtre les choix selon le contexte sans changer leur stockage:

- un champ image propose uniquement les images;
- un champ document propose uniquement les documents autorisés;
- un éditeur riche sélectionne ou crée d'abord un média catalogué;
- les messages SMTP et Brevo réutilisent les mêmes médias publics.

Les sélecteurs compacts affichent une vignette carrée de l’image originale,
son nom, ses dimensions et son poids. Les documents affichent un repère PDF.
Cette prévisualisation ne crée aucune copie physique : le navigateur contraint
simplement l’image originale à la taille d’affichage. De vraies miniatures ne
seront générées que si le volume de la médiathèque le justifie.

Dans un formulaire Filament, le picker est toujours relié à une relation
Eloquent et jamais à un chemin de fichier:

```php
MediaPicker::make('hero_media_id')
    ->relationship('heroMedia', 'display_name')
    ->imagesOnly();

MediaPicker::make('document_media_id')
    ->relationship('documentMedia', 'display_name')
    ->documentsOnly();
```

Le composant utilise le `ModalTableSelect` natif de Filament. Il ouvre une
grille recherchable et filtrable, permet de sélectionner un média existant et
propose la même action d'upload contrôlée que la page « Médias ».

## Catalogue et usages

Le modèle central Media conserve au minimum le type, le chemin relatif, les
noms physique et original, le type MIME, l'extension, le poids, l'empreinte,
les dimensions des images et les métadonnées éditoriales.

Les champs structurés référencent un média par identifiant. Les blocs JSON et
les éditeurs riches enregistrent également une référence traçable. La
suppression physique est refusée tant qu'un usage existe dans un modèle, un
bloc, un contenu riche, un message ou un snapshot d'email.

## Migration et assainissement

Avant toute migration ou opération de nettoyage, créer un snapshot logique de
la base courante :

```bash
php artisan maracuja:db:backup --name=avant-migration-medias
```

Le snapshot est écrit hors Git dans `storage/app/private/database-backups`. Il
contient les colonnes et lignes de toutes les tables. L'absence de
`mysqldump` sur l'hébergement ne dispense jamais de cette sauvegarde.

### Déploiement sur un autre site Maracuja

Chaque site est traité séparément. Ne jamais réutiliser le manifeste, les
identifiants de médias ou le snapshot d'un autre site.

Ordre obligatoire :

1. déployer le code et vérifier la configuration du disque `public` ;
2. créer un snapshot `maracuja:db:backup` et le conserver hors du serveur ;
3. exécuter `maracuja:media:audit` et archiver sa sortie ;
4. créer un nouveau manifeste avec `maracuja:media:migrate` ;
5. relire les exclusions et références avant toute application ;
6. appliquer le manifeste, tester le front, l'administration et les emails ;
7. attendre la validation fonctionnelle avant `--cleanup` ;
8. relancer l'audit et exiger zéro anomalie ;
9. conserver le snapshot et le manifeste pendant la période de garantie.

Une référence en base non prise en charge bloque l'application du manifeste :
elle doit être migrée explicitement, jamais ignorée ou remplacée par
approximation.

Les emplacements existants ne constituent pas une architecture historique à
conserver. Ils sont des sources de migration vers le contrat unique.

La migration doit:

1. inventorier les fichiers des stockages public, ancien public et privé;
2. calculer une empreinte SHA-256 et regrouper les copies identiques;
3. relever les références dans les colonnes, le JSON et le HTML;
4. produire un manifeste `ancien chemin -> média -> nouveau chemin` avant
   toute écriture;
5. créer un seul média catalogué par fichier utile;
6. déplacer la copie retenue sous `public/storage/media`;
7. mettre à jour et tester toutes les références;
8. supprimer les copies et dossiers obsolètes après validation;
9. confirmer qu'aucun fichier ou dossier fantôme ne subsiste.

Le mode `--dry-run` est obligatoire avant chaque migration de site. Le starter
est migré et validé en premier. Les autres sites sont ensuite traités
séparément avec la même commande et le même contrat, sans modification pendant
le prototype du starter.

La commande Maracuja sépare explicitement la préparation et l’écriture :

```bash
# Crée seulement un manifeste privé. Aucun média n'est modifié.
php artisan maracuja:media:migrate --manifest=nom-du-plan.json

# Vérifie les empreintes, copie vers le stockage canonique et crée le catalogue.
# Les sources historiques sont conservées.
php artisan maracuja:media:migrate --apply --manifest=nom-du-plan.json

# Revérifie les copies canoniques puis supprime les copies historiques.
php artisan maracuja:media:migrate --cleanup --manifest=nom-du-plan.json

# Restaure les anciens chemins nettoyés puis supprime uniquement les médias
# créés par le plan, s'ils ne sont pas utilisés.
php artisan maracuja:media:migrate --rollback --manifest=nom-du-plan.json
```

Le manifeste est stocké dans `storage/app/private/media-migrations`. Il contient
les sources, copies identiques, exclusions, empreintes, destinations et
identifiants de catalogue. Une application est refusée si une source a changé
depuis la création du plan ou si des références en base nécessitent un
migrateur qui n'est pas encore disponible.

## Audit permanent

L'audit Media signale comme erreur:

- un média public hors de `public/storage/media`;
- un fichier métier dans `storage/app/public`;
- un média public dans `storage/app/private`;
- un chemin absolu ou une URL de domaine stocké en base;
- une référence vers un fichier absent;
- un fichier public sans entrée Media;
- une entrée Media sans fichier;
- un dossier public non déclaré;
- un upload Filament configuré vers un ancien dossier métier.

Ces contrôles doivent être disponibles dans `maracuja:media:audit`, intégrés à
`maracuja:doctor` et couverts par les tests automatisés.

## Galeries

Le module Galerie utilise deux niveaux métier:

- `Gallery`: collection publiée, par exemple `home`;
- `GalleryImage`: photos rattachées à une collection.

`GalleryImage` référence un média central. Il ne crée pas de dossier physique
propre à la galerie et ne duplique pas le fichier lorsqu'une image est
réutilisée ailleurs.

Le template choisit la galerie à afficher par slug:

```env
MARACUJA_GALLERY_SLUG=home
```

Les galeries système comme `home` ne sont pas supprimables depuis l’admin, afin de protéger les templates qui les utilisent. Les photos se gèrent dans la galerie, via l’onglet `Photos`.

## Dimensions et moules d’affichage

Le module Media renseigne automatiquement `width` et `height` à l'upload ou à
la migration. La galerie lit ces dimensions pour alimenter la lightbox
PhotoSwipe.

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
    src="media/images/2026/07/01KXYZ.webp"
    alt="Détail d'un archet"
    width="1200"
    height="800"
/>
```

Figure :

```blade
<x-site.figure
    src="media/images/2026/07/01KXYZ.webp"
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

- Générer éventuellement des thumbnails et des conversions WebP/AVIF derrière
  un service Maracuja, sans changer le contrat de stockage public.
- Ajouter un champ `is_decorative` aux images si le besoin éditorial est
  confirmé.

## Éditeurs riches et messages ciblés

Tous les contenus riches du starter utilisent `MaracujaRichEditor`. Son bouton
« Insérer une image de la médiathèque » insère l’URL publique canonique et un
identifiant `media-{id}` dans le nœud image. Cet identifiant alimente
`media_usages` à l’enregistrement du contenu et protège le média contre une
suppression accidentelle.

Chaque sélecteur média doit toujours proposer les deux parcours suivants :

- choisir un média déjà présent sur le site ;
- importer un fichier depuis l’ordinateur.

Un import depuis l’ordinateur crée d’abord une entrée du catalogue via
`MediaStorageService`, dans le chemin canonique correspondant, puis sélectionne
ce nouveau média. Il ne constitue jamais un upload direct vers un dossier du
module appelant.

L’upload de pièces jointes natif du RichEditor Filament est désactivé : il ne
doit jamais créer un fichier public hors du catalogue.

Pour les messages ciblés :

- les images intégrées au corps viennent de `media/images` et fonctionnent dans
  un envoi standard comme dans une campagne Brevo ;
- « Insérer un document » choisit ou importe un PDF de `media/documents`, puis
  ajoute automatiquement un lien de téléchargement au corps du message ;
- le libellé du lien reprend le nom du média et reste modifiable ;
- ce parcours unique fonctionne dans tous les modes d’envoi et évite d’exposer
  au client les différences techniques entre fournisseurs.

Le corps et le snapshot HTML conservés en base utilisent toujours des chemins
portables `/storage/...`. Pour Brevo, Maracuja transforme ces chemins en URL
absolues uniquement dans la charge envoyée à l’API. Le snapshot ne mémorise
donc jamais le domaine local ou celui d’un hébergeur.

## État du starter après migration

Le starter a été migré vers le catalogue central avec le manifeste privé
`starter-phase8-plan.json`. Les copies et dossiers publics hérités ont été
nettoyés après validation. Le manifeste conserve les informations nécessaires
au rollback et les snapshots de base restent dans le stockage privé hors Git.

L’audit final du 22 juillet 2026 retourne zéro anomalie. Les doublons encore
inventoriés sont limités aux imports Audience privés et aux fichiers
transitoires `livewire-tmp`; ils ne constituent pas des médias publics hors du
catalogue.
