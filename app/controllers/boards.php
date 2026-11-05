<?php
declare(strict_types=1);

/* Tableaux de bord par service : service client et comptabilité. */

function admin_service_dashboard(): void
{
    $user = require_perm('service');
    $d7 = date('Y-m-d H:i:s', time() - 7 * 86400);
    $today = date('Y-m-d') . ' 00:00:00';
    $chat = ['ready' => chat_ready()];
    if ($chat['ready']) {
        $chat += [
            'waiting'   => chat_waiting_count(),
            'active'    => (int) scalar("SELECT COUNT(*) FROM chat_sessions WHERE status = 'active'"),
            'today'     => (int) scalar('SELECT COUNT(*) FROM chat_sessions WHERE created_at >= ?', [$today]),
            'week'      => (int) scalar('SELECT COUNT(*) FROM chat_sessions WHERE created_at >= ?', [$d7]),
            'answered'  => (int) scalar('SELECT COUNT(*) FROM chat_sessions WHERE created_at >= ? AND first_reply_at IS NOT NULL', [$d7]),
            'rated'     => (int) scalar('SELECT COUNT(*) FROM chat_sessions WHERE created_at >= ? AND rating IS NOT NULL', [$d7]),
            'happy'     => (int) scalar('SELECT COUNT(*) FROM chat_sessions WHERE created_at >= ? AND rating = 1', [$d7]),
            'queue'     => rows("SELECT s.*, (SELECT body FROM chat_messages m WHERE m.session_id = s.id ORDER BY m.id LIMIT 1) first_body
                                 FROM chat_sessions s WHERE s.status = 'waiting' ORDER BY s.created_at LIMIT 10"),
        ];
        // Temps de première réponse (médiane sur 7 jours, en secondes)
        $delays = [];
        foreach (rows('SELECT created_at, first_reply_at FROM chat_sessions WHERE created_at >= ? AND first_reply_at IS NOT NULL', [$d7]) as $r) {
            $delays[] = max(0, strtotime($r['first_reply_at']) - strtotime($r['created_at']));
        }
        sort($delays);
        $chat['median'] = $delays ? $delays[intdiv(count($delays), 2)] : null;
        $chat['byAgent'] = rows("SELECT u.name, COUNT(*) n FROM chat_sessions s JOIN users u ON u.id = s.assigned_to WHERE s.created_at >= ? GROUP BY u.name ORDER BY n DESC", [$d7]);
    }
    $inbox = ['ready' => inbox_ready()];
    if ($inbox['ready']) {
        $inbox += [
            'unread' => inbox_unread_count(),
            'open'   => (int) scalar("SELECT COUNT(*) FROM threads WHERE status = 'open'"),
            'week'   => (int) scalar("SELECT COUNT(*) FROM messages WHERE direction = 'in' AND created_at >= ?", [$d7]),
            'latest' => rows("SELECT t.*, (SELECT body FROM messages m WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1) last_body
                              FROM threads t WHERE t.unread > 0 AND t.status <> 'spam' ORDER BY t.last_message_at DESC LIMIT 6"),
        ];
    }
    $orders = [
        'service'     => (int) scalar("SELECT COUNT(*) FROM orders WHERE payment_method = 'service' AND paid_at IS NULL AND status <> 'annulee'"),
        'unconfirmed' => (int) scalar("SELECT COUNT(*) FROM orders WHERE confirmed_at IS NULL AND paid_at IS NULL AND status = 'devis' AND created_at <= ?", [date('Y-m-d H:i:s', time() - 2 * 86400)]),
        'design'      => (int) scalar("SELECT COUNT(*) FROM orders WHERE status IN ('design','epreuve')"),
        'new'         => (int) scalar("SELECT COUNT(*) FROM orders WHERE created_at >= ?", [$d7]),
    ];
    $news = newsletter_ready() ? (int) scalar("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'confirmed' AND confirmed_at >= ?", [$d7]) : null;
    $agents = array_values(array_filter(team_members(), fn($m) => staff_can($m, 'service')));
    view('admin/service', ['title' => 'Service client | Admin EliteTap', 'nav' => 'service', 'user' => $user,
        'chat' => $chat, 'inbox' => $inbox, 'orders' => $orders, 'news' => $news, 'agents' => $agents, 'presence' => presence()], 'admin');
}

/* ------------------------------------------------------------------ */
/* Comptabilité                                                        */
/* ------------------------------------------------------------------ */

/** Période choisie : [début Y-m-d, fin Y-m-d, libellé de préréglage]. */
function accounting_period(): array
{
    $preset = is_string($_GET['p'] ?? null) ? $_GET['p'] : 'mois';
    $y = (int) date('Y');
    $m = (int) date('n');
    [$from, $to] = match ($preset) {
        'mois-dernier' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('first day of last month'))],
        'trimestre'    => [sprintf('%d-%02d-01', $y, intdiv($m - 1, 3) * 3 + 1), date('Y-m-t', strtotime(sprintf('%d-%02d-01', $y, intdiv($m - 1, 3) * 3 + 3)))],
        'annee'        => ["$y-01-01", "$y-12-31"],
        'annee-derniere' => [($y - 1) . '-01-01', ($y - 1) . '-12-31'],
        'perso'        => [
            preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['du'] ?? '')) ? $_GET['du'] : date('Y-m-01'),
            preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['au'] ?? '')) ? $_GET['au'] : date('Y-m-d'),
        ],
        default        => [date('Y-m-01'), date('Y-m-t')],
    };
    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }
    return [$from, $to, $preset];
}

