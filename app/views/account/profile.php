<?php
$v = fn(string $k) => old($k, (string) $p[$k]);
$name = full_name($p) ?: $p['company'];
?>
<div class="page-head">
  <div>
    <h1>Mon profil</h1>
    <p class="muted">Les changements sont visibles dès l'enregistrement, sur toutes vos cartes.</p>
  </div>
  <div class="head-actions">
    <a class="btn btn-light" href="/u/<?= e($p['slug']) ?>" target="_blank" rel="noopener">Voir mon profil</a>
    <button class="btn btn-primary" type="submit" form="profile-form">Enregistrer</button>
  </div>
</div>

<div class="kpis">
  <div class="kpi"><span>Ouvertures (30 j)</span><strong><?= $totals['view'] + $totals['tap'] ?></strong></div>
  <div class="kpi"><span>Contacts enregistrés</span><strong><?= $totals['vcard'] ?></strong></div>
  <div class="kpi"><span>Clics sur liens</span><strong><?= $totals['link'] ?></strong></div>
  <div class="kpi"><span>Cartes actives</span><strong><?= $activeCards ?></strong></div>
</div>

<div class="editor">
  <div class="panel editor-form">
    <div class="avatar-row">
      <?php if ($p['avatar_path']): ?>
        <img class="avatar-sm" src="/uploads/avatars/<?= e($p['avatar_path']) ?>" alt="">
      <?php else: ?>
        <div class="avatar-sm avatar-initials" style="background: <?= e($p['accent_color']) ?>; color: <?= e(on_color($p['accent_color'])) ?>"><?= e(initials($p)) ?></div>
      <?php endif; ?>
      <form method="post" action="/compte/photo" enctype="multipart/form-data" class="avatar-actions">
        <?= csrf_field() ?>
        <label class="btn btn-light">Changer la photo<input type="file" name="avatar" id="avatar-input" accept="image/jpeg,image/png,image/webp" class="visually-hidden"></label>
        <noscript><button class="btn btn-light" type="submit">Envoyer</button></noscript>
        <?php if ($p['avatar_path']): ?><button class="btn btn-text" type="submit" name="remove" value="1">Retirer</button><?php endif; ?>
      </form>
    </div>

    <form method="post" action="/compte" id="profile-form" class="form grid-2">
      <?= csrf_field() ?>
      <label>Prénom<input type="text" name="first_name" value="<?= e($v('first_name')) ?>" autocomplete="given-name"></label>
      <label>Nom<input type="text" name="last_name" value="<?= e($v('last_name')) ?>" autocomplete="family-name"></label>
      <label>Titre<input type="text" name="title" value="<?= e($v('title')) ?>" placeholder="Courtière immobilière"></label>
      <label>Entreprise<input type="text" name="company" value="<?= e($v('company')) ?>" autocomplete="organization"></label>
      <label>Téléphone<input type="tel" name="phone" value="<?= e($v('phone')) ?>" autocomplete="tel"></label>
      <label>Courriel affiché<input type="email" name="email" value="<?= e($v('email')) ?>"></label>
      <label class="span-2">Courte présentation <span class="hint">300 caractères max</span><textarea name="bio" rows="3" maxlength="300"><?= e($v('bio')) ?></textarea></label>
      <label class="span-2">Site web<input type="url" name="website" value="<?= e($v('website')) ?>" placeholder="https://"></label>
      <label class="span-2">Adresse <span class="hint">ajoute un bouton Itinéraire</span><input type="text" name="address" value="<?= e($v('address')) ?>" autocomplete="street-address"></label>

      <fieldset class="span-2 fieldset">
        <legend>Apparence</legend>
        <div class="grid-2">
          <label>Couleur de la bannière<input type="color" name="cover_color" value="<?= e($p['cover_color']) ?>"></label>
          <label>Couleur des boutons<input type="color" name="accent_color" value="<?= e($p['accent_color']) ?>"></label>
        </div>
      </fieldset>

      <fieldset class="span-2 fieldset">
        <legend>Adresse du profil</legend>
        <label class="prefix-input"><span><?= e(public_host()) ?>/u/</span><input type="text" name="slug" value="<?= e($v('slug')) ?>" pattern="[a-z0-9-]{2,60}" maxlength="60" aria-label="Adresse du profil"></label>
        <p class="hint">Vos cartes continuent de fonctionner si vous la changez. Les anciens liens partagés, eux, ne fonctionneront plus.</p>
        <label class="check"><input type="checkbox" name="is_public" value="1"<?= $p['is_public'] ? ' checked' : '' ?>> Profil public (décocher pour le masquer temporairement)</label>
      </fieldset>
      <button class="btn btn-primary span-2" type="submit">Enregistrer</button>
    </form>
  </div>

  <div class="preview" aria-label="Aperçu">
    <span class="preview-label">Aperçu en direct</span>
    <div class="phone">
      <div class="phone-cover" data-pv="cover" style="background: <?= e($p['cover_color']) ?>"></div>
      <?php if ($p['avatar_path']): ?>
        <img class="phone-avatar" src="/uploads/avatars/<?= e($p['avatar_path']) ?>" alt="">
      <?php else: ?>
        <div class="phone-avatar" data-pv="accent" style="background: <?= e($p['accent_color']) ?>; color: <?= e(on_color($p['accent_color'])) ?>"><span data-pv="initials"><?= e(initials($p)) ?></span></div>
      <?php endif; ?>
      <div class="phone-id">
        <strong data-pv="name"><?= e($name ?: 'Votre nom') ?></strong>
        <span data-pv="sub"><?= e(implode(' · ', array_filter([$p['title'], $p['company']]))) ?></span>
        <p data-pv="bio"<?= $p['bio'] === '' ? ' hidden' : '' ?>><?= e($p['bio']) ?></p>
      </div>
      <div class="phone-save" data-pv="accent" style="background: <?= e($p['accent_color']) ?>; color: <?= e(on_color($p['accent_color'])) ?>">Enregistrer le contact</div>
      <div class="phone-links">
        <?php foreach (array_slice($links, 0, 4) as $l): ?><span><?= e($l['label']) ?></span><?php endforeach; ?>
        <?php if (!$links): ?><a href="/compte/liens">+ Ajouter des liens</a><?php endif; ?>
      </div>
    </div>
    <p class="hint"><?= e(public_host()) ?>/u/<span data-pv="slug"><?= e($p['slug']) ?></span></p>
  </div>
</div>
