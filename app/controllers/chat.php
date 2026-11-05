<?php
declare(strict_types=1);

/* Clavardage : côté visiteur (JSON pour le module du site) et côté service client (Admin › Clavardage). */

function chat_require_ready(): void
{
    if (!chat_ready()) {
        json_out(['error' => 'unavailable'], 503);
    }
}

function chat_state(array $s, int $after): array
{
    chat_maybe_nudge($s);
    $s = row('SELECT * FROM chat_sessions WHERE id = ?', [$s['id']]);
    $agent = $s['assigned_to'] ? scalar('SELECT name FROM users WHERE id = ?', [$s['assigned_to']]) : null;
    return [
        'status'   => $s['status'],
        'email'    => $s['visitor_email'] ? true : false,
        'agent'    => $agent ? explode(' ', trim((string) $agent))[0] : null,
        'rated'    => $s['rating'] !== null,
        'online'   => presence()['online'],
        'messages' => array_map('chat_public_message', chat_messages_after((int) $s['id'], $after)),
    ];
}

function chat_from_token(): array
{
    chat_require_ready();
    $s = chat_session_by_token(input('token', 40));
    if (!$s) {
        json_out(['error' => 'unknown'], 404);
    }
    return $s;
}

/** Nouvelle discussion (ou message laissé quand l'équipe est absente). */
function chat_start(): void
{
    chat_require_ready();
    if (input('website') !== '') {
        json_out(['ok' => true, 'mode' => 'offline']);          // robot : on ne garde rien
    }
    $name = input('name', 80);
    $email = strtolower(input('email', 190));
    $message = trim(input('message', CHAT_MAX_BODY));
    $page = parse_url(input('page', 255), PHP_URL_PATH) ?: '/';
    if ($message === '') {
        json_out(['error' => t('Écrivez votre message.')], 422);
    }
    if ($email !== '' && !valid_email($email)) {
        json_out(['error' => t('Indiquez un courriel valide.')], 422);
    }
    $key = throttle_key('chat_start');
    if (too_many_attempts($key, 6, 60)) {
        json_out(['error' => t('Trop de discussions ouvertes. Réessayez plus tard ou écrivez-nous sur WhatsApp.')], 429);
    }
    hit_attempt($key);
    chat_purge();

    // Équipe absente : le message devient une conversation courriel (Admin › Messages)
    if (!presence()['online'] || input('mode', 10) === 'offline') {
        if (!valid_email($email)) {
            json_out(['error' => t('Indiquez votre courriel pour que nous puissions vous répondre.')], 422);
        }
        if (inbox_ready()) {
            $tid = thread_create($email, $name, t('Message laissé sur le site'), null, lang());
            thread_add_message($tid, ['direction' => 'in', 'kind' => 'chat', 'from_email' => $email, 'from_name' => $name,
                'subject' => 'Message laissé sur le site', 'body' => $message . "\n\n(Page : $page)"]);
            inbox_maybe_autoreply($tid, ['from_email' => $email, 'from_name' => $name, 'subject' => t('Votre message'), 'text' => $message]);
            notify_new_chat($name ?: $email, $message, "/admin/messages/$tid", false);
        }
        json_out(['ok' => true, 'mode' => 'offline']);
    }

    $token = bin2hex(random_bytes(16));
    q('INSERT INTO chat_sessions (token_hash, visitor_name, visitor_email, lang, page, status, last_message_at, last_visitor_at, created_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [hash('sha256', $token), $name, $email ?: null, lang(), mb_substr($page, 0, 255), 'waiting', now(), now(), now()]);
    $s = row('SELECT * FROM chat_sessions WHERE id = ?', [(int) db()->lastInsertId()]);
    chat_add($s, 'visitor', $message);
    // Personne n'est dans l'admin en ce moment : on prévient par courriel
    $seen = last_admin_activity();
    if (!$seen || $seen < time() - PRESENCE_ACTIVE_MINUTES * 60) {
        notify_new_chat($name ?: 'Un visiteur', $message, '/admin/chat?s=' . $s['id'], true);
    }
    json_out(['ok' => true, 'mode' => 'chat', 'token' => $token] + chat_state($s, 0));
}

function notify_new_chat(string $who, string $message, string $path, bool $live): void
{
    if (setting('inbox_notify', '1') !== '1') {
        return;
    }
    send_mail(notify_email(), ($live ? 'Clavardage en attente : ' : 'Message laissé sur le site : ') . $who,
        ($live ? "Un visiteur attend une réponse sur le clavardage du site.\n\n" : "Un visiteur a laissé un message (équipe absente).\n\n")
        . mb_substr($message, 0, 1000) . "\n\nRépondre : " . url($path) . "\n");
}

function chat_send(): void
{
    $s = chat_from_token();
    if ($s['status'] === 'closed') {
        json_out(['error' => 'closed'] + chat_state($s, (int) input('after', 20)), 409);
    }
    $body = trim(input('body', CHAT_MAX_BODY));
    if ($body !== '') {
        $n = (int) scalar("SELECT COUNT(*) FROM chat_messages WHERE session_id = ? AND sender = 'visitor' AND created_at >= ?",
            [$s['id'], date('Y-m-d H:i:s', time() - 600)]);
        if ($n >= 40) {
            json_out(['error' => t('Trop de messages. Patientez un instant.')], 429);
        }
        chat_add($s, 'visitor', $body);
    }
    json_out(['ok' => true] + chat_state($s, (int) input('after', 20)));
}

function chat_poll(): void
{
    $s = chat_from_token();
    if ($s['status'] !== 'closed' && strtotime($s['last_visitor_at']) < time() - 20) {
        q('UPDATE chat_sessions SET last_visitor_at = ? WHERE id = ?', [now(), $s['id']]);
    }
    json_out(['ok' => true] + chat_state($s, (int) input('after', 20)));
}

function chat_email(): void
{
    $s = chat_from_token();
    $email = strtolower(input('email', 190));
    if (!valid_email($email)) {
        json_out(['error' => t('Indiquez un courriel valide.')], 422);
    }
    q('UPDATE chat_sessions SET visitor_email = ?, visitor_name = CASE WHEN visitor_name = \'\' THEN ? ELSE visitor_name END WHERE id = ?',
        [$email, input('name', 80), $s['id']]);
    json_out(['ok' => true] + chat_state($s, (int) input('after', 20)));
}

function chat_close_visitor(): void
{
    $s = chat_from_token();
    chat_close($s, 'visitor');
    $s = row('SELECT * FROM chat_sessions WHERE id = ?', [$s['id']]);
    if (input('transcript', 1) === '1' && $s['visitor_email']) {
        in_lang($s['lang'], fn() => send_mail($s['visitor_email'], t('Votre discussion avec EliteTap'),
            t("Bonjour,\n\nVoici la transcription de votre discussion avec EliteTap :\n\n{texte}\n\nL'équipe EliteTap\n", ['texte' => chat_transcript($s)])));
    }
    json_out(['ok' => true] + chat_state($s, (int) input('after', 20)));
}

function chat_rate(): void
{
    $s = chat_from_token();
    $r = input('rating', 1);
    if (in_array($r, ['0', '1'], true) && $s['rating'] === null) {
        q('UPDATE chat_sessions SET rating = ? WHERE id = ?', [(int) $r, $s['id']]);
    }
    json_out(['ok' => true]);
}

/* ------------------------------------------------------------------ */
/* Infolettre (public)                                                 */
/* ------------------------------------------------------------------ */

function newsletter_signup(): void
{
    $json = wants_json();
    $email = strtolower(input('email', 190));
    $source = in_array(input('source', 20), ['chat', 'after_chat', 'footer'], true) ? input('source', 20) : 'site';
    $consent = isset($_POST['consent']);
    $key = throttle_key('newsletter');
    $error = null;
    if (input('website') !== '') {
        $result = 'pending';                                   // robot
    } elseif (!$consent) {
        $error = t('Cochez la case pour accepter de recevoir l\'infolettre.');
    } elseif (!valid_email($email)) {
        $error = t('Indiquez un courriel valide.');
    } elseif (too_many_attempts($key, 5, 60)) {
        $error = t('Trop de demandes. Réessayez plus tard.');
    } else {
        hit_attempt($key);
        $result = newsletter_subscribe($email, input('name', 120), lang(), $source);
        if ($result === 'invalid') {
            $error = t('Inscription impossible pour le moment.');
        }
    }
    $msg = $error ?? (($result ?? '') === 'already' ? t('Vous êtes déjà inscrit à l\'infolettre. Merci !')
        : t('Presque fini : confirmez votre inscription avec le lien reçu par courriel.'));
    if ($json) {
        json_out($error ? ['error' => $error] : ['ok' => true, 'message' => $msg], $error ? 422 : 200);
    }
    flash($error ? 'error' : 'success', $msg);
    $back = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? '/'), PHP_URL_PATH) ?: '/';
    redirect(($back[0] ?? '') === '/' && !str_starts_with($back, '//') ? $back . '#infolettre' : '/#infolettre');
}

