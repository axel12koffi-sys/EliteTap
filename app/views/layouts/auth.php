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
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
<main class="auth">
  <a href="/" class="auth-logo"><img src="<?= asset('img/logo-white.png') ?>" alt="EliteTap" width="140" height="40"></a>
  <div class="auth-card">
    <?php foreach (flashes() as $f): ?>
      <p class="alert alert-<?= e($f['type']) ?>" role="alert"><?= e($f['msg']) ?></p>
    <?php endforeach; ?>
    <?= $content ?>
  </div>
  <a class="auth-lang" href="<?= e(switch_lang_url()) ?>"><?= e(lang_switch_label()) ?></a>
</main>
</body>
</html>
