<section class="page container page-center">
  <h1><?= e(t('Merci pour votre paiement !')) ?></h1>
  <p><?= e(t('Un reçu vous est envoyé par courriel. Nous vous tenons au courant de chaque étape de la production.')) ?></p>
  <?php if (preg_match('/^ET-[A-Z0-9]{6}$/', $ref)): ?><span class="ref"><?= e(t('Référence')) ?> : <?= e($ref) ?></span><?php endif; ?>
  <div class="actions"><a class="btn btn-outline" href="/"><?= e(t('Retour à l\'accueil')) ?></a></div>
</section>
