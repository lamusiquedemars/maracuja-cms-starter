<div style="font-family: Arial, sans-serif; color: #222; line-height: 1.55; max-width: 640px;">
    @php
        $body = $segmentMessage->bodyForEmail($message ?? null);
    @endphp

    @if (str_contains($body, '<'))
        {!! $body !!}
    @else
        {!! nl2br(e($body)) !!}
    @endif

    <hr style="border: 0; border-top: 1px solid #ddd; margin: 28px 0 16px;">

    <p style="color: #666; font-size: 13px;">
        Vous recevez ce message car votre adresse figure dans la liste de contacts de l’atelier.
        @if (empty($isPreview) && $contact->unsubscribe_token)
            <br>
            <a href="{{ route('audience.unsubscribe', ['token' => $contact->unsubscribe_token]) }}" style="color: #555;">
                Ne plus recevoir ces messages
            </a>
        @elseif (! empty($isPreview))
            <br>
            <span style="color: #555;">Lien de désinscription masqué dans l’aperçu.</span>
        @endif
    </p>
</div>
