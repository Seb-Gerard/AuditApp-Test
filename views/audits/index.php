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
            <a href="index.php?controller=admin&action=index" class="btn btn-secondary">
                <i class="fas fa-cog"></i> Admin
            </a>
        </div>
    </div>

    <?php if (empty($audits)): ?>
        <div class="alert alert-info">
            Aucun audit n'a encore été créé.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Lieu</th>
                        <th>Auditeur</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audits as $audit): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($audit['date_audit'])); ?></td>
                            <td><?php echo htmlspecialchars($audit['lieu']); ?></td>
                            <td><?php echo htmlspecialchars($audit['auditeur']); ?></td>
                            <td><?php echo htmlspecialchars($audit['type_audit']); ?></td>
                            <td>
                                <a href="index.php?action=audits&method=view&id=<?php echo $audit['id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?action=audits&method=edit&id=<?php echo $audit['id']; ?>" 
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 