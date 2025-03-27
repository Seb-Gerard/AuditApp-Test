<?php
require_once __DIR__ . '/../config/database.php';

class PointVigilance {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->db->prepare("
            SELECT pv.*, 
                   sc.nom as sous_categorie_nom,
                   c.nom as categorie_nom,
                   sc.categorie_id,
                   (SELECT COUNT(*) FROM criteres cr WHERE cr.point_vigilance_id = pv.id) as nb_criteres,
                   (SELECT COUNT(*) FROM audit_points ap WHERE ap.point_vigilance_id = pv.id) as nb_audits
            FROM points_vigilance pv
            JOIN sous_categories sc ON pv.sous_categorie_id = sc.id
            JOIN categories c ON sc.categorie_id = c.id
            ORDER BY c.nom, sc.nom, pv.nom
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBySousCategorie($sousCategorieId) {
        $stmt = $this->db->prepare("
            SELECT pv.*, 
                   sc.nom as sous_categorie_nom,
                   c.nom as categorie_nom,
                   sc.categorie_id,
                   (SELECT COUNT(*) FROM criteres cr WHERE cr.point_vigilance_id = pv.id) as nb_criteres,
                   (SELECT COUNT(*) FROM audit_points ap WHERE ap.point_vigilance_id = pv.id) as nb_audits
            FROM points_vigilance pv
            JOIN sous_categories sc ON pv.sous_categorie_id = sc.id
            JOIN categories c ON sc.categorie_id = c.id
            WHERE pv.sous_categorie_id = ?
            ORDER BY pv.nom
        ");
        $stmt->execute([$sousCategorieId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT pv.*, 
                   sc.nom as sous_categorie_nom,
                   c.nom as categorie_nom,
                   sc.categorie_id,
                   (SELECT COUNT(*) FROM criteres cr WHERE cr.point_vigilance_id = pv.id) as nb_criteres,
                   (SELECT COUNT(*) FROM audit_points ap WHERE ap.point_vigilance_id = pv.id) as nb_audits
            FROM points_vigilance pv
            JOIN sous_categories sc ON pv.sous_categorie_id = sc.id
            JOIN categories c ON sc.categorie_id = c.id
            WHERE pv.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCriteres($pointVigilanceId) {
        $stmt = $this->db->prepare("SELECT * FROM criteres WHERE point_vigilance_id = ?");
        $stmt->execute([$pointVigilanceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            $query = "INSERT INTO points_vigilance (sous_categorie_id, nom, description) 
                     VALUES (:sous_categorie_id, :nom, :description)";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
                'sous_categorie_id' => $data['sous_categorie_id'],
                'nom' => $data['nom'],
                'description' => $data['description']
            ]);

            if ($success) {
                $this->db->commit();
                return $this->db->lastInsertId();
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur SQL: " . $e->getMessage());
            throw new Exception("Erreur lors de la création du point de vigilance");
        }
    }

    public function update($data) {
        try {
            $this->db->beginTransaction();
            
            $query = "UPDATE points_vigilance 
                     SET sous_categorie_id = :sous_categorie_id,
                         nom = :nom,
                         description = :description
                     WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
                'id' => $data['id'],
                'sous_categorie_id' => $data['sous_categorie_id'],
                'nom' => $data['nom'],
                'description' => $data['description']
            ]);

            if ($success) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur SQL: " . $e->getMessage());
            throw new Exception("Erreur lors de la mise à jour du point de vigilance");
        }
    }

    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // Vérifier s'il y a des critères ou des audits liés
            $stmt = $this->db->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM criteres WHERE point_vigilance_id = ?) as nb_criteres,
                    (SELECT COUNT(*) FROM audit_points WHERE point_vigilance_id = ?) as nb_audits
            ");
            $stmt->execute([$id, $id]);
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($counts['nb_criteres'] > 0 || $counts['nb_audits'] > 0) {
                throw new Exception("Impossible de supprimer le point de vigilance car il est utilisé dans des critères ou des audits");
            }
            
            $query = "DELETE FROM points_vigilance WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute(['id' => $id]);

            if ($success) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur SQL: " . $e->getMessage());
            throw new Exception("Erreur lors de la suppression du point de vigilance");
        }
    }
} 