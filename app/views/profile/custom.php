<?php
// Profil sur mesure : gabarit validé par l'admin, rempli avec les données du profil.
require __DIR__ . '/parts/vars.php';
$capture = function (string $file) use ($p, $links, $leadForm, $leadSent, $flashes, $actions, $name, $sub) {
    ob_start();
    require __DIR__ . '/parts/' . $file . '.php';
    return trim((string) ob_get_clean());
};
// Chaque élément de la plateforme est enveloppé dans .et-block (espacement par défaut, modifiable par le gabarit)
$block = fn(string $k, string $html) => trim($html) === '' ? '' : '<div class="et-block et-block-' . $k . '">' . $html . '</div>';
$components = [
    'bouton_contact' => $block('contact', $capture('save')),
    'actions'        => $block('actions', $capture('actions')),
    'wallet'         => $block('wallet', $capture('wallet')),
    'liens'          => $block('liens', $capture('links')),
    'prospects'      => $block('prospects', $capture('lead')),
];
[$css] = sanitize_css((string) $design['css']);
[$tpl] = sanitize_template((string) $design['html']);
$body = render_design($tpl, $p, $components, $assets);
// Polices Google importées par le gabarit : chargées par <link> (l'@import reste permis par la CSP)
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
<link rel="icon" href="<?= asset('img/favicon.png') ?>">
<link rel="stylesheet" href="<?= asset('css/profile.css') ?>">
<style>:root{--cover:<?= e($cover) ?>;--accent:<?= e($accent) ?>;--on-accent:<?= e($onAccent) ?>;--title-font:<?= $fontStack ?>;--on-cover:<?= e(on_color($cover)) ?>;}
body{background:#FFFFFF;} .et-custom{min-height:100vh;min-height:100dvh;} .et-essentials{max-width:480px;margin:0 auto;padding:20px;display:flex;flex-direction:column;gap:12px;}
.et-block{max-width:480px;margin:12px auto 0;padding:0 20px;box-sizing:border-box;}
.et-block .links{padding-left:0;padding-right:0;}
.et-block .lead{margin:12px 0 0;}
</style>
<style><?= $css ?></style>
</head>
<body>
<?php if ($isOwner): ?>
  <div class="owner-bar"><?= te('Aperçu de votre profil') ?><?= $p['is_public'] ? '' : ' (' . te('privé') . ')' ?> · <a href="/compte"><?= te('Modifier') ?></a></div>
<?php endif; ?>
<?php if (!empty($previewBar)): ?><div class="owner-bar">Aperçu du brouillon · version <?= (int) $design['version'] ?> · non publié</div><?php endif; ?>
<div class="et-custom">
<?= $body ?>
</div>
<footer class="made et-made"><?= te('Carte') ?> <strong>EliteTap</strong> · <a href="/"><?= te('Obtenir la mienne') ?></a> · <a href="<?= e(switch_lang_url()) ?>"><?= lang() === 'fr' ? 'EN' : 'FR' ?></a></footer>
</body>
</html>
