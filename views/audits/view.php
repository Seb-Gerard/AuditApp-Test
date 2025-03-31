<?php
$pageTitle = "Détails de l'Audit";
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-9">
            <h2>Détails de l'Audit</h2>
        </div>
        <div class="col-md-3 text-end">
            <a href="index.php?action=audits" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>
    
    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success']; 
        unset($_SESSION['success']);
        ?>
    </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Informations générales</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Numéro du site:</strong> <?php echo htmlspecialchars($audit['numero_site']); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Nom de l'entreprise:</strong> <?php echo htmlspecialchars($audit['nom_entreprise']); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Créé le:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($audit['created_at']))); ?></p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <p><strong>Dernière mise à jour:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($audit['updated_at']))); ?></p>
                </div>
                <div class="col-md-4">
                    <p>
                        <strong>Statut:</strong>
                        <span class="badge <?php echo (isset($audit['statut']) && $audit['statut'] === 'en_cours') ? 'bg-warning' : 'bg-success'; ?> p-2">
                            <?php echo (isset($audit['statut']) && $audit['statut'] === 'en_cours') ? 'En cours' : 'Terminé'; ?>
                        </span>
                        <?php if (!isset($audit['statut']) || $audit['statut'] === 'en_cours'): ?>
                            <a href="index.php?action=audits&method=updateStatus&id=<?php echo $audit['id']; ?>&statut=termine" 
                               class="btn btn-sm btn-success ms-2" title="Marquer comme terminé">
                                <i class="fas fa-check"></i> Terminer
                            </a>
                        <?php else: ?>
                            <a href="index.php?action=audits&method=updateStatus&id=<?php echo $audit['id']; ?>&statut=en_cours" 
                               class="btn btn-sm btn-warning ms-2" title="Marquer comme en cours">
                                <i class="fas fa-sync"></i> Reprendre
                            </a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Points de vigilance sélectionnés</h5>
        </div>
        <div class="card-body">
            <?php if(empty($pointsVigilance)): ?>
                <div class="alert alert-warning">
                    Aucun point de vigilance n'a été sélectionné pour cet audit.
                </div>
            <?php else: ?>
                <div class="accordion" id="accordionPointsVigilance">
                    <?php $counter = 1; ?>
                    <?php foreach($pointsVigilance as $point): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-<?php echo $point['point_vigilance_id']; ?>">
                                <button class="accordion-button <?php echo ($counter > 1) ? 'collapsed' : ''; ?>" type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse-<?php echo $point['point_vigilance_id']; ?>" 
                                        aria-expanded="<?php echo ($counter == 1) ? 'true' : 'false'; ?>" 
                                        aria-controls="collapse-<?php echo $point['point_vigilance_id']; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div>
                                            <span class="badge bg-primary me-2"><?php echo $counter++; ?></span>
                                            <strong><?php echo htmlspecialchars($point['point_vigilance_nom']); ?></strong>
                                        </div>
                                        <div class="text-muted small">
                                            <?php echo htmlspecialchars($point['categorie_nom']); ?> / 
                                            <?php echo htmlspecialchars($point['sous_categorie_nom']); ?>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse-<?php echo $point['point_vigilance_id']; ?>" 
                                 class="accordion-collapse collapse <?php echo ($counter == 2) ? 'show' : ''; ?>" 
                                 aria-labelledby="heading-<?php echo $point['point_vigilance_id']; ?>" 
                                 data-bs-parent="#accordionPointsVigilance">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="card">
                                                <div class="card-header">Évaluation</div>
                                                <div class="card-body">
                                                    <p class="text-center text-muted">
                                                        <em>Le formulaire d'évaluation sera disponible prochainement</em>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <?php 
                                            // Vérifier si une image est associée à ce point de vigilance
                                            try {
                                                $pv = new PointVigilance();
                                                $pointDetails = $pv->getById($point['point_vigilance_id']);
                                                
                                                if (!empty($pointDetails['image'])) {
                                                    $imagePath = 'public/uploads/points_vigilance/' . $pointDetails['image'];
                                                    if (file_exists($imagePath)) {
                                                        echo '<div class="card">';
                                                        echo '<div class="card-header">Image</div>';
                                                        echo '<div class="card-body text-center">';
                                                        echo '<img src="' . $imagePath . '" class="img-fluid rounded img-thumbnail" alt="Image du point de vigilance" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#imageModal-' . $point['point_vigilance_id'] . '">';
                                                        echo '</div></div>';
                                                        
                                                        // Modal pour afficher l'image en grand
                                                        echo '<div class="modal fade" id="imageModal-' . $point['point_vigilance_id'] . '" tabindex="-1" aria-labelledby="imageModalLabel-' . $point['point_vigilance_id'] . '" aria-hidden="true">';
                                                        echo '<div class="modal-dialog modal-lg modal-dialog-centered">';
                                                        echo '<div class="modal-content">';
                                                        echo '<div class="modal-header">';
                                                        echo '<h5 class="modal-title" id="imageModalLabel-' . $point['point_vigilance_id'] . '">' . htmlspecialchars($point['point_vigilance_nom']) . '</h5>';
                                                        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>';
                                                        echo '</div>';
                                                        echo '<div class="modal-body text-center">';
                                                        echo '<img src="' . $imagePath . '" class="img-fluid" alt="Image du point de vigilance">';
                                                        echo '</div>';
                                                        echo '</div>';
                                                        echo '</div>';
                                                        echo '</div>';
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                // Ne rien faire en cas d'erreur
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12 text-end">
            <a href="index.php?action=audits&method=edit&id=<?php echo $audit['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Modifier l'audit
            </a>
            <a href="#" onclick="confirmDelete(<?php echo $audit['id']; ?>); return false;" class="btn btn-danger">
                <i class="fas fa-trash"></i> Supprimer l'audit
            </a>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cet audit ? Cette action est irréversible.")) {
        window.location.href = "index.php?action=audits&method=delete&id=" + id;
    }
}
</script>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 