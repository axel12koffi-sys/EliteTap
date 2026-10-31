<?php
$v = fn(string $k) => old($k, (string) $p[$k]);
$name = full_name($p) ?: $p['company'];
[$cover, $accent] = profile_colors($p);
$brand = can('brand');
$leads = can('leads');
$themes = [];
foreach (THEMES as $k => [$label, $c, $a]) $themes[$k] = ['cover' => $c, 'accent' => $a];
$fontStack = profile_font($p)[2];
?>
<div class="page-head">
  <div>
    <h1><?= te('Mon profil') ?></h1>
    <p class="muted"><?= te('Les changements sont visibles dès l\'enregistrement, sur toutes vos cartes.') ?></p>
  </div>
  <div class="head-actions">
    <a class="btn btn-light" href="/u/<?= e($p['slug']) ?>" target="_blank" rel="noopener"><?= te('Voir mon profil') ?></a>
    <button class="btn btn-primary" type="submit" form="profile-form"><?= te('Enregistrer') ?></button>
  </div>
</div>

<div class="kpis">
  <div class="kpi"><span><?= te('Ouvertures (30 j)') ?></span><strong><?= $totals['view'] + $totals['tap'] ?></strong></div>
  <div class="kpi"><span><?= te('Contacts enregistrés') ?></span><strong><?= $totals['vcard'] ?></strong></div>
  <a class="kpi kpi-link" href="/compte/prospects"><span><?= te('Prospects (30 j)') ?><?php if ($newLeads): ?> <em class="new"><?= te('{n} nouveau(x)', ['n' => $newLeads]) ?></em><?php endif; ?></span><strong><?= $totals['lead'] ?></strong></a>
  <div class="kpi"><span><?= te('Cartes actives') ?></span><strong><?= $activeCards ?></strong></div>
</div>

