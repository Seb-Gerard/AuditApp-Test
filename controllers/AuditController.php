<?php
require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/SousCategorie.php';
require_once __DIR__ . '/../models/PointVigilance.php';
require_once __DIR__ . '/../models/Audit.php';

class AuditController {
    private $categorieModel;
    private $sousCategorieModel;
    private $pointVigilanceModel;
    private $auditModel;

    public function __construct() {
        $this->categorieModel = new Categorie();
        $this->sousCategorieModel = new SousCategorie();
        $this->pointVigilanceModel = new PointVigilance();
        $this->auditModel = new Audit();
    }

    public function index() {
        $audits = $this->auditModel->getAll();
        include_once __DIR__ . '/../views/audits/index.php';
    }

    /**
     * Créer un nouvel audit
     *
     * @return void
     */
    public function create() {
        try {
            // Vérification si le formulaire a été soumis
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                
                // Récupérer les données du formulaire
                $numero_site = isset($_POST['numero_site']) ? $_POST['numero_site'] : null;
                $nom_entreprise = isset($_POST['nom_entreprise']) ? $_POST['nom_entreprise'] : null;
                $date_creation = isset($_POST['date_creation']) ? $_POST['date_creation'] : null;
                $statut = isset($_POST['statut']) ? $_POST['statut'] : 'en_cours';
                
                // Vérifier que les champs obligatoires sont présents
                if (empty($numero_site) || empty($nom_entreprise) || empty($date_creation)) {
                    throw new Exception("Tous les champs obligatoires doivent être remplis");
                }
                
                // Créer un nouvel audit
                $auditId = $this->auditModel->create([
                    'numero_site' => $numero_site,
                    'nom_entreprise' => $nom_entreprise,
                    'date_creation' => $date_creation,
                    'statut' => $statut
                ]);
                
                if (!$auditId) {
                    throw new Exception("Erreur lors de la création de l'audit");
                }
                
                // Traiter les points de vigilance sélectionnés
                $pointsData = [];
                
                // Vérifier si des points de vigilance ont été sélectionnés
                if (isset($_POST['points']) && is_array($_POST['points'])) {
                    foreach ($_POST['points'] as $index => $point) {
                        if (isset($point['point_vigilance_id'], $point['categorie_id'], $point['sous_categorie_id'])) {
                            // Convertir les IDs en entiers
                            $pointId = intval($point['point_vigilance_id']);
                            $categorieId = intval($point['categorie_id']);
                            $sousCategorieId = intval($point['sous_categorie_id']);
                            
                            // Vérifier que les IDs sont valides
                            if ($pointId <= 0 || $categorieId <= 0 || $sousCategorieId <= 0) {
                                continue;
                            }
                            
                            $pointsData[] = [
                                'audit_id' => $auditId,
                                'point_vigilance_id' => $pointId,
                                'categorie_id' => $categorieId,
                                'sous_categorie_id' => $sousCategorieId
                            ];
                        }
                    }
                }
                
                // Enregistrer les points de vigilance s'il y en a
                if (!empty($pointsData)) {
                    $this->auditModel->saveAuditPoints($pointsData);
                }
                
                // Rediriger vers la page de détails de l'audit
                header('Location: index.php?action=audits&method=view&id=' . $auditId);
                exit;
            }
            
            // Si le formulaire n'a pas été soumis, afficher le formulaire de création
            $categories = $this->categorieModel->getAll();
            include_once __DIR__ . '/../views/audits/create.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: index.php?action=audits');
            exit;
        }
    }

    public function view() {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['error'] = "ID d'audit non valide";
            header('Location: index.php?action=audits');
            exit;
        }
        
        $auditId = $_GET['id'];
        $audit = $this->auditModel->getById($auditId);
        
        if (!$audit) {
            $_SESSION['error'] = "Audit non trouvé";
            header('Location: index.php?action=audits');
            exit;
        }
        
        // Vérifier si la table audit_points a la bonne structure
        try {
            $db = $this->auditModel->getConnection();
            $checkSql = "SHOW COLUMNS FROM audit_points LIKE 'categorie_id'";
            $stmt = $db->prepare($checkSql);
            $stmt->execute();
            $hasCategorieId = $stmt->rowCount() > 0;
            
            // Si la colonne categorie_id n'existe pas, exécuter le script de mise à jour
            if (!$hasCategorieId) {
                error_log("La colonne categorie_id n'existe pas dans la table audit_points. Mise à jour de la structure...");
                
                try {
                    // Ajouter la colonne categorie_id
                    $db->exec("ALTER TABLE audit_points ADD COLUMN categorie_id INT NOT NULL AFTER audit_id");
                    error_log("Colonne categorie_id ajoutée.");
                    
                    // Ajouter la colonne sous_categorie_id
                    $db->exec("ALTER TABLE audit_points ADD COLUMN sous_categorie_id INT NOT NULL AFTER categorie_id");
                    error_log("Colonne sous_categorie_id ajoutée.");
                    
                    // Mettre à jour les valeurs des nouvelles colonnes
                    $db->exec("UPDATE audit_points ap 
                              JOIN points_vigilance pv ON ap.point_vigilance_id = pv.id 
                              SET ap.categorie_id = pv.categorie_id, 
                                  ap.sous_categorie_id = pv.sous_categorie_id");
                    error_log("Colonnes mises à jour avec succès.");
                } catch (Exception $e) {
                    error_log("Erreur lors de l'exécution du script SQL: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la vérification/mise à jour de la structure de la table : " . $e->getMessage());
        }
        
        $pointsVigilance = $this->auditModel->getAuditPointsById($auditId);
        
        // Inclure le modèle PointVigilance pour l'affichage des images
        require_once __DIR__ . '/../models/PointVigilance.php';
        
        include_once __DIR__ . '/../views/audits/view.php';
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

    /**
     * Éditer un audit existant
     *
     * @return void
     */
    public function edit() {
        try {
            // Vérifier si l'ID de l'audit est valide
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                throw new Exception("ID d'audit non valide");
            }
            
            $auditId = (int)$_GET['id'];
            $audit = $this->auditModel->getById($auditId);
            
            if (!$audit) {
                throw new Exception("Audit non trouvé");
            }
            
            // Traitement du formulaire s'il est soumis
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Récupérer les données du formulaire
                $numero_site = isset($_POST['numero_site']) ? $_POST['numero_site'] : null;
                $nom_entreprise = isset($_POST['nom_entreprise']) ? $_POST['nom_entreprise'] : null;
                $date_creation = isset($_POST['date_creation']) ? $_POST['date_creation'] : null;
                $statut = isset($_POST['statut']) ? $_POST['statut'] : 'en_cours';
                
                // Vérifier que les champs obligatoires sont présents
                if (empty($numero_site) || empty($nom_entreprise) || empty($date_creation)) {
                    throw new Exception("Tous les champs obligatoires doivent être remplis");
                }
                
                // Mettre à jour l'audit
                $result = $this->auditModel->update($auditId, [
                    'numero_site' => $numero_site,
                    'nom_entreprise' => $nom_entreprise,
                    'date_creation' => $date_creation,
                    'statut' => $statut
                ]);
                
                if (!$result) {
                    throw new Exception("Erreur lors de la mise à jour de l'audit");
                }
                
                // Traiter les points de vigilance
                $pointsData = [];
                
                // Supprimer d'abord tous les points existants
                $this->auditModel->deleteAuditPoints($auditId);
                
                // Vérifier si des points de vigilance ont été sélectionnés
                if (isset($_POST['points']) && is_array($_POST['points'])) {
                    foreach ($_POST['points'] as $index => $point) {
                        if (isset($point['point_vigilance_id'], $point['categorie_id'], $point['sous_categorie_id'])) {
                            // Convertir les IDs en entiers
                            $pointId = intval($point['point_vigilance_id']);
                            $categorieId = intval($point['categorie_id']);
                            $sousCategorieId = intval($point['sous_categorie_id']);
                            
                            // Vérifier que les IDs sont valides
                            if ($pointId <= 0 || $categorieId <= 0 || $sousCategorieId <= 0) {
                                continue;
                            }
                            
                            $pointsData[] = [
                                'audit_id' => $auditId,
                                'point_vigilance_id' => $pointId,
                                'categorie_id' => $categorieId,
                                'sous_categorie_id' => $sousCategorieId
                                // Note: L'ordre est ajouté par la méthode saveAuditPoints basé sur l'index dans le tableau
                            ];
                        }
                    }
                }
                
                // Enregistrer les nouveaux points de vigilance s'il y en a
                if (!empty($pointsData)) {
                    $this->auditModel->saveAuditPoints($pointsData);
                }
                
                $_SESSION['success'] = "L'audit a été mis à jour avec succès";
                header('Location: index.php?action=audits&method=view&id=' . $auditId);
                exit;
            }
            
            // Charger les données nécessaires pour le formulaire d'édition
            $categories = $this->categorieModel->getAll();
            $pointsVigilance = $this->auditModel->getAuditPointsById($auditId);
            
            // Afficher la vue d'édition
            include_once __DIR__ . '/../views/audits/edit.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: index.php?action=audits');
            exit;
        }
    }
    
    /**
     * Supprimer un audit
     *
     * @return void
     */
    public function delete() {
        try {
            // Vérifier si l'ID de l'audit est valide
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                throw new Exception("ID d'audit non valide");
            }
            
            $auditId = (int)$_GET['id'];
            
            // Vérifier que l'audit existe
            $audit = $this->auditModel->getById($auditId);
            if (!$audit) {
                throw new Exception("Audit non trouvé");
            }
            
            // Supprimer d'abord les points de vigilance associés
            $this->auditModel->deleteAuditPoints($auditId);
            
            // Puis supprimer l'audit lui-même
            $result = $this->auditModel->delete($auditId);
            
            if (!$result) {
                throw new Exception("Erreur lors de la suppression de l'audit");
            }
            
            $_SESSION['success'] = "L'audit a été supprimé avec succès";
            header('Location: index.php?action=audits');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: index.php?action=audits');
            exit;
        }
    }

    /**
     * Met à jour uniquement le statut d'un audit
     * 
     * @return void
     */
    public function updateStatus() {
        try {
            // Vérifier si l'ID de l'audit et le statut sont valides
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                throw new Exception("ID d'audit non valide");
            }
            
            if (!isset($_GET['statut']) || !in_array($_GET['statut'], ['en_cours', 'termine'])) {
                throw new Exception("Statut non valide");
            }
            
            $auditId = (int)$_GET['id'];
            $statut = $_GET['statut'];
            
            // Vérifier que l'audit existe
            $audit = $this->auditModel->getById($auditId);
            if (!$audit) {
                throw new Exception("Audit non trouvé");
            }
            
            // Mettre à jour le statut de l'audit
            $result = $this->auditModel->updateStatus($auditId, $statut);
            
            if (!$result) {
                throw new Exception("Erreur lors de la mise à jour du statut");
            }
            
            $_SESSION['success'] = "Le statut de l'audit a été mis à jour avec succès";
            
            // Rediriger vers la page de détails de l'audit
            header('Location: index.php?action=audits&method=view&id=' . $auditId);
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: index.php?action=audits');
            exit;
        }
    }
} 