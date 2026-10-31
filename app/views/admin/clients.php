<div class="page-head">
  <div>
    <h1>Clients</h1>
    <p class="muted">Forfait, activation et gestion du profil de chaque client. « Gérer le profil » ouvre son espace pour faire les modifications (forfait Essentiel).</p>
  </div>
</div>

<?php require __DIR__ . '/_kpis.php'; ?>

<nav class="tabs" aria-label="Filtrer">
  <?php foreach (['' => 'Tous', 'essentiel' => 'Essentiel', 'pro' => 'Pro', 'entreprise' => 'Entreprise', 'inactif' => 'Désactivés'] as $k => $label): ?>
    <a href="/admin/clients<?= $k ? '?forfait=' . $k : '' ?>"<?= $filter === $k ? ' aria-current="true"' : '' ?>><?= $label ?></a>
  <?php endforeach; ?>
</nav>

<div class="panel table-wrap">
  <?php if (!$clients): ?>
    <p class="muted empty-row">Aucun client dans cette liste.</p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>Client</th><th>Profil</th><th>Forfait</th><th class="num">Cartes</th><th class="num">Ouvertures 30 j</th><th class="num">Prospects 30 j</th><th>Dernière connexion</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
    <tbody>
      <?php foreach ($clients as $c): ?>
        <tr<?= $c['is_active'] ? '' : ' class="is-off"' ?>>
          <td><strong><?= e($c['name']) ?></strong><?= $c['is_active'] ? '' : ' <span class="status status-disabled">Désactivé</span>' ?><br><span class="muted small"><?= e($c['email']) ?></span></td>
          <td><?= $c['slug'] ? '<a href="/u/' . e($c['slug']) . '" target="_blank" rel="noopener">/u/' . e($c['slug']) . '</a>' : '—' ?></td>
          <td>
            <span class="plan-badge <?= e($c['plan']) ?>"><?= e(PLANS[$c['plan']] ?? $c['plan']) ?></span>
            <?php if ($c['plan_source'] === 'stripe'): ?><br><span class="muted small">Stripe<?= $c['plan_status'] && $c['plan_status'] !== 'active' ? ' · ' . e($c['plan_status']) : '' ?></span><?php endif; ?>
          </td>
          <td class="num"><?= (int) $c['cards'] ?></td>
          <td class="num"><?= (int) $c['opens'] ?></td>
          <td class="num"><?= (int) $c['leads'] ?></td>
          <td class="small muted"><?= $c['last_login_at'] ? date_fr($c['last_login_at']) : 'Jamais' ?></td>
          <td>
            <div class="row-forms">
              <form method="post" action="/admin/clients/<?= (int) $c['id'] ?>/gerer"><?= csrf_field() ?><button class="btn btn-primary btn-sm" type="submit">Gérer le profil</button></form>
              <form method="post" action="/admin/clients/<?= (int) $c['id'] ?>/forfait" class="row-forms">
                <?= csrf_field() ?>
                <label class="visually-hidden" for="plan-<?= (int) $c['id'] ?>">Forfait de <?= e($c['name']) ?></label>
                <select id="plan-<?= (int) $c['id'] ?>" name="plan"><?php foreach (PLANS as $k => $label): ?><option value="<?= $k ?>"<?= $c['plan'] === $k ? ' selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select>
                <button class="btn btn-light btn-sm" type="submit">OK</button>
              </form>
              <form method="post" action="/admin/clients/<?= (int) $c['id'] ?>/statut"<?= $c['is_active'] ? ' data-confirm="Désactiver ' . e($c['name']) . ' ? Son profil et ses cartes ne s\'ouvriront plus."' : '' ?>><?= csrf_field() ?><button class="btn btn-light btn-sm<?= $c['is_active'] ? ' danger' : '' ?>" type="submit"><?= $c['is_active'] ? 'Désactiver' : 'Réactiver' ?></button></form>
              <form method="post" action="/admin/clients/<?= (int) $c['id'] ?>/lien" data-confirm="Envoyer un lien d'accès à <?= e($c['email']) ?> ?"><?= csrf_field() ?><button class="btn btn-text btn-sm" type="submit">Renvoyer l'accès</button></form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<div class="panel">
  <h2 class="h2">Nouveau client</h2>
  <form method="post" action="/admin/clients" class="form">
    <?= csrf_field() ?>
    <div class="grid-2">
      <label>Nom complet<input type="text" name="name" required maxlength="120" value="<?= e(old('name')) ?>"></label>
      <label>Courriel<input type="email" name="email" required maxlength="190" value="<?= e(old('email')) ?>"></label>
      <label>Entreprise <span class="hint">optionnel</span><input type="text" name="company" maxlength="120" value="<?= e(old('company')) ?>"></label>
      <label>Téléphone <span class="hint">optionnel</span><input type="tel" name="phone" maxlength="40" value="<?= e(old('phone')) ?>"></label>
      <label>Forfait
        <select name="plan"><?php foreach (PLANS as $k => $label): ?><option value="<?= $k ?>"><?= $label ?></option><?php endforeach; ?></select>
      </label>
      <label>Carte à activer sur son profil
        <select name="card_id">
          <option value="">Aucune pour l'instant</option>
          <?php foreach ($stock as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['code']) ?> · <?= e($s['material']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Langue du client
        <select name="lang"><option value="fr">Français</option><option value="en">English</option></select>
      </label>
      <label class="check"><input type="checkbox" name="send_access" value="1" checked> Envoyer au client son lien d'accès (72 h)</label>
    </div>
    <?php if (!$stock): ?><p class="hint">Aucun code en stock : <a href="/admin/cartes">générez des codes</a> pour pouvoir en relier un.</p><?php endif; ?>
    <div><button class="btn btn-primary" type="submit">Créer le client</button></div>
  </form>
</div>
