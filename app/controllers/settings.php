<?php
declare(strict_types=1);

function admin_settings(): void
{
    $user = require_admin();
    $prices = [];
    foreach (ORDER_CHOICES as $c) {
        $prices[$c] = card_price($c);
    }
    view('admin/settings', ['title' => 'Réglages | Admin EliteTap', 'nav' => 'reglages', 'user' => $user,
        'prices' => $prices], 'admin');
}

function admin_settings_save(): void
{
    require_admin();
    $errors = [];
    foreach (ORDER_CHOICES as $c) {
        $raw = input(price_key($c), 12);
        if ($raw === '') {
            set_setting(price_key($c), null);
            continue;
        }
        $v = (float) str_replace([' ', ','], ['', '.'], $raw);
        if ($v <= 0 || $v > 100000) {
            $errors[] = "Prix invalide pour $c.";
            continue;
        }
        set_setting(price_key($c), (string) (int) round($v * 100));
    }
    $tax = str_replace(',', '.', input('tax_rate', 8));
    if ($tax === '' || !is_numeric($tax) || (float) $tax < 0 || (float) $tax > 30) {
        $errors[] = 'Taux de taxes invalide (entre 0 et 30 %).';
    } else {
        set_setting('tax_rate', (string) (float) $tax);
    }
    $notify = strtolower(input('notify_email', 190));
    if ($notify !== '' && !valid_email($notify)) {
        $errors[] = 'Courriel de notification invalide.';
    } else {
        set_setting('notify_email', $notify ?: null);
    }
    set_setting('online_payment', isset($_POST['online_payment']) ? '1' : '0');
    $pro = input('pro_price_month', 12);
    set_setting('pro_price_month', $pro === '' ? null : (string) (int) round((float) str_replace([' ', ','], ['', '.'], $pro) * 100));

    foreach ($errors as $e) flash('error', $e);
    if (!$errors) flash('success', 'Réglages enregistrés.');
    redirect('/admin/reglages');
}

function admin_mail(): void
{
    $user = require_admin();
    $log = [];
    try {
        $log = rows('SELECT * FROM mail_log ORDER BY id DESC LIMIT 150');
    } catch (Throwable $e) {
        flash('error', 'Table mail_log absente : exécutez la migration 004 dans phpMyAdmin.');
    }
    $smtp = config('mail')['smtp'] ?? [];
    view('admin/mail', ['title' => 'Courriels | Admin EliteTap', 'nav' => 'courriels', 'user' => $user,
        'log' => $log, 'transport' => mail_transport(), 'smtp' => $smtp], 'admin');
}

function admin_mail_test(): void
{
    $admin = require_admin();
    $to = strtolower(input('to', 190)) ?: $admin['email'];
    $ok = send_mail($to, 'Test EliteTap — ' . date('H:i'),
        "Bonjour,\n\nCeci est un courriel de test envoyé depuis l'administration EliteTap (" . public_host() . ").\n"
        . "Si vous le lisez, l'envoi des courriels fonctionne.\n\nMode d'envoi : " . strtoupper(mail_transport()) . "\n");
    flash($ok ? 'success' : 'error', $ok
        ? "Courriel de test envoyé à $to. Vérifiez la boîte de réception et les indésirables."
        : "L'envoi a échoué. Le détail de l'erreur est dans le journal ci-dessous.");
    redirect('/admin/courriels');
}
