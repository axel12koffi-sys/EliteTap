<?php
$check = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8.5l3 3 7-7" stroke="#3666AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$flashes = flashes();
$designs = public_designs();
$faq = [
      ['Mon téléphone est-il compatible ?', 'iPhone XS / XR et plus récent (iOS 13+) et la plupart des Android depuis 2015, NFC activé. Sans NFC, le code QR au dos fonctionne avec n\'importe quel appareil.'],
      ['Faut-il une application ?', 'Non. Le profil s\'ouvre directement dans le navigateur du téléphone de votre contact.'],
      ['Puis-je modifier mes informations ?', 'Oui, sans réimprimer votre carte : vous-même depuis votre tableau de bord avec le forfait Pro, ou par l\'équipe EliteTap avec le forfait Essentiel.'],
      ['Que deviennent les coordonnées laissées par mes visiteurs ?', 'Elles arrivent dans votre boîte de prospects et par courriel. Chaque visiteur coche un consentement, conformément à la Loi 25.'],
      ['Combien coûte une carte ?', 'Le prix dépend de la finition, du design et de la quantité. Demandez une soumission gratuite : réponse sous 24 h, devis personnalisé.'],
      ['Quels sont les délais de livraison ?', 'La production commence après validation de votre épreuve. Le délai exact vous est confirmé avec le devis, selon la matière et la quantité.'],
      ['Et si je perds ma carte ?', 'Désactivez-la en un clic depuis votre espace client, puis commandez un remplacement relié au même profil.'],
      ['Puis-je payer en ligne ?', 'Oui. Une fois votre soumission acceptée, vous recevez un lien de paiement sécurisé par carte de crédit.'],
    ];
