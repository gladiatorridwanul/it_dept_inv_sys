<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fix the path - use absolute path from document root
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

header('Content-Type: application/json');

$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

if($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

try {
    $serials = [];
    
    // Get from item_serial_numbers
    $sql1 = "
        SELECT 
            isn.id as serial_id,
            isn.serial_number,
            isn.model_number,
            isn.version,
            isn.is_assigned,
            isn.assigned_to,
            isn.assignment_id,
            a.assignment_no,
            a.assigned_date,
            a.source,
            e.id as employee_id,
            e.full_name as employee_name,
            e.pf_no,
            e.designation,
            e.department,
            e.phone,
            e.email
        FROM item_serial_numbers isn
        LEFT JOIN assignments a ON isn.assignment_id = a.id
        LEFT JOIN employees e ON isn.assigned_to = e.id
        WHERE isn.item_id = ?
        AND isn.is_assigned = 1
    ";
    
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([$item_id]);
    $results = $stmt1->fetchAll();
    
    foreach($results as $row) {
        $serials[] = [
            'source' => $row['source'] ?? 'admin',
            'assignment_id' => $row['assignment_id'] ?? 0,
            'assignment_no' => $row['assignment_no'] ?? '',
            'assigned_date' => $row['assigned_date'] ?? '',
            'employee_id' => $row['employee_id'] ?? 0,
            'employee_name' => $row['employee_name'] ?? 'Not assigned',
            'pf_no' => $row['pf_no'] ?? '',
            'designation' => $row['designation'] ?? '',
            'department' => $row['department'] ?? '',
            'phone' => $row['phone'] ?? '',
            'email' => $row['email'] ?? '',
            'serial_id' => $row['serial_id'],
            'serial_number' => $row['serial_number'] ?? '',
            'model_number' => $row['model_number'] ?? '',
            'version' => $row['version'] ?? '',
            'is_assigned' => $row['is_assigned']
        ];
    }
    
    // If no serials found, try getting from assignments directly
    if(empty($serials)) {
        $sql2 = "
            SELECT 
                a.id as assignment_id,
                a.assignment_no,
                a.assigned_date,
                a.source,
                a.employee_id,
                e.full_name as employee_name,
                e.pf_no,
                e.designation,
                e.department,
                e.phone,
                e.email,
                i.serial_number,
                i.model_number
            FROM assignments a
            JOIN employees e ON a.employee_id = e.id
            JOIN items i ON a.item_id = i.id
            WHERE a.item_id = ?
            AND a.status = 'assigned'
            AND (a.return_status IS NULL OR a.return_status = 'active')
        ";
        
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$item_id]);
        $direct_results = $stmt2->fetchAll();
        
        foreach($direct_results as $row) {
            $serial_no = $row['serial_number'] ?: $row['assignment_no'];
            $serials[] = [
                'source' => $row['source'] ?? 'admin',
                'assignment_id' => $row['assignment_id'],
                'assignment_no' => $row['assignment_no'],
                'assigned_date' => $row['assigned_date'],
                'employee_id' => $row['employee_id'],
                'employee_name' => $row['employee_name'],
                'pf_no' => $row['pf_no'],
                'designation' => $row['designation'],
                'department' => $row['department'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'serial_id' => 0,
                'serial_number' => $serial_no,
                'model_number' => $row['model_number'] ?? '',
                'version' => '',
                'is_assigned' => 1
            ];
        }
    }
    
    // Remove duplicates
    $seen = [];
    $unique_serials = [];
    foreach($serials as $serial) {
        $key = $serial['serial_number'] . '_' . $serial['assignment_id'];
        if(!isset($seen[$key]) && !empty($serial['serial_number'])) {
            $seen[$key] = true;
            $unique_serials[] = $serial;
        }
    }
    
    echo json_encode([
        'success' => true,
        'serials' => $unique_serials,
        'total' => count($unique_serials),
        'debug' => ['item_id' => $item_id, 'found' => count($unique_serials)]
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