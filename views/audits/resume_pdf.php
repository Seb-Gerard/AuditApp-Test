<?php
// Template pour l'export PDF du résumé d'audit
// Basé sur resume.php mais optimisé pour TCPDF

// Fonction pour obtenir la classe de couleur en fonction du pourcentage
function getColorClass($percentage, $inverse = false)
{
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
        margin: 0;
        padding: 20px;
    }
    .container {
        width: 100%;
    }
    h2 {
        font-size: 24px;
        margin: 0;
    }
    .header-section {
        width: 100%;
        margin-bottom: 30px;
    }
    .header-table {
        width: 100%;
        border-collapse: collapse;
    }
    .header-table td {
        vertical-align: middle;
        border: none;
    }
    .header-title {
        text-align: left;
        width: 70%;
    }
    .header-logo {
        text-align: right;
        width: 30%;
    }
    .card {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 20px;
        background-color: #fff;
        overflow: hidden;
    }
    .card-header {
        background-color: #1a237e;
        color: #fff;
        padding: 12px 15px;
        border-radius: 15px;
        font-weight: bold;
        font-size: 16px;
    }
    .card-body {
        padding: 15px;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
    }
    .info-label {
        font-weight: bold;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
        background-color: #fff;
    }
    .table th {
        background-color: #17a2b8;
        color: #fff;
        padding: 12px;
        text-align: left;
        font-weight: normal;
        border: 1px solid #dee2e6;
    }
    .table td {
        padding: 12px;
        border: 1px solid #dee2e6;
        vertical-align: middle;
    }
    .progress-container {
        width: 100%;
        background-color: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        position: relative;
    }
    .progress-bar {
        height: 24px;
        line-height: 24px;
        color: white;
        text-align: center;
        font-weight: bold;
        transition: width .6s ease;
    }
    .progress-bar-audit {
        background-color: #17a2b8;
        width: 0;
    }
    .progress-bar-conformite {
        background-color: #dc3545;
        width: 0;
    }
    .progress-legend {
        display: flex;
        justify-content: space-between;
        font-size: 6px;
        margin-top: 4px;
        color: #666;
    }
    .priority-badge {
        padding: 6px 12px;
        border-radius: 4px;
        color: white;
        font-weight: bold;
        text-align: center;
        width: fit-content;
        margin: 0 auto;
    }
    .priority-faible {
        background-color: #28a745;
    }
    .priority-non-definie {
        color: #6c757d;
        font-style: italic;
    }
    .section-title {
        background-color: #1a237e;
        color: white;
        padding: 12px;
        text-align: center;
        font-weight: bold;
        margin-bottom: 20px;
    }
    .text-center {
        text-align: center;
    }
    .logo-header {
        width: 100px;
        height: auto;
    }
    .info-table {
        width: 100%;
        border-collapse: collapse;
    }
    .info-table td {
        vertical-align: middle;
        border: none;
        padding: 5px 0;
    }
    .info-left {
        text-align: left;
        width: 70%;
    }
    .info-right {
        text-align: right;
        width: 30%;
    }
</style>
';

echo $css;
?>