?>
<!-- HERO -->
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <p class="kicker et-up et-d1"><?= te('NFC & QR · Laval · Montréal · Québec') ?></p>
      <h1 class="et-up et-d2"><?= te('La carte de visite intelligente') ?> <span class="soft"><?= te('pour les pros du Québec') ?></span></h1>
      <p class="hero-tagline et-up et-d3"><?= te('Sans application. Toujours à jour.') ?></p>
      <p class="lead et-up et-d3"><?= te('Partagez vos coordonnées en un tap, sur n\'importe quel iPhone ou Android. Vos clients reçoivent un profil complet, vous récupérez leurs contacts.') ?></p>
      <div class="actions et-up et-d4">
        <a href="#commande" class="btn btn-primary btn-lg"><?= te('Obtenir ma carte') ?></a>
        <a href="#demo" class="btn btn-ghost btn-lg"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15l13-7.5z"/></svg><?= te('Voir la démo') ?></a>
      </div>
      <p class="hero-compat et-up et-d5"><?= te('Compatible iPhone XS et plus · Android depuis 2015 · Réponse sous 24 h') ?></p>
      <div class="zones et-up et-d5">
        <span class="zones-label"><?= te('Zones servies :') ?></span>
        <ul><?php foreach (['Laval', 'Montréal', 'Québec', 'Rive-Nord', 'Rive-Sud'] as $z): ?><li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><?= te($z) ?></li><?php endforeach; ?></ul>
      </div>
    </div>

    <div class="tap-wrap et-visual" data-tap>
      <div class="tap-stage" role="img" aria-label="<?= te('Animation : une carte EliteTap touche un téléphone et le profil de sa propriétaire s\'ouvre') ?>">
        <div class="tap-glow" aria-hidden="true"></div>
        <div class="tap-phone" aria-hidden="true">
          <div class="tap-screen">
            <div class="tap-lock"><div class="tap-time">9:41</div></div>
            <div class="tap-notif">
              <span class="tap-notif-ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#FFED00" stroke-width="2.2" stroke-linecap="round"><path d="M6 8a8 8 0 0 1 0 8"/><path d="M10 6a12 12 0 0 1 0 12"/><path d="M14 4a16 16 0 0 1 0 16"/></svg></span>
              <span><b>EliteTap</b><span><?= te('Ouvrir le profil de Sophie Lavoie') ?></span></span>
            </div>
            <div class="tap-notif tap-added">
              <span class="tap-notif-ic tap-added-ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>
              <span><b><?= te('Contact ajouté') ?></b><span><?= te('Sophie Lavoie est dans vos contacts') ?></span></span>
            </div>
            <div class="tap-profile">
              <div class="tap-cover"></div>
              <?php if (is_file(APP_ROOT . '/public/assets/img/demo-sophie.jpg')): ?><img class="tap-avatar" src="<?= asset('img/demo-sophie.jpg') ?>" alt="" width="70" height="70"><?php else: ?><div class="tap-avatar">SL</div><?php endif; ?>
              <div class="tap-who"><b>Sophie Lavoie</b><span><?= te('Courtière immobilière') ?></span></div>
              <div class="tap-save"><?= te('Enregistrer le contact') ?></div>
              <div class="tap-rows">
                <div class="tap-row"><i><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#3666AF" stroke-width="2.2"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></i><span>514 555-0142<small><?= te('Mobile') ?></small></span></div>
                <div class="tap-row"><i><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#3666AF" stroke-width="2.2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></i><span>sophie@exemple.ca<small><?= te('Courriel') ?></small></span></div>
                <div class="tap-row"><i><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#3666AF" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg></i><span><?= te('Prendre rendez-vous') ?><small><?= te('Lien') ?></small></span></div>
              </div>
            </div>
          </div>
        </div>
        <span class="tap-ripple" aria-hidden="true"></span><span class="tap-ripple r2" aria-hidden="true"></span><span class="tap-ripple r3" aria-hidden="true"></span>
        <div class="tap-card-pos" aria-hidden="true">
          <div class="tap-card">
            <div class="tap-face tap-front">
              <img src="<?= asset('img/logo-white.png') ?>" alt="" class="tap-logo" width="150" height="40">
              <div class="tap-name"><b>Sophie Lavoie</b><span><?= te('COURTIÈRE IMMOBILIÈRE') ?></span></div>
              <svg class="tap-nfc" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#FFED00" stroke-width="1.8" stroke-linecap="round"><path d="M6 8a8 8 0 0 1 0 8"/><path d="M10 6a12 12 0 0 1 0 12"/><path d="M14 4a16 16 0 0 1 0 16"/></svg>
            </div>
            <div class="tap-face tap-back"><canvas width="184" height="184" data-tap-qr></canvas><span><?= te('Touchez ou scannez') ?></span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- AVANT / APRÈS -->
<section id="avantapres" class="section container et-rv">
  <div class="ba-head">
    <h2 class="h2"><?= te('Fini les cartes papier perdues') ?></h2>
    <p class="sub"><?= te('Découvrez ce que ça change vraiment') ?></p>
  </div>
  <div class="ba">
    <div class="ba-col ba-before">
      <h3><span class="ba-ico" aria-hidden="true">✕</span><?= te('Carte de visite classique') ?></h3>
      <ul>
        <?php foreach (['Se perd, se froisse, s\'oublie', 'Réimprimer à chaque changement', 'Aucun suivi : vous ne savez pas qui l\'a vue', 'Coût élevé à chaque mise à jour', 'Besoin d\'une application pour sauvegarder'] as $l): ?><li><?= te($l) ?></li><?php endforeach; ?>
      </ul>
    </div>
    <div class="ba-col ba-after">
      <h3><span class="ba-ico" aria-hidden="true">✓</span><?= te('Carte NFC EliteTap') ?></h3>
      <ul>
        <?php foreach (['Un tap : profil complet ouvert immédiatement', 'Mises à jour illimitées, sans réimprimer', 'Tableau de bord : combien de taps, qui vous a contacté', 'Une seule carte pour toute votre carrière', 'Sans application : fonctionne nativement'] as $l): ?><li><?= te($l) ?></li><?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<!-- VIDÉO DÉMO -->
