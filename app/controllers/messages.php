<?php
declare(strict_types=1);

/* Admin › Messages : conversations, réponses, présence et réponses automatiques. */

const THREAD_VIEWS = ['ouvertes' => 'Ouvertes', 'non-lues' => 'Non lues', 'archivees' => 'Archivées', 'robots' => 'Robots et listes'];
function get_param(string $k, int $max): string
{
    $v = $_GET[$k] ?? '';
    return is_string($v) ? trim(mb_substr($v, 0, $max)) : '';
}

const DEFAULT_SIGNATURE = "L'équipe EliteTap\ninfo@elitetap.ca · elitetap.ca";

function require_inbox(): void
{
    if (!inbox_ready()) {
        flash('error', 'La messagerie a besoin de la migration 005 : exécutez database/migrations/005-messagerie.mysql.sql dans phpMyAdmin.');
        redirect('/admin');
    }
}

function admin_messages(): void
{
    $user = require_admin();
    require_inbox();
    $vue = $_GET['vue'] ?? 'ouvertes';
    $vue = isset(THREAD_VIEWS[$vue]) ? $vue : 'ouvertes';
    $where = match ($vue) {
        'non-lues'  => "t.unread > 0 AND t.status <> 'spam'",
        'archivees' => "t.status = 'archived'",
        'robots'    => "t.status = 'spam'",
        default     => "t.status = 'open'",
    };
    $params = [];
    $search = trim(mb_substr(is_string($_GET['q'] ?? null) ? $_GET['q'] : '', 0, 100));
    if ($search !== '') {
        $where .= ' AND (t.contact_email LIKE ? OR t.contact_name LIKE ? OR t.subject LIKE ? OR EXISTS (SELECT 1 FROM messages m2 WHERE m2.thread_id = t.id AND m2.body LIKE ?))';
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
        $params = [$like, $like, $like, $like];
    }
    $threads = rows("SELECT t.*, o.reference,
            (SELECT body FROM messages m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) last_body,
            (SELECT direction FROM messages m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) last_dir,
            (SELECT kind FROM messages m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) last_kind
        FROM threads t LEFT JOIN orders o ON o.id = t.order_id
        WHERE $where ORDER BY t.last_message_at DESC LIMIT 200", $params);
    $counts = [
        'ouvertes'  => (int) scalar("SELECT COUNT(*) FROM threads WHERE status = 'open'"),
        'non-lues'  => (int) scalar("SELECT COUNT(*) FROM threads WHERE unread > 0 AND status <> 'spam'"),
        'archivees' => (int) scalar("SELECT COUNT(*) FROM threads WHERE status = 'archived'"),
        'robots'    => (int) scalar("SELECT COUNT(*) FROM threads WHERE status = 'spam'"),
    ];
    $lastIn = scalar("SELECT MAX(created_at) FROM messages WHERE direction = 'in' AND kind = 'mail'");
    view('admin/messages', ['title' => 'Messages | Admin EliteTap', 'nav' => 'messages', 'user' => $user,
        'threads' => $threads, 'vue' => $vue, 'counts' => $counts, 'search' => $search, 'lastIn' => $lastIn], 'admin');
}

function admin_message(string $id): void
{
    $user = require_admin();
    require_inbox();
    $t = row('SELECT * FROM threads WHERE id = ?', [(int) $id]) ?? abort(404);
    if ($t['unread'] > 0) {
        q('UPDATE threads SET unread = 0 WHERE id = ?', [$t['id']]);
    }
    $messages = rows('SELECT m.*, u.name sender_name FROM messages m LEFT JOIN users u ON u.id = m.sent_by WHERE m.thread_id = ? ORDER BY m.id', [$t['id']]);
    $order = $t['order_id'] ? row('SELECT * FROM orders WHERE id = ?', [$t['order_id']]) : null;
    $client = row('SELECT * FROM users WHERE email = ?', [$t['contact_email']]);
    $others = rows('SELECT id, subject, last_message_at FROM threads WHERE contact_email = ? AND id <> ? ORDER BY last_message_at DESC LIMIT 5', [$t['contact_email'], $t['id']]);
    $lastIn = null;
    foreach ($messages as $m) {
        if ($m['direction'] === 'in') {
            $lastIn = $m;
        }
    }
    view('admin/message', ['title' => e($t['contact_name'] ?: $t['contact_email']) . ' | Messages', 'nav' => 'messages', 'user' => $user,
        't' => $t, 'messages' => $messages, 'order' => $order, 'client' => $client, 'others' => $others, 'lastIn' => $lastIn,
        'signature' => setting('mail_signature', DEFAULT_SIGNATURE)], 'admin');
}

/** Réponse de l'admin : texte + signature + dernier message du client cité. */
function admin_message_reply(string $id): void
{
    $admin = require_admin();
    require_inbox();
    $t = row('SELECT * FROM threads WHERE id = ?', [(int) $id]) ?? abort(404);
    $body = trim(input('body', 20000));
    if ($body === '') {
        flash('error', 'Écrivez votre réponse.');
        redirect("/admin/messages/{$t['id']}#repondre");
    }
    $subject = input('subject', 200) ?: ('Re: ' . base_subject($t['subject']));
    $text = $body;
    if (isset($_POST['signature'])) {
        $text .= "\n\n" . setting('mail_signature', DEFAULT_SIGNATURE);
    }
    $last = row("SELECT * FROM messages WHERE thread_id = ? AND direction = 'in' ORDER BY id DESC LIMIT 1", [$t['id']]);
    if ($last && isset($_POST['quote'])) {
        $who = $last['from_name'] ?: $last['from_email'];
        $intro = ($t['lang'] === 'en' ? 'On ' . date('M j, Y \a\t g:i A', strtotime($last['created_at'])) . ", $who wrote:"
            : 'Le ' . date('d/m/Y à H:i', strtotime($last['created_at'])) . ", $who a écrit :");
        $quoted = implode("\n", array_map(fn($l) => '> ' . $l, array_slice(explode("\n", $last['body']), 0, 40)));
        $text .= "\n\n$intro\n$quoted";
    }
    $ok = thread_send((int) $t['id'], $subject, $text, 'mail', (int) $admin['id']);
    if ($ok && isset($_POST['archive'])) {
        q("UPDATE threads SET status = 'archived' WHERE id = ?", [$t['id']]);
    } elseif ($t['status'] === 'spam') {
        q("UPDATE threads SET status = 'open' WHERE id = ?", [$t['id']]);
    }
    flash($ok ? 'success' : 'error', $ok ? 'Réponse envoyée à ' . $t['contact_email'] . '.'
        : "L'envoi a échoué (voir Admin › Courriels). Votre texte est conservé dans la conversation.");
    redirect($ok && isset($_POST['archive']) ? '/admin/messages' : "/admin/messages/{$t['id']}");
}

function admin_message_action(string $id): void
{
    require_admin();
    require_inbox();
    $t = row('SELECT * FROM threads WHERE id = ?', [(int) $id]) ?? abort(404);
    $action = input('action', 20);
    switch ($action) {
        case 'archive':
            q("UPDATE threads SET status = 'archived', unread = 0 WHERE id = ?", [$t['id']]);
            flash('success', 'Conversation archivée. Elle revient dans « Ouvertes » si le client répond.');
            redirect('/admin/messages');
        case 'reopen':
        case 'notspam':
            q("UPDATE threads SET status = 'open' WHERE id = ?", [$t['id']]);
            break;
        case 'spam':
            q("UPDATE threads SET status = 'spam', unread = 0 WHERE id = ?", [$t['id']]);
            flash('success', 'Rangée dans « Robots et listes ».');
            redirect('/admin/messages');
        case 'unread':
            q('UPDATE threads SET unread = 1 WHERE id = ?', [$t['id']]);
            redirect('/admin/messages');
    }
    redirect("/admin/messages/{$t['id']}");
}

/** Nouvelle conversation : depuis une commande (?commande=ID) ou vers une adresse. */
function admin_message_new(): void
{
    $user = require_admin();
    require_inbox();
    if ($oid = (int) ($_GET['commande'] ?? 0)) {
        $o = row('SELECT * FROM orders WHERE id = ?', [$oid]) ?? abort(404);
        redirect('/admin/messages/' . order_thread_id($o) . '#repondre');
    }
    view('admin/message_new', ['title' => 'Nouveau message | Admin EliteTap', 'nav' => 'messages', 'user' => $user,
        'to' => strtolower(get_param('a', 190)), 'name' => get_param('nom', 120), 'signature' => setting('mail_signature', DEFAULT_SIGNATURE)], 'admin');
}

function admin_message_create(): void
{
    $admin = require_admin();
    require_inbox();
    $to = strtolower(input('to', 190));
    $name = input('name', 120);
    $subject = input('subject', 200);
    $body = trim(input('body', 20000));
    if (!valid_email($to) || $subject === '' || $body === '') {
        flash('error', 'Indiquez un courriel valide, un sujet et un message.');
        with_old(['to' => $to, 'name' => $name, 'subject' => $subject, 'body' => $body]);
        redirect('/admin/messages/nouveau');
    }
    $tid = thread_create($to, $name, $subject);
    $text = $body . (isset($_POST['signature']) ? "\n\n" . setting('mail_signature', DEFAULT_SIGNATURE) : '');
    $ok = thread_send($tid, $subject, $text, 'mail', (int) $admin['id']);
    flash($ok ? 'success' : 'error', $ok ? "Message envoyé à $to." : "L'envoi a échoué (voir Admin › Courriels).");
    redirect("/admin/messages/$tid");
}

function admin_message_file(string $msgId, string $idx): void
{
    require_admin();
    require_inbox();
    $m = row('SELECT attachments FROM messages WHERE id = ?', [(int) $msgId]) ?? abort(404);
    $list = json_decode((string) $m['attachments'], true) ?: [];
    $a = $list[(int) $idx] ?? abort(404);
    $path = realpath(INBOX_DIR . '/' . $a['path']);
    if (!$path || !str_starts_with($path, realpath(INBOX_DIR)) || !is_file($path)) {
        abort(404);
    }
    $inline = preg_match('#^image/(png|jpe?g|gif|webp)$|^application/pdf$#', $a['type'] ?? '') && isset($_GET['voir']);
    header('Content-Type: ' . ($inline ? $a['type'] : 'application/octet-stream'));
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($a['name']));
    header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
    readfile($path);
    exit;
}

