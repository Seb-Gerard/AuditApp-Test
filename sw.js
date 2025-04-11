/// <reference lib="webworker" />

/* global self */

const PREFIX = "V1.5";
const urlsToCache = [
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
  "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css",
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js",
  "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/webfonts/fa-solid-900.woff2",
  "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/webfonts/fa-solid-900.ttf",
  "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/webfonts/fa-regular-400.woff2",
  "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/webfonts/fa-regular-400.ttf",
  "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/webfonts/fa-brands-400.woff2",
  "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/webfonts/fa-brands-400.ttf",
];

// Fonction pour transformer les chemins relatifs en URLs absolues
function getAbsoluteUrl(relativeUrl) {
  // Obtenir l'origine de l'emplacement du service worker
  const baseUrl = self.location.origin;

  // Si l'URL est déjà absolue, la retourner telle quelle
  if (relativeUrl.startsWith("http://") || relativeUrl.startsWith("https://")) {
    return relativeUrl;
  }

  // S'assurer que l'URL relative commence par /
  const normalizedPath = relativeUrl.startsWith("/")
    ? relativeUrl
    : "/" + relativeUrl;

  return baseUrl + normalizedPath;
}

// Ressources locales de base à mettre en cache
const localResources = [
  "/Audit/offline.php",
  "/Audit/index.php?action=articles",
  "/Audit/index.php?action=audits",
  "/Audit/public/assets/css/style.css",
  "/Audit/public/assets/img/Logo_CNPP_250.jpg",
  "/Audit/public/assets/img/Logo_CNPP_192.png",
  "/Audit/public/assets/img/screenshot-wide.png",
  "/Audit/public/assets/img/CNPP_logotype_blanc.png",
  "/Audit/public/assets/js/db.js",
  "/Audit/public/assets/js/auditdb.js",
  "/Audit/public/assets/js/offline-manager.js",
  "/Audit/public/assets/js/script.js",
  "/Audit/manifest.json",
];

// Ajout d'une fonction pour vérifier si une URL est un fichier PHP direct
function isDirectPhpFile(url) {
  try {
    let urlObj;

    // Traiter différemment les URLs relatives et absolues
    if (url.startsWith("http://") || url.startsWith("https://")) {
      // URL absolue, on peut la construire directement
      urlObj = new URL(url);
    } else {
      // URL relative, on doit la combiner avec l'origine du service worker
      const baseUrl = self.location.origin;
      urlObj = new URL(url, baseUrl);
    }

    const path = urlObj.pathname;
    return (
      path.endsWith(".php") &&
      (path.includes("/includes/") ||
        path.includes("/config/") ||
        path.includes("/controllers/"))
    );
  } catch (e) {
    console.error(
      "[Service Worker] Erreur lors de l'analyse de l'URL:",
      url,
      e
    );
    // En cas d'erreur, on suppose que ce n'est pas un fichier PHP direct
    return false;
  }
}

// Cache dynamique pour stocker les URLs visitées
const DYNAMIC_CACHE = "dynamic-v1";

// Fonction pour mettre en cache une URL dynamiquement
async function cacheUrl(url, cache) {
  // Ignorer les URLs null/undefined ou les extensions de navigateur
  if (!url || url.includes("extension://")) return null;

  try {
    console.log("[Service Worker] Tentative de mise en cache dynamique:", url);
    let response;

    // Vérifier si l'URL est valide pour la mise en cache
    try {
      const urlObj = new URL(url);

      // Ne pas mettre en cache les extensions de navigateur ou les URLs non-HTTP(S)
      if (!urlObj.protocol.startsWith("http")) {
        console.log("[Service Worker] URL ignorée (non HTTP/HTTPS):", url);
        return null;
      }
    } catch (urlError) {
      console.error("[Service Worker] URL invalide:", url);
      return null;
    }

    // Utiliser fetch avec les paramètres appropriés
    if (
      url.includes("cdn.jsdelivr.net") ||
      url.includes("cdnjs.cloudflare.com")
    ) {
      response = await fetch(url, {
        mode: "cors",
        credentials: "omit",
      });
    } else {
      response = await fetch(url, {
        mode: "same-origin",
        credentials: "same-origin",
      });
    }

    if (response && response.ok) {
      const clonedResponse = response.clone();
      await cache.put(url, clonedResponse);
      console.log("[Service Worker] URL mise en cache dynamique:", url);
      return response;
    }
    return null;
  } catch (error) {
    console.error(
      "[Service Worker] Erreur de mise en cache dynamique:",
      url,
      error
    );
    return null;
  }
}

