<?php
declare(strict_types=1);

function card_tap(string $code): void
{
    $card = row('SELECT * FROM cards WHERE code = ?', [$code]);
    if (!$card) {
        abort(404);
    }

    if ($card['status'] === 'active' && $card['profile_id']) {
        $profile = row('SELECT id, slug FROM profiles WHERE id = ?', [$card['profile_id']]);
        if ($profile) {
            track((int) $profile['id'], 'tap', (int) $card['id']);
            // Marqueur pour ne pas compter la vue qui suit en double
            $_SESSION['from_tap'] = (int) $profile['id'];
            redirect('/u/' . $profile['slug']);
        }
    }

    // Carte en stock (pas encore activée) ou désactivée
    $user = current_user();
    if (!$user && $card['status'] === 'stock') {
        // Après connexion, le client revient ici pour activer sa carte
        $_SESSION['intended'] = '/t/' . $card['code'];
    }
    view('profile/card_inactive', [
        'title'    => 'Carte EliteTap',
        'card'     => $card,
        'user'     => $user,
        'canClaim' => $card['status'] === 'stock' && $user !== null,
    ], 'site');
}

/** Le client connecté associe une carte neuve à son profil (auto-activation). */
function card_activate(string $code): void
{
    $user = require_login();
    $card = row('SELECT * FROM cards WHERE code = ?', [$code]);
    if (!$card || $card['status'] !== 'stock') {
        flash('error', t('Cette carte ne peut pas être activée.'));
        redirect('/compte/cartes');
    }
    $profileId = scalar('SELECT id FROM profiles WHERE user_id = ?', [$user['id']]);
    if (!$profileId) {
        abort(403);
    }
    q("UPDATE cards SET profile_id = ?, status = 'active', activated_at = ? WHERE id = ? AND status = 'stock'",
        [$profileId, now(), $card['id']]);
    flash('success', t('Carte activée. Elle ouvre maintenant votre profil.'));
    redirect('/compte/cartes');
}

function load_public_profile(string $slug): array
{
    $p = row('SELECT * FROM profiles WHERE slug = ?', [$slug]);
    if (!$p) {
        abort(404);
    }
    $owner = current_user();
    $isOwner = $owner && (int) $owner['id'] === (int) $p['user_id'];
    if (!$p['is_public'] && !$isOwner && ($owner['role'] ?? '') !== 'admin') {
        abort(404);
    }
    return [$p, $isOwner];
}

function profile_show(string $slug): void
{
    [$p, $isOwner] = load_public_profile($slug);

    $fromTap = ($_SESSION['from_tap'] ?? null) === (int) $p['id'];
    unset($_SESSION['from_tap']);
    if (!$fromTap && !$isOwner) {
        track((int) $p['id'], 'view');
    }

    $links = rows('SELECT id, label, url FROM links WHERE profile_id = ? AND is_active = 1 ORDER BY position, id', [$p['id']]);
    view('profile/show', ['p' => $p, 'links' => $links, 'isOwner' => $isOwner]);
}

function vcard_escape(string $s): string
{
    return str_replace(["\\", "\n", ',', ';'], ["\\\\", '\\n', '\\,', '\\;'], $s);
}

function profile_vcard(string $slug): void
{
    [$p, $isOwner] = load_public_profile($slug);
    if (!$isOwner) {
        track((int) $p['id'], 'vcard');
    }

    $lines = [
        'BEGIN:VCARD',
        'VERSION:3.0',
        'N:' . vcard_escape($p['last_name']) . ';' . vcard_escape($p['first_name']) . ';;;',
        'FN:' . vcard_escape(full_name($p) ?: $p['company']),
    ];
    if ($p['company'] !== '') $lines[] = 'ORG:' . vcard_escape($p['company']);
    if ($p['title'] !== '')   $lines[] = 'TITLE:' . vcard_escape($p['title']);
    if ($p['phone'] !== '')   $lines[] = 'TEL;TYPE=CELL,VOICE:' . vcard_escape($p['phone']);
    if ($p['email'] !== '')   $lines[] = 'EMAIL;TYPE=INTERNET:' . vcard_escape($p['email']);
    if ($p['website'] !== '') $lines[] = 'URL:' . vcard_escape($p['website']);
    if ($p['address'] !== '') $lines[] = 'ADR;TYPE=WORK:;;' . vcard_escape($p['address']) . ';;;;';
    if ($p['bio'] !== '')     $lines[] = 'NOTE:' . vcard_escape($p['bio']);
    $lines[] = 'URL;TYPE=EliteTap:' . url('/u/' . $p['slug']);

    if ($p['avatar_path']) {
        $file = APP_ROOT . '/public/uploads/avatars/' . basename($p['avatar_path']);
        if (is_file($file) && filesize($file) < 300 * 1024) {
            $lines[] = 'PHOTO;ENCODING=b;TYPE=JPEG:' . base64_encode((string) file_get_contents($file));
        }
    }
    $lines[] = 'END:VCARD';

    $filename = (slugify(full_name($p)) ?: 'contact') . '.vcf';
    header('Content-Type: text/vcard; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo implode("\r\n", $lines) . "\r\n";
}

function link_go(string $id): void
{
    $l = row('SELECT l.id, l.url, l.profile_id, p.user_id FROM links l JOIN profiles p ON p.id = l.profile_id
              WHERE l.id = ? AND l.is_active = 1', [(int) $id]);
    if (!$l) {
        abort(404);
    }
    $u = current_user();
    if (!$u || (int) $u['id'] !== (int) $l['user_id']) {
        track((int) $l['profile_id'], 'link', null, (int) $l['id']);
    }
    // L'URL a été validée (http/https) à l'enregistrement
    redirect($l['url']);
}
