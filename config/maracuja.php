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
            'audience' => true,
            'campaigns' => false,
        ],
    ],
];
