<div class="page-head">
  <div>
    <h1>Sécurité</h1>
    <p class="muted">Compte : <?= e($user['email']) ?></p>
  </div>
</div>
<div class="panel narrow-panel">
  <h2 class="h2">Changer le mot de passe</h2>
  <form method="post" action="/compte/securite" class="form">
    <?= csrf_field() ?>
    <input type="email" name="username" value="<?= e($user['email']) ?>" autocomplete="username" hidden>
    <label>Mot de passe actuel<input type="password" name="current" autocomplete="current-password" required></label>
    <label>Nouveau mot de passe <span class="hint">10 caractères minimum</span><input type="password" name="password" autocomplete="new-password" minlength="10" required></label>
    <label>Confirmer<input type="password" name="password_confirm" autocomplete="new-password" minlength="10" required></label>
    <button class="btn btn-primary" type="submit">Enregistrer</button>
  </form>
</div>
