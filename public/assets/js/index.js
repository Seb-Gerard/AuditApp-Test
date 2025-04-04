// Fichier d'entrée principal pour le JavaScript de l'application

/**
 * Ce fichier sert de point d'entrée principal pour tous les scripts JavaScript
 * de l'application. Dans un environnement de production, on pourrait utiliser
 * un bundler comme Webpack pour optimiser les imports.
 */

// Chargement des différents modules en fonction de la page active
document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM chargé, initialisation des scripts...");

  // Fonctionnalités spécifiques à différentes pages
  if (document.querySelector(".admin-panel")) {
    // Page d'administration
    console.log("Chargement des scripts d'administration");
    loadScript("./public/assets/js/admin.js", function () {
      console.log("Script d'administration chargé");
    });
  }

  if (document.querySelector(".audit-page")) {
    // Page d'audit
    console.log("Chargement des scripts d'audit");
    loadScript("./public/assets/js/audit_manager.js", function () {
      console.log("Script d'audit manager chargé");
      // Initialiser AuditManager si disponible
      if (typeof AuditManager !== "undefined") {
        console.log("Initialisation d'AuditManager depuis index.js");
        AuditManager.init();
      }
    });
  }

  // NOTE: Les scripts pour la page d'articles sont maintenant chargés directement dans le HTML
  // pour éviter les problèmes de dépendances et de timing
});

/**
 * Fonction utilitaire pour charger dynamiquement un script JavaScript
 * @param {string} url - L'URL du script à charger
 * @param {Function|null} callback - Fonction à exécuter une fois le script chargé (optionnel)
 */
function loadScript(url, callback) {
  console.log(`Chargement du script: ${url}`);

  // Vérifier si le script est déjà chargé
  const existingScript = document.querySelector(`script[src="${url}"]`);
  if (existingScript) {
    console.log(`Le script ${url} est déjà chargé`);
    if (callback && typeof callback === "function") {
      callback();
    }
    return;
  }

  const script = document.createElement("script");
  script.src = url;

  script.onerror = function (e) {
    console.error(`Erreur lors du chargement du script ${url}:`, e);

    // Mettre à jour la section de débogage
    const debugOutput = document.getElementById("debug-output");
    if (debugOutput) {
      debugOutput.innerHTML += `<p style='color:red;'>Erreur lors du chargement de ${url}</p>`;
    }
  };

  if (callback && typeof callback === "function") {
    script.onload = function () {
      console.log(`Script chargé avec succès: ${url}`);
      callback();
    };
  }

  document.head.appendChild(script);
}

// Initialisation du service worker (s'il est supporté)
if ("serviceWorker" in navigator) {
  window.addEventListener("load", () => {
    navigator.serviceWorker
      .register("./sw.js")
      .then((registration) => {
        console.log("Service Worker enregistré avec succès:", registration);
      })
      .catch((error) => {
        console.log("Échec de l'enregistrement du Service Worker:", error);
      });
  });
}
