<?php
require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$assignment_id = $_POST['assignment_id'] ?? 0;
if (!$assignment_id) {
    echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               i.name as item_name, i.item_code, i.brand,
               isn.serial_number, isn.model_number, isn.version,
               e.full_name as employee_name, e.pf_no, e.designation, e.department,
               u.full_name as assigned_by_name
        FROM assignments a
        LEFT JOIN items i ON a.item_id = i.id
        LEFT JOIN item_serial_numbers isn ON a.id = isn.assignment_id
        LEFT JOIN employees e ON a.employee_id = e.id
        LEFT JOIN users u ON a.assigned_by = u.id
        WHERE a.id = ?
    ");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($assignment) {
        $status_class = match($assignment['status']) {
            'assigned' => 'success',
            'returned' => 'info',
            'damaged' => 'danger',
            'replaced' => 'warning',
            default => 'secondary'
        };
        
        echo json_encode([
            'success' => true,
            'assignment_no' => $assignment['assignment_no'],
            'employee_name' => $assignment['employee_name'],
            'pf_no' => $assignment['pf_no'],
            'designation' => $assignment['designation'],
            'department' => $assignment['department'],
            'item_name' => $assignment['item_name'],
            'item_code' => $assignment['item_code'],
            'brand' => $assignment['brand'],
            'serial_number' => $assignment['serial_number'],
            'model_number' => $assignment['model_number'],
            'version' => $assignment['version'],
            'quantity' => $assignment['quantity'],
            'assigned_date' => date('d-m-Y', strtotime($assignment['assigned_date'])),
            'expected_return_date' => $assignment['expected_return_date'] ? date('d-m-Y', strtotime($assignment['expected_return_date'])) : null,
            'status' => ucfirst($assignment['status']),
            'status_class' => $status_class,
            'notes' => $assignment['notes'],
            'assigned_by_name' => $assignment['assigned_by_name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>