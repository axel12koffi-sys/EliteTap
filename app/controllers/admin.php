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
        'orders' => rows($sql, $params), 'status' => $status, 'search' => $search, 'counts' => $counts, 'kpis' => admin_kpis()], 'admin');
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
        in_lang($o['lang'], fn() => send_mail($o['email'], t('Votre commande EliteTap {ref} : {statut}', ['ref' => $o['reference'], 'statut' => t(ORDER_STATUSES[$status])]),
            t("Bonjour {nom},\n\nVotre commande {ref} est maintenant : {statut}.\n\nUne question ? Répondez à ce courriel.\n\nL'équipe EliteTap\n",
                ['nom' => $o['name'], 'ref' => $o['reference'], 'statut' => t(ORDER_STATUSES[$status])])));
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
        q('UPDATE users SET lang = ? WHERE id = ?', [$o['lang'], $userId]);
        $link = make_reset_link($userId, 72);
        in_lang($o['lang'], fn() => send_mail($o['email'], t('Votre espace EliteTap est prêt'),
            t("Bonjour {nom},\n\nVotre espace client EliteTap est créé. C'est là que vous modifiez le profil qui s'ouvre quand on tape votre carte.\n\nChoisissez votre mot de passe ici (lien valable 72 heures) :\n{lien}\n\nEnsuite, connectez-vous sur {connexion}\n\nL'équipe EliteTap\n",
                ['nom' => $o['name'], 'lien' => $link, 'connexion' => url('/connexion')])));
    }
    q('UPDATE orders SET user_id = ?, updated_at = ? WHERE id = ?', [$userId, now(), $o['id']]);

    // Les cartes déjà liées à la commande suivent le client
    $profileId = scalar('SELECT id FROM profiles WHERE user_id = ?', [$userId]);
    q("UPDATE cards SET profile_id = ?, status = 'active', activated_at = ? WHERE order_id = ? AND status = 'stock'",
        [$profileId, now(), $o['id']]);

    flash('success', $existing ? 'Commande reliée au compte existant.' : 'Compte créé et lien d\'accès envoyé au client.');
    redirect('/admin/commandes/' . $o['id']);
}

