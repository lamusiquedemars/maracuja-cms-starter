<?php

return [
    'product_name' => env('MARACUJA_PRODUCT_NAME', 'Maracuja CMS'),

    'theme' => env('MARACUJA_THEME', 'default'),

    'offer' => env('MARACUJA_OFFER', 'signature'),

    'module_manifest' => [
        'enabled' => env('MARACUJA_MODULE_MANIFEST_ENABLED', true),
    ],

    'seo' => [
        'indexable' => env('MARACUJA_INDEXABLE', false),
    ],

    'acquisition' => [
        'cremona' => [
            'enabled' => env('MARACUJA_CREMONA_ENABLED', false),
            'endpoint' => env('MARACUJA_CREMONA_ENDPOINT'),
            'reporting_endpoint' => env('MARACUJA_CREMONA_REPORTING_ENDPOINT'),
            'token' => env('MARACUJA_CREMONA_TOKEN'),
            'site_reference' => env('MARACUJA_CREMONA_SITE_REFERENCE'),
        ],
    ],

    'gallery' => [
        'slug' => env('MARACUJA_GALLERY_SLUG', 'home'),
        'layout' => env('MARACUJA_GALLERY_LAYOUT', 'grid'),
        'lightbox' => env('MARACUJA_GALLERY_LIGHTBOX', true),
    ],

    'news' => [
        'default_duration_days' => env('MARACUJA_NEWS_DEFAULT_DURATION_DAYS', 30),
    ],

    'articles' => [
        'public_path' => env('MARACUJA_ARTICLES_PUBLIC_PATH', 'articles'),
    ],

    'events' => [
        'public_path' => env('MARACUJA_EVENTS_PUBLIC_PATH', 'evenements'),
    ],

    'media' => [
        'disk' => 'public',
        'images_directory' => 'media/images',
        'documents_directory' => 'media/documents',
        'videos_directory' => 'media/videos',
        'video_thumbnails_directory' => 'media/video-thumbnails',
        'originals_disk' => 'local',
        'originals_directory' => 'media/originals',
        'ffmpeg_binary' => env('MARACUJA_FFMPEG_BINARY', 'ffmpeg'),
        'video_thumbnail_second' => 1,
        'image_max_size_kb' => 25 * 1024,
        'image_max_pixels' => 36_000_000,
        'image_variant_widths' => [640, 960, 1440, 1920],
        'image_webp_quality' => 82,
        'document_max_size_kb' => 15 * 1024,
        'video_max_size_kb' => 100 * 1024,
        'mime_types' => [
            'image/jpeg' => ['type' => 'image', 'extension' => 'jpg'],
            'image/png' => ['type' => 'image', 'extension' => 'png'],
            'image/webp' => ['type' => 'image', 'extension' => 'webp'],
            'image/heic' => ['type' => 'image', 'extension' => 'heic'],
            'image/heif' => ['type' => 'image', 'extension' => 'heif'],
            'application/pdf' => ['type' => 'document', 'extension' => 'pdf'],
            'video/mp4' => ['type' => 'video', 'extension' => 'mp4'],
            'video/webm' => ['type' => 'video', 'extension' => 'webm'],
        ],
    ],

    'conversations' => [
        'retention_days' => env('MARACUJA_CONVERSATIONS_RETENTION_DAYS', 90),
        'archive_inactive_after_hours' => env('MARACUJA_CONVERSATIONS_ARCHIVE_INACTIVE_AFTER_HOURS', 48),
        'reference_length' => env('MARACUJA_CONVERSATIONS_REFERENCE_LENGTH', 8),
        'public' => [
            'handover_message' => env(
                'MARACUJA_CONVERSATIONS_HANDOVER_MESSAGE',
                'Votre demande de prise en charge humaine a bien été enregistrée.',
            ),
        ],
        'callback' => [
            'ask_name' => 'Bien sûr. Comment souhaitez-vous que nous vous appelions ?',
            'invalid_name' => 'Indiquez simplement le prénom ou le nom que nous pouvons utiliser.',
            'ask_preference' => 'Préférez-vous être contacté par WhatsApp, téléphone ou email ?',
            'invalid_preference' => 'Répondez simplement : WhatsApp, téléphone ou email.',
            'ask_email' => 'À quelle adresse email pouvons-nous vous répondre ?',
            'ask_phone' => 'Quel numéro pouvons-nous utiliser pour vous contacter ?',
            'invalid_email' => 'Cette adresse email ne semble pas valide. Pouvez-vous la vérifier ?',
            'invalid_phone' => 'Ce numéro ne semble pas complet. Pouvez-vous le vérifier avec son indicatif ?',
            'ask_consent' => 'Autorisez-vous notre équipe à utiliser ces coordonnées uniquement pour répondre à cette demande ? Répondez oui ou non.',
            'invalid_consent' => 'Merci de répondre clairement par oui ou non.',
            'consent_refused' => 'Aucune coordonnée n’a été enregistrée. Vous pouvez continuer directement sur WhatsApp.',
            'completed' => 'Merci. Votre demande a été enregistrée et notre équipe pourra vous recontacter.',
        ],
        'notifications' => [
            'enabled' => env('MARACUJA_CONVERSATIONS_NOTIFICATIONS_ENABLED', true),
            'recipient' => env('MARACUJA_CONVERSATIONS_NOTIFICATION_EMAIL'),
            'subject' => 'Nouvelle demande de rappel depuis le site',
        ],
        'ai' => [
            'provider' => env('MARACUJA_CONVERSATIONS_AI_PROVIDER', 'fake'),
            'model' => env('OPENAI_CONVERSATIONS_MODEL', 'gpt-5.6-luna'),
            'reasoning_effort' => env('OPENAI_CONVERSATIONS_REASONING_EFFORT', 'low'),
            'max_output_tokens' => env('OPENAI_CONVERSATIONS_MAX_OUTPUT_TOKENS', 600),
            'history_messages' => env('MARACUJA_CONVERSATIONS_HISTORY_MESSAGES', 24),
            'timeout_seconds' => env('MARACUJA_CONVERSATIONS_AI_TIMEOUT', 20),
            'fallback_message' => env(
                'MARACUJA_CONVERSATIONS_AI_FALLBACK_MESSAGE',
                'Je ne peux pas répondre pour le moment. Votre demande va être transmise à une personne.',
            ),
        ],
    ],

    'modules' => [
        'site_settings' => env('MARACUJA_MODULE_SITE_SETTINGS', true),
        'notices' => env('MARACUJA_MODULE_NOTICES', true),
        'content_slots' => env('MARACUJA_MODULE_CONTENT_SLOTS', true),
        'pages' => env('MARACUJA_MODULE_PAGES', true),
        'news' => env('MARACUJA_MODULE_NEWS', true),
        'articles' => env('MARACUJA_MODULE_ARTICLES', true),
        'venues' => env('MARACUJA_MODULE_VENUES', true),
        'events' => env('MARACUJA_MODULE_EVENTS', true),
        'gallery' => env('MARACUJA_MODULE_GALLERY', true),
        'contact_form' => env('MARACUJA_MODULE_CONTACT_FORM', true),
        'inquiries' => env('MARACUJA_MODULE_INQUIRIES', true),
        'contacts' => env('MARACUJA_MODULE_CONTACTS', true),
        'conversations' => env('MARACUJA_MODULE_CONVERSATIONS', false),
        'appointments' => env('MARACUJA_MODULE_APPOINTMENTS', false),
        'audience' => env('MARACUJA_MODULE_AUDIENCE', false),
        'acquisition' => env('MARACUJA_MODULE_ACQUISITION', false),
        'campaigns' => env('MARACUJA_MODULE_CAMPAIGNS', false),
    ],

    'developer_tools' => [
        'pages_admin' => env('MARACUJA_DEV_PAGES_ADMIN', false),
        'image_optimization' => env('MARACUJA_DEV_IMAGE_OPTIMIZATION', false),
    ],

    'offers' => [
        'essence' => [
            'site_settings' => true,
            'notices' => false,
            'content_slots' => false,
            'pages' => true,
            'news' => false,
            'articles' => false,
            'venues' => false,
            'events' => false,
            'gallery' => false,
            'contact_form' => true,
            'inquiries' => false,
            'contacts' => true,
            'conversations' => false,
            'appointments' => false,
            'audience' => false,
            'acquisition' => false,
            'campaigns' => false,
        ],
        'signature' => [
            'site_settings' => true,
            'notices' => true,
            'content_slots' => true,
            'pages' => true,
            'news' => true,
            'articles' => true,
            'venues' => true,
            'events' => true,
            'gallery' => true,
            'contact_form' => true,
            'inquiries' => true,
            'contacts' => true,
            'conversations' => false,
            'appointments' => false,
            'audience' => false,
            'acquisition' => false,
            'campaigns' => false,
        ],
        'univers' => [
            'site_settings' => true,
            'notices' => true,
            'content_slots' => true,
            'pages' => true,
            'news' => true,
            'articles' => true,
            'venues' => true,
            'events' => true,
            'gallery' => true,
            'contact_form' => true,
            'inquiries' => true,
            'contacts' => true,
            'conversations' => true,
            'appointments' => true,
            'audience' => true,
            'acquisition' => false,
            'campaigns' => false,
        ],
    ],
];
