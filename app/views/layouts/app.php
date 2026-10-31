<?php
$isAdminShell = false;
$menu = [
    ['profil',    '/compte',              t('Mon profil'),      null],
    ['liens',     '/compte/liens',        t('Liens & boutons'), 'edit'],
    ['prospects', '/compte/prospects',    t('Prospects'),       'leads'],
    ['stats',     '/compte/statistiques', t('Statistiques'),    'stats'],
    ['cartes',    '/compte/cartes',       t('Mes cartes'),      null],
    ['forfait',   '/compte/forfait',      t('Forfait'),         null],
    ['securite',  '/compte/securite',     t('Sécurité'),        null],
];
require __DIR__ . '/_shell.php';
