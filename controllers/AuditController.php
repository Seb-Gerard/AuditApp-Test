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
        
        // Ajouter les documents et photos pour chaque point
        foreach ($pointsVigilance as $key => $point) {
            $photos = $this->auditDocumentModel->getDocumentsByPoint($auditId, $point['point_vigilance_id'], 'photo');
            $documents = $this->auditDocumentModel->getDocumentsByPoint($auditId, $point['point_vigilance_id'], 'document');
            
            $pointsVigilance[$key]['photos'] = $photos;
            $pointsVigilance[$key]['documents'] = $documents;
        }
        
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

    /**
     * Mettre à jour l'évaluation d'un point de vigilance pour un audit
     */
    public function evaluerPoint() {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];
        
        try {
            // Vérifier si la requête est de type POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée");
            }
            
            // Vérifier que les paramètres requis sont présents
            if (!isset($_POST['audit_id']) || !isset($_POST['point_vigilance_id'])) {
                throw new Exception("Paramètres manquants");
            }
            
            $auditId = intval($_POST['audit_id']);
            $pointVigilanceId = intval($_POST['point_vigilance_id']);
            
            // Récupérer l'état actuel pour comparer après mise à jour
            $auditPoints = $this->auditModel->getAuditPointsById($auditId);
            $currentPoint = null;
            foreach ($auditPoints as $point) {
                if ($point['point_vigilance_id'] == $pointVigilanceId) {
                    $currentPoint = $point;
                    break;
                }
            }
            
            error_log("État actuel pour le point $pointVigilanceId: " . ($currentPoint ? "non_audite = {$currentPoint['non_audite']}" : "non trouvé"));
            
            // Récupérer les données du formulaire
            // Pour les checkboxes, vérifier explicitement si elles sont présentes
            // et convertir les valeurs en entiers pour s'assurer du bon type
            $mesureReglementaire = isset($_POST['mesure_reglementaire']) && ($_POST['mesure_reglementaire'] == '1' || $_POST['mesure_reglementaire'] === true) ? 1 : 0;
            $nonAudite = isset($_POST['non_audite']) && ($_POST['non_audite'] == '1' || $_POST['non_audite'] === true) ? 1 : 0;
            
            error_log("Valeur reçue pour non_audite: " . (isset($_POST['non_audite']) ? $_POST['non_audite'] : "non défini"));
            error_log("Valeur traitée pour non_audite: $nonAudite");
            
            $data = [
                'mesure_reglementaire' => $mesureReglementaire,
                'mode_preuve' => $_POST['mode_preuve'] ?? null,
                'non_audite' => $nonAudite,
                'resultat' => $_POST['resultat'] ?? null,
                'justification' => $_POST['justification'] ?? null,
                'plan_action_numero' => !empty($_POST['plan_action_numero']) ? intval($_POST['plan_action_numero']) : null,
                'plan_action_description' => $_POST['plan_action_description'] ?? null
            ];
            
            // Journaliser les données pour le débogage
            error_log("Données reçues du formulaire: " . json_encode($_POST));
            error_log("Données traitées: " . json_encode($data));
            
            // Mettre à jour l'évaluation
            $success = $this->auditModel->updateEvaluation($auditId, $pointVigilanceId, $data);
            
            if (!$success) {
                throw new Exception("Erreur lors de la mise à jour de l'évaluation");
            }
            
            // Vérifier que la mise à jour a bien eu lieu
            $updatedPoints = $this->auditModel->getAuditPointsById($auditId);
            $updatedPoint = null;
            foreach ($updatedPoints as $point) {
                if ($point['point_vigilance_id'] == $pointVigilanceId) {
                    $updatedPoint = $point;
                    break;
                }
            }
            
            if ($updatedPoint) {
                error_log("État après mise à jour pour le point $pointVigilanceId: non_audite = {$updatedPoint['non_audite']}");
                if ($currentPoint && $currentPoint['non_audite'] != $updatedPoint['non_audite']) {
                    error_log("La valeur de non_audite a été modifiée: {$currentPoint['non_audite']} -> {$updatedPoint['non_audite']}");
                }
            }
            
            $response['success'] = true;
            $response['message'] = "Évaluation mise à jour avec succès";
            $response['data'] = $updatedPoint;
            
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
            error_log("Erreur dans evaluerPoint: " . $e->getMessage());
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Ajouter un document à un point d'audit
     */
    public function ajouterDocument() {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];
        
        try {
            // Vérifier si la requête est de type POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée");
            }
            
            // Vérifier que les paramètres requis sont présents
            if (!isset($_POST['audit_id']) || !isset($_POST['point_vigilance_id']) || !isset($_POST['type'])) {
                throw new Exception("Paramètres manquants");
            }
            
            // Vérifier qu'un fichier a été uploadé
            if (!isset($_FILES['document']) || $_FILES['document']['error'] != 0) {
                throw new Exception("Erreur lors du téléchargement du fichier");
            }
            
            $auditId = intval($_POST['audit_id']);
            $pointVigilanceId = intval($_POST['point_vigilance_id']);
            $type = $_POST['type'];
            
            // Vérifier le type
            if ($type !== 'document' && $type !== 'photo') {
                throw new Exception("Type de fichier non valide");
            }
            
            // Ajouter le document
            $documentId = $this->auditDocumentModel->ajouter($auditId, $pointVigilanceId, $type, $_FILES['document']);
            
            if (!$documentId) {
                throw new Exception("Erreur lors de l'ajout du document");
            }
            
            // Récupérer les informations du document
            $documents = $this->auditDocumentModel->getDocumentsByPoint($auditId, $pointVigilanceId, $type);
            $newDocument = null;
            
            foreach ($documents as $doc) {
                if ($doc['id'] == $documentId) {
                    $newDocument = $doc;
                    break;
                }
            }
            
            $response['success'] = true;
            $response['message'] = "Document ajouté avec succès";
            $response['document'] = $newDocument;
            
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Supprimer un document
     */
    public function supprimerDocument() {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];
        
        try {
            // Vérifier si la requête est de type POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée");
            }
            
            // Vérifier que l'ID du document est présent
            if (!isset($_POST['document_id'])) {
                throw new Exception("ID du document manquant");
            }
            
            $documentId = intval($_POST['document_id']);
            
            // Supprimer le document
            $success = $this->auditDocumentModel->supprimer($documentId);
            
            if (!$success) {
                throw new Exception("Erreur lors de la suppression du document");
            }
            
            $response['success'] = true;
            $response['message'] = "Document supprimé avec succès";
            
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Prendre une photo via la webcam
     */
    public function prendrePhoto() {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];
        
        try {
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
            $response['message'] = $e->getMessage();
        }
        
        error_log("Réponse finale: " . json_encode($response));
        echo json_encode($response);
        exit;
    }
} 