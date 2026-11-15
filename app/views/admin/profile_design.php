<?php
$statusLabel = ['draft' => 'Brouillon', 'published' => 'En ligne', 'archived' => 'Archivée'];
$hasCode = $current && trim((string) $current['html']) !== '';
$isPdf = $current && $current['source_mime'] === 'application/pdf';
$base = '/admin/clients/' . (int) $client['id'] . '/design';
$live = $p['custom_design_id'] ? (int) $p['custom_design_id'] : null;
?>
<p class="crumb"><a href="/admin/clients">Clients</a> › <?= e($client['name']) ?></p>
<div class="page-head">
  <div>
    <h1>Profil sur mesure</h1>
    <p class="muted"><?= e($client['name']) ?> · <a href="/u/<?= e($p['slug']) ?>" target="_blank" rel="noopener">/u/<?= e($p['slug']) ?></a> ·
      <?= $live ? 'design sur mesure en ligne' . ($p['use_custom'] ? '' : ' (le client affiche le thème standard)') : 'thème standard' ?></p>
  </div>
  <?php if ($current): ?><span class="ship-status <?= $current['status'] === 'published' ? 'ship-livree' : ($current['status'] === 'draft' ? 'ship-a_preparer' : '') ?> ship-status-lg">Version <?= (int) $current['version'] ?> · <?= e($statusLabel[$current['status']]) ?></span><?php endif; ?>
</div>

<?php if (!$current): ?>
  <div class="panel form narrow-panel">
    <h2 class="h2">1. Ajoutez le design de la carte</h2>
    <p class="muted">Le fichier de la carte commandée (recto, ou recto et verso dans un PDF). L'IA s'en sert pour créer un profil dans le même style ; les informations du client restent celles de son espace.</p>
    <form method="post" action="<?= $base ?>/source" enctype="multipart/form-data" class="row-forms">
      <?= csrf_field() ?>
      <label class="visually-hidden" for="source">Design de la carte</label>
      <input type="file" id="source" name="source" accept="image/png,image/jpeg,image/webp,application/pdf" required>
      <button class="btn btn-primary" type="submit">Téléverser</button>
    </form>
    <?php if (!$aiOn): ?><p class="alert alert-error">L'IA n'est pas encore configurée : ajoutez la clé API Anthropic dans <span class="mono">app/config.php</span> (voir config.sample.php). Vous pouvez aussi écrire le gabarit à la main.</p><?php endif; ?>
  </div>
<?php else: ?>

