<?php
$pageTitle = "Détails de l'Audit";
include_once __DIR__ . '/../../includes/header.php';
?>

<style>
    /* Styles pour les en-têtes de sous-catégories */
    .subcategory-header {
        background-color: #f0f5ff !important;
        font-weight: bold;
    }
    
    .subcategory-header td {
        padding: 8px 12px;
        border-top: 2px solid #c0d6ff;
        border-bottom: 2px solid #c0d6ff;
        color: #2c5282;
    }

    /* Style pour l'offcanvas */
    .offcanvas.w-70 {
        width: 70% !important;
    }

    /* Style pour la modal de l'image */
    .modal-backdrop {
        z-index: 1060 !important;
    }
    .modal {
        z-index: 1061 !important;
    }
</style>

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
                <?php
                // Réorganiser les points par sous-catégorie
                $pointsBySousCategorie = [];
                foreach($pointsVigilance as $point) {
                    $scId = $point['sous_categorie_id'];
                    if (!isset($pointsBySousCategorie[$scId])) {
                        $pointsBySousCategorie[$scId] = [
                            'nom' => $point['sous_categorie_nom'],
                            'categorie_nom' => $point['categorie_nom'],
                            'points' => []
                        ];
                    }
                    $pointsBySousCategorie[$scId]['points'][] = $point;
                }
                ?>
                
                <div class="accordion" id="accordionSousCategories">
                    <?php $scCounter = 1; ?>
                    <?php foreach($pointsBySousCategorie as $scId => $sousCategorie): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="sc-heading-<?php echo $scId; ?>">
                                <button class="accordion-button <?php echo ($scCounter > 1) ? 'collapsed' : ''; ?>" type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#sc-collapse-<?php echo $scId; ?>" 
                                        aria-expanded="<?php echo ($scCounter == 1) ? 'true' : 'false'; ?>" 
                                        aria-controls="sc-collapse-<?php echo $scId; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div>
                                            <span class="badge bg-primary me-2"><?php echo $scCounter++; ?></span>
                                            <strong><?php echo htmlspecialchars($sousCategorie['nom']); ?></strong>
                                        </div>
                                        <div class="text-muted small">
                                            <?php echo htmlspecialchars($sousCategorie['categorie_nom']); ?>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="sc-collapse-<?php echo $scId; ?>" 
                                 class="accordion-collapse collapse <?php echo ($scCounter == 2) ? 'show' : ''; ?>" 
                                 aria-labelledby="sc-heading-<?php echo $scId; ?>" 
                                 data-bs-parent="#accordionSousCategories">
                                <div class="accordion-body">
                                    <div class="list-group">
                                        <?php foreach($sousCategorie['points'] as $pointIndex => $point): ?>
                                            <div class="list-group-item list-group-item-action">
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-md-10">
                                                        <h5 class="mb-0">
                                                            <span class="badge bg-secondary me-2"><?php echo ($pointIndex + 1); ?></span>
                                                            <a href="#" class="text-decoration-none text-dark" data-bs-toggle="offcanvas" 
                                                               data-bs-target="#pointOffcanvas-<?php echo $point['point_vigilance_id']; ?>" 
                                                               aria-controls="pointOffcanvas-<?php echo $point['point_vigilance_id']; ?>">
                                                                <?php echo htmlspecialchars($point['point_vigilance_nom']); ?>
                                                            </a>
                                                        </h5>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Offcanvas pour les détails des points -->
    <?php foreach($pointsVigilance as $point): ?>
    <div class="offcanvas offcanvas-end w-70" tabindex="-1" id="pointOffcanvas-<?php echo $point['point_vigilance_id']; ?>" 
         aria-labelledby="pointOffcanvasLabel-<?php echo $point['point_vigilance_id']; ?>">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="pointOffcanvasLabel-<?php echo $point['point_vigilance_id']; ?>">
                <?php echo htmlspecialchars($point['point_vigilance_nom']); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
        </div>
        <div class="offcanvas-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-3">
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
                    try {
                        $pv = new PointVigilance();
                        $pointDetails = $pv->getById($point['point_vigilance_id']);
                        
                        if (!empty($pointDetails['image'])) {
                            $imagePath = 'public/uploads/points_vigilance/' . $pointDetails['image'];
                            if (file_exists($imagePath)) {
                                echo '<div class="card">';
                                echo '<div class="card-header">Image</div>';
                                echo '<div class="card-body text-center">';
                                echo '<img src="' . $imagePath . '" class="img-fluid rounded img-thumbnail" alt="Image du point de vigilance" style="cursor: pointer;" onclick="showImageModal(' . $point['point_vigilance_id'] . ')">';
                                echo '</div></div>';
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
    <?php endforeach; ?>

    <!-- Modals pour les images (en dehors de l'offcanvas) -->
    <?php foreach($pointsVigilance as $point): ?>
        <?php 
        try {
            $pv = new PointVigilance();
            $pointDetails = $pv->getById($point['point_vigilance_id']);
            
            if (!empty($pointDetails['image'])) {
                $imagePath = 'public/uploads/points_vigilance/' . $pointDetails['image'];
                if (file_exists($imagePath)) {
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
    <?php endforeach; ?>
    
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

function showImageModal(pointId) {
    // Fermer l'offcanvas
    const offcanvas = document.querySelector('.offcanvas.show');
    if (offcanvas) {
        const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
        if (bsOffcanvas) {
            bsOffcanvas.hide();
        }
    }
    
    // Afficher la modal
    const modal = new bootstrap.Modal(document.getElementById('imageModal-' + pointId));
    modal.show();
}
</script>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 