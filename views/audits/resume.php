<?php
$pageTitle = "Résumé de l'Audit";
include_once __DIR__ . '/../../includes/header.php';

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
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Résumé de l'Audit</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="index.php?action=audits&method=view&id=<?php echo $audit['id']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour aux détails
            </a>
        </div>
    </div>
    
    <!-- Informations générales -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Informations générales</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>SITE :</strong> <?php echo htmlspecialchars($audit['numero_site'] . ' - ' . $audit['nom_entreprise']); ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <p><strong>DATE :</strong> <?php echo date('d/m/Y', strtotime($audit['updated_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistiques par catégorie -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white text-center">
            <h5 class="mb-0">RÉCAPITULATIF DE L'ÉVALUATION DES MESURES DE SÛRETÉ EXISTANTES</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="bg-info text-white text-center">
                        <tr>
                            <th style="vertical-align: middle;">Thème</th>
                            <th style="vertical-align: middle;">% Audité</th>
                            <th style="vertical-align: middle;">% Conformité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categoriesStats as $categorie): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($categorie['nom']); ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1" style="height: 24px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?php echo isset($categorie['pct_audite']) ? $categorie['pct_audite'] : 0; ?>%;" 
                                             aria-valuenow="<?php echo isset($categorie['pct_audite']) ? $categorie['pct_audite'] : 0; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            <?php if(isset($categorie['pct_audite']) && $categorie['pct_audite'] > 10): ?>
                                                <?php echo $categorie['pct_audite']; ?>%
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if(!isset($categorie['pct_audite']) || $categorie['pct_audite'] <= 10): ?>
                                        <span class="ms-2"><?php echo isset($categorie['pct_audite']) ? $categorie['pct_audite'] : 0; ?>%</span>
                                    <?php endif; ?>
                                </div>
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
                                
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1" style="height: 30px;">
                                        <div class="progress-bar conformite-satisfait" role="progressbar" 
                                             style="width: <?php echo $scoreConformite; ?>%;" 
                                             aria-valuenow="<?php echo $scoreConformite; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            <?php if($scoreConformite > 10): ?>
                                                <?php echo $scoreConformite; ?>%
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if(!isset($scoreConformite) || $scoreConformite <= 10): ?>
                                        <span class="ms-2"><?php echo $scoreConformite; ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <div class="progress-legend d-flex justify-content-between mt-1">
                                    <small class="conformite-non-satisfait-text">Non satisfait (0%)</small>
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
    </div>
    
    <!-- Plans d'action -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white text-center">
            <h5 class="mb-0">PLAN D'ACTIONS</h5>
        </div>
        <div class="card-body">
            <?php if(empty($plansAction)): ?>
                <div class="alert alert-info">
                    Aucun plan d'action n'a été défini pour cet audit.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-info text-white">
                            <tr>
                                <th>N°</th>
                                <th>Libellé</th>
                                <th>Priorité</th>
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
                                <td class="text-center" style="width: 100px; vertical-align: middle;">
                                    <?php if(!empty($action['priorite'])): ?>
                                        <?php if($action['priorite'] === 'faible'): ?>
                                            <div class="priority-faible py-2">Faible</div>
                                        <?php elseif($action['priorite'] === 'moyen'): ?>
                                            <div class="priority-moyen py-2">Moyen</div>
                                        <?php elseif($action['priorite'] === 'grande'): ?>
                                            <div class="priority-grande py-2">Grande</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Non définie</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Boutons d'export -->
    <div class="row mt-4 mb-5">
        <div class="col-12 text-end">
            <button type="button" class="btn btn-success me-2" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimer
            </button>
            <a href="index.php?action=audits&method=exportPDF&id=<?php echo $audit['id']; ?>" class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Télécharger PDF
            </a>
        </div>
    </div>
</div>

<!-- Modal d'information sur l'export PDF -->
<div class="modal fade" id="pdfInfoModal" tabindex="-1" aria-labelledby="pdfInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="pdfInfoModalLabel">Export PDF</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><i class="fas fa-info-circle text-info"></i> <strong>La fonctionnalité d'export PDF nécessite l'installation de la bibliothèque TCPDF.</strong></p>
        <p>Pour activer cette fonctionnalité, veuillez suivre ces étapes :</p>
        <ol>
          <li>Assurez-vous que <a href="https://getcomposer.org/download/" target="_blank">Composer</a> est installé sur votre serveur</li>
          <li>Ouvrez un terminal dans le répertoire racine de l'application</li>
          <li>Exécutez la commande : <code>composer require tecnickcom/tcpdf</code></li>
          <li>Une fois l'installation terminée, rafraîchissez cette page</li>
        </ol>
        <p>Cette opération n'a besoin d'être effectuée qu'une seule fois.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

<style>
    /* Styles pour les barres de progression */
    .progress {
        box-shadow: inset 0 1px 3px rgba(0,0,0,.2);
        border-radius: 5px;
        overflow: hidden;
    }
    
    .progress-bar {
        font-weight: bold;
        text-shadow: 1px 1px 1px rgba(0,0,0,.3);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: width .6s ease;
        border-right: 1px solid rgba(0,0,0,.1);
    }
    
    .progress-legend {
        font-size: 11px;
        margin-top: 2px;
    }
    
    .progress-legend small {
        font-weight: bold;
    }
    
    /* Styles pour les indicateurs de priorité */
    .priority-faible {
        background-color: #28a745;
        color: white;
        font-weight: bold;
        border-radius: 4px;
    }
    
    .priority-moyen {
        background-color: #fd7e14;
        color: white;
        font-weight: bold;
        border-radius: 4px;
    }
    
    .priority-grande {
        background-color: #dc3545;
        color: white;
        font-weight: bold;
        border-radius: 4px;
    }
    
    /* Styles pour l'impression */
    @media print {
        .btn, nav, .footer {
            display: none !important;
        }
        
        .container {
            width: 100% !important;
            max-width: 100% !important;
        }
        
        .card {
            break-inside: avoid;
        }
        
        .table {
            width: 100% !important;
        }
        
        /* Conserver les couleurs pour l'impression */
        .progress {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
            border: 1px solid #ddd;
        }
        
        .progress-bar {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        
        .bg-primary, .bg-info, .bg-success, .bg-warning, .bg-danger {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        
        .conformite-satisfait, .conformite-partiel, .conformite-non-satisfait {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        
        .priority-faible, .priority-moyen, .priority-grande {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
            border: 1px solid #333;
        }
    }
</style>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?> 