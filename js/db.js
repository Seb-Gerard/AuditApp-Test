const DB_NAME = 'AuditDB';
const DB_VERSION = 1;
const STORE_NAME = 'articles';

// Variable pour stocker l'instance de DB
let dbInstance = null;

class ArticleDB {
    static async initDB() {
        // Si une instance existe déjà, la retourner
        if (dbInstance) {
            return dbInstance;
        }

        return new Promise((resolve, reject) => {
            try {
                const request = indexedDB.open(DB_NAME, DB_VERSION);

                request.onerror = (event) => {
                    console.error('Erreur lors de l\'ouverture de la base de données:', event.target.error);
                    reject(event.target.error);
                };

                request.onsuccess = (event) => {
                    dbInstance = event.target.result;
                    
                    // Gérer les cas où la connexion est fermée
                    dbInstance.onclose = () => {
                        console.log('Connexion à la base de données fermée');
                        dbInstance = null;
                    };
                    
                    // Gérer les erreurs sur la base de données
                    dbInstance.onerror = (event) => {
                        console.error('Erreur de base de données:', event.target.error);
                    };
                    
                    console.log('Base de données ouverte avec succès');
                    resolve(dbInstance);
                };

                request.onupgradeneeded = (event) => {
                    // @ts-ignore
                    const db = event.target?.result;
                    if (db && !db.objectStoreNames.contains(STORE_NAME)) {
                        console.log('Création du store pour les articles');
                        const store = db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                        store.createIndex('created_at', 'created_at', { unique: false });
                        store.createIndex('server_id', 'server_id', { unique: false });
                    }
                };
                
                request.onblocked = (event) => {
                    console.warn('La base de données est bloquée. Fermez les autres onglets utilisant l\'application.');
                    alert('La base de données est bloquée. Veuillez fermer les autres onglets ou fenêtres utilisant l\'application, puis réessayez.');
                };
            } catch (error) {
                console.error('Erreur lors de l\'initialisation de la base de données:', error);
                reject(error);
            }
        });
    }

    static async saveArticle(article) {
        try {
            const db = await this.initDB();
            return new Promise((resolve, reject) => {
                try {
                    const transaction = db.transaction([STORE_NAME], 'readwrite');
                    const store = transaction.objectStore(STORE_NAME);
                    
                    let request;
                    
                    if (article.id) {
                        // Si l'article a déjà un ID, on le met à jour
                        request = store.put(article);
                        console.log('Mise à jour d\'un article existant, ID:', article.id);
                    } else {
                        // Sinon, on crée un nouvel article
                        const newArticle = {
                            ...article,
                            server_id: null, // S'assurer que server_id est null pour les nouveaux articles
                            created_at: article.created_at || new Date().toISOString()
                        };
                        request = store.add(newArticle);
                        console.log('Ajout d\'un nouvel article');
                    }

                    request.onsuccess = () => {
                        console.log('Article sauvegardé avec succès, ID local:', request.result);
                        resolve(request.result);
                    };
                    
                    request.onerror = () => {
                        console.error('Erreur lors de la sauvegarde de l\'article:', request.error);
                        reject(request.error);
                    };
                    
                    transaction.oncomplete = () => {
                        console.log('Transaction d\'écriture terminée');
                    };
                    
                    transaction.onerror = (event) => {
                        console.error('Erreur de transaction lors de la sauvegarde:', event.target.error);
                        reject(event.target.error);
                    };
                } catch (error) {
                    console.error('Erreur lors de la création de la transaction:', error);
                    reject(error);
                }
            });
        } catch (error) {
            console.error('Erreur lors de l\'initialisation de la base de données pour saveArticle:', error);
            throw error;
        }
    }

    static async getArticles() {
        try {
            // Initialise et obtient la référence à la base de données
            const db = await this.initDB();
            console.log('Base de données obtenue pour getArticles:', db);
            
            return new Promise((resolve, reject) => {
                try {
                    const transaction = db.transaction([STORE_NAME], 'readonly');
                    const store = transaction.objectStore(STORE_NAME);
                    
                    const request = store.getAll();
                    
                    request.onsuccess = function() {
                        console.log(`${request.result.length} articles récupérés avec succès`);
                        resolve(request.result);
                    };
                    
                    request.onerror = function() {
                        console.error('Erreur lors de la récupération des articles:', request.error);
                        reject(request.error);
                    };
                } catch (error) {
                    console.error('Erreur de transaction:', error);
                    reject(error);
                }
            });
        } catch (error) {
            console.error('Erreur lors de l\'initialisation de la base de données pour getArticles:', error);
            throw error;
        }
    }