self.addEventListener("install", (event) => {
  // @ts-ignore
  self.skipWaiting();
  console.log("[Service Worker] Installé avec succès");
  event.waitUntil(
    (async () => {
      const cache = await caches.open(PREFIX);
      console.log("[Service Worker] Mise en cache des ressources");

      // D'abord mettre en cache les ressources CDN
      for (const url of urlsToCache) {
        try {
          console.log("[Service Worker] Tentative de mise en cache CDN:", url);

          const response = await fetch(url, {
            mode: "cors",
            credentials: "omit",
          });

          if (response.ok) {
            await cache.put(url, response);
            console.log(
              "[Service Worker] Ressource CDN mise en cache avec succès:",
              url
            );
          } else {
            console.warn(
              "[Service Worker] Échec de mise en cache CDN:",
              url,
              response.status
            );
          }
        } catch (error) {
          console.error(
            "[Service Worker] Erreur lors de la mise en cache CDN:",
            url,
            error
          );
        }
      }

      // Ensuite mettre en cache les ressources locales avec des URLs absolues
      const failedResources = [];

      for (const relativeUrl of localResources) {
        try {
          // Vérifier si c'est un fichier PHP direct qu'on ne devrait pas mettre en cache
          if (isDirectPhpFile(relativeUrl)) {
            console.log(
              "[Service Worker] Ignoring direct PHP file from cache list:",
              relativeUrl
            );
            continue;
          }

          const absoluteUrl = getAbsoluteUrl(relativeUrl);
          console.log(
            "[Service Worker] Tentative de mise en cache locale:",
            absoluteUrl
          );

          const response = await fetch(absoluteUrl, {
            mode: "same-origin",
            credentials: "same-origin",
            headers: {
              "Cache-Control": "no-cache",
              Pragma: "no-cache",
            },
          });

          if (response.ok) {
            await cache.put(relativeUrl, response);
            console.log(
              "[Service Worker] Ressource locale mise en cache avec succès:",
              relativeUrl
            );
          } else {
            console.warn(
              "[Service Worker] Échec de mise en cache locale:",
              relativeUrl,
              response.status
            );
            failedResources.push({ url: relativeUrl, status: response.status });
          }
        } catch (error) {
          console.error(
            "[Service Worker] Erreur lors de la mise en cache locale:",
            relativeUrl,
            error
          );
          failedResources.push({ url: relativeUrl, error: error.message });
        }
      }

      // Créer ou ouvrir le cache dynamique
      await caches.open(DYNAMIC_CACHE);

      if (failedResources.length > 0) {
        console.warn(
          "[Service Worker] Ressources non mises en cache:",
          failedResources
        );
      } else {
        console.log(
          "[Service Worker] Toutes les ressources ont été mises en cache avec succès"
        );
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
          // Ne pas supprimer le cache dynamique
          if (!key.includes(PREFIX) && key !== DYNAMIC_CACHE) {
            console.log("[Service Worker] Suppression de l'ancien cache:", key);
            return caches.delete(key);
          }
        })
      );
    })()
  );
});

