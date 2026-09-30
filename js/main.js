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
  toastTimer = setTimeout(() => toast.classList.remove('is-visible'), 3200);
}

// ---- Confirm dialog helper for delete forms ----
function confirmDelete(message) {
  return window.confirm(message || 'Yakin ingin menghapus data ini?');
}

document.addEventListener('DOMContentLoaded', () => {
  startLiveClock();
  initTabs();

  // Tampilkan flash message dari server (di-set lewat data attribute di <body>)
  const flashStatus = document.body.getAttribute('data-flash-status');
  const flashError = document.body.getAttribute('data-flash-error');
  if (flashStatus) showToast(flashStatus);
  if (flashError) showToast(flashError);
});
