<h1><?= te('Mot de passe oublié') ?></h1>
<p class="muted"><?= te('Indiquez votre courriel : nous vous envoyons un lien pour en choisir un nouveau.') ?></p>
<form method="post" action="/mot-de-passe-oublie" class="form">
  <?= csrf_field() ?>
  <label><?= te('Courriel') ?><input type="email" name="email" autocomplete="email" required autofocus></label>
  <button class="btn btn-primary btn-block" type="submit"><?= te('Envoyer le lien') ?></button>
</form>
<p class="auth-foot"><a href="/connexion"><?= te('Retour à la connexion') ?></a></p>
