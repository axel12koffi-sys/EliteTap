<div class="page-head">
  <div>
    <h1>Clients</h1>
    <p class="muted">Les comptes se créent depuis une commande (bouton « Créer le compte client »).</p>
  </div>
</div>
<div class="panel table-wrap">
  <?php if (!$clients): ?>
    <p class="muted empty-row">Aucun client pour l'instant.</p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>Client</th><th>Profil</th><th class="num">Cartes actives</th><th class="num">Événements</th><th>Dernière connexion</th><th><span class="visually-hidden">Action</span></th></tr></thead>
    <tbody>
      <?php foreach ($clients as $c): ?>
        <tr>
          <td><strong><?= e($c['name']) ?></strong><br><span class="muted small"><?= e($c['email']) ?></span></td>
          <td><?= $c['slug'] ? '<a href="/u/' . e($c['slug']) . '" target="_blank" rel="noopener">/u/' . e($c['slug']) . '</a>' : '—' ?></td>
          <td class="num"><?= (int) $c['cards'] ?></td>
          <td class="num"><?= (int) $c['events'] ?></td>
          <td class="small muted"><?= $c['last_login_at'] ? date_fr($c['last_login_at']) : 'Jamais' ?></td>
          <td class="right">
            <form method="post" action="/admin/clients/<?= (int) $c['id'] ?>/lien" data-confirm="Envoyer un lien d'accès à <?= e($c['email']) ?> ?">
              <?= csrf_field() ?><button class="btn btn-light btn-sm" type="submit">Renvoyer l'accès</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
