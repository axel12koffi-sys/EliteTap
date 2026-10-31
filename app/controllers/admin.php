<?php
declare(strict_types=1);

function admin_orders(): void
{
    $user = require_admin();
    $status = $_GET['statut'] ?? '';
    $search = trim((string) ($_GET['q'] ?? ''));

    $where = [];
    $params = [];
    if (isset(ORDER_STATUSES[$status])) {
        $where[] = 'o.status = ?';
        $params[] = $status;
    }
    if ($search !== '') {
        $where[] = '(o.reference LIKE ? OR o.name LIKE ? OR o.email LIKE ? OR o.company LIKE ?)';
        array_push($params, ...array_fill(0, 4, '%' . $search . '%'));
    }
    $sql = 'SELECT o.* FROM orders o' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY o.created_at DESC LIMIT 200';

    $counts = ['' => (int) scalar('SELECT COUNT(*) FROM orders')];
    foreach (rows('SELECT status, COUNT(*) n FROM orders GROUP BY status') as $r) {
        $counts[$r['status']] = (int) $r['n'];
    }
    view('admin/orders', ['title' => 'Commandes | Admin EliteTap', 'nav' => 'commandes', 'user' => $user,
        'orders' => rows($sql, $params), 'status' => $status, 'search' => $search, 'counts' => $counts], 'admin');
}

function admin_order(string $id): void
{
    $user = require_admin();
    $o = row('SELECT * FROM orders WHERE id = ?', [(int) $id]);
    if (!$o) abort(404);
    $client = $o['user_id'] ? row('SELECT u.id, u.email, u.name, p.slug FROM users u LEFT JOIN profiles p ON p.user_id = u.id WHERE u.id = ?', [$o['user_id']]) : null;
    $existing = !$client ? row('SELECT id, name FROM users WHERE email = ?', [$o['email']]) : null;
    $cards = rows('SELECT * FROM cards WHERE order_id = ? ORDER BY id', [$o['id']]);
    $stock = rows("SELECT id, code FROM cards WHERE status = 'stock' AND order_id IS NULL ORDER BY id LIMIT 200");
    view('admin/order', ['title' => $o['reference'] . ' | Admin EliteTap', 'nav' => 'commandes', 'user' => $user,
        'o' => $o, 'client' => $client, 'existing' => $existing, 'cards' => $cards, 'stock' => $stock], 'admin');
}

function admin_order_save(string $id): void
{
    require_admin();
    $o = row('SELECT * FROM orders WHERE id = ?', [(int) $id]);
    if (!$o) abort(404);

    $status = input('status', 20);
    if (!isset(ORDER_STATUSES[$status])) {
        $status = $o['status'];
    }
    $amount = input('amount', 12);
    $cents = $amount === '' ? null : (int) round((float) str_replace([' ', ','], ['', '.'], $amount) * 100);

    q('UPDATE orders SET status = ?, amount_cents = ?, admin_notes = ?, updated_at = ? WHERE id = ?',
        [$status, $cents, input('admin_notes', 5000), now(), $o['id']]);

    // Lier une carte du stock à cette commande (et au profil du client s'il existe)
    $cardId = (int) ($_POST['attach_card'] ?? 0);
    if ($cardId) {
        $profileId = $o['user_id'] ? scalar('SELECT id FROM profiles WHERE user_id = ?', [$o['user_id']]) : null;
        q("UPDATE cards SET order_id = ?, profile_id = ?, status = ?, activated_at = ?, material = ?
           WHERE id = ? AND status = 'stock'", [
            $o['id'], $profileId, $profileId ? 'active' : 'stock', $profileId ? now() : null,
            in_array($o['card_type'], CARD_TYPES, true) ? $o['card_type'] : 'Standard', $cardId,
        ]);
    }

    if (isset($_POST['notify']) && $status !== $o['status']) {
        send_mail($o['email'], "Votre commande EliteTap {$o['reference']} : " . ORDER_STATUSES[$status],
            "Bonjour {$o['name']},\n\nVotre commande {$o['reference']} est maintenant : " . ORDER_STATUSES[$status] . ".\n\n"
            . "Une question ? Répondez à ce courriel.\n\nL'équipe EliteTap\n");
    }
    flash('success', 'Commande mise à jour.');
    redirect('/admin/commandes/' . $o['id']);
}

