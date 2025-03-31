<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="container mt-4 admin-panel">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <h1 class="mb-4">Administration</h1>

    <nav>
        <div class="nav nav-tabs mb-3" id="nav-tab" role="tablist">
            <button class="nav-link active" id="nav-categories-tab" data-bs-toggle="tab" data-bs-target="#nav-categories" type="button" role="tab" aria-controls="nav-categories" aria-selected="true">
                Catégories
            </button>
            <button class="nav-link" id="nav-subcategories-tab" data-bs-toggle="tab" data-bs-target="#nav-subcategories" type="button" role="tab" aria-controls="nav-subcategories" aria-selected="false">
                Sous-catégories
            </button>
            <button class="nav-link" id="nav-points-tab" data-bs-toggle="tab" data-bs-target="#nav-points" type="button" role="tab" aria-controls="nav-points" aria-selected="false">
                Points de vigilance
            </button>
        </div>
    </nav>

    <div class="tab-content" id="nav-tabContent">
        <!-- Onglet Catégories -->
        <div class="tab-pane fade show active" id="nav-categories" role="tabpanel" aria-labelledby="nav-categories-tab">
            <div class="card">
                <div class="card-body">
                    <form action="index.php?controller=admin&method=createCategory" method="POST">
                        <div class="mb-3">
                            <label for="category_nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="category_nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_description" class="form-label">Description</label>
                            <textarea class="form-control" id="category_description" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter une catégorie</button>
                    </form>

                    <hr>

                    <h3 class="h6 mb-3">Catégories existantes</h3>
                    <ul class="list-group">
                        <?php foreach ($categories as $categorie): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <strong><?= htmlspecialchars($categorie['nom']) ?></strong>
                                    <?php if (!empty($categorie['description'])): ?>
                                        <br><small><?= htmlspecialchars($categorie['description']) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editCategorie(<?= $categorie['id'] ?>, '<?= addslashes($categorie['nom']) ?>', '<?= addslashes($categorie['description']) ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDeleteCategorie(<?= $categorie['id'] ?>, '<?= addslashes($categorie['nom']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <!-- Modal Modification Catégorie -->
                    <div class="modal fade" id="editCategorieModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Modifier la catégorie</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="index.php?controller=admin&method=updateCategory" method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" id="edit_categorie_id" name="id">
                                        <div class="mb-3">
                                            <label for="edit_categorie_nom" class="form-label">Nom</label>
                                            <input type="text" class="form-control" id="edit_categorie_nom" name="nom" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_categorie_description" class="form-label">Description</label>
                                            <textarea class="form-control" id="edit_categorie_description" name="description" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Suppression Catégorie -->
                    <div class="modal fade" id="deleteCategorieModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmer la suppression</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    Êtes-vous sûr de vouloir supprimer la catégorie <strong id="delete_categorie_name"></strong> ?
                                    <br>Cette action est irréversible.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <form action="index.php?controller=admin&method=deleteCategory" method="POST" class="d-inline">
                                        <input type="hidden" id="delete_categorie_id" name="id">
                                        <button type="submit" class="btn btn-danger">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Sous-catégories -->
        <div class="tab-pane fade" id="nav-subcategories" role="tabpanel" aria-labelledby="nav-subcategories-tab">
            <div class="card">
                <div class="card-body">
                    <form action="index.php?controller=admin&method=createSubCategory" method="POST">
                        <div class="mb-3">
                            <label for="subcategory_category" class="form-label">Catégorie parente</label>
                            <select class="form-select" id="subcategory_category" name="categorie_id" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?= $categorie['id'] ?>"><?= htmlspecialchars($categorie['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subcategory_nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="subcategory_nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="subcategory_description" class="form-label">Description</label>
                            <textarea class="form-control" id="subcategory_description" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter une sous-catégorie</button>
                    </form>

                    <hr>

                    <div id="subcategories-list" style="display: none;">
                        <h3 class="h6 mb-3">Sous-catégories existantes</h3>
                        <ul class="list-group" id="subcategories-container">
                            <!-- Les sous-catégories seront chargées dynamiquement ici -->
                        </ul>
                    </div>

                    <!-- Modal Modification Sous-catégorie -->
                    <div class="modal fade" id="editSousCategorieModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Modifier la sous-catégorie</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="index.php?controller=admin&method=updateSubCategory" method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" id="edit_souscategorie_id" name="id">
                                        <div class="mb-3">
                                            <label for="edit_souscategorie_category" class="form-label">Catégorie parente</label>
                                            <select class="form-select" id="edit_souscategorie_category" name="categorie_id" required>
                                                <?php foreach ($categories as $categorie): ?>
                                                    <option value="<?= $categorie['id'] ?>"><?= htmlspecialchars($categorie['nom']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_souscategorie_nom" class="form-label">Nom</label>
                                            <input type="text" class="form-control" id="edit_souscategorie_nom" name="nom" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_souscategorie_description" class="form-label">Description</label>
                                            <textarea class="form-control" id="edit_souscategorie_description" name="description" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Suppression Sous-catégorie -->
                    <div class="modal fade" id="deleteSousCategorieModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmer la suppression</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    Êtes-vous sûr de vouloir supprimer la sous-catégorie <strong id="delete_souscategorie_name"></strong> ?
                                    <br>Cette action est irréversible.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <form action="index.php?controller=admin&method=deleteSubCategory" method="POST" class="d-inline">
                                        <input type="hidden" id="delete_souscategorie_id" name="id">
                                        <button type="submit" class="btn btn-danger">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Points de vigilance -->
        <div class="tab-pane fade" id="nav-points" role="tabpanel" aria-labelledby="nav-points-tab">
            <div class="card">
                <div class="card-body">
                    <form action="index.php?controller=admin&method=createVigilancePoint" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="vigilance_category" class="form-label">Catégorie</label>
                            <select class="form-select" id="vigilance_category" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?= $categorie['id'] ?>"><?= htmlspecialchars($categorie['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="vigilance_subcategory" class="form-label">Sous-catégorie</label>
                            <select class="form-select" id="vigilance_subcategory" name="sous_categorie_id" required disabled>
                                <option value="">Sélectionnez d'abord une catégorie</option>
                            </select>
                        </div>
                        <div id="vigilance-form-fields" style="display: none;">
                            <div class="mb-3">
                                <label for="vigilance_nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="vigilance_nom" name="nom" required>
                            </div>
                            <div class="mb-3">
                                <label for="vigilance_description" class="form-label">Description</label>
                                <textarea class="form-control" id="vigilance_description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="vigilance_image" class="form-label">Image</label>
                                <input type="file" class="form-control" id="vigilance_image" name="image" accept="image/*">
                                <small class="form-text text-muted">Formats acceptés : JPG, PNG, GIF. Taille max : 2Mo</small>
                            </div>
                            <button type="submit" class="btn btn-primary">Ajouter un point de vigilance</button>
                        </div>
                    </form>

                    <hr>

                    <div id="points-list" style="display: none;">
                        <h3 class="h6 mb-3">Points de vigilance existants</h3>
                        <ul class="list-group" id="points-container">
                            <?php foreach ($pointsVigilance as $point): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start" data-subcategory="<?= $point['sous_categorie_id'] ?>">
                                    <div class="ms-2 me-auto">
                                        <?php if (!empty($point['image'])): ?>
                                            <div class="point-image mb-2">
                                                <img src="public/uploads/points_vigilance/<?= htmlspecialchars($point['image']) ?>" 
                                                     alt="<?= htmlspecialchars($point['nom']) ?>" 
                                                     class="img-thumbnail" style="max-width: 100px;">
                                            </div>
                                        <?php endif; ?>
                                        <strong><?= htmlspecialchars($point['nom']) ?></strong>
                                        <br><small class="text-muted">
                                            <?= htmlspecialchars($point['categorie_nom']) ?> > <?= htmlspecialchars($point['sous_categorie_nom']) ?>
                                        </small>
                                        <?php if (!empty($point['description'])): ?>
                                            <br><small><?= htmlspecialchars($point['description']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editPoint(<?= $point['id'] ?>, <?= $point['sous_categorie_id'] ?>, '<?= addslashes($point['nom']) ?>', '<?= addslashes($point['description']) ?>', '<?= addslashes($point['image'] ?? '') ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDeletePoint(<?= $point['id'] ?>, '<?= addslashes($point['nom']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Modal Modification Point de vigilance -->
                    <div class="modal fade" id="editPointModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Modifier le point de vigilance</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="index.php?controller=admin&method=updateVigilancePoint" method="POST" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <input type="hidden" id="edit_point_id" name="id">
                                        <div class="mb-3">
                                            <label for="edit_point_category" class="form-label">Catégorie</label>
                                            <select class="form-select" id="edit_point_category" required>
                                                <?php foreach ($categories as $categorie): ?>
                                                    <option value="<?= $categorie['id'] ?>"><?= htmlspecialchars($categorie['nom']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_point_subcategory" class="form-label">Sous-catégorie</label>
                                            <select class="form-select" id="edit_point_subcategory" name="sous_categorie_id" required>
                                                <option value="">Sélectionnez d'abord une catégorie</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_point_nom" class="form-label">Nom</label>
                                            <input type="text" class="form-control" id="edit_point_nom" name="nom" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_point_description" class="form-label">Description</label>
                                            <textarea class="form-control" id="edit_point_description" name="description" rows="3"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_point_image" class="form-label">Image</label>
                                            <div id="edit_point_image_preview" class="mb-2"></div>
                                            <input type="file" class="form-control" id="edit_point_image" name="image" accept="image/*">
                                            <small class="form-text text-muted">Formats acceptés : JPG, PNG, GIF. Taille max : 2Mo</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Suppression Point de vigilance -->
                    <div class="modal fade" id="deletePointModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmer la suppression</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    Êtes-vous sûr de vouloir supprimer le point de vigilance <strong id="delete_point_name"></strong> ?
                                    <br>Cette action est irréversible.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <form action="index.php?controller=admin&method=deleteVigilancePoint" method="POST" class="d-inline">
                                        <input type="hidden" id="delete_point_id" name="id">
                                        <button type="submit" class="btn btn-danger">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inclusion du fichier JavaScript central -->
<script src="public/assets/js/index.js"></script>
<script src="public/assets/js/admin.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 