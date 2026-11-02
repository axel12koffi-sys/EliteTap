<?php
$fmtPct = fn(?float $p) => $p === null ? '—' : number_format($p, $p == floor($p) ? 0 : 1, ',', ' ') . ' %';
$months = ['01' => 'janv.', '02' => 'févr.', '03' => 'mars', '04' => 'avr.', '05' => 'mai', '06' => 'juin', '07' => 'juil.', '08' => 'août', '09' => 'sept.', '10' => 'oct.', '11' => 'nov.', '12' => 'déc.'];
$monthLabel = fn(string $ym) => $months[substr($ym, 5, 2)] . ' ' . substr($ym, 2, 2);
$revMax = max(1, ...array_values($revenue));
$tapMax = max(1, ...array_values($taps));
$tapTotal = array_sum($taps);
$funMax = max(1, ...array_values($funnel));
$todoItems = array_filter([
    $todo['service'] ? [$todo['service'] . ' commande(s) à régler avec le service client', '/admin/commandes?statut=devis'] : null,
    $todo['paid_no_card'] ? [$todo['paid_no_card'] . ' commande(s) payée(s) sans carte liée', '/admin/commandes?statut=design'] : null,
    $todo['unconfirmed'] ? [$todo['unconfirmed'] . ' demande(s) non confirmée(s) depuis plus de 48 h', '/admin/commandes?statut=devis'] : null,
    $todo['mail_failed'] ? [$todo['mail_failed'] . ' courriel(s) en échec cette semaine', '/admin/courriels'] : null,
]);
?>
<div class="page-head">
  <div>
    <h1>Tableau de bord</h1>
    <p class="muted">Les indicateurs de succès du plan d'évolution, calculés à partir des données de la plateforme.</p>
  </div>
</div>

<?php if ($todoItems): ?>
  <div class="panel todo">
    <h2 class="h2">À traiter</h2>
    <ul>
      <?php foreach ($todoItems as [$label, $href]): ?><li><a href="<?= e($href) ?>"><?= e($label) ?></a></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="kpis kpis-5">
  <div class="kpi">
    <span>Taux d'activation</span><strong><?= $fmtPct($activation['pct']) ?></strong>
    <em><?= $activation['total'] ? 'cartes touchées dans leurs 30 premiers jours (' . $activation['total'] . ' cartes)' : 'aucune carte activée depuis 30 jours' ?></em>
  </div>
  <div class="kpi">
    <span>Conversion Pro</span><strong><?= $fmtPct($conversion['pct']) ?></strong>
    <em><?= $conversion['paid'] ?> client(s) Pro ou Entreprise sur <?= $conversion['clients'] ?></em>
  </div>
  <div class="kpi">
    <span>Revenu récurrent (MRR)</span><strong><?= $mrr === null ? '—' : money($mrr) ?></strong>
    <em><?= $proPrice === null ? '<a href="/admin/reglages">Indiquez le prix du Pro</a>' : $proCount . ' abonné(s) Pro × ' . money((int) $proPrice) ?></em>
  </div>
  <div class="kpi">
    <span>Rétention à 6 mois</span><strong><?= $fmtPct($retention[6]['pct']) ?></strong>
    <em><?= $retention[6]['total'] ? 'à 12 mois : ' . $fmtPct($retention[12]['pct']) : 'pas encore d\'abonné de plus de 6 mois' ?></em>
  </div>
  <div class="kpi">
    <span>Prospects générés</span><strong><?= $leads['d30'] ?></strong>
    <em>sur 30 jours · <?= $leads['perCard'] === null ? '—' : number_format($leads['perCard'], 1, ',', ' ') ?> par carte active au total</em>
  </div>
</div>