function newsletter_confirm(string $token): void
{
    $sub = newsletter_ready() ? row('SELECT * FROM newsletter_subscribers WHERE confirm_token = ?', [$token]) : null;
    if ($sub && $sub['status'] === 'pending') {
        q("UPDATE newsletter_subscribers SET status = 'confirmed', confirmed_at = ? WHERE id = ?", [now(), $sub['id']]);
    }
    $ok = $sub && $sub['status'] !== 'unsubscribed';
    view('site/newsletter', ['title' => t('Infolettre | EliteTap'), 'heading' => $ok ? t('Inscription confirmée') : t('Lien invalide'),
        'text' => $ok ? t('Merci ! Vous recevrez l\'infolettre d\'EliteTap. Vous pouvez vous désabonner en tout temps avec le lien présent dans chaque envoi.')
            : t('Ce lien de confirmation n\'est plus valide. Inscrivez-vous de nouveau depuis le site.')], 'site');
}

function newsletter_unsubscribe(string $token): void
{
    $sub = newsletter_ready() ? row('SELECT * FROM newsletter_subscribers WHERE unsub_token = ?', [$token]) : null;
    if ($sub && $sub['status'] !== 'unsubscribed') {
        q("UPDATE newsletter_subscribers SET status = 'unsubscribed', unsubscribed_at = ? WHERE id = ?", [now(), $sub['id']]);
    }
    view('site/newsletter', ['title' => t('Infolettre | EliteTap'), 'heading' => $sub ? t('Vous êtes désabonné') : t('Lien invalide'),
        'text' => $sub ? t('Vous ne recevrez plus l\'infolettre d\'EliteTap. Vous pouvez vous réinscrire en tout temps depuis le site.')
            : t('Ce lien de désabonnement n\'est pas valide.')], 'site');
}

