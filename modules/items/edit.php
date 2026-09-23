<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../includes/auth.php';

// ============================================
// HANDLE POST REQUEST BEFORE ANY HTML OUTPUT
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    $name = $_POST['name'] ?? '';
    $brand_id = !empty($_POST['brand_id']) ? $_POST['brand_id'] : NULL;
    $type_id = !empty($_POST['type_id']) ? $_POST['type_id'] : NULL;
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : NULL;
    $sub_category_id = !empty($_POST['sub_category_id']) ? $_POST['sub_category_id'] : NULL;
    $warranty_period = $_POST['warranty_period'] ?? 0;
    $new_price = $_POST['regular_price'] ?? 0;
    $specification = $_POST['specification'] ?? NULL;
    $min_qty = $_POST['min_qty'] ?? 5;
    $price_change_reason = $_POST['price_change_reason'] ?? NULL;
    $selected_vendors = $_POST['vendors'] ?? [];
    $vendor_prices = $_POST['vendor_prices'] ?? [];
    $primary_vendor_index = $_POST['primary_vendor'] ?? 0;
    
    // Get serial data from POST
    $serial_numbers = isset($_POST['serial_numbers']) ? $_POST['serial_numbers'] : [];
    $model_numbers = isset($_POST['model_numbers']) ? $_POST['model_numbers'] : [];
    $versions = isset($_POST['versions']) ? $_POST['versions'] : [];
    $serial_ids = isset($_POST['serial_ids']) ? $_POST['serial_ids'] : [];
    
    // FIX: Handle delete_serials properly - it may be a JSON string or array
    $delete_serials = [];
    if (isset($_POST['delete_serials'])) {
        if (is_array($_POST['delete_serials'])) {
            $delete_serials = $_POST['delete_serials'];
        } elseif (is_string($_POST['delete_serials']) && !empty($_POST['delete_serials'])) {
            // Try to decode as JSON
            $decoded = json_decode($_POST['delete_serials'], true);
            if (is_array($decoded)) {
                $delete_serials = $decoded;
            } else {
                // If not JSON, it might be a comma-separated string
                $delete_serials = explode(',', $_POST['delete_serials']);
            }
        }
    }
    
    try {
        // Get current item data
        $stmt = $pdo->prepare("SELECT price, revision_count, current_qty, available_qty, total_assigned, total_returned FROM items WHERE id = ?");
        $stmt->execute([$id]);
        $item_data = $stmt->fetch();
        $old_price = $item_data['price'];
        
        // ============================================
        // STEP 1: Update Item Information
        // ============================================
        $stmt = $pdo->prepare("UPDATE items SET name=?, brand_id=?, type_id=?, category_id=?, sub_category_id=?, 
                              warranty_period=?, price=?, specification=?, min_qty=? WHERE id=?");
        $stmt->execute([$name, $brand_id, $type_id, $category_id, $sub_category_id, 
                       $warranty_period, $new_price, $specification, $min_qty, $id]);
        
        // Track price change if price changed
        if($old_price != $new_price && !empty($price_change_reason)) {
            $new_revision = $item_data['revision_count'] + 1;
            $stmt = $pdo->prepare("UPDATE items SET revision_count = ? WHERE id = ?");
            $stmt->execute([$new_revision, $id]);
            
            $stmt = $pdo->prepare("INSERT INTO item_price_history (item_id, old_price, new_price, change_reason, changed_by, revision_number) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $old_price, $new_price, $price_change_reason, $_SESSION['user_id'], $new_revision]);
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // ============================================
        // STEP 2: Handle Serial Numbers - DELETE
        // ============================================
        
        $deleted_count = 0;
        
        // Process delete serials - FIX: Handle array properly
        if(!empty($delete_serials)) {
            // Filter out empty values and ensure they are integers
            $delete_serials = array_filter($delete_serials, function($val) {
                return !empty($val) && is_numeric($val);
            });
            $delete_serials = array_map('intval', $delete_serials);
            
            if(!empty($delete_serials)) {
                // Check if any of these serials are assigned or damaged
                $placeholders = implode(',', array_fill(0, count($delete_serials), '?'));
                $checkStmt = $pdo->prepare("
                    SELECT id FROM item_serial_numbers 
                    WHERE id IN ($placeholders) 
                    AND (is_assigned = 1 OR is_assigned = 2 OR damage_id IS NOT NULL)
                ");
                $checkStmt->execute($delete_serials);
                $locked_serials = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Remove locked serials from delete list
                if(!empty($locked_serials)) {
                    $delete_serials = array_diff($delete_serials, $locked_serials);
                }
                
                // Delete remaining serials
                if(!empty($delete_serials)) {
                    $placeholders = implode(',', array_fill(0, count($delete_serials), '?'));
                    $stmt = $pdo->prepare("DELETE FROM item_serial_numbers WHERE id IN ($placeholders)");
                    $stmt->execute($delete_serials);
                    $deleted_count = $stmt->rowCount();
                }
            }
        }
        
        // ============================================
        // STEP 3: Handle Serial Numbers - UPDATE & ADD
        // ============================================
        
        $serials_added = 0;
        $serials_updated = 0;
        
        // Process each serial row
        foreach($serial_numbers as $index => $serial) {
            $serial = trim($serial);
            if(!empty($serial)) {
                $serial_id = isset($serial_ids[$index]) && !empty($serial_ids[$index]) ? intval($serial_ids[$index]) : null;
                $model = isset($model_numbers[$index]) ? trim($model_numbers[$index]) : '';
                $version = isset($versions[$index]) ? trim($versions[$index]) : '';
                
                if($serial_id) {
                    // Update existing serial - only if not assigned or damaged
                    $checkStmt = $pdo->prepare("SELECT is_assigned, damage_id FROM item_serial_numbers WHERE id = ?");
                    $checkStmt->execute([$serial_id]);
                    $serial_check = $checkStmt->fetch();
                    
                    if($serial_check && $serial_check['is_assigned'] == 0 && (empty($serial_check['damage_id']) || $serial_check['damage_id'] == 0)) {
                        $stmt = $pdo->prepare("UPDATE item_serial_numbers 
                                              SET serial_number = ?, model_number = ?, version = ?
                                              WHERE id = ?");
                        $stmt->execute([$serial, $model, $version, $serial_id]);
                        $serials_updated++;
                    }
                } else {
                    // Add new serial
                    $stmt = $pdo->prepare("INSERT INTO item_serial_numbers (item_id, serial_number, model_number, version, is_assigned, created_at) 
                                          VALUES (?, ?, ?, ?, 0, NOW())");
                    $stmt->execute([$id, $serial, $model, $version]);
                    $serials_added++;
                }
            }
        }
        
        // ============================================
        // STEP 4: Update Stock Quantities
        // ============================================
        
        // Get updated count of serial numbers
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM item_serial_numbers WHERE item_id = ?");
        $stmt->execute([$id]);
        $new_current_qty = $stmt->fetch()['count'];
        
        // Get assigned count from assignments table
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(quantity), 0) as total_assigned 
            FROM assignments 
            WHERE item_id = ? AND status = 'assigned' AND return_status = 'active'
        ");
        $stmt->execute([$id]);
        $total_assigned = $stmt->fetch()['total_assigned'];
        
        // Get returned count
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(quantity), 0) as total_returned 
            FROM assignments 
            WHERE item_id = ? AND (status = 'returned' OR return_status = 'returned')
        ");
        $stmt->execute([$id]);
        $total_returned = $stmt->fetch()['total_returned'];
        
        // Calculate available quantity
        $available_qty = max(0, $new_current_qty - $total_assigned);
        
        // Update items table with new stock values
        $stmt = $pdo->prepare("
            UPDATE items 
            SET current_qty = ?, 
                total_assigned = ?, 
                total_returned = ?, 
                available_qty = ? 
            WHERE id = ?
        ");
        $stmt->execute([$new_current_qty, $total_assigned, $total_returned, $available_qty, $id]);
        
        // ============================================
        // STEP 5: Update Vendors
        // ============================================
        $stmt = $pdo->prepare("DELETE FROM item_vendors WHERE item_id = ?");
        $stmt->execute([$id]);
        
        foreach($selected_vendors as $index => $vendor_id) {
            if(!empty($vendor_id)) {
                $purchase_price = isset($vendor_prices[$index]) && !empty($vendor_prices[$index]) ? $vendor_prices[$index] : NULL;
                $is_primary = ($primary_vendor_index == $index) ? 1 : 0;
                $stmt = $pdo->prepare("INSERT INTO item_vendors (item_id, vendor_id, purchase_price, is_primary) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->execute([$id, $vendor_id, $purchase_price, $is_primary]);
            }
        }
        
        $pdo->commit();
        
        // Build success message
        $success_msg = "Item updated successfully!";
        $success_msg .= " | Total Serial Numbers: " . $new_current_qty;
        $success_msg .= " | Assigned: " . $total_assigned;
        $success_msg .= " | Available: " . $available_qty;
        if($deleted_count > 0) {
            $success_msg .= " | Deleted: " . $deleted_count . " serial(s)";
        }
        if($serials_added > 0) {
            $success_msg .= " | Added: " . $serials_added . " serial(s)";
        }
        if($serials_updated > 0) {
            $success_msg .= " | Updated: " . $serials_updated . " serial(s)";
        }
        
        $_SESSION['success_message'] = $success_msg;
        
        header("Location: list.php");
        exit();
        
    } catch(Exception $e) {
        if($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
        $_SESSION['edit_error'] = $error_message;
        header("Location: edit.php?id=" . $id);
        exit();
    }
}

// Now include header AFTER processing POST
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;

if($id <= 0) {
    echo '<div class="alert alert-danger">Invalid item ID</div>';
    include '../../includes/footer.php';
    exit;
}

// Get item details with assigned quantity calculation
$stmt = $pdo->prepare("
    SELECT i.*, 
           b.name as brand_name,
           (SELECT COALESCE(SUM(quantity), 0) FROM assignments WHERE item_id = i.id AND status = 'assigned' AND return_status = 'active') as assigned_qty,
           (SELECT COUNT(*) FROM item_serial_numbers WHERE item_id = i.id) as total_serials,
           (SELECT COUNT(*) FROM item_serial_numbers WHERE item_id = i.id AND is_assigned = 1) as assigned_serials
    FROM items i 
    LEFT JOIN brands b ON i.brand_id = b.id 
    WHERE i.id = ?
");
$stmt->execute([$id]);
$item = $stmt->fetch();

if(!$item) {
    echo '<div class="alert alert-danger">Item not found</div>';
    include '../../includes/footer.php';
    exit;
}

// Use serial counts for accurate stock information
$totalSerials = (int)($item['total_serials'] ?? 0);
$assignedSerials = (int)($item['assigned_serials'] ?? 0);

// If no serials exist, fall back to current_qty
if($totalSerials == 0) {
    $totalSerials = (int)$item['current_qty'];
    $assignedSerials = (int)$item['assigned_qty'];
}

$available_qty = max(0, $totalSerials - $assignedSerials);

// Get item vendors
$stmt = $pdo->prepare("SELECT iv.*, v.vendor_name as name, v.phone, v.email 
                       FROM item_vendors iv 
                       JOIN vendors v ON iv.vendor_id = v.id 
                       WHERE iv.item_id = ?");
$stmt->execute([$id]);
$itemVendors = $stmt->fetchAll();

// Get serial numbers for this item with full details including version and damage status
$stmt = $pdo->prepare("
    SELECT 
        isn.*, 
        e.full_name as assigned_to_name,
        a.assignment_no,
        a.assigned_date as assignment_assigned_date,
        a.status as assignment_status,
        a.return_status,
        d.id as damage_id,
        d.damage_no,
        d.status as damage_record_status,
        d.damage_severity
    FROM item_serial_numbers isn
    LEFT JOIN assignments a ON isn.assignment_id = a.id
    LEFT JOIN employees e ON isn.assigned_to = e.id
    LEFT JOIN damages d ON isn.damage_id = d.id
    WHERE isn.item_id = ? 
    ORDER BY isn.id
");
$stmt->execute([$id]);
$serialNumbers = $stmt->fetchAll();

// Process serials to show actual status
$processedSerials = [];
foreach($serialNumbers as $serial) {
    $actual_status = 'available';
    
    if($serial['is_assigned'] == 1) {
        if($serial['assignment_status'] == 'returned' || $serial['return_status'] == 'returned') {
            $actual_status = 'returned';
        } else {
            $actual_status = 'assigned';
        }
    } 
    elseif($serial['is_assigned'] == 2) {
        if($serial['damage_record_status'] == 'repaired') {
            $actual_status = 'available';
        } else {
            $actual_status = 'damaged';
        }
    } 
    elseif(!empty($serial['damage_id'])) {
        if($serial['damage_record_status'] == 'repaired') {
            $actual_status = 'available';
        } else {
            $actual_status = 'damaged';
        }
    } 
    else {
        $actual_status = 'available';
    }
    
    $serial['actual_status'] = $actual_status;
    $processedSerials[] = $serial;
}

// Get complete price history with user details
$stmt = $pdo->prepare("SELECT iph.*, u.full_name as changed_by_name 
                       FROM item_price_history iph 
                       LEFT JOIN users u ON iph.changed_by = u.id 
                       WHERE iph.item_id = ? 
                       ORDER BY iph.revision_number DESC, iph.created_at DESC");
$stmt->execute([$id]);
$priceHistory = $stmt->fetchAll();

// Get all data for dropdowns
$categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 AND parent_id IS NULL ORDER BY name")->fetchAll();
$subCategories = $pdo->query("SELECT * FROM categories WHERE is_active=1 AND parent_id IS NOT NULL ORDER BY name")->fetchAll();
$itemTypes = $pdo->query("SELECT * FROM item_types WHERE is_active=1 ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands WHERE is_active=1 ORDER BY name")->fetchAll();

// Get vendors
$vendors = $pdo->query("SELECT id, vendor_name as name, company_name FROM vendors WHERE is_active=1 ORDER BY vendor_name")->fetchAll();
if(empty($vendors)) {
    $vendors = $pdo->query("SELECT id, name FROM vendors WHERE is_active=1 ORDER BY name")->fetchAll();
}

$error_message = isset($_SESSION['edit_error']) ? $_SESSION['edit_error'] : '';
if(isset($_SESSION['edit_error'])) {
    unset($_SESSION['edit_error']);
}

$success_message = '';
if(isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .modern-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .modern-card:hover {
            box-shadow: 0 15px 50px rgba(0,0,0,0.12);
        }
        .card-header-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px 25px;
            color: white;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            padding-bottom: 12px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            position: relative;
        }
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .form-label-modern {
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control-modern, .form-select-modern {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        .form-control-modern:focus, .form-select-modern:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            outline: none;
        }
        .price-history-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
            border-left: 3px solid;
        }
        .price-history-item:hover {
            transform: translateX(5px);
            background: #f1f5f9;
        }
        .vendor-table, .serial-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            width: 100%;
            border-collapse: collapse;
        }
        .vendor-table th, .serial-table th {
            background: #f1f5f9;
            padding: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            text-align: left;
        }
        .vendor-table td, .serial-table td {
            padding: 10px;
            vertical-align: middle;
        }
        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
        }
        .price-input-group {
            display: inline-flex;
            align-items: center;
            width: 100%;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .price-input-group .currency-symbol {
            background: white;
            padding: 10px 12px;
            font-weight: 600;
            color: #334155;
            border-right: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .price-input-group .price-input {
            border: none;
            padding: 10px 12px;
            width: 100%;
            font-size: 0.9rem;
            outline: none;
        }
        .vendor-price-group {
            display: inline-flex;
            align-items: center;
            width: 100%;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .vendor-price-group .currency-symbol {
            background: white;
            padding: 8px 10px;
            font-weight: 600;
            color: #334155;
            border-right: 1px solid #e2e8f0;
            font-size: 13px;
        }
        .vendor-price-group .vendor-price-input {
            border: none;
            padding: 8px 10px;
            width: 100%;
            font-size: 0.85rem;
            outline: none;
        }
        .stock-info-box {
            background: #f0fdf4;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
        }
        .stock-info-box .label {
            font-size: 12px;
            color: #166534;
        }
        .stock-info-box .value {
            font-size: 24px;
            font-weight: 700;
            color: #166534;
        }
        .stock-info-box .value.warning {
            color: #d97706;
        }
        .status-badge {
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .status-assigned {
            background: #fee2e2;
            color: #dc2626;
        }
        .status-available {
            background: #d1fae5;
            color: #059669;
        }
        .status-damaged {
            background: #fef3c7;
            color: #d97706;
        }
        .status-returned {
            background: #dbeafe;
            color: #2563eb;
        }
        .assigned-info {
            font-size: 0.7rem;
            color: #6b7280;
            margin-top: 4px;
        }
        .damage-info {
            font-size: 0.7rem;
            color: #dc2626;
            margin-top: 4px;
        }
        .serial-version {
            font-size: 0.65rem;
            background: #e2e8f0;
            padding: 1px 8px;
            border-radius: 10px;
            color: #475569;
        }
        .container-fluid {
            padding: 20px 24px;
            max-width: 1600px;
            margin: 0 auto;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -12px;
        }
        .col-lg-7 {
            width: 58.333%;
            padding: 0 12px;
        }
        .col-lg-5 {
            width: 41.667%;
            padding: 0 12px;
        }
        .col-md-6 {
            width: 50%;
            padding: 0 12px;
        }
        .col-md-4 {
            width: 33.333%;
            padding: 0 12px;
        }
        .row.g-3 {
            margin: 0 -12px;
        }
        .mb-4 {
            margin-bottom: 24px;
        }
        .mb-3 {
            margin-bottom: 16px;
        }
        .mt-3 {
            margin-top: 16px;
        }
        .mt-4 {
            margin-top: 24px;
        }
        .pt-3 {
            padding-top: 16px;
        }
        .border-top {
            border-top: 1px solid #e2e8f0;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .me-1 {
            margin-right: 4px;
        }
        .me-2 {
            margin-right: 8px;
        }
        .ms-2 {
            margin-left: 8px;
        }
        .ms-3 {
            margin-left: 16px;
        }
        .px-4 {
            padding-left: 24px;
            padding-right: 24px;
        }
        .py-2 {
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .rounded-pill {
            border-radius: 30px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .d-flex {
            display: flex;
        }
        .justify-content-between {
            justify-content: space-between;
        }
        .align-items-center {
            align-items: center;
        }
        .fw-bold {
            font-weight: 700;
        }
        .opacity-75 {
            opacity: 0.75;
        }
        .btn-success {
            background: #10b981;
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-primary {
            background: #667eea;
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            background: #5a67d8;
        }
        .btn-secondary {
            background: #64748b;
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background: #475569;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 8px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .bg-primary {
            background: #667eea;
            color: white;
        }
        .bg-white {
            background: white;
        }
        .text-dark {
            color: #1e293b;
        }
        .text-danger {
            color: #dc2626;
        }
        .text-success {
            color: #10b981;
        }
        .text-muted {
            color: #64748b;
        }
        .text-primary {
            color: #667eea;
        }
        .alert-success {
            background-color: #d1fae5;
            border-color: #a7f3d0;
            color: #065f46;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #fee2e2;
            border-color: #fecaca;
            color: #991b1b;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .alert-info {
            background-color: #e0f2fe;
            border-color: #bae6fd;
            color: #0369a1;
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .col-lg-7, .col-lg-5, .col-md-6, .col-md-4 {
                width: 100%;
                margin-bottom: 15px;
            }
            .container-fluid {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-3">
    <!-- Header Section -->
    <div class="modern-card mb-4">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i> Edit Item / Device</h4>
                <p class="mb-0 opacity-75 mt-1">Update item information, manage vendors, serial numbers, and track price history</p>
            </div>
            <div>
                <span class="bg-white text-dark px-3 py-2 rounded-pill">
                    <i class="fas fa-code-branch text-primary me-1"></i>
                    Revision #<?php echo $item['revision_count']; ?>
                </span>
            </div>
        </div>
    </div>

    <?php if($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Main Form Column -->
        <div class="col-lg-7">
            <div class="modern-card">
                <div class="card-body p-4">
                    <!-- Stock Information Summary -->
                    <div class="stock-info-box mb-4">
                        <div class="row text-center">
                            <div class="col-3">
                                <div class="label">Total Stock</div>
                                <div class="value"><?php echo $totalSerials; ?></div>
                            </div>
                            <div class="col-3">
                                <div class="label">Assigned</div>
                                <div class="value warning"><?php echo $assignedSerials; ?></div>
                            </div>
                            <div class="col-3">
                                <div class="label">Returned</div>
                                <div class="value text-primary"><?php echo (int)$item['total_returned']; ?></div>
                            </div>
                            <div class="col-3">
                                <div class="label">Available</div>
                                <div class="value"><?php echo $available_qty; ?></div>
                            </div>
                        </div>
                        <div class="text-center mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Available = Total Stock - Assigned
                            </small>
                        </div>
                    </div>

                    <form method="POST" id="itemForm" action="edit.php">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="delete_serials" id="deleteSerialsHidden" value="">
                        
                        <!-- Basic Information -->
                        <div class="section-title">Basic Information</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-modern">Item Code</label>
                                <input type="text" class="form-control-modern form-control" value="<?php echo $item['item_code']; ?>" disabled style="background:#f1f5f9;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern">Item Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control-modern form-control" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern">Brand</label>
                                <select name="brand_id" class="form-select-modern form-select">
                                    <option value="">Select Brand</option>
                                    <?php foreach($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>" <?php echo $item['brand_id'] == $brand['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern">Item Type</label>
                                <select name="type_id" class="form-select-modern form-select">
                                    <option value="">Select Type</option>
                                    <?php foreach($itemTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" <?php echo $item['type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern">Category</label>
                                <select name="category_id" class="form-select-modern form-select" id="categorySelect">
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $item['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern">Sub-Category</label>
                                <select name="sub_category_id" class="form-select-modern form-select" id="subCategorySelect">
                                    <option value="">Select Sub-Category</option>
                                    <?php foreach($subCategories as $sub): ?>
                                    <option value="<?php echo $sub['id']; ?>" data-parent="<?php echo $sub['parent_id']; ?>" <?php echo $item['sub_category_id'] == $sub['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sub['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Device Details -->
                        <div class="section-title mt-3">Device Details</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-modern">Warranty (Months)</label>
                                <input type="number" name="warranty_period" class="form-control-modern form-control" value="<?php echo $item['warranty_period']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern">Minimum Quantity Alert</label>
                                <input type="number" name="min_qty" class="form-control-modern form-control" value="<?php echo $item['min_qty']; ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-modern">Specifications</label>
                                <textarea name="specification" rows="3" class="form-control-modern form-control"><?php echo htmlspecialchars($item['specification']); ?></textarea>
                            </div>
                        </div>

                        <!-- Serial Numbers -->
                        <div class="section-title mt-3">
                            <i class="fas fa-qrcode me-2 text-primary"></i> Serial Numbers / Models / Versions
                            <span class="badge bg-primary ms-2">Total: <?php echo count($processedSerials); ?></span>
                        </div>
                        <div class="alert alert-info py-2 px-3 mb-3 rounded-3">
                            <i class="fas fa-info-circle me-2"></i> 
                            <strong>Note:</strong> Serial numbers that are already assigned, damaged, or repaired cannot be edited or deleted.
                        </div>
                        
                        <div class="table-responsive mb-4">
                            <table class="serial-table table table-bordered" id="serialTable">
                                <thead>
                                    <tr>
                                        <th width="25%">Serial Number <span class="text-danger">*</span></th>
                                        <th width="25%">Model Number</th>
                                        <th width="15%">Version</th>
                                        <th width="25%">Status</th>
                                        <th width="10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="serialBody">
                                    <?php if(empty($processedSerials)): ?>
                                    <tr class="serial-row" id="serial_row_0">
                                        <td>
                                            <input type="text" name="serial_numbers[]" class="form-control-modern serial-input" placeholder="Enter Serial Number" required>
                                            <input type="hidden" name="serial_ids[]" value="">
                                        </td>
                                        <td>
                                            <input type="text" name="model_numbers[]" class="form-control-modern model-input" placeholder="Model Number">
                                        </td>
                                        <td>
                                            <input type="text" name="versions[]" class="form-control-modern version-input" placeholder="Version">
                                        </td>
                                        <td class="text-center">
                                            <span class="status-badge status-available">Available</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-icon remove-serial" data-row="0" disabled>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach($processedSerials as $index => $serial): 
                                            $canEdit = ($serial['actual_status'] == 'available' || $serial['actual_status'] == 'returned');
                                        ?>
                                        <tr class="serial-row" id="serial_row_<?php echo $index; ?>" data-serial-id="<?php echo $serial['id']; ?>">
                                            <td>
                                                <input type="text" name="serial_numbers[]" class="form-control-modern serial-input" 
                                                       value="<?php echo htmlspecialchars($serial['serial_number']); ?>" 
                                                       required <?php echo $canEdit ? '' : 'readonly style="background:#fef2f2;"'; ?>>
                                                <input type="hidden" name="serial_ids[]" value="<?php echo $serial['id']; ?>">
                                            </td>
                                            <td>
                                                <input type="text" name="model_numbers[]" class="form-control-modern model-input" 
                                                       value="<?php echo htmlspecialchars($serial['model_number']); ?>" 
                                                       <?php echo $canEdit ? '' : 'readonly'; ?>>
                                            </td>
                                            <td>
                                                <input type="text" name="versions[]" class="form-control-modern version-input" 
                                                       value="<?php echo htmlspecialchars($serial['version']); ?>" 
                                                       <?php echo $canEdit ? '' : 'readonly'; ?>>
                                            </td>
                                            <td class="text-center">
                                                <?php if($serial['actual_status'] == 'assigned'): ?>
                                                    <span class="status-badge status-assigned">Assigned</span>
                                                    <?php if($serial['assigned_to_name']): ?>
                                                        <div class="assigned-info">To: <?php echo htmlspecialchars($serial['assigned_to_name']); ?></div>
                                                    <?php endif; ?>
                                                    <?php if($serial['assignment_no']): ?>
                                                        <div class="assigned-info">ASN: <?php echo $serial['assignment_no']; ?></div>
                                                    <?php endif; ?>
                                                <?php elseif($serial['actual_status'] == 'damaged'): ?>
                                                    <span class="status-badge status-damaged">Damaged</span>
                                                    <?php if($serial['damage_no']): ?>
                                                        <div class="damage-info">DMG: <?php echo $serial['damage_no']; ?></div>
                                                    <?php endif; ?>
                                                    <?php if($serial['damage_record_status']): ?>
                                                        <div class="damage-info">Status: <?php echo ucfirst($serial['damage_record_status']); ?></div>
                                                    <?php endif; ?>
                                                <?php elseif($serial['actual_status'] == 'returned'): ?>
                                                    <span class="status-badge status-available">Returned</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-available">Available</span>
                                                <?php endif; ?>
                                                <?php if($serial['version']): ?>
                                                    <div class="serial-version mt-1">Ver: <?php echo htmlspecialchars($serial['version']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if($serial['actual_status'] == 'available' || $serial['actual_status'] == 'returned'): ?>
                                                    <button type="button" class="btn btn-danger btn-icon remove-serial ajax-delete-serial" 
                                                            data-row="<?php echo $index; ?>" 
                                                            data-serial-id="<?php echo $serial['id']; ?>"
                                                            data-item-id="<?php echo $id; ?>"
                                                            title="Delete this serial number">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-secondary btn-icon" disabled title="Cannot delete <?php echo $serial['actual_status']; ?> serial">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5">
                                            <button type="button" class="btn btn-success btn-sm" id="addSerialBtn">
                                                <i class="fas fa-plus me-1"></i> Add Another Serial Number
                                            </button>
                                            <span class="ms-3 text-muted small">
                                                <i class="fas fa-info-circle"></i> Adding a serial will increase stock quantity by 1
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Pricing -->
                        <div class="section-title mt-3">Pricing Information</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-modern">Regular Price (BDT)</label>
                                <div class="price-input-group">
                                    <span class="currency-symbol">৳</span>
                                    <input type="number" step="0.01" name="regular_price" class="price-input" value="<?php echo $item['price']; ?>" id="regularPrice">
                                </div>
                            </div>
                            <div class="col-md-6" id="priceReasonDiv" style="display:none;">
                                <label class="form-label-modern">Reason for Price Change</label>
                                <input type="text" name="price_change_reason" class="form-control-modern form-control" placeholder="e.g., Vendor price increase">
                                <small class="text-muted"><i class="fas fa-info-circle"></i> This will be recorded in history</small>
                            </div>
                        </div>

                        <!-- Vendors -->
                        <div class="section-title mt-3">
                            <i class="fas fa-truck me-2 text-primary"></i> Multiple Vendors
                        </div>
                        <div class="alert alert-info py-2 px-3 mb-3 rounded-3">
                            <i class="fas fa-info-circle me-2"></i> Add multiple vendors. The primary vendor's price auto-updates the regular price.
                        </div>
                        
                        <div class="table-responsive">
                            <table class="vendor-table table table-bordered" id="vendorsTable">
                                <thead>
                                    <tr>
                                        <th width="40%">Vendor Name</th>
                                        <th width="30%">Purchase Price (BDT)</th>
                                        <th width="20%">Primary</th>
                                        <th width="10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="vendorsBody">
                                    <?php if(empty($itemVendors)): ?>
                                    <tr class="vendor-row" id="vendor_row_0">
                                        <td>
                                            <select name="vendors[]" class="form-select-modern vendor-select" required>
                                                <option value="">Select Vendor</option>
                                                <?php foreach($vendors as $vendor): ?>
                                                <option value="<?php echo $vendor['id']; ?>">
                                                    <?php echo htmlspecialchars($vendor['name'] ?? $vendor['vendor_name'] ?? 'Unnamed Vendor'); ?>
                                                    <?php if(!empty($vendor['company_name'])): ?>
                                                        (<?php echo htmlspecialchars($vendor['company_name']); ?>)
                                                    <?php endif; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="vendor-price-group">
                                                <span class="currency-symbol">৳</span>
                                                <input type="number" step="0.01" name="vendor_prices[]" class="vendor-price-input vendor-price" placeholder="0.00">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <input type="radio" name="primary_vendor" value="0" class="primary-radio" checked>
                                            <span class="badge bg-success">Primary</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-icon remove-vendor" data-row="0" disabled>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach($itemVendors as $index => $iv): ?>
                                        <tr class="vendor-row" id="vendor_row_<?php echo $index; ?>">
                                            <td>
                                                <select name="vendors[]" class="form-select-modern vendor-select" required>
                                                    <option value="">Select Vendor</option>
                                                    <?php foreach($vendors as $vendor): ?>
                                                    <option value="<?php echo $vendor['id']; ?>" <?php echo $iv['vendor_id'] == $vendor['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($vendor['name'] ?? $vendor['vendor_name'] ?? 'Unnamed Vendor'); ?>
                                                        <?php if(!empty($vendor['company_name'])): ?>
                                                            (<?php echo htmlspecialchars($vendor['company_name']); ?>)
                                                        <?php endif; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <div class="vendor-price-group">
                                                    <span class="currency-symbol">৳</span>
                                                    <input type="number" step="0.01" name="vendor_prices[]" class="vendor-price-input vendor-price" value="<?php echo $iv['purchase_price']; ?>">
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <input type="radio" name="primary_vendor" value="<?php echo $index; ?>" class="primary-radio" <?php echo $iv['is_primary'] ? 'checked' : ''; ?>>
                                                <?php if($iv['is_primary']): ?>
                                                <span class="badge bg-success">Primary</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-icon remove-vendor" data-row="<?php echo $index; ?>" <?php echo $index == 0 ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4">
                                            <button type="button" class="btn btn-success btn-sm" id="addVendorBtn">
                                                <i class="fas fa-plus me-1"></i> Add Another Vendor
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Action Buttons -->
                        <div class="text-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill" id="submitBtn">
                                <i class="fas fa-save me-2"></i> Update Item
                            </button>
                            <a href="list.php" class="btn btn-secondary px-4 py-2 rounded-pill ms-2">
                                <i class="fas fa-times me-2"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Price History Sidebar -->
        <div class="col-lg-5">
            <div class="modern-card">
                <div class="card-header-modern">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i> Price Change History</h5>
                    <small class="opacity-75">Complete revision tracking with BDT currency</small>
                </div>
                <div class="card-body p-4" style="max-height: 700px; overflow-y: auto;">
                    <?php if(empty($priceHistory)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-chart-line fa-4x mb-3 opacity-25"></i>
                            <p>No price changes recorded yet</p>
                            <small>When you change the price, it will be tracked here</small>
                        </div>
                    <?php else: ?>
                        <?php foreach($priceHistory as $history): ?>
                        <div class="price-history-item" style="border-left-color: <?php echo $history['revision_number'] == $item['revision_count'] ? '#10b981' : '#667eea'; ?>">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge" style="background: <?php echo $history['revision_number'] == $item['revision_count'] ? '#10b981' : '#667eea'; ?>">
                                    <i class="fas fa-tag me-1"></i> Revision #<?php echo $history['revision_number']; ?>
                                </span>
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt me-1"></i> <?php echo date('d-m-Y H:i', strtotime($history['created_at'])); ?>
                                </small>
                            </div>
                            
                            <div class="bg-white rounded p-3 mb-2">
                                <div class="row text-center">
                                    <div class="col-5">
                                        <div class="text-muted small mb-1">Old Price</div>
                                        <?php if($history['old_price']): ?>
                                        <span class="text-danger">
                                            <del>৳<?php echo number_format($history['old_price'], 2); ?></del>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-2">
                                        <i class="fas fa-arrow-right text-muted"></i>
                                    </div>
                                    <div class="col-5">
                                        <div class="text-muted small mb-1">New Price</div>
                                        <span class="text-success fw-bold fs-6">
                                            ৳<?php echo number_format($history['new_price'], 2); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if($history['change_reason']): ?>
                            <div class="mb-2">
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-question-circle me-1"></i> Reason
                                </span>
                                <div class="small text-muted mt-1 ms-1"><?php echo htmlspecialchars($history['change_reason']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-user me-1"></i> Changed By
                                </span>
                                <div class="small text-muted mt-1 ms-1"><?php echo $history['changed_by_name'] ?? 'System'; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light py-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i> Each price change creates a new revision. History is permanent.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let vendorCounter = <?php echo max(1, count($itemVendors)); ?>;
    let serialCounter = <?php echo max(1, count($processedSerials)); ?>;
    let originalPrice = <?php echo $item['price']; ?>;
    let deletedSerialIds = [];
    
    // Show/hide price reason field when price changes
    $('#regularPrice').on('change input', function() {
        const newPrice = $(this).val();
        if(parseFloat(newPrice) !== parseFloat(originalPrice)) {
            $('#priceReasonDiv').fadeIn();
        } else {
            $('#priceReasonDiv').fadeOut();
        }
    });
    
    // Filter sub-categories
    $('#categorySelect').change(function() {
        const categoryId = $(this).val();
        $('#subCategorySelect option').each(function() {
            const parentId = $(this).data('parent');
            if(categoryId == parentId || !categoryId) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        $('#subCategorySelect').val('');
    });
    
    // ============================================
    // ADD SERIAL ROW
    // ============================================
    $('#addSerialBtn').click(function() {
        const newRowId = serialCounter;
        const newRow = `
            <tr class="serial-row" id="serial_row_${newRowId}">
                <td>
                    <input type="text" name="serial_numbers[]" class="form-control-modern serial-input" placeholder="Enter Serial Number" required>
                    <input type="hidden" name="serial_ids[]" value="">
                </td>
                <td>
                    <input type="text" name="model_numbers[]" class="form-control-modern model-input" placeholder="Model Number">
                </td>
                <td>
                    <input type="text" name="versions[]" class="form-control-modern version-input" placeholder="Version">
                </td>
                <td class="text-center">
                    <span class="status-badge status-available">Available</span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-icon remove-serial" data-row="${newRowId}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#serialBody').append(newRow);
        serialCounter++;
        updateRemoveSerialButtons();
    });
    
    // ============================================
    // REMOVE SERIAL ROW (Client-side only)
    // ============================================
    $(document).on('click', '.remove-serial', function() {
        const rowCount = $('.serial-row').length;
        if(rowCount > 1) {
            const rowId = $(this).data('row');
            $(`#serial_row_${rowId}`).remove();
            updateRemoveSerialButtons();
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Remove',
                text: 'At least one serial number is required!',
                confirmButtonColor: '#3085d6'
            });
        }
    });
    
    function updateRemoveSerialButtons() {
        const rowCount = $('.serial-row').length;
        $('.remove-serial, .ajax-delete-serial').prop('disabled', false);
        if(rowCount === 1) {
            $('.remove-serial, .ajax-delete-serial').prop('disabled', true);
        }
    }
    
    // ============================================
    // VENDOR MANAGEMENT
    // ============================================
    // Vendor options HTML from PHP
    var vendorOptionsHtml = '';
    <?php foreach($vendors as $vendor): ?>
    vendorOptionsHtml += '<option value="<?php echo $vendor['id']; ?>"><?php echo addslashes(htmlspecialchars($vendor['name'] ?? $vendor['vendor_name'] ?? 'Unnamed Vendor')); ?><?php echo !empty($vendor['company_name']) ? ' (' . addslashes(htmlspecialchars($vendor['company_name'])) . ')' : ''; ?></option>';
    <?php endforeach; ?>
    
    $('#addVendorBtn').click(function() {
        const newRowId = vendorCounter;
        const newRow = `
            <tr class="vendor-row" id="vendor_row_${newRowId}">
                <td>
                    <select name="vendors[]" class="form-select-modern vendor-select" required>
                        <option value="">Select Vendor</option>
                        ${vendorOptionsHtml}
                    </select>
                </td>
                <td>
                    <div class="vendor-price-group">
                        <span class="currency-symbol">৳</span>
                        <input type="number" step="0.01" name="vendor_prices[]" class="vendor-price-input vendor-price" placeholder="0.00">
                    </div>
                </td>
                <td class="text-center">
                    <input type="radio" name="primary_vendor" value="${newRowId}" class="primary-radio">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-icon remove-vendor" data-row="${newRowId}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#vendorsBody').append(newRow);
        vendorCounter++;
        $('.remove-vendor').prop('disabled', false);
    });
    
    $(document).on('click', '.remove-vendor', function() {
        const rowCount = $('.vendor-row').length;
        if(rowCount > 1) {
            const rowId = $(this).data('row');
            $('#vendor_row_' + rowId).remove();
            if($('.vendor-row').length === 1) {
                $('.remove-vendor').prop('disabled', true);
            }
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Remove',
                text: 'At least one vendor is required!',
                confirmButtonColor: '#3085d6'
            });
        }
    });
    
    // Primary radio change
    $(document).on('change', '.primary-radio', function() {
        const row = $(this).closest('.vendor-row');
        const price = row.find('.vendor-price').val();
        if(price && parseFloat(price) > 0) {
            $('#regularPrice').val(price);
            originalPrice = parseFloat(price);
            $('#priceReasonDiv').fadeIn();
        }
    });
    
    // Vendor price change
    $(document).on('change', '.vendor-price', function() {
        const row = $(this).closest('.vendor-row');
        const isPrimary = row.find('.primary-radio').is(':checked');
        if(isPrimary) {
            const price = $(this).val();
            if(price && parseFloat(price) > 0) {
                $('#regularPrice').val(price);
                originalPrice = parseFloat(price);
                $('#priceReasonDiv').fadeIn();
            }
        }
    });
    
    // ============================================
    // AJAX DELETE SERIAL NUMBER
    // ============================================
    $(document).on('click', '.ajax-delete-serial', function() {
        const serialId = $(this).data('serial-id');
        const itemId = $(this).data('item-id');
        const rowId = $(this).data('row');
        const $row = $('#serial_row_' + rowId);
        const serialNumber = $row.find('.serial-input').val();
        
        if(!serialId) {
            // If no serial ID, just remove the row (newly added serial)
            $row.remove();
            updateSerialCounters();
            Swal.fire({
                icon: 'info',
                title: 'Removed',
                text: 'Serial number removed from the list.',
                timer: 1500,
                showConfirmButton: false
            });
            return;
        }
        
        // Confirm deletion
        Swal.fire({
            title: 'Delete Serial Number?',
            html: 'Are you sure you want to delete serial number:<br><strong>' + escapeHtml(serialNumber) + '</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if(result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while the serial number is deleted.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send AJAX request
                $.ajax({
                    url: 'ajax/delete_serial.php',
                    type: 'POST',
                    data: {
                        serial_id: serialId,
                        item_id: itemId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            // Update the stock display
                            updateStockDisplay(response.total_serials, response.assigned, response.available);
                            
                            // Remove the row
                            $row.remove();
                            updateSerialCounters();
                            
                            // Add to deleted serials list for form submission
                            deletedSerialIds.push(serialId);
                            $('#deleteSerialsHidden').val(JSON.stringify(deletedSerialIds));
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                html: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message || 'Failed to delete serial number.',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while deleting the serial number.',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                });
            }
        });
    });
    
    function updateStockDisplay(total, assigned, available) {
        // Update the stock info boxes
        $('.stock-info-box .value').eq(0).text(total);
        $('.stock-info-box .value').eq(1).text(assigned);
        $('.stock-info-box .value').eq(2).text(available);
        
        // Also update the serial count badge
        $('.section-title .badge.bg-primary').text('Total: ' + total);
    }
    
    function updateSerialCounters() {
        const rowCount = $('.serial-row').length;
        if(rowCount === 1) {
            $('.remove-serial, .ajax-delete-serial').prop('disabled', true);
        }
        // Update serial counter
        serialCounter = rowCount;
    }
    
    function escapeHtml(text) {
        if(!text) return '';
        return String(text).replace(/[&<>]/g, function(m) {
            if(m === '&') return '&amp;';
            if(m === '<') return '&lt;';
            if(m === '>') return '&gt;';
            return m;
        });
    }
    
    // ============================================
    // FORM SUBMIT - Include deleted serials
    // ============================================
    $('#itemForm').submit(function(e) {
        // Check for empty serial numbers
        let hasEmptySerial = false;
        $('.serial-input').each(function() {
            if($(this).val().trim() === '') {
                hasEmptySerial = true;
            }
        });
        
        if(hasEmptySerial) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Empty Serial Number',
                text: 'Please fill in all serial number fields.',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }
        
        // Set the delete serials hidden field
        $('#deleteSerialsHidden').val(JSON.stringify(deletedSerialIds));
        
        return true;
    });
    
    // ============================================
    // INITIALIZE
    // ============================================
    updateRemoveSerialButtons();
});
</script>

<?php include '../../includes/footer.php'; ?>