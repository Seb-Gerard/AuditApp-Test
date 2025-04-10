-- Script de configuration complet pour la base de données
-- Créé le 01/04/2024
-- Ce fichier contient toutes les instructions nécessaires pour créer et configurer la base de données

-- Création et sélection de la base de données
DROP DATABASE IF EXISTS audit;
CREATE DATABASE audit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE audit;

-- Création des tables principales

-- Table des catégories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des sous-catégories
CREATE TABLE sous_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des points de vigilance
CREATE TABLE points_vigilance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sous_categorie_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    FOREIGN KEY (sous_categorie_id) REFERENCES sous_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des audits
CREATE TABLE audits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_site VARCHAR(50) NOT NULL,
    nom_entreprise VARCHAR(255) NOT NULL,
    date_creation DATE NOT NULL,
    logo VARCHAR(255),
    statut ENUM('en_cours', 'termine') DEFAULT 'en_cours',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de liaison entre audits et points de vigilance
CREATE TABLE audit_points (
    audit_id INT NOT NULL,
    point_vigilance_id INT NOT NULL,
    categorie_id INT NOT NULL,
    sous_categorie_id INT NOT NULL,
    ordre INT NOT NULL DEFAULT 1,
    statut TINYINT(1) DEFAULT 0,
    commentaire TEXT,
    mesure_reglementaire TINYINT(1) DEFAULT 0,
    mode_preuve TEXT,
    non_audite TINYINT(1) DEFAULT 0,
    resultat ENUM('satisfait', 'non_satisfait') DEFAULT NULL,
    justification TEXT,
    plan_action_numero INT DEFAULT NULL,
    plan_action_description TEXT,
    PRIMARY KEY (audit_id, point_vigilance_id),
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    FOREIGN KEY (point_vigilance_id) REFERENCES points_vigilance(id) ON DELETE CASCADE,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (sous_categorie_id) REFERENCES sous_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour stocker les documents et photos liés aux points d'audit
CREATE TABLE audit_point_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    point_vigilance_id INT NOT NULL,
    type ENUM('photo', 'document') NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(255) NOT NULL,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id, point_vigilance_id) REFERENCES audit_points(audit_id, point_vigilance_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des critères
CREATE TABLE criteres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    point_vigilance_id INT NOT NULL,
    description TEXT NOT NULL,
    statut TINYINT(1) DEFAULT 0,
    commentaire TEXT,
    FOREIGN KEY (point_vigilance_id) REFERENCES points_vigilance(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des articles (si nécessaire)
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des données initiales

-- Catégories
INSERT INTO categories (nom, description) VALUES
('Gestion de la sûreté', 'Aspects généraux de la gestion de la sûreté'),
('Détection intrusion', 'Systèmes de détection d''intrusion'),
('Vidéo surveillance', 'Systèmes de vidéo surveillance'),
('Gestion des accès électroniques', 'Contrôle d''accès électronique'),
('Gestion des accès', 'Gestion générale des accès'),
('Food defense', 'Protection alimentaire'),
('Surveillance humaine', 'Surveillance par le personnel');

-- Sous-catégories
INSERT INTO sous_categories (categorie_id, nom, description) VALUES
(1, 'Politique de sûreté', 'Définition et mise en œuvre de la politique de sûreté'),
(1, 'Organisation', 'Structure organisationnelle de la sûreté'),
(2, 'Équipements', 'Équipements de détection d''intrusion'),
(2, 'Maintenance', 'Maintenance des systèmes de détection'),
(3, 'Caméras', 'Installation et configuration des caméras'),
(3, 'Enregistrement', 'Systèmes d''enregistrement vidéo'),
(4, 'Badges', 'Gestion des badges d''accès'),
(4, 'Contrôleurs', 'Contrôleurs d''accès électroniques');

-- Points de vigilance
INSERT INTO points_vigilance (sous_categorie_id, nom, description) VALUES
(1, 'Documentation', 'Vérification de la documentation de sûreté'),
(1, 'Communication', 'Communication de la politique de sûreté'),
(2, 'Responsabilités', 'Définition des responsabilités'),
(2, 'Formation', 'Formation du personnel'),
(3, 'Couverture', 'Couverture des zones sensibles'),
(3, 'Qualité', 'Qualité des équipements'),
(4, 'Contrats', 'Contrats de maintenance'),
(4, 'Tests', 'Tests périodiques des équipements'); 