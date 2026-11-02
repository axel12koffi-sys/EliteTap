<?php
declare(strict_types=1);

/*
 * Tableau de bord admin : indicateurs de succès du plan d'évolution (section 05)
 *  - taux d'activation, conversion Pro, revenu récurrent (MRR), rétention, prospects générés,
 *  - chiffre d'affaires des commandes, touchers et entonnoir des commandes.
 */

function pct(int $part, int $total): ?float
{
    return $total > 0 ? round($part / $total * 100, 1) : null;
}

function admin_dashboard(): void
{
    $user = require_admin();
    $now = time();
    $d30 = date('Y-m-d H:i:s', $now - 30 * 86400);

    // Taux d'activation : cartes activées depuis au moins 30 jours, touchées dans leurs 30 premiers jours
    $act = row("SELECT COUNT(*) total,
                  SUM(CASE WHEN EXISTS (SELECT 1 FROM events e WHERE e.card_id = c.id AND e.type = 'tap'
                       AND e.created_at <= " . date_add_sql('c.activated_at', 30) . ") THEN 1 ELSE 0 END) touched
                FROM cards c WHERE c.activated_at IS NOT NULL AND c.activated_at <= ?", [$d30]);

    // Conversion Essentiel → Pro
    $clients = (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'client' AND is_active = 1");
    $paid = (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'client' AND is_active = 1 AND plan IN ('pro','entreprise')");
    $stripePro = (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'client' AND is_active = 1 AND plan = 'pro' AND plan_source = 'stripe' AND plan_status IN ('active','trialing','past_due')");

    // MRR estimé : abonnés Pro × prix mensuel du Pro (Admin › Réglages)
    $proPrice = setting('pro_price_month');
    $proCount = (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'client' AND is_active = 1 AND plan = 'pro'");
    $mrr = $proPrice !== null ? $proCount * (int) $proPrice : null;

    // Rétention : forfaits payants commencés il y a 6 / 12 mois ou plus, encore payants aujourd'hui
    $ret = [];
    foreach ([6, 12] as $m) {
        $since = date('Y-m-d H:i:s', strtotime("-$m months"));
        $r = row("SELECT COUNT(*) total, SUM(CASE WHEN plan IN ('pro','entreprise') THEN 1 ELSE 0 END) still
                  FROM users WHERE role = 'client' AND plan_started_at IS NOT NULL AND plan_started_at <= ?", [$since]);
        $ret[$m] = ['total' => (int) $r['total'], 'pct' => pct((int) $r['still'], (int) $r['total'])];
    }

    // Prospects générés
    $activeCards = (int) scalar("SELECT COUNT(*) FROM cards WHERE status = 'active'");
    $leads30 = (int) scalar('SELECT COUNT(*) FROM leads WHERE created_at >= ?', [$d30]);
    $leadsAll = (int) scalar('SELECT COUNT(*) FROM leads');

    // Chiffre d'affaires des commandes payées : 12 derniers mois
    $revenue = [];
    for ($i = 11; $i >= 0; $i--) {
        $revenue[date('Y-m', strtotime("first day of -$i months"))] = 0;
    }
    foreach (rows("SELECT SUBSTR(paid_at, 1, 7) m, SUM(amount_cents) s FROM orders WHERE paid_at IS NOT NULL AND paid_at >= ? GROUP BY SUBSTR(paid_at, 1, 7)",
        [array_key_first($revenue) . '-01 00:00:00']) as $r) {
        if (isset($revenue[$r['m']])) $revenue[$r['m']] = (int) $r['s'];
    }
    $thisMonth = $revenue[date('Y-m')] ?? 0;

    // Touchers par jour, 30 jours (toutes cartes)
    $taps = [];
    for ($i = 29; $i >= 0; $i--) {
        $taps[date('Y-m-d', strtotime("-$i days"))] = 0;
    }
    foreach (rows("SELECT SUBSTR(created_at, 1, 10) d, COUNT(*) n FROM events WHERE type = 'tap' AND created_at >= ? GROUP BY SUBSTR(created_at, 1, 10)",
        [array_key_first($taps) . ' 00:00:00']) as $r) {
        if (isset($taps[$r['d']])) $taps[$r['d']] = (int) $r['n'];
    }

    // Entonnoir des commandes (90 jours)
    $d90 = date('Y-m-d H:i:s', $now - 90 * 86400);
    $f = row("SELECT COUNT(*) recues,
                SUM(CASE WHEN confirmed_at IS NOT NULL THEN 1 ELSE 0 END) confirmees,
                SUM(CASE WHEN paid_at IS NOT NULL THEN 1 ELSE 0 END) payees,
                SUM(CASE WHEN status = 'expediee' THEN 1 ELSE 0 END) expediees
              FROM orders WHERE created_at >= ? AND status <> 'annulee'", [$d90]);
    $funnel = [
        'Demandes reçues' => (int) $f['recues'],
        'Courriel confirmé' => (int) $f['confirmees'],
        'Payées' => (int) $f['payees'],
        'Expédiées' => (int) $f['expediees'],
    ];

    // À traiter
    $todo = [
        'service' => (int) scalar("SELECT COUNT(*) FROM orders WHERE payment_method = 'service' AND paid_at IS NULL AND status <> 'annulee'"),
        'unconfirmed' => (int) scalar("SELECT COUNT(*) FROM orders WHERE confirmed_at IS NULL AND paid_at IS NULL AND status = 'devis' AND created_at <= ?", [date('Y-m-d H:i:s', $now - 2 * 86400)]),
        'paid_no_card' => (int) scalar('SELECT COUNT(*) FROM orders o WHERE o.paid_at IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cards c WHERE c.order_id = o.id)'),
        'mail_failed' => (int) safe_scalar('SELECT COUNT(*) FROM mail_log WHERE status = ? AND created_at >= ?', ['failed', date('Y-m-d H:i:s', $now - 7 * 86400)]),
    ];

    view('admin/dashboard', [
        'title' => 'Tableau de bord | Admin EliteTap', 'nav' => 'tableau', 'user' => $user,
        'activation' => ['pct' => pct((int) $act['touched'], (int) $act['total']), 'total' => (int) $act['total']],
        'conversion' => ['pct' => pct($paid, $clients), 'paid' => $paid, 'clients' => $clients, 'stripe' => $stripePro],
        'mrr' => $mrr, 'proCount' => $proCount, 'proPrice' => $proPrice,
        'retention' => $ret,
        'leads' => ['d30' => $leads30, 'all' => $leadsAll, 'perCard' => $activeCards ? round($leadsAll / $activeCards, 1) : null, 'cards' => $activeCards],
        'revenue' => $revenue, 'thisMonth' => $thisMonth,
        'taps' => $taps, 'funnel' => $funnel, 'todo' => $todo,
    ], 'admin');
}

/** Date + N jours, en SQL compatible MySQL et SQLite (tests). */
function date_add_sql(string $col, int $days): string
{
    return str_starts_with(config('db')['dsn'], 'sqlite:')
        ? "datetime($col, '+$days days')"
        : "DATE_ADD($col, INTERVAL $days DAY)";
}

function safe_scalar(string $sql, array $params = []): mixed
{
    try {
        return scalar($sql, $params);
    } catch (Throwable $e) {
        return 0;
    }
}
