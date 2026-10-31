<?php
declare(strict_types=1);

/** Prix de l'abonnement Pro configurés dans Stripe (Price ID). */
function pro_prices(): array
{
    $s = config('stripe', []);
    return array_filter([
        'month' => $s['price_pro_monthly'] ?? '',
        'year'  => $s['price_pro_yearly'] ?? '',
    ]);
}

function account_plan(): void
{
    $user = require_login();
    view('account/plan', ['title' => t('Forfait | EliteTap'), 'nav' => 'forfait', 'user' => $user, 'p' => my_profile($user),
        'prices' => stripe_enabled() ? pro_prices() : [], 'success' => isset($_GET['succes'])], 'app');
}

/** Démarre l'abonnement Pro (Stripe Checkout, mode abonnement). */
function account_plan_subscribe(): void
{
    $user = require_login();
    $interval = input('interval', 5);
    $price = pro_prices()[$interval] ?? null;
    if (!$price || !stripe_enabled() || plan_is_paid($user['plan'])) {
        redirect('/compte/forfait');
    }
    $params = [
        'mode' => 'subscription',
        'locale' => lang() === 'en' ? 'en' : 'fr-CA',
        'line_items[0][price]' => $price,
        'line_items[0][quantity]' => 1,
        'client_reference_id' => (string) $user['id'],
        'metadata[user_id]' => (string) $user['id'],
        'subscription_data[metadata][user_id]' => (string) $user['id'],
        'allow_promotion_codes' => 'true',
        'success_url' => url('/compte/forfait?succes=1'),
        'cancel_url' => url('/compte/forfait'),
    ];
    if ($user['stripe_customer_id']) {
        $params['customer'] = $user['stripe_customer_id'];
    } else {
        $params['customer_email'] = $user['email'];
    }
    try {
        $session = stripe_request('POST', 'checkout/sessions', $params);
    } catch (Throwable $e) {
        flash('error', t('Le paiement est momentanément indisponible. Réessayez dans un instant ou écrivez-nous.'));
        redirect('/compte/forfait');
    }
    redirect($session['url'], 303);
}

/** Portail Stripe : changer de carte, voir les factures, annuler. */
function account_plan_portal(): void
{
    $user = require_login();
    if (!$user['stripe_customer_id'] || !stripe_enabled()) {
        redirect('/compte/forfait');
    }
    try {
        $s = stripe_request('POST', 'billing_portal/sessions', [
            'customer' => $user['stripe_customer_id'],
            'return_url' => url('/compte/forfait'),
            'locale' => lang() === 'en' ? 'en' : 'fr-CA',
        ]);
    } catch (Throwable $e) {
        flash('error', t('Le paiement est momentanément indisponible. Réessayez dans un instant ou écrivez-nous.'));
        redirect('/compte/forfait');
    }
    redirect($s['url'], 303);
}

/**
 * Événements d'abonnement reçus par le webhook Stripe.
 * Un forfait attribué à la main par l'admin (plan_source = manual) n'est jamais rétrogradé par Stripe.
 */
function stripe_subscription_event(string $type, array $obj): void
{
    if ($type === 'checkout.session.completed' && ($obj['mode'] ?? '') === 'subscription') {
        $userId = (int) ($obj['metadata']['user_id'] ?? $obj['client_reference_id'] ?? 0);
        $u = row('SELECT id, plan, email, name, lang FROM users WHERE id = ?', [$userId]);
        if (!$u) return;
        q("UPDATE users SET plan = CASE WHEN plan = 'entreprise' THEN plan ELSE 'pro' END, plan_source = 'stripe', plan_status = 'active',
           stripe_customer_id = ?, stripe_subscription_id = ? WHERE id = ?",
            [(string) ($obj['customer'] ?? ''), (string) ($obj['subscription'] ?? ''), $u['id']]);
        send_mail(config('mail')['notify_to'], "Nouvel abonnement Pro : {$u['name']}",
            "{$u['name']} ({$u['email']}) vient de s'abonner au forfait Pro.\n\n" . url('/admin/clients') . "\n");
        return;
    }

    if (in_array($type, ['customer.subscription.updated', 'customer.subscription.deleted'], true)) {
        $u = row('SELECT id, plan, plan_source FROM users WHERE stripe_subscription_id = ?', [(string) ($obj['id'] ?? '')])
            ?? row('SELECT id, plan, plan_source FROM users WHERE id = ?', [(int) ($obj['metadata']['user_id'] ?? 0)]);
        if (!$u) return;
        $status = $type === 'customer.subscription.deleted' ? 'canceled' : (string) ($obj['status'] ?? '');
        $renews = isset($obj['current_period_end']) ? date('Y-m-d H:i:s', (int) $obj['current_period_end']) : null;
        $ended = in_array($status, ['canceled', 'incomplete_expired', 'unpaid'], true);
        $plan = $u['plan'];
        if ($ended && $u['plan_source'] === 'stripe' && $plan === 'pro') {
            $plan = 'essentiel';                           // retour au forfait gratuit, le profil reste en ligne
        } elseif (in_array($status, ['active', 'trialing'], true) && $plan === 'essentiel') {
            $plan = 'pro';
        }
        q('UPDATE users SET plan = ?, plan_status = ?, plan_renews_at = ?, stripe_subscription_id = ? WHERE id = ?',
            [$plan, $status, $renews, $ended ? null : (string) $obj['id'], $u['id']]);
    }
}
