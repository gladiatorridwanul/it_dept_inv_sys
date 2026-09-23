<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$action = $_POST['action'] ?? '';

if($action == 'add_item') {
    // Get form data
    $item_name = trim($_POST['item_name'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $brand_id = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $type_id = !empty($_POST['type_id']) ? (int)$_POST['type_id'] : null;
    $specification = trim($_POST['specification'] ?? '');
    $regular_price = floatval($_POST['regular_price'] ?? 0);
    $warranty_period = !empty($_POST['warranty_period']) ? (int)$_POST['warranty_period'] : 12;
    
    // NEW: Get quantity for stock
    $quantity = !empty($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Get serial numbers, model numbers, versions
    $serial_numbers = $_POST['serial_numbers'] ?? [];
    $model_numbers = $_POST['model_numbers'] ?? [];
    $versions = $_POST['versions'] ?? [];
    
    // Filter out empty serials
    $valid_serials = array_filter($serial_numbers, function($s) { return trim($s) !== ''; });
    $serial_count = count($valid_serials);
    
    // If serial count is less than quantity, adjust quantity to match serial count
    if($serial_count > 0 && $serial_count < $quantity) {
        $quantity = $serial_count;
    }
    // If no serials provided, quantity stays as is
    
    // Validation
    if(empty($item_name)) {
        echo json_encode(['success' => false, 'message' => 'Item name is required']);
        exit();
    }
    
    if($regular_price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid unit price is required']);
        exit();
    }
    
    if($quantity <= 0) {
        $quantity = 1;
    }
    
    // Generate item code
    $prefix = 'ITM-';
    try {
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(item_code, 5) AS UNSIGNED)) as max_num FROM items WHERE item_code LIKE ?");
        $stmt->execute([$prefix . '%']);
        $row = $stmt->fetch();
        $next = ($row['max_num'] ?? 0) + 1;
        $item_code = $prefix . str_pad($next, 6, '0', STR_PAD_LEFT);
    } catch(Exception $e) {
        $item_code = $prefix . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    $pdo->beginTransaction();
    
    try {
        // Insert into items table with current_qty = quantity
        $stmt = $pdo->prepare("
            INSERT INTO items (item_code, name, specification, category_id, brand_id, type_id, 
                              regular_price, price, warranty_period, min_qty, current_qty, 
                              is_active, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 1, ?)
        ");
        $stmt->execute([
            $item_code, $item_name, $specification, $category_id, $brand_id, $type_id,
            $regular_price, $regular_price, $warranty_period, $quantity, $_SESSION['user_id']
        ]);
        $item_id = $pdo->lastInsertId();
        
        // Insert serial numbers
        $serial_inserted = 0;
        foreach($serial_numbers as $index => $serial) {
            $serial = trim($serial);
            if(!empty($serial)) {
                $model = trim($model_numbers[$index] ?? '');
                $version = trim($versions[$index] ?? '');
                
                $stmt = $pdo->prepare("
                    INSERT INTO item_serial_numbers (item_id, serial_number, model_number, version, is_assigned) 
                    VALUES (?, ?, ?, ?, 0)
                ");
                $stmt->execute([$item_id, $serial, $model, $version]);
                $serial_inserted++;
            }
        }
        
        // If quantity > serial_count, add placeholder serials or just update quantity
        if($quantity > $serial_inserted) {
            $stmt = $pdo->prepare("UPDATE items SET current_qty = ? WHERE id = ?");
            $stmt->execute([$quantity, $item_id]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Item added successfully',
            'item_id' => $item_id,
            'item_code' => $item_code,
            'item_name' => $item_name,
            'price' => $regular_price,
            'quantity' => $quantity,
            'serial_count' => $serial_inserted
        ]);
        
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>