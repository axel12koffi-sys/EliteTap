<?php
$presets = ['mois' => 'Ce mois-ci', 'mois-dernier' => 'Mois dernier', 'trimestre' => 'Ce trimestre', 'annee' => 'Cette année', 'annee-derniere' => 'Année dernière'];
$months = $months ?? [];
$mMax = max(1, ...array_values($months));
$mNames = ['01' => 'janv.', '02' => 'févr.', '03' => 'mars', '04' => 'avr.', '05' => 'mai', '06' => 'juin', '07' => 'juil.', '08' => 'août', '09' => 'sept.', '10' => 'oct.', '11' => 'nov.', '12' => 'déc.'];
$ml = fn(string $ym) => $mNames[substr($ym, 5, 2)] . ' ' . substr($ym, 0, 4);
$axis = fn(string $ym) => $mNames[substr($ym, 5, 2)] . (substr($ym, 5, 2) === '01' ? ' ' . substr($ym, 2, 2) : '');
$qs = http_build_query(['p' => $preset, 'du' => $from, 'au' => $to]);
$isQc = abs(tax_rate() - 14.975) < 0.001;
$recvTotal = array_sum(array_map(fn($o) => (int) $o['amount_cents'], $receivable));
?>
<div class="page-head">
  <div>
    <h1>Comptabilité</h1>
    <p class="muted">Ventes encaissées du <strong><?= date('d/m/Y', strtotime($from)) ?></strong> au <strong><?= date('d/m/Y', strtotime($to)) ?></strong>, selon la date de paiement.</p>
  </div>
  <a class="btn btn-primary" href="/admin/comptabilite/export.csv?<?= e($qs) ?>">Exporter (CSV)</a>
</div>

<div class="period-bar">
  <nav class="tabs" aria-label="Période">
    <?php foreach ($presets as $k => $l): ?><a href="/admin/comptabilite?p=<?= $k ?>"<?= $preset === $k ? ' aria-current="true"' : '' ?>><?= e($l) ?></a><?php endforeach; ?>
  </nav>
  <form method="get" action="/admin/comptabilite" class="search period-form">
    <input type="hidden" name="p" value="perso">
    <label>Du <input type="date" name="du" value="<?= e($from) ?>"></label>
    <label>au <input type="date" name="au" value="<?= e($to) ?>"></label>
    <button class="btn btn-light" type="submit">Afficher</button>
  </form>
</div>

<div class="kpis kpis-5">
  <div class="kpi"><span>Ventes (avant taxes)</span><strong><?= money($tot['sub']) ?></strong><em><?= $tot['count'] ?> commande(s) payée(s)</em></div>
  <?php if ($isQc): ?>
    <div class="kpi"><span>TPS perçue (5 %)</span><strong><?= money($tot['tps']) ?></strong><em>à remettre</em></div>
    <div class="kpi"><span>TVQ perçue (9,975 %)</span><strong><?= money($tot['tvq']) ?></strong><em>à remettre</em></div>
  <?php else: ?>
    <div class="kpi"><span>Taxes perçues</span><strong><?= money($tot['tax']) ?></strong><em>taux <?= e(rtrim(rtrim(number_format(tax_rate(), 3, ',', ''), '0'), ',')) ?> %</em></div>
  <?php endif; ?>
  <div class="kpi"><span>Total encaissé</span><strong><?= money($tot['total']) ?></strong><em>taxes incluses</em></div>
  <div class="kpi<?= $receivable ? ' kpi-alert' : '' ?>"><span>À recevoir</span><strong><?= money($recvTotal) ?></strong><em><?= count($receivable) ?> commande(s) non payée(s)</em></div>
