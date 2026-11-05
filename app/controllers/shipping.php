<?php
declare(strict_types=1);

/* Département Livraison : Admin › Livraisons, et page publique de suivi pour le client. */

function require_shipping(): array
{
    $u = require_perm('shipping');
    if (!shipping_ready()) {
        flash('error', 'Le suivi des livraisons a besoin de la migration 007 : exécutez database/migrations/007-livraisons.mysql.sql dans phpMyAdmin.');
        redirect(staff_home($u));
    }
    return $u;
}

function admin_shipments(): void
{
    $user = require_shipping();
    $vue = is_string($_GET['vue'] ?? null) ? $_GET['vue'] : 'en-cours';
    $where = match ($vue) {
        'a-preparer' => "s.status IN ('a_preparer','preparee')",
        'en-route'   => "s.status IN ('expediee','en_transit')",
        'livrees'    => "s.status = 'livree'",
        'problemes'  => "s.status IN ('probleme','retournee')",
        'toutes'     => '1 = 1',
        default      => "s.status NOT IN ('livree','retournee')",
    };
    $params = [];
    $search = trim(mb_substr(is_string($_GET['q'] ?? null) ? $_GET['q'] : '', 0, 100));
    if ($search !== '') {
        $where .= ' AND (o.reference LIKE ? OR o.name LIKE ? OR o.email LIKE ? OR s.tracking_number LIKE ? OR s.city LIKE ?)';
        $like = '%' . $search . '%';
        $params = [$like, $like, $like, $like, $like];
    }
    $shipments = rows("SELECT s.*, o.reference, o.name client_name, o.company, o.card_type, o.quantity
        FROM shipments s JOIN orders o ON o.id = s.order_id WHERE $where ORDER BY
        CASE s.status WHEN 'probleme' THEN 0 WHEN 'a_preparer' THEN 1 WHEN 'preparee' THEN 2 WHEN 'expediee' THEN 3 WHEN 'en_transit' THEN 4 ELSE 5 END, s.updated_at DESC LIMIT 300", $params);
    // Délai moyen expédition → livraison sur 90 jours (jours)
    $delays = [];
    foreach (rows("SELECT shipped_at, delivered_at FROM shipments WHERE delivered_at IS NOT NULL AND shipped_at IS NOT NULL AND delivered_at >= ?",
        [date('Y-m-d H:i:s', time() - 90 * 86400)]) as $r) {
        $delays[] = max(0, strtotime($r['delivered_at']) - strtotime($r['shipped_at'])) / 86400;
    }
    view('admin/shipments', ['title' => 'Livraisons | Admin EliteTap', 'nav' => 'livraisons', 'user' => $user,
        'shipments' => $shipments, 'vue' => $vue, 'search' => $search, 'counts' => shipping_counts(),
        'toShip' => orders_to_ship(), 'avgDays' => $delays ? array_sum($delays) / count($delays) : null,
        'delivered30' => (int) scalar("SELECT COUNT(*) FROM shipments WHERE delivered_at >= ?", [date('Y-m-d H:i:s', time() - 30 * 86400)])], 'admin');
}

/** Nouvelle expédition pour une commande (adresse reprise de la dernière expédition du même client). */
function admin_shipment_create(): void
{
    $user = require_shipping();
    $o = row('SELECT * FROM orders WHERE id = ?', [(int) input('order_id', 12)]) ?? abort(404);
    $prev = row('SELECT * FROM shipments s JOIN orders o ON o.id = s.order_id WHERE o.email = ? ORDER BY s.id DESC LIMIT 1', [$o['email']]);
    q('INSERT INTO shipments (order_id, token, carrier, status, recipient, address, city, province, postal_code, phone, packages, created_by, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
        $o['id'], bin2hex(random_bytes(16)), $prev['carrier'] ?? 'postes_canada', 'a_preparer',
        $prev['recipient'] ?? ($o['company'] !== '' ? $o['name'] . ' (' . $o['company'] . ')' : $o['name']),
        $prev['address'] ?? '', $prev['city'] ?? '', $prev['province'] ?? 'QC', $prev['postal_code'] ?? '', $prev['phone'] ?? $o['phone'],
        1, $user['id'], now(), now(),
    ]);
    $id = (int) db()->lastInsertId();
    q('INSERT INTO shipment_events (shipment_id, status, note, notified, user_id, created_at) VALUES (?, ?, ?, 0, ?, ?)',
        [$id, 'a_preparer', 'Expédition créée', $user['id'], now()]);
    flash('success', 'Expédition créée : complétez l\'adresse et le suivi.');
    redirect("/admin/livraisons/$id");
}

