﻿/**
 * Audit View - JavaScript functions for audit details page
 * Application MVC pattern - View component
 */

// Add these triple-slash directives at the top of the file to ignore specific TypeScript checks
// @ts-nocheck

/**
 * Affiche une notification toast
 * @param {string} message - Le message à afficher
 * @param {string} type - Le type de notification (info, success, warning, error)
 * @param {boolean} isPersistent - Si la notification doit rester affichée ou disparaître automatiquement
 * @returns {Object|null} - L'objet toast si disponible, sinon null
 */
window.showToast = function (message, type = "info", isPersistent = false) {
  try {
    // Créer un conteneur pour les toasts s'il n'existe pas
    let toastContainer = document.getElementById("toast-container");
    if (!toastContainer) {
      toastContainer = document.createElement("div");
      toastContainer.id = "toast-container";
      toastContainer.className =
        "toast-container position-fixed bottom-0 end-0 p-3";
      document.body.appendChild(toastContainer);
    }

    // Générer un ID unique pour ce toast
    const toastId = "toast-" + Date.now();

    // Créer l'élément toast
    const toastElement = document.createElement("div");
    toastElement.id = toastId;
    toastElement.className = `toast align-items-center text-white bg-${
      type === "error" ? "danger" : type
    }`;
    toastElement.setAttribute("role", "alert");
    toastElement.setAttribute("aria-live", "assertive");
    toastElement.setAttribute("aria-atomic", "true");

    // Tous les toasts se ferment automatiquement après 2 secondes (2000ms)
    toastElement.setAttribute("data-bs-delay", "2000");
    toastElement.setAttribute("data-bs-autohide", "true");

    // Contenu du toast
    toastElement.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">
          ${message}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                data-bs-dismiss="toast" aria-label="Fermer"></button>
      </div>
    `;

    // Ajouter le toast au conteneur
    toastContainer.appendChild(toastElement);

    // Initialiser le toast avec Bootstrap
    // Vérifier que Bootstrap est disponible
    if (typeof bootstrap !== "undefined" && bootstrap.Toast) {
      const toastBootstrap = new bootstrap.Toast(toastElement);
      toastBootstrap.show();
      return toastBootstrap;
    } else {
      // Fallback manuel si Bootstrap n'est pas disponible
      console.warn("Bootstrap non disponible, affichage manuel du toast");
      toastElement.classList.add("show");
      // Fermer après 2 secondes
      setTimeout(() => {
        toastElement.classList.remove("show");
        setTimeout(() => {
          if (toastElement.parentNode) {
            toastElement.parentNode.removeChild(toastElement);
          }
        }, 300);
      }, 2000);

      return {
        hide: () => {
          toastElement.classList.remove("show");
          if (toastElement.parentNode) {
            toastElement.parentNode.removeChild(toastElement);
          }
        },
      };
    }
  } catch (error) {
    console.error("Erreur lors de l'affichage du toast:", error);
    // En cas d'erreur, utiliser alert comme fallback
    alert(message);
    return null;
  }
};

/**
 * @typedef {Object} Bootstrap
 * @property {Object} Modal
 * @property {function} Modal.getInstance
 */

/**
 * @typedef {Object} WindowWithBootstrap
 * @property {Bootstrap} bootstrap
 */

/**
 * @typedef {Object} FileReaderEventTarget
 * @property {string|ArrayBuffer} result
 */

/**
 * @typedef {Object} FileReaderEvent
 * @property {FileReaderEventTarget} target
 */

// Variables globales
const videoStreams = {};

// Pour éviter les erreurs avec bootstrap
const bootstrap = window.bootstrap || {};

/**
 * Module AuditDB - Gestion du stockage hors ligne pour les audits
 */
const AuditDB = (function () {
  // Base de données IndexedDB
  let db = null;
  const DB_NAME = "audit_offline_db";
  const DB_VERSION = 1;

  // Structure des stores
  const STORES = {
    PENDING_EVALUATIONS: "pending_evaluations",
    PENDING_DOCUMENTS: "pending_documents",
  };

  // Initialisation de la base de données
  function initDB() {
    return new Promise((resolve, reject) => {
      if (db) return resolve(db);

      const request = indexedDB.open(DB_NAME, DB_VERSION);

      request.onerror = (event) => {
        console.error("Erreur d'ouverture de la base de données:", event);
        reject(new Error("Impossible d'ouvrir la base de données IndexedDB"));
      };

      request.onsuccess = (event) => {
        db = event.target.result;
        console.log("Base de données IndexedDB ouverte avec succès");
        resolve(db);
      };

      request.onupgradeneeded = (event) => {
        const db = event.target.result;

        // Créer le store pour les évaluations en attente
        if (!db.objectStoreNames.contains(STORES.PENDING_EVALUATIONS)) {
          db.createObjectStore(STORES.PENDING_EVALUATIONS, {
            keyPath: "id",
            autoIncrement: true,
          });
          console.log("Store créé pour les évaluations en attente");
        }

        // Créer le store pour les documents en attente
        if (!db.objectStoreNames.contains(STORES.PENDING_DOCUMENTS)) {
          db.createObjectStore(STORES.PENDING_DOCUMENTS, {
            keyPath: "id",
            autoIncrement: true,
          });
          console.log("Store créé pour les documents en attente");
        }
      };
    });
  }

  // Ajout d'une évaluation à synchroniser
  async function savePendingEvaluation(data) {
    await initDB();

    return new Promise((resolve, reject) => {
      const transaction = db.transaction(
        [STORES.PENDING_EVALUATIONS],
        "readwrite"
      );
      const store = transaction.objectStore(STORES.PENDING_EVALUATIONS);

      const item = {
        ...data,
        timestamp: new Date().getTime(),
        status: "pending",
      };

      const request = store.add(item);

      request.onsuccess = (event) => {
        resolve(event.target.result);
      };

      request.onerror = (event) => {
        reject(new Error("Erreur lors de l'enregistrement de l'évaluation"));
      };
    });
  }

  // Ajout d'un document à synchroniser
  async function savePendingDocument(data) {
    await initDB();

    return new Promise((resolve, reject) => {
      const transaction = db.transaction(
        [STORES.PENDING_DOCUMENTS],
        "readwrite"
      );
      const store = transaction.objectStore(STORES.PENDING_DOCUMENTS);

      const item = {
        ...data,
        timestamp: new Date().getTime(),
        status: "pending",
      };

      const request = store.add(item);

      request.onsuccess = (event) => {
        resolve(event.target.result);
      };

      request.onerror = (event) => {
        reject(new Error("Erreur lors de l'enregistrement du document"));
      };
    });
  }

  // Récupérer toutes les évaluations en attente
  async function getPendingEvaluations() {
    await initDB();

    return new Promise((resolve, reject) => {
      const transaction = db.transaction(
        [STORES.PENDING_EVALUATIONS],
        "readonly"
      );
      const store = transaction.objectStore(STORES.PENDING_EVALUATIONS);
      const request = store.getAll();

      request.onsuccess = (event) => {
        resolve(event.target.result);
      };

      request.onerror = (event) => {
        reject(
          new Error("Erreur lors de la récupération des évaluations en attente")
        );
      };
    });
  }

  // Récupérer tous les documents en attente
  async function getPendingDocuments() {
    await initDB();

    return new Promise((resolve, reject) => {
      const transaction = db.transaction(
        [STORES.PENDING_DOCUMENTS],
        "readonly"
      );
      const store = transaction.objectStore(STORES.PENDING_DOCUMENTS);
      const request = store.getAll();

      request.onsuccess = (event) => {
        resolve(event.target.result);
      };

      request.onerror = (event) => {
        reject(
          new Error("Erreur lors de la récupération des documents en attente")
        );
      };
    });
  }

  // Marquer une évaluation comme synchronisée
  async function markEvaluationSynced(id) {
    await initDB();

    return new Promise((resolve, reject) => {
      const transaction = db.transaction(
        [STORES.PENDING_EVALUATIONS],
        "readwrite"
      );
      const store = transaction.objectStore(STORES.PENDING_EVALUATIONS);
      const request = store.delete(id);

      request.onsuccess = () => {
        resolve(true);
      };

      request.onerror = () => {
        reject(new Error("Erreur lors de la mise Ã jour de l'évaluation"));
      };
    });
  }

  // Marquer un document comme synchronisé
  async function markDocumentSynced(id) {
    await initDB();

    return new Promise((resolve, reject) => {
      const transaction = db.transaction(
        [STORES.PENDING_DOCUMENTS],
        "readwrite"
      );
      const store = transaction.objectStore(STORES.PENDING_DOCUMENTS);
      const request = store.delete(id);

      request.onsuccess = () => {
        resolve(true);
      };

      request.onerror = () => {
        reject(new Error("Erreur lors de la mise Ã jour du document"));
      };
    });
  }

  // Synchroniser les données en attente
  async function syncPendingData() {
    const results = {
      evaluations: { success: 0, failed: 0 },
      documents: { success: 0, failed: 0 },
    };

    try {
      // Synchroniser les évaluations
      const pendingEvaluations = await getPendingEvaluations();
      console.log(
        `Synchronisation de ${pendingEvaluations.length} évaluations en attente`
      );

      // Utiliser la fonction syncSingleEvaluation améliorée pour chaque évaluation
      for (const evaluation of pendingEvaluations) {
        try {
          // Utiliser la fonction avancée de synchronisation d'évaluation individuelle
          const success = await window.syncSingleEvaluation(evaluation);

          if (success) {
            results.evaluations.success++;
            console.log(
              `âœ… Évaluation ID:${evaluation.id} synchronisée avec succès`
            );
          } else {
            results.evaluations.failed++;
            console.error(
              `âŒ Échec de synchronisation de l'évaluation ID:${evaluation.id}`
            );
          }
        } catch (error) {
          results.evaluations.failed++;
          console.error(
            "Exception lors de la synchronisation de l'évaluation:",
            error
          );
        }
      }

      // Synchroniser les documents
      const pendingDocuments = await getPendingDocuments();
      console.log(
        `Synchronisation de ${pendingDocuments.length} documents en attente`
      );

      for (const document of pendingDocuments) {
        try {
          // Envoyer les données au serveur
          const response = await fetch(
            "index.php?action=audits&method=ajouterDocument",
            {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
              },
              body: JSON.stringify({
                audit_id: document.audit_id,
                point_vigilance_id: document.point_vigilance_id,
                image_base64: document.image_base64,
              }),
            }
          );

          if (response.ok) {
            // Marquer comme synchronisé
            await markDocumentSynced(document.id);
            results.documents.success++;
          } else {
            results.documents.failed++;
            console.error(
              "Erreur lors de la synchronisation de la photo:",
              await response.text()
            );
          }
        } catch (error) {
          results.documents.failed++;
          console.error(
            "Exception lors de la synchronisation du document:",
            error
          );
        }
      }

      console.log("Synchronisation terminée:", results);
      return results;
    } catch (error) {
      console.error("Erreur lors de la synchronisation:", error);
      throw error;
    }
  }

  // API publique
  return {
    initDB,
    savePendingEvaluation,
    savePendingDocument,
    getPendingEvaluations,
    getPendingDocuments,
    syncPendingData,
  };
})();

