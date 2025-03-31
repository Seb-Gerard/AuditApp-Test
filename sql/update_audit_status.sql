-- Ajout de la colonne statut à la table audits si elle n'existe pas déjà
ALTER TABLE audits
ADD COLUMN IF NOT EXISTS statut ENUM('en_cours', 'termine') NOT NULL DEFAULT 'en_cours';

-- Mise à jour de tous les audits existants pour avoir le statut 'en_cours'
UPDATE audits SET statut = 'en_cours' WHERE statut IS NULL;

-- Mise à jour des autres tables
-- Vérifier si la table audit_points a les colonnes nécessaires
SHOW COLUMNS FROM audit_points LIKE 'statut';
SELECT @statut_exists := COUNT(*) FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'audit_points' AND column_name = 'statut';

-- Ajouter les colonnes manquantes si nécessaire
SELECT IF(@statut_exists < 1, 
    'ALTER TABLE audit_points ADD COLUMN statut TINYINT(1) DEFAULT 0 AFTER ordre', 
    'SELECT "La colonne statut existe déjà"') INTO @sql;
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter la colonne commentaire si elle n'existe pas
SHOW COLUMNS FROM audit_points LIKE 'commentaire';
SELECT @commentaire_exists := COUNT(*) FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'audit_points' AND column_name = 'commentaire';

SELECT IF(@commentaire_exists < 1, 
    'ALTER TABLE audit_points ADD COLUMN commentaire TEXT AFTER statut', 
    'SELECT "La colonne commentaire existe déjà"') INTO @sql;
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 