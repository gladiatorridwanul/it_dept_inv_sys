<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pf_no'])) {
    $pf_no = trim($_POST['pf_no']);
    
    $stmt = $pdo->prepare("SELECT id, pf_no, full_name, designation, department, phone, email FROM employees WHERE pf_no = ? AND is_active = 1");
    $stmt->execute([$pf_no]);
    $employee = $stmt->fetch();
    
    if($employee) {
        echo json_encode([
            'success' => true,
            'data' => $employee
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Employee not found. Please check your PF number.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>