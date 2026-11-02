<?php
declare(strict_types=1);

/*
 * Parcours de commande :
 *  1. le client envoie le formulaire → courriel avec un lien de confirmation ;
 *  2. le lien confirme son adresse et ouvre /commande/{réf}/{jeton} ;
 *  3. il choisit sa carte et la quantité ;
 *  4. il paie en ligne (prix fixés dans Admin › Réglages), ou choisit de régler avec le service client.
 *  Après paiement, le compte client est créé et son lien d'accès lui est envoyé.
 */

/** Types proposés sur la page de commande. */
const ORDER_CHOICES = ['Standard', 'Premium', 'Bois', 'Métal'];

function confirmable_order(string $ref, string $token): array
{
    $o = row('SELECT * FROM orders WHERE reference = ?', [$ref]);
    if (!$o || !$o['confirm_token'] || !hash_equals($o['confirm_token'], $token)) {
        abort(404);
    }
    // Sans choix explicite du visiteur, la page s'affiche dans la langue de la commande
    if (!isset($_GET['lang']) && !isset($_COOKIE['lang'])) {
        $GLOBALS['lang_override'] = $o['lang'];
    }
    return $o;
}

function order_show(string $ref, string $token): void
{
    $o = confirmable_order($ref, $token);
    if (!$o['confirmed_at']) {
        q('UPDATE orders SET confirmed_at = ?, updated_at = ? WHERE id = ?', [now(), now(), $o['id']]);
        $o['confirmed_at'] = now();
    }
    $choices = [];
    foreach (ORDER_CHOICES as $c) {
        $choices[$c] = card_price($c);
    }
    view('order/show', [
        'title'   => t('Votre commande {ref} | EliteTap', ['ref' => $o['reference']]),
        'o'       => $o,
        'choices' => $choices,
        'taxRate' => tax_rate(),
        'online'  => online_payment_enabled(),
        'sent'    => $_GET['envoye'] ?? null,
    ], 'site');
}

function order_submit(string $ref, string $token): void
{
    $o = confirmable_order($ref, $token);
    $back = '/commande/' . $o['reference'] . '/' . $o['confirm_token'];
    if ($o['paid_at'] || $o['status'] === 'annulee') {
        redirect($back);
    }

    $type = input('card_type', 40);
    if (!in_array($type, ORDER_CHOICES, true)) {
        $type = $o['card_type'];
    }
    $qty = (int) input('quantity', 6);
    if ($qty < 1 || $qty > 10000) {
        flash('error', t('La quantité doit être entre 1 et 10 000.'));
        redirect($back);
    }
    $method = input('method', 10);
    $price = in_array($type, ORDER_CHOICES, true) ? card_price($type) : null;

    // Paiement en ligne : seulement si un prix est fixé pour ce type de carte
    if ($method === 'online' && $price !== null && online_payment_enabled()) {
        [$sub, $tax, $total] = order_totals($price, $qty);
        $payToken = $o['pay_token'] ?: bin2hex(random_bytes(16));
        q('UPDATE orders SET card_type = ?, quantity = ?, subtotal_cents = ?, tax_cents = ?, amount_cents = ?, pay_token = ?, payment_method = ?, updated_at = ? WHERE id = ?',
            [$type, $qty, $sub, $tax, $total, $payToken, 'online', now(), $o['id']]);
        $o = row('SELECT * FROM orders WHERE id = ?', [$o['id']]);
        try {
            $session = stripe_request('POST', 'checkout/sessions', stripe_checkout_params($o));
        } catch (Throwable $e) {
            flash('error', t('Le paiement est momentanément indisponible. Réessayez dans un instant ou écrivez-nous.'));
            redirect($back);
        }
        redirect($session['url'], 303);
    }

    // Service client : l'équipe EliteTap prend le relais par courriel
    $totals = $price !== null ? order_totals($price, $qty) : [null, null, null];
    q('UPDATE orders SET card_type = ?, quantity = ?, subtotal_cents = ?, tax_cents = ?, amount_cents = COALESCE(?, amount_cents), payment_method = ?, updated_at = ? WHERE id = ?',
        [$type, $qty, $totals[0], $totals[1], $totals[2], 'service', now(), $o['id']]);
    send_mail(notify_email(), "Commande confirmée — service client : {$o['reference']} ({$o['name']})",
        "{$o['name']} a confirmé sa commande et souhaite régler avec le service client.\n\n"
        . "Carte : $type × $qty\n" . ($totals[2] !== null ? 'Total estimé : ' . money($totals[2]) . " taxes incluses\n" : "Prix : sur devis\n")
        . "Courriel : {$o['email']}\nTéléphone : {$o['phone']}\n\n"
        . "Écrivez-lui pour convenir du paiement, puis « Marquer comme payée » dans l'admin :\n" . url('/admin/commandes/' . $o['id']) . "\n",
        $o['email']);
    in_lang($o['lang'], fn() => send_mail($o['email'], t('Commande {ref} confirmée', ['ref' => $o['reference']]),
        t("Bonjour {nom},\n\nVotre commande est confirmée : {carte} × {qte}.\n\nNotre service client vous écrit dans les 24 h pour le paiement et votre épreuve de design.\n\nSuivre votre commande : {lien}\n\nL'équipe EliteTap\n",
            ['nom' => $o['name'], 'carte' => t($type), 'qte' => $qty, 'lien' => order_link($o)])));
    redirect($back . '?envoye=service');
}

/** « Je n'ai pas reçu le courriel » sur la page de remerciement. */
function order_resend(): void
{
    $ref = $_SESSION['last_order_ref'] ?? null;
    $key = throttle_key('resend');
    if ($ref && !too_many_attempts($key, 3, 60)) {
        hit_attempt($key);
        $o = row('SELECT * FROM orders WHERE reference = ?', [$ref]);
        if ($o && $o['confirm_token']) {
            send_order_confirmation($o);
        }
    }
    flash('success', t('Si l\'adresse est correcte, le lien vient de vous être renvoyé. Pensez à vérifier vos courriels indésirables.'));
    redirect('/merci');
}
