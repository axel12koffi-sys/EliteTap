<?php
[$days, $from, $to] = $hours;
$mode = setting('presence_mode', 'auto');
$labels = ['autoreply_online_fr' => 'En ligne — français', 'autoreply_away_fr' => 'Absent — français',
           'autoreply_online_en' => 'En ligne — anglais', 'autoreply_away_en' => 'Absent — anglais'];
?>
<p class="crumb"><a href="/admin/messages">Messages</a> › Présence et réponses automatiques</p>
<div class="page-head">
  <div>
    <h1>Présence et réponses automatiques</h1>
    <p class="muted">Statut actuel : <strong><?= $presence['online'] ? 'En ligne' : 'Absent' ?></strong> (<?= e($presence['reason']) ?>).</p>
  </div>
</div>

<form method="post" action="/admin/messages/reglages" class="form settings-form">
  <?= csrf_field() ?>
  <div class="panel form">
    <h2 class="h2">Présence</h2>
    <fieldset class="fieldset">
      <legend class="visually-hidden">Mode de présence</legend>
      <label class="check"><input type="radio" name="presence_mode" value="auto"<?= $mode === 'auto' ? ' checked' : '' ?>> <span><strong>Automatique</strong> — en ligne pendant les heures d'ouverture, ou quand un admin utilise l'administration (activité de moins de <?= PRESENCE_ACTIVE_MINUTES ?> min)</span></label>
      <label class="check"><input type="radio" name="presence_mode" value="online"<?= $mode === 'online' ? ' checked' : '' ?>> <span><strong>Toujours en ligne</strong></span></label>
      <label class="check"><input type="radio" name="presence_mode" value="away"<?= $mode === 'away' ? ' checked' : '' ?>> <span><strong>Absent</strong> jusqu'à ce que vous changiez le statut</span></label>
    </fieldset>
    <label style="max-width: 320px;">Absent jusqu'au (vacances, facultatif)<input type="date" name="away_until" value="<?= e(setting('away_until', '')) ?>">
      <span class="hint">Pendant cette période, le message « absent » est envoyé quel que soit le mode, avec la date de retour.</span></label>
  </div>

  <div class="panel form">
    <h2 class="h2">Heures d'ouverture</h2>
    <div class="day-picks">
      <?php foreach (WEEKDAYS_FR as $n => $d): ?>
        <label class="check"><input type="checkbox" name="hours_days[]" value="<?= $n ?>"<?= in_array($n, $days, true) ? ' checked' : '' ?>> <?= e(ucfirst($d)) ?></label>
      <?php endforeach; ?>
    </div>
    <div class="grid-2" style="max-width: 420px;">
      <label>Ouverture<input type="time" name="hours_from" value="<?= e($from) ?>" required></label>
      <label>Fermeture<input type="time" name="hours_to" value="<?= e($to) ?>" required></label>
    </div>
    <p class="hint">Fuseau horaire : heure de l'Est (Montréal). Affiché aux clients : « <?= e(hours_label()) ?> ».</p>
  </div>

  <div class="panel form">
    <h2 class="h2">Réponses automatiques</h2>
    <label class="check"><input type="checkbox" name="autoreply_enabled" value="1"<?= setting('autoreply_enabled', '1') === '1' ? ' checked' : '' ?>> Répondre automatiquement aux courriels reçus</label>
    <p class="muted small">Une seule réponse automatique par expéditeur et par 24 h, jamais aux robots, listes de diffusion ni retours d'erreur, et pas pendant un échange en cours (vous avez écrit dans la conversation il y a moins de 2 h). La langue suit celle du client (commande ou compte), sinon celle de son message.
      Variables : <span class="mono">{nom}</span> prénom, <span class="mono">{retour}</span> moment du retour (ex. « lundi à 9 h »), <span class="mono">{heures}</span> heures d'ouverture, <span class="mono">{whatsapp}</span> lien WhatsApp.</p>
    <div class="grid-2">
      <?php foreach ($labels as $k => $label): ?>
        <label><?= e($label) ?><textarea name="<?= $k ?>" rows="9"><?= e($tpl[$k]) ?></textarea></label>
      <?php endforeach; ?>
    </div>
    <p class="muted small">Les courriels de confirmation de commande indiquent aussi la présence : « Notre équipe est en ligne… » ou « … nous traiterons votre demande lundi à 9 h ».</p>
  </div>

  <div class="panel form">
    <h2 class="h2">Signature et notifications</h2>
    <label>Signature de vos réponses<textarea name="mail_signature" rows="3"><?= e($signature) ?></textarea></label>
    <label class="check"><input type="checkbox" name="inbox_notify" value="1"<?= setting('inbox_notify', '1') === '1' ? ' checked' : '' ?>> M'avertir de chaque nouveau message à <?= e(notify_email()) ?> (si ce n'est pas info@)</label>
  </div>

  <div><button class="btn btn-primary" type="submit">Enregistrer</button></div>
</form>
