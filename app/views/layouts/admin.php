<?php
$isAdminShell = true;
$u = $user ?? current_user();
$can = fn(string $perm) => staff_can($u, $perm);
$isAdmin = ($u['role'] ?? '') === 'admin';
$only = fn(array $items) => array_values(array_filter($items));
// Lien : [clé, adresse, libellé, fonction Pro, compteur] ; section dépliable : ['_group', id, libellé, [liens]]
$menu = array_values(array_filter([
    $isAdmin ? ['tableau', '/admin', 'Tableau de bord'] : null,
    $can('service') ? ['_group', 'service', 'Service client', $only([
        ['service', '/admin/service', 'Vue d\'ensemble'],
        ['clavardage', '/admin/chat', 'Clavardage', null, chat_attention_count()],
        ['messages', '/admin/messages', 'Messages', null, inbox_unread_count()],
        $can('newsletter') ? ['infolettre', '/admin/infolettre', 'Infolettre'] : null,
    ])] : null,
    $can('orders') ? ['_group', 'ventes', 'Ventes', $only([
        ['commandes', '/admin/commandes', 'Commandes'],
        $can('clients') ? ['clients', '/admin/clients', 'Clients'] : null,
        $can('compta') ? ['compta', '/admin/comptabilite', 'Comptabilité'] : null,
    ])] : null,
    ['_group', 'equipe', 'Équipe', $only([
        ['equipe', '/admin/equipe/chat', 'Discussion d\'équipe', null, team_unread_total($u)],
        $isAdmin ? ['employes', '/admin/employes', 'Employés'] : null,
    ])],
    $isAdmin ? ['_group', 'plateforme', 'Plateforme', [
        ['cartes', '/admin/cartes', 'Cartes NFC'],
        ['designs', '/admin/designs', 'Designs'],
        ['courriels', '/admin/courriels', 'Courriels'],
        ['reglages', '/admin/reglages', 'Réglages'],
    ]] : null,
]));
require __DIR__ . '/_shell.php';
