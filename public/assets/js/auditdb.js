// Classe pour gérer les audits dans IndexedDB
class AuditDB {
  static _initInProgress = false;
  static DB_NAME = "AuditDB";
  static DB_VERSION = 2;
  static STORE_NAME = "audits";
  static AUDIT_POINTS_STORE = "audit_points";
  static dbInstance = null;

  /**
   * Initialise la base de données
   */
  static async initDB() {
    if (this.dbInstance) {
      console.log("[AuditDB] Instance de base de données déjà existante");
      return Promise.resolve(this.dbInstance);
    }

    // En cas d'initialisation en boucle, réinitialiser le flag après un délai
    // pour éviter le blocage permanent
    if (this._initInProgress) {
      console.log("[AuditDB] Une initialisation est déjà en cours, attente...");
      // Anti-blocage : réinitialiser le flag après 3 secondes si aucun événement n'est émis
      setTimeout(() => {
        if (this._initInProgress) {
          console.log(
            "[AuditDB] Réinitialisation du flag _initInProgress après timeout"
          );
          this._initInProgress = false;
        }
      }, 3000);

      return new Promise((resolve, reject) => {
        document.addEventListener(
          "auditdb_ready",
          () => {
            if (this.dbInstance) {
              resolve(this.dbInstance);
            } else {
              reject(
                new Error(
                  "La base de données n'a pas été initialisée correctement"
                )
              );
            }
          },
          { once: true }
        );

        // Définir un timeout pour éviter d'attendre indéfiniment
        setTimeout(() => {
          reject(
            new Error(
              "Timeout lors de l'attente de l'initialisation de la base de données"
            )
          );
        }, 5000);
      });
    }

    return new Promise((resolve, reject) => {
      console.log(
        "[AuditDB] Ouverture de la base de données:",
        this.DB_NAME,
        "version:",
        this.DB_VERSION
      );

      // Éviter l'initialisation en boucle
      if (this._initInProgress) {
        console.log("[AuditDB] Initialisation déjà en cours, en attente...");
        document.addEventListener(
          "auditdb_ready",
          () => {
            resolve(this.dbInstance);
          },
          { once: true }
        );
        return;
      }

      this._initInProgress = true;

      const request = indexedDB.open(this.DB_NAME, this.DB_VERSION);

      request.onerror = (event) => {
        console.error(
          "[AuditDB] Erreur lors de l'ouverture de la base de données:",
          event.target.error
        );
        this._initInProgress = false;
        reject(new Error("Erreur d'ouverture de la base de données AuditDB"));
      };

      request.onupgradeneeded = (event) => {
        console.log("[AuditDB] Mise à niveau de la base de données");
        const db = event.target.result;

        // Créer un object store pour les audits
        if (!db.objectStoreNames.contains(this.STORE_NAME)) {
          console.log("[AuditDB] Création du store:", this.STORE_NAME);
          const auditStore = db.createObjectStore(this.STORE_NAME, {
            keyPath: "id",
            autoIncrement: true,
          });

          // Créer des index pour des recherches rapides
          auditStore.createIndex("numero_site", "numero_site", {
            unique: false,
          });
          auditStore.createIndex("server_id", "server_id", { unique: false });
          auditStore.createIndex("statut", "statut", { unique: false });
        }

        // Créer un object store pour les points d'audit
        if (!db.objectStoreNames.contains(this.AUDIT_POINTS_STORE)) {
          console.log("[AuditDB] Création du store:", this.AUDIT_POINTS_STORE);
          const pointsStore = db.createObjectStore(this.AUDIT_POINTS_STORE, {
            keyPath: "id",
            autoIncrement: true,
          });

          // Créer des index pour des recherches rapides
          pointsStore.createIndex("audit_id", "audit_id", { unique: false });
          pointsStore.createIndex("point_vigilance_id", "point_vigilance_id", {
            unique: false,
          });
          pointsStore.createIndex("server_id", "server_id", { unique: false });
          pointsStore.createIndex(
            "audit_point_id",
            ["audit_id", "point_vigilance_id"],
            { unique: false }
          );
        }
      };

      request.onsuccess = (event) => {
        this.dbInstance = event.target.result;
        console.log("[AuditDB] Base de données ouverte avec succès");

        // Signaler que la base de données est prête
        this._initInProgress = false;
        document.dispatchEvent(new CustomEvent("auditdb_ready"));

        resolve(this.dbInstance);
      };
    });
  }

