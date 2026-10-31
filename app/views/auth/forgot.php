<h1>Mot de passe oublié</h1>
<p class="muted">Indiquez votre courriel : nous vous envoyons un lien pour en choisir un nouveau.</p>
<form method="post" action="/mot-de-passe-oublie" class="form">
  <?= csrf_field() ?>
  <label>Courriel<input type="email" name="email" autocomplete="email" required autofocus></label>
  <button class="btn btn-primary btn-block" type="submit">Envoyer le lien</button>
</form>
<p class="auth-foot"><a href="/connexion">Retour à la connexion</a></p>
