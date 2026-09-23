<?php
require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_item') {
    $item_name = trim($_POST['item_name']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $brand_id = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $type_id = !empty($_POST['type_id']) ? (int)$_POST['type_id'] : null;
    $specification = trim($_POST['specification']);
    $regular_price = !empty($_POST['regular_price']) ? (float)$_POST['regular_price'] : null;
    $min_qty = !empty($_POST['min_qty']) ? (int)$_POST['min_qty'] : 1;
    
    $serial_numbers = $_POST['serial_numbers'] ?? [];
    $model_numbers = $_POST['model_numbers'] ?? [];
    $versions = $_POST['versions'] ?? [];
    
    if(empty($item_name)) {
        echo json_encode(['success' => false, 'message' => 'Item name is required']);
        exit();
    }
    
    if(!$category_id) {
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        exit();
    }
    
    try {
        // Generate item code
        $prefix = 'ITM';
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $cat = $stmt->fetch();
        if($cat) {
            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $cat['name']), 0, 3));
        }
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM items");
        $count = $stmt->fetch()['count'] + 1;
        $item_code = $prefix . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        
        $pdo->beginTransaction();
        
        // Insert item
        $stmt = $pdo->prepare("INSERT INTO items (item_code, name, category_id, brand_id, type_id, specification, regular_price, price, min_qty, current_qty, is_active, created_by, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, ?, NOW())");
        $stmt->execute([$item_code, $item_name, $category_id, $brand_id, $type_id, $specification, $regular_price, $regular_price, $min_qty, $_SESSION['user_id']]);
        
        $item_id = $pdo->lastInsertId();
        
        // Insert serial numbers, models, versions
        foreach($serial_numbers as $index => $serial) {
            if(!empty($serial)) {
                $model = $model_numbers[$index] ?? '';
                $version = $versions[$index] ?? '';
                
                $stmt = $pdo->prepare("INSERT INTO item_serial_numbers (item_id, serial_number, model_number, version, is_assigned, created_at) 
                                      VALUES (?, ?, ?, ?, 0, NOW())");
                $stmt->execute([$item_id, $serial, $model, $version]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'item_id' => $item_id,
            'item_code' => $item_code,
            'item_name' => $item_name,
            'price' => $regular_price
        ]);
        
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>