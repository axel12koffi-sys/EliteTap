<?php
$cur = $channels[$current];
$csrf = csrf_token();
?>
<div class="page-head">
  <div>
    <h1>Discussion d'équipe</h1>
    <p class="muted">Messages internes : visibles seulement par l'équipe EliteTap.</p>
  </div>
</div>

<div class="live-layout" data-live="team" data-channel="<?= e($current) ?>" data-csrf="<?= e($csrf) ?>"
     data-after="<?= $messages ? (int) end($messages)['id'] : 0 ?>" data-poll="/admin/equipe/chat/suivre" data-send="/admin/equipe/chat">
  <nav class="live-list" aria-label="Canaux">
    <span class="live-list-head">Canaux</span>
    <?php foreach ($channels as $k => $c): if ($c['type'] !== 'channel') continue; ?>
      <a href="/admin/equipe/chat?c=<?= rawurlencode($k) ?>" data-key="<?= e($k) ?>"<?= $k === $current ? ' aria-current="page"' : '' ?>>
        <span># <?= e($c['label']) ?></span><span class="nav-count" data-count<?= empty($unread[$k]) ? ' hidden' : '' ?>><?= (int) ($unread[$k] ?? 0) ?></span></a>
    <?php endforeach; ?>
    <span class="live-list-head">Messages directs</span>
    <?php foreach ($channels as $k => $c): if ($c['type'] !== 'dm') continue; ?>
      <a href="/admin/equipe/chat?c=<?= rawurlencode($k) ?>" data-key="<?= e($k) ?>"<?= $k === $current ? ' aria-current="page"' : '' ?>>
        <span><span class="member-dot<?= member_online($c['member']) ? ' is-on' : '' ?>" data-member="<?= (int) $c['member']['id'] ?>"></span><?= e($c['label']) ?></span>
        <span class="nav-count" data-count<?= empty($unread[$k]) ? ' hidden' : '' ?>><?= (int) ($unread[$k] ?? 0) ?></span></a>
    <?php endforeach; ?>
    <?php if (!array_filter($channels, fn($c) => $c['type'] === 'dm')): ?><p class="muted small" style="padding: 0 12px;">Ajoutez des employés pour leur écrire directement.</p><?php endif; ?>
  </nav>

  <section class="live-main" aria-label="<?= e($cur['type'] === 'dm' ? 'Messages avec ' . $cur['label'] : 'Canal ' . $cur['label']) ?>">
    <header class="live-head"><strong><?= $cur['type'] === 'dm' ? e($cur['label']) : '# ' . e($cur['label']) ?></strong>
      <span class="muted small"><?= $cur['type'] === 'dm' ? e(services_label($cur['member'])) : ($current === 'general' ? 'Toute l\'équipe' : 'Membres du service et administrateurs') ?></span></header>
    <ol class="live-feed" data-feed aria-live="polite">
      <?php if (!$messages): ?><li class="live-empty muted" data-empty>Aucun message pour l'instant. Lancez la conversation !</li><?php endif; ?>
      <?php foreach ($messages as $m): $mine = (int) $m['user_id'] === (int) $user['id']; ?>
        <li class="live-msg<?= $mine ? ' is-mine' : '' ?>" data-id="<?= (int) $m['id'] ?>">
          <span class="live-meta"><strong><?= e($mine ? 'Vous' : ($m['name'] ?? '?')) ?></strong> <span class="muted small" title="<?= e(date_fr($m['created_at'])) ?>"><?= date('H:i', strtotime($m['created_at'])) ?></span></span>
          <span class="live-body"><?= nl2br(e($m['body'])) ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
    <form method="post" action="/admin/equipe/chat" class="live-compose" data-compose>
      <?= csrf_field() ?>
      <input type="hidden" name="c" value="<?= e($current) ?>">
      <label class="visually-hidden" for="body">Message</label>
      <textarea id="body" name="body" rows="2" maxlength="4000" required placeholder="Écrire à <?= e($cur['type'] === 'dm' ? $cur['label'] : '#' . $cur['label']) ?>… (Entrée pour envoyer)"></textarea>
      <button class="btn btn-primary" type="submit">Envoyer</button>
    </form>
  </section>
</div>
