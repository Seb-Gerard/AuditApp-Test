<?php
$pageTitle = "Gestion des articles";
include_once __DIR__ . '/../../includes/header.php';

// Récupérer les articles directement de la base de données
$articleModel = new Article();
$articles = $articleModel->getAll();
?>

<div class="container mt-5 articles-page">
    <h2 class="mb-4">Gestion des articles</h2>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="h5 mb-0">Articles</h3>
                    <div>
                        <a href="index.php?action=articles&display=create" class="btn btn-sm btn-primary me-2">
                            <i class="fas fa-plus"></i> Nouvel article
                        </a>
                        <button id="sync-button" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-sync"></i> Synchroniser
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="articles-list">
                        <?php if (empty($articles)): ?>
                            <p class="text-center text-muted">Aucun article disponible.</p>
                        <?php else: ?>
                            <?php foreach ($articles as $article): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($article['title']) ?></h5>
                                        <p class="card-text"><?= nl2br(htmlspecialchars($article['content'])) ?></p>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                Créé le <?= date('d/m/Y H:i', strtotime($article['created_at'])) ?>
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inclusion du Service Worker uniquement -->
<script>
// Initialisation du service worker (s'il est supporté)
if ("serviceWorker" in navigator) {
  window.addEventListener("load", () => {
    navigator.serviceWorker
      .register("./sw.js")
      .then((registration) => {
        console.log("Service Worker enregistré avec succès:", registration);
      })
      .catch((error) => {
        console.log("Échec de l'enregistrement du Service Worker:", error);
      });
  });
}
</script>

<!-- Synchronisation manuelle uniquement lorsque le bouton est cliqué -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const syncButton = document.getElementById('sync-button');
    if (syncButton) {
        syncButton.addEventListener('click', function() {
            // Rediriger vers la page actuelle pour rafraîchir les articles
            window.location.reload();
        });
    }
});
</script>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 