<div class="editor">
  <div class="panel editor-form">
    <div class="avatar-row">
      <?php if ($p['avatar_path']): ?>
        <img class="avatar-sm" src="/uploads/avatars/<?= e($p['avatar_path']) ?>" alt="">
      <?php else: ?>
        <div class="avatar-sm avatar-initials" style="background: <?= e($accent) ?>; color: <?= e(on_color($accent)) ?>"><?= e(initials($p)) ?></div>
      <?php endif; ?>
      <form method="post" action="/compte/photo" enctype="multipart/form-data" class="avatar-actions">
        <?= csrf_field() ?>
        <label class="btn btn-light"><?= te('Changer la photo') ?><input type="file" name="avatar" id="avatar-input" accept="image/jpeg,image/png,image/webp" class="visually-hidden"></label>
        <noscript><button class="btn btn-light" type="submit"><?= te('Envoyer') ?></button></noscript>
        <?php if ($p['avatar_path']): ?><button class="btn btn-text" type="submit" name="remove" value="1"><?= te('Retirer') ?></button><?php endif; ?>
      </form>
    </div>

    <form method="post" action="/compte" id="profile-form" class="form grid-2" data-themes='<?= e(json_encode($themes)) ?>'>
      <?= csrf_field() ?>
      <label><?= te('Prénom') ?><input type="text" name="first_name" value="<?= e($v('first_name')) ?>" autocomplete="given-name"></label>
      <label><?= te('Nom') ?><input type="text" name="last_name" value="<?= e($v('last_name')) ?>" autocomplete="family-name"></label>
      <label><?= te('Titre') ?><input type="text" name="title" value="<?= e($v('title')) ?>" placeholder="<?= te('Courtière immobilière') ?>"></label>
      <label><?= te('Entreprise') ?><input type="text" name="company" value="<?= e($v('company')) ?>" autocomplete="organization"></label>
      <label><?= te('Téléphone') ?><input type="tel" name="phone" value="<?= e($v('phone')) ?>" autocomplete="tel"></label>
      <label>WhatsApp <span class="hint"><?= te('avec l\'indicatif, ex. +1 514…') ?></span><input type="tel" name="whatsapp" value="<?= e($v('whatsapp')) ?>"></label>
      <label class="span-2"><?= te('Courriel affiché') ?><input type="email" name="email" value="<?= e($v('email')) ?>"></label>
      <label class="span-2"><?= te('Courte présentation') ?> <span class="hint"><?= te('300 caractères max') ?></span><textarea name="bio" rows="3" maxlength="300"><?= e($v('bio')) ?></textarea></label>
      <label class="span-2"><?= te('Site web') ?><input type="url" name="website" value="<?= e($v('website')) ?>" placeholder="https://"></label>
      <label class="span-2"><?= te('Adresse') ?> <span class="hint"><?= te('ajoute un bouton Itinéraire') ?></span><input type="text" name="address" value="<?= e($v('address')) ?>" autocomplete="street-address"></label>

      <fieldset class="span-2 fieldset" id="apparence">
        <legend><?= te('Apparence') ?></legend>
        <div class="themes" role="radiogroup" aria-label="<?= te('Thème') ?>">
          <?php foreach (THEMES as $k => [$label, $c, $a]): ?>
            <label class="theme"><input type="radio" name="theme" value="<?= e($k) ?>"<?= $p['theme'] === $k ? ' checked' : '' ?>>
              <span class="theme-swatch" style="background: linear-gradient(90deg, <?= e($c) ?> 70%, <?= e($a) ?> 70%)"></span><span><?= te($label) ?></span></label>
          <?php endforeach; ?>
          <?php if ($brand): ?>
            <label class="theme"><input type="radio" name="theme" value="custom"<?= $p['theme'] === 'custom' ? ' checked' : '' ?>>
              <span class="theme-swatch" style="background: linear-gradient(90deg, <?= e($p['cover_color']) ?> 70%, <?= e($p['accent_color']) ?> 70%)"></span><span><?= te('Mes couleurs') ?></span></label>
          <?php endif; ?>
        </div>

        <?php if ($brand): ?>
          <div class="grid-2 brand-colors">
            <label><?= te('Couleur de la bannière') ?><input type="color" name="cover_color" value="<?= e($p['cover_color']) ?>"></label>
            <label><?= te('Couleur des boutons') ?><input type="color" name="accent_color" value="<?= e($p['accent_color']) ?>"></label>
          </div>
          <p class="readability" data-ok="<?= te('Lisibilité OK') ?>" data-bad="<?= te('Texte peu lisible sur cette couleur : choisissez-en une plus foncée ou plus claire.') ?>"></p>
          <label><?= te('Police des titres') ?>
            <select name="font">
              <?php foreach (FONTS as $k => [$label, $_, $stack]): ?><option value="<?= e($k) ?>" data-stack="<?= e($stack) ?>"<?= $p['font'] === $k ? ' selected' : '' ?>><?= te($label) ?></option><?php endforeach; ?>
            </select>
          </label>
        <?php else: ?>
          <p class="locked"><span class="tag-pro">Pro</span> <?= te('Couleurs de votre marque, logo et police avec le forfait Pro.') ?> <a href="/compte/forfait"><?= te('Voir le forfait Pro') ?></a></p>
        <?php endif; ?>
      </fieldset>

      <?php if ($leads): ?>
      <fieldset class="span-2 fieldset">
        <legend><?= te('Prospects') ?></legend>
        <label class="check"><input type="checkbox" name="lead_form" value="1"<?= $p['lead_form'] ? ' checked' : '' ?>> <?= te('Afficher « Laissez vos coordonnées » sur mon profil') ?></label>
        <label class="check"><input type="checkbox" name="lead_alerts" value="1"<?= $p['lead_alerts'] ? ' checked' : '' ?>> <?= te('M\'avertir par courriel à chaque nouveau prospect') ?></label>
      </fieldset>
      <?php else: ?>
        <input type="hidden" name="lead_form" value="1"><input type="hidden" name="lead_alerts" value="1">
      <?php endif; ?>

      <fieldset class="span-2 fieldset">
        <legend><?= te('Adresse du profil') ?></legend>
        <label class="prefix-input"><span><?= e(public_host()) ?>/u/</span><input type="text" name="slug" value="<?= e($v('slug')) ?>" pattern="[a-z0-9-]{2,60}" maxlength="60" aria-label="<?= te('Adresse du profil') ?>"></label>
        <p class="hint"><?= te('Vos cartes continuent de fonctionner si vous la changez. Les anciens liens partagés, eux, ne fonctionneront plus.') ?></p>
        <label class="check"><input type="checkbox" name="is_public" value="1"<?= $p['is_public'] ? ' checked' : '' ?>> <?= te('Profil public (décocher pour le masquer temporairement)') ?></label>
      </fieldset>
      <button class="btn btn-primary span-2" type="submit"><?= te('Enregistrer') ?></button>
    </form>

    <?php if ($brand): ?>
    <div class="logo-row">
      <div class="logo-box"><?php if ($p['logo_path']): ?><img src="/uploads/avatars/<?= e($p['logo_path']) ?>" alt="<?= te('Logo') ?>"><?php else: ?><span class="hint"><?= te('Aucun logo') ?></span><?php endif; ?></div>
      <form method="post" action="/compte/logo" enctype="multipart/form-data" class="avatar-actions">
        <?= csrf_field() ?>
        <label class="btn btn-light"><?= te('Logo de l\'entreprise') ?><input type="file" name="logo" class="visually-hidden auto-submit" accept="image/png,image/jpeg,image/webp"></label>
        <noscript><button class="btn btn-light" type="submit"><?= te('Envoyer') ?></button></noscript>
        <?php if ($p['logo_path']): ?><button class="btn btn-text" type="submit" name="remove" value="1"><?= te('Retirer') ?></button><?php endif; ?>
      </form>
      <p class="hint"><?= te('PNG transparent de préférence. Il s\'affiche sous votre nom.') ?></p>
    </div>
    <?php endif; ?>
  </div>

  <div class="preview" aria-label="<?= te('Aperçu') ?>">
    <span class="preview-label"><?= te('Aperçu en direct') ?></span>
    <div class="phone">
      <div class="phone-cover" data-pv="cover" style="background: <?= e($cover) ?>"></div>
      <?php if ($p['avatar_path']): ?>
        <img class="phone-avatar" src="/uploads/avatars/<?= e($p['avatar_path']) ?>" alt="">
      <?php else: ?>
        <div class="phone-avatar" data-pv="accent" style="background: <?= e($accent) ?>; color: <?= e(on_color($accent)) ?>"><span data-pv="initials"><?= e(initials($p)) ?></span></div>
      <?php endif; ?>
      <div class="phone-id">
        <strong data-pv="name" data-empty="<?= te('Votre nom') ?>" style="font-family: <?= e($fontStack) ?>"><?= e($name ?: t('Votre nom')) ?></strong>
        <span data-pv="sub"><?= e(implode(' · ', array_filter([$p['title'], $p['company']]))) ?></span>
        <p data-pv="bio"<?= $p['bio'] === '' ? ' hidden' : '' ?>><?= e($p['bio']) ?></p>
      </div>
      <div class="phone-save" data-pv="accent" style="background: <?= e($accent) ?>; color: <?= e(on_color($accent)) ?>"><?= te('Enregistrer le contact') ?></div>
      <div class="phone-links">
        <?php foreach (array_slice($links, 0, 4) as $l): ?><span><?= e($l['label']) ?></span><?php endforeach; ?>
        <?php if (!$links): ?><a href="/compte/liens">+ <?= te('Ajouter des liens') ?></a><?php endif; ?>
      </div>
    </div>
    <p class="hint"><?= e(public_host()) ?>/u/<span data-pv="slug"><?= e($p['slug']) ?></span></p>
  </div>
</div>
