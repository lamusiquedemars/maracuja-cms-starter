@props([
    'media',
    'poster' => null,
    'preload' => 'metadata',
])

@php
    $posterUrl = filled($poster) ? $poster : $media?->thumbnailUrl();
@endphp

<video {{ $attributes->merge(['controls' => true, 'preload' => $preload]) }} @if ($posterUrl) poster="{{ $posterUrl }}" @endif>
    <source src="{{ $media->publicPath() }}" type="{{ $media->mime_type }}">
    Votre navigateur ne peut pas lire cette vidéo.
</video>
