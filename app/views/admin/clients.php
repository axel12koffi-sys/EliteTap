<div class="page-head">
  <div>
    <h1>Clients</h1>
    <p class="muted">Depuis une commande (« Créer le compte client ») ou directement ici, par exemple pour les clients de l'ancien site.</p>
  </div>
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
      <label>Carte à activer sur son profil
        <select name="card_id">
          <option value="">Aucune pour l'instant</option>
          <?php foreach ($stock as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['code']) ?> · <?= e($s['material']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Langue du client
        <select name="lang"><option value="fr">Français</option><option value="en">English</option></select>
      </label>
      <label class="check span-2"><input type="checkbox" name="send_access" value="1" checked> Envoyer au client son lien d'accès (72 h)</label>
    </div>
    <?php if (!$stock): ?><p class="hint">Aucun code en stock : <a href="/admin/cartes">générez des codes</a> pour pouvoir en relier un.</p><?php endif; ?>
    <div><button class="btn btn-primary" type="submit">Créer le client</button></div>
  </form>
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
