@extends('layouts.site', [
    'seoTitle' => $page?->seo_title ?? ('Actualités - ' . $settings->site_name),
    'seoDescription' => $page?->seo_description ?? 'Dernières actualités publiées.',
    'seoImage' => $page?->hero_image_path,
])

@section('content')
    <x-site.hero
        :title="$page?->hero_title ?? $page?->title ?? 'Actualités'"
        :subtitle="$page?->hero_subtitle ?? $page?->excerpt ?? 'Les contenus récurrents publiés depuis l’admin.'"
        :image="\App\Support\MediaFiles::url($page?->hero_image_path)"
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
