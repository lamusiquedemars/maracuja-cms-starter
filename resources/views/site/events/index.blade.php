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
        @if ($events->isEmpty())
            <div class="prose">
                <p>Aucun événement annoncé pour le moment.</p>
            </div>
        @else
            <div class="event-list">
                @foreach ($events as $event)
                    <article class="event-card">
                        <div class="event-card__date">
                            <span>{{ $event->starts_at->translatedFormat('d') }}</span>
                            <strong>{{ $event->starts_at->translatedFormat('M') }}</strong>
                        </div>

                        <div class="event-card__body">
                            <p class="event-card__meta">
                                {{ $event->starts_at->translatedFormat('d F Y à H:i') }}
                                @if ($event->venueLabel())
                                    · {{ $event->venueLabel() }}
                                @endif
                            </p>
                            <h2><a href="{{ route('events.show', $event->slug) }}">{{ $event->title }}</a></h2>
                            @if ($event->publicExcerpt())
                                <p>{{ $event->publicExcerpt() }}</p>
                            @endif
                            @if ($event->status !== \App\Modules\Events\Models\Event::STATUS_SCHEDULED)
                                <span class="event-card__status">{{ $event->statusLabel() }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            {{ $events->links() }}
        @endif
    </x-site.section>
@endsection
