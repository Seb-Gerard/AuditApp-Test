CREATE TABLE IF NOT EXISTS audits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_audit DATE NOT NULL,
    lieu VARCHAR(255) NOT NULL,
    auditeur VARCHAR(255) NOT NULL,
    type_audit ENUM('interne', 'externe', 'certification', 'surveillance') NOT NULL,
    contexte TEXT NOT NULL,
    objectifs TEXT NOT NULL,
    criteres TEXT NOT NULL,
    methode TEXT NOT NULL,
    ressources TEXT NOT NULL,
    planification TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 