/** Bouton de présence de la barre latérale. */
function admin_presence(): void
{
    require_admin();
    $mode = input('mode', 10);
    if (in_array($mode, ['auto', 'online', 'away'], true)) {
        set_setting('presence_mode', $mode);
        if ($mode !== 'away') {
            set_setting('away_until', null);
        }
    }
    $back = (string) ($_SERVER['HTTP_REFERER'] ?? '/admin');
    $path = parse_url($back, PHP_URL_PATH) ?: '/admin';
    $query = parse_url($back, PHP_URL_QUERY);
    redirect(str_starts_with($path, '/admin') ? $path . ($query ? "?$query" : '') : '/admin');
}

function admin_messages_settings(): void
{
    $user = require_admin();
    $tpl = [];
    foreach (AUTOREPLY_DEFAULTS as $k => $v) {
        $tpl[$k] = setting($k, $v);
    }
    view('admin/messages_settings', ['title' => 'Présence et réponses automatiques | Admin EliteTap', 'nav' => 'messages', 'user' => $user,
        'tpl' => $tpl, 'hours' => business_hours(), 'presence' => presence(),
        'signature' => setting('mail_signature', DEFAULT_SIGNATURE)], 'admin');
}

function admin_messages_settings_save(): void
{
    require_admin();
    $errors = [];
    $mode = input('presence_mode', 10);
    set_setting('presence_mode', in_array($mode, ['auto', 'online', 'away'], true) ? $mode : 'auto');
    $until = input('away_until', 10);
    if ($until !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $until)) {
        $errors[] = 'Date de retour invalide.';
    } else {
        set_setting('away_until', $until ?: null);
    }
    $days = array_values(array_filter(array_map('intval', (array) ($_POST['hours_days'] ?? [])), fn($d) => $d >= 1 && $d <= 7));
    sort($days);
    set_setting('hours_days', implode(',', $days));
    $from = input('hours_from', 5);
    $to = input('hours_to', 5);
    $hm = '/^([01]\d|2[0-3]):[0-5]\d$/';
    if (!preg_match($hm, $from) || !preg_match($hm, $to) || $from >= $to) {
        $errors[] = 'Heures d\'ouverture invalides : format HH:MM, fermeture après l\'ouverture.';
    } else {
        set_setting('hours_from', $from);
        set_setting('hours_to', $to);
    }
    set_setting('autoreply_enabled', isset($_POST['autoreply_enabled']) ? '1' : '0');
    set_setting('inbox_notify', isset($_POST['inbox_notify']) ? '1' : '0');
    foreach (AUTOREPLY_DEFAULTS as $k => $default) {
        $v = trim(input($k, 4000));
        set_setting($k, ($v === '' || $v === $default) ? null : $v);
    }
    $sig = trim(input('mail_signature', 500));
    set_setting('mail_signature', ($sig === '' || $sig === DEFAULT_SIGNATURE) ? null : $sig);
    foreach ($errors as $e) flash('error', $e);
    if (!$errors) flash('success', 'Réglages de la messagerie enregistrés.');
    redirect('/admin/messages/reglages');
}
