<?php
// Titre de la page pour le header
$pageTitle = "Mode Hors Ligne";

// Styles supplémentaires spécifiques à offline.php
$additionalStyles = [];

// Scripts supplémentaires spécifiques à offline.php
$additionalScripts = [
    'public/assets/js/db.js'
];

// Inclure le header
include_once __DIR__ . '/includes/header.php';
?>

<!-- Préchargement forcé de db.js -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Forcer le téléchargement de db.js avant d'utiliser l'application
    const dbScriptUrl = 'public/assets/js/db.js';
    
    // Fonction pour vérifier si ArticleDB est défini
    function checkArticleDB() {
        if (typeof ArticleDB !== 'undefined') {
            console.log('ArticleDB est correctement chargé!');
            // Déclencher un événement personnalisé pour indiquer que ArticleDB est prêt
            document.dispatchEvent(new Event('articledb_ready'));
        } else {
            console.warn('ArticleDB n\'est toujours pas défini, tentative de rechargement...');
            // Tenter de charger le script manuellement
            loadScript(dbScriptUrl);
        }
    }
    
    // Fonction pour charger dynamiquement un script
    function loadScript(url) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = url;
            script.onload = () => {
                console.log(`Script ${url} chargé avec succès`);
                setTimeout(checkArticleDB, 500); // Vérifier après un court délai
                resolve();
            };
            script.onerror = (error) => {
                console.error(`Erreur lors du chargement de ${url}:`, error);
                reject(error);
            };
            document.head.appendChild(script);
        });
    }
    
    // Vérifier d'abord si ArticleDB est déjà défini
    setTimeout(checkArticleDB, 500);
});
</script>

<div class="container mt-5">
    <div class="alert alert-warning" id="offlineAlert">
        <h4 class="alert-heading">Mode hors ligne</h4>
        <p>Vous êtes actuellement en mode hors ligne. Certaines fonctionnalités peuvent être limitées.</p>
    </div>
    
    <div class="alert alert-success" id="onlineAlert" style="display: none;">
        <h4 class="alert-heading">Connexion rétablie!</h4>
        <p>Votre connexion a été rétablie. Synchronisation automatique en cours...</p>
        <div class="progress" role="progressbar">
            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Articles stockés localement</h2>
        <div>
            <button id="syncButton" class="btn btn-primary" style="display: none;">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                Synchroniser
            </button>
            <span id="lastSync" class="ms-2 text-muted small"></span>
        </div>
    </div>

    <div id="articlesContainer" class="row">
        <!-- Les articles seront chargés ici -->
    </div>

    <div class="mt-4">
        <h3>Créer un article hors ligne</h3>
        <form id="articleForm" class="mt-3">
            <div class="mb-3">
                <label for="title" class="form-label">Titre</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Contenu</label>
                <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
        </div>
            <button type="submit" class="btn btn-primary">Créer l'article</button>
        </form>
    </div>
</div>