/* ------------------------------------------------------------------ */
/* Service client : Admin › Clavardage                                 */
/* ------------------------------------------------------------------ */

function agent_sessions(): array
{
    return rows("SELECT s.*, u.name agent_name,
            (SELECT body FROM chat_messages m WHERE m.session_id = s.id ORDER BY m.id DESC LIMIT 1) last_body,
            (SELECT COUNT(*) FROM chat_messages m WHERE m.session_id = s.id AND m.sender = 'visitor' AND m.id > s.agent_last_read) unread
        FROM chat_sessions s LEFT JOIN users u ON u.id = s.assigned_to
        WHERE s.status <> 'closed' OR s.closed_at >= ?
        ORDER BY CASE s.status WHEN 'waiting' THEN 0 WHEN 'active' THEN 1 ELSE 2 END, s.last_message_at DESC LIMIT 60",
        [date('Y-m-d H:i:s', time() - 86400)]);
}

function agent_session_summary(array $s): array
{
    $label = $s['visitor_name'] ?: ($s['visitor_email'] ?: 'Visiteur #' . $s['id']);
    return ['id' => (int) $s['id'], 'label' => $label, 'status' => $s['status'], 'unread' => $s['status'] === 'closed' ? 0 : (int) $s['unread'],
        'agent' => $s['agent_name'], 'last' => mb_strimwidth(preg_replace('/\s+/', ' ', (string) $s['last_body']), 0, 80, '…'),
        'time' => date('H:i', strtotime($s['last_message_at'])),
        'away' => $s['status'] !== 'closed' && strtotime($s['last_visitor_at']) < time() - 60];
}

