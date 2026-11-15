<?php require __DIR__ . '/parts/vars.php'; ?>
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
<?php require __DIR__ . '/parts/save.php'; ?>

<?php require __DIR__ . '/parts/wallet.php'; ?>

<?php require __DIR__ . '/parts/actions.php'; ?>
  </div>

<?php require __DIR__ . '/parts/links.php'; ?>

<?php require __DIR__ . '/parts/lead.php'; ?>

  <footer class="made"><?= te('Carte') ?> <strong>EliteTap</strong> · <a href="/"><?= te('Obtenir la mienne') ?></a> · <a href="<?= e(switch_lang_url()) ?>"><?= lang() === 'fr' ? 'EN' : 'FR' ?></a></footer>
</main>
</body>
</html>
