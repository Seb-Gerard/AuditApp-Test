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
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#sc-collapse-<?php echo $scId; ?>" 
                                        aria-expanded="false" 
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
                                 class="accordion-collapse collapse" 
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
                                                
                                                <?php if (!empty($point['mesure_reglementaire']) || 
                                                      !empty($point['mode_preuve']) || 
                                                      !empty($point['resultat']) || 
                                                      !empty($point['justification']) || 
                                                      !empty($point['plan_action_description']) ||
                                                      !empty($point['photos']) ||
                                                      !empty($point['documents'])): ?>
                                                <div class="mt-3">
                                                    <div class="alert alert-light">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <?php if (!empty($point['mesure_reglementaire'])): ?>
                                                                    <p><i class="fas fa-check-circle text-success"></i> <strong>Mesure réglementaire</strong></p>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (!empty($point['mode_preuve'])): ?>
                                                                    <p><strong>Mode de preuve:</strong> <?php echo nl2br(htmlspecialchars($point['mode_preuve'])); ?></p>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (!empty($point['non_audite'])): ?>
                                                                    <p><i class="fas fa-ban text-warning"></i> <strong>Non audité</strong></p>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (isset($point['resultat']) && !empty($point['resultat'])): ?>
                                                                    <p>
                                                                        <strong>Résultat:</strong> 
                                                                        <span class="badge <?php echo ($point['resultat'] === 'satisfait') ? 'bg-success' : 'bg-danger'; ?>">
                                                                            <?php echo ($point['resultat'] === 'satisfait') ? 'Satisfait' : 'Non satisfait'; ?>
                                                                        </span>
                                                                    </p>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <div class="col-md-6">
                                                                <?php if (!empty($point['justification'])): ?>
                                                                    <p><strong>Justification:</strong> <?php echo nl2br(htmlspecialchars($point['justification'])); ?></p>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (!empty($point['plan_action_numero']) || !empty($point['plan_action_description'])): ?>
                                                                    <div class="card mt-2 border-warning">
                                                                        <div class="card-header bg-warning bg-opacity-25">Plan d'action</div>
                                                                        <div class="card-body">
                                                                            <?php if (!empty($point['plan_action_numero'])): ?>
                                                                                <p><strong>N°:</strong> <?php echo htmlspecialchars($point['plan_action_numero']); ?></p>
                                                                            <?php endif; ?>
                                                                            
                                                                            <?php if (!empty($point['plan_action_description'])): ?>
                                                                                <p><strong>Action:</strong> <?php echo nl2br(htmlspecialchars($point['plan_action_description'])); ?></p>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Affichage des documents et photos -->
                                                        <?php if (!empty($point['photos']) || !empty($point['documents'])): ?>
                                                        <div class="row mt-3">
                                                            <!-- Photos -->
                                                            <?php if(!empty($point['photos'])): ?>
                                                            <div class="col-md-6">
                                                                <h6><i class="fas fa-camera me-2"></i>Photos (<?php echo count($point['photos']); ?>)</h6>
                                                                <div class="row">
                                                                    <?php foreach($point['photos'] as $photoIndex => $photo): ?>
                                                                    <?php if($photoIndex < 3): // Limiter l'affichage à 3 photos max ?>
                                                                    <div class="col-md-4 mb-2">
                                                                        <img src="<?php echo $photo['chemin_fichier']; ?>" class="img-thumbnail" 
                                                                             alt="<?php echo htmlspecialchars($photo['nom_fichier']); ?>"
                                                                             style="height: 80px; width: 100%; object-fit: cover; cursor: pointer;"
                                                                             onclick="showPhotoModal('<?php echo $photo['chemin_fichier']; ?>', '<?php echo htmlspecialchars($photo['nom_fichier']); ?>')">
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    <?php endforeach; ?>
                                                                    
                                                                    <?php if(count($point['photos']) > 3): ?>
                                                                    <div class="col-12 mt-1">
                                                                        <small class="text-muted">
                                                                            + <?php echo count($point['photos']) - 3; ?> autres photos (ouvrir le formulaire pour toutes les voir)
                                                                        </small>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Documents -->
                                                            <?php if(!empty($point['documents'])): ?>
                                                            <div class="col-md-6">
                                                                <h6><i class="fas fa-file-alt me-2"></i>Documents (<?php echo count($point['documents']); ?>)</h6>
                                                                <div class="list-group list-group-flush">
                                                                    <?php foreach($point['documents'] as $docIndex => $document): ?>
                                                                    <?php if($docIndex < 3): // Limiter l'affichage à 3 documents max ?>
                                                                    <a href="<?php echo $document['chemin_fichier']; ?>" target="_blank" class="list-group-item list-group-item-action p-1">
                                                                        <i class="fas fa-file me-2"></i><?php echo htmlspecialchars($document['nom_fichier']); ?>
                                                                    </a>
                                                                    <?php endif; ?>
                                                                    <?php endforeach; ?>
                                                                    
                                                                    <?php if(count($point['documents']) > 3): ?>
                                                                    <div class="p-1">
                                                                        <small class="text-muted">
                                                                            + <?php echo count($point['documents']) - 3; ?> autres documents (ouvrir le formulaire pour tous les voir)
                                                                        </small>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="text-end mt-2">
                                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" 
                                                                    data-bs-target="#pointOffcanvas-<?php echo $point['point_vigilance_id']; ?>">
                                                                <i class="fas fa-edit"></i> Modifier l'évaluation
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                <div class="mt-2 text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="offcanvas" 
                                                            data-bs-target="#pointOffcanvas-<?php echo $point['point_vigilance_id']; ?>">
                                                        <i class="fas fa-plus-circle"></i> Évaluer ce point
                                                    </button>
                                                </div>
                                                <?php endif; ?>
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
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Évaluation</span>
                            <div>
                                <button type="button" class="btn btn-sm btn-primary me-2" data-bs-toggle="modal" 
                                        data-bs-target="#photoModal-<?php echo $point['point_vigilance_id']; ?>">
                                    <i class="fas fa-camera"></i> Prendre une photo
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" 
                                        data-bs-target="#documentModal-<?php echo $point['point_vigilance_id']; ?>">
                                    <i class="fas fa-file-upload"></i> Ajouter un document
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="evaluation-form-<?php echo $point['point_vigilance_id']; ?>" 
                                  class="evaluation-form" 
                                  data-audit-id="<?php echo $audit['id']; ?>" 
                                  data-point-id="<?php echo $point['point_vigilance_id']; ?>">
                                
                                <input type="hidden" name="audit_id" value="<?php echo $audit['id']; ?>">
                                <input type="hidden" name="point_vigilance_id" value="<?php echo $point['point_vigilance_id']; ?>">
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="mesure_reglementaire-<?php echo $point['point_vigilance_id']; ?>" 
                                           name="mesure_reglementaire" value="1" 
                                           <?php echo (!empty($point['mesure_reglementaire'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="mesure_reglementaire-<?php echo $point['point_vigilance_id']; ?>">
                                        Mesure réglementaire
                                    </label>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="mode_preuve-<?php echo $point['point_vigilance_id']; ?>" class="form-label">Mode de preuve attendu</label>
                                    <textarea class="form-control" id="mode_preuve-<?php echo $point['point_vigilance_id']; ?>" 
                                              name="mode_preuve" rows="2"><?php echo htmlspecialchars($point['mode_preuve'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="non_audite-<?php echo $point['point_vigilance_id']; ?>" 
                                           name="non_audite" value="1" 
                                           <?php echo (!empty($point['non_audite'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="non_audite-<?php echo $point['point_vigilance_id']; ?>">
                                        Non Audité
                                    </label>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Résultat de l'évaluation</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="resultat" 
                                               id="resultat_satisfait-<?php echo $point['point_vigilance_id']; ?>" 
                                               value="satisfait" <?php echo (isset($point['resultat']) && $point['resultat'] === 'satisfait') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="resultat_satisfait-<?php echo $point['point_vigilance_id']; ?>">
                                            Satisfait
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="resultat" 
                                               id="resultat_non_satisfait-<?php echo $point['point_vigilance_id']; ?>" 
                                               value="non_satisfait" <?php echo (isset($point['resultat']) && $point['resultat'] === 'non_satisfait') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="resultat_non_satisfait-<?php echo $point['point_vigilance_id']; ?>">
                                            Non Satisfait
                                        </label>
                                    </div>
                                    <div class="form-text text-muted">
                                        Vous pouvez choisir un résultat indépendamment de l'option "Non Audité"
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="justification-<?php echo $point['point_vigilance_id']; ?>" class="form-label">Commentaire / Justification de l'évaluation</label>
                                    <textarea class="form-control" id="justification-<?php echo $point['point_vigilance_id']; ?>" 
                                              name="justification" rows="3"><?php echo htmlspecialchars($point['justification'] ?? ''); ?></textarea>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h5>Plan d'action</h5>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="plan_action_numero-<?php echo $point['point_vigilance_id']; ?>" class="form-label">N°</label>
                                            <input type="number" class="form-control" id="plan_action_numero-<?php echo $point['point_vigilance_id']; ?>" 
                                                   name="plan_action_numero" value="<?php echo htmlspecialchars($point['plan_action_numero'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="mb-3">
                                            <label for="plan_action_description-<?php echo $point['point_vigilance_id']; ?>" class="form-label">Action</label>
                                            <textarea class="form-control" id="plan_action_description-<?php echo $point['point_vigilance_id']; ?>" 
                                                      name="plan_action_description" rows="3"><?php echo htmlspecialchars($point['plan_action_description'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                </div>
                            </form>
                            
                            <!-- Section des photos -->
                            <?php if(!empty($point['photos'])): ?>
                            <div class="mt-4">
                                <h5><i class="fas fa-camera me-2"></i>Photos (<?php echo count($point['photos']); ?>)</h5>
                                <div class="row">
                                    <?php foreach($point['photos'] as $photo): ?>
                                    <div class="col-md-4 mb-3" id="photo-container-<?php echo $photo['id']; ?>">
                                        <div class="card h-100">
                                            <img src="<?php echo $photo['chemin_fichier']; ?>" class="card-img-top img-thumbnail" 
                                                 alt="<?php echo htmlspecialchars($photo['nom_fichier']); ?>"
                                                 style="height: 150px; object-fit: cover; cursor: pointer;"
                                                 onclick="showPhotoModal('<?php echo $photo['chemin_fichier']; ?>', '<?php echo htmlspecialchars($photo['nom_fichier']); ?>')">
                                            <div class="card-footer d-flex justify-content-between align-items-center py-1">
                                                <small class="text-muted" title="<?php echo date('d/m/Y H:i', strtotime($photo['date_ajout'])); ?>">
                                                    <?php echo date('d/m/Y', strtotime($photo['date_ajout'])); ?>
                                                </small>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="supprimerDocument(<?php echo $photo['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Section des documents -->
                            <?php if(!empty($point['documents'])): ?>
                            <div class="mt-4">
                                <h5><i class="fas fa-file-alt me-2"></i>Documents (<?php echo count($point['documents']); ?>)</h5>
                                <div class="list-group">
                                    <?php foreach($point['documents'] as $document): ?>
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                                         id="document-container-<?php echo $document['id']; ?>">
                                        <a href="<?php echo $document['chemin_fichier']; ?>" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-file me-2"></i>
                                            <?php echo htmlspecialchars($document['nom_fichier']); ?>
                                        </a>
                                        <div>
                                            <small class="text-muted me-3" title="<?php echo date('d/m/Y H:i', strtotime($document['date_ajout'])); ?>">
                                                <?php echo date('d/m/Y', strtotime($document['date_ajout'])); ?>
                                            </small>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="supprimerDocument(<?php echo $document['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
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
    
    <!-- Modals pour la prise de photo -->
    <?php foreach($pointsVigilance as $point): ?>
    <div class="modal fade" id="photoModal-<?php echo $point['point_vigilance_id']; ?>" tabindex="-1" 
         aria-labelledby="photoModalLabel-<?php echo $point['point_vigilance_id']; ?>">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel-<?php echo $point['point_vigilance_id']; ?>">
                        Prendre une photo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <div id="camera-container-<?php echo $point['point_vigilance_id']; ?>" class="mb-3">
                                <video id="video-<?php echo $point['point_vigilance_id']; ?>" 
                                      style="width: 100%; max-width: 640px; border: 1px solid #ddd;" autoplay></video>
                            </div>
                            <div id="captured-photo-container-<?php echo $point['point_vigilance_id']; ?>" 
                                 style="display: none; margin-bottom: 1rem;">
                                <canvas id="canvas-<?php echo $point['point_vigilance_id']; ?>" 
                                       style="width: 100%; max-width: 640px; border: 1px solid #ddd;"></canvas>
                            </div>
                            <div class="mb-3">
                                <button type="button" id="capture-btn-<?php echo $point['point_vigilance_id']; ?>" 
                                        class="btn btn-primary me-2" onclick="capturePhoto(<?php echo $point['point_vigilance_id']; ?>)">
                                    <i class="fas fa-camera"></i> Capturer
                                </button>
                                <button type="button" id="save-btn-<?php echo $point['point_vigilance_id']; ?>" 
                                        class="btn btn-success me-2" style="display: none;" 
                                        onclick="savePhoto(<?php echo $audit['id']; ?>, <?php echo $point['point_vigilance_id']; ?>)">
                                    <i class="fas fa-save"></i> Enregistrer
                                </button>
                                <button type="button" id="retake-btn-<?php echo $point['point_vigilance_id']; ?>" 
                                        class="btn btn-secondary" style="display: none;" 
                                        onclick="retakePhoto(<?php echo $point['point_vigilance_id']; ?>)">
                                    <i class="fas fa-redo"></i> Reprendre
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <!-- Modals pour l'ajout de document -->
    <?php foreach($pointsVigilance as $point): ?>
    <div class="modal fade" id="documentModal-<?php echo $point['point_vigilance_id']; ?>" tabindex="-1" 
         aria-labelledby="documentModalLabel-<?php echo $point['point_vigilance_id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="documentModalLabel-<?php echo $point['point_vigilance_id']; ?>">
                        Ajouter un document
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <form id="document-form-<?php echo $point['point_vigilance_id']; ?>" 
                          class="document-form"
                          enctype="multipart/form-data">
                        <input type="hidden" name="audit_id" value="<?php echo $audit['id']; ?>">
                        <input type="hidden" name="point_vigilance_id" value="<?php echo $point['point_vigilance_id']; ?>">
                        <input type="hidden" name="type" value="document">
                        
                        <div class="mb-3">
                            <label for="document-file-<?php echo $point['point_vigilance_id']; ?>" class="form-label">
                                Sélectionner un document
                            </label>
                            <input type="file" class="form-control" id="document-file-<?php echo $point['point_vigilance_id']; ?>" 
                                   name="document" required accept=".pdf,.doc,.docx,.xls,.xlsx,.txt">
                            <div class="form-text">Formats acceptés: PDF, Word, Excel, TXT</div>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Télécharger
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <!-- Modal pour afficher les photos -->
    <div class="modal fade" id="viewPhotoModal" tabindex="-1" 
         aria-labelledby="viewPhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPhotoModalLabel">Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modal-photo" src="" class="img-fluid" alt="Photo">
                </div>
            </div>
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

<!-- Charger le script externe plutôt que d'intégrer le JavaScript dans la page -->
<script src="public/assets/js/audit_view.js"></script>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 