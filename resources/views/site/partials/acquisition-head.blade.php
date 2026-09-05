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
            script.src = 'https://www.googletagmanager.com/gtm.js?id='
                + encodeURIComponent(window.MaracujaAcquisitionConfig.gtmContainerId);
            document.head.appendChild(script);
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
        @endif
        @endif
    </script>
@endif
