<?php
$pageTitle = "Nouvel Audit";
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-5 audit-page">
    <h2 class="mb-4">Nouvel Audit</h2>
    
    <form action="index.php?action=audits&method=create" method="POST" class="needs-validation" id="audit-form" novalidate enctype="multipart/form-data">
        <div class="container">
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="logo" class="form-label">Logo de l'entreprise</label>
                        <input type="file" class="form-control" name="logo" id="logo" accept="image/*">
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="numero_site" class="form-label">Numéro du site</label>
                    <input type="text" class="form-control" name="numero_site" id="numero_site" placeholder="N° du site" required>
                </div>
                <div class="col-md-5">
                    <label for="nom_entreprise" class="form-label">Nom de l'entreprise</label>
                    <input type="text" class="form-control" name="nom_entreprise" id="nom_entreprise" placeholder="Nom de l'entreprise" required>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="date_creation" class="form-label">Date de création</label>
                    <input type="date" class="form-control" name="date_creation" id="date_creation" required>
                </div>
                <div class="col-md-4">
                    <label for="statut" class="form-label">Statut</label>
                    <select name="statut" id="statut" class="form-select">
                        <option value="en_cours" selected>En cours</option>
                        <option value="termine">Terminé</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100" id="submitBtn">Créer</button>
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

<!-- Inclusions des scripts JavaScript -->
<script src="public/assets/js/admin.js?v=<?php echo time(); ?>"></script>
<script src="public/assets/js/audit_manager.js?v=<?php echo time(); ?>"></script>

<!-- Script d'initialisation du formulaire -->
<script>
// Fonction d'initialisation sécurisée
function initAuditManager() {
    if (typeof AuditManager !== 'undefined') {
        if (typeof AuditManager.manualInit === 'function') {
            AuditManager.manualInit();
        } else if (typeof AuditManager.init === 'function') {
            AuditManager.init();
        }
    }
}

// Essayer d'initialiser immédiatement
initAuditManager();

// Fallback - DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    // S'assurer que le formulaire a un ID
    const auditForm = document.querySelector('form[action="index.php?action=audits&method=create"]');
    if (auditForm && !auditForm.id) {
        auditForm.id = 'audit-form';
    }
    
    // Réessayer après un court délai
    setTimeout(initAuditManager, 300);
});

// Fallback final - window load
window.addEventListener('load', function() {
    setTimeout(initAuditManager, 500);
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