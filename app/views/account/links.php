<div class="page-head">
  <div>
    <h1>Liens &amp; boutons</h1>
    <p class="muted">Prise de rendez-vous, réseaux sociaux, portfolio, menu… Ils s'affichent sous vos coordonnées, dans cet ordre.</p>
  </div>
</div>

<div class="panel">
  <h2 class="h2">Ajouter un lien</h2>
  <form method="post" action="/compte/liens" class="form inline-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <label>Libellé<input type="text" name="label" maxlength="80" required placeholder="Prendre rendez-vous"></label>
    <label class="grow">Adresse<input type="text" name="url" maxlength="500" required placeholder="https://calendly.com/…" inputmode="url"></label>
    <button class="btn btn-primary" type="submit">Ajouter</button>
  </form>
</div>

<?php if (!$links): ?>
  <div class="panel empty">
    <p><strong>Aucun lien pour l'instant.</strong></p>
    <p class="muted">Ajoutez votre LinkedIn, votre Instagram ou un lien de prise de rendez-vous : c'est souvent ce que vos contacts cliquent en premier.</p>
  </div>
<?php else: ?>
  <ul class="link-list">
    <?php foreach ($links as $i => $l): ?>
      <li class="panel link-item<?= $l['is_active'] ? '' : ' is-off' ?>">
        <div class="link-order">
          <form method="post" action="/compte/liens"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><input type="hidden" name="action" value="up">
            <button class="icon-btn" type="submit" aria-label="Monter « <?= e($l['label']) ?> »"<?= $i === 0 ? ' disabled' : '' ?>><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 15l6-6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button></form>
          <form method="post" action="/compte/liens"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><input type="hidden" name="action" value="down">
            <button class="icon-btn" type="submit" aria-label="Descendre « <?= e($l['label']) ?> »"<?= $i === count($links) - 1 ? ' disabled' : '' ?>><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button></form>
        </div>
        <form method="post" action="/compte/liens" class="form inline-form grow">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
          <label>Libellé<input type="text" name="label" maxlength="80" required value="<?= e($l['label']) ?>"></label>
          <label class="grow">Adresse<input type="text" name="url" maxlength="500" required value="<?= e($l['url']) ?>"></label>
          <label class="check"><input type="checkbox" name="is_active" value="1"<?= $l['is_active'] ? ' checked' : '' ?>> Visible</label>
          <span class="clicks"><?= $clicks[(int) $l['id']] ?? 0 ?> clic(s)</span>
          <button class="btn btn-light" type="submit" name="action" value="update">Enregistrer</button>
        </form>
        <form method="post" action="/compte/liens" data-confirm="Supprimer ce lien ?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><input type="hidden" name="action" value="delete">
          <button class="btn btn-text danger" type="submit">Supprimer</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
