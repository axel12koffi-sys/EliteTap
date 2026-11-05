<?php
declare(strict_types=1);

/* Admin › Employés (affectation aux services) et clavardage interne de l'équipe. */

const TEAM_CHANNELS = ['general' => 'Général', 'service' => 'Service client', 'compta' => 'Comptabilité', 'livraison' => 'Livraison'];

/* ------------------------------------------------------------------ */
/* Employés                                                            */
/* ------------------------------------------------------------------ */

function admin_staff(): void
{
    $user = require_admin();
    require_team_ready($user);
    $members = rows("SELECT id, name, email, role, services, is_active, last_login_at, last_seen_at, created_at FROM users WHERE role IN ('admin','staff') ORDER BY role, name");
    view('admin/staff', ['title' => 'Employés | Admin EliteTap', 'nav' => 'employes', 'user' => $user, 'members' => $members], 'admin');
}

function staff_services_from_post(): string
{
    $s = array_values(array_intersect(array_keys(SERVICES), (array) ($_POST['services'] ?? [])));
    return implode(',', $s);
}

function send_staff_invite(array $u): bool
{
    $link = make_reset_link((int) $u['id'], 72);
    return send_mail($u['email'], 'Votre accès à l\'équipe EliteTap',
        "Bonjour {$u['name']},\n\nVous avez été ajouté à l'équipe EliteTap (" . services_label($u) . ").\n\n"
        . "Choisissez votre mot de passe ici (lien valable 72 heures) :\n$link\n\n"
        . "Ensuite, connectez-vous sur " . url('/connexion') . " avec votre courriel.\n\nL'équipe EliteTap\n");
}

function admin_staff_create(): void
{
    require_team_ready(require_admin());
    $name = input('name', 120);
    $email = strtolower(input('email', 190));
    $services = staff_services_from_post();
    if ($name === '' || !valid_email($email)) {
        flash('error', 'Indiquez le nom et un courriel valide.');
        with_old(['name' => $name, 'email' => $email]);
        redirect('/admin/employes');
    }
    if ($services === '') {
        flash('error', 'Choisissez au moins un service.');
        with_old(['name' => $name, 'email' => $email]);
        redirect('/admin/employes');
    }
    $existing = row('SELECT id, role FROM users WHERE email = ?', [$email]);
    if ($existing) {
        flash('error', $existing['role'] === 'client'
            ? 'Ce courriel appartient déjà à un compte client. Utilisez une autre adresse (ex. son courriel professionnel).'
            : 'Ce courriel fait déjà partie de l\'équipe.');
        redirect('/admin/employes');
    }
    q('INSERT INTO users (email, password_hash, name, role, services, lang, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)', [
        $email, password_hash(random_code(24), PASSWORD_DEFAULT), $name, 'staff', $services, 'fr', now(),
    ]);
    $u = row('SELECT * FROM users WHERE id = ?', [(int) db()->lastInsertId()]);
    $ok = send_staff_invite($u);
    flash($ok ? 'success' : 'error', $ok ? "$name a été ajouté. Il a reçu un lien pour choisir son mot de passe."
        : "$name a été ajouté, mais l'invitation n'est pas partie (voir Courriels). Renvoyez-la depuis la liste.");
    redirect('/admin/employes');
}

function admin_staff_save(string $id): void
{
    $admin = require_admin();
    $u = row("SELECT * FROM users WHERE id = ? AND role = 'staff'", [(int) $id]) ?? abort(404);
    switch (input('action', 20)) {
        case 'services':
            $s = staff_services_from_post();
            if ($s === '') {
                flash('error', 'Un employé doit avoir au moins un service.');
                break;
            }
            q('UPDATE users SET services = ? WHERE id = ?', [$s, $u['id']]);
            flash('success', "Services de {$u['name']} mis à jour.");
            break;
        case 'toggle':
            q('UPDATE users SET is_active = ? WHERE id = ?', [$u['is_active'] ? 0 : 1, $u['id']]);
            flash('success', $u['is_active'] ? "{$u['name']} ne peut plus se connecter." : "{$u['name']} peut de nouveau se connecter.");
            break;
        case 'invite':
            $ok = send_staff_invite($u);
            flash($ok ? 'success' : 'error', $ok ? "Lien d'accès renvoyé à {$u['email']}." : 'L\'envoi a échoué (voir Courriels).');
            break;
    }
    redirect('/admin/employes');
}

/* ------------------------------------------------------------------ */
/* Clavardage interne                                                  */
/* ------------------------------------------------------------------ */

/** Canaux visibles par un membre : Général, ses services, ses messages directs. */
function team_channels_for(array $u): array
{
    $list = [];
    foreach (TEAM_CHANNELS as $k => $label) {
        if ($k === 'general' || $u['role'] === 'admin' || in_array($k, user_services($u), true)
            || ($k === 'livraison' && staff_can($u, 'shipping'))) {
            $list[$k] = ['key' => $k, 'label' => $label, 'type' => 'channel'];
        }
    }
    foreach (team_members() as $m) {
        if ((int) $m['id'] === (int) $u['id']) {
            continue;
        }
        $k = dm_key((int) $u['id'], (int) $m['id']);
        $list[$k] = ['key' => $k, 'label' => $m['name'], 'type' => 'dm', 'member' => $m];
    }
    return $list;
}