  /**
   * Sauvegarde un audit dans IndexedDB
   */
  static async saveAudit(audit) {
    try {
      const db = await this.initDB();
      return new Promise((resolve, reject) => {
        try {
          const transaction = db.transaction([this.STORE_NAME], "readwrite");
          const store = transaction.objectStore(this.STORE_NAME);

          let request;

          if (audit.id) {
            // Si l'audit a déjà un ID, on le met à jour
            request = store.put(audit);
            console.log(
              "[AuditDB] Mise à jour d'un audit existant, ID:",
              audit.id
            );
          } else {
            // Sinon, on crée un nouvel audit
            const newAudit = {
              ...audit,
              server_id: null, // S'assurer que server_id est null pour les nouveaux audits
              created_at:
                audit.created_at ||
                audit.date_creation ||
                new Date().toISOString(),
              updated_at: new Date().toISOString(),
            };
            request = store.add(newAudit);
            console.log("[AuditDB] Ajout d'un nouvel audit");
          }

          request.onsuccess = () => {
            console.log(
              "[AuditDB] Audit sauvegardé avec succès, ID local:",
              request.result
            );
            resolve(request.result);
          };

          request.onerror = () => {
            console.error(
              "[AuditDB] Erreur lors de la sauvegarde de l'audit:",
              request.error
            );
            reject(request.error);
          };
        } catch (error) {
          console.error(
            "[AuditDB] Erreur lors de la création de la transaction:",
            error
          );
          reject(error);
        }
      });
    } catch (error) {
      console.error(
        "[AuditDB] Erreur lors de l'initialisation de la base de données pour saveAudit:",
        error
      );
      throw error;
    }
  }

  /**
   * Récupère tous les audits stockés localement
   */
  static async getAudits() {
    try {
      const db = await this.initDB();
      return new Promise((resolve, reject) => {
        try {
          const transaction = db.transaction([this.STORE_NAME], "readonly");
          const store = transaction.objectStore(this.STORE_NAME);
          const request = store.getAll();

          request.onsuccess = function () {
            console.log(`[AuditDB] ${request.result.length} audits récupérés`);
            resolve(request.result);
          };

          request.onerror = function () {
            console.error(
              "[AuditDB] Erreur lors de la récupération des audits:",
              request.error
            );
            reject(request.error);
          };
        } catch (error) {
          console.error("[AuditDB] Erreur de transaction:", error);
          reject(error);
        }
      });
    } catch (error) {
      console.error(
        "[AuditDB] Erreur lors de l'initialisation de la base de données pour getAudits:",
        error
      );
      throw error;
    }
  }

  /**
   * Récupère les audits en cours
   */
  static async getAuditsEnCours() {
    const audits = await this.getAudits();
    return audits.filter((audit) => audit.statut === "en_cours");
  }

  /**
   * Récupère un audit par son ID
   */
  static async getAuditById(id) {
    try {
      const db = await this.initDB();
      return new Promise((resolve, reject) => {
        try {
          const transaction = db.transaction([this.STORE_NAME], "readonly");
          const store = transaction.objectStore(this.STORE_NAME);
          const request = store.get(id);

          request.onsuccess = function () {
            if (request.result) {
              console.log(`[AuditDB] Audit ID:${id} récupéré`);
              resolve(request.result);
            } else {
              console.log(`[AuditDB] Aucun audit trouvé avec l'ID:${id}`);
              resolve(null);
            }
          };

          request.onerror = function () {
            console.error(
              `[AuditDB] Erreur lors de la récupération de l'audit ID:${id}:`,
              request.error
            );
            reject(request.error);
          };
        } catch (error) {
          console.error("[AuditDB] Erreur de transaction:", error);
          reject(error);
        }
      });
    } catch (error) {
      console.error(
        "[AuditDB] Erreur lors de l'initialisation de la base de données pour getAuditById:",
        error
      );
      throw error;
    }
  }

