<?php
$isAdminShell = true;
$menu = [
    ['commandes', '/admin',         'Commandes'],
    ['cartes',    '/admin/cartes',  'Cartes NFC'],
    ['clients',   '/admin/clients', 'Clients'],
];
require __DIR__ . '/_shell.php';
