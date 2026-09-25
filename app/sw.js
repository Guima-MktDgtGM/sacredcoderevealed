// Service Worker - Santuario de las 12 Palabras
const CACHE_NAME = 'guion-divino-v1';
const ASSETS_TO_CACHE = [
  './index.html',
  './manifest.json',
  './images/app-icon.jpg',
  './images/guion-divino-cover.jpg',
  './images/acelerador-cover.jpg',
  './images/150-afirmaciones-cover.jpg',
  './images/frecuencias-cover.jpg'
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS_TO_CACHE).catch(() => {}))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) return caches.delete(key);
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (e) => {
  e.respondWith(
    caches.match(e.request).then((res) => res || fetch(e.request).catch(() => caches.match('./index.html')))
  );
});
