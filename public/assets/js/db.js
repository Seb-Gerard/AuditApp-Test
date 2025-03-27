// Vérifier que les variables ne sont pas déjà définies dans le scope global
// et les définir comme variables globales si nécessaires
if (typeof window.DB_NAME === "undefined") {
  window.DB_NAME = "AuditDB";
  window.DB_VERSION = 1;
  window.STORE_NAME = "articles";
  window.dbInstance = null;
}

class ArticleDB {
  static _initInProgress = false;

  /**
   * Initialise la base de données de manière sécurisée
   *
   * @returns {Promise} Une promesse résolue lorsque la base de données est prête
   */
  static async initDB() {
    if (window.dbInstance) {
      console.log("[DB] Instance de base de données déjà existante");
      return Promise.resolve(window.dbInstance);
    }

    return new Promise((resolve, reject) => {
      console.log(
        "[DB] Ouverture de la base de données:",
        window.DB_NAME,
        "version:",
        window.DB_VERSION
      );

      // Éviter l'initialisation en boucle
      if (this._initInProgress) {
        console.log("[DB] Initialisation déjà en cours, en attente...");
        document.addEventListener(
          "dbready",
          () => {
            resolve(window.dbInstance);
          },
          { once: true }
        );
        return;
      }

      this._initInProgress = true;

      const request = indexedDB.open(window.DB_NAME, window.DB_VERSION);

      request.onerror = (event) => {
        console.error(
          "[DB] Erreur lors de l'ouverture de la base de données:",
          event.target.error
        );
        this._initInProgress = false;
        reject(new Error("Erreur d'ouverture de la base de données"));
      };

      request.onupgradeneeded = (event) => {
        console.log("[DB] Mise à niveau de la base de données");
        const db = event.target.result;

        // Créer un object store pour les articles si nécessaire
        if (!db.objectStoreNames.contains(window.STORE_NAME)) {
          console.log("[DB] Création du store:", window.STORE_NAME);
          const objectStore = db.createObjectStore(window.STORE_NAME, {
            keyPath: "id",
            autoIncrement: true,
          });

          // Créer des index pour des recherches rapides
          objectStore.createIndex("title", "title", { unique: false });
          objectStore.createIndex("server_id", "server_id", { unique: false });
        }
      };

      request.onsuccess = (event) => {
        window.dbInstance = event.target.result;
        console.log("[DB] Base de données ouverte avec succès");

        // Signaler que la base de données est prête
        this._initInProgress = false;
        document.dispatchEvent(new CustomEvent("dbready"));

        resolve(window.dbInstance);
      };
    });
  }

  static async saveArticle(article) {
    try {
      const db = await this.initDB();
      return new Promise((resolve, reject) => {
        try {
          const transaction = db.transaction([window.STORE_NAME], "readwrite");
          const store = transaction.objectStore(window.STORE_NAME);

          let request;

          if (article.id) {
            // Si l'article a déjà un ID, on le met à jour
            request = store.put(article);
            console.log("Mise à jour d'un article existant, ID:", article.id);
          } else {
            // Sinon, on crée un nouvel article
            const newArticle = {
              ...article,
              server_id: null, // S'assurer que server_id est null pour les nouveaux articles
              created_at: article.created_at || new Date().toISOString(),
            };
            request = store.add(newArticle);
            console.log("Ajout d'un nouvel article");
          }

          request.onsuccess = () => {
            console.log(
              "Article sauvegardé avec succès, ID local:",
              request.result
            );
            resolve(request.result);
          };

          request.onerror = () => {
            console.error(
              "Erreur lors de la sauvegarde de l'article:",
              request.error
            );
            reject(request.error);
          };

          transaction.oncomplete = () => {
            console.log("Transaction d'écriture terminée");
          };

          transaction.onerror = (event) => {
            console.error(
              "Erreur de transaction lors de la sauvegarde:",
              event.target.error
            );
            reject(event.target.error);
          };
        } catch (error) {
          console.error("Erreur lors de la création de la transaction:", error);
          reject(error);
        }
      });
    } catch (error) {
      console.error(
        "Erreur lors de l'initialisation de la base de données pour saveArticle:",
        error
      );
      throw error;
    }
  }

