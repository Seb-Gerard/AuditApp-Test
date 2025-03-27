<?php
require_once __DIR__ . '/../config/database.php';

class Categorie {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM sous_categories sc WHERE sc.categorie_id = c.id) as nb_sous_categories,
                   (SELECT COUNT(*) FROM points_vigilance pv 
                    JOIN sous_categories sc ON pv.sous_categorie_id = sc.id 
                    WHERE sc.categorie_id = c.id) as nb_points_vigilance
            FROM categories c 
            ORDER BY c.nom
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM sous_categories sc WHERE sc.categorie_id = c.id) as nb_sous_categories,
                   (SELECT COUNT(*) FROM points_vigilance pv 
                    JOIN sous_categories sc ON pv.sous_categorie_id = sc.id 
                    WHERE sc.categorie_id = c.id) as nb_points_vigilance
            FROM categories c 
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSousCategories($categorieId) {
        try {
            $stmt = $this->db->prepare("
                SELECT sc.*, 
                       c.nom as categorie_nom,
                       c.id as categorie_id,
                       (SELECT COUNT(*) FROM points_vigilance pv WHERE pv.sous_categorie_id = sc.id) as nb_points_vigilance
                FROM sous_categories sc 
                JOIN categories c ON sc.categorie_id = c.id 
                WHERE sc.categorie_id = ? 
                ORDER BY sc.nom
            ");
            $stmt->execute([$categorieId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($result === false) {
                throw new Exception("Erreur lors de la récupération des sous-catégories");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur SQL: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des sous-catégories");
        }
    }

    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            $query = "INSERT INTO categories (nom, description) VALUES (:nom, :description)";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
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
            throw new Exception("Erreur lors de la création de la catégorie");
        }
    }

    public function update($data) {
        try {
            $this->db->beginTransaction();
            
            $query = "UPDATE categories SET nom = :nom, description = :description WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
                'id' => $data['id'],
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
            throw new Exception("Erreur lors de la mise à jour de la catégorie");
        }
    }

    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // Vérifier s'il y a des sous-catégories liées
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM sous_categories WHERE categorie_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                throw new Exception("Impossible de supprimer la catégorie car elle contient des sous-catégories");
            }
            
            $query = "DELETE FROM categories WHERE id = :id";
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
            throw new Exception("Erreur lors de la suppression de la catégorie");
        }
    }
} 