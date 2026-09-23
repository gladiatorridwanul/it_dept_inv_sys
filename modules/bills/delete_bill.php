<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

// Check authentication
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Check if user is admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if($user['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Only administrators can delete bills']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$bill_id = intval($_POST['id']);

try {
    $pdo->beginTransaction();
    
    // Get bill items to adjust stock
    $stmt = $pdo->prepare("SELECT item_id, quantity FROM bill_items WHERE bill_id = ?");
    $stmt->execute([$bill_id]);
    $bill_items = $stmt->fetchAll();
    
    // Restore stock quantities
    foreach($bill_items as $item) {
        $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty - ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['item_id']]);
    }
    
    // Delete payment slips first (foreign key)
    $stmt = $pdo->prepare("DELETE FROM payment_slips WHERE bill_id = ?");
    $stmt->execute([$bill_id]);
    
    // Delete bill items
    $stmt = $pdo->prepare("DELETE FROM bill_items WHERE bill_id = ?");
    $stmt->execute([$bill_id]);
    
    // Delete the bill
    $stmt = $pdo->prepare("DELETE FROM bills WHERE id = ?");
    $stmt->execute([$bill_id]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Bill deleted successfully']);
    
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>