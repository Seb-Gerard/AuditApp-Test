<?php
// Fichier de debug pour diagnostiquer les problèmes avec les requêtes AJAX
header('Content-Type: text/plain');

echo "=== Debugging Request Information ===\n\n";

// 1. Informations sur la requête
echo "REQUEST METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "\n";
echo "REQUEST URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "QUERY STRING: " . ($_SERVER['QUERY_STRING'] ?? 'N/A') . "\n\n";

// 2. Headers
echo "=== HTTP HEADERS ===\n";
foreach (getallheaders() as $name => $value) {
    echo "$name: $value\n";
}
echo "\n";

// 3. GET/POST data
echo "=== GET PARAMETERS ===\n";
foreach ($_GET as $key => $value) {
    echo "$key: " . (is_array($value) ? print_r($value, true) : $value) . "\n";
}
echo "\n";

echo "=== POST PARAMETERS ===\n";
foreach ($_POST as $key => $value) {
    echo "$key: " . (is_array($value) ? print_r($value, true) : $value) . "\n";
}
echo "\n";

// 4. Raw POST data
echo "=== RAW POST DATA ===\n";
$rawData = file_get_contents('php://input');
echo "Length: " . strlen($rawData) . " bytes\n";
echo "Content: $rawData\n\n";

// 5. Try to parse JSON
echo "=== JSON PARSE ATTEMPT ===\n";
if (!empty($rawData)) {
    $jsonData = json_decode($rawData, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Valid JSON detected:\n";
        print_r($jsonData);
    } else {
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
} else {
    echo "No data to parse\n";
}
echo "\n";

// 6. Session information
echo "=== SESSION ===\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
print_r($_SESSION);
echo "\n";

// 7. Server information
echo "=== SERVER ENVIRONMENT ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "\n";

// 8. Database connection test
echo "=== DATABASE CONNECTION TEST ===\n";
require_once __DIR__ . '/config/database.php';
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    echo "Database connection successful!\n";
    
    // Test de requête simple
    $stmt = $db->query("SELECT COUNT(*) FROM articles");
    $count = $stmt->fetchColumn();
    echo "Number of articles in database: $count\n";
} catch (PDOException $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== END OF DEBUG INFO ===\n"; 