<!-- Script spécifique à offline.php -->
<script>
    let isOnline = navigator.onLine;
    const offlineAlert = document.getElementById('offlineAlert');
    const onlineAlert = document.getElementById('onlineAlert');
    
    // Surveillez les changements de statut de connexion
    window.addEventListener('online', handleConnectionChange);
    window.addEventListener('offline', handleConnectionChange);
    
    // Surveiller les changements de visibilité pour synchroniser quand l'onglet redevient actif
    document.addEventListener('visibilitychange', handleVisibilityChange);
    
    function handleVisibilityChange() {
        if (!document.hidden && isOnline) {
            // La page est redevenue visible et en ligne, vérifier s'il y a des articles à synchroniser
            checkPendingArticles();
        }
    }
    
    // Vérifier s'il y a des articles en attente de synchronisation
    async function checkPendingArticles() {
        try {
            if (typeof ArticleDB === 'undefined') {
                console.error('ArticleDB n\'est pas disponible');
                return;
            }
            
            const articles = await ArticleDB.getArticles();
            const pendingArticles = articles.filter(article => !article.server_id);
            
            if (pendingArticles.length > 0 && isOnline) {
                console.log(`${pendingArticles.length} articles en attente de synchronisation détectés`);
                const syncButton = document.getElementById('syncButton');
                
                // Mettre à jour l'interface pour indiquer qu'il y a des articles à synchroniser
                syncButton.style.display = 'inline-block';
                
                // Synchroniser automatiquement sans demander confirmation
                synchronizeArticles();
            }
        } catch (error) {
            console.error('Erreur lors de la vérification des articles en attente:', error);
        }
    }
    
    function handleConnectionChange() {
        const wasOffline = !isOnline;
        isOnline = navigator.onLine;
        
        if (isOnline) {
            offlineAlert.style.display = 'none';
            onlineAlert.style.display = 'block';
            
            // Si on était hors ligne avant, on lance une vérification
            if (wasOffline) {
                // Afficher un message de synchronisation
                onlineAlert.innerHTML = `
                    <h4 class="alert-heading">Connexion rétablie!</h4>
                    <p>Votre connexion a été rétablie. Vérification des articles à synchroniser...</p>
                    <div class="progress" role="progressbar">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                    </div>
                `;
                
                // Vérifier les articles en attente après un court délai
                setTimeout(async () => {
                    try {
                        // Mettre à jour l'affichage des articles pour afficher le bouton de sync si nécessaire
                        await loadArticles();
                        
                        // Vérifier les articles en attente de synchronisation
                        await checkPendingArticles();
                        
                        // Si aucune synchronisation n'a été déclenchée, afficher seulement un message
                        onlineAlert.innerHTML = `
                            <h4 class="alert-heading">Connexion rétablie!</h4>
                            <p>Votre connexion a été rétablie.</p>
                        `;
                        
                        // Faire disparaître la notification après quelques secondes
                        setTimeout(() => {
                            onlineAlert.style.display = 'none';
                        }, 5000);
                    } catch (error) {
                        console.error("Erreur lors de la vérification des articles:", error);
                        onlineAlert.innerHTML = `
                            <h4 class="alert-heading">Erreur de vérification</h4>
                            <p>Une erreur est survenue lors de la vérification de vos articles.</p>
                            <button id="manualSyncBtn" class="btn btn-success btn-sm">Réessayer manuellement</button>
                        `;
                        document.getElementById('manualSyncBtn').addEventListener('click', synchronizeArticles);
                    }
                }, 1000);
            }
        } else {
            offlineAlert.style.display = 'block';
            onlineAlert.style.display = 'none';
        }
    }
    
    // Vérifie l'état initial de la connexion
    handleConnectionChange();

    // Vérifier si le Service Worker est enregistré
    if ('serviceWorker' in navigator) {
        console.log('Tentative d\'enregistrement du Service Worker...');
        
        // Tenter d'enregistrer le Service Worker avec le scope approprié
        navigator.serviceWorker.register('/Audit/sw.js', { scope: '/Audit/' })
            .then(registration => {
                console.log('Service Worker enregistré avec succès!');
                console.log('Scope:', registration.scope);
                
                // Vérifier s'il y a une mise à jour disponible
                registration.addEventListener('updatefound', () => {
                    if (registration.installing) {
                        // Un nouveau Service Worker est en cours d'installation
                        const newWorker = registration.installing;
                        
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                console.log('Nouveau Service Worker installé et en attente d\'activation');
                                // Demander au Service Worker d'activer immédiatement
                                newWorker.postMessage({ type: 'SKIP_WAITING' });
                            }
                        });
                    }
                });
                
                // Attendre que ArticleDB soit prêt avant de charger les articles
                document.addEventListener('articledb_ready', function() {
                    console.log('ArticleDB est prêt, chargement des articles...');
                    loadArticles();
                }, { once: true });
                
                // Vérifier si ArticleDB est déjà prêt
                if (typeof ArticleDB !== 'undefined') {
                    console.log('ArticleDB déjà prêt, chargement des articles...');
                    loadArticles();
                }
            })
            .catch(error => {
                console.error('Erreur d\'enregistrement du Service Worker:', error);
                // Malgré l'erreur, tenter de charger les articles quand même
                document.addEventListener('articledb_ready', function() {
                    loadArticles();
                }, { once: true });
            });
    } else {
        console.warn('Les Service Workers ne sont pas supportés par ce navigateur.');
        // Charger les articles même sans Service Worker
        document.addEventListener('articledb_ready', function() {
            loadArticles();
        }, { once: true });
    }

    // Charger les articles stockés localement
    async function loadArticles() {
        try {
            console.log('Tentative de chargement des articles depuis IndexedDB...');
            
            // Vérifier que ArticleDB est bien défini
            if (typeof ArticleDB === 'undefined') {
                console.warn("ArticleDB n'est pas encore disponible, attente en cours...");
                
                // Attendre que ArticleDB soit prêt
                await new Promise((resolve, reject) => {
                    if (typeof ArticleDB !== 'undefined') {
                        resolve();
                    } else {
                        const timeoutId = setTimeout(() => {
                            document.removeEventListener('articledb_ready', readyHandler);
                            reject(new Error("ArticleDB n'est pas disponible après le timeout"));
                        }, 5000);
                        
                        const readyHandler = () => {
                            clearTimeout(timeoutId);
                            resolve();
                        };
                        
                        document.addEventListener('articledb_ready', readyHandler, { once: true });
                    }
                });
                
                // Vérifier encore une fois après l'attente
                if (typeof ArticleDB === 'undefined') {
                    throw new Error("ArticleDB n'est toujours pas défini après l'attente");
                }
            }
            
            const articles = await ArticleDB.getArticles();
            const container = document.getElementById('articlesContainer');
            container.innerHTML = '';

            if (articles.length === 0) {
                container.innerHTML = '<div class="col-12"><p>Aucun article stocké localement</p></div>';
                return;
            }

            // Vérifier s'il y a des articles en attente de synchronisation
            const pendingArticles = articles.filter(article => !article.server_id);
            const syncButton = document.getElementById('syncButton');
            syncButton.style.display = pendingArticles.length > 0 && isOnline ? 'inline-block' : 'none';

            console.log(`Affichage de ${articles.length} articles, dont ${pendingArticles.length} en attente de synchronisation`);

            articles.forEach(article => {
                const card = document.createElement('div');
                card.className = 'col-md-4 mb-4';
                card.innerHTML = `
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">${escapeHtml(article.title)}</h5>
                            <p class="card-text">${escapeHtml(article.content)}</p>
                            <p class="card-text"><small class="text-muted">Créé le: ${new Date(article.created_at).toLocaleString()}</small></p>
                            ${!article.server_id ? '<span class="badge bg-warning">En attente de synchronisation</span>' : ''}
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        } catch (error) {
            console.error('Erreur lors du chargement des articles:', error);
            document.getElementById('articlesContainer').innerHTML = 
                '<div class="col-12">' +
                '<p class="text-danger">Erreur lors du chargement des articles</p>' +
                '<button id="retryButton" class="btn btn-outline-primary btn-sm mt-2">Réessayer</button>' +
                '</div>';
                
            // Ajouter un bouton pour réessayer manuellement
            document.getElementById('retryButton')?.addEventListener('click', () => {
                loadArticles();
            });
        }
    }

    // Fonction pour échapper les caractères spéciaux HTML (sécurité)
    function escapeHtml(text) {
        if (!text) return '';
        
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    // Gérer la soumission du formulaire
    document.getElementById('articleForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const title = document.getElementById('title').value;
        const content = document.getElementById('content').value;

        try {
            // S'assurer que ArticleDB est défini
            if (typeof ArticleDB === 'undefined') {
                console.warn("ArticleDB n'est pas encore disponible, attente en cours...");
                
                // Retarder la soumission jusqu'à ce que ArticleDB soit prêt
                const waitForArticleDB = () => {
                    return new Promise((resolve) => {
                        if (typeof ArticleDB !== 'undefined') {
                            resolve();
                        } else {
                            document.addEventListener('articledb_ready', resolve, { once: true });
                            
                            // Timeout de sécurité
                            setTimeout(() => {
                                document.removeEventListener('articledb_ready', resolve);
                                alert("Impossible de sauvegarder l'article: ArticleDB n'a pas pu être chargé.");
                                reject(new Error("ArticleDB non disponible après timeout"));
                            }, 5000);
                        }
                    });
                };
                
                await waitForArticleDB();
            }
            
            await ArticleDB.saveArticle({
                title,
                content,
                created_at: new Date().toISOString()
            });

            // Réinitialiser le formulaire
            e.target.reset();
            
            // Recharger les articles
            loadArticles();

            // Afficher le bouton de synchronisation si en ligne
            if (isOnline) {
                document.getElementById('syncButton').style.display = 'inline-block';
            }
            
            // Si en ligne, synchroniser immédiatement
            if (isOnline) {
                synchronizeArticles();
            }
        } catch (error) {
            console.error('Erreur lors de la sauvegarde:', error);
            alert('Erreur lors de la sauvegarde de l\'article: ' + (error.message || 'Erreur inconnue'));
        }
    });

    // Gérer la synchronisation manuelle
    document.getElementById('syncButton').addEventListener('click', synchronizeArticles);
    
    // Fonction de synchronisation
    async function synchronizeArticles() {
        const button = document.getElementById('syncButton');
        const spinner = button.querySelector('.spinner-border');
        
        try {
            button.disabled = true;
            spinner.classList.remove('d-none');

            console.log('Début de la synchronisation manuelle des articles');
            
            // Vérifier la disponibilité d'ArticleDB
            if (typeof ArticleDB === 'undefined') {
                console.warn("ArticleDB n'est pas encore disponible, attente en cours...");
                
                // Attendre que ArticleDB soit prêt
                await new Promise((resolve, reject) => {
                    if (typeof ArticleDB !== 'undefined') {
                        resolve();
                    } else {
                        const timeoutId = setTimeout(() => {
                            document.removeEventListener('articledb_ready', readyHandler);
                            reject(new Error("ArticleDB n'est pas disponible après le timeout"));
                        }, 5000);
                        
                        const readyHandler = () => {
                            clearTimeout(timeoutId);
                            resolve();
                        };
                        
                        document.addEventListener('articledb_ready', readyHandler, { once: true });
                    }
                });
            }
            
            // Utiliser directement la synchronisation manuelle pour plus de fiabilité
            const syncResult = await manualSyncArticles();
            
            if (syncResult) {
                console.log('Synchronisation réussie, articles envoyés au serveur');
                // Mettre à jour le timestamp de dernière synchronisation
                document.getElementById('lastSync').textContent = 
                    `Dernière synchro: ${new Date().toLocaleTimeString()}`;
                
                // Recharger les articles après synchronisation
                await loadArticles();
                
                // Si on était hors ligne et maintenant en ligne, rediriger automatiquement
                if (isOnline) {
                    // Redirection automatique vers la liste des articles
                    window.location.href = './index.php?action=articles';
                }
            } else {
                console.log('Aucun article n\'a été synchronisé');
                alert('Aucun article n\'a été synchronisé.');
            }
        } catch (error) {
            console.error('Erreur lors de la synchronisation:', error);
            alert('Erreur lors de la synchronisation: ' + (error.message || 'Erreur inconnue'));
        } finally {
            setTimeout(() => {
                button.disabled = false;
                spinner.classList.add('d-none');
            }, 1000);
        }
    }

    // Synchronisation manuelle (fallback)
    async function manualSyncArticles() {
        try {
            console.log('Démarrage de la synchronisation manuelle des articles');
            
            // Vérifier la disponibilité d'ArticleDB
            if (typeof ArticleDB === 'undefined') {
                console.warn("ArticleDB n'est pas encore disponible, attente en cours...");
                
                // Attendre que ArticleDB soit prêt
                await new Promise((resolve, reject) => {
                    if (typeof ArticleDB !== 'undefined') {
                        resolve();
                    } else {
                        const timeoutId = setTimeout(() => {
                            document.removeEventListener('articledb_ready', readyHandler);
                            reject(new Error("ArticleDB n'est pas disponible après le timeout"));
                        }, 5000);
                        
                        const readyHandler = () => {
                            clearTimeout(timeoutId);
                            resolve();
                        };
                        
                        document.addEventListener('articledb_ready', readyHandler, { once: true });
                    }
                });
            }
            
            // Obtenir tous les articles locaux
            const articles = await ArticleDB.getArticles();
            
            // Filtrer les articles qui n'ont pas encore été synchronisés avec le serveur
            const pendingArticles = articles.filter(article => !article.server_id);
            
            console.log(`${pendingArticles.length} articles en attente de synchronisation`);
            
            if (pendingArticles.length === 0) {
                return false; // Rien à synchroniser
            }
            
            // Vérification préalable de la connexion réseau
            if (!navigator.onLine) {
                console.error('Impossible de synchroniser: appareil hors ligne');
                alert('Impossible de synchroniser les articles car vous êtes hors ligne.');
                return false;
            }
            
            let syncCount = 0;
            let errorCount = 0;
            
            // Synchroniser séquentiellement chaque article
            for (const article of pendingArticles) {
                console.log(`Tentative de synchronisation de l'article: ${article.title}`);
                try {
                    // Créer directement l'article avec l'endpoint method=create au lieu de tenter la synchronisation standard
                    console.log("Création directe de l'article sur le serveur:", article.title);
                    const createResponse = await fetch('./index.php?action=articles&method=create', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            title: article.title,
                            content: article.content,
                            created_at: article.created_at
                        })
                    });
                    
                    if (createResponse.ok) {
                        try {
                            const createResult = await createResponse.json();
                            console.log("Résultat de la création:", createResult);
                            
                            if (createResult && createResult.id) {
                                // Supprimer l'article de l'IndexedDB puisqu'il est maintenant sur le serveur
                                await ArticleDB.deleteArticle(article.id);
                                syncCount++;
                                console.log(`Article synchronisé avec succès: ${article.title} (ID serveur: ${createResult.id})`);
                            } else if (Array.isArray(createResult)) {
                                // Si on reçoit quand même un tableau, chercher un article correspondant par titre
                                console.log("Le serveur a renvoyé un tableau, recherche par titre...");
                                console.log("Recherche de correspondance pour:", article.title);
                                
                                // Essayer d'abord une correspondance exacte
                                let titleMatches = createResult.filter(serverArticle => 
                                    serverArticle.title.trim() === article.title.trim()
                                );
                                
                                // Si pas de correspondance exacte, essayer une correspondance partielle
                                if (titleMatches.length === 0) {
                                    console.log("Pas de correspondance exacte, essai avec correspondance partielle");
                                    
                                    // Vérifier si le titre de l'article local commence par le titre d'un article du serveur
                                    // ou si un titre d'article du serveur commence par le titre de l'article local
                                    titleMatches = createResult.filter(serverArticle => {
                                        const localTitle = article.title.trim().toLowerCase();
                                        const serverTitle = serverArticle.title.trim().toLowerCase();
                                        
                                        return localTitle.startsWith(serverTitle) || 
                                               serverTitle.startsWith(localTitle) ||
                                               // Ou si les 10 premiers caractères correspondent
                                               (localTitle.length >= 10 && 
                                                serverTitle.length >= 10 && 
                                                localTitle.substring(0, 10) === serverTitle.substring(0, 10));
                                    });
                                }
                                
                                if (titleMatches.length > 0) {
                                    // L'article a probablement été créé, mais on reçoit quand même la liste complète
                                    await ArticleDB.deleteArticle(article.id);
                                    syncCount++;
                                    console.log(`Article probablement créé sur le serveur: ${article.title}`);
                                    console.log(`Correspondance trouvée avec: ${titleMatches[0].title}`);
                                } else {
                                    errorCount++;
                                    console.error(`Aucun article correspondant trouvé pour: ${article.title}`);
                                }
                            } else {
                                errorCount++;
                                console.error(`Format de réponse inattendu:`, createResult);
                            }
                        } catch (jsonError) {
                            console.error("Erreur lors du traitement de la réponse:", jsonError);
                            errorCount++;
                        }
                    } else {
                        // Si la création échoue, essayer la synchronisation alternative
                        console.log(`Création directe échouée (${createResponse.status}), tentative alternative...`);
                        
                        // Récupérer tous les articles du serveur
                        const listResponse = await fetch('./index.php?action=articles', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (listResponse.ok) {
                            const serverArticles = await listResponse.json();
                            
                            if (Array.isArray(serverArticles)) {
                                // Chercher par correspondance exacte du titre
                                let exactMatches = serverArticles.filter(serverArticle => 
                                    serverArticle.title.trim() === article.title.trim()
                                );
                                
                                // Si pas de correspondance exacte, essayer une correspondance partielle
                                if (exactMatches.length === 0) {
                                    console.log("Pas de correspondance exacte dans la liste, essai avec correspondance partielle");
                                    
                                    // Utiliser une logique similaire pour la correspondance partielle
                                    exactMatches = serverArticles.filter(serverArticle => {
                                        const localTitle = article.title.trim().toLowerCase();
                                        const serverTitle = serverArticle.title.trim().toLowerCase();
                                        
                                        return localTitle.startsWith(serverTitle) || 
                                               serverTitle.startsWith(localTitle) ||
                                               // Ou si les 10 premiers caractères correspondent
                                               (localTitle.length >= 10 && 
                                                serverTitle.length >= 10 && 
                                                localTitle.substring(0, 10) === serverTitle.substring(0, 10));
                                    });
                                }
                                
                                if (exactMatches.length > 0) {
                                    // Article trouvé par titre, le considérer comme déjà synchronisé
                                    await ArticleDB.deleteArticle(article.id);
                                    syncCount++;
                                    console.log(`Article trouvé sur le serveur par titre: ${article.title}`);
                                    console.log(`Correspondance trouvée avec: ${exactMatches[0].title}`);
                                } else {
                                    errorCount++;
                                    console.error(`Aucun article correspondant au titre: ${article.title}`);
                                }
                            } else {
                                errorCount++;
                                console.error("Format de réponse inattendu lors de la récupération des articles");
                            }
                        } else {
                            errorCount++;
                            console.error(`Échec de la récupération des articles: ${listResponse.status}`);
                        }
                    }
                } catch (error) {
                    errorCount++;
                    console.error(`Erreur lors de la synchronisation de l'article ${article.title}:`, error);
                }
                
                // Attendre un peu entre chaque requête pour éviter de surcharger le serveur
                await new Promise(resolve => setTimeout(resolve, 500));
            }
            
            console.log(`Synchronisation terminée: ${syncCount} réussis, ${errorCount} échecs`);
            
            return syncCount > 0;
        } catch (error) {
            console.error('Erreur globale lors de la synchronisation manuelle:', error);
            return false;
        }
    }
</script>

<?php
// Inclure le footer
include_once __DIR__ . '/includes/footer.php';
?>

  