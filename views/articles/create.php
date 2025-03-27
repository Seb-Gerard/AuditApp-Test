<?php
$pageTitle = "Nouvel Article";
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-5">
    <h2 class="mb-4">Nouvel Article</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire avec soumission directe à la base de données -->
    <form id="article-form" method="POST" action="index.php?action=articles&method=create" class="needs-validation direct-submit" novalidate>
        <div class="mb-3">
            <label for="title" class="form-label">Titre</label>
            <input type="text" class="form-control" id="title" name="title" required>
            <div class="invalid-feedback">
                Veuillez saisir un titre.
            </div>
        </div>
        
        <div class="mb-3">
            <label for="content" class="form-label">Contenu</label>
            <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
            <div class="invalid-feedback">
                Veuillez saisir un contenu.
            </div>
        </div>
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="index.php?action=articles" class="btn btn-secondary me-md-2">Annuler</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
</div>

<!-- Inclusion du Service Worker uniquement, sans les scripts de gestion d'articles -->
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

// Validation Bootstrap du formulaire sans interférer avec la soumission
document.addEventListener('DOMContentLoaded', function() {
    // Validation du formulaire Bootstrap
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 