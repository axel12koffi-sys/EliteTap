<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title ?? 'EliteTap') ?></title>
<link rel="icon" href="<?= asset('img/favicon.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="shell">
  <aside class="side">
    <div class="side-top">
      <a href="/" class="side-logo"><img src="<?= asset('img/logo-white.png') ?>" alt="EliteTap" width="112" height="32"></a>
      <?php if ($isAdminShell): ?><span class="tag">ADMIN</span><?php endif; ?>
    </div>
    <nav class="side-nav" aria-label="<?= $isAdminShell ? 'Administration' : 'Espace client' ?>">
      <?php foreach ($menu as [$key, $href, $label]): ?>
        <a href="<?= e($href) ?>"<?= ($nav ?? '') === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-bottom">
      <?php if (!$isAdminShell && isset($p)): ?>
        <a class="side-link" href="/u/<?= e($p['slug']) ?>" target="_blank" rel="noopener">
          <span>Votre lien public</span>
          <strong><?= e(public_host()) ?>/u/<?= e($p['slug']) ?></strong>
        </a>
      <?php endif; ?>
      <?php if ($isAdminShell): ?><a class="side-switch" href="/compte">Mon profil</a><?php elseif (($user['role'] ?? '') === 'admin'): ?><a class="side-switch" href="/admin">Administration</a><?php endif; ?>
      <form method="post" action="/deconnexion">
        <?= csrf_field() ?>
        <button type="submit" class="side-logout"><?= e($user['name'] ?? '') ?> · Se déconnecter</button>
      </form>
    </div>
  </aside>
  <main class="main">
    <?php foreach (flashes() as $f): ?>
      <p class="alert alert-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($f['msg']) ?></p>
    <?php endforeach; ?>
    <?= $content ?>
  </main>
</div>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
