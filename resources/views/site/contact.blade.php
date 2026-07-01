@extends('layouts.site', [
    'seoTitle' => $page?->seo_title ?? ('Contact - ' . $settings->site_name),
    'seoDescription' => $page?->seo_description ?? ('Contacter ' . $settings->site_name),
    'seoImage' => $page?->hero_image_path,
])

@section('content')
    <x-site.hero
        :title="$page?->hero_title ?? $page?->title ?? 'Contact'"
        :subtitle="$page?->hero_subtitle ?? $page?->excerpt ?? 'Un formulaire simple pour envoyer un message.'"
        :image="\App\Support\MediaFiles::url($page?->hero_image_path)"
    />

    <x-site.section inner-class="contact-layout">
        @if (session('status'))
            <p class="notice">{{ session('status') }}</p>
        @endif

        <form method="post" action="{{ route('contact.store') }}" class="contact-form" data-form>
            @csrf
            <input type="text" name="website" value="" autocomplète="off" tabindex="-1" style="position:absolute; left:-9999px; top:auto; width:1px; height:1px; overflow:hidden;">

            @if ($settings->contact_form_show_name)
                <label>
                    Nom
                    <input name="name" value="{{ old('name') }}" required>
                    @error('name') <small>{{ $message }}</small> @enderror
                </label>
            @endif

            <label>
                Email
                <input name="email" type="email" value="{{ old('email') }}" required>
                @error('email') <small>{{ $message }}</small> @enderror
            </label>

            @if ($settings->contact_form_show_phone)
                <label>
                    Téléphone
                    <input name="phone" value="{{ old('phone') }}">
                </label>
            @endif

            @if ($settings->contact_form_show_subject)
                <label>
                    Sujet
                    <input name="subject" value="{{ old('subject') }}">
                </label>
            @endif

            <label class="full">
                Message
                <textarea name="message" rows="7" required>{{ old('message') }}</textarea>
                @error('message') <small>{{ $message }}</small> @enderror
            </label>
            <x-site.button type="submit">Envoyer</x-site.button>
        </form>
    </x-site.section>
@endsection
