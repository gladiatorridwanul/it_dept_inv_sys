<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid stock ID']);
    exit;
}

try {
    // Get stock details
    $stmt = $pdo->prepare("
        SELECT s.*, v.vendor_name, 
               b.bill_no, b.status as bill_status, b.paid_amount, b.balance_amount
        FROM stock_in s
        LEFT JOIN vendors v ON s.vendor_id = v.id
        LEFT JOIN bills b ON s.bill_id = b.id
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$data) {
        echo json_encode(['success' => false, 'message' => 'Stock entry not found']);
        exit;
    }
    
    // Get stock items
    $stmt = $pdo->prepare("
        SELECT sii.*, i.item_code, i.name, i.specification
        FROM stock_in_items sii
        LEFT JOIN items i ON sii.item_id = i.id
        WHERE sii.stock_in_id = ?
    ");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'items' => $items
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}