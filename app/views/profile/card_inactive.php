<section class="page container page-center">
  <?php if ($card['status'] === 'disabled'): ?>
    <h1><?= te('Cette carte est désactivée.') ?></h1>
    <p><?= te('Son propriétaire l\'a désactivée, par exemple après une perte. Le profil associé n\'est plus accessible par cette carte.') ?></p>
    <div class="actions"><a class="btn btn-primary" href="/"><?= te('Découvrir EliteTap') ?></a></div>
  <?php elseif ($canClaim): ?>
    <h1><?= te('Activer cette carte ?') ?></h1>
    <p><?= te('Cette carte EliteTap est neuve. Associez-la à votre profil : elle l\'ouvrira à chaque tap.') ?></p>
    <span class="ref"><?= te('Carte') ?> <?= e($card['code']) ?></span>
    <form method="post" action="/t/<?= e($card['code']) ?>/activer" class="actions">
      <?= csrf_field() ?>
      <button class="btn btn-primary btn-lg" type="submit"><?= te('Activer sur mon profil') ?></button>
    </form>
  <?php else: ?>
    <h1><?= te('Cette carte n\'est pas encore activée.') ?></h1>
    <p><?= te('C\'est votre carte ? Connectez-vous à votre espace EliteTap puis tapez-la de nouveau pour l\'associer à votre profil.') ?></p>
    <div class="actions">
      <a class="btn btn-primary" href="/connexion"><?= te('Se connecter') ?></a>
      <a class="btn btn-outline" href="/"><?= te('Découvrir EliteTap') ?></a>
    </div>
  <?php endif; ?>
</section>
