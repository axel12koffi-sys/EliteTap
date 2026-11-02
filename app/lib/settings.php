<?php
declare(strict_types=1);

/** Réglages modifiables depuis l'admin (table settings). */
function setting(string $name, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (rows('SELECT name, value FROM settings') as $r) {
                $cache[$r['name']] = $r['value'];
            }
        } catch (Throwable $e) {
            // Table absente (migration 004 pas encore exécutée) : valeurs par défaut
        }
    }
    $v = $cache[$name] ?? null;
    return ($v === null || $v === '') ? $default : $v;
}

function set_setting(string $name, ?string $value): void
{
    $exists = scalar('SELECT COUNT(*) FROM settings WHERE name = ?', [$name]);
    if ($exists) {
        q('UPDATE settings SET value = ?, updated_at = ? WHERE name = ?', [$value, now(), $name]);
    } else {
        q('INSERT INTO settings (name, value, updated_at) VALUES (?, ?, ?)', [$name, $value, now()]);
    }
}

/** Adresse qui reçoit les notifications internes (nouvelles demandes, paiements…). */
function notify_email(): string
{
    return setting('notify_email') ?? (config('mail')['notify_to'] ?? config('mail')['from']);
}

/** Clé de réglage du prix d'un type de carte. */
function price_key(string $cardType): string
{
    return 'price_' . (slugify($cardType) ?: 'autre');
}

/** Prix unitaire en cents, ou null = sur devis (Entreprise, bois, métal…). */
function card_price(string $cardType): ?int
{
    if (!in_array($cardType, ['Standard', 'Premium', 'Bois', 'Métal'], true)) {
        return null;
    }
    $v = setting(price_key($cardType));
    return $v !== null && is_numeric($v) && (int) $v > 0 ? (int) $v : null;
}

/** Prix « à partir de » affiché sur l'accueil (réglage, sinon valeur d'origine). */
function display_price(string $cardType, int $fallback): int
{
    return card_price($cardType) ?? $fallback;
}

/** Taux de taxes en pourcentage (TPS 5 % + TVQ 9,975 % par défaut). */
function tax_rate(): float
{
    $v = setting('tax_rate', '14.975');
    return is_numeric($v) ? max(0.0, (float) $v) : 14.975;
}

/** [sous-total, taxes, total] en cents. */
function order_totals(int $unitCents, int $qty): array
{
    $sub = $unitCents * $qty;
    $tax = (int) round($sub * tax_rate() / 100);
    return [$sub, $tax, $sub + $tax];
}

function online_payment_enabled(): bool
{
    return stripe_enabled() && setting('online_payment', '1') === '1';
}
