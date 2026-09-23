<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_POST['employee_id']) || empty($_POST['employee_id'])) {
    echo json_encode(['error' => 'Employee ID required']);
    exit();
}

$employee_id = (int)$_POST['employee_id'];

// Get active assignments for this employee (not returned yet)
$stmt = $pdo->prepare("
    SELECT 
        a.id as assignment_id,
        a.assignment_no,
        a.assigned_date,
        a.quantity,
        i.id as item_id,
        i.item_code,
        i.name,
        i.serial_number,
        i.model_number,
        i.brand_id,
        b.name as brand_name
    FROM assignments a
    JOIN items i ON a.item_id = i.id
    LEFT JOIN brands b ON i.brand_id = b.id
    WHERE a.employee_id = ? 
    AND a.status = 'assigned'
    AND (a.return_status IS NULL OR a.return_status = 'active')
    ORDER BY a.assigned_date DESC
");

$stmt->execute([$employee_id]);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'devices' => $devices,
    'total' => count($devices)
]);
?>