</div>
<?php if ($tot['estimated']): ?><p class="muted small"><?= $tot['estimated'] ?> commande(s) antérieure(s) au détail des taxes : sous-total et taxes calculés à partir du total payé (colonne « estimé » de l'export).</p><?php endif; ?>

<div class="dash-cols">
  <figure class="panel chart">
    <figcaption>
      <h2 class="h2">Ventes avant taxes, 12 mois</h2>
      <p class="muted small">Jusqu'à <?= e($ml(array_key_last($months))) ?></p>
    </figcaption>
    <div class="vbars" role="img" aria-label="Ventes avant taxes par mois sur 12 mois, maximum <?= e(money($mMax)) ?>">
      <?php foreach ($months as $m => $c): ?><div class="vbar" tabindex="0" data-tip="<?= e($ml($m) . ' : ' . money($c)) ?>"><i style="height: <?= $c ? max(2, round($c / $mMax * 100, 1)) : 0 ?>%"></i></div><?php endforeach; ?>
    </div>
    <div class="vbar-axis"><?php foreach ($months as $m => $c): ?><span><?= e($axis($m)) ?></span><?php endforeach; ?></div>
    <details class="data-table"><summary>Voir les données</summary>
      <table class="table"><thead><tr><th>Mois</th><th class="num">Ventes avant taxes</th></tr></thead><tbody>
        <?php foreach ($months as $m => $c): ?><tr><td><?= e($ml($m)) ?></td><td class="num"><?= money($c) ?></td></tr><?php endforeach; ?>
      </tbody></table>
    </details>
  </figure>
  <div class="panel">
    <h2 class="h2">Répartition de la période</h2>
    <h3 class="h3">Par moyen de paiement (taxes incluses)</h3>
    <?php if (!$byMethod): ?><p class="muted small">Aucune vente.</p><?php endif; ?>
    <div class="hbars"><?php $bm = max(1, ...array_values($byMethod ?: [0])); foreach ($byMethod as $l => $c): ?>
      <div class="hbar"><span class="hbar-label"><?= e($l) ?></span><span class="hbar-track"><i style="width: <?= round($c / $bm * 100, 1) ?>%"></i></span><span class="hbar-value"><?= money($c) ?></span></div>
    <?php endforeach; ?></div>
    <h3 class="h3" style="margin-top: 18px;">Par type de carte (avant taxes)</h3>
    <div class="hbars"><?php $bc = max(1, ...array_values($byCard ?: [0])); foreach ($byCard as $l => $c): ?>
      <div class="hbar"><span class="hbar-label"><?= e($l) ?></span><span class="hbar-track"><i style="width: <?= round($c / $bc * 100, 1) ?>%"></i></span><span class="hbar-value"><?= money($c) ?></span></div>
    <?php endforeach; ?></div>
    <p class="muted small" style="margin-top: 18px;">Abonnements Pro : <?= $proCount ?> actif(s)<?= $mrr !== null ? ' · revenu récurrent estimé ' . money($mrr) . ' / mois (facturé par Stripe, non inclus ci-dessus)' : '' ?>.</p>
  </div>
</div>

<div class="panel table-wrap">
  <h2 class="h2" style="padding: 20px 20px 0;">Ventes de la période</h2>
  <?php if (!$orders): ?>
    <p class="muted empty-row">Aucune vente payée sur cette période.</p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>Payée le</th><th>Commande</th><th>Client</th><th class="num">Sous-total</th><?php if ($isQc): ?><th class="num">TPS</th><th class="num">TVQ</th><?php else: ?><th class="num">Taxes</th><?php endif; ?><th class="num">Total</th><th>Moyen</th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): $s = $o['split']; ?>
        <tr>
          <td class="small"><?= date('d/m/Y', strtotime($o['paid_at'])) ?></td>
          <td><a href="/admin/commandes/<?= (int) $o['id'] ?>"><?= e($o['reference']) ?></a><br><span class="muted small"><?= e($o['card_type']) ?> × <?= (int) $o['quantity'] ?></span></td>
          <td><?= e($o['name']) ?><?= $o['company'] !== '' ? '<br><span class="muted small">' . e($o['company']) . '</span>' : '' ?></td>
          <td class="num"><?= money($s['sub']) ?><?= $s['estimated'] ? '<br><span class="muted small">estimé</span>' : '' ?></td>
          <?php if ($isQc): ?><td class="num"><?= money($s['tps']) ?></td><td class="num"><?= money($s['tvq']) ?></td><?php else: ?><td class="num"><?= money($s['tax']) ?></td><?php endif; ?>
          <td class="num strong"><?= money($s['total']) ?></td>
          <td class="small"><?= e(payment_label($o)) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot><tr><th colspan="3">Total</th><th class="num"><?= money($tot['sub']) ?></th><?php if ($isQc): ?><th class="num"><?= money($tot['tps']) ?></th><th class="num"><?= money($tot['tvq']) ?></th><?php else: ?><th class="num"><?= money($tot['tax']) ?></th><?php endif; ?><th class="num"><?= money($tot['total']) ?></th><th></th></tr></tfoot>
  </table>
  <?php endif; ?>
</div>

<div class="panel table-wrap">
  <h2 class="h2" style="padding: 20px 20px 0;">À recevoir</h2>
  <?php if (!$receivable): ?>
    <p class="muted empty-row">Rien en attente de paiement.</p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>Commande</th><th>Client</th><th>Depuis</th><th class="num">Montant</th><th>Situation</th></tr></thead>
    <tbody>
      <?php foreach ($receivable as $o): ?>
        <tr>
          <td><a href="/admin/commandes/<?= (int) $o['id'] ?>"><?= e($o['reference']) ?></a></td>
          <td><?= e($o['name']) ?><br><span class="muted small"><?= e($o['email']) ?></span></td>
          <td class="small"><?= date_fr($o['created_at']) ?></td>
          <td class="num strong"><?= money((int) $o['amount_cents']) ?></td>
          <td class="small"><?= $o['payment_method'] === 'service' ? 'Paiement avec le service client' : 'Lien de paiement envoyé' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<p class="muted small">Ce tableau aide au suivi ; il ne remplace pas la tenue de livres de votre comptable. Les frais Stripe et les remboursements se consultent dans le tableau de bord Stripe.</p>
