<?php
$labels = ['smtp' => 'SMTP', 'mail' => 'mail() du serveur', 'log' => 'Journal seulement (test)'];
$failed = count(array_filter($log, fn($l) => $l['status'] === 'failed'));
?>
<div class="page-head">
  <div>
    <h1>Courriels</h1>
    <p class="muted">Vérifiez que les courriels partent : test d'envoi et journal des 150 derniers envois (destinataire et sujet seulement).</p>
  </div>
</div>

<div class="cols">
  <div class="col-main">
    <div class="panel">
      <h2 class="h2">Journal des envois</h2>
      <?php if ($failed): ?><p class="alert alert-error"><?= $failed ?> envoi(s) en échec dans la liste : le détail est dans la colonne Erreur.</p><?php endif; ?>
      <?php if (!$log): ?>
        <p class="muted">Aucun envoi enregistré pour l'instant.</p>
      <?php else: ?>
      <div class="table-wrap" style="padding: 0; border: 0;">
        <table class="table">
          <thead><tr><th>Date</th><th>Destinataire</th><th>Sujet</th><th>Résultat</th><th>Erreur</th></tr></thead>
          <tbody>
            <?php foreach ($log as $l): ?>
              <tr>
                <td class="small muted"><?= date_fr($l['created_at']) ?></td>
                <td><?= e($l['to_email']) ?></td>
                <td><?= e($l['subject']) ?></td>
                <td><span class="status <?= $l['status'] === 'failed' ? 'status-disabled' : 'status-active' ?>"><?= $l['status'] === 'failed' ? 'Échec' : ($l['status'] === 'logged' ? 'Journalisé' : 'Envoyé') ?></span><br><span class="muted small"><?= e(strtoupper($l['transport'])) ?></span></td>
                <td class="small"><?= e($l['error'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-side">
    <form method="post" action="/admin/courriels" class="panel form">
      <?= csrf_field() ?>
      <h2 class="h2">Envoyer un courriel test</h2>
      <label>Destinataire<input type="email" name="to" value="<?= e($user['email']) ?>"></label>
      <button class="btn btn-primary" type="submit">Envoyer le test</button>
    </form>

    <div class="panel">
      <h2 class="h2">Mode d'envoi</h2>
      <p><strong><?= e($labels[$transport] ?? $transport) ?></strong></p>
      <?php if ($transport === 'smtp'): ?>
        <p class="muted small">Serveur <span class="mono"><?= e($smtp['host']) ?>:<?= (int) ($smtp['port'] ?? 465) ?></span>, compte <span class="mono"><?= e($smtp['user'] ?? '—') ?></span>.</p>
      <?php else: ?>
        <p class="muted small">Les courriels partent par <span class="mono">mail()</span>, souvent classés en indésirables ou refusés. Pour un envoi fiable :</p>
        <ol class="muted small steps-list">
          <li>cPanel › <em>Comptes de messagerie</em> : créez <span class="mono">info@elitetap.ca</span>.</li>
          <li>Dans <span class="mono">app/config.php</span>, section <span class="mono">mail</span>, ajoutez :
            <pre class="code">'smtp' => [
  'host' => 'mail.elitetap.ca',
  'port' => 465,
  'secure' => 'ssl',
  'user' => 'info@elitetap.ca',
  'pass' => 'MOT_DE_PASSE',
],</pre></li>
          <li>cPanel › <em>Délivrabilité des courriels</em> : vérifiez que SPF et DKIM sont « valides ».</li>
          <li>Revenez ici et envoyez un test.</li>
        </ol>
      <?php endif; ?>
      <p class="muted small">Notifications envoyées à <strong><?= e(notify_email()) ?></strong> (<a href="/admin/reglages">modifier</a>).</p>
    </div>
  </div>
</div>
