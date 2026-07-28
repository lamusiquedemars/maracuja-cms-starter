@extends('layouts.site')

@section('content')
    <x-site.section>
        <x-site.heading
            eyebrow="Rendez-vous"
            title="Choisissez un créneau"
            intro="La réservation est assurée par le service configuré par le site."
        />

        @if ($isDemo)
            <div class="notice">
                <strong>Mode démonstration :</strong> aucun rendez-vous réel ne sera créé.
            </div>
        @endif

        <p>
            <x-site.button :href="$appointment->booking_url">
                Ouvrir la page de réservation
            </x-site.button>
        </p>
    </x-site.section>
@endsection
