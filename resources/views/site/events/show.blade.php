@extends('layouts.site', [
    'seoTitle' => $event->seo_title ?? $event->title,
    'seoDescription' => $event->seo_description ?? $event->publicExcerpt(),
    'seoImage' => $event->image_path,
])

@section('content')
    <x-site.hero
        :eyebrow="$label"
        :title="$event->title"
        :subtitle="$event->starts_at->translatedFormat('d F Y à H:i')"
        variant="page"
    />

    <x-site.breadcrumb :items="[
        ['label' => $label, 'url' => route('events.index')],
        ['label' => $event->title],
    ]" />

    <x-site.section container="readable">
        <article class="event-detail prose">
            @if ($event->image_path)
                <x-site.figure
                    :src="str_starts_with($event->image_path, '/') ? $event->image_path : asset('storage/' . $event->image_path)"
                    :alt="$event->title"
                />
            @endif

            <dl class="event-detail__facts">
                <div>
                    <dt>Date</dt>
                    <dd>
                        {{ $event->starts_at->translatedFormat('d F Y à H:i') }}
                        @if ($event->ends_at)
                            <br>Fin : {{ $event->ends_at->translatedFormat('d F Y à H:i') }}
                        @endif
                    </dd>
                </div>

                @if ($event->venue)
                    <div>
                        <dt>Lieu</dt>
                        <dd>
                            <strong>{{ $event->venue->name }}</strong>
                            @if ($event->venue->fullAddress())
                                <br>{{ $event->venue->fullAddress() }}
                            @endif
                        </dd>
                    </div>
                @endif

                @if ($event->status !== \App\Modules\Events\Models\Event::STATUS_SCHEDULED)
                    <div>
                        <dt>Statut</dt>
                        <dd>{{ $event->statusLabel() }}</dd>
                    </div>
                @endif
            </dl>

            @if ($event->description)
                {!! $event->description !!}
            @elseif ($event->excerpt)
                <p>{{ $event->excerpt }}</p>
            @endif

            <div class="event-detail__actions">
                @if ($event->ticket_url)
                    <x-site.button :href="$event->ticket_url">Billetterie / inscription</x-site.button>
                @endif

                @if ($event->external_url)
                    <x-site.button :href="$event->external_url" variant="secondary">En savoir plus</x-site.button>
                @endif

                @if ($event->venue?->maps_url)
                    <x-site.button :href="$event->venue->maps_url" variant="secondary">Voir la carte</x-site.button>
                @endif
            </div>

            <x-site.back-link :href="route('events.index')" :label="'Retour à ' . strtolower($label)" />
        </article>
    </x-site.section>
@endsection
