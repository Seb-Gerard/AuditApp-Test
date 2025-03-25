<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/style.css">
    <title>Mode Hors Ligne</title>
</head>
<body>    
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="public/assets/img/Logo_CNPP_250.jpg" alt="Logo" width="150">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Accueil</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h1 class="card-title text-center mb-4">Mode Hors Ligne</h1>
                        <div class="alert alert-warning text-center mb-4">
                            <i class="fas fa-wifi-slash me-2"></i>
                            Vous êtes actuellement hors ligne. Les articles affichés sont ceux stockés localement.
                        </div>

                        <h2 class="mb-4">Articles stockés localement</h2>
                        <div class="articles-list">
                            <p class="text-center">Chargement des articles...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            if ('serviceWorker' in navigator) {
                try {
                    const registration = await navigator.serviceWorker.register('/Audit/sw.js');
                    console.log('ServiceWorker registered');

                    // Attendre que le Service Worker soit prêt
                    if (registration.active) {
                        loadArticles();
                    } else {
                        registration.addEventListener('activate', () => {
                            loadArticles();
                        });
                    }
                } catch (error) {
                    console.error('ServiceWorker registration failed:', error);
                    showError('Erreur lors de l\'initialisation du Service Worker');
                }
            } else {
                showError('Les Service Workers ne sont pas supportés par votre navigateur');
            }
        });

        function loadArticles() {
            if (navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({
                    type: 'GET_ARTICLES'
                });
            }
        }

        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data.type === 'ARTICLES_RETRIEVED') {
                const articlesList = document.querySelector('.articles-list');
                
                if (event.data.error) {
                    showError('Erreur lors du chargement des articles: ' + event.data.error);
                    return;
                }

                const articles = event.data.articles;
                if (articles && articles.length > 0) {
                    articlesList.innerHTML = articles.map(article => `
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">${escapeHtml(article.title)}</h5>
                                <p class="card-text">${escapeHtml(article.content).replace(/\n/g, '<br>')}</p>
                                <p class="card-text"><small class="text-muted">Créé le ${new Date(article.created_at).toLocaleString()}</small></p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    articlesList.innerHTML = '<p class="text-center">Aucun article stocké localement.</p>';
                }
            }
        });

        function showError(message) {
            const articlesList = document.querySelector('.articles-list');
            articlesList.innerHTML = `<p class="text-center text-danger">${message}</p>`;
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>

  