<div class="page-head">
  <div>
    <p class="crumb"><a href="/admin">Commandes</a> /</p>
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
        <dt>Courriel</dt><dd><a href="mailto:<?= e($o['email']) ?>?subject=<?= rawurlencode('Votre demande EliteTap ' . $o['reference']) ?>"><?= e($o['email']) ?></a></dd>
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
      <h2 class="h2">Paiement en ligne</h2>
      <?php if ($o['paid_at']): ?>
        <p><span class="status status-active">Payée</span> le <?= date_fr($o['paid_at']) ?></p>
        <p class="muted small">Montant : <?= money((int) $o['amount_cents']) ?><br>Référence Stripe : <span class="mono"><?= e($o['payment_ref']) ?></span></p>
      <?php elseif (!stripe_enabled()): ?>
        <p class="muted small">Ajoutez les clés Stripe dans <span class="mono">app/config.php</span> pour activer le paiement en ligne.</p>
      <?php elseif ($o['amount_cents'] === null): ?>
        <p class="muted small">Indiquez le montant total (taxes incluses) dans « Suivi » pour pouvoir envoyer un lien de paiement.</p>
      <?php else: ?>
        <p class="muted small">Le client paie <?= money((int) $o['amount_cents']) ?> par carte sur une page Stripe sécurisée. La commande passe à « payée » automatiquement.</p>
        <?php if ($o['pay_token']): $payLink = url('/payer/' . $o['reference'] . '/' . $o['pay_token']); ?>
          <label class="form" style="margin-top: 12px;"><span class="small">Lien à partager (WhatsApp, texto…)</span><input type="text" readonly value="<?= e($payLink) ?>" onclick="this.select()" class="cell-input mono"></label>
        <?php endif; ?>
        <form method="post" action="/admin/commandes/<?= (int) $o['id'] ?>/paiement" class="row-actions">
          <?= csrf_field() ?>
          <button class="btn btn-primary" type="submit" name="send" value="1"><?= $o['pay_token'] ? 'Renvoyer par courriel' : 'Envoyer le lien par courriel' ?></button>
          <?php if (!$o['pay_token']): ?><button class="btn btn-light" type="submit">Créer le lien seulement</button><?php endif; ?>
        </form>
      <?php endif; ?>
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
