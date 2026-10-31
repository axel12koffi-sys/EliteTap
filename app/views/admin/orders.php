<div class="page-head">
  <h1>Commandes</h1>
  <form method="get" action="/admin" class="search">
    <?php if ($status): ?><input type="hidden" name="statut" value="<?= e($status) ?>"><?php endif; ?>
    <label class="visually-hidden" for="q">Rechercher</label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Client, courriel, référence">
    <button class="btn btn-light" type="submit">Rechercher</button>
  </form>
</div>

<?php require __DIR__ . '/_kpis.php'; ?>

<nav class="tabs" aria-label="Filtrer par statut">
  <a href="/admin"<?= $status === '' ? ' aria-current="true"' : '' ?>>Toutes <span><?= $counts[''] ?? 0 ?></span></a>
  <?php foreach (ORDER_STATUSES as $k => $label): ?>
    <a href="/admin?statut=<?= $k ?>"<?= $status === $k ? ' aria-current="true"' : '' ?>><?= e($label) ?> <span><?= $counts[$k] ?? 0 ?></span></a>
  <?php endforeach; ?>
</nav>

<div class="panel table-wrap">
  <?php if (!$orders): ?>
    <p class="muted empty-row">Aucune commande<?= $search !== '' ? ' pour cette recherche' : '' ?>.</p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>Référence</th><th>Client</th><th>Carte</th><th class="num">Qté</th><th class="num">Montant</th><th>Statut</th><th>Reçue</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td class="strong"><?= e($o['reference']) ?></td>
          <td><?= e($o['name']) ?><?= $o['company'] !== '' ? '<br><span class="muted small">' . e($o['company']) . '</span>' : '' ?></td>
          <td><?= e($o['card_type']) ?></td>
          <td class="num"><?= (int) $o['quantity'] ?></td>
          <td class="num"><?= money($o['amount_cents'] !== null ? (int) $o['amount_cents'] : null) ?><?= $o['paid_at'] ? '<br><span class="status status-active">Payée</span>' : '' ?></td>
          <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(ORDER_STATUSES[$o['status']] ?? $o['status']) ?></span></td>
          <td class="small muted"><?= date_fr($o['created_at']) ?></td>
          <td class="right"><a href="/admin/commandes/<?= (int) $o['id'] ?>">Ouvrir</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
