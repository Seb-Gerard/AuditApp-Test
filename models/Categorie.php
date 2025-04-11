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

    /**
     * Récupère une catégorie par son ID
     * 
     * @param int $id L'ID de la catégorie à récupérer
     * @return array|null Les données de la catégorie ou null si non trouvée
     */
    public function getById($id) {
        try {
            // Valider l'ID
            $id = intval($id);
            if ($id <= 0) {
                error_log("ID de catégorie invalide pour getById: " . $id);
                return null;
            }
            
            error_log("Exécution de getById pour la catégorie avec ID: " . $id);
            
            $stmt = $this->db->prepare("
                SELECT c.*, 
                      (SELECT COUNT(*) FROM sous_categories sc WHERE sc.categorie_id = c.id) as nb_sous_categories
                FROM categories c 
                WHERE c.id = ?
            ");
            
            if (!$stmt) {
                error_log("Erreur de préparation de la requête SQL dans getById: " . implode(", ", $this->db->errorInfo()));
                return null;
            }
            
            $result = $stmt->execute([$id]);
            
            if ($result === false) {
                error_log("Erreur d'exécution de la requête SQL dans getById: " . implode(", ", $stmt->errorInfo()));
                return null;
            }
            
            $categorie = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$categorie) {
                error_log("Aucune catégorie trouvée avec l'ID: " . $id);
                return null;
            }
            
            error_log("Catégorie récupérée avec succès, ID: " . $id . ", Nom: " . $categorie['nom']);
            return $categorie;
            
        } catch (PDOException $e) {
            error_log("Erreur SQL dans getById: " . $e->getMessage());
            return null;
        } catch (Exception $e) {
            error_log("Exception générale dans getById: " . $e->getMessage());
            return null;
        }
    }

    public function getSousCategories($categorieId) {
        try {
            // Valider l'ID de la catégorie
            $categorieId = intval($categorieId);
            if ($categorieId <= 0) {
                error_log("ID de catégorie invalide: " . $categorieId);
                return [];
            }
            
            error_log("Exécution de getSousCategories avec ID: " . $categorieId);
            
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
            
            if (!$stmt) {
                error_log("Erreur de préparation de la requête SQL: " . implode(", ", $this->db->errorInfo()));
                return [];
            }
            
            $result = $stmt->execute([$categorieId]);
            
            if ($result === false) {
                error_log("Erreur d'exécution de la requête SQL: " . implode(", ", $stmt->errorInfo()));
                return [];
            }
            
            $sousCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // S'assurer que le résultat est un tableau
            if (!is_array($sousCategories)) {
                error_log("Résultat non attendu dans getSousCategories: " . var_export($sousCategories, true));
                return [];
            }
            
            error_log("Nombre de sous-catégories trouvées pour ID " . $categorieId . ": " . count($sousCategories));
            return $sousCategories;
            
        } catch (PDOException $e) {
            error_log("Erreur SQL dans getSousCategories: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des sous-catégories");
        } catch (Exception $e) {
            error_log("Exception générale dans getSousCategories: " . $e->getMessage());
            throw $e;
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