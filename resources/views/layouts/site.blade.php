<!doctype html>
<html lang="fr">
<head>
    @php
        $seo = \App\Support\Seo::make($settings, [
            'title' => $seoTitle ?? null,
            'description' => $seoDescription ?? null,
            'image' => $seoImage ?? null,
            'type' => $seoType ?? null,
            'canonical' => $canonical ?? null,
        ]);
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="robots" content="{{ $seo['robots'] }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">

    <meta property="og:site_name" content="{{ $seo['site_name'] }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:type" content="{{ $seo['type'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    @if ($seo['image'])
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    @if ($seo['image'])
        <meta name="twitter:image" content="{{ $seo['image'] }}">
    @endif

    @if ($settings->favicon_path)
        <link rel="icon" href="{{ \App\Support\Seo::absoluteUrl($settings->favicon_path) }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-shell theme-{{ config('maracuja.theme', 'default') }}">
    <header class="site-header container" data-nav>
        <a class="site-brand" href="{{ route('home') }}">
            <span class="site-brand__mark">M</span>
            <span>
                <strong>{{ $settings->site_name }}</strong>
                @if ($settings->baseline)
                    <small>{{ $settings->baseline }}</small>
                @endif
            </span>
        </a>

        <button class="btn btn--secondary nav-toggle" data-nav-toggle type="button">
            Menu
        </button>

        <nav class="site-nav" data-nav-menu aria-label="Navigation principale">
            <a href="{{ route('home') }}">Accueil</a>
            @if (\App\Support\Modules::enabled('news'))
                <a href="{{ route('news.index') }}">Actualités</a>
            @endif
            @if (\App\Support\Modules::enabled('articles'))
                <a href="{{ route('articles.index') }}">{{ \App\Support\ContentSlots::value('articles.public_label', 'Articles') }}</a>
            @endif
            @if (\App\Support\Modules::enabled('events'))
                <a href="{{ route('events.index') }}">{{ \App\Support\ContentSlots::value('events.public_label', 'Événements') }}</a>
            @endif
            @if (\App\Support\Modules::enabled('pages'))
                <a href="{{ route('pages.show', 'services') }}">Services</a>
            @endif
            @if (\App\Support\Modules::enabled('contact_form'))
                <a href="{{ route('contact') }}">Contact</a>
            @endif
            <a href="/admin">Admin</a>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer container">
        <p>&copy; {{ now()->year }} {{ $settings->site_name }}</p>
        @if ($settings->contact_email)
            <a href="mailto:{{ $settings->contact_email }}">{{ $settings->contact_email }}</a>
        @endif
        @if (\App\Support\Modules::enabled('pages'))
            <a href="{{ route('pages.show', 'mentions-legales') }}">Mentions légales</a>
        @endif
    </footer>

    <button class="btn btn--primary back-to-top" type="button" data-back-to-top hidden aria-label="Retour en haut">
        <span class="back-to-top__icon" aria-hidden="true">↑</span>
    </button>
</body>
</html>
