// Déclaration des types globaux
window.ArticleDB = window.ArticleDB || {};
window.AuditDB = window.AuditDB || {};

// Gestionnaire de mode hors ligne
const OfflineManager = {
  init: async function () {
    console.log("[OfflineManager] Initialisation");

    // Vérifier immédiatement l'état de la connexion et mettre à jour l'UI
    this.updateConnectionStatus();

    // Attendre que ArticleDB soit disponible
    if (!window.ArticleDB) {
      console.error(
        "[OfflineManager] ArticleDB n'est pas disponible. Attente de 1 seconde..."
      );
      await new Promise((resolve) => setTimeout(resolve, 1000));

      if (!window.ArticleDB) {
        console.error(
          "[OfflineManager] ArticleDB toujours pas disponible après attente"
        );
        alert(
          "Erreur: La base de données n'est pas disponible. Veuillez recharger la page."
        );
        return;
      }
    }

    try {
      console.log("[OfflineManager] Tentative d'initialisation de ArticleDB");
      await window.ArticleDB.initDB();
      console.log("[OfflineManager] ArticleDB initialisé avec succès");

      this.setupEventListeners();
      this.loadOfflineArticles();

      this.loadOfflineAudits();

      // Ajouter les écouteurs d'événements pour la connectivité
      window.addEventListener("online", async () => {
        console.log(
          "[OfflineManager] Connexion rétablie, synchronisation en cours..."
        );
        this.updateConnectionStatus();

        // Afficher une notification de reconnexion
        if (typeof showToast === "function") {
          showToast(
            "Connexion internet rétablie. Synchronisation en cours...",
            "success"
          );
        }

        // Petite pause pour laisser le temps au réseau de se stabiliser
        await new Promise((resolve) => setTimeout(resolve, 2000));

        try {
          // Synchroniser les données
          if (window.AuditDB) {
            const syncResult = await window.AuditDB.syncPendingData();
            console.log(
              "[OfflineManager] Résultat de la synchronisation:",
              syncResult
            );

            // Afficher un message de résultat
            const totalSync =
              syncResult.evaluations.success + syncResult.documents.success;
            if (totalSync > 0 && typeof showToast === "function") {
              showToast(
                `${totalSync} élément(s) synchronisé(s) avec succès`,
                "success"
              );
            }
          }
        } catch (error) {
          console.error(
            "[OfflineManager] Erreur lors de la synchronisation:",
            error
          );
          if (typeof showToast === "function") {
            showToast(
              "Erreur lors de la synchronisation: " + error.message,
              "error"
            );
          }
        }
      });

      window.addEventListener("offline", () => {
        console.log("[OfflineManager] Connexion perdue");
        this.updateConnectionStatus();

        // Afficher une notification de perte de connexion
        if (typeof showToast === "function") {
          showToast(
            "Connexion internet perdue. Les données seront enregistrées localement.",
            "warning"
          );
        }
      });
    } catch (error) {
      console.error(
        "[OfflineManager] Erreur d'initialisation de ArticleDB:",
        error
      );
      alert("Erreur lors de l'initialisation de la base de données");
    }
  },

  setupEventListeners: function () {
    const form = document.getElementById("offlineArticleForm");
    if (form) {
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        this.saveOfflineArticle();
      });
    }

    const testButton = document.getElementById("testAuditButton");
    if (testButton) {
      testButton.addEventListener("click", () => {
        this.testAuditDB();
      });
    }
  },

  saveOfflineArticle: function () {
    const titleInput = document.getElementById("articleTitle");
    const contentInput = document.getElementById("articleContent");

    if (
      !titleInput ||
      !contentInput ||
      !(titleInput instanceof HTMLInputElement) ||
      !(contentInput instanceof HTMLTextAreaElement)
    ) {
      console.error(
        "[OfflineManager] Éléments du formulaire non trouvés ou de type incorrect"
      );
      return;
    }

    const title = titleInput.value;
    const content = contentInput.value;

    if (!title || !content) {
      alert("Veuillez remplir tous les champs");
      return;
    }

    const article = {
      title: title,
      content: content,
      created_at: new Date().toISOString(),
      status: "offline",
    };

    // Sauvegarder dans IndexedDB
    if (window.ArticleDB) {
      window.ArticleDB.saveArticle(article)
        .then(() => {
          alert("Article sauvegardé localement");
          const form = document.getElementById("offlineArticleForm");
          if (form instanceof HTMLFormElement) {
            form.reset();
          }
          this.loadOfflineArticles();
        })
        .catch((error) => {
          console.error("Erreur lors de la sauvegarde:", error);
          alert("Erreur lors de la sauvegarde de l'article");
        });
    } else {
      console.error("ArticleDB n'est pas disponible");
      alert("Erreur: Base de données non disponible");
    }
  },

  loadOfflineArticles: function () {
    const container = document.getElementById("articlesContainer");
    if (!container) return;

    if (window.ArticleDB) {
      window.ArticleDB.getArticles()
        .then((articles) => {
          // Filtrer uniquement les articles qui n'ont pas encore de server_id
          const unsyncedArticles = articles.filter(
            (article) => !article.server_id
          );

          if (unsyncedArticles.length === 0) {
            container.innerHTML =
              '<div class="col-12"><p class="text-center">Aucun article en attente de synchronisation</p></div>';
            return;
          }

          const html = unsyncedArticles
            .map(
              (article) => `
            <div class="col-md-6 mb-4">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">${article.title}</h5>
                  <p class="card-text">${article.content}</p>
                  <small class="text-muted">Créé le: ${new Date(
                    article.created_at
                  ).toLocaleString()}</small>
                  <div class="mt-2">
                    <span class="badge bg-warning">En attente de synchronisation</span>
                  </div>
                </div>
              </div>
            </div>
          `
            )
            .join("");

          container.innerHTML = html;
        })
        .catch((error) => {
          console.error("Erreur lors du chargement des articles:", error);
          container.innerHTML =
            '<div class="col-12"><p class="text-center text-danger">Erreur lors du chargement des articles</p></div>';
        });
    } else {
      console.error("ArticleDB n'est pas disponible");
      container.innerHTML =
        '<div class="col-12"><p class="text-center text-danger">Base de données non disponible</p></div>';
    }
  },

  loadOfflineAudits: function () {
    const container = document.getElementById("offlineAuditsContainer");
    if (!container) return;

    if (typeof window.AuditDB !== "undefined") {
      window.AuditDB.getAudits()
        .then((audits) => {
          if (audits.length === 0) {
            container.innerHTML =
              '<p class="text-center">Aucun audit stocké localement</p>';
            return;
          }

          const html = `
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Numéro de site</th>
                    <th>Date</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody>
                  ${audits
                    .map(
                      (audit) => `
                    <tr>
                      <td>${audit.id}</td>
                      <td>${audit.numero_site || "N/A"}</td>
                      <td>${new Date(audit.created_at).toLocaleString()}</td>
                      <td>${audit.statut || "En cours"}</td>
                    </tr>
                  `
                    )
                    .join("")}
                </tbody>
              </table>
            </div>
          `;

          container.innerHTML = html;
        })
        .catch((error) => {
          console.error("Erreur lors du chargement des audits:", error);
          container.innerHTML =
            '<p class="text-center text-danger">Erreur lors du chargement des audits</p>';
        });
    } else {
      console.error("AuditDB n'est pas disponible");
      container.innerHTML =
        '<p class="text-center text-danger">Base de données non disponible</p>';
    }
  },

  testAuditDB: function () {
    console.log("[OfflineManager] Test de AuditDB");

    if (typeof window.AuditDB === "undefined") {
      alert("AuditDB n'est pas disponible");
      return;
    }

    // Créer un audit de test
    const testAudit = {
      numero_site: "TEST-" + Date.now(),
      created_at: new Date().toISOString(),
      statut: "En cours",
    };

    window.AuditDB.saveAudit(testAudit)
      .then((id) => {
        console.log("[OfflineManager] Audit de test créé avec l'ID:", id);
        alert("Audit de test créé avec succès!");
        this.loadOfflineAudits();
      })
      .catch((error) => {
        console.error(
          "[OfflineManager] Erreur lors de la création de l'audit de test:",
          error
        );
        alert("Erreur lors de la création de l'audit de test");
      });
  },

  // Mettre à jour les indicateurs visuels d'état de connexion
  updateConnectionStatus: function () {
    const isConnected = this.isOnline();

    // Mettre à jour les éléments d'UI si disponibles
    const onlineAlert = document.getElementById("onlineAlert");
    const offlineAlert = document.getElementById("offlineAlert");

    if (onlineAlert) onlineAlert.style.display = isConnected ? "block" : "none";
    if (offlineAlert)
      offlineAlert.style.display = isConnected ? "none" : "block";

    console.log(
      `[OfflineManager] État de connexion mis à jour: ${
        isConnected ? "En ligne" : "Hors ligne"
      }`
    );
  },

  // Vérifier si l'appareil est actuellement en ligne
  isOnline: function () {
    // Vérifier navigator.onLine (API standard)
    if (typeof navigator !== "undefined" && "onLine" in navigator) {
      return navigator.onLine;
    }
    // Par défaut, supposer en ligne
    return true;
  },
};
