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
