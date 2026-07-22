@extends('layouts.site', [
    'seoTitle' => $page->seo_title,
    'seoDescription' => $page->seo_description,
    'seoImage' => $page->heroImageUrl(),
])

@section('content')
    <x-site.hero
        :title="$page->hero_title ?? $page->title"
        :subtitle="$page->hero_subtitle ?? $page->excerpt"
        :image="$page->heroImageUrl()"
    />

    <x-site.breadcrumb :items="[
        ['label' => $page->title],
    ]" />

    <x-site.section container="narrow" inner-class="prose">
        <h2>{{ $page->title }}</h2>
        @if ($page->excerpt)
            <p class="lead">{{ $page->excerpt }}</p>
        @endif
        @if ($page->content)
            {!! strip_tags($page->content, '<p><br><strong><b><em><i><u><ul><ol><li><a><sup><sub>') !!}
        @endif

        <x-site.back-link :href="route('home')" label="Retour à l'accueil" />
    </x-site.section>
@endsection