function agent_message(array $m): array
{
    return ['id' => (int) $m['id'], 'from' => $m['sender'], 'name' => $m['sender'] === 'agent' ? ($m['agent_name'] ?? 'EliteTap') : null,
        'body' => $m['body'], 'time' => date('H:i', strtotime($m['created_at']))];
}

function admin_chat(): void
{
    $user = require_perm('service');
    if (!chat_ready()) {
        flash('error', 'Le clavardage a besoin de la migration 006 : exécutez database/migrations/006-equipe-clavardage-infolettre.mysql.sql dans phpMyAdmin.');
        redirect('/admin/service');
    }
    $sid = (int) ($_GET['s'] ?? 0);
    $current = $sid ? row('SELECT * FROM chat_sessions WHERE id = ?', [$sid]) : null;
    $messages = $current ? chat_messages_after((int) $current['id'], 0) : [];
    if ($current && $messages) {
        q('UPDATE chat_sessions SET agent_last_read = ? WHERE id = ?', [(int) end($messages)['id'], $current['id']]);
    }
    $history = $current && $current['visitor_email']
        ? rows('SELECT id, created_at FROM chat_sessions WHERE visitor_email = ? AND id <> ? ORDER BY id DESC LIMIT 5', [$current['visitor_email'], $current['id']]) : [];
    $order = $current && $current['visitor_email']
        ? row("SELECT id, reference, status FROM orders WHERE email = ? ORDER BY id DESC LIMIT 1", [$current['visitor_email']]) : null;
    view('admin/chat', ['title' => 'Clavardage | Admin EliteTap', 'nav' => 'clavardage', 'user' => $user,
        'sessions' => agent_sessions(), 'current' => $current, 'messages' => $messages, 'history' => $history, 'order' => $order,
        'agents' => array_filter(team_members(), fn($m) => staff_can($m, 'service'))], 'admin');
}

function admin_chat_poll(): void
{
    $user = require_perm('service');
    chat_require_ready();
    $sid = (int) input('s', 12);
    $after = (int) input('after', 20);
    $out = ['sessions' => array_map('agent_session_summary', agent_sessions()), 'waiting' => chat_waiting_count()];
    if ($sid && ($s = row('SELECT * FROM chat_sessions WHERE id = ?', [$sid]))) {
        $msgs = chat_messages_after($sid, $after);
        if ($msgs) {
            q('UPDATE chat_sessions SET agent_last_read = ? WHERE id = ? AND agent_last_read < ?', [(int) end($msgs)['id'], $sid, (int) end($msgs)['id']]);
        }
        $out['current'] = ['id' => $sid, 'status' => $s['status'], 'messages' => array_map('agent_message', $msgs),
            'visitorAway' => $s['status'] !== 'closed' && strtotime($s['last_visitor_at']) < time() - 60,
            'email' => $s['visitor_email']];
    }
    json_out($out);
}

