<?php
require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/SousCategorie.php';
require_once __DIR__ . '/../models/PointVigilance.php';
require_once __DIR__ . '/../models/Audit.php';
require_once __DIR__ . '/../models/AuditDocument.php';

class AuditController {
    private $categorieModel;
    private $sousCategorieModel;
    private $pointVigilanceModel;
    private $auditModel;
    private $auditDocumentModel;

    public function __construct() {
        $this->categorieModel = new Categorie();
        $this->sousCategorieModel = new SousCategorie();
        $this->pointVigilanceModel = new PointVigilance();
        $this->auditModel = new Audit();
        $this->auditDocumentModel = new AuditDocument();
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

                // Traitement du logo
                $logo = null;
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'public/uploads/logos/';
                    
                    // Vérifier si le dossier existe, sinon le créer
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    // Vérifier le type de fichier
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $fileType = $_FILES['logo']['type'];
                    
                    if (!in_array($fileType, $allowedTypes)) {
                        throw new Exception("Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.");
                    }
                    
                    // Générer un nom unique pour le fichier
                    $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid('logo_') . '.' . $extension;
                    $uploadFile = $uploadDir . $fileName;
                    
                    // Déplacer le fichier
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadFile)) {
                        $logo = $fileName;
                    } else {
                        throw new Exception("Erreur lors de l'upload du logo");
                    }
                }
                
                // Créer un nouvel audit
                $auditId = $this->auditModel->create([
                    'numero_site' => $numero_site,
                    'nom_entreprise' => $nom_entreprise,
                    'date_creation' => $date_creation,
                    'statut' => $statut,
                    'logo' => $logo
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
        
        // Ajouter les documents et photos pour chaque point
        foreach ($pointsVigilance as $key => $point) {
            $photos = $this->auditDocumentModel->getDocumentsByPoint($auditId, $point['point_vigilance_id'], 'photo');
            $documents = $this->auditDocumentModel->getDocumentsByPoint($auditId, $point['point_vigilance_id'], 'document');
            
            $pointsVigilance[$key]['photos'] = $photos;
            $pointsVigilance[$key]['documents'] = $documents;
        }
        
        // Vérifier si le format JSON est demandé (pour l'API)
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            header('Content-Type: application/json');
            echo json_encode([
                'audit' => $audit,
                'points' => $pointsVigilance
            ]);
            exit;
        }
        
        // Inclure le modèle PointVigilance pour l'affichage des images
        require_once __DIR__ . '/../models/PointVigilance.php';
        
        include_once __DIR__ . '/../views/audits/view.php';
    }

    public function getSousCategories() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['categorie_id'])) {
            error_log('getSousCategories appelé sans categorie_id');
            echo json_encode(['error' => 'categorie_id manquant']);
            exit;
        }

        try {
            // Log pour le débogage
            error_log('Requête getSousCategories reçue');
            
            // Support for single or multiple category IDs
            $categorieIds = is_array($_GET['categorie_id']) ? $_GET['categorie_id'] : [$_GET['categorie_id']];
            error_log('categorie_id bruts: ' . json_encode($categorieIds));
            
            // Valider les IDs (s'assurer qu'ils sont numériques)
            $validCategorieIds = [];
            foreach ($categorieIds as $id) {
                $id = filter_var($id, FILTER_VALIDATE_INT);
                if ($id !== false) {
                    $validCategorieIds[] = $id;
                }
            }
            
            // Si aucun ID valide, renvoyer une erreur
            if (empty($validCategorieIds)) {
                error_log('Aucun ID de catégorie valide fourni');
                echo json_encode(['error' => 'Aucun ID de catégorie valide fourni']);
                exit;
            }
            
            error_log('IDs de catégories valides: ' . json_encode($validCategorieIds));
            
            // Vérifier que le modèle existe
            if (!isset($this->categorieModel)) {
                error_log('ERREUR: Modèle de catégorie non initialisé');
                throw new Exception('Erreur interne du serveur: modèle non initialisé');
            }
            
            // Vidanger le tampon de sortie pour éviter les problèmes de format
            if (ob_get_length()) {
                error_log('Tampon de sortie non vide, nettoyage');
                ob_clean();
                header('Content-Type: application/json');
            }
            
            $sousCategories = [];
            
            foreach ($validCategorieIds as $categorieId) {
                try {
                    $categorie = $this->categorieModel->getById($categorieId);
                    if (!$categorie) {
                        error_log('Catégorie non trouvée: ' . $categorieId);
                        continue;
                    }
                    
                    $sousCategs = $this->categorieModel->getSousCategories($categorieId);
                    error_log('Sous-catégories obtenues pour ID ' . $categorieId . ': ' . (is_array($sousCategs) ? count($sousCategs) : 'non-tableau'));
                    
                    if (is_array($sousCategs)) {
                        $sousCategories = array_merge($sousCategories, $sousCategs);
                    } else {
                        error_log('ERREUR: getSousCategories a retourné un format non attendu: ' . gettype($sousCategs));
                    }
                } catch (Exception $innerE) {
                    error_log('Erreur lors de la récupération des sous-catégories pour ID ' . $categorieId . ': ' . $innerE->getMessage());
                    // Continuer avec les autres IDs même si une erreur se produit
                }
            }
            
            // Assurer que le résultat est un tableau (même vide)
            if (!is_array($sousCategories)) {
                error_log('Résultat inattendu: ' . var_export($sousCategories, true));
                $sousCategories = [];
            }
            
            error_log('Nombre total de sous-catégories: ' . count($sousCategories));
            echo json_encode($sousCategories);
            
        } catch (Exception $e) {
            error_log('Exception dans getSousCategories: ' . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function getPointsVigilance() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['sous_categorie_id'])) {
            error_log('getPointsVigilance appelé sans sous_categorie_id');
            echo json_encode(['error' => 'sous_categorie_id manquant']);
            exit;
        }

        try {
            // Nettoyer le tampon de sortie pour éviter les problèmes de format
            if (ob_get_length()) {
                error_log('Tampon de sortie non vide, nettoyage');
                ob_clean();
                header('Content-Type: application/json');
            }
            
            // Support for single or multiple sous category IDs
            $sousCategorieIds = is_array($_GET['sous_categorie_id']) ? $_GET['sous_categorie_id'] : [$_GET['sous_categorie_id']];
            error_log('Récupération des points de vigilance pour les sous-catégories: ' . json_encode($sousCategorieIds));
            
            // Valider les IDs (s'assurer qu'ils sont numériques)
            $validSousCategorieIds = [];
            foreach ($sousCategorieIds as $id) {
                $id = filter_var($id, FILTER_VALIDATE_INT);
                if ($id !== false && $id > 0) {
                    $validSousCategorieIds[] = $id;
                }
            }
            
            if (empty($validSousCategorieIds)) {
                error_log('Aucun ID de sous-catégorie valide fourni');
                echo json_encode(['error' => 'Aucun ID de sous-catégorie valide fourni']);
                exit;
            }
            
            error_log('IDs de sous-catégories valides: ' . json_encode($validSousCategorieIds));
            
            if (!isset($this->sousCategorieModel)) {
                error_log('ERREUR: Modèle de sous-catégorie non initialisé');
                throw new Exception('Erreur interne du serveur: modèle non initialisé');
            }
            
            $pointsVigilance = [];
            
            foreach ($validSousCategorieIds as $sousCategorieId) {
                try {
                    $sousCateg = $this->sousCategorieModel->getById($sousCategorieId);
                    if (!$sousCateg) {
                        error_log('Sous-catégorie non trouvée: ' . $sousCategorieId);
                        continue;
                    }
                    
                    $points = $this->sousCategorieModel->getPointsVigilance($sousCategorieId);
                    error_log('Points de vigilance pour sous-catégorie ' . $sousCategorieId . ': ' . count($points));
                    
                    if (is_array($points)) {
                        $pointsVigilance = array_merge($pointsVigilance, $points);
                    } else {
                        error_log('getPointsVigilance a retourné un format non attendu pour ID ' . $sousCategorieId);
                    }
                } catch (Exception $innerE) {
                    error_log('Erreur lors de la récupération des points pour la sous-catégorie ' . $sousCategorieId . ': ' . $innerE->getMessage());
                    // Continuer avec les autres IDs même si une erreur se produit
                }
            }
            
            // Assurer que le résultat est un tableau
            if (!is_array($pointsVigilance)) {
                error_log('Résultat inattendu pour getPointsVigilance: ' . var_export($pointsVigilance, true));
                $pointsVigilance = [];
            }
            
            error_log('Nombre total de points de vigilance retournés: ' . count($pointsVigilance));
            echo json_encode($pointsVigilance);
            
        } catch (Exception $e) {
            error_log('Exception dans getPointsVigilance: ' . $e->getMessage());
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

                // Traitement du logo
                $logo = $audit['logo']; // Garder l'ancien logo par défaut
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'public/uploads/logos/';
                    
                    // Vérifier si le dossier existe, sinon le créer
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    // Vérifier le type de fichier
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $fileType = $_FILES['logo']['type'];
                    
                    if (!in_array($fileType, $allowedTypes)) {
                        throw new Exception("Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.");
                    }
                    
                    // Supprimer l'ancien logo s'il existe
                    if (!empty($audit['logo'])) {
                        $oldLogoPath = $uploadDir . $audit['logo'];
                        if (file_exists($oldLogoPath)) {
                            unlink($oldLogoPath);
                        }
                    }
                    
                    // Générer un nom unique pour le fichier
                    $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid('logo_') . '.' . $extension;
                    $uploadFile = $uploadDir . $fileName;
                    
                    // Déplacer le fichier
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadFile)) {
                        $logo = $fileName;
                    } else {
                        throw new Exception("Erreur lors de l'upload du logo");
                    }
                }
                
                // Mettre à jour l'audit
                $result = $this->auditModel->update($auditId, [
                    'numero_site' => $numero_site,
                    'nom_entreprise' => $nom_entreprise,
                    'date_creation' => $date_creation,
                    'statut' => $statut,
                    'logo' => $logo
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
        if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['statut'])) {
            $_SESSION['error'] = "Paramètres invalides pour la mise à jour du statut";
            header('Location: index.php?action=audits');
            exit;
        }
        
        $auditId = $_GET['id'];
        $statut = $_GET['statut'];
        
        if ($statut !== 'en_cours' && $statut !== 'termine') {
            $_SESSION['error'] = "Statut invalide";
            header('Location: index.php?action=audits');
            exit;
        }
        
        try {
            $success = $this->auditModel->updateStatus($auditId, $statut);
            
            if ($success) {
                $_SESSION['success'] = "Le statut de l'audit a été mis à jour avec succès";
            } else {
                $_SESSION['error'] = "Erreur lors de la mise à jour du statut";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        // Redirection en fonction du paramètre redirect
        if (isset($_GET['redirect']) && $_GET['redirect'] === 'resume') {
            header('Location: index.php?action=audits&method=resume&id=' . $auditId);
        } else {
            header('Location: index.php?action=audits&method=view&id=' . $auditId);
        }
        exit;
    }

    /**
     * Mettre à jour l'évaluation d'un point de vigilance pour un audit
     */
    public function evaluerPoint() {
        // Créer le dossier de logs s'il n'existe pas
        $logDir = __DIR__ . '/../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        // S'assurer que l'en-tête est défini avant toute sortie
        header('Content-Type: application/json');
        // Permettre l'accès depuis n'importe quelle origine (pour les requêtes AJAX)
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        
        // En cas de requête OPTIONS (pre-flight), renvoyer juste les en-têtes
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit();
        }
        
        // Initialiser la réponse
        $response = ['success' => false, 'message' => ''];
        
        // Journaliser le début de la requête
        $logMsg = "[" . date('Y-m-d H:i:s') . "] DÉBUT REQUÊTE EVALUER POINT\n";
        $logMsg .= "Méthode: " . $_SERVER['REQUEST_METHOD'] . "\n";
        $logMsg .= "Headers: " . json_encode(getallheaders()) . "\n";
        $logMsg .= "POST: " . print_r($_POST, true) . "\n";
        file_put_contents($logDir . '/controller_logs.log', $logMsg, FILE_APPEND);
        
        try {
            // Vérifier si une requête AJAX est utilisée
            $isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            file_put_contents($logDir . '/controller_logs.log', "[" . date('Y-m-d H:i:s') . "] Type requête: " . ($isAjaxRequest ? "AJAX" : "Standard") . "\n", FILE_APPEND);
            
            // Pour les requêtes AJAX avec Content-Type: application/json
            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : '';
            if (strpos($contentType, 'application/json') !== false) {
                $jsonData = file_get_contents('php://input');
                $_POST = json_decode($jsonData, true);
                file_put_contents($logDir . '/controller_logs.log', "[" . date('Y-m-d H:i:s') . "] Données JSON reçues: " . $jsonData . "\n", FILE_APPEND);
            }
            
            // Vérifier les paramètres nécessaires
            if (!isset($_POST['audit_id']) || !isset($_POST['point_vigilance_id'])) {
                $response['message'] = "Paramètres manquants: audit_id et/ou point_vigilance_id requis.";
                file_put_contents($logDir . '/controller_logs.log', "[" . date('Y-m-d H:i:s') . "] ERREUR: " . $response['message'] . "\n", FILE_APPEND);
                echo json_encode($response);
                return;
            }
            
            // Récupérer les identifiants de l'audit et du point de vigilance
            $auditId = filter_var($_POST['audit_id'], FILTER_VALIDATE_INT);
            $pointVigilanceId = filter_var($_POST['point_vigilance_id'], FILTER_VALIDATE_INT);
            
            // Conversion explicite en entier pour éviter les erreurs de type
            if ($auditId === false) {
                $auditId = intval($_POST['audit_id']);
            }
            if ($pointVigilanceId === false) {
                $pointVigilanceId = intval($_POST['point_vigilance_id']);
            }
            
            file_put_contents($logDir . '/controller_logs.log', "[" . date('Y-m-d H:i:s') . "] Valeurs après conversion: audit_id=$auditId, point_vigilance_id=$pointVigilanceId\n", FILE_APPEND);
            
            if ($auditId <= 0 || $pointVigilanceId <= 0) {
                $response['message'] = "Valeurs invalides pour audit_id ou point_vigilance_id.";
                file_put_contents($logDir . '/controller_logs.log', "[" . date('Y-m-d H:i:s') . "] ERREUR: " . $response['message'] . "\n", FILE_APPEND);
                echo json_encode($response);
                return;
            }
            
            // Préparer les données pour le modèle
            $data = [
                'audit_id' => $auditId,
                'point_vigilance_id' => $pointVigilanceId,
                'mesure_reglementaire' => isset($_POST['mesure_reglementaire']) ? filter_var($_POST['mesure_reglementaire'], FILTER_VALIDATE_INT) : 0,
                'non_audite' => isset($_POST['non_audite']) ? filter_var($_POST['non_audite'], FILTER_VALIDATE_INT) : 0,
                'mode_preuve' => isset($_POST['mode_preuve']) ? trim(htmlspecialchars($_POST['mode_preuve'])) : null,
                'resultat' => isset($_POST['resultat']) ? trim(htmlspecialchars($_POST['resultat'])) : null,
                'justification' => isset($_POST['justification']) ? trim(htmlspecialchars($_POST['justification'])) : null,
                'plan_action_numero' => !empty($_POST['plan_action_numero']) ? filter_var($_POST['plan_action_numero'], FILTER_VALIDATE_INT) : null,
                'plan_action_priorite' => isset($_POST['plan_action_priorite']) ? trim(htmlspecialchars($_POST['plan_action_priorite'])) : null,
                'plan_action_description' => isset($_POST['plan_action_description']) ? trim(htmlspecialchars($_POST['plan_action_description'])) : null
            ];
            
            // Assurer une conversion explicite des champs numériques
            if ($data['mesure_reglementaire'] === false) $data['mesure_reglementaire'] = 0;
            if ($data['non_audite'] === false) $data['non_audite'] = 0;
            if ($data['plan_action_numero'] === false) $data['plan_action_numero'] = null;
            
            // Journaliser les données traitées
            file_put_contents($logDir . '/controller_logs.log', "[" . date('Y-m-d H:i:s') . "] Données traitées pour updateEvaluation: " . print_r($data, true) . "\n", FILE_APPEND);
            
            // Mettre à jour ou créer l'évaluation
            $result = $this->auditModel->updateEvaluation($auditId, $pointVigilanceId, $data);
            file_put_contents($logDir . '/controller_logs.log', "[" . date('Y-m-d H:i:s') . "] Résultat updateEvaluation: " . ($result ? "SUCCÈS" : "ÉCHEC") . "\n", FILE_APPEND);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = "Évaluation mise à jour avec succès";
                $response['data'] = $data;
            } else {
                $response['message'] = "Erreur lors de la mise à jour de l'évaluation.";
            }
        } catch (Exception $e) {
            $response['message'] = "Erreur: " . $e->getMessage();
            file_put_contents($logDir . '/controller_logs.log', "[" . date('Y-m-d H:i:s') . "] EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
        }
        
        // Journaliser la réponse
        file_put_contents($logDir . '/controller_logs.log', "[" . date('Y-m-d H:i:s') . "] RÉPONSE: " . json_encode($response) . "\n", FILE_APPEND);
        
        // Renvoyer la réponse JSON
        echo json_encode($response);
        return;
    }
    
    /**
     * Supprimer un document
     */
    public function supprimerDocument() {
        // Définir le type de contenu comme JSON
        header('Content-Type: application/json');
        
        // S'assurer qu'aucun contenu HTML n'est déjà dans le buffer
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Initialiser la réponse
        $response = [
            'success' => false,
            'message' => ''
        ];

        try {
            // Vérifier la méthode de requête
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $response['message'] = 'Méthode non autorisée. Utilisez POST.';
                echo json_encode($response);
                exit;
            }

            // Récupérer et valider le document_id
            $documentId = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (!$documentId) {
                $response['message'] = 'ID du document manquant ou invalide.';
                echo json_encode($response);
                exit;
            }

            error_log("Tentative de suppression du document ID: " . $documentId);

            // Instancier le modèle Audit
            $auditModel = new Audit();
            
            // Récupérer les informations du document
            $document = $auditModel->getDocumentById($documentId);
            
            if (!$document) {
                $response['message'] = 'Document non trouvé.';
                echo json_encode($response);
                exit;
            }
            
            // Vérifier si l'audit est complété
            $audit = $auditModel->getById($document['audit_id']);
            if (!$audit) {
                $response['message'] = 'Audit non trouvé.';
                echo json_encode($response);
                exit;
            }
            
            if ($audit['statut'] === 'completed') {
                $response['message'] = 'Impossible de modifier un audit complété.';
                echo json_encode($response);
                exit;
            }
            
            // Supprimer le fichier physique si présent
            if (!empty($document['chemin_fichier']) && file_exists($document['chemin_fichier'])) {
                if (!unlink($document['chemin_fichier'])) {
                    error_log("Impossible de supprimer le fichier: " . $document['chemin_fichier']);
                } else {
                    error_log("Fichier supprimé: " . $document['chemin_fichier']);
                }
            }
            
            // Supprimer l'entrée de la base de données
            $result = $auditModel->supprimerDocument($documentId);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Document supprimé avec succès.';
                error_log("Document supprimé avec succès: ID=" . $documentId);
            } else {
                $response['message'] = 'Erreur lors de la suppression du document.';
                error_log("Échec de la suppression du document: ID=" . $documentId);
            }
        } catch (Exception $e) {
            error_log("Erreur dans supprimerDocument: " . $e->getMessage());
            $response['message'] = 'Une erreur est survenue lors de la suppression du document: ' . $e->getMessage();
        }
        
        // S'assurer qu'aucun HTML ou autre contenu n'est envoyé avant le JSON
        if (ob_get_length()) {
            ob_clean();
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Prendre une photo via la webcam
     */
    public function prendrePhoto() {
        // Toujours définir l'en-tête Content-Type en premier
        header('Content-Type: application/json');
        
        // Initialiser la réponse
        $response = ['success' => false, 'message' => ''];
        
        try {
            // Suppression de la vérification AJAX, car elle est trop restrictive 
            // avec le Service Worker qui intercepte les requêtes
            
            // Vérifier si la requête est de type POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée");
            }
            
            // Vérifier que les paramètres requis sont présents
            if (!isset($_POST['audit_id']) || !isset($_POST['point_vigilance_id']) || !isset($_POST['image_base64'])) {
                throw new Exception("Paramètres manquants");
            }
            
            $auditId = intval($_POST['audit_id']);
            $pointVigilanceId = intval($_POST['point_vigilance_id']);
            $imageData = $_POST['image_base64'];
            
            error_log("Début traitement photo - audit_id: $auditId, point_vigilance_id: $pointVigilanceId");
            
            // Vérifier que les données de l'image sont valides
            if (empty($imageData)) {
                throw new Exception("Données d'image vides");
            }
            
            if (strpos($imageData, 'data:image') !== 0) {
                $firstChars = substr($imageData, 0, 30); // Premiers caractères pour le débogage
                error_log("Format d'image non valide. Début des données: " . $firstChars);
                throw new Exception("Format d'image non valide");
            }
            
            // Extraire les données de l'image base64
            $parts = explode(',', $imageData);
            if (count($parts) < 2) {
                error_log("Format de données d'image incorrect: pas de virgule trouvée");
                throw new Exception("Format de données d'image incorrect");
            }
            
            $encodedImage = $parts[1];
            error_log("Taille des données encodées: " . strlen($encodedImage) . " caractères");
            
            // Décoder l'image
            $decodedImage = base64_decode($encodedImage, true);
            
            if ($decodedImage === false) {
                error_log("Échec du décodage base64 de l'image");
                throw new Exception("Échec du décodage de l'image");
            }
            
            error_log("Image décodée avec succès. Taille: " . strlen($decodedImage) . " octets");
            
            // Vérifier les répertoires de destination
            $uploadDir = 'public/uploads/audit_photos/';
            if (!is_dir($uploadDir)) {
                error_log("Le répertoire $uploadDir n'existe pas ou n'est pas accessible");
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception("Impossible de créer le répertoire de destination");
                }
                error_log("Répertoire $uploadDir créé");
            }
            
            // Vérifier les permissions d'écriture
            if (!is_writable($uploadDir)) {
                error_log("Le répertoire $uploadDir n'est pas accessible en écriture");
                throw new Exception("Le répertoire de destination n'est pas accessible en écriture");
            }
            
            // Créer un fichier temporaire pour l'image
            $tempFile = tempnam(sys_get_temp_dir(), 'photo');
            error_log("Fichier temporaire créé: $tempFile");
            
            if (!file_put_contents($tempFile, $decodedImage)) {
                error_log("Échec de l'écriture dans le fichier temporaire");
                throw new Exception("Impossible d'enregistrer l'image temporaire");
            }
            
            error_log("Image temporaire enregistrée avec succès. Taille: " . filesize($tempFile) . " octets");
            
            // Préparer les informations du fichier
            $fileName = 'webcam_photo_' . date('YmdHis') . '.jpg';
            $file = [
                'name' => $fileName,
                'tmp_name' => $tempFile,
                'error' => 0,
                'size' => strlen($decodedImage)
            ];
            
            error_log("Tentative d'ajout de la photo dans la base de données et déplacement vers: $uploadDir$fileName");
            
            // Ajouter la photo
            $documentId = $this->auditDocumentModel->ajouter($auditId, $pointVigilanceId, 'photo', $file);
            
            // Supprimer le fichier temporaire
            if (file_exists($tempFile)) {
                if (unlink($tempFile)) {
                    error_log("Fichier temporaire supprimé: $tempFile");
                } else {
                    error_log("Impossible de supprimer le fichier temporaire: $tempFile");
                }
            }
            
            if (!$documentId) {
                error_log("Échec de l'ajout de la photo dans la base de données");
                throw new Exception("Erreur lors de l'ajout de la photo");
            }
            
            error_log("Photo ajoutée avec succès. ID: $documentId");
            
            // Récupérer les informations de la photo
            $photos = $this->auditDocumentModel->getDocumentsByPoint($auditId, $pointVigilanceId, 'photo');
            $newPhoto = null;
            
            foreach ($photos as $photo) {
                if ($photo['id'] == $documentId) {
                    $newPhoto = $photo;
                    break;
                }
            }
            
            if ($newPhoto) {
                error_log("Informations de la nouvelle photo récupérées: " . json_encode($newPhoto));
            } else {
                error_log("Impossible de récupérer les informations de la nouvelle photo");
            }
            
            $response['success'] = true;
            $response['message'] = "Photo ajoutée avec succès";
            $response['photo'] = $newPhoto;
            
        } catch (Exception $e) {
            error_log("Exception dans prendrePhoto: " . $e->getMessage());
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            
            // Trace complète pour le débogage
            error_log("Trace: " . $e->getTraceAsString());
        }
        
        // S'assurer qu'aucun HTML ou autre contenu ne soit affiché avant la réponse JSON
        if (ob_get_length()) {
            ob_clean();
        }
        
        error_log("Réponse finale: " . json_encode($response));
        echo json_encode($response);
        exit;
    }
    
    /**
     * Afficher un résumé de l'audit
     * Avec statistiques par catégorie et sous-catégorie
     * et liste des plans d'action
     */
    public function resume() {
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
        
        // Récupérer tous les points de vigilance de l'audit
        $pointsVigilance = $this->auditModel->getAuditPointsById($auditId);
        
        // Initialiser les tableaux pour les statistiques
        $categoriesStats = [];
        $sousCategories = [];
        $plansAction = [];
        
        // Analyser les points de vigilance pour générer les statistiques
        foreach ($pointsVigilance as $point) {
            // Stats par catégorie
            $categorieId = $point['categorie_id'];
            $categorieName = $point['categorie_nom'];
            
            if (!isset($categoriesStats[$categorieId])) {
                $categoriesStats[$categorieId] = [
                    'nom' => $categorieName,
                    'total' => 0,
                    'audite' => 0,
                    'non_audite' => 0,
                    'satisfait' => 0,
                    'non_satisfait' => 0,
                    'partiellement' => 0
                ];
            }
            
            // Stats par sous-catégorie
            $sousCategorieId = $point['sous_categorie_id'];
            $sousCategorieName = $point['sous_categorie_nom'];
            
            if (!isset($sousCategories[$sousCategorieId])) {
                $sousCategories[$sousCategorieId] = [
                    'nom' => $sousCategorieName,
                    'categorie_nom' => $categorieName,
                    'total' => 0,
                    'audite' => 0,
                    'non_audite' => 0,
                    'satisfait' => 0,
                    'non_satisfait' => 0,
                    'partiellement' => 0
                ];
            }
            
            // Incrémenter les compteurs
            $categoriesStats[$categorieId]['total']++;
            $sousCategories[$sousCategorieId]['total']++;
            
            // Évaluation: audité ou non
            if (!empty($point['non_audite'])) {
                $categoriesStats[$categorieId]['audite']++;
                $sousCategories[$sousCategorieId]['audite']++;
            } else {
                $categoriesStats[$categorieId]['non_audite']++;
                $sousCategories[$sousCategorieId]['non_audite']++;
            }
            
            // Évaluation: satisfait, non satisfait, partiellement
            if (isset($point['resultat'])) {
                if ($point['resultat'] === 'satisfait') {
                    $categoriesStats[$categorieId]['satisfait']++;
                    $sousCategories[$sousCategorieId]['satisfait']++;
                } elseif ($point['resultat'] === 'non_satisfait') {
                    $categoriesStats[$categorieId]['non_satisfait']++;
                    $sousCategories[$sousCategorieId]['non_satisfait']++;
                } elseif ($point['resultat'] === 'partiellement') {
                    $categoriesStats[$categorieId]['partiellement']++;
                    $sousCategories[$sousCategorieId]['partiellement']++;
                }
            }
            
            // Collecter les plans d'action
            if (!empty($point['plan_action_description'])) {
                $plansAction[] = [
                    'point_vigilance_id' => $point['point_vigilance_id'],
                    'point_vigilance_nom' => $point['point_vigilance_nom'],
                    'categorie_nom' => $categorieName,
                    'sous_categorie_nom' => $sousCategorieName,
                    'numero' => $point['plan_action_numero'] ?? '',
                    'description' => $point['plan_action_description'],
                    'resultat' => $point['resultat'] ?? '',
                    'priorite' => $point['plan_action_priorite'] ?? ''
                ];
            }
        }
        
        // Calculer les pourcentages pour chaque catégorie et sous-catégorie
        foreach ($categoriesStats as $id => $categorie) {
            if ($categorie['total'] > 0) {
                $categoriesStats[$id]['pct_audite'] = round(($categorie['audite'] / $categorie['total']) * 100, 1);
                $categoriesStats[$id]['pct_non_audite'] = round(($categorie['non_audite'] / $categorie['total']) * 100, 1);
                
                $totalEvalues = $categorie['satisfait'] + $categorie['non_satisfait'] + $categorie['partiellement'];
                if ($totalEvalues > 0) {
                    $categoriesStats[$id]['pct_satisfait'] = round(($categorie['satisfait'] / $totalEvalues) * 100, 1);
                    $categoriesStats[$id]['pct_non_satisfait'] = round(($categorie['non_satisfait'] / $totalEvalues) * 100, 1);
                    $categoriesStats[$id]['pct_partiellement'] = round(($categorie['partiellement'] / $totalEvalues) * 100, 1);
                }
            }
        }
        
        foreach ($sousCategories as $id => $sousCategorie) {
            if ($sousCategorie['total'] > 0) {
                $sousCategories[$id]['pct_audite'] = round(($sousCategorie['audite'] / $sousCategorie['total']) * 100, 1);
                $sousCategories[$id]['pct_non_audite'] = round(($sousCategorie['non_audite'] / $sousCategorie['total']) * 100, 1);
                
                $totalEvalues = $sousCategorie['satisfait'] + $sousCategorie['non_satisfait'] + $sousCategorie['partiellement'];
                if ($totalEvalues > 0) {
                    $sousCategories[$id]['pct_satisfait'] = round(($sousCategorie['satisfait'] / $totalEvalues) * 100, 1);
                    $sousCategories[$id]['pct_non_satisfait'] = round(($sousCategorie['non_satisfait'] / $totalEvalues) * 100, 1);
                    $sousCategories[$id]['pct_partiellement'] = round(($sousCategorie['partiellement'] / $totalEvalues) * 100, 1);
                }
            }
        }
        
        // Trier les plans d'action par numéro si disponible
        usort($plansAction, function($a, $b) {
            if (empty($a['numero']) && empty($b['numero'])) {
                return 0;
            }
            if (empty($a['numero'])) {
                return 1;
            }
            if (empty($b['numero'])) {
                return -1;
            }
            return $a['numero'] - $b['numero'];
        });
        
        include_once __DIR__ . '/../views/audits/resume.php';
    }

    /**
     * Exporte le résumé de l'audit en PDF
     */
    public function exportPDF() {
        // Ajouter des logs de débogage
        error_log('Début de la méthode exportPDF');
        
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['error'] = "ID d'audit invalide";
            error_log('ID d\'audit invalide');
            header('Location: index.php?action=audits');
            exit;
        }
        
        $auditId = $_GET['id'];
        error_log('ID d\'audit récupéré: ' . $auditId);
        $audit = $this->auditModel->getById($auditId);
        
        if (!$audit) {
            $_SESSION['error'] = "Audit non trouvé";
            error_log('Audit non trouvé avec ID: ' . $auditId);
            header('Location: index.php?action=audits');
            exit;
        }
        
        error_log('Audit trouvé: ' . $audit['numero_site']);
        
        // Récupérer les données nécessaires pour le PDF
        $pointsVigilance = $this->auditModel->getAuditPointsById($auditId);
        error_log('Nombre de points de vigilance récupérés: ' . count($pointsVigilance));
        
        // Créer des tableaux pour stocker les statistiques
        $categoriesStats = [];
        $sousCategories = [];
        $plansAction = [];
        
        // Parcourir les points de vigilance pour calculer les statistiques
        foreach ($pointsVigilance as $point) {
            // Récupérer les informations de catégorie et sous-catégorie
            $categorieId = $point['categorie_id'];
            $categorieName = $point['categorie_nom'];
            $sousCategorieId = $point['sous_categorie_id'];
            $sousCategorieName = $point['sous_categorie_nom'];
            
            // Initialiser les compteurs si nécessaire pour cette catégorie
            if (!isset($categoriesStats[$categorieId])) {
                $categoriesStats[$categorieId] = [
                    'nom' => $categorieName,
                    'total' => 0,
                    'audite' => 0,
                    'non_audite' => 0,
                    'satisfait' => 0,
                    'non_satisfait' => 0,
                    'partiellement' => 0
                ];
            }
            
            // Initialiser les compteurs si nécessaire pour cette sous-catégorie
            if (!isset($sousCategories[$sousCategorieId])) {
                $sousCategories[$sousCategorieId] = [
                    'nom' => $sousCategorieName,
                    'categorie_nom' => $categorieName,
                    'total' => 0,
                    'audite' => 0,
                    'non_audite' => 0,
                    'satisfait' => 0,
                    'non_satisfait' => 0,
                    'partiellement' => 0
                ];
            }
            
            // Incrémenter les compteurs
            $categoriesStats[$categorieId]['total']++;
            $sousCategories[$sousCategorieId]['total']++;
            
            // Évaluation: audité ou non
            if (!empty($point['non_audite'])) {
                $categoriesStats[$categorieId]['audite']++;
                $sousCategories[$sousCategorieId]['audite']++;
            } else {
                $categoriesStats[$categorieId]['non_audite']++;
                $sousCategories[$sousCategorieId]['non_audite']++;
            }
            
            // Évaluation: satisfait, non satisfait, partiellement
            if (isset($point['resultat'])) {
                if ($point['resultat'] === 'satisfait') {
                    $categoriesStats[$categorieId]['satisfait']++;
                    $sousCategories[$sousCategorieId]['satisfait']++;
                } elseif ($point['resultat'] === 'non_satisfait') {
                    $categoriesStats[$categorieId]['non_satisfait']++;
                    $sousCategories[$sousCategorieId]['non_satisfait']++;
                } elseif ($point['resultat'] === 'partiellement') {
                    $categoriesStats[$categorieId]['partiellement']++;
                    $sousCategories[$sousCategorieId]['partiellement']++;
                }
            }
            
            // Collecter les plans d'action
            if (!empty($point['plan_action_description'])) {
                $plansAction[] = [
                    'point_vigilance_id' => $point['point_vigilance_id'],
                    'point_vigilance_nom' => $point['point_vigilance_nom'],
                    'categorie_nom' => $categorieName,
                    'sous_categorie_nom' => $sousCategorieName,
                    'numero' => $point['plan_action_numero'] ?? '',
                    'description' => $point['plan_action_description'],
                    'resultat' => $point['resultat'] ?? '',
                    'priorite' => $point['plan_action_priorite'] ?? ''
                ];
            }
        }
        
        // Calculer les pourcentages pour chaque catégorie
        foreach ($categoriesStats as $id => $categorie) {
            if ($categorie['total'] > 0) {
                $categoriesStats[$id]['pct_audite'] = round(($categorie['audite'] / $categorie['total']) * 100, 1);
                
                $totalEvalues = $categorie['satisfait'] + $categorie['non_satisfait'] + $categorie['partiellement'];
                if ($totalEvalues > 0) {
                    $categoriesStats[$id]['pct_satisfait'] = round(($categorie['satisfait'] / $totalEvalues) * 100, 1);
                    $categoriesStats[$id]['pct_non_satisfait'] = round(($categorie['non_satisfait'] / $totalEvalues) * 100, 1);
                    $categoriesStats[$id]['pct_partiellement'] = round(($categorie['partiellement'] / $totalEvalues) * 100, 1);
                }
            }
        }
        
        // Trier les plans d'action par numéro
        usort($plansAction, function($a, $b) {
            if (empty($a['numero']) && empty($b['numero'])) {
                return 0;
            }
            if (empty($a['numero'])) {
                return 1;
            }
            if (empty($b['numero'])) {
                return -1;
            }
            return $a['numero'] - $b['numero'];
        });
        
        // Vérifier si la librairie TCPDF est installée
        error_log('Vérification de la présence de la librairie TCPDF');
        if (!class_exists('\\TCPDF')) {
            error_log('La librairie TCPDF n\'est pas installée');
            // Si TCPDF n'est pas installé, on redirige vers la page de résumé avec un message d'erreur
            $_SESSION['error'] = "La librairie TCPDF n'est pas installée. Merci d'exécuter 'composer require tecnickcom/tcpdf' dans le répertoire du projet.";
            header('Location: index.php?action=audits&method=resume&id=' . $auditId);
            exit;
        }
        
        error_log('La librairie TCPDF est bien installée, création de l\'instance');
        try {
            // Créer une instance de TCPDF
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            error_log('Instance TCPDF créée avec succès');
            
            // Configuration du document
            $pdf->SetCreator('Audit App');
            $pdf->SetAuthor('Système Audit');
            $pdf->SetTitle('Résumé Audit ' . $audit['numero_site']);
            $pdf->SetSubject('Résumé de l\'audit');
            
            // Configuration des marges
            $pdf->setMargins(15, 15, 15);
            
            // Suppression des en-têtes et pieds de page par défaut
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Auto page break
            $pdf->setAutoPageBreak(true, 15);
            
            // Police par défaut
            $pdf->setFont('dejavusans', '', 10);
            
            // Ajouter une page
            $pdf->AddPage();
            
            // Commencer à capturer la sortie
            ob_start();
            
            // Inclure le template du PDF (qui est une version modifiée de resume.php)
            error_log('Inclusion du template resume_pdf.php');
            include_once __DIR__ . '/../views/audits/resume_pdf.php';
            
            // Récupérer le contenu HTML
            $html = ob_get_clean();
            error_log('Contenu HTML récupéré, taille: ' . strlen($html) . ' octets');
            
            // Ajouter le contenu HTML au PDF
            $pdf->writeHTML($html, true, false, true, false, '');
            error_log('HTML ajouté au PDF');
            
            // Définir le nom du fichier
            $filename = 'Audit_' . $audit['numero_site'] . '_' . date('Y-m-d') . '.pdf';
            error_log('Nom du fichier PDF: ' . $filename);
            
            // Envoyer le PDF au navigateur
            error_log('Envoi du PDF au navigateur...');
            $pdf->Output($filename, 'D');
            error_log('PDF envoyé avec succès');
            exit;
        } catch (\Exception $e) {
            error_log('Erreur lors de la génération du PDF: ' . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de la génération du PDF: " . $e->getMessage();
            header('Location: index.php?action=audits&method=resume&id=' . $auditId);
            exit;
        }
    }

    /**
     * Teste la structure de la table audit_points
     * 
     * @return void
     */
    public function testStructure() {
        header('Content-Type: application/json');
        
        try {
            $auditModel = new Audit();
            $result = $auditModel->debugTableStructure();
            
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        exit;
    }

    /**
     * Ajouter un document à un point de vigilance
     */
    public function ajouterDocument() {
        // Définir le type de contenu comme JSON
        header('Content-Type: application/json');
        
        // Initialiser la réponse
        $response = ['success' => false, 'message' => ''];

        try {
            // Vérifier la méthode de requête
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $response['message'] = 'Méthode non autorisée. Utilisez POST.';
                echo json_encode($response);
                exit;
            }

            // Vérifier les paramètres requis
            if (!isset($_POST['audit_id']) || !isset($_POST['point_vigilance_id'])) {
                $response['message'] = 'Paramètres manquants (audit_id, point_vigilance_id)';
                echo json_encode($response);
                exit;
            }

            $auditId = intval($_POST['audit_id']);
            $pointVigilanceId = intval($_POST['point_vigilance_id']);

            if (!$auditId || !$pointVigilanceId) {
                $response['message'] = 'Paramètres invalides (audit_id, point_vigilance_id)';
                echo json_encode($response);
                exit;
            }

            // Vérifier que l'audit existe et n'est pas terminé
            $audit = $this->auditModel->getById($auditId);
            if (!$audit) {
                $response['message'] = 'Audit non trouvé';
                echo json_encode($response);
                exit;
            }

            if ($audit['statut'] === 'completed') {
                $response['message'] = 'Impossible de modifier un audit complété';
                echo json_encode($response);
                exit;
            }

            // Vérifier que le fichier a été correctement téléchargé
            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = 'Erreur lors du téléchargement du fichier';
                if (isset($_FILES['document'])) {
                    switch ($_FILES['document']['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                            $errorMessage = 'Le fichier dépasse la taille maximale autorisée';
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $errorMessage = 'Le fichier dépasse la taille maximale autorisée par le formulaire';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errorMessage = 'Le fichier n\'a été que partiellement téléchargé';
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $errorMessage = 'Aucun fichier n\'a été téléchargé';
                            break;
                    }
                }
                $response['message'] = $errorMessage;
                echo json_encode($response);
                exit;
            }

            // Vérifier le type MIME du fichier
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $fileType = $finfo->file($_FILES['document']['tmp_name']);

            if (!in_array($fileType, $allowedTypes)) {
                $response['message'] = 'Type de fichier non autorisé. Utilisez PDF, DOC, DOCX, JPG, PNG ou GIF.';
                echo json_encode($response);
                exit;
            }

            // Ajouter le document
            $documentId = $this->auditModel->ajouterDocument(
                $auditId, 
                $pointVigilanceId, 
                $_FILES['document']['name'], 
                $_FILES['document']['tmp_name']
            );

            if (!$documentId) {
                $response['message'] = 'Erreur lors de l\'ajout du document';
                echo json_encode($response);
                exit;
            }

            // Récupérer les informations du document ajouté
            $documentInfo = $this->auditModel->getDocumentById($documentId);

            $response['success'] = true;
            $response['message'] = 'Document ajouté avec succès';
            $response['document'] = $documentInfo;

        } catch (Exception $e) {
            error_log("Erreur dans ajouterDocument: " . $e->getMessage());
            $response['message'] = 'Une erreur est survenue lors de l\'ajout du document: ' . $e->getMessage();
        }

        // S'assurer qu'aucun HTML ou autre contenu n'est envoyé avant le JSON
        if (ob_get_length()) {
            ob_clean();
        }

        echo json_encode($response);
        exit;
    }
} 