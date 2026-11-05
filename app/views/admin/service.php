<?php
$fmtDelay = fn(?int $s) => $s === null ? '—' : ($s < 60 ? $s . ' s' : (int) round($s / 60) . ' min');
$pct = fn(int $a, int $b) => $b ? (int) round($a / $b * 100) . ' %' : '—';
?>
<div class="page-head">
  <div>
    <h1>Service client</h1>
    <p class="muted">Clavardage, messages et demandes à traiter. Vous êtes affiché <strong><?= $presence['online'] ? 'en ligne' : 'absent' ?></strong> (<?= e($presence['reason']) ?>).</p>
  </div>
  <div class="row-actions">
    <a class="btn btn-primary" href="/admin/chat">Ouvrir le clavardage</a>
    <a class="btn btn-light" href="/admin/messages">Messages</a>
  </div>
</div>

<?php if (!$chat['ready']): ?><p class="alert alert-error">Le clavardage a besoin de la migration 006 (phpMyAdmin).</p><?php endif; ?>

<div class="kpis kpis-5">
  <a class="kpi kpi-link<?= ($chat['waiting'] ?? 0) ? ' kpi-alert' : '' ?>" href="/admin/chat"><span>Clavardages en attente</span><strong><?= $chat['waiting'] ?? 0 ?></strong><em><?= $chat['active'] ?? 0 ?> en cours · <?= $chat['today'] ?? 0 ?> aujourd'hui</em></a>
  <div class="kpi"><span>Première réponse</span><strong><?= $fmtDelay($chat['median'] ?? null) ?></strong><em>médiane sur 7 jours · <?= $pct($chat['answered'] ?? 0, $chat['week'] ?? 0) ?> des discussions répondues</em></div>
  <div class="kpi"><span>Satisfaction</span><strong><?= $pct($chat['happy'] ?? 0, $chat['rated'] ?? 0) ?></strong><em><?= $chat['rated'] ?? 0 ?> avis sur 7 jours</em></div>
  <a class="kpi kpi-link<?= ($inbox['unread'] ?? 0) ? ' kpi-alert' : '' ?>" href="/admin/messages?vue=non-lues"><span>Messages non lus</span><strong><?= $inbox['unread'] ?? 0 ?></strong><em><?= $inbox['open'] ?? 0 ?> conversations ouvertes · <?= $inbox['week'] ?? 0 ?> reçus en 7 jours</em></a>
  <a class="kpi kpi-link" href="/admin/commandes?statut=devis"><span>Commandes à suivre</span><strong><?= $orders['service'] + $orders['unconfirmed'] ?></strong><em><?= $orders['service'] ?> à régler avec le service · <?= $orders['unconfirmed'] ?> non confirmées (48 h)</em></a>
</div>

<div class="dash-cols">
  <div class="panel">
    <h2 class="h2">File d'attente du clavardage</h2>
    <?php if (empty($chat['queue'])): ?>
      <p class="muted">Personne n'attend. Les nouvelles discussions sonnent dans l'onglet Clavardage.</p>
    <?php else: ?>
      <ul class="plain-list">
        <?php foreach ($chat['queue'] as $q): ?>
          <li><a href="/admin/chat?s=<?= (int) $q['id'] ?>"><strong><?= e($q['visitor_name'] ?: 'Visiteur #' . $q['id']) ?></strong></a>
            <span class="muted small">depuis <?= max(1, (int) round((time() - strtotime($q['created_at'])) / 60)) ?> min</span><br>
            <span class="small"><?= e(mb_strimwidth((string) $q['first_body'], 0, 120, '…')) ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <div class="panel">
    <h2 class="h2">Messages non lus</h2>
    <?php if (empty($inbox['latest'])): ?>
      <p class="muted">Tout est lu.</p>
    <?php else: ?>
      <ul class="plain-list">
        <?php foreach ($inbox['latest'] as $t): ?>
          <li><a href="/admin/messages/<?= (int) $t['id'] ?>"><strong><?= e($t['contact_name'] ?: $t['contact_email']) ?></strong> — <?= e($t['subject']) ?></a>
            <span class="muted small"><?= date_fr($t['last_message_at']) ?></span><br>
            <span class="small muted"><?= e(mb_strimwidth(preg_replace('/\s+/', ' ', (string) $t['last_body']), 0, 120, '…')) ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<div class="dash-cols">
  <div class="panel">
    <h2 class="h2">L'équipe du service client</h2>
    <ul class="plain-list">
      <?php foreach ($agents as $a): ?>
        <li><span class="member-dot<?= member_online($a) ? ' is-on' : '' ?>" aria-hidden="true"></span><strong><?= e($a['name']) ?></strong>
          <span class="muted small"><?= member_online($a) ? 'en ligne' : ($a['last_seen_at'] ? 'vu ' . date_fr($a['last_seen_at']) : 'pas encore connecté') ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php if (!empty($chat['byAgent'])): ?>
      <p class="muted small" style="margin-top: 12px;">Discussions prises en charge (7 jours) : <?= e(implode(' · ', array_map(fn($r) => $r['name'] . ' ' . $r['n'], $chat['byAgent']))) ?></p>
    <?php endif; ?>
  </div>
  <div class="panel">
    <h2 class="h2">Cette semaine</h2>
    <dl class="dl dl-wide">
      <dt>Discussions</dt><dd><?= $chat['week'] ?? 0 ?></dd>
      <dt>Nouvelles demandes</dt><dd><?= $orders['new'] ?> commande(s)</dd>
      <dt>En design / épreuve</dt><dd><?= $orders['design'] ?></dd>
      <?php if ($ship): ?><dt>Livraisons</dt><dd><a href="/admin/livraisons"><?= $ship['toShip'] ?> à expédier · <?= $ship['attention'] ?> à préparer ou en problème</a><?= $ship['problems'] ? ' <span class="status status-disabled">' . $ship['problems'] . ' problème(s)</span>' : '' ?></dd><?php endif; ?>
      <dt>Infolettre</dt><dd><?= $news === null ? '—' : $news . ' nouvel(s) abonné(s) confirmé(s)' ?></dd>
    </dl>
  </div>
</div>