self.addEventListener("fetch", (event) => {
  const url = new URL(event.request.url);
  console.log(
    "[Service Worker] Intercepting request:",
    url.pathname + url.search
  );

  // Gérer les requêtes POST de synchronisation spécifiques
  if (event.request.method === "POST") {
    // Vérifier si c'est une requête de synchronisation d'audit
    const isSyncRequest =
      url.pathname.includes("/index.php") &&
      url.searchParams.has("action") &&
      (url.searchParams.get("action") === "audits" ||
        url.searchParams.get("action") === "articles") &&
      url.searchParams.has("method") &&
      (url.searchParams.get("method") === "evaluerPoint" ||
        url.searchParams.get("method") === "create");

    if (isSyncRequest) {
      console.log(
        "[Service Worker] Autorisation de requête POST de synchronisation:",
        url.pathname
      );
      return; // Laisser passer la requête au réseau sans interception
    }

    // Vérifier si c'est une requête avec un paramètre de synchronisation
    if (url.searchParams.has("_t")) {
      console.log(
        "[Service Worker] Autorisation de requête POST avec timestamp:",
        url.pathname
      );
      return; // Laisser passer les requêtes avec timestamp (probablement des synchronisations)
    }

    console.log("[Service Worker] Ignoring POST request:", url.pathname);
    return;
  }

  // Ne pas intercepter les requêtes AJAX ou JSON
  if (
    event.request.headers.get("X-Requested-With") === "XMLHttpRequest" ||
    event.request.headers.get("Accept")?.includes("application/json") ||
    (url.searchParams.has("format") &&
      url.searchParams.get("format") === "json")
  ) {
    console.log("[Service Worker] Ignoring AJAX/JSON request:", url.pathname);
    return;
  }

  // Ne pas intercepter les fichiers PHP directs (header.php, footer.php, etc.)
  if (isDirectPhpFile(url.href)) {
    console.log("[Service Worker] Ignoring direct PHP file:", url.pathname);
    return;
  }

  // Ne pas intercepter les requêtes de test de connexion
  if (url.searchParams.has("test_connection")) {
    return;
  }

  // Détecter les requêtes d'audit avec ID qui doivent être cachées
  const isAuditWithId =
    url.pathname.includes("/index.php") &&
    url.searchParams.has("action") &&
    url.searchParams.get("action") === "audits" &&
    url.searchParams.has("id");

  if (isAuditWithId) {
    console.log(
      `[Service Worker] Détection d'une requête d'audit avec ID:`,
      url.href
    );
    event.respondWith(
      (async () => {
        try {
          // Essayer d'abord le réseau
          console.log(
            "[Service Worker] Tentative réseau pour l'audit:",
            url.href
          );

          try {
            const response = await fetch(event.request);
            if (response.ok) {
              // Mettre à jour le cache avec la nouvelle version
              const cache = await caches.open(PREFIX);
              const clonedResponse = response.clone();
              await cache.put(event.request, clonedResponse);
              console.log(
                "[Service Worker] Audit mis à jour dans le cache:",
                url.href
              );
              return response;
            }
            throw new Error(`Echec de récupération: ${response.status}`);
          } catch (networkError) {
            console.log(
              "[Service Worker] Réseau indisponible, utilisation du cache"
            );
            // Si le réseau échoue, utiliser le cache
            const cacheResponse = await caches.match(event.request);
            if (cacheResponse) {
              return cacheResponse;
            }
            // Si pas en cache non plus, retourner une erreur
            return new Response(
              JSON.stringify({ error: "Donnée d'audit non disponible" }),
              { headers: { "Content-Type": "application/json" } }
            );
          }
        } catch (error) {
          console.error("[Service Worker] Erreur:", error);
          return new Response(JSON.stringify({ error: "Erreur interne" }), {
            headers: { "Content-Type": "application/json" },
          });
        }
      })()
    );
    return;
  }

  // Gérer les polices web en particulier
  if (url.pathname.match(/\.(woff|woff2|ttf|eot)$/)) {
    event.respondWith(
      (async () => {
        try {
          // Traiter les polices Font Awesome spécifiquement
          if (url.pathname.includes("font-awesome")) {
            const fixedUrl = `https://cdnjs.cloudflare.com${url.pathname}`;
            console.log(
              "[Service Worker] Traitement spécial pour Font Awesome:",
              fixedUrl
            );

            // Vérifier d'abord le cache
            const cache = await caches.open(PREFIX);
            let cachedResponse = await cache.match(fixedUrl);

            if (cachedResponse) {
              console.log(
                "[Service Worker] Police Font Awesome servie depuis le cache:",
                fixedUrl
              );
              return cachedResponse;
            }

            // Si non trouvée, essayer de la récupérer et la mettre en cache
            try {
              const fontResponse = await fetch(fixedUrl, {
                credentials: "omit",
                mode: "cors",
              });

              if (fontResponse.ok) {
                const clonedResponse = fontResponse.clone();
                cache.put(fixedUrl, clonedResponse);
                console.log(
                  "[Service Worker] Police Font Awesome mise en cache:",
                  fixedUrl
                );
                return fontResponse;
              }
            } catch (fontError) {
              console.error(
                "[Service Worker] Erreur lors de la récupération de la police Font Awesome:",
                fontError
              );
            }
          }

          // Continuer avec le traitement normal pour les autres polices
          const cache = await caches.open(PREFIX);
          const cachedResponse = await cache.match(event.request);

          if (cachedResponse) {
            console.log(
              "[Service Worker] Police servie depuis le cache:",
              url.pathname
            );
            return cachedResponse;
          }

          try {
            const networkResponse = await fetch(event.request, {
              credentials: "same-origin",
              mode: "cors",
            });

            if (networkResponse.ok) {
              cache.put(event.request, networkResponse.clone());
              return networkResponse;
            }
          } catch (fontNetworkError) {
            console.error(
              "[Service Worker] Erreur réseau pour la police:",
              url.pathname,
              fontNetworkError
            );
          }

          // Renvoyer une réponse vide mais valide pour éviter les erreurs dans la console
          return new Response("", {
            status: 200,
            headers: { "Content-Type": "font/woff2" },
          });
        } catch (error) {
          console.error(
            "[Service Worker] Erreur lors du traitement des polices:",
            error
          );
          return new Response("", { status: 200 });
        }
      })()
    );
    return;
  }

  // Gérer toutes les autres ressources statiques (images, CSS, JS)
  if (url.pathname.match(/\.(jpg|jpeg|png|gif|svg|ico|css|js|json)$/)) {
    event.respondWith(
      (async () => {
        try {
          // Essayer d'abord le cache
          const cache = await caches.open(PREFIX);
          const cachedResponse = await cache.match(event.request);
          if (cachedResponse) {
            console.log(
              "[Service Worker] Ressource servie depuis le cache:",
              url.pathname
            );
            return cachedResponse;
          }

          // Si pas en cache, essayer le réseau
          try {
            console.log(
              "[Service Worker] Ressource non trouvée en cache, tentative réseau:",
              url.pathname
            );
            const networkResponse = await fetch(event.request, {
              credentials: "same-origin",
            });

            if (networkResponse.ok) {
              // Mettre en cache une copie de la réponse
              const clonedResponse = networkResponse.clone();
              cache
                .put(event.request, clonedResponse)
                .catch((error) =>
                  console.error(
                    "[Service Worker] Erreur lors de la mise en cache:",
                    url.pathname,
                    error
                  )
                );

              console.log(
                "[Service Worker] Ressource récupérée du réseau et mise en cache:",
                url.pathname
              );
              return networkResponse;
            } else {
              console.warn(
                "[Service Worker] Ressource non disponible (réseau):",
                url.pathname,
                networkResponse.status
              );
              throw new Error(
                `Ressource non disponible: ${networkResponse.status}`
              );
            }
          } catch (networkError) {
            console.error(
              "[Service Worker] Erreur réseau pour:",
              url.pathname,
              networkError
            );

            if (url.pathname.match(/\.(css|js)$/)) {
              // Pour CSS et JS, essayer de retourner une version vide mais valide
              if (url.pathname.endsWith(".css")) {
                return new Response("/* Ressource CSS non disponible */", {
                  status: 200,
                  headers: { "Content-Type": "text/css" },
                });
              } else if (url.pathname.endsWith(".js")) {
                return new Response("// Ressource JavaScript non disponible", {
                  status: 200,
                  headers: { "Content-Type": "application/javascript" },
                });
              }
            }

            // Pour les images, retourner une erreur
            return new Response("Ressource non disponible", { status: 503 });
          }
        } catch (error) {
          console.error(
            "[Service Worker] Erreur lors de la récupération de la ressource:",
            url.pathname,
            error
          );
          return new Response(
            "Erreur lors de la récupération de la ressource",
            {
              status: 503,
            }
          );
        }
      })()
    );
    return;
  }

  // Gérer les requêtes API (y compris les audits)
  if (
    event.request.method === "GET" &&
    url.searchParams.has("action") &&
    (url.searchParams.get("action") === "audits" ||
      url.searchParams.get("action") === "articles")
  ) {
    // Vérifier si c'est une requête de données dynamiques (getSous*, get*)
    const methodParam = url.searchParams.get("method");
    const isDynamicDataRequest =
      methodParam !== null &&
      (methodParam.startsWith("getSous") || methodParam.startsWith("get"));

    event.respondWith(
      (async () => {
        try {
          // Pour les requêtes de données dynamiques, essayer d'abord le réseau
          if (isDynamicDataRequest) {
            console.log(
              "[Service Worker] Requête de données dynamiques, priorité au réseau:",
              url.href
            );
            try {
              const networkResponse = await fetch(event.request);
              if (networkResponse.ok) {
                // Mettre à jour le cache avec la nouvelle réponse
                const dynamicCache = await caches.open(DYNAMIC_CACHE);
                const clonedResponse = networkResponse.clone();
                await dynamicCache.put(event.request, clonedResponse);
                console.log(
                  "[Service Worker] Données dynamiques récupérées du réseau et mises en cache:",
                  url.href
                );
                return networkResponse;
              }
              throw new Error(
                `Échec de récupération depuis le réseau: ${networkResponse.status}`
              );
            } catch (networkError) {
              console.log(
                "[Service Worker] Réseau indisponible pour les données dynamiques, utilisation du cache"
              );
              // Si le réseau échoue, essayer le cache comme solution de secours
              const cacheResponse = await caches.match(event.request);
              if (cacheResponse) {
                return cacheResponse;
              }
              // Si non trouvé dans le cache, renvoyer un tableau vide
              return new Response(JSON.stringify([]), {
                headers: { "Content-Type": "application/json" },
              });
            }
          }

          // Pour les autres requêtes API, continuer avec la stratégie "Cache First"
          // Vérifier d'abord dans le cache
          const cacheResponse = await caches.match(event.request);
          if (cacheResponse) {
            console.log(
              "[Service Worker] Réponse API trouvée dans le cache:",
              url.href
            );
            return cacheResponse;
          }

          // Sinon, essayer de récupérer depuis le réseau
          try {
            console.log(
              "[Service Worker] Tentative de récupération API depuis le réseau:",
              url.href
            );
            const networkResponse = await fetch(event.request);

            if (networkResponse.ok) {
              // Stocker dans le cache dynamique
              const dynamicCache = await caches.open(DYNAMIC_CACHE);
              const clonedResponse = networkResponse.clone();
              await dynamicCache.put(event.request, clonedResponse);
              console.log(
                "[Service Worker] Réponse API mise en cache dynamique:",
                url.href
              );
              return networkResponse;
            }

            throw new Error(
              `Echec de récupération API: ${networkResponse.status}`
            );
          } catch (networkError) {
            console.error(
              "[Service Worker] Erreur réseau pour l'API:",
              url.href,
              networkError
            );

            // Renvoyer une réponse vide mais valide si offline
            return new Response(JSON.stringify([]), {
              headers: { "Content-Type": "application/json" },
            });
          }
        } catch (error) {
          console.error(
            "[Service Worker] Erreur lors du traitement de la requête API:",
            error
          );
          return new Response(JSON.stringify({ error: "Erreur interne" }), {
            headers: { "Content-Type": "application/json" },
          });
        }
      })()
    );
    return;
  }

  // Gérer les requêtes de navigation (les pages)
  if (event.request.mode === "navigate") {
    event.respondWith(
      (async () => {
        try {
          // Vérifier d'abord dans le cache
          const cachedResponse = await caches.match(event.request);
          if (cachedResponse) {
            console.log(
              "[Service Worker] Page trouvée dans le cache:",
              url.href
            );
            return cachedResponse;
          }

          // Sinon, essayer de récupérer depuis le réseau
          try {
            console.log(
              "[Service Worker] Tentative de navigation réseau:",
              url.href
            );
            const response = await fetch(event.request);

            if (response.ok) {
              // Mettre en cache dynamique les pages visitées
              const dynamicCache = await caches.open(DYNAMIC_CACHE);
              const clonedResponse = response.clone();
              await dynamicCache.put(event.request, clonedResponse);
              console.log(
                "[Service Worker] Page mise en cache dynamique:",
                url.href
              );
              return response;
            }
          } catch (error) {
            console.log(
              "[Service Worker] Navigation fetch failed, serving offline page",
              error
            );
          }

          // Essayer de servir offline.php depuis le cache
          const cache = await caches.open(PREFIX);
          const offlineResponse = await cache.match("/Audit/offline.php");

          if (offlineResponse) {
            console.log("[Service Worker] Serving offline.php from cache");
            return offlineResponse;
          }

          // Si offline.php n'est pas dans le cache, essayer de le récupérer
          try {
            console.log("[Service Worker] Attempting to fetch offline.php");
            const response = await fetch("/Audit/offline.php");
            if (response.ok) {
              await cache.put("/Audit/offline.php", response.clone());
              console.log("[Service Worker] Successfully cached offline.php");
              return response;
            }
          } catch (offlineError) {
            console.error(
              "[Service Worker] Failed to fetch offline.php",
              offlineError
            );
          }

          // Fallback basique si tout échoue
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
        } catch (error) {
          console.error("[Service Worker] Erreur de navigation:", error);
          return new Response("Erreur de chargement de la page", {
            status: 500,
          });
        }
      })()
    );
    return;
  }

  // Gérer les autres requêtes (ressources statiques)
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
        if (url.pathname.match(/\.(css|js|jpg|png|svg|ico|json|php)$/)) {
          await cache.put(event.request, response.clone());
        }
        return response;
      } catch (error) {
        return new Response("", { status: 503 });
      }
    })()
  );
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
