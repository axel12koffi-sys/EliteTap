<?php $labels = ['confirmed' => 'Confirmés', 'pending' => 'En attente de confirmation', 'unsubscribed' => 'Désabonnés']; $src = ['chat' => 'Clavardage', 'after_chat' => 'Après clavardage', 'footer' => 'Pied de page', 'site' => 'Site']; ?>
<div class="page-head">
  <div>
    <h1>Infolettre</h1>
    <p class="muted">Inscriptions avec double confirmation par courriel. Le texte du consentement et sa date sont conservés (LCAP, Loi 25). <?= $month ?> nouvel(s) abonné(s) confirmé(s) en 30 jours.</p>
  </div>
  <a class="btn btn-primary" href="/admin/infolettre/export.csv">Exporter les abonnés confirmés (CSV)</a>
</div>
<div class="page-head" style="align-items: center;">
  <nav class="tabs" aria-label="Statut">
    <?php foreach ($labels as $k => $l): ?><a href="/admin/infolettre?statut=<?= $k ?>"<?= $status === $k ? ' aria-current="true"' : '' ?>><?= e($l) ?> <span><?= $counts[$k] ?? 0 ?></span></a><?php endforeach; ?>
  </nav>
  <form method="get" action="/admin/infolettre" class="search">
    <input type="hidden" name="statut" value="<?= e($status) ?>">
    <label class="visually-hidden" for="q">Rechercher</label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Courriel ou nom">
    <button class="btn btn-light" type="submit">Rechercher</button>
  </form>
</div>
<div class="panel table-wrap">
  <?php if (!$subs): ?>
    <p class="muted empty-row">Aucun abonné dans cette liste.</p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>Courriel</th><th>Source</th><th>Langue</th><th>Consentement</th><th>Confirmé</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
    <tbody>
      <?php foreach ($subs as $s): ?>
        <tr>
          <td><strong><?= e($s['email']) ?></strong><?= $s['name'] !== '' ? '<br><span class="muted small">' . e($s['name']) . '</span>' : '' ?></td>
          <td class="small"><?= e($src[$s['source']] ?? $s['source']) ?></td>
          <td class="small"><?= strtoupper(e($s['lang'])) ?></td>
          <td class="small muted"><?= date_fr($s['consented_at']) ?></td>
          <td class="small"><?= $s['confirmed_at'] ? date_fr($s['confirmed_at']) : '—' ?><?= $s['unsubscribed_at'] ? '<br><span class="muted">désabonné ' . date_fr($s['unsubscribed_at']) . '</span>' : '' ?></td>
          <td class="right"><form method="post" action="/admin/infolettre/<?= (int) $s['id'] ?>/supprimer" data-confirm="Effacer définitivement cet abonné et son consentement ?"><?= csrf_field() ?><button class="btn btn-text btn-sm" type="submit">Effacer</button></form></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<p class="muted small">Pour envoyer l'infolettre, importez le CSV dans votre outil d'envoi (Mailchimp, Brevo…) : il contient le lien de désabonnement de chaque abonné. Un abonné désabonné ne doit plus recevoir d'envoi ; « Effacer » répond à une demande de suppression de ses données.</p>
