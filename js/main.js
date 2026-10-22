/* ==========================================================================
   TaniRaya ERP — Shared JS
   ========================================================================== */

// ---- Live clock shown in headers ----
function startLiveClock() {
  const el = document.getElementById('liveClock');
  if (!el) return;

  const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];
  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

  function pad(n) { return n.toString().padStart(2, '0'); }

  function render() {
    const now = new Date();
    const time = pad(now.getHours()) + ':' + pad(now.getMinutes());
    const date = dayNames[now.getDay()] + ', ' + now.getDate() + ' ' + monthNames[now.getMonth()] + ' ' + now.getFullYear();
    el.innerHTML = '<span class="clock-time">' + time + '</span><span>' + date + '</span>';
  }
  render();
  setInterval(render, 15000);
}

// ---- Tabs: switch between "Daftar" (list) and "Form" views ----
function initTabs() {
  const tabButtons = document.querySelectorAll('[data-tab-target]');
  tabButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-tab-target');
      switchView(targetId, btn);
    });
  });
}

function switchView(targetId, triggerBtn) {
  document.querySelectorAll('.view').forEach((v) => v.classList.remove('is-active'));
  const target = document.getElementById(targetId);
  if (target) target.classList.add('is-active');

  if (triggerBtn) {
    const group = triggerBtn.closest('.tabs');
    if (group) {
      group.querySelectorAll('.tab-btn').forEach((b) => b.classList.remove('is-active'));
      triggerBtn.classList.add('is-active');
    }
  }
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ---- Toast (dipakai untuk flash message dari Laravel: session('status') / errors) ----
let toastTimer = null;
function showToast(message) {
  let toast = document.getElementById('appToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'appToast';
    toast.className = 'toast';
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.classList.add('is-visible');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('is-visible'), 5000);
}

// ---- Form transaksi aman: cegah kirim ganda + simpan draf otomatis ----
function makeToken() {
  if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
  return 'tk-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
}

function initSafeForms() {
  const DRAFT_MAX_AGE = 24 * 60 * 60 * 1000; // draf lebih dari 24 jam dibuang
  const hasFlashStatus = !!document.body.getAttribute('data-flash-status');
  const hasErrors = !!document.querySelector('.alert-warning');

  document.querySelectorAll('form[data-safe-form]').forEach((form) => {
    const key = 'draft:' + form.getAttribute('data-safe-form');
    const useDraft = !form.hasAttribute('data-no-draft');

    // 1) Kode unik per form (server menolak kiriman yang kodenya sama)
    let tokenInput = form.querySelector('input[name="_submit_token"]');
    if (!tokenInput) {
      tokenInput = document.createElement('input');
      tokenInput.type = 'hidden';
      tokenInput.name = '_submit_token';
      form.appendChild(tokenInput);
    }
    tokenInput.value = makeToken();

    // 2) Kolom yang ikut disimpan sebagai draf
    const skipNames = ['_token', '_submit_token', '_method'];
    const skipTypes = ['hidden', 'password', 'file', 'submit', 'button', 'checkbox', 'radio'];
    const fields = Array.from(form.elements).filter((el) =>
      el.name && skipNames.indexOf(el.name) === -1 && skipTypes.indexOf(el.type) === -1
    );

    function readDraft() {
      try { return JSON.parse(localStorage.getItem(key) || 'null'); } catch (e) { return null; }
    }

    function snapshot(submitted) {
      const values = {};
      let any = false;
      fields.forEach((el) => { values[el.name] = el.value; if (el.value) any = true; });
      try {
        if (any) localStorage.setItem(key, JSON.stringify({ values: values, submitted: submitted, at: Date.now() }));
        else localStorage.removeItem(key);
      } catch (e) { /* penyimpanan penuh / diblokir: abaikan */ }
    }

    // 3) Pulihkan draf (atau hapus kalau sebelumnya sudah sukses tersimpan)
    let draft = useDraft ? readDraft() : null;
    if (draft && (Date.now() - (draft.at || 0) > DRAFT_MAX_AGE)) {
      try { localStorage.removeItem(key); } catch (e) {}
      draft = null;
    }
    if (draft && draft.submitted && hasFlashStatus) {
      try { localStorage.removeItem(key); } catch (e) {}
      draft = null;
    }
    if (draft && draft.values && !hasErrors) {
      let restored = false;
      fields.forEach((el) => {
        if (draft.values[el.name] !== undefined && draft.values[el.name] !== '') {
          el.value = draft.values[el.name];
          restored = true;
        }
      });
      if (restored && typeof showToast === 'function') showToast('Draf terakhir dipulihkan.');
    }

    // 4) Simpan draf otomatis saat mengetik
    let timer = null;
    const scheduleSave = () => {
      clearTimeout(timer);
      timer = setTimeout(() => snapshot(false), 400);
    };
    if (useDraft) {
      form.addEventListener('input', scheduleSave);
      form.addEventListener('change', scheduleSave);
    }

    // 5) Kunci tombol simpan setelah diklik
    const buttons = Array.from(form.querySelectorAll('button[type="submit"], button:not([type])'));
    let unlockTimer = null;
    const lock = () => {
      buttons.forEach((b) => {
        b.dataset.origHtml = b.innerHTML;
        b.disabled = true;
        b.textContent = 'Menyimpan...';
      });
      // Jaga-jaga koneksi macet: tombol dibuka lagi setelah 15 detik (kode unik tetap mencegah data ganda)
      unlockTimer = setTimeout(unlock, 15000);
    };
    const unlock = () => {
      clearTimeout(unlockTimer);
      buttons.forEach((b) => {
        if (b.dataset.origHtml !== undefined) b.innerHTML = b.dataset.origHtml;
        b.disabled = false;
      });
    };

    form._safeFormUnlock = unlock; // dipakai skrip form yang dikirim lewat fetch (Master User)

    form.addEventListener('submit', () => {
      clearTimeout(timer);
      if (useDraft) snapshot(true); // tandai "sedang dikirim"; dihapus otomatis kalau server membalas sukses
      setTimeout(lock, 0);
    });

    // Kembali lewat tombol Back (halaman dari cache browser): buka kunci + kode baru
    window.addEventListener('pageshow', (e) => {
      if (e.persisted) {
        unlock();
        tokenInput.value = makeToken();
      }
    });
  });

  // Logout: hapus semua draf di HP ini supaya tidak terlihat user berikutnya
  document.querySelectorAll('form[action$="/logout"]').forEach((f) => {
    f.addEventListener('submit', () => {
      try {
        Object.keys(localStorage)
          .filter((k) => k.indexOf('draft:') === 0)
          .forEach((k) => localStorage.removeItem(k));
      } catch (e) {}
    });
  });
}

// ---- Muat ulang halaman yang terlalu lama tidak dibuka (aplikasi di HP), supaya token form tidak basi ----
(function () {
  var hiddenAt = null;
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) { hiddenAt = Date.now(); return; }
    if (hiddenAt && Date.now() - hiddenAt > 30 * 60 * 1000) { location.reload(); }
    hiddenAt = null;
  });
  window.addEventListener('pageshow', function (e) { if (e.persisted) location.reload(); });
})();

