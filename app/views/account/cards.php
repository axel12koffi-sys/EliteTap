<?php $profileUrl = url('/u/' . $p['slug']); ?>
<div class="page-head">
  <div>
    <h1><?= te('Mes cartes') ?></h1>
    <p class="muted"><?= te('Carte perdue ? Désactivez-la : elle n\'ouvrira plus votre profil. Vous pouvez la réactiver à tout moment.') ?></p>
  </div>
</div>

<?php if (!$cards): ?>
  <div class="panel empty">
    <p><strong><?= te('Aucune carte reliée à votre profil.') ?></strong></p>
    <p class="muted"><?= te('Vous avez reçu une carte neuve ? Tapez-la sur votre téléphone en étant connecté : vous pourrez l\'activer en un clic.') ?></p>
  </div>
<?php else: ?>
  <div class="card-grid">
    <?php foreach ($cards as $c): ?>
      <div class="panel card-item">
        <div class="mini-card mini-<?= e(slugify($c['material'])) ?>" aria-hidden="true"></div>
        <div class="card-meta">
          <strong><?= te($c['material']) ?></strong>
          <span class="muted"><?= te('Code {code} · {n} tap(s)', ['code' => $c['code'], 'n' => (int) $c['taps']]) ?></span>
          <span class="status status-<?= e($c['status']) ?>"><?= $c['status'] === 'active' ? te('Active') : te('Désactivée') ?></span>
        </div>
        <form method="post" action="/compte/cartes/<?= (int) $c['id'] ?>"<?= $c['status'] === 'active' ? ' data-confirm="' . te('Désactiver cette carte ? Elle n\'ouvrira plus votre profil.') . '"' : '' ?>>
          <?= csrf_field() ?>
          <button class="btn <?= $c['status'] === 'active' ? 'btn-light danger' : 'btn-primary' ?>" type="submit"><?= $c['status'] === 'active' ? te('Désactiver') : te('Réactiver') ?></button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="panel">
  <h2 class="h2"><?= te('QR code de votre profil') ?></h2>
  <div class="qr-panel">
    <div class="qr-box" data-qr="<?= e($profileUrl) ?>"></div>
    <div>
      <p class="muted"><?= te('À ajouter à votre signature courriel, vos présentations ou un comptoir : il ouvre votre profil comme votre carte.') ?></p>
      <p class="row-actions"><button class="btn btn-light" type="button" data-qr-download="<?= e($profileUrl) ?>" data-filename="qr-<?= e($p['slug']) ?>.svg"><?= te('Télécharger (SVG)') ?></button></p>
    </div>
  </div>
</div>

<div class="panel">
  <h2 class="h2"><?= te('Besoin d\'une autre carte ?') ?></h2>
  <p class="muted"><?= te('Remplacement, deuxième matière, cartes pour votre équipe : écrivez-nous, elles seront reliées à ce même profil.') ?></p>
  <p class="row-actions"><a class="btn btn-light" href="mailto:info@elitetap.ca?subject=<?= rawurlencode(t('Nouvelle carte')) ?>">info@elitetap.ca</a> <a class="btn btn-light" href="https://wa.me/<?= e(config('whatsapp')) ?>" rel="noopener">WhatsApp</a></p>
</div>
