<?php
declare(strict_types=1);

/*
 * Messagerie : conversations avec les clients, dans l'admin.
 *  - Réception : cPanel transmet chaque courriel reçu sur info@ à bin/inbound-mail.php (redirecteur « pipe »).
 *    Le courriel reste aussi dans la boîte info@ (webmail).
 *  - Les demandes du formulaire du site ouvrent aussi une conversation.
 *  - Les réponses de l'admin partent depuis info@ avec les en-têtes de suivi (In-Reply-To / References) :
 *    la réponse du client revient dans la même conversation.
 *  - Réponses automatiques : texte « en ligne » ou « absent » selon la présence (manuelle ou automatique).
 */

const INBOX_MAX_RAW = 25 * 1024 * 1024;      // courriel brut accepté (25 Mo)
const INBOX_MAX_ATTACHMENT = 15 * 1024 * 1024;
const INBOX_DIR = APP_ROOT . '/storage/uploads/inbox';
const PRESENCE_ACTIVE_MINUTES = 15;           // un admin actif depuis moins de 15 min = en ligne
const WEEKDAYS_FR = [1 => 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
const WEEKDAYS_EN = [1 => 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

/* ------------------------------------------------------------------ */
/* Présence                                                            */
/* ------------------------------------------------------------------ */

/** Note l'activité d'un admin (au plus une écriture par minute). */
function touch_presence(array $user): void
{
    if (!in_array($user['role'] ?? '', ['admin', 'staff'], true) || PHP_SAPI === 'cli') {
        return;
    }
    if (($_SESSION['seen_at'] ?? 0) > time() - 60) {
        return;
    }
    $_SESSION['seen_at'] = time();
    try {
        q('UPDATE users SET last_seen_at = ? WHERE id = ?', [now(), $user['id']]);
    } catch (Throwable $e) {
        // Colonne absente (migration 005 non exécutée)
    }
}

/** Heures d'ouverture : [jours (1 = lundi … 7), 'HH:MM' début, 'HH:MM' fin]. */
function business_hours(): array
{
    $days = array_values(array_filter(array_map('intval', explode(',', setting('hours_days', '1,2,3,4,5'))),
        fn($d) => $d >= 1 && $d <= 7));
    return [$days, setting('hours_from', '09:00'), setting('hours_to', '17:00')];
}

function within_business_hours(?int $ts = null): bool
{
    $ts ??= time();
    [$days, $from, $to] = business_hours();
    $hm = date('H:i', $ts);
    return in_array((int) date('N', $ts), $days, true) && $hm >= $from && $hm < $to;
}

/** Prochaine ouverture (timestamp), pour « nous vous répondrons dès … ». */
function next_opening(?int $ts = null): ?int
{
    $ts ??= time();
    [$days, $from] = business_hours();
    if (!$days) {
        return null;
    }
    for ($i = 0; $i <= 7; $i++) {
        $day = strtotime(date('Y-m-d', $ts) . " +$i day");
        $open = strtotime(date('Y-m-d', $day) . ' ' . $from);
        if (in_array((int) date('N', $day), $days, true) && $open > $ts) {
            return $open;
        }
    }
    return null;
}

function away_until(): ?int
{
    $d = setting('away_until');
    $ts = $d ? strtotime($d . ' 23:59:59') : false;
    return $ts && $ts > time() ? $ts : null;
}

function last_admin_activity(): ?int
{
    try {
        // Présence du service client : admins et employés du service client
        $v = scalar("SELECT MAX(last_seen_at) FROM users WHERE is_active = 1 AND (role = 'admin' OR (role = 'staff' AND services LIKE '%service%'))");
        return $v ? strtotime((string) $v) : null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Présence actuelle.
 * Mode manuel « online » / « away », sinon automatique : en ligne pendant les heures d'ouverture
 * ou si un admin est actif dans l'administration. Une date « absent jusqu'au » l'emporte sur tout.
 * @return array{online: bool, mode: string, reason: string, back: ?int}
 */
function presence(): array
{
    $mode = setting('presence_mode', 'auto');
    if ($until = away_until()) {
        return ['online' => false, 'mode' => 'vacances', 'reason' => 'Absent jusqu\'au ' . date('d/m/Y', $until),
            'back' => next_opening($until) ?? $until + 1];
    }
    if ($mode === 'online') {
        return ['online' => true, 'mode' => 'online', 'reason' => 'Statut réglé à la main', 'back' => null];
    }
    if ($mode === 'away') {
        return ['online' => false, 'mode' => 'away', 'reason' => 'Statut réglé à la main', 'back' => null];
    }
    $active = ($seen = last_admin_activity()) && $seen > time() - PRESENCE_ACTIVE_MINUTES * 60;
    if (within_business_hours()) {
        return ['online' => true, 'mode' => 'auto', 'reason' => 'Heures d\'ouverture', 'back' => null];
    }
    if ($active) {
        return ['online' => true, 'mode' => 'auto', 'reason' => 'Actif dans l\'admin', 'back' => null];
    }
    return ['online' => false, 'mode' => 'auto', 'reason' => 'Hors des heures d\'ouverture', 'back' => next_opening()];
}

/** « lundi 9 h » / « Monday 9:00 AM » : moment du retour, dans la langue du message. */
function format_back(?int $ts, string $lang): string
{
    if (!$ts) {
        return $lang === 'en' ? 'as soon as possible' : 'dès que possible';
    }
    $sameWeek = $ts - time() < 6 * 86400;
    if ($lang === 'en') {
        $day = WEEKDAYS_EN[(int) date('N', $ts)];
        return ($sameWeek ? $day : $day . ' ' . date('F j', $ts)) . ' at ' . date('g:i A', $ts);
    }
    $day = WEEKDAYS_FR[(int) date('N', $ts)];
    $h = (int) date('G', $ts) . ' h' . (date('i', $ts) !== '00' ? ' ' . date('i', $ts) : '');
    return ($sameWeek ? $day : $day . ' ' . (int) date('j', $ts) . ' ' . month_fr((int) date('n', $ts))) . ' à ' . $h;
}

function month_fr(int $m): string
{
    return ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'][$m];
}

/** Heures d'ouverture lisibles : « du lundi au vendredi, 9 h à 17 h ». */
function hours_label(string $lang = 'fr'): string
{
    [$days, $from, $to] = business_hours();
    if (!$days) {
        return '';
    }
    $names = $lang === 'en' ? WEEKDAYS_EN : WEEKDAYS_FR;
    $contiguous = $days === range($days[0], end($days));
    $d = $contiguous && count($days) > 2
        ? ($lang === 'en' ? $names[$days[0]] . ' to ' . $names[end($days)] : 'du ' . $names[$days[0]] . ' au ' . $names[end($days)])
        : implode(', ', array_map(fn($x) => $names[$x], $days));
    $fmt = fn(string $hm) => $lang === 'en' ? date('g:i A', strtotime($hm)) : (int) substr($hm, 0, 2) . ' h' . (substr($hm, 3) !== '00' ? ' ' . substr($hm, 3) : '');
    return $lang === 'en' ? "$d, {$fmt($from)} to {$fmt($to)}" : "$d, de {$fmt($from)} à {$fmt($to)}";
}

/* ------------------------------------------------------------------ */
/* Réponses automatiques                                               */
/* ------------------------------------------------------------------ */

const AUTOREPLY_DEFAULTS = [
    'autoreply_online_fr' => "Bonjour {nom},\n\nMerci pour votre message, nous l'avons bien reçu. Notre équipe est en ligne et vous répond dans les plus brefs délais.\n\nPour une réponse immédiate, écrivez-nous sur WhatsApp : {whatsapp}\n\nL'équipe EliteTap",
    'autoreply_away_fr'   => "Bonjour {nom},\n\nMerci pour votre message, nous l'avons bien reçu. Notre équipe est absente pour le moment ; nous vous répondrons {retour}.\n\nNos heures : {heures}.\n\nL'équipe EliteTap",
    'autoreply_online_en' => "Hello {nom},\n\nThank you for your message, we have received it. Our team is online and will get back to you shortly.\n\nFor an immediate answer, message us on WhatsApp: {whatsapp}\n\nThe EliteTap team",
    'autoreply_away_en'   => "Hello {nom},\n\nThank you for your message, we have received it. Our team is away right now; we will reply {retour}.\n\nOur hours: {heures}.\n\nThe EliteTap team",
];

function autoreply_text(bool $online, string $lang, string $name): string
{
    $lang = $lang === 'en' ? 'en' : 'fr';
    $key = 'autoreply_' . ($online ? 'online' : 'away') . '_' . $lang;
    $p = presence();
    return strtr(setting($key, AUTOREPLY_DEFAULTS[$key]), [
        '{nom}'      => $name !== '' ? $name : ($lang === 'en' ? 'there' : ''),
        '{retour}'   => format_back($p['back'], $lang),
        '{heures}'   => hours_label($lang),
        '{whatsapp}' => 'https://wa.me/' . config('whatsapp'),
    ]);
}

/** Phrase de présence ajoutée aux courriels de confirmation de commande. */
function presence_line(string $lang): string
{
    $p = presence();
    if ($lang === 'en') {
        return $p['online'] ? 'Our team is online right now and will review your request shortly.'
            : 'Our team is away right now; we will review your request ' . format_back($p['back'], 'en') . '.';
    }
    return $p['online'] ? 'Notre équipe est en ligne et traite votre demande dans les plus brefs délais.'
        : 'Notre équipe est absente pour le moment ; nous traiterons votre demande ' . format_back($p['back'], 'fr') . '.';
}

/* ------------------------------------------------------------------ */
/* Conversations                                                       */
/* ------------------------------------------------------------------ */

function inbox_ready(): bool
{
    static $ok = null;
    if ($ok === null) {
        try {
            scalar('SELECT COUNT(*) FROM threads WHERE 1 = 0');
            $ok = true;
        } catch (Throwable $e) {
            $ok = false;
        }
    }
    return $ok;
}

function inbox_unread_count(): int
{
    return inbox_ready() ? (int) scalar("SELECT COUNT(*) FROM threads WHERE unread > 0 AND status <> 'spam'") : 0;
}

function mail_domain(): string
{
    return substr(strrchr((string) config('mail')['from'], '@') ?: '@localhost', 1);
}

/** Message-ID de nos envois : contient le numéro de conversation, pour retrouver les réponses. */
function new_message_id(int $threadId): string
{
    return 'et-' . $threadId . '-' . bin2hex(random_bytes(8)) . '@' . mail_domain();
}

function thread_create(string $email, string $name, string $subject, ?int $orderId = null, ?string $lang = null): int
{
    $email = strtolower(trim($email));
    $userId = scalar('SELECT id FROM users WHERE email = ?', [$email]);
    if ($orderId === null) {
        $orderId = scalar("SELECT id FROM orders WHERE email = ? AND status <> 'annulee' ORDER BY id DESC LIMIT 1", [$email]);
    }
    $lang ??= $orderId ? scalar('SELECT lang FROM orders WHERE id = ?', [$orderId])
        : ($userId ? scalar('SELECT lang FROM users WHERE id = ?', [$userId]) : null);
    q('INSERT INTO threads (subject, contact_email, contact_name, order_id, user_id, status, unread, lang, last_message_at, created_at)
       VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)', [
        mb_substr($subject !== '' ? $subject : '(sans sujet)', 0, 255), mb_substr($email, 0, 190), mb_substr($name, 0, 190),
        $orderId ? (int) $orderId : null, $userId ? (int) $userId : null, 'open', $lang, now(), now(),
    ]);
    return (int) db()->lastInsertId();
}

/** Ajoute un message à une conversation et met à jour son état (non lu, dernière activité). */
function thread_add_message(int $threadId, array $m): int
{
    $in = $m['direction'] === 'in';
    q('INSERT INTO messages (thread_id, direction, kind, from_email, from_name, to_email, subject, body, body_full, message_id, in_reply_to, attachments, sent_by, status, created_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
        $threadId, $m['direction'], $m['kind'] ?? 'mail',
        mb_substr((string) ($m['from_email'] ?? ''), 0, 190), mb_substr((string) ($m['from_name'] ?? ''), 0, 190),
        mb_substr((string) ($m['to_email'] ?? ''), 0, 190), mb_substr((string) ($m['subject'] ?? ''), 0, 255),
        (string) ($m['body'] ?? ''), $m['body_full'] ?? null,
        isset($m['message_id']) ? mb_substr($m['message_id'], 0, 255) : null,
        isset($m['in_reply_to']) ? mb_substr($m['in_reply_to'], 0, 255) : null,
        !empty($m['attachments']) ? json_encode($m['attachments'], JSON_UNESCAPED_UNICODE) : null,
        $m['sent_by'] ?? null, $m['status'] ?? 'ok', $m['created_at'] ?? now(),
    ]);
    $id = (int) db()->lastInsertId();
    if ($in) {
        // Un nouveau message rouvre une conversation archivée
        q("UPDATE threads SET unread = unread + 1, last_message_at = ?, status = CASE WHEN status = 'spam' THEN 'spam' ELSE 'open' END WHERE id = ?", [now(), $threadId]);
    } else {
        q('UPDATE threads SET last_message_at = ? WHERE id = ?', [now(), $threadId]);
    }
    return $id;
}

/** Envoie un courriel dans une conversation (réponse admin, réponse auto, confirmation de commande). */
function thread_send(int $threadId, string $subject, string $body, string $kind = 'mail', ?int $sentBy = null): bool
{
    $t = row('SELECT * FROM threads WHERE id = ?', [$threadId]);
    if (!$t) {
        return false;
    }
    $refs = array_column(rows("SELECT message_id FROM messages WHERE thread_id = ? AND message_id IS NOT NULL AND message_id <> '' ORDER BY id DESC LIMIT 10", [$threadId]), 'message_id');
    $refs = array_reverse($refs);
    $mid = new_message_id($threadId);
    $opts = ['message_id' => $mid];
    if ($refs) {
        // On répond au dernier message du client (sinon à notre dernier envoi)
        $lastIn = scalar("SELECT message_id FROM messages WHERE thread_id = ? AND direction = 'in' AND message_id IS NOT NULL AND message_id <> '' ORDER BY id DESC LIMIT 1", [$threadId]);
        $opts['in_reply_to'] = '<' . ($lastIn ?: end($refs)) . '>';
        $opts['references'] = implode(' ', array_map(fn($r) => "<$r>", $refs));
    }
    if ($kind === 'auto') {
        $opts['auto'] = true;
    }
    $ok = send_mail($t['contact_email'], $subject, $body, null, $opts);
    thread_add_message($threadId, [
        'direction' => 'out', 'kind' => $kind, 'from_email' => config('mail')['from'], 'from_name' => config('mail')['from_name'] ?? 'EliteTap',
        'to_email' => $t['contact_email'], 'subject' => $subject, 'body' => $body, 'message_id' => $mid,
        'in_reply_to' => isset($opts['in_reply_to']) ? trim($opts['in_reply_to'], '<>') : null,
        'sent_by' => $sentBy, 'status' => $ok ? 'ok' : 'failed',
    ]);
    return $ok;
}

/** Conversation d'une commande (créée au besoin). */
function order_thread_id(array $o, bool $create = true): ?int
{
    if (!inbox_ready()) {
        return null;
    }
    $id = scalar('SELECT id FROM threads WHERE order_id = ? ORDER BY id LIMIT 1', [$o['id']]);
    if ($id) {
        return (int) $id;
    }
    return $create ? thread_create($o['email'], $o['name'], 'Commande ' . $o['reference'], (int) $o['id'], $o['lang'] ?? null) : null;
}

/** Retrouve la conversation d'un courriel reçu, sinon null. */
function inbox_find_thread(array $mail): ?int
{
    // 1. Réponse à l'un de nos envois : le numéro de conversation est dans notre Message-ID
    $ids = array_merge($mail['in_reply_to'], $mail['references']);
    $domain = preg_quote(mail_domain(), '/');
    foreach (array_reverse($ids) as $id) {
        if (preg_match('/^et-(\d+)-[a-f0-9]+@' . $domain . '$/i', $id, $m)
            && scalar('SELECT id FROM threads WHERE id = ?', [(int) $m[1]])) {
            return (int) $m[1];
        }
    }
    // 2. Message-ID connu (suite d'un échange)
    foreach (array_reverse($ids) as $id) {
        $t = scalar('SELECT thread_id FROM messages WHERE message_id = ? LIMIT 1', [mb_substr($id, 0, 255)]);
        if ($t) {
            return (int) $t;
        }
    }
    // 3. Référence de commande dans le sujet (ET-XXXXXX)
    if (preg_match('/\bET-([A-Z0-9]{6})\b/', $mail['subject'], $m)) {
        $o = row('SELECT * FROM orders WHERE reference = ? AND email = ?', ['ET-' . $m[1], $mail['from_email']]);
        if ($o) {
            return order_thread_id($o);
        }
    }
    // 4. Même expéditeur, même sujet, conversation récente
    $t = scalar("SELECT id FROM threads WHERE contact_email = ? AND status <> 'spam' AND last_message_at >= ? AND subject = ? ORDER BY id DESC LIMIT 1",
        [$mail['from_email'], date('Y-m-d H:i:s', time() - 30 * 86400), mb_substr(base_subject($mail['subject']), 0, 255)]);
    return $t ? (int) $t : null;
}

/** Sujet sans « Re: », « TR: », « Fwd: »… */
function base_subject(string $s): string
{
    $s = trim($s);
    while (preg_match('/^(re|tr|fw|fwd|réf|aw|sv)\s*(\[\d+\])?\s*:\s*/iu', $s, $m)) {
        $s = trim(mb_substr($s, mb_strlen($m[0])));
    }
    return $s;
}

/**
 * Traite un courriel brut reçu (appelé par bin/inbound-mail.php).
 * @return string résumé pour le journal (« stored #12 », « ignored: … »)
 */
function inbox_receive(string $raw): string
{
    if (strlen($raw) > INBOX_MAX_RAW) {
        $raw = substr($raw, 0, INBOX_MAX_RAW);
    }
    $mail = mime_parse_message($raw);
    if ($mail['from_email'] === '' || !valid_email($mail['from_email'])) {
        return 'ignored: no sender';
    }
    $own = strtolower((string) config('mail')['from']);
    if ($mail['from_email'] === $own || $mail['real_from'] === $own || !empty($mail['headers']['x-elitetap'])) {
        return 'ignored: own message';                // nos propres notifications envoyées à info@
    }
    // Doublon (le même courriel transmis deux fois)
    if ($mail['message_id'] && scalar('SELECT id FROM messages WHERE message_id = ? AND direction = ?', [mb_substr($mail['message_id'], 0, 255), 'in'])) {
        return 'ignored: duplicate';
    }

    $automated = mail_is_automated($mail);
    $threadId = inbox_find_thread($mail);
    $isNew = $threadId === null;
    if ($isNew) {
        $threadId = thread_create($mail['from_email'], $mail['from_name'], base_subject($mail['subject']));
        if ($automated) {
            q("UPDATE threads SET status = 'spam' WHERE id = ?", [$threadId]);  // messages de robots : rangés à part
        }
    }

    $attachments = inbox_store_attachments($mail['attachments']);
    $body = strip_quoted_reply($mail['text']);
    thread_add_message($threadId, [
        'direction' => 'in', 'kind' => 'mail', 'from_email' => $mail['from_email'], 'from_name' => $mail['from_name'],
        'to_email' => $mail['to'], 'subject' => $mail['subject'], 'body' => $body,
        'body_full' => $body !== trim($mail['text']) ? $mail['text'] : null,
        'message_id' => $mail['message_id'], 'in_reply_to' => $mail['in_reply_to'] ? end($mail['in_reply_to']) : null,
        'attachments' => $attachments,
    ]);

    if (!$automated) {
        inbox_maybe_autoreply($threadId, $mail);
        $notify = notify_email();
        if (strtolower($notify) !== $own && setting('inbox_notify', '1') === '1') {
            send_mail($notify, 'Nouveau message de ' . ($mail['from_name'] ?: $mail['from_email']) . ' — ' . base_subject($mail['subject']),
                "De : {$mail['from_name']} <{$mail['from_email']}>\nSujet : {$mail['subject']}\n\n"
                . mb_substr($body, 0, 1500) . "\n\nRépondre depuis l'admin : " . url("/admin/messages/$threadId") . "\n");
        }
    }
    return "stored #$threadId" . ($isNew ? ' (new)' : '');
}

/** Robots, listes de diffusion, retours d'erreur : pas de réponse automatique. */
function mail_is_automated(array $mail): bool
{
    $h = $mail['headers'];
    $auto = strtolower($h['auto-submitted'] ?? 'no');
    if ($auto !== '' && $auto !== 'no') {
        return true;
    }
    if (in_array(strtolower($h['precedence'] ?? ''), ['bulk', 'junk', 'list', 'auto_reply'], true)) {
        return true;
    }
    if (!empty($h['list-id']) || !empty($h['list-unsubscribe']) || !empty($h['x-autoreply']) || !empty($h['x-autorespond'])
        || isset($h['x-auto-response-suppress'])) {
        return true;
    }
    return (bool) preg_match('/^(mailer-daemon|postmaster|no-?reply|do-?not-?reply|bounce[s]?|notifications?)@/i', $mail['from_email']);
}

/** Une réponse automatique par expéditeur et par 24 h, si activée. */
function inbox_maybe_autoreply(int $threadId, array $mail): void
{
    if (setting('autoreply_enabled', '1') !== '1') {
        return;
    }
    $recent = scalar("SELECT COUNT(*) FROM messages WHERE direction = 'out' AND kind = 'auto' AND to_email = ? AND created_at >= ?",
        [$mail['from_email'], date('Y-m-d H:i:s', time() - 86400)]);
    if ($recent) {
        return;
    }
    // Pas de réponse auto si l'équipe a écrit dans cette conversation il y a moins de 2 h (échange en cours)
    $live = scalar("SELECT COUNT(*) FROM messages WHERE thread_id = ? AND direction = 'out' AND kind = 'mail' AND created_at >= ?",
        [$threadId, date('Y-m-d H:i:s', time() - 7200)]);
    if ($live) {
        return;
    }
    $t = row('SELECT * FROM threads WHERE id = ?', [$threadId]);
    $lang = $t['lang'] ?: guess_lang($mail['subject'] . ' ' . $mail['text']);
    $online = presence()['online'];
    $subject = 'Re: ' . base_subject($mail['subject'] ?: ($lang === 'en' ? 'Your message' : 'Votre message'));
    $first = trim(explode(' ', trim($mail['from_name']))[0] ?? '');
    thread_send($threadId, $subject, autoreply_text($online, $lang, $first), 'auto');
}

/** Devine fr / en d'après quelques mots courants. */
function guess_lang(string $text): string
{
    $t = ' ' . mb_strtolower(mb_substr($text, 0, 3000)) . ' ';
    $en = preg_match_all('/\b(the|and|you|your|hello|hi|please|thanks|thank|would|card|cards|price|order)\b/u', $t);
    $fr = preg_match_all('/\b(le|la|les|et|vous|votre|bonjour|merci|pour|carte|cartes|prix|commande|est|je)\b/u', $t);
    return $en > $fr ? 'en' : 'fr';
}

/** Coupe l'historique cité (« Le … a écrit : », lignes « > »…) pour n'afficher que le nouveau texte. */
function strip_quoted_reply(string $text): string
{
    $lines = preg_split("/\r\n|\n|\r/", $text);
    $out = [];
    foreach ($lines as $i => $line) {
        $l = trim($line);
        $next = trim($lines[$i + 1] ?? '');
        if (preg_match('/^(-{2,}\s*(original message|message d\'origine|message original)|_{5,}$)/i', $l)
            || preg_match('/^(le|on)\s.+(a écrit|wrote)\s*:$/iu', $l)
            || (preg_match('/^(le|on)\s.+/iu', $l) && preg_match('/(a écrit|wrote)\s*:$/iu', $next) && mb_strlen($l . $next) < 250)
            || preg_match('/^(de|from)\s*:\s.+@/iu', $l) && preg_match('/^(envoyé|sent|date|à|to)\s*:/iu', $next)) {
            break;
        }
        $out[] = $line;
    }
    // Lignes citées « > » restées à la fin
    while ($out && (trim(end($out)) === '' || str_starts_with(ltrim(end($out)), '>'))) {
        array_pop($out);
    }
    $r = trim(implode("\n", $out));
    return $r !== '' ? $r : trim($text);
}

function inbox_store_attachments(array $parts): array
{
    $saved = [];
    foreach (array_slice($parts, 0, 10) as $a) {
        if ($a['data'] === '' || strlen($a['data']) > INBOX_MAX_ATTACHMENT) {
            continue;
        }
        $dir = INBOX_DIR . '/' . date('Y/m');
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $ext = strtolower(pathinfo($a['name'], PATHINFO_EXTENSION));
        $ext = preg_match('/^[a-z0-9]{1,8}$/', $ext) ? $ext : 'bin';
        $file = bin2hex(random_bytes(16)) . '.' . $ext;
        if (@file_put_contents("$dir/$file", $a['data']) !== false) {
            $saved[] = ['name' => mb_substr($a['name'], 0, 200), 'path' => date('Y/m') . '/' . $file, 'size' => strlen($a['data']), 'type' => $a['type']];
        }
    }
    return $saved;
}

/* ------------------------------------------------------------------ */
/* Lecture MIME (sans extension imap ni mailparse)                     */
/* ------------------------------------------------------------------ */

/** @return array{0: array<string,string>, 1: string} en-têtes (clés en minuscules) et corps */
function mime_split(string $raw): array
{
    $raw = str_replace("\r\n", "\n", $raw);
    $pos = strpos($raw, "\n\n");
    $head = $pos === false ? $raw : substr($raw, 0, $pos);
    $body = $pos === false ? '' : substr($raw, $pos + 2);
    $head = preg_replace("/\n[ \t]+/", ' ', $head);              // en-têtes repliés
    $headers = [];
    foreach (explode("\n", $head) as $line) {
        if (preg_match('/^([A-Za-z0-9-]+):\s*(.*)$/', $line, $m)) {
            $k = strtolower($m[1]);
            if ($k === 'from' && str_starts_with($line, 'From ')) {
                continue;
            }
            $headers[$k] = isset($headers[$k]) && in_array($k, ['received'], true) ? $headers[$k] . "\n" . $m[2] : ($headers[$k] ?? $m[2]);
        }
    }
    return [$headers, $body];
}

function mime_decode_header(string $v): string
{
    if (!str_contains($v, '=?')) {
        return trim(to_utf8($v, 'UTF-8'));
    }
    $v = preg_replace('/\?=\s+=\?/', '?==?', $v);                  // mots encodés consécutifs
    $out = preg_replace_callback('/=\?([^?]+)\?([bBqQ])\?([^?]*)\?=/', function ($m) {
        $data = strtoupper($m[2]) === 'B' ? base64_decode($m[3]) : quoted_printable_decode(str_replace('_', ' ', $m[3]));
        return to_utf8((string) $data, $m[1]);
    }, $v);
    return trim((string) $out);
}

function to_utf8(string $s, string $charset): string
{
    $charset = strtoupper(trim($charset, " \"'"));
    if (str_contains($charset, '*')) {
        $charset = strstr($charset, '*', true);
    }
    if ($charset === '' || $charset === 'UTF-8' || $charset === 'US-ASCII') {
        return mb_check_encoding($s, 'UTF-8') ? $s : mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
    }
    if (in_array($charset, ['ISO-8859-1', 'LATIN1'], true)) {
        $charset = 'Windows-1252';                                  // sur-ensemble courant
    }
    try {
        $r = @mb_convert_encoding($s, 'UTF-8', $charset);
        return is_string($r) ? $r : $s;
    } catch (Throwable $e) {
        return mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
    }
}

/** Paramètre d'un en-tête : boundary, charset, name, filename (y compris RFC 2231). */
function mime_param(string $header, string $name): ?string
{
    if (preg_match('/' . $name . '\*=([^\';]*)\'[^\']*\'([^;]+)/i', $header, $m)) {
        return to_utf8(rawurldecode(trim($m[2], '"')), $m[1] ?: 'UTF-8');
    }
    if (preg_match_all('/' . $name . '\*\d+\*?="?([^";]*)"?/i', $header, $m)) {
        return to_utf8(rawurldecode(implode('', $m[1])), 'UTF-8');
    }
    if (preg_match('/' . $name . '\s*=\s*"([^"]*)"/i', $header, $m) || preg_match('/' . $name . '\s*=\s*([^;\s]+)/i', $header, $m)) {
        return mime_decode_header($m[1]);
    }
    return null;
}

function mime_decode_body(string $body, string $encoding): string
{
    $encoding = strtolower(trim($encoding));
    if ($encoding === 'base64') {
        return (string) base64_decode(preg_replace('/\s+/', '', $body));
    }
    if ($encoding === 'quoted-printable') {
        return quoted_printable_decode($body);
    }
    return $body;
}

/** Parcourt les parties : premier texte brut, premier HTML, pièces jointes. */
function mime_walk(array $headers, string $body, array &$acc, int $depth = 0): void
{
    $ctype = $headers['content-type'] ?? 'text/plain';
    $type = strtolower(trim(explode(';', $ctype)[0]));
    $disp = strtolower($headers['content-disposition'] ?? '');
    if ($depth < 8 && str_starts_with($type, 'multipart/') && ($b = mime_param($ctype, 'boundary'))) {
        $chunks = explode('--' . $b, $body);
        array_shift($chunks);                                        // préambule
        foreach ($chunks as $chunk) {
            if (str_starts_with($chunk, '--')) {
                break;                                               // fin
            }
            [$h, $bd] = mime_split(ltrim($chunk, "\r\n"));
            mime_walk($h, $bd, $acc, $depth + 1);
        }
        return;
    }
    if ($type === 'message/rfc822' && $depth < 8) {
        [$h, $bd] = mime_split($body);
        $acc['attachments'][] = ['name' => (mime_decode_header($h['subject'] ?? 'message') ?: 'message') . '.eml', 'type' => 'message/rfc822', 'data' => $body];
        return;
    }
    $data = mime_decode_body($body, $headers['content-transfer-encoding'] ?? '7bit');
    $filename = mime_param($disp, 'filename') ?? mime_param($ctype, 'name');
    $isAttachment = str_starts_with($disp, 'attachment') || ($filename && !in_array($type, ['text/plain', 'text/html'], true));
    if (!$isAttachment && $type === 'text/plain' && $acc['text'] === null) {
        $acc['text'] = to_utf8($data, mime_param($ctype, 'charset') ?? 'UTF-8');
    } elseif (!$isAttachment && $type === 'text/html' && $acc['html'] === null) {
        $acc['html'] = to_utf8($data, mime_param($ctype, 'charset') ?? 'UTF-8');
    } elseif ($filename || $isAttachment) {
        $acc['attachments'][] = ['name' => $filename ?: 'piece-jointe', 'type' => $type, 'data' => $data];
    }
}

function html_to_text(string $html): string
{
    $html = preg_replace('#<(script|style|head)[^>]*>.*?</\1>#is', '', $html);
    $html = preg_replace('#<blockquote[^>]*>.*?</blockquote>#is', "\n", $html);   // historique cité
    $html = preg_replace('#<br\s*/?>#i', "\n", $html);
    $html = preg_replace('#</(p|div|tr|li|h[1-6])>#i', "\n", $html);
    $html = preg_replace('#<li[^>]*>#i', '• ', $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/[ \t\x{00A0}]+/u", ' ', $text);
    return trim(preg_replace("/\n\s*\n\s*\n+/", "\n\n", $text));
}

/** Adresse et nom depuis « Nom <adresse> ». */
function parse_address(string $v): array
{
    $v = mime_decode_header($v);
    if (preg_match('/^(.*)<([^>]+)>/', $v, $m)) {
        return [strtolower(trim($m[2])), trim(trim($m[1]), "\" '")];
    }
    return [strtolower(trim($v, " <>\"'")), ''];
}

function parse_msg_ids(string $v): array
{
    preg_match_all('/<([^<>\s]+)>/', $v, $m);
    return $m[1];
}

function mime_parse_message(string $raw): array
{
    [$headers, $body] = mime_split($raw);
    $acc = ['text' => null, 'html' => null, 'attachments' => []];
    mime_walk($headers, $body, $acc);
    $from = $headers['reply-to'] ?? null;                           // on répond à l'adresse demandée
    [$fromEmail, $fromName] = parse_address($headers['from'] ?? '');
    if ($from) {
        [$replyEmail] = parse_address($from);
        if (valid_email($replyEmail) && !str_contains($from, ',')) {
            $fromEmail = $replyEmail;
        }
    }
    $text = $acc['text'] ?? ($acc['html'] !== null ? html_to_text($acc['html']) : '');
    $text = trim(str_replace("\r\n", "\n", $text));
    return [
        'headers'     => $headers,
        'real_from'   => parse_address($headers['from'] ?? '')[0],
        'from_email'  => $fromEmail,
        'from_name'   => $fromName,
        'to'          => parse_address($headers['to'] ?? '')[0],
        'subject'     => mime_decode_header($headers['subject'] ?? ''),
        'message_id'  => parse_msg_ids($headers['message-id'] ?? '')[0] ?? null,
        'in_reply_to' => parse_msg_ids($headers['in-reply-to'] ?? ''),
        'references'  => parse_msg_ids($headers['references'] ?? ''),
        'text'        => mb_substr($text, 0, 60000),
        'attachments' => $acc['attachments'],
    ];
}
