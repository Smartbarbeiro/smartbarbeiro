/* Tesora service worker — enables installability and a light offline fallback. */
const CACHE_NAME = 'tesora-v1';
const PRECACHE_URLS = [
    '/site.webmanifest',
    '/offline.html',
    '/web-app-manifest-192x192.png',
    '/web-app-manifest-512x512.png',
    '/apple-touch-icon.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key !== CACHE_NAME)
                        .map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Never cache authenticated app navigations or APIs — network only.
    if (
        request.mode === 'navigate' ||
        url.pathname.startsWith('/webhooks/') ||
        url.pathname.startsWith('/api/')
    ) {
        event.respondWith(
            fetch(request).catch(async () => {
                const offline = await caches.match('/offline.html');

                return offline || Response.error();
            }),
        );

        return;
    }

    // Vite bundles change hash on every deploy — always fetch fresh from network.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            fetch(request).catch(() => caches.match(request)),
        );

        return;
    }

    // Other static assets: cache-first, then network.
    if (
        url.pathname.startsWith('/images/') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.ico') ||
        url.pathname.endsWith('.webmanifest')
    ) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) {
                    return cached;
                }

                return fetch(request).then((response) => {
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }

                    const copy = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));

                    return response;
                });
            }),
        );
    }
});
