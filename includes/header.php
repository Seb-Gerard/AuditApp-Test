<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
            <a class="navbar-brand" href="index.php">
                <img src="public/assets/img/Logo_CNPP_250.jpg" alt="Logo" width="150">
            </a>
            <div class="title">
                <h1>Application Audit</h1>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Accueil</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</body>
</html>