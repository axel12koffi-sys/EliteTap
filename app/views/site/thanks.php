<section class="page container page-center">
  <h1><?= te('Merci, votre demande est envoyée.') ?></h1>
  <p><?= te('Nous vous répondons en moins de 24 h avec votre devis. Un accusé de réception vient d\'être envoyé à votre courriel.') ?></p>
  <?php if ($ref): ?><span class="ref"><?= te('Référence') ?> : <?= e($ref) ?></span><?php endif; ?>
  <div class="actions">
    <a class="btn btn-primary" href="https://wa.me/<?= e(config('whatsapp')) ?>" rel="noopener"><?= te('Nous écrire sur WhatsApp') ?></a>
    <a class="btn btn-outline" href="/"><?= te('Retour à l\'accueil') ?></a>
  </div>
</section>
