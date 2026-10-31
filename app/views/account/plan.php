<?php
$plan = $user['plan'] ?? 'essentiel';
$paid = plan_is_paid($plan);
$viaStripe = ($user['plan_source'] ?? '') === 'stripe' && $user['stripe_customer_id'];
?>
<div class="page-head">
  <div>
    <h1><?= te('Forfait') ?></h1>
    <p class="muted"><?= te('Votre profil de base reste gratuit à vie. Le forfait Pro est un choix, jamais une obligation.') ?></p>
  </div>
</div>

<?php if ($success): ?>
  <p class="alert alert-success" role="status"><?= te('Merci ! Votre abonnement Pro s\'active dans quelques secondes. Rechargez la page si besoin.') ?></p>
<?php endif; ?>

<div class="panel current-plan">
  <div>
    <span class="muted small"><?= te('Forfait actuel') ?></span>
    <strong class="plan-name"><?= te(PLANS[$plan]) ?></strong>
    <?php if (($user['plan_status'] ?? '') === 'past_due'): ?>
      <p class="alert alert-error" style="margin: 8px 0 0;"><?= te('Le dernier paiement a échoué. Mettez à jour votre carte pour garder le forfait Pro.') ?></p>
    <?php elseif ($user['plan_renews_at'] && $viaStripe): ?>
      <span class="muted small"><?= te('Prochain renouvellement : {date}', ['date' => date(lang() === 'en' ? 'm/d/Y' : 'd/m/Y', strtotime($user['plan_renews_at']))]) ?></span>
    <?php endif; ?>
  </div>
  <?php if ($viaStripe): ?>
    <form method="post" action="/compte/forfait/portail"><?= csrf_field() ?><button class="btn btn-light" type="submit"><?= te('Gérer mon abonnement') ?></button></form>
  <?php endif; ?>
</div>

<div class="plans-grid">
  <article class="panel plan-card<?= $plan === 'essentiel' ? ' is-current' : '' ?>">
    <h2><?= te('Essentiel') ?></h2>
    <p class="muted small"><?= te('Inclus avec chaque carte · gratuit à vie') ?></p>
    <ul class="checklist">
      <li><?= te('Profil public hébergé') ?></li>
      <li><?= te('Choix parmi 6 thèmes') ?></li>
      <li><?= te('Enregistrement du contact (vCard)') ?></li>
      <li><?= te('Modifications faites par EliteTap') ?></li>
    </ul>
  </article>

  <article class="panel plan-card plan-pro<?= $plan === 'pro' ? ' is-current' : '' ?>">
    <h2>Pro <span class="tag-pro"><?= te('Recommandé') ?></span></h2>
    <p class="muted small"><?= te('Abonnement mensuel ou annuel') ?></p>
    <ul class="checklist">
      <li><?= te('Modification autonome du profil') ?></li>
      <li><?= te('Touchers, visites et prospects') ?></li>
      <li><?= te('Alertes courriel et export des prospects') ?></li>
      <li><?= te('Couleurs, logo et police de la marque') ?></li>
    </ul>
    <?php if (!$paid): ?>
      <?php if ($prices): ?>
        <form method="post" action="/compte/forfait/abonnement" class="plan-actions">
          <?= csrf_field() ?>
          <?php if (isset($prices['month'])): ?><button class="btn btn-primary" type="submit" name="interval" value="month"><?= te('S\'abonner au mois') ?></button><?php endif; ?>
          <?php if (isset($prices['year'])): ?><button class="btn btn-light" type="submit" name="interval" value="year"><?= te('S\'abonner à l\'année') ?></button><?php endif; ?>
        </form>
        <p class="hint"><?= te('Paiement sécurisé par Stripe. Annulable à tout moment ; votre profil reste en ligne.') ?></p>
      <?php else: ?>
        <p class="plan-actions"><a class="btn btn-primary" href="mailto:info@elitetap.ca?subject=<?= rawurlencode(t('Forfait Pro')) ?>"><?= te('Demander le forfait Pro') ?></a></p>
      <?php endif; ?>
    <?php endif; ?>
  </article>

  <article class="panel plan-card<?= $plan === 'entreprise' ? ' is-current' : '' ?>">
    <h2><?= te('Entreprise') ?></h2>
    <p class="muted small"><?= te('Par carte / par employé · sur devis') ?></p>
    <ul class="checklist">
      <li><?= te('Tout le forfait Pro') ?></li>
      <li><?= te('Design sur mesure') ?></li>
      <li><?= te('Image de marque uniforme pour l\'équipe') ?></li>
      <li><?= te('Accompagnement dédié') ?></li>
    </ul>
    <?php if ($plan !== 'entreprise'): ?>
      <p class="plan-actions"><a class="btn btn-light" href="mailto:info@elitetap.ca?subject=<?= rawurlencode(t('Forfait Entreprise')) ?>"><?= te('Nous contacter') ?></a></p>
    <?php endif; ?>
  </article>
</div>
