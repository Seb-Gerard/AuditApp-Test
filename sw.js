/// <reference lib="webworker" />

const PREFIX = 'V1';
const urlsToCache = [ 
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
  "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css",
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js",
  "/Audit/js/db.js",
  "/Audit/js/app.js",
  "/Audit/offline.php",
  "/Audit/views/articles/index.php",
  "/Audit/includes/header.php",
  "/Audit/includes/footer.php",
  "/Audit/public/assets/css/style.css",
  "/Audit/public/assets/img/Logo_CNPP_250.jpg"
];

// Import the database handler
importScripts('/Audit/js/db.js');

self.addEventListener('install', (event) => {
    // @ts-ignore
    self.skipWaiting();
    event.waitUntil((async () => {
        const cache = await caches.open(PREFIX);
        await cache.addAll(urlsToCache);
    })());
    console.log(`${PREFIX} Install`);
});

self.addEventListener('activate', (event) => {
    // @ts-ignore
    self.clients.claim();
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
    const url = new URL(event.request.url);
    console.log(`${PREFIX} Fetching: ${url.pathname}, mode: ${event.request.mode}`);
    
    // Ne pas intercepter les requêtes AJAX
    if (event.request.headers.get('X-Requested-With') === 'XMLHttpRequest') {
        return;
    }
    
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
    if (!event.source) return;
    
    if (event.data.type === 'SAVE_ARTICLE') {
        try {
            await ArticleDB.saveArticle(event.data.article);
            event.source.postMessage({ type: 'ARTICLE_SAVED', success: true });
        } catch (error) {
            event.source.postMessage({ 
                type: 'ARTICLE_SAVED', 
                success: false, 
                error: error instanceof Error ? error.message : 'Unknown error' 
            });
        }
    } else if (event.data.type === 'GET_ARTICLES') {
        try {
            const articles = await ArticleDB.getArticles();
            event.source.postMessage({ type: 'ARTICLES_RETRIEVED', articles });
        } catch (error) {
            event.source.postMessage({ 
                type: 'ARTICLES_RETRIEVED', 
                error: error instanceof Error ? error.message : 'Unknown error' 
            });
        }
    } else if (event.data.type === 'SYNC_ARTICLES') {
        try {
            const success = await syncArticles();
            event.source.postMessage({ 
                type: 'SYNC_COMPLETED', 
                success: success,
                timestamp: new Date().toISOString() 
            });
        } catch (error) {
            event.source.postMessage({ 
                type: 'SYNC_COMPLETED', 
                success: false,
                error: error instanceof Error ? error.message : 'Unknown error',
                timestamp: new Date().toISOString()
            });
        }
    }
});

// Gestion de la synchronisation
self.addEventListener('sync', event => {
    if (event.tag === 'sync-articles') {
        console.log(`Synchronisation déclenchée avec le tag: ${event.tag}`);
        event.waitUntil(syncArticles());
    }
});

async function syncArticles() {
    console.log('Début de la synchronisation des articles depuis le Service Worker...');
    try {
        const articles = await ArticleDB.getArticles();
        console.log('Articles trouvés dans IndexedDB:', articles.length);
        
        // Filtrer les articles qui n'ont pas d'ID serveur
        const offlineArticles = articles.filter(article => !article.server_id);
        console.log('Articles à synchroniser:', offlineArticles.length);
        
        let syncSuccess = false;
        
        if (offlineArticles.length > 0) {
            let successCount = 0;
            let retryCount = 0;
            const maxRetries = 3;
            
            // Tentative de synchronisation avec retries en cas d'erreur 503
            async function syncWithRetry() {
                if (retryCount >= maxRetries) {
                    console.log(`Abandon après ${maxRetries} tentatives`);
                    return false;
                }
                
                try {
                    for (const article of offlineArticles) {
                        try {
                            console.log('Tentative de synchronisation pour article:', article.title);
                            
                            // Ajouter un délai entre les requêtes pour éviter de surcharger le serveur
                            if (successCount > 0 || retryCount > 0) {
                                await new Promise(resolve => setTimeout(resolve, 1000));
                            }
                            
                            const response = await fetch('/Audit/index.php?action=create', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    title: article.title,
                                    content: article.content
                                })
                            });

                            console.log('Statut de la réponse:', response.status, response.statusText);
                            
                            // Si le serveur est indisponible (503), on réessaiera toute la synchronisation
                            if (response.status === 503) {
                                console.error('Serveur indisponible (503)');
                                throw new Error('Service Unavailable (503)');
                            }
                            
                            // Clone la réponse pour pouvoir la lire plusieurs fois si nécessaire
                            const responseClone = response.clone();
                            
                            if (response.ok) {
                                try {
                                    const result = await response.json();
                                    console.log('Réponse du serveur:', result);
                                    
                                    if (result && result.id) {
                                        // Supprimer l'article de l'IndexedDB après synchronisation réussie
                                        if (article.id) {
                                            await ArticleDB.deleteArticle(article.id);
                                            console.log('Article supprimé de la base locale après synchronisation, ID local:', article.id);
                                        }
                                        successCount++;
                                    } else {
                                        console.error('Pas d\'ID reçu du serveur:', result);
                                    }
                                } catch (jsonError) {
                                    const responseText = await responseClone.text();
                                    console.error('Erreur lors du parsing JSON:', jsonError);
                                    console.error('Réponse brute:', responseText.substring(0, 200));
                                }
                            } else {
                                const errorText = await response.text();
                                console.error('Erreur lors de la synchronisation (HTTP):', response.status, errorText.substring(0, 100));
                            }
                        } catch (error) {
                            console.error('Erreur lors de la synchronisation d\'un article:', 
                                        error instanceof Error ? error.message : error);
                                        
                            // Si l'erreur est 503, on réessaiera toute la synchronisation
                            if (error.message && error.message.includes('503')) {
                                retryCount++;
                                console.log(`Tentative ${retryCount}/${maxRetries} échouée, nouvelle tentative dans 3 secondes...`);
                                await new Promise(resolve => setTimeout(resolve, 3000));
                                return syncWithRetry(); // Retry récursif pour tous les articles
                            }
                        }
                    }
                    return true;
                } catch (error) {
                    console.error('Erreur globale:', error);
                    retryCount++;
                    if (retryCount < maxRetries) {
                        console.log(`Tentative ${retryCount}/${maxRetries} échouée, nouvelle tentative dans 3 secondes...`);
                        await new Promise(resolve => setTimeout(resolve, 3000));
                        return syncWithRetry(); // Retry récursif
                    }
                    return false;
                }
            }
            
            const syncResult = await syncWithRetry();
            syncSuccess = successCount > 0;
            console.log(`Synchronisation terminée: ${successCount}/${offlineArticles.length} articles synchronisés (réussie: ${syncSuccess})`);
        } else {
            console.log('Aucun article à synchroniser');
        }
        
        // Notifier les clients que la synchronisation est terminée
        try {
            // @ts-ignore
            const clients = await self.clients.matchAll();
            console.log(`Notification à ${clients.length} clients`);
            for (const client of clients) {
                client.postMessage({ 
                    type: 'SYNC_COMPLETED', 
                    success: syncSuccess,
                    timestamp: new Date().toISOString()
                });
            }
        } catch (error) {
            console.error('Erreur lors de la notification des clients:', 
                        error instanceof Error ? error.message : error);
        }
        
        return syncSuccess;
    } catch (error) {
        console.error('Erreur globale lors de la synchronisation:', 
                    error instanceof Error ? error.message : error);
        return false;
    }
}