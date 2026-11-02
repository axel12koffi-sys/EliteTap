<?php $val = fn(?int $c) => $c === null ? '' : number_format($c / 100, 2, ',', ''); ?>
<div class="page-head">
  <div>
    <h1>Réglages</h1>
    <p class="muted">Prix utilisés sur la page de commande du client, taxes et adresse des notifications.</p>
  </div>
</div>

<form method="post" action="/admin/reglages" class="form settings-form">
  <?= csrf_field() ?>
  <div class="panel form">
    <h2 class="h2">Prix des cartes (par carte, avant taxes)</h2>
    <p class="muted small">Laisser vide = <strong>sur devis</strong> : le client ne peut pas payer en ligne et passe par le service client. Ces prix s'affichent aussi sur l'accueil (« À partir de »).</p>
    <div class="grid-2">
      <?php foreach ($prices as $type => $cents): ?>
        <label><?= e($type) ?> ($)<input type="text" name="<?= e(price_key($type)) ?>" inputmode="decimal" placeholder="Sur devis" value="<?= e($val($cents)) ?>"></label>
      <?php endforeach; ?>
    </div>
    <label style="max-width: 320px;">Taxes ajoutées (%)<input type="text" name="tax_rate" inputmode="decimal" value="<?= e(rtrim(rtrim(number_format(tax_rate(), 3, ',', ''), '0'), ',')) ?>">
      <span class="hint">14,975 = TPS 5 % + TVQ 9,975 %. Mettre 0 si vos prix incluent déjà les taxes.</span></label>
  </div>

  <div class="panel form">
    <h2 class="h2">Paiement</h2>
    <label class="check"><input type="checkbox" name="online_payment" value="1"<?= setting('online_payment', '1') === '1' ? ' checked' : '' ?>> Proposer le paiement en ligne sur la page de commande</label>
    <p class="muted small">
      <?php if (stripe_enabled()): ?>Stripe est configuré.<?php else: ?><strong>Stripe n'est pas encore configuré</strong> (clés dans <span class="mono">app/config.php</span>) : en attendant, les clients choisissent « Régler avec le service client ».<?php endif; ?>
    </p>
  </div>

  <div class="panel form">
    <h2 class="h2">Forfait Pro</h2>
    <label style="max-width: 320px;">Prix du forfait Pro, par mois ($)<input type="text" name="pro_price_month" inputmode="decimal" placeholder="À définir" value="<?= e(($v = setting('pro_price_month')) ? number_format((int) $v / 100, 2, ',', '') : '') ?>">
      <span class="hint">Sert au calcul du revenu récurrent (MRR) dans le tableau de bord. Le prix facturé, lui, se règle dans Stripe.</span></label>
  </div>

  <div class="panel form">
    <h2 class="h2">Notifications</h2>
    <label>Courriel qui reçoit les nouvelles demandes, confirmations et paiements
      <input type="email" name="notify_email" value="<?= e(setting('notify_email', '')) ?>" placeholder="<?= e(config('mail')['notify_to'] ?? '') ?>">
      <span class="hint">Vide = <?= e(config('mail')['notify_to'] ?? config('mail')['from']) ?> (config.php). Utile tant que info@elitetap.ca n'existe pas.</span></label>
  </div>

  <div><button class="btn btn-primary" type="submit">Enregistrer les réglages</button></div>
</form>
