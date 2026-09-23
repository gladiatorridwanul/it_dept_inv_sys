<?php
// modules/unlisted_devices/ajax/process_device.php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/session_fix.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

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
$action = isset($_POST['action']) ? $_POST['action'] : '';
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';

if($device_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid device ID']);
    exit();
}

if($action != 'add_to_stock' && $action != 'approve' && $action != 'reject') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Get device details with submission and employee info
    $stmt = $pdo->prepare("
        SELECT sd.*, s.employee_id, s.submission_no, e.full_name as employee_name, e.pf_no
        FROM submission_devices sd
        JOIN unlisted_device_submissions s ON sd.submission_id = s.id
        JOIN employees e ON s.employee_id = e.id
        WHERE sd.id = ?
    ");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$device) {
        throw new Exception("Device not found");
    }
    
    // Check if already added to stock
    if($device['status'] == 'added_to_stock') {
        throw new Exception("Device already added to stock. Cannot modify.");
    }
    
    if($action == 'reject') {
        $stmt = $pdo->prepare("
            UPDATE submission_devices 
            SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?)
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $admin_notes, $device_id]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Device rejected successfully!"]);
        exit();
    }
    
    if($action == 'approve') {
        $update_notes = "Approved on " . date('Y-m-d H:i:s');
        if(!empty($admin_notes)) {
            $update_notes .= " | Note: $admin_notes";
        }
        
        $stmt = $pdo->prepare("
            UPDATE submission_devices 
            SET status = 'approved', 
                admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?),
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$update_notes, $_SESSION['user_id'], $device_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "✅ Device approved successfully!"
        ]);
        exit();
    }
    
    if($action == 'add_to_stock') {
        // Get the existing item ID from submission_devices.new_item_id
        $existing_item_id = $device['new_item_id'];
        $final_quantity = $device['final_quantity'] ?: $quantity;
        $final_price = ($price > 0) ? $price : ($device['final_price'] ?: 0);
        
        if($final_price <= 0) {
            throw new Exception("Price is required to add device to stock");
        }
        
        if($existing_item_id && $existing_item_id > 0) {
            // =============================================
            // LINKED TO EXISTING ITEM - Just update quantity
            // =============================================
            $existing_item = $pdo->prepare("SELECT * FROM items WHERE id = ? AND is_active = 1");
            $existing_item->execute([$existing_item_id]);
            $item = $existing_item->fetch();
            
            if(!$item) {
                throw new Exception("Linked item not found with ID: " . $existing_item_id);
            }
            
            $old_stock = $item['current_qty'];
            
            // Update stock quantity
            $stmt = $pdo->prepare("
                UPDATE items SET 
                    current_qty = current_qty + ?,
                    available_qty = available_qty + ?
                WHERE id = ?
            ");
            $stmt->execute([$final_quantity, $final_quantity, $existing_item_id]);
            
            // Add serial number if provided
            if(!empty($device['serial_number'])) {
                try {
                    $check_serial = $pdo->prepare("SELECT id FROM item_serial_numbers WHERE serial_number = ? AND item_id = ?");
                    $check_serial->execute([$device['serial_number'], $existing_item_id]);
                    if(!$check_serial->fetch()) {
                        $stmt = $pdo->prepare("
                            INSERT INTO item_serial_numbers (item_id, serial_number, model_number, version, is_assigned, assigned_to, assigned_date, created_at)
                            VALUES (?, ?, ?, '1.0', 1, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $existing_item_id, 
                            $device['serial_number'], 
                            $device['model_number'], 
                            $device['employee_id'], 
                            date('Y-m-d')
                        ]);
                    }
                } catch(Exception $e) {
                    error_log("Could not add to item_serial_numbers: " . $e->getMessage());
                }
            }
            
            // Create assignment
            $assignment_no = 'ASN-' . date('Ymd') . '-' . rand(1000, 9999);
            $stmt = $pdo->prepare("
                INSERT INTO assignments (
                    assignment_no, employee_id, item_id, quantity, assigned_date, 
                    status, notes, assigned_by, created_at, return_status
                ) VALUES (?, ?, ?, ?, ?, 'assigned', ?, ?, NOW(), 'active')
            ");
            $stmt->execute([
                $assignment_no,
                $device['employee_id'],
                $existing_item_id,
                $final_quantity,
                $device['purchase_date'] ?: date('Y-m-d'),
                "Device added from unlisted submission. Linked to existing item: " . $item['item_code'],
                $_SESSION['user_id']
            ]);
            
            $update_notes = "Processed on " . date('Y-m-d H:i:s') . " | ADDED TO EXISTING ITEM: " . $item['item_code'] . " (" . $item['name'] . ") | Qty: $final_quantity | Assignment: $assignment_no";
            if(!empty($admin_notes)) {
                $update_notes .= " | Note: $admin_notes";
            }
            
            $stmt = $pdo->prepare("
                UPDATE submission_devices 
                SET status = 'added_to_stock', 
                    final_quantity = ?,
                    final_price = ?,
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?),
                    reviewed_by = ?,
                    reviewed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$final_quantity, $final_price, $update_notes, $_SESSION['user_id'], $device_id]);
            
            $new_stock = $item['current_qty'] + $final_quantity;
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => "✅ <strong>DEVICE ADDED TO EXISTING STOCK!</strong><br>
                             <hr>
                             <strong>Item:</strong> " . htmlspecialchars($item['name']) . " (<strong>{$item['item_code']}</strong>)<br>
                             <strong>Quantity Added:</strong> $final_quantity<br>
                             <strong>Previous Stock:</strong> " . $old_stock . "<br>
                             <strong>New Total Stock:</strong> $new_stock<br>
                             <strong>Assignment No:</strong> $assignment_no"
            ]);
            
        } else {
            // =============================================
            // NO EXISTING ITEM LINKED - Create NEW item
            // =============================================
            
            $code_stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(item_code, LOCATE('-', item_code) + 1) AS UNSIGNED)) as max_num FROM items WHERE item_code LIKE 'ITM-%'");
            $result = $code_stmt->fetch();
            $next_num = ($result['max_num'] ?? 0) + 1;
            $item_code = 'ITM-' . str_pad($next_num, 6, '0', STR_PAD_LEFT);
            
            $warranty_months = !empty($device['final_warranty_months']) ? $device['final_warranty_months'] : 12;
            $purchase_date = !empty($device['purchase_date']) ? $device['purchase_date'] : date('Y-m-d');
            $warranty_end_date = date('Y-m-d', strtotime("+$warranty_months months", strtotime($purchase_date)));
            
            $category_id = $device['final_category_id'] ?: $device['category_id'];
            $sub_category_id = $device['final_sub_category_id'] ?: $device['sub_category_id'];
            $brand_id = $device['final_brand_id'] ?: $device['brand_id'];
            
            $stmt = $pdo->prepare("
                INSERT INTO items (
                    item_code, name, specification, type_id, category_id, sub_category_id,
                    serial_number, model_number, brand_id, warranty_period, price,
                    regular_price, current_qty, available_qty, total_assigned, total_returned,
                    min_qty, is_active, created_by, purchase_date, warranty_end_date, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, 0, ?, 0,
                    1, 1, ?, ?, ?, NOW()
                )
            ");
            
            $stmt->execute([
                $item_code,
                $device['device_name'],
                $device['specification'],
                $device['item_type_id'],
                $category_id,
                $sub_category_id,
                $device['serial_number'],
                $device['model_number'],
                $brand_id,
                $warranty_months,
                $final_price,
                $final_price,
                $final_quantity,
                $final_quantity,
                $_SESSION['user_id'],
                $purchase_date,
                $warranty_end_date
            ]);
            
            $new_item_id = $pdo->lastInsertId();
            
            if(!empty($device['serial_number'])) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO item_serial_numbers (item_id, serial_number, model_number, version, is_assigned, assigned_to, assigned_date, created_at)
                        VALUES (?, ?, ?, '1.0', 1, ?, ?, NOW())
                    ");
                    $stmt->execute([$new_item_id, $device['serial_number'], $device['model_number'], $device['employee_id'], date('Y-m-d')]);
                } catch(Exception $e) {
                    error_log("Could not add to item_serial_numbers: " . $e->getMessage());
                }
            }
            
            $assignment_no = 'ASN-' . date('Ymd') . '-' . rand(1000, 9999);
            $stmt = $pdo->prepare("
                INSERT INTO assignments (
                    assignment_no, employee_id, item_id, quantity, assigned_date, 
                    status, notes, assigned_by, created_at, return_status
                ) VALUES (?, ?, ?, ?, ?, 'assigned', ?, ?, NOW(), 'active')
            ");
            $stmt->execute([
                $assignment_no,
                $device['employee_id'],
                $new_item_id,
                $final_quantity,
                $purchase_date,
                "Device added from unlisted submission. NEW item created.",
                $_SESSION['user_id']
            ]);
            
            $update_notes = "Processed on " . date('Y-m-d H:i:s') . " | NEW ITEM: $item_code | Qty: $final_quantity | Assignment: $assignment_no";
            if(!empty($admin_notes)) {
                $update_notes .= " | Note: $admin_notes";
            }
            
            $stmt = $pdo->prepare("
                UPDATE submission_devices 
                SET status = 'added_to_stock', 
                    new_item_id = ?,
                    final_quantity = ?,
                    final_price = ?,
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?),
                    reviewed_by = ?,
                    reviewed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$new_item_id, $final_quantity, $final_price, $update_notes, $_SESSION['user_id'], $device_id]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => "✅ NEW item created!<br>
                             <hr>
                             <strong>Item Code:</strong> $item_code<br>
                             <strong>Item Name:</strong> " . htmlspecialchars($device['device_name']) . "<br>
                             <strong>Quantity:</strong> $final_quantity<br>
                             <strong>Assignment No:</strong> $assignment_no"
            ]);
        }
        
        // Check if all devices in submission are processed
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN status IN ('added_to_stock', 'approved', 'rejected') THEN 1 ELSE 0 END) as processed
            FROM submission_devices 
            WHERE submission_id = ?
        ");
        $stmt->execute([$device['submission_id']]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($status['total'] == $status['processed']) {
            $stmt = $pdo->prepare("UPDATE unlisted_device_submissions SET status = 'added_to_stock' WHERE id = ?");
            $stmt->execute([$device['submission_id']]);
        }
    }
    
} catch(PDOException $e) {
    $pdo->rollBack();
    error_log("PDO Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    $pdo->rollBack();
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>