<?php

// Titre de la page pour le header
$pageTitle = "Mode Hors Ligne";

// Styles supplémentaires spécifiques à offline.php
$additionalStyles = [];

// Scripts supplémentaires spécifiques à offline.php
$additionalScripts = [];

// Vérification si l'utilisateur est en ligne
if (isset($_SERVER['HTTP_USER_AGENT']) && !isset($_GET['force_offline'])) {
    // Vérification de base côté serveur (pas parfait mais aide à rediriger)
    echo '<script>
        if (navigator.onLine) {
            window.location.href = "index.php";
        }
    </script>';
}

// Inclure le header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <!-- Alertes pour l'état de connexion -->
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

    <!-- Section des articles -->
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

    <!-- Formulaire pour ajouter un article -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Rédiger un nouvel article</h5>
        </div>
        <div class="card-body">
            <form id="offlineArticleForm">
                <div class="mb-3">
                    <label for="articleTitle" class="form-label">Titre</label>
                    <input type="text" class="form-control" id="articleTitle" required>
                </div>
                <div class="mb-3">
                    <label for="articleContent" class="form-label">Contenu</label>
                    <textarea class="form-control" id="articleContent" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer localement</button>
            </form>
        </div>
    </div>

    <div id="articlesContainer" class="row">
        <!-- Les articles seront chargés ici -->
        <div class="col-12">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p>Chargement des articles...</p>
            </div>
        </div>
    </div>

    <!-- Section des audits -->
    <div class="mt-5 pt-4 border-top">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Audits stockés localement</h2>
            <div>
                <button id="syncAuditsButton" class="btn btn-primary" style="display: none;">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    Synchroniser les audits
                </button>
                <button id="testAuditButton" class="btn btn-secondary ms-2">
                    Test AuditDB
                </button>
                <span id="lastAuditSync" class="ms-2 text-muted small"></span>
            </div>
        </div>
        
        <!-- Tableaux des audits stockés localement -->
        <div id="offlineAuditsContainer">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p>Chargement des audits...</p>
            </div>
        </div>
    </div>
</div>

<!-- Script d'initialisation -->
<script>
// Vérification de l'état en ligne et redirection si nécessaire
if (navigator.onLine && !window.location.search.includes('force_offline')) {
    console.log("Utilisateur en ligne, redirection vers index.php");
    window.location.href = 'index.php';
}

// Fonction pour charger dynamiquement un script
function loadScript(url) {
    console.log(`Tentative de chargement du script: ${url}`);
    return new Promise((resolve, reject) => {
        // Vérifier si le script est déjà chargé
        if (document.querySelector(`script[src="${url}"]`)) {
            console.log(`Script ${url} déjà chargé`);
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = url;
        script.onload = () => {
            console.log(`Script ${url} chargé avec succès`);
            resolve();
        };
        script.onerror = (error) => {
            console.error(`Erreur lors du chargement de ${url}:`, error);
            reject(error);
        };
        document.head.appendChild(script);
    });
}

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', async function() {
    try {
        // Charger les scripts dans l'ordre
        await loadScript('/Audit/public/assets/js/db.js');
        console.log('db.js chargé');
        
        await loadScript('/Audit/public/assets/js/auditdb.js');
        console.log('auditdb.js chargé');
        
        await loadScript('/Audit/public/assets/js/offline-manager.js');
        console.log('offline-manager.js chargé');

        // Vérifier que les objets nécessaires sont disponibles
        if (typeof window.ArticleDB === 'undefined') {
            throw new Error("ArticleDB n'est pas disponible après le chargement");
        }

        // Initialiser le gestionnaire de mode hors ligne
        if (typeof OfflineManager !== 'undefined') {
            await OfflineManager.init();
        } else {
            throw new Error("Le module OfflineManager n'est pas disponible");
        }
    } catch (error) {
        console.error("Erreur lors du chargement des scripts:", error);
        document.getElementById('articlesContainer').innerHTML = 
            '<div class="alert alert-danger">Une erreur est survenue lors du chargement des modules nécessaires. Veuillez recharger la page.</div>';
        document.getElementById('offlineAuditsContainer').innerHTML =
            '<div class="alert alert-danger">Une erreur est survenue lors du chargement des modules nécessaires. Veuillez recharger la page.</div>';
    }
});
</script>

<?php
// Inclure le footer
include_once __DIR__ . '/includes/footer.php';
?>

  