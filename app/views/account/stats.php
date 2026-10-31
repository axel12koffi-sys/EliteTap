<?php
$max = max(1, max($daily));
$opens = $totals['view'] + $totals['tap'];
?>
<div class="page-head">
  <div>
    <h1>Statistiques</h1>
    <p class="muted">Visiteurs anonymes : aucune donnée personnelle de vos contacts n'est collectée.</p>
  </div>
  <nav class="segmented" aria-label="Période">
    <?php foreach ([7 => '7 jours', 30 => '30 jours', 90 => '90 jours'] as $d => $label): ?>
      <a href="?jours=<?= $d ?>"<?= $days === $d ? ' aria-current="true"' : '' ?>><?= $label ?></a>
    <?php endforeach; ?>
  </nav>
</div>

<div class="kpis">
  <div class="kpi"><span>Ouvertures du profil</span><strong><?= $opens ?></strong></div>
  <div class="kpi"><span>dont par tap de carte</span><strong><?= $totals['tap'] ?></strong></div>
  <div class="kpi"><span>Contacts enregistrés</span><strong><?= $totals['vcard'] ?></strong></div>
  <div class="kpi"><span>Visiteurs uniques / jour (cumul)</span><strong><?= $totals['visitors'] ?></strong></div>
</div>

<div class="panel">
  <h2 class="h2">Ouvertures par jour</h2>
  <?php if ($opens === 0): ?>
    <p class="muted">Aucune ouverture sur la période. Tapez votre carte sur un téléphone pour tester.</p>
  <?php else: ?>
  <div class="bars" role="img" aria-label="Ouvertures du profil par jour sur <?= $days ?> jours, maximum <?= $max ?> par jour">
    <?php foreach ($daily as $d => $n): ?>
      <div class="bar" style="height: <?= round($n / $max * 100, 1) ?>%" title="<?= date('d/m', strtotime($d)) ?> : <?= $n ?>"></div>
    <?php endforeach; ?>
  </div>
  <div class="bars-axis"><span><?= date('d/m', strtotime(array_key_first($daily))) ?></span><span>Max <?= $max ?> / jour</span><span><?= date('d/m', strtotime(array_key_last($daily))) ?></span></div>
  <?php endif; ?>
</div>

<div class="panel">
  <h2 class="h2">Liens les plus cliqués</h2>
  <?php if (!$topLinks): ?>
    <p class="muted">Aucun clic sur la période.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Lien</th><th class="num">Clics</th></tr></thead>
      <tbody>
        <?php foreach ($topLinks as $l): ?><tr><td><?= e($l['label']) ?></td><td class="num"><?= (int) $l['n'] ?></td></tr><?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
