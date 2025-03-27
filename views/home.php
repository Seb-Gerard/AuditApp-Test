<?php
$pageTitle = "Accueil - Gestion des Audits";
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Tableau de bord</h1>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Articles</h3>
                    <p class="card-text">
                        <strong><?php echo $articleCount; ?></strong> articles enregistrés
                    </p>
                    <p class="card-text">Gérez vos articles et documents.</p>
                    <a href="index.php?action=articles" class="btn btn-primary">
                        <i class="fas fa-file-alt"></i> Voir les articles
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Audits</h3>
                    <p class="card-text">
                        <strong><?php echo $auditCount; ?></strong> audits enregistrés
                    </p>
                    <p class="card-text">Gérez vos audits et rapports.</p>
                    <a href="index.php?action=audits" class="btn btn-primary">
                        <i class="fas fa-clipboard-check"></i> Voir les audits
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?> 