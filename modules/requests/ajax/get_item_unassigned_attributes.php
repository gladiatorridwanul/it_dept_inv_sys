<?php
require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_POST['item_id']) || empty($_POST['item_id'])) {
    echo json_encode(['error' => 'Item ID is required']);
    exit();
}

$item_id = (int)$_POST['item_id'];

try {
    // Fetch unassigned serial numbers, model numbers, and versions from item_serial_numbers table
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            serial_number,
            model_number,
            version
        FROM item_serial_numbers 
        WHERE item_id = ? AND (is_assigned = 0 OR is_assigned IS NULL)
        ORDER BY serial_number
    ");
    $stmt->execute([$item_id]);
    $attributes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $serials = [];
    $models = [];
    $versions = [];
    
    foreach ($attributes as $attr) {
        if (!empty($attr['serial_number'])) {
            $serials[] = ['serial_number' => $attr['serial_number']];
        }
        if (!empty($attr['model_number'])) {
            $models[] = ['model_number' => $attr['model_number']];
        }
        if (!empty($attr['version'])) {
            $versions[] = ['version' => $attr['version']];
        }
    }
    
    // Remove duplicates
    $serials = array_unique($serials, SORT_REGULAR);
    $models = array_unique($models, SORT_REGULAR);
    $versions = array_unique($versions, SORT_REGULAR);
    
    // Also check items table for any missing attributes not in item_serial_numbers
    $stmt = $pdo->prepare("
        SELECT serial_number, model_number, version 
        FROM items 
        WHERE id = ? 
        AND (serial_number IS NOT NULL AND serial_number != '')
        AND (is_active = 1)
    ");
    $stmt->execute([$item_id]);
    $item_attrs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($item_attrs) {
        if (!empty($item_attrs['serial_number']) && !in_array(['serial_number' => $item_attrs['serial_number']], $serials)) {
            $serials[] = ['serial_number' => $item_attrs['serial_number']];
        }
        if (!empty($item_attrs['model_number']) && !in_array(['model_number' => $item_attrs['model_number']], $models)) {
            $models[] = ['model_number' => $item_attrs['model_number']];
        }
        if (!empty($item_attrs['version']) && !in_array(['version' => $item_attrs['version']], $versions)) {
            $versions[] = ['version' => $item_attrs['version']];
        }
    }
    
    echo json_encode([
        'success' => true,
        'serials' => $serials,
        'models' => $models,
        'versions' => $versions
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>