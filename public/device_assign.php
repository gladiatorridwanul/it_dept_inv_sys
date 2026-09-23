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

function uploadFile($file, $subfolder, $prefix) {
    if(isset($file) && $file['error'] == 0 && $file['size'] > 0) {
        $upload_dir = '../uploads/assignments/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $new_filename = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if(move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                return 'uploads/assignments/' . $new_filename;
            }
        }
    }
    return null;
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

// Handle AJAX request for items by item type - ONLY items with available quantity > 0
if(isset($_GET['action']) && $_GET['action'] == 'get_items_by_type' && isset($_GET['item_type_id'])) {
    header('Content-Type: application/json');
    $item_type_id = (int)$_GET['item_type_id'];
    
    $stmt = $pdo->prepare("
        SELECT i.id, i.name, i.item_code, i.current_qty,
               (SELECT COALESCE(SUM(quantity), 0) FROM assignments WHERE item_id = i.id AND status = 'assigned' AND return_status = 'active') as assigned_qty
        FROM items i
        WHERE i.type_id = ? 
            AND i.is_active = 1
            AND i.current_qty > 0
        HAVING (i.current_qty - assigned_qty) > 0
        ORDER BY i.name
    ");
    $stmt->execute([$item_type_id]);
    $items = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $items]);
    exit();
}

// Get item types for dropdown
$item_types = $pdo->query("SELECT id, name FROM item_types WHERE is_active = 1 ORDER BY name")->fetchAll();

// Get supervisors for dropdown
$supervisors = $pdo->query("SELECT id, pf_no, full_name FROM employees WHERE is_active=1 ORDER BY full_name")->fetchAll();

