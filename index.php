<?php
require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/ArticleController.php';
require_once __DIR__ . '/controllers/AuditController.php';
require_once __DIR__ . '/controllers/AdminController.php';

// Récupération de l'action et du contrôleur depuis l'URL
$controller = isset($_GET['controller']) ? $_GET['controller'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : 'home';
$method = isset($_GET['method']) ? $_GET['method'] : 'index';

// Si un contrôleur est spécifié, il a la priorité
if ($controller === 'admin') {
    $controllerInstance = new AdminController();
} else {
    // Sinon, on utilise le routage basé sur l'action
    switch ($action) {
        case 'home':
            $controllerInstance = new HomeController();
            break;
        case 'articles':
            $controllerInstance = new ArticleController();
            break;
        case 'audits':
            $controllerInstance = new AuditController();
            break;
        default:
            // Redirection vers la page d'accueil si l'action n'est pas reconnue
            header('Location: index.php?action=home');
            exit;
    }
}

// Appel de la méthode correspondante
if (method_exists($controllerInstance, $method)) {
    $controllerInstance->$method();
} else {
    // Si la méthode n'existe pas, on affiche la page d'index par défaut
    $controllerInstance->index();
}
  