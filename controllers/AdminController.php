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
        
        // TEMPORAIRE: permettre l'accès sans vérification d'admin pour tester
        // Nous commenterons la vérification et supposerons que l'utilisateur est administrateur
        
        /* Vérification temporairement désactivée pour tests
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            // Pour les requêtes AJAX, renvoyer une erreur JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Non autorisé']);
                exit;
            }
            
            // Pour les requêtes normales, rediriger vers la page de connexion
            header('Location: index.php?controller=auth&action=login');
            exit;
        }
        */
        
        // Simule un utilisateur connecté en tant qu'admin
        $_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
        $_SESSION['is_admin'] = true;
        
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
            // Ajouter un log pour débogage
            error_log("getSousCategories appelé avec categorie_id: " . $_GET['categorie_id']);
            
            $sousCategories = $this->categorieModel->getSousCategories($_GET['categorie_id']);
            
            // Vérifier le format des données avant d'encoder en JSON
            if (!is_array($sousCategories)) {
                error_log("getSousCategories: format de données invalide: " . var_export($sousCategories, true));
                echo json_encode(['error' => 'Format de données invalide']);
                exit;
            }
            
            // Vérifier si l'array est vide
            if (empty($sousCategories)) {
                error_log("getSousCategories: aucune sous-catégorie trouvée pour categorie_id: " . $_GET['categorie_id']);
                echo json_encode([]);
                exit;
            }
            
            // Encoder et renvoyer les données
            $json = json_encode($sousCategories);
            
            // Vérifier si l'encodage JSON a réussi
            if ($json === false) {
                error_log("getSousCategories: erreur d'encodage JSON: " . json_last_error_msg());
                echo json_encode(['error' => 'Erreur d\'encodage JSON']);
                exit;
            }
            
            error_log("getSousCategories: renvoi de " . count($sousCategories) . " sous-catégories");
            echo $json;
        } catch (Exception $e) {
            error_log("getSousCategories: exception: " . $e->getMessage());
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
            $image = null;
            
            // Traitement de l'image si elle est présente
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'public/uploads/points_vigilance/';
                
                // Créer le répertoire s'il n'existe pas
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Générer un nom de fichier unique pour éviter les conflits
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('point_') . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                // Déplacer le fichier téléchargé vers le répertoire cible
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image = $file_name;
                } else {
                    $_SESSION['error'] = "Erreur lors de l'upload de l'image.";
                    header('Location: index.php?controller=admin&tab=points');
                    exit;
                }
            }
            
            if (!empty($sous_categorie_id) && !empty($nom)) {
                $this->pointVigilanceModel->create([
                    'sous_categorie_id' => $sous_categorie_id,
                    'nom' => $nom,
                    'description' => $description,
                    'image' => $image
                ]);
                $_SESSION['success'] = "Le point de vigilance a été créé avec succès.";
                header('Location: index.php?controller=admin&tab=points');
                exit;
            }
        }
        $_SESSION['error'] = "Données manquantes pour créer le point de vigilance.";
        header('Location: index.php?controller=admin&tab=points');
        exit;
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
            // Ajouter un log pour débogage
            error_log("getSousCategorieDetails appelé avec id: " . $_GET['id']);
            
            $sousCategorie = $this->sousCategorieModel->getById($_GET['id']);
            
            // Vérifier si les données sont valides
            if (!is_array($sousCategorie)) {
                error_log("getSousCategorieDetails: format de données invalide: " . var_export($sousCategorie, true));
                echo json_encode(['error' => 'Format de données invalide']);
                exit;
            }
            
            // Encoder et renvoyer les données
            $json = json_encode($sousCategorie);
            
            // Vérifier si l'encodage JSON a réussi
            if ($json === false) {
                error_log("getSousCategorieDetails: erreur d'encodage JSON: " . json_last_error_msg());
                echo json_encode(['error' => 'Erreur d\'encodage JSON']);
                exit;
            }
            
            error_log("getSousCategorieDetails: données renvoyées: " . $json);
            echo $json;
        } catch (Exception $e) {
            error_log("getSousCategorieDetails: exception: " . $e->getMessage());
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
            
            $data = [
                'id' => $id,
                'sous_categorie_id' => $sous_categorie_id,
                'nom' => $nom,
                'description' => $description
            ];
            
            // Traitement de l'image si elle est présente et si elle a été modifiée
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'public/uploads/points_vigilance/';
                
                // Créer le répertoire s'il n'existe pas
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Récupérer l'ancienne image pour la supprimer si elle existe
                $point = $this->pointVigilanceModel->getById($id);
                if ($point && !empty($point['image'])) {
                    $old_image_path = $upload_dir . $point['image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                
                // Générer un nom de fichier unique pour éviter les conflits
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('point_') . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                // Déplacer le fichier téléchargé vers le répertoire cible
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $data['image'] = $file_name;
                } else {
                    $_SESSION['error'] = "Erreur lors de l'upload de l'image.";
                    header('Location: index.php?controller=admin&tab=points');
                    exit;
                }
            }
            
            if (!empty($id) && !empty($sous_categorie_id) && !empty($nom)) {
                $this->pointVigilanceModel->update($data);
                $_SESSION['success'] = "Le point de vigilance a été mis à jour avec succès.";
            } else {
                $_SESSION['error'] = "Données manquantes pour mettre à jour le point de vigilance.";
            }
        }
        header('Location: index.php?controller=admin&tab=points');
        exit;
    }

    public function deleteVigilancePoint() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            
            if (!empty($id)) {
                // Récupérer l'image pour la supprimer si elle existe
                $point = $this->pointVigilanceModel->getById($id);
                if ($point && !empty($point['image'])) {
                    $image_path = 'public/uploads/points_vigilance/' . $point['image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                $this->pointVigilanceModel->delete($id);
                $_SESSION['success'] = "Le point de vigilance a été supprimé avec succès.";
            } else {
                $_SESSION['error'] = "ID du point de vigilance manquant.";
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
            // Ajouter un log pour débogage
            error_log("getPointsVigilance appelé avec sous_categorie_id: " . $_GET['sous_categorie_id']);
            
            $points = $this->pointVigilanceModel->getBySousCategorie($_GET['sous_categorie_id']);
            
            // Vérifier le format des données
            if (!is_array($points)) {
                error_log("getPointsVigilance: format de données invalide: " . var_export($points, true));
                echo json_encode(['error' => 'Format de données invalide']);
                exit;
            }
            
            // Vérifier si l'array est vide
            if (empty($points)) {
                error_log("getPointsVigilance: aucun point de vigilance trouvé pour sous_categorie_id: " . $_GET['sous_categorie_id']);
                echo json_encode([]);
                exit;
            }
            
            // Encoder et renvoyer les données
            $json = json_encode($points);
            
            // Vérifier si l'encodage JSON a réussi
            if ($json === false) {
                error_log("getPointsVigilance: erreur d'encodage JSON: " . json_last_error_msg());
                echo json_encode(['error' => 'Erreur d\'encodage JSON']);
                exit;
            }
            
            error_log("getPointsVigilance: renvoi de " . count($points) . " points de vigilance");
            echo $json;
        } catch (Exception $e) {
            error_log("getPointsVigilance: exception: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
} 