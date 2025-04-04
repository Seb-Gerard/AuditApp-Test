<?php
$pageTitle = "Liste des Audits";
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Liste des Audits</h2>
        <div class="btn-group">
            <a href="index.php?action=audits&method=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvel Audit
            </a>
            <a href="index.php?controller=admin&method=index" class="btn btn-secondary">
                <i class="fas fa-cog"></i> Admin
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (empty($audits)): ?>
        <div class="alert alert-info">
            Aucun audit n'a encore été créé.
        </div>
    <?php else: ?>
        <?php
        // Séparer les audits en deux tableaux
        $auditsEnCours = [];
        $auditsTermines = [];
        
        foreach ($audits as $audit) {
            if (!isset($audit['statut']) || $audit['statut'] === 'en_cours') {
                $auditsEnCours[] = $audit;
            } else {
                $auditsTermines[] = $audit;
            }
        }
        ?>
        
        <!-- Tableau des audits en cours -->
        <h3 class="mt-4 mb-3">Audits en cours</h3>
        <?php if (empty($auditsEnCours)): ?>
            <div class="alert alert-info">
                Aucun audit en cours.
            </div>
        <?php else: ?>
            <div class="table-responsive mb-5">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>N° du site</th>
                            <th>Nom de l'entreprise</th>
                            <th>Date de création</th>
                            <th>Date de modification</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditsEnCours as $audit): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($audit['numero_site']); ?></td>
                                <td>
                                    <a href="index.php?action=audits&method=view&id=<?php echo $audit['id']; ?>" 
                                       class="btn btn-sm btn-info text-light" title="Voir les détails">
                                        <i class="fas fa-eye"></i>
                                        <?php echo htmlspecialchars($audit['nom_entreprise']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($audit['date_creation'])); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($audit['updated_at'])); ?></td>
                                <td>
                                    <span class="badge bg-warning p-2 text-light">
                                        En cours
                                    </span>
                                </td>
                                <td>
                                    <a href="index.php?action=audits&method=edit&id=<?php echo $audit['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" onclick="confirmDelete(<?php echo $audit['id']; ?>); return false;" 
                                       class="btn btn-sm btn-danger" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="index.php?action=audits&method=updateStatus&id=<?php echo $audit['id']; ?>&statut=termine" 
                                       class="btn btn-sm btn-success" title="Marquer comme terminé">
                                        <i class="fas fa-check"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Tableau des audits terminés -->
        <h3 class="mt-4 mb-3">Audits terminés</h3>
        <?php if (empty($auditsTermines)): ?>
            <div class="alert alert-info">
                Aucun audit terminé.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>N° du site</th>
                            <th>Nom de l'entreprise</th>
                            <th>Date de création</th>
                            <th>Date de modification</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditsTermines as $audit): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($audit['numero_site']); ?></td>
                                <td>
                                    <a href="index.php?action=audits&method=view&id=<?php echo $audit['id']; ?>" 
                                       class="btn btn-sm btn-info text-light" title="Voir les détails">
                                        <i class="fas fa-eye"></i>
                                        <?php echo htmlspecialchars($audit['nom_entreprise']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($audit['date_creation'])); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($audit['updated_at'])); ?></td>
                                <td>
                                    <span class="badge bg-success p-2">
                                        Terminé
                                    </span>
                                </td>
                                <td>
                                    <a href="index.php?action=audits&method=edit&id=<?php echo $audit['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" onclick="confirmDelete(<?php echo $audit['id']; ?>); return false;" 
                                       class="btn btn-sm btn-danger" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="index.php?action=audits&method=updateStatus&id=<?php echo $audit['id']; ?>&statut=en_cours" 
                                       class="btn btn-sm btn-warning" title="Marquer comme en cours">
                                        <i class="fas fa-sync"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="public/assets/js/audit_manager.js"></script>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 