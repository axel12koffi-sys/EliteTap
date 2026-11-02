<?php
$check = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8.5l3 3 7-7" stroke="#3666AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$flashes = flashes();
$designs = public_designs();
$faq = [
      ['Faut-il une application ?', 'Non. Le profil s\'ouvre directement dans le navigateur du téléphone de votre contact.'],
      ['Puis-je modifier mes informations ?', 'Oui, sans réimprimer votre carte : vous-même depuis votre tableau de bord avec le forfait Pro, ou par l\'équipe EliteTap avec le forfait Essentiel.'],
      ['Que deviennent les coordonnées laissées par mes visiteurs ?', 'Elles arrivent dans votre boîte de prospects et par courriel. Chaque visiteur coche un consentement, conformément à la Loi 25.'],
      ['Combien coûte une carte ?', 'À partir de 89 $ CAD (Standard) et 129 $ CAD (Premium). Bois, métal et commandes d\'équipe sur devis.'],
      ['Quels sont les délais de livraison ?', 'La production commence après validation de votre épreuve. Le délai exact vous est confirmé avec le devis, selon la matière et la quantité.'],
      ['Et si je perds ma carte ?', 'Désactivez-la en un clic depuis votre espace client, puis commandez un remplacement relié au même profil.'],
      ['Puis-je payer en ligne ?', 'Oui. Une fois votre devis accepté, vous recevez un lien de paiement sécurisé par carte de crédit.'],
    ];
?>
<!-- HERO -->
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <p class="kicker et-up et-d1"><?= te('Cartes d\'affaires NFC & QR · Laval · Montréal · Québec') ?></p>
      <h1 class="et-up et-d2"><?= te('La carte NFC') ?> <span class="soft"><?= te('qui parle pour vous.') ?></span></h1>
      <p class="lead et-up et-d3"><?= te('Un toucher sur un iPhone ou un Android, et votre profil s\'ouvre sur-le-champ, sans application. Vous le mettez à jour quand vous voulez et voyez, dans votre tableau de bord, chaque toucher, visite et prospect.') ?></p>
      <div class="actions et-up et-d4">
        <a href="#commande" class="btn btn-primary btn-lg"><?= te('Commander ma carte') ?></a>
        <a href="#materiaux" class="btn btn-ghost btn-lg"><?= te('Voir les designs') ?></a>
      </div>
      <ul class="trust et-up et-d5">
        <li><?= $check ?><?= te('Sans application') ?></li>
        <li><?= $check ?><?= te('iPhone & Android') ?></li>
        <li><?= $check ?><?= te('Toujours à jour') ?></li>
      </ul>
    </div>

    <div class="hero-visual et-visual" aria-hidden="true">
      <div class="demo-card et-card">
        <div class="demo-card-top">
          <span class="demo-card-name">Sophie Lavoie</span>
          <svg width="30" height="30" viewBox="0 0 28 28" fill="none"><path d="M10 9a7 7 0 0 1 0 10M14 6a11 11 0 0 1 0 16M18 3a15 15 0 0 1 0 22" stroke="#FFED00" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <div class="demo-card-bottom">
          <span><?= te('Courtière immobilière') ?></span>
          <span class="demo-qr"><?php foreach ([1,0,1,1,0,1,0,1,1,1,0,0,1,0,1,1] as $b): ?><i<?= $b ? ' class="on"' : '' ?>></i><?php endforeach; ?></span>
        </div>
      </div>
      <span class="et-ripple"></span>
      <div class="demo-phone et-phone">
        <div class="demo-cover"></div>
        <span class="et-hint"><?= te('Approchez la carte…') ?></span>
        <div class="demo-avatar et-r et-r1">SL</div>
        <div class="demo-id et-r et-r2"><strong>Sophie Lavoie</strong><span><?= te('Courtière immobilière') ?></span></div>
        <div class="demo-save et-r et-r3"><?= te('Enregistrer le contact') ?></div>
        <div class="demo-actions et-r et-r4">
          <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" stroke="#14284B" stroke-width="2" stroke-linejoin="round"/></svg><?= te('Appeler') ?></span>
          <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="#14284B" stroke-width="2"/><path d="M3 7l9 6 9-6" stroke="#14284B" stroke-width="2"/></svg><?= te('Courriel') ?></span>
          <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z" stroke="#14284B" stroke-width="2" stroke-linejoin="round"/></svg>WhatsApp</span>
        </div>
        <div class="demo-links et-r et-r5">
          <span><?= te('Prendre rendez-vous') ?> <b>→</b></span>
          <span>LinkedIn <b>→</b></span>
          <span><?= te('Laissez vos coordonnées') ?> <b>→</b></span>
        </div>
      </div>
    </div>
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
<section id="materiaux" class="dark">
  <div class="section container et-rv">
    <div class="section-head">
      <div>
        <p class="eyebrow eyebrow-dark"><?= te('Nos designs') ?></p>
        <h2 class="h2"><?= te('Quatre matières, un design fait pour vous.') ?></h2>
        <p class="sub" style="margin-bottom: 40px;"><?= te('Votre marque, vos couleurs, votre style. Une épreuve vous est envoyée avant toute production.') ?></p>
      </div>
    </div>
    <div class="materials">
      <?php foreach ([
        ['Standard', 'PVC blanc ou noir, impression couleur recto-verso.', 'img/carte-standard.jpg', '#FFFFFF'],
        ['Premium', 'Finition mate ou brillante, design avancé sur mesure.', 'img/carte-premium.jpg', '#3666AF'],
        ['Bois', 'Gravure laser sur bois naturel. Chaleureux, écoresponsable.', 'img/carte-bois.jpg', '#A47148'],
        ['Métal', 'Acier gravé, la carte qu\'on garde. Sur devis.', 'img/carte-metal.jpg', '#8E949E'],
      ] as [$name, $desc, $img, $fallback]):
        $photo = null;
        foreach ($designs as $d) { if ($d['material'] === $name) { $photo = $d; break; } } ?>
      <article class="material">
        <?php if ($photo): ?>
          <img src="/uploads/designs/<?= e($photo['thumb_path']) ?>" alt="<?= te('Carte NFC {matiere} EliteTap', ['matiere' => t($name)]) ?>" loading="lazy" class="material-img">
        <?php elseif (is_file(APP_ROOT . '/public/assets/' . $img)): ?>
          <img src="<?= asset($img) ?>" alt="<?= te('Carte NFC {matiere} EliteTap', ['matiere' => t($name)]) ?>" loading="lazy" class="material-img">
        <?php else: ?>
          <div class="material-img" style="background: <?= $fallback ?>"></div>
        <?php endif; ?>
        <h3><?= te($name) ?></h3>
        <p><?= te($desc) ?></p>
      </article>
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