// ---- Daftarkan service worker (PWA) ----
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  });
}

// ---- Confirm dialog helper for delete forms ----
function confirmDelete(message) {
  return window.confirm(message || 'Yakin ingin menghapus data ini?');
}

// ---- Popup approve pre-order (isi jumlah disetujui) ----
function openApprovePopup(button) {
  const url = button.getAttribute('data-po-url');
  const code = button.getAttribute('data-po-code');
  const qty = button.getAttribute('data-po-qty');

  document.getElementById('approveForm').action = url;
  document.getElementById('approvePoLabel').textContent = code;
  document.getElementById('approveQtyInput').value = qty;

  document.getElementById('approveModalOverlay').classList.add('is-open');
}
function closeApprovePopup() {
  document.getElementById('approveModalOverlay').classList.remove('is-open');
}

// ---- Dropdown user menu di header ----
function toggleUserMenu(event) {
  event.stopPropagation();
  document.getElementById('userMenuDropdown')?.classList.toggle('is-open');
}
document.addEventListener('click', (e) => {
  const dropdown = document.getElementById('userMenuDropdown');
  if (dropdown && !dropdown.contains(e.target) && !e.target.closest('.user-menu-trigger')) {
    dropdown.classList.remove('is-open');
  }
});

// ---- Format nominal otomatis: 200000 -> 200.000 (field bertanda data-currency) ----
function formatCurrencyValue(value) {
  const digits = String(value == null ? '' : value).replace(/\D/g, '').replace(/^0+(?=\d)/, '');
  return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Dipakai saat mengetik: posisi kursor dijaga supaya tidak loncat ke ujung
function formatCurrencyInput(el) {
  const before = el.value;
  const pos = el.selectionStart;
  const digitsBeforeCursor = pos == null ? null : before.slice(0, pos).replace(/\D/g, '').length;
  const formatted = formatCurrencyValue(before);
  if (formatted === before) return;

  el.value = formatted;

  if (digitsBeforeCursor === null) return;
  let newPos = formatted.length;
  if (digitsBeforeCursor === 0) {
    newPos = 0;
  } else {
    let count = 0;
    for (let i = 0; i < formatted.length; i++) {
      if (/\d/.test(formatted[i])) count++;
      if (count === digitsBeforeCursor) { newPos = i + 1; break; }
    }
  }
  try { el.setSelectionRange(newPos, newPos); } catch (e) {}
}

// Format ulang semua field nominal (dipanggil saat halaman dimuat, setelah edit diisi, dan saat tombol Back)
function formatAllCurrency(root) {
  (root || document).querySelectorAll('[data-currency]').forEach((el) => {
    el.value = formatCurrencyValue(el.value);
  });
}

function initCurrencyInputs() {
  formatAllCurrency(document);

  // Format saat mengetik
  document.addEventListener('input', (e) => {
    if (e.target.matches && e.target.matches('[data-currency]')) formatCurrencyInput(e.target);
  });

  // Buang titik sebelum dikirim ke server (server tetap terima angka murni, mis. 200000)
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!form.querySelectorAll) return;
    const fields = form.querySelectorAll('[data-currency]');
    if (!fields.length) return;

    fields.forEach((el) => { el.value = el.value.replace(/\D/g, ''); });
    // Data form sudah terambil saat submit; tampilkan titik lagi supaya tidak terlihat berubah
    setTimeout(() => formatAllCurrency(form), 0);
  });

  // Kembali lewat tombol Back (halaman dari cache browser)
  window.addEventListener('pageshow', () => formatAllCurrency(document));
}

