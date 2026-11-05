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
    <?php
    $curNav = $nav ?? '';
    $navLink = function (array $item) use ($curNav) {
        [$key, $href, $label] = $item;
        $feature = $item[3] ?? null;
        $count = (int) ($item[4] ?? 0);
        $h = '<a href="' . e($href) . '"' . ($curNav === $key ? ' aria-current="page"' : '') . '>' . e($label);
        if ($feature && !can($feature)) $h .= ' <span class="tag-pro">Pro</span>';
        if ($count) $h .= ' <span class="nav-count" aria-label="' . $count . ' non lu(s)">' . $count . '</span>';
        return $h . '</a>';
    };
    ?>
    <button type="button" class="side-toggle" aria-expanded="false" aria-controls="side-nav" data-side-toggle>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      <span>Menu</span>
      <?php $allCount = 0; foreach ($menu as $it) { $allCount += $it[0] === '_group' ? array_sum(array_map(fn($l) => (int) ($l[4] ?? 0), $it[3])) : (int) ($it[4] ?? 0); } ?>
      <?php if ($allCount): ?><span class="nav-count" aria-label="<?= $allCount ?> non lu(s)"><?= $allCount ?></span><?php endif; ?>
    </button>
    <nav class="side-nav" id="side-nav" aria-label="<?= $isAdminShell ? 'Administration' : te('Espace client') ?>">
      <?php foreach ($menu as $item): ?>
        <?php if ($item[0] === '_group'): [$_, $gid, $glabel, $links] = $item; if (!$links) continue;
          $active = in_array($nav ?? '', array_column($links, 0), true);
          $total = array_sum(array_map(fn($l) => (int) ($l[4] ?? 0), $links)); ?>
          <details class="nav-group" data-group="<?= e($gid) ?>"<?= $active ? ' open data-active' : '' ?>>
            <summary>
              <span><?= e($glabel) ?></span>
              <?php if ($total): ?><span class="nav-count nav-count-group" aria-label="<?= $total ?> non lu(s)"><?= $total ?></span><?php endif; ?>
              <svg class="nav-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </summary>
            <div class="nav-group-links"><?php foreach ($links as $l) echo $navLink($l); ?></div>
          </details>
        <?php else: echo $navLink($item); endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="side-bottom">
      <?php if ($isAdminShell && staff_can($user ?? current_user(), 'service')): $pr = presence(); $pm = setting('presence_mode', 'auto'); ?>
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
      <?php if ($isAdminShell && ($user['role'] ?? '') === 'admin'): ?><a class="side-switch" href="/compte"><?= te('Mon profil') ?></a><?php elseif (!$isAdminShell && ($user['role'] ?? '') === 'admin'): ?><a class="side-switch" href="/admin">Administration</a><?php endif; ?>
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
<?php if ($isAdminShell && in_array($nav ?? '', ['clavardage', 'equipe'], true)): ?><script src="<?= asset('js/live.js') ?>" defer></script><?php endif; ?>
<?php if (($nav ?? '') === 'cartes' && !$isAdminShell): ?><script src="<?= asset('js/vendor/qrcode.js') ?>"></script><script src="<?= asset('js/qr.js') ?>"></script><?php endif; ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
