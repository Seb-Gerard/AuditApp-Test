<?php
class Database {
    private static $instance = null;
    private $connection = null;

    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=localhost;dbname=auditapp;charset=utf8mb4",
                "root",
                "",
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
} 