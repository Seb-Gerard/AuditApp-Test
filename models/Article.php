<?php
require_once __DIR__ . '/../config/database.php';

class Article {
    private $db;

    public function __construct() {
        $this->db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
    }

    public function create($article) {
        $sql = "INSERT INTO articles (title, content, created_at) VALUES (:title, :content, :created_at)";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':title' => $article['title'],
            ':content' => $article['content'],
            ':created_at' => $article['created_at']
        ]);
    }

    public function getAll() {
        $sql = "SELECT * FROM articles ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLastInsertId() {
        return $this->db->lastInsertId();
    }
} 