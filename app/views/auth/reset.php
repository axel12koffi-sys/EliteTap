<?php if (!$valid): ?>
  <h1>Lien expiré</h1>
  <p class="muted">Ce lien n'est plus valide. Demandez-en un nouveau.</p>
  <p class="auth-foot"><a class="btn btn-primary btn-block" href="/mot-de-passe-oublie">Nouveau lien</a></p>
<?php else: ?>
  <h1>Choisir un mot de passe</h1>
  <p class="muted">Compte : <?= e($email) ?></p>
  <form method="post" action="/reinitialiser/<?= e($token) ?>" class="form">
    <?= csrf_field() ?>
    <input type="email" name="username" value="<?= e($email) ?>" autocomplete="username" hidden>
    <label>Nouveau mot de passe <span class="hint">10 caractères minimum</span><input type="password" name="password" autocomplete="new-password" minlength="10" required autofocus></label>
    <label>Confirmer<input type="password" name="password_confirm" autocomplete="new-password" minlength="10" required></label>
    <button class="btn btn-primary btn-block" type="submit">Enregistrer et me connecter</button>
  </form>
<?php endif; ?>
