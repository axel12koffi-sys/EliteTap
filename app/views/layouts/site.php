<?php
$path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
$isHome = $path === '/';
?>
<!doctype html>
<html lang="<?= lang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? t('Carte de visite NFC à Laval, Montréal et Québec | EliteTap')) ?></title>
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
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
<link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/site.css') ?>">
<?php if ($isHome): ?>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"LocalBusiness","name":"EliteTap","url":"<?= e(url('/')) ?>","email":"info@elitetap.ca","telephone":"+1-438-220-7167","address":{"@type":"PostalAddress","addressLocality":"Laval","addressRegion":"QC","addressCountry":"CA"},"image":"<?= e(url('/assets/img/share.jpg')) ?>","logo":"<?= e(url('/assets/img/logo-color.png')) ?>","areaServed":[{"@type":"City","name":"Laval"},{"@type":"City","name":"Montréal"},{"@type":"City","name":"Québec"},{"@type":"Place","name":"Rive-Nord"},{"@type":"Place","name":"Rive-Sud"}],"sameAs":["https://www.instagram.com/elitetap_ca/"]}
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
      <a href="/#commande" class="btn btn-primary"><?= te('Obtenir ma carte') ?></a>
    </div>
  </nav>
</header>
<div class="brand-bar" aria-hidden="true"></div>

<main id="contenu">
<?= $content ?>
</main>

<footer class="footer">
  <?php if (newsletter_ready()): ?>
  <div class="container footer-news" id="infolettre">
    <div>
      <h2 class="footer-news-title"><?= te('Restez informé') ?></h2>
      <p><?= te('Recevez nos nouveautés et offres EliteTap, environ une fois par mois.') ?></p>
    </div>
    <form method="post" action="/infolettre" class="news-form" data-news>
      <?= csrf_field() ?>
      <input type="hidden" name="source" value="footer">
      <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hp" aria-hidden="true">
      <div class="news-row">
        <label class="visually-hidden" for="news-email"><?= te('Votre courriel') ?></label>
        <input type="email" id="news-email" name="email" required placeholder="<?= te('Votre courriel') ?>" autocomplete="email">
        <button type="submit" class="btn btn-primary"><?= te('M\'abonner') ?></button>
      </div>
      <label class="news-consent"><input type="checkbox" name="consent" value="1" required> <span><?= e(newsletter_consent_text(lang())) ?> <a href="/confidentialite"><?= te('Confidentialité') ?></a></span></label>
      <p class="news-msg" data-news-msg role="status" hidden></p>
    </form>
  </div>
  <?php endif; ?>
  <div class="container footer-grid">
    <div class="footer-brand">
      <img src="<?= asset('img/logo-white.png') ?>" alt="EliteTap" width="98" height="28">
      <p class="footer-tag">NFC &amp; QR Business Cards<br>Laval · Montréal · Québec</p>
      <p class="footer-contact">
        <a href="tel:+14382207167">+1 438-220-7167</a>
        <a href="https://wa.me/<?= e(config('whatsapp')) ?>?text=<?= rawurlencode(t('Bonjour, je suis intéressé par les cartes NFC EliteTap')) ?>" rel="noopener">WhatsApp</a>
        <a href="mailto:info@elitetap.ca">info@elitetap.ca</a>
        <a href="https://www.instagram.com/elitetap_ca/" rel="noopener">Instagram</a>
      </p>
    </div>
    <nav class="footer-col" aria-label="<?= te('Navigation') ?>">
      <h2><?= te('Navigation') ?></h2>
      <a href="/"><?= te('Accueil') ?></a>
      <a href="/#materiaux"><?= te('Designs') ?></a>
      <a href="/#fonctionnement"><?= te('Comment ça marche') ?></a>
      <a href="/#tarifs"><?= te('Tarifs') ?></a>
      <a href="/#temoignages"><?= te('Témoignages') ?></a>
      <a href="/#commande"><?= te('Contact') ?></a>
    </nav>
    <nav class="footer-col" aria-label="<?= te('Légal') ?>">
      <h2><?= te('Légal') ?></h2>
      <a href="/confidentialite"><?= te('Politique de confidentialité') ?></a>
      <a href="/remboursement"><?= te('Politique de remboursement') ?></a>
      <a href="/conditions"><?= te('Conditions d\'utilisation') ?></a>
      <a href="<?= e(switch_lang_url()) ?>" lang="<?= lang() === 'fr' ? 'en' : 'fr' ?>"><?= e(lang_switch_label()) ?></a>
    </nav>
  </div>
  <div class="container footer-bottom">
    <span>© <?= date('Y') ?> EliteTap · <?= te('Tous droits réservés') ?> · Laval, Québec, Canada</span>
    <span><?= te('Fait au Québec') ?> 🍁</span>
  </div>
</footer>
<a class="wa-float" href="https://wa.me/<?= e(config('whatsapp')) ?>?text=<?= rawurlencode(t('Bonjour, je suis intéressé par les cartes NFC EliteTap')) ?>" rel="noopener" target="_blank" aria-label="<?= te('Écrire à EliteTap sur WhatsApp') ?>">
  <svg width="28" height="28" viewBox="0 0 32 32" fill="#fff" aria-hidden="true"><path d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3zm0 23.6a10.6 10.6 0 0 1-5.4-1.5l-.4-.2-3.9 1 1-3.8-.2-.4A10.6 10.6 0 1 1 16 26.6zm5.8-7.9c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1a8.7 8.7 0 0 1-4.3-3.8c-.3-.6.3-.5.9-1.7a.6.6 0 0 0 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.9.4 3.6 3.6 0 0 0-1.1 2.7 6.3 6.3 0 0 0 1.3 3.3 14.4 14.4 0 0 0 5.5 4.9c2 .9 2.8.9 3.8.8a3.2 3.2 0 0 0 2.1-1.5 2.6 2.6 0 0 0 .2-1.5c-.1-.2-.3-.3-.6-.5z"/></svg>
</a>
<div class="cookie-bar" data-cookie-bar role="region" aria-label="<?= te('Témoins (cookies)') ?>" hidden>
  <p><strong><?= te('Témoins (cookies)') ?></strong> <?= te('Ce site utilise uniquement des témoins essentiels : rester connecté à votre espace client et retenir votre langue. Aucun témoin publicitaire ni de suivi par des tiers.') ?> <a href="/confidentialite"><?= te('En savoir plus') ?></a></p>
  <button type="button" class="btn btn-primary" data-cookie-ok><?= te('J\'ai compris') ?></button>
</div>
<?php if (chat_ready()): $pres = presence(); ?>
<div id="et-chat" class="et-chat" data-csrf="<?= e(csrf_token()) ?>" data-online="<?= $pres['online'] ? '1' : '0' ?>" data-lang="<?= lang() ?>"
     data-i18n="<?= e(json_encode(chat_widget_strings(), JSON_UNESCAPED_UNICODE)) ?>"></div>
<script src="<?= asset('js/chat-widget.js') ?>" defer></script>
<?php endif; ?>
<script src="<?= asset('js/site.js') ?>" defer></script>
</body>
</html>
