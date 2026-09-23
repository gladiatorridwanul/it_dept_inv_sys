<?php
// modules/items/ajax/save_item.php
// ============================================
// IMPORTANT: Session fix MUST be FIRST
// ============================================
require_once '../../../config/session_fix.php';

// Force session restore for AJAX
if (!isset($_SESSION['user_id'])) {
    if (restoreSession()) {
        error_log("AJAX save_item: Session restored");
    } else {
        // Session restore failed
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Session expired. Please refresh the page and try again.'
        ]);
        exit();
    }
}

// Now include auth
require_once '../../../includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'You must be logged in to add items.'
    ]);
    exit();
}

header('Content-Type: application/json');

// Get POST data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$brand_id = isset($_POST['brand_id']) && $_POST['brand_id'] !== '' ? (int)$_POST['brand_id'] : null;
$type_id = isset($_POST['type_id']) && $_POST['type_id'] !== '' ? (int)$_POST['type_id'] : null;
$category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
$sub_category_id = isset($_POST['sub_category_id']) && $_POST['sub_category_id'] !== '' ? (int)$_POST['sub_category_id'] : null;
$initial_qty = isset($_POST['initial_qty']) ? (int)$_POST['initial_qty'] : 1;
$min_qty = isset($_POST['min_qty']) ? (int)$_POST['min_qty'] : 5;
$warranty_period = isset($_POST['warranty_period']) ? (int)$_POST['warranty_period'] : 12;
$regular_price = isset($_POST['regular_price']) ? (float)$_POST['regular_price'] : 0;
$specification = isset($_POST['specification']) ? trim($_POST['specification']) : '';

// Decode JSON data
$vendors = isset($_POST['vendors']) ? json_decode($_POST['vendors'], true) : [];
$vendor_prices = isset($_POST['vendor_prices']) ? json_decode($_POST['vendor_prices'], true) : [];
$primary_vendor = isset($_POST['primary_vendor']) ? (int)$_POST['primary_vendor'] : 0;
$serial_numbers = isset($_POST['serial_numbers']) ? json_decode($_POST['serial_numbers'], true) : [];
$model_numbers = isset($_POST['model_numbers']) ? json_decode($_POST['model_numbers'], true) : [];
$versions = isset($_POST['versions']) ? json_decode($_POST['versions'], true) : [];

// Validate required fields
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Item name is required.']);
    exit();
}

if (empty($category_id)) {
    echo json_encode(['success' => false, 'message' => 'Category is required.']);
    exit();
}

if (empty($vendors) || count($vendors) == 0) {
    echo json_encode(['success' => false, 'message' => 'At least one vendor is required.']);
    exit();
}

if (empty($serial_numbers) || count($serial_numbers) == 0) {
    echo json_encode(['success' => false, 'message' => 'At least one serial number is required.']);
    exit();
}

if ($regular_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Price must be greater than 0.']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Generate item code
    $item_code = generateNumber('ITM-', 'items', 'item_code');
    
    // Insert item
    $stmt = $pdo->prepare("
        INSERT INTO items (
            item_code, name, specification, type_id, category_id, sub_category_id, 
            brand_id, price, regular_price, current_qty, min_qty, 
            warranty_period_months, created_by, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            ?, ?, NOW()
        )
    ");
    
    $stmt->execute([
        $item_code,
        $name,
        $specification,
        $type_id,
        $category_id,
        $sub_category_id,
        $brand_id,
        $regular_price, // price
        $regular_price, // regular_price
        $initial_qty, // current_qty
        $min_qty,
        $warranty_period,
        $_SESSION['user_id']
    ]);
    
    $item_id = $pdo->lastInsertId();
    
    // Insert vendors
    $vendor_stmt = $pdo->prepare("
        INSERT INTO item_vendors (item_id, vendor_id, purchase_price, is_primary) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($vendors as $index => $vendor_id) {
        $price = isset($vendor_prices[$index]) ? (float)$vendor_prices[$index] : 0;
        $is_primary = ($index == $primary_vendor) ? 1 : 0;
        $vendor_stmt->execute([$item_id, $vendor_id, $price, $is_primary]);
    }
    
    // Insert serial numbers
    $serial_stmt = $pdo->prepare("
        INSERT INTO item_serial_numbers (item_id, serial_number, model_number, version, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    foreach ($serial_numbers as $index => $serial) {
        $serial = trim($serial);
        if (!empty($serial)) {
            $model = isset($model_numbers[$index]) ? trim($model_numbers[$index]) : '';
            $version = isset($versions[$index]) ? trim($versions[$index]) : '';
            $serial_stmt->execute([$item_id, $serial, $model, $version]);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    error_log("Item added successfully by user: " . $_SESSION['user_id'] . " - Item ID: " . $item_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Item "' . htmlspecialchars($name) . '" has been added successfully!',
        'item_id' => $item_id,
        'item_code' => $item_code
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error adding item: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error adding item: ' . $e->getMessage()
    ]);
}
?>