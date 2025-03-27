<?php
require_once 'models/Categorie.php';
require_once 'models/SousCategorie.php';
require_once 'models/PointVigilance.php';

class AdminController {
    private $categorieModel;
    private $sousCategorieModel;
    private $pointVigilanceModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->categorieModel = new Categorie();
        $this->sousCategorieModel = new SousCategorie();
        $this->pointVigilanceModel = new PointVigilance();
    }

    public function index() {
        $categories = $this->categorieModel->getAll();
        $sousCategories = $this->sousCategorieModel->getAll();
        $pointsVigilance = $this->pointVigilanceModel->getAll();
        
        require_once 'views/admin/index.php';
    }

    public function getSousCategories() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['categorie_id'])) {
            echo json_encode(['error' => 'categorie_id manquant']);
            exit;
        }

        try {
            $sousCategories = $this->categorieModel->getSousCategories($_GET['categorie_id']);
            echo json_encode($sousCategories);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function createCategory() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = $_POST['nom'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if (!empty($nom)) {
                $this->categorieModel->create(['nom' => $nom, 'description' => $description]);
                header('Location: index.php?controller=admin&tab=categories');
                exit;
            }
        }
    }

    public function createSubCategory() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categorie_id = $_POST['categorie_id'] ?? '';
            $nom = $_POST['nom'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if (!empty($categorie_id) && !empty($nom)) {
                $this->sousCategorieModel->create([
                    'categorie_id' => $categorie_id,
                    'nom' => $nom,
                    'description' => $description
                ]);
                header('Location: index.php?controller=admin&tab=subcategories');
                exit;
            }
        }
    }

    public function createVigilancePoint() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sous_categorie_id = $_POST['sous_categorie_id'] ?? '';
            $nom = $_POST['nom'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if (!empty($sous_categorie_id) && !empty($nom)) {
                $this->pointVigilanceModel->create([
                    'sous_categorie_id' => $sous_categorie_id,
                    'nom' => $nom,
                    'description' => $description
                ]);
                header('Location: index.php?controller=admin&tab=points');
                exit;
            }
        }
    }

    public function updateCategory() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $nom = $_POST['nom'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if (!empty($id) && !empty($nom)) {
                $this->categorieModel->update([
                    'id' => $id,
                    'nom' => $nom,
                    'description' => $description
                ]);
                header('Location: index.php?controller=admin&tab=categories');
                exit;
            }
        }
        header('Location: index.php?controller=admin&tab=categories');
        exit;
    }

    public function deleteCategory() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            
            if (!empty($id)) {
                $this->categorieModel->delete($id);
            }
        }
        header('Location: index.php?controller=admin&tab=categories');
        exit;
    }

    public function updateSubCategory() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $categorie_id = $_POST['categorie_id'] ?? '';
            $nom = $_POST['nom'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if (!empty($id) && !empty($categorie_id) && !empty($nom)) {
                $this->sousCategorieModel->update([
                    'id' => $id,
                    'categorie_id' => $categorie_id,
                    'nom' => $nom,
                    'description' => $description
                ]);
            }
        }
        header('Location: index.php?controller=admin&tab=subcategories');
        exit;
    }

    public function deleteSubCategory() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            
            if (!empty($id)) {
                try {
                    $success = $this->sousCategorieModel->delete($id);
                    if ($success) {
                        $_SESSION['success'] = "La sous-catégorie a été supprimée avec succès.";
                    } else {
                        $_SESSION['error'] = "La suppression de la sous-catégorie a échoué.";
                        error_log("Échec de la suppression de la sous-catégorie ID: " . $id);
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = $e->getMessage();
                    error_log("Erreur lors de la suppression de la sous-catégorie: " . $e->getMessage());
                }
            } else {
                $_SESSION['error'] = "ID de sous-catégorie manquant.";
            }
        }
        header('Location: index.php?controller=admin&tab=subcategories');
        exit;
    }

    public function getSousCategorieDetails() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'id manquant']);
            exit;
        }

        try {
            $sousCategorie = $this->sousCategorieModel->getById($_GET['id']);
            echo json_encode($sousCategorie);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function updateVigilancePoint() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $sous_categorie_id = $_POST['sous_categorie_id'] ?? '';
            $nom = $_POST['nom'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if (!empty($id) && !empty($sous_categorie_id) && !empty($nom)) {
                $this->pointVigilanceModel->update([
                    'id' => $id,
                    'sous_categorie_id' => $sous_categorie_id,
                    'nom' => $nom,
                    'description' => $description
                ]);
            }
        }
        header('Location: index.php?controller=admin&tab=points');
        exit;
    }

    public function deleteVigilancePoint() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            
            if (!empty($id)) {
                $this->pointVigilanceModel->delete($id);
            }
        }
        header('Location: index.php?controller=admin&tab=points');
        exit;
    }

    public function getPointsVigilance() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['sous_categorie_id'])) {
            echo json_encode(['error' => 'sous_categorie_id manquant']);
            exit;
        }

        try {
            $points = $this->pointVigilanceModel->getBySousCategorie($_GET['sous_categorie_id']);
            echo json_encode($points);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
} 