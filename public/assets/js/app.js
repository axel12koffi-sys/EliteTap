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
  bind('cover_color', function (v) { $('[data-pv=cover]').style.background = v; });
  bind('accent_color', function (v) {
    document.querySelectorAll('[data-pv=accent]').forEach(function (el) { el.style.background = v; el.style.color = onColor(v); });
  });
  bind('slug', function (v) { var s = $('[data-pv=slug]'); if (s) s.textContent = v || '…'; });

  function onColor(hex) {
    var c = hex.replace('#', ''); if (c.length !== 6) return '#fff';
    var rgb = [0, 2, 4].map(function (i) { var x = parseInt(c.substr(i, 2), 16) / 255; return x <= 0.03928 ? x / 12.92 : Math.pow((x + 0.055) / 1.055, 2.4); });
    return (0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2]) > 0.4 ? '#14161A' : '#FFFFFF';
  }
})();

// Envoi automatique de la photo dès qu'elle est choisie
(function () {
  var input = document.getElementById('avatar-input');
  if (input) input.addEventListener('change', function () { if (input.files.length) input.form.submit(); });
})();

// Confirmation des actions destructives
document.addEventListener('submit', function (e) {
  var msg = e.target.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) e.preventDefault();
});
