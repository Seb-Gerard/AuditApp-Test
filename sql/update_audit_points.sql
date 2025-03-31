-- Création de la table audit_points si elle n'existe pas
CREATE TABLE IF NOT EXISTS audit_points (
  audit_id INT NOT NULL,
  point_vigilance_id INT NOT NULL,
  categorie_id INT NOT NULL,
  sous_categorie_id INT NOT NULL,
  ordre INT NOT NULL DEFAULT 1,
  statut TINYINT(1) DEFAULT 0,
  commentaire TEXT,
  PRIMARY KEY (audit_id, point_vigilance_id),
  FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter la colonne categorie_id si elle n'existe pas déjà
ALTER TABLE audit_points ADD COLUMN IF NOT EXISTS categorie_id INT NOT NULL AFTER point_vigilance_id;

-- Ajouter la colonne sous_categorie_id si elle n'existe pas déjà
ALTER TABLE audit_points ADD COLUMN IF NOT EXISTS sous_categorie_id INT NOT NULL AFTER categorie_id;

-- Ajouter la colonne ordre si elle n'existe pas déjà
ALTER TABLE audit_points ADD COLUMN IF NOT EXISTS ordre INT NOT NULL DEFAULT 1 AFTER sous_categorie_id;

-- Ajouter la colonne statut si elle n'existe pas déjà
ALTER TABLE audit_points ADD COLUMN IF NOT EXISTS statut TINYINT(1) DEFAULT 0 AFTER ordre;

-- Ajouter la colonne commentaire si elle n'existe pas déjà
ALTER TABLE audit_points ADD COLUMN IF NOT EXISTS commentaire TEXT AFTER statut;

-- Mettre à jour les colonnes categorie_id et sous_categorie_id pour les enregistrements existants
UPDATE audit_points ap
JOIN points_vigilance pv ON ap.point_vigilance_id = pv.id
SET ap.categorie_id = pv.categorie_id, 
    ap.sous_categorie_id = pv.sous_categorie_id
WHERE 1=1; 