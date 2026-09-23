<?php
require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['employee_id'])) {
    $employee_id = $_POST['employee_id'];
    
    // Get only devices that are currently assigned to this employee
    $stmt = $pdo->prepare("SELECT a.id as assignment_id, a.assignment_no, a.assigned_date, 
                           i.id as item_id, i.item_code, i.name, i.serial_number, i.model_number, i.specification
                           FROM assignments a 
                           JOIN items i ON a.item_id = i.id 
                           WHERE a.employee_id = ? AND a.status = 'assigned'");
    $stmt->execute([$employee_id]);
    $devices = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'devices' => $devices]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit();
?>