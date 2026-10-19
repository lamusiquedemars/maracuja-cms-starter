<?php

// Transition : l'application conserve ses migrations historiques Gallery.
// Le module partagé les utilise sans recréer les tables.
return [
    'load_migrations' => false,
    'system_slug' => config('maracuja.gallery.slug', 'home'),
    'legacy_disk' => 'public',
];
