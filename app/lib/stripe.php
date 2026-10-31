<?php
declare(strict_types=1);

/**
 * Paiement Stripe Checkout, sans SDK (appels HTTPS directs).
 * Clés dans app/config.php : 'stripe' => ['secret_key' => 'sk_…', 'webhook_secret' => 'whsec_…'].
 */

function stripe_enabled(): bool
{
    $c = config('stripe', []);
    return !empty($c['secret_key']) && !empty($c['webhook_secret']);
}

function stripe_request(string $method, string $path, array $params = []): array
{
    $ch = curl_init('https://api.stripe.com/v1/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_USERPWD        => config('stripe')['secret_key'] . ':',
        CURLOPT_POSTFIELDS     => $method === 'GET' ? null : http_build_query($params),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => ['Stripe-Version: 2024-06-20'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $json = is_string($body) ? json_decode($body, true) : null;
    if ($code < 200 || $code >= 300 || !is_array($json)) {
        error_log('Stripe ' . $path . ' : HTTP ' . $code . ' ' . ($err ?: (string) $body));
        throw new RuntimeException($json['error']['message'] ?? 'Erreur de paiement');
    }
    return $json;
}

/** Paramètres de la session Checkout (séparés pour pouvoir être testés). */
function stripe_checkout_params(array $order): array
{
    $payUrl = url('/payer/' . $order['reference'] . '/' . $order['pay_token']);
    return [
        'mode'                 => 'payment',
        'locale'               => $order['lang'] === 'en' ? 'en' : 'fr-CA',
        'customer_email'       => $order['email'],
        'client_reference_id'  => (string) $order['id'],
        'metadata[order_id]'   => (string) $order['id'],
        'metadata[reference]'  => $order['reference'],
        'payment_intent_data[metadata][order_id]' => (string) $order['id'],
        'payment_intent_data[description]' => 'EliteTap ' . $order['reference'],
        'line_items[0][quantity]' => 1,
        'line_items[0][price_data][currency]' => 'cad',
        'line_items[0][price_data][unit_amount]' => (int) $order['amount_cents'],
        'line_items[0][price_data][product_data][name]' =>
            'EliteTap ' . $order['reference'] . ' · ' . in_lang($order['lang'], fn() => t($order['card_type'])) . ' × ' . (int) $order['quantity'],
        'success_url' => url('/paiement/merci?ref=' . rawurlencode($order['reference'])),
        'cancel_url'  => $payUrl,
    ];
}

/**
 * Vérifie l'en-tête Stripe-Signature (HMAC SHA-256 de "t.payload"), tolérance 5 min.
 */
function stripe_verify_signature(string $payload, string $header, string $secret, int $tolerance = 300): bool
{
    $t = null;
    $sigs = [];
    foreach (explode(',', $header) as $part) {
        [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($k === 't') $t = (int) $v;
        if ($k === 'v1') $sigs[] = $v;
    }
    if (!$t || !$sigs || abs(time() - $t) > $tolerance) {
        return false;
    }
    $expected = hash_hmac('sha256', $t . '.' . $payload, $secret);
    foreach ($sigs as $s) {
        if (hash_equals($expected, $s)) {
            return true;
        }
    }
    return false;
}
