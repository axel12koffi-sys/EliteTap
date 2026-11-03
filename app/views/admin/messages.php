<?php $p = presence(); ?>
<div class="page-head">
  <div>
    <h1>Messages</h1>
    <p class="muted">Courriels reçus sur <?= e(config('mail')['from']) ?> et demandes du site. Vos réponses partent de cette adresse.</p>
  </div>
  <div class="row-actions">
    <a class="btn btn-light" href="/admin/messages/reglages">Présence et réponses auto</a>
    <a class="btn btn-primary" href="/admin/messages/nouveau">Nouveau message</a>
  </div>
</div>

<div class="presence-bar <?= $p['online'] ? 'is-online' : 'is-away' ?>">
  <span class="presence-dot" aria-hidden="true"></span>
  <span><strong><?= $p['online'] ? 'En ligne' : 'Absent' ?></strong> · <?= e($p['reason']) ?> ·
    réponse automatique <?= setting('autoreply_enabled', '1') === '1' ? '« ' . ($p['online'] ? 'en ligne' : 'absent') . ' » envoyée aux nouveaux messages' : 'désactivée' ?></span>
</div>

<?php if (!$lastIn): ?>
  <div class="panel todo">
    <h2 class="h2">Recevoir les courriels de info@ ici</h2>
    <p class="muted">Aucun courriel reçu pour l'instant. Dans cPanel › <strong>Redirecteurs</strong> › Ajouter un redirecteur : adresse <span class="mono">info</span>, puis « Diriger vers un programme » :
      <span class="mono">repositories/EliteTap/bin/inbound-mail.php</span>. Les courriels restent aussi dans le webmail. Les demandes du formulaire du site arrivent déjà ici.</p>
  </div>
<?php endif; ?>

<div class="page-head" style="align-items: center;">
  <nav class="tabs" aria-label="Filtrer les conversations">
    <?php foreach (THREAD_VIEWS as $k => $label): ?>
      <a href="/admin/messages?vue=<?= $k ?>"<?= $vue === $k ? ' aria-current="true"' : '' ?>><?= e($label) ?> <span><?= $counts[$k] ?></span></a>
    <?php endforeach; ?>
  </nav>
  <form method="get" action="/admin/messages" class="search">
    <input type="hidden" name="vue" value="<?= e($vue) ?>">
    <label class="visually-hidden" for="q">Rechercher</label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Nom, courriel, sujet, texte">
    <button class="btn btn-light" type="submit">Rechercher</button>
  </form>
</div>

<div class="panel thread-list">
  <?php if (!$threads): ?>
    <p class="muted empty-row">Aucune conversation<?= $search !== '' ? ' pour cette recherche' : '' ?>.</p>
  <?php else: foreach ($threads as $t): ?>
    <a class="thread-row<?= $t['unread'] ? ' is-unread' : '' ?>" href="/admin/messages/<?= (int) $t['id'] ?>">
      <span class="thread-who">
        <?php if ($t['unread']): ?><span class="unread-dot" aria-label="Non lu"></span><?php endif; ?>
        <?= e($t['contact_name'] ?: $t['contact_email']) ?>
        <?php if ($t['contact_name']): ?><span class="muted small"><?= e($t['contact_email']) ?></span><?php endif; ?>
      </span>
      <span class="thread-subject">
        <?= e($t['subject']) ?><?php if ($t['reference']): ?> <span class="badge badge-design"><?= e($t['reference']) ?></span><?php endif; ?>
        <span class="muted"> — <?= $t['last_dir'] === 'out' ? ($t['last_kind'] === 'auto' ? 'Réponse auto : ' : 'Vous : ') : '' ?><?= e(mb_strimwidth(preg_replace('/\s+/', ' ', (string) $t['last_body']), 0, 110, '…')) ?></span>
      </span>
      <span class="thread-date small muted"><?= date_fr($t['last_message_at']) ?></span>
    </a>
  <?php endforeach; endif; ?>
</div>
