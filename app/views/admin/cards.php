<div class="page-head">
  <div>
    <h1>Cartes NFC</h1>
    <p class="muted">Chaque code devient l'URL encodée dans la puce et le QR : <span class="mono"><?= e(url('/t/')) ?>CODE</span>. Une carte peut être réassignée sans être reprogrammée.</p>
  </div>
  <div class="head-actions">
    <a class="btn btn-light" href="/admin/cartes/qr?statut=<?= e($filter ?: 'all') ?>" target="_blank">QR codes</a>
    <a class="btn btn-light" href="/admin/cartes/export.csv?statut=<?= e($filter ?: 'all') ?>">Exporter CSV</a>
  </div>
</div>

<div class="panel">
  <h2 class="h2">Générer des codes</h2>
  <form method="post" action="/admin/cartes" class="form inline-form">
    <?= csrf_field() ?>
    <label>Nombre<input type="number" name="count" min="1" max="500" value="10" required></label>
    <label>Matière
      <select name="material"><?php foreach (array_slice(CARD_TYPES, 0, 4) as $t): ?><option><?= e($t) ?></option><?php endforeach; ?></select>
    </label>
    <button class="btn btn-primary" type="submit">Générer</button>
  </form>
</div>

<nav class="tabs" aria-label="Filtrer">
  <?php foreach (['' => 'Toutes', 'stock' => 'En stock', 'active' => 'Actives', 'disabled' => 'Désactivées'] as $k => $label): ?>
    <a href="/admin/cartes<?= $k ? '?statut=' . $k : '' ?>"<?= $filter === $k ? ' aria-current="true"' : '' ?>><?= $label ?></a>
  <?php endforeach; ?>
</nav>

<div class="panel table-wrap">
  <?php if (!$cards): ?>
    <p class="muted empty-row">Aucune carte.</p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>Code</th><th>Matière</th><th>Commande</th><th class="num">Taps</th><th>Profil (adresse)</th><th>UID NFC</th><th>Statut</th><th><span class="visually-hidden">Action</span></th></tr></thead>
    <tbody>
      <?php foreach ($cards as $c): $fid = 'card-' . (int) $c['id']; ?>
        <tr>
          <td class="mono strong"><?= e($c['code']) ?></td>
          <td><?= e($c['material']) ?></td>
          <td><?= $c['reference'] ? '<a href="/admin/commandes/' . (int) $c['order_id'] . '">' . e($c['reference']) . '</a>' : '—' ?></td>
          <td class="num"><?= (int) $c['taps'] ?></td>
          <td><input form="<?= $fid ?>" type="text" name="slug" value="<?= e($c['slug'] ?? '') ?>" placeholder="adresse-du-profil" aria-label="Profil de la carte <?= e($c['code']) ?>" class="cell-input"></td>
          <td><input form="<?= $fid ?>" type="text" name="nfc_uid" value="<?= e($c['nfc_uid'] ?? '') ?>" placeholder="04:A2:…" aria-label="UID NFC de la carte <?= e($c['code']) ?>" class="cell-input mono"></td>
          <td>
            <select form="<?= $fid ?>" name="status" aria-label="Statut de la carte <?= e($c['code']) ?>" class="cell-input">
              <?php foreach (['stock' => 'En stock', 'active' => 'Active', 'disabled' => 'Désactivée'] as $k => $label): ?><option value="<?= $k ?>"<?= $c['status'] === $k ? ' selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
            </select>
          </td>
          <td class="right">
            <form id="<?= $fid ?>" method="post" action="/admin/cartes/<?= (int) $c['id'] ?>"><?= csrf_field() ?><input type="hidden" name="back" value="<?= e($filter) ?>"><button class="btn btn-light btn-sm" type="submit">Enregistrer</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