<div class="design-layout">
  <section class="panel design-preview" aria-label="Aperçu">
    <div class="design-preview-bar">
      <div class="segmented" role="group" aria-label="Taille de l'aperçu">
        <button type="button" class="is-on" data-device="390" aria-pressed="true">Mobile</button>
        <button type="button" data-device="1280" aria-pressed="false">Ordinateur</button>
      </div>
      <a class="btn btn-text btn-sm" href="<?= $base ?>/<?= (int) $current['id'] ?>/apercu" target="_blank" rel="noopener">Ouvrir dans un onglet</a>
    </div>
    <?php if ($hasCode): ?>
      <div class="device-frame" data-frame-wrap>
        <iframe title="Aperçu du profil sur mesure" src="<?= $base ?>/<?= (int) $current['id'] ?>/apercu" data-preview width="390" height="760"></iframe>
      </div>
      <div class="design-checks" data-checks>
        <h3 class="h3">Vérifications</h3>
        <ul class="check-list">
          <?php foreach ($checks as $c): ?><li class="is-warn"><?= e($c) ?></li><?php endforeach; ?>
          <?php if ($report): ?><li class="is-warn">Retiré par le filtre de sécurité : <?= e(implode(', ', array_slice($report, 0, 12))) ?><?= count($report) > 12 ? '…' : '' ?></li><?php endif; ?>
          <li class="muted" data-readability>Contrôle de lisibilité en cours…</li>
        </ul>
      </div>
    <?php else: ?>
      <p class="muted design-empty"><?= $aiOn ? 'Pas encore de gabarit. Lancez la génération par l\'IA à droite.' : 'Pas encore de gabarit. Écrivez-le ci-dessous ou configurez l\'IA.' ?></p>
    <?php endif; ?>
  </section>

  <aside class="design-side">
    <div class="panel">
      <h2 class="h2">Design de la carte</h2>
      <?php if ($current['source_path']): ?>
        <?php if ($isPdf): ?>
          <p><a href="<?= $base ?>/<?= (int) $current['id'] ?>/carte" target="_blank" rel="noopener">Ouvrir le PDF (<?= e($current['source_name']) ?>)</a></p>
        <?php else: ?>
          <a href="<?= $base ?>/<?= (int) $current['id'] ?>/carte" target="_blank" rel="noopener"><img class="design-source" src="<?= $base ?>/<?= (int) $current['id'] ?>/carte" alt="Design de la carte de <?= e($client['name']) ?>"></a>
        <?php endif; ?>
      <?php else: ?><p class="muted small">Aucun fichier pour cette version.</p><?php endif; ?>
      <form method="post" action="<?= $base ?>/source" enctype="multipart/form-data" class="form" style="margin-top: 12px;">
        <?= csrf_field() ?>
        <label class="small">Nouveau design (crée une nouvelle version)<input type="file" name="source" accept="image/png,image/jpeg,image/webp,application/pdf" required></label>
        <button class="btn btn-light btn-sm" type="submit">Téléverser</button>
      </form>
    </div>

    <form method="post" action="<?= $base ?>/<?= (int) $current['id'] ?>/generer" class="panel form" data-ai-form>
      <?= csrf_field() ?>
      <h2 class="h2">Agent IA</h2>
      <?php if (!$aiOn): ?>
        <p class="alert alert-error">Ajoutez la clé API Anthropic dans <span class="mono">app/config.php</span> pour activer la génération.</p>
      <?php endif; ?>
      <?php if ($hasCode): ?>
        <fieldset class="fieldset">
          <legend class="visually-hidden">Mode</legend>
          <label class="check"><input type="radio" name="mode" value="corriger" checked> Corriger le gabarit actuel</label>
          <label class="check"><input type="radio" name="mode" value="nouveau"> Repartir de zéro</label>
        </fieldset>
      <?php endif; ?>
      <label><?= $hasCode ? 'Correction demandée' : 'Indications (facultatif)' ?>
        <textarea name="instruction" rows="3" maxlength="2000" placeholder="<?= $hasCode ? 'Ex. logo plus grand, fond plus sombre, boutons arrondis comme la carte' : 'Ex. reprendre le motif doré du verso, fond noir' ?>"></textarea></label>
      <button class="btn btn-primary" type="submit"<?= $aiOn && $current['source_path'] ? '' : ' disabled' ?> data-ai-submit><?= $hasCode ? 'Corriger avec l\'IA' : 'Générer avec l\'IA' ?></button>
      <p class="hint" data-ai-wait hidden>L'IA travaille… cela prend en général 20 à 60 secondes.</p>
      <p class="hint">Le résultat est enregistré comme brouillon : rien n'est publié sans votre validation.</p>
    </form>

    <div class="panel">
      <h2 class="h2">Images d'habillage</h2>
      <p class="muted small">Fond, motif ou texture repris de la carte, utilisables dans le gabarit par leur nom.</p>
      <?php if ($assets): ?>
        <ul class="plain-list">
          <?php foreach ($assets as $name => $url): ?>
            <li class="asset-row"><img src="<?= e($url) ?>" alt="" width="40" height="40"><span class="mono small">{{asset:<?= e($name) ?>}}</span>
              <form method="post" action="<?= $base ?>/image"><?= csrf_field() ?><input type="hidden" name="v" value="<?= (int) $current['id'] ?>"><button class="btn btn-text btn-sm" name="delete" value="<?= e($name) ?>">Retirer</button></form></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <form method="post" action="<?= $base ?>/image" enctype="multipart/form-data" class="form" style="margin-top: 10px;">
        <?= csrf_field() ?>
        <input type="hidden" name="v" value="<?= (int) $current['id'] ?>">
        <div class="grid-2">
          <label class="small">Nom<input type="text" name="name" maxlength="40" placeholder="fond" required></label>
          <label class="small">Image<input type="file" name="asset" accept="image/png,image/jpeg,image/webp,image/gif" required></label>
        </div>
        <button class="btn btn-light btn-sm" type="submit">Ajouter l'image</button>
      </form>
    </div>

    <div class="panel">
      <h2 class="h2">Versions</h2>
      <ul class="plain-list versions">
        <?php foreach ($versions as $v): ?>
          <li<?= (int) $v['id'] === (int) $current['id'] ? ' class="is-current"' : '' ?>><a href="<?= $base ?>?v=<?= (int) $v['id'] ?>">Version <?= (int) $v['version'] ?></a>
            <span class="ship-status <?= $v['status'] === 'published' ? 'ship-livree' : ($v['status'] === 'draft' ? 'ship-a_preparer' : '') ?>"><?= e($statusLabel[$v['status']]) ?></span>
            <br><span class="muted small"><?= date_fr($v['updated_at']) ?><?= $v['ai_used'] ? ' · IA' : '' ?><?= $v['author'] ? ' · ' . e($v['author']) : '' ?></span></li>
        <?php endforeach; ?>
      </ul>
      <?php if ($live): ?>
        <form method="post" action="<?= $base ?>/<?= (int) $current['id'] ?>" data-confirm="Retirer le profil sur mesure ? Le profil standard s'affichera de nouveau." style="margin-top: 12px;">
          <?= csrf_field() ?>
          <button class="btn btn-text btn-sm danger" name="action" value="disable">Retirer le profil sur mesure</button>
        </form>
      <?php endif; ?>
    </div>
  </aside>