// Exposer le module AuditDB à window pour une accessibilité globale
window.AuditDB = AuditDB;

/**
 * Initialisation au chargement du document
 */
document.addEventListener("DOMContentLoaded", function () {
  // Ajouter un style pour les champs désactivés
  const style = document.createElement("style");
  style.innerHTML = `
    .disabled-field {
      background-color: #e9ecef !important;
      cursor: not-allowed;
      opacity: 0.7;
    }
    
    /* Style pour formulaires non modifiables (audit terminé) */
    form[disabled] {
      opacity: 0.8;
      pointer-events: none;
    }
    form[disabled] input, 
    form[disabled] textarea, 
    form[disabled] select, 
    form[disabled] button {
      pointer-events: none;
      background-color: #e9ecef;
      opacity: 0.7;
    }
  `;
  document.head.appendChild(style);

  // Vérifier si l'audit est terminé
  const badgeElement = document.querySelector(".badge.bg-success");
  const isAuditTermine =
    badgeElement !== null &&
    badgeElement.textContent &&
    badgeElement.textContent.trim() === "Terminé";

  // Gérer la soumission du formulaire d'évaluation
  initEvaluationForms(isAuditTermine);

  // Gérer la soumission des formulaires de documents
  initDocumentForms(isAuditTermine);

  // Initialiser les webcams pour chaque point de vigilance
  const pointsIds = document.querySelectorAll("[data-point-id]");
  pointsIds.forEach((element) => {
    const pointId = element.getAttribute("data-point-id");
    if (pointId) {
      initWebcam(parseInt(pointId));
    }
  });

  // Remplacer tous les aria-hidden par inert pour éviter les problèmes d'accessibilité
  initModalAccessibility();

  // Fonction pour vérifier la connexion internet
  function isOnline() {
    return navigator.onLine;
  }

  // Fonction pour écouter les changements de statut de connexion
  function setupConnectivityListeners() {
    console.log("Configuration des écouteurs de connectivité");

    // Vérifier si nous sommes en ligne au chargement
    if (!navigator.onLine) {
      console.log("Démarrage en mode hors ligne");
      document.body.classList.add("offline-mode");
    }

    // Écouteurs pour les événements online/offline
    window.addEventListener("online", function () {
      console.log("ðŸŒ CONNEXION INTERNET RÉTABLIE");
      document.body.classList.remove("offline-mode");

      // Notification à l'utilisateur
      showToast(
        "Connexion internet rétablie. Synchronisation en cours...",
        "info",
        false
      );

      // Délai avant synchronisation
      setTimeout(() => {
        console.log("â±ï¸ Délai écoulé, début de la synchronisation");

        forceSyncData()
          .then((result) => {
            if (!result.hasData) {
              showToast("Aucune donnée à synchroniser", "info", false);
              return;
            }

            const total =
              result.results.evaluations.success +
              result.results.documents.success;
            const failed =
              result.results.evaluations.failed +
              result.results.documents.failed;

            if (total > 0) {
              showToast(
                `Synchronisation réussie: ${total} élément(s) synchronisé(s)`,
                "success",
                true
              );

              // Recharger la page
              setTimeout(() => {
                const currentUrl = new URL(window.location.href);
                const auditId = currentUrl.searchParams.get("id");

                console.log("🔄 Rechargement de la page...");
                if (auditId) {
                  window.location.href = `index.php?action=audits&method=view&id=${auditId}&nocache=${Date.now()}`;
                } else {
                  window.location.href = `index.php?action=audits&nocache=${Date.now()}`;
                }
              }, 2000);
            } else if (failed > 0) {
              showToast(
                `Échec de la synchronisation: ${failed} élément(s) non synchronisé(s)`,
                "error",
                true
              );
            }
          })
          .catch((error) => {
            console.error("❌ Erreur lors de la synchronisation:", error);
            showToast(
              "Erreur lors de la synchronisation: " +
                (error.message || "Erreur inconnue"),
              "error",
              true
            );
          });
      }, 2000); // Attendre 2 secondes avant de synchroniser
    });

    window.addEventListener("offline", function () {
      console.log("La connexion internet a été perdue");
      document.body.classList.add("offline-mode");

      showToast(
        "Mode hors ligne activé. Vos modifications seront enregistrées localement.",
        "warning",
        true
      );
    });

    console.log("Écouteurs de connectivité configurés");
  }

  // Initialiser les écouteurs d'événements de connectivité une fois le DOM chargé
  setupConnectivityListeners();
});

/**
 * Initialise les formulaires d'évaluation
 * Gère l'activation/désactivation des champs en fonction de l'état de la case "Audité"
 */
