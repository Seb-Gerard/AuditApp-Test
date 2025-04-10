<?php
// Inclure notre autoloader personnalisé pour gérer les dépendances non installées
require_once __DIR__ . '/includes/autoload.php';

require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/ArticleController.php';
require_once __DIR__ . '/controllers/AuditController.php';
require_once __DIR__ . '/controllers/AdminController.php';

// Détecter si c'est une requête AJAX
$isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Vérifier si on demande du JSON
$isJsonRequest = isset($_GET['format']) && $_GET['format'] === 'json';

// Pour les requêtes AJAX ou JSON, s'assurer que le traitement est approprié
if ($isAjaxRequest || $isJsonRequest) {
    header('Content-Type: application/json');
    // Ne pas inclure header/footer pour les requêtes AJAX
} else {
    // Inclure le header pour les requêtes non-AJAX/JSON
    include_once __DIR__ . '/includes/header.php';
}

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
            if ($isAjaxRequest || $isJsonRequest) {
                echo json_encode(['error' => 'Action non reconnue']);
                exit;
            }
            header('Location: index.php?action=home');
            exit;
    }
}

// Traiter les exceptions
try {
    // Appel de la méthode correspondante
    if (method_exists($controllerInstance, $method)) {
        $controllerInstance->$method();
    } else {
        // Si la méthode n'existe pas
        if ($isAjaxRequest || $isJsonRequest) {
            echo json_encode(['error' => 'Méthode non reconnue']);
            exit;
        }
        // Sinon afficher la page d'accueil
        $controllerInstance->index();
    }
} catch (Exception $e) {
    // En cas d'exception
    if ($isAjaxRequest || $isJsonRequest) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    // Sinon afficher un message d'erreur
    echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
}
  
// Inclure le footer pour les requêtes non-AJAX/JSON
if (!$isAjaxRequest && !$isJsonRequest) {
    include_once __DIR__ . '/includes/footer.php';
}
  