<p class="crumb"><a href="/admin/messages">Messages</a> › Nouveau message</p>
<div class="page-head"><h1>Nouveau message</h1></div>
<form method="post" action="/admin/messages/nouveau" class="panel form narrow-panel">
  <?= csrf_field() ?>
  <div class="grid-2">
    <label>Courriel du destinataire<input type="email" name="to" required value="<?= e(old('to', $to)) ?>"></label>
    <label>Nom (facultatif)<input type="text" name="name" value="<?= e(old('name', $name)) ?>"></label>
  </div>
  <label>Sujet<input type="text" name="subject" required maxlength="200" value="<?= e(old('subject')) ?>"></label>
  <label>Message<textarea name="body" rows="10" required><?= e(old('body')) ?></textarea></label>
  <label class="check"><input type="checkbox" name="signature" value="1" checked> Ajouter la signature</label>
  <pre class="signature-preview"><?= e($signature) ?></pre>
  <div><button class="btn btn-primary" type="submit">Envoyer</button></div>
</form>
