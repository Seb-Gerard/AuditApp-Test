/// <reference lib="webworker" />

/* global self */

const PREFIX = "V1";
const urlsToCache = [
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
  "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css",
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js",
  "/Audit/offline.php",
  "/Audit/index.php?action=articles",
  "/Audit/public/assets/css/style.css",
  "/Audit/public/assets/img/Logo_CNPP_250.jpg",
];

// Note: Nous n'importons PAS db.js car il utilise window qui n'est pas disponible dans le SW

self.addEventListener("install", (event) => {
  // @ts-ignore
  self.skipWaiting();
  console.log("[Service Worker] Installé avec succès");
  event.waitUntil(
    (async () => {
      const cache = await caches.open(PREFIX);
      try {
        for (const url of urlsToCache) {
          try {
            const response = await fetch(url, {
              cache: "no-cache",
              credentials: "same-origin",
            });

            if (response.ok) {
              await cache.put(url, response);
            }
          } catch (fileError) {
            // Échec silencieux pour la mise en cache
          }
        }
      } catch (error) {
        // Ignorer les erreurs de mise en cache
      }
    })()
  );
});

self.addEventListener("activate", (event) => {
  // @ts-ignore
  self.clients.claim();
  console.log("[Service Worker] Activé avec succès");
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
});

self.addEventListener("fetch", (event) => {
  const url = new URL(event.request.url);

  // Ne pas intercepter les requêtes AJAX
  if (event.request.headers.get("X-Requested-With") === "XMLHttpRequest") {
    return;
  }

  if (event.request.mode === "navigate") {
    event.respondWith(
      (async () => {
        try {
          return await fetch(event.request);
        } catch (error) {
          const cache = await caches.open(PREFIX);
          const cached = await cache.match("/Audit/offline.php");
          if (cached) {
            return cached;
          }

          // Fallback basique si la page offline.php n'est pas dans le cache
          return new Response(
            `<!DOCTYPE html>
          <html lang="fr">
          <head>
            <meta charset="UTF-8">
            <title>Hors ligne</title>
            <style>
              body { font-family: Arial; padding: 20px; }
              .alert { padding: 15px; background-color: #fff3cd; border: 1px solid #ffeeba; }
            </style>
          </head>
          <body>
            <div class="alert">
              <h4>Mode hors ligne</h4>
              <p>Vous êtes actuellement hors ligne. Reconnectez-vous pour accéder à toutes les fonctionnalités.</p>
            </div>
          </body>
          </html>`,
            {
              status: 200,
              headers: { "Content-Type": "text/html; charset=utf-8" },
            }
          );
        }
      })()
    );
  } else {
    event.respondWith(
      (async () => {
        const cache = await caches.open(PREFIX);
        const cachedResponse = await cache.match(event.request);
        if (cachedResponse) {
          return cachedResponse;
        }
        try {
          const response = await fetch(event.request);
          // Ne mettre en cache que les ressources statiques
          if (url.pathname.match(/\.(css|js|jpg|png|svg|ico)$/)) {
            await cache.put(event.request, response.clone());
          }
          return response;
        } catch (error) {
          return new Response("", { status: 503 });
        }
      })()
    );
  }
});

// Fonctionnalité minimale de messages
self.addEventListener("message", (event) => {
  if (!event.source) return;

  if (event.data.type === "SKIP_WAITING") {
    // @ts-ignore
    self.skipWaiting();
  }
});

// Rediriger les demandes de synchronisation vers la page
self.addEventListener("sync", (event) => {
  if (event.tag === "sync-articles") {
    // @ts-ignore
    self.clients.matchAll().then((clients) => {
      clients.forEach((client) => {
        client.postMessage({
          type: "SYNC_REQUIRED",
          message: "La synchronisation doit être effectuée par la page",
        });
      });
    });
  }
});