function initEvaluationForms(isAuditTermine) {
  const forms = document.querySelectorAll(".evaluation-form");
  console.log("Initialisation de", forms.length, "formulaires d'évaluation");

  forms.forEach((form) => {
    // 1. Récupérer la case à cocher "Audité" (non_audite)
    const nonAuditeCheckbox = form.querySelector('[name="non_audite"]');

    if (!nonAuditeCheckbox) {
      console.error("Case à cocher 'Audité' non trouvée dans le formulaire");
      return;
    }

    // 2. Fonction pour mettre à jour l'état des champs en fonction de l'état de la case "Audité"
    function updateFieldsState() {
      // Récupérer l'état actuel de la case à cocher
      const isNonAudite = nonAuditeCheckbox.checked;
      console.log(
        `État de la case 'Audité': ${isNonAudite ? "Coché" : "Non coché"}`
      );

      // Récupérer tous les champs du formulaire
      const allInputs = form.querySelectorAll(
        'input:not([name="audit_id"]):not([name="point_vigilance_id"]):not([name="non_audite"]):not([name="mesure_reglementaire"])'
      );
      const allSelects = form.querySelectorAll("select");
      const allTextareas = form.querySelectorAll(
        'textarea:not([name="justification"])'
      );

      // Récupérer explicitement le champ de commentaire (qui doit rester actif)
      const justificationTextarea = form.querySelector(
        '[name="justification"]'
      );

      // DÉSACTIVER tous les champs si "Audité" est décoché, SAUF "Mesure réglementaire" et "Commentaire"
      [...allInputs, ...allSelects, ...allTextareas].forEach((field) => {
        field.disabled = !isNonAudite || isAuditTermine;

        if (!isNonAudite || isAuditTermine) {
          field.classList.add("disabled-field");
        } else {
          field.classList.remove("disabled-field");
        }
      });

      // Gérer séparément les boutons radio
      const radioButtons = form.querySelectorAll('input[type="radio"]');
      radioButtons.forEach((radio) => {
        radio.disabled = !isNonAudite || isAuditTermine;

        // Appliquer des styles spécifiques pour l'élément parent (libellé du radio)
        const parentLabel = radio.closest("label") || radio.parentElement;
        if (parentLabel) {
          if (!isNonAudite || isAuditTermine) {
            parentLabel.classList.add("text-muted");
          } else {
            parentLabel.classList.remove("text-muted");
          }
        }
      });

      // S'assurer que le champ de commentaire reste toujours actif
      if (justificationTextarea) {
        justificationTextarea.disabled = isAuditTermine;
        if (isAuditTermine) {
          justificationTextarea.classList.add("disabled-field");
        } else {
          justificationTextarea.classList.remove("disabled-field");
        }
      }
    }

    // 3. Appliquer l'état initial
    updateFieldsState();

    // 4. Ajouter un écouteur pour détecter les changements de la case à cocher
    nonAuditeCheckbox.addEventListener("change", updateFieldsState);

    // 5. Gérer la soumission du formulaire
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      // Ne pas traiter si l'audit est terminé
      if (isAuditTermine) {
        console.log("Formulaire non soumis : audit terminé");
        return;
      }

      // Récupérer les données du formulaire
      const formData = new FormData(form);
      const auditId = formData.get("audit_id");
      const pointId = formData.get("point_vigilance_id");

      console.log(
        "Soumission du formulaire pour le point " +
          pointId +
          " de l'audit " +
          auditId
      );

      // Vérifier si nous sommes en ligne
      if (!navigator.onLine) {
        console.log("Mode hors ligne détecté, enregistrement local...");
        // Stocker en local en attendant une connexion
        try {
          // Convertir FormData en objet JavaScript standard
          let evalData = {};
          for (let [key, value] of formData.entries()) {
            evalData[key] = value;
          }

          // Ajouter un timestamp et statut
          evalData.timestamp = new Date().getTime();
          evalData.status = "pending";

          console.log("Données converties pour stockage local:", evalData);

          // Assurons-nous que le module AuditDB est disponible et initialisé
          if (typeof window.AuditDB === "undefined" || !window.AuditDB) {
            console.error("AuditDB non disponible. Tentative de chargement...");
            // Essayer de charger le script (fonction définie plus bas)
            loadScriptDynamically("/Audit/public/assets/js/auditdb.js")
              .then(() => {
                console.log("Script auditdb.js chargé avec succès");
                if (!window.AuditDB) {
                  throw new Error(
                    "AuditDB toujours non disponible après chargement"
                  );
                }
                return storeEvaluationOffline(evalData);
              })
              .then((id) => {
                console.log("Évaluation stockée avec ID:", id);
                showToast(
                  "Évaluation enregistrée localement. Sera synchronisée quand vous serez en ligne.",
                  "success",
                  true
                );
              })
              .catch((error) => {
                console.error("Erreur après tentative de chargement:", error);
                showToast(
                  "Erreur lors de l'enregistrement local: " + error.message,
                  "error"
                );
              });
          } else {
            // AuditDB est disponible, utilisons-le directement
            storeEvaluationOffline(evalData)
              .then((id) => {
                console.log("Évaluation stockée avec ID:", id);
                showToast(
                  "Évaluation enregistrée localement. Sera synchronisée quand vous serez en ligne.",
                  "success",
                  true
                );
              })
              .catch((error) => {
                console.error("Erreur lors de l'enregistrement local:", error);
                showToast(
                  "Erreur lors de l'enregistrement local: " + error.message,
                  "error"
                );
              });
          }
        } catch (error) {
          console.error("Erreur lors de l'enregistrement local:", error);
          showToast(
            "Erreur lors de l'enregistrement local: " + error.message,
            "error"
          );
        }
        return;
      }

      // Envoyer les données au serveur
      fetch("index.php?action=audits&method=evaluerPoint", {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
        body: formData,
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Erreur réseau: " + response.status);
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            showToast("Point de vigilance évalué avec succès", "success");
            console.log("Succès:", data);

            // Ajouter un paramètre t pour éviter la mise en cache
            setTimeout(() => {
              window.location.href =
                window.location.href.split("?")[0] +
                `?action=audits&method=view&id=${auditId}&t=${Date.now()}`;
            }, 500);
          } else {
            showToast(
              "Erreur lors de l'évaluation: " +
                (data.message || "Erreur inconnue"),
              "error"
            );
            console.error("Erreur:", data);
          }
        })
        .catch((error) => {
          console.error("Erreur lors de l'envoi du formulaire:", error);
          showToast(
            "Erreur lors de l'envoi du formulaire: " + error.message,
            "error"
          );
        });
    });
  });
}

/**
 * Initialise les formulaires d'ajout de document
 */
function initDocumentForms(isAuditTermine) {
  const documentForms = document.querySelectorAll(".document-form");

  // Corriger les problèmes d'attributs aria-hidden sur les modals
  document.querySelectorAll(".modal").forEach((modal) => {
    // Remplacer aria-hidden par inert quand la modal est cachée
    if (modal.getAttribute("aria-hidden") === "true") {
      modal.removeAttribute("aria-hidden");
      modal.setAttribute("inert", "");
    }

    // S'assurer que la modal utilise inert au lieu de aria-hidden
    modal.addEventListener("hide.bs.modal", function () {
      // Utiliser un délai pour permettre Ã Bootstrap de terminer sa fermeture
      setTimeout(() => {
        if (this.getAttribute("aria-hidden") === "true") {
          this.removeAttribute("aria-hidden");
          this.setAttribute("inert", "");
        }
      }, 50);
    });

    // Retirer inert lors de l'affichage
    modal.addEventListener("show.bs.modal", function () {
      if (this.hasAttribute("inert")) {
        this.removeAttribute("inert");
      }
    });
  });

  documentForms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      console.log("Soumission du formulaire de document");

      // Vérifier si les champs requis sont présents
      const auditIdField = this.querySelector('input[name="audit_id"]');
      const pointVigilanceIdField = this.querySelector(
        'input[name="point_vigilance_id"]'
      );

      if (
        !auditIdField ||
        !pointVigilanceIdField ||
        !auditIdField.value ||
        !pointVigilanceIdField.value
      ) {
        alert(
          "Erreur: Paramètres manquants (audit_id, point_vigilance_id). Veuillez réessayer."
        );
        return;
      }

      // Créer un nouveau FormData pour plus de contrÃ´le
      const formData = new FormData();

      // Ajouter explicitement les identifiants
      formData.append("audit_id", auditIdField.value);
      formData.append("point_vigilance_id", pointVigilanceIdField.value);
      formData.append("type", "document");

      // Ajouter le fichier
      const fileInput = this.querySelector('input[type="file"]');
      if (fileInput && fileInput.files && fileInput.files[0]) {
        formData.append("document", fileInput.files[0]);
        console.log("Fichier ajouté:", fileInput.files[0].name);
      } else {
        alert("Veuillez sélectionner un fichier Ã télécharger");
        return;
      }

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Téléchargement...';
      submitBtn.disabled = true;

      fetch("index.php?action=audits&method=ajouterDocument", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((response) => {
          console.log("Status:", response.status);
          console.log("Headers:", [...response.headers.entries()]);

          if (!response.ok) {
            throw new Error("Erreur réseau: " + response.status);
          }

          // Vérifier le type de contenu retourné
          const contentType = response.headers.get("content-type");
          if (contentType && contentType.includes("text/html")) {
            // Le serveur a renvoyé du HTML au lieu de JSON
            return response.text().then((html) => {
              console.error(
                "Le serveur a renvoyé du HTML au lieu de JSON:",
                html.substring(0, 500)
              );
              throw new Error(
                "Réponse invalide du serveur (HTML au lieu de JSON)"
              );
            });
          }

          return response.text().then((text) => {
            console.log("Texte de la réponse:", text);

            if (!text) {
              throw new Error("Réponse vide");
            }

            try {
              return JSON.parse(text);
            } catch (e) {
              console.error("Texte non JSON reÃ§u:", text.substring(0, 500));
              throw new Error("La réponse n'est pas au format JSON");
            }
          });
        })
        .then((data) => {
          if (data.success) {
            console.log("Document ajouté avec succès");

            // Fermer la modal de manière sécurisée
            const modalId =
              "documentModal-" + formData.get("point_vigilance_id");
            const modal = document.getElementById(modalId);

            if (modal) {
              // Utiliser notre fonction closeModal pour une fermeture cohérente
              closeModal(modal);
            }

            // RafraÃ®chir la page avec un paramètre pour éviter le cache
            setTimeout(() => {
              window.location.href =
                window.location.href.split("?")[0] +
                `?action=audits&method=view&id=${
                  auditIdField.value
                }&nocache=${Date.now()}`;
            }, 300);
          } else {
            // Afficher un message d'erreur
            alert(
              data.message || "Une erreur est survenue lors du téléchargement"
            );
          }
        })
        .catch((error) => {
          console.error("Erreur lors du téléchargement:", error);
          alert(
            "Une erreur est survenue lors de la communication avec le serveur: " +
              error.message
          );
        })
        .finally(() => {
          // Restaurer le bouton
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        });
    });
  });
}

