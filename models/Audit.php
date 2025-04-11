<?php
require_once __DIR__ . '/../config/database.php';

class Audit {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
        $sql = "INSERT INTO audits (
            numero_site, nom_entreprise, logo, date_creation, statut
        ) VALUES (
            :numero_site, :nom_entreprise, :logo, :date_creation, :statut
        )";

        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([
            ':numero_site' => $data['numero_site'],
            ':nom_entreprise' => $data['nom_entreprise'],
            ':logo' => $data['logo'],
            ':date_creation' => $data['date_creation'],
            ':statut' => isset($data['statut']) ? $data['statut'] : 'en_cours'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Enregistre les points de vigilance associés à un audit
     *
     * @param array $pointsData Tableau contenant les données des points à enregistrer
     * @return bool Succès ou échec de l'opération
     */
    public function saveAuditPoints($pointsData) {
        if (empty($pointsData)) {
            error_log("Aucun point de vigilance à enregistrer");
            return true; // Pas d'erreur, simplement rien à faire
        }

        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO audit_points (
                audit_id, point_vigilance_id, categorie_id, sous_categorie_id, ordre
            ) VALUES (
                :audit_id, :point_vigilance_id, :categorie_id, :sous_categorie_id, :ordre
            )";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($pointsData as $index => $point) {
                $result = $stmt->execute([
                    ':audit_id' => $point['audit_id'],
                    ':point_vigilance_id' => $point['point_vigilance_id'],
                    ':categorie_id' => $point['categorie_id'],
                    ':sous_categorie_id' => $point['sous_categorie_id'],
                    ':ordre' => $index + 1 // L'ordre est basé sur l'index (commençant à 1)
                ]);
                
                if (!$result) {
                    throw new Exception("Erreur lors de l'insertion d'un point: " . implode(", ", $stmt->errorInfo()));
                }
            }
            
            $this->db->commit();
            error_log("Points de vigilance enregistrés avec succès : " . count($pointsData));
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de l'enregistrement des points de vigilance: " . $e->getMessage());
            return false;
        }
    }

    public function getAll() {
        $sql = "SELECT * FROM audits ORDER BY date_creation DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCount() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM audits");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Compte le nombre d'audits par statut
     * 
     * @param string $status Le statut à compter (en_cours, termine, etc.)
     * @return int Le nombre d'audits avec le statut spécifié
     */
    public function getCountByStatus($status) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM audits WHERE statut = :status");
        $stmt->execute([':status' => $status]);
        return $stmt->fetchColumn();
    }

    public function getById($id) {
        $sql = "SELECT * FROM audits WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAuditPointsById($auditId) {
        try {
            // Requête adaptée à la structure réelle de la table audit_points
            $sql = "SELECT ap.*, 
                    c.nom as categorie_nom, 
                    sc.nom as sous_categorie_nom, 
                    pv.nom as point_vigilance_nom,
                    pv.description as point_vigilance_description,
                    pv.image as point_vigilance_image,
                    ap.mesure_reglementaire,
                    ap.mode_preuve,
                    ap.non_audite,
                    ap.resultat,
                    ap.justification,
                    ap.plan_action_numero,
                    ap.plan_action_description,
                    ap.plan_action_priorite
                    FROM audit_points ap
                    JOIN points_vigilance pv ON ap.point_vigilance_id = pv.id
                    JOIN categories c ON ap.categorie_id = c.id
                    JOIN sous_categories sc ON ap.sous_categorie_id = sc.id
                    WHERE ap.audit_id = :audit_id
                    ORDER BY ap.ordre ASC";
            
            error_log("Exécution de la requête pour récupérer les points de vigilance de l'audit #" . $auditId);
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':audit_id' => $auditId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Nombre de points de vigilance trouvés: " . count($result));
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur dans getAuditPointsById: " . $e->getMessage());
            // En cas d'erreur, retourner un tableau vide
            return [];
        }
    }

    public function getConnection() {
        return $this->db;
    }
    
    /**
     * Met à jour un audit existant
     *
     * @param int $id ID de l'audit à mettre à jour
     * @param array $data Données à mettre à jour
     * @return bool Succès ou échec de l'opération
     */
    public function update($id, $data) {
        $sql = "UPDATE audits SET 
                numero_site = :numero_site, 
                nom_entreprise = :nom_entreprise, 
                logo = :logo,
                date_creation = :date_creation,
                statut = :statut,
                updated_at = NOW()
                WHERE id = :id";
                
        $stmt = $this->db->prepare($sql);
        
        $params = [
            ':id' => $id,
            ':numero_site' => $data['numero_site'],
            ':nom_entreprise' => $data['nom_entreprise'],
            ':logo' => $data['logo'],
            ':date_creation' => $data['date_creation'],
            ':statut' => isset($data['statut']) ? $data['statut'] : 'en_cours'
        ];
        
        return $stmt->execute($params);
    }
    
    /**
     * Met à jour le statut d'un audit
     *
     * @param int $id ID de l'audit
     * @param string $statut Nouveau statut ('en_cours' ou 'termine')
     * @return bool Succès ou échec de l'opération
     */
    public function updateStatus($id, $statut) {
        $sql = "UPDATE audits SET 
                statut = :statut,
                updated_at = NOW()
                WHERE id = :id";
                
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':id' => $id,
            ':statut' => $statut
        ]);
    }
    
    /**
     * Supprime les points de vigilance associés à un audit
     *
     * @param int $auditId ID de l'audit
     * @return bool Succès ou échec de l'opération
     */
    public function deleteAuditPoints($auditId) {
        $sql = "DELETE FROM audit_points WHERE audit_id = :audit_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':audit_id' => $auditId]);
    }
    
    /**
     * Supprime un audit
     *
     * @param int $id ID de l'audit à supprimer
     * @return bool Succès ou échec de l'opération
     */
    public function delete($id) {
        $sql = "DELETE FROM audits WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Met à jour les données d'évaluation d'un point de vigilance pour un audit
     *
     * @param int $auditId ID de l'audit
     * @param int $pointVigilanceId ID du point de vigilance
     * @param array $data Données d'évaluation
     * @return bool Succès ou échec de l'opération
     */
    public function updateEvaluation($auditId, $pointVigilanceId, $data) {
        try {
            // Vérification de la connexion à la base de données
            if (!$this->db || !($this->db instanceof PDO)) {
                file_put_contents('logs/db_errors.log', date('Y-m-d H:i:s') . " - ERREUR: Connexion à la BDD non disponible\n", FILE_APPEND);
                return false;
            }
            
            // Journaliser tous les paramètres reçus pour le diagnostic
            file_put_contents('logs/audit_logs.log', date('Y-m-d H:i:s') . " - DÉBUT TRAITEMENT ÉVALUATION - audit:" . $auditId . ", point:" . $pointVigilanceId . "\n", FILE_APPEND);
            file_put_contents('logs/audit_logs.log', date('Y-m-d H:i:s') . " - DONNÉES REÇUES: " . print_r($data, true) . "\n", FILE_APPEND);
            
            // Traitement des checkboxes - conversion explicite en entiers
            // Important: utiliser la conversion explicite pour éviter les erreurs
            $mesureReglementaire = (isset($data['mesure_reglementaire']) && (int)$data['mesure_reglementaire'] === 1) ? 1 : 0;
            $nonAudite = (isset($data['non_audite']) && (int)$data['non_audite'] === 1) ? 1 : 0;
            
            // Pour les champs de texte, s'assurer qu'ils sont des chaînes non-null
            $modePreuve = isset($data['mode_preuve']) ? (string)$data['mode_preuve'] : '';
            $resultat = isset($data['resultat']) ? (string)$data['resultat'] : '';
            $justification = isset($data['justification']) ? (string)$data['justification'] : '';
            $planActionNumero = isset($data['plan_action_numero']) && !empty($data['plan_action_numero']) ? (int)$data['plan_action_numero'] : null;
            $planActionPriorite = isset($data['plan_action_priorite']) ? (string)$data['plan_action_priorite'] : '';
            $planActionDescription = isset($data['plan_action_description']) ? (string)$data['plan_action_description'] : '';
            
            file_put_contents('logs/audit_logs.log', date('Y-m-d H:i:s') . " - Valeurs traitées: mesure_reglementaire=$mesureReglementaire, non_audite=$nonAudite\n", FILE_APPEND);
            file_put_contents('logs/audit_logs.log', date('Y-m-d H:i:s') . " - Valeurs traitées: mode_preuve='$modePreuve', resultat='$resultat', justification='$justification'\n", FILE_APPEND);
            
            // 1. Vérifier l'existence du point
            $check = "SELECT COUNT(*) FROM audit_points WHERE audit_id = ? AND point_vigilance_id = ?";
            $stmt = $this->db->prepare($check);
            $stmt->execute([$auditId, $pointVigilanceId]);
            $exists = (int)$stmt->fetchColumn() > 0;
            
            file_put_contents('logs/audit_logs.log', date('Y-m-d H:i:s') . " - Le point existe: " . ($exists ? "OUI" : "NON") . "\n", FILE_APPEND);
            
            // Préparer les données pour la requête SQL
            $sqlData = [
                'audit_id' => $auditId,
                'point_vigilance_id' => $pointVigilanceId,
                'mesure_reglementaire' => $mesureReglementaire,
                'non_audite' => $nonAudite,
                'mode_preuve' => $modePreuve,
                'resultat' => $resultat,
                'justification' => $justification,
                'plan_action_numero' => $planActionNumero,
                'plan_action_priorite' => $planActionPriorite,
                'plan_action_description' => $planActionDescription
            ];
            
            // Log des données finales qui seront utilisées
            file_put_contents('logs/audit_logs.log', date('Y-m-d H:i:s') . " - DONNÉES SQL FINALES: " . print_r($sqlData, true) . "\n", FILE_APPEND);
            
            // 2. Insérer ou mettre à jour selon le cas
            if ($exists) {
                // Mettre à jour le point existant
                $sql = "UPDATE audit_points SET 
                        mesure_reglementaire = :mesure_reglementaire,
                        non_audite = :non_audite,
                        mode_preuve = :mode_preuve,
                        resultat = :resultat,
                        justification = :justification,
                        plan_action_numero = :plan_action_numero,
                        plan_action_priorite = :plan_action_priorite,
                        plan_action_description = :plan_action_description
                        WHERE audit_id = :audit_id AND point_vigilance_id = :point_vigilance_id";
            } else {
                // Insérer un nouveau point
                $sql = "INSERT INTO audit_points (
                            audit_id, point_vigilance_id, 
                            mesure_reglementaire, non_audite,
                            mode_preuve, resultat, justification,
                            plan_action_numero, plan_action_priorite, plan_action_description
                        ) VALUES (
                            :audit_id, :point_vigilance_id,
                            :mesure_reglementaire, :non_audite,
                            :mode_preuve, :resultat, :justification,
                            :plan_action_numero, :plan_action_priorite, :plan_action_description
                        )";
            }
                        
                $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($sqlData);
            
            file_put_contents('logs/audit_logs.log', date('Y-m-d H:i:s') . " - RÉSULTAT REQUÊTE: " . ($result ? "SUCCÈS" : "ÉCHEC") . "\n", FILE_APPEND);
            if (!$result) {
                file_put_contents('logs/audit_logs.log', date('Y-m-d H:i:s') . " - ERREUR SQL: " . print_r($stmt->errorInfo(), true) . "\n", FILE_APPEND);
            }
            
            return $result;
        } catch (Exception $e) {
            file_put_contents('logs/audit_logs.log', date('Y-m-d H:i:s') . " - EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }

    /**
     * Supprime un document lié à un audit
     *
     * @param int $documentId ID du document à supprimer
     * @return bool Succès ou échec de l'opération
     */
    public function supprimerDocument($documentId) {
        try {
            // Vérifier que le document existe
            $document = $this->getDocumentById($documentId);
            if (!$document) {
                error_log("Document non trouvé: ID = $documentId");
                    return false;
                }
                
            // Supprimer le document de la base de données
            $sql = "DELETE FROM audit_point_documents WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $documentId]);

            if ($result) {
                error_log("Document supprimé avec succès: ID = $documentId");
                return true;
            } else {
                error_log("Échec de la suppression du document: ID = $documentId");
                return false;
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la suppression du document: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère un document par son ID
     *
     * @param int $id ID du document
     * @return array|false Les informations du document ou false si non trouvé
     */
    public function getDocumentById($id) {
        try {
            $sql = "SELECT * FROM audit_point_documents WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du document: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajoute un document à un point de vigilance
     *
     * @param int $auditId ID de l'audit
     * @param int $pointVigilanceId ID du point de vigilance
     * @param string $titre Titre du document
     * @param string $cheminFichier Chemin du fichier
     * @return bool Succès ou échec de l'opération
     */
    public function ajouterDocument($auditId, $pointVigilanceId, $titre, $cheminFichier) {
        try {
            $sql = "INSERT INTO audit_point_documents (
                audit_id, point_vigilance_id, type, nom_fichier, chemin_fichier, date_ajout
            ) VALUES (
                :audit_id, :point_vigilance_id, 'document', :nom_fichier, :chemin_fichier, NOW()
            )";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':audit_id' => $auditId,
                ':point_vigilance_id' => $pointVigilanceId,
                ':nom_fichier' => $titre,
                ':chemin_fichier' => $cheminFichier
            ]);
            
            if ($result) {
                error_log("Document ajouté avec succès: audit_id=$auditId, point_vigilance_id=$pointVigilanceId");
                    return true;
                } else {
                error_log("Échec de l'ajout du document: audit_id=$auditId, point_vigilance_id=$pointVigilanceId");
                return false;
            }
        } catch (Exception $e) {
            error_log("Erreur lors de l'ajout du document: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fonction de diagnostic pour vérifier la structure de la table audit_points
     * 
     * @return array Informations sur la structure de la table
     */
    public function debugTableStructure() {
        try {
            // Vérifier que la table existe
            $checkTable = "SHOW TABLES LIKE 'audit_points'";
            $stmt = $this->db->prepare($checkTable);
            $stmt->execute();
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
                return [
                    'error' => true,
                    'message' => 'La table audit_points n\'existe pas'
                ];
            }
            
            // Récupérer la structure de la table
            $describeTable = "DESCRIBE audit_points";
            $stmt = $this->db->prepare($describeTable);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Tester une insertion simple
            $testInsert = "INSERT INTO audit_points_test (test_column) VALUES ('test') ON DUPLICATE KEY UPDATE test_column = 'test_updated'";
            try {
                $this->db->exec("CREATE TABLE IF NOT EXISTS audit_points_test (id INT AUTO_INCREMENT PRIMARY KEY, test_column VARCHAR(50))");
                $stmt = $this->db->prepare($testInsert);
                $testInsertResult = $stmt->execute();
                $this->db->exec("DROP TABLE audit_points_test");
            } catch (Exception $e) {
                $testInsertResult = false;
                $testInsertError = $e->getMessage();
            }
            
            return [
                'error' => false,
                'table_exists' => $tableExists,
                'columns' => $columns,
                'test_insert' => $testInsertResult ? 'success' : 'failed',
                'test_insert_error' => isset($testInsertError) ? $testInsertError : null,
                'db_connection' => $this->db ? 'connected' : 'not connected'
            ];
        } catch (Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
} 