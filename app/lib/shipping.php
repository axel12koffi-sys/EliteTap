<?php
declare(strict_types=1);

/*
 * Livraisons : une ou plusieurs expéditions par commande, transporteur, numéro et lien de suivi,
 * historique des statuts, courriels au client et page publique de suivi (/suivi/{jeton}).
 * Les statuts sont mis à jour par l'équipe (pas de liaison automatique aux transporteurs).
 */

/** Transporteurs : libellé et modèle de lien de suivi ({n} = numéro). */
const CARRIERS = [
    'postes_canada' => ['Postes Canada', 'https://www.canadapost-postescanada.ca/track-reperage/fr#/search?searchFor={n}'],
    'purolator'     => ['Purolator', 'https://www.purolator.com/en/shipping/tracker?pin={n}'],
    'ups'           => ['UPS', 'https://www.ups.com/track?loc=fr_CA&tracknum={n}'],
    'fedex'         => ['FedEx', 'https://www.fedex.com/fedextrack/?trknbr={n}'],
    'dhl'           => ['DHL', 'https://www.dhl.com/ca-en/home/tracking.html?tracking-id={n}'],
    'intelcom'      => ['Intelcom', null],
    'main_propre'   => ['Livraison en main propre', null],
    'ramassage'     => ['Ramassage à Laval', null],
    'autre'         => ['Autre transporteur', null],
];

const SHIP_STATUSES = [
    'a_preparer' => 'À préparer',
    'preparee'   => 'Prête à partir',
    'expediee'   => 'Expédiée',
    'en_transit' => 'En transit',
    'livree'     => 'Livrée',
    'probleme'   => 'Problème de livraison',
    'retournee'  => 'Retournée',
];

/** Statuts pour lesquels on propose de prévenir le client. */
const SHIP_NOTIFY = ['expediee', 'en_transit', 'livree', 'probleme'];

function shipping_ready(): bool
{
    static $ok = null;
    if ($ok === null) {
        try {
            scalar('SELECT COUNT(*) FROM shipments WHERE 1 = 0');
            $ok = true;
        } catch (Throwable $e) {
            $ok = false;
        }
    }
    return $ok;
}

/** Lien de suivi : celui saisi, sinon construit à partir du transporteur et du numéro. */
function tracking_link(array $s): ?string
{
    if (!empty($s['tracking_url'])) {
        return $s['tracking_url'];
    }
    $tpl = CARRIERS[$s['carrier']][1] ?? null;
    return $tpl && !empty($s['tracking_number']) ? str_replace('{n}', rawurlencode($s['tracking_number']), $tpl) : null;
}

function carrier_label(string $c): string
{
    return CARRIERS[$c][0] ?? $c;
}

function public_tracking_link(array $s): string
{
    return url('/suivi/' . $s['token']);
}

/** Statut pour le client, dans sa langue. */
function ship_status_public(string $status): string
{
    return match ($status) {
        'a_preparer' => t('En préparation'),
        'preparee'   => t('Prête à partir'),
        'expediee'   => t('Expédiée'),
        'en_transit' => t('En transit'),
        'livree'     => t('Livrée'),
        'probleme'   => t('Retard ou problème de livraison'),
        'retournee'  => t('Retournée à EliteTap'),
        default      => $status,
    };
}

/**
 * Change le statut d'une expédition : historique, dates, statut de la commande, courriel au client.
 */
