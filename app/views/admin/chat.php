<?php
$csrf = csrf_token();
$statusLabel = ['waiting' => 'En attente', 'active' => 'En cours', 'closed' => 'Terminée'];
$p = presence();
?>
<div class="page-head">
  <div>
    <h1>Clavardage du site</h1>
    <p class="muted">Les visiteurs vous écrivent depuis le site. Statut affiché : <strong><?= $p['online'] ? 'En ligne' : 'Absent' ?></strong> (<?= e($p['reason']) ?>)<?= $p['online'] ? '' : ' : les visiteurs laissent un message qui arrive dans Messages' ?>.</p>
  </div>
</div>

<div class="live-layout" data-live="agent" data-csrf="<?= e($csrf) ?>" data-session="<?= (int) ($current['id'] ?? 0) ?>"
     data-after="<?= $messages ? (int) end($messages)['id'] : 0 ?>" data-poll="/admin/chat/suivre" data-visitor="<?= e($current['visitor_name'] ?? '' ?: 'Visiteur') ?>">
  <nav class="live-list" aria-label="Discussions" data-sessions>
    <span class="live-list-head">Discussions (24 h)</span>
    <?php if (!$sessions): ?><p class="muted small" style="padding: 0 12px;" data-none>Aucune discussion pour l'instant.</p><?php endif; ?>
    <?php foreach ($sessions as $s): $sum = agent_session_summary($s); ?>
      <a href="/admin/chat?s=<?= $sum['id'] ?>" data-key="<?= $sum['id'] ?>" class="chat-item is-<?= e($sum['status']) ?>"<?= $current && (int) $current['id'] === $sum['id'] ? ' aria-current="page"' : '' ?>>
        <span class="chat-item-top"><strong><?= e($sum['label']) ?></strong><span class="muted small"><?= e($sum['time']) ?></span></span>
        <span class="chat-item-last muted small"><?= e($sum['last']) ?></span>
        <span class="chat-item-tags"><span class="chat-status"><?= e($statusLabel[$sum['status']]) ?></span><?php if ($sum['agent']): ?><span class="muted small"><?= e($sum['agent']) ?></span><?php endif; ?><span class="nav-count" data-count<?= $sum['unread'] ? '' : ' hidden' ?>><?= $sum['unread'] ?></span></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if (!$current): ?>
    <section class="live-main live-placeholder">
      <p class="muted">Choisissez une discussion à gauche. Les nouvelles discussions apparaissent en haut de la liste, sans recharger la page.</p>
    </section>
  <?php else: $closed = $current['status'] === 'closed'; ?>
    <section class="live-main" aria-label="Discussion avec <?= e($current['visitor_name'] ?: 'un visiteur') ?>">
      <header class="live-head">
        <div>
          <strong><?= e($current['visitor_name'] ?: 'Visiteur #' . $current['id']) ?></strong>
          <span class="muted small"><?= e($current['visitor_email'] ?: 'sans courriel') ?> · <?= $current['lang'] === 'en' ? 'anglais' : 'français' ?> · page <?= e($current['page']) ?></span>
          <span class="muted small" data-visitor-away<?= strtotime($current['last_visitor_at']) < time() - 60 || $closed ? '' : ' hidden' ?>>· le visiteur a peut-être quitté la page</span>
        </div>
        <?php if (!$closed): ?>
        <div class="row-actions">
        <form method="post" action="/admin/chat/<?= (int) $current['id'] ?>/action" class="row-actions">
          <?= csrf_field() ?>
          <?php if ((int) $current['assigned_to'] !== (int) $user['id']): ?><button class="btn btn-light btn-sm" name="action" value="take">Prendre en charge</button><?php endif; ?>
          <label class="visually-hidden" for="agent">Confier à</label>
          <select id="agent" name="agent" class="select-sm">
            <?php foreach ($agents as $a): ?><option value="<?= (int) $a['id'] ?>"<?= (int) $a['id'] === (int) $current['assigned_to'] ? ' selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?>
          </select>
          <button class="btn btn-light btn-sm" name="action" value="assign">Confier</button>
        </form>
        <form method="post" action="/admin/chat/<?= (int) $current['id'] ?>/action" data-confirm="Terminer la discussion ? Le visiteur ne pourra plus écrire dans celle-ci.">
          <?= csrf_field() ?>
          <button class="btn btn-text btn-sm" name="action" value="close">Terminer</button>
        </form>
        </div>
        <?php endif; ?>
      </header>
      <ol class="live-feed" data-feed aria-live="polite">
        <?php foreach ($messages as $m): ?>
          <li class="live-msg from-<?= e($m['sender']) ?><?= $m['sender'] === 'agent' ? ' is-mine' : '' ?>" data-id="<?= (int) $m['id'] ?>">
            <?php if ($m['sender'] !== 'system'): ?><span class="live-meta"><strong><?= e($m['sender'] === 'agent' ? ($m['agent_name'] ?? 'EliteTap') : ($current['visitor_name'] ?: 'Visiteur')) ?></strong> <span class="muted small"><?= date('H:i', strtotime($m['created_at'])) ?></span></span><?php endif; ?>
            <span class="live-body"><?= nl2br(e($m['body'])) ?></span>
          </li>
        <?php endforeach; ?>
      </ol>
      <?php if ($closed): ?>
        <p class="live-closed muted">Discussion terminée<?= $current['rating'] !== null ? ' · avis du visiteur : ' . ($current['rating'] ? 'utile' : 'pas utile') : '' ?>.
          <?php if ($current['thread_id']): ?><a href="/admin/messages/<?= (int) $current['thread_id'] ?>">Continuer par courriel</a><?php endif; ?></p>
      <?php else: ?>
        <form method="post" action="/admin/chat/<?= (int) $current['id'] ?>" class="live-compose" data-compose>
          <?= csrf_field() ?>
          <label class="visually-hidden" for="body">Réponse</label>
          <textarea id="body" name="body" rows="2" maxlength="2000" required placeholder="Répondre… (Entrée pour envoyer, Maj+Entrée pour aller à la ligne)"></textarea>
          <button class="btn btn-primary" type="submit">Envoyer</button>
        </form>
      <?php endif; ?>
    </section>
    <aside class="live-side">
      <div class="panel">
        <h2 class="h2">Visiteur</h2>
        <dl class="dl">
          <dt>Début</dt><dd><?= date_fr($current['created_at']) ?></dd>
          <dt>Courriel</dt><dd><?= e($current['visitor_email'] ?: '—') ?></dd>
          <?php if ($order): ?><dt>Commande</dt><dd><a href="/admin/commandes/<?= (int) $order['id'] ?>"><?= e($order['reference']) ?></a></dd><?php endif; ?>
          <?php if ($current['thread_id']): ?><dt>Courriels</dt><dd><a href="/admin/messages/<?= (int) $current['thread_id'] ?>">Conversation</a></dd><?php endif; ?>
          <?php if ($history): ?><dt>Déjà venu</dt><dd><?php foreach ($history as $h): ?><a href="/admin/chat?s=<?= (int) $h['id'] ?>"><?= date('d/m', strtotime($h['created_at'])) ?></a> <?php endforeach; ?></dd><?php endif; ?>
        </dl>
      </div>
    </aside>
  <?php endif; ?>
</div>