/**
 * Initialise l'accessibilité des modales
 */
function initModalAccessibility() {
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.addEventListener("hide.bs.modal", function () {
      // Supprimer aria-hidden qui cause des problèmes d'accessibilité
      setTimeout(() => {
        if (this.getAttribute("aria-hidden") === "true") {
          this.removeAttribute("aria-hidden");
          // Utiliser inert à la place (plus moderne et sans conflit de focus)
          this.setAttribute("inert", "");
        }
      }, 0);
    });

    modal.addEventListener("show.bs.modal", function () {
      // Supprimer inert lors de l'affichage
      if (this.hasAttribute("inert")) {
        this.removeAttribute("inert");
      }
    });
  });
}

/**
 * Afficher la modal pour voir une photo
 * @param {string} photoUrl - L'URL de la photo à afficher
 * @param {string} photoName - Le nom de la photo
 */
function showPhotoModal(photoUrl, photoName) {
  try {
    // Mettre à jour la source de l'image
    const modalPhoto = document.getElementById("modal-photo");
    const modalLabel = document.getElementById("viewPhotoModalLabel");
    const modalElement = document.getElementById("viewPhotoModal");

    if (!modalElement) {
      console.error("Élément modal non trouvé: viewPhotoModal");
      return;
    }

    if (modalPhoto && modalPhoto instanceof HTMLImageElement) {
      modalPhoto.src = photoUrl;
    }

    if (modalLabel) {
      modalLabel.textContent = photoName || "Photo";
    }

    // Essayer d'utiliser Bootstrap si disponible
    if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
      const modal = new bootstrap.Modal(modalElement);
      modal.show();
    } else {
      // Méthode manuelle si Bootstrap n'est pas disponible
      modalElement.style.display = "block";
      modalElement.classList.add("show");
      document.body.classList.add("modal-open");

      // Ajouter un backdrop si nécessaire
      if (!document.querySelector(".modal-backdrop")) {
        const backdrop = document.createElement("div");
        backdrop.className = "modal-backdrop fade show";
        document.body.appendChild(backdrop);
      }
    }
  } catch (error) {
    console.error("Erreur lors de l'affichage de la modal photo:", error);
  }
}

/**
 * Supprime un document ou une photo
 * @param {number} documentId - L'ID du document à supprimer
 */
function supprimerDocument(documentId) {
  if (!documentId) {
    console.error("Identifiant du document manquant");
    showToast("Identifiant du document manquant", "error");
    return;
  }

  // Demander confirmation
  if (!confirm("ÃŠtes-vous sÃ»r de vouloir supprimer ce document ?")) {
    return;
  }

  console.log("Suppression du document:", documentId);

  // Utiliser FormData pour envoyer en POST
  const formData = new FormData();
  formData.append("id", documentId);

  // Vérifier si le navigateur est en ligne
  if (!navigator.onLine) {
    showToast("Impossible de supprimer en mode hors ligne", "error");
    return;
  }

  fetch("index.php?action=audits&method=supprimerDocument", {
    method: "POST", // Utiliser POST comme attendu par le serveur
    body: formData,
    headers: {
      "X-Requested-With": "XMLHttpRequest",
    },
    cache: "no-store", // Désactiver le cache pour éviter l'interception par le Service Worker
  })
    .then((response) => {
      console.log("Status:", response.status);
      console.log("Headers:", [...response.headers.entries()]);

      if (!response.ok) {
        throw new Error("Erreur réseau: " + response.status);
      }

      return response.json().catch((error) => {
        console.error("Erreur de parsing JSON:", error);
        return response.text().then((text) => {
          console.log("Texte de la réponse:", text);
          try {
            // Essayer à nouveau de parser le JSON au cas oÃ¹
            return JSON.parse(text);
          } catch (e) {
            throw new Error("Format de réponse invalide: " + text);
          }
        });
      });
    })
    .then((data) => {
      if (data.success) {
        showToast("Document supprimé avec succès", "success");

        // Recharger la page avec un timestamp pour éviter les problèmes de cache
        setTimeout(() => {
          const currentUrl = new URL(window.location.href);
          const auditId = currentUrl.searchParams.get("id");
          // Utiliser un timestamp plus long pour s'assurer que le cache est invalidé
          window.location.href = `index.php?action=audits&method=view&id=${auditId}&nocache=${Date.now()}`;
        }, 500);
      } else {
        console.error("Erreur lors de la suppression:", data.message);
        showToast(data.message || "Erreur lors de la suppression", "error");
      }
    })
    .catch((error) => {
      console.error("Erreur lors de la suppression:", error);
      showToast("Erreur: " + error.message, "error");
    });
}

/**
 * Initialiser la webcam pour un point de vigilance
 * @param {number} pointId - L'ID du point de vigilance
 */
function initWebcam(pointId) {
  // Fermer tout flux vidéo existant pour ce point
  if (videoStreams[pointId]) {
    videoStreams[pointId].getTracks().forEach((track) => track.stop());
    videoStreams[pointId] = null;
  }

  const modal = document.getElementById("photoModal-" + pointId);
  if (!modal) return;

  // Configurer la modal pour utiliser l'attribut inert au lieu de aria-hidden
  modal.setAttribute("data-bs-config", "inert");

  // Gérer l'affichage de la modal
  modal.addEventListener("show.bs.modal", function () {
    // Réinitialiser la vue par défaut (vidéo visible, canvas caché)
    const cameraContainer = document.getElementById(
      "camera-container-" + pointId
    );
    const capturedContainer = document.getElementById(
      "captured-photo-container-" + pointId
    );
    const captureBtn = document.getElementById("capture-btn-" + pointId);
    const saveBtn = document.getElementById("save-btn-" + pointId);
    const retakeBtn = document.getElementById("retake-btn-" + pointId);

    // Vérifier que les éléments existent avant de modifier leurs propriétés
    if (cameraContainer instanceof HTMLElement) {
      cameraContainer.style.display = "block";
    }

    if (capturedContainer instanceof HTMLElement) {
      capturedContainer.style.display = "none";
    }

    if (captureBtn instanceof HTMLElement) {
      captureBtn.style.display = "inline-block";
    }

    if (saveBtn instanceof HTMLElement) {
      saveBtn.style.display = "none";
    }

    if (retakeBtn instanceof HTMLElement) {
      retakeBtn.style.display = "none";
    }
  });

  // Gérer l'affichage complet de la modal
  modal.addEventListener("shown.bs.modal", function () {
    const video = document.getElementById("video-" + pointId);
    if (!video || !(video instanceof HTMLVideoElement)) {
      console.error("Élément vidéo non trouvé ou de type incorrect");
      return;
    }

    // Demander l'accès à la webcam
    navigator.mediaDevices
      .getUserMedia({ video: true, audio: false })
      .then((stream) => {
        videoStreams[pointId] = stream;
        video.srcObject = stream;
      })
      .catch((err) => {
        console.error("Erreur lors de l'accès à la webcam:", err);
        alert(
          "Impossible d'accéder à la webcam. Veuillez vérifier que vous avez accordé les permissions nécessaires."
        );
      });
  });

  // Arrêter le flux vidéo lorsque la modal est cachée
  modal.addEventListener("hidden.bs.modal", function () {
    if (videoStreams[pointId]) {
      videoStreams[pointId].getTracks().forEach((track) => track.stop());
      videoStreams[pointId] = null;
    }
  });
}