<section id="demo" class="demo-section">
  <div class="section container et-rv">
    <div class="demo-head">
      <h2 class="h2"><?= te('Voyez la magie en action') ?></h2>
      <p class="sub"><?= te('Un tap suffit. Pas d\'application. Fonctionne sur tous les iPhone et Android récents.') ?></p>
    </div>
    <?php if (is_file(APP_ROOT . '/public/assets/img/video01.mp4')): ?>
    <div class="demo-player">
      <video controls muted playsinline preload="metadata" poster="<?= asset('img/video01.jpg') ?>" aria-label="<?= te('Démonstration : une carte EliteTap touche un iPhone et le profil s\'ouvre') ?>">
        <source src="<?= asset('img/video01.mp4') ?>" type="video/mp4">
      </video>
    </div>
    <?php else: ?>
    <p class="demo-missing"><?= te('La vidéo de démonstration arrive bientôt.') ?></p>
    <?php endif; ?>
  </div>
</section>

<!-- FONCTIONNEMENT : les 4 étapes du plan -->
<section id="fonctionnement" class="section container et-rv">
  <p class="eyebrow"><?= te('Comment ça fonctionne') ?></p>
  <h2 class="h2"><?= te('Toucher, profil, agir. Puis gérer et mesurer.') ?></h2>
  <p class="sub"><?= te('La carte fait le premier pas. Le tableau de bord vous montre ce qui se passe ensuite.') ?></p>
  <ol class="steps">
    <li><span class="step-tag"><?= te('Étape') ?></span><span class="step-n">01</span><h3><?= te('Toucher') ?></h3><p><?= te('La carte est touchée, ou le QR scanné, sur n\'importe quel téléphone récent. Sans application.') ?></p></li>
    <li><span class="step-tag"><?= te('Étape') ?></span><span class="step-n">02</span><h3><?= te('Profil') ?></h3><p><?= te('Votre profil s\'ouvre instantanément, avec vos coordonnées, vos liens et votre image de marque.') ?></p></li>
    <li><span class="step-tag"><?= te('Étape') ?></span><span class="step-n">03</span><h3><?= te('Agir') ?></h3><p><?= te('Le visiteur enregistre votre contact, vous appelle, vous écrit ou vous laisse ses coordonnées.') ?></p></li>
    <li><span class="step-tag"><?= te('Plateforme') ?></span><span class="step-n">04</span><h3><?= te('Gérer & mesurer') ?></h3><p><?= te('Votre tableau de bord : modifiez votre profil et voyez chaque toucher, visite et prospect.') ?></p></li>
  </ol>
</section>