<!-- TARIFS -->
<section id="tarifs" class="section container et-rv">
  <p class="eyebrow"><?= te('Tarifs') ?></p>
  <h2 class="h2"><?= te('Prix clairs, devis gratuit.') ?></h2>
  <p class="sub"><?= te('Le prix final dépend du design, de la finition et de la quantité. Réponse en moins de 24 h.') ?></p>
  <div class="plans">
    <article class="plan">
      <div><h3><?= te('Standard') ?></h3><p class="muted"><?= te('Pour démarrer.') ?></p></div>
      <p class="price-line"><?= te('À partir de') ?> <span class="price"><?= money(display_price('Standard', 8900)) ?></span><?= te('par carte, design de base inclus') ?></p>
      <ul><li><?= te('NFC + code QR') ?></li><li><?= te('Profil hébergé à vie') ?></li><li><?= te('6 thèmes au choix') ?></li><li><?= te('Livraison au Québec') ?></li></ul>
      <a href="#commande" class="btn btn-outline" data-card="Standard"><?= te('Choisir Standard') ?></a>
    </article>
    <article class="plan plan-featured">
      <div><div class="plan-title"><h3><?= te('Premium') ?></h3><span class="badge"><?= te('Le plus choisi') ?></span></div><p class="muted"><?= te('Finition haut de gamme.') ?></p></div>
      <p class="price-line"><?= te('À partir de') ?> <span class="price"><?= money(display_price('Premium', 12900)) ?></span><?= te('par carte, design premium inclus') ?></p>
      <ul><li><?= te('Tout le plan Standard') ?></li><li><?= te('Finition mate ou brillante') ?></li><li><?= te('Design avancé sur mesure') ?></li><li><?= te('Production prioritaire') ?></li></ul>
      <a href="#commande" class="btn btn-primary" data-card="Premium"><?= te('Choisir Premium') ?></a>
    </article>
    <article class="plan">
      <div><h3><?= te('Entreprise') ?></h3><p class="muted"><?= te('Équipes et identité corporative.') ?></p></div>
      <p class="price-line"><?= te('Tarif') ?> <span class="price"><?= te('Sur devis') ?></span><?= te('dégressif selon la quantité') ?></p>
      <ul><li><?= te('Cartes bois ou métal') ?></li><li><?= te('Une carte par employé') ?></li><li><?= te('Branding corporatif complet') ?></li><li><?= te('Accompagnement dédié') ?></li></ul>
      <a href="#commande" class="btn btn-outline" data-card="Métal"><?= te('Demander un devis') ?></a>
    </article>
  </div>
