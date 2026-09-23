<?php
require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT i.id, i.item_code, i.name, i.regular_price as price, i.current_qty
        FROM items i
        WHERE i.is_active = 1
        ORDER BY i.name
    ");
    $stmt->execute();
    $items = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'items' => $items]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>