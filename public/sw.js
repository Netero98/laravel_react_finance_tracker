// Service Worker for Laravel React Portfolio PWA

const CACHE_NAME = 'portfolio-cache-v1';
const urlsToCache = [
  '/manifest.json',
  '/favicon.ico',
  '/favicon.svg',
  '/apple-touch-icon.png',
  // Add other static assets here
];

// Install event - cache assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
  // Dont use cache for any requests for now


  // // Skip caching for root URL and other authenticated routes
  // const url = new URL(event.request.url);
  // const isRootPath = url.pathname === '/' || url.pathname === '';
  // const isAuthenticatedRoute = isRootPath ||
  //                             url.pathname.startsWith('/dashboard') ||
  //                             url.pathname.startsWith('/categories') ||
  //                             url.pathname.startsWith('/wallets') ||
  //                             url.pathname.startsWith('/transactions');
  //
  // // For authenticated routes, don't use cache
  // if (isAuthenticatedRoute) {
  //   return;
  // }
  //
  // event.respondWith(
  //   caches.match(event.request)
  //     .then(response => {
  //       // Cache hit - return response
  //       if (response) {
  //         return response;
  //       }
  //
  //       // Clone the request because it's a one-time use stream
  //       const fetchRequest = event.request.clone();
  //
  //       return fetch(fetchRequest).then(
  //         response => {
  //           // Check if we received a valid response
  //           if (!response || response.status !== 200 || response.type !== 'basic') {
  //             return response;
  //           }
  //
  //           // Clone the response because it's a one-time use stream
  //           const responseToCache = response.clone();
  //
  //           caches.open(CACHE_NAME)
  //             .then(cache => {
  //               // Don't cache API requests or other dynamic content
  //               if (!event.request.url.includes('/api/')) {
  //                 cache.put(event.request, responseToCache);
  //               }
  //             });
  //
  //           return response;
  //         }
  //       );
  //     })
  // );
});
