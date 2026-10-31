<?php
$max = max(1, ...array_values($daily ?: [0]));
$opens = $totals['view'] + $totals['tap'];
$fmt = lang() === 'en' ? 'm/d' : 'd/m';
?>
<div class="page-head">
  <div>
    <h1><?= te('Statistiques') ?></h1>
    <p class="muted"><?= te('Visiteurs anonymes : aucune donnée personnelle de vos contacts n\'est collectée.') ?></p>
  </div>
  <nav class="segmented" aria-label="<?= te('Période') ?>">
    <?php foreach ([7, 30, 90] as $d): ?>
      <a href="?jours=<?= $d ?>"<?= $days === $d ? ' aria-current="true"' : '' ?>><?= te('{n} jours', ['n' => $d]) ?></a>
    <?php endforeach; ?>
  </nav>
</div>

<div class="kpis">
  <div class="kpi"><span><?= te('Ouvertures du profil') ?></span><strong><?= $opens ?></strong></div>
  <div class="kpi"><span><?= te('dont par tap de carte') ?></span><strong><?= $totals['tap'] ?></strong></div>
  <div class="kpi"><span><?= te('Contacts enregistrés') ?></span><strong><?= $totals['vcard'] ?></strong></div>
  <div class="kpi"><span><?= te('Prospects') ?></span><strong><?= $totals['lead'] ?></strong></div>
  <div class="kpi"><span><?= te('Visiteurs uniques / jour (cumul)') ?></span><strong><?= $totals['visitors'] ?></strong></div>
</div>

<div class="panel">
  <h2 class="h2"><?= te('Ouvertures par jour') ?></h2>
  <?php if ($opens === 0): ?>
    <p class="muted"><?= te('Aucune ouverture sur la période. Tapez votre carte sur un téléphone pour tester.') ?></p>
  <?php else: ?>
  <div class="bars" role="img" aria-label="<?= te('Ouvertures du profil par jour sur {n} jours, maximum {max} par jour', ['n' => $days, 'max' => $max]) ?>">
    <?php foreach ($daily as $d => $n): ?>
      <div class="bar" style="height: <?= round($n / $max * 100, 1) ?>%" title="<?= date($fmt, strtotime($d)) ?> : <?= $n ?>"></div>
    <?php endforeach; ?>
  </div>
  <div class="bars-axis"><span><?= date($fmt, strtotime(array_key_first($daily))) ?></span><span><?= te('Max {n} / jour', ['n' => $max]) ?></span><span><?= date($fmt, strtotime(array_key_last($daily))) ?></span></div>
  <?php endif; ?>
</div>

<div class="panel">
  <h2 class="h2"><?= te('Liens les plus cliqués') ?></h2>
  <?php if (!$topLinks): ?>
    <p class="muted"><?= te('Aucun clic sur la période.') ?></p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th><?= te('Lien') ?></th><th class="num"><?= te('Clics') ?></th></tr></thead>
      <tbody>
        <?php foreach ($topLinks as $l): ?><tr><td><?= e($l['label']) ?></td><td class="num"><?= (int) $l['n'] ?></td></tr><?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
