<?php
[$cover, $accent] = profile_colors($p);
$name = full_name($p) ?: $p['company'];
?>
<div class="page-head">
  <div>
    <h1><?= te('Mon profil') ?></h1>
    <p class="muted"><?= te('Forfait Essentiel : votre profil est hébergé à vie et mis à jour par l\'équipe EliteTap.') ?></p>
  </div>
  <div class="head-actions">
    <a class="btn btn-light" href="/u/<?= e($p['slug']) ?>" target="_blank" rel="noopener"><?= te('Voir mon profil') ?></a>
  </div>
</div>

<div class="editor">
  <div class="editor-form" style="display: flex; flex-direction: column; gap: 24px;">
    <form method="post" action="/compte/theme" class="panel form">
      <?= csrf_field() ?>
      <h2 class="h2"><?= te('Choisissez votre thème') ?></h2>
      <div class="themes" role="radiogroup" aria-label="<?= te('Thème') ?>">
        <?php foreach (THEMES as $k => [$label, $c, $a]): ?>
          <label class="theme"><input type="radio" name="theme" value="<?= e($k) ?>"<?= $p['theme'] === $k ? ' checked' : '' ?>>
            <span class="theme-swatch" style="background: linear-gradient(90deg, <?= e($c) ?> 70%, <?= e($a) ?> 70%)"></span><span><?= te($label) ?></span></label>
        <?php endforeach; ?>
      </div>
      <div><button class="btn btn-primary" type="submit"><?= te('Appliquer le thème') ?></button></div>
    </form>

    <div class="panel">
      <h2 class="h2"><?= te('Une modification à faire ?') ?></h2>
      <p class="muted"><?= te('Nouveau numéro, nouveau poste, nouvelle photo : écrivez-nous, nous mettons votre profil à jour.') ?></p>
      <p class="row-actions">
        <a class="btn btn-light" href="mailto:info@elitetap.ca?subject=<?= rawurlencode(t('Modification de mon profil') . ' ' . public_host() . '/u/' . $p['slug']) ?>">info@elitetap.ca</a>
        <a class="btn btn-light" href="https://wa.me/<?= e(config('whatsapp')) ?>" rel="noopener">WhatsApp</a>
      </p>
    </div>

    <div class="panel upsell">
      <h2 class="h2"><span class="tag-pro">Pro</span> <?= te('Gérez votre profil vous-même') ?></h2>
      <ul class="checklist">
        <li><?= te('Modifiez vos informations, photo et liens à tout moment') ?></li>
        <li><?= te('Recevez des prospects : vos visiteurs laissent leurs coordonnées') ?></li>
        <li><?= te('Voyez vos touchers, visites et prospects') ?></li>
        <li><?= te('Couleurs, logo et police de votre marque') ?></li>
      </ul>
      <p class="row-actions"><a class="btn btn-primary" href="/compte/forfait"><?= te('Découvrir le forfait Pro') ?></a></p>
    </div>
  </div>

  <div class="preview" aria-label="<?= te('Aperçu') ?>">
    <span class="preview-label"><?= te('Votre profil') ?></span>
    <div class="phone">
      <div class="phone-cover" style="background: <?= e($cover) ?>"></div>
      <?php if ($p['avatar_path']): ?>
        <img class="phone-avatar" src="/uploads/avatars/<?= e($p['avatar_path']) ?>" alt="">
      <?php else: ?>
        <div class="phone-avatar" style="background: <?= e($accent) ?>; color: <?= e(on_color($accent)) ?>"><?= e(initials($p)) ?></div>
      <?php endif; ?>
      <div class="phone-id">
        <strong><?= e($name ?: t('Votre nom')) ?></strong>
        <span><?= e(implode(' · ', array_filter([$p['title'], $p['company']]))) ?></span>
      </div>
      <div class="phone-save" style="background: <?= e($accent) ?>; color: <?= e(on_color($accent)) ?>"><?= te('Enregistrer le contact') ?></div>
    </div>
    <p class="hint"><?= e(public_host()) ?>/u/<?= e($p['slug']) ?></p>
  </div>
</div>
