<?php
declare(strict_types=1);

function payable_order(string $ref, string $token): array
{
    $o = row('SELECT * FROM orders WHERE reference = ?', [$ref]);
    if (!$o || !$o['pay_token'] || !hash_equals($o['pay_token'], $token)) {
        abort(404);
    }
    return $o;
}

/** Page récapitulative (GET) : aucun appel à Stripe ici, pour que les aperçus de lien WhatsApp ne créent pas de session. */
function pay_show(string $ref, string $token): void
{
    $o = payable_order($ref, $token);
    // Sans choix explicite du visiteur, la page s'affiche dans la langue de la commande
    if (!isset($_GET['lang']) && !isset($_COOKIE['lang'])) {
        $GLOBALS['lang_override'] = $o['lang'];
    }
    view('payment/show', ['title' => t('Paiement {ref} | EliteTap', ['ref' => $o['reference']]), 'o' => $o,
        'enabled' => stripe_enabled()], 'site');
}

function pay_start(string $ref, string $token): void
{
    $o = payable_order($ref, $token);
    if ($o['paid_at'] || $o['amount_cents'] === null || (int) $o['amount_cents'] < 50 || !stripe_enabled() || $o['status'] === 'annulee') {
        redirect('/payer/' . $ref . '/' . $token);
    }
    try {
        $session = stripe_request('POST', 'checkout/sessions', stripe_checkout_params($o));
    } catch (Throwable $e) {
        flash('error', t('Le paiement est momentanément indisponible. Réessayez dans un instant ou écrivez-nous.'));
        redirect('/payer/' . $ref . '/' . $token);
    }
    redirect($session['url'], 303);
}

function pay_thanks(): void
{
    view('payment/thanks', ['title' => t('Paiement reçu | EliteTap'), 'ref' => (string) ($_GET['ref'] ?? '')], 'site');
}

/** Webhook Stripe : seule source de vérité pour marquer une commande payée. */
function stripe_webhook(): void
{
    $payload = (string) file_get_contents('php://input');
    $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    if (!stripe_enabled() || !stripe_verify_signature($payload, $sig, config('stripe')['webhook_secret'])) {
        http_response_code(400);
        exit('signature');
    }
    $event = json_decode($payload, true);
    $type = $event['type'] ?? '';
    $obj = $event['data']['object'] ?? [];

    // Abonnements Pro
    stripe_subscription_event($type, $obj);

    // Paiement unique d'une commande
    if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)
        && ($obj['mode'] ?? 'payment') === 'payment'
        && ($obj['payment_status'] ?? '') === 'paid') {
        $orderId = (int) ($obj['metadata']['order_id'] ?? $obj['client_reference_id'] ?? 0);
        $o = row('SELECT * FROM orders WHERE id = ?', [$orderId]);
        if ($o && !$o['paid_at'] && (int) ($obj['amount_total'] ?? 0) === (int) $o['amount_cents']) {
            order_mark_paid($o, (string) ($obj['payment_intent'] ?? $obj['id'] ?? ''), 'online');
        }
    }
    http_response_code(200);
    echo 'ok';
}
