<?php
$views = ['en-cours' => 'En cours', 'a-preparer' => 'À préparer', 'en-route' => 'En route', 'problemes' => 'Problèmes', 'livrees' => 'Livrées', 'toutes' => 'Toutes'];
$c = fn(array $keys) => array_sum(array_map(fn($k) => $counts[$k] ?? 0, $keys));
$viewCounts = ['en-cours' => $c(['a_preparer', 'preparee', 'expediee', 'en_transit', 'probleme']), 'a-preparer' => $c(['a_preparer', 'preparee']),
    'en-route' => $c(['expediee', 'en_transit']), 'problemes' => $c(['probleme', 'retournee']), 'livrees' => $c(['livree']), 'toutes' => array_sum($counts)];
?>
<div class="page-head">
  <div>
    <h1>Suivi des livraisons</h1>
    <p class="muted">Département Livraison : préparation, expédition et suivi des colis. Le client reçoit un courriel et un lien de suivi à chaque étape importante.</p>
  </div>
  <form method="get" action="/admin/livraisons" class="search">
    <input type="hidden" name="vue" value="<?= e($vue) ?>">
    <label class="visually-hidden" for="q">Rechercher</label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Commande, client, n° de suivi, ville">
    <button class="btn btn-light" type="submit">Rechercher</button>
  </form>
</div>

<div class="kpis kpis-5">
  <a class="kpi kpi-link<?= $toShip ? ' kpi-alert' : '' ?>" href="#a-expedier"><span>Commandes à expédier</span><strong><?= count($toShip) ?></strong><em>payées, sans expédition</em></a>
  <a class="kpi kpi-link" href="/admin/livraisons?vue=a-preparer"><span>À préparer</span><strong><?= $viewCounts['a-preparer'] ?></strong><em><?= $counts['preparee'] ?? 0 ?> prête(s) à partir</em></a>
  <a class="kpi kpi-link" href="/admin/livraisons?vue=en-route"><span>En route</span><strong><?= $viewCounts['en-route'] ?></strong><em>expédiées ou en transit</em></a>
  <a class="kpi kpi-link<?= ($counts['probleme'] ?? 0) ? ' kpi-alert' : '' ?>" href="/admin/livraisons?vue=problemes"><span>Problèmes</span><strong><?= $viewCounts['problemes'] ?></strong><em><?= $counts['retournee'] ?? 0 ?> retournée(s)</em></a>
  <div class="kpi"><span>Livrées (30 jours)</span><strong><?= $delivered30 ?></strong><em><?= $avgDays === null ? 'délai moyen : —' : 'délai moyen ' . number_format($avgDays, 1, ',', ' ') . ' j après l\'envoi' ?></em></div>
</div>

<?php if ($toShip): ?>
  <div class="panel" id="a-expedier">
    <h2 class="h2">Commandes payées à expédier</h2>
    <div class="table-wrap" style="padding: 0; border: 0;">
      <table class="table">
        <thead><tr><th>Commande</th><th>Client</th><th>Carte</th><th>Statut</th><th>Payée</th><th><span class="visually-hidden">Action</span></th></tr></thead>
        <tbody>
          <?php foreach ($toShip as $o): ?>
            <tr>
              <td><a href="/admin/commandes/<?= (int) $o['id'] ?>"><?= e($o['reference']) ?></a></td>
              <td><?= e($o['name']) ?><?= $o['company'] !== '' ? '<br><span class="muted small">' . e($o['company']) . '</span>' : '' ?></td>
              <td class="small"><?= e($o['card_type']) ?> × <?= (int) $o['quantity'] ?></td>
              <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(ORDER_STATUSES[$o['status']]) ?></span></td>
              <td class="small muted"><?= date_fr($o['paid_at']) ?></td>
              <td class="right"><form method="post" action="/admin/livraisons"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>"><button class="btn btn-light btn-sm" type="submit">Créer l'expédition</button></form></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<nav class="tabs" aria-label="Filtrer les expéditions">
  <?php foreach ($views as $k => $l): ?><a href="/admin/livraisons?vue=<?= $k ?>"<?= $vue === $k ? ' aria-current="true"' : '' ?>><?= e($l) ?> <span><?= $viewCounts[$k] ?></span></a><?php endforeach; ?>
</nav>

<div class="panel table-wrap">
  <?php if (!$shipments): ?>
    <p class="muted empty-row">Aucune expédition<?= $search !== '' ? ' pour cette recherche' : ' dans cette liste' ?>.</p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>Commande</th><th>Destination</th><th>Transporteur</th><th>Statut</th><th>Mise à jour</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
    <tbody>
      <?php foreach ($shipments as $s): $link = tracking_link($s); ?>
        <tr>
          <td class="strong"><?= e($s['reference']) ?><br><span class="muted small"><?= e($s['client_name']) ?> · <?= e($s['card_type']) ?> × <?= (int) $s['quantity'] ?></span></td>
          <td class="small"><?= $s['city'] !== '' ? e($s['city']) . ', ' . e($s['province']) : '<span class="muted">Adresse à compléter</span>' ?></td>
          <td class="small"><?= e(carrier_label($s['carrier'])) ?><?php if ($s['tracking_number']): ?><br><?php if ($link): ?><a href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer" class="mono"><?= e($s['tracking_number']) ?></a><?php else: ?><span class="mono"><?= e($s['tracking_number']) ?></span><?php endif; ?><?php endif; ?></td>
          <td><span class="ship-status ship-<?= e($s['status']) ?>"><?= e(SHIP_STATUSES[$s['status']]) ?></span><?= $s['estimated_at'] && !in_array($s['status'], ['livree', 'retournee'], true) ? '<br><span class="muted small">prévue le ' . date('d/m', strtotime($s['estimated_at'])) . '</span>' : '' ?></td>
          <td class="small muted"><?= date_fr($s['updated_at']) ?></td>
          <td class="right"><a href="/admin/livraisons/<?= (int) $s['id'] ?>">Ouvrir</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
