<?php
declare(strict_types=1);

/*
 * Prospects (Loi 25) :
 *  - le visiteur coche un consentement explicite ; le texte exact accepté et l'heure sont conservés ;
 *  - seules les coordonnées saisies sont gardées (aucune IP) ;
 *  - le titulaire du profil peut les supprimer ; conservation limitée ('lead_retention_days', 730 j par défaut).
 */

function lead_consent_text(array $p): string
{
    $name = full_name($p) ?: $p['company'];
    return t('J\'accepte que {nom} reçoive ces coordonnées pour me recontacter. Elles sont conservées par EliteTap pour son compte et peuvent être supprimées sur demande.', ['nom' => $name]);
}

/** Envoi du formulaire « Laissez vos coordonnées » depuis un profil public. */
function lead_submit(string $slug): void
{
    $p = row('SELECT * FROM profiles WHERE slug = ?', [$slug]);
    if (!$p || !$p['is_public'] || !$p['lead_form'] || !profile_can($p, 'leads') || !owner_active($p)) {
        abort(404);
    }
    $back = '/u/' . $p['slug'];

    if (input('site_web') !== '') {              // pot de miel anti-robots
        redirect($back . '?merci=1#contact');
    }
    $d = [
        'name'    => input('name', 120),
        'email'   => strtolower(input('email', 190)),
        'phone'   => input('phone', 40),
        'company' => input('company', 120),
        'message' => input('message', 1000),
    ];
    $errors = [];
    if ($d['name'] === '') {
        $errors[] = t('Indiquez votre nom.');
    }
    if ($d['email'] === '' && $d['phone'] === '') {
        $errors[] = t('Indiquez au moins un courriel ou un téléphone.');
    }
    if ($d['email'] !== '' && !valid_email($d['email'])) {
        $errors[] = t('Indiquez un courriel valide.');
    }
    if (!isset($_POST['consent'])) {
        $errors[] = t('Cochez la case de consentement pour envoyer vos coordonnées.');
    }
    $key = throttle_key('lead');
    if (too_many_attempts($key, 5, 60)) {
        $errors[] = t('Trop d\'envois. Réessayez plus tard.');
    }
    if ($errors) {
        foreach ($errors as $e) flash('error', $e);
        with_old($d);
        redirect($back . '#contact');
    }
    hit_attempt($key);

    $tap = $_SESSION['tap_card'] ?? null;
    $cardId = ($tap && (int) $tap['profile'] === (int) $p['id']) ? (int) $tap['card'] : null;

    q('INSERT INTO leads (profile_id, card_id, name, email, phone, company, message, consent_text, consent_at, created_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
        $p['id'], $cardId, $d['name'], $d['email'], $d['phone'], $d['company'], $d['message'],
        mb_substr(lead_consent_text($p), 0, 500), now(), now(),
    ]);
    if (!is_bot()) {
        q('INSERT INTO events (profile_id, card_id, type, visitor, created_at) VALUES (?, ?, ?, ?, ?)',
            [$p['id'], $cardId, 'lead', visitor_id(), now()]);
    }

    // Alerte instantanée au titulaire du profil, dans sa langue
    if ($p['lead_alerts']) {
        $owner = row('SELECT email, name, lang FROM users WHERE id = ?', [$p['user_id']]);
        if ($owner) {
            in_lang($owner['lang'], fn() => send_mail($owner['email'],
                t('Nouveau prospect : {nom}', ['nom' => $d['name']]),
                t("Bonjour {titulaire},\n\nUne personne vient de laisser ses coordonnées sur votre profil EliteTap :\n\n{details}\n\nRetrouvez tous vos prospects ici : {lien}\n\nL'équipe EliteTap\n", [
                    'titulaire' => $owner['name'],
                    'details'   => implode("\n", array_filter([
                        $d['name'] . ($d['company'] !== '' ? ' (' . $d['company'] . ')' : ''),
                        $d['email'], $d['phone'],
                        $d['message'] !== '' ? "\n« " . $d['message'] . ' »' : '',
                    ])),
                    'lien' => url('/compte/prospects'),
                ]),
                $d['email'] !== '' ? $d['email'] : null));
        }
    }
    redirect($back . '?merci=1#contact');
}

function owner_active(array $p): bool
{
    return (bool) scalar('SELECT is_active FROM users WHERE id = ?', [$p['user_id']]);
}

function purge_old_leads(int $profileId): void
{
    $days = (int) config('lead_retention_days', 730);
    q('DELETE FROM leads WHERE profile_id = ? AND created_at < ?', [$profileId, date('Y-m-d H:i:s', time() - $days * 86400)]);
}

function account_leads(): void
{
    $user = require_feature('leads');
    $p = my_profile($user);
    purge_old_leads((int) $p['id']);
    $leads = rows('SELECT l.*, c.material, c.code FROM leads l LEFT JOIN cards c ON c.id = l.card_id
                   WHERE l.profile_id = ? ORDER BY l.created_at DESC LIMIT 500', [$p['id']]);
    // Ouvrir la boîte marque les prospects comme lus (le badge « Nouveau » reste affiché cette fois-ci)
    q('UPDATE leads SET read_at = ? WHERE profile_id = ? AND read_at IS NULL', [now(), $p['id']]);
    view('account/leads', ['title' => t('Prospects | EliteTap'), 'nav' => 'prospects', 'user' => $user, 'p' => $p,
        'leads' => $leads, 'retention' => (int) config('lead_retention_days', 730)], 'app');
}

function account_leads_delete(): void
{
    $user = require_feature('leads');
    $p = my_profile($user);
    if (isset($_POST['all'])) {
        q('DELETE FROM leads WHERE profile_id = ?', [$p['id']]);
        flash('success', t('Tous les prospects ont été supprimés.'));
    } else {
        q('DELETE FROM leads WHERE id = ? AND profile_id = ?', [(int) ($_POST['id'] ?? 0), $p['id']]);
        flash('success', t('Prospect supprimé.'));
    }
    redirect('/compte/prospects');
}

function account_leads_export(): void
{
    $user = require_feature('leads');
    $p = my_profile($user);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="prospects-' . $p['slug'] . '-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, [t('Date'), t('Contact'), t('Entreprise'), t('Courriel'), t('Téléphone'), t('Message'), t('Carte d\'origine'), t('Consentement')]);
    foreach (rows('SELECT l.*, c.material FROM leads l LEFT JOIN cards c ON c.id = l.card_id WHERE l.profile_id = ? ORDER BY l.created_at DESC', [$p['id']]) as $l) {
        fputcsv($out, [$l['created_at'], $l['name'], $l['company'], $l['email'], $l['phone'], $l['message'],
            $l['material'] ? t($l['material']) : t('Lien ou QR'), $l['consent_at']]);
    }
    fclose($out);
}
