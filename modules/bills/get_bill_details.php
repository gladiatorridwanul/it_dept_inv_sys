<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid bill ID']);
    exit;
}

try {
    // Get bill details
    $stmt = $pdo->prepare("
        SELECT b.*, v.vendor_name, v.phone as vendor_phone, v.office_address, v.tin_no, v.bin_no
        FROM bills b 
        JOIN vendors v ON b.vendor_id = v.id 
        WHERE b.id = ?
    ");
    $stmt->execute([$id]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$bill) {
        echo json_encode(['success' => false, 'message' => 'Bill not found']);
        exit;
    }
    
    // Get bill items - FIX: Properly fetch items from bill_items table
    // First try to get items from bill_items table
    $stmt = $pdo->prepare("
        SELECT bi.*, i.name, i.item_code 
        FROM bill_items bi
        LEFT JOIN items i ON bi.item_id = i.id
        WHERE bi.bill_id = ?
    ");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no items in bill_items, try to get from stock_in_items
    if(empty($items)) {
        $stmt = $pdo->prepare("
            SELECT sii.*, i.name, i.item_code
            FROM stock_in_items sii
            LEFT JOIN items i ON sii.item_id = i.id
            WHERE sii.stock_in_id = (
                SELECT stock_in_id FROM bills WHERE id = ?
            )
        ");
        $stmt->execute([$id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // If still no items, try to get from stock_in directly
    if(empty($items)) {
        $stmt = $pdo->prepare("
            SELECT si.*, i.name, i.item_code
            FROM stock_in si
            LEFT JOIN items i ON si.item_id = i.id
            WHERE si.bill_id = ?
        ");
        $stmt->execute([$id]);
        $stockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert stock_in format to bill_items format
        foreach($stockItems as $stock) {
            $items[] = [
                'id' => $stock['id'],
                'bill_id' => $id,
                'item_id' => $stock['item_id'],
                'quantity' => $stock['quantity'] ?? 1,
                'unit_price' => $stock['unit_price'] ?? 0,
                'total_price' => $stock['total_amount'] ?? 0,
                'name' => $stock['name'] ?? 'Unknown Item',
                'item_code' => $stock['item_code'] ?? 'N/A'
            ];
        }
    }
    
    // Get payment history
    $stmt = $pdo->prepare("
        SELECT * FROM bill_payments 
        WHERE bill_id = ? 
        ORDER BY payment_date DESC, created_at DESC
    ");
    $stmt->execute([$id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment slips
    $stmt = $pdo->prepare("
        SELECT * FROM payment_slips 
        WHERE bill_id = ? 
        ORDER BY payment_date DESC, created_at DESC
    ");
    $stmt->execute([$id]);
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Merge payments and slips
    $allPayments = [];
    
    // Add from bill_payments
    foreach($payments as $payment) {
        $allPayments[] = [
            'slip_no' => 'PAY-' . str_pad($payment['id'], 6, '0', STR_PAD_LEFT),
            'payment_date' => $payment['payment_date'],
            'payment_amount' => $payment['amount'],
            'payment_mode' => $payment['payment_mode'],
            'cheque_no' => $payment['cheque_no'] ?? null,
            'transaction_id' => $payment['transaction_id'] ?? null,
            'reference_no' => $payment['reference_no'] ?? null,
            'notes' => $payment['notes'] ?? null
        ];
    }
    
    // Add from payment_slips
    foreach($slips as $slip) {
        $allPayments[] = [
            'slip_no' => $slip['slip_no'],
            'payment_date' => $slip['payment_date'],
            'payment_amount' => $slip['payment_amount'],
            'payment_mode' => $slip['payment_mode'],
            'cheque_no' => $slip['cheque_no'] ?? null,
            'transaction_id' => $slip['transaction_id'] ?? null,
            'reference_no' => null,
            'notes' => $slip['notes'] ?? null
        ];
    }
    
    // Sort payments by date (newest first)
    usort($allPayments, function($a, $b) {
        return strtotime($b['payment_date']) - strtotime($a['payment_date']);
    });
    
    echo json_encode([
        'success' => true,
        'bill' => $bill,
        'items' => $items,
        'payments' => $allPayments
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>