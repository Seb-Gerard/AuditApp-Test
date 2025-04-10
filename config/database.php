<?php
class Database {
    private static $instance = null;
    private $connection = null;

    private function __construct() {
        try {
            // Créer le dossier de logs s'il n'existe pas
            $logDir = __DIR__ . '/../logs';
            if (!file_exists($logDir)) {
                mkdir($logDir, 0777, true);
            }
            
            // Journaliser l'initialisation
            error_log("Initialisation de la connexion à la base de données", 3, $logDir . "/db_errors.log");
            
            $this->connection = new PDO(
                "mysql:host=localhost;dbname=audit;charset=utf8mb4",
                "root",
                "",
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
            
            error_log("Connexion à la base de données établie avec succès", 3, $logDir . "/db_errors.log");
        } catch (PDOException $e) {
            error_log("Erreur de connexion à la base de données: " . $e->getMessage(), 3, __DIR__ . '/../logs/db_errors.log');
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
    
    /**
     * Journalise une erreur de base de données
     * 
     * @param string $message Le message d'erreur
     * @param array $context Contexte supplémentaire (requête, paramètres, etc.)
     * @return void
     */
    public static function logError($message, array $context = []) {
        $logDir = __DIR__ . '/../logs';
        $log = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
        
        if (!empty($context)) {
            $log .= "Contexte: " . print_r($context, true) . "\n";
        }
        
        $log .= "--------------------------------------------\n";
        
        error_log($log, 3, $logDir . "/db_errors.log");
    }
} 