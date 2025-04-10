// Logs de débogage pour le chargement
console.log("[DB] Début du chargement de db.js");

// Vérifier que les variables ne sont pas déjà définies dans le scope global
// et les définir comme variables globales si nécessaires
if (typeof window.DB_NAME === "undefined") {
  console.log("[DB] Initialisation des variables globales");
  window.DB_NAME = "AuditDB";
  window.DB_VERSION = 2;
  window.STORE_NAME = "articles";
  window.dbInstance = null;
}

class ArticleDB {
  static _initInProgress = false;
  static DB_NAME = "AuditDB";
  static DB_VERSION = 2;
  static STORE_NAME = "articles";

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
        this.DB_NAME,
        "version:",
        this.DB_VERSION
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

      const request = indexedDB.open(this.DB_NAME, this.DB_VERSION);

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
    console.log("[DB] Début de la synchronisation avec le serveur...");
    try {
      // Récupérer les articles du serveur
      const fullUrl =
        window.location.origin +
        window.location.pathname.split("index.php")[0] +
        "index.php?action=articles&method=list&format=json";
      console.log("[DB] URL de synchronisation:", fullUrl);

      const response = await fetch(fullUrl, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "Cache-Control": "no-cache",
          Pragma: "no-cache",
        },
        credentials: "same-origin",
        cache: "no-store",
      });

      if (!response.ok) {
        const errorText = await response.text();
        console.error("[DB] Erreur HTTP:", response.status, errorText);
        throw new Error(`Erreur HTTP: ${response.status}`);
      }

      const contentType = response.headers.get("content-type");
      console.log("[DB] Type de contenu de la réponse:", contentType);

      if (!contentType || !contentType.includes("application/json")) {
        console.error("[DB] Type de contenu incorrect:", contentType);
        const text = await response.text();
        console.error(
          "[DB] Contenu de la réponse:",
          text.substring(0, 200) + "..."
        );
        throw new Error("La réponse du serveur n'est pas au format JSON");
      }

      const serverArticles = await response.json();
      console.log("[DB] Articles du serveur:", serverArticles);

      // Récupérer les articles locaux
      const localArticles = await this.getArticles();
      console.log("[DB] Articles locaux:", localArticles);

      // Liste des articles synchronisés pour suppression
      const synchronizedArticleIds = [];

      // Synchroniser les articles locaux avec le serveur
      for (const article of localArticles) {
        if (!article.server_id) {
          console.log(
            "[DB] Synchronisation de l'article local:",
            article.title
          );
          try {
            const createUrl =
              window.location.origin +
              window.location.pathname.split("index.php")[0] +
              "index.php?action=articles&method=create&format=json";
            console.log("[DB] URL de création d'article:", createUrl);

            const syncResponse = await fetch(createUrl, {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "Cache-Control": "no-cache",
                Pragma: "no-cache",
              },
              body: JSON.stringify({
                title: article.title,
                content: article.content,
              }),
              credentials: "same-origin",
              cache: "no-store",
            });

            if (!syncResponse.ok) {
              const errorText = await syncResponse.text();
              console.error(
                "[DB] Erreur HTTP lors de la synchronisation:",
                syncResponse.status,
                errorText
              );
              throw new Error(
                `Erreur HTTP lors de la synchronisation: ${syncResponse.status}`
              );
            }

            const contentType = syncResponse.headers.get("content-type");
            console.log(
              "[DB] Type de contenu de la réponse de création:",
              contentType
            );

            if (!contentType || !contentType.includes("application/json")) {
              console.error(
                "[DB] Type de contenu incorrect pour la réponse de création:",
                contentType
              );
              const text = await syncResponse.text();
              console.error(
                "[DB] Contenu de la réponse de création:",
                text.substring(0, 200) + "..."
              );
              throw new Error(
                "La réponse de création n'est pas au format JSON"
              );
            }

            const result = await syncResponse.json();
            console.log("[DB] Réponse de création d'article:", result);

            if (result.id) {
              // Ajouter l'ID de l'article à la liste des articles synchronisés
              synchronizedArticleIds.push(article.id);
              console.log(
                "[DB] Article synchronisé avec succès, ID serveur:",
                result.id
              );
            } else {
              console.error(
                "[DB] La réponse ne contient pas d'ID d'article:",
                result
              );
            }
          } catch (error) {
            console.error(
              "[DB] Erreur lors de la synchronisation de l'article:",
              error
            );
          }
        } else {
          // L'article est déjà synchronisé (a déjà un server_id)
          synchronizedArticleIds.push(article.id);
        }
      }

      // Supprimer les articles synchronisés de la base de données locale
      if (synchronizedArticleIds.length > 0) {
        console.log(
          "[DB] Suppression des articles synchronisés:",
          synchronizedArticleIds
        );
        for (const id of synchronizedArticleIds) {
          await this.deleteArticle(id);
        }
      }

      console.log("[DB] Synchronisation terminée avec succès");
      return true;
    } catch (error) {
      console.error(
        "[DB] Erreur lors de la synchronisation avec le serveur:",
        error
      );
      return false;
    }
  }

  static async syncArticleWithServer(article) {
    try {
      console.log("[DB] Tentative de synchronisation pour article:", article);

      // Ajouter une vérification de connectivité avant d'envoyer la requête
      if (!navigator.onLine) {
        console.log("[DB] Appareil hors ligne, impossible de synchroniser");
        return false;
      }

      // Ajouter un délai entre les tentatives pour éviter de surcharger le serveur
      await new Promise((resolve) => setTimeout(resolve, 500));

      const response = await fetch("index.php?action=articles&method=create", {
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
        "[DB] Statut de la réponse:",
        response.status,
        response.statusText
      );

      if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status}`);
      }

      const result = await response.json();
      console.log("[DB] Réponse du serveur:", result);

      // Mettre à jour l'article local avec l'ID du serveur
      if (result.id) {
        await this.updateArticle(article.id, { server_id: result.id });
        console.log("[DB] Article mis à jour avec l'ID serveur:", result.id);
        return true;
      }

      return false;
    } catch (error) {
      console.error(
        "[DB] Erreur lors de la synchronisation de l'article:",
        error
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

  static async updateArticle(id, updates) {
    try {
      const db = await this.initDB();
      return new Promise((resolve, reject) => {
        try {
          const transaction = db.transaction([window.STORE_NAME], "readwrite");
          const store = transaction.objectStore(window.STORE_NAME);

          // Récupérer d'abord l'article
          const getRequest = store.get(id);

          getRequest.onsuccess = () => {
            if (!getRequest.result) {
              console.error(
                "[DB] Article non trouvé pour mise à jour, ID:",
                id
              );
              reject(new Error(`Article avec ID ${id} non trouvé`));
              return;
            }

            // Mettre à jour l'article avec les nouvelles valeurs
            const updatedArticle = { ...getRequest.result, ...updates };
            const updateRequest = store.put(updatedArticle);

            updateRequest.onsuccess = () => {
              console.log("[DB] Article mis à jour avec succès, ID:", id);
              resolve(id);
            };

            updateRequest.onerror = () => {
              console.error(
                "[DB] Erreur lors de la mise à jour de l'article:",
                updateRequest.error
              );
              reject(updateRequest.error);
            };
          };

          getRequest.onerror = () => {
            console.error(
              "[DB] Erreur lors de la récupération de l'article pour mise à jour:",
              getRequest.error
            );
            reject(getRequest.error);
          };
        } catch (error) {
          console.error(
            "[DB] Erreur de transaction pour updateArticle:",
            error
          );
          reject(error);
        }
      });
    } catch (error) {
      console.error("[DB] Erreur d'initialisation pour updateArticle:", error);
      throw error;
    }
  }
}

// Exposer la classe ArticleDB globalement
console.log("[DB] Exposition de ArticleDB dans window");
window.ArticleDB = ArticleDB;

console.log("[DB] Fin du chargement de db.js");

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