// ---- Isi ulang form "Tambah" dengan data lama, ubah jadi mode edit ----
function openEditForm(button, formSelector) {
  const data = JSON.parse(button.getAttribute('data-edit'));
  const updateUrl = button.getAttribute('data-update-url');
  const form = document.querySelector(formSelector);
  if (!form) return;

  form.action = updateUrl;

  let methodInput = form.querySelector('input[name="_method"]');
  if (!methodInput) {
    methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    form.prepend(methodInput);
  }
  methodInput.value = 'PUT';

  Object.keys(data).forEach((key) => {
    const field = form.querySelector('[name="' + key + '"]');
    if (field) field.value = data[key];
  });
  formatAllCurrency(form);

  const submitBtn = form.querySelector('[data-form-submit]');
  if (submitBtn) submitBtn.textContent = 'Simpan Perubahan';

  switchView('viewForm', document.querySelector('[data-tab-target="viewForm"]'));
}

// ---- Balikin form ke mode "Tambah" baru (dipanggil saat klik tab "+ Tambah") ----
function resetFormToCreate(formSelector, createUrl, defaultLabel) {
  const form = document.querySelector(formSelector);
  if (!form) return;

  form.reset();
  form.action = createUrl;

  const methodInput = form.querySelector('input[name="_method"]');
  if (methodInput) methodInput.remove();

  const submitBtn = form.querySelector('[data-form-submit]');
  if (submitBtn) submitBtn.textContent = defaultLabel || 'Simpan';
}

