<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="icon" href="public/assets/img/Logo_CNPP_250.jpg">
    <link rel="stylesheet" href="public/assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    
    <!-- Styles supplémentaires spécifiques à la page -->
    <?php 
    if (isset($additionalStyles) && is_array($additionalStyles)) {
        foreach ($additionalStyles as $style) {
            echo '<link rel="stylesheet" href="' . $style . '">' . PHP_EOL;
        }
    }
    ?>
    
    <!-- Scripts supplémentaires spécifiques à la page -->
    <?php 
    if (isset($additionalScripts) && is_array($additionalScripts)) {
        foreach ($additionalScripts as $script) {
            echo '<script src="' . $script . '"></script>' . PHP_EOL;
        }
    }
    ?>
    
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Accueil'; ?></title>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center w-100">
                <a class="navbar-brand" href="index.php">
                    <img src="public/assets/img/Logo_CNPP_250.jpg" alt="Logo" width="150">
                </a>
                <div class="title flex-grow-1 text-center">
                    <h1>Application Audit</h1>
                </div>
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            
            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'articles' ? 'active' : ''; ?>" 
                               href="index.php?action=articles">
                                <i class="fas fa-newspaper"></i> Articles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($_GET['action']) && $_GET['action'] === 'audits' ? 'active' : ''; ?>" 
                               href="index.php?action=audits">
                                <i class="fas fa-clipboard-check"></i> Audits
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</body>
</html>