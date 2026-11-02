<div class="page-head">
  <div>
    <h1>Designs</h1>
    <p class="muted">Photos de vos réalisations affichées sur l'accueil, dans « Nos designs ». La première photo de chaque matière illustre aussi sa carte.</p>
  </div>
  <a class="btn btn-light" href="/#materiaux" target="_blank" rel="noopener">Voir sur l'accueil</a>
</div>

<form method="post" action="/admin/designs" enctype="multipart/form-data" class="panel form">
  <?= csrf_field() ?>
  <h2 class="h2">Ajouter des photos</h2>
  <div class="grid-2">
    <label>Photos (plusieurs possibles)<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required></label>
    <label>Matière
      <select name="material"><?php foreach (['Standard', 'Premium', 'Bois', 'Métal'] as $m): ?><option><?= $m ?></option><?php endforeach; ?></select>
    </label>
    <label class="span-2">Titre <span class="hint">facultatif ; sinon le nom du fichier. Ex. « Gestion JC — carte noire et or »</span><input type="text" name="title" maxlength="120"></label>
  </div>
  <p class="hint">JPG, PNG ou WEBP, 15 Mo max. Les photos sont redimensionnées automatiquement. Format conseillé : carte bien cadrée, fond uni.</p>
  <div><button class="btn btn-primary" type="submit">Ajouter</button></div>
</form>

<?php if (!$designs): ?>
  <div class="panel empty">
    <p><strong>Aucune photo pour l'instant.</strong></p>
    <p class="muted">Tant qu'il n'y en a pas, l'accueil affiche des aplats de couleur pour chaque matière.</p>
  </div>
<?php else: ?>
  <div class="design-grid">
    <?php foreach ($designs as $i => $d): ?>
      <div class="panel design-item<?= $d['is_visible'] ? '' : ' is-off' ?>">
        <img src="/uploads/designs/<?= e($d['thumb_path']) ?>" alt="<?= e($d['title']) ?>" loading="lazy">
        <form method="post" action="/admin/designs/<?= (int) $d['id'] ?>" class="form">
          <?= csrf_field() ?>
          <label class="visually-hidden" for="dt-<?= (int) $d['id'] ?>">Titre</label>
          <input id="dt-<?= (int) $d['id'] ?>" type="text" name="title" maxlength="120" value="<?= e($d['title']) ?>" class="cell-input">
          <div class="design-row">
            <select name="material" class="cell-input" aria-label="Matière"><?php foreach (['Standard', 'Premium', 'Bois', 'Métal'] as $m): ?><option<?= $d['material'] === $m ? ' selected' : '' ?>><?= $m ?></option><?php endforeach; ?></select>
            <label class="check"><input type="checkbox" name="is_visible" value="1"<?= $d['is_visible'] ? ' checked' : '' ?>> Visible</label>
          </div>
          <div class="design-row">
            <button class="btn btn-light btn-sm" type="submit" name="action" value="save">Enregistrer</button>
            <button class="icon-btn" type="submit" name="action" value="up" aria-label="Avancer"<?= $i === 0 ? ' disabled' : '' ?>>←</button>
            <button class="icon-btn" type="submit" name="action" value="down" aria-label="Reculer"<?= $i === count($designs) - 1 ? ' disabled' : '' ?>>→</button>
          </div>
        </form>
        <form method="post" action="/admin/designs/<?= (int) $d['id'] ?>" data-confirm="Supprimer cette photo ?">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete">
          <button class="btn btn-text danger btn-sm" type="submit">Supprimer</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
