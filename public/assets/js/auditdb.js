// Classe pour gérer les audits dans IndexedDB
class AuditDB {
  static DB_NAME = "AuditDB";
  static DB_VERSION = 4;
  static STORE_NAME = "audits";
  static AUDIT_POINTS_STORE = "audit_points";
  static PENDING_EVALUATIONS_STORE = "pending_evaluations";
  static PENDING_DOCUMENTS_STORE = "pending_documents";
  static _dbInstance = null;
  static _initInProgress = false;
  static _initPromise = null;

  /**
   * Initialise la base de données
   */
  static async init() {
    console.log("[AuditDB] Début de l'initialisation de la base de données");
    if (this._initPromise) {
      console.log("[AuditDB] Initialisation déjà en cours");
      return this._initPromise;
    }

    this._initPromise = new Promise((resolve, reject) => {
      console.log(
        "[AuditDB] Ouverture de IndexedDB:",
        this.DB_NAME,
        "version",
        this.DB_VERSION
      );
      const request = indexedDB.open(this.DB_NAME, this.DB_VERSION);

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

        // Vérifier les noms des stores
        console.log(
          "[AuditDB] Stores disponibles:",
          Array.from(this._db.objectStoreNames)
        );

        this._initPromise = null;
        resolve(this._db);
      };

      request.onupgradeneeded = (event) => {
        console.log("[AuditDB] Mise à niveau de la base de données");
        const db = event.target.result;

        // Créer le store "audits" s'il n'existe pas
        if (!db.objectStoreNames.contains(this.STORE_NAME)) {
          console.log(`[AuditDB] Création du store '${this.STORE_NAME}'`);
          const store = db.createObjectStore(this.STORE_NAME, {
            keyPath: "id",
            autoIncrement: true,
          });
          store.createIndex("date", "date", { unique: false });
        }

        // Créer le store "audit_points" s'il n'existe pas
        if (!db.objectStoreNames.contains(this.AUDIT_POINTS_STORE)) {
          console.log(
            `[AuditDB] Création du store '${this.AUDIT_POINTS_STORE}'`
          );
          const store = db.createObjectStore(this.AUDIT_POINTS_STORE, {
            keyPath: "id",
            autoIncrement: true,
          });
          store.createIndex("audit_id", "audit_id", { unique: false });
        }

        // Créer le store pour les évaluations en attente
        if (!db.objectStoreNames.contains(this.PENDING_EVALUATIONS_STORE)) {
          console.log(
            `[AuditDB] Création du store '${this.PENDING_EVALUATIONS_STORE}'`
          );
          const store = db.createObjectStore(this.PENDING_EVALUATIONS_STORE, {
            keyPath: "id",
            autoIncrement: true,
          });
          store.createIndex("audit_id", "audit_id", { unique: false });
          store.createIndex("point_vigilance_id", "point_vigilance_id", {
            unique: false,
          });
          store.createIndex("timestamp", "timestamp", { unique: false });
        }

        // Créer le store pour les documents en attente
        if (!db.objectStoreNames.contains(this.PENDING_DOCUMENTS_STORE)) {
          console.log(
            `[AuditDB] Création du store '${this.PENDING_DOCUMENTS_STORE}'`
          );
          const store = db.createObjectStore(this.PENDING_DOCUMENTS_STORE, {
            keyPath: "id",
            autoIncrement: true,
          });
          store.createIndex("audit_id", "audit_id", { unique: false });
          store.createIndex("point_vigilance_id", "point_vigilance_id", {
            unique: false,
          });
          store.createIndex("type", "type", { unique: false });
          store.createIndex("timestamp", "timestamp", { unique: false });
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

      // Construire l'URL absolue pour l'API
      const currentUrl = window.location;
      const baseUrl =
        currentUrl.origin + currentUrl.pathname.split("index.php")[0];
      const apiUrl = baseUrl + "index.php?action=audits&method=evaluerPoint";

      console.log(`[AuditDB] 🌐 URL API: ${apiUrl}`);

      // Ajouter des paramètres de débogage pour éviter le cache
      const timestamp = new Date().getTime();
      const finalUrl = `${apiUrl}&_t=${timestamp}`;
      console.log(`[AuditDB] 🌐 URL finale avec timestamp: ${finalUrl}`);

      // Log complet du FormData pour débogage
      console.log("[AuditDB] 📦 FormData préparé");
      for (const [key, value] of formData.entries()) {
        console.log(`[AuditDB] 📦 ${key}: ${value}`);
      }

      // Envoyer la requête
      console.log(`[AuditDB] 📤 Envoi de l'évaluation au serveur...`);

      // Utiliser fetch avec un timeout et un meilleur traitement d'erreurs
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 secondes timeout

      try {
        const response = await fetch(finalUrl, {
          method: "POST",
          body: formData,
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
            "Cache-Control": "no-cache, no-store, must-revalidate",
          },
          credentials: "same-origin",
          signal: controller.signal,
        });

        clearTimeout(timeoutId);

        console.log(
          `[AuditDB] 📥 Réponse: ${response.status} ${response.statusText}`
        );

        if (!response.ok) {
          const errorText = await response.text();
          console.error(
            `[AuditDB] ❌ Erreur HTTP: ${response.status}`,
            errorText
          );
          return false;
        }

        // Traiter la réponse
        let success = false;
        try {
          // Essayer de traiter comme JSON
          const responseText = await response.text();
          console.log(`[AuditDB] 📝 Réponse brute: ${responseText}`);

          if (
            responseText.includes("success") ||
            response.status === 200 ||
            responseText.includes("OK")
          ) {
            success = true;
          } else {
            try {
              const data = JSON.parse(responseText);
              success = !!data.success;
            } catch (jsonError) {
              // Si ce n'est pas du JSON valide mais que le statut est 200, considérer comme succès
              success = response.status >= 200 && response.status < 300;
            }
          }

          if (success) {
            // Marquer comme synchronisée
            await this.markEvaluationAsSynced(audit.id);
            console.log(
              `[AuditDB] ✅ Évaluation ID:${audit.id} synchronisée avec succès`
            );
            return true;
          } else {
            console.error(`[AuditDB] ❌ Échec de la synchronisation`);
            return false;
          }
        } catch (responseError) {
          console.error(
            `[AuditDB] ❌ Erreur traitement réponse: ${responseError.message}`
          );

          // Si le statut est 200 malgré l'erreur de parsing, considérer comme un succès
          if (response.status >= 200 && response.status < 300) {
            await this.markEvaluationAsSynced(audit.id);
            console.log(
              `[AuditDB] ✅ Évaluation ID:${audit.id} synchronisée malgré l'erreur de parsing`
            );
            return true;
          } else {
            return false;
          }
        }
      } catch (fetchError) {
        clearTimeout(timeoutId);
        console.error(`[AuditDB] ❌ Erreur réseau: ${fetchError.message}`);
        return false;
      }
    } catch (error) {
      console.error(
        `[AuditDB] Erreur lors de la synchronisation de l'audit ${audit.id}:`,
        error
      );
      return false;
    }
  }

  /**
   * Sauvegarde une évaluation de point de vigilance en attente
   * @param {Object} evaluation Les données d'évaluation
   * @returns {Promise<number>} L'ID de l'évaluation sauvegardée
   */
  static async savePendingEvaluation(evaluation) {
    try {
      console.log(
        "[AuditDB] Début savePendingEvaluation avec données:",
        evaluation
      );

      // S'assurer que la base de données est initialisée
      const db = await this.init();
      console.log(
        "[AuditDB] Base de données initialisée pour savePendingEvaluation"
      );

      return new Promise((resolve, reject) => {
        try {
          // Ajouter un timestamp pour ordonner les syncronisations
          const evalData = {
            ...evaluation,
            timestamp: evaluation.timestamp || new Date().toISOString(),
            synced: false,
          };

          console.log(
            "[AuditDB] Données préparées pour le stockage:",
            evalData
          );

          // Créer une transaction en mode écriture
          const transaction = db.transaction(
            [this.PENDING_EVALUATIONS_STORE],
            "readwrite"
          );
          console.log(
            "[AuditDB] Transaction créée pour",
            this.PENDING_EVALUATIONS_STORE
          );

          // Récupérer le store d'objets
          const store = transaction.objectStore(this.PENDING_EVALUATIONS_STORE);
          console.log("[AuditDB] Store récupéré");

          // Ajouter l'évaluation dans le store
          const request = store.add(evalData);
          console.log("[AuditDB] Requête d'ajout envoyée");

          request.onsuccess = () => {
            console.log(
              "[AuditDB] Évaluation en attente sauvegardée avec succès, ID:",
              request.result
            );
            resolve(request.result);
          };

          request.onerror = () => {
            console.error(
              "[AuditDB] Erreur lors de la sauvegarde de l'évaluation:",
              request.error
            );
            reject(request.error);
          };

          // Gestion des erreurs de transaction
          transaction.oncomplete = () => {
            console.log("[AuditDB] Transaction terminée avec succès");
          };

          transaction.onerror = () => {
            console.error(
              "[AuditDB] Erreur de transaction:",
              transaction.error
            );
            reject(transaction.error);
          };
        } catch (error) {
          console.error("[AuditDB] Erreur de transaction:", error);
          reject(error);
        }
      });
    } catch (error) {
      console.error("[AuditDB] Erreur d'initialisation:", error);
      throw error;
    }
  }

  /**
   * Sauvegarde un document (photo ou fichier) en attente
   * @param {Object} document Les données du document avec le contenu en base64
   * @returns {Promise<number>} L'ID du document sauvegardé
   */
  static async savePendingDocument(document) {
    try {
      const db = await this.init();
      return new Promise((resolve, reject) => {
        try {
          // Ajouter un timestamp pour ordonner les syncronisations
          const docData = {
            ...document,
            timestamp: new Date().toISOString(),
            synced: false,
          };

          const transaction = db.transaction(
            [this.PENDING_DOCUMENTS_STORE],
            "readwrite"
          );
          const store = transaction.objectStore(this.PENDING_DOCUMENTS_STORE);
          const request = store.add(docData);

          request.onsuccess = () => {
            console.log(
              "[AuditDB] Document en attente sauvegardé, ID:",
              request.result
            );
            resolve(request.result);
          };

          request.onerror = () => {
            console.error(
              "[AuditDB] Erreur lors de la sauvegarde du document:",
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
      console.error("[AuditDB] Erreur d'initialisation:", error);
      throw error;
    }
  }

  /**
   * Récupère toutes les évaluations en attente
   * @returns {Promise<Array>} Liste des évaluations en attente
   */
  static async getPendingEvaluations() {
    try {
      console.log(
        "[AuditDB] 🔍 Début de récupération des évaluations en attente"
      );

      // S'assurer que la base de données est initialisée
      const db = await this.init();
      console.log(
        "[AuditDB] ✓ Base de données initialisée pour getPendingEvaluations"
      );

      // Afficher explicitement les stores disponibles pour le débogage
      console.log(
        "[AuditDB] 📋 Stores disponibles:",
        Array.from(db.objectStoreNames)
      );

      // Vérifier si le store existe
      if (!db.objectStoreNames.contains(this.PENDING_EVALUATIONS_STORE)) {
        console.error(
          `[AuditDB] ❌ Store '${this.PENDING_EVALUATIONS_STORE}' non trouvé!`
        );
        return [];
      }

      return new Promise((resolve, reject) => {
        try {
          // Utiliser directement une transaction simple
          const transaction = db.transaction(
            [this.PENDING_EVALUATIONS_STORE],
            "readonly"
          );
          console.log("[AuditDB] ✓ Transaction créée");

          const store = transaction.objectStore(this.PENDING_EVALUATIONS_STORE);
          console.log("[AuditDB] ✓ Store obtenu:", store.name);

          // Utiliser getAll pour récupérer toutes les évaluations
          const request = store.getAll();
          console.log("[AuditDB] ✓ Requête getAll envoyée");

          request.onerror = (event) => {
            console.error(
              "[AuditDB] ❌ Erreur lors de la récupération des évaluations:",
              event.target.error
            );
            reject(event.target.error);
          };

          request.onsuccess = (event) => {
            const result = event.target.result || [];
            console.log(`[AuditDB] ✓ ${result.length} évaluations récupérées`);

            // Log détaillé des données trouvées
            if (result.length > 0) {
              console.log(
                "[AuditDB] 📝 Évaluations trouvées:",
                JSON.stringify(result)
              );
            } else {
              // En cas de tableau vide, vérifions directement avec un curseur
              console.log(
                "[AuditDB] ⚠️ Aucune évaluation trouvée avec getAll(), essai avec curseur"
              );

              // Tentative alternative avec curseur
              const cursorRequest = store.openCursor();
              let items = [];

              cursorRequest.onsuccess = (e) => {
                const cursor = e.target.result;
                if (cursor) {
                  items.push(cursor.value);
                  cursor.continue();
                } else {
                  console.log(
                    `[AuditDB] ✓ ${items.length} évaluations récupérées via curseur`
                  );
                  if (items.length > 0) {
                    console.log(
                      "[AuditDB] 📝 Évaluations trouvées via curseur:",
                      JSON.stringify(items)
                    );
                    resolve(items);
                  } else {
                    console.log(
                      "[AuditDB] ❌ Aucune évaluation trouvée même avec curseur"
                    );
                    resolve([]);
                  }
                }
              };

              cursorRequest.onerror = (err) => {
                console.error(
                  "[AuditDB] ❌ Erreur lors du parcours avec curseur:",
                  err
                );
                // Retourner le résultat vide original
                resolve(result);
              };

              // Sortir de la fonction ici pour laisser le curseur faire son travail
              return;
            }

            resolve(result);
          };
        } catch (error) {
          console.error(
            "[AuditDB] ❌ Erreur lors de la création de transaction:",
            error
          );
          reject(error);
        }
      });
    } catch (error) {
      console.error("[AuditDB] ❌ Erreur générale:", error);
      return [];
    }
  }

  /**
   * Récupère tous les documents en attente
   * @returns {Promise<Array>} Les documents en attente
   */
  static async getPendingDocuments() {
    try {
      const db = await this.init();
      return new Promise((resolve, reject) => {
        const transaction = db.transaction(
          [this.PENDING_DOCUMENTS_STORE],
          "readonly"
        );
        const store = transaction.objectStore(this.PENDING_DOCUMENTS_STORE);
        const request = store.getAll();

        request.onsuccess = () => {
          console.log(
            "[AuditDB] Documents en attente récupérés:",
            request.result.length
          );
          resolve(request.result);
        };

        request.onerror = () => {
          console.error(
            "[AuditDB] Erreur lors de la récupération des documents:",
            request.error
          );
          reject(request.error);
        };
      });
    } catch (error) {
      console.error(
        "[AuditDB] Erreur lors de la récupération des documents:",
        error
      );
      throw error;
    }
  }

  /**
   * Marque une évaluation comme synchronisée
   * @param {number} id L'ID de l'évaluation
   * @returns {Promise<boolean>} true si la mise à jour a réussi
   */
  static async markEvaluationAsSynced(id) {
    try {
      const db = await this.init();
      return new Promise((resolve, reject) => {
        const transaction = db.transaction(
          [this.PENDING_EVALUATIONS_STORE],
          "readwrite"
        );
        const store = transaction.objectStore(this.PENDING_EVALUATIONS_STORE);
        const getRequest = store.get(id);

        getRequest.onsuccess = () => {
          if (getRequest.result) {
            const evaluation = getRequest.result;
            evaluation.synced = true;
            evaluation.synced_at = new Date().toISOString();

            const updateRequest = store.put(evaluation);
            updateRequest.onsuccess = () => {
              console.log(
                "[AuditDB] Évaluation marquée comme synchronisée:",
                id
              );
              resolve(true);
            };

            updateRequest.onerror = () => {
              console.error(
                "[AuditDB] Erreur lors de la mise à jour:",
                updateRequest.error
              );
              reject(updateRequest.error);
            };
          } else {
            console.warn("[AuditDB] Évaluation non trouvée:", id);
            resolve(false);
          }
        };

        getRequest.onerror = () => {
          console.error(
            "[AuditDB] Erreur lors de la récupération de l'évaluation:",
            getRequest.error
          );
          reject(getRequest.error);
        };
      });
    } catch (error) {
      console.error(
        "[AuditDB] Erreur lors du marquage de l'évaluation:",
        error
      );
      throw error;
    }
  }

  /**
   * Marque un document comme synchronisé
   * @param {number} id L'ID du document
   * @returns {Promise<boolean>} true si la mise à jour a réussi
   */
  static async markDocumentAsSynced(id) {
    try {
      const db = await this.init();
      return new Promise((resolve, reject) => {
        const transaction = db.transaction(
          [this.PENDING_DOCUMENTS_STORE],
          "readwrite"
        );
        const store = transaction.objectStore(this.PENDING_DOCUMENTS_STORE);
        const getRequest = store.get(id);

        getRequest.onsuccess = () => {
          if (getRequest.result) {
            const document = getRequest.result;
            document.synced = true;
            document.synced_at = new Date().toISOString();

            const updateRequest = store.put(document);
            updateRequest.onsuccess = () => {
              console.log("[AuditDB] Document marqué comme synchronisé:", id);
              resolve(true);
            };

            updateRequest.onerror = () => {
              console.error(
                "[AuditDB] Erreur lors de la mise à jour:",
                updateRequest.error
              );
              reject(updateRequest.error);
            };
          } else {
            console.warn("[AuditDB] Document non trouvé:", id);
            resolve(false);
          }
        };

        getRequest.onerror = () => {
          console.error(
            "[AuditDB] Erreur lors de la récupération du document:",
            getRequest.error
          );
          reject(getRequest.error);
        };
      });
    } catch (error) {
      console.error("[AuditDB] Erreur lors du marquage du document:", error);
      throw error;
    }
  }

  /**
   * Synchronise toutes les évaluations et documents en attente
   * @returns {Promise<Object>} Résultats de la synchronisation
   */
  static async syncPendingData() {
    try {
      console.log("[AuditDB] 🔄 Début de la synchronisation");

      // S'assurer que la base est initialisée
      const db = await this.init();
      console.log("[AuditDB] ✓ Base de données initialisée");

      // Vérifier que nous sommes en ligne
      if (typeof navigator !== "undefined" && !navigator.onLine) {
        console.log("[AuditDB] ❌ Impossible de synchroniser, hors ligne");
        throw new Error("Hors ligne");
      }

      // Récupérer les données en attente
      console.log("[AuditDB] 🔍 Récupération des données en attente");
      let pendingEvaluations;

      try {
        pendingEvaluations = await this.getPendingEvaluations();
        console.log(
          `[AuditDB] ✓ ${pendingEvaluations.length} évaluations récupérées`
        );

        // Debug - Afficher toutes les évaluations en attente
        if (pendingEvaluations.length > 0) {
          console.log(
            "[AuditDB] 📋 Liste des évaluations en attente:",
            pendingEvaluations.map((e) => ({
              id: e.id,
              audit_id: e.audit_id,
              point_vigilance_id: e.point_vigilance_id,
              synced: e.synced,
            }))
          );
        }
      } catch (fetchError) {
        console.error(
          "[AuditDB] ❌ Erreur lors de la récupération des évaluations:",
          fetchError
        );
        pendingEvaluations = [];
      }

      // Filtrer uniquement celles qui ne sont pas encore synchronisées
      const unsyncedEvaluations = pendingEvaluations.filter((e) => !e.synced);
      console.log(
        `[AuditDB] 📊 ${unsyncedEvaluations.length} évaluations non synchronisées`
      );

      const results = {
        evaluations: { success: 0, failed: 0 },
        documents: { success: 0, failed: 0 },
      };

      // S'il n'y a rien à synchroniser, renvoyer immédiatement le résultat
      if (unsyncedEvaluations.length === 0) {
        console.log("[AuditDB] ✓ Aucune évaluation à synchroniser");
        return results;
      }

      // Pour chaque évaluation non synchronisée
      for (const evaluation of unsyncedEvaluations) {
        try {
          console.log(
            `[AuditDB] 🔄 Traitement de l'évaluation ID:${evaluation.id}`
          );

          // Vérifier que les champs obligatoires sont présents
          if (!evaluation.audit_id || !evaluation.point_vigilance_id) {
            console.error(
              `[AuditDB] ❌ Évaluation ID:${evaluation.id} incomplète, champs obligatoires manquants`
            );
            results.evaluations.failed++;
            continue;
          }

          // Préparer les données pour l'envoi
          const formData = new FormData();
          formData.append("audit_id", evaluation.audit_id);
          formData.append("point_vigilance_id", evaluation.point_vigilance_id);

          // Ajouter d'autres champs s'ils existent
          const additionalFields = [
            "non_audite",
            "mode_preuve",
            "resultat",
            "justification",
            "plan_action_numero",
            "plan_action_priorite",
            "plan_action_description",
          ];

          for (const field of additionalFields) {
            if (evaluation[field] !== undefined && evaluation[field] !== null) {
              // Convertir explicitement en chaîne
              formData.append(field, String(evaluation[field]));
              console.log(
                `[AuditDB] ↪ Ajout champ ${field}='${evaluation[field]}'`
              );
            }
          }

          // Construire l'URL absolue pour l'API
          const currentUrl = window.location;
          const baseUrl =
            currentUrl.origin + currentUrl.pathname.split("index.php")[0];
          const apiUrl =
            baseUrl + "index.php?action=audits&method=evaluerPoint";

          console.log(`[AuditDB] 🌐 URL API: ${apiUrl}`);

          // Ajouter des paramètres de débogage pour éviter le cache
          const timestamp = new Date().getTime();
          const finalUrl = `${apiUrl}&_t=${timestamp}`;
          console.log(`[AuditDB] 🌐 URL finale avec timestamp: ${finalUrl}`);

          // Log complet du FormData pour débogage
          console.log("[AuditDB] 📦 FormData préparé");
          for (const [key, value] of formData.entries()) {
            console.log(`[AuditDB] 📦 ${key}: ${value}`);
          }

          // Envoyer la requête
          console.log(`[AuditDB] 📤 Envoi de l'évaluation au serveur...`);

          // Utiliser fetch avec un timeout et un meilleur traitement d'erreurs
          const controller = new AbortController();
          const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 secondes timeout

          try {
            const response = await fetch(finalUrl, {
              method: "POST",
              body: formData,
              headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "Cache-Control": "no-cache, no-store, must-revalidate",
              },
              credentials: "same-origin",
              signal: controller.signal,
            });

            clearTimeout(timeoutId);

            console.log(
              `[AuditDB] 📥 Réponse: ${response.status} ${response.statusText}`
            );

            if (!response.ok) {
              const errorText = await response.text();
              console.error(
                `[AuditDB] ❌ Erreur HTTP: ${response.status}`,
                errorText
              );
              results.evaluations.failed++;
              continue;
            }

            // Traiter la réponse
            let success = false;
            try {
              // Essayer de traiter comme JSON
              const responseText = await response.text();
              console.log(`[AuditDB] 📝 Réponse brute: ${responseText}`);

              if (
                responseText.includes("success") ||
                response.status === 200 ||
                responseText.includes("OK")
              ) {
                success = true;
              } else {
                try {
                  const data = JSON.parse(responseText);
                  success = !!data.success;
                } catch (jsonError) {
                  // Si ce n'est pas du JSON valide mais que le statut est 200, considérer comme succès
                  success = response.status >= 200 && response.status < 300;
                }
              }

              if (success) {
                // Marquer comme synchronisée
                await this.markEvaluationAsSynced(evaluation.id);
                console.log(
                  `[AuditDB] ✅ Évaluation ID:${evaluation.id} synchronisée avec succès`
                );
                results.evaluations.success++;
              } else {
                console.error(`[AuditDB] ❌ Échec de la synchronisation`);
                results.evaluations.failed++;
              }
            } catch (responseError) {
              console.error(
                `[AuditDB] ❌ Erreur traitement réponse: ${responseError.message}`
              );

              // Si le statut est 200 malgré l'erreur de parsing, considérer comme un succès
              if (response.status >= 200 && response.status < 300) {
                await this.markEvaluationAsSynced(evaluation.id);
                console.log(
                  `[AuditDB] ✅ Évaluation ID:${evaluation.id} synchronisée malgré l'erreur de parsing`
                );
                results.evaluations.success++;
              } else {
                results.evaluations.failed++;
              }
            }
          } catch (fetchError) {
            clearTimeout(timeoutId);
            console.error(`[AuditDB] ❌ Erreur réseau: ${fetchError.message}`);
            results.evaluations.failed++;
          }
        } catch (error) {
          console.error(
            `[AuditDB] ❌ Erreur lors de la synchronisation: ${error.message}`,
            error
          );
          results.evaluations.failed++;
        }
      }

      // Synchroniser les documents
      try {
        const pendingDocuments = await this.getPendingDocuments();
        console.log(
          `[AuditDB] 📊 ${pendingDocuments.length} documents en attente`
        );

        const unsyncedDocuments = pendingDocuments.filter((d) => !d.synced);
        console.log(
          `[AuditDB] 📊 ${unsyncedDocuments.length} documents non synchronisés`
        );

        // Traiter chaque document
        // ... code existant ...
      } catch (docError) {
        console.error(
          "[AuditDB] ❌ Erreur lors de la synchronisation des documents:",
          docError
        );
      }

      console.log("[AuditDB] ✓ Synchronisation terminée:", results);
      return results;
    } catch (error) {
      console.error("[AuditDB] ❌ Erreur générale de synchronisation:", error);
      throw error;
    }
  }

  /**
   * Test direct pour sauvegarder une évaluation (pour le débogage)
   */
  static async testSaveEvaluation() {
    console.log("[AuditDB] Test de sauvegarde d'une évaluation...");
    try {
      const db = await this.init();
      console.log("[AuditDB] Base de données initialisée pour test");

      const testData = {
        audit_id: "999",
        point_vigilance_id: "888",
        non_audite: "1",
        justification: "Test direct de sauvegarde",
        timestamp: new Date().toISOString(),
        synced: false,
      };

      return new Promise((resolve, reject) => {
        try {
          console.log("[AuditDB] Création de la transaction pour test");
          const transaction = db.transaction(
            [this.PENDING_EVALUATIONS_STORE],
            "readwrite"
          );
          console.log("[AuditDB] Transaction créée");

          const store = transaction.objectStore(this.PENDING_EVALUATIONS_STORE);
          console.log("[AuditDB] Store récupéré:", store.name);

          console.log("[AuditDB] Ajout des données test:", testData);
          const request = store.add(testData);

          request.onsuccess = () => {
            console.log("[AuditDB] Test réussi, ID:", request.result);
            resolve(request.result);
          };

          request.onerror = () => {
            console.error("[AuditDB] Erreur pendant le test:", request.error);
            reject(request.error);
          };
        } catch (error) {
          console.error(
            "[AuditDB] Erreur pendant la création de la transaction de test:",
            error
          );
          reject(error);
        }
      });
    } catch (error) {
      console.error(
        "[AuditDB] Erreur pendant l'initialisation pour le test:",
        error
      );
      throw error;
    }
  }

  /**
   * Méthode de diagnostic pour débogage
   * @returns {Promise<Object>} Résultat du diagnostic
   */
  static async diagnoseSync() {
    console.log("[AuditDB] 🔍 Début du diagnostic de synchronisation");

    try {
      // 1. Vérifier si l'application est en ligne
      const isOnline = navigator.onLine;
      console.log(
        `[AuditDB] ℹ️ État de connexion: ${
          isOnline ? "En ligne" : "Hors ligne"
        }`
      );

      // 2. Vérifier l'état de la base de données
      const dbInfo = { stores: [] };

      try {
        const request = indexedDB.open("AuditDB");

        await new Promise((resolve, reject) => {
          request.onerror = (event) => {
            console.error(
              "[AuditDB] ❌ Erreur d'ouverture de la base:",
              event.target.error
            );
            reject(event.target.error);
          };

          request.onsuccess = (event) => {
            const db = event.target.result;
            dbInfo.version = db.version;
            dbInfo.stores = Array.from(db.objectStoreNames);
            console.log("[AuditDB] ✓ Base ouverte, version:", db.version);
            console.log("[AuditDB] ✓ Stores disponibles:", dbInfo.stores);

            // Vérifier le store pending_evaluations
            if (db.objectStoreNames.contains("pending_evaluations")) {
              const transaction = db.transaction(
                ["pending_evaluations"],
                "readonly"
              );
              const store = transaction.objectStore("pending_evaluations");
              const countRequest = store.count();

              countRequest.onsuccess = () => {
                dbInfo.pendingEvaluationsCount = countRequest.result;
                console.log(
                  `[AuditDB] ✓ Nombre d'évaluations en attente: ${countRequest.result}`
                );
                resolve(dbInfo);
              };

              countRequest.onerror = (event) => {
                console.error(
                  "[AuditDB] ❌ Erreur de comptage:",
                  event.target.error
                );
                reject(event.target.error);
              };
            } else {
              dbInfo.pendingEvaluationsCount = 0;
              console.warn(
                "[AuditDB] ⚠️ Store 'pending_evaluations' non trouvé"
              );
              resolve(dbInfo);
            }
          };
        });
      } catch (dbError) {
        console.error(
          "[AuditDB] ❌ Erreur lors de l'accès à IndexedDB:",
          dbError
        );
        dbInfo.error = dbError.message;
      }

      // 3. Tester les permissions IndexedDB
      const storageEstimate = (await navigator.storage?.estimate()) || {
        quota: 0,
        usage: 0,
      };
      console.log(
        `[AuditDB] ℹ️ Stockage: ${Math.round(
          storageEstimate.usage / 1024 / 1024
        )}MB utilisés sur ${Math.round(
          storageEstimate.quota / 1024 / 1024
        )}MB disponibles`
      );

      // 4. Retourner le résultat du diagnostic
      return {
        timestamp: new Date().toISOString(),
        online: isOnline,
        database: dbInfo,
        storage: storageEstimate,
        browser: {
          userAgent: navigator.userAgent,
          platform: navigator.platform,
          language: navigator.language,
        },
      };
    } catch (error) {
      console.error("[AuditDB] ❌ Erreur lors du diagnostic:", error);
      return {
        timestamp: new Date().toISOString(),
        error: error.message,
        stack: error.stack,
      };
    }
  }
}

// Exposer la classe AuditDB globalement
console.log("[AuditDB] Exposition de AuditDB dans window");

// Exposer les méthodes avec des logs détaillés
window.AuditDB = {
  // Méthode d'initialisation
  initDB: function () {
    console.log("[AuditDB] Appel à initDB depuis l'interface globale");
    return AuditDB.init();
  },

  // Méthode pour sauvegarder une évaluation
  savePendingEvaluation: function (data) {
    console.log("[AuditDB:global] Sauvegarde d'une évaluation :", data);
    // Garantir que tous les champs requis sont présents
    if (!data || !data.audit_id || !data.point_vigilance_id) {
      console.error("[AuditDB:global] Données d'évaluation invalides:", data);
      return Promise.reject(new Error("Données d'évaluation invalides"));
    }
    return AuditDB.savePendingEvaluation(data);
  },

  // Méthode pour sauvegarder un document
  savePendingDocument: function (data) {
    console.log("[AuditDB:global] Sauvegarde d'un document");
    return AuditDB.savePendingDocument(data);
  },

  // Méthodes pour récupérer les données en attente
  getPendingEvaluations: function () {
    console.log("[AuditDB:global] Récupération des évaluations en attente");
    return AuditDB.getPendingEvaluations();
  },

  getPendingDocuments: function () {
    console.log("[AuditDB:global] Récupération des documents en attente");
    return AuditDB.getPendingDocuments();
  },

  // Méthodes pour marquer les données comme synchronisées
  markEvaluationAsSynced: function (id) {
    console.log(
      "[AuditDB:global] Marquage de l'évaluation comme synchronisée:",
      id
    );
    return AuditDB.markEvaluationAsSynced(id);
  },

  markDocumentAsSynced: function (id) {
    console.log("[AuditDB:global] Marquage du document comme synchronisé:", id);
    return AuditDB.markDocumentAsSynced(id);
  },

  // Méthode de synchronisation
  syncPendingData: function () {
    console.log("[AuditDB:global] Synchronisation des données en attente");
    return AuditDB.syncPendingData();
  },

  // Fonction de test pour sauvegarder directement une évaluation
  testSaveEvaluation: function () {
    console.log("[AuditDB:global] Exécution du test de sauvegarde");
    return AuditDB.testSaveEvaluation();
  },

  // Fonction pour effacer la base de données et la recréer
  resetDatabase: function () {
    console.log(
      "[AuditDB:global] Tentative de réinitialisation de la base de données"
    );
    return new Promise((resolve, reject) => {
      // Fermer toute connexion existante
      if (AuditDB._db) {
        AuditDB._db.close();
        AuditDB._db = null;
      }

      // Réinitialiser la promesse d'initialisation
      AuditDB._initPromise = null;

      // Supprimer la base de données
      const deleteRequest = indexedDB.deleteDatabase(AuditDB.DB_NAME);

      deleteRequest.onsuccess = () => {
        console.log("[AuditDB:global] Base de données supprimée avec succès");
        // Réinitialiser la base de données
        AuditDB.init()
          .then(() => {
            console.log(
              "[AuditDB:global] Base de données réinitialisée avec succès"
            );
            resolve(true);
          })
          .catch((error) => {
            console.error(
              "[AuditDB:global] Erreur lors de la réinitialisation:",
              error
            );
            reject(error);
          });
      };

      deleteRequest.onerror = (event) => {
        console.error(
          "[AuditDB:global] Erreur lors de la suppression de la base de données:",
          event
        );
        reject(
          new Error("Erreur lors de la suppression de la base de données")
        );
      };
    });
  },

  // Méthode de diagnostic pour débogage
  diagnoseSync: function () {
    console.log("[AuditDB:global] Exécution du diagnostic de synchronisation");
    return AuditDB.diagnoseSync();
  },
};

console.log("[AuditDB] Méthodes exposées:", Object.keys(window.AuditDB));
console.log("[AuditDB] Fin du chargement de auditdb.js");
