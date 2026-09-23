<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$damage_id = isset($_POST['damage_id']) ? intval($_POST['damage_id']) : 0;

if($damage_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid damage ID']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Get current damage record with item and serial details
    $stmt = $pdo->prepare("
        SELECT d.*, 
               isn.id as serial_id,
               isn.serial_number,
               isn.version as serial_version,
               isn.is_assigned
        FROM damages d
        LEFT JOIN item_serial_numbers isn ON d.item_id = isn.item_id 
            AND d.employee_id = isn.assigned_to
        WHERE d.id = ?
    ");
    $stmt->execute([$damage_id]);
    $damage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$damage) {
        throw new Exception('Damage record not found');
    }
    
    $user_id = $_SESSION['user_id'];
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    switch($action) {
        case 'update_status':
            $new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
            
            if(empty($new_status)) {
                throw new Exception('Status is required');
            }
            
            if($new_status == 'approved') {
                // ============================================
                // WHEN APPROVED: Increment version for damaged serial
                // ============================================
                if($damage['serial_id'] > 0) {
                    // Get current version
                    $ver_stmt = $pdo->prepare("SELECT version FROM item_serial_numbers WHERE id = ?");
                    $ver_stmt->execute([$damage['serial_id']]);
                    $current_ver = $ver_stmt->fetchColumn();
                    
                    // Increment version (e.g., 1.0 -> 1.1, or 2.0 -> 2.1)
                    $new_version = $current_ver ? incrementVersion($current_ver) : '1.0';
                    
                    $update_serial_stmt = $pdo->prepare("
                        UPDATE item_serial_numbers 
                        SET version = ? 
                        WHERE id = ?
                    ");
                    $update_serial_stmt->execute([$new_version, $damage['serial_id']]);
                    
                    // Log version update
                    $log_stmt = $pdo->prepare("
                        INSERT INTO system_logs (user_id, action, message, table_name, record_id) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $log_stmt->execute([
                        $user_id,
                        'serial_version_updated_damage',
                        "Serial ID {$damage['serial_id']} version updated from {$current_ver} to {$new_version} (Damage Approved: {$damage['damage_no']})",
                        'item_serial_numbers',
                        $damage['serial_id']
                    ]);
                }
                
                // Update damage status to approved
                $update_stmt = $pdo->prepare("
                    UPDATE damages 
                    SET status = ?, 
                        approved_by = ?, 
                        approved_date = CURDATE(),
                        repair_notes = CONCAT(IFNULL(repair_notes, ''), '\n', ?)
                    WHERE id = ?
                ");
                $update_stmt->execute([$new_status, $user_id, $notes, $damage_id]);
                
                $response = ['success' => true, 'message' => 'Damage successfully approved! Serial version updated.'];
                
            } elseif($new_status == 'rejected') {
                // Update damage status to rejected
                $update_stmt = $pdo->prepare("
                    UPDATE damages 
                    SET status = ?, 
                        approved_by = ?, 
                        approved_date = CURDATE(),
                        repair_notes = CONCAT(IFNULL(repair_notes, ''), '\n', ?)
                    WHERE id = ?
                ");
                $update_stmt->execute([$new_status, $user_id, $notes, $damage_id]);
                
                $response = ['success' => true, 'message' => 'Damage rejected successfully'];
            }
            break;
            
        case 'repair':
            $actual_cost = isset($_POST['actual_cost']) ? floatval($_POST['actual_cost']) : 0;
            $repair_notes = isset($_POST['repair_notes']) ? trim($_POST['repair_notes']) : '';
            $repaired_by = isset($_POST['repaired_by']) ? trim($_POST['repaired_by']) : '';
            $repaired_date = isset($_POST['repaired_date']) ? $_POST['repaired_date'] : date('Y-m-d');
            
            if(empty($repair_notes)) {
                throw new Exception('Repair notes are required');
            }
            
            // ============================================
            // WHEN REPAIRED: Increment version and mark as available
            // ============================================
            if($damage['serial_id'] > 0) {
                // Get current version
                $ver_stmt = $pdo->prepare("SELECT version FROM item_serial_numbers WHERE id = ?");
                $ver_stmt->execute([$damage['serial_id']]);
                $current_ver = $ver_stmt->fetchColumn();
                
                // Increment version (e.g., 1.1 -> 1.2, or 2.1 -> 2.2)
                $new_version = $current_ver ? incrementVersion($current_ver) : '1.0';
                
                // Update serial: new version, mark as available (is_assigned = 0)
                $update_serial_stmt = $pdo->prepare("
                    UPDATE item_serial_numbers 
                    SET version = ?,
                        is_assigned = 0,
                        assigned_to = NULL,
                        assigned_date = NULL,
                        assignment_id = NULL,
                        damage_id = NULL
                    WHERE id = ?
                ");
                $update_serial_stmt->execute([$new_version, $damage['serial_id']]);
                
                // Log version update
                $log_stmt = $pdo->prepare("
                    INSERT INTO system_logs (user_id, action, message, table_name, record_id) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $log_stmt->execute([
                    $user_id,
                    'serial_version_updated_repaired',
                    "Serial ID {$damage['serial_id']} version updated from {$current_ver} to {$new_version} (Repaired: {$damage['damage_no']}) and marked available",
                    'item_serial_numbers',
                    $damage['serial_id']
                ]);
            }
            
            // Update damage status to repaired
            $update_stmt = $pdo->prepare("
                UPDATE damages 
                SET status = 'repaired',
                    actual_cost = ?,
                    repair_notes = ?,
                    repaired_by = ?,
                    repaired_date = ?,
                    approved_by = COALESCE(approved_by, ?),
                    approved_date = COALESCE(approved_date, CURDATE())
                WHERE id = ?
            ");
            $update_stmt->execute([$actual_cost, $repair_notes, $repaired_by, $repaired_date, $user_id, $damage_id]);
            
            // Update item available quantity
            if($damage['item_id'] > 0) {
                $item_stmt = $pdo->prepare("SELECT current_qty, available_qty FROM items WHERE id = ?");
                $item_stmt->execute([$damage['item_id']]);
                $item = $item_stmt->fetch(PDO::FETCH_ASSOC);
                
                if($item) {
                    $new_available_qty = ($item['available_qty'] ?? 0) + 1;
                    
                    $update_item_stmt = $pdo->prepare("
                        UPDATE items 
                        SET available_qty = ?,
                            current_qty = current_qty + 1
                        WHERE id = ?
                    ");
                    $update_item_stmt->execute([$new_available_qty, $damage['item_id']]);
                }
            }
            
            $response = ['success' => true, 'message' => 'Damage marked as repaired successfully! Serial version updated and item is now available.'];
            break;
            
        case 'replace':
            $replacement_item_id = isset($_POST['replacement_item_id']) ? intval($_POST['replacement_item_id']) : 0;
            $actual_cost = isset($_POST['actual_cost']) ? floatval($_POST['actual_cost']) : 0;
            $repair_notes = isset($_POST['repair_notes']) ? trim($_POST['repair_notes']) : '';
            
            if($replacement_item_id <= 0) {
                throw new Exception('Please select a replacement item');
            }
            
            // ============================================
            // WHEN REPLACED: Mark damaged serial as damaged status (is_assigned = 2)
            // ============================================
            if($damage['serial_id'] > 0) {
                // Mark serial as damaged (is_assigned = 2 means damaged/unusable)
                $update_serial_stmt = $pdo->prepare("
                    UPDATE item_serial_numbers 
                    SET is_assigned = 2,
                        assigned_to = NULL,
                        assigned_date = NULL,
                        assignment_id = NULL,
                        damage_id = NULL
                    WHERE id = ?
                ");
                $update_serial_stmt->execute([$damage['serial_id']]);
                
                // Log
                $log_stmt = $pdo->prepare("
                    INSERT INTO system_logs (user_id, action, message, table_name, record_id) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $log_stmt->execute([
                    $user_id,
                    'serial_marked_damaged_replaced',
                    "Serial ID {$damage['serial_id']} marked as damaged (replaced) for Damage: {$damage['damage_no']}",
                    'item_serial_numbers',
                    $damage['serial_id']
                ]);
            }
            
            // Update damage status to replaced
            $update_stmt = $pdo->prepare("
                UPDATE damages 
                SET status = 'replaced',
                    replacement_item_id = ?,
                    actual_cost = ?,
                    repair_notes = CONCAT(IFNULL(repair_notes, ''), '\n', ?),
                    approved_by = COALESCE(approved_by, ?),
                    approved_date = COALESCE(approved_date, CURDATE())
                WHERE id = ?
            ");
            $update_stmt->execute([$replacement_item_id, $actual_cost, $repair_notes, $user_id, $damage_id]);
            
            // Update stock for replacement
            $update_item_stmt = $pdo->prepare("
                UPDATE items 
                SET available_qty = available_qty - 1,
                    current_qty = current_qty - 1
                WHERE id = ?
            ");
            $update_item_stmt->execute([$replacement_item_id]);
            
            $response = ['success' => true, 'message' => 'Damage marked as replaced successfully!'];
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode($response);
    
} catch(Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Helper function to increment version number
 * e.g., 1.0 -> 1.1, 1.5 -> 1.6, 2.0 -> 2.1
 */
function incrementVersion($version) {
    if(empty($version)) {
        return '1.0';
    }
    
    // Check if version contains a dot
    if(strpos($version, '.') !== false) {
        list($major, $minor) = explode('.', $version);
        $minor = intval($minor) + 1;
        return $major . '.' . $minor;
    } else {
        // If no dot, just add .1
        return $version . '.1';
    }
}
?>