<?php
require_once __DIR__ . '/../config/database.php';

class Audit {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
        $sql = "INSERT INTO audits (
            date_audit, lieu, auditeur, type_audit, contexte, 
            objectifs, criteres, methode, ressources, planification
        ) VALUES (
            :date_audit, :lieu, :auditeur, :type_audit, :contexte,
            :objectifs, :criteres, :methode, :ressources, :planification
        )";

        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':date_audit' => $data['date_audit'],
            ':lieu' => $data['lieu'],
            ':auditeur' => $data['auditeur'],
            ':type_audit' => $data['type_audit'],
            ':contexte' => $data['contexte'],
            ':objectifs' => $data['objectifs'],
            ':criteres' => $data['criteres'],
            ':methode' => $data['methode'],
            ':ressources' => $data['ressources'],
            ':planification' => $data['planification']
        ]);
    }

    public function getAll() {
        $sql = "SELECT * FROM audits ORDER BY date_audit DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCount() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM audits");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
} 