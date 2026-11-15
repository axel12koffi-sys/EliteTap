/* Admin › Profil sur mesure : taille de l'aperçu, attente de l'IA, contrôle de lisibilité dans l'aperçu. */
(function () {
  'use strict';
  var frame = document.querySelector('[data-preview]');
  var wrap = document.querySelector('[data-frame-wrap]');

  document.querySelectorAll('[data-device]').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('[data-device]').forEach(function (x) { x.classList.remove('is-on'); x.setAttribute('aria-pressed', 'false'); });
      b.classList.add('is-on'); b.setAttribute('aria-pressed', 'true');
      if (!frame) return;
      var w = parseInt(b.getAttribute('data-device'), 10);
      frame.width = w;
      wrap.classList.toggle('is-desktop', w > 600);
      fit();
    });
  });
  // Ordinateur : on réduit le cadre pour qu'il tienne dans le panneau
  function fit() {
    if (!frame || !wrap) return;
    var w = parseInt(frame.width, 10), avail = wrap.clientWidth;
    var s = w > avail ? avail / w : 1;
    frame.style.transform = s < 1 ? 'scale(' + s + ')' : '';
    frame.style.transformOrigin = 'top left';
    wrap.style.height = s < 1 ? (frame.height * s) + 'px' : '';
  }
  window.addEventListener('resize', fit);

  var aiForm = document.querySelector('[data-ai-form]');
  if (aiForm) aiForm.addEventListener('submit', function () {
    var btn = aiForm.querySelector('[data-ai-submit]');
    var wait = aiForm.querySelector('[data-ai-wait]');
    setTimeout(function () { btn.disabled = true; btn.textContent = 'Génération en cours…'; wait.hidden = false; }, 0);
  });

  /* ---------- Contrôle de lisibilité ---------- */
  function rgba(str) {
    var m = str.match(/rgba?\(([^)]+)\)/);
    if (!m) return null;
    var p = m[1].split(/[,\s/]+/).filter(Boolean).map(parseFloat);
    return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 };
  }
  function lum(c) {
    var f = function (v) { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
    return 0.2126 * f(c.r) + 0.7152 * f(c.g) + 0.0722 * f(c.b);
  }
  function ratio(a, b) { var x = lum(a), y = lum(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); }
  function blend(top, under) {
    var a = top.a;
    return { r: top.r * a + under.r * (1 - a), g: top.g * a + under.g * (1 - a), b: top.b * a + under.b * (1 - a), a: 1 };
  }
  function background(el, win) {
    var layers = [];
    for (var n = el; n && n.nodeType === 1; n = n.parentElement) {
      var cs = win.getComputedStyle(n);
      if (cs.backgroundImage && cs.backgroundImage !== 'none') return null;   // image : on ne peut pas juger
      var c = rgba(cs.backgroundColor);
      if (c && c.a > 0) { layers.push(c); if (c.a >= 1) break; }
    }
    var out = { r: 255, g: 255, b: 255, a: 1 };
    for (var i = layers.length - 1; i >= 0; i--) out = blend(layers[i], out);
    return out;
  }
  function check() {
    var list = document.querySelector('[data-readability]');
    if (!frame || !list) return;
    var doc, win;
    try { doc = frame.contentDocument; win = frame.contentWindow; } catch (e) { list.textContent = 'Contrôle de lisibilité indisponible.'; return; }
    var root = doc.querySelector('.et-custom') || doc.body;
    var issues = [], unknown = 0, checked = 0, seen = new Set();
    root.querySelectorAll('*').forEach(function (el) {
      var own = Array.prototype.some.call(el.childNodes, function (t) { return t.nodeType === 3 && t.textContent.trim().length > 1; });
      if (!own) return;
      var cs = win.getComputedStyle(el);
      if (cs.display === 'none' || cs.visibility === 'hidden' || parseFloat(cs.opacity) === 0) return;
      var r = el.getBoundingClientRect();
      if (r.width === 0 || r.height === 0) return;
      checked++;
      var text = el.textContent.trim().replace(/\s+/g, ' ').slice(0, 40);
      var size = parseFloat(cs.fontSize), bold = parseInt(cs.fontWeight, 10) >= 700;
      if (size < 12 && !seen.has('s' + text)) { seen.add('s' + text); issues.push('« ' + text + ' » : texte de ' + Math.round(size) + ' px (12 px minimum, 14 conseillé)'); }
      var fg = rgba(cs.color), bg = background(el, win);
      if (!fg || !bg) { unknown++; return; }
      var c = ratio(blend(fg, bg), bg), min = (size >= 24 || (size >= 18.66 && bold)) ? 3 : 4.5;
      if (c < min && !seen.has('c' + text)) { seen.add('c' + text); issues.push('« ' + text + ' » : contraste ' + c.toFixed(1).replace('.', ',') + ':1 (minimum ' + String(min).replace('.', ',') + ':1)'); }
    });
    root.querySelectorAll('a, button').forEach(function (el) {
      var r = el.getBoundingClientRect();
      if (r.width > 0 && r.height > 0 && r.height < 40 && issues.length < 30) {
        var t = el.textContent.trim().slice(0, 30) || 'lien';
        if (!seen.has('t' + t)) { seen.add('t' + t); issues.push('« ' + t + ' » : zone tactile de ' + Math.round(r.height) + ' px de haut (44 px conseillés)'); }
      }
    });
    var ul = list.parentNode;
    list.remove();
    if (!issues.length) {
      var ok = document.createElement('li'); ok.className = 'is-ok';
      ok.textContent = 'Lisibilité : ' + checked + ' textes vérifiés, aucun problème de contraste ni de taille' + (unknown ? ' (' + unknown + ' sur image, à vérifier à l\'œil)' : '') + '.';
      ul.appendChild(ok);
    } else {
      issues.slice(0, 12).forEach(function (t) { var li = document.createElement('li'); li.className = 'is-warn'; li.textContent = t; ul.appendChild(li); });
      if (issues.length > 12) { var more = document.createElement('li'); more.className = 'muted'; more.textContent = '… et ' + (issues.length - 12) + ' autre(s).'; ul.appendChild(more); }
      if (unknown) { var u = document.createElement('li'); u.className = 'muted'; u.textContent = unknown + ' texte(s) posé(s) sur une image : vérifiez-les à l\'œil.'; ul.appendChild(u); }
    }
  }
  if (frame) {
    frame.addEventListener('load', function () { setTimeout(check, 400); fit(); });
  }
})();
