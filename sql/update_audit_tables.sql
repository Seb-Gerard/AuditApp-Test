-- Mise à jour de la table audits pour correspondre au formulaire
ALTER TABLE audits
DROP COLUMN date_audit,
DROP COLUMN lieu,
DROP COLUMN auditeur,
DROP COLUMN type_audit,
DROP COLUMN contexte,
DROP COLUMN objectifs,
DROP COLUMN criteres,
DROP COLUMN methode,
DROP COLUMN ressources,
DROP COLUMN planification;

-- Ajout des nouveaux champs
ALTER TABLE audits
ADD COLUMN numero_site VARCHAR(255) NOT NULL,
ADD COLUMN nom_entreprise VARCHAR(255) NOT NULL,
ADD COLUMN date_creation DATE NOT NULL;

-- Vérifier si la table audit_points existe, sinon la créer
CREATE TABLE IF NOT EXISTS audit_points (
  id INT AUTO_INCREMENT PRIMARY KEY,
  audit_id INT NOT NULL,
  categorie_id INT NOT NULL,
  sous_categorie_id INT NOT NULL,
  point_vigilance_id INT NOT NULL,
  FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
  FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE,
  FOREIGN KEY (sous_categorie_id) REFERENCES sous_categories(id) ON DELETE CASCADE,
  FOREIGN KEY (point_vigilance_id) REFERENCES points_vigilance(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 

-- Ajout de la colonne statut à la table audits
ALTER TABLE audits
ADD COLUMN statut ENUM('en_cours', 'termine') NOT NULL DEFAULT 'en_cours'; 