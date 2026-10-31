<section class="page container page-center">
  <h1><?= (int) $code === 403 ? 'Accès refusé' : 'Une erreur est survenue' ?></h1>
  <p><?= (int) $code === 403 ? 'Vous n\'avez pas accès à cette page.' : 'Réessayez dans un instant.' ?></p>
  <div class="actions"><a class="btn btn-primary" href="/">Retour à l'accueil</a></div>
</section>
