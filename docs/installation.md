# Installation

Maracuja CMS V1 est un starter Laravel + Filament. Chaque client a sa propre installation, sa base de donnees, ses medias et sa configuration.

## Nouvelle installation locale

Creer d'abord une base MySQL vide en `utf8mb4`, puis renseigner ses acces dans
`.env`. Le nom local recommande est `maracuja_cms_starter`.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Admin:

- URL: `/admin`
- email démo: `admin@maracuja.test`
- mot de passe démo: `password`

## Configuration client minimale

Dans `.env`:

```env
APP_NAME="Nom du site"
APP_URL=https://example.com
APP_LOCALE=fr

MARACUJA_PRODUCT_NAME="Maracuja CMS"
MARACUJA_THEME=default
MARACUJA_OFFER=signature
MARACUJA_INDEXABLE=false
```

Ne passer `MARACUJA_INDEXABLE=true` qu’au moment de mise en ligne publique.

## Profils d’offre

```env
MARACUJA_OFFER=essence
MARACUJA_OFFER=signature
MARACUJA_OFFER=univers
```

Le profil indique le niveau de complexité vendu. Il ne remplace pas le cadrage fonctionnel.

Les variables `MARACUJA_MODULE_*` permettent d’activer ou retirer un module pour un projet précis.

Exemple:

```env
MARACUJA_MODULE_CONTENT_SLOTS=true
MARACUJA_MODULE_NEWS=false
```

Le module `Pages` n’est pas un module client standard. Son admin est masqué par défaut:

```env
MARACUJA_DEV_PAGES_ADMIN=false
```

Le passer à `true` seulement pour un usage développeur.

## Base de données

Le starter utilise MySQL en local, dans les tests et en production. SQLite ne
fait pas partie du parcours d'installation supporte.

Exemple:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=maracuja_cms_starter
DB_USERNAME=root
DB_PASSWORD=
```

Les tests utilisent une base distincte nommee
`maracuja_cms_starter_testing`. Elle doit rester strictement locale et ne doit
contenir aucune donnee a conserver.

Le starter force cette base dans `phpunit.xml` et bloque le démarrage des tests
si la base active n'est ni une base en mémoire ni une base dont le nom se
termine par `_testing`. Cette protection ne doit jamais être retirée dans un
projet client.

Toujours lancer la suite avec `composer test`, qui efface aussi un éventuel
cache de configuration avant PHPUnit. Ne jamais lancer `migrate:fresh`,
`migrate:refresh` ou `db:wipe` sur une base contenant des données à conserver.

Avant une migration ou une synchronisation importante, créer un snapshot :

```bash
php artisan maracuja:db:backup --name=avant-migration
```

## Livraison

Avant livraison:

```bash
php artisan maracuja:doctor
php artisan test
npm run build
php artisan view:cache
php artisan optimize:clear
```

Avant mise en ligne publique:

```bash
php artisan maracuja:doctor --production
```

Verifier ensuite:

- `/` repond en 200;
- `/admin/login` repond en 200;
- le formulaire contact fonctionne;
- les modules vendus apparaissent dans l admin;
- les modules non vendus sont absents;
- `MARACUJA_INDEXABLE=true` seulement si le site doit être indexé;
- le compte démo a été remplacé ou supprimé.

## Regle produit

Le client administré ses contenus vivants. Le développeur garde les templates, les CTA structurants, la mise en page, les presets galerie et le thème.
