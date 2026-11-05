<?php
$isAdminShell = true;
$u = $user ?? current_user();
$can = fn(string $perm) => staff_can($u, $perm);
$isAdmin = ($u['role'] ?? '') === 'admin';
$menu = array_values(array_filter([
    $isAdmin ? ['tableau', '/admin', 'Tableau de bord'] : null,
    $can('service') ? ['_', 'Service client'] : null,
    $can('service') ? ['service', '/admin/service', 'Vue d\'ensemble'] : null,
    $can('service') ? ['clavardage', '/admin/chat', 'Clavardage', null, chat_attention_count()] : null,
    $can('service') ? ['messages', '/admin/messages', 'Messages', null, inbox_unread_count()] : null,
    $can('newsletter') ? ['infolettre', '/admin/infolettre', 'Infolettre'] : null,
    $can('orders') ? ['_', 'Ventes'] : null,
    $can('orders') ? ['commandes', '/admin/commandes', 'Commandes'] : null,
    $can('clients') ? ['clients', '/admin/clients', 'Clients'] : null,
    $can('compta') ? ['compta', '/admin/comptabilite', 'Comptabilité'] : null,
    ['_', 'Équipe'],
    ['equipe', '/admin/equipe/chat', 'Discussion d\'équipe', null, team_unread_total($u)],
    $isAdmin ? ['employes', '/admin/employes', 'Employés'] : null,
    $isAdmin ? ['_', 'Plateforme'] : null,
    $isAdmin ? ['cartes', '/admin/cartes', 'Cartes NFC'] : null,
    $isAdmin ? ['designs', '/admin/designs', 'Designs'] : null,
    $isAdmin ? ['courriels', '/admin/courriels', 'Courriels'] : null,
    $isAdmin ? ['reglages', '/admin/reglages', 'Réglages'] : null,
]));
require __DIR__ . '/_shell.php';
