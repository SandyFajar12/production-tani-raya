/* TaniRaya ERP — Service Worker (cache tampilan + halaman offline) */
const CACHE = 'taniraya-v1';
const BASE = self.registration.scope;
const PRECACHE = [
  'offline.html',
  'css/style.css',
  'js/main.js',
  'manifest.json',
  'icons/icon-192.png',
  'icons/icon-512.png',
  'icons/apple-touch-icon.png'
].map((p) => new URL(p, BASE).href);
const OFFLINE_URL = new URL('offline.html', BASE).href;

function isCacheable(res) {
  if (!res || !res.ok) return false;
  const type = res.headers.get('content-type') || '';
  return type.indexOf('text/html') === -1;
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE)
      .then((cache) => Promise.all(PRECACHE.map((url) =>
        fetch(url, { credentials: 'same-origin' })
          .then((res) => (url === OFFLINE_URL || isCacheable(res)) && res.ok ? cache.put(url, res) : null)
          .catch(() => null)
      )))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

async function staleWhileRevalidate(request) {
  const cache = await caches.open(CACHE);
  const cached = await cache.match(request);
  const network = fetch(request)
    .then((res) => {
      if (isCacheable(res)) cache.put(request, res.clone());
      return res;
    })
    .catch(() => null);
  return cached || (await network) || Response.error();
}

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  // Halaman: selalu ambil dari server (data stok harus terbaru). Kalau gagal -> halaman offline.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match(OFFLINE_URL).then((r) => r || Response.error()))
    );
    return;
  }

  const sameOrigin = url.origin === self.location.origin;
  const isFont = url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com';
  const isStatic = sameOrigin && /\.(css|js|png|jpg|jpeg|svg|ico|json|woff2?)$/i.test(url.pathname)
    && !url.pathname.endsWith('/sw.js');

  if (isStatic || isFont) {
    event.respondWith(staleWhileRevalidate(request));
  }
});