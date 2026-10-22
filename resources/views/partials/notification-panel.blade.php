<div class="notif-overlay" id="notifOverlay" aria-hidden="true">
  <div class="notif-backdrop" data-notif-close></div>
  <aside class="notif-panel" id="notifPanel" role="dialog" aria-modal="true" aria-label="Notifikasi" tabindex="-1">
    <div class="notif-panel__header">
      <button type="button" class="icon-btn notif-panel__back" data-notif-close aria-label="Kembali">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      </button>
      <span class="notif-panel__title">Notifikasi</span>
      <button type="button" class="icon-btn notif-panel__x" data-notif-close aria-label="Tutup">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="notif-panel__body" id="notifBody"></div>
  </aside>
</div>

<script>
(function () {
  var overlay = document.getElementById('notifOverlay');
  var panel = document.getElementById('notifPanel');
  var body = document.getElementById('notifBody');
  if (!overlay || !panel || !body) return;

  var URLS = {
    list: '{{ route('notifications.index', [], false) }}',
    count: '{{ route('notifications.count', [], false) }}',
    seen: '{{ route('notifications.seen', [], false) }}',
    read: '{{ route('notifications.read', ['id' => '__ID__'], false) }}',
    login: '{{ route('login', [], false) }}'
  };

  var ICONS = {
    preorder: '<svg viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
    stock: '<svg viewBox="0 0 24 24"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    info: '<svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>'
  };

  var isOpen = false, busy = false, reqId = 0;
  var nextPage = 1, since = null;
  var lastFocus = null, lastCountAt = Date.now();
  var wrapNew = null, wrapOld = null, moreBtn = null;

  function el(tag, cls, text) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text != null) e.textContent = text;
    return e;
  }

  function csrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }
  function setCsrf(t) {
    var m = document.querySelector('meta[name="csrf-token"]');
    if (m && t) m.setAttribute('content', t);
  }

  // Sesi habis / token basi: arahkan ke login dengan pesan yang jelas
  function expired() {
    window.location.href = URLS.login + '?expired=1';
  }

  function api(method, url) {
    return fetch(url, {
      method: method,
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf()
      }
    }).then(function (res) {
      if (res.status === 401 || res.status === 419) { expired(); throw new Error('expired'); }
      if (!res.ok) throw new Error('http ' + res.status);
      return res.json();
    });
  }

  function setBadge(n) {
    n = parseInt(n, 10) || 0;
    document.querySelectorAll('[data-notif-badge]').forEach(function (b) {
      if (n <= 0) { b.hidden = true; b.textContent = ''; }
      else { b.hidden = false; b.textContent = n > 99 ? '99+' : String(n); }
    });
  }

  // Waktu relatif: "5 menit lalu"
  function ago(iso) {
    var t = new Date(iso).getTime();
    if (isNaN(t)) return '';
    var s = Math.max(0, Math.floor((Date.now() - t) / 1000));
    if (s < 60) return 'Baru saja';
    var m = Math.floor(s / 60);
    if (m < 60) return m + ' menit lalu';
    var h = Math.floor(m / 60);
    if (h < 24) return h + ' jam lalu';
    var d = Math.floor(h / 24);
    if (d === 1) return 'Kemarin';
    if (d < 7) return d + ' hari lalu';
    return new Date(t).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  /* ---------- Tampilan daftar ---------- */

  function showMessage(text, retry) {
    body.innerHTML = '';
    var box = el('div', 'notif-empty', text);
    if (retry) {
      var b = el('button', 'btn btn-sm btn-secondary notif-more', 'Coba lagi');
      b.type = 'button';
      b.style.width = 'auto';
      b.addEventListener('click', function () { load(true); });
      box.appendChild(b);
    }
    body.appendChild(box);
  }

  function resetList() {
    body.innerHTML = '';
    wrapNew = el('div');
    wrapNew.hidden = true;
    wrapNew.appendChild(el('div', 'notif-group', 'Baru'));
    wrapOld = el('div');
    wrapOld.hidden = true;
    wrapOld.appendChild(el('div', 'notif-group', 'Sebelumnya'));
    body.appendChild(wrapNew);
    body.appendChild(wrapOld);
    moreBtn = null;
  }

  function itemNode(it) {
    var b = el('button', 'notif-item' + (it.is_read ? '' : ' is-unread'));
    b.type = 'button';

    var k = ICONS[it.kind] ? it.kind : 'info';
    var ic = el('span', 'notif-item__icon notif-item__icon--' + k);
    ic.innerHTML = ICONS[k];
    b.appendChild(ic);

    var main = el('span', 'notif-item__main');
    main.appendChild(el('span', 'notif-item__title', it.title));
    if (it.body) main.appendChild(el('span', 'notif-item__body', it.body));
    main.appendChild(el('span', 'notif-item__time', ago(it.created_at)));
    b.appendChild(main);

    if (!it.is_read) b.appendChild(el('span', 'notif-item__dot'));

    b.addEventListener('click', function () { openItem(it, b); });
    return b;
  }

  function addItems(items) {
    items.forEach(function (it) {
      var wrap = it.is_new ? wrapNew : wrapOld;
      wrap.hidden = false;
      wrap.appendChild(itemNode(it));
    });
  }

  function updateMore(has) {
    if (moreBtn && moreBtn.parentNode) moreBtn.parentNode.removeChild(moreBtn);
    moreBtn = null;
    if (!has) return;
    moreBtn = el('button', 'btn btn-sm btn-secondary notif-more', 'Muat lebih banyak');
    moreBtn.type = 'button';
    moreBtn.style.width = 'auto';
    moreBtn.addEventListener('click', function () { load(false); });
    body.appendChild(moreBtn);
  }

  function openItem(it, node) {
    function go() { if (it.url) window.location.href = it.url; }

    if (it.is_read) { go(); return; }

    api('POST', URLS.read.replace('__ID__', encodeURIComponent(it.id)))
      .then(function () {
        it.is_read = true;
        node.classList.remove('is-unread');
        var dot = node.querySelector('.notif-item__dot');
        if (dot) dot.parentNode.removeChild(dot);
        go();
      })
      .catch(function (err) {
        if (err && err.message === 'expired') return;
        go();
      });
  }

  /* ---------- Muat data ---------- */

  function load(first) {
    if (busy && !first) return;
    busy = true;
    var my = ++reqId;

    var url = URLS.list + '?page=' + nextPage + (since ? '&since=' + encodeURIComponent(since) : '');

    if (first) {
      body.innerHTML = '';
      body.appendChild(el('div', 'notif-loading', 'Memuat notifikasi...'));
    } else if (moreBtn) {
      moreBtn.disabled = true;
      moreBtn.textContent = 'Memuat...';
    }

    api('GET', url).then(function (d) {
      if (my !== reqId) return;
      busy = false;

      if (first) {
        since = d.since;
        if (!d.items.length) {
          showMessage('Belum ada notifikasi.', false);
        } else {
          resetList();
          addItems(d.items);
          updateMore(d.has_more);
        }
        // Daftar sudah tampil: badge kembali 0 dan "terakhir dilihat" disimpan
        setBadge(0);
        api('POST', URLS.seen).catch(function () {});
      } else {
        addItems(d.items);
        updateMore(d.has_more);
      }
      nextPage = d.next_page;
    }).catch(function (err) {
      if (my !== reqId) return;
      busy = false;
      if (err && err.message === 'expired') return;
      if (first) {
        showMessage('Gagal memuat notifikasi.', true);
      } else if (moreBtn) {
        moreBtn.disabled = false;
        moreBtn.textContent = 'Gagal, coba lagi';
      }
    });
  }

  /* ---------- Buka / tutup ---------- */

  function open() {
    if (isOpen) return;
    isOpen = true;
    lastFocus = document.activeElement;
    nextPage = 1;
    since = null;

    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.classList.add('notif-open');
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { overlay.classList.add('is-visible'); });
    });
    panel.focus();
    load(true);
  }

  function close() {
    if (!isOpen) return;
    isOpen = false;
    overlay.classList.remove('is-visible');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('notif-open');
    setTimeout(function () { if (!isOpen) overlay.classList.remove('is-open'); }, 260);
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  document.querySelectorAll('[data-notif-bell]').forEach(function (b) {
    b.addEventListener('click', open);
  });

  overlay.addEventListener('click', function (e) {
    var t = e.target;
    if (t && t.closest && t.closest('[data-notif-close]')) close();
  });

  document.addEventListener('keydown', function (e) {
    if ((e.key === 'Escape' || e.key === 'Esc') && isOpen) close();
  });

  /* ---------- Perbarui badge saat aplikasi kembali aktif (tanpa polling) ---------- */

  function refreshCount() {
    if (isOpen || document.hidden) return;
    if (Date.now() - lastCountAt < 10000) return;
    lastCountAt = Date.now();
    api('GET', URLS.count).then(function (d) {
      setCsrf(d.csrf);
      setBadge(d.count);
    }).catch(function () {});
  }

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) refreshCount();
  });
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) refreshCount();
  });
})();
</script>