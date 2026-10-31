<div class="page-head">
  <div>
    <h1><?= te('Sécurité') ?></h1>
    <p class="muted"><?= te('Compte') ?> : <?= e($user['email']) ?></p>
  </div>
</div>
<div class="panel narrow-panel">
  <h2 class="h2"><?= te('Changer le mot de passe') ?></h2>
  <form method="post" action="/compte/securite" class="form">
    <?= csrf_field() ?>
    <input type="email" name="username" value="<?= e($user['email']) ?>" autocomplete="username" hidden>
    <label><?= te('Mot de passe actuel') ?><input type="password" name="current" autocomplete="current-password" required></label>
    <label><?= te('Nouveau mot de passe') ?> <span class="hint"><?= te('10 caractères minimum') ?></span><input type="password" name="password" autocomplete="new-password" minlength="10" required></label>
    <label><?= te('Confirmer') ?><input type="password" name="password_confirm" autocomplete="new-password" minlength="10" required></label>
    <button class="btn btn-primary" type="submit"><?= te('Enregistrer') ?></button>
  </form>
</div>