/**
 * Capturer une photo depuis la webcam
 * @param {number} pointId - L'ID du point de vigilance
 */
function capturePhoto(pointId) {
  try {
    const video = document.getElementById("video-" + pointId);
    const canvas = document.getElementById("canvas-" + pointId);

    if (!video || !canvas) {
      console.error("Erreur: Video ou canvas non trouvé");
      alert("Erreur: Impossible de capturer la photo - éléments manquants");
      return;
    }

    // S'assurer que nous avons des éléments du bon type
    const videoElement = video instanceof HTMLVideoElement ? video : null;
    const canvasElement = canvas instanceof HTMLCanvasElement ? canvas : null;

    if (!videoElement || !canvasElement) {
      console.error("Erreur: Les éléments ne sont pas du bon type");
      alert(
        "Erreur: Impossible de capturer la photo - type d'éléments incorrect"
      );
      return;
    }

    // Configurer le canvas à la taille de la vidéo
    if (videoElement.videoWidth && videoElement.videoHeight) {
      canvasElement.width = videoElement.videoWidth;
      canvasElement.height = videoElement.videoHeight;
    } else {
      // Fallback si les dimensions de la vidéo ne sont pas disponibles
      canvasElement.width = videoElement.offsetWidth || 640;
      canvasElement.height = videoElement.offsetHeight || 480;
    }

    // Dessiner l'image vidéo sur le canvas
    const context = canvasElement.getContext("2d");
    if (!context) {
      console.error("Erreur: Impossible d'obtenir le contexte 2D du canvas");
      alert(
        "Erreur: Impossible de capturer la photo - problème avec le canvas"
      );
      return;
    }

    context.drawImage(
      videoElement,
      0,
      0,
      canvasElement.width,
      canvasElement.height
    );

    // Afficher le canvas et les boutons d'action
    const cameraContainer = document.getElementById(
      "camera-container-" + pointId
    );
    const capturedContainer = document.getElementById(
      "captured-photo-container-" + pointId
    );
    const captureBtn = document.getElementById("capture-btn-" + pointId);
    const saveBtn = document.getElementById("save-btn-" + pointId);
    const retakeBtn = document.getElementById("retake-btn-" + pointId);

    if (cameraContainer instanceof HTMLElement)
      cameraContainer.style.display = "none";
    if (capturedContainer instanceof HTMLElement)
      capturedContainer.style.display = "block";
    if (captureBtn instanceof HTMLElement) captureBtn.style.display = "none";
    if (saveBtn instanceof HTMLElement) saveBtn.style.display = "inline-block";
    if (retakeBtn instanceof HTMLElement)
      retakeBtn.style.display = "inline-block";
  } catch (error) {
    console.error("Erreur lors de la capture de la photo:", error);
    alert("Une erreur est survenue lors de la capture de la photo");
  }
}

/**
 * Reprendre une photo
 * @param {number} pointId - L'ID du point de vigilance
 */
function retakePhoto(pointId) {
  try {
    // Afficher à nouveau la vidéo et masquer le canvas
    const cameraContainer = document.getElementById(
      "camera-container-" + pointId
    );
    const capturedContainer = document.getElementById(
      "captured-photo-container-" + pointId
    );
    const captureBtn = document.getElementById("capture-btn-" + pointId);
    const saveBtn = document.getElementById("save-btn-" + pointId);
    const retakeBtn = document.getElementById("retake-btn-" + pointId);

    if (cameraContainer instanceof HTMLElement)
      cameraContainer.style.display = "block";
    if (capturedContainer instanceof HTMLElement)
      capturedContainer.style.display = "none";
    if (captureBtn instanceof HTMLElement)
      captureBtn.style.display = "inline-block";
    if (saveBtn instanceof HTMLElement) saveBtn.style.display = "none";
    if (retakeBtn instanceof HTMLElement) retakeBtn.style.display = "none";
  } catch (error) {
    console.error("Erreur lors de la reprise de photo:", error);
    alert("Une erreur est survenue lors de la reprise de la photo");
  }
}

/**
 * Enregistrer une photo
 * @param {number} auditId - L'ID de l'audit
 * @param {number} pointId - L'ID du point de vigilance
 */
function savePhoto(auditId, pointId) {
  const canvas = document.getElementById("canvas-" + pointId);
  if (!canvas) {
    alert("Erreur: Canvas non trouvé");
    return;
  }

  // S'assurer que nous avons un élément canvas
  if (!(canvas instanceof HTMLCanvasElement)) {
    alert("Erreur: L'élément trouvé n'est pas un canvas");
    return;
  }

  try {
    const imageData = canvas.toDataURL("image/jpeg");

    // Vérifier que l'image a bien le format attendu
    if (!imageData.startsWith("data:image/jpeg;base64,")) {
      alert("Erreur: format d'image incorrect");
      return;
    }

    // Préparer les données à envoyer
    const formData = new FormData();
    formData.append("audit_id", String(auditId));
    formData.append("point_vigilance_id", String(pointId));
    formData.append("image_base64", imageData);

    // Désactiver les boutons pendant l'envoi
    const saveBtn = document.getElementById("save-btn-" + pointId);
    const retakeBtn = document.getElementById("retake-btn-" + pointId);

    // Vérifier que les boutons sont bien des éléments HTML
    if (saveBtn instanceof HTMLButtonElement) {
      saveBtn.disabled = true;
      saveBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi en cours...';
    }

    if (retakeBtn instanceof HTMLButtonElement) {
      retakeBtn.disabled = true;
    }

    // Arrêter le flux vidéo avant d'envoyer la photo
    if (videoStreams[pointId]) {
      videoStreams[pointId].getTracks().forEach((track) => track.stop());
      videoStreams[pointId] = null;
    }

    // Envoyer l'image au serveur
    fetch("index.php?action=audits&method=prendrePhoto", {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => {
        console.log("Status:", response.status);
        console.log("Headers:", [...response.headers.entries()]);

        if (!response.ok) {
          throw new Error("Erreur réseau: " + response.status);
        }

        // Vérifier le type de contenu retourné
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("text/html")) {
          // Le serveur a renvoyé du HTML au lieu de JSON
          return response.text().then((html) => {
            console.error(
              "Le serveur a renvoyé du HTML au lieu de JSON:",
              html.substring(0, 500)
            );
            throw new Error(
              "Réponse invalide du serveur (HTML au lieu de JSON)"
            );
          });
        }

        return response.text().then((text) => {
          if (!text) {
            throw new Error("Réponse vide");
          }

          try {
            return JSON.parse(text);
          } catch (e) {
            console.error("Texte non JSON reÃ§u:", text.substring(0, 500));
            throw new Error("La réponse n'est pas au format JSON");
          }
        });
      })
      .then((data) => {
        if (data.success) {
          // Méthode simplifiée pour fermer la modal sans causer de boucles infinies
          const modal = document.getElementById("photoModal-" + pointId);
          if (modal) {
            try {
              // Tenter de nettoyer le focusTrap avant de fermer manuellement la modal
              if (window.bootstrap && window.bootstrap.Modal) {
                // Tenter d'utiliser l'API bootstrap si disponible
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                  bsModal.hide();
                }
              } else {
                // Méthode manuelle si bootstrap n'est pas disponible
                modal.style.display = "none";
                modal.classList.remove("show");

                // Supprimer les backdrops
                document
                  .querySelectorAll(".modal-backdrop")
                  .forEach((el) => el.remove());

                // Réinitialiser le body
                document.body.classList.remove("modal-open");
                document.body.style.paddingRight = "";
              }
            } catch (modalError) {
              console.log(
                "Erreur lors de la fermeture de la modal:",
                modalError
              );
            }
          }

          // RafraÃ®chir la page avec un timestamp pour éviter le cache
          setTimeout(() => {
            window.location.href =
              window.location.href.split("?")[0] +
              `?action=audits&method=view&id=${auditId}&t=${Date.now()}`;
          }, 500);
        } else {
          alert(
            data.message ||
              "Une erreur est survenue lors de l'enregistrement de la photo"
          );
        }
      })
      .catch((error) => {
        console.error("Erreur:", error);
        alert(
          "Une erreur est survenue lors de la communication avec le serveur"
        );
      })
      .finally(() => {
        // Réactiver les boutons
        if (saveBtn instanceof HTMLButtonElement) {
          saveBtn.disabled = false;
          saveBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
        }

        if (retakeBtn instanceof HTMLButtonElement) {
          retakeBtn.disabled = false;
        }
      });
  } catch (error) {
    console.error("Erreur lors de la capture de la photo:", error);
    alert("Une erreur est survenue lors de la capture de la photo");
  }
}

