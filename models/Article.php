<?php
require_once __DIR__ . '/../config/database.php';

class Article {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crée un nouvel article dans la base de données
     * 
     * @param array $data Données de l'article (title, content)
     * @return int|false ID du nouvel article ou false si erreur
     */
    public function create($data) {
        try {
            // Validation des données d'entrée
            if (empty($data['title']) || empty($data['content'])) {
                error_log("Tentative de création d'article avec des données incomplètes");
                return false;
            }
            
            $sql = "INSERT INTO articles (title, content, created_at) VALUES (:title, :content, NOW())";
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([
                ':title' => $data['title'],
                ':content' => $data['content']
            ]);
            
            // Si l'insertion a réussi, retourner l'ID du nouvel article
            if ($result) {
                $newId = $this->db->lastInsertId();
                error_log("Nouvel article créé avec succès, ID: " . $newId);
                return $newId;
            }
            
            error_log("Échec de l'insertion: " . implode(', ', $stmt->errorInfo()));
            return false;
        } catch (\PDOException $e) {
            error_log("Exception PDO lors de la création d'article: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            error_log("Exception générale lors de la création d'article: " . $e->getMessage());
            return false;
        }
    }

    public function getAll() {
        $sql = "SELECT id, title, content, created_at FROM articles ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        // Utiliser FETCH_ASSOC pour avoir des clés nommées
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les dates pour la cohérence
        foreach ($articles as &$article) {
            // Convertir la date au format ISO pour la cohérence avec les dates JavaScript
            if (isset($article['created_at'])) {
                $date = new DateTime($article['created_at']);
                $article['created_at'] = $date->format('c'); // Format ISO 8601
            }
        }
        
        return $articles;
    }

    /**
     * Récupère un article par son ID
     * 
     * @param int $id ID de l'article
     * @return array|false L'article ou false si non trouvé
     */
    public function findById($id) {
        try {
            $sql = "SELECT id, title, content, created_at FROM articles WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Formater la date pour la cohérence
            if ($article && isset($article['created_at'])) {
                $date = new DateTime($article['created_at']);
                $article['created_at'] = $date->format('c'); // Format ISO 8601
            }
            
            return $article ?: false;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération de l'article #$id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère un article par son ID (méthode alternative, pour compatibilité)
     * 
     * @param int $id ID de l'article
     * @return array|false L'article ou false si non trouvé
     */
    public function getById($id) {
        return $this->findById($id);
    }

    public function getCount() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM articles");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
} 