/** Crée le compte client à partir de la commande (ou relie un compte existant) et envoie le lien d'accès. */
function admin_order_client(string $id): void
{
    require_admin();
    $o = row('SELECT * FROM orders WHERE id = ?', [(int) $id]);
    if (!$o) abort(404);

    $existing = row('SELECT id FROM users WHERE email = ?', [$o['email']]);
    if ($existing) {
        $userId = (int) $existing['id'];
    } else {
        [$userId] = create_client($o['name'], $o['email']);
        if ($o['company'] !== '') {
            q('UPDATE profiles SET company = ?, phone = ? WHERE user_id = ?', [$o['company'], $o['phone'], $userId]);
        }
        $link = make_reset_link($userId, 72);
        send_mail($o['email'], 'Votre espace EliteTap est prêt',
            "Bonjour {$o['name']},\n\nVotre espace client EliteTap est créé. C'est là que vous modifiez le profil qui s'ouvre quand on tape votre carte.\n\n"
            . "Choisissez votre mot de passe ici (lien valable 72 heures) :\n$link\n\n"
            . "Ensuite, connectez-vous sur " . url('/connexion') . "\n\nL'équipe EliteTap\n");
    }
    q('UPDATE orders SET user_id = ?, updated_at = ? WHERE id = ?', [$userId, now(), $o['id']]);

    // Les cartes déjà liées à la commande suivent le client
    $profileId = scalar('SELECT id FROM profiles WHERE user_id = ?', [$userId]);
    q("UPDATE cards SET profile_id = ?, status = 'active', activated_at = ? WHERE order_id = ? AND status = 'stock'",
        [$profileId, now(), $o['id']]);

    flash('success', $existing ? 'Commande reliée au compte existant.' : 'Compte créé et lien d\'accès envoyé au client.');
    redirect('/admin/commandes/' . $o['id']);
}

function admin_order_file(string $id): void
{
    require_admin();
    $o = row('SELECT file_path, file_name FROM orders WHERE id = ?', [(int) $id]);
    if (!$o || !$o['file_path']) abort(404);
    $base = realpath(APP_ROOT . '/storage/uploads/orders');
    $file = realpath(APP_ROOT . '/storage/uploads/orders/' . $o['file_path']);
    if (!$file || !$base || !str_starts_with($file, $base)) abort(404);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $o['file_name'] ?: basename($file)) . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
}

function admin_cards(): void
{
    $user = require_admin();
    $filter = $_GET['statut'] ?? '';
    $params = [];
    $sql = "SELECT c.*, p.slug, p.first_name, p.last_name, o.reference,
              (SELECT COUNT(*) FROM events e WHERE e.card_id = c.id AND e.type = 'tap') taps
            FROM cards c LEFT JOIN profiles p ON p.id = c.profile_id LEFT JOIN orders o ON o.id = c.order_id";
    if (in_array($filter, ['stock', 'active', 'disabled'], true)) {
        $sql .= ' WHERE c.status = ?';
        $params[] = $filter;
    }
    $sql .= ' ORDER BY c.id DESC LIMIT 500';
    view('admin/cards', ['title' => 'Cartes | Admin EliteTap', 'nav' => 'cartes', 'user' => $user,
        'cards' => rows($sql, $params), 'filter' => $filter], 'admin');
}

function admin_cards_generate(): void
{
    require_admin();
    $n = max(1, min(500, (int) ($_POST['count'] ?? 0)));
    $material = in_array($_POST['material'] ?? '', CARD_TYPES, true) ? $_POST['material'] : 'Standard';
    db()->beginTransaction();
    for ($i = 0; $i < $n; $i++) {
        do {
            $code = random_code(8);
        } while (scalar('SELECT id FROM cards WHERE code = ?', [$code]) !== null);
        q("INSERT INTO cards (code, material, status, created_at) VALUES (?, ?, 'stock', ?)", [$code, $material, now()]);
    }
    db()->commit();
    flash('success', "$n code(s) de carte créés. Exportez le CSV pour l'encodage NFC et l'impression des QR.");
    redirect('/admin/cartes?statut=stock');
}

function admin_card_save(string $id): void
{
    require_admin();
    $card = row('SELECT * FROM cards WHERE id = ?', [(int) $id]);
    if (!$card) abort(404);

    $slug = slugify(input('slug', 60));
    $profileId = null;
    if ($slug !== '') {
        $profileId = scalar('SELECT id FROM profiles WHERE slug = ?', [$slug]);
        if (!$profileId) {
            flash('error', "Aucun profil « $slug ».");
            redirect('/admin/cartes');
        }
    }
    $status = input('status', 10);
    if (!in_array($status, ['stock', 'active', 'disabled'], true)) {
        $status = $card['status'];
    }
    if ($status === 'active' && !$profileId) {
        $status = 'stock';
    }
    q('UPDATE cards SET profile_id = ?, nfc_uid = ?, status = ?, activated_at = COALESCE(activated_at, ?) WHERE id = ?', [
        $profileId, preg_replace('/[^0-9A-Fa-f:]/', '', input('nfc_uid', 32)) ?: null, $status,
        $status === 'active' ? now() : null, $card['id'],
    ]);
    flash('success', "Carte {$card['code']} mise à jour.");
    redirect('/admin/cartes' . (isset($_POST['back']) ? '?statut=' . urlencode((string) $_POST['back']) : ''));
}

