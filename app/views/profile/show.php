<?php
$name = full_name($p) ?: $p['company'];
$sub = implode(' · ', array_filter([$p['title'], $p['company'] !== $name ? $p['company'] : '']));
[$cover, $accent] = profile_colors($p);
$onAccent = on_color($accent);
[$fontLabel, $fontParam, $fontStack] = profile_font($p);
$logo = $p['logo_path'] && profile_can($p, 'brand') ? $p['logo_path'] : null;
$telHref = preg_replace('/[^0-9+]/', '', $p['phone']);
$actions = [];
if ($p['phone'] !== '') {
    $actions[] = ['tel:' . $telHref, t('Appeler'), '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" stroke-linejoin="round"/>'];
    $actions[] = ['sms:' . $telHref, t('Texto'), '<path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z" stroke-linejoin="round"/>'];
}
if ($p['whatsapp'] !== '') {
    $actions[] = ['https://wa.me/' . preg_replace('/\D/', '', $p['whatsapp']), 'WhatsApp', '<path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z" stroke-linejoin="round"/><path d="M9 9.5c.5 2 2 3.5 4.5 4.5l1-1.2 2 .8-.3 1.6c-3.6.3-7.6-3.4-7.4-7l1.6-.3.8 2z" stroke-linejoin="round"/>'];
}
if ($p['email'] !== '') {
    $actions[] = ['mailto:' . $p['email'], t('Courriel'), '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>'];
}
if ($p['website'] !== '') {
    $actions[] = [$p['website'], t('Site web'), '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>'];
}
if ($p['address'] !== '') {
    $actions[] = ['https://www.google.com/maps/search/?api=1&query=' . rawurlencode($p['address']), t('Itinéraire'), '<path d="M12 21c-5-4.5-7-8-7-11a7 7 0 0 1 14 0c0 3-2 6.5-7 11z"/><circle cx="12" cy="10" r="2.5"/>'];
}
?>
<!doctype html>
<html lang="<?= lang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($name) ?><?= $sub ? ' — ' . e($sub) : '' ?></title>
<meta name="description" content="<?= e($p['bio'] ?: ($name . ($sub ? ', ' . $sub : ''))) ?>">
<meta property="og:title" content="<?= e($name) ?>">
<meta property="og:description" content="<?= e($sub ?: t('Profil EliteTap')) ?>">
<?php if ($p['avatar_path']): ?><meta property="og:image" content="<?= e(url('/uploads/avatars/' . $p['avatar_path'])) ?>"><?php endif; ?>
<meta name="robots" content="noindex, follow">
<meta name="theme-color" content="<?= e($cover) ?>">
<link rel="icon" href="<?= asset('img/favicon.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=<?= $fontParam ?>&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/profile.css') ?>">
<style>:root{--cover:<?= e($cover) ?>;--accent:<?= e($accent) ?>;--on-accent:<?= e($onAccent) ?>;--title-font:<?= $fontStack ?>;--on-cover:<?= e(on_color($cover)) ?>;}</style>
</head>
<body>
<?php if ($isOwner): ?>
  <div class="owner-bar"><?= te('Aperçu de votre profil') ?><?= $p['is_public'] ? '' : ' (' . te('privé') . ')' ?> · <a href="/compte"><?= te('Modifier') ?></a></div>
