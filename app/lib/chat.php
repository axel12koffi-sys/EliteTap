<?php
declare(strict_types=1);

/*
 * Clavardage du site (visiteurs ↔ service client) et infolettre.
 * Sans serveur temps réel : le navigateur interroge le serveur toutes les quelques secondes (compatible cPanel).
 * Loi 25 : aucune adresse IP conservée ; conversations supprimées après CHAT_RETENTION_DAYS jours.
 */

const CHAT_RETENTION_DAYS = 365;
const CHAT_NUDGE_SECONDS = 120;          // sans réponse d'un agent après 2 min : proposer de laisser son courriel
const CHAT_MAX_BODY = 2000;

function chat_ready(): bool
{
    static $ok = null;
    if ($ok === null) {
        try {
            scalar('SELECT COUNT(*) FROM chat_sessions WHERE 1 = 0');
            $ok = true;
        } catch (Throwable $e) {
            $ok = false;
        }
    }
    return $ok;
}

function chat_session_by_token(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
        return null;
    }
    return row('SELECT * FROM chat_sessions WHERE token_hash = ?', [hash('sha256', $token)]);
}

function chat_add(array $s, string $sender, string $body, ?int $userId = null): int
{
    q('INSERT INTO chat_messages (session_id, sender, user_id, body, created_at) VALUES (?, ?, ?, ?, ?)',
        [$s['id'], $sender, $userId, mb_substr($body, 0, CHAT_MAX_BODY), now()]);
    $id = (int) db()->lastInsertId();
    if ($sender === 'agent') {
        q("UPDATE chat_sessions SET last_message_at = ?, status = CASE WHEN status = 'waiting' THEN 'active' ELSE status END,
             assigned_to = COALESCE(assigned_to, ?), first_reply_at = COALESCE(first_reply_at, ?) WHERE id = ?",
            [now(), $userId, now(), $s['id']]);
    } elseif ($sender === 'visitor') {
        q('UPDATE chat_sessions SET last_message_at = ?, last_visitor_at = ? WHERE id = ?', [now(), now(), $s['id']]);
    }
    return $id;
}

