<?php
// modules/unlisted_devices/ajax/link_existing_item.php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/session_fix.php';

header('Content-Type: application/json');

error_log("=== link_existing_item.php called ===");
error_log("POST data: " . print_r($_POST, true));

// Restore session if needed
if (!isset($_SESSION['user_id'])) {
    restoreSession();
}

// Check authentication
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'it_staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$device_id = isset($_POST['device_id']) ? (int)$_POST['device_id'] : 0;
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

if($device_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid device ID']);
    exit();
}

try {
    if($item_id > 0) {
        // Get complete item details from items table
        $item_stmt = $pdo->prepare("
            SELECT i.*, 
                   c.id as category_id, c.name as category_name,
                   sc.id as sub_category_id, sc.name as sub_category_name,
                   b.id as brand_id, b.name as brand_name,
                   it.id as type_id, it.name as type_name
            FROM items i
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN categories sc ON i.sub_category_id = sc.id
            LEFT JOIN brands b ON i.brand_id = b.id
            LEFT JOIN item_types it ON i.type_id = it.id
            WHERE i.id = ? AND i.is_active = 1
        ");
        $item_stmt->execute([$item_id]);
        $item = $item_stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            exit();
        }
        
        error_log("Found item: " . print_r($item, true));
        
        // Update the submission device with the linked item and all its details
        $update = $pdo->prepare("
            UPDATE submission_devices 
            SET new_item_id = ?,
                item_type_id = ?,
                final_category_id = ?,
                final_sub_category_id = ?,
                final_brand_id = ?,
                final_price = ?,
                final_quantity = 1,
                final_warranty_months = ?
            WHERE id = ?
        ");
        $update->execute([
            $item['id'],
            $item['type_id'],
            $item['category_id'],
            $item['sub_category_id'],
            $item['brand_id'],
            $item['price'],
            $item['warranty_period_months'] ?? 12,
            $device_id
        ]);
        
        error_log("Device updated successfully. Device ID: $device_id, Item ID: $item_id");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Device linked to: ' . $item['name'] . ' (' . $item['item_code'] . ')',
            'data' => [
                'item_id' => $item['id'],
                'item_name' => $item['name'],
                'item_code' => $item['item_code'],
                'category_id' => $item['category_id'],
                'category_name' => $item['category_name'],
                'sub_category_id' => $item['sub_category_id'],
                'sub_category_name' => $item['sub_category_name'],
                'brand_id' => $item['brand_id'],
                'brand_name' => $item['brand_name'],
                'type_id' => $item['type_id'],
                'type_name' => $item['type_name'],
                'price' => $item['price'],
                'warranty_months' => $item['warranty_period_months'] ?? 12
            ]
        ]);
    } else {
        // Remove the link
        $update = $pdo->prepare("
            UPDATE submission_devices 
            SET new_item_id = NULL
            WHERE id = ?
        ");
        $update->execute([$device_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Device link removed',
            'data' => null
        ]);
    }
} catch(Exception $e) {
    error_log("Error in link_existing_item.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>