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
    let draft = readDraft();
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
    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);

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

    form.addEventListener('submit', () => {
      clearTimeout(timer);
      snapshot(true); // tandai "sedang dikirim"; dihapus otomatis kalau server membalas sukses
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
  initTabs();
  initSafeForms();

  // Tampilkan flash message dari server (di-set lewat data attribute di <body>)
  const flashStatus = document.body.getAttribute('data-flash-status');
  const flashError = document.body.getAttribute('data-flash-error');
  if (flashStatus) showToast(flashStatus);
  if (flashError) showToast(flashError);
});