<!-- PLATEFORME : ce que voit chaque entreprise -->
<section id="plateforme" class="section-bottom container et-rv">
  <div class="platform">
    <div>
      <p class="eyebrow"><?= te('La plateforme') ?></p>
      <h2 class="h2"><?= te('Plus qu\'une carte : un tableau de bord pour votre entreprise.') ?></h2>
      <p class="sub" style="margin-bottom: 32px;"><?= te('Chaque client se connecte à son propre espace : ses touchers, ses visites, ses prospects. Rien de partagé, rien de manuel.') ?></p>
      <div class="features">
        <div><span class="feature-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4z" stroke="#3666AF" stroke-width="1.8" stroke-linejoin="round"/></svg></span><h3><?= te('Profil modifiable') ?></h3><p><?= te('Photo, logo, coordonnées, WhatsApp et liens. En ligne aussitôt, sans réimpression.') ?></p></div>
        <div><span class="feature-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2" stroke="#3666AF" stroke-width="1.8" stroke-linecap="round"/></svg></span><h3><?= te('Touchers et visites') ?></h3><p><?= te('L\'activité de chaque carte, jour après jour.') ?></p></div>
        <div><span class="feature-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="4" stroke="#3666AF" stroke-width="1.8"/><path d="M2 20a7 7 0 0 1 14 0M17 11l2 2 4-4" stroke="#3666AF" stroke-width="1.8" stroke-linecap="round"/></svg></span><h3><?= te('Prospects') ?></h3><p><?= te('Vos visiteurs laissent leurs coordonnées, avec un consentement conforme à la Loi 25. Alerte courriel et export CSV.') ?></p></div>
        <div><span class="feature-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="#3666AF" stroke-width="1.8"/><path d="M12 3a9 9 0 0 0 0 18c1.5 0 2-1 2-2s-1-1.5-1-2.5S14 15 15 15h2a4 4 0 0 0 4-4" stroke="#3666AF" stroke-width="1.8"/></svg></span><h3><?= te('Votre image de marque') ?></h3><p><?= te('6 thèmes prêts à utiliser, ou vos couleurs, votre logo et votre police.') ?></p></div>
      </div>
    </div>
    <figure style="margin: 0;">
      <div class="browser" aria-hidden="true">
        <div class="browser-bar"><i></i><i></i><i></i><span class="browser-url">elitetap.ca / <?= te('tableau-de-bord') ?></span></div>
        <div class="dash">
          <div class="dash-head"><?= te('Votre carte ce mois-ci') ?><span><?= te('Voir ma carte') ?></span></div>
          <div class="dash-kpis">
            <div><span><?= te('Touchers') ?></span><strong>1 204</strong></div>
            <div><span><?= te('Visites') ?></span><strong>1 061</strong></div>
            <div><span><?= te('Prospects') ?></span><strong>37</strong></div>
          </div>
          <span class="dash-label"><?= te('Touchers, 30 derniers jours') ?></span>
          <div class="dash-bars"><?php foreach ([24, 38, 30, 50, 44, 62, 70, 58, 74, 68, 78, 72, 86, 100] as $h): ?><i style="height: <?= $h ?>%"></i><?php endforeach; ?></div>
          <span class="dash-label"><?= te('Prospects récents') ?></span>
          <div class="dash-leads">
            <div><b></b><?= te('il y a {n} j', ['n' => 2]) ?></div>
            <div><b style="width: 26%"></b><?= te('il y a {n} j', ['n' => 4]) ?></div>
            <div><b style="width: 30%"></b><?= te('il y a {n} j', ['n' => 6]) ?></div>
          </div>
        </div>
      </div>
      <figcaption class="dash-caption"><?= te('Aperçu du tableau de bord client, avec des données d\'exemple.') ?></figcaption>
    </figure>
  </div>
</section>

<!-- POUR QUI -->
<section class="section-bottom container et-rv">
  <p class="eyebrow"><?= te('Pour qui') ?></p>
  <h2 class="h2" style="margin-bottom: 24px;"><?= te('Pour les professionnels qui rencontrent du monde.') ?></h2>
  <div class="audience">
    <span><?= te('Courtiers immobiliers') ?></span>
    <span><?= te('Avocats et notaires') ?></span>
    <span><?= te('Finance et placements') ?></span>
    <span><?= te('Ventes et représentants') ?></span>
    <span><?= te('Entrepreneurs') ?></span>
    <span><?= te('Équipes d\'entreprise') ?></span>
  </div>
</section>

