<?php
declare(strict_types=1);

const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview|curl|wget|python|headless/i';

function is_bot(): bool
{
    return (bool) preg_match(BOT_PATTERN, $_SERVER['HTTP_USER_AGENT'] ?? 'bot');
}

/** Identifiant visiteur anonyme, change chaque jour (aucune IP stockée). */
function visitor_id(): string
{
    $raw = ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . date('Y-m-d');
    return substr(hash_hmac('sha256', $raw, (string) config('secret')), 0, 16);
}

function track(int $profileId, string $type, ?int $cardId = null, ?int $linkId = null): void
{
    if (is_bot()) {
        return;
    }
    q('INSERT INTO events (profile_id, card_id, link_id, type, visitor, created_at) VALUES (?, ?, ?, ?, ?, ?)',
        [$profileId, $cardId, $linkId, $type, visitor_id(), now()]);
}

/** Totaux par type sur N jours. */
function stats_totals(int $profileId, int $days): array
{
    $since = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
    $out = ['view' => 0, 'tap' => 0, 'vcard' => 0, 'link' => 0, 'lead' => 0, 'visitors' => 0];
    foreach (rows('SELECT type, COUNT(*) n FROM events WHERE profile_id = ? AND created_at >= ? GROUP BY type', [$profileId, $since]) as $r) {
        $out[$r['type']] = (int) $r['n'];
    }
    $out['visitors'] = (int) scalar('SELECT COUNT(DISTINCT visitor) FROM events WHERE profile_id = ? AND created_at >= ?', [$profileId, $since]);
    return $out;
}

/** Ouvertures (vue + tap) par jour, jours vides inclus. */
function stats_daily(int $profileId, int $days): array
{
    $since = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
    $map = [];
    foreach (rows("SELECT SUBSTR(created_at, 1, 10) d, COUNT(*) n FROM events
                   WHERE profile_id = ? AND created_at >= ? AND type IN ('view','tap')
                   GROUP BY SUBSTR(created_at, 1, 10)", [$profileId, $since]) as $r) {
        $map[$r['d']] = (int) $r['n'];
    }
    $out = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $out[$d] = $map[$d] ?? 0;
    }
    return $out;
}
