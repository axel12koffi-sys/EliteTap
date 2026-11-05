<?php
$link = tracking_link($s);
$noTracking = in_array($s['carrier'], ['main_propre', 'ramassage'], true);
$order = array_keys(SHIP_STATUSES);
?>
<p class="crumb"><a href="/admin/livraisons">Livraisons</a> › <?= e($o['reference']) ?></p>
<div class="page-head">
  <div>
    <h1>Livraison <?= e($o['reference']) ?></h1>
    <p class="muted"><?= e($o['name']) ?><?= $o['company'] !== '' ? ' · ' . e($o['company']) : '' ?> · <?= e($o['card_type']) ?> × <?= (int) $o['quantity'] ?> · <a href="/admin/commandes/<?= (int) $o['id'] ?>">Ouvrir la commande</a></p>
  </div>
  <span class="ship-status ship-<?= e($s['status']) ?> ship-status-lg"><?= e(SHIP_STATUSES[$s['status']]) ?></span>
</div>

<ol class="ship-steps" aria-label="Étapes">
  <?php foreach (['a_preparer', 'preparee', 'expediee', 'en_transit', 'livree'] as $k): $done = !in_array($s['status'], ['probleme', 'retournee'], true) && array_search($k, $order) <= array_search($s['status'], $order); ?>
    <li class="<?= $done ? 'is-done' : '' ?><?= $k === $s['status'] ? ' is-current' : '' ?>"><span><?= e(SHIP_STATUSES[$k]) ?></span></li>
  <?php endforeach; ?>
</ol>

<div class="cols">
  <div class="col-main">
    <form method="post" action="/admin/livraisons/<?= (int) $s['id'] ?>" class="panel form" id="statut">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="status">
      <h2 class="h2">Changer le statut</h2>
      <div class="grid-2">
        <label>Nouveau statut<select name="status" data-ship-status>
          <?php foreach (SHIP_STATUSES as $k => $l): ?><option value="<?= $k ?>"<?= $k === $s['status'] ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select></label>
        <label>Note (facultatif)<input type="text" name="note" maxlength="500" placeholder="Ex. remis au bureau de poste de Laval">
          <span class="hint">Ajoutée au courriel du client, sauf pour « Livrée ».</span></label>
      </div>
      <label class="check"><input type="checkbox" name="notify" value="1" checked> Prévenir le client par courriel (expédiée, en transit, livrée, problème) avec le lien de suivi</label>
      <?php if (!$s['tracking_number'] && !$noTracking): ?><label class="check"><input type="checkbox" name="sans_suivi" value="1"> Expédier sans numéro de suivi</label><?php endif; ?>
      <div><button class="btn btn-primary" type="submit">Enregistrer le statut</button></div>
    </form>

    <form method="post" action="/admin/livraisons/<?= (int) $s['id'] ?>" class="panel form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="details">
      <h2 class="h2">Transporteur et suivi</h2>
      <div class="grid-2">
        <label>Transporteur<select name="carrier">
          <?php foreach (CARRIERS as $k => [$l]): ?><option value="<?= $k ?>"<?= $k === $s['carrier'] ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select></label>
        <label>Numéro de suivi<input type="text" name="tracking_number" maxlength="80" value="<?= e($s['tracking_number'] ?? '') ?>" class="mono" autocomplete="off"></label>
        <label>Lien de suivi (facultatif)<input type="url" name="tracking_url" maxlength="500" value="<?= e($s['tracking_url'] ?? '') ?>" placeholder="https://…">
          <span class="hint">Vide = lien créé automatiquement pour Postes Canada, Purolator, UPS, FedEx et DHL.</span></label>
        <label>Livraison prévue le<input type="date" name="estimated_at" value="<?= e($s['estimated_at'] ?? '') ?>"></label>
        <label>Nombre de colis<input type="number" name="packages" min="1" max="99" value="<?= (int) $s['packages'] ?>"></label>
      </div>
      <h3 class="h3" style="margin-top: 8px;">Adresse de livraison</h3>
      <div class="grid-2">
        <label>Destinataire<input type="text" name="recipient" maxlength="120" value="<?= e($s['recipient']) ?>" autocomplete="off"></label>
        <label>Téléphone<input type="tel" name="phone" maxlength="40" value="<?= e($s['phone']) ?>"></label>
        <label style="grid-column: 1 / -1;">Adresse<input type="text" name="address" maxlength="255" value="<?= e($s['address']) ?>"></label>
        <label>Ville<input type="text" name="city" maxlength="100" value="<?= e($s['city']) ?>"></label>
        <div class="grid-2" style="gap: 12px;">
          <label>Province<input type="text" name="province" maxlength="30" value="<?= e($s['province']) ?>"></label>
          <label>Code postal<input type="text" name="postal_code" maxlength="12" value="<?= e($s['postal_code']) ?>"></label>
        </div>
      </div>
      <label>Notes internes<textarea name="notes" rows="3" maxlength="2000"><?= e($s['notes'] ?? '') ?></textarea></label>
      <div><button class="btn btn-light" type="submit">Enregistrer</button></div>
    </form>
  </div>

  <aside class="col-side">
    <div class="panel">
      <h2 class="h2">Suivi</h2>
      <dl class="dl">
        <dt>Transporteur</dt><dd><?= e(carrier_label($s['carrier'])) ?></dd>
        <dt>N° de suivi</dt><dd><?= $s['tracking_number'] ? ($link ? '<a href="' . e($link) . '" target="_blank" rel="noopener noreferrer" class="mono">' . e($s['tracking_number']) . '</a>' : '<span class="mono">' . e($s['tracking_number']) . '</span>') : '—' ?></dd>
        <dt>Expédiée</dt><dd><?= $s['shipped_at'] ? date_fr($s['shipped_at']) : '—' ?></dd>
        <dt>Livrée</dt><dd><?= $s['delivered_at'] ? date_fr($s['delivered_at']) : '—' ?></dd>
      </dl>
      <p class="small" style="margin-top: 14px;">Page de suivi du client :<br><a href="<?= e(public_tracking_link($s)) ?>" target="_blank" rel="noopener" class="mono small" style="overflow-wrap: anywhere;"><?= e(public_tracking_link($s)) ?></a></p>
    </div>
    <div class="panel">
      <h2 class="h2">Historique</h2>
      <ol class="ship-history">
        <?php foreach ($events as $e): ?>
          <li><strong><?= e(SHIP_STATUSES[$e['status']] ?? $e['status']) ?></strong> <span class="muted small"><?= date_fr($e['created_at']) ?><?= $e['user_name'] ? ' · ' . e($e['user_name']) : '' ?></span>
            <?php if ($e['note'] !== ''): ?><br><span class="small"><?= e($e['note']) ?></span><?php endif; ?>
            <?php if ($e['notified']): ?><br><span class="small muted">Client prévenu par courriel</span><?php endif; ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php if ($others): ?>
      <div class="panel">
        <h2 class="h2">Autres colis de la commande</h2>
        <ul class="plain-list"><?php foreach ($others as $x): ?><li><a href="/admin/livraisons/<?= (int) $x['id'] ?>"><?= e(SHIP_STATUSES[$x['status']]) ?></a> <span class="mono small"><?= e($x['tracking_number'] ?? '') ?></span></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <form method="post" action="/admin/livraisons" class="panel">
      <?= csrf_field() ?>
      <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
      <p class="small muted">Commande envoyée en plusieurs fois ?</p>
      <button class="btn btn-light btn-sm" type="submit">Ajouter un autre colis</button>
    </form>
  </aside>
</div>
