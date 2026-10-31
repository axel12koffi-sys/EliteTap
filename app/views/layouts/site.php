<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'EliteTap') ?></title>
<meta name="description" content="<?= e($description ?? 'Cartes de visite NFC personnalisées à Laval, Montréal et partout au Québec. Partagez vos coordonnées par NFC ou code QR, sans application. Devis gratuit en moins de 24 h.') ?>">
<link rel="canonical" href="<?= e(url(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'))) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="EliteTap">
<meta property="og:title" content="<?= e($title ?? 'EliteTap') ?>">
<meta property="og:description" content="Votre carte d'affaires, partagée en un seul geste. Sans application.">
<meta property="og:image" content="<?= e(url('/assets/img/share.jpg')) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#0E0F12">
<link rel="icon" href="<?= asset('img/favicon.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/site.css') ?>">
<?php if (($_SERVER['REQUEST_URI'] ?? '') === '/' || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/?')): ?>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"LocalBusiness","name":"EliteTap","url":"<?= e(url('/')) ?>","email":"info@elitetap.ca","telephone":"+1-438-220-7167","address":{"@type":"PostalAddress","addressLocality":"Laval","addressRegion":"QC","addressCountry":"CA"},"areaServed":"Québec","sameAs":["https://www.instagram.com/elitetap_ca/"]}
</script>
<?php endif; ?>
</head>
<body>
<a class="skip" href="#contenu">Aller au contenu</a>
<header class="nav-wrap">
  <nav class="nav container" aria-label="Navigation principale">
    <a href="/" class="nav-logo"><img src="<?= asset('img/logo-white.png') ?>" alt="EliteTap" width="126" height="36"></a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-menu" aria-label="Ouvrir le menu">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
    <div class="nav-menu" id="nav-menu">
      <a href="/#fonctionnement">Fonctionnement</a>
      <a href="/#materiaux">Cartes</a>
      <a href="/#tarifs">Tarifs</a>
      <a href="/#faq">FAQ</a>
      <span class="nav-sep" aria-hidden="true"></span>
      <a href="<?= current_user() ? '/compte' : '/connexion' ?>"><?= current_user() ? 'Mon espace' : 'Se connecter' ?></a>
      <a href="/#commande" class="btn btn-primary">Commander ma carte</a>
    </div>
  </nav>
</header>

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
      <a href="/confidentialite">Confidentialité</a>
      <a href="/remboursement">Remboursements</a>
    </div>
  </div>
</footer>
<script src="<?= asset('js/site.js') ?>" defer></script>
</body>
</html>