// ---- Ganti 1 query param di URL saat ini, reset ke halaman 1 ----
function updateQueryParam(key, value) {
  const url = new URL(window.location.href);
  url.searchParams.set(key, value);
  url.searchParams.delete('page');
  return url.toString();
}

// ---- Nomor urut otomatis di semua tabel (berlanjut antar halaman) ----
function initRowNumbers() {
  const params = new URLSearchParams(window.location.search);
  let page = parseInt(params.get('page'), 10);
  if (!(page > 1)) page = 1;
  let per = parseInt(params.get('per_page'), 10);
  if ([10, 20, 50, 100].indexOf(per) === -1) per = 20;
  const offset = (page - 1) * per;

  document.querySelectorAll('table.data-table:not([data-no-number])').forEach((table) => {
    const headRow = table.querySelector('thead tr');
    if (!headRow) return;

    const th = document.createElement('th');
    th.className = 'col-no';
    th.textContent = 'No';
    headRow.insertBefore(th, headRow.firstChild);

    let n = offset;
    table.querySelectorAll('tbody tr').forEach((tr) => {
      const span = tr.querySelector('td[colspan]');
      if (span) { span.colSpan = span.colSpan + 1; return; }
      n++;
      const td = document.createElement('td');
      td.className = 'col-no';
      td.textContent = n;
      tr.insertBefore(td, tr.firstChild);
    });
  });
}

