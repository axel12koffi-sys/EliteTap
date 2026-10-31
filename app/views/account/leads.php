<div class="page-head">
  <div>
    <h1><?= te('Prospects') ?></h1>
    <p class="muted"><?= te('Les visiteurs de votre profil qui vous ont laissé leurs coordonnées, avec leur consentement.') ?></p>
  </div>
  <?php if ($leads): ?>
  <div class="head-actions">
    <a class="btn btn-light" href="/compte/prospects.csv"><?= te('Exporter (CSV)') ?></a>
  </div>
  <?php endif; ?>
</div>

<?php if (!$leads): ?>
  <div class="panel empty">
    <p><strong><?= te('Aucun prospect pour l\'instant.') ?></strong></p>
    <p class="muted"><?= te('Le formulaire « Laissez vos coordonnées » s\'affiche en bas de votre profil. Chaque envoi arrive ici et par courriel.') ?></p>
  </div>
<?php else: ?>
  <div class="panel table-wrap">
    <table class="table">
      <thead><tr><th><?= te('Contact') ?></th><th><?= te('Courriel') ?></th><th><?= te('Téléphone') ?></th><th><?= te('Carte d\'origine') ?></th><th><?= te('Date') ?></th><th><span class="visually-hidden"><?= te('Supprimer') ?></span></th></tr></thead>
      <tbody>
        <?php foreach ($leads as $l): ?>
          <tr>
            <td>
              <strong><?= e($l['name']) ?></strong><?php if (!$l['read_at']): ?> <span class="status status-active"><?= te('Nouveau') ?></span><?php endif; ?>
              <?php if ($l['company'] !== ''): ?><br><span class="muted small"><?= e($l['company']) ?></span><?php endif; ?>
              <?php if ($l['message'] !== ''): ?><div class="lead-msg"><?= nl2br(e($l['message'])) ?></div><?php endif; ?>
            </td>
            <td><?= $l['email'] !== '' ? '<a href="mailto:' . e($l['email']) . '">' . e($l['email']) . '</a>' : '—' ?></td>
            <td><?= $l['phone'] !== '' ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $l['phone'])) . '">' . e($l['phone']) . '</a>' : '—' ?></td>
            <td><span class="status status-stock"><?= $l['material'] ? te($l['material']) : te('Lien ou QR') ?></span></td>
            <td class="small muted"><?= date(lang() === 'en' ? 'm/d/Y H:i' : 'd/m/Y H:i', strtotime($l['created_at'])) ?></td>
            <td class="right">
              <form method="post" action="/compte/prospects" data-confirm="<?= te('Supprimer ce prospect ?') ?>">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                <button class="btn btn-text danger" type="submit"><?= te('Supprimer') ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="panel">
    <h2 class="h2"><?= te('Confidentialité (Loi 25)') ?></h2>
    <p class="muted"><?= te('Chaque prospect a coché une case de consentement ; le texte accepté et la date sont conservés. Les prospects sont supprimés automatiquement après {n} jours. Si une personne vous demande d\'effacer ses coordonnées, supprimez-la ici.', ['n' => $retention]) ?></p>
    <form method="post" action="/compte/prospects" class="row-actions" data-confirm="<?= te('Supprimer définitivement tous les prospects ?') ?>">
      <?= csrf_field() ?><input type="hidden" name="all" value="1">
      <button class="btn btn-light danger" type="submit"><?= te('Tout supprimer') ?></button>
    </form>
  </div>
<?php endif; ?>
