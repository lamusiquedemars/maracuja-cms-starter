@extends('layouts.site', [
    'seoTitle' => $label . ' - ' . $settings->site_name,
    'seoDescription' => $subtitle,
])

@section('content')
    <x-site.hero
        :eyebrow="$label"
        :title="$label"
        :subtitle="$subtitle"
        variant="page"
    />

    <x-site.breadcrumb :items="[['label' => $label]]" />

    <x-site.section>
        @if ($posts->isEmpty())
            <div class="prose">
                <p>Aucun article publié pour le moment.</p>
            </div>
        @else
            <x-site.grid columns="3">
                @foreach ($posts as $post)
                    <x-site.card
                        :title="$post->title"
                        :url="route('articles.show', $post->slug)"
                        :image="$post->image_path ? (str_starts_with($post->image_path, '/') ? $post->image_path : asset('storage/' . $post->image_path)) : '/assets/images/merle.png'"
                        variant="featured"
                    >
                        {{ $post->publicExcerpt() }}
                    </x-site.card>
                @endforeach
            </x-site.grid>
            {{ $posts->links() }}
        @endif
    </x-site.section>
@endsection