</div>

<form method="post" action="<?= $base ?>/<?= (int) $current['id'] ?>" class="panel form">
  <?= csrf_field() ?>
  <h2 class="h2">Code du gabarit</h2>
  <p class="muted small">HTML et CSS du profil. Les informations du client s'insèrent par les emplacements ; enregistrer une version en ligne crée un nouveau brouillon.</p>
  <div class="code-grid">
    <label>HTML<textarea name="html" rows="22" class="code-area" spellcheck="false"><?= e((string) $current['html']) ?></textarea></label>
    <label>CSS<textarea name="css" rows="22" class="code-area" spellcheck="false"><?= e((string) $current['css']) ?></textarea></label>
  </div>
  <details class="placeholders">
    <summary>Emplacements disponibles</summary>
    <div class="grid-2">
      <div><h3 class="h3">Informations (texte)</h3><ul class="plain-list small"><?php foreach (DESIGN_FIELDS as $k => $l): ?><li><span class="mono">{{<?= $k ?>}}</span> <?= e($l) ?></li><?php endforeach; ?></ul>
        <p class="small muted">Section affichée seulement si l'information existe : <span class="mono">{{#photo}}…{{/photo}}</span></p></div>
      <div><h3 class="h3">Éléments de la plateforme</h3><ul class="plain-list small"><?php foreach (DESIGN_COMPONENTS as $k => $l): ?><li><span class="mono">{{<?= $k ?>}}</span> <?= e($l) ?></li><?php endforeach; ?></ul>
        <p class="small muted">Couleurs des éléments : variables <span class="mono">--accent</span>, <span class="mono">--on-accent</span>, <span class="mono">--card</span>, <span class="mono">--line</span>, <span class="mono">--text</span>, <span class="mono">--muted</span> sur <span class="mono">.et-custom</span>.</p></div>
    </div>
  </details>
  <label class="check"><input type="checkbox" name="notify" value="1" checked> À la publication, prévenir le client par courriel</label>
  <div class="row-actions">
    <button class="btn btn-light" name="action" value="save">Enregistrer le brouillon</button>
    <button class="btn btn-primary" name="action" value="save_publish">Enregistrer et publier</button>
  </div>
  <p class="hint">Le filtre de sécurité retire tout script, formulaire, cadre et adresse externe (sauf les polices Google). Les éléments essentiels absents du gabarit sont ajoutés à la fin du profil.</p>
</form>
<?php endif; ?>
<script src="<?= asset('js/design.js') ?>" defer></script>