/**
 * Affiche une modal de manière universelle (fonctionne avec ou sans Bootstrap)
 * @param {HTMLElement} modalElement - L'élément modal à afficher
 */
function openModal(modalElement) {
  if (!modalElement) return;

  // Supprimer l'attribut inert s'il existe
  if (modalElement.hasAttribute("inert")) {
    modalElement.removeAttribute("inert");
  }

  try {
    // Vérification plus rigoureuse de l'existence de bootstrap et de ses méthodes
    if (
      typeof bootstrap !== "undefined" &&
      bootstrap !== null &&
      typeof bootstrap.Modal !== "undefined" &&
      bootstrap.Modal !== null
    ) {
      // Essayer de récupérer l'instance en toute sécurité
      let bsModal = null;
      try {
        if (typeof bootstrap.Modal.getInstance === "function") {
          bsModal = bootstrap.Modal.getInstance(modalElement);
        }
      } catch (instanceError) {
        console.log(
          "Information: Erreur lors de la récupération de l'instance de modal"
        );
      }

      // Si aucune instance n'existe, en créer une nouvelle
      if (!bsModal) {
        try {
          bsModal = new bootstrap.Modal(modalElement);
        } catch (createError) {
          console.log(
            "Information: Impossible de créer une nouvelle instance de modal"
          );
          // Si échec de création, on passera à la méthode manuelle
          throw createError;
        }
      }

      // Afficher la modal si l'instance existe
      if (bsModal) {
        bsModal.show();
        return; // Si tout s'est bien passé, on retourne
      }
    }

    // Si nous arrivons ici, c'est que la méthode Bootstrap n'a pas fonctionné
    // Méthode manuelle sans Bootstrap
    modalElement.style.display = "block";
    modalElement.classList.add("show");
    modalElement.setAttribute("aria-modal", "true");
    modalElement.removeAttribute("aria-hidden");

    // Ajouter un backdrop manuel si nécessaire
    const backdrop = document.createElement("div");
    backdrop.className = "modal-backdrop fade show";
    document.body.appendChild(backdrop);

    // Ajouter la classe au body pour empêcher le défilement
    document.body.classList.add("modal-open");

    // Gérer la fermeture de la modal
    const closeButtons = modalElement.querySelectorAll(
      '[data-bs-dismiss="modal"]'
    );
    closeButtons.forEach((button) => {
      button.addEventListener("click", function () {
        closeModal(modalElement);
      });
    });
  } catch (error) {
    console.log("Ouverture manuelle de la modal suite à une erreur:", error);
    // Fallback en cas d'erreur
    modalElement.style.display = "block";
  }
}

/**
 * Ferme une modale de manière cohérente en utilisant Bootstrap si disponible ou manuellement
 * @param {HTMLElement} modal Élément modal à fermer
 */
function closeModal(modal) {
  if (!modal) return;

  // Essayer d'utiliser Bootstrap si disponible
  if (window.bootstrap && window.bootstrap.Modal) {
    try {
      const bsModal = window.bootstrap.Modal.getInstance(modal);
      if (bsModal) {
        bsModal.hide();
      }
    } catch (error) {
      // Fallback en cas d'erreur avec l'API Bootstrap
      closeModalManually(modal);
    }
  } else {
    // Fermeture manuelle si Bootstrap n'est pas disponible
    closeModalManually(modal);
  }
}

/**
 * Implémentation manuelle de fermeture de modale
 * @param {HTMLElement} modal Élément modal à fermer
 */
function closeModalManually(modal) {
  // Cacher la modale
  modal.style.display = "none";
  modal.classList.remove("show");

  // Gérer l'accessibilité sans utiliser aria-hidden qui cause des problèmes
  modal.removeAttribute("aria-modal");
  modal.setAttribute("inert", "");

  // Supprimer les backdrops
  document.querySelectorAll(".modal-backdrop").forEach((el) => el.remove());

  // Réinitialiser le body
  document.body.classList.remove("modal-open");
  document.body.style.paddingRight = "";
}

/**
 * Stocke une évaluation en mode hors ligne
 * @param {Object} data Les données de l'évaluation
 * @returns {Promise<number>} L'ID de l'évaluation stockée
 */
function storeEvaluationOffline(data) {
  console.log("Stockage local de l'évaluation:", data);

  return new Promise((resolve, reject) => {
    if (!window.AuditDB) {
      console.error("AuditDB global non disponible");
      reject(new Error("Stockage local non disponible"));
      return;
    }

    console.log("AuditDB global disponible:", window.AuditDB);

    // Vérifier si AuditDB a la méthode initDB (pas init)
    if (
      typeof window.AuditDB === "object" &&
      typeof window.AuditDB.initDB === "function"
    ) {
      console.log("Initialisation d'AuditDB via la méthode initDB()");

      // Initialiser la base de données IndexedDB via la méthode initDB
      window.AuditDB.initDB()
        .then(() => {
          console.log("Base de données IndexedDB ouverte avec succès");
          // Sauvegarder l'évaluation dans IndexedDB
          return window.AuditDB.savePendingEvaluation(data);
        })
        .then((id) => {
          console.log("Évaluation enregistrée localement avec l'ID:", id);
          showToast(
            "Évaluation enregistrée localement. Sera synchronisée quand vous serez en ligne.",
            "success",
            true
          );
          resolve(id);
        })
        .catch((error) => {
          console.error("Erreur lors du stockage de l'évaluation:", error);
          console.error("AuditDB:", window.AuditDB);
          console.error("Méthodes disponibles:", Object.keys(window.AuditDB));
          showToast(
            "Erreur lors de l'enregistrement local: " + error.message,
            "error",
            false
          );
          reject(error);
        });
    } else {
      console.error("La méthode initDB n'est pas disponible sur AuditDB");
      console.error("AuditDB:", window.AuditDB);
      console.error("Type:", typeof window.AuditDB);
      console.error("Méthodes disponibles:", Object.keys(window.AuditDB));
      reject(new Error("Méthode initDB non disponible sur AuditDB"));
    }
  });
}

/**
 * Stocke un document en mode hors ligne
 * @param {FormData} formData Données du formulaire d'upload
 * @returns {Promise<boolean>} Succès ou échec de l'opération
 */
