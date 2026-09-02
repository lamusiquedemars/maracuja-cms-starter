@if ($acquisitionSettings?->canTrack())
    @if ($acquisitionSettings->consent_enabled)
        @php
            $privacyUrl = $acquisitionSettings->privacy_policy_url ?: route('pages.show', 'mentions-legales');
        @endphp
        <aside class="acquisition-consent" data-acquisition-consent-banner aria-label="Choix des cookies" hidden>
            <strong>Mesure d’audience</strong>
            <p>Avec votre accord, nous utilisons une mesure d’audience pour comprendre l’usage du site et améliorer son contenu.</p>
            <p><a href="{{ $privacyUrl }}">En savoir plus dans notre politique de confidentialité</a></p>
            <div class="acquisition-consent__actions">
                <button class="btn btn--primary" type="button" data-acquisition-consent="granted">Accepter</button>
                <button class="btn btn--secondary" type="button" data-acquisition-consent="denied">Refuser</button>
            </div>
        </aside>
    @else
        <noscript>
            <iframe
                src="https://www.googletagmanager.com/ns.html?id={{ urlencode($acquisitionSettings->gtm_container_id) }}"
                height="0"
                width="0"
                style="display:none;visibility:hidden"
                title="Google Tag Manager"
            ></iframe>
        </noscript>
    @endif
@endif