<?php endif; ?>
<main class="profile">
  <div class="cover" aria-hidden="true">
    <svg width="26" height="26" viewBox="0 0 28 28" fill="none"><path d="M10 9a7 7 0 0 1 0 10M14 6a11 11 0 0 1 0 16M18 3a15 15 0 0 1 0 22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  </div>
  <?php if ($p['avatar_path']): ?>
    <img class="avatar" src="/uploads/avatars/<?= e($p['avatar_path']) ?>" alt="<?= te('Photo de {nom}', ['nom' => $name]) ?>" width="112" height="112">
  <?php else: ?>
    <div class="avatar avatar-initials" aria-hidden="true"><?= e(initials($p)) ?></div>
  <?php endif; ?>

  <header class="identity">
    <h1><?= e($name) ?></h1>
    <?php if ($sub): ?><p class="role"><?= e($sub) ?></p><?php endif; ?>
    <?php if ($p['bio'] !== ''): ?><p class="bio"><?= e($p['bio']) ?></p><?php endif; ?>
    <?php if ($logo): ?><img class="logo" src="/uploads/avatars/<?= e($logo) ?>" alt="<?= e($p['company'] ?: $name) ?>"><?php endif; ?>
  </header>

  <div class="block">
    <a class="save" href="/u/<?= e($p['slug']) ?>/contact.vcf">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M3 20a7 7 0 0 1 12-5M19 14v6M16 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      <?= te('Enregistrer le contact') ?>
    </a>

    <?php
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $isApple = (bool) preg_match('/iPhone|iPad|iPod|Macintosh/i', $ua);
    $isAndroid = stripos($ua, 'Android') !== false;
    $walletOk = profile_can($p, 'wallet');
    $showApple = $walletOk && wallet_apple_enabled() && !$isAndroid;
    $showGoogle = $walletOk && wallet_google_enabled() && !$isApple;
    ?>
    <?php if ($showApple || $showGoogle): ?>
    <div class="wallet">
      <?php if ($showApple): ?><a class="wallet-btn" href="/u/<?= e($p['slug']) ?>/wallet/apple"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 10h18M15 15h3"/></svg><?= te('Ajouter à Apple Wallet') ?></a><?php endif; ?>
      <?php if ($showGoogle): ?><a class="wallet-btn" href="/u/<?= e($p['slug']) ?>/wallet/google" rel="nofollow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 10h18M15 15h3"/></svg><?= te('Ajouter à Google Wallet') ?></a><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($actions): ?>
    <nav class="actions" aria-label="<?= te('Me contacter') ?>" style="--n: <?= min(4, count($actions)) ?>">
      <?php foreach ($actions as [$href, $label, $icon]): ?>
        <a href="<?= e($href) ?>"<?= str_starts_with($href, 'http') ? ' rel="noopener" target="_blank"' : '' ?>>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><?= $icon ?></svg>
          <?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
  </div>

  <?php if ($links): ?>
  <ul class="links">
    <?php foreach ($links as $l): ?>
      <li><a href="/l/<?= (int) $l['id'] ?>" rel="noopener" target="_blank"><?= e($l['label']) ?><span aria-hidden="true">→</span></a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php if ($leadForm): ?>
  <section class="lead" id="contact" aria-labelledby="lead-title">
    <h2 id="lead-title"><?= te('Laissez vos coordonnées') ?></h2>
    <?php if ($leadSent): ?>
      <p class="lead-ok" role="status"><?= te('Merci ! {nom} a bien reçu vos coordonnées.', ['nom' => $name]) ?></p>
    <?php else: ?>
      <p class="lead-intro"><?= te('{nom} vous recontactera.', ['nom' => $name]) ?></p>
      <?php foreach ($flashes as $f): ?><p class="lead-err" role="alert"><?= e($f['msg']) ?></p><?php endforeach; ?>
      <form method="post" action="/u/<?= e($p['slug']) ?>/prospect" class="lead-form">
        <?= csrf_field() ?>
        <label><?= te('Votre nom') ?><input type="text" name="name" required maxlength="120" autocomplete="name" value="<?= e(old('name')) ?>"></label>
        <label><?= te('Courriel') ?><input type="email" name="email" maxlength="190" autocomplete="email" value="<?= e(old('email')) ?>"></label>
        <label><?= te('Téléphone') ?><input type="tel" name="phone" maxlength="40" autocomplete="tel" value="<?= e(old('phone')) ?>"></label>
        <label><?= te('Votre entreprise') ?> <span class="opt">(<?= te('optionnel') ?>)</span><input type="text" name="company" maxlength="120" autocomplete="organization" value="<?= e(old('company')) ?>"></label>
        <label><?= te('Message') ?> <span class="opt">(<?= te('optionnel') ?>)</span><textarea name="message" rows="3" maxlength="1000"><?= e(old('message')) ?></textarea></label>
        <label class="hp" aria-hidden="true">Site web<input type="text" name="site_web" tabindex="-1" autocomplete="off"></label>
        <label class="consent"><input type="checkbox" name="consent" value="1" required> <span><?= e(lead_consent_text($p)) ?> <a href="/confidentialite"><?= te('Politique de confidentialité') ?></a></span></label>
        <button type="submit" class="send"><?= te('Envoyer mes coordonnées') ?></button>
      </form>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <footer class="made"><?= te('Carte') ?> <strong>EliteTap</strong> · <a href="/"><?= te('Obtenir la mienne') ?></a> · <a href="<?= e(switch_lang_url()) ?>"><?= lang() === 'fr' ? 'EN' : 'FR' ?></a></footer>
</main>
</body>
</html>