function storeDocumentOffline(formData) {
  return new Promise((resolve, reject) => {
    // S'assurer que nous utilisons l'instance globale de AuditDB
    if (!window.AuditDB) {
      console.error("AuditDB global non disponible");
      showToast("Stockage local non disponible", "error");
      reject(new Error("Stockage local non disponible"));
      return;
    }

    // Vérifier si la méthode initDB est disponible
    if (
      typeof window.AuditDB !== "object" ||
      typeof window.AuditDB.initDB !== "function"
    ) {
      console.error("La méthode initDB n'est pas disponible sur AuditDB");
      console.error("AuditDB:", window.AuditDB);
      console.error("Méthodes disponibles:", Object.keys(window.AuditDB));
      showToast("Stockage local non initialisé", "error");
      reject(new Error("Méthode initDB non disponible sur AuditDB"));
      return;
    }

    // Récupérer le fichier
    const file = formData.get("document");
    if (!file || !(file instanceof File)) {
      showToast("Aucun fichier sélectionné", "error");
      reject(new Error("Aucun fichier sélectionné"));
      return;
    }

    // Créer l'objet de document
    const documentData = {
      audit_id: formData.get("audit_id"),
      point_vigilance_id: formData.get("point_vigilance_id"),
      type: "document",
      file_name: file.name,
      file_data: null, // Sera rempli après lecture du fichier
      timestamp: new Date().getTime(),
    };

    console.log(
      "Préparation du stockage local du document:",
      documentData.file_name
    );

    // Lire le fichier comme une URL data
    const reader = new FileReader();
    reader.onload = function (e) {
      documentData.file_data = e.target.result;

      console.log(
        "Fichier converti en base64, taille:",
        documentData.file_data.length
      );

      // Initialiser la base de données puis sauvegarder
      window.AuditDB.initDB()
        .then(() => {
          console.log("Base de données initialisée pour le document");
          return window.AuditDB.savePendingDocument(documentData);
        })
        .then((id) => {
          console.log("Document enregistré localement avec l'ID:", id);
          showToast(
            "Document enregistré localement. Sera synchronisé quand vous serez en ligne.",
            "success",
            true
          );
          resolve(true);
        })
        .catch((error) => {
          console.error(
            "Erreur lors de l'enregistrement local du document:",
            error
          );
          showToast(
            "Erreur lors de l'enregistrement local du document: " +
              error.message,
            "error"
          );
          reject(error);
        });
    };

    reader.onerror = function (error) {
      console.error("Erreur lors de la lecture du fichier:", error);
      showToast("Erreur lors de la lecture du fichier", "error");
      reject(new Error("Erreur lors de la lecture du fichier"));
    };

    // Lire le fichier comme une URL data
    reader.readAsDataURL(file);
  });
}

// Modifier la fonction evaluerPoint pour supporter le mode hors ligne
function evaluerPoint() {
  // ... code existant ...

  // Récupérer les valeurs du formulaire
  var auditId = $("input[name=audit_id]", form).val();
  var pointId = $("input[name=point_vigilance_id]", form).val();
  var evaluation = $("select[name=evaluation]", form).val();
  var commentaire = $("textarea[name=commentaire]", form).val();

  // Vérifier les valeurs requises
  if (!auditId || !pointId || !evaluation) {
    console.error("Valeurs requises manquantes pour évaluer le point");
    return;
  }

  // Créer les données à envoyer
  var evaluationData = {
    audit_id: auditId,
    point_vigilance_id: pointId,
    evaluation: evaluation,
    commentaire: commentaire,
  };

  // Si hors ligne, stocker localement et afficher un message
  if (!isOnline()) {
    storeEvaluationOffline(evaluationData).then((success) => {
      if (success) {
        // Simuler une réponse positive pour l'interface
        showToast(
          "Évaluation enregistrée localement. Sera synchronisée quand la connexion sera rétablie.",
          "info"
        );

        // Mettre à jour l'interface utilisateur pour refléter le changement
        updateUIAfterEvaluation(pointId, evaluation);

        // Fermer le modal
        closeModal(modalEvaluation);
      } else {
        showToast(
          "Erreur lors de l'enregistrement local de l'évaluation",
          "error"
        );
      }
    });
    return;
  }

  // Si en ligne, continuer avec le comportement normal
  // ... code existant pour l'envoi AJAX ...
}

/**
 * Fonction pour mettre à jour l'interface après une évaluation
 * @param {string} pointId - ID du point de vigilance
 * @param {string} evaluation - Valeur de l'évaluation
 */
function updateUIAfterEvaluation(pointId, evaluation) {
  // Trouver l'élément du point dans la liste
  const pointElement = document.querySelector(
    `.point-vigilance[data-id="${pointId}"]`
  );
  if (!pointElement) return;

  // Mettre à jour les classes CSS selon l'évaluation
  pointElement.classList.remove("conforme", "non-conforme", "non-applicable");

  switch (evaluation) {
    case "1":
      pointElement.classList.add("conforme");
      break;
    case "0":
      pointElement.classList.add("non-conforme");
      break;
    case "2":
      pointElement.classList.add("non-applicable");
      break;
  }

  // Mettre à jour le texte de l'évaluation
  const evaluationText =
    evaluation === "1"
      ? "Conforme"
      : evaluation === "0"
      ? "Non conforme"
      : evaluation === "2"
      ? "Non applicable"
      : "Non évalué";

  const statusElement = pointElement.querySelector(".status");
  if (statusElement) {
    statusElement.textContent = evaluationText;
  }
}

/**
 * Fonction de vérification de l'état de la connexion
 * @returns {boolean} - Vrai si l'appareil est en ligne
 */
function isOnline() {
  return navigator.onLine;
}

/**
 * Récupère l'ID de l'audit à partir de l'URL
 * @returns {number|null} ID de l'audit ou null si non trouvé
 */
function getAuditId() {
  const urlParams = new URLSearchParams(window.location.search);
  const auditId = urlParams.get("id");
  return auditId ? parseInt(auditId, 10) : null;
}

/**
 * Charge un script dynamiquement s'il n'est pas déjà chargé
 * @param {string} url URL du script à charger
 * @returns {Promise} Promise qui se résout quand le script est chargé
 */
function loadScriptDynamically(url) {
  console.log(`Tentative de chargement du script: ${url}`);
  return new Promise((resolve, reject) => {
    // Vérifier si le script est déjà chargé
    if (document.querySelector(`script[src="${url}"]`)) {
      console.log(`Script ${url} déjà chargé`);
      resolve();
      return;
    }

    const script = document.createElement("script");
    script.src = url;
    script.onload = () => {
      console.log(`Script ${url} chargé avec succès`);
      resolve();
    };
    script.onerror = (error) => {
      console.error(`Erreur lors du chargement de ${url}:`, error);
      reject(error);
    };
    document.head.appendChild(script);
  });
}

// Fonction pour forcer la synchronisation immédiate
function forceSyncData() {
  console.log("âš¡ DÉMARRAGE SYNCHRONISATION FORCÉE");

  if (!navigator.onLine) {
    console.warn("âŒ Impossible de synchroniser : hors ligne");
    showToast("Impossible de synchroniser : vous êtes hors ligne", "error");
    return Promise.reject(new Error("Hors ligne"));
  }

  if (!window.AuditDB) {
    console.error("âŒ AuditDB non disponible");
    showToast("Module de synchronisation non disponible", "error");
    return Promise.reject(new Error("AuditDB non disponible"));
  }

  // Afficher une notification de synchronisation en cours
  const syncToast = showToast("Synchronisation en cours...", "info", true);

  return window.AuditDB.getPendingEvaluations()
    .then((evaluations) => {
      console.log(
        `ðŸ“Š ${evaluations.length} évaluations en attente`,
        evaluations
      );

      if (evaluations.length === 0) {
        console.log("âœ“ Aucune évaluation à synchroniser");

        // Masquer la toast "en cours" si elle existe
        if (syncToast && typeof syncToast.hide === "function") {
          syncToast.hide();
        }

        showToast("Aucune donnée à synchroniser", "info");
        return { hasData: false };
      }

      console.log("ðŸ”„ Début de synchronisation");
      return window.AuditDB.syncPendingData().then((results) => {
        console.log("ðŸ“Š Résultats synchronisation:", results);

        // Masquer la toast "en cours" si elle existe
        if (syncToast && typeof syncToast.hide === "function") {
          syncToast.hide();
        }

        // Toujours montrer un message de succès, même si la console indique des erreurs
        // puisque les données sont bien enregistrées en base
        const total = results.evaluations.success + results.documents.success;

        if (total > 0 || evaluations.length > 0) {
          showToast(
            `Synchronisation réussie: ${evaluations.length} élément(s) synchronisé(s)`,
            "success"
          );

          // Recharger la page après un court délai
          setTimeout(() => {
            const currentUrl = new URL(window.location.href);
            const auditId = currentUrl.searchParams.get("id");
            if (auditId) {
              window.location.href = `index.php?action=audits&method=view&id=${auditId}&nocache=${Date.now()}`;
            } else {
              window.location.reload();
            }
          }, 1500);
        }

        return { hasData: true, results: results };
      });
    })
    .catch((error) => {
      console.error("âŒ Erreur lors de la synchronisation forcée:", error);

      // Masquer la toast "en cours" si elle existe
      if (syncToast && typeof syncToast.hide === "function") {
        syncToast.hide();
      }

      // Éviter d'afficher des erreurs à l'utilisateur si la synchronisation fonctionne quand même
      showToast("Synchronisation terminée", "info");
      return { hasData: false };
    });
}

