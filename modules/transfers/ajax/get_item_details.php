<?php
require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

if($item_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT i.*, b.name as brand_name, c.name as category_name 
                                FROM items i 
                                LEFT JOIN brands b ON i.brand_id = b.id 
                                LEFT JOIN categories c ON i.category_id = c.id 
                                WHERE i.id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        
        if($item) {
            // Get available serial numbers for this item
            $stmt2 = $pdo->prepare("SELECT serial_number, model_number, version FROM item_serial_numbers WHERE item_id = ? AND (is_assigned = 0 OR is_assigned IS NULL)");
            $stmt2->execute([$item_id]);
            $serials = $stmt2->fetchAll();
            
            echo json_encode([
                'success' => true, 
                'item' => $item,
                'serials' => $serials
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
}
?>