/* EliteTap — module de clavardage du site + inscription à l'infolettre (sans dépendance). */
(function () {
  'use strict';
  var root = document.getElementById('et-chat');
  if (!root || !window.fetch || !window.FormData) return;

  var S = JSON.parse(root.getAttribute('data-i18n') || '{}');
  var csrf = root.getAttribute('data-csrf');
  var online = root.getAttribute('data-online') === '1';
  var KEY = 'et_chat_v1';
  var state = load() || {};          // {token, after, status, email}
  var view = 'start';
  var timer = null;
  var isOpen = false;
  var unread = 0;
  var hasEmail = !!state.email;
  var shown = {};                    // ids de messages affichés

  function load() { try { return JSON.parse(localStorage.getItem(KEY)); } catch (e) { return null; } }
  function save() { try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {} }
  function forget() { state = {}; shown = {}; try { localStorage.removeItem(KEY); } catch (e) {} }

  function el(tag, attrs, children) {
    var n = document.createElement(tag);
    for (var k in (attrs || {})) {
      if (k === 'text') n.textContent = attrs[k];
      else if (k === 'class') n.className = attrs[k];
      else if (k.indexOf('on') === 0) n.addEventListener(k.slice(2), attrs[k]);
      else if (attrs[k] !== false && attrs[k] != null) n.setAttribute(k, attrs[k] === true ? '' : attrs[k]);
    }
    (children || []).forEach(function (c) { if (c) n.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
    return n;
  }
  function icon(path) {
    var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    s.setAttribute('width', '22'); s.setAttribute('height', '22'); s.setAttribute('viewBox', '0 0 24 24');
    s.setAttribute('fill', 'none'); s.setAttribute('aria-hidden', 'true');
    var p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    p.setAttribute('d', path); p.setAttribute('stroke', 'currentColor'); p.setAttribute('stroke-width', '2');
    p.setAttribute('stroke-linecap', 'round'); p.setAttribute('stroke-linejoin', 'round');
    s.appendChild(p); return s;
  }

  function post(url, data) {
    var fd = new FormData();
    fd.append('_csrf', csrf);
    if (state.token) fd.append('token', state.token);
    for (var k in data) fd.append(k, data[k]);
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) {
        return r.json().catch(function () { return { error: S.error }; }).then(function (j) { j._status = r.status; return j; });
      })
      .catch(function () { return { error: S.error, _status: 0 }; });
  }

  /* ---------- Structure ---------- */
  var launcher = el('button', { type: 'button', class: 'et-launcher', 'aria-expanded': 'false', 'aria-controls': 'et-chat-panel', onclick: toggle }, [
    icon('M4 20l1.3-3.9A8 8 0 1 1 8 19z'),
    el('span', { class: 'et-launcher-label', text: S.title }),
    el('span', { class: 'et-dot' + (online ? ' is-on' : ''), 'aria-hidden': 'true' })
  ]);
  launcher.setAttribute('aria-label', S.open);
  var badge = el('span', { class: 'et-badge', hidden: true, 'aria-hidden': 'true' });
  launcher.appendChild(badge);

  var statusLine = el('span', { class: 'et-status', text: online ? S.online : S.away });
  var head = el('div', { class: 'et-head' }, [
    el('div', {}, [el('strong', { text: S.title }), el('span', { class: 'et-status-row' }, [el('span', { class: 'et-dot' + (online ? ' is-on' : ''), 'aria-hidden': 'true' }), statusLine])]),
    el('button', { type: 'button', class: 'et-icon-btn', 'aria-label': S.minimize, onclick: toggle }, [icon('M6 9l6 6 6-6')])
  ]);
  var body = el('div', { class: 'et-body' });
  var panel = el('div', { id: 'et-chat-panel', class: 'et-panel', role: 'dialog', 'aria-label': S.title, hidden: true }, [head, body]);
  root.appendChild(panel);
  root.appendChild(launcher);

  function toggle() {
    isOpen = !isOpen;
    panel.hidden = !isOpen;
    launcher.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    root.classList.toggle('is-open', isOpen);
    if (isOpen) {
      unread = 0; updateBadge();
      var f = panel.querySelector('textarea, input:not([type=hidden]):not(.hp)');
      if (f) setTimeout(function () { f.focus(); }, 50);
      var feed = panel.querySelector('.et-feed'); if (feed) feed.scrollTop = feed.scrollHeight;
    }
    schedule();
  }
  function updateBadge() { badge.hidden = !unread; badge.textContent = unread; }

  function field(label, input) { return el('label', { class: 'et-field' }, [el('span', { text: label }), input]); }
  function errorBox() { return el('p', { class: 'et-error', role: 'alert', hidden: true }); }
  function showError(box, msg) { box.textContent = msg || S.error; box.hidden = false; }

  /* ---------- Vues ---------- */
  function render(v) {
    view = v;
    body.textContent = '';
    if (v === 'start') renderStart();
    else if (v === 'chat') renderChat();
    else if (v === 'ended') renderEnded();
    else if (v === 'sent') renderSent();
    else if (v === 'news') renderNews(true);
    schedule();
  }

  function renderStart() {
    var err = errorBox();
    var name = el('input', { type: 'text', name: 'name', maxlength: '80', autocomplete: 'name' });
    var email = el('input', { type: 'email', name: 'email', autocomplete: 'email', required: !online });
    var msg = el('textarea', { name: 'message', rows: '3', maxlength: '2000', required: true });
    var hp = el('input', { type: 'text', name: 'website', class: 'hp', tabindex: '-1', autocomplete: 'off', 'aria-hidden': 'true' });
    var btn = el('button', { type: 'submit', class: 'et-btn', text: online ? S.start : S.sendAway });
    var form = el('form', { class: 'et-form', onsubmit: function (e) {
      e.preventDefault();
      if (!msg.value.trim()) { msg.focus(); return; }
      btn.disabled = true;
      post('/chat/demarrer', { name: name.value, email: email.value, message: msg.value, website: hp.value, page: location.pathname, mode: online ? 'chat' : 'offline' })
        .then(function (r) {
          btn.disabled = false;
          if (r.error) return showError(err, r.error);
          if (r.mode === 'offline') { state = { email: email.value }; hasEmail = true; return render('sent'); }
          state = { token: r.token, after: 0, status: r.status, email: !!email.value }; hasEmail = !!email.value; save();
          render('chat'); apply(r);
        });
    } }, [
      el('p', { class: 'et-intro', text: online ? S.intro : S.introAway }),
      field(S.name, name), field(online ? S.emailOpt : S.email, email), field(S.message, msg), hp, err, btn,
      el('button', { type: 'button', class: 'et-link', text: S.newsLink, onclick: function () { render('news'); } }),
      privacy()
    ]);
    body.appendChild(form);
  }

  function privacy() {
    return el('p', { class: 'et-privacy' }, [S.privacy + ' ', el('a', { href: '/confidentialite', text: S.privacyLink })]);
  }

  var feed, composer, emailRow;
  function renderChat() {
    feed = el('ol', { class: 'et-feed', 'aria-live': 'polite' });
    shown = {};
    var ta = el('textarea', { rows: '2', maxlength: '2000', placeholder: S.placeholder, 'aria-label': S.message });
    ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); composer.requestSubmit ? composer.requestSubmit() : sendNow(); } });
    var err = errorBox();
    function sendNow() {
      var text = ta.value.trim(); if (!text) return;
      ta.value = ''; ta.focus();
      post('/chat/envoyer', { body: text, after: state.after || 0 }).then(function (r) {
        if (r.error && r._status !== 409) { ta.value = text; return showError(err, r.error); }
        err.hidden = true; apply(r);
      });
    }
    composer = el('form', { class: 'et-compose', onsubmit: function (e) { e.preventDefault(); sendNow(); } }, [
      ta, el('button', { type: 'submit', class: 'et-btn et-send', 'aria-label': S.send }, [icon('M5 12h14M13 6l6 6-6 6')])
    ]);
    // Courriel demandé si l'agent tarde (le serveur ajoute un message système)
    var em = el('input', { type: 'email', placeholder: S.email, 'aria-label': S.email, autocomplete: 'email' });
    var emErr = errorBox();
    emailRow = el('form', { class: 'et-email-row', hidden: true, onsubmit: function (e) {
      e.preventDefault();
      post('/chat/courriel', { email: em.value, after: state.after || 0 }).then(function (r) {
        if (r.error) return showError(emErr, r.error);
        hasEmail = state.email = true; save();
        emailRow.textContent = ''; emailRow.appendChild(el('p', { class: 'et-ok', text: S.emailSaved }));
        apply(r);
      });
    } }, [em, el('button', { type: 'submit', class: 'et-btn et-btn-sm', text: S.saveEmail }), emErr]);
    var transcript = el('input', { type: 'checkbox', checked: true });
    var closeBar = el('div', { class: 'et-close-bar' }, [
      el('label', { class: 'et-check', hidden: !hasEmail }, [transcript, el('span', { text: S.transcript })]),
      el('button', { type: 'button', class: 'et-link', text: S.close, onclick: function () {
        post('/chat/fermer', { transcript: transcript.checked && hasEmail ? '1' : '0', after: state.after || 0 }).then(function (r) { apply(r); });
      } })
    ]);
    body.appendChild(feed); body.appendChild(emailRow); body.appendChild(err); body.appendChild(composer); body.appendChild(closeBar);
  }

  function addMessage(m) {
    if (shown[m.id] || !feed) return;
    shown[m.id] = true;
    var li = el('li', { class: 'et-msg from-' + m.from });
    if (m.from === 'agent') li.appendChild(el('span', { class: 'et-who', text: m.name + ' · ' + m.time }));
    li.appendChild(el('span', { class: 'et-text', text: m.body }));
    feed.appendChild(li);
    feed.scrollTop = feed.scrollHeight;
    if (m.from === 'agent' && !isOpen) { unread++; updateBadge(); }
  }

  /** Applique l'état renvoyé par le serveur. */
  function apply(r) {
    if (!r || r.error === 'unknown') { forget(); return render('start'); }
    if (typeof r.online === 'boolean' && r.online !== online) {
      online = r.online; statusLine.textContent = online ? S.online : S.away;
      root.querySelectorAll('.et-dot').forEach(function (d) { d.classList.toggle('is-on', online); });
    }
    (r.messages || []).forEach(function (m) {
      addMessage(m);
      if (m.id > (state.after || 0)) state.after = m.id;
      if (m.from === 'system' && !hasEmail && emailRow) emailRow.hidden = false;
    });
    if (r.email) { hasEmail = state.email = true; }
    state.status = r.status; save();
    if (r.status === 'closed' && view === 'chat') { state.rated = r.rated; save(); setTimeout(function () { render('ended'); }, 900); }
    schedule();
  }

  function renderEnded() {
    var rate = el('div', { class: 'et-rate' });
    if (!state.rated) {
      rate.appendChild(el('p', { text: S.helpful }));
      ['1', '0'].forEach(function (v) {
        rate.appendChild(el('button', { type: 'button', class: 'et-btn et-btn-light et-btn-sm', text: v === '1' ? S.yes : S.no, onclick: function () {
          post('/chat/avis', { rating: v }); state.rated = true; save();
          rate.textContent = ''; rate.appendChild(el('p', { class: 'et-ok', text: S.thanksRate }));
        } }));
      });
    }
    body.appendChild(el('p', { class: 'et-ended', text: S.ended }));
    body.appendChild(rate);
    renderNews(false, 'after_chat');
    body.appendChild(el('button', { type: 'button', class: 'et-btn et-btn-light', text: S.newChat, onclick: function () { forget(); render('start'); } }));
  }

  function renderSent() {
    body.appendChild(el('p', { class: 'et-ok et-big', text: S.sentAway }));
    renderNews(false, 'chat');
    body.appendChild(el('button', { type: 'button', class: 'et-btn et-btn-light', text: S.newChat, onclick: function () { forget(); render('start'); } }));
  }

  function renderNews(standalone, source) {
    var err = errorBox();
    var email = el('input', { type: 'email', required: true, autocomplete: 'email', 'aria-label': S.email, placeholder: S.email });
    var consent = el('input', { type: 'checkbox', required: true });
    var hp = el('input', { type: 'text', class: 'hp', tabindex: '-1', autocomplete: 'off', 'aria-hidden': 'true' });
    var box = el('form', { class: 'et-news', onsubmit: function (e) {
      e.preventDefault();
      post('/infolettre', { email: email.value, consent: consent.checked ? '1' : '', source: source || 'chat', website: hp.value }).then(function (r) {
        if (r.error) return showError(err, r.error);
        box.textContent = ''; box.appendChild(el('p', { class: 'et-ok', text: r.message || S.newsOk }));
      });
    } }, [
      el('strong', { text: S.newsTitle }), el('p', { text: S.newsText }),
      email, el('label', { class: 'et-check' }, [consent, el('span', { text: S.newsConsent })]), hp, err,
      el('button', { type: 'submit', class: 'et-btn', text: S.subscribe })
    ]);
    if (standalone) body.appendChild(el('button', { type: 'button', class: 'et-link et-back', text: '← ' + S.title, onclick: function () { render(state.token ? 'chat' : 'start'); } }));
    body.appendChild(box);
  }

  /* ---------- Suivi ---------- */
  function schedule() {
    clearTimeout(timer);
    if (!state.token || state.status === 'closed') return;
    var delay = document.hidden ? 15000 : (isOpen ? 3000 : 10000);
    timer = setTimeout(function () {
      post('/chat/suivre', { after: state.after || 0 }).then(function (r) {
        if (r._status === 404) { forget(); if (view === 'chat') render('start'); return; }
        if (!r.error) apply(r); else schedule();
      });
    }, delay);
  }
  document.addEventListener('visibilitychange', schedule);

  /* ---------- Démarrage : reprendre une discussion en cours ---------- */
  if (state.token) {
    render('chat');
    post('/chat/suivre', { after: 0 }).then(function (r) {
      if (r._status === 404 || r.error === 'unknown') { forget(); return render('start'); }
      apply(r);
    });
  } else {
    render('start');
  }

  /* ---------- Formulaire d'infolettre du pied de page ---------- */
  document.querySelectorAll('form[data-news]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var out = f.querySelector('[data-news-msg]');
      var fd = new FormData(f);
      fetch(f.action, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          out.hidden = false; out.textContent = j.error || j.message;
          out.className = 'news-msg ' + (j.error ? 'is-error' : 'is-ok');
          if (!j.error) f.reset();
        })
        .catch(function () { f.submit(); });
    });
  });
})();
