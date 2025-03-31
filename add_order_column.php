<?php
// Script pour vérifier et ajouter la colonne ordre à la table audit_points

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Vérifier si la colonne existe déjà
    $checkSql = "SHOW COLUMNS FROM audit_points LIKE 'ordre'";
    $stmt = $db->prepare($checkSql);
    $stmt->execute();
    $hasOrderColumn = $stmt->rowCount() > 0;
    
    if (!$hasOrderColumn) {
        echo "La colonne 'ordre' n'existe pas dans la table audit_points. Ajout en cours...\n";
        
        // Ajouter la colonne ordre
        $alterSql = "ALTER TABLE audit_points ADD COLUMN ordre INT DEFAULT 0 AFTER sous_categorie_id";
        $db->exec($alterSql);
        
        // Mettre à jour l'ordre pour les enregistrements existants (par point_vigilance_id croissant)
        $updateSql = "SET @rank := 0; 
                     UPDATE audit_points 
                     SET ordre = (@rank := @rank + 1) 
                     WHERE audit_id = audit_id 
                     ORDER BY point_vigilance_id";
        $db->exec($updateSql);
        
        echo "Colonne 'ordre' ajoutée avec succès et valeurs par défaut attribuées.\n";
    } else {
        echo "La colonne 'ordre' existe déjà dans la table audit_points.\n";
    }
    
    echo "Structure actuelle de la table audit_points:\n";
    $descSql = "DESCRIBE audit_points";
    $stmt = $db->prepare($descSql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}";
        if ($column['Null'] === 'NO') echo ", NOT NULL";
        if ($column['Default'] !== null) echo ", DEFAULT {$column['Default']}";
        echo ")\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?> 