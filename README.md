# Maracuja CMS Starter

Maracuja CMS is a Laravel + Filament starter for building focused, administrable showcase websites. The V1 target is simple: one installation per client, a custom Blade frontend, and a Filament admin that only exposes the modules enabled for that project.

## V1 Modules

- Site Settings: identity, SEO defaults, contact details, social links.
- Pages: developer-only page metadata and protected templates, not a client page builder.
- Content Slots: named short values used by coded templates.
- Notices: short dated messages such as opening hours or temporary alerts.
- News: recurring editorial content with publication windows, pinned posts and optional detail pages.
- Articles: structured editorial pages with reusable blocks, for Confluence-style long-form content without a page builder.
- Gallery: ordered image entries with gallery presets selected by the project.
- Contact Form: public form and email notification.
- Inquiries: stored contact requests and admin follow-up.
- Audience: contacts, segments and targeted messages, currently experimental.

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Admin URL: `/admin`

Demo admin:

- Email: `admin@maracuja.test`
- Password: `password`

## Offer Profiles

The commercial profile is configured with:

```env
MARACUJA_OFFER=essence
MARACUJA_OFFER=signature
MARACUJA_OFFER=univers
```

- `essence`: pages, contact and settings.
- `signature`: full showcase starter with notices, news, articles and gallery.
- `univers`: structured business module or connected use case without becoming fully custom.

## Module Toggles

Modules live in `config/maracuja.php` and can be overridden through environment variables:

```env
MARACUJA_MODULE_NOTICES=true
MARACUJA_MODULE_CONTENT_SLOTS=true
MARACUJA_MODULE_PAGES=true
MARACUJA_MODULE_NEWS=true
MARACUJA_MODULE_ARTICLES=true
MARACUJA_MODULE_GALLERY=true
MARACUJA_MODULE_CONTACT_FORM=true
MARACUJA_MODULE_INQUIRIES=true
MARACUJA_MODULE_AUDIENCE=false
MARACUJA_MODULE_CAMPAIGNS=false
MARACUJA_MODULE_APPOINTMENTS=false
MARACUJA_DEV_PAGES_ADMIN=false
```

Disabled modules disappear from Filament navigation and their public routes return 404.

Article URLs and public wording can be project-specific:

```env
MARACUJA_ARTICLES_PUBLIC_LABEL=Articles
MARACUJA_ARTICLES_PUBLIC_PATH=articles
```

## Front Presets

The client edits gallery images, not the gallery structure. The project chooses the sold layout:

```env
MARACUJA_GALLERY_LAYOUT=grid
MARACUJA_GALLERY_LAYOUT=featured
MARACUJA_GALLERY_LAYOUT=carousel
```

## Documentation

- `docs/installation.md`: fresh install and delivery checklist.
- `docs/recap-general.md`: current workspace recap and general remaining work.
- `docs/offer-profiles.md`: Essence, Signature and Univers profiles.
- `docs/content-admin.md`: what clients can safely edit.
- `docs/contact-flow.md`: contact form, inquiries and audience flow.
- `docs/audience.md`: contacts, segments and targeted messages.
- `docs/catalogue-metier.md`: pattern for business catalog modules.
- `docs/front-system.md`: Blade/CSS component system.
- `docs/media-system.md`: images, galleries and lightbox.
- `docs/js-system.md`: progressive JavaScript modules.
- `docs/seo-system.md`: SEO, sitemap and robots.
- `docs/theme-system.md`: themes and client visual variants.

## Delivery Check

Run the Maracuja health check before handing over a client installation:

```bash
php artisan maracuja:doctor
php artisan maracuja:doctor --production
```

## Product Direction

This repository is the reference application for Maracuja Site. Shared
capabilities are progressively consumed as private Composer packages from
Maracuja Site Core. During a module transition, the local implementation and
its historical migrations remain until the package has fully replaced it.

The intended path is:

1. Use this starter for the first Maracuja CMS client installations.
2. Keep changes versioned through the changelog.
3. Extract stable shared behavior into a private Laravel package after several real projects.
