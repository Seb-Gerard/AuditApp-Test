<?php
require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/SousCategorie.php';
require_once __DIR__ . '/../models/PointVigilance.php';

class AuditController {
    private $categorieModel;
    private $sousCategorieModel;
    private $pointVigilanceModel;

    public function __construct() {
        $this->categorieModel = new Categorie();
        $this->sousCategorieModel = new SousCategorie();
        $this->pointVigilanceModel = new PointVigilance();
    }

    public function index() {
        $categories = $this->categorieModel->getAll();
        include_once __DIR__ . '/../views/audits/index.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Traitement du formulaire d'audit
            // À implémenter selon vos besoins
        }

        $categories = $this->categorieModel->getAll();
        include_once __DIR__ . '/../views/audits/create.php';
    }

    public function getSousCategories() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['categorie_id'])) {
            echo json_encode(['error' => 'categorie_id manquant']);
            exit;
        }

        try {
            // Support for single or multiple category IDs
            $categorieIds = is_array($_GET['categorie_id']) ? $_GET['categorie_id'] : [$_GET['categorie_id']];
            $sousCategories = [];
            
            foreach ($categorieIds as $categorieId) {
                $sousCategs = $this->categorieModel->getSousCategories($categorieId);
                $sousCategories = array_merge($sousCategories, $sousCategs);
            }
            
            echo json_encode($sousCategories);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function getPointsVigilance() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['sous_categorie_id'])) {
            echo json_encode(['error' => 'sous_categorie_id manquant']);
            exit;
        }

        try {
            // Support for single or multiple sous category IDs
            $sousCategorieIds = is_array($_GET['sous_categorie_id']) ? $_GET['sous_categorie_id'] : [$_GET['sous_categorie_id']];
            $pointsVigilance = [];
            
            foreach ($sousCategorieIds as $sousCategorieId) {
                $points = $this->sousCategorieModel->getPointsVigilance($sousCategorieId);
                $pointsVigilance = array_merge($pointsVigilance, $points);
            }
            
            echo json_encode($pointsVigilance);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
} 