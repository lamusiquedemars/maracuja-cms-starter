@extends('layouts.site', [
    'seoTitle' => $post->seo_title ?? $post->title,
    'seoDescription' => $post->seo_description ?? $post->excerpt,
    'seoImage' => $post->imageUrl(),
    'seoType' => 'article',
])

@section('content')
    <x-site.hero :title="$post->title" :subtitle="$post->excerpt" />

    <x-site.breadcrumb :items="[
        ['label' => 'Actualités', 'url' => route('news.index')],
        ['label' => $post->title],
    ]" />

    <x-site.section container="narrow" inner-class="prose">
        {!! $post->content !!}

        <x-site.back-link :href="route('news.index')" label="Retour aux actualités" />
    </x-site.section>
@endsection
