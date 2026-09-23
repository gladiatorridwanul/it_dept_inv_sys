<?php
require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

if($item_id > 0) {
    try {
        $serials = [];
        
        // FIRST: Get ALL serials from item_serial_numbers for this item
        // This gets every serial number regardless of assignment status
        $sql = "SELECT 
                    isn.id as serial_id,
                    isn.serial_number,
                    isn.model_number,
                    isn.version,
                    isn.is_assigned,
                    isn.assignment_id,
                    a.assignment_no,
                    a.assigned_date,
                    a.expected_return_date,
                    a.status as assignment_status,
                    e.id as employee_id,
                    e.full_name as employee_name,
                    e.pf_no,
                    e.designation,
                    e.department,
                    e.job_location,
                    e.phone,
                    e.email
                FROM item_serial_numbers isn
                LEFT JOIN assignments a ON isn.assignment_id = a.id
                LEFT JOIN employees e ON a.employee_id = e.id
                WHERE isn.item_id = ?
                ORDER BY isn.serial_number ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$item_id]);
        $serials = $stmt->fetchAll();
        
        // If no serials found in item_serial_numbers
        if(empty($serials)) {
            // SECOND: Check assignments directly for this item
            $sql2 = "SELECT 
                        a.id as assignment_id,
                        a.assignment_no,
                        a.assigned_date,
                        a.expected_return_date,
                        a.status as assignment_status,
                        e.id as employee_id,
                        e.full_name as employee_name,
                        e.pf_no,
                        e.designation,
                        e.department,
                        e.job_location,
                        e.phone,
                        e.email
                    FROM assignments a
                    INNER JOIN employees e ON a.employee_id = e.id
                    WHERE a.item_id = ? AND a.status = 'assigned'
                    ORDER BY a.assigned_date DESC";
            
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([$item_id]);
            $assignments = $stmt2->fetchAll();
            
            // For each assignment, try to find associated serial numbers
            foreach($assignments as $assign) {
                $stmt3 = $pdo->prepare("SELECT serial_number, model_number, version FROM item_serial_numbers WHERE assignment_id = ?");
                $stmt3->execute([$assign['assignment_id']]);
                $assign_serials = $stmt3->fetchAll();
                
                if(!empty($assign_serials)) {
                    foreach($assign_serials as $s) {
                        if(!empty($s['serial_number'])) {
                            $serials[] = [
                                'serial_id' => 0,
                                'serial_number' => $s['serial_number'],
                                'model_number' => $s['model_number'],
                                'version' => $s['version'],
                                'is_assigned' => 1,
                                'assignment_id' => $assign['assignment_id'],
                                'assignment_no' => $assign['assignment_no'],
                                'assigned_date' => $assign['assigned_date'],
                                'assignment_status' => $assign['assignment_status'],
                                'employee_id' => $assign['employee_id'],
                                'employee_name' => $assign['employee_name'],
                                'pf_no' => $assign['pf_no'],
                                'designation' => $assign['designation'],
                                'department' => $assign['department'],
                                'job_location' => $assign['job_location'] ?? '',
                                'phone' => $assign['phone'],
                                'email' => $assign['email']
                            ];
                        }
                    }
                } else {
                    // No serial numbers found for this assignment
                    // Create a placeholder serial from assignment
                    $serials[] = [
                        'serial_id' => 0,
                        'serial_number' => 'SN-' . $assign['assignment_no'],
                        'model_number' => '',
                        'version' => '',
                        'is_assigned' => 1,
                        'assignment_id' => $assign['assignment_id'],
                        'assignment_no' => $assign['assignment_no'],
                        'assigned_date' => $assign['assigned_date'],
                        'assignment_status' => $assign['assignment_status'],
                        'employee_id' => $assign['employee_id'],
                        'employee_name' => $assign['employee_name'],
                        'pf_no' => $assign['pf_no'],
                        'designation' => $assign['designation'],
                        'department' => $assign['department'],
                        'job_location' => $assign['job_location'] ?? '',
                        'phone' => $assign['phone'],
                        'email' => $assign['email']
                    ];
                }
            }
        }
        
        // Filter: Only keep serials that have a valid serial number or assignment
        $validSerials = [];
        foreach($serials as $serial) {
            // Skip if no serial number and no assignment
            if(empty($serial['serial_number']) && empty($serial['assignment_id'])) {
                continue;
            }
            // Skip if serial number is null or empty string
            if(isset($serial['serial_number']) && $serial['serial_number'] === '') {
                continue;
            }
            $validSerials[] = $serial;
        }
        
        // Remove duplicates based on serial number + assignment_id
        $uniqueSerials = [];
        $seenKeys = [];
        foreach($validSerials as $serial) {
            $key = ($serial['serial_number'] ?: '') . '_' . ($serial['assignment_id'] ?: 0);
            if(!in_array($key, $seenKeys)) {
                $seenKeys[] = $key;
                $uniqueSerials[] = $serial;
            }
        }
        
        echo json_encode(['success' => true, 'serials' => $uniqueSerials]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
}
?>