function admin_chat_send(string $id): void
{
    $user = require_perm('service');
    chat_require_ready();
    $s = row('SELECT * FROM chat_sessions WHERE id = ?', [(int) $id]) ?? abort(404);
    $body = trim(input('body', CHAT_MAX_BODY));
    if ($body !== '' && $s['status'] !== 'closed') {
        chat_add($s, 'agent', $body, (int) $user['id']);
    }
    if (wants_json()) {
        admin_chat_poll();
    }
    redirect('/admin/chat?s=' . $s['id']);
}

function admin_chat_action(string $id): void
{
    $user = require_perm('service');
    chat_require_ready();
    $s = row('SELECT * FROM chat_sessions WHERE id = ?', [(int) $id]) ?? abort(404);
    switch (input('action', 20)) {
        case 'take':
            q('UPDATE chat_sessions SET assigned_to = ? WHERE id = ?', [$user['id'], $s['id']]);
            break;
        case 'assign':
            $to = (int) input('agent', 12);
            $agent = row('SELECT * FROM users WHERE id = ? AND is_active = 1', [$to]);
            if ($agent && staff_can($agent, 'service')) {
                q('UPDATE chat_sessions SET assigned_to = ? WHERE id = ?', [$to, $s['id']]);
                flash('success', "Discussion confiée à {$agent['name']}.");
            }
            break;
        case 'close':
            chat_close($s, 'agent', (int) $user['id']);
            flash('success', 'Discussion terminée.' . ($s['visitor_email'] ? ' La transcription est dans Messages.' : ''));
            break;
    }
    redirect('/admin/chat?s=' . $s['id']);
}

/* ------------------------------------------------------------------ */
/* Admin › Infolettre                                                  */
/* ------------------------------------------------------------------ */

function admin_newsletter(): void
{
    $user = require_perm('newsletter');
    if (!newsletter_ready()) {
        flash('error', 'L\'infolettre a besoin de la migration 006.');
        redirect(staff_home($user));
    }
    $status = in_array($_GET['statut'] ?? '', ['confirmed', 'pending', 'unsubscribed'], true) ? $_GET['statut'] : 'confirmed';
    $search = trim(mb_substr(is_string($_GET['q'] ?? null) ? $_GET['q'] : '', 0, 100));
    $params = [$status];
    $where = 'status = ?';
    if ($search !== '') {
        $where .= ' AND (email LIKE ? OR name LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $counts = [];
    foreach (rows('SELECT status, COUNT(*) n FROM newsletter_subscribers GROUP BY status') as $r) {
        $counts[$r['status']] = (int) $r['n'];
    }
    view('admin/newsletter', ['title' => 'Infolettre | Admin EliteTap', 'nav' => 'infolettre', 'user' => $user,
        'subs' => rows("SELECT * FROM newsletter_subscribers WHERE $where ORDER BY created_at DESC LIMIT 500", $params),
        'counts' => $counts, 'status' => $status, 'search' => $search,
        'month' => (int) scalar("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'confirmed' AND confirmed_at >= ?", [date('Y-m-d H:i:s', time() - 30 * 86400)])], 'admin');
}

function admin_newsletter_export(): void
{
    require_perm('newsletter');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="infolettre-elitetap-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['courriel', 'nom', 'langue', 'source', 'consentement', 'date_consentement', 'date_confirmation', 'lien_desabonnement']);
    foreach (rows("SELECT * FROM newsletter_subscribers WHERE status = 'confirmed' ORDER BY confirmed_at") as $s) {
        fputcsv($out, [$s['email'], $s['name'], $s['lang'], $s['source'], $s['consent_text'], $s['consented_at'], $s['confirmed_at'], newsletter_unsub_link($s)]);
    }
    exit;
}

function admin_newsletter_delete(string $id): void
{
    require_perm('newsletter');
    q('DELETE FROM newsletter_subscribers WHERE id = ?', [(int) $id]);
    flash('success', 'Abonné supprimé (ses données sont effacées).');
    redirect('/admin/infolettre');
}
