<?php
require_once __DIR__ . '/../config/database.php';

class Audit {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
        $sql = "INSERT INTO audits (
            numero_site, nom_entreprise, date_creation, statut
        ) VALUES (
            :numero_site, :nom_entreprise, :date_creation, :statut
        )";

        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([
            ':numero_site' => $data['numero_site'],
            ':nom_entreprise' => $data['nom_entreprise'],
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
                    ap.plan_action_description
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
                date_creation = :date_creation,
                statut = :statut,
                updated_at = NOW()
                WHERE id = :id";
                
        $stmt = $this->db->prepare($sql);
        
        $params = [
            ':id' => $id,
            ':numero_site' => $data['numero_site'],
            ':nom_entreprise' => $data['nom_entreprise'],
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
            // Vérifier les données actuelles avant la mise à jour
            $sql = "SELECT * FROM audit_points WHERE audit_id = :audit_id AND point_vigilance_id = :point_vigilance_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':audit_id' => $auditId,
                ':point_vigilance_id' => $pointVigilanceId
            ]);
            $currentData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Données actuelles avant mise à jour: " . json_encode($currentData));
            
            // Valider les valeurs reçues
            $mesureReglementaire = isset($data['mesure_reglementaire']) ? (int)$data['mesure_reglementaire'] : 0;
            $nonAudite = isset($data['non_audite']) ? (int)$data['non_audite'] : 0;
            $resultat = isset($data['resultat']) && !empty($data['resultat']) ? $data['resultat'] : null;
            $justification = isset($data['justification']) ? $data['justification'] : null;
            $planActionNumero = !empty($data['plan_action_numero']) ? (int)$data['plan_action_numero'] : null;
            $planActionDescription = isset($data['plan_action_description']) ? $data['plan_action_description'] : null;
            
            // Préparer la requête SQL de mise à jour
            $sql = "UPDATE audit_points SET 
                    mesure_reglementaire = :mesure_reglementaire,
                    mode_preuve = :mode_preuve,
                    non_audite = :non_audite,
                    resultat = :resultat,
                    justification = :justification,
                    plan_action_numero = :plan_action_numero,
                    plan_action_description = :plan_action_description
                    WHERE audit_id = :audit_id AND point_vigilance_id = :point_vigilance_id";
                    
            $stmt = $this->db->prepare($sql);
            
            $params = [
                ':audit_id' => $auditId,
                ':point_vigilance_id' => $pointVigilanceId,
                ':mesure_reglementaire' => $mesureReglementaire,
                ':mode_preuve' => $data['mode_preuve'] ?? null,
                ':non_audite' => $nonAudite,
                ':resultat' => $resultat,
                ':justification' => $justification,
                ':plan_action_numero' => $planActionNumero,
                ':plan_action_description' => $planActionDescription
            ];
            
            // Enregistrer le détail des données pour le débogage
            error_log("Mise à jour de l'évaluation pour audit_id=$auditId, point_vigilance_id=$pointVigilanceId");
            error_log("Paramètres de la requête: " . json_encode($params));
            
            // Exécuter la requête
            $result = $stmt->execute($params);
            
            if (!$result) {
                error_log("Erreur SQL: " . json_encode($stmt->errorInfo()));
                return false;
            }
            
            // Vérifier que la mise à jour a bien eu lieu
            $rowCount = $stmt->rowCount();
            error_log("Nombre de lignes affectées: $rowCount");
            
            if ($rowCount > 0) {
                // Récupérer les données après la mise à jour pour confirmation
                $sql = "SELECT * FROM audit_points WHERE audit_id = :audit_id AND point_vigilance_id = :point_vigilance_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':audit_id' => $auditId,
                    ':point_vigilance_id' => $pointVigilanceId
                ]);
                $updatedData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                error_log("Données après mise à jour: " . json_encode($updatedData));
                
                if ($currentData && $updatedData) {
                    // Comparer les valeurs avant/après pour non_audite
                    $oldNonAudite = isset($currentData['non_audite']) ? (int)$currentData['non_audite'] : 0;
                    $newNonAudite = isset($updatedData['non_audite']) ? (int)$updatedData['non_audite'] : 0;
                    
                    error_log("Valeur de non_audite avant: $oldNonAudite");
                    error_log("Valeur de non_audite après: $newNonAudite");
                    
                    if ($oldNonAudite !== $newNonAudite) {
                        error_log("La valeur de non_audite a bien été modifiée!");
                    } else {
                        error_log("La valeur de non_audite n'a pas changé.");
                    }
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Exception dans updateEvaluation: " . $e->getMessage());
            return false;
        }
    }
} 