function dm_key(int $a, int $b): string
{
    return 'dm:' . min($a, $b) . '-' . max($a, $b);
}

function team_unread(array $u): array
{
    $out = [];
    try {
        $reads = [];
        foreach (rows('SELECT channel, last_read_id FROM team_reads WHERE user_id = ?', [$u['id']]) as $r) {
            $reads[$r['channel']] = (int) $r['last_read_id'];
        }
        $keys = array_keys(team_channels_for($u));
        if (!$keys) {
            return [];
        }
        $in = implode(',', array_fill(0, count($keys), '?'));
        foreach (rows("SELECT channel, id FROM team_messages WHERE channel IN ($in) AND user_id <> ? AND id > ? ORDER BY id DESC LIMIT 500",
            array_merge($keys, [$u['id'], $reads ? min($reads) : 0])) as $m) {
            if ($m['id'] > ($reads[$m['channel']] ?? 0)) {
                $out[$m['channel']] = ($out[$m['channel']] ?? 0) + 1;
            }
        }
    } catch (Throwable $e) {
        return [];
    }
    return $out;
}

function team_unread_total(array $u): int
{
    return array_sum(team_unread($u));
}

function team_mark_read(array $u, string $channel, int $lastId): void
{
    $cur = scalar('SELECT last_read_id FROM team_reads WHERE user_id = ? AND channel = ?', [$u['id'], $channel]);
    if ($cur === null) {
        q('INSERT INTO team_reads (user_id, channel, last_read_id) VALUES (?, ?, ?)', [$u['id'], $channel, $lastId]);
    } elseif ($lastId > (int) $cur) {
        q('UPDATE team_reads SET last_read_id = ? WHERE user_id = ? AND channel = ?', [$lastId, $u['id'], $channel]);
    }
}

function team_messages_after(string $channel, int $after, int $limit = 200): array
{
    $rows = rows('SELECT m.id, m.user_id, m.body, m.created_at, u.name FROM team_messages m LEFT JOIN users u ON u.id = m.user_id
                  WHERE m.channel = ? AND m.id > ? ORDER BY m.id DESC LIMIT ' . $limit, [$channel, $after]);
    return array_reverse($rows);
}

function team_channel_or_default(array $u, string $c): string
{
    $channels = team_channels_for($u);
    return isset($channels[$c]) ? $c : 'general';
}

function team_chat(): void
{
    $user = require_perm('team');
    require_team_ready($user);
    $channels = team_channels_for($user);
    $c = team_channel_or_default($user, is_string($_GET['c'] ?? null) ? $_GET['c'] : 'general');
    $messages = team_messages_after($c, 0, 150);
    if ($messages) {
        team_mark_read($user, $c, (int) end($messages)['id']);
    }
    view('admin/team_chat', ['title' => 'Équipe | Admin EliteTap', 'nav' => 'equipe', 'user' => $user,
        'channels' => $channels, 'current' => $c, 'messages' => $messages, 'unread' => team_unread($user)], 'admin');
}

function team_chat_send(): void
{
    $user = require_perm('team');
    require_team_ready($user);
    $c = team_channel_or_default($user, input('c', 40));
    $body = trim(input('body', 4000));
    if ($body !== '') {
        $key = throttle_key('team', (string) $user['id']);
        if (!too_many_attempts($key, 40, 1)) {
            hit_attempt($key);
            q('INSERT INTO team_messages (channel, user_id, body, created_at) VALUES (?, ?, ?, ?)', [$c, $user['id'], $body, now()]);
            team_mark_read($user, $c, (int) db()->lastInsertId());
        }
    }
    if (wants_json()) {
        team_chat_poll();
    }
    redirect('/admin/equipe/chat?c=' . rawurlencode($c));
}

function team_chat_poll(): void
{
    $user = require_perm('team');
    if (!team_ready()) {
        json_out(['error' => 'unavailable'], 503);
    }
    $c = team_channel_or_default($user, input('c', 40));
    $after = (int) input('after', 20);
    $msgs = team_messages_after($c, $after);
    if ($msgs) {
        team_mark_read($user, $c, (int) end($msgs)['id']);
    }
    $online = [];
    foreach (team_members() as $m) {
        $online[$m['id']] = member_online($m);
    }
    json_out([
        'me' => (int) $user['id'],
        'messages' => array_map(fn($m) => ['id' => (int) $m['id'], 'mine' => (int) $m['user_id'] === (int) $user['id'],
            'name' => $m['name'] ?? '?', 'body' => $m['body'], 'time' => date('H:i', strtotime($m['created_at'])),
            'date' => date_fr($m['created_at'])], $msgs),
        'unread' => team_unread($user), 'online' => $online,
    ]);
}
