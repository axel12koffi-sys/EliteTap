<?php
$path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
$isHome = $path === '/';
?>
<!doctype html>
<html lang="<?= lang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? t('Carte de visite NFC personnalisée au Québec | EliteTap')) ?></title>
<meta name="description" content="<?= te('Cartes de visite NFC personnalisées à Laval, Montréal et partout au Québec. Partagez vos coordonnées par NFC ou code QR, sans application. Devis gratuit en moins de 24 h.') ?>">
<link rel="canonical" href="<?= e(url($path) . (lang() === 'en' ? '?lang=en' : '')) ?>">
<link rel="alternate" hreflang="fr-CA" href="<?= e(url($path)) ?>">
<link rel="alternate" hreflang="en-CA" href="<?= e(url($path) . '?lang=en') ?>">
<link rel="alternate" hreflang="x-default" href="<?= e(url($path)) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="EliteTap">
<meta property="og:locale" content="<?= lang() === 'en' ? 'en_CA' : 'fr_CA' ?>">
<meta property="og:title" content="<?= e($title ?? 'EliteTap') ?>">
<meta property="og:description" content="<?= te('Votre carte d\'affaires, partagée en un seul geste. Sans application.') ?>">
<meta property="og:image" content="<?= e(url('/assets/img/share.jpg')) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php if (!is_indexable()): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<meta name="theme-color" content="#FFFFFF">
<link rel="icon" href="<?= asset('img/favicon.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/site.css') ?>">
<?php if ($isHome): ?>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"LocalBusiness","name":"EliteTap","url":"<?= e(url('/')) ?>","email":"info@elitetap.ca","telephone":"+1-438-220-7167","address":{"@type":"PostalAddress","addressLocality":"Laval","addressRegion":"QC","addressCountry":"CA"},"image":"<?= e(url('/assets/img/share.jpg')) ?>","logo":"<?= e(url('/assets/img/logo-color.png')) ?>","priceRange":"$$","areaServed":[{"@type":"City","name":"Laval"},{"@type":"City","name":"Montréal"},{"@type":"AdministrativeArea","name":"Québec"}],"sameAs":["https://www.instagram.com/elitetap_ca/"]}
</script>
<?php endif; ?>
</head>
<body>
<a class="skip" href="#contenu"><?= te('Aller au contenu') ?></a>
<header class="nav-wrap">
  <nav class="nav container" aria-label="<?= te('Navigation principale') ?>">
    <a href="/" class="nav-logo"><img src="<?= asset('img/logo-color.png') ?>" alt="EliteTap" width="133" height="38"></a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-menu" aria-label="<?= te('Ouvrir le menu') ?>">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
    <div class="nav-menu" id="nav-menu">
      <a href="/#fonctionnement"><?= te('Fonctionnement') ?></a>
      <a href="/#plateforme"><?= te('Plateforme') ?></a>
      <a href="/#materiaux"><?= te('Designs') ?></a>
      <a href="/#tarifs"><?= te('Tarifs') ?></a>
      <a href="/#faq"><?= te('FAQ') ?></a>
      <span class="nav-sep" aria-hidden="true"></span>
      <a href="<?= e(switch_lang_url()) ?>" lang="<?= lang() === 'fr' ? 'en' : 'fr' ?>" hreflang="<?= lang() === 'fr' ? 'en' : 'fr' ?>"><?= lang() === 'fr' ? 'EN' : 'FR' ?></a>
      <a href="<?= current_user() ? '/compte' : '/connexion' ?>"><?= current_user() ? te('Mon espace') : te('Se connecter') ?></a>
      <a href="/#commande" class="btn btn-primary"><?= te('Commander ma carte') ?></a>
    </div>
  </nav>
</header>
<div class="brand-bar" aria-hidden="true"></div>

<main id="contenu">
<?= $content ?>
</main>

<footer class="footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <img src="<?= asset('img/logo-white.png') ?>" alt="EliteTap" width="98" height="28">
      <span>© <?= date('Y') ?> · Laval, Québec</span>
    </div>
    <div class="footer-links">
      <a href="mailto:info@elitetap.ca">info@elitetap.ca</a>
      <a href="https://www.instagram.com/elitetap_ca/" rel="noopener">Instagram</a>
      <a href="/confidentialite"><?= te('Confidentialité') ?></a>
      <a href="/remboursement"><?= te('Remboursements') ?></a>
      <a href="<?= e(switch_lang_url()) ?>" lang="<?= lang() === 'fr' ? 'en' : 'fr' ?>"><?= e(lang_switch_label()) ?></a>
    </div>
  </div>
</footer>
<script src="<?= asset('js/site.js') ?>" defer></script>
</body>
</html>
