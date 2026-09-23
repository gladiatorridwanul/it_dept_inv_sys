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

// Get item types for dropdown
$item_types = $pdo->query("SELECT id, name, category_id, sub_category_id FROM item_types WHERE is_active = 1 ORDER BY name")->fetchAll();

$error_message = '';
$success_message = '';
$submission_no = '';

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['verified_employee_id']) && !empty($_POST['verified_employee_id'])) {
        $employee_id = $_POST['verified_employee_id'];
    } elseif(isset($_POST['employee_id']) && !empty($_POST['employee_id'])) {
        $employee_id = $_POST['employee_id'];
    } elseif(isset($_POST['pf_no']) && !empty($_POST['pf_no'])) {
        $pf_no = trim($_POST['pf_no']);
        $check_emp = $pdo->prepare("SELECT id FROM employees WHERE pf_no = ? AND is_active = 1");
        $check_emp->execute([$pf_no]);
        $emp_result = $check_emp->fetch();
        $employee_id = $emp_result ? $emp_result['id'] : 0;
    } else {
        $employee_id = 0;
    }
    
    $submission_notes = trim($_POST['submission_notes'] ?? '');
    
    $check_emp = $pdo->prepare("SELECT id, pf_no, full_name FROM employees WHERE id = ? AND is_active = 1");
    $check_emp->execute([$employee_id]);
    $employee_data = $check_emp->fetch();
    
    if(!$employee_data) {
        $error_message = "Invalid employee information. Please verify your PF number again.";
    } else {
        $submission_no = 'UNL-' . date('Ymd') . '-' . rand(1000, 9999);
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO unlisted_device_submissions 
                (submission_no, employee_id, submission_notes, submitted_by_ip, status, total_devices) 
                VALUES (?, ?, ?, ?, 'pending', 0)");
            $stmt->execute([$submission_no, $employee_id, $submission_notes, $_SERVER['REMOTE_ADDR']]);
            $submission_id = $pdo->lastInsertId();
            
            $device_count = 0;
            $device_names = $_POST['device_name'] ?? [];
            
            foreach($device_names as $index => $device_name) {
                if(empty($device_name)) continue;
                
                $item_type_id = !empty($_POST['item_type_id'][$index]) ? $_POST['item_type_id'][$index] : null;
                $brand_name = trim($_POST['brand_name'][$index] ?? '');
                $model_number = trim($_POST['model_number'][$index] ?? '');
                $serial_number = trim($_POST['serial_number'][$index] ?? '');
                $specification = trim($_POST['specification'][$index] ?? '');
                $purchase_date = !empty($_POST['purchase_date'][$index]) ? $_POST['purchase_date'][$index] : null;
                $assigned_date = !empty($_POST['assigned_date'][$index]) ? $_POST['assigned_date'][$index] : null;
                $assigned_by = trim($_POST['assigned_by'][$index] ?? '');
                $current_condition = $_POST['current_condition'][$index] ?? 'good';
                $device_notes = trim($_POST['device_notes'][$index] ?? '');
                
                $document_path = null;
                if(isset($_FILES['supporting_document']['name'][$index]) && $_FILES['supporting_document']['error'][$index] == 0 && $_FILES['supporting_document']['size'][$index] > 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                    $filename = $_FILES['supporting_document']['name'][$index];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if(in_array($ext, $allowed)) {
                        $upload_dir = '../uploads/unlisted_devices/';
                        if(!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                        $new_filename = $submission_no . '_device_' . $index . '_' . time() . '.' . $ext;
                        $destination = $upload_dir . $new_filename;
                        
                        if(move_uploaded_file($_FILES['supporting_document']['tmp_name'][$index], $destination)) {
                            $document_path = 'uploads/unlisted_devices/' . $new_filename;
                        }
                    }
                }
                
                $device_stmt = $pdo->prepare("INSERT INTO submission_devices 
                    (submission_id, item_type_id, device_name, brand_name, model_number, serial_number, 
                     specification, purchase_date, assigned_date, assigned_by, current_condition, 
                     notes, supporting_document, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                
                $device_stmt->execute([
                    $submission_id, $item_type_id, $device_name, $brand_name, $model_number,
                    $serial_number, $specification, $purchase_date, $assigned_date, $assigned_by,
                    $current_condition, $device_notes, $document_path
                ]);
                
                $device_count++;
            }
            
            $update_stmt = $pdo->prepare("UPDATE unlisted_device_submissions SET total_devices = ? WHERE id = ?");
            $update_stmt->execute([$device_count, $submission_id]);
            
            $pdo->commit();
            
            $success_message = "Your submission has been received! Reference Number: " . $submission_no . " (" . $device_count . " device(s))";
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error_message = "Error submitting form: " . $e->getMessage();
        }
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Report Unlisted Device - IT Support</title>
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
            background: #3b82f6; 
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
            border-color: #3b82f6; 
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1); 
            outline: none; 
        }
        
        .btn-submit { 
            display: inline-block;
            padding: 10px 30px;
            background: #3b82f6;
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
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
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
        .upload-area:hover { border-color: #3b82f6; background: #eff6ff; }
        
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
        
        .device-card { 
            background: #f8fafc; 
            border-radius: 16px; 
            padding: 20px; 
            margin-bottom: 20px; 
            border: 1px solid #e2e8f0; 
        }
        .device-card-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 15px; 
            padding-bottom: 10px; 
            border-bottom: 1px solid #e2e8f0; 
        }
        .device-number { font-weight: 700; color: #3b82f6; font-size: 0.9rem; }
        
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
            .device-card { padding: 15px; }
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
                    <h2><i class="fas fa-microchip"></i> Report Unlisted Device</h2>
                    <div class="underline"></div>
                    <p>Submit devices assigned to you that are not recorded in IT inventory</p>
                </div>

                <?php if(isset($success_message) && $success_message): ?>
                    <div class="alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h5 class="mb-2"><?php echo htmlspecialchars($success_message); ?></h5>
                        <p class="mb-3">IT team will review your submission.</p>
                        <a href="submit_unlisted_device.php" class="btn-submit d-inline-block" style="text-decoration: none;">Submit Another Device</a>
                        <a href="my_submissions.php" class="btn-reset d-inline-block" style="text-decoration: none; margin-left: 10px;">View My Submissions</a>
                    </div>
                <?php else: ?>
                    <?php if(isset($error_message) && $error_message): ?>
                        <div class="alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="deviceForm">
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
                            <label class="form-label"><i class="fas fa-laptop"></i> Devices Information</label>
                        </div>
                        
                        <div id="devicesContainer">
                            <div class="device-card" data-device-index="0">
                                <div class="device-card-header">
                                    <span class="device-number">Device #1</span>
                                    <button type="button" class="btn-remove-device" style="display:none;" onclick="removeDevice(0)"><i class="fas fa-trash me-1"></i> Remove</button>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Device Type</label>
                                        <select name="item_type_id[]" class="form-select">
                                            <option value="">Select Device Type</option>
                                            <?php foreach($item_types as $type): ?>
                                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Device Name *</label>
                                        <input type="text" name="device_name[]" class="form-control" placeholder="e.g., Dell XPS 15, HP LaserJet" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Brand Name</label>
                                        <input type="text" name="brand_name[]" class="form-control" placeholder="e.g., Dell, HP, Lenovo">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Model Number</label>
                                        <input type="text" name="model_number[]" class="form-control" placeholder="Model number">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Serial Number</label>
                                        <input type="text" name="serial_number[]" class="form-control" placeholder="Serial number if available">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Purchase Date</label>
                                        <input type="date" name="purchase_date[]" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date Assigned to You</label>
                                        <input type="date" name="assigned_date[]" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Assigned By</label>
                                        <input type="text" name="assigned_by[]" class="form-control" placeholder="Who assigned this device?">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Specifications</label>
                                        <textarea name="specification[]" rows="2" class="form-control" placeholder="RAM: 8GB, Processor: Intel i5, Storage: 256GB SSD"></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Current Condition *</label>
                                        <select name="current_condition[]" class="form-select" required>
                                            <option value="good">Good - Fully functional</option>
                                            <option value="minor_damage">Minor Damage - Minor issues</option>
                                            <option value="major_damage">Major Damage - Significant issues</option>
                                            <option value="not_working">Not Working - Non-functional</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Supporting Document</label>
                                        <div class="upload-area" onclick="triggerFileUpload(this, 0)">
                                            <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#3b82f6;"></i>
                                            <p class="mb-0">Click to upload document</p>
                                            <small>JPG, PNG, PDF (Max 5MB)</small>
                                            <input type="file" name="supporting_document[]" class="d-none" accept=".jpg,.jpeg,.png,.pdf" onchange="updateFileName(this, 0)">
                                        </div>
                                        <div id="fileName_0" class="mt-2 small text-muted"></div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Device Notes</label>
                                        <textarea name="device_notes[]" rows="1" class="form-control" placeholder="Any additional notes about this device"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mb-4">
                            <button type="button" class="btn-add-device" id="addDeviceBtn">
                                <i class="fas fa-plus me-2"></i> Add Another Device
                            </button>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-comment"></i> General Notes</label>
                            <textarea name="submission_notes" rows="2" class="form-control" placeholder="Any additional information about this submission"></textarea>
                        </div>
                        
                        <div class="alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            By submitting this form, you confirm that the device information provided is accurate to the best of your knowledge. 
                            IT department will verify and update the central inventory accordingly.
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
        });
        
        function triggerFileUpload(element, index) {
            const fileInput = $(element).find('input[type="file"]')[0];
            if(fileInput) fileInput.click();
        }
        
        function updateFileName(input, index) {
            if(input.files && input.files[0]) {
                var fileName = input.files[0].name;
                var fileSize = (input.files[0].size / 1024 / 1024).toFixed(2);
                $('#fileName_' + index).html('<i class="fas fa-check-circle text-success"></i> ' + fileName + ' (' + fileSize + ' MB)');
            }
        }
        
        $('#addDeviceBtn').click(function() {
            const itemTypeOptions = `<?php 
                $options = '';
                foreach($item_types as $type) {
                    $options .= '<option value="' . $type['id'] . '">' . addslashes($type['name']) . '</option>';
                }
                echo $options;
            ?>`;
            
            const deviceHtml = `<div class="device-card" data-device-index="${deviceCount}"><div class="device-card-header"><span class="device-number">Device #${deviceCount + 1}</span><button type="button" class="btn-remove-device" onclick="removeDevice(${deviceCount})"><i class="fas fa-trash me-1"></i> Remove</button></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Device Type</label><select name="item_type_id[]" class="form-select"><option value="">Select Device Type</option>${itemTypeOptions}</select></div><div class="col-md-6"><label class="form-label">Device Name *</label><input type="text" name="device_name[]" class="form-control" placeholder="e.g., Dell XPS 15, HP LaserJet" required></div><div class="col-md-6"><label class="form-label">Brand Name</label><input type="text" name="brand_name[]" class="form-control" placeholder="e.g., Dell, HP, Lenovo"></div><div class="col-md-6"><label class="form-label">Model Number</label><input type="text" name="model_number[]" class="form-control" placeholder="Model number"></div><div class="col-md-6"><label class="form-label">Serial Number</label><input type="text" name="serial_number[]" class="form-control" placeholder="Serial number if available"></div><div class="col-md-6"><label class="form-label">Purchase Date</label><input type="date" name="purchase_date[]" class="form-control"></div><div class="col-md-6"><label class="form-label">Date Assigned</label><input type="date" name="assigned_date[]" class="form-control"></div><div class="col-md-6"><label class="form-label">Assigned By</label><input type="text" name="assigned_by[]" class="form-control" placeholder="Who assigned this device?"></div><div class="col-md-12"><label class="form-label">Specifications</label><textarea name="specification[]" rows="2" class="form-control" placeholder="RAM: 8GB, Processor: Intel i5, Storage: 256GB SSD"></textarea></div><div class="col-md-6"><label class="form-label">Current Condition *</label><select name="current_condition[]" class="form-select" required><option value="good">Good</option><option value="minor_damage">Minor Damage</option><option value="major_damage">Major Damage</option><option value="not_working">Not Working</option></select></div><div class="col-md-6"><label class="form-label">Supporting Document</label><div class="upload-area" onclick="triggerFileUpload(this, ${deviceCount})"><i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#3b82f6;"></i><p class="mb-0">Click to upload document</p><small>JPG, PNG, PDF (Max 5MB)</small><input type="file" name="supporting_document[]" class="d-none" accept=".jpg,.jpeg,.png,.pdf" onchange="updateFileName(this, ${deviceCount})"></div><div id="fileName_${deviceCount}" class="mt-2 small text-muted"></div></div><div class="col-md-12"><label class="form-label">Device Notes</label><textarea name="device_notes[]" rows="1" class="form-control" placeholder="Any additional notes"></textarea></div></div></div>`;
            $('#devicesContainer').append(deviceHtml);
            deviceCount++;
            updateDeviceNumbers();
        });
        
        function removeDevice(index) {
            $(`div[data-device-index="${index}"]`).remove();
            updateDeviceNumbers();
        }
        
        function updateDeviceNumbers() {
            $('.device-card').each(function(i) {
                $(this).find('.device-number').text('Device #' + (i + 1));
                $(this).attr('data-device-index', i);
                $(this).find('.btn-remove-device').attr('onclick', 'removeDevice(' + i + ')');
            });
            deviceCount = $('.device-card').length;
            if(deviceCount === 1) { $('.btn-remove-device').hide(); } 
            else { $('.btn-remove-device').show(); }
        }
        
        let deviceCount = 1;
        
        $('button[type="reset"]').on('click', function(e) {
            e.preventDefault();
            location.reload();
        });
        
        $('#deviceForm').submit(function(e) {
            if($('#employee_verified').val() !== '1' || !$('#selected_employee_id').val()) {
                e.preventDefault();
                alert('Please verify your PF number first');
                return false;
            }
            
            let hasDevice = false;
            $('input[name="device_name[]"]').each(function() {
                if($(this).val().trim() !== '') hasDevice = true;
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