  /**
   * Supprime un audit
   */
  static async deleteAudit(id) {
    try {
      const db = await this.initDB();
      // D'abord supprimer tous les points d'audit associés
      await this.deleteAuditPoints(id);

      return new Promise((resolve, reject) => {
        try {
          const transaction = db.transaction([this.STORE_NAME], "readwrite");
          const store = transaction.objectStore(this.STORE_NAME);
          const request = store.delete(id);

          request.onsuccess = () => {
            console.log("[AuditDB] Audit supprimé avec succès, ID local:", id);
            resolve();
          };

          request.onerror = () => {
            console.error(
              "[AuditDB] Erreur lors de la suppression de l'audit:",
              request.error
            );
            reject(request.error);
          };
        } catch (error) {
          console.error(
            "[AuditDB] Erreur lors de la création de la transaction:",
            error
          );
          reject(error);
        }
      });
    } catch (error) {
      console.error(
        "[AuditDB] Erreur lors de l'initialisation de la base de données pour deleteAudit:",
        error
      );
      throw error;
    }
  }

  /**
   * Ajoute ou met à jour un point d'audit
   */
  static async saveAuditPoint(point) {
    try {
      const db = await this.initDB();
      return new Promise((resolve, reject) => {
        try {
          const transaction = db.transaction(
            [this.AUDIT_POINTS_STORE],
            "readwrite"
          );
          const store = transaction.objectStore(this.AUDIT_POINTS_STORE);

          let request;
          if (point.id) {
            // Mise à jour d'un point existant
            request = store.put(point);
          } else {
            // Création d'un nouveau point
            const newPoint = {
              ...point,
              server_id: null,
              updated_at: new Date().toISOString(),
            };
            request = store.add(newPoint);
          }

          request.onsuccess = () => {
            console.log(
              "[AuditDB] Point d'audit sauvegardé, ID:",
              request.result
            );
            resolve(request.result);
          };

          request.onerror = () => {
            console.error(
              "[AuditDB] Erreur lors de la sauvegarde du point d'audit:",
              request.error
            );
            reject(request.error);
          };
        } catch (error) {
          console.error(
            "[AuditDB] Erreur lors de la création de la transaction:",
            error
          );
          reject(error);
        }
      });
    } catch (error) {
      console.error(
        "[AuditDB] Erreur lors de l'initialisation de la base de données pour saveAuditPoint:",
        error
      );
      throw error;
    }
  }

  /**
   * Récupère tous les points d'un audit
   */
  static async getAuditPoints(auditId) {
    try {
      const db = await this.initDB();
      return new Promise((resolve, reject) => {
        try {
          const transaction = db.transaction(
            [this.AUDIT_POINTS_STORE],
            "readonly"
          );
          const store = transaction.objectStore(this.AUDIT_POINTS_STORE);
          const index = store.index("audit_id");
          const request = index.getAll(auditId);

          request.onsuccess = function () {
            console.log(
              `[AuditDB] ${request.result.length} points récupérés pour l'audit ${auditId}`
            );
            resolve(request.result);
          };

          request.onerror = function () {
            console.error(
              "[AuditDB] Erreur lors de la récupération des points:",
              request.error
            );
            reject(request.error);
          };
        } catch (error) {
          console.error("[AuditDB] Erreur de transaction:", error);
          reject(error);
        }
      });
    } catch (error) {
      console.error(
        "[AuditDB] Erreur lors de l'initialisation de la base de données pour getAuditPoints:",
        error
      );
      throw error;
    }
  }

  /**
   * Supprime tous les points d'un audit
   */
  static async deleteAuditPoints(auditId) {
    try {
      const db = await this.initDB();
      const points = await this.getAuditPoints(auditId);

      return new Promise(async (resolve, reject) => {
        try {
          const transaction = db.transaction(
            [this.AUDIT_POINTS_STORE],
            "readwrite"
          );
          const store = transaction.objectStore(this.AUDIT_POINTS_STORE);

          let deleteCount = 0;
          for (const point of points) {
            const request = store.delete(point.id);
            request.onsuccess = () => deleteCount++;
          }

          transaction.oncomplete = () => {
            console.log(
              `[AuditDB] ${deleteCount} points d'audit supprimés pour l'audit ${auditId}`
            );
            resolve();
          };

          transaction.onerror = (event) => {
            console.error(
              "[AuditDB] Erreur lors de la suppression des points:",
              event.target.error
            );
            reject(event.target.error);
          };
        } catch (error) {
          console.error(
            "[AuditDB] Erreur lors de la création de la transaction:",
            error
          );
          reject(error);
        }
      });
    } catch (error) {
      console.error(
        "[AuditDB] Erreur lors de l'initialisation de la base de données pour deleteAuditPoints:",
        error
      );
      throw error;
    }
  }