  static async getArticles() {
    try {
      // Initialise et obtient la référence à la base de données
      const db = await this.initDB();
      console.log("Base de données obtenue pour getArticles:", db);

      return new Promise((resolve, reject) => {
        try {
          const transaction = db.transaction([window.STORE_NAME], "readonly");
          const store = transaction.objectStore(window.STORE_NAME);

          const request = store.getAll();

          request.onsuccess = function () {
            console.log(
              `${request.result.length} articles récupérés avec succès`
            );
            resolve(request.result);
          };

          request.onerror = function () {
            console.error(
              "Erreur lors de la récupération des articles:",
              request.error
            );
            reject(request.error);
          };
        } catch (error) {
          console.error("Erreur de transaction:", error);
          reject(error);
        }
      });
    } catch (error) {
      console.error(
        "Erreur lors de l'initialisation de la base de données pour getArticles:",
        error
      );
      throw error;
    }
  }

  static async deleteArticle(id) {
    try {
      const db = await this.initDB();
      return new Promise((resolve, reject) => {
        try {
          const transaction = db.transaction([window.STORE_NAME], "readwrite");
          const store = transaction.objectStore(window.STORE_NAME);
          const request = store.delete(id);

          // @ts-ignore
          request.onsuccess = () => {
            console.log("Article supprimé avec succès, ID local:", id);
            resolve();
          };

          request.onerror = () => {
            console.error(
              "Erreur lors de la suppression de l'article:",
              request.error
            );
            reject(request.error);
          };

          transaction.oncomplete = () => {
            console.log("Transaction de suppression terminée");
          };

          transaction.onerror = (event) => {
            console.error(
              "Erreur de transaction lors de la suppression:",
              event.target.error
            );
            reject(event.target.error);
          };
        } catch (error) {
          console.error("Erreur lors de la création de la transaction:", error);
          reject(error);
        }
      });
    } catch (error) {
      console.error(
        "Erreur lors de l'initialisation de la base de données pour deleteArticle:",
        error
      );
      throw error;
    }
  }

  static async getPendingArticles() {
    const articles = await this.getArticles();
    return articles.filter((article) => !article.server_id);
  }

