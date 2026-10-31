<?php
$isAdminShell = false;
$menu = [
    ['profil',   '/compte',              'Mon profil'],
    ['liens',    '/compte/liens',        'Liens & boutons'],
    ['stats',    '/compte/statistiques', 'Statistiques'],
    ['cartes',   '/compte/cartes',       'Mes cartes'],
    ['securite', '/compte/securite',     'Sécurité'],
];
require __DIR__ . '/_shell.php';
