# Application d'Audit

Cette application permet de gérer des audits, d'évaluer des points de vigilance et de générer des rapports.

## Installation

1. Clonez ce dépôt dans votre environnement de développement
2. Installez les dépendances via Composer :

```bash
composer install
```

### Dépendances principales

- PHP 7.4 ou supérieur
- MySQL/MariaDB
- Composer
- mPDF (pour l'export PDF)

## Fonctionnalités

- Création et gestion d'audits
- Évaluation de points de vigilance
- Génération de résumés et de statistiques
- Export PDF des résumés d'audit
- Gestion des photos et documents associés aux points de vigilance

## Export PDF

L'application utilise la bibliothèque mPDF pour générer des PDF. Si vous rencontrez une erreur lors de l'export PDF, assurez-vous que mPDF est correctement installé :

```bash
composer require mpdf/mpdf
```

## Utilisation

1. Créez un nouvel audit
2. Sélectionnez les points de vigilance à évaluer
3. Évaluez chaque point (Audité/Non audité, Satisfait/Non satisfait)
4. Ajoutez des plans d'action si nécessaire
5. Consultez le résumé de l'audit
6. Exportez le résumé en PDF si besoin