  /**
   * Récupère les audits en attente de synchronisation
   */
  static async getPendingAudits() {
    const audits = await this.getAudits();
    return audits.filter((audit) => !audit.server_id);
  }

  /**
   * Synchronise les audits avec le serveur
   */
  static async syncWithServer() {
    try {
      console.log("[AuditDB] Début de la synchronisation des audits");
      const pendingAudits = await this.getPendingAudits();

      if (pendingAudits.length === 0) {
        console.log("[AuditDB] Aucun audit à synchroniser");
        return {
          success: true,
          message: "Aucun audit à synchroniser",
          count: 0,
        };
      }

      let successCount = 0;
      let errorCount = 0;

      for (const audit of pendingAudits) {
        try {
          const success = await this.syncAuditWithServer(audit);
          if (success) {
            successCount++;
          } else {
            errorCount++;
          }
        } catch (error) {
          console.error(
            `[AuditDB] Erreur lors de la synchronisation de l'audit ${audit.id}:`,
            error
          );
          errorCount++;
        }
      }

      console.log(
        `[AuditDB] Synchronisation terminée: ${successCount} réussis, ${errorCount} échecs`
      );
      return {
        success: errorCount === 0,
        message: `${successCount} audits synchronisés, ${errorCount} échecs`,
        count: successCount,
      };
    } catch (error) {
      console.error(
        "[AuditDB] Erreur globale lors de la synchronisation:",
        error
      );
      return {
        success: false,
        message: "Erreur lors de la synchronisation",
        error: error.message,
      };
    }
  }

  /**
   * Synchronise un seul audit avec le serveur
   */
  static async syncAuditWithServer(audit) {
    try {
      console.log(
        `[AuditDB] Tentative de synchronisation de l'audit ${audit.id}`
      );

      // Récupérer les points d'audit associés
      const points = await this.getAuditPoints(audit.id);

      // Préparer les données pour l'envoi
      const formData = new FormData();
      formData.append("numero_site", audit.numero_site);
      formData.append("nom_entreprise", audit.nom_entreprise);
      formData.append("date_creation", audit.date_creation);
      formData.append("statut", audit.statut || "en_cours");

      // Ajouter les points de vigilance s'il y en a
      if (points && points.length > 0) {
        for (let i = 0; i < points.length; i++) {
          formData.append(
            `points[${i}][point_vigilance_id]`,
            points[i].point_vigilance_id
          );
          formData.append(`points[${i}][categorie_id]`, points[i].categorie_id);
          formData.append(
            `points[${i}][sous_categorie_id]`,
            points[i].sous_categorie_id
          );
        }
      }

      // Envoyer l'audit au serveur
      const response = await fetch("./index.php?action=audits&method=create", {
        method: "POST",
        body: formData,
      });

      if (response.ok) {
        // URL de redirection obtenue de la réponse
        const redirectUrl = response.url;

        // Extraire l'ID de l'audit du serveur depuis l'URL de redirection
        const matches = redirectUrl.match(/id=(\d+)/);
        if (matches && matches[1]) {
          const serverId = parseInt(matches[1]);

          // Supprimer l'audit local
          await this.deleteAudit(audit.id);

          console.log(
            `[AuditDB] Audit ${audit.id} synchronisé avec succès, ID serveur: ${serverId}`
          );
          return true;
        }
      }

      console.error(
        `[AuditDB] Échec de la synchronisation de l'audit ${audit.id}`
      );
      return false;
    } catch (error) {
      console.error(
        `[AuditDB] Erreur lors de la synchronisation de l'audit ${audit.id}:`,
        error
      );
      return false;
    }
  }
}