<!-- DESIGNS / MATIÈRES -->
<section id="materiaux" class="mats-section">
  <div class="section container et-rv">
    <div class="mats-head">
      <p class="eyebrow"><?= te('Nos designs') ?></p>
      <h2 class="h2"><?= te('Votre marque. Vos couleurs. Votre style.') ?></h2>
      <p class="sub"><?= te('6 finitions disponibles, entièrement personnalisables à votre image. Une épreuve vous est envoyée avant toute production.') ?></p>
      <p class="mats-quote"><?= te('Besoin de plus de 10 cartes ?') ?> <a href="#commande"><?= te('Demander un devis') ?></a></p>
    </div>
    <div class="mats">
      <?php foreach ([
        ['Plastique premium', 'Matt Noir Élite', 'matt-noir'],
        ['Plastique premium', 'Noir Brillant Pro', 'noir-brillant'],
        ['Métal brossé', 'Or Prestige', 'or'],
        ['Métal brossé', 'Argent Corporate', 'argent'],
        ['Bois naturel', 'Érable Nordique', 'erable'],
        ['Transparent', 'Crystal NFC', 'crystal'],
      ] as [$material, $name, $key]): ?>
      <figure class="mat mat-<?= $key ?>">
        <div class="mat-stage" aria-hidden="true"><div class="mat-card"><span class="mat-nfc"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 8a8 8 0 0 1 0 8"/><path d="M10 6a12 12 0 0 1 0 12"/><path d="M14 4a16 16 0 0 1 0 16"/></svg></span></div></div>
        <figcaption><span class="mat-type"><?= te($material) ?></span><strong><?= e($name) ?></strong></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>

    <?php if ($designs): ?>
    <h3 class="gallery-title"><?= te('Réalisations') ?></h3>
    <div class="gallery">
      <?php foreach (array_slice($designs, 0, 12) as $d): ?>
        <a class="gallery-item" href="/uploads/designs/<?= e($d['image_path']) ?>" data-lightbox data-caption="<?= e($d['title']) ?>">
          <img src="/uploads/designs/<?= e($d['thumb_path']) ?>" alt="<?= e($d['title'] ?: t('Carte NFC {matiere} EliteTap', ['matiere' => t($d['material'])])) ?>" loading="lazy">
          <span><?= e($d['title']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<dialog class="lightbox" id="lightbox" aria-label="<?= te('Agrandissement') ?>">
  <button type="button" class="lightbox-close" aria-label="<?= te('Fermer') ?>">×</button>
  <img alt="">
  <p></p>
</dialog>

<!-- FORFAITS DU PROFIL (plan d'évolution, section 05) -->
<section id="forfaits" class="section-bottom container et-rv">
  <p class="eyebrow"><?= te('Votre profil') ?></p>
  <h2 class="h2"><?= te('Gratuit à vie avec votre carte. Pro si vous voulez aller plus loin.') ?></h2>
  <p class="sub"><?= te('Le profil de base est inclus avec chaque carte. Les fonctions de gestion sont un abonnement optionnel.') ?></p>
  <div class="tiers">
    <article class="tier">
      <h3><?= te('Essentiel') ?></h3>
      <p class="tier-sub"><?= te('Inclus avec chaque carte · gratuit à vie') ?></p>
      <ul><li><?= te('Profil public hébergé') ?></li><li><?= te('Choix parmi 6 thèmes') ?></li><li><?= te('Enregistrement du contact (vCard)') ?></li><li><?= te('Modifications faites par EliteTap') ?></li></ul>
    </article>
    <article class="tier tier-pro">
      <h3>Pro <span class="tier-tag"><?= te('Recommandé') ?></span></h3>
      <p class="tier-sub"><?= te('Abonnement mensuel ou annuel') ?></p>
      <ul><li><?= te('Tableau de bord client') ?></li><li><?= te('Modification autonome du profil') ?></li><li><?= te('Touchers, visites et prospects') ?></li><li><?= te('Alertes courriel et export des prospects') ?></li><li><?= te('Couleurs, logo et police de la marque') ?></li></ul>
    </article>
    <article class="tier">
      <h3><?= te('Entreprise') ?></h3>
      <p class="tier-sub"><?= te('Par carte / par employé · sur devis') ?></p>
      <ul><li><?= te('Tout le forfait Pro') ?></li><li><?= te('Design sur mesure') ?></li><li><?= te('Image de marque uniforme pour l\'équipe') ?></li><li><?= te('Accompagnement dédié') ?></li></ul>
    </article>
  </div>
  <p class="note-line"><?= te('Votre profil gratuit reste tel quel ; le passage au forfait Pro est un choix, jamais une obligation.') ?></p>
</section>

<!-- TEMOIGNAGES + APPEL A L'ACTION -->
<section id="temoignages" class="section-bottom container et-rv">
  <h2 class="h2 testis-title"><?= te('Ils ont adopté la carte NFC') ?></h2>
  <div class="testis">
    <?php foreach ([
      ['Guy', 'Client EliteTap · Montréal', 'temoin-guy.jpg', 'Ma carte NFC EliteTap a changé ma façon de réseauter. En un tap, mes clients ont tout ce qu\'il faut. J\'ai eu 3 nouveaux contacts la première semaine !'],
      ['Jean Cyrus Oulai', 'Client EliteTap · Laval', 'temoin-jean-cyrus.jpg', 'Très professionnel et facile à utiliser. Je recommande EliteTap à tous mes collègues dans le domaine immobilier.'],
    ] as [$who, $role, $photo, $quote]):
      $initials = implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(explode(' ', $who), 0, 2))); ?>
    <figure class="testi">
      <p class="testi-stars" role="img" aria-label="<?= te('5 étoiles sur 5') ?>">★★★★★</p>
      <blockquote><?= te($quote) ?></blockquote>
      <figcaption>
        <?php if (is_file(APP_ROOT . '/public/assets/img/' . $photo)): ?>
          <img class="testi-photo" src="<?= asset('img/' . $photo) ?>" alt="" width="104" height="104" loading="lazy">
        <?php else: ?>
          <span class="testi-photo testi-initials" aria-hidden="true"><?= e($initials) ?></span>
        <?php endif; ?>
        <strong><?= e($who) ?></strong>
        <span><?= te($role) ?></span>
      </figcaption>
    </figure>
    <?php endforeach; ?>
  </div>
  <div class="cta-band">
    <div>
      <h2><?= te('Votre carte, à votre image.') ?></h2>
      <p><?= te('Une carte NFC personnalisée, un profil numérique et un accompagnement à chaque étape.') ?></p>
    </div>
    <a href="#commande" class="cta-band-btn"><?= te('Discutons de votre carte') ?> <span aria-hidden="true">→</span></a>
  </div>
