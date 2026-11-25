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

// Photo de réseautage : la fenêtre s'ouvre en arrivant, reste ouverte, puis se referme en repartant
(function () {
  var scene = document.querySelector('[data-scene]');
  if (!scene) return;
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var st = scene.style, ticking = false;
  var clamp = function (n) { return n < 0 ? 0 : n > 1 ? 1 : n; };
  var ease = function (n) { return n * n * (3 - 2 * n); };
  function update() {
    ticking = false;
    var r = scene.getBoundingClientRect(), vh = window.innerHeight;
    var p = (vh - r.top) / (r.height + vh);            // 0 : la scène arrive, 1 : elle est passée
    var o = ease(clamp(Math.min(p / 0.42, (1 - p) / 0.3)));
    st.setProperty('--sc-y', (22 * (1 - o)).toFixed(2) + '%');
    st.setProperty('--sc-x', (14 * (1 - o)).toFixed(2) + '%');
    st.setProperty('--sc-r', (28 * (1 - o)).toFixed(1) + 'px');
    st.setProperty('--sc-s', (1.12 - 0.12 * o).toFixed(3));
    st.setProperty('--sc-o', o > 0.8 ? '1' : '0');
  }
  var onScroll = function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } };
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  update();
})();

// Bannière : mise à l'échelle de la scène et code QR décoratif
(function () {
  var wrap = document.querySelector('[data-tap]');
  if (!wrap) return;
  var stage = wrap.querySelector('.tap-stage');
  function fit() { stage.style.setProperty('--s', Math.min(1, wrap.clientWidth / 540, wrap.clientHeight / 640)); }
  fit(); window.addEventListener('resize', fit);
  var c = wrap.querySelector('[data-tap-qr]');
  if (c && c.getContext) {
    var g = c.getContext('2d'), N = 25, u = c.width / (N + 4), seed = 7, ink = '#3666AF';
    var rnd = function () { seed = (seed * 9301 + 49297) % 233280; return seed / 233280; };
    g.clearRect(0, 0, c.width, c.height); g.fillStyle = ink;
    for (var y = 0; y < N; y++) for (var x = 0; x < N; x++) {
      var f = (x < 8 && y < 8) || (x > N - 9 && y < 8) || (x < 8 && y > N - 9);
      if (!f && rnd() > 0.52) g.fillRect((x + 2) * u, (y + 2) * u, Math.ceil(u), Math.ceil(u));
    }
    [[0, 0], [N - 7, 0], [0, N - 7]].forEach(function (p) {
      g.fillStyle = ink; g.fillRect((p[0] + 2) * u, (p[1] + 2) * u, 7 * u, 7 * u);
      g.clearRect((p[0] + 3) * u, (p[1] + 3) * u, 5 * u, 5 * u);
      g.fillRect((p[0] + 4) * u, (p[1] + 4) * u, 3 * u, 3 * u);
    });
  }
})();

// Bandeau de témoins : affiché tant que le visiteur n'a pas cliqué « J'ai compris »
(function () {
  var bar = document.querySelector('[data-cookie-bar]');
  if (!bar) return;
  var KEY = 'et_cookies_ok', seen = false;
  try { seen = localStorage.getItem(KEY) === '1'; } catch (e) {}
  if (seen) return;
  bar.hidden = false;
  bar.querySelector('[data-cookie-ok]').addEventListener('click', function () {
    try { localStorage.setItem(KEY, '1'); } catch (e) {}
    bar.hidden = true;
  });
})();


// Vidéo démo : boucle muette, lue seulement quand elle est visible (et pas si l'animation est réduite)
(function () {
  var v = document.querySelector('.demo-loop');
  if (!v) return;
  v.muted = true;
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { v.removeAttribute('autoplay'); v.pause(); return; }
  var play = function () { var p = v.play(); if (p && p.catch) p.catch(function () {}); };
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) play(); else v.pause(); }); }, { threshold: 0.25 }).observe(v);
  } else { play(); }
})();