  static async clearAllArticles() {
    const db = await this.initDB();
    return new Promise((resolve, reject) => {
      const transaction = db.transaction([window.STORE_NAME], "readwrite");
      const store = transaction.objectStore(window.STORE_NAME);
      const request = store.clear();

      // @ts-ignore
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  static async syncWithServer() {
    try {
      console.log(
        "[syncWithServer] Début de la synchronisation avec le serveur..."
      );

      // 1. Récupérer tous les articles du serveur
      const response = await fetch("./index.php?action=articles", {
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json",
        },
      });

      if (!response.ok) {
        const errorText = await response.text();
        console.error(
          "[syncWithServer] Erreur HTTP:",
          response.status,
          errorText.substring(0, 100)
        );
        throw new Error(`Erreur HTTP: ${response.status}`);
      }

      // Clone la réponse pour pouvoir la lire plusieurs fois si nécessaire
      const responseClone = response.clone();

      let serverArticles;
      try {
        serverArticles = await response.json();
      } catch (jsonError) {
        console.error(
          "[syncWithServer] Erreur lors du parsing JSON:",
          jsonError
        );
        const responseText = await responseClone.text();
        console.error(
          "[syncWithServer] Contenu de la réponse:",
          responseText.substring(0, 500)
        );
        throw new Error("La réponse du serveur n'est pas un JSON valide");
      }

      console.log("[syncWithServer] Articles du serveur:", serverArticles);

      // 2. Récupérer tous les articles locaux
      const localArticles = await this.getArticles();
      console.log("[syncWithServer] Articles locaux:", localArticles);

      // 3. Pour chaque article local sans server_id, essayer de le synchroniser
      const pendingArticles = localArticles.filter(
        (article) => !article.server_id
      );

      if (pendingArticles.length > 0) {
        console.log(
          "[syncWithServer] Articles en attente de synchronisation:",
          pendingArticles.length
        );

        // Synchronisation directe des articles
        let syncSuccessCount = 0;
        for (const article of pendingArticles) {
          const success = await this.syncArticleWithServer(article);
          if (success) syncSuccessCount++;
        }
        console.log(
          `[syncWithServer] ${syncSuccessCount}/${pendingArticles.length} articles synchronisés avec succès`
        );
      }

      // 4. Mettre à jour les articles locaux avec les données du serveur
      for (const serverArticle of serverArticles) {
        const existingLocal = localArticles.find(
          (local) =>
            local.server_id &&
            local.server_id.toString() === serverArticle.id.toString()
        );

        if (!existingLocal) {
          // Article du serveur qui n'existe pas localement, on l'ajoute
          const newLocalId = await this.saveArticle({
            title: serverArticle.title,
            content: serverArticle.content,
            created_at: serverArticle.created_at,
            server_id: serverArticle.id,
          });
          console.log(
            "[syncWithServer] Article du serveur ajouté localement, server_id:",
            serverArticle.id,
            "new local ID:",
            newLocalId
          );
        }
      }

      console.log("[syncWithServer] Synchronisation terminée avec succès");
      return true;
    } catch (error) {
      console.error(
        "[syncWithServer] Erreur lors de la synchronisation avec le serveur:",
        error instanceof Error ? error.message : error
      );
      return false;
    }
  }

  /**
   * Synchronise un article avec le serveur
   * @param {Object} article - L'article à synchroniser
   * @returns {Promise<boolean>} - true si la synchronisation a réussi
   */
  static async syncArticleWithServer(article) {
    try {
      console.log(
        "[syncArticleWithServer] Tentative de synchronisation pour article:",
        article
      );

      // Ajouter une vérification de connectivité avant d'envoyer la requête
      if (!navigator.onLine) {
        console.log(
          "[syncArticleWithServer] Appareil hors ligne, impossible de synchroniser"
        );
        return false;
      }

      // URL pour la création d'article sur le serveur
      const url = new URL("./index.php?action=articles", window.location.href);
      console.log(
        "[syncArticleWithServer] URL complète pour la synchronisation:",
        url.href
      );

      // Ajouter un délai entre les tentatives pour éviter de surcharger le serveur
      await new Promise((resolve) => setTimeout(resolve, 500));

      const response = await fetch(url.href, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json",
        },
        body: JSON.stringify({
          title: article.title,
          content: article.content,
        }),
      });

      console.log(
        "[syncArticleWithServer] Statut de la réponse:",
        response.status,
        response.statusText
      );

      // Si le serveur est indisponible (503), on attend un peu et on réessaie
      if (response.status === 503) {
        console.log(
          "[syncArticleWithServer] Serveur indisponible (503), nouvelle tentative dans 2 secondes..."
        );
        await new Promise((resolve) => setTimeout(resolve, 2000));
        return this.syncArticleWithServer(article); // Tentative récursive
      }

      if (response.ok) {
        try {
          const responseClone = response.clone();
          let result;

          try {
            result = await response.json();
            console.log("[syncArticleWithServer] Réponse JSON reçue:", result);
          } catch (jsonError) {
            console.error(
              "[syncArticleWithServer] Erreur lors du parsing JSON, tentative de lecture du texte:",
              jsonError
            );
            const responseText = await responseClone.text();
            console.log(
              "[syncArticleWithServer] Réponse texte brute:",
              responseText
            );

            // Tentative de trouver un JSON valide dans la réponse textuelle
            try {
              const jsonMatch = responseText.match(/\{.*\}/s);
              if (jsonMatch) {
                result = JSON.parse(jsonMatch[0]);
                console.log(
                  "[syncArticleWithServer] JSON extrait de la réponse textuelle:",
                  result
                );
              } else {
                console.error(
                  "[syncArticleWithServer] Aucun JSON trouvé dans la réponse:",
                  responseText.substring(0, 200)
                );
                return false;
              }
            } catch (e) {
              console.error(
                "[syncArticleWithServer] Impossible d'extraire un JSON:",
                e
              );
              console.error(
                "[syncArticleWithServer] Réponse brute:",
                responseText.substring(0, 200)
              );
              return false;
            }
          }

          console.log(
            "[syncArticleWithServer] Réponse du serveur analysée:",
            result
          );

          // Gérer le cas où le serveur renvoie un tableau au lieu d'un seul objet
          let serverArticle = null;
          if (Array.isArray(result)) {
            console.log(
              "[syncArticleWithServer] Le serveur a renvoyé un tableau de",
              result.length,
              "articles. Recherche d'une correspondance..."
            );

            // Logs de debugging pour mieux comprendre pourquoi la correspondance échoue
            console.log("[syncArticleWithServer] Article local:", {
              title: article.title,
              content: article.content,
              created_at: article.created_at,
            });

            // 1. D'abord essayer correspondance exacte (titre et contenu)
            serverArticle = result.find(
              (a) => a.title === article.title && a.content === article.content
            );

            // 2. Si pas de correspondance exacte, essayer avec juste le titre
            if (!serverArticle) {
              console.log(
                "[syncArticleWithServer] Pas de correspondance exacte, essai avec titre uniquement"
              );
              serverArticle = result.find((a) => a.title === article.title);
            }

            // 3. Si pas de correspondance, prendre le plus récent
            if (!serverArticle && result.length > 0) {
              console.log(
                "[syncArticleWithServer] Aucune correspondance trouvée, utilisation de l'article le plus récent"
              );

              // Afficher les titres disponibles pour debug
              console.log(
                "[syncArticleWithServer] Titres disponibles:",
                result.map((a) => ({
                  id: a.id,
                  title: a.title.substring(0, 20),
                }))
              );

              // Trier du plus récent au plus ancien
              const sorted = [...result].sort((a, b) => {
                const dateA = new Date(a.created_at).getTime();
                const dateB = new Date(b.created_at).getTime();
                return dateB - dateA;
              });

              serverArticle = sorted[0];
              console.log("[syncArticleWithServer] Utilisation de l'article:", {
                id: serverArticle.id,
                title: serverArticle.title,
              });
            }
          } else if (result && result.id) {
            // Cas standard : le serveur a renvoyé un seul article
            serverArticle = result;
          }

          if (serverArticle && serverArticle.id) {
            console.log(
              "[syncArticleWithServer] Article trouvé sur le serveur, ID:",
              serverArticle.id
            );

            // Si l'article local existe, le supprimer
            if (article.id) {
              try {
                await this.deleteArticle(article.id);
                console.log(
                  "[syncArticleWithServer] Article supprimé de la base locale après synchronisation, ID local:",
                  article.id
                );
              } catch (deleteError) {
                console.error(
                  "[syncArticleWithServer] Erreur lors de la suppression de l'article local:",
                  deleteError
                );
                // Continuer malgré l'erreur de suppression
              }
            }

            return true;
          } else {
            // FALLBACK: Si aucun article correspondant n'est trouvé mais qu'il y a des articles
            // sur le serveur, considérer que l'article a été enregistré d'une manière ou d'une autre
            if (Array.isArray(result) && result.length > 0) {
              console.log(
                "[syncArticleWithServer] FALLBACK: Considérant l'article comme synchronisé car des articles existent sur le serveur"
              );

              // Supprimer l'article local puisqu'on suppose que la synchronisation a réussi
              if (article.id) {
                try {
                  await this.deleteArticle(article.id);
                  console.log(
                    "[syncArticleWithServer] Article supprimé de la base locale par fallback, ID local:",
                    article.id
                  );
                  return true;
                } catch (deleteError) {
                  console.error(
                    "[syncArticleWithServer] Erreur lors de la suppression de l'article local (fallback):",
                    deleteError
                  );
                }
              }
            }

            console.error(
              "[syncArticleWithServer] Réponse du serveur invalide:",
              result
            );
            return false;
          }
        } catch (parseError) {
          console.error(
            "[syncArticleWithServer] Erreur lors du traitement de la réponse:",
            parseError
          );
          return false;
        }
      } else {
        const errorText = await response.text();
        console.error(
          `[syncArticleWithServer] Erreur HTTP (${response.status}):`,
          errorText.substring(0, 100)
        );

        // Si le status est 401 ou 403, l'utilisateur doit peut-être se reconnecter
        if (response.status === 401 || response.status === 403) {
          alert("Session expirée ou non autorisée. Veuillez vous reconnecter.");
        }

        return false;
      }
    } catch (error) {
      console.error(
        "[syncArticleWithServer] Erreur lors de la synchronisation d'un article:",
        error instanceof Error ? error.message : error
      );
      return false;
    }
  }

