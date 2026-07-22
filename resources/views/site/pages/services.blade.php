@extends('layouts.site', [
    'seoTitle' => $page->seo_title,
    'seoDescription' => $page->seo_description,
    'seoImage' => $page->heroImageUrl(),
])

@section('content')
    <x-site.hero
        variant="page"
        :title="$page->hero_title ?? $page->title"
        :subtitle="$page->hero_subtitle ?? $page->excerpt"
        :image="$page->heroImageUrl()"
        :cta-url="$contactUrl"
        cta-label="{{ \App\Support\ContentSlots::value('services.hero.cta_label', 'Parler du projet') }}"
    />

    <x-site.breadcrumb :items="[
        ['label' => $page->title],
    ]" />

    <x-site.section
        :title="\App\Support\ContentSlots::value('services.offers.title', 'Trois niveaux, un même socle')"
        :intro="\App\Support\ContentSlots::value('services.offers.intro', 'La différence se joue sur la richesse du contenu, les modules actifs et le degré de personnalisation.')"
        heading-variant="accent">
        <x-site.grid columns="3">
            <x-site.card title="Essence" :kicker="\App\Support\ContentSlots::value('services.essence.price', 'À partir de 1500')" variant="featured">
                {{ \App\Support\ContentSlots::value('services.essence.description', 'Pages essentielles, contact, SEO de base, thème simple et administration limitée aux contenus utiles.') }}
            </x-site.card>

            <x-site.card title="Signature" :kicker="\App\Support\ContentSlots::value('services.signature.price', 'Sur devis cadre')" variant="highlight">
                {{ \App\Support\ContentSlots::value('services.signature.description', 'Structure plus riche, actualités, galerie, sections de preuve, CTA, media system et finitions thème.') }}
            </x-site.card>

            <x-site.card title="Univers" :kicker="\App\Support\ContentSlots::value('services.univers.price', 'Sur devis métier')">
                {{ \App\Support\ContentSlots::value('services.univers.description', 'Module métier client, catalogue (avec ou sans paiement), workflow spécifique ou intégration externe selon le besoin.') }}
            </x-site.card>
        </x-site.grid>
    </x-site.section>

    <x-site.section
        variant="muted"
        :title="\App\Support\ContentSlots::value('services.common.title', 'Ce qui reste commun')"
        heading-variant="underline">
        <x-site.grid columns="2">
            <x-site.feature-card title="Socle technique" icon="A">
                {{ \App\Support\ContentSlots::value('services.common.tech.text', 'Laravel, Filament, modules activables, migrations, seeders, tests et conventions de livraison.') }}
            </x-site.feature-card>
            <x-site.feature-card title="Socle front" icon="B">
                {{ \App\Support\ContentSlots::value('services.common.front.text', 'Composants Blade, CSS maison, JS progressif, media system et thèmes clients.') }}
            </x-site.feature-card>
        </x-site.grid>
    </x-site.section>

    <x-site.section>
        <x-site.cta
            :title="\App\Support\ContentSlots::value('services.cta.title', 'Une offre simple à expliquer')"
            :text="\App\Support\ContentSlots::value('services.cta.text', 'Le client choisit un niveau de site. Le développeur garde un socle commun versionné.')"
            :href="$contactUrl"
            :label="\App\Support\ContentSlots::value('services.cta.label', 'Présenter un projet')"
            inline
        />

        <div class="stack stack--md">
            <x-site.back-link :href="route('home')" label="Retour à l'accueil" />
        </div>
    </x-site.section>
@endsection
