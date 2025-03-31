-- Ajout de la colonne statut à la table audits
ALTER TABLE audits
ADD COLUMN IF NOT EXISTS statut ENUM('en_cours', 'termine') NOT NULL DEFAULT 'en_cours';

-- Mise à jour de tous les audits existants pour avoir le statut 'en_cours'
UPDATE audits SET statut = 'en_cours' WHERE statut IS NULL; 