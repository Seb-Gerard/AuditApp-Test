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
                    <div class="row">
                        <div class="col-md-4">
                            <p class="card-text">
                                <strong><?php echo $auditCount; ?></strong> audits enregistrés
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p class="card-text badge bg-warning p-2 text-light">
                                <strong><?php echo $auditEnCoursCount; ?></strong> audits en cours
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p class="card-text badge bg-success p-2 text-light">
                                <strong><?php echo $auditTermineCount; ?></strong> audits terminés
                            </p>
                        </div>
                    </div>
                    <p class="card-text mt-2">Gérez vos audits et rapports.</p>
                    <a href="index.php?action=audits" class="btn btn-primary">
                        <i class="fas fa-clipboard-check"></i> Voir les audits
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?> 