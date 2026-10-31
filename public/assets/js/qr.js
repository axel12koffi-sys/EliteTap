// Génère un QR (SVG) dans chaque élément [data-qr] et gère le téléchargement.
// Bibliothèque : qrcode-generator (MIT), chargée avant ce fichier.
(function () {
  function svgFor(text) {
    var qr = qrcode(0, 'M');
    qr.addData(text);
    qr.make();
    return qr.createSvgTag({ cellSize: 8, margin: 4, scalable: true });
  }

  document.querySelectorAll('[data-qr]').forEach(function (el) {
    var svg = svgFor(el.getAttribute('data-qr'));
    el.innerHTML = svg;
    var s = el.querySelector('svg');
    if (s) {
      s.setAttribute('role', 'img');
      s.setAttribute('aria-label', 'Code QR : ' + el.getAttribute('data-qr'));
    }
  });

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-qr-download]');
    if (!btn) return;
    var url = btn.getAttribute('data-qr-download');
    var name = btn.getAttribute('data-filename') || 'qr-elitetap.svg';
    var svg = svgFor(url).replace('<svg ', '<svg xmlns="http://www.w3.org/2000/svg" ');
    var blob = new Blob(['<?xml version="1.0" encoding="UTF-8"?>\n' + svg], { type: 'image/svg+xml' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = name;
    document.body.appendChild(a);
    a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
  });
})();
