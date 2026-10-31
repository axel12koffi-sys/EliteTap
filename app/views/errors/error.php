<section class="page container page-center">
  <h1><?= (int) $code === 403 ? te('Accès refusé') : te('Une erreur est survenue') ?></h1>
  <p><?= (int) $code === 403 ? te('Vous n\'avez pas accès à cette page.') : te('Réessayez dans un instant.') ?></p>
  <div class="actions"><a class="btn btn-primary" href="/"><?= te('Retour à l\'accueil') ?></a></div>
</section>