function admin_cards_export(): void
{
    require_admin();
    $status = $_GET['statut'] ?? 'stock';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cartes-elitetap-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM pour Excel
    fputcsv($out, ['code', 'url_nfc_et_qr', 'materiau', 'statut', 'profil']);
    $params = [];
    $sql = 'SELECT c.code, c.material, c.status, p.slug FROM cards c LEFT JOIN profiles p ON p.id = c.profile_id';
    if (in_array($status, ['stock', 'active', 'disabled'], true)) {
        $sql .= ' WHERE c.status = ?';
        $params[] = $status;
    }
    foreach (rows($sql . ' ORDER BY c.id', $params) as $c) {
        fputcsv($out, [$c['code'], url('/t/' . $c['code']), $c['material'], $c['status'], $c['slug'] ?? '']);
    }
    fclose($out);
}

function admin_clients(): void
{
    $user = require_admin();
    $clients = rows("SELECT u.id, u.name, u.email, u.created_at, u.last_login_at, p.slug,
                       (SELECT COUNT(*) FROM cards c WHERE c.profile_id = p.id AND c.status = 'active') cards,
                       (SELECT COUNT(*) FROM events e WHERE e.profile_id = p.id) events
                     FROM users u LEFT JOIN profiles p ON p.user_id = u.id
                     WHERE u.role = 'client' ORDER BY u.created_at DESC LIMIT 500");
    $stock = rows("SELECT id, code, material FROM cards WHERE status = 'stock' ORDER BY id LIMIT 200");
    view('admin/clients', ['title' => 'Clients | Admin EliteTap', 'nav' => 'clients', 'user' => $user,
        'clients' => $clients, 'stock' => $stock], 'admin');
}

/** Création manuelle d'un client (ex. : migration des clients de l'ancien site), sans commande. */
function admin_client_create(): void
{
    require_admin();
    $name = input('name', 120);
    $email = strtolower(input('email', 190));
    $company = input('company', 120);
    $phone = input('phone', 40);
    $cardId = (int) ($_POST['card_id'] ?? 0);

    if ($name === '' || !valid_email($email)) {
        flash('error', 'Indiquez un nom et un courriel valide.');
        with_old($_POST);
        redirect('/admin/clients');
    }
    if (scalar('SELECT id FROM users WHERE email = ?', [$email]) !== null) {
        flash('error', "Un compte existe déjà pour $email.");
        with_old($_POST);
        redirect('/admin/clients');
    }

    [$userId] = create_client($name, $email);
    q('UPDATE profiles SET company = ?, phone = ?, updated_at = ? WHERE user_id = ?', [$company, $phone, now(), $userId]);
    $profileId = (int) scalar('SELECT id FROM profiles WHERE user_id = ?', [$userId]);

    $msg = 'Client créé.';
    if ($cardId) {
        $code = scalar("SELECT code FROM cards WHERE id = ? AND status = 'stock'", [$cardId]);
        if ($code) {
            q("UPDATE cards SET profile_id = ?, status = 'active', activated_at = ? WHERE id = ?", [$profileId, now(), $cardId]);
            $msg .= " Carte $code activée sur son profil.";
        }
    }

    if (isset($_POST['send_access'])) {
        $link = make_reset_link($userId, 72);
        send_mail($email, 'Votre nouvel espace EliteTap',
            "Bonjour $name,\n\nEliteTap a une nouvelle plateforme. Votre espace client est prêt : c'est là que vous modifiez le profil qui s'ouvre quand on tape votre carte.\n\n"
            . "Choisissez votre mot de passe ici (lien valable 72 heures) :\n$link\n\n"
            . "Ensuite, connectez-vous sur " . url('/connexion') . "\n\nL'équipe EliteTap\n");
        $msg .= " Lien d'accès envoyé.";
    }
    flash('success', $msg);
    redirect('/admin/clients');
}

function admin_client_reset_link(string $id): void
{
    require_admin();
    $u = row("SELECT id, name, email FROM users WHERE id = ? AND role = 'client'", [(int) $id]);
    if (!$u) abort(404);
    $link = make_reset_link((int) $u['id'], 72);
    send_mail($u['email'], 'Accès à votre espace EliteTap',
        "Bonjour {$u['name']},\n\nVoici un lien pour choisir votre mot de passe (valable 72 heures) :\n$link\n\nL'équipe EliteTap\n");
    flash('success', "Lien d'accès envoyé à {$u['email']}.");
    redirect('/admin/clients');
}
