@extends('layouts.site', [
    'seoTitle' => $post->seo_title ?? $post->title,
    'seoDescription' => $post->seo_description ?? $post->publicExcerpt(),
    'seoImage' => $post->imageUrl(),
    'seoType' => 'article',
])

@section('content')
    <x-site.hero
        :eyebrow="$label"
        :title="$post->title"
        :subtitle="$post->published_at?->translatedFormat('d F Y')"
        variant="page"
    />

    <x-site.breadcrumb :items="[
        ['label' => $label, 'url' => route('articles.index')],
        ['label' => $post->title],
    ]" />

    <x-site.section container="readable">
        <article class="article-content prose">
            @if ($post->imageUrl())
                <x-site.figure
                    :src="$post->imageUrl()"
                    :alt="$post->title"
                />
            @endif

            {{ \App\Support\ArticleBlocks::render($post->body_blocks) }}

            <x-site.back-link :href="route('articles.index')" :label="'Retour à ' . strtolower($label)" />
        </article>
    </x-site.section>
@endsection
