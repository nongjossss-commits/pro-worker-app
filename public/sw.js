// Service Worker for PWL System
// Strategy: Network Only (No Offline Support)
// This ensures maximum stability and avoids ERR_FAILED issues from stale caches.

const CACHE_NAME = 'pwl-system-v3-online-only';

self.addEventListener('install', event => {
    // Force the waiting service worker to become the active service worker.
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    // Tell the active service worker to take control of the page immediately.
    event.waitUntil(
        Promise.all([
            self.clients.claim(),
            // Clean up old caches to save space and avoid confusion
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        return caches.delete(cacheName);
                    })
                );
            })
        ])
    );
});

self.addEventListener('fetch', event => {
    // Pass all requests directly to the network.
    // If the network fails, the browser will show its standard offline page.
    // We intentionally do NOT use event.respondWith() here for a pure passthrough,
    // but using it with fetch() allows us to potentially catch errors if we wanted to.
    // For "smoothness", a direct fetch is best.

    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(fetch(event.request));
});
