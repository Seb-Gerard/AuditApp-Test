/// <reference lib="webworker" />

const PREFIX = 'V1';
const urlsToCache = [ 
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
  "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css",
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js",
  "/Audit/views/articles/index.php",
  "/Audit/includes/header.php",
  "/Audit/includes/footer.php",
  "/Audit/js/db.js",
  "/Audit/js/app.js",
  "/Audit/offline.php"
];

// Import the database handler
importScripts('/Audit/js/db.js');

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil((async () => {
        const cache = await caches.open(PREFIX);
        await cache.addAll(urlsToCache);
    })());
    console.log(`${PREFIX} Install`);
});

self.addEventListener('activate', (event) => {
    clients.claim();
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(
                keys.map((key) => {
                    if (!key.includes(PREFIX)) {
                        return caches.delete(key);
                    }
                })
            );
        })()
    );
    console.log(`${PREFIX} Active`);
});

self.addEventListener('fetch', (event) => {
    console.log(
        `${PREFIX} Fetching : ${event.request.url}, mode : ${event.request.mode}`
    );
    
    if (event.request.mode === 'navigate') {
        event.respondWith((async () => {
            try {
                const preloadResponse = await event.preloadResponse;
                if (preloadResponse) {
                    return preloadResponse;
                }
                return await fetch(event.request);
            } catch (error) {
                const cache = await caches.open(PREFIX);
                return await cache.match('/Audit/offline.php');
            }
        })());
    } else {
        event.respondWith((async () => {
            const cache = await caches.open(PREFIX);
            const cachedResponse = await cache.match(event.request);
            if (cachedResponse) {
                return cachedResponse;
            }
            try {
                const response = await fetch(event.request);
                await cache.put(event.request, response.clone());
                return response;
            } catch (error) {
                return new Response('', { status: 503 });
            }
        })());
    }
});

// Handle article submission
self.addEventListener('message', async (event) => {
    if (event.data.type === 'SAVE_ARTICLE') {
        try {
            await ArticleDB.saveArticle(event.data.article);
            event.source.postMessage({ type: 'ARTICLE_SAVED', success: true });
        } catch (error) {
            event.source.postMessage({ type: 'ARTICLE_SAVED', success: false, error: error.message });
        }
    } else if (event.data.type === 'GET_ARTICLES') {
        try {
            const articles = await ArticleDB.getArticles();
            event.source.postMessage({ type: 'ARTICLES_RETRIEVED', articles });
        } catch (error) {
            event.source.postMessage({ type: 'ARTICLES_RETRIEVED', error: error.message });
        }
    }
});