// Classe pour gérer les audits dans IndexedDB
class AuditDB {
  static DB_NAME = "AuditDB";
  static DB_VERSION = 3;
  static STORE_NAME = "audits";
  static AUDIT_POINTS_STORE = "audit_points";
  static _dbInstance = null;
  static _initInProgress = false;
  static _initPromise = null;

  /**
   * Initialise la base de données
   */
  static async init() {
    if (this._initPromise) {
      console.log("[AuditDB] Initialisation déjà en cours");
      return this._initPromise;
    }

    this._initPromise = new Promise((resolve, reject) => {
      const request = indexedDB.open("AuditDB", 3);

      request.onerror = (event) => {
        console.error(
          "[AuditDB] Erreur lors de l'ouverture de la base de données:",
          event.target.error
        );
        this._initPromise = null;
        reject(new Error("Erreur lors de l'ouverture de la base de données"));
      };

      request.onsuccess = (event) => {
        this._db = event.target.result;
        console.log("[AuditDB] Base de données ouverte avec succès");
        this._initPromise = null;
        resolve(this._db);
      };

      request.onupgradeneeded = (event) => {
        console.log("[AuditDB] Mise à niveau de la base de données");
        const db = event.target.result;

        // Créer le store "audits" s'il n'existe pas
        if (!db.objectStoreNames.contains("audits")) {
          console.log("[AuditDB] Création du store 'audits'");
          const store = db.createObjectStore("audits", {
            keyPath: "id",
            autoIncrement: true,
          });
          store.createIndex("date", "date", { unique: false });
        }

        // Créer le store "audit_points" s'il n'existe pas
        if (!db.objectStoreNames.contains("audit_points")) {
          console.log("[AuditDB] Création du store 'audit_points'");
          const store = db.createObjectStore("audit_points", {
            keyPath: "id",
            autoIncrement: true,
          });
          store.createIndex("audit_id", "audit_id", { unique: false });
        }
      };
    });

    return this._initPromise;
  }

  /**
   * Sauvegarde un audit dans IndexedDB
   */
  static async saveAudit(audit) {
    try {
      const db = await this.init();
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
      // S'assurer que la base de données est initialisée
      if (!this._db) {
        console.log(
          "[AuditDB] Base de données non initialisée, tentative d'initialisation"
        );
        await this.init();
      }

      if (!this._db) {
        throw new Error("La base de données n'a pas pu être initialisée");
      }

      return new Promise((resolve, reject) => {
        try {
          const transaction = this._db.transaction(
            [this.STORE_NAME],
            "readonly"
          );
          const store = transaction.objectStore(this.STORE_NAME);
          const request = store.getAll();

          request.onsuccess = () => {
            console.log(
              "[AuditDB] Audits récupérés avec succès:",
              request.result.length
            );
            resolve(request.result);
          };

          request.onerror = () => {
            console.error(
              "[AuditDB] Erreur lors de la récupération des audits:",
              request.error
            );
            reject(request.error);
          };

          transaction.oncomplete = () => {
            console.log("[AuditDB] Transaction terminée");
          };

          transaction.onerror = () => {
            console.error(
              "[AuditDB] Erreur de transaction:",
              transaction.error
            );
            reject(transaction.error);
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
        "[AuditDB] Erreur lors de la récupération des audits:",
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
      const db = await this.init();
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
      const db = await this.init();
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
      const db = await this.init();
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
      const db = await this.init();
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
      const db = await this.init();
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

// Exposer la classe AuditDB globalement
console.log("[AuditDB] Exposition de AuditDB dans window");
window.AuditDB = AuditDB;

console.log("[AuditDB] Fin du chargement de auditdb.js");
