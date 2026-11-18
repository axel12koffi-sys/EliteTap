// Menu mobile
(function () {
  var btn = document.querySelector('.nav-toggle');
  var menu = document.getElementById('nav-menu');
  if (btn && menu) {
    btn.addEventListener('click', function () {
      var open = menu.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    menu.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') { menu.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); }
    });
  }

  // Les boutons des forfaits présélectionnent le type de carte
  var select = document.getElementById('card_type');
  document.querySelectorAll('[data-card]').forEach(function (a) {
    a.addEventListener('click', function () { if (select) select.value = a.getAttribute('data-card'); });
  });
})();

// Agrandissement des photos de réalisations
(function () {
  var dlg = document.getElementById('lightbox');
  if (!dlg || typeof dlg.showModal !== 'function') return;
  var img = dlg.querySelector('img'), cap = dlg.querySelector('p');
  document.querySelectorAll('[data-lightbox]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      img.src = a.getAttribute('href');
      img.alt = a.querySelector('img').alt;
      cap.textContent = a.getAttribute('data-caption') || '';
      dlg.showModal();
    });
  });
  dlg.querySelector('.lightbox-close').addEventListener('click', function () { dlg.close(); });
  dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
})();

// Vidéo de la bannière : chargée seulement si les animations sont permises et la connexion n'est pas en mode économie
(function () {
  var v = document.querySelector('.hero-video[data-src]');
  if (!v) return;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var save = navigator.connection && navigator.connection.saveData;
  if (reduce || save) return;                      // l'image d'attente (poster) reste affichée
  v.src = v.getAttribute('data-src');
  v.removeAttribute('data-src');
  var play = function () { var p = v.play(); if (p && p.catch) p.catch(function () {}); };
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting) play(); else v.pause(); });
    }, { threshold: 0.2 }).observe(v);
  } else { play(); }
})();