</section>

<!-- FAQ -->
<section id="faq" class="section-bottom container narrow et-rv">
  <h2 class="h2"><?= te('Questions fréquentes') ?></h2>
  <div class="faq">
    <?php foreach ($faq as $i => [$q, $a]): ?>
      <details<?= $i === 0 ? ' open' : '' ?>>
        <summary><?= te($q) ?><span class="plus" aria-hidden="true">+</span></summary>
        <p><?= te($a) ?></p>
      </details>
    <?php endforeach; ?>
  </div>
</section>

<!-- COMMANDE -->
<section id="commande" class="dark">
  <div class="section container order-grid et-rv">
    <div class="order-copy">
      <h2 class="h2 h2-xl"><?= te('Obtenez votre soumission gratuite') ?></h2>
      <p class="lead"><?= te('Réponse garantie sous 24 h · Devis personnalisé · Livraison Laval et Montréal') ?></p>
      <?php $pres = presence(); ?>
      <p class="presence-pill <?= $pres['online'] ? 'is-online' : 'is-away' ?>"><span class="presence-dot" aria-hidden="true"></span>
        <?= $pres['online'] ? te('En ligne maintenant · réponse rapide') : te('Absents pour le moment · réponse {retour}', ['retour' => format_back($pres['back'], lang())]) ?></p>
      <div class="contact-list">
        <a href="https://wa.me/<?= e(config('whatsapp')) ?>" rel="noopener"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z" stroke="#7FAEDD" stroke-width="1.8" stroke-linejoin="round"/></svg>WhatsApp · 438 220-7167</a>
        <a href="mailto:info@elitetap.ca"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="#7FAEDD" stroke-width="1.8"/><path d="M3 7l9 6 9-6" stroke="#7FAEDD" stroke-width="1.8"/></svg>info@elitetap.ca</a>
        <a href="https://www.instagram.com/elitetap_ca/" rel="noopener"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" stroke="#7FAEDD" stroke-width="1.8"/><circle cx="12" cy="12" r="4" stroke="#7FAEDD" stroke-width="1.8"/></svg>@elitetap_ca</a>
      </div>
    </div>

    <form class="order-form" method="post" action="/devis" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <?php foreach ($flashes as $f): ?>
        <p class="alert alert-<?= e($f['type']) ?> span-2" role="alert"><?= e($f['msg']) ?></p>
      <?php endforeach; ?>
      <label><?= te('Prénom et nom') ?> <span class="req" aria-hidden="true">*</span><input type="text" name="name" autocomplete="name" required placeholder="Jean Tremblay" value="<?= e(old('name')) ?>"></label>
      <label><?= te('Entreprise') ?> <span class="opt">(<?= te('optionnel') ?>)</span><input type="text" name="company" autocomplete="organization" placeholder="RE/MAX Excellence" value="<?= e(old('company')) ?>"></label>
      <label><?= te('Courriel') ?> <span class="req" aria-hidden="true">*</span><input type="email" name="email" autocomplete="email" required placeholder="jean@exemple.ca" value="<?= e(old('email')) ?>"></label>
      <label><?= te('Téléphone') ?> <span class="opt">(<?= te('optionnel') ?>)</span><input type="tel" name="phone" autocomplete="tel" placeholder="514 000-0000" value="<?= e(old('phone')) ?>"></label>
      <label><?= te('Type de carte') ?>
        <select name="card_type" id="card_type">
          <?php foreach (CARD_TYPES as $ct): ?>
            <option value="<?= e($ct) ?>"<?= old('card_type', 'Premium') === $ct ? ' selected' : '' ?>><?= te($ct) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label><?= te('Quantité de cartes') ?> <span class="req" aria-hidden="true">*</span>
        <select name="qty_range" required>
          <option value=""><?= te('Choisir') ?></option>
          <?php foreach (QTY_RANGES as $k => $lbl): ?><option value="<?= e($k) ?>"<?= old('qty_range') === $k ? ' selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label><?= te('Secteur d\'activité') ?> <span class="opt">(<?= te('optionnel') ?>)</span>
        <select name="sector">
          <option value=""><?= te('Choisir') ?></option>
          <?php foreach (SECTORS as $sec): ?><option value="<?= e($sec) ?>"<?= old('sector') === $sec ? ' selected' : '' ?>><?= te($sec) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label class="span-2"><?= te('Message') ?> <span class="opt">(<?= te('optionnel') ?>)</span><textarea name="message" rows="4" placeholder="<?= te('Je voudrais des cartes personnalisées…') ?>"><?= e(old('message')) ?></textarea></label>
      <label class="span-2"><?= te('Logo ou maquette') ?> <span class="opt">(<?= te('PNG, JPG, SVG ou PDF, 10 Mo max') ?>)</span><input type="file" name="file" accept=".png,.jpg,.jpeg,.webp,.svg,.pdf"></label>
      <label class="hp" aria-hidden="true">Site web<input type="text" name="site_web" tabindex="-1" autocomplete="off"></label>
      <button type="submit" class="btn btn-primary btn-lg span-2"><?= te('Obtenir ma soumission gratuite') ?> <span aria-hidden="true">→</span></button>
      <p class="fine span-2"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg> <?= t('Vos informations sont confidentielles · <a href="/confidentialite">Politique de confidentialité</a>') ?></p>
    </form>
  </div>
