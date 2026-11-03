<?php
$isAdminShell = true;
$menu = [
    ['tableau',   '/admin',           'Tableau de bord'],
    ['messages',  '/admin/messages',  'Messages', null, inbox_unread_count()],
    ['commandes', '/admin/commandes', 'Commandes'],
    ['clients',   '/admin/clients',   'Clients'],
    ['cartes',    '/admin/cartes',    'Cartes NFC'],
    ['designs',   '/admin/designs',   'Designs'],
    ['courriels', '/admin/courriels', 'Courriels'],
    ['reglages',  '/admin/reglages',  'Réglages'],
];
require __DIR__ . '/_shell.php';
