<?php
require_once __DIR__ . '/../config/database.php';

class SousCategorie {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->db->prepare("
            SELECT sc.*, 
                   c.nom as categorie_nom,
                   (SELECT COUNT(*) FROM points_vigilance pv WHERE pv.sous_categorie_id = sc.id) as nb_points_vigilance
            FROM sous_categories sc 
            JOIN categories c ON sc.categorie_id = c.id
            ORDER BY c.nom, sc.nom
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT sc.*, 
                   c.nom as categorie_nom,
                   (SELECT COUNT(*) FROM points_vigilance pv WHERE pv.sous_categorie_id = sc.id) as nb_points_vigilance
            FROM sous_categories sc 
            JOIN categories c ON sc.categorie_id = c.id
            WHERE sc.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPointsVigilance($sousCategorieId) {
        $stmt = $this->db->prepare("
            SELECT pv.*, 
                   sc.nom as sous_categorie_nom,
                   c.nom as categorie_nom,
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

    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            $query = "INSERT INTO sous_categories (categorie_id, nom, description) 
                     VALUES (:categorie_id, :nom, :description)";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
                'categorie_id' => $data['categorie_id'],
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
            throw new Exception("Erreur lors de la création de la sous-catégorie");
        }
    }

    public function update($data) {
        try {
            $this->db->beginTransaction();
            
            $query = "UPDATE sous_categories 
                     SET categorie_id = :categorie_id, 
                         nom = :nom, 
                         description = :description 
                     WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
                'id' => $data['id'],
                'categorie_id' => $data['categorie_id'],
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
            throw new Exception("Erreur lors de la mise à jour de la sous-catégorie");
        }
    }

    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // Vérifier s'il y a des points de vigilance liés
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM points_vigilance WHERE sous_categorie_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                throw new Exception("Impossible de supprimer la sous-catégorie car elle contient des points de vigilance");
            }
            
            // Vérifier si la sous-catégorie existe
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM sous_categories WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception("La sous-catégorie n'existe pas");
            }
            
            $query = "DELETE FROM sous_categories WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute(['id' => $id]);

            if ($success) {
                $this->db->commit();
                error_log("Sous-catégorie ID: " . $id . " supprimée avec succès");
                return true;
            } else {
                $this->db->rollBack();
                error_log("Échec de la suppression de la sous-catégorie ID: " . $id);
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur SQL lors de la suppression de la sous-catégorie ID: " . $id . " - " . $e->getMessage());
            throw new Exception("Erreur lors de la suppression de la sous-catégorie");
        }
    }
} 