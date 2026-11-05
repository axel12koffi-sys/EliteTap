/* EliteTap admin — pages en direct : clavardage du service client et discussion d'équipe (suivi toutes les 3 s). */
(function () {
  'use strict';
  var root = document.querySelector('[data-live]');
  if (!root || !window.fetch) return;

  var mode = root.getAttribute('data-live');          // 'agent' | 'team'
  var csrf = root.getAttribute('data-csrf');
  var after = parseInt(root.getAttribute('data-after'), 10) || 0;
  var feed = root.querySelector('[data-feed]');
  var compose = root.querySelector('[data-compose]');
  var baseTitle = document.title;
  var lastWaiting = null;
  var timer = null;
  var audio = null;

  function el(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; }

  function post(url, data) {
    var fd = new FormData();
    fd.append('_csrf', csrf);
    for (var k in data) fd.append(k, data[k]);
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); });
  }

  function addMsg(m) {
    if (!feed || feed.querySelector('[data-id="' + m.id + '"]')) return;
    var empty = feed.querySelector('[data-empty]'); if (empty) empty.remove();
    var li = el('li', 'live-msg');
    li.setAttribute('data-id', m.id);
    var mine = mode === 'team' ? m.mine : m.from === 'agent';
    if (mine) li.classList.add('is-mine');
    if (mode === 'agent') li.classList.add('from-' + m.from);
    if (m.from !== 'system') {
      var meta = el('span', 'live-meta');
      meta.appendChild(el('strong', null, mode === 'team' ? (m.mine ? 'Vous' : m.name) : (m.from === 'agent' ? (m.name || 'EliteTap') : (root.getAttribute('data-visitor') || 'Visiteur'))));
      meta.appendChild(document.createTextNode(' '));
      meta.appendChild(el('span', 'muted small', m.time));
      li.appendChild(meta);
    }
    li.appendChild(el('span', 'live-body', m.body));
    var nearBottom = feed.scrollHeight - feed.scrollTop - feed.clientHeight < 120;
    feed.appendChild(li);
    if (nearBottom || mine) feed.scrollTop = feed.scrollHeight;
    if (m.id > after) after = m.id;
  }

  function setCount(item, n) {
    var c = item && item.querySelector('[data-count]');
    if (!c) return;
    c.textContent = n; c.hidden = !n;
  }

  /* ---------- Discussion d'équipe ---------- */
  function applyTeam(r) {
    (r.messages || []).forEach(addMsg);
    var channel = root.getAttribute('data-channel');
    root.querySelectorAll('.live-list a[data-key]').forEach(function (a) {
      var k = a.getAttribute('data-key');
      setCount(a, k === channel ? 0 : (r.unread && r.unread[k]) || 0);
    });
    root.querySelectorAll('[data-member]').forEach(function (d) {
      d.classList.toggle('is-on', !!(r.online && r.online[d.getAttribute('data-member')]));
    });
    var total = 0; for (var k in (r.unread || {})) if (k !== channel) total += r.unread[k];
    document.title = (total ? '(' + total + ') ' : '') + baseTitle;
  }

  /* ---------- Clavardage du service client ---------- */
  var statusLabel = { waiting: 'En attente', active: 'En cours', closed: 'Terminée' };
  function renderSessions(list) {
    var nav = root.querySelector('[data-sessions]');
    if (!nav) return;
    var current = root.getAttribute('data-session');
    nav.querySelectorAll('a[data-key], [data-none]').forEach(function (n) { n.remove(); });
    if (!list.length) { var p = el('p', 'muted small', 'Aucune discussion pour l\'instant.'); p.setAttribute('data-none', ''); p.style.padding = '0 12px'; nav.appendChild(p); }
    list.forEach(function (s) {
      var a = el('a', 'chat-item is-' + s.status);
      a.href = '/admin/chat?s=' + s.id; a.setAttribute('data-key', s.id);
      if (String(s.id) === current) a.setAttribute('aria-current', 'page');
      var top = el('span', 'chat-item-top'); top.appendChild(el('strong', null, s.label)); top.appendChild(el('span', 'muted small', s.time));
      var tags = el('span', 'chat-item-tags'); tags.appendChild(el('span', 'chat-status', statusLabel[s.status]));
      if (s.agent) tags.appendChild(el('span', 'muted small', s.agent));
      var c = el('span', 'nav-count', String(s.unread)); c.setAttribute('data-count', ''); c.hidden = !s.unread || String(s.id) === current; tags.appendChild(c);
      a.appendChild(top); a.appendChild(el('span', 'chat-item-last muted small', s.last)); a.appendChild(tags);
      nav.appendChild(a);
    });
  }

  function beep() {
    try {
      audio = audio || new (window.AudioContext || window.webkitAudioContext)();
      var o = audio.createOscillator(), g = audio.createGain();
      o.frequency.value = 880; g.gain.value = 0.05;
      o.connect(g); g.connect(audio.destination); o.start(); o.stop(audio.currentTime + 0.18);
    } catch (e) {}
  }

  function applyAgent(r) {
    renderSessions(r.sessions || []);
    if (lastWaiting !== null && r.waiting > lastWaiting) beep();
    lastWaiting = r.waiting;
    var attention = (r.sessions || []).filter(function (s) { return s.unread && String(s.id) !== root.getAttribute('data-session'); }).length;
    document.title = (r.waiting || attention ? '(' + (r.waiting || attention) + ') ' : '') + baseTitle;
    if (r.current) {
      (r.current.messages || []).forEach(addMsg);
      var away = root.querySelector('[data-visitor-away]');
      if (away) away.hidden = !r.current.visitorAway;
      if (r.current.status === 'closed' && compose) { location.reload(); }
    }
  }

  function poll() {
    clearTimeout(timer);
    var req = mode === 'team'
      ? post(root.getAttribute('data-poll'), { c: root.getAttribute('data-channel'), after: after }).then(applyTeam)
      : post(root.getAttribute('data-poll'), { s: root.getAttribute('data-session'), after: after }).then(applyAgent);
    req.catch(function () {}).then(function () { timer = setTimeout(poll, document.hidden ? 10000 : 3000); });
  }

  /* ---------- Envoi ---------- */
  if (compose) {
    var ta = compose.querySelector('textarea');
    ta.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); compose.requestSubmit ? compose.requestSubmit() : compose.dispatchEvent(new Event('submit', { cancelable: true })); }
    });
    compose.addEventListener('submit', function (e) {
      e.preventDefault();
      var text = ta.value.trim();
      if (!text) return;
      ta.value = '';
      var data = { body: text };
      data.after = after;
      if (mode === 'team') data.c = root.getAttribute('data-channel'); else data.s = root.getAttribute('data-session');
      var err = compose.querySelector('.live-error');
      if (err) err.remove();
      post(compose.action, data).then(mode === 'team' ? applyTeam : applyAgent).catch(function () {
        ta.value = text;
        var p = el('p', 'live-error', 'Le message n\'est pas parti. Réessayez.'); p.setAttribute('role', 'alert');
        compose.appendChild(p);
      });
      ta.focus();
    });
  }

  if (feed) feed.scrollTop = feed.scrollHeight;
  document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
  timer = setTimeout(poll, 3000);
})();
