-- Création de la base de données
CREATE DATABASE IF NOT EXISTS audit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE audit;

-- Table des articles
CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des catégories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des sous-catégories
CREATE TABLE IF NOT EXISTS sous_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des points de vigilance
CREATE TABLE IF NOT EXISTS points_vigilance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sous_categorie_id INT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    FOREIGN KEY (sous_categorie_id) REFERENCES sous_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des critères
CREATE TABLE IF NOT EXISTS criteres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    point_vigilance_id INT,
    description TEXT NOT NULL,
    statut BOOLEAN DEFAULT FALSE,
    commentaire TEXT,
    FOREIGN KEY (point_vigilance_id) REFERENCES points_vigilance(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des audits
CREATE TABLE IF NOT EXISTS audits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_site VARCHAR(50) NOT NULL,
    nom_entreprise VARCHAR(255) NOT NULL,
    date_creation DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de liaison entre audits et points de vigilance
CREATE TABLE IF NOT EXISTS audit_points (
    audit_id INT,
    point_vigilance_id INT,
    ordre INT NOT NULL,
    statut BOOLEAN DEFAULT FALSE,
    commentaire TEXT,
    PRIMARY KEY (audit_id, point_vigilance_id),
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    FOREIGN KEY (point_vigilance_id) REFERENCES points_vigilance(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de test pour les catégories
INSERT INTO categories (nom, description) VALUES
('Gestion de la sûreté', 'Aspects généraux de la gestion de la sûreté'),
('Détection intrusion', 'Systèmes de détection d''intrusion'),
('Vidéo surveillance', 'Systèmes de vidéo surveillance'),
('Gestion des accès électroniques', 'Contrôle d''accès électronique'),
('Gestion des accès', 'Gestion générale des accès'),
('Food defense', 'Protection alimentaire'),
('Surveillance humaine', 'Surveillance par le personnel');

-- Données de test pour les sous-catégories
INSERT INTO sous_categories (categorie_id, nom, description) VALUES
(1, 'Politique de sûreté', 'Définition et mise en œuvre de la politique de sûreté'),
(1, 'Organisation', 'Structure organisationnelle de la sûreté'),
(2, 'Équipements', 'Équipements de détection d''intrusion'),
(2, 'Maintenance', 'Maintenance des systèmes de détection');

-- Données de test pour les points de vigilance
INSERT INTO points_vigilance (sous_categorie_id, nom, description) VALUES
(1, 'Documentation', 'Vérification de la documentation de sûreté'),
(1, 'Communication', 'Communication de la politique de sûreté'),
(2, 'Responsabilités', 'Définition des responsabilités'),
(2, 'Formation', 'Formation du personnel');
