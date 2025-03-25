<?php
session_start();
require_once __DIR__ . '/controllers/ArticleController.php';

$controller = new ArticleController();

// Déterminer l'action à exécuter en fonction du paramètre GET
$action = $_GET['action'] ?? 'index';

// Exécuter l'action demandée
switch ($action) {
    case 'create':
        $controller->create();
        break;
    case 'index':
    default:
        $controller->index();
        break;
}
  