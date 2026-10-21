<?php

// Transition : les tables et migrations historiques restent actives tant que
// les ressources Filament et les routes locales n'ont pas été remplacées.
// Le package est déjà installé pour permettre une bascule contrôlée.
return [
    'load_migrations' => false,
    // Les écrans et points d'entrée utilisent désormais les packages Core.
    'use_legacy_resources' => false,
];
