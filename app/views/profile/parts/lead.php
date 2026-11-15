  <?php if ($leadForm): ?>
  <section class="lead" id="contact" aria-labelledby="lead-title">
    <h2 id="lead-title"><?= te('Laissez vos coordonnées') ?></h2>
    <?php if ($leadSent): ?>
      <p class="lead-ok" role="status"><?= te('Merci ! {nom} a bien reçu vos coordonnées.', ['nom' => $name]) ?></p>
    <?php else: ?>
      <p class="lead-intro"><?= te('{nom} vous recontactera.', ['nom' => $name]) ?></p>
      <?php foreach ($flashes as $f): ?><p class="lead-err" role="alert"><?= e($f['msg']) ?></p><?php endforeach; ?>
      <form method="post" action="/u/<?= e($p['slug']) ?>/prospect" class="lead-form">
        <?= csrf_field() ?>
        <label><?= te('Votre nom') ?><input type="text" name="name" required maxlength="120" autocomplete="name" value="<?= e(old('name')) ?>"></label>
        <label><?= te('Courriel') ?><input type="email" name="email" maxlength="190" autocomplete="email" value="<?= e(old('email')) ?>"></label>
        <label><?= te('Téléphone') ?><input type="tel" name="phone" maxlength="40" autocomplete="tel" value="<?= e(old('phone')) ?>"></label>
        <label><?= te('Votre entreprise') ?> <span class="opt">(<?= te('optionnel') ?>)</span><input type="text" name="company" maxlength="120" autocomplete="organization" value="<?= e(old('company')) ?>"></label>
        <label><?= te('Message') ?> <span class="opt">(<?= te('optionnel') ?>)</span><textarea name="message" rows="3" maxlength="1000"><?= e(old('message')) ?></textarea></label>
        <label class="hp" aria-hidden="true">Site web<input type="text" name="site_web" tabindex="-1" autocomplete="off"></label>
        <label class="consent"><input type="checkbox" name="consent" value="1" required> <span><?= e(lead_consent_text($p)) ?> <a href="/confidentialite"><?= te('Politique de confidentialité') ?></a></span></label>
        <button type="submit" class="send"><?= te('Envoyer mes coordonnées') ?></button>
      </form>
    <?php endif; ?>
  </section>
  <?php endif; ?>
