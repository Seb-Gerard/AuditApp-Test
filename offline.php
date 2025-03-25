<?php
// Titre de la page pour le header
$pageTitle = "Mode Hors Ligne";

// Styles supplémentaires spécifiques à offline.php
$additionalStyles = [];

// Scripts supplémentaires spécifiques à offline.php
$additionalScripts = [
    '/Audit/js/db.js'
];

// Inclure le header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="alert alert-warning" id="offlineAlert">
        <h4 class="alert-heading">Mode hors ligne</h4>
        <p>Vous êtes actuellement en mode hors ligne. Certaines fonctionnalités peuvent être limitées.</p>
    </div>
    
    <div class="alert alert-success" id="onlineAlert" style="display: none;">
        <h4 class="alert-heading">Connexion rétablie!</h4>
        <p>Votre connexion a été rétablie. Synchronisation en cours...</p>
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
    
    function handleConnectionChange() {
        const wasOffline = !isOnline;
        isOnline = navigator.onLine;
        
        if (isOnline) {
            offlineAlert.style.display = 'none';
            onlineAlert.style.display = 'block';
            
            // Si on était hors ligne avant, on lance une synchronisation
            if (wasOffline) {
                synchronizeArticles();
                
                // Rediriger vers la page d'accueil après 3 secondes
                setTimeout(() => {
                    window.location.href = '/Audit/index.php';
                }, 3000);
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
        let serviceWorkerReady = false;
        
        // Fonction pour initialiser l'application une fois que le Service Worker est prêt
        function initializeApp() {
            if (serviceWorkerReady) {
                console.log('Application initialisée, chargement des articles...');
                loadArticles();
                return;
            }
            
            console.log('Attente du Service Worker...');
            
            // Tentative d'enregistrement ou récupération du Service Worker existant
            navigator.serviceWorker.ready
                .then(registration => {
                    console.log('Service Worker prêt, SW version:', registration.active.scriptURL);
                    serviceWorkerReady = true;
                    
                    // Écouter les messages du Service Worker
                    navigator.serviceWorker.addEventListener('message', (event) => {
                        if (event.data && event.data.type === 'SYNC_COMPLETED') {
                            console.log('Synchronisation terminée:', event.data);
                            document.getElementById('lastSync').textContent = 
                                `Dernière synchro: ${new Date().toLocaleTimeString()}`;
                            loadArticles();
                        }
                    });
                    
                    // Chargement initial des articles
                    loadArticles();
                })
                .catch(error => {
                    console.error('Erreur lors de l\'initialisation du Service Worker:', error);
                    // Malgré l'erreur, tenter de charger les articles quand même
                    setTimeout(() => {
                        loadArticles();
                    }, 1000);
                });
        }
        
        // Vérifier si un service worker est déjà contrôleur
        if (navigator.serviceWorker.controller) {
            console.log('Service Worker déjà contrôleur');
            serviceWorkerReady = true;
            initializeApp();
        } else {
            console.log('Tentative d\'enregistrement du Service Worker...');
            // Tenter d'enregistrer ou récupérer le Service Worker
            navigator.serviceWorker.register('/Audit/sw.js')
                .then(registration => {
                    console.log('Service Worker enregistré');
                    
                    if (registration.active) {
                        console.log('Service Worker actif');
                        serviceWorkerReady = true;
                        initializeApp();
                    } else if (registration.installing) {
                        console.log('Service Worker en cours d\'installation');
                        const sw = registration.installing;
                        sw.addEventListener('statechange', (event) => {
                            if (sw.state === 'activated') {
                                console.log('Service Worker activé');
                                serviceWorkerReady = true;
                                initializeApp();
                            }
                        });
                    } else if (registration.waiting) {
                        console.log('Service Worker en attente d\'activation');
                        registration.waiting.postMessage({type: 'SKIP_WAITING'});
                        setTimeout(initializeApp, 1000);
                    }
                })
                .catch(error => {
                    console.error('Erreur d\'enregistrement du Service Worker:', error);
                    // Malgré l'erreur, tenter de charger les articles quand même
                    setTimeout(() => {
                        loadArticles();
                    }, 1000);
                });
                
            // Définir un timeout au cas où le Service Worker ne s'active pas
            setTimeout(() => {
                if (!serviceWorkerReady) {
                    console.warn('Timeout d\'activation du Service Worker, tentative de chargement des articles...');
                    loadArticles();
                }
            }, 5000);
        }
    } else {
        console.warn('Les Service Workers ne sont pas supportés par ce navigateur.');
        // Charger les articles même sans Service Worker
        loadArticles();
    }

    // Charger les articles stockés localement
    async function loadArticles() {
        try {
            console.log('Tentative de chargement des articles depuis IndexedDB...');
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
            
            // Réessayer une fois après un court délai
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            try {
                console.log('Nouvelle tentative de chargement des articles...');
                const retryArticles = await ArticleDB.getArticles();
                const container = document.getElementById('articlesContainer');
                container.innerHTML = '';
                
                if (retryArticles.length === 0) {
                    container.innerHTML = '<div class="col-12"><p>Aucun article stocké localement</p></div>';
                    return;
                }
                
                // Afficher les articles après la nouvelle tentative
                retryArticles.forEach(article => {
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
            } catch (retryError) {
                console.error('Échec de la seconde tentative de chargement des articles:', retryError);
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
            alert('Erreur lors de la sauvegarde de l\'article');
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

            if (navigator.serviceWorker.controller) {
                const registration = await navigator.serviceWorker.ready;
                try {
                    // Essayez d'utiliser Background Sync si disponible
                    if ('SyncManager' in window) {
                        await registration.sync.register('sync-articles');
                        console.log('Synchronisation demandée via Background Sync');
                    } else {
                        console.log('Background Sync non disponible, utilisation d\'un message direct');
                        // Sinon, envoyez un message direct au Service Worker
                        navigator.serviceWorker.controller.postMessage({
                            type: 'SYNC_ARTICLES'
                        });
                    }
                } catch (syncError) {
                    console.error('Erreur lors de la demande de synchronisation:', syncError);
                    // Fallback: utiliser la synchronisation dans la page
                    await manualSyncArticles();
                }
            } else {
                console.log('Aucun Service Worker n\'est contrôleur, synchronisation manuelle');
                // Pas de Service Worker contrôleur, synchroniser manuellement
                await manualSyncArticles();
            }

            // Mettre à jour le timestamp de dernière synchronisation
            document.getElementById('lastSync').textContent = 
                `Dernière synchro: ${new Date().toLocaleTimeString()}`;
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
            const articles = await ArticleDB.getArticles();
            const pendingArticles = articles.filter(article => !article.server_id);
            
            let syncCount = 0;
            for (const article of pendingArticles) {
                const success = await ArticleDB.syncArticleWithServer(article);
                if (success) {
                    syncCount++;
                    // L'article a déjà été supprimé par syncArticleWithServer
                }
            }
            
            console.log(`Synchronisation manuelle terminée: ${syncCount}/${pendingArticles.length} articles synchronisés`);
            
            // Recharger les articles après synchronisation
            loadArticles();
            
            return syncCount > 0;
        } catch (error) {
            console.error('Erreur lors de la synchronisation manuelle:', error);
            return false;
        }
    }
</script>

<?php
// Inclure le footer
include_once __DIR__ . '/includes/footer.php';
?>

  