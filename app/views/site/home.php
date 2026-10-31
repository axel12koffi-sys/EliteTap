<?php
$check = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3 8.5l3 3 7-7" stroke="#60A5FA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$flashes = flashes();
$faq = [
      ['Faut-il une application ?', 'Non. Le profil s\'ouvre directement dans le navigateur du téléphone de votre contact.'],
      ['Puis-je modifier mes informations ?', 'Oui, à tout moment depuis votre espace client, sans réimprimer votre carte.'],
      ['Combien coûte une carte ?', 'À partir de 89 $ CAD (Standard) et 129 $ CAD (Premium). Bois, métal et commandes d\'équipe sur devis.'],
      ['Quels sont les délais de livraison ?', 'La production commence après validation de votre épreuve. Le délai exact vous est confirmé avec le devis, selon la matière et la quantité.'],
      ['Et si je perds ma carte ?', 'Désactivez-la en un clic depuis votre espace client, puis commandez un remplacement relié au même profil.'],
      ['Puis-je payer en ligne ?', 'Oui. Une fois votre devis accepté, vous recevez un lien de paiement sécurisé par carte de crédit.'],
    ];
?>
<!-- HERO -->
<section class="hero dark">
  <div class="container hero-grid">
    <div class="hero-copy">
      <p class="pill et-up et-d1"><?= te('Cartes de visite NFC · Laval, Montréal & tout le Québec') ?></p>
      <h1 class="et-up et-d2"><?= te('Votre carte d\'affaires, partagée en un seul geste.') ?></h1>
      <p class="lead et-up et-d3"><?= te('Un tap sur le téléphone de votre contact et votre profil s\'ouvre : coordonnées, liens, photo. Sans application. Modifiable à tout moment, sans réimprimer.') ?></p>
      <div class="actions et-up et-d4">
        <a href="#commande" class="btn btn-primary btn-lg"><?= te('Commander ma carte') ?></a>
        <a href="#fonctionnement" class="btn btn-ghost btn-lg"><?= te('Comment ça marche') ?></a>
      </div>
      <ul class="trust et-up et-d5">
        <li><?= $check ?><?= te('Sans application') ?></li>
        <li><?= $check ?><?= te('iPhone & Android') ?></li>
        <li><?= $check ?><?= te('Devis en moins de 24 h') ?></li>
      </ul>
    </div>

    <div class="hero-visual et-visual" aria-hidden="true">
      <div class="demo-card et-card">
        <div class="demo-card-top">
          <span class="demo-card-name">Sophie Lavoie</span>
          <svg width="30" height="30" viewBox="0 0 28 28" fill="none"><path d="M10 9a7 7 0 0 1 0 10M14 6a11 11 0 0 1 0 16M18 3a15 15 0 0 1 0 22" stroke="#60A5FA" stroke-width="2" stroke-linecap="round"/></svg>
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
          <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" stroke="#14161A" stroke-width="2" stroke-linejoin="round"/></svg><?= te('Appeler') ?></span>
          <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="#14161A" stroke-width="2"/><path d="M3 7l9 6 9-6" stroke="#14161A" stroke-width="2"/></svg><?= te('Courriel') ?></span>
          <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#14161A" stroke-width="2"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18" stroke="#14161A" stroke-width="2"/></svg><?= te('Site web') ?></span>
        </div>
        <div class="demo-links et-r et-r5">
          <span><?= te('Prendre rendez-vous') ?> <b>→</b></span>
          <span>LinkedIn <b>→</b></span>
          <span>Instagram <b>→</b></span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FONCTIONNEMENT -->
<section id="fonctionnement" class="section container et-rv">
  <p class="eyebrow"><?= te('Comment ça fonctionne') ?></p>
  <h2 class="h2"><?= te('Trois étapes, puis vous n\'imprimez plus jamais de cartes.') ?></h2>
  <ol class="steps">
    <li class="tile"><span class="step-n">01</span><h3><?= te('Choisissez votre carte') ?></h3><p><?= te('PVC, finition premium, bois ou métal. Nous créons le design à vos couleurs et vous envoyons une épreuve à valider.') ?></p></li>
    <li class="tile"><span class="step-n">02</span><h3><?= te('Créez votre profil') ?></h3><p><?= te('Depuis votre espace client : photo, coordonnées, réseaux, liens de prise de rendez-vous.') ?></p></li>
    <li class="tile"><span class="step-n">03</span><h3><?= te('Tapez, c\'est partagé') ?></h3><p><?= te('Approchez la carte d\'un téléphone : le profil s\'ouvre. Pas de NFC ? Le code QR au dos fait le même travail.') ?></p></li>
  </ol>
</section>