/** Prépare le lien de paiement et l'envoie au client dans sa langue. */
function admin_order_payment(string $id): void
{
    require_admin();
    $o = row('SELECT * FROM orders WHERE id = ?', [(int) $id]);
    if (!$o) abort(404);
    if ($o['paid_at']) {
        flash('error', 'Cette commande est déjà payée.');
        redirect('/admin/commandes/' . $o['id']);
    }
    if ($o['amount_cents'] === null || (int) $o['amount_cents'] < 50) {
        flash('error', 'Indiquez d\'abord le montant total de la commande.');
        redirect('/admin/commandes/' . $o['id']);
    }
    $token = $o['pay_token'] ?: bin2hex(random_bytes(16));
    q('UPDATE orders SET pay_token = ?, updated_at = ? WHERE id = ?', [$token, now(), $o['id']]);
    $link = url('/payer/' . $o['reference'] . '/' . $token);

    if (isset($_POST['send'])) {
        in_lang($o['lang'], fn() => send_mail($o['email'], t('Votre devis EliteTap {ref} : {montant}', ['ref' => $o['reference'], 'montant' => money((int) $o['amount_cents'])]),
            t("Bonjour {nom},\n\nVoici votre devis pour {carte} × {qte} : {montant} (taxes incluses).\n\nVous pouvez le régler en ligne, par carte de crédit, en toute sécurité :\n{lien}\n\nDès réception du paiement, nous lançons la production après validation de votre épreuve.\n\nUne question ? Répondez simplement à ce courriel.\n\nL'équipe EliteTap\n",
                ['nom' => $o['name'], 'carte' => t($o['card_type']), 'qte' => (int) $o['quantity'], 'montant' => money((int) $o['amount_cents']), 'lien' => $link])));
        flash('success', 'Lien de paiement envoyé à ' . $o['email'] . '.');
    } else {
        flash('success', 'Lien de paiement prêt : copiez-le ci-dessous.');
    }
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

/** Planche de QR : ?statut=stock|active|disabled ou ?commande=ID */
function admin_cards_qr(): void
{
    require_admin();
    $params = [];
    $sql = 'SELECT code FROM cards';
    if (!empty($_GET['commande'])) {
        $sql .= ' WHERE order_id = ?';
        $params[] = (int) $_GET['commande'];
    } elseif (in_array($_GET['statut'] ?? '', ['stock', 'active', 'disabled'], true)) {
        $sql .= ' WHERE status = ?';
        $params[] = $_GET['statut'];
    }
    view('admin/qr', ['cards' => rows($sql . ' ORDER BY id LIMIT 1000', $params)]);
}

/** Indicateurs du tableau de bord admin. */
function admin_kpis(): array
{
    $week = date('Y-m-d H:i:s', time() - 7 * 86400);
    $month = date('Y-m-d H:i:s', time() - 30 * 86400);
    return [
        'clients'  => (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'client' AND is_active = 1"),
        'cards'    => (int) scalar("SELECT COUNT(*) FROM cards WHERE status = 'active'"),
        'taps'     => (int) scalar("SELECT COUNT(*) FROM events WHERE type = 'tap' AND created_at >= ?", [$week]),
        'leads'    => (int) scalar('SELECT COUNT(*) FROM leads WHERE created_at >= ?', [$month]),
        'pro'      => (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'client' AND is_active = 1 AND plan IN ('pro','entreprise')"),
    ];
}

function admin_clients(): void
{
    $user = require_admin();
    $month = date('Y-m-d H:i:s', time() - 30 * 86400);
    $filter = $_GET['forfait'] ?? '';
    $where = "u.role = 'client'";
    $params = [$month, $month];
    if (isset(PLANS[$filter])) {
        $where .= ' AND u.plan = ?';
        $params[] = $filter;
    } elseif ($filter === 'inactif') {
        $where .= ' AND u.is_active = 0';
    }
    $clients = rows("SELECT u.id, u.name, u.email, u.created_at, u.last_login_at, u.plan, u.plan_source, u.plan_status, u.is_active, p.slug, p.company,
                       (SELECT COUNT(*) FROM cards c WHERE c.profile_id = p.id AND c.status = 'active') cards,
                       (SELECT COUNT(*) FROM events e WHERE e.profile_id = p.id AND e.type IN ('tap','view') AND e.created_at >= ?) opens,
                       (SELECT COUNT(*) FROM leads l WHERE l.profile_id = p.id AND l.created_at >= ?) leads
                     FROM users u LEFT JOIN profiles p ON p.user_id = u.id
                     WHERE $where ORDER BY u.created_at DESC LIMIT 500", $params);
    $stock = rows("SELECT id, code, material FROM cards WHERE status = 'stock' ORDER BY id LIMIT 200");
    view('admin/clients', ['title' => 'Clients | Admin EliteTap', 'nav' => 'clients', 'user' => $user,
        'clients' => $clients, 'stock' => $stock, 'kpis' => admin_kpis(), 'filter' => $filter], 'admin');
}

function admin_client_plan(string $id): void
{
    require_admin();
    $plan = input('plan', 12);
    if (!isset(PLANS[$plan])) abort(400);
    $u = row("SELECT id, name, plan_source, stripe_subscription_id FROM users WHERE id = ? AND role = 'client'", [(int) $id]);
    if (!$u) abort(404);
    // Un forfait choisi ici devient « manuel » : Stripe ne le modifiera plus
    q("UPDATE users SET plan = ?, plan_source = 'manual' WHERE id = ?", [$plan, $u['id']]);
    $msg = "Forfait de {$u['name']} : " . PLANS[$plan] . '.';
    if ($u['stripe_subscription_id']) {
        $msg .= ' Attention : un abonnement Stripe est actif pour ce client, à gérer aussi dans Stripe.';
    }
    flash('success', $msg);
    redirect('/admin/clients');
}

/** Activer / désactiver un client : profil masqué, cartes inopérantes, connexion bloquée. Rien n'est supprimé. */
function admin_client_toggle(string $id): void
{
    require_admin();
    $u = row("SELECT id, name, is_active FROM users WHERE id = ? AND role = 'client'", [(int) $id]);
    if (!$u) abort(404);
    q('UPDATE users SET is_active = ? WHERE id = ?', [$u['is_active'] ? 0 : 1, $u['id']]);
    flash('success', $u['is_active']
        ? "{$u['name']} est désactivé : son profil et ses cartes ne s'ouvrent plus. Rien n'est supprimé."
        : "{$u['name']} est réactivé.");
    redirect('/admin/clients');
}

/** « Gérer le profil » : l'admin ouvre l'espace du client pour faire les modifications (forfait Essentiel). */
function admin_client_manage(string $id): void
{
    $admin = require_admin();
    $u = row("SELECT id FROM users WHERE id = ? AND role = 'client'", [(int) $id]);
    if (!$u) abort(404);
    session_regenerate_id(true);
    $_SESSION['impersonator'] = (int) $admin['id'];
    $_SESSION['uid'] = (int) $u['id'];
    redirect('/compte');
}

function admin_stop_managing(): void
{
    $adminId = (int) ($_SESSION['impersonator'] ?? 0);
    if (!$adminId) {
        redirect('/compte');
    }
    unset($_SESSION['impersonator']);
    session_regenerate_id(true);
    $_SESSION['uid'] = $adminId;
    redirect('/admin/clients');
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
    $clang = in_array($_POST['lang'] ?? '', LANGS, true) ? $_POST['lang'] : 'fr';
    $cplan = isset(PLANS[$_POST['plan'] ?? '']) ? $_POST['plan'] : 'essentiel';

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
    q('UPDATE users SET lang = ?, plan = ? WHERE id = ?', [$clang, $cplan, $userId]);
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
        in_lang($clang, fn() => send_mail($email, t('Votre nouvel espace EliteTap'),
            t("Bonjour {nom},\n\nEliteTap a une nouvelle plateforme. Votre espace client est prêt : c'est là que vous modifiez le profil qui s'ouvre quand on tape votre carte.\n\nChoisissez votre mot de passe ici (lien valable 72 heures) :\n{lien}\n\nEnsuite, connectez-vous sur {connexion}\n\nL'équipe EliteTap\n",
                ['nom' => $name, 'lien' => $link, 'connexion' => url('/connexion')])));
        $msg .= " Lien d'accès envoyé.";
    }
    flash('success', $msg);
    redirect('/admin/clients');
}

function admin_client_reset_link(string $id): void
{
    require_admin();
    $u = row("SELECT id, name, email, lang FROM users WHERE id = ? AND role = 'client'", [(int) $id]);
    if (!$u) abort(404);
    $link = make_reset_link((int) $u['id'], 72);
    in_lang($u['lang'], fn() => send_mail($u['email'], t('Accès à votre espace EliteTap'),
        t("Bonjour {nom},\n\nVoici un lien pour choisir votre mot de passe (valable 72 heures) :\n{lien}\n\nL'équipe EliteTap\n", ['nom' => $u['name'], 'lien' => $link])));
    flash('success', "Lien d'accès envoyé à {$u['email']}.");
    redirect('/admin/clients');
}
