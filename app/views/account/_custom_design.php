<?php if (design_ready()): $hasCustom = !empty($p['custom_design_id']); ?>
<section class="panel sur-mesure" id="sur-mesure" aria-labelledby="sm-title">
  <h2 class="h2" id="sm-title"><?= te('Profil sur mesure') ?></h2>
  <?php if ($hasCustom): ?>
    <p><?= $p['use_custom'] ? te('Votre profil affiche un design créé à partir de votre carte. Vos informations se modifient ici : le design suit automatiquement.') : te('Votre profil affiche actuellement le thème standard. Votre design sur mesure reste disponible.') ?></p>
    <div class="row-actions" style="margin-top: 12px;">
      <form method="post" action="/compte/sur-mesure"><?= csrf_field() ?><input type="hidden" name="use_custom" value="<?= $p['use_custom'] ? '0' : '1' ?>">
        <button class="btn btn-light" type="submit"><?= $p['use_custom'] ? te('Revenir au thème standard') : te('Afficher mon design sur mesure') ?></button></form>
      <a class="btn btn-text" href="/u/<?= e($p['slug']) ?>" target="_blank" rel="noopener"><?= te('Voir mon profil') ?></a>
    </div>
  <?php else: ?>
    <p><?= te('Un profil aux couleurs exactes de votre carte : typographie, motifs et logo repris par notre équipe. Vos informations restent modifiables ici.') ?></p>
  <?php endif; ?>
  <details class="sm-request"<?= old('sm_open') ? ' open' : '' ?>>
    <summary><?= $hasCustom ? te('Demander une modification du design') : te('Demander mon profil sur mesure') ?></summary>
    <form method="post" action="/compte/sur-mesure/demande" class="form" style="margin-top: 10px;">
      <?= csrf_field() ?>
      <label><?= te('Votre demande') ?><textarea name="message" rows="3" maxlength="2000" required placeholder="<?= $hasCustom ? te('Ex. agrandir le logo, utiliser le bleu de ma carte pour les boutons') : te('Ex. reprendre le fond noir et doré de ma carte') ?>"></textarea></label>
      <div><button class="btn btn-primary" type="submit"><?= te('Envoyer la demande') ?></button></div>
    </form>
  </details>
</section>
<?php endif; ?>
