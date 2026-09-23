<?php
require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

if(!isset($_POST['item_id'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'Item ID required',
        'serials' => [],
        'models' => [],
        'versions' => [],
        'has_serials' => false,
        'has_models' => false,
        'has_versions' => false
    ]);
    exit();
}

$item_id = intval($_POST['item_id']);

// Initialize arrays
$serials = [];
$models = [];
$versions = [];

// Check if item_serial_numbers table exists
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'item_serial_numbers'");
    $tableExists = $tableCheck->rowCount() > 0;
    
    if($tableExists) {
        // Get serial numbers from item_serial_numbers table
        $stmt = $pdo->prepare("
            SELECT serial_number, model_number, version 
            FROM item_serial_numbers 
            WHERE item_id = ? AND (is_assigned = 0 OR is_assigned IS NULL)
            ORDER BY serial_number
        ");
        $stmt->execute([$item_id]);
        $serials = $stmt->fetchAll();
        
        // Get unique model numbers
        $stmt = $pdo->prepare("
            SELECT DISTINCT model_number 
            FROM item_serial_numbers 
            WHERE item_id = ? AND model_number IS NOT NULL AND model_number != '' 
            ORDER BY model_number
        ");
        $stmt->execute([$item_id]);
        $models = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get unique versions
        $stmt = $pdo->prepare("
            SELECT DISTINCT version 
            FROM item_serial_numbers 
            WHERE item_id = ? AND version IS NOT NULL AND version != '' 
            ORDER BY version
        ");
        $stmt->execute([$item_id]);
        $versions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch(PDOException $e) {
    error_log("Error checking item_serial_numbers table: " . $e->getMessage());
}

// Get item info from items table
$itemInfo = [];
$stmt = $pdo->prepare("SELECT model_number, version, serial_number, name, item_code FROM items WHERE id = ?");
$stmt->execute([$item_id]);
$itemInfo = $stmt->fetch();

if($itemInfo) {
    // Add model from items table if not already present
    if($itemInfo['model_number'] && !in_array($itemInfo['model_number'], $models)) {
        $models[] = $itemInfo['model_number'];
    }
    
    // Add version from items table if not already present
    if($itemInfo['version'] && !in_array($itemInfo['version'], $versions)) {
        $versions[] = $itemInfo['version'];
    }
    
    // Add serial from items table if not already present
    if($itemInfo['serial_number']) {
        $serialExists = false;
        foreach($serials as $s) {
            if($s['serial_number'] == $itemInfo['serial_number']) {
                $serialExists = true;
                break;
            }
        }
        if(!$serialExists) {
            $serials[] = [
                'serial_number' => $itemInfo['serial_number'],
                'model_number' => $itemInfo['model_number'] ?? '',
                'version' => $itemInfo['version'] ?? ''
            ];
        }
    }
}

// Sort arrays
sort($models);
sort($versions);

// Remove duplicates
$models = array_unique($models);
$versions = array_unique($versions);

// Re-index arrays
$models = array_values($models);
$versions = array_values($versions);

echo json_encode([
    'success' => true,
    'serials' => $serials,
    'models' => $models,
    'versions' => $versions,
    'has_serials' => count($serials) > 0,
    'has_models' => count($models) > 0,
    'has_versions' => count($versions) > 0
]);
?>