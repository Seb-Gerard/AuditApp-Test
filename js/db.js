const DB_NAME = 'AuditDB';
const DB_VERSION = 1;
const STORE_NAME = 'articles';

class ArticleDB {
    static async initDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result);

            request.onupgradeneeded = (event) => {
                // @ts-ignore
                const db = event.target?.result;
                if (db && !db.objectStoreNames.contains(STORE_NAME)) {
                    const store = db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                    store.createIndex('created_at', 'created_at', { unique: false });
                    store.createIndex('server_id', 'server_id', { unique: false });
                }
            };
        });
    }

    static async saveArticle(article) {
        const db = await this.initDB();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            
            let request;
            
            if (article.id) {
                // Si l'article a déjà un ID, on le met à jour
                request = store.put(article);
            } else {
                // Sinon, on crée un nouvel article
                const newArticle = {
                    ...article,
                    server_id: null, // S'assurer que server_id est null pour les nouveaux articles
                    created_at: article.created_at || new Date().toISOString()
                };
                request = store.add(newArticle);
            }

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    static async getArticles() {
        const db = await this.initDB();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            const request = store.getAll();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    static async deleteArticle(id) {
        const db = await this.initDB();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            const request = store.delete(id);

            // @ts-ignore
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
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