</section>

<?php
// Données structurées : FAQ (résultats enrichis) et avis clients (sans prix, voir la stratégie « soumission »)
$ld = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn($qa) => [
                '@type' => 'Question', 'name' => t($qa[0]),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => t($qa[1])],
            ], $faq),
        ],
        [
            '@type' => 'Product',
            'name' => t('Carte de visite NFC EliteTap'),
            'description' => t('Carte de visite NFC personnalisée avec code QR et profil numérique modifiable, sans application.'),
            'brand' => ['@type' => 'Brand', 'name' => 'EliteTap'],
            'image' => url('/assets/img/share.jpg'),
            'review' => [
                ['@type' => 'Review', 'author' => ['@type' => 'Person', 'name' => 'Guy'], 'reviewRating' => ['@type' => 'Rating', 'ratingValue' => 5, 'bestRating' => 5],
                 'reviewBody' => t('Ma carte NFC EliteTap a changé ma façon de réseauter. En un tap, mes clients ont tout ce qu\'il faut. J\'ai eu 3 nouveaux contacts la première semaine !')],
                ['@type' => 'Review', 'author' => ['@type' => 'Person', 'name' => 'Jean Cyrus Oulai'], 'reviewRating' => ['@type' => 'Rating', 'ratingValue' => 5, 'bestRating' => 5],
                 'reviewBody' => t('Très professionnel et facile à utiliser. Je recommande EliteTap à tous mes collègues dans le domaine immobilier.')],
            ],
        ],
    ],
];
?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
