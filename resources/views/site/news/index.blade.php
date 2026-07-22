@extends('layouts.site', [
    'seoTitle' => $page?->seo_title ?? ('Actualités - ' . $settings->site_name),
    'seoDescription' => $page?->seo_description ?? 'Dernières actualités publiées.',
    'seoImage' => $page?->heroImageUrl(),
])

@section('content')
    <x-site.hero
        :title="$page?->hero_title ?? $page?->title ?? 'Actualités'"
        :subtitle="$page?->hero_subtitle ?? $page?->excerpt ?? 'Les contenus récurrents publiés depuis l’admin.'"
        :image="$page?->heroImageUrl()"
    />

    <x-site.breadcrumb :items="[
        ['label' => $page?->title ?? 'Actualités'],
    ]" />

    <x-site.section>
        <x-site.grid columns="3" class="news-list">
            @foreach ($posts as $post)
                <x-site.card :title="$post->title" :url="$post->hasDetailPage() ? route('news.show', $post->slug) : null">
                    @if ($post->is_pinned)
                        <x-site.badge>Épinglé</x-site.badge>
                    @endif

                    {{ $post->excerpt }}
                </x-site.card>
            @endforeach
        </x-site.grid>
        {{ $posts->links() }}
    </x-site.section>
@endsection
