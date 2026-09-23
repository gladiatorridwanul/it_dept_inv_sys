<?php
require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['employee_id'])) {
    $employee_id = $_POST['employee_id'];
    
    $stmt = $pdo->prepare("SELECT pf_no, full_name, designation, department, phone, email FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    
    if($employee) {
        echo json_encode([
            'success' => true,
            'pf_no' => $employee['pf_no'],
            'full_name' => $employee['full_name'],
            'designation' => $employee['designation'],
            'department' => $employee['department'],
            'phone' => $employee['phone'],
            'email' => $employee['email']
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

echo json_encode(['success' => false]);
exit();
?>