<section class="section-tight container et-rv">
  <div class="features">
    <div><svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4z" stroke="#14161A" stroke-width="1.8" stroke-linejoin="round"/></svg><h3><?= te('Toujours à jour') ?></h3><p><?= te('Nouveau poste, nouveau numéro : modifiez votre profil, la carte suit.') ?></p></div>
    <div><svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3" stroke="#14161A" stroke-width="1.8"/><path d="M11 18h2" stroke="#14161A" stroke-width="1.8" stroke-linecap="round"/></svg><h3><?= te('NFC + code QR') ?></h3><p><?= te('Deux façons de partager, pour que chaque contact reparte avec vos infos.') ?></p></div>
    <div><svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2" stroke="#14161A" stroke-width="1.8" stroke-linecap="round"/></svg><h3><?= te('Statistiques de taps') ?></h3><p><?= te('Voyez combien de fois votre profil est ouvert et quels liens sont cliqués.') ?></p></div>
    <div><svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21c-5-3-8-7-8-11a8 8 0 0 1 16 0c0 4-3 8-8 11z" stroke="#14161A" stroke-width="1.8"/><path d="M9 11l2 2 4-4" stroke="#14161A" stroke-width="1.8" stroke-linecap="round"/></svg><h3><?= te('Une seule carte, durable') ?></h3><p><?= te('Fini les boîtes de 500 cartes à jeter à chaque changement.') ?></p></div>
  </div>
</section>

<!-- MATERIAUX -->
<section id="materiaux" class="dark">
  <div class="section container et-rv">
    <div class="section-head">
      <div>
        <p class="eyebrow eyebrow-dark"><?= te('Nos cartes') ?></p>
        <h2 class="h2"><?= te('Quatre matières, un design fait pour vous.') ?></h2>
      </div>
    </div>
    <div class="materials">
      <?php foreach ([
        ['Standard', 'PVC blanc ou noir, impression couleur recto-verso.', 'img/carte-standard.jpg', '#FFFFFF'],
        ['Premium', 'Finition mate ou brillante, design avancé sur mesure.', 'img/carte-premium.jpg', '#1B1D22'],
        ['Bois', 'Gravure laser sur bois naturel. Chaleureux, écoresponsable.', 'img/carte-bois.jpg', '#A47148'],
        ['Métal', 'Acier gravé, la carte qu\'on garde. Sur devis.', 'img/carte-metal.jpg', '#8E949E'],
      ] as [$name, $desc, $img, $fallback]): ?>
      <article class="material">
        <?php if (is_file(APP_ROOT . '/public/assets/' . $img)): ?>
          <img src="<?= asset($img) ?>" alt="<?= te('Carte NFC {matiere} EliteTap', ['matiere' => t($name)]) ?>" loading="lazy" class="material-img">
        <?php else: ?>
          <div class="material-img" style="background: <?= $fallback ?>"></div>
        <?php endif; ?>
        <h3><?= te($name) ?></h3>
        <p><?= te($desc) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- TARIFS -->
<section id="tarifs" class="section container et-rv">
  <p class="eyebrow"><?= te('Tarifs') ?></p>
  <h2 class="h2"><?= te('Prix clairs, devis gratuit.') ?></h2>
  <p class="sub"><?= te('Le prix final dépend du design, de la finition et de la quantité. Réponse en moins de 24 h.') ?></p>
  <div class="plans">
    <article class="plan">
      <div><h3><?= te('Standard') ?></h3><p class="muted"><?= te('Pour démarrer.') ?></p></div>
      <p class="price-line"><?= te('À partir de') ?> <span class="price"><?= money(8900) ?></span><?= te('par carte, design de base inclus') ?></p>
      <ul><li><?= te('NFC + code QR') ?></li><li><?= te('Profil modifiable à vie') ?></li><li><?= te('Statistiques de base') ?></li><li><?= te('Livraison au Québec') ?></li></ul>
      <a href="#commande" class="btn btn-outline" data-card="Standard"><?= te('Choisir Standard') ?></a>
    </article>
    <article class="plan plan-featured">
      <div><div class="plan-title"><h3><?= te('Premium') ?></h3><span class="badge"><?= te('Le plus choisi') ?></span></div><p class="muted"><?= te('Finition haut de gamme.') ?></p></div>
      <p class="price-line"><?= te('À partir de') ?> <span class="price"><?= money(12900) ?></span><?= te('par carte, design premium inclus') ?></p>
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
        <a href="https://wa.me/<?= e(config('whatsapp')) ?>" rel="noopener"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z" stroke="#60A5FA" stroke-width="1.8" stroke-linejoin="round"/></svg>WhatsApp · 438 220-7167</a>
        <a href="mailto:info@elitetap.ca"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="#60A5FA" stroke-width="1.8"/><path d="M3 7l9 6 9-6" stroke="#60A5FA" stroke-width="1.8"/></svg>info@elitetap.ca</a>
        <a href="https://www.instagram.com/elitetap_ca/" rel="noopener"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" stroke="#60A5FA" stroke-width="1.8"/><circle cx="12" cy="12" r="4" stroke="#60A5FA" stroke-width="1.8"/></svg>@elitetap_ca</a>
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
                ['@type' => 'Offer', 'name' => t('Standard'), 'price' => '89.00', 'priceCurrency' => 'CAD', 'availability' => 'https://schema.org/InStock', 'url' => url('/#tarifs')],
                ['@type' => 'Offer', 'name' => t('Premium'), 'price' => '129.00', 'priceCurrency' => 'CAD', 'availability' => 'https://schema.org/InStock', 'url' => url('/#tarifs')],
            ],
        ],
    ],
];
?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
