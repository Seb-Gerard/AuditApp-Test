<?php
/**
 * Autoloader personnalisé pour les dépendances
 */

// Vérifie si l'autoloader de Composer existe et le charge
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Fonction d'autoload pour les classes de TCPDF
spl_autoload_register(function ($className) {
    // Vérifier si c'est une classe TCPDF et que l'autoloader Composer n'est pas disponible
    if (strpos($className, 'TCPDF') === 0 && !file_exists(__DIR__ . '/../vendor/autoload.php')) {
        $_SESSION['error'] = "La bibliothèque TCPDF n'est pas installée. Veuillez exécuter 'composer require tecnickcom/tcpdf' dans le répertoire du projet.";
        header('Location: index.php?action=audits');
        exit;
    }
});

// Message dans les logs pour confirmer que l'autoloader est chargé
error_log('Autoloader personnalisé chargé'); 