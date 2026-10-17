@if ($acquisitionSettings)
    @php
        $acquisitionConfig = [
            'enabled' => true,
            'trackingEnabled' => $acquisitionSettings->canTrack(),
            'consentEnabled' => $acquisitionSettings->consent_enabled,
            'consentMode' => $acquisitionSettings->consent_mode,
            'gtmContainerId' => $acquisitionSettings->gtm_container_id,
            'analyticsConsent' => false,
            'marketingConsent' => false,
        ];
    @endphp
    <script>
        window.dataLayer = window.dataLayer || [];
        window.MaracujaAcquisitionConfig = {{ Illuminate\Support\Js::from($acquisitionConfig) }};

        @if ($acquisitionSettings->canTrack())

        window.maracujaGtag = window.maracujaGtag || function () {
            window.dataLayer.push(arguments);
        };

        @if ($acquisitionSettings->consent_enabled)
            window.maracujaGtag('consent', 'default', {
                analytics_storage: 'denied',
                ad_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied',
                wait_for_update: 500,
            });
        @endif

        window.maracujaLoadGtm = window.maracujaLoadGtm || function () {
            if (window.maracujaGtmLoaded) {
                return;
            }

            window.maracujaGtmLoaded = true;
            window.dataLayer.push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js',
            });

            const script = document.createElement('script');
            script.async = true;
            const source = new URL('https://www.googletagmanager.com/gtm.js');
            source.searchParams.set('id', window.MaracujaAcquisitionConfig.gtmContainerId);

            // Tag Assistant uses these session parameters to obtain the preview
            // container. Normal visitors have none of them.
            const pageParameters = new URLSearchParams(window.location.search);
            ['gtm_auth', 'gtm_preview', 'gtm_debug', 'gtm_cookies_win'].forEach((name) => {
                const value = pageParameters.get(name);
                if (value) source.searchParams.set(name, value);
            });

            script.src = source.toString();
            document.head.appendChild(script);
        };

        window.maracujaIsGtmPreview = window.maracujaIsGtmPreview || function () {
            const pageParameters = new URLSearchParams(window.location.search);

            return ['gtm_auth', 'gtm_preview', 'gtm_debug'].some((name) => pageParameters.has(name))
                || document.referrer.startsWith('https://tagassistant.google.com')
                || /(?:^|;\\s*)__TAG_ASSISTANT=/.test(document.cookie);
        };

        window.maracujaSetConsent = window.maracujaSetConsent || function (choice) {
            const analyticsGranted = choice?.analytics === true;
            const marketingGranted = choice?.marketing === true;

            window.MaracujaAcquisitionConfig.analyticsConsent = analyticsGranted;
            window.MaracujaAcquisitionConfig.marketingConsent = marketingGranted;

            window.maracujaGtag('consent', 'update', {
                analytics_storage: analyticsGranted ? 'granted' : 'denied',
                ad_storage: marketingGranted ? 'granted' : 'denied',
                ad_user_data: marketingGranted ? 'granted' : 'denied',
                ad_personalization: marketingGranted ? 'granted' : 'denied',
            });

            if (analyticsGranted || marketingGranted) {
                window.maracujaLoadGtm();
            }

            window.dispatchEvent(new CustomEvent('maracuja:consent-updated', {
                detail: { analytics: analyticsGranted, marketing: marketingGranted },
            }));
        };

        @if ($acquisitionSettings->loadsContainerBeforeConsent())
            window.maracujaLoadGtm();
        @elseif ($acquisitionSettings->consent_enabled)
            // Preview is an explicit local debugging session. It does not alter
            // the consent-first behavior for ordinary public visits.
            if (window.maracujaIsGtmPreview()) window.maracujaLoadGtm();
        @endif
        @endif
    </script>
@endif
