<?php
require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

if($item_id > 0) {
    $result = [];
    
    // Get item info
    $stmt = $pdo->prepare("SELECT id, item_code, name FROM items WHERE id = ?");
    $stmt->execute([$item_id]);
    $result['item'] = $stmt->fetch();
    
    // Get all serials for this item
    $stmt = $pdo->prepare("SELECT * FROM item_serial_numbers WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $result['all_serials'] = $stmt->fetchAll();
    
    // Get assigned serials only
    $stmt = $pdo->prepare("SELECT * FROM item_serial_numbers WHERE item_id = ? AND is_assigned = 1");
    $stmt->execute([$item_id]);
    $result['assigned_serials'] = $stmt->fetchAll();
    
    echo json_encode($result);
} else {
    echo json_encode(['error' => 'No item ID provided']);
}
?>