    static async deleteArticle(id) {
        try {
            const db = await this.initDB();
            return new Promise((resolve, reject) => {
                try {
                    const transaction = db.transaction([STORE_NAME], 'readwrite');
                    const store = transaction.objectStore(STORE_NAME);
                    const request = store.delete(id);

                    // @ts-ignore
                    request.onsuccess = () => {
                        console.log('Article supprimé avec succès, ID local:', id);
                        resolve();
                    };
                    
                    request.onerror = () => {
                        console.error('Erreur lors de la suppression de l\'article:', request.error);
                        reject(request.error);
                    };
                    
                    transaction.oncomplete = () => {
                        console.log('Transaction de suppression terminée');
                    };
                    
                    transaction.onerror = (event) => {
                        console.error('Erreur de transaction lors de la suppression:', event.target.error);
                        reject(event.target.error);
                    };
                } catch (error) {
                    console.error('Erreur lors de la création de la transaction:', error);
                    reject(error);
                }
            });
        } catch (error) {
            console.error('Erreur lors de l\'initialisation de la base de données pour deleteArticle:', error);
            throw error;
        }
    }

    static async getPendingArticles() {
        const articles = await this.getArticles();
        return articles.filter(article => !article.server_id);
    }

    static async clearAllArticles() {
        const db = await this.initDB();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            const request = store.clear();

            // @ts-ignore
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    static async syncWithServer() {
        try {
            console.log('Début de la synchronisation avec le serveur...');
            
            // 1. Récupérer tous les articles du serveur
            const response = await fetch('/Audit/index.php?action=index', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Erreur HTTP:', response.status, errorText.substring(0, 100));
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            // Clone la réponse pour pouvoir la lire plusieurs fois si nécessaire
            const responseClone = response.clone();
            
            let serverArticles;
            try {
                serverArticles = await response.json();
            } catch (jsonError) {
                console.error('Erreur lors du parsing JSON:', jsonError);
                const responseText = await responseClone.text();
                console.error('Contenu de la réponse:', responseText.substring(0, 500));
                throw new Error('La réponse du serveur n\'est pas un JSON valide');
            }
            
            console.log('Articles du serveur:', serverArticles);
            
            // 2. Récupérer tous les articles locaux
            const localArticles = await this.getArticles();
            console.log('Articles locaux:', localArticles);
            
            // 3. Pour chaque article local sans server_id, essayer de le synchroniser
            const pendingArticles = localArticles.filter(article => !article.server_id);
            
            if (pendingArticles.length > 0) {
                console.log('Articles en attente de synchronisation:', pendingArticles.length);
                
                // Synchronisation directe des articles
                for (const article of pendingArticles) {
                    await this.syncArticleWithServer(article);
                }
            }
            
            // 4. Mettre à jour les articles locaux avec les données du serveur
            for (const serverArticle of serverArticles) {
                const existingLocal = localArticles.find(local => 
                    local.server_id && local.server_id.toString() === serverArticle.id.toString());
                
                if (!existingLocal) {
                    // Article du serveur qui n'existe pas localement, on l'ajoute
                    await this.saveArticle({
                        title: serverArticle.title,
                        content: serverArticle.content,
                        created_at: serverArticle.created_at,
                        server_id: serverArticle.id
                    });
                    console.log('Article du serveur ajouté localement:', serverArticle.id);
                }
            }
            
            console.log('Synchronisation terminée avec succès');
            return true;
        } catch (error) {
            console.error('Erreur lors de la synchronisation avec le serveur:', 
                        error instanceof Error ? error.message : error);
            return false;
        }
    }
    
    static async syncArticleWithServer(article) {
        try {
            console.log('Tentative de synchronisation pour article:', article);
            
            // Ajouter une vérification de connectivité avant d'envoyer la requête
            if (!navigator.onLine) {
                console.log('Appareil hors ligne, impossible de synchroniser');
                return false;
            }
            
            // Ajouter un délai entre les tentatives pour éviter de surcharger le serveur
            await new Promise(resolve => setTimeout(resolve, 500)); 
            
            const response = await fetch('/Audit/index.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title: article.title,
                    content: article.content
                })
            });

            console.log('Statut de la réponse:', response.status, response.statusText);
            
            // Si le serveur est indisponible (503), on attend un peu et on réessaie
            if (response.status === 503) {
                console.log('Serveur indisponible (503), nouvelle tentative dans 2 secondes...');
                await new Promise(resolve => setTimeout(resolve, 2000));
                return this.syncArticleWithServer(article); // Tentative récursive
            }

            if (response.ok) {
                try {
                    const responseClone = response.clone();
                    let result;
                    
                    try {
                        result = await response.json();
                    } catch (jsonError) {
                        console.error('Erreur lors du parsing JSON, tentative de lecture du texte');
                        const responseText = await responseClone.text();
                        
                        // Tentative de trouver un JSON valide dans la réponse textuelle
                        try {
                            const jsonMatch = responseText.match(/\{.*\}/);
                            if (jsonMatch) {
                                result = JSON.parse(jsonMatch[0]);
                                console.log('JSON extrait de la réponse textuelle:', result);
                            } else {
                                console.error('Aucun JSON trouvé dans la réponse:', responseText.substring(0, 200));
                                return false;
                            }
                        } catch (e) {
                            console.error('Impossible d\'extraire un JSON:', e);
                            console.error('Réponse brute:', responseText.substring(0, 200));
                            return false;
                        }
                    }
                    
                    console.log('Réponse du serveur analysée:', result);
                    
                    if (result && result.id) {
                        // Supprimer l'article de l'IndexedDB après synchronisation réussie
                        if (article.id) {
                            await this.deleteArticle(article.id);
                            console.log('Article supprimé de la base locale après synchronisation, ID local:', article.id);
                        }
                        return true;
                    } else {
                        console.error('Réponse du serveur invalide:', result);
                        return false;
                    }
                } catch (parseError) {
                    console.error('Erreur lors du traitement de la réponse:', parseError);
                    return false;
                }
            } else {
                const errorText = await response.text();
                console.error(`Erreur HTTP (${response.status}):`, errorText.substring(0, 100));
                
                // Si le status est 401 ou 403, l'utilisateur doit peut-être se reconnecter
                if (response.status === 401 || response.status === 403) {
                    alert('Session expirée ou non autorisée. Veuillez vous reconnecter.');
                }
                
                return false;
            }
        } catch (error) {
            console.error('Erreur lors de la synchronisation d\'un article:', 
                        error instanceof Error ? error.message : error);
            return false;
        }
    }
}

