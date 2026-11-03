<?php
$kinds = ['form' => 'Formulaire du site', 'auto' => 'Réponse automatique', 'system' => 'Courriel automatique de la plateforme'];
$fmtSize = fn(int $b) => $b > 1048576 ? number_format($b / 1048576, 1, ',', ' ') . ' Mo' : max(1, (int) round($b / 1024)) . ' Ko';
$name = $t['contact_name'] ?: $t['contact_email'];
?>
<p class="crumb"><a href="/admin/messages">Messages</a> › <?= e($name) ?></p>
<div class="page-head">
  <div>
    <h1><?= e($t['subject']) ?></h1>
    <p class="muted"><?= e($name) ?> · <?= e($t['contact_email']) ?><?= $t['status'] === 'archived' ? ' · <strong>Archivée</strong>' : ($t['status'] === 'spam' ? ' · <strong>Robots et listes</strong>' : '') ?></p>
  </div>
  <form method="post" action="/admin/messages/<?= (int) $t['id'] ?>/action" class="row-actions">
    <?= csrf_field() ?>
    <?php if ($t['status'] === 'open'): ?>
      <button class="btn btn-light" name="action" value="archive">Archiver</button>
      <button class="btn btn-light" name="action" value="unread">Marquer non lu</button>
      <button class="btn btn-text" name="action" value="spam">Robot / liste</button>
    <?php elseif ($t['status'] === 'spam'): ?>
      <button class="btn btn-light" name="action" value="notspam">Ce n'est pas un robot</button>
    <?php else: ?>
      <button class="btn btn-light" name="action" value="reopen">Rouvrir</button>
    <?php endif; ?>
  </form>
</div>

<div class="cols">
  <div class="col-main">
    <ol class="convo">
      <?php foreach ($messages as $m): $out = $m['direction'] === 'out'; $files = json_decode((string) $m['attachments'], true) ?: []; ?>
        <li class="bubble <?= $out ? 'bubble-out' : 'bubble-in' ?> bubble-<?= e($m['kind']) ?>">
          <div class="bubble-head">
            <strong><?= $out ? e($m['kind'] === 'mail' ? ($m['sender_name'] ?: 'EliteTap') : 'EliteTap') : e($m['from_name'] ?: $m['from_email']) ?></strong>
            <?php if (isset($kinds[$m['kind']])): ?><span class="tag-kind"><?= e($kinds[$m['kind']]) ?></span><?php endif; ?>
            <?php if ($m['status'] === 'failed'): ?><span class="status status-disabled">Échec de l'envoi</span><?php endif; ?>
            <span class="muted small"><?= date_fr($m['created_at']) ?></span>
          </div>
          <?php if ($m['subject'] !== '' && base_subject($m['subject']) !== base_subject($t['subject'])): ?><p class="small muted">Sujet : <?= e($m['subject']) ?></p><?php endif; ?>
          <div class="bubble-body"><?= nl2br(e($m['body'])) ?></div>
          <?php if ($m['body_full']): ?>
            <details class="bubble-full"><summary>Message complet avec l'historique</summary><div><?= nl2br(e($m['body_full'])) ?></div></details>
          <?php endif; ?>
          <?php if ($files): ?>
            <ul class="files">
              <?php foreach ($files as $i => $f): $viewable = preg_match('#^image/(png|jpe?g|gif|webp)$|^application/pdf$#', $f['type'] ?? ''); ?>
                <li>📎 <a href="/admin/messages/piece/<?= (int) $m['id'] ?>/<?= $i ?><?= $viewable ? '?voir=1' : '' ?>"<?= $viewable ? ' target="_blank" rel="noopener"' : '' ?>><?= e($f['name']) ?></a> <span class="muted small"><?= $fmtSize((int) $f['size']) ?></span></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>

    <form method="post" action="/admin/messages/<?= (int) $t['id'] ?>" class="panel form" id="repondre">
      <?= csrf_field() ?>
      <h2 class="h2">Répondre à <?= e($name) ?></h2>
      <label>Sujet<input type="text" name="subject" value="<?= e('Re: ' . base_subject($lastIn['subject'] ?? $t['subject'])) ?>" maxlength="200"></label>
      <label>Message<textarea name="body" rows="8" required placeholder="<?= $t['lang'] === 'en' ? 'Hello ' : 'Bonjour ' ?><?= e(explode(' ', trim($t['contact_name']))[0] ?? '') ?>,"></textarea>
        <span class="hint">Langue du client : <?= $t['lang'] === 'en' ? 'anglais' : 'français' ?>. Envoyé depuis <?= e(config('mail')['from']) ?> ; sa réponse reviendra ici.</span></label>
      <label class="check"><input type="checkbox" name="signature" value="1" checked> Ajouter la signature</label>
      <pre class="signature-preview"><?= e($signature) ?></pre>
      <?php if ($lastIn): ?><label class="check"><input type="checkbox" name="quote" value="1" checked> Citer son dernier message</label><?php endif; ?>
      <label class="check"><input type="checkbox" name="archive" value="1"> Archiver la conversation après l'envoi</label>
      <div><button class="btn btn-primary" type="submit">Envoyer la réponse</button></div>
    </form>
  </div>

  <aside class="col-side">
    <div class="panel">
      <h2 class="h2">Contact</h2>
      <dl class="dl">
        <dt>Courriel</dt><dd><?= e($t['contact_email']) ?></dd>
        <dt>Langue</dt><dd><?= $t['lang'] === 'en' ? 'Anglais' : ($t['lang'] === 'fr' ? 'Français' : 'Non précisée') ?></dd>
        <?php if ($client): ?><dt>Compte</dt><dd><?= e($client['name']) ?> · <?= e(PLANS[$client['plan']] ?? $client['plan']) ?><br><a href="/admin/clients?q=<?= rawurlencode($client['email']) ?>">Voir le client</a></dd><?php endif; ?>
      </dl>
    </div>
    <?php if ($order): ?>
      <div class="panel">
        <h2 class="h2">Commande <?= e($order['reference']) ?></h2>
        <p><span class="badge badge-<?= e($order['status']) ?>"><?= e(ORDER_STATUSES[$order['status']] ?? $order['status']) ?></span>
          <?= $order['paid_at'] ? '<span class="status status-active">Payée</span>' : '' ?></p>
        <p class="small"><?= e($order['card_type']) ?> × <?= (int) $order['quantity'] ?><?= $order['amount_cents'] !== null ? ' · ' . money((int) $order['amount_cents']) : '' ?></p>
        <p><a href="/admin/commandes/<?= (int) $order['id'] ?>">Ouvrir la commande</a></p>
      </div>
    <?php endif; ?>
    <?php if ($others): ?>
      <div class="panel">
        <h2 class="h2">Autres conversations</h2>
        <ul class="plain-list">
          <?php foreach ($others as $o): ?><li><a href="/admin/messages/<?= (int) $o['id'] ?>"><?= e($o['subject']) ?></a> <span class="muted small"><?= date_fr($o['last_message_at']) ?></span></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </aside>
</div>
