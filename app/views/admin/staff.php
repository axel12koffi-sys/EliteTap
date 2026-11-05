<div class="page-head">
  <div>
    <h1>Employés</h1>
    <p class="muted">Chaque employé voit seulement les pages de ses services. Tous ont accès à la discussion d'équipe.</p>
  </div>
</div>

<div class="cols">
  <div class="col-main">
    <div class="panel table-wrap">
      <table class="table">
        <thead><tr><th>Membre</th><th>Services</th><th>Dernière activité</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
        <tbody>
          <?php foreach ($members as $m): $isStaff = $m['role'] === 'staff'; $mine = array_flip(user_services($m)); ?>
            <tr>
              <td><span class="member-dot<?= member_online($m) ? ' is-on' : '' ?>" aria-label="<?= member_online($m) ? 'En ligne' : 'Hors ligne' ?>"></span>
                <strong><?= e($m['name']) ?></strong><br><span class="muted small"><?= e($m['email']) ?></span>
                <?php if (!$m['is_active']): ?><br><span class="status status-disabled">Désactivé</span><?php endif; ?></td>
              <td>
                <?php if ($isStaff): ?>
                  <form method="post" action="/admin/employes/<?= (int) $m['id'] ?>" class="services-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="services">
                    <?php foreach (SERVICES as $k => $label): ?>
                      <label class="check-inline"><input type="checkbox" name="services[]" value="<?= $k ?>"<?= isset($mine[$k]) ? ' checked' : '' ?>> <?= e($label) ?></label>
                    <?php endforeach; ?>
                    <button class="btn btn-light btn-sm" type="submit">Enregistrer</button>
                  </form>
                <?php else: ?>
                  <span class="badge badge-design">Administrateur</span> <span class="muted small">tous les services</span>
                <?php endif; ?>
              </td>
              <td class="small muted"><?= $m['last_seen_at'] ? date_fr($m['last_seen_at']) : ($m['last_login_at'] ? date_fr($m['last_login_at']) : 'Jamais connecté') ?></td>
              <td class="right">
                <?php if ($isStaff): ?>
                  <form method="post" action="/admin/employes/<?= (int) $m['id'] ?>" class="row-actions" style="justify-content: flex-end;">
                    <?= csrf_field() ?>
                    <?php if (!$m['last_login_at']): ?><button class="btn btn-text btn-sm" name="action" value="invite">Renvoyer l'invitation</button><?php endif; ?>
                    <button class="btn btn-text btn-sm" name="action" value="toggle"><?= $m['is_active'] ? 'Désactiver' : 'Réactiver' ?></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <aside class="col-side">
    <form method="post" action="/admin/employes" class="panel form">
      <?= csrf_field() ?>
      <h2 class="h2">Ajouter un employé</h2>
      <label>Nom<input type="text" name="name" required maxlength="120" value="<?= e(old('name')) ?>"></label>
      <label>Courriel<input type="email" name="email" required value="<?= e(old('email')) ?>">
        <span class="hint">Il reçoit un lien pour choisir son mot de passe (72 h).</span></label>
      <fieldset class="fieldset">
        <legend>Services</legend>
        <?php foreach (SERVICES as $k => $label): ?>
          <label class="check"><input type="checkbox" name="services[]" value="<?= $k ?>"> <?= e($label) ?></label>
        <?php endforeach; ?>
      </fieldset>
      <button class="btn btn-primary" type="submit">Ajouter et inviter</button>
    </form>
    <div class="panel">
      <h2 class="h2">Accès par service</h2>
      <dl class="dl dl-wide">
        <dt>Service client</dt><dd>Vue d'ensemble, clavardage du site, messages, infolettre, commandes (statut, compte client), clients (lecture), présence</dd>
        <dt>Comptabilité</dt><dd>Comptabilité (ventes, taxes, export), commandes, liens de paiement, paiements reçus</dd>
        <dt>Administrateur</dt><dd>Tout, dont employés, cartes, designs, réglages</dd>
      </dl>
    </div>
  </aside>
</div>
