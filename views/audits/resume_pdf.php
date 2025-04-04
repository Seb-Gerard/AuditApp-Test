<?php
// Template pour l'export PDF du résumé d'audit
// Basé sur resume.php mais optimisé pour TCPDF

// Fonction pour obtenir la classe de couleur en fonction du pourcentage
function getColorClass($percentage, $inverse = false) {
    if ($inverse) {
        if ($percentage >= 80) return 'danger';
        if ($percentage >= 50) return 'warning';
        return 'success';
    } else {
        if ($percentage >= 80) return 'success';
        if ($percentage >= 50) return 'warning';
        return 'danger';
    }
}

// CSS Personnalisé pour le PDF
$css = '
<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12pt;
        line-height: 1.5;
    }
    .container {
        width: 100%;
    }
    .card {
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 20px;
        background-color: #fff;
    }
    .card-header {
        padding: 10px 15px;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
    }
    .card-body {
        padding: 15px;
    }
    .bg-primary {
        background-color: #007bff;
        color: #fff;
    }
    .bg-info {
        background-color: #17a2b8;
        color: #fff;
    }
    .text-center {
        text-align: center;
    }
    .text-end {
        text-align: right;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }
    .table th, .table td {
        padding: 8px;
        border: 1px solid #ddd;
        text-align: left;
    }
    .table th {
        background-color: #17a2b8;
        color: #fff;
    }
    .progress {
        background-color: #e9ecef;
        border-radius: 4px;
        height: 20px;
        margin-bottom: 5px;
        position: relative;
        overflow: hidden;
    }
    .progress-bar {
        background-color: #007bff;
        color: #fff;
        text-align: center;
        line-height: 20px;
        font-weight: bold;
        height: 100%;
    }
    .priority-faible {
        background-color: #28a745;
        color: white;
        font-weight: bold;
        border-radius: 4px;
        padding: 5px;
        text-align: center;
    }
    .priority-moyen {
        background-color: #fd7e14;
        color: white;
        font-weight: bold;
        border-radius: 4px;
        padding: 5px;
        text-align: center;
    }
    .priority-grande {
        background-color: #dc3545;
        color: white;
        font-weight: bold;
        border-radius: 4px;
        padding: 5px;
        text-align: center;
    }
    .alert-info {
        background-color: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
        padding: 12px;
        border-radius: 4px;
    }
    .progress-legend {
        font-size: 9pt;
        margin-top: 2px;
        display: flex;
        justify-content: space-between;
    }
    .conformite-satisfait {
        background-color: #28a745;
    }
    .conformite-partiel-text {
        text-align: center;
    }
    .conformite-satisfait-text {
        text-align: right;
    }
</style>
';

// En-tête du document PDF
echo $css;
?>

<div class="container">
    <h1 style="text-align: center; color: #007bff;">Résumé de l'Audit</h1>
    
    <!-- Informations générales -->
    <div class="card">
        <div class="card-header bg-primary">
            <h3>Informations générales</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; justify-content: space-between;">
                <div>
                    <p><strong>SITE :</strong> <?php echo htmlspecialchars($audit['numero_site'] . ' - ' . $audit['nom_entreprise']); ?></p>
                </div>
                <div>
                    <p><strong>DATE :</strong> <?php echo date('d/m/Y', strtotime($audit['updated_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistiques par catégorie -->
    <div class="card">
        <div class="card-header bg-primary text-center">
            <h3>RÉCAPITULATIF DE L'ÉVALUATION DES MESURES DE SÛRETÉ EXISTANTES</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead class="bg-info text-center">
                    <tr>
                        <th style="width: 40%;">Thème</th>
                        <th style="width: 20%;">% Audité</th>
                        <th style="width: 40%;">% Conformité</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($categoriesStats as $categorie): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($categorie['nom']); ?></td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo isset($categorie['pct_audite']) ? $categorie['pct_audite'] : 0; ?>%;">
                                    <?php if(isset($categorie['pct_audite']) && $categorie['pct_audite'] > 10): ?>
                                        <?php echo $categorie['pct_audite']; ?>%
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if(!isset($categorie['pct_audite']) || $categorie['pct_audite'] <= 10): ?>
                                <span><?php echo isset($categorie['pct_audite']) ? $categorie['pct_audite'] : 0; ?>%</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            // Calculer le score de satisfaction
                            $satisfait = isset($categorie['pct_satisfait']) ? $categorie['pct_satisfait'] : 0;
                            $partiellement = isset($categorie['pct_partiellement']) ? $categorie['pct_partiellement'] : 0;
                            $nonSatisfait = isset($categorie['pct_non_satisfait']) ? $categorie['pct_non_satisfait'] : 0;
                            
                            $totalPoints = $satisfait + $partiellement + $nonSatisfait;
                        
                            if ($totalPoints > 0) {
                                $scoreConformite = ($satisfait / $totalPoints) * 100;
                            } else {
                                $scoreConformite = 0;
                            }
                            
                            $scoreConformite = round($scoreConformite);
                            ?>
                            
                            <div class="progress">
                                <div class="progress-bar conformite-satisfait" style="width: <?php echo $scoreConformite; ?>%;">
                                    <?php if($scoreConformite > 10): ?>
                                        <?php echo $scoreConformite; ?>%
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if(!isset($scoreConformite) || $scoreConformite <= 10): ?>
                                <span><?php echo $scoreConformite; ?>%</span>
                            <?php endif; ?>
                            <div class="progress-legend">
                                <small>Non satisfait (0%)</small>
                                <small class="conformite-partiel-text">Partiellement</small>
                                <small class="conformite-satisfait-text">Satisfait (100%)</small>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Plans d'action -->
    <div class="card">
        <div class="card-header bg-primary text-center">
            <h3>PLAN D'ACTIONS</h3>
        </div>
        <div class="card-body">
            <?php if(empty($plansAction)): ?>
                <div class="alert-info">
                    Aucun plan d'action n'a été défini pour cet audit.
                </div>
            <?php else: ?>
                <table class="table">
                    <thead class="bg-info">
                        <tr>
                            <th style="width: 10%;">N°</th>
                            <th style="width: 70%;">Libellé</th>
                            <th style="width: 20%;">Priorité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($plansAction as $index => $action): ?>
                        <tr>
                            <td class="text-center"><?php echo !empty($action['numero']) ? $action['numero'] : ($index + 1); ?></td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($action['point_vigilance_nom']); ?></strong>
                                </div>
                                <div>
                                    <?php echo nl2br(htmlspecialchars($action['description'])); ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if(!empty($action['priorite'])): ?>
                                    <?php if($action['priorite'] === 'faible'): ?>
                                        <div class="priority-faible">Faible</div>
                                    <?php elseif($action['priorite'] === 'moyen'): ?>
                                        <div class="priority-moyen">Moyen</div>
                                    <?php elseif($action['priorite'] === 'grande'): ?>
                                        <div class="priority-grande">Grande</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span>Non définie</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pied de page -->
    <div style="text-align: center; margin-top: 20px; font-size: 10pt; color: #6c757d;">
        <p>Document généré le <?php echo date('d/m/Y H:i'); ?></p>
    </div>
</div> 