function chat_messages_after(int $sessionId, int $after): array
{
    return rows('SELECT m.*, u.name agent_name FROM chat_messages m LEFT JOIN users u ON u.id = m.user_id
                 WHERE m.session_id = ? AND m.id > ? ORDER BY m.id LIMIT 300', [$sessionId, $after]);
}

/** Message tel que le visiteur le voit (prénom de l'agent seulement). */
function chat_public_message(array $m): array
{
    $first = $m['agent_name'] ? explode(' ', trim($m['agent_name']))[0] : 'EliteTap';
    return ['id' => (int) $m['id'], 'from' => $m['sender'], 'name' => $m['sender'] === 'agent' ? $first : null,
        'body' => $m['body'], 'time' => date('H:i', strtotime($m['created_at']))];
}

function chat_waiting_count(): int
{
    return chat_ready() ? (int) scalar("SELECT COUNT(*) FROM chat_sessions WHERE status = 'waiting'") : 0;
}

/** Conversations qui attendent une réponse : en attente, ou dernier message du visiteur non lu par l'agent. */
function chat_attention_count(): int
{
    if (!chat_ready()) {
        return 0;
    }
    return (int) scalar("SELECT COUNT(*) FROM chat_sessions s WHERE s.status <> 'closed' AND EXISTS
        (SELECT 1 FROM chat_messages m WHERE m.session_id = s.id AND m.sender = 'visitor' AND m.id > s.agent_last_read)");
}

/** Aucun agent n'a répondu depuis 2 min : un message propose de laisser son courriel (une seule fois). */
function chat_maybe_nudge(array $s): void
{
    if ($s['status'] !== 'waiting' || strtotime($s['created_at']) > time() - CHAT_NUDGE_SECONDS) {
        return;
    }
    if ((int) scalar("SELECT COUNT(*) FROM chat_messages WHERE session_id = ? AND sender <> 'visitor'", [$s['id']])) {
        return;
    }
    $text = in_lang($s['lang'], fn() => $s['visitor_email']
        ? t('Nos agents sont occupés. Restez en ligne, ou fermez la discussion : nous vous répondrons par courriel à {email}.', ['email' => $s['visitor_email']])
        : t('Nos agents sont occupés. Laissez votre courriel ci-dessous et nous vous répondrons dès que possible.'));
    chat_add($s, 'system', $text);
}

function chat_transcript(array $s): string
{
    $lines = [];
    foreach (chat_messages_after((int) $s['id'], 0) as $m) {
        $who = match ($m['sender']) {
            'visitor' => $s['visitor_name'] ?: 'Visiteur',
            'agent'   => ($m['agent_name'] ? explode(' ', trim($m['agent_name']))[0] : 'EliteTap') . ' (EliteTap)',
            default   => 'EliteTap',
        };
        $lines[] = '[' . date('H:i', strtotime($m['created_at'])) . "] $who : " . $m['body'];
    }
    return implode("\n", $lines);
}

/**
 * Ferme une discussion. Si le visiteur a laissé son courriel, la transcription rejoint ses conversations
 * (Admin › Messages) ; sans réponse d'un agent, elle y arrive non lue pour une réponse par courriel.
 */
function chat_close(array $s, string $by, ?int $userId = null): void
{
    if ($s['status'] === 'closed') {
        return;
    }
    q("UPDATE chat_sessions SET status = 'closed', closed_at = ?, closed_by = ? WHERE id = ?", [now(), $by, $s['id']]);
    chat_add($s, 'system', in_lang($s['lang'], fn() => $by === 'visitor' ? t('Vous avez fermé la discussion.') : t('La discussion est terminée. Merci !')), $userId);
    if (!$s['visitor_email'] || !inbox_ready()) {
        return;
    }
    $answered = (bool) scalar("SELECT COUNT(*) FROM chat_messages WHERE session_id = ? AND sender = 'agent'", [$s['id']]);
    $hasVisitor = (bool) scalar("SELECT COUNT(*) FROM chat_messages WHERE session_id = ? AND sender = 'visitor'", [$s['id']]);
    if (!$hasVisitor) {
        return;
    }
    $tid = $s['thread_id'] ?: thread_create($s['visitor_email'], $s['visitor_name'], 'Clavardage du ' . date('d/m/Y'), null, $s['lang']);
    thread_add_message((int) $tid, [
        'direction' => 'in', 'kind' => 'chat', 'from_email' => $s['visitor_email'], 'from_name' => $s['visitor_name'],
        'subject' => 'Clavardage', 'body' => ($answered ? "Discussion terminée.\n\n" : "Discussion sans réponse d'un agent : à rappeler par courriel.\n\n") . chat_transcript($s),
    ]);
    if ($answered) {
        q('UPDATE threads SET unread = 0 WHERE id = ?', [$tid]);
    }
    q('UPDATE chat_sessions SET thread_id = ? WHERE id = ?', [$tid, $s['id']]);
}

function chat_purge(): void
{
    if (random_int(1, 200) !== 1) {
        return;
    }
    $old = date('Y-m-d H:i:s', time() - CHAT_RETENTION_DAYS * 86400);
    q('DELETE FROM chat_messages WHERE session_id IN (SELECT id FROM chat_sessions WHERE last_message_at < ?)', [$old]);
    q('DELETE FROM chat_sessions WHERE last_message_at < ?', [$old]);
    // Discussions laissées ouvertes depuis plus de 12 h : fermées
    foreach (rows("SELECT * FROM chat_sessions WHERE status <> 'closed' AND last_message_at < ?", [date('Y-m-d H:i:s', time() - 43200)]) as $s) {
        chat_close($s, 'system');
    }
}

/** Textes du module de clavardage, dans la langue du visiteur. */
function chat_widget_strings(): array
{
    $p = presence();
    return [
        'title'       => t('Discutez avec nous'),
        'online'      => t('En ligne · réponse en quelques minutes'),
        'away'        => t('Absents pour le moment · réponse {retour}', ['retour' => format_back($p['back'], lang())]),
        'intro'       => t('Bonjour ! Une question sur nos cartes NFC ? Écrivez-nous.'),
        'introAway'   => t('Laissez-nous un message : nous vous répondrons par courriel.'),
        'name'        => t('Votre nom'),
        'email'       => t('Votre courriel'),
        'emailOpt'    => t('Votre courriel (facultatif, pour vous répondre si vous partez)'),
        'message'     => t('Votre message'),
        'start'       => t('Démarrer la discussion'),
        'sendAway'    => t('Envoyer le message'),
        'send'        => t('Envoyer'),
        'placeholder' => t('Écrivez votre message…'),
        'close'       => t('Fermer la discussion'),
        'minimize'    => t('Réduire'),
        'open'        => t('Ouvrir le clavardage'),
        'saveEmail'   => t('Enregistrer'),
        'emailSaved'  => t('Merci, nous vous répondrons à cette adresse.'),
        'ended'       => t('Discussion terminée'),
        'helpful'     => t('Cette discussion vous a-t-elle aidé ?'),
        'yes'         => t('Oui'),
        'no'          => t('Non'),
        'thanksRate'  => t('Merci pour votre avis.'),
        'transcript'  => t('Recevoir la transcription par courriel'),
        'newsTitle'   => t('Restez informé'),
        'newsText'    => t('Recevez nos nouveautés et offres EliteTap, environ une fois par mois.'),
        'newsConsent' => newsletter_consent_text(lang()),
        'subscribe'   => t('M\'abonner'),
        'newsLink'    => t('S\'abonner à l\'infolettre'),
        'newsOk'      => t('Presque fini : confirmez votre inscription avec le lien reçu par courriel.'),
        'newChat'     => t('Nouvelle discussion'),
        'sentAway'    => t('Message envoyé. Nous vous répondrons par courriel.'),
        'error'       => t('Le message n\'est pas parti. Réessayez.'),
        'privacy'     => t('Vos messages sont conservés 12 mois au plus.'),
        'privacyLink' => t('Confidentialité'),
        'agentJoined' => t('vous répond'),
        'you'         => t('Vous'),
    ];
}

/* ------------------------------------------------------------------ */
/* Infolettre                                                          */
/* ------------------------------------------------------------------ */

function newsletter_ready(): bool
{
    static $ok = null;
    if ($ok === null) {
        try {
            scalar('SELECT COUNT(*) FROM newsletter_subscribers WHERE 1 = 0');
            $ok = true;
        } catch (Throwable $e) {
            $ok = false;
        }
    }
    return $ok;
}

function newsletter_consent_text(string $lang): string
{
    return $lang === 'en'
        ? 'I agree to receive the EliteTap newsletter by email. I can unsubscribe at any time with the link in each email.'
        : 'J\'accepte de recevoir l\'infolettre d\'EliteTap par courriel. Je peux me désabonner en tout temps avec le lien présent dans chaque envoi.';
}

/**
 * Inscription avec double confirmation (le consentement est noté avec son texte et sa date).
 * @return string 'pending' (courriel de confirmation envoyé) | 'already' (déjà inscrit) | 'invalid'
 */
function newsletter_subscribe(string $email, string $name, string $lang, string $source): string
{
    $email = strtolower(trim($email));
    if (!valid_email($email) || !newsletter_ready()) {
        return 'invalid';
    }
    $lang = $lang === 'en' ? 'en' : 'fr';
    $sub = row('SELECT * FROM newsletter_subscribers WHERE email = ?', [$email]);
    if ($sub && $sub['status'] === 'confirmed') {
        return 'already';
    }
    $confirm = bin2hex(random_bytes(16));
    if ($sub) {
        q("UPDATE newsletter_subscribers SET status = 'pending', name = CASE WHEN ? <> '' THEN ? ELSE name END, lang = ?, source = ?, consent_text = ?, consented_at = ?, confirm_token = ?, unsubscribed_at = NULL WHERE id = ?",
            [$name, $name, $lang, $source, newsletter_consent_text($lang), now(), $confirm, $sub['id']]);
        $unsub = $sub['unsub_token'];
    } else {
        $unsub = bin2hex(random_bytes(16));
        q('INSERT INTO newsletter_subscribers (email, name, lang, source, status, consent_text, consented_at, confirm_token, unsub_token, created_at)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$email, mb_substr($name, 0, 120), $lang, $source, 'pending', newsletter_consent_text($lang), now(), $confirm, $unsub, now()]);
    }
    in_lang($lang, fn() => send_mail($email, t('Confirmez votre inscription à l\'infolettre EliteTap'),
        t("Bonjour,\n\nPour recevoir l'infolettre d'EliteTap, confirmez votre adresse :\n{lien}\n\nVous n'avez rien demandé ? Ignorez ce courriel : vous ne serez pas inscrit.\n\nL'équipe EliteTap\n",
            ['lien' => url('/infolettre/confirmer/' . $confirm)])));
    return 'pending';
}

/** Lien de désabonnement à mettre dans chaque envoi de l'infolettre. */
function newsletter_unsub_link(array $sub): string
{
    return url('/infolettre/desabonner/' . $sub['unsub_token']);
}
