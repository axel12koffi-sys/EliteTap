<!doctype html>
<html lang="<?= lang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title ?? 'EliteTap') ?></title>
<link rel="icon" href="<?= asset('img/favicon.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600<?= ($nav ?? '') === 'profil' ? '&family=Playfair+Display:wght@600;700&family=Cormorant+Garamond:wght@600;700' : '' ?>&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="shell">
  <aside class="side">
    <div class="side-top">
      <a href="/" class="side-logo"><img src="<?= asset('img/logo-white.png') ?>" alt="EliteTap" width="112" height="32"></a>
      <?php if ($isAdminShell): ?><span class="tag">ADMIN</span><?php endif; ?>
    </div>
    <nav class="side-nav" aria-label="<?= $isAdminShell ? 'Administration' : te('Espace client') ?>">
      <?php foreach ($menu as $item): [$key, $href, $label] = $item; $feature = $item[3] ?? null; ?>
        <a href="<?= e($href) ?>"<?= ($nav ?? '') === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?><?php if ($feature && !can($feature)): ?> <span class="tag-pro">Pro</span><?php endif; ?><?php if (!empty($item[4])): ?> <span class="nav-count" aria-label="<?= (int) $item[4] ?> non lu(s)"><?= (int) $item[4] ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-bottom">
      <?php if ($isAdminShell): $pr = presence(); $pm = setting('presence_mode', 'auto'); ?>
        <form method="post" action="/admin/presence" class="side-presence <?= $pr['online'] ? 'is-online' : 'is-away' ?>">
          <?= csrf_field() ?>
          <span class="presence-now"><span class="presence-dot" aria-hidden="true"></span><strong><?= $pr['online'] ? 'En ligne' : 'Absent' ?></strong><span><?= e($pr['reason']) ?></span></span>
          <span class="presence-modes" role="group" aria-label="Présence">
            <?php foreach (['auto' => 'Auto', 'online' => 'En ligne', 'away' => 'Absent'] as $k => $l): ?>
              <button type="submit" name="mode" value="<?= $k ?>"<?= $pm === $k ? ' aria-pressed="true"' : ' aria-pressed="false"' ?>><?= $l ?></button>
            <?php endforeach; ?>
          </span>
        </form>
      <?php endif; ?>
      <?php if (!$isAdminShell && isset($p)): ?>
        <a class="side-link" href="/u/<?= e($p['slug']) ?>" target="_blank" rel="noopener">
          <span><?= te('Votre lien public') ?></span>
          <strong><?= e(public_host()) ?>/u/<?= e($p['slug']) ?></strong>
        </a>
      <?php endif; ?>
      <?php if ($isAdminShell): ?><a class="side-switch" href="/compte"><?= te('Mon profil') ?></a><?php elseif (($user['role'] ?? '') === 'admin'): ?><a class="side-switch" href="/admin">Administration</a><?php endif; ?>
      <?php if (!$isAdminShell): ?><a class="side-switch" href="<?= e(switch_lang_url()) ?>"><?= e(lang_switch_label()) ?></a><?php endif; ?>
      <form method="post" action="/deconnexion">
        <?= csrf_field() ?>
        <button type="submit" class="side-logout"><?= e($user['name'] ?? '') ?> · <?= te('Se déconnecter') ?></button>
      </form>
    </div>
  </aside>
  <main class="main">
    <?php if (!$isAdminShell && acting_as_admin()): ?>
      <form method="post" action="/compte/retour-admin" class="managing">
        <?= csrf_field() ?>
        <span>Vous gérez le profil de <strong><?= e($user['name'] ?? '') ?></strong> (<?= e(PLANS[$user['plan'] ?? 'essentiel'] ?? '') ?>). Toutes les fonctions sont ouvertes.</span>
        <button class="btn btn-light btn-sm" type="submit">Revenir à l'admin</button>
      </form>
    <?php endif; ?>
    <?php foreach (flashes() as $f): ?>
      <p class="alert alert-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($f['msg']) ?></p>
    <?php endforeach; ?>
    <?= $content ?>
  </main>
</div>
<?php if (($nav ?? '') === 'cartes' && !$isAdminShell): ?><script src="<?= asset('js/vendor/qrcode.js') ?>"></script><script src="<?= asset('js/qr.js') ?>"></script><?php endif; ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
