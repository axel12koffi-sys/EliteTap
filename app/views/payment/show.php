<section class="page container page-center">
  <?php foreach (flashes() as $f): ?>
    <p class="alert alert-<?= e($f['type']) ?>" role="alert" style="margin-bottom: 20px;"><?= e($f['msg']) ?></p>
  <?php endforeach; ?>

  <?php if ($o['paid_at']): ?>
    <h1><?= e(t('Cette commande est déjà payée.')) ?></h1>
    <p><?= e(t('Merci ! Nous avons reçu votre paiement le {date}.', ['date' => date('d/m/Y', strtotime($o['paid_at']))])) ?></p>
    <span class="ref"><?= e($o['reference']) ?> · <?= money((int) $o['amount_cents']) ?></span>
  <?php elseif ($o['status'] === 'annulee' || $o['amount_cents'] === null || !$enabled): ?>
    <h1><?= e(t('Paiement indisponible')) ?></h1>
    <p><?= e(t('Ce lien de paiement n\'est pas actif. Écrivez-nous et nous vous aiderons.')) ?></p>
    <div class="actions"><a class="btn btn-primary" href="mailto:info@elitetap.ca?subject=<?= rawurlencode($o['reference']) ?>">info@elitetap.ca</a></div>
  <?php else: ?>
    <h1><?= e(t('Régler votre commande')) ?></h1>
    <p><?= e(t('Bonjour {nom}, voici le récapitulatif de votre commande.', ['nom' => $o['name']])) ?></p>
    <div class="pay-box">
      <dl>
        <dt><?= e(t('Référence')) ?></dt><dd><?= e($o['reference']) ?></dd>
        <dt><?= e(t('Carte')) ?></dt><dd><?= e(t($o['card_type'])) ?> × <?= (int) $o['quantity'] ?></dd>
        <dt><?= e(t('Total (taxes incluses)')) ?></dt><dd class="pay-total"><?= money((int) $o['amount_cents']) ?></dd>
      </dl>
    </div>
    <form method="post" action="/payer/<?= e($o['reference']) ?>/<?= e($o['pay_token']) ?>" class="actions">
      <?= csrf_field() ?>
      <button class="btn btn-primary btn-lg" type="submit"><?= e(t('Payer {montant} par carte', ['montant' => money((int) $o['amount_cents'])])) ?></button>
    </form>
    <p class="fine-dark"><?= e(t('Paiement sécurisé par Stripe. EliteTap ne voit jamais votre numéro de carte.')) ?></p>
  <?php endif; ?>
</section>
