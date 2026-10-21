<?php

// Maracuja Site existait avant l'extraction de Foundation : ses tables restent
// celles de l'application pendant la transition. Les nouvelles installations
// du package conservent la valeur par défaut (true).
return [
    'load_migrations' => false,
];