</section>

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

<!-- COMPATIBILITE + TEMOIGNAGES -->
<section class="section-bottom container split et-rv">
  <div class="tile compat">
    <h2 class="h3"><?= te('Compatible avec votre téléphone ?') ?></h2>
    <div class="compat-grid">
      <div><h3>iPhone</h3><p><?= te('XS / XR et plus récent, iOS 13+. Aucune application.') ?></p></div>
      <div><h3>Android</h3><p><?= te('La plupart des modèles depuis 2015, NFC activé.') ?></p></div>
    </div>
    <p class="note"><?= te('Pas de NFC ? Le code QR au dos fonctionne avec n\'importe quel appareil.') ?></p>
  </div>
  <div class="quotes">
    <figure class="tile">
      <blockquote><?= te('« Dans la gestion de fonds de retraite, la crédibilité est primordiale. EliteTap m\'a permis de moderniser mon approche tout en restant professionnel. »') ?></blockquote>
      <figcaption><strong>Guy</strong> · <?= te('Gestion de fonds, Montréal') ?></figcaption>
    </figure>
    <figure class="tile">
      <blockquote><?= te('« Ma carte a été entièrement personnalisée à l\'image de mon entreprise. Professionnel, moderne et élégant. »') ?></blockquote>
      <figcaption><strong>Jean Cyrus Oulai</strong> · Gestion JC</figcaption>
    </figure>
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
      <h2 class="h2 h2-xl"><?= te('Parlons de votre carte.') ?></h2>
      <p class="lead"><?= te('Devis gratuit, réponse en moins de 24 h. Vous recevez une épreuve avant toute production.') ?></p>
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
      <label><?= te('Prénom et nom') ?><input type="text" name="name" autocomplete="name" required value="<?= e(old('name')) ?>"></label>
      <label><?= te('Entreprise') ?> <span class="opt">(<?= te('optionnel') ?>)</span><input type="text" name="company" autocomplete="organization" value="<?= e(old('company')) ?>"></label>
      <label><?= te('Courriel') ?><input type="email" name="email" autocomplete="email" required value="<?= e(old('email')) ?>"></label>
      <label><?= te('Téléphone') ?> <span class="opt">(<?= te('optionnel') ?>)</span><input type="tel" name="phone" autocomplete="tel" value="<?= e(old('phone')) ?>"></label>
      <label><?= te('Type de carte') ?>
        <select name="card_type" id="card_type">
          <?php foreach (CARD_TYPES as $ct): ?>
            <option value="<?= e($ct) ?>"<?= old('card_type', 'Premium') === $ct ? ' selected' : '' ?>><?= te($ct) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label><?= te('Quantité') ?><input type="number" name="quantity" min="1" max="10000" value="<?= e(old('quantity', '1')) ?>"></label>
      <label class="span-2"><?= te('Votre idée de design') ?> <span class="opt">(<?= te('optionnel') ?>)</span><textarea name="message" rows="4"><?= e(old('message')) ?></textarea></label>
      <label class="span-2"><?= te('Logo ou maquette') ?> <span class="opt">(<?= te('PNG, JPG, SVG ou PDF, 10 Mo max') ?>)</span><input type="file" name="file" accept=".png,.jpg,.jpeg,.webp,.svg,.pdf"></label>
      <label class="hp" aria-hidden="true">Site web<input type="text" name="site_web" tabindex="-1" autocomplete="off"></label>
      <button type="submit" class="btn btn-primary btn-lg span-2"><?= te('Recevoir mon devis gratuit') ?></button>
      <p class="fine span-2"><?= t('En envoyant ce formulaire, vous acceptez notre <a href="/confidentialite">politique de confidentialité</a>.') ?></p>
    </form>
  </div>
</section>

<?php
// Données structurées : FAQ (résultats enrichis) et offres avec prix
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
            'offers' => [
                ['@type' => 'Offer', 'name' => t('Standard'), 'price' => number_format(display_price('Standard', 8900) / 100, 2, '.', ''), 'priceCurrency' => 'CAD', 'availability' => 'https://schema.org/InStock', 'url' => url('/#tarifs')],
                ['@type' => 'Offer', 'name' => t('Premium'), 'price' => number_format(display_price('Premium', 12900) / 100, 2, '.', ''), 'priceCurrency' => 'CAD', 'availability' => 'https://schema.org/InStock', 'url' => url('/#tarifs')],
            ],
        ],
    ],
];
?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