/**
 * Détail comptable d'une commande payée : sous-total, TPS, TVQ, total (cents).
 * Commandes antérieures au détail des taxes : calculé à partir du total et du taux actuel (« estimé »).
 */
function order_tax_split(array $o): array
{
    $total = (int) $o['amount_cents'];
    $rate = tax_rate();
    $estimated = false;
    if ($o['tax_cents'] !== null && $o['subtotal_cents'] !== null) {
        $sub = (int) $o['subtotal_cents'];
        $tax = (int) $o['tax_cents'];
    } else {
        $sub = (int) round($total / (1 + $rate / 100));
        $tax = $total - $sub;
        $estimated = true;
    }
    // Québec : TPS 5 % et TVQ 9,975 % ; autre taux : tout en « taxes »
    if (abs($rate - 14.975) < 0.001) {
        $tps = (int) round($sub * 0.05);
        $tvq = $tax - $tps;
    } else {
        $tps = $tax;
        $tvq = 0;
    }
    return ['sub' => $sub, 'tps' => $tps, 'tvq' => $tvq, 'tax' => $tax, 'total' => $total, 'estimated' => $estimated];
}

function payment_label(array $o): string
{
    if ($o['payment_method'] === 'online') {
        return 'En ligne (Stripe)';
    }
    $ref = (string) $o['payment_ref'];
    return str_starts_with($ref, 'manuel : ') ? substr($ref, strlen('manuel : ')) : 'Service client';
}

function accounting_orders(string $from, string $to): array
{
    return rows("SELECT * FROM orders WHERE paid_at IS NOT NULL AND paid_at >= ? AND paid_at <= ? AND amount_cents IS NOT NULL ORDER BY paid_at",
        [$from . ' 00:00:00', $to . ' 23:59:59']);
}

function admin_accounting(): void
{
    $user = require_perm('compta');
    [$from, $to, $preset] = accounting_period();
    $orders = accounting_orders($from, $to);
    $tot = ['sub' => 0, 'tps' => 0, 'tvq' => 0, 'tax' => 0, 'total' => 0, 'count' => 0, 'estimated' => 0];
    $byMethod = [];
    $byCard = [];
    foreach ($orders as &$o) {
        $o['split'] = order_tax_split($o);
        foreach (['sub', 'tps', 'tvq', 'tax', 'total'] as $k) {
            $tot[$k] += $o['split'][$k];
        }
        $tot['count']++;
        $tot['estimated'] += $o['split']['estimated'] ? 1 : 0;
        $m = payment_label($o);
        $byMethod[$m] = ($byMethod[$m] ?? 0) + $o['split']['total'];
        $byCard[$o['card_type']] = ($byCard[$o['card_type']] ?? 0) + $o['split']['sub'];
    }
    unset($o);
    arsort($byMethod);
    arsort($byCard);

    // 12 mois se terminant au mois de fin de période
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $months[date('Y-m', strtotime(substr($to, 0, 7) . "-01 -$i months"))] = 0;
    }
    $start = array_key_first($months) . '-01';
    foreach (accounting_orders($start, date('Y-m-t', strtotime(array_key_last($months) . '-01'))) as $o) {
        $k = substr($o['paid_at'], 0, 7);
        if (isset($months[$k])) {
            $months[$k] += order_tax_split($o)['sub'];
        }
    }

    // À recevoir : commandes à régler avec le service client, ou lien de paiement envoyé, non payées
    $receivable = rows("SELECT * FROM orders WHERE paid_at IS NULL AND status <> 'annulee' AND amount_cents IS NOT NULL
                        AND (payment_method = 'service' OR pay_token IS NOT NULL) ORDER BY created_at");
    $proPrice = setting('pro_price_month');
    $proCount = (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'client' AND is_active = 1 AND plan = 'pro'");

    view('admin/accounting', ['title' => 'Comptabilité | Admin EliteTap', 'nav' => 'compta', 'user' => $user,
        'from' => $from, 'to' => $to, 'preset' => $preset, 'orders' => $orders, 'tot' => $tot,
        'byMethod' => $byMethod, 'byCard' => $byCard, 'months' => $months, 'receivable' => $receivable,
        'mrr' => $proPrice !== null ? $proCount * (int) $proPrice : null, 'proCount' => $proCount], 'admin');
}

function admin_accounting_export(): void
{
    require_perm('compta');
    [$from, $to] = accounting_period();
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"ventes-elitetap-$from-au-$to.csv\"");
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $num = fn(int $c) => number_format($c / 100, 2, ',', '');
    fputcsv($out, ['date_paiement', 'reference', 'client', 'entreprise', 'courriel', 'carte', 'quantite', 'sous_total', 'tps', 'tvq', 'total', 'moyen', 'reference_paiement', 'detail_estime'], ';');
    foreach (accounting_orders($from, $to) as $o) {
        $s = order_tax_split($o);
        fputcsv($out, [substr($o['paid_at'], 0, 10), $o['reference'], $o['name'], $o['company'], $o['email'], $o['card_type'], $o['quantity'],
            $num($s['sub']), $num($s['tps']), $num($s['tvq']), $num($s['total']), payment_label($o), $o['payment_ref'], $s['estimated'] ? 'oui' : 'non'], ';');
    }
    exit;
}
