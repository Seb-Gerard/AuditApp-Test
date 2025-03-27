<?php
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Audit.php';

class HomeController {
    private $articleModel;
    private $auditModel;

    public function __construct() {
        $this->articleModel = new Article();
        $this->auditModel = new Audit();
    }

    public function index() {
        // Récupérer les statistiques pour la page d'accueil
        $articleCount = $this->articleModel->getCount();
        $auditCount = $this->auditModel->getCount();
        
        // Passer les données à la vue
        include_once __DIR__ . '/../views/home.php';
    }
} 