function shipment_set_status(array $s, string $status, string $note, bool $notify, ?int $userId): void
{
    if (!isset(SHIP_STATUSES[$status])) {
        return;
    }
    $now = now();
    q('UPDATE shipments SET status = ?, updated_at = ?,
         shipped_at = CASE WHEN ? IN (\'expediee\', \'en_transit\', \'livree\') AND shipped_at IS NULL THEN ? ELSE shipped_at END,
         delivered_at = CASE WHEN ? = \'livree\' THEN COALESCE(delivered_at, ?) ELSE delivered_at END
       WHERE id = ?', [$status, $now, $status, $now, $status, $now, $s['id']]);
    $sent = false;
    $s = row('SELECT * FROM shipments WHERE id = ?', [$s['id']]);
    $o = row('SELECT * FROM orders WHERE id = ?', [$s['order_id']]);
    if ($o && in_array($status, ['expediee', 'en_transit', 'livree'], true) && !in_array($o['status'], ['expediee', 'annulee'], true)) {
        q('UPDATE orders SET status = ?, updated_at = ? WHERE id = ?', ['expediee', $now, $o['id']]);
    }
    if ($notify && $o && in_array($status, SHIP_NOTIFY, true)) {
        $sent = send_shipment_mail($s, $o, $status, $note);
    }
    q('INSERT INTO shipment_events (shipment_id, status, note, notified, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
        [$s['id'], $status, mb_substr($note, 0, 500), $sent ? 1 : 0, $userId, $now]);
}

function send_shipment_mail(array $s, array $o, string $status, string $note): bool
{
    return (bool) in_lang($o['lang'] ?? 'fr', function () use ($s, $o, $status, $note) {
        $link = tracking_link($s);
        $vars = ['nom' => $o['name'], 'ref' => $o['reference'], 'suivi' => public_tracking_link($s),
            'transporteur' => carrier_label($s['carrier']), 'numero' => $s['tracking_number'] ?: '—'];
        [$subject, $body] = match ($status) {
            'livree' => [t('Votre commande EliteTap {ref} est livrée', $vars),
                t("Bonjour {nom},\n\nVotre commande {ref} a été livrée. Nous espérons que vos cartes vous plaisent !\n\nUne question ou un souci ? Répondez à ce courriel.\n\nL'équipe EliteTap\n", $vars)],
            'probleme' => [t('Votre livraison EliteTap {ref} : mise à jour', $vars),
                t("Bonjour {nom},\n\nLa livraison de votre commande {ref} a pris du retard ou rencontre un problème. Nous nous en occupons.\n\nSuivi : {suivi}\n\nUne question ? Répondez à ce courriel.\n\nL'équipe EliteTap\n", $vars)],
            default => [t('Votre commande EliteTap {ref} est en route', $vars),
                t("Bonjour {nom},\n\nBonne nouvelle : votre commande {ref} est en route.\n\nTransporteur : {transporteur}\nNuméro de suivi : {numero}\nSuivre mon colis : {suivi}\n\nUne question ? Répondez à ce courriel.\n\nL'équipe EliteTap\n", $vars)],
        };
        if ($note !== '' && $status !== 'livree') {
            $body = str_replace("\n\nL'équipe EliteTap", "\n\n" . $note . "\n\nL'équipe EliteTap", $body);
            $body = str_replace("\n\nThe EliteTap team", "\n\n" . $note . "\n\nThe EliteTap team", $body);
        }
        if ($link && $status !== 'livree') {
            $body = str_replace($vars['suivi'], $vars['suivi'] . "\n" . t('Site du transporteur : {lien}', ['lien' => $link]), $body);
        }
        // Dans la conversation de la commande (Admin › Messages) quand elle existe
        $tid = function_exists('order_thread_id') ? order_thread_id($o) : null;
        return $tid ? thread_send($tid, $subject, $body, 'system') : send_mail($o['email'], $subject, $body);
    });
}

function shipping_counts(): array
{
    if (!shipping_ready()) {
        return [];
    }
    $c = [];
    foreach (rows('SELECT status, COUNT(*) n FROM shipments GROUP BY status') as $r) {
        $c[$r['status']] = (int) $r['n'];
    }
    return $c;
}

/** Commandes payées, en production ou prêtes, sans expédition : à expédier. */
function orders_to_ship(): array
{
    if (!shipping_ready()) {
        return [];
    }
    return rows("SELECT o.* FROM orders o WHERE o.paid_at IS NOT NULL AND o.status IN ('design','epreuve','production')
                 AND NOT EXISTS (SELECT 1 FROM shipments s WHERE s.order_id = o.id) ORDER BY o.paid_at LIMIT 100");
}

/** À traiter par le département : à préparer, prêtes, problèmes. */
function shipping_attention_count(): int
{
    if (!shipping_ready()) {
        return 0;
    }
    return (int) scalar("SELECT COUNT(*) FROM shipments WHERE status IN ('a_preparer','preparee','probleme')");
}
