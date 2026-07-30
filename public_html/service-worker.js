const GARBALIA_SW_VERSION = 'garbalia-pos-v1';

self.addEventListener('install', function () {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(keys.map(function (key) {
        return key !== GARBALIA_SW_VERSION ? caches.delete(key) : Promise.resolve();
      }));
    }).then(function () {
      return self.clients.claim();
    })
  );
});

self.addEventListener('fetch', function (event) {
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);
  if (url.origin !== self.location.origin) return;

  // POS data is deliberately network-only. This prevents stale orders,
  // totals or table states from being shown from an offline cache.
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request, {cache: 'no-store'}).catch(function () {
        return new Response(
          '<!doctype html><html lang="ka"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>GARBALIA POS</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f6efe4;color:#2b1b10;font-family:Arial,sans-serif;text-align:center}main{width:min(460px,calc(100% - 32px));padding:30px;border:1px solid #e4d2bc;border-radius:24px;background:#fffaf2;box-shadow:0 20px 50px rgba(43,27,16,.16)}h1{margin:0 0 10px;font-size:1.45rem}p{margin:0;color:#6d5140;font-weight:700;line-height:1.5}</style></head><body><main><h1>ინტერნეტთან კავშირი არ არის</h1><p>შეკვეთების უსაფრთხოდ დასამუშავებლად GARBALIA POS-ს აქტიური ინტერნეტი სჭირდება. კავშირის აღდგენის შემდეგ გვერდი განაახლე.</p></main></body></html>',
          {status: 503, headers: {'Content-Type': 'text/html; charset=UTF-8', 'Cache-Control': 'no-store'}}
        );
      })
    );
  }
});
