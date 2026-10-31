<?php
$isAdminShell = false;
$menu = [
    ['profil',   '/compte',              t('Mon profil')],
    ['liens',    '/compte/liens',        t('Liens & boutons')],
    ['stats',    '/compte/statistiques', t('Statistiques')],
    ['cartes',   '/compte/cartes',       t('Mes cartes')],
    ['securite', '/compte/securite',     t('Sécurité')],
];
require __DIR__ . '/_shell.php';
