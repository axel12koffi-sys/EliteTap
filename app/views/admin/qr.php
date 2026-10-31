<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>QR des cartes | Admin EliteTap</title>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<style>
  body { background: #FFF; }
  .qr-page { max-width: 1100px; margin: 0 auto; padding: 32px 24px; }
  .qr-bar { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; margin-bottom: 24px; }
  .qr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }
  .qr-item { border: 1px solid #E4E2DD; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; align-items: center; gap: 8px; break-inside: avoid; }
  .qr-item [data-qr] { width: 140px; height: 140px; }
  .qr-item [data-qr] svg { width: 100%; height: 100%; display: block; }
  .qr-code { font-family: ui-monospace, monospace; font-weight: 600; font-size: 15px; }
  .qr-url { font-size: 11px; color: #5B606B; word-break: break-all; text-align: center; }
  @media print {
    .qr-bar, .no-print { display: none !important; }
    .qr-page { padding: 0; max-width: none; }
    .qr-grid { grid-template-columns: repeat(4, 1fr); gap: 8mm; }
    .qr-item { border-color: #CCC; }
  }
</style>
</head>
<body>
<main class="qr-page">
  <div class="qr-bar">
    <div>
      <p class="crumb"><a href="/admin/cartes">Cartes NFC</a> /</p>
      <h1 style="font-size: 26px; margin: 0;">QR codes · <?= count($cards) ?> carte(s)</h1>
      <p class="muted">Chaque QR ouvre la même URL que la puce NFC. Format SVG vectoriel, prêt pour l'imprimeur.</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="window.print()">Imprimer / PDF</button>
  </div>
  <?php if (!$cards): ?>
    <p class="muted">Aucune carte dans cette sélection.</p>
  <?php endif; ?>
  <div class="qr-grid">
    <?php foreach ($cards as $c): $u = url('/t/' . $c['code']); ?>
      <div class="qr-item">
        <div data-qr="<?= e($u) ?>"></div>
        <span class="qr-code"><?= e($c['code']) ?></span>
        <span class="qr-url"><?= e($u) ?></span>
        <button class="btn btn-light btn-sm no-print" type="button" data-qr-download="<?= e($u) ?>" data-filename="qr-<?= e($c['code']) ?>.svg">SVG</button>
      </div>
    <?php endforeach; ?>
  </div>
</main>
<script src="<?= asset('js/vendor/qrcode.js') ?>"></script>
<script src="<?= asset('js/qr.js') ?>"></script>
</body>
</html>
