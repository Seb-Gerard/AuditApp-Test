<?php
require_once __DIR__ . '/../includes/Database.php';

class Article {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($title, $content) {
        try {
            $sql = "INSERT INTO articles (title, content, created_at) VALUES (:title, :content, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':title' => $title,
                ':content' => $content
            ]);
        } catch(PDOException $e) {
            die("Erreur lors de la création de l'article : " . $e->getMessage());
        }
    }

    public function getAll() {
        try {
            $sql = "SELECT * FROM articles ORDER BY created_at DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            die("Erreur lors de la récupération des articles : " . $e->getMessage());
        }
    }
} 