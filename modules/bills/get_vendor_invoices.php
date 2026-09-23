<?php
require_once '../../config/session_fix.php';
require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}

$vendor_id = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0;

if(!$vendor_id) {
    echo json_encode(['success' => false, 'message' => 'Vendor ID required']);
    exit();
}

try {
    // FIX: Check ALL conditions where a bill hasn't been created yet
    // Also include 'unbilled' status which might be set in some stock entries
    $stmt = $pdo->prepare("
        SELECT s.*, v.vendor_name 
        FROM stock_in s 
        JOIN vendors v ON s.vendor_id = v.id 
        WHERE s.vendor_id = ? 
        AND (
            s.bill_id IS NULL 
            OR s.bill_id = 0 
            OR s.bill_status IS NULL 
            OR s.bill_status = '' 
            OR s.bill_status = 'pending'
            OR s.bill_status = 'unbilled'
            OR s.bill_status = '0'
            OR s.bill_status = 'null'
        )
        ORDER BY s.id DESC
    ");
    $stmt->execute([$vendor_id]);
    $invoices = $stmt->fetchAll();
    
    // Debug log for troubleshooting
    error_log("get_vendor_invoices: vendor_id=$vendor_id, found=" . count($invoices));
    
    // If no invoices found, try a simpler query to check if there are any records at all
    if(count($invoices) == 0) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as total FROM stock_in WHERE vendor_id = ?");
        $checkStmt->execute([$vendor_id]);
        $total = $checkStmt->fetch()['total'];
        error_log("get_vendor_invoices: vendor_id=$vendor_id, total records in stock_in=$total");
        
        // If there are records but none match the condition, check their bill_status values
        if($total > 0) {
            $statusStmt = $pdo->prepare("SELECT bill_id, bill_status FROM stock_in WHERE vendor_id = ? LIMIT 5");
            $statusStmt->execute([$vendor_id]);
            $sample = $statusStmt->fetchAll();
            error_log("get_vendor_invoices: sample bill_status values: " . json_encode($sample));
        }
    }
    
    echo json_encode(['success' => true, 'invoices' => $invoices]);
} catch(Exception $e) {
    error_log("Error in get_vendor_invoices.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>