<?php
// ============================================
// IMPORTANT: Session fix MUST be FIRST
// ============================================
require_once '../../../config/session_fix.php';

// Force session restore for AJAX
if (!isset($_SESSION['user_id'])) {
    if (restoreSession()) {
        error_log("AJAX delete_serial: Session restored");
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Session expired. Please refresh the page and try again.'
        ]);
        exit();
    }
}

require_once '../../../includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'You must be logged in to delete serial numbers.'
    ]);
    exit();
}

header('Content-Type: application/json');

$serial_id = isset($_POST['serial_id']) ? intval($_POST['serial_id']) : 0;
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

if(!$serial_id || !$item_id) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid serial number or item ID.'
    ]);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if serial is assigned or damaged
    $checkStmt = $pdo->prepare("
        SELECT is_assigned, damage_id FROM item_serial_numbers 
        WHERE id = ? AND item_id = ?
    ");
    $checkStmt->execute([$serial_id, $item_id]);
    $serial = $checkStmt->fetch();
    
    if(!$serial) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Serial number not found.'
        ]);
        exit();
    }
    
    if($serial['is_assigned'] == 1) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete an assigned serial number.'
        ]);
        exit();
    }
    
    if($serial['is_assigned'] == 2 || !empty($serial['damage_id'])) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete a damaged serial number.'
        ]);
        exit();
    }
    
    // Delete the serial number
    $stmt = $pdo->prepare("DELETE FROM item_serial_numbers WHERE id = ? AND item_id = ?");
    $stmt->execute([$serial_id, $item_id]);
    $deleted_count = $stmt->rowCount();
    
    if($deleted_count == 0) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Serial number not found or could not be deleted.'
        ]);
        exit();
    }
    
    // Update stock quantities
    // Get updated count of serial numbers
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM item_serial_numbers WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $new_current_qty = $stmt->fetch()['count'];
    
    // Get assigned count from assignments table
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantity), 0) as total_assigned 
        FROM assignments 
        WHERE item_id = ? AND status = 'assigned' AND return_status = 'active'
    ");
    $stmt->execute([$item_id]);
    $total_assigned = $stmt->fetch()['total_assigned'];
    
    // Get returned count
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantity), 0) as total_returned 
        FROM assignments 
        WHERE item_id = ? AND (status = 'returned' OR return_status = 'returned')
    ");
    $stmt->execute([$item_id]);
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
    $stmt->execute([$new_current_qty, $total_assigned, $total_returned, $available_qty, $item_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Serial number deleted successfully!',
        'total_serials' => $new_current_qty,
        'assigned' => $total_assigned,
        'available' => $available_qty
    ]);
    
} catch(Exception $e) {
    $pdo->rollBack();
    error_log("Error deleting serial: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting serial number: ' . $e->getMessage()
    ]);
}
?>