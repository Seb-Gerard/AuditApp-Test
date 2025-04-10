# Application d'Audit

## Description

Application web de gestion d'audits permettant de :

- Créer et gérer des audits
- Générer des rapports PDF
- Gérer les points de vigilance
- Suivre les actions correctives
- Gérer les utilisateurs et leurs droits

## Technologies utilisées

- PHP 8.2
- MySQL 8.0
- HTML5/CSS3
- JavaScript/jQuery
- Bootstrap 5
- TCPDF (pour l'export PDF)

## Prérequis

- PHP 8.2 ou supérieur
- MySQL 8.0 ou supérieur
- Composer
- Serveur web (Apache/Nginx)
- Extension PHP GD ou Imagick (requise pour TCPDF)

## Installation

1. Cloner le dépôt
2. Installer les dépendances avec Composer :

```bash
composer install
```

3. Configurer la base de données dans `config/database.php`
4. Importer le schéma de la base de données depuis `database/schema.sql`
5. Configurer les droits d'accès aux dossiers :

```bash
chmod 777 uploads/
chmod 777 tmp/
```

## Configuration

### Extensions PHP requises

L'application nécessite l'extension PHP GD ou Imagick pour générer des PDF avec TCPDF. Si vous rencontrez une erreur lors de l'export PDF, assurez-vous que l'une de ces extensions est activée :

#### Pour XAMPP (Windows) :

1. Ouvrez le fichier `C:\xampp\php\php.ini`
2. Recherchez la ligne `;extension=gd`
3. Supprimez le point-virgule au début de la ligne pour la décommenter
4. Sauvegardez le fichier
5. Redémarrez le serveur Apache

#### Pour Linux :

```bash
sudo apt-get install php-gd
sudo service apache2 restart
```

### TCPDF

L'application utilise la bibliothèque TCPDF pour générer des PDF. Si vous rencontrez une erreur lors de l'export PDF, assurez-vous que TCPDF est correctement installé :

```bash
composer require tecnickcom/tcpdf
```

## Structure du projet

```
├── config/             # Configuration de l'application
├── controllers/        # Contrôleurs de l'application
├── models/            # Modèles de données
├── views/             # Vues et templates
├── public/            # Fichiers publics (CSS, JS, images)
├── uploads/           # Dossier pour les fichiers uploadés
├── tmp/              # Dossier temporaire
└── vendor/           # Dépendances (géré par Composer)
```

## Fonctionnalités principales

- Gestion des audits (création, modification, suppression)
- Génération de rapports PDF
- Gestion des points de vigilance
- Suivi des actions correctives
- Gestion des utilisateurs et des droits d'accès
- Interface responsive

## Sécurité

- Protection contre les injections SQL
- Validation des entrées utilisateur
- Gestion des sessions sécurisée
- Protection CSRF
- Hachage des mots de passe

## Maintenance

- Les logs sont stockés dans `logs/`
- Les sauvegardes de la base de données dans `backups/`
- Les fichiers temporaires dans `tmp/`

## Contribution

1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Créer une Pull Request

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.
