<?php
$paid = (bool) $o['paid_at'];
$current = in_array($o['card_type'], array_keys($choices), true) ? $o['card_type'] : 'Premium';
$fmtPrice = fn(?int $c) => $c === null ? t('Sur devis') : money($c);
?>
<section class="page container order-page">
  <p class="eyebrow"><?= te('Commande {ref}', ['ref' => $o['reference']]) ?></p>

  <?php if ($paid): ?>
    <h1><?= te('Merci, votre commande est payée.') ?></h1>
    <p class="lead-dark"><?= te('Nous préparons votre épreuve de design. Votre accès à votre espace EliteTap vous a été envoyé par courriel.') ?></p>
    <div class="order-status">
      <?php foreach (['devis' => 'Commande reçue', 'design' => 'Design en cours', 'epreuve' => 'Épreuve envoyée', 'production' => 'En production', 'expediee' => 'Expédiée'] as $k => $label):
        $order = array_keys(ORDER_STATUSES); $done = array_search($k, $order) <= array_search($o['status'], $order); ?>
        <span class="<?= $done ? 'done' : '' ?>"><?= te($label) ?></span>
      <?php endforeach; ?>
    </div>
    <?php foreach ((shipping_ready() ? rows("SELECT * FROM shipments WHERE order_id = ? AND status NOT IN ('a_preparer','preparee') ORDER BY id", [$o['id']]) : []) as $sh): ?>
      <p><a class="btn btn-primary" href="/suivi/<?= e($sh['token']) ?>"><?= te('Suivre ma livraison') ?></a> <span class="muted"><?= e(ship_status_public($sh['status'])) ?></span></p>
    <?php endforeach; ?>
  <?php elseif ($sent === 'service' || ($o['payment_method'] === 'service')): ?>
    <h1><?= te('C\'est noté, notre équipe vous écrit.') ?></h1>
    <p class="lead-dark"><?= te('Vous avez choisi de régler avec notre service client. Nous vous écrivons dans les 24 h à {courriel} pour le paiement et votre épreuve de design.', ['courriel' => $o['email']]) ?></p>
    <p class="muted"><?= te('Vous préférez finalement payer en ligne ? Choisissez de nouveau ci-dessous.') ?></p>
  <?php else: ?>
    <h1><?= te('Choisissez votre carte.') ?></h1>
    <p class="lead-dark"><?= te('Merci {nom}, votre adresse courriel est confirmée. Choisissez votre carte et la quantité, puis payez en ligne ou réglez avec notre service client.', ['nom' => $o['name']]) ?></p>
  <?php endif; ?>

  <?php foreach (flashes() as $f): ?><p class="alert alert-<?= e($f['type']) ?>" role="alert"><?= e($f['msg']) ?></p><?php endforeach; ?>

  <?php if (!$paid && $o['status'] !== 'annulee'): ?>
  <form method="post" action="/commande/<?= e($o['reference']) ?>/<?= e($o['confirm_token']) ?>" class="order-choose" id="order-form"
        data-tax="<?= e((string) $taxRate) ?>" data-lang="<?= lang() ?>">
    <?= csrf_field() ?>
    <fieldset class="choice-grid">
      <legend class="visually-hidden"><?= te('Type de carte') ?></legend>
      <?php foreach ($choices as $type => $price): ?>
        <label class="choice">
          <input type="radio" name="card_type" value="<?= e($type) ?>" data-price="<?= $price ?? '' ?>"<?= $type === $current ? ' checked' : '' ?>>
          <span class="choice-body">
            <strong><?= te($type) ?></strong>
            <span class="choice-price"><?= $price === null ? te('Sur devis') : te('{prix} / carte', ['prix' => money($price)]) ?></span>
          </span>
        </label>
      <?php endforeach; ?>
    </fieldset>

    <div class="order-row">
      <label class="qty"><?= te('Quantité') ?><input type="number" name="quantity" min="1" max="10000" value="<?= (int) $o['quantity'] ?>"></label>
      <dl class="totals" aria-live="polite">
        <dt><?= te('Sous-total') ?></dt><dd data-t="sub">—</dd>
        <dt><?= te('Taxes ({taux} %)', ['taux' => rtrim(rtrim(number_format($taxRate, 3, lang() === 'en' ? '.' : ',', ''), '0'), ',.')]) ?></dt><dd data-t="tax">—</dd>
        <dt class="total-label"><?= te('Total') ?></dt><dd data-t="total" class="total-value">—</dd>
      </dl>
    </div>
    <p class="quote-note" data-t="quote" hidden><?= te('Ce choix est sur devis : notre service client vous écrit avec un prix adapté.') ?></p>

    <div class="order-actions">
      <?php if ($online): ?>
        <button class="btn btn-primary btn-lg" type="submit" name="method" value="online" data-t="pay"><?= te('Payer en ligne') ?></button>
      <?php endif; ?>
      <button class="btn btn-ghost btn-lg" type="submit" name="method" value="service"><?= te('Régler avec le service client') ?></button>
    </div>
    <p class="fine-dark"><?= $online ? te('Paiement par carte sécurisé par Stripe. Vous recevez une épreuve de design avant toute production.') : te('Notre service client vous écrit pour le paiement. Vous recevez une épreuve de design avant toute production.') ?></p>
  </form>
  <?php endif; ?>

  <p class="fine-dark" style="margin-top: 32px;"><?= te('Une question ? Écrivez-nous à {courriel} ou sur WhatsApp.', ['courriel' => 'info@elitetap.ca']) ?></p>
</section>
<script>
(function () {
  var f = document.getElementById('order-form'); if (!f) return;
  var tax = parseFloat(f.getAttribute('data-tax')) || 0, en = f.getAttribute('data-lang') === 'en';
  var fmt = function (c) { var v = (c / 100).toFixed(2); return en ? '$' + v.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : v.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d),)/g, ' ') + ' $'; };
  var q = function (s) { return f.querySelector('[data-t=' + s + ']'); };
  var pay = q('pay');
  var calc = function () {
    var r = f.querySelector('input[name=card_type]:checked'), qty = Math.max(1, parseInt(f.elements.quantity.value, 10) || 1);
    var price = r && r.getAttribute('data-price') ? parseInt(r.getAttribute('data-price'), 10) : null;
    if (price === null) {
      ['sub', 'tax', 'total'].forEach(function (k) { q(k).textContent = '—'; });
      q('quote').hidden = false; if (pay) pay.disabled = true; return;
    }
    var sub = price * qty, t = Math.round(sub * tax / 100);
    q('sub').textContent = fmt(sub); q('tax').textContent = fmt(t); q('total').textContent = fmt(sub + t);
    q('quote').hidden = true; if (pay) pay.disabled = false;
  };
  f.addEventListener('change', calc); f.elements.quantity.addEventListener('input', calc); calc();
})();
</script>