// Get vendors for dropdown
$vendors = $pdo->query("SELECT id, vendor_name FROM vendors WHERE is_active = 1 ORDER BY vendor_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Multiple Device Assignment - IT Support</title>
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
            background: #8b5cf6; 
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
            border-color: #8b5cf6; 
            box-shadow: 0 0 0 3px rgba(139,92,246,0.1); 
            outline: none; 
        }
        
        .btn-submit { 
            display: inline-block;
            padding: 10px 30px;
            background: #8b5cf6;
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
            background: #7c3aed;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139,92,246,0.3);
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
        
        .upload-area { 
            border: 2px dashed #e2e8f0; 
            border-radius: 12px; 
            padding: 15px; 
            text-align: center; 
            cursor: pointer; 
            background: white;
            transition: all 0.3s ease;
        }
        .upload-area:hover { border-color: #8b5cf6; background: #f5f3ff; }
        
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
        .device-number { font-weight: 700; color: #8b5cf6; font-size: 0.9rem; margin: 0; }
        
        .alert-success { background: #d1fae5; color: #065f46; border: none; border-radius: 12px; padding: 20px; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: none; border-radius: 12px; padding: 15px; }
        .alert-info { background: #eff6ff; color: #1e40af; border: none; border-radius: 12px; padding: 15px; }
        
        .form-check-label { font-size: 0.85rem; }
        small.text-muted { font-size: 0.7rem; }
        
        .stock-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 12px;
            background: #e2e8f0;
            color: #475569;
            margin-left: 5px;
        }
        .stock-available {
            background: #d1fae5;
            color: #065f46;
        }
        .stock-low {
            background: #fef3c7;
            color: #92400e;
        }
        
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
            .upload-area { padding: 10px; font-size: 0.7rem; }
            .upload-area i.fa-2x { font-size: 1.6rem; }
            .select2-container--default .select2-selection--single { height: 38px !important; }
            .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 34px !important; font-size: 0.75rem; }
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
                    <h2><i class="fas fa-laptop-house"></i> Multiple Device Assignment</h2>
                    <div class="underline"></div>
                    <p>Request multiple devices (Laptop, Desktop, Printer, Router, IP Phone, UPS, etc.) at once</p>
                </div>

                <?php
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    if(isset($_POST['verified_employee_id']) && !empty($_POST['verified_employee_id'])) {
                        $employee_id = (int)$_POST['verified_employee_id'];
                    } elseif(isset($_POST['employee_id']) && !empty($_POST['employee_id'])) {
                        $employee_id = (int)$_POST['employee_id'];
                    } else {
                        $employee_id = 0;
                    }
                    
                    $assigned_by_date = $_POST['assigned_by_date'];
                    $project_duration = trim($_POST['project_duration']);
                    $urgent_requirement = isset($_POST['urgent_requirement']) ? 1 : 0;
                    $supervisor_id = $_POST['supervisor_id'] ?? null;
                    $overall_reason = trim($_POST['overall_reason']);
                    $vendor_recommendation = trim($_POST['vendor_recommendation']);
                    
                    $device_ids = $_POST['device_ids'] ?? [];
                    $required_specifications = $_POST['required_specifications'] ?? [];
                    $reasons = $_POST['reasons'] ?? [];
                    
                    $validation_errors = [];
                    if($employee_id <= 0) $validation_errors[] = "Please verify your identity first";
                    if(empty($device_ids) || count($device_ids) == 0) $validation_errors[] = "Please add at least one device";
                    
                    $attachment = uploadFile($_FILES['attachment'], 'assignments', 'DEVASN');
                    
                    $supervisor_name = '';
                    if($supervisor_id) {
                        $stmt = $pdo->prepare("SELECT full_name FROM employees WHERE id = ?");
                        $stmt->execute([$supervisor_id]);
                        $sup = $stmt->fetch();
                        $supervisor_name = $sup['full_name'] ?? '';
                    }
                    
                    if(empty($validation_errors)) {
                        $devices_data = [];
                        foreach($device_ids as $index => $device_id) {
                            if(!empty($device_id)) {
                                $stmt = $pdo->prepare("SELECT name, item_code, specification FROM items WHERE id = ?");
                                $stmt->execute([$device_id]);
                                $device = $stmt->fetch();
                                if($device) {
                                    $devices_data[] = ['device_id' => $device_id, 'device_name' => $device['name'], 'item_code' => $device['item_code'], 'specification' => $device['specification'], 'required_specifications' => $required_specifications[$index] ?? '', 'reason' => $reasons[$index] ?? ''];
                                }
                            }
                        }
                        
                        $request_no = 'DEVASN-' . date('YmdHis') . rand(100, 999);
                        $request_data = json_encode(['devices' => $devices_data, 'project_duration' => $project_duration, 'urgent_requirement' => $urgent_requirement, 'supervisor_name' => $supervisor_name, 'assigned_by_date' => $assigned_by_date, 'overall_reason' => $overall_reason, 'vendor_recommendation' => $vendor_recommendation]);
                        $description = "Multiple Device Assignment Request\nAssigned By Date: $assigned_by_date\nDevices: " . count($devices_data) . " device(s)\nReason: $overall_reason";
                        $urgency_level = $urgent_requirement ? 'high' : 'medium';
                        
                        $pdo->beginTransaction();
                        try {
                            $stmt = $pdo->prepare("INSERT INTO requests (request_no, employee_id, request_type, description, request_data, urgency_level, requested_date, status, notes, created_at) 
                                                  VALUES (?, ?, 'device_assign', ?, ?, ?, NOW(), 'pending', ?, NOW())");
                            $stmt->execute([$request_no, $employee_id, $description, $request_data, $urgency_level, $attachment]);
                            $request_id = $pdo->lastInsertId();
                            
                            foreach($devices_data as $dev) {
                                $stmt = $pdo->prepare("INSERT INTO assign_device_requests (request_id, device_id, required_specifications, reason, project_duration, supervisor_approval, urgent_requirement, created_at) 
                                                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                                $stmt->execute([$request_id, $dev['device_id'], $dev['required_specifications'], $dev['reason'], $project_duration, $supervisor_id ? 1 : 0, $urgent_requirement]);
                            }
                            
                            if($attachment) {
                                $stmt = $pdo->prepare("INSERT INTO request_attachments (request_id, file_name, file_path, file_type, uploaded_by, created_at) VALUES (?, ?, ?, 'document', NULL, NOW())");
                                $stmt->execute([$request_id, $_FILES['attachment']['name'], $attachment]);
                            }
                            
                            $pdo->commit();
                            $success = "Device assignment request submitted successfully! Request No: " . $request_no;
                        } catch(Exception $e) {
                            $pdo->rollBack();
                            $error = "Database Error: " . $e->getMessage();
                            error_log("Device Assignment Error: " . $e->getMessage());
                        }
                    } else {
                        $error = implode("<br>", $validation_errors);
                    }
                }
                ?>

                <?php if(isset($success)): ?>
                    <div class="alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h5 class="mb-2"><?php echo $success; ?></h5>
                        <p class="mb-3">Our IT team will review your device assignment request.</p>
                        <a href="device_assign.php" class="btn-submit d-inline-block" style="text-decoration: none;">Submit Another Request</a>
                        <a href="my_submissions.php" class="btn-reset d-inline-block" style="text-decoration: none; margin-left: 10px;">View My Requests</a>
                    </div>
                <?php else: ?>
                    <?php if(isset($error)): ?>
                        <div class="alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="assignmentForm">
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-user"></i> Select Employee *</label>
                            
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

                        <div id="devicesContainer">
                            <div class="device-item" data-device-index="0">
                                <div class="device-header">
                                    <h6 class="device-number">Device #1</h6>
                                    <i class="fas fa-trash-alt remove-device" style="display:none; cursor:pointer; color:#ef4444;"></i>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Item Type *</label>
                                        <select name="item_type_ids[]" class="form-select item-type-select" data-device-index="0" required style="width:100%">
                                            <option value="">Select Item Type</option>
                                            <?php foreach($item_types as $type): ?>
                                                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Select device category</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Device List *</label>
                                        <select name="device_ids[]" class="form-control device-list-select" data-device-index="0" required style="width:100%" disabled>
                                            <option value="">First select Item Type</option>
                                        </select>
                                        <small class="text-muted">Select specific device (only items with available stock shown)</small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Required Specifications</label>
                                    <textarea name="required_specifications[]" rows="2" class="form-control" placeholder="RAM, Processor, Storage, OS, etc."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reason for this device</label>
                                    <textarea name="reasons[]" rows="2" class="form-control"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mb-4">
                            <button type="button" class="btn-add-device" id="addDeviceBtn">
                                <i class="fas fa-plus me-2"></i> Add Another Device
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Assigned By Date *</label>
                                <input type="date" name="assigned_by_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Project Duration</label>
                                <select name="project_duration" class="form-select">
                                    <option value="short_term">Short Term (&lt;3 months)</option>
                                    <option value="medium_term">Medium Term (3-12 months)</option>
                                    <option value="long_term">Long Term (>1 year)</option>
                                    <option value="permanent">Permanent</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Supervisor Name</label>
                                <select name="supervisor_id" class="form-select select2-supervisor" style="width:100%">
                                    <option value="">Type supervisor name to search</option>
                                </select>
                                <small class="text-muted">Type supervisor name to search</small>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="form-check mt-4 pt-2">
                                    <input type="checkbox" name="urgent_requirement" class="form-check-input" id="urgentRequirement">
                                    <label class="form-check-label" for="urgentRequirement">This is an urgent requirement</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Vendor Recommendation</label>
                                <select name="vendor_recommendation" class="form-select">
                                    <option value="">Select Vendor (Optional)</option>
                                    <?php foreach($vendors as $vendor): ?>
                                        <option value="<?php echo htmlspecialchars($vendor['vendor_name']); ?>"><?php echo htmlspecialchars($vendor['vendor_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Recommended vendor for device procurement</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Overall Reason for Request</label>
                            <textarea name="overall_reason" rows="2" class="form-control"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Upload Supporting Document</label>
                            <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#8b5cf6;"></i>
                                <p class="mb-0">Click to upload supporting document</p>
                                <small>JPG, PNG, PDF (Max 5MB)</small>
                                <input type="file" name="attachment" id="fileInput" style="display:none">
                            </div>
                            <div id="fileName" class="mt-2 small text-muted"></div>
                        </div>

                        <div class="alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            By submitting this form, you confirm that the device assignment information provided is accurate to the best of your knowledge.
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
        function changeEmployee() {
            if(confirm('Are you sure you want to change employee? All form data will be cleared.')) {
                location.reload();
            }
        }
        
        function newVerification() {
            if(confirm('Start new verification? This will clear the current employee data.')) {
                $('#submitBtn').prop('disabled', true);
                
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
                            $('#employee_verified').val('1');
                            $('#submitBtn').prop('disabled', false);
                        } else {
                            alert(response.message);
                            $('#employee_info_display').hide();
                            $('#selected_employee_id').val('');
                            $('#employee_verified').val('0');
                            $('#submitBtn').prop('disabled', true);
                            $('#verifyPfBtn').prop('disabled', false).html('Verify PF').removeClass('btn-success').addClass('btn-primary');
                        }
                    },
                    error: function() {
                        alert('Error verifying employee. Please try again.');
                        $('#verifyPfBtn').prop('disabled', false).html('Verify PF').removeClass('btn-success').addClass('btn-primary');
                    }
                });
            });
            
            $('#pf_no_search').keypress(function(e) {
                if(e.which == 13) { $('#verifyPfBtn').click(); return false; }
            });
            <?php endif; ?>
            
            $('.select2-supervisor').select2({
                placeholder: "Type supervisor name",
                minimumInputLength: 1,
                ajax: { url: 'search_employees.php', dataType: 'json', delay: 300, data: function(params) { return { term: params.term }; }, processResults: function(data) { return { results: data }; } }
            });
            
            function loadDevicesForType(itemTypeId, deviceSelect, deviceIndex) {
                if(itemTypeId) {
                    deviceSelect.prop('disabled', true);
                    deviceSelect.html('<option value="">Loading devices...</option>');
                    
                    $.ajax({
                        url: window.location.href + '?action=get_items_by_type&item_type_id=' + itemTypeId,
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if(response.success && response.data.length > 0) {
                                var options = '<option value="">Select Device</option>';
                                $.each(response.data, function(i, item) {
                                    var availableQty = item.current_qty - (item.assigned_qty || 0);
                                    options += '<option value="' + item.id + '" data-available="' + availableQty + '">' + escapeHtml(item.name) + ' (' + escapeHtml(item.item_code || 'No Code') + ') - Available: ' + availableQty + '</option>';
                                });
                                deviceSelect.html(options);
                                deviceSelect.prop('disabled', false);
                                
                                if(deviceSelect.hasClass('select2-hidden-accessible')) {
                                    deviceSelect.select2('destroy');
                                }
                                deviceSelect.select2({
                                    placeholder: "Select device",
                                    width: '100%'
                                });
                            } else {
                                deviceSelect.html('<option value="">No items available in this category</option>');
                                deviceSelect.prop('disabled', true);
                            }
                        },
                        error: function() {
                            deviceSelect.html('<option value="">Error loading devices</option>');
                            deviceSelect.prop('disabled', true);
                        }
                    });
                } else {
                    deviceSelect.html('<option value="">First select Item Type</option>');
                    deviceSelect.prop('disabled', true);
                }
            }
            
            function initDeviceRow(container, deviceIndex) {
                var itemTypeSelect = $(container).find('.item-type-select');
                var deviceSelect = $(container).find('.device-list-select');
                
                itemTypeSelect.attr('data-device-index', deviceIndex);
                deviceSelect.attr('data-device-index', deviceIndex);
                
                itemTypeSelect.off('change').on('change', function() {
                    var itemTypeId = $(this).val();
                    var idx = $(this).data('device-index');
                    var devSelect = $('.device-list-select[data-device-index="' + idx + '"]');
                    loadDevicesForType(itemTypeId, devSelect, idx);
                });
                
                if(itemTypeSelect.val()) {
                    loadDevicesForType(itemTypeSelect.val(), deviceSelect, deviceIndex);
                } else {
                    deviceSelect.html('<option value="">First select Item Type</option>').prop('disabled', true);
                }
            }
            
            initDeviceRow($('.device-item:first'), 0);
            
            let deviceCounter = 1;
            
            $('#addDeviceBtn').click(function() {
                var newRow = $('.device-item:first').clone();
                var newIndex = deviceCounter;
                newRow.attr('data-device-index', newIndex);
                newRow.find('.device-number').text('Device #' + (deviceCounter + 1));
                newRow.find('.remove-device').show();
                
                newRow.find('.item-type-select').val('');
                newRow.find('.device-list-select').val('').html('<option value="">First select Item Type</option>');
                newRow.find('textarea').val('');
                
                newRow.find('.select2-container').remove();
                newRow.find('.select2-hidden-accessible').removeClass('select2-hidden-accessible');
                
                newRow.find('.item-type-select').attr('data-device-index', newIndex);
                newRow.find('.device-list-select').attr('data-device-index', newIndex);
                
                $('#devicesContainer').append(newRow);
                initDeviceRow(newRow, newIndex);
                deviceCounter++;
            });
            
            $(document).on('click', '.remove-device', function() {
                if($('.device-item').length > 1) {
                    $(this).closest('.device-item').remove();
                    $('.device-item').each(function(i) {
                        $(this).find('.device-number').text('Device #' + (i+1));
                        $(this).attr('data-device-index', i);
                        $(this).find('.item-type-select').attr('data-device-index', i);
                        $(this).find('.device-list-select').attr('data-device-index', i);
                    });
                    deviceCounter = $('.device-item').length;
                } else {
                    alert('At least one device is required!');
                }
            });
            
            $('#fileInput').on('change', function() {
                if(this.files && this.files[0]) {
                    var fileName = this.files[0].name;
                    var fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
                    $('#fileName').html('<i class="fas fa-check-circle text-success"></i> ' + fileName + ' (' + fileSize + ' MB)');
                } else {
                    $('#fileName').html('');
                }
            });
            
            $('button[type="reset"]').on('click', function(e) {
                e.preventDefault();
                location.reload();
            });
            
            $('#assignmentForm').submit(function(e) {
                if($('#employee_verified').val() !== '1' || !$('#selected_employee_id').val()) {
                    e.preventDefault();
                    alert('Please verify your PF number first');
                    return false;
                }
                
                let hasDevice = false;
                $('select[name="device_ids[]"]').each(function() {
                    if($(this).val() !== '' && $(this).val() !== null) hasDevice = true;
                });
                if(!hasDevice) { 
                    e.preventDefault(); 
                    alert('Please add at least one device'); 
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
        });
        
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