// Fonction pour s'assurer que la DB est initialisée
async function ensureDBInitialized() {
    if (!dbInstance) {
        console.log('Base de données non initialisée, tentative d\'initialisation...');
        try {
            await ArticleDB.initDB();
            return true;
        } catch (error) {
            console.error('Erreur lors de l\'initialisation de la DB:', error);
            return false;
        }
    }
    return true;
}

/**
 * Récupération des articles
 * @returns {Promise<Array>} Liste des articles
 */
ArticleDB.getArticles = async function() {
    try {
        // Initialise et obtient la référence à la base de données
        const db = await this.initDB();
        console.log('Base de données obtenue pour getArticles:', db);
        
        return new Promise((resolve, reject) => {
            try {
                const transaction = db.transaction([STORE_NAME], 'readonly');
                const store = transaction.objectStore(STORE_NAME);
                
                const request = store.getAll();
                
                request.onsuccess = function() {
                    console.log(`${request.result.length} articles récupérés avec succès`);
                    resolve(request.result);
                };
                
                request.onerror = function() {
                    console.error('Erreur lors de la récupération des articles:', request.error);
                    reject(request.error);
                };
            } catch (error) {
                console.error('Erreur de transaction:', error);
                reject(error);
            }
        });
    } catch (error) {
        console.error('Erreur lors de l\'initialisation de la base de données pour getArticles:', error);
        throw error;
    }
}; 