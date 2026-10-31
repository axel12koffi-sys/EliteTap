// Aperçu en direct du profil pendant l'édition
(function () {
  var form = document.getElementById('profile-form');
  if (!form) return;
  var bind = function (name, fn) {
    var el = form.elements[name];
    if (!el) return;
    var run = function () { fn(el.value, el); };
    el.addEventListener('input', run);
    run();
  };
  var $ = function (sel) { return document.querySelector(sel); };
  var name = function () {
    var n = (form.elements.first_name.value + ' ' + form.elements.last_name.value).trim();
    return n || form.elements.company.value || document.querySelector('[data-pv=name]').getAttribute('data-empty');
  };
  var sub = function () {
    return [form.elements.title.value, form.elements.company.value].filter(Boolean).join(' · ');
  };
  var initials = function () {
    var f = form.elements.first_name.value.trim().charAt(0), l = form.elements.last_name.value.trim().charAt(0);
    return ((f + l) || 'ET').toUpperCase();
  };
  var refresh = function () {
    $('[data-pv=name]').textContent = name();
    $('[data-pv=sub]').textContent = sub();
    var ini = $('[data-pv=initials]');
    if (ini) ini.textContent = initials();
  };
  ['first_name', 'last_name', 'title', 'company'].forEach(function (n) { bind(n, refresh); });
  bind('bio', function (v) { var b = $('[data-pv=bio]'); b.textContent = v; b.hidden = !v; });
  // Couleurs : thème choisi, ou couleurs de marque (« Mes couleurs », Pro)
  var themes = JSON.parse(form.getAttribute('data-themes') || '{}');
  var readability = $('.readability');
  var paint = function () {
    var picked = form.querySelector('input[name=theme]:checked');
    var key = picked ? picked.value : 'marine';
    var cover, accent;
    if (key === 'custom' && form.elements.cover_color) {
      cover = form.elements.cover_color.value; accent = form.elements.accent_color.value;
    } else {
      var t = themes[key] || themes.marine; cover = t.cover; accent = t.accent;
    }
    $('[data-pv=cover]').style.background = cover;
    document.querySelectorAll('[data-pv=accent]').forEach(function (el) { el.style.background = accent; el.style.color = onColor(accent); });
    if (readability && form.elements.accent_color) {
      var a = form.elements.accent_color.value, ok = contrast(a, onColor(a)) >= 4.5;
      readability.textContent = ok ? '✓ ' + readability.getAttribute('data-ok') : readability.getAttribute('data-bad');
      readability.className = 'readability ' + (ok ? 'ok' : 'bad');
    }
  };
  form.querySelectorAll('input[name=theme]').forEach(function (r) { r.addEventListener('change', paint); });
  ['cover_color', 'accent_color'].forEach(function (n) {
    var el = form.elements[n]; if (!el) return;
    el.addEventListener('input', function () {
      var custom = form.querySelector('input[name=theme][value=custom]');
      if (custom) custom.checked = true;
      paint();
    });
  });
  paint();
  var font = form.elements.font;
  if (font) font.addEventListener('change', function () {
    $('[data-pv=name]').style.fontFamily = font.options[font.selectedIndex].getAttribute('data-stack');
  });
  bind('slug', function (v) { var s = $('[data-pv=slug]'); if (s) s.textContent = v || '…'; });

  function lum(hex) {
    var c = hex.replace('#', '');
    var rgb = [0, 2, 4].map(function (i) { var x = parseInt(c.substr(i, 2), 16) / 255; return x <= 0.03928 ? x / 12.92 : Math.pow((x + 0.055) / 1.055, 2.4); });
    return 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2];
  }
  function contrast(a, b) { var x = lum(a), y = lum(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); }
  function onColor(hex) { return contrast(hex, '#FFFFFF') >= contrast(hex, '#14161A') ? '#FFFFFF' : '#14161A'; }
})();

// Envoi automatique de la photo dès qu'elle est choisie
(function () {
  document.querySelectorAll('#avatar-input, .auto-submit').forEach(function (input) {
    input.addEventListener('change', function () { if (input.files.length) input.form.submit(); });
  });
})();

// Confirmation des actions destructives
document.addEventListener('submit', function (e) {
  var msg = e.target.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) e.preventDefault();
});