function admin_shipment(string $id): void
{
    $user = require_shipping();
    $s = row('SELECT * FROM shipments WHERE id = ?', [(int) $id]) ?? abort(404);
    $o = row('SELECT * FROM orders WHERE id = ?', [$s['order_id']]);
    $events = rows('SELECT e.*, u.name user_name FROM shipment_events e LEFT JOIN users u ON u.id = e.user_id WHERE e.shipment_id = ? ORDER BY e.id DESC', [$s['id']]);
    $others = rows('SELECT id, status, tracking_number FROM shipments WHERE order_id = ? AND id <> ? ORDER BY id', [$s['order_id'], $s['id']]);
    view('admin/shipment', ['title' => 'Livraison ' . $o['reference'] . ' | Admin EliteTap', 'nav' => 'livraisons', 'user' => $user,
        's' => $s, 'o' => $o, 'events' => $events, 'others' => $others], 'admin');
}

function admin_shipment_save(string $id): void
{
    $user = require_shipping();
    $s = row('SELECT * FROM shipments WHERE id = ?', [(int) $id]) ?? abort(404);
    if (input('action', 20) === 'status') {
        $status = input('status', 12);
        if (!isset(SHIP_STATUSES[$status])) {
            flash('error', 'Statut inconnu.');
            redirect("/admin/livraisons/{$s['id']}");
        }
        if (in_array($status, ['expediee', 'en_transit'], true) && $s['tracking_number'] === null && !in_array($s['carrier'], ['main_propre', 'ramassage'], true)
            && !isset($_POST['sans_suivi'])) {
            flash('error', 'Indiquez le numéro de suivi avant d\'expédier (ou cochez « sans numéro de suivi »).');
            redirect("/admin/livraisons/{$s['id']}#statut");
        }
        $notify = isset($_POST['notify']);
        shipment_set_status($s, $status, input('note', 500), $notify, (int) $user['id']);
        flash('success', 'Statut : ' . SHIP_STATUSES[$status] . '.' . ($notify && in_array($status, SHIP_NOTIFY, true) ? ' Le client a été prévenu par courriel.' : ''));
        redirect("/admin/livraisons/{$s['id']}");
    }
    // Détails : transporteur, suivi, adresse
    $carrier = isset(CARRIERS[input('carrier', 30)]) ? input('carrier', 30) : $s['carrier'];
    $url = input('tracking_url', 500);
    if ($url !== '' && !preg_match('#^https://#i', $url)) {
        flash('error', 'Le lien de suivi doit commencer par https://');
        redirect("/admin/livraisons/{$s['id']}");
    }
    $est = input('estimated_at', 10);
    q('UPDATE shipments SET carrier = ?, tracking_number = ?, tracking_url = ?, recipient = ?, address = ?, city = ?, province = ?, postal_code = ?,
         phone = ?, packages = ?, estimated_at = ?, notes = ?, updated_at = ? WHERE id = ?', [
        $carrier, preg_replace('/\s+/', '', input('tracking_number', 80)) ?: null, $url ?: null,
        input('recipient', 120), input('address', 255), input('city', 100), input('province', 30), strtoupper(input('postal_code', 12)),
        input('phone', 40), max(1, min(99, (int) input('packages', 3))), preg_match('/^\d{4}-\d{2}-\d{2}$/', $est) ? $est : null,
        input('notes', 2000), now(), $s['id'],
    ]);
    flash('success', 'Expédition enregistrée.');
    redirect("/admin/livraisons/{$s['id']}");
}

/** Page publique de suivi (lien envoyé au client). */
function public_tracking(string $token): void
{
    $s = shipping_ready() ? row('SELECT * FROM shipments WHERE token = ?', [$token]) : null;
    if (!$s) {
        abort(404);
    }
    $o = row('SELECT * FROM orders WHERE id = ?', [$s['order_id']]);
    if (!isset($_GET['lang']) && empty($_COOKIE['lang']) && in_array($o['lang'] ?? '', ['fr', 'en'], true)) {
        $GLOBALS['lang_override'] = $o['lang'];
    }
    $events = rows("SELECT status, created_at FROM shipment_events WHERE shipment_id = ? ORDER BY id", [$s['id']]);
    header('X-Robots-Tag: noindex, nofollow');
    view('site/tracking', ['title' => t('Suivi de commande {ref} | EliteTap', ['ref' => $o['reference']]), 's' => $s, 'o' => $o, 'events' => $events], 'site');
}
