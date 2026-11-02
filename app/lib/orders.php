<?php
declare(strict_types=1);

/** Lien de confirmation / suivi de la commande envoyé au client. */
function order_link(array $o): string
{
    return url('/commande/' . $o['reference'] . '/' . $o['confirm_token']);
}

/**
 * Crée le compte client de la commande (ou relie un compte existant) et active les cartes liées.
 * Renvoie [user_id, compte_cree].
 */
function order_ensure_client(array $o, bool $sendAccess = true): array
{
    $existing = row('SELECT id FROM users WHERE email = ?', [$o['email']]);
    $created = false;
    if ($existing) {
        $userId = (int) $existing['id'];
    } else {
        [$userId] = create_client($o['name'], $o['email']);
        if ($o['company'] !== '' || $o['phone'] !== '') {
            q('UPDATE profiles SET company = ?, phone = ? WHERE user_id = ?', [$o['company'], $o['phone'], $userId]);
        }
        q('UPDATE users SET lang = ? WHERE id = ?', [$o['lang'], $userId]);
        $created = true;
        if ($sendAccess) {
            $link = make_reset_link($userId, 72);
            in_lang($o['lang'], fn() => send_mail($o['email'], t('Votre espace EliteTap est prêt'),
                t("Bonjour {nom},\n\nVotre espace client EliteTap est créé. C'est là que vous modifiez le profil qui s'ouvre quand on tape votre carte.\n\nChoisissez votre mot de passe ici (lien valable 72 heures) :\n{lien}\n\nEnsuite, connectez-vous sur {connexion}\n\nL'équipe EliteTap\n",
                    ['nom' => $o['name'], 'lien' => $link, 'connexion' => url('/connexion')])));
        }
    }
    q('UPDATE orders SET user_id = ?, updated_at = ? WHERE id = ?', [$userId, now(), $o['id']]);
    $profileId = scalar('SELECT id FROM profiles WHERE user_id = ?', [$userId]);
    q("UPDATE cards SET profile_id = ?, status = 'active', activated_at = ? WHERE order_id = ? AND status = 'stock'",
        [$profileId, now(), $o['id']]);
    return [$userId, $created];
}

/**
 * Commande payée (en ligne via Stripe, ou à la main par l'admin) :
 * statut, notifications, et création du compte client avec son lien d'accès.
 */
function order_mark_paid(array $o, string $paymentRef, string $method): void
{
    q('UPDATE orders SET paid_at = ?, payment_ref = ?, payment_method = ?, status = CASE WHEN status = ? THEN ? ELSE status END, updated_at = ? WHERE id = ?',
        [now(), $paymentRef, $method, 'devis', 'design', now(), $o['id']]);
    send_mail(notify_email(), "Paiement reçu : {$o['reference']} — " . money((int) $o['amount_cents']),
        "La commande {$o['reference']} ({$o['name']}) est payée (" . ($method === 'online' ? 'en ligne' : 'service client') . ").\n"
        . "Elle passe à l'étape « Design en cours ».\n\n" . url('/admin/commandes/' . $o['id']) . "\n");
    in_lang($o['lang'], fn() => send_mail($o['email'], t('Paiement reçu — commande {ref}', ['ref' => $o['reference']]),
        t("Bonjour {nom},\n\nNous avons bien reçu votre paiement de {montant} pour la commande {ref}. Merci !\n\nNous vous tenons au courant de chaque étape de la production.\n\nL'équipe EliteTap\n",
            ['nom' => $o['name'], 'montant' => money((int) $o['amount_cents']), 'ref' => $o['reference']])));
    if (!$o['user_id']) {
        order_ensure_client($o);
    }
}

/** Courriel de confirmation envoyé après la demande (et sur « Renvoyer le lien »). */
function send_order_confirmation(array $o): bool
{
    return (bool) in_lang($o['lang'], fn() => send_mail($o['email'],
        t('Confirmez votre commande EliteTap ({ref})', ['ref' => $o['reference']]),
        t("Bonjour {nom},\n\nMerci pour votre demande de carte {carte}.\n\nConfirmez votre commande et choisissez votre forfait ici :\n{lien}\n\nVous pourrez ensuite payer en ligne, ou régler avec notre service client si vous préférez.\n\nRéférence : {ref}\n\nUne question ? Répondez à ce courriel ou écrivez-nous sur WhatsApp : {whatsapp}\n\nL'équipe EliteTap\n",
            ['nom' => $o['name'], 'carte' => t($o['card_type']), 'lien' => order_link($o), 'ref' => $o['reference'], 'whatsapp' => 'https://wa.me/' . config('whatsapp')])));
}
