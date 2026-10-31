<h1>Connexion</h1>
<p class="muted">Accédez à votre profil, vos cartes et vos statistiques.</p>
<form method="post" action="/connexion" class="form">
  <?= csrf_field() ?>
  <label>Courriel<input type="email" name="email" autocomplete="email" required autofocus value="<?= e(old('email')) ?>"></label>
  <label>Mot de passe<input type="password" name="password" autocomplete="current-password" required></label>
  <button class="btn btn-primary btn-block" type="submit">Se connecter</button>
</form>
<p class="auth-foot"><a href="/mot-de-passe-oublie">Mot de passe oublié ?</a></p>
<p class="auth-foot muted">Pas encore de carte ? <a href="/#commande">Demander un devis</a></p>
