<?php
$pageTitle = "Modifier l'Audit";
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-5 audit-page">
    <div class="row mb-4">
        <div class="col-md-9">
            <h2 class="mb-4">Modifier l'Audit #<?php echo htmlspecialchars($audit['numero_site']); ?></h2>
        </div>
        <div class="col-md-3 text-end">
            <a href="index.php?action=audits&method=view&id=<?php echo $audit['id']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour aux détails
            </a>
        </div>
    </div>
    
    <form action="index.php?action=audits&method=edit&id=<?php echo $audit['id']; ?>" method="POST" class="needs-validation" id="audit-form" novalidate enctype="multipart/form-data">
        <div class="container">
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="logo" class="form-label">Logo de l'entreprise</label>
                        <?php if (!empty($audit['logo'])): ?>
                            <div class="mb-2">
                                <img src="public/uploads/logos/<?php echo htmlspecialchars($audit['logo']); ?>" 
                                     alt="Logo actuel" class="img-thumbnail" style="max-height: 50px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="logo" id="logo" accept="image/*">
                        <small class="form-text text-muted">Laissez vide pour conserver le logo actuel</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="numero_site" class="form-label">Numéro du site</label>
                    <input type="text" class="form-control" name="numero_site" id="numero_site" placeholder="N° du site" required value="<?php echo htmlspecialchars($audit['numero_site']); ?>">
                </div>
                <div class="col-md-5">
                    <label for="nom_entreprise" class="form-label">Nom de l'entreprise</label>
                    <input type="text" class="form-control" name="nom_entreprise" id="nom_entreprise" placeholder="Nom de l'entreprise" required value="<?php echo htmlspecialchars($audit['nom_entreprise']); ?>">
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="date_creation" class="form-label">Date de création</label>
                    <input type="date" class="form-control" name="date_creation" id="date_creation" required value="<?php echo htmlspecialchars($audit['date_creation']); ?>">
                </div>
                <div class="col-md-4">
                    <label for="statut" class="form-label">Statut</label>
                    <select name="statut" id="statut" class="form-select">
                        <option value="en_cours" <?php echo (isset($audit['statut']) && $audit['statut'] === 'en_cours') ? 'selected' : ''; ?>>En cours</option>
                        <option value="termine" <?php echo (isset($audit['statut']) && $audit['statut'] === 'termine') ? 'selected' : ''; ?>>Terminé</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100" id="submitBtn">Mettre à jour</button>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Sélectionnez les catégories</h5>
                    <div class="dropdown-check-list">
                        <div class="dropdown-check-button form-control d-flex justify-content-between align-items-center">
                            <span id="categories_text">Sélectionnez les catégories</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-check-items">
                        <?php foreach ($categories as $categorie): ?>
                                <div class="form-check">
                                    <input class="form-check-input categorie-checkbox" type="checkbox" 
                                           name="categories[]" value="<?php echo $categorie['id']; ?>" 
                                           id="categorie-<?php echo $categorie['id']; ?>">
                                    <label class="form-check-label" for="categorie-<?php echo $categorie['id']; ?>">
                                <?php echo htmlspecialchars($categorie['nom']); ?>
                                    </label>
                                </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>Sélectionnez les sous-catégories</h5>
                    <div class="dropdown-check-list">
                        <div class="dropdown-check-button form-control d-flex justify-content-between align-items-center">
                            <span id="sous_categories_text">Veuillez d'abord sélectionner au moins une catégorie</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div id="sous_categories_container" class="dropdown-check-items">
                            <!-- Les sous-catégories seront chargées ici dynamiquement -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <h4>Points de vigilance disponibles</h4>
                    <div id="points_vigilance_container">
                        <p>Veuillez sélectionner des catégories et sous-catégories pour voir les points de vigilance disponibles</p>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div id="selected_points_section" style="display: none;">
                        <h4>Points de vigilance sélectionnés</h4>
                        <p class="text-muted small"><i class="fas fa-info-circle"></i> Vous pouvez réordonner les points en faisant glisser les lignes à l'aide de l'icône <i class="fas fa-grip-vertical"></i>.</p>
                        <div id="selected_points_container">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th class="col-1">Action</th>
                                        <th class="col-2">Catégorie</th>
                                        <th class="col-2">Sous-catégorie</th>
                                        <th class="col-6">Point de vigilance</th>
                                        <th class="col-1">Supprimer</th>
                                    </tr>
                                </thead>
                                <tbody id="selected_points_list">
                                    <tr>
                                        <td colspan="5" class="text-center">Aucun point sélectionné</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="hidden_fields_container">
                <!-- Les champs cachés seront ajoutés ici par JavaScript -->
            </div>
        </div>
    </form>
</div>
<!-- Script d'initialisation avec les points existants -->
<script src="public/assets/js/admin.js?v=<?php echo time(); ?>"></script>
<script src="public/assets/js/audit_manager.js?v=<?php echo time(); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AuditManager !== 'undefined') {
        // Initialiser le gestionnaire d'audit
        AuditManager.manualInit();
        
        // Précharger les points de vigilance existants
        <?php if (!empty($pointsVigilance)): ?>
        // Ajouter chaque point existant
        <?php foreach ($pointsVigilance as $point): ?>
            AuditManager.addPointVigilance(
                <?php echo $point['point_vigilance_id']; ?>,
                <?php echo $point['categorie_id']; ?>,
                <?php echo $point['sous_categorie_id']; ?>,
                "<?php echo addslashes($point['categorie_nom']); ?>",
                "<?php echo addslashes($point['sous_categorie_nom']); ?>",
                "<?php echo addslashes($point['point_vigilance_nom']); ?>"
            );
        <?php endforeach; ?>
        <?php endif; ?>
    }
});
</script>

<style>
    .dropdown-check-list {
        position: relative;
        width: 100%;
    }

    .dropdown-check-button {
        cursor: pointer;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 10px 15px;
    }

    .dropdown-check-items {
        display: none;
        position: absolute;
        width: 100%;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        padding: 0 2rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .dropdown-check-items.show {
        display: block;
    }

    .dropdown-check-items .form-check {
        padding: 8px;
        margin-bottom: 2px;
        border-radius: 4px;
    }

    .dropdown-check-items .form-check:hover {
        background-color: #f8f9fa;
    }

    .dropdown-check-items .form-check-input:checked + .form-check-label {
        font-weight: bold;
    }
    
    /* Styles pour le drag-and-drop */
    #selected_points_list tr {
        cursor: move;
        transition: background-color 0.2s ease;
    }

    #selected_points_list tr.dragging {
        opacity: 0.6;
        background-color: #f0f0f0;
        border: 1px dashed #007bff;
    }

    #selected_points_list tr.drag-over {
        background-color: #e6f7ff;
        border-top: 2px solid #007bff;
    }

    .drag-handle {
        cursor: move;
        user-select: none;
    }

    .drag-handle:hover {
        color: #007bff;
    }
    
    /* Styles pour les en-têtes de sous-catégories */
    .subcategory-header {
        background-color: #f0f5ff !important;
        font-weight: bold;
        cursor: default !important;
    }
    
    .subcategory-header td {
        padding: 8px 12px;
        border-top: 2px solid #c0d6ff;
        border-bottom: 2px solid #c0d6ff;
        color: #2c5282;
    }
</style>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 