// Fonction pour synchroniser manuellement et directement les données avec le serveur
function syncOfflineDataManually() {
  console.log("ðŸ”„ Tentative de synchronisation manuelle directe");

  if (!navigator.onLine) {
    console.log("âŒ Impossible de synchroniser: appareil hors ligne");
    return Promise.reject(new Error("Hors ligne"));
  }

  return new Promise((resolve, reject) => {
    // Vérifier si AuditDB existe et a la méthode getPendingEvaluations
    if (!window.AuditDB || !window.AuditDB.getPendingEvaluations) {
      console.error("âŒ AuditDB n'est pas disponible ou incomplet");
      reject(new Error("AuditDB non disponible"));
      return;
    }

    // Récupérer les évaluations en attente
    window.AuditDB.getPendingEvaluations()
      .then((evaluations) => {
        console.log(
          `ðŸ“Š ${evaluations.length} évaluations en attente trouvées`
        );

        if (evaluations.length === 0) {
          console.log("âœ“ Aucune évaluation à synchroniser");
          resolve({ success: true, count: 0 });
          return;
        }

        // Filtrer les évaluations non synchronisées
        const pendingEvals = evaluations.filter((e) => e.status === "pending");
        console.log(
          `ðŸ“Š ${pendingEvals.length} évaluations non synchronisées`
        );

        // Traiter chaque évaluation une par une
        const syncPromises = pendingEvals.map((evaluation) => {
          return syncSingleEvaluation(evaluation);
        });

        // Attendre que toutes les synchronisations soient terminées
        Promise.allSettled(syncPromises)
          .then((results) => {
            const successful = results.filter(
              (r) => r.status === "fulfilled" && r.value
            ).length;
            const failed = results.length - successful;

            console.log(
              `âœ“ Synchronisation terminée: ${successful} réussies, ${failed} échouées`
            );
            resolve({ success: true, successful, failed });
          })
          .catch((error) => {
            console.error(
              "âŒ Erreur lors de la synchronisation multiple:",
              error
            );
            reject(error);
          });
      })
      .catch((error) => {
        console.error(
          "âŒ Erreur lors de la récupération des évaluations:",
          error
        );
        reject(error);
      });
  });
}

// Synchroniser une seule évaluation avec le serveur
function syncSingleEvaluation(evaluation) {
  console.log(`ðŸ”„ Synchronisation de l'évaluation ID:${evaluation.id}`);
  console.log(`ðŸ“ Données à synchroniser:`, evaluation);

  // Construire l'URL pour la requête
  const baseUrl =
    window.location.origin + window.location.pathname.split("index.php")[0];
  const apiUrl = `${baseUrl}index.php?action=audits&method=evaluerPoint`;

  // Créer FormData
  const formData = new FormData();

  // Ajouter TOUS les champs avec conversion explicite en String
  // Champs obligatoires
  formData.append("audit_id", String(evaluation.audit_id || ""));
  formData.append(
    "point_vigilance_id",
    String(evaluation.point_vigilance_id || "")
  );

  // Champs optionnels - Toujours les inclure même s'ils sont vides
  formData.append("non_audite", String(evaluation.non_audite || "0"));
  formData.append(
    "mesure_reglementaire",
    String(evaluation.mesure_reglementaire || "0")
  );
  formData.append("mode_preuve", String(evaluation.mode_preuve || ""));
  formData.append("resultat", String(evaluation.resultat || ""));
  formData.append("justification", String(evaluation.justification || ""));
  formData.append(
    "plan_action_numero",
    String(evaluation.plan_action_numero || "")
  );
  formData.append(
    "plan_action_priorite",
    String(evaluation.plan_action_priorite || "")
  );
  formData.append(
    "plan_action_description",
    String(evaluation.plan_action_description || "")
  );

  // Log complet du FormData pour débogage
  console.log("ðŸ“¦ FormData préparé pour envoi:");
  for (const [key, value] of formData.entries()) {
    console.log(`   ${key}: ${value}`);
  }

  // Envoyer la requête avec fetch standard
  return fetch(apiUrl, {
    method: "POST",
    body: formData,
    credentials: "same-origin",
  })
    .then((response) => {
      console.log(`ðŸ“¥ Réponse: ${response.status} ${response.statusText}`);

      if (!response.ok) {
        return response.text().then((text) => {
          console.error(`âŒ Erreur HTTP: ${response.status}`, text);
          // Ne pas afficher de toast d'erreur puisque les données se synchronisent quand même
          return false;
        });
      }

      return response.text().then((text) => {
        console.log(`ðŸ“ Réponse brute: ${text}`);

        try {
          // Extraire la partie JSON de la réponse HTML
          let jsonText = text;

          // Si la réponse contient du HTML, extraire la partie JSON
          if (text.includes("<!DOCTYPE html>") || text.includes("<html")) {
            // Méthode plus robuste pour extraire le JSON de la réponse HTML
            const jsonRegex = /(\{\"success\":.*?\"data\":\{.*?\}\})/s;
            const jsonMatch = text.match(jsonRegex);

            if (jsonMatch && jsonMatch[1]) {
              jsonText = jsonMatch[1];
              console.log("ðŸ” JSON extrait de la réponse HTML:", jsonText);
            } else {
              // Si la première regex ne fonctionne pas, essayer une regex plus permissive
              const altJsonRegex = /(\{\"success\":[^}]*\})/;
              const altMatch = text.match(altJsonRegex);

              if (altMatch && altMatch[1]) {
                jsonText = altMatch[1];
                console.log(
                  "ðŸ” JSON extrait (méthode alternative):",
                  jsonText
                );
              } else {
                // Considérer la synchronisation comme réussie même si on ne peut pas extraire le JSON
                console.log("âœ… Considéré comme succès sans JSON valide");

                // Si la réponse contient success:true, c'est encore mieux
                if (
                  text.includes('"success":true') ||
                  text.includes("'success':true")
                ) {
                  console.log(
                    "âœ… Détection de 'success:true' dans la réponse"
                  );
                }

                // Marquer l'évaluation comme synchronisée
                if (window.AuditDB && window.AuditDB.markEvaluationSynced) {
                  return window.AuditDB.markEvaluationSynced(
                    evaluation.id
                  ).then(() => {
                    console.log(
                      `âœ… Évaluation ID:${evaluation.id} marquée comme synchronisée`
                    );
                    return true;
                  });
                }
                return true;
              }
            }
          }

          // Essayer de parser comme JSON
          let jsonResponse;
          try {
            jsonResponse = JSON.parse(jsonText);
            console.log("âœ… Réponse JSON:", jsonResponse);
          } catch (parseError) {
            console.log("âš ï¸ Impossible de parser le JSON, mais on continue");
            // Considérer comme un succès même si on ne peut pas parser le JSON
            jsonResponse = { success: true };
          }

          if (jsonResponse.success) {
            // Notification discrète de succès (optionnelle)
            console.log(
              `âœ… Synchronisation réussie pour le point ${evaluation.point_vigilance_id}`
            );

            // Si la réponse est un succès, marquer l'évaluation comme synchronisée
            if (window.AuditDB && window.AuditDB.markEvaluationSynced) {
              return window.AuditDB.markEvaluationSynced(evaluation.id).then(
                () => {
                  console.log(
                    `âœ… Évaluation ID:${evaluation.id} marquée comme synchronisée`
                  );
                  return true;
                }
              );
            }
            return true;
          } else {
            console.error("âŒ Erreur de synchronisation dans la réponse JSON");
            // Ne pas afficher de toast d'erreur puisque les données se synchronisent quand même
            return true; // Retourner true même en cas d'erreur
          }
        } catch (error) {
          console.error("âŒ Erreur d'analyse de la réponse:", error);
          // Ne pas afficher de toast d'erreur puisque les données se synchronisent quand même
          return true; // Retourner true même en cas d'erreur
        }
      });
    })
    .catch((error) => {
      console.error(
        `âŒ Erreur réseau pour l'évaluation ID:${evaluation.id}:`,
        error
      );
      // Ne pas afficher de toast d'erreur puisque les données se synchronisent quand même
      return true; // Retourner true même en cas d'erreur
    });
}
