<div class="page-head">
  <div>
    <p class="crumb"><a href="/admin/commandes">Commandes</a> /</p>
    <h1><?= e($o['reference']) ?> · <?= e($o['name']) ?></h1>
    <p class="muted">Reçue le <?= date_fr($o['created_at']) ?> · mise à jour le <?= date_fr($o['updated_at']) ?></p>
  </div>
  <span class="badge badge-<?= e($o['status']) ?> badge-lg"><?= e(ORDER_STATUSES[$o['status']]) ?></span>
</div>

<div class="cols">
  <div class="col-main">
    <div class="panel">
      <h2 class="h2">Demande</h2>
      <dl class="dl">
        <dt>Courriel</dt><dd><?= e($o['email']) ?><br><a href="/admin/messages/nouveau?commande=<?= (int) $o['id'] ?>">Écrire au client</a></dd>
        <dt>Téléphone</dt><dd><?= $o['phone'] !== '' ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $o['phone'])) . '">' . e($o['phone']) . '</a> · <a href="https://wa.me/' . e(preg_replace('/\D/', '', $o['phone'])) . '" rel="noopener">WhatsApp</a>' : '—' ?></dd>
        <dt>Entreprise</dt><dd><?= e($o['company'] ?: '—') ?></dd>
        <dt>Carte</dt><dd><?= e($o['card_type']) ?> × <?= (int) $o['quantity'] ?></dd>
        <dt>Fichier</dt><dd><?= $o['file_path'] ? '<a href="/admin/commandes/' . (int) $o['id'] . '/fichier">' . e($o['file_name']) . '</a>' : '—' ?></dd>
      </dl>
      <?php if ($o['message']): ?><div class="message"><?= nl2br(e($o['message'])) ?></div><?php endif; ?>
    </div>

    <div class="panel">
      <h2 class="h2">Cartes de la commande</h2>
      <?php if ($cards): ?>
        <p style="margin-bottom: 12px;"><a class="btn btn-light btn-sm" href="/admin/cartes/qr?commande=<?= (int) $o['id'] ?>" target="_blank">QR codes de ces cartes</a></p>
        <table class="table">
          <thead><tr><th>Code</th><th>Matière</th><th>Statut</th><th>URL à encoder</th></tr></thead>
          <tbody>
          <?php foreach ($cards as $c): ?>
            <tr><td class="mono"><?= e($c['code']) ?></td><td><?= e($c['material']) ?></td><td><span class="status status-<?= e($c['status']) ?>"><?= e(CARD_STATUSES[$c['status']] ?? $c['status']) ?></span></td><td class="mono small"><?= e(url('/t/' . $c['code'])) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="muted">Aucune carte liée. Choisissez un code du stock dans le formulaire de droite.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-side">
    <?php if (shipping_ready() && staff_can($user, 'shipping') && $o['status'] !== 'annulee'): ?>
      <div class="panel">
        <h2 class="h2">Livraison</h2>
        <?php if (!$shipments): ?>
          <p class="muted small"><?= $o['paid_at'] ? 'Aucune expédition pour l\'instant.' : 'La commande n\'est pas encore payée.' ?></p>
        <?php else: foreach ($shipments as $sh): ?>
          <p><a href="/admin/livraisons/<?= (int) $sh['id'] ?>"><span class="ship-status ship-<?= e($sh['status']) ?>"><?= e(SHIP_STATUSES[$sh['status']]) ?></span></a>
            <span class="small"><?= e(carrier_label($sh['carrier'])) ?><?= $sh['tracking_number'] ? ' · <span class="mono">' . e($sh['tracking_number']) . '</span>' : '' ?></span></p>
        <?php endforeach; endif; ?>
        <form method="post" action="/admin/livraisons"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
          <button class="btn btn-light btn-sm" type="submit"><?= $shipments ? 'Ajouter un colis' : 'Créer l\'expédition' ?></button></form>
      </div>
    <?php endif; ?>
    <form method="post" action="/admin/commandes/<?= (int) $o['id'] ?>" class="panel form">
      <?= csrf_field() ?>
      <h2 class="h2">Suivi</h2>
      <label>Statut
        <select name="status">
          <?php foreach (ORDER_STATUSES as $k => $label): ?><option value="<?= $k ?>"<?= $o['status'] === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label class="check"><input type="checkbox" name="notify" value="1"> Prévenir le client par courriel si le statut change</label>
      <label>Montant total ($ CAD)<input type="text" name="amount" inputmode="decimal" value="<?= $o['amount_cents'] !== null ? e(number_format((int) $o['amount_cents'] / 100, 2, ',', '')) : '' ?>" placeholder="Vide = sur devis"></label>
      <label>Lier une carte du stock
        <select name="attach_card">
          <option value="">—</option>
          <?php foreach ($stock as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['code']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <?php if (!$stock): ?><p class="hint">Stock vide : <a href="/admin/cartes">générez des codes</a>.</p><?php endif; ?>
      <label>Notes internes<textarea name="admin_notes" rows="4"><?= e($o['admin_notes'] ?? '') ?></textarea></label>
      <button class="btn btn-primary btn-block" type="submit">Enregistrer</button>
    </form>

    <div class="panel">
      <h2 class="h2">Paiement</h2>
      <p class="small">
        <?php if ($o['confirmed_at']): ?><span class="status status-active">Courriel confirmé</span> le <?= date_fr($o['confirmed_at']) ?><?php else: ?><span class="status status-stock">Courriel pas encore confirmé</span><?php endif; ?>
        <?php if ($o['payment_method'] === 'service' && !$o['paid_at']): ?><br><strong>Le client veut régler avec le service client.</strong><?php endif; ?>
      </p>
      <?php if ($o['subtotal_cents'] !== null): ?>
        <p class="muted small">Sous-total <?= money((int) $o['subtotal_cents']) ?> · taxes <?= money((int) $o['tax_cents']) ?> · total <?= money((int) $o['amount_cents']) ?></p>
      <?php endif; ?>
      <?php if ($o['paid_at']): ?>
        <p><span class="status status-active">Payée</span> le <?= date_fr($o['paid_at']) ?></p>
        <p class="muted small">Montant : <?= money((int) $o['amount_cents']) ?><br>Référence : <span class="mono"><?= e($o['payment_ref']) ?></span></p>
      <?php else: ?>
        <?php if (!stripe_enabled()): ?>
          <p class="muted small">Paiement en ligne inactif : ajoutez les clés Stripe dans <span class="mono">app/config.php</span>.</p>
        <?php elseif ($o['amount_cents'] === null): ?>
          <p class="muted small">Indiquez le montant total (taxes incluses) dans « Suivi » pour envoyer un lien de paiement.</p>
        <?php else: ?>
          <?php if ($o['pay_token']): $payLink = url('/payer/' . $o['reference'] . '/' . $o['pay_token']); ?>
            <label class="form" style="margin-top: 12px;"><span class="small">Lien de paiement à partager</span><input type="text" readonly value="<?= e($payLink) ?>" onclick="this.select()" class="cell-input mono"></label>
          <?php endif; ?>
          <form method="post" action="/admin/commandes/<?= (int) $o['id'] ?>/paiement" class="row-actions">
            <?= csrf_field() ?>
            <button class="btn btn-primary" type="submit" name="send" value="1"><?= $o['pay_token'] ? 'Renvoyer le lien de paiement' : 'Envoyer le lien de paiement' ?></button>
            <?php if (!$o['pay_token']): ?><button class="btn btn-light" type="submit">Créer le lien seulement</button><?php endif; ?>
          </form>
        <?php endif; ?>

        <form method="post" action="/admin/commandes/<?= (int) $o['id'] ?>/paye" class="form paid-form" data-confirm="Enregistrer ce paiement ? Le client recevra son reçu et l'accès à son espace.">
          <?= csrf_field() ?>
          <h3 class="h3-sm">Paiement reçu par le service client</h3>
          <div class="grid-2">
            <label>Moyen<select name="method"><option>Virement Interac</option><option>Comptant</option><option>Chèque</option><option>Autre</option></select></label>
            <label>Montant reçu ($)<input type="text" name="amount" inputmode="decimal" value="<?= $o['amount_cents'] !== null ? e(number_format((int) $o['amount_cents'] / 100, 2, ',', '')) : '' ?>"></label>
          </div>
          <button class="btn btn-light" type="submit">Marquer comme payée</button>
        </form>
      <?php endif; ?>
      <p class="small" style="margin-top: 14px;"><a href="<?= e(order_link($o)) ?>" target="_blank" rel="noopener">Page de commande du client</a></p>
    </div>

    <div class="panel">
      <h2 class="h2">Compte client</h2>
      <?php if ($client): ?>
        <p><strong><?= e($client['name']) ?></strong><br><span class="muted small"><?= e($client['email']) ?></span></p>
        <?php if ($client['slug']): ?><p><a href="/u/<?= e($client['slug']) ?>" target="_blank" rel="noopener"><?= e(public_host()) ?>/u/<?= e($client['slug']) ?></a></p><?php endif; ?>
      <?php else: ?>
        <p class="muted small"><?= $existing ? 'Un compte existe déjà pour ce courriel (' . e($existing['name']) . ').' : 'Créer le compte envoie au client un lien pour choisir son mot de passe (72 h). Les cartes liées sont activées sur son profil.' ?></p>
        <form method="post" action="/admin/commandes/<?= (int) $o['id'] ?>/client">
          <?= csrf_field() ?>
          <button class="btn btn-light btn-block" type="submit"><?= $existing ? 'Relier au compte existant' : 'Créer le compte client' ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
