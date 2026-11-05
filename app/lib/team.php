<?php
declare(strict_types=1);

/*
 * Équipe EliteTap : administrateurs et employés (rôle « staff ») affectés à des services.
 *  - admin : tout.
 *  - service client : clavardage du site, messages, commandes, clients (lecture), infolettre.
 *  - comptabilité : tableau de comptabilité, commandes, paiements.
 *  - tous : clavardage interne de l'équipe.
 */

const SERVICES = ['service' => 'Service client', 'compta' => 'Comptabilité', 'livraison' => 'Livraison'];

/** Permissions → services qui y donnent accès (l'admin a toujours tout). */
const PERMISSIONS = [
    'service'    => ['service'],              // clavardage, messages, tableau du service client, présence
    'compta'     => ['compta'],               // comptabilité, lien de paiement, marquer payée
    'orders'     => ['service', 'compta', 'livraison'],  // voir les commandes
    'shipping'   => ['service', 'livraison'],  // suivi des livraisons (département Livraison)
    'clients'    => ['service'],              // voir les clients
    'newsletter' => ['service'],
];

function is_team(?array $u): bool
{
    return $u !== null && in_array($u['role'] ?? '', ['admin', 'staff'], true);
}

/** Services d'un employé (tableau de clés de SERVICES). */
function user_services(array $u): array
{
    if (($u['role'] ?? '') === 'admin') {
        return array_keys(SERVICES);
    }
    return array_values(array_intersect(array_keys(SERVICES), explode(',', (string) ($u['services'] ?? ''))));
}

function staff_can(?array $u, string $perm): bool
{
    if (!is_team($u)) {
        return false;
    }
    if ($u['role'] === 'admin' || $perm === 'team') {
        return true;                       // le clavardage interne est ouvert à toute l'équipe
    }
    return (bool) array_intersect(PERMISSIONS[$perm] ?? [], user_services($u));
}

/** Page réservée à une permission de l'équipe. */
function require_perm(string $perm): array
{
    $u = require_login();
    if (!staff_can($u, $perm)) {
        if (is_team($u)) {
            flash('error', 'Cette page n\'est pas ouverte à votre service.');
            redirect(staff_home($u));
        }
        abort(403);
    }
    touch_presence($u);
    return $u;
}

/** Page d'accueil d'un membre de l'équipe après connexion. */
function staff_home(array $u): string
{
    if ($u['role'] === 'admin') {
        return '/admin';
    }
    $s = user_services($u);
    return in_array('service', $s, true) ? '/admin/service' : (in_array('compta', $s, true) ? '/admin/comptabilite'
        : (in_array('livraison', $s, true) ? '/admin/livraisons' : '/admin/equipe/chat'));
}

/** Membres actifs de l'équipe (pour l'affectation, le clavardage interne). */
function team_members(): array
{
    try {
        return rows("SELECT id, name, email, role, services, last_seen_at FROM users WHERE role IN ('admin','staff') AND is_active = 1 ORDER BY name");
    } catch (Throwable $e) {
        return rows("SELECT id, name, email, role, '' services, NULL last_seen_at FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY name");
    }
}

function member_online(array $m): bool
{
    return !empty($m['last_seen_at']) && strtotime($m['last_seen_at']) > time() - PRESENCE_ACTIVE_MINUTES * 60;
}

function services_label(array $u): string
{
    if ($u['role'] === 'admin') {
        return 'Administrateur';
    }
    $s = array_map(fn($k) => SERVICES[$k], user_services($u));
    return $s ? implode(', ', $s) : 'Aucun service';
}

/** Page d'arrivée après connexion pour ce compte. */
function home_for(int $userId): string
{
    $u = row('SELECT * FROM users WHERE id = ?', [$userId]);
    return $u && is_team($u) ? staff_home($u) : '/compte';
}

/* JSON pour les pages qui se mettent à jour seules (clavardage). */
function wants_json(): bool
{
    return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Migration 006 exécutée (employés, clavardage interne) ? */
function team_ready(): bool
{
    static $ok = null;
    if ($ok === null) {
        try {
            scalar('SELECT COUNT(*) FROM team_messages WHERE 1 = 0');
            scalar('SELECT services FROM users WHERE 1 = 0');
            $ok = true;
        } catch (Throwable $e) {
            $ok = false;
        }
    }
    return $ok;
}

function require_team_ready(array $u): void
{
    if (!team_ready()) {
        flash('error', 'Cette page a besoin de la migration 006 : exécutez database/migrations/006-equipe-clavardage-infolettre.mysql.sql dans phpMyAdmin.');
        redirect(($u['role'] ?? '') === 'admin' ? '/admin' : '/connexion');
    }
}
