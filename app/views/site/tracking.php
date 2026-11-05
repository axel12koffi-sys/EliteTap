<?php
$link = tracking_link($s);
$steps = ['a_preparer', 'expediee', 'en_transit', 'livree'];
$rank = ['a_preparer' => 0, 'preparee' => 0, 'expediee' => 1, 'en_transit' => 2, 'livree' => 3, 'probleme' => 2, 'retournee' => 2];
$cur = $rank[$s['status']] ?? 0;
$dates = [];
foreach ($events as $ev) {
    $dates[$rank[$ev['status']] ?? 0] ??= $ev['created_at'];
}
$problem = in_array($s['status'], ['probleme', 'retournee'], true);
?>
<section class="section container narrow-page tracking">
  <p class="eyebrow"><?= te('Suivi de commande') ?> · <?= e($o['reference']) ?></p>
  <h1 class="h2 h2-xl"><?= e(ship_status_public($s['status'])) ?></h1>
  <?php if ($s['estimated_at'] && !in_array($s['status'], ['livree', 'retournee'], true)): ?>
    <p class="lead"><?= te('Livraison prévue le {date}.', ['date' => date('d/m/Y', strtotime($s['estimated_at']))]) ?></p>
  <?php endif; ?>
  <?php if ($problem): ?><p class="alert alert-error"><?= te('Votre livraison a pris du retard ou rencontre un problème. Notre équipe s\'en occupe et vous tient au courant.') ?></p><?php endif; ?>

  <ol class="track-steps">
    <?php foreach ($steps as $i => $k): ?>
      <li class="<?= $i <= $cur ? 'is-done' : '' ?><?= $i === $cur ? ' is-current' : '' ?>">
        <span class="track-dot" aria-hidden="true"></span>
        <span><strong><?= e(ship_status_public($k)) ?></strong><?php if (isset($dates[$i]) && $i <= $cur): ?><br><span class="muted"><?= date('d/m/Y', strtotime($dates[$i])) ?></span><?php endif; ?></span>
      </li>
    <?php endforeach; ?>
  </ol>

  <dl class="track-info">
    <dt><?= te('Transporteur') ?></dt><dd><?= e(t(carrier_label($s['carrier']))) ?></dd>
    <?php if ($s['tracking_number']): ?><dt><?= te('Numéro de suivi') ?></dt><dd class="mono"><?= e($s['tracking_number']) ?></dd><?php endif; ?>
    <?php if ($s['packages'] > 1): ?><dt><?= te('Colis') ?></dt><dd><?= (int) $s['packages'] ?></dd><?php endif; ?>
  </dl>
  <div class="track-actions">
    <?php if ($link): ?><a class="btn btn-primary" href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer"><?= te('Suivre sur le site du transporteur') ?></a><?php endif; ?>
    <a class="btn btn-outline" href="https://wa.me/<?= e(config('whatsapp')) ?>" rel="noopener"><?= te('Une question ? WhatsApp') ?></a>
  </div>
</section>
