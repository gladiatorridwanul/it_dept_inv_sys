<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

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
    $min_qty = !empty($_POST['min_qty']) ? (int)$_POST['min_qty'] : 1;
    $warranty_period = !empty($_POST['warranty_period']) ? (int)$_POST['warranty_period'] : 12;
    
    // For temporary storage, just return success with item data
    // The actual database insert will happen when the stock is saved
    
    if(empty($item_name)) {
        echo json_encode(['success' => false, 'message' => 'Item name is required']);
        exit();
    }
    
    if($regular_price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid unit price is required']);
        exit();
    }
    
    // Generate temporary item code for display
    $temp_item_code = 'NEW-' . time() . '-' . rand(1000, 9999);
    
    echo json_encode([
        'success' => true,
        'message' => 'Item ready to be added',
        'item_id' => 0, // Temporary ID
        'item_code' => $temp_item_code,
        'item_name' => $item_name,
        'price' => $regular_price
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>