<?php
// modules/unlisted_devices/ajax/save_device_changes.php
header('Content-Type: application/json');
require_once '../../../config/database.php';
require_once '../../../config/session_fix.php';

// Restore session if needed
if (!isset($_SESSION['user_id'])) {
    restoreSession();
}

error_log("=== save_device_changes.php called ===");
error_log("POST data: " . print_r($_POST, true));

// Check authentication
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'it_staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['device_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$device_id = intval($_POST['device_id']);

// Check if device is already added to stock
$check_status = $pdo->prepare("SELECT status FROM submission_devices WHERE id = ?");
$check_status->execute([$device_id]);
$status = $check_status->fetchColumn();

if($status == 'added_to_stock') {
    echo json_encode(['success' => false, 'message' => 'Cannot edit device that has already been added to stock']);
    exit();
}

// Get existing item ID from POST
$existing_item_id = isset($_POST['existing_item_id']) && !empty($_POST['existing_item_id']) ? intval($_POST['existing_item_id']) : null;

error_log("Device ID: $device_id, Existing Item ID: " . ($existing_item_id ?: 'NULL'));

try {
    // First, verify if the existing_item_id actually exists in items table
    $item_details = null;
    if($existing_item_id) {
        $check_item = $pdo->prepare("SELECT id, name, item_code, category_id, sub_category_id, brand_id, price, type_id, warranty_period_months FROM items WHERE id = ? AND is_active = 1");
        $check_item->execute([$existing_item_id]);
        $item_details = $check_item->fetch();
        
        if(!$item_details) {
            error_log("Warning: Item ID $existing_item_id not found, clearing link");
            $existing_item_id = null;
        } else {
            error_log("Linking to existing item: {$item_details['item_code']} - {$item_details['name']}");
        }
    }
    
    // Get all POST values with proper defaults
    $device_name = isset($_POST['device_name']) ? trim($_POST['device_name']) : '';
    $brand_name = isset($_POST['brand_name']) ? trim($_POST['brand_name']) : '';
    $model_number = isset($_POST['model_number']) ? trim($_POST['model_number']) : '';
    $serial_number = isset($_POST['serial_number']) ? trim($_POST['serial_number']) : '';
    $specification = isset($_POST['specification']) ? trim($_POST['specification']) : '';
    $purchase_date = isset($_POST['purchase_date']) && !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $assigned_date = isset($_POST['assigned_date']) && !empty($_POST['assigned_date']) ? $_POST['assigned_date'] : null;
    $assigned_by = isset($_POST['assigned_by']) ? trim($_POST['assigned_by']) : '';
    $current_condition = isset($_POST['current_condition']) ? $_POST['current_condition'] : 'good';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $item_type_id = isset($_POST['item_type_id']) && !empty($_POST['item_type_id']) ? intval($_POST['item_type_id']) : null;
    $category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $sub_category_id = isset($_POST['sub_category_id']) && !empty($_POST['sub_category_id']) ? intval($_POST['sub_category_id']) : null;
    $brand_id = isset($_POST['brand_id']) && !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null;
    $warranty_months = isset($_POST['warranty_months']) && !empty($_POST['warranty_months']) ? intval($_POST['warranty_months']) : 12;
    $price = isset($_POST['price']) && !empty($_POST['price']) ? floatval($_POST['price']) : null;
    $quantity = isset($_POST['quantity']) && !empty($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Validate required fields
    if(empty($device_name)) {
        throw new Exception("Device name is required");
    }
    
    error_log("Updating device with: device_name=$device_name, category_id=$category_id, price=$price, quantity=$quantity");
    
    // Update the submission device
    $stmt = $pdo->prepare("
        UPDATE submission_devices SET
            device_name = ?,
            brand_name = ?,
            model_number = ?,
            serial_number = ?,
            specification = ?,
            purchase_date = ?,
            assigned_date = ?,
            assigned_by = ?,
            current_condition = ?,
            notes = ?,
            item_type_id = ?,
            final_category_id = ?,
            final_sub_category_id = ?,
            final_brand_id = ?,
            final_warranty_months = ?,
            final_price = ?,
            final_quantity = ?,
            new_item_id = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $device_name,
        $brand_name,
        $model_number,
        $serial_number,
        $specification,
        $purchase_date,
        $assigned_date,
        $assigned_by,
        $current_condition,
        $notes,
        $item_type_id,
        $category_id,
        $sub_category_id,
        $brand_id,
        $warranty_months,
        $price,
        $quantity,
        $existing_item_id,
        $device_id
    ]);
    
    if(!$result) {
        throw new Exception("Database update failed");
    }
    
    // Verify the update worked
    $verify = $pdo->prepare("SELECT new_item_id, final_category_id, final_sub_category_id, final_brand_id, final_price FROM submission_devices WHERE id = ?");
    $verify->execute([$device_id]);
    $saved = $verify->fetch();
    
    error_log("After save - new_item_id = " . ($saved['new_item_id'] ?: 'NULL'));
    
    // Build success message
    $message = "Device saved successfully";
    if($existing_item_id && $item_details) {
        $message = "Device linked to existing item: " . $item_details['item_code'] . " - " . $item_details['name'];
    }
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch(PDOException $e) {
    error_log("PDO Error in save_device_changes.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    error_log("Error in save_device_changes.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>