<div class="container">
    <div class="header-section">
        <table class="header-table">
            <tr>
                <td class="header-title">
                    <h2>Résumé de l'Audit</h2>
                </td>
                <td class="header-logo">
                    <?php
                    // Vérifier si l'image existe avant de l'afficher
                    $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/Audit/public/assets/img/logo_CNPP_512.png';
                    if (file_exists($logoPath)) {
                        echo '<img src="' . $logoPath . '" alt="Logo CNPP" class="logo-header">';
                    } else {
                        echo '<div class="logo-header">Logo CNPP</div>';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Informations générales -->
    <div class="card">
        <div class="card-header">
            Informations générales
        </div>
        <div class="card-body">
            <table class="info-table">
                <tr>
                    <td class="info-left">
                        <?php if (!empty($audit['logo'])):
                            // Vérifier si l'image existe avant de l'afficher
                            $companyLogoPath = $_SERVER['DOCUMENT_ROOT'] . '/Audit/public/uploads/logos/' . htmlspecialchars($audit['logo']);
                            if (file_exists($companyLogoPath)) {
                                // Utiliser une image JPG si possible, sinon utiliser l'image existante
                                $imageInfo = getimagesize($companyLogoPath);
                                if ($imageInfo && $imageInfo[2] == IMAGETYPE_PNG) {
                                    // Si c'est un PNG, créer une copie en JPG
                                    $jpgPath = str_replace('.png', '.jpg', $companyLogoPath);
                                    if (!file_exists($jpgPath)) {
                                        $image = imagecreatefrompng($companyLogoPath);
                                        if ($image) {
                                            // Créer un fond blanc
                                            $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
                                            imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                                            // Fusionner l'image avec le fond blanc
                                            imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                                            // Sauvegarder en JPG
                                            imagejpeg($bg, $jpgPath, 90);
                                            imagedestroy($image);
                                            imagedestroy($bg);
                                        }
                                    }
                                    if (file_exists($jpgPath)) {
                                        echo '<img src="' . $jpgPath . '" alt="Logo de l\'entreprise" class="company-logo" width="100">';
                                    } else {
                                        echo '<div class="company-logo">Logo</div>';
                                    }
                                } else {
                                    echo '<img src="' . $companyLogoPath . '" alt="Logo de l\'entreprise" class="company-logo" width="100">';
                                }
                            } else {
                                echo '<div class="company-logo">Logo</div>';
                            }
                        endif; ?>
                        <div>
                            <span class="info-label">SITE : </span>
                            <?php echo htmlspecialchars($audit['numero_site']); ?>
                        </div>
                        <div>
                            <span class="info-label">ENTREPRISE : </span>
                            <?php echo htmlspecialchars($audit['nom_entreprise']); ?>
                        </div>
                    </td>
                    <td class="info-right">
                        <span class="info-label">DATE : </span>
                        <?php echo date('d/m/Y', strtotime($audit['updated_at'])); ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Statistiques par catégorie -->
    <div class="section-title">
        RÉCAPITULATIF DE L'ÉVALUATION DES MESURES DE SÛRETÉ EXISTANTES
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Thème</th>
                <th>% Audité</th>
                <th>% Conformité</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categoriesStats as $categorie): ?>
                <tr>
                    <td><?php echo htmlspecialchars($categorie['nom']); ?></td>
                    <td>
                        <?php
                        // Calcul du pourcentage audité
                        $pctAudite = isset($categorie['pct_audite']) ? intval($categorie['pct_audite']) : 0;
                        ?>
                        <div class="progress-container" style="background-color: #e9ecef;">
                            <?php if ($pctAudite > 0): ?>
                                <div class="progress-bar progress-bar-audit"
                                    style="width: <?php echo $pctAudite; ?>%">
                                    <?php echo $pctAudite; ?>%
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; line-height: 24px;">0%</div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php
                        // Calcul du score de conformité
                        $satisfait = isset($categorie['pct_satisfait']) ? intval($categorie['pct_satisfait']) : 0;
                        $partiellement = isset($categorie['pct_partiellement']) ? intval($categorie['pct_partiellement']) : 0;
                        $nonSatisfait = isset($categorie['pct_non_satisfait']) ? intval($categorie['pct_non_satisfait']) : 0;

                        $totalPoints = $satisfait + $partiellement + $nonSatisfait;
                        $scoreConformite = ($totalPoints > 0) ? round(($satisfait / $totalPoints) * 100) : 0;
                        ?>

                        <div class="progress-container" style="background-color: #e9ecef;">
                            <?php if ($scoreConformite > 0): ?>
                                <div class="progress-bar progress-bar-conformite"
                                    style="width: <?php echo $scoreConformite; ?>%">
                                    <?php echo $scoreConformite; ?>%
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; line-height: 24px;">0%</div>
                            <?php endif; ?>
                        </div>
                        <div class="progress-legend">
                            <span>Non satisfait (0%)</span>
                            <span>Satisfait (100%)</span>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Plans d'action -->
    <div class="section-title">
        PLAN D'ACTIONS
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>N°</th>
                <th>Libellé</th>
                <th>Priorité</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($plansAction as $action): ?>
                <tr>
                    <td class="text-center"><?php echo !empty($action['numero']) ? $action['numero'] : '1'; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($action['point_vigilance_nom']); ?></strong><br>
                        <?php echo nl2br(htmlspecialchars($action['description'])); ?>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($action['priorite'])): ?>
                            <div class="priority-badge priority-<?php echo $action['priorite']; ?>">
                                <?php echo ucfirst($action['priorite']); ?>
                            </div>
                        <?php else: ?>
                            <div class="priority-non-definie">Non définie</div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>