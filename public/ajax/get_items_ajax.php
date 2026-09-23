<?php
require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, item_code, name, price FROM items WHERE is_active = 1 ORDER BY name");
        $items = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'items' => $items]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>