// ---- Dropdown yang bisa dicari (semua <select>; pengecualian: beri atribut data-no-search) ----
(function () {
  var MAX_RENDER = 300; // baris maksimal yang digambar sekaligus (sisanya muncul setelah mengetik)

  var valueDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
  var indexDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');
  var panel = null, backdrop = null, search = null, list = null, state = null, bound = false;

  function isMobile() { return window.matchMedia('(max-width: 767px)').matches; }
  function isLocked(select) { return select.style.pointerEvents === 'none'; }
  function clean(text) { return String(text || '').replace(/\s+/g, ' ').trim(); }

  /* ---------- Panel bersama (satu panel dipakai semua dropdown) ---------- */

  function ensurePanel() {
    if (panel) return;

    backdrop = document.createElement('div');
    backdrop.className = 'ss-backdrop';
    backdrop.addEventListener('click', function (e) { e.preventDefault(); close(true); });

    panel = document.createElement('div');
    panel.className = 'ss-panel';
    panel.innerHTML = '<input type="text" class="ss-search" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Ketik untuk mencari..." aria-label="Cari pilihan"><ul class="ss-list" role="listbox"></ul>';
    search = panel.querySelector('.ss-search');
    list = panel.querySelector('.ss-list');

    document.body.appendChild(backdrop);
    document.body.appendChild(panel);

    search.addEventListener('input', render);
    search.addEventListener('keydown', onSearchKey);

    list.addEventListener('click', function (e) {
      var li = e.target.closest ? e.target.closest('.ss-item') : null;
      if (!li || li.classList.contains('is-disabled')) return;
      choose(parseInt(li.getAttribute('data-pos'), 10));
    });
    list.addEventListener('mousemove', function (e) {
      var li = e.target.closest ? e.target.closest('.ss-item') : null;
      if (!li || li.classList.contains('is-disabled')) return;
      setActive(parseInt(li.getAttribute('data-pos'), 10), false);
    });
  }

  function render() {
    var q = search.value.toLowerCase().split(/\s+/).filter(Boolean);
    var matches = state.items.filter(function (it) {
      return q.every(function (t) { return it.lower.indexOf(t) !== -1; });
    });
    state.total = matches.length;
    state.filtered = matches.slice(0, MAX_RENDER);

    list.innerHTML = '';

    if (!state.filtered.length) {
      var empty = document.createElement('li');
      empty.className = 'ss-note';
      empty.textContent = state.items.length ? 'Tidak ada hasil' : 'Tidak ada pilihan';
      list.appendChild(empty);
      state.active = -1;
      return;
    }

    var frag = document.createDocumentFragment();
    state.filtered.forEach(function (it, pos) {
      var li = document.createElement('li');
      li.className = 'ss-item' + (it.selected ? ' is-selected' : '') + (it.disabled ? ' is-disabled' : '');
      li.setAttribute('role', 'option');
      li.setAttribute('aria-selected', it.selected ? 'true' : 'false');
      li.setAttribute('data-pos', pos);
      li.textContent = it.text || '-';
      frag.appendChild(li);
    });
    if (state.total > state.filtered.length) {
      var more = document.createElement('li');
      more.className = 'ss-note';
      more.textContent = 'Menampilkan ' + state.filtered.length + ' dari ' + state.total + '. Ketik untuk mempersempit.';
      frag.appendChild(more);
    }
    list.appendChild(frag);

    // Sorot pilihan yang sedang aktif; kalau tidak ada, baris pertama yang bisa dipilih
    var start = state.filtered.findIndex(function (it) { return it.selected && !it.disabled; });
    if (start < 0) start = state.filtered.findIndex(function (it) { return !it.disabled; });
    setActive(start, true);
  }

  function ensureVisible(el) {
    var top = el.offsetTop, bottom = top + el.offsetHeight;
    if (top < list.scrollTop) list.scrollTop = top;
    else if (bottom > list.scrollTop + list.clientHeight) list.scrollTop = bottom - list.clientHeight;
  }

  function setActive(pos, scroll) {
    var prev = list.querySelector('.ss-item.is-active');
    if (prev) prev.classList.remove('is-active');
    state.active = pos;
    if (pos < 0) return;
    var el = list.querySelector('.ss-item[data-pos="' + pos + '"]');
    if (!el) return;
    el.classList.add('is-active');
    if (scroll) ensureVisible(el);
  }

  function move(dir) {
    var n = state.filtered.length;
    if (!n) return;
    var pos = state.active;
    for (var i = 0; i < n; i++) {
      pos = (pos + dir + n) % n;
      if (!state.filtered[pos].disabled) { setActive(pos, true); return; }
    }
  }

  function onSearchKey(e) {
    if (!state) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
    else if (e.key === 'Enter') { e.preventDefault(); if (state.active >= 0) choose(state.active); }
    else if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(true); }
    else if (e.key === 'Tab') { close(false); }
  }

  function choose(pos) {
    var it = state && state.filtered[pos];
    if (!it || it.disabled) return;
    var select = state.select;
    var changed = select.selectedIndex !== it.index;
    if (changed) {
      select.selectedIndex = it.index;
      select.dispatchEvent(new Event('input', { bubbles: true }));
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }
    close(true);
  }

  function place() {
    if (!state) return;
    var sheet = isMobile();
    panel.classList.toggle('ss-sheet', sheet);
    backdrop.classList.toggle('is-open', sheet);

    if (sheet) { // HP: panel di bagian atas layar, tidak tertutup keyboard
      panel.style.top = panel.style.bottom = panel.style.left = panel.style.width = '';
      list.style.maxHeight = '';
      return;
    }

    var r = state.trigger.getBoundingClientRect();
    var vh = window.innerHeight;
    var below = vh - r.bottom - 12;
    var above = r.top - 12;
    var flip = below < 220 && above > below;
    var width = Math.max(r.width, 220);
    var left = Math.max(8, Math.min(r.left, window.innerWidth - width - 8));

    panel.style.width = width + 'px';
    panel.style.left = left + 'px';
    list.style.maxHeight = Math.max(120, Math.min(280, (flip ? above : below) - 62)) + 'px';
    if (flip) { panel.style.top = 'auto'; panel.style.bottom = (vh - r.top + 4) + 'px'; }
    else { panel.style.bottom = 'auto'; panel.style.top = (r.bottom + 4) + 'px'; }
  }

  function open(select) {
    var meta = select._ss;
    if (!meta || select.disabled || isLocked(select)) return;
    if (state && state.select === select) { close(true); return; }
    close(false);
    ensurePanel();

    var skipEmpty = select.required; // pilihan kosong ("Pilih ...") disembunyikan kalau wajib diisi
    var items = [];
    Array.prototype.forEach.call(select.options, function (o, i) {
      if (skipEmpty && o.value === '') return;
      var text = clean(o.text);
      items.push({ index: i, text: text, lower: text.toLowerCase(), disabled: o.disabled, selected: i === select.selectedIndex });
    });

    state = { select: select, trigger: meta.trigger, items: items, filtered: [], total: 0, active: -1 };
    search.value = '';
    render();

    panel.classList.add('is-open');
    meta.trigger.classList.add('is-open');
    meta.trigger.setAttribute('aria-expanded', 'true');
    place();

    var a = list.querySelector('.ss-item.is-active');
    if (a) ensureVisible(a);
    try { search.focus({ preventScroll: true }); } catch (e) { search.focus(); }
  }

  function close(refocus) {
    if (!state) return;
    var s = state;
    state = null;
    panel.classList.remove('is-open');
    backdrop.classList.remove('is-open');
    s.trigger.classList.remove('is-open');
    s.trigger.setAttribute('aria-expanded', 'false');
    if (refocus) { try { s.trigger.focus({ preventScroll: true }); } catch (e) {} }
  }

  /* ---------- Membungkus tiap <select> ---------- */

  function refresh(select) {
    var meta = select._ss;
    if (!meta) return;
    var opt = select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
    meta.label.textContent = opt ? clean(opt.text) : '';
    meta.label.classList.toggle('is-placeholder', !opt || (select.required && opt.value === ''));

    var locked = isLocked(select); // dikunci oleh skrip halaman (mis. Pembelian terkait pre-order)
    meta.trigger.disabled = select.disabled;
    meta.trigger.classList.toggle('is-locked', locked);
    meta.trigger.tabIndex = locked ? -1 : 0;
    if (select.tabIndex !== -1) select.tabIndex = -1;
  }

  function enhance(select) {
    if (select._ss || select.multiple || select.size > 1 || select.hasAttribute('data-no-search')) return;

    var wrap = document.createElement('div');
    wrap.className = 'ss' + (select.style.width === 'auto' ? ' ss--auto' : '');

    var trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = select.className + ' ss-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    if (select.style.width) trigger.style.width = select.style.width;
    if (select.style.height) trigger.style.height = select.style.height;

    var label = document.createElement('span');
    label.className = 'ss-label';
    var caret = document.createElement('span');
    caret.className = 'ss-caret';
    caret.setAttribute('aria-hidden', 'true');
    trigger.appendChild(label);
    trigger.appendChild(caret);

    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    wrap.appendChild(trigger);
    select.classList.add('ss-native');
    select.setAttribute('aria-hidden', 'true');
    select.tabIndex = -1;

    select._ss = { wrap: wrap, trigger: trigger, label: label };

    // Skrip halaman sering mengubah select.value secara langsung: tampilan ikut diperbarui
    Object.defineProperty(select, 'value', {
      configurable: true,
      get: function () { return valueDesc.get.call(this); },
      set: function (v) { valueDesc.set.call(this, v); refresh(select); }
    });
    Object.defineProperty(select, 'selectedIndex', {
      configurable: true,
      get: function () { return indexDesc.get.call(this); },
      set: function (v) { indexDesc.set.call(this, v); refresh(select); }
    });

    // Pilihan dibangun ulang / dinonaktifkan / dikunci oleh skrip
    new MutationObserver(function () { refresh(select); }).observe(select, {
      childList: true, subtree: true, attributes: true,
      attributeFilter: ['disabled', 'style', 'tabindex', 'required', 'label']
    });

    trigger.addEventListener('click', function () { open(select); });
    trigger.addEventListener('keydown', function (e) {
      if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && !(state && state.select === select)) {
        e.preventDefault();
        open(select);
      }
    });

    select.addEventListener('change', function () { trigger.classList.remove('is-invalid'); refresh(select); });
    select.addEventListener('invalid', function () { trigger.classList.add('is-invalid'); });
    if (select.form) {
      select.form.addEventListener('reset', function () { setTimeout(function () { refresh(select); }, 0); });
    }

    // Klik teks label ikut membuka dropdown
    if (select.id && window.CSS && CSS.escape) {
      document.querySelectorAll('label[for="' + CSS.escape(select.id) + '"]').forEach(function (l) {
        l.addEventListener('click', function (e) { e.preventDefault(); if (!trigger.disabled) open(select); });
      });
    }

    refresh(select);
  }

  function enhanceAll(root) {
    (root || document).querySelectorAll('select').forEach(enhance);
  }

  function bindGlobals() {
    if (bound) return;
    bound = true;

    document.addEventListener('pointerdown', function (e) {
      if (!state) return;
      var t = e.target;
      if (panel.contains(t) || state.trigger.contains(t) || t === backdrop) return;
      close(false);
    }, true);
    document.addEventListener('keydown', function (e) { if (state && e.key === 'Escape') close(true); });
    window.addEventListener('resize', function () { if (state) place(); });
    window.addEventListener('scroll', function (e) { if (state && !panel.contains(e.target)) place(); }, { capture: true, passive: true });
    window.addEventListener('pageshow', function () {
      document.querySelectorAll('select.ss-native').forEach(function (s) { refresh(s); });
    });
  }

  window.initSearchableSelects = function () { bindGlobals(); enhanceAll(document); };
  window.enhanceSelects = enhanceAll; // untuk <select> yang dibuat belakangan lewat skrip
})();