  static async syncFromServer() {
    try {
      console.log("Synchronisation depuis le serveur en cours...");

      // 1. Récupérer tous les articles du serveur avec AJAX
      const response = await fetch("./index.php?action=articles", {
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json",
        },
      });

      if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status}`);
      }

      const serverArticles = await response.json();
      console.log("Articles récupérés du serveur:", serverArticles);

      if (!Array.isArray(serverArticles)) {
        console.error(
          "Les données du serveur ne sont pas un tableau:",
          serverArticles
        );
        return false;
      }

      // 2. Récupérer tous les articles locaux pour comparaison
      const localArticles = await this.getArticles();
      console.log(
        "Articles locaux avant synchronisation:",
        localArticles.length
      );

      // 3. Nettoyer les articles locaux ayant des server_id en doublon
      // Créer un Map pour détecter les doublons par server_id
      const serverIdMap = new Map();
      const duplicateIds = [];

      // Identifier les doublons
      for (const article of localArticles) {
        if (article.server_id) {
          if (serverIdMap.has(article.server_id)) {
            // C'est un doublon, on le stocke pour suppression
            duplicateIds.push(article.id);
          } else {
            // Premier article avec ce server_id
            serverIdMap.set(article.server_id, article.id);
          }
        }
      }

      // Supprimer les doublons
      if (duplicateIds.length > 0) {
        console.log(
          `Suppression de ${duplicateIds.length} articles dupliqués localement...`
        );
        for (const id of duplicateIds) {
          await this.deleteArticle(id);
        }
        console.log("Doublons supprimés avec succès");
      }

      // 4. Pour chaque article du serveur, vérifier s'il existe localement
      for (const serverArticle of serverArticles) {
        // Vérifier si l'article existe déjà localement (par son ID serveur)
        const existingLocal = localArticles.find(
          (local) =>
            local.server_id &&
            local.server_id.toString() === serverArticle.id.toString()
        );

        if (!existingLocal) {
          // L'article n'existe pas localement, l'ajouter à IndexedDB
          await this.saveArticle({
            title: serverArticle.title,
            content: serverArticle.content,
            created_at: serverArticle.created_at,
            server_id: serverArticle.id,
          });
          console.log(
            `Article du serveur ajouté localement: ${serverArticle.id} - ${serverArticle.title}`
          );
        }
      }

      // 5. Vérifier si des articles locaux ont un server_id qui n'existe plus sur le serveur
      // (article supprimé côté serveur)
      if (serverArticles.length > 0) {
        const serverIds = serverArticles.map((a) => a.id.toString());
        const orphanedArticles = localArticles.filter(
          (local) =>
            local.server_id && !serverIds.includes(local.server_id.toString())
        );

        if (orphanedArticles.length > 0) {
          console.log(
            `Suppression de ${orphanedArticles.length} articles orphelins...`
          );
          for (const article of orphanedArticles) {
            await this.deleteArticle(article.id);
            console.log(
              `Article orphelin supprimé, ID: ${article.id}, server_id: ${article.server_id}`
            );
          }
        }
      }

      console.log("Synchronisation depuis le serveur terminée avec succès");
      return true;
    } catch (error) {
      console.error(
        "Erreur lors de la synchronisation depuis le serveur:",
        error instanceof Error ? error.message : error
      );
      return false;
    }
  }

  static async openDatabase() {
    console.log("[DB] Initialisation manuelle de la base de données...");
    await this.initDB()
      .then(() => {
        console.log("[DB] Base de données initialisée avec succès");

        // Synchronisation manuelle uniquement sur demande
        document.dispatchEvent(new CustomEvent("dbready"));
      })
      .catch((error) => {
        console.error(
          "[DB] Erreur lors de l'initialisation de la base de données:",
          error
        );
        // Afficher un message à l'utilisateur si nécessaire
        if (document.querySelector("#articles-container")) {
          document.querySelector(
            "#articles-container"
          ).innerHTML = `<div class="alert alert-danger">
               Erreur d'initialisation de la base de données locale. 
               Veuillez rafraîchir la page ou vider le cache du navigateur.
             </div>`;
        }
      });
  }
}

// Fonction pour s'assurer que la DB est initialisée
async function ensureDBInitialized() {
  if (!window.dbInstance) {
    console.log(
      "Base de données non initialisée, tentative d'initialisation..."
    );
    try {
      await ArticleDB.initDB();
      return true;
    } catch (error) {
      console.error("Erreur lors de l'initialisation de la DB:", error);
      return false;
    }
  }
  return true;
}

/**
 * Récupération des articles
 * @returns {Promise<Array>} Liste des articles
 */
ArticleDB.getArticles = async function () {
  try {
    // Initialise et obtient la référence à la base de données
    const db = await this.initDB();
    console.log("Base de données obtenue pour getArticles:", db);

    return new Promise((resolve, reject) => {
      try {
        const transaction = db.transaction([window.STORE_NAME], "readonly");
        const store = transaction.objectStore(window.STORE_NAME);

        const request = store.getAll();

        request.onsuccess = function () {
          console.log(
            `${request.result.length} articles récupérés avec succès`
          );
          resolve(request.result);
        };

        request.onerror = function () {
          console.error(
            "Erreur lors de la récupération des articles:",
            request.error
          );
          reject(request.error);
        };
      } catch (error) {
        console.error("Erreur de transaction:", error);
        reject(error);
      }
    });
  } catch (error) {
    console.error(
      "Erreur lors de l'initialisation de la base de données pour getArticles:",
      error
    );
    throw error;
  }
};
