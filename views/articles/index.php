<!-- Ce fichier est inclus par le contrôleur, qui a déjà chargé le header -->
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Articles</h1>
        <div>
            <button id="syncButton" class="btn btn-primary me-2" style="display: none;">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                Synchroniser les articles hors ligne
            </button>
            <a href="/Audit/index.php?action=create" class="btn btn-success">Nouvel article</a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div id="syncSuccess" class="alert alert-success" style="display: none;">
        Synchronisation réussie!
    </div>

    <div id="articlesContainer" class="row">
        <?php foreach ($articles as $article): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($article['content'])); ?></p>
                        <p class="card-text"><small class="text-muted">Créé le: <?php echo $article['created_at']; ?></small></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="/Audit/js/db.js"></script>
<script>
    // Vérifier s'il y a des articles en attente de synchronisation
    async function checkPendingArticles() {
        try {
            // D'abord, récupérer les articles localement
            const articles = await ArticleDB.getArticles();
            console.log('Articles stockés localement:', articles);
            
            const pendingArticles = articles.filter(article => !article.server_id);
            console.log('Articles en attente:', pendingArticles.length);
            
            const syncButton = document.getElementById('syncButton');
            syncButton.style.display = pendingArticles.length > 0 ? 'inline-block' : 'none';
            
            // Si nous sommes en ligne et qu'il y a des articles en attente, tenter de les synchroniser
            if (navigator.onLine && pendingArticles.length > 0) {
                try {
                    // Synchroniser avec le serveur, mais ne pas bloquer l'affichage
                    setTimeout(async () => {
                        try {
                            for (const article of pendingArticles) {
                                await ArticleDB.syncArticleWithServer(article);
                            }
                            // Vérifier à nouveau après la synchronisation
                            const remainingPending = (await ArticleDB.getArticles())
                                .filter(article => !article.server_id);
                            syncButton.style.display = remainingPending.length > 0 ? 'inline-block' : 'none';
                        } catch (syncError) {
                            console.error('Erreur lors de la synchronisation automatique:', syncError);
                        }
                    }, 1000);
                } catch (error) {
                    console.error('Erreur lors de la synchronisation:', error);
                }
            }
            
            return pendingArticles.length > 0;
        } catch (error) {
            console.error('Erreur lors de la vérification des articles:', error);
            return false;
        }
    }

    // Gérer la synchronisation manuelle
    document.getElementById('syncButton').addEventListener('click', async () => {
        const button = document.getElementById('syncButton');
        const spinner = button.querySelector('.spinner-border');
        
        try {
            button.disabled = true;
            spinner.classList.remove('d-none');

            // Demander la synchronisation au Service Worker
            if ('serviceWorker' in navigator) {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register('sync-articles');
                
                // Afficher un message de confirmation
                const syncSuccess = document.getElementById('syncSuccess');
                syncSuccess.style.display = 'block';
                
                // Cacher le message après 3 secondes
                setTimeout(() => {
                    syncSuccess.style.display = 'none';
                }, 3000);
                
                // Rafraîchir la page après un court délai
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } catch (error) {
            console.error('Erreur lors de la synchronisation:', error);
            alert('Erreur lors de la synchronisation');
        } finally {
            setTimeout(() => {
                button.disabled = false;
                spinner.classList.add('d-none');
            }, 1000);
        }
    });

    // Écouter les messages du Service Worker pour la synchronisation
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'SYNC_COMPLETED' && event.data.success) {
                console.log('Synchronisation terminée avec succès');
                window.location.reload(); // Recharger la page pour afficher les nouveaux articles
            }
        });
    }

    // Vérifier les articles en attente au chargement de la page
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.ready.then(() => {
            checkPendingArticles();
        });
    }
</script> 