// ---- Detail modal (dipakai semua modul) ----
function ensureDetailModal() {
  let overlay = document.getElementById('detailModalOverlay');
  if (overlay) return overlay;

  overlay = document.createElement('div');
  overlay.id = 'detailModalOverlay';
  overlay.className = 'modal-overlay';
  overlay.innerHTML =
    '<div class="modal-box">' +
      '<div class="modal-box__header">' +
        '<span class="modal-box__title" id="detailModalTitle"></span>' +
        '<button type="button" class="modal-box__close" onclick="closeDetailModal()">&times;</button>' +
      '</div>' +
      '<div id="detailModalBody"></div>' +
    '</div>';
  document.body.appendChild(overlay);
  overlay.addEventListener('click', (e) => { if (e.target === overlay) closeDetailModal(); });
  return overlay;
}

function showDetailModal(button) {
  const data = JSON.parse(button.getAttribute('data-detail'));
  const title = button.getAttribute('data-detail-title') || 'Detail';

  ensureDetailModal();
  document.getElementById('detailModalTitle').textContent = title;

  const body = document.getElementById('detailModalBody');
  body.innerHTML = '';

  Object.keys(data).forEach((label) => {
    if (label.indexOf('## ') === 0) {
      const heading = document.createElement('div');
      heading.className = 'modal-section';
      heading.textContent = label.slice(3);
      body.appendChild(heading);
      return;
    }
    if (label === '__photo__') {
      if (data[label]) {
        const img = document.createElement('img');
        img.src = data[label];
        img.style.cssText = 'width:100%;border-radius:12px;margin-bottom:10px;';
        body.appendChild(img);
      }
      return;
    }
    const row = document.createElement('div');
    row.className = 'modal-row';
    row.innerHTML = '<span class="modal-row__label">' + label + '</span><span class="modal-row__value">' + (data[label] ?? '-') + '</span>';
    body.appendChild(row);
  });

  document.getElementById('detailModalOverlay').classList.add('is-open');
}

function closeDetailModal() {
  document.getElementById('detailModalOverlay')?.classList.remove('is-open');
}

document.addEventListener('DOMContentLoaded', () => {
  startLiveClock();
  initRowNumbers();
  initTabs();
  initSafeForms();
  initCurrencyInputs();
  initSearchableSelects();

  // Tampilkan flash message dari server (di-set lewat data attribute di <body>)
  const flashStatus = document.body.getAttribute('data-flash-status');
  const flashError = document.body.getAttribute('data-flash-error');
  if (flashStatus) showToast(flashStatus);
  if (flashError) showToast(flashError);
});
