<div class="page-head">
  <div>
    <h1>Mes cartes</h1>
    <p class="muted">Carte perdue ? Désactivez-la : elle n'ouvrira plus votre profil. Vous pouvez la réactiver à tout moment.</p>
  </div>
</div>

<?php if (!$cards): ?>
  <div class="panel empty">
    <p><strong>Aucune carte reliée à votre profil.</strong></p>
    <p class="muted">Vous avez reçu une carte neuve ? Tapez-la sur votre téléphone en étant connecté : vous pourrez l'activer en un clic.</p>
  </div>
<?php else: ?>
  <div class="card-grid">
    <?php foreach ($cards as $c): ?>
      <div class="panel card-item">
        <div class="mini-card mini-<?= e(slugify($c['material'])) ?>" aria-hidden="true"></div>
        <div class="card-meta">
          <strong><?= e($c['material']) ?></strong>
          <span class="muted">Code <?= e($c['code']) ?> · <?= (int) $c['taps'] ?> tap(s)</span>
          <span class="status status-<?= e($c['status']) ?>"><?= $c['status'] === 'active' ? 'Active' : 'Désactivée' ?></span>
        </div>
        <form method="post" action="/compte/cartes/<?= (int) $c['id'] ?>"<?= $c['status'] === 'active' ? ' data-confirm="Désactiver cette carte ? Elle n\'ouvrira plus votre profil."' : '' ?>>
          <?= csrf_field() ?>
          <button class="btn <?= $c['status'] === 'active' ? 'btn-light danger' : 'btn-primary' ?>" type="submit"><?= $c['status'] === 'active' ? 'Désactiver' : 'Réactiver' ?></button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="panel">
  <h2 class="h2">Besoin d'une autre carte ?</h2>
  <p class="muted">Remplacement, deuxième matière, cartes pour votre équipe : écrivez-nous, elles seront reliées à ce même profil.</p>
  <p class="row-actions"><a class="btn btn-light" href="mailto:info@elitetap.ca?subject=Nouvelle carte">info@elitetap.ca</a> <a class="btn btn-light" href="https://wa.me/<?= e(config('whatsapp')) ?>" rel="noopener">WhatsApp</a></p>
</div>
