<?php

return [
    'product_name' => env('MARACUJA_PRODUCT_NAME', 'Maracuja CMS'),

    'theme' => env('MARACUJA_THEME', 'default'),

    'offer' => env('MARACUJA_OFFER', 'signature'),

    'seo' => [
        'indexable' => env('MARACUJA_INDEXABLE', false),
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
        'image_max_size_kb' => 5 * 1024,
        'document_max_size_kb' => 15 * 1024,
        'mime_types' => [
            'image/jpeg' => ['type' => 'image', 'extension' => 'jpg'],
            'image/png' => ['type' => 'image', 'extension' => 'png'],
            'image/webp' => ['type' => 'image', 'extension' => 'webp'],
            'application/pdf' => ['type' => 'document', 'extension' => 'pdf'],
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
            'history_messages' => env('MARACUJA_CONVERSATIONS_HISTORY_MESSAGES', 12),
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
            'campaigns' => false,
        ],
    ],
];
