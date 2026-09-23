<?php
require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}

$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

if(!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Employee ID required']);
    exit();
}

try {
    // Get item IDs that are currently assigned to this employee (active assignments)
    $stmt = $pdo->prepare("SELECT DISTINCT item_id 
                           FROM assignments 
                           WHERE employee_id = ? AND status = 'assigned'");
    $stmt->execute([$employee_id]);
    $assignedItems = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true, 
        'assigned_item_ids' => $assignedItems
    ]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>