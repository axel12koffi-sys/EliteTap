<h1><?= te('Connexion') ?></h1>
<p class="muted"><?= te('Accédez à votre profil, vos cartes et vos statistiques.') ?></p>
<form method="post" action="/connexion" class="form">
  <?= csrf_field() ?>
  <label><?= te('Courriel') ?><input type="email" name="email" autocomplete="email" required autofocus value="<?= e(old('email')) ?>"></label>
  <label><?= te('Mot de passe') ?><input type="password" name="password" autocomplete="current-password" required></label>
  <button class="btn btn-primary btn-block" type="submit"><?= te('Se connecter') ?></button>
</form>
<p class="auth-foot"><a href="/mot-de-passe-oublie"><?= te('Mot de passe oublié ?') ?></a></p>
<p class="auth-foot muted"><?= te('Pas encore de carte ?') ?> <a href="/#commande"><?= te('Demander un devis') ?></a></p>
