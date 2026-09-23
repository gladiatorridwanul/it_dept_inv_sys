<?php
// Include session fix FIRST - Same as index.php
require_once dirname(__DIR__) . '/config/session_fix.php';
require_once dirname(__DIR__) . '/config/database.php';

// Check login status - Same as index.php
$isLoggedIn = false;
$userName = '';
$userRole = '';

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $isLoggedIn = true;
    $userName = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
    $userRole = $_SESSION['role'] ?? 'staff';
}

// PF Verification variables
$employee_found = false;
$employee_info = null;
$employee_id = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : 
               (isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0);

if($employee_id > 0) {
    $stmt = $pdo->prepare("SELECT id, pf_no, full_name, designation, department, phone, email FROM employees WHERE id = ? AND is_active = 1");
    $stmt->execute([$employee_id]);
    $employee_info = $stmt->fetch();
    if($employee_info) {
        $employee_found = true;
        $_SESSION['employee_id'] = $employee_info['id'];
        $_SESSION['employee_pf'] = $employee_info['pf_no'];
        $_SESSION['employee_name'] = $employee_info['full_name'];
        $_SESSION['employee_designation'] = $employee_info['designation'];
        $_SESSION['employee_department'] = $employee_info['department'];
    }
}

// Handle AJAX request for employee verification
if(isset($_GET['action']) && $_GET['action'] == 'verify_employee' && isset($_GET['pf_no'])) {
    header('Content-Type: application/json');
    $pf_no = trim($_GET['pf_no']);
    
    $stmt = $pdo->prepare("SELECT id, pf_no, full_name, designation, department, phone, email FROM employees WHERE pf_no = ? AND is_active = 1");
    $stmt->execute([$pf_no]);
    $employee = $stmt->fetch();
    
    if($employee) {
        $_SESSION['employee_id'] = $employee['id'];
        $_SESSION['employee_pf'] = $employee['pf_no'];
        $_SESSION['employee_name'] = $employee['full_name'];
        $_SESSION['employee_designation'] = $employee['designation'];
        $_SESSION['employee_department'] = $employee['department'];
        
        echo json_encode(['success' => true, 'data' => $employee]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found with PF Number: ' . $pf_no]);
    }
    exit();
}

// Handle AJAX request to clear employee session (New Verification)
if(isset($_GET['action']) && $_GET['action'] == 'clear_employee_session') {
    header('Content-Type: application/json');
    unset($_SESSION['employee_id']);
    unset($_SESSION['employee_pf']);
    unset($_SESSION['employee_name']);
    unset($_SESSION['employee_designation']);
    unset($_SESSION['employee_department']);
    session_regenerate_id(true);
    echo json_encode(['success' => true]);
    exit();
}

// Handle AJAX request to get employee devices with assignment IDs
if(isset($_GET['action']) && $_GET['action'] == 'get_employee_devices' && isset($_GET['employee_id'])) {
    header('Content-Type: application/json');
    $employee_id = (int)$_GET['employee_id'];
    
    $stmt = $pdo->prepare("
        SELECT 
            a.id as assignment_id,
            a.assignment_no,
            a.assigned_date,
            a.status as assignment_status,
            i.id as item_id, 
            i.name as item_name, 
            i.item_code, 
            i.brand,
            i.specification,
            i.type_id,
            t.name as type_name,
            (SELECT isn.serial_number FROM item_serial_numbers isn 
             WHERE isn.item_id = i.id AND isn.assigned_to = a.employee_id AND isn.is_assigned = 1 
             LIMIT 1) as serial_number,
            (SELECT isn.model_number FROM item_serial_numbers isn 
             WHERE isn.item_id = i.id AND isn.assigned_to = a.employee_id AND isn.is_assigned = 1 
             LIMIT 1) as model_number,
            (SELECT isn.id FROM item_serial_numbers isn 
             WHERE isn.item_id = i.id AND isn.assigned_to = a.employee_id AND isn.is_assigned = 1 
             LIMIT 1) as serial_record_id
        FROM assignments a
        INNER JOIN items i ON a.item_id = i.id
        LEFT JOIN item_types t ON i.type_id = t.id
        WHERE a.employee_id = ? 
            AND a.status = 'assigned'
            AND a.return_status = 'active'
        ORDER BY i.name
    ");
    $stmt->execute([$employee_id]);
    $results = $stmt->fetchAll();
    
    $device_list = [];
    
    foreach($results as $row) {
        $display_text = $row['item_name'];
        if(!empty($row['type_name'])) {
            $display_text .= ' (' . $row['type_name'] . ')';
        }
        if(!empty($row['serial_number']) && $row['serial_number'] != '') {
            $display_text .= ' | SN: ' . $row['serial_number'];
        }
        if(!empty($row['model_number']) && $row['model_number'] != '') {
            $display_text .= ' | Model: ' . $row['model_number'];
        }
        
        $device_list[] = [
            'assignment_id' => $row['assignment_id'],
            'assignment_no' => $row['assignment_no'],
            'assigned_date' => $row['assigned_date'],
            'serial_record_id' => $row['serial_record_id'] ?? 0,
            'item_id' => $row['item_id'],
            'item_name' => $row['item_name'],
            'item_code' => $row['item_code'],
            'type_name' => $row['type_name'] ?? 'N/A',
            'brand' => $row['brand'] ?? 'N/A',
            'serial_number' => $row['serial_number'] ?? 'N/A',
            'model_number' => $row['model_number'] ?? 'N/A',
            'specification' => $row['specification'] ?? 'N/A',
            'display_text' => $display_text
        ];
    }
    
    echo json_encode(['success' => true, 'devices' => $device_list, 'count' => count($device_list)]);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("POST Data: " . print_r($_POST, true));
    
    if(isset($_POST['verified_employee_id']) && !empty($_POST['verified_employee_id'])) {
        $employee_id = (int)$_POST['verified_employee_id'];
    } elseif(isset($_POST['employee_id']) && !empty($_POST['employee_id'])) {
        $employee_id = (int)$_POST['employee_id'];
    } elseif($employee_found && $employee_info) {
        $employee_id = $employee_info['id'];
    } else {
        $employee_id = 0;
    }
    
    $assignment_ids = $_POST['assignment_ids'] ?? [];
    $serial_record_ids = $_POST['serial_record_ids'] ?? [];
    $device_conditions = $_POST['device_conditions'] ?? [];
    $damage_descriptions = $_POST['damage_descriptions'] ?? [];
    $accessories_returned_arr = $_POST['accessories_returned_arr'] ?? [];
    $return_reason = trim($_POST['return_reason'] ?? '');
    $reason_details = trim($_POST['reason_details'] ?? '');
    $cleaning_done = isset($_POST['cleaning_done']) ? 1 : 0;
    $data_backup_confirmed = isset($_POST['data_backup_confirmed']) ? 1 : 0;
    $exit_clearance = isset($_POST['exit_clearance']) ? 1 : 0;
    
    $validation_errors = [];
    if($employee_id <= 0) $validation_errors[] = "Please verify your identity first";
    if(empty($assignment_ids) || count($assignment_ids) == 0) $validation_errors[] = "Please select at least one device to return";
    
    $valid_assignments = [];
    $valid_serials = [];
    foreach($assignment_ids as $index => $assignment_id) {
        if(!empty($assignment_id) && $assignment_id > 0) {
            $valid_assignments[$index] = $assignment_id;
            $valid_serials[$index] = $serial_record_ids[$index] ?? 0;
        }
    }
    
    if(empty($valid_assignments)) {
        $validation_errors[] = "No valid devices selected for return";
    }
    
    if(empty($validation_errors)) {
        $request_no = 'RET-' . date('YmdHis') . rand(100, 999);
        
        $devices_data = [];
        
        foreach($valid_assignments as $index => $assignment_id) {
            $serial_record_id = $valid_serials[$index] ?? 0;
            
            $stmt = $pdo->prepare("
                SELECT 
                    a.id as assignment_id,
                    a.assignment_no,
                    a.assigned_date,
                    i.id as item_id,
                    i.name as item_name,
                    i.item_code,
                    i.specification,
                    i.brand,
                    (SELECT isn.serial_number FROM item_serial_numbers isn 
                     WHERE isn.item_id = i.id AND isn.assigned_to = a.employee_id AND isn.is_assigned = 1 
                     LIMIT 1) as serial_number,
                    (SELECT isn.model_number FROM item_serial_numbers isn 
                     WHERE isn.item_id = i.id AND isn.assigned_to = a.employee_id AND isn.is_assigned = 1 
                     LIMIT 1) as model_number
                FROM assignments a
                INNER JOIN items i ON a.item_id = i.id
                WHERE a.id = ? AND a.employee_id = ? AND a.status = 'assigned'
            ");
            $stmt->execute([$assignment_id, $employee_id]);
            $assignment = $stmt->fetch();
            
            if($assignment) {
                $devices_data[] = [
                    'assignment_id' => $assignment['assignment_id'],
                    'assignment_no' => $assignment['assignment_no'],
                    'item_id' => $assignment['item_id'],
                    'item_name' => $assignment['item_name'],
                    'item_code' => $assignment['item_code'],
                    'serial_number' => $assignment['serial_number'] ?? 'N/A',
                    'model_number' => $assignment['model_number'] ?? 'N/A',
                    'specification' => $assignment['specification'] ?? 'N/A',
                    'brand' => $assignment['brand'] ?? 'N/A',
                    'device_condition' => $device_conditions[$index] ?? 'good',
                    'damage_description' => $damage_descriptions[$index] ?? '',
                    'accessories_returned' => $accessories_returned_arr[$index] ?? ''
                ];
            }
        }
        
        if(empty($devices_data)) {
            $error = "No valid devices found to return. Please make sure the devices are properly assigned to you.";
        } else {
            $request_data = json_encode(['devices' => $devices_data, 'return_reason' => $return_reason]);
            $description = "Device Return Request\nRequest No: $request_no\nTotal Devices: " . count($devices_data) . "\nReturn Reason: $return_reason";
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO requests (request_no, employee_id, request_type, description, request_data, urgency_level, requested_date, status, created_at) 
                                      VALUES (?, ?, 'return_device', ?, ?, 'medium', NOW(), 'pending', NOW())");
                $stmt->execute([$request_no, $employee_id, $description, $request_data]);
                $request_id = $pdo->lastInsertId();
                
                $first_device = $devices_data[0];
                $first_assignment_id = $first_device['assignment_id'];
                
                $stmt = $pdo->prepare("INSERT INTO return_device_requests (request_no, request_id, assignment_id, device_condition, return_reason, reason_details, accessories_returned, damage_description, cleaning_done, data_backup_confirmed, exit_clearance, total_devices, created_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $request_no, 
                    $request_id, 
                    $first_assignment_id, 
                    $device_conditions[array_keys($valid_assignments)[0]] ?? 'good', 
                    $return_reason, 
                    $reason_details, 
                    $accessories_returned_arr[array_keys($valid_assignments)[0]] ?? '', 
                    $damage_descriptions[array_keys($valid_assignments)[0]] ?? '', 
                    $cleaning_done, 
                    $data_backup_confirmed, 
                    $exit_clearance, 
                    count($devices_data)
                ]);
                
                $return_request_id = $pdo->lastInsertId();
                
                foreach($devices_data as $dev) {
                    $stmt = $pdo->prepare("INSERT INTO return_device_items (return_request_id, assignment_id, device_condition, damage_description, accessories_returned, created_at) 
                                          VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $return_request_id, 
                        $dev['assignment_id'], 
                        $dev['device_condition'], 
                        $dev['damage_description'], 
                        $dev['accessories_returned']
                    ]);
                }
                
                foreach($devices_data as $dev) {
                    $stmt = $pdo->prepare("UPDATE assignments SET return_status = 'return_requested', return_request_id = ? WHERE id = ?");
                    $stmt->execute([$return_request_id, $dev['assignment_id']]);
                }
                
                $pdo->commit();
                $success = "Return request submitted successfully! Request No: " . $request_no . " (" . count($devices_data) . " device(s))";
                
            } catch(Exception $e) {
                $pdo->rollBack();
                $error = "Database Error: " . $e->getMessage();
                error_log("Return Device Error: " . $e->getMessage());
            }
        }
    } else {
        $error = implode("<br>", $validation_errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Return Device - IT Support</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', 'Roboto', Arial, sans-serif; background: #f5f7fa; }
        
        .navbar { background: #ffffff; box-shadow: 0 2px 15px rgba(0,0,0,0.08); padding: 0.6rem 0; position: fixed; width: 100%; top: 0; z-index: 1000; }
        .navbar-brand { font-size: 1.3rem; font-weight: 700; color: #3b82f6; }
        .navbar-brand i { color: #3b82f6; margin-right: 8px; }
        
        .navbar-toggler { border: none; padding: 0; }
        .navbar-toggler:focus { box-shadow: none; outline: none; }
        .navbar-toggler-icon { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important; }
        
        .nav-link { font-weight: 500; color: #4b5563; transition: all 0.3s ease; margin: 0 0.6rem; font-size: 0.9rem; }
        .nav-link:hover { color: #3b82f6; transform: translateY(-2px); }
        
        .btn-dashboard, .btn-logout { 
            border: none; 
            padding: 6px 20px; 
            border-radius: 30px; 
            font-weight: 600; 
            font-size: 0.85rem; 
            text-decoration: none; 
            display: inline-block; 
        }
        .btn-dashboard { background: #10b981; color: white; }
        .btn-dashboard:hover { background: #059669; color: white; }
        .btn-logout { background: #ef4444; color: white; }
        .btn-logout:hover { background: #dc2626; color: white; }
        
        .main-content { padding-top: 75px; min-height: calc(100vh - 80px); }
        .form-card { 
            background: white; 
            border-radius: 20px; 
            padding: 30px; 
            max-width: 1000px; 
            margin: 0 auto 30px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.08); 
        }
        .form-header { text-align: center; margin-bottom: 30px; }
        .form-header h2 { 
            font-size: 1.6rem; 
            font-weight: 700; 
            color: #1f2937; 
            margin-bottom: 8px; 
        }
        .form-header .underline { 
            width: 50px; 
            height: 3px; 
            background: #ef4444; 
            margin: 12px auto 0; 
            border-radius: 3px; 
        }
        .form-header p { font-size: 0.85rem; color: #6b7280; margin-top: 10px; }
        
        .form-label { 
            font-weight: 600; 
            color: #334155; 
            margin-bottom: 6px; 
            font-size: 0.85rem; 
        }
        .form-control, .form-select { 
            border: 2px solid #e2e8f0; 
            border-radius: 12px; 
            padding: 10px 15px; 
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus { 
            border-color: #ef4444; 
            box-shadow: 0 0 0 3px rgba(239,68,68,0.1); 
            outline: none; 
        }
        
        .btn-submit { 
            display: inline-block;
            padding: 10px 30px;
            background: #ef4444;
            color: white;
            text-decoration: none;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-submit:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239,68,68,0.3);
            color: white;
        }
        .btn-submit:active { transform: translateY(0px); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        
        .btn-reset { 
            background: #e2e8f0; 
            color: #475569; 
            padding: 10px 30px; 
            border-radius: 40px; 
            font-weight: 600; 
            border: none; 
            margin-left: 15px; 
            font-size: 0.85rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-reset:hover { background: #cbd5e1; color: #1f2937; }
        
        .btn-add-device {
            background: #10b981;
            color: white;
            border: none;
            border-radius: 40px;
            padding: 8px 24px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .btn-add-device:hover {
            background: #059669;
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-remove-device { 
            background: #ef4444; 
            color: white; 
            border: none; 
            padding: 4px 12px; 
            border-radius: 30px; 
            font-size: 0.7rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-remove-device:hover { background: #dc2626; }
        
        footer { background: #1f2937; color: white; padding: 18px 0; margin-top: 40px; }
        .footer-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .copyright { color: #9ca3af; font-size: 0.7rem; }
        .footer-login { text-align: right; }
        .btn-footer-login { 
            background: transparent; 
            border: 1px solid #4b5563; 
            color: #9ca3af; 
            padding: 5px 16px; 
            border-radius: 30px; 
            font-size: 0.7rem; 
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .btn-footer-login:hover { background: #3b82f6; border-color: #3b82f6; color: white; }
        
        .employee-info-card { 
            background: #eff6ff; 
            border-radius: 12px; 
            padding: 15px; 
            margin-bottom: 20px; 
            border-left: 4px solid #3b82f6; 
            position: relative;
        }
        
        .employee-info-card .btn-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .device-item { 
            background: #f8fafc; 
            border-radius: 16px; 
            padding: 20px; 
            margin-bottom: 20px; 
            border: 1px solid #e2e8f0; 
        }
        .device-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 15px; 
            padding-bottom: 10px; 
            border-bottom: 1px solid #e2e8f0; 
        }
        .device-number { font-weight: 700; color: #ef4444; font-size: 0.9rem; }
        
        .device-details-badge {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 10px 12px;
            margin-top: 10px;
        }
        .badge-info-custom {
            background: #e2e8f0;
            color: #1e293b;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-block;
            margin-right: 8px;
            margin-bottom: 4px;
        }
        
        .btn-change-employee {
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 40px;
            padding: 5px 15px;
            font-size: 0.75rem;
            transition: all 0.3s ease;
        }
        .btn-change-employee:hover {
            background: #d97706;
            color: white;
        }
        
        .btn-new-verification {
            background: #8b5cf6;
            color: white;
            border: none;
            border-radius: 40px;
            padding: 5px 15px;
            font-size: 0.75rem;
            transition: all 0.3s ease;
        }
        .btn-new-verification:hover {
            background: #7c3aed;
            color: white;
        }
        
        .alert-success { background: #d1fae5; color: #065f46; border: none; border-radius: 12px; padding: 20px; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: none; border-radius: 12px; padding: 15px; }
        .alert-info { background: #eff6ff; color: #1e40af; border: none; border-radius: 12px; padding: 15px; }
        
        .form-check-label { font-size: 0.85rem; }
        small.text-muted { font-size: 0.7rem; }
        
        .select2-container--default .select2-selection--single {
            border: 2px solid #e2e8f0 !important;
            border-radius: 12px !important;
            height: 45px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 41px !important;
            color: #1e293b !important;
            padding-left: 14px !important;
            font-size: 0.85rem;
        }
        .select2-dropdown { 
            border: 2px solid #e2e8f0 !important; 
            border-radius: 12px !important;
        }
        
        @media (max-width: 576px) {
            .navbar-brand { font-size: 1rem; }
            .nav-link { font-size: 0.8rem; padding: 8px 0; text-align: center; }
            .btn-dashboard, .btn-logout { padding: 6px 16px; font-size: 0.75rem; margin: 5px 0; display: inline-block; width: auto; }
            .navbar-nav { text-align: center; padding: 10px 0; }
            .navbar-nav .nav-item { margin: 5px 0; }
            
            .form-card { padding: 20px; margin: 0 15px 30px; }
            .form-header h2 { font-size: 1.3rem; }
            .form-header p { font-size: 0.7rem; }
            .form-label { font-size: 0.75rem; }
            .form-control, .form-select { font-size: 0.75rem; padding: 8px 12px; }
            
            .btn-submit, .btn-reset { width: 100%; margin: 5px 0; padding: 8px 20px; font-size: 0.75rem; }
            .btn-reset { margin-left: 0; }
            .btn-add-device { width: 100%; margin: 5px 0; padding: 8px 20px; font-size: 0.75rem; }
            .btn-remove-device { padding: 3px 10px; font-size: 0.65rem; }
            
            .main-content { padding-top: 68px; }
            
            .footer-content { flex-direction: row; justify-content: space-between; }
            .copyright p { font-size: 0.6rem; margin: 0; }
            .btn-footer-login { padding: 4px 12px; font-size: 0.6rem; }
            .footer-login { text-align: right; }
            
            .employee-info-card { padding: 10px; font-size: 0.7rem; }
            .employee-info-card .btn-group { flex-direction: column; gap: 5px; margin-top: 10px; }
            .device-item { padding: 15px; }
            .select2-container--default .select2-selection--single { height: 38px !important; }
            .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 34px !important; font-size: 0.75rem; }
            .device-details-badge { padding: 8px; }
            .badge-info-custom { font-size: 0.65rem; padding: 2px 6px; }
            .btn-change-employee, .btn-new-verification { font-size: 0.65rem; padding: 3px 10px; }
        }
        
        @media (min-width: 577px) and (max-width: 992px) {
            .form-card { margin: 0 20px 30px; }
        }
        
        html { scroll-behavior: smooth; }
    </style>
</head>
<body>
    <!-- Navbar - Exactly same as index.php -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-laptop-code"></i> IT Inventory</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php#requests">Request Form</a></li>
                    <li class="nav-item"><a class="nav-link" href="track_request.php"><i class="fas fa-search me-1"></i> Track Request</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_submissions.php"><i class="fas fa-clipboard-list me-1"></i> My Reports</a></li>
                    <?php if($isLoggedIn): ?>
                        <li class="nav-item ms-2"><a class="btn btn-dashboard" href="../modules/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                        <li class="nav-item ms-2"><a class="btn btn-logout" href="../modules/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="form-card">
                <div class="form-header">
                    <h2><i class="fas fa-undo-alt"></i> Return Device</h2>
                    <div class="underline"></div>
                    <p>Return single or multiple devices when leaving the organization or transferring</p>
                </div>

                <?php if(isset($success)): ?>
                    <div class="alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h5 class="mb-2"><?php echo $success; ?></h5>
                        <p class="mb-3">Thank you for submitting your return request.</p>
                        <a href="return_device.php" class="btn-submit d-inline-block" style="text-decoration: none;">Submit Another Request</a>
                        <a href="my_submissions.php" class="btn-reset d-inline-block" style="text-decoration: none; margin-left: 10px;">View My Requests</a>
                    </div>
                <?php else: ?>
                    <?php if(isset($error)): ?>
                        <div class="alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" id="returnForm">
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-user"></i> Select Your Name *</label>
                            
                            <?php if($employee_found && $employee_info): ?>
                                <div class="employee-info-card">
                                    <div class="row">
                                        <div class="col-md-6 mb-2"><strong>PF No:</strong> <?php echo htmlspecialchars($employee_info['pf_no']); ?></div>
                                        <div class="col-md-6 mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($employee_info['full_name']); ?></div>
                                        <div class="col-md-6 mb-2"><strong>Designation:</strong> <?php echo htmlspecialchars($employee_info['designation'] ?? 'N/A'); ?></div>
                                        <div class="col-md-6 mb-2"><strong>Department:</strong> <?php echo htmlspecialchars($employee_info['department'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="text-end mt-2 btn-group">
                                        <button type="button" class="btn-change-employee" onclick="changeEmployee()">
                                            <i class="fas fa-exchange-alt me-1"></i> Change Employee
                                        </button>
                                        <button type="button" class="btn-new-verification" onclick="newVerification()">
                                            <i class="fas fa-sync-alt me-1"></i> New Verification
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="verified_employee_id" id="selected_employee_id" value="<?php echo $employee_id; ?>">
                                <input type="hidden" id="employee_verified" value="1">
                            <?php else: ?>
                                <div id="employeeVerificationArea">
                                    <div class="row">
                                        <div class="col-md-8 mb-2 mb-md-0">
                                            <input type="text" id="pf_no_search" class="form-control" placeholder="Enter your PF Number to verify" autocomplete="off">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" id="verifyPfBtn" class="btn btn-primary w-100" style="border-radius: 40px; padding: 10px;">Verify PF</button>
                                        </div>
                                    </div>
                                    <div id="employee_info_display" style="display:none;" class="mt-3"></div>
                                    <input type="hidden" name="verified_employee_id" id="selected_employee_id" value="">
                                    <input type="hidden" id="employee_verified" value="0">
                                    <small class="text-muted mt-2 d-block">Enter your PF Number (e.g., 100003) to verify your identity</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-laptop"></i> Select Devices to Return *</label>
                            <div id="devicesContainer">
                                <div class="device-item" data-device-index="0">
                                    <div class="device-header">
                                        <span class="device-number">Device #1</span>
                                        <button type="button" class="btn-remove-device" style="display:none;" onclick="removeDevice(0)"><i class="fas fa-trash me-1"></i> Remove</button>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <select name="assignment_ids[]" class="form-select device-select" data-device-index="0" required <?php echo (!$employee_found) ? 'disabled' : ''; ?> style="width:100%">
                                                <option value=""><?php echo (!$employee_found) ? '-- First Verify Employee --' : '-- Select a device --'; ?></option>
                                            </select>
                                            <input type="hidden" name="serial_record_ids[]" class="selected-serial-record" data-device-index="0" value="">
                                        </div>
                                    </div>
                                    <div class="device-info" data-device-index="0" style="display:none;">
                                        <div class="device-details-badge">
                                            <span class="badge-info-custom"><i class="fas fa-microchip"></i> <span class="selected-device-name"></span></span>
                                            <span class="badge-info-custom"><i class="fas fa-barcode"></i> <span class="selected-device-code"></span></span>
                                            <span class="badge-info-custom"><i class="fas fa-qrcode"></i> SN: <span class="selected-device-serial"></span></span>
                                            <span class="badge-info-custom"><i class="fas fa-tag"></i> Model: <span class="selected-device-model"></span></span>
                                            <span class="badge-info-custom"><i class="fas fa-calendar"></i> Assigned: <span class="selected-device-assigned-date"></span></span>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Device Condition</label>
                                            <select name="device_conditions[]" class="form-select">
                                                <option value="good">Good - Working perfectly</option>
                                                <option value="minor_damage">Minor Damage</option>
                                                <option value="major_damage">Major Damage</option>
                                                <option value="not_working">Not Working</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8 mb-3">
                                            <label class="form-label">Damage Description</label>
                                            <input type="text" name="damage_descriptions[]" class="form-control" placeholder="Describe any damage">
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Accessories Returned</label>
                                            <input type="text" name="accessories_returned_arr[]" class="form-control" placeholder="e.g., Charger, Cable, Box">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mb-4">
                                <button type="button" class="btn-add-device" id="addDeviceBtn" <?php echo (!$employee_found) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-plus me-2"></i> Add Another Device
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Return Reason</label>
                                <select name="return_reason" class="form-select">
                                    <option value="resignation">Resignation</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="upgrade">Upgrade</option>
                                    <option value="repair">Repair</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Reason Details</label>
                                <input type="text" name="reason_details" class="form-control" placeholder="Additional details">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" name="cleaning_done" class="form-check-input" id="cleaningDone">
                                    <label class="form-check-label" for="cleaningDone">Device cleaned</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" name="data_backup_confirmed" class="form-check-input" id="dataBackup">
                                    <label class="form-check-label" for="dataBackup">Data backed up</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" name="exit_clearance" class="form-check-input" id="exitClearance">
                                    <label class="form-check-label" for="exitClearance">Exit clearance obtained</label>
                                </div>
                            </div>
                        </div>

                        <div class="alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            By submitting this form, you confirm that the device return information provided is accurate to the best of your knowledge.
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn-submit" id="submitBtn" <?php echo (!$employee_found) ? 'disabled' : ''; ?>>
                                <i class="fas fa-paper-plane"></i> Submit Request
                            </button>
                            <button type="reset" class="btn-reset">Reset Form</button>
                            <a href="index.php" class="btn-reset" style="text-decoration: none;">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> IT Inventory Management System. All rights reserved.</p>
                </div>
                <div class="footer-login">
                    <?php if(!$isLoggedIn): ?>
                        <a href="../modules/login.php" class="btn-footer-login">
                            <i class="fas fa-lock me-1"></i> Staff Login
                        </a>
                    <?php else: ?>
                        <span style="font-size: 0.7rem; color: #9ca3af;">
                            <i class="fas fa-user-check me-1"></i> Logged in as <?php echo htmlspecialchars($userName); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        let currentEmployeeId = <?php echo $employee_found ? $employee_id : 0; ?>;
        let assignedDevices = [];
        let deviceCounter = 1;
        
        function changeEmployee() {
            if(confirm('Are you sure you want to change employee? All selections will be cleared.')) {
                location.reload();
            }
        }
        
        function newVerification() {
            if(confirm('Start new verification? This will clear the current employee data.')) {
                $('#submitBtn').prop('disabled', true);
                $('#addDeviceBtn').prop('disabled', true);
                $('.device-select').prop('disabled', true);
                
                $.ajax({
                    url: window.location.href + '?action=clear_employee_session',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            location.reload();
                        } else {
                            alert('Error clearing session. Please refresh the page.');
                        }
                    },
                    error: function() {
                        alert('Error clearing session. Please refresh the page manually.');
                    }
                });
            }
        }
        
        function escapeHtml(str) {
            if(!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if(m === '&') return '&amp;';
                if(m === '<') return '&lt;';
                if(m === '>') return '&gt;';
                return m;
            });
        }
        
        $(document).ready(function() {
            <?php if(!$employee_found): ?>
            $('#verifyPfBtn').click(function() {
                var pfNo = $('#pf_no_search').val().trim();
                if(!pfNo) { alert('Please enter your PF Number'); return; }
                
                $('#verifyPfBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verifying...');
                
                $.ajax({
                    url: window.location.href + '?action=verify_employee&pf_no=' + encodeURIComponent(pfNo),
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            var emp = response.data;
                            var employeeHtml = `
                                <div class="employee-info-card">
                                    <div class="row">
                                        <div class="col-md-6 mb-2"><strong>PF No:</strong> ${escapeHtml(emp.pf_no)}</div>
                                        <div class="col-md-6 mb-2"><strong>Name:</strong> ${escapeHtml(emp.full_name)}</div>
                                        <div class="col-md-6 mb-2"><strong>Designation:</strong> ${escapeHtml(emp.designation || 'N/A')}</div>
                                        <div class="col-md-6 mb-2"><strong>Department:</strong> ${escapeHtml(emp.department || 'N/A')}</div>
                                    </div>
                                    <div class="text-end mt-2 btn-group">
                                        <button type="button" class="btn-change-employee" onclick="changeEmployee()">
                                            <i class="fas fa-exchange-alt me-1"></i> Change Employee
                                        </button>
                                        <button type="button" class="btn-new-verification" onclick="newVerification()">
                                            <i class="fas fa-sync-alt me-1"></i> New Verification
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="verified_employee_id" id="selected_employee_id" value="${emp.id}">
                                <input type="hidden" id="employee_verified" value="1">
                            `;
                            $('#employeeVerificationArea').html(employeeHtml);
                            currentEmployeeId = emp.id;
                            $('#employee_verified').val('1');
                            $('#submitBtn').prop('disabled', false);
                            $('#addDeviceBtn').prop('disabled', false);
                            
                            loadEmployeeDevices(emp.id);
                        } else {
                            alert(response.message);
                            $('#verifyPfBtn').prop('disabled', false).html('Verify PF');
                        }
                    },
                    error: function() {
                        alert('Error verifying employee. Please try again.');
                        $('#verifyPfBtn').prop('disabled', false).html('Verify PF');
                    }
                });
            });
            
            $('#pf_no_search').keypress(function(e) {
                if(e.which == 13) { $('#verifyPfBtn').click(); return false; }
            });
            <?php endif; ?>
        });
        
        function loadEmployeeDevices(empId) {
            if(empId) {
                $('#devicesContainer .device-select').html('<option value="">Loading devices...</option>');
                
                $.ajax({
                    url: window.location.href + '?action=get_employee_devices&employee_id=' + empId,
                    type: 'GET',
                    dataType: 'json',
                    timeout: 15000,
                    success: function(res) {
                        console.log('Devices response:', res);
                        if(res.success && res.devices && res.devices.length > 0) {
                            assignedDevices = res.devices;
                            
                            $('#devicesContainer').empty();
                            deviceCounter = 0;
                            addDeviceRow();
                            
                            $('.device-select').each(function() {
                                var select = $(this);
                                var deviceIndex = select.data('device-index');
                                select.empty().append('<option value="">-- Select Device --</option>');
                                $.each(res.devices, function(i, d) {
                                    select.append('<option value="' + d.assignment_id + '" data-assignment-id="' + d.assignment_id + '" data-serial-record-id="' + (d.serial_record_id || 0) + '" data-item-name="' + escapeHtml(d.item_name) + '" data-item-code="' + escapeHtml(d.item_code) + '" data-serial="' + escapeHtml(d.serial_number) + '" data-model="' + escapeHtml(d.model_number) + '" data-assigned-date="' + escapeHtml(d.assigned_date) + '">' + escapeHtml(d.display_text) + '</option>');
                                });
                                select.prop('disabled', false);
                                if(select.data('select2')) {
                                    select.select2('destroy');
                                }
                                select.select2({
                                    theme: 'default',
                                    placeholder: '-- Select Device --',
                                    allowClear: true,
                                    width: '100%'
                                }).on('change', function() {
                                    var idx = $(this).data('device-index');
                                    var opt = $(this).find(':selected');
                                    var deviceInfoDiv = $('.device-info[data-device-index="' + idx + '"]');
                                    var serialRecordInput = $('.selected-serial-record[data-device-index="' + idx + '"]');
                                    
                                    if($(this).val()) {
                                        var serial = opt.data('serial') || 'N/A';
                                        var model = opt.data('model') || 'N/A';
                                        var assignedDate = opt.data('assigned-date') || 'N/A';
                                        var serialRecordId = opt.data('serial-record-id') || 0;
                                        deviceInfoDiv.find('.selected-device-name').text(opt.data('item-name') || 'N/A');
                                        deviceInfoDiv.find('.selected-device-code').text(opt.data('item-code') || 'N/A');
                                        deviceInfoDiv.find('.selected-device-serial').text(serial);
                                        deviceInfoDiv.find('.selected-device-model').text(model);
                                        deviceInfoDiv.find('.selected-device-assigned-date').text(assignedDate);
                                        deviceInfoDiv.show();
                                        serialRecordInput.val(serialRecordId);
                                    } else {
                                        deviceInfoDiv.hide();
                                        serialRecordInput.val('');
                                    }
                                });
                            });
                        } else {
                            $('#devicesContainer').html('<div class="alert alert-warning text-center py-4">No active devices assigned to you.</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        $('#devicesContainer').html('<div class="alert alert-danger">Error loading devices. Please refresh the page.</div>');
                    }
                });
            }
        }
        
        function addDeviceRow() {
            var newIndex = deviceCounter;
            var newRow = $(`
                <div class="device-item" data-device-index="${newIndex}">
                    <div class="device-header">
                        <span class="device-number">Device #${deviceCounter + 1}</span>
                        <button type="button" class="btn-remove-device" onclick="removeDevice(${newIndex})"><i class="fas fa-trash me-1"></i> Remove</button>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <select name="assignment_ids[]" class="form-select device-select" data-device-index="${newIndex}" required style="width:100%">
                                <option value="">-- Select Device --</option>
                            </select>
                            <input type="hidden" name="serial_record_ids[]" class="selected-serial-record" data-device-index="${newIndex}" value="">
                        </div>
                    </div>
                    <div class="device-info" data-device-index="${newIndex}" style="display:none;">
                        <div class="device-details-badge">
                            <span class="badge-info-custom"><i class="fas fa-microchip"></i> <span class="selected-device-name"></span></span>
                            <span class="badge-info-custom"><i class="fas fa-barcode"></i> <span class="selected-device-code"></span></span>
                            <span class="badge-info-custom"><i class="fas fa-qrcode"></i> SN: <span class="selected-device-serial"></span></span>
                            <span class="badge-info-custom"><i class="fas fa-tag"></i> Model: <span class="selected-device-model"></span></span>
                            <span class="badge-info-custom"><i class="fas fa-calendar"></i> Assigned: <span class="selected-device-assigned-date"></span></span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Device Condition</label>
                            <select name="device_conditions[]" class="form-select">
                                <option value="good">Good - Working perfectly</option>
                                <option value="minor_damage">Minor Damage</option>
                                <option value="major_damage">Major Damage</option>
                                <option value="not_working">Not Working</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Damage Description</label>
                            <input type="text" name="damage_descriptions[]" class="form-control" placeholder="Describe any damage">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Accessories Returned</label>
                            <input type="text" name="accessories_returned_arr[]" class="form-control" placeholder="e.g., Charger, Cable, Box">
                        </div>
                    </div>
                </div>
            `);
            $('#devicesContainer').append(newRow);
            deviceCounter++;
            
            if(assignedDevices.length > 0) {
                var newSelect = $('.device-select[data-device-index="' + newIndex + '"]');
                newSelect.empty().append('<option value="">-- Select Device --</option>');
                $.each(assignedDevices, function(i, d) {
                    newSelect.append('<option value="' + d.assignment_id + '" data-assignment-id="' + d.assignment_id + '" data-serial-record-id="' + (d.serial_record_id || 0) + '" data-item-name="' + escapeHtml(d.item_name) + '" data-item-code="' + escapeHtml(d.item_code) + '" data-serial="' + escapeHtml(d.serial_number) + '" data-model="' + escapeHtml(d.model_number) + '" data-assigned-date="' + escapeHtml(d.assigned_date) + '">' + escapeHtml(d.display_text) + '</option>');
                });
                newSelect.prop('disabled', false);
                if(newSelect.data('select2')) {
                    newSelect.select2('destroy');
                }
                newSelect.select2({
                    theme: 'default',
                    placeholder: '-- Select Device --',
                    allowClear: true,
                    width: '100%'
                }).on('change', function() {
                    var idx = $(this).data('device-index');
                    var opt = $(this).find(':selected');
                    var deviceInfoDiv = $('.device-info[data-device-index="' + idx + '"]');
                    var serialRecordInput = $('.selected-serial-record[data-device-index="' + idx + '"]');
                    
                    if($(this).val()) {
                        var serial = opt.data('serial') || 'N/A';
                        var model = opt.data('model') || 'N/A';
                        var assignedDate = opt.data('assigned-date') || 'N/A';
                        var serialRecordId = opt.data('serial-record-id') || 0;
                        deviceInfoDiv.find('.selected-device-name').text(opt.data('item-name') || 'N/A');
                        deviceInfoDiv.find('.selected-device-code').text(opt.data('item-code') || 'N/A');
                        deviceInfoDiv.find('.selected-device-serial').text(serial);
                        deviceInfoDiv.find('.selected-device-model').text(model);
                        deviceInfoDiv.find('.selected-device-assigned-date').text(assignedDate);
                        deviceInfoDiv.show();
                        serialRecordInput.val(serialRecordId);
                    } else {
                        deviceInfoDiv.hide();
                        serialRecordInput.val('');
                    }
                });
            }
        }
        
        function removeDevice(deviceIndex) {
            if($('.device-item').length > 1) {
                $('.device-item[data-device-index="' + deviceIndex + '"]').remove();
                $('.device-item').each(function(i) {
                    $(this).attr('data-device-index', i);
                    $(this).find('.device-number').text('Device #' + (i + 1));
                    $(this).find('.device-select').attr('data-device-index', i);
                    $(this).find('.device-info').attr('data-device-index', i);
                    $(this).find('.selected-serial-record').attr('data-device-index', i);
                });
                deviceCounter = $('.device-item').length;
            } else {
                alert('At least one device is required!');
            }
        }
        
        $('#addDeviceBtn').click(function() {
            addDeviceRow();
        });
        
        $('button[type="reset"]').on('click', function(e) {
            e.preventDefault();
            location.reload();
        });
        
        $('#returnForm').submit(function(e) {
            if($('#employee_verified').val() !== '1' || !$('#selected_employee_id').val()) {
                e.preventDefault();
                alert('Please verify your PF number first');
                return false;
            }
            
            var hasDevice = false;
            $('.device-select').each(function() {
                if($(this).val()) hasDevice = true;
            });
            if(!hasDevice) {
                e.preventDefault();
                alert('Please select at least one device to return');
                return false;
            }
            
            var submitBtn = $('#submitBtn');
            if(submitBtn.length) {
                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
            }
            return true;
        });
        
        document.querySelectorAll('.navbar-nav .nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                    bsCollapse.hide();
                }
            });
        });
        
        <?php if($employee_found && $employee_info): ?>
        $(document).ready(function() {
            console.log('Employee already verified, loading devices for ID: <?php echo $employee_id; ?>');
            loadEmployeeDevices(<?php echo $employee_id; ?>);
        });
        <?php endif; ?>
        
        setTimeout(function() {
            const alert = document.querySelector('.alert-success, .alert-danger');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 8000);
    </script>
</body>
</html>