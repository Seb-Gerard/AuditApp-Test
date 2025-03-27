<?php
$pageTitle = "Nouvel Audit";
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-5 audit-page">
    <h2 class="mb-4">Nouvel Audit</h2>
    
    <form action="index.php?action=audits&method=create" method="POST" class="needs-validation" novalidate>
        <div class="container">
            <div class="row mb-4">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="numero_site" placeholder="N° du site" required>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="nom_entreprise" placeholder="Nom de l'entreprise" required>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" name="date_creation" required>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary">Créer</button>
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
                        <!-- Les points de vigilance seront chargés ici dynamiquement -->
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div id="selected_points_section" style="display: none;">
                        <h4>Points de vigilance sélectionnés</h4>
                        <div id="selected_points_container">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th class="col-2">Catégorie</th>
                                        <th class="col-2">Sous-catégorie</th>
                                        <th class="col-8">Point de vigilance</th>
                                        <th class="col-1">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="selected_points_body">
                                    <!-- Les points sélectionnés seront ajoutés ici -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Inclusion du fichier JavaScript central -->
<script src="public/assets/js/audit.js"></script>
<script src="public/assets/js/admin.js"></script>

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

</style>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 