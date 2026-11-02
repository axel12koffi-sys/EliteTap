<section class="page container page-center">
  <h1><?= te('Merci ! Vérifiez vos courriels.') ?></h1>
  <p><?= te('Nous venons de vous envoyer un lien pour confirmer votre commande et choisir votre carte. Vous pourrez ensuite payer en ligne ou régler avec notre service client.') ?></p>
  <?php if ($ref): ?><span class="ref"><?= te('Référence') ?> : <?= e($ref) ?></span><?php endif; ?>
  <?php foreach (flashes() as $f): ?><p class="alert alert-<?= e($f['type']) ?>" role="status" style="margin-top: 20px;"><?= e($f['msg']) ?></p><?php endforeach; ?>
  <?php if ($ref): ?>
  <form method="post" action="/merci/renvoyer" class="actions">
    <?= csrf_field() ?>
    <button class="btn btn-ghost" type="submit"><?= te('Je n\'ai rien reçu : renvoyer le lien') ?></button>
  </form>
  <?php endif; ?>
  <p class="fine-dark"><?= te('Pensez à vérifier vos courriels indésirables.') ?></p>
  <div class="actions">
    <a class="btn btn-primary" href="https://wa.me/<?= e(config('whatsapp')) ?>" rel="noopener"><?= te('Nous écrire sur WhatsApp') ?></a>
    <a class="btn btn-outline" href="/"><?= te('Retour à l\'accueil') ?></a>
  </div>
</section>
