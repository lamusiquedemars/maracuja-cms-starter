<?php

// Transition : le Starter conserve temporairement ses migrations historiques
// Media. Le package partagé est installé et testé sans recréer les tables.
return [
    'load_migrations' => false,
    'public_disk' => env('MARACUJA_MEDIA_PUBLIC_DISK', 'public'),
    'originals_disk' => env('MARACUJA_MEDIA_ORIGINALS_DISK', 'local'),
    'processing_disk' => env('MARACUJA_MEDIA_PROCESSING_DISK', 'local'),
];
