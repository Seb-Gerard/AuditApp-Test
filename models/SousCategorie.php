<?php
require_once __DIR__ . '/../config/database.php';

class SousCategorie {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère toutes les sous-catégories
     * 
     * @return array Liste de toutes les sous-catégories
     */
    public function getAll() {
        try {
            error_log("Exécution de getAll() pour les sous-catégories");
            
            $stmt = $this->db->query("
                SELECT sc.*, 
                       c.nom as categorie_nom,
                       c.id as categorie_id,
                       (SELECT COUNT(*) FROM points_vigilance pv WHERE pv.sous_categorie_id = sc.id) as nb_points_vigilance
                FROM sous_categories sc 
                JOIN categories c ON sc.categorie_id = c.id 
                ORDER BY c.nom, sc.nom
            ");
            
            if (!$stmt) {
                error_log("Erreur lors de l'exécution de la requête getAll() pour les sous-catégories: " . implode(", ", $this->db->errorInfo()));
                return [];
            }
            
            $sousCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Nombre de sous-catégories récupérées: " . count($sousCategories));
            
            return $sousCategories;
        } catch (PDOException $e) {
            error_log("Erreur SQL dans getAll() des sous-catégories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère une sous-catégorie par son ID
     * 
     * @param int $id ID de la sous-catégorie
     * @return array|null Les données de la sous-catégorie ou null si non trouvée
     */
    public function getById($id) {
        try {
            // Valider l'ID
            $id = intval($id);
            if ($id <= 0) {
                error_log("ID de sous-catégorie invalide: " . $id);
                return null;
            }
            
            error_log("Récupération de la sous-catégorie avec ID: " . $id);
            
            $stmt = $this->db->prepare("
                SELECT sc.*, 
                       c.nom as categorie_nom,
                       c.id as categorie_id,
                       (SELECT COUNT(*) FROM points_vigilance pv WHERE pv.sous_categorie_id = sc.id) as nb_points_vigilance
                FROM sous_categories sc 
                JOIN categories c ON sc.categorie_id = c.id 
                WHERE sc.id = ?
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
            
            $sousCategorie = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sousCategorie) {
                error_log("Aucune sous-catégorie trouvée avec l'ID: " . $id);
                return null;
            }
            
            error_log("Sous-catégorie récupérée: " . $sousCategorie['nom']);
            return $sousCategorie;
        } catch (PDOException $e) {
            error_log("Erreur SQL dans getById: " . $e->getMessage());
            return null;
        } catch (Exception $e) {
            error_log("Exception dans getById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les points de vigilance associés à une sous-catégorie
     * 
     * @param int $sousCategorieId ID de la sous-catégorie
     * @return array Liste des points de vigilance
     */
    public function getPointsVigilance($sousCategorieId) {
        try {
            // Valider l'ID
            $sousCategorieId = intval($sousCategorieId);
            if ($sousCategorieId <= 0) {
                error_log("ID de sous-catégorie invalide pour getPointsVigilance: " . $sousCategorieId);
                return [];
            }
            
            error_log("Récupération des points de vigilance pour la sous-catégorie " . $sousCategorieId);
            
            $stmt = $this->db->prepare("
                SELECT pv.*, 
                       sc.id as sous_categorie_id, 
                       sc.nom as sous_categorie_nom,
                       c.id as categorie_id,
                       c.nom as categorie_nom
                FROM points_vigilance pv
                JOIN sous_categories sc ON pv.sous_categorie_id = sc.id
                JOIN categories c ON sc.categorie_id = c.id
                WHERE pv.sous_categorie_id = ?
                ORDER BY pv.id
            ");
            
            if (!$stmt) {
                error_log("Erreur de préparation de la requête SQL dans getPointsVigilance: " . implode(", ", $this->db->errorInfo()));
                return [];
            }
            
            $result = $stmt->execute([$sousCategorieId]);
            
            if ($result === false) {
                error_log("Erreur d'exécution de la requête SQL dans getPointsVigilance: " . implode(", ", $stmt->errorInfo()));
                return [];
            }
            
            $pointsVigilance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Nombre de points de vigilance trouvés: " . count($pointsVigilance));
            
            return $pointsVigilance;
        } catch (PDOException $e) {
            error_log("Erreur SQL dans getPointsVigilance: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Exception dans getPointsVigilance: " . $e->getMessage());
            return [];
        }
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