<div class="dash-cols">
  <figure class="panel chart">
    <figcaption>
      <h2 class="h2">Chiffre d'affaires des commandes</h2>
      <p class="muted small">Commandes payées par mois, taxes incluses · ce mois-ci : <strong><?= money($thisMonth) ?></strong></p>
    </figcaption>
    <div class="vbars" role="img" aria-label="Chiffre d'affaires par mois sur 12 mois, maximum <?= e(money($revMax)) ?>">
      <?php foreach ($revenue as $m => $c): ?>
        <div class="vbar" tabindex="0" data-tip="<?= e($monthLabel($m) . ' : ' . money($c)) ?>"><i style="height: <?= $c ? max(2, round($c / $revMax * 100, 1)) : 0 ?>%"></i></div>
      <?php endforeach; ?>
    </div>
    <div class="vbar-axis"><?php foreach ($revenue as $m => $c): ?><span><?= e(substr($m, 5, 2) === '01' ? $monthLabel($m) : $months[substr($m, 5, 2)]) ?></span><?php endforeach; ?></div>
    <details class="data-table"><summary>Voir les données</summary>
      <table class="table"><thead><tr><th>Mois</th><th class="num">Montant</th></tr></thead><tbody>
        <?php foreach ($revenue as $m => $c): ?><tr><td><?= e($monthLabel($m)) ?></td><td class="num"><?= money($c) ?></td></tr><?php endforeach; ?>
      </tbody></table>
    </details>
  </figure>

  <figure class="panel chart">
    <figcaption>
      <h2 class="h2">Entonnoir des commandes</h2>
      <p class="muted small">Demandes des 90 derniers jours</p>
    </figcaption>
    <div class="hbars">
      <?php $first = max(1, reset($funnel)); foreach ($funnel as $label => $n): ?>
        <div class="hbar">
          <span class="hbar-label"><?= e($label) ?></span>
          <span class="hbar-track"><i style="width: <?= $n ? max(1, round($n / $funMax * 100, 1)) : 0 ?>%"></i></span>
          <span class="hbar-value"><?= $n ?><small><?= $label === 'Demandes reçues' ? '' : ' · ' . $fmtPct(pct($n, $first)) ?></small></span>
        </div>
      <?php endforeach; ?>
    </div>
  </figure>
</div>

<figure class="panel chart">
  <figcaption>
    <h2 class="h2">Touchers de cartes</h2>
    <p class="muted small">Toutes cartes confondues, 30 derniers jours · total <strong><?= $tapTotal ?></strong></p>
  </figcaption>
  <?php if (!$tapTotal): ?>
    <p class="muted">Aucun toucher sur la période.</p>
  <?php else: ?>
  <div class="vbars vbars-dense" role="img" aria-label="Touchers par jour sur 30 jours, maximum <?= $tapMax ?> par jour">
    <?php foreach ($taps as $d => $n): ?>
      <div class="vbar" tabindex="0" data-tip="<?= e(date('d/m', strtotime($d)) . ' : ' . $n . ' toucher(s)') ?>"><i style="height: <?= $n ? max(2, round($n / $tapMax * 100, 1)) : 0 ?>%"></i></div>
    <?php endforeach; ?>
  </div>
  <div class="vbar-axis vbar-axis-ends"><span><?= date('d/m', strtotime(array_key_first($taps))) ?></span><span>max <?= $tapMax ?> / jour</span><span><?= date('d/m', strtotime(array_key_last($taps))) ?></span></div>
  <details class="data-table"><summary>Voir les données</summary>
    <table class="table"><thead><tr><th>Jour</th><th class="num">Touchers</th></tr></thead><tbody>
      <?php foreach ($taps as $d => $n): ?><tr><td><?= date('d/m/Y', strtotime($d)) ?></td><td class="num"><?= $n ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </details>
  <?php endif; ?>
</figure>

<p class="muted small">Définitions (plan d'évolution) : <strong>activation</strong> = part des cartes activées depuis 30 jours ou plus, touchées au moins une fois dans leurs 30 premiers jours · <strong>conversion</strong> = clients actifs en Pro ou Entreprise · <strong>MRR</strong> = abonnés Pro × prix mensuel du Pro · <strong>rétention</strong> = forfaits payants commencés il y a 6 (ou 12) mois et toujours payants.</p>
