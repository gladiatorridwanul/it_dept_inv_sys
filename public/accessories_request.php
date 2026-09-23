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
        $upload_dir = '../uploads/accessories/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $new_filename = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if(move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                return 'uploads/accessories/' . $new_filename;
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

// Get item types for dropdown
$itemTypes = $pdo->query("SELECT id, name FROM item_types WHERE is_active = 1 ORDER BY name")->fetchAll();

// Get vendors for dropdown
$vendors = $pdo->query("SELECT id, vendor_name FROM vendors WHERE is_active = 1 ORDER BY vendor_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Accessories Request - IT Support</title>
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
        .upload-area:hover { border-color: #ef4444; background: #fef2f2; }
        
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
            
            .main-content { padding-top: 68px; }
            
            .footer-content { flex-direction: row; justify-content: space-between; }
            .copyright p { font-size: 0.6rem; margin: 0; }
            .btn-footer-login { padding: 4px 12px; font-size: 0.6rem; }
            .footer-login { text-align: right; }
            
            .employee-info-card { padding: 10px; font-size: 0.7rem; }
            .employee-info-card .btn-group { flex-direction: column; gap: 5px; margin-top: 10px; }
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
                    <h2><i class="fas fa-mouse"></i> IT Accessories Request</h2>
                    <div class="underline"></div>
                    <p>Request mouse, keyboard, headphone, cable, toner, or other IT accessories</p>
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
                    
                    $item_type = trim($_POST['item_type']);
                    $accessory_name = trim($_POST['accessory_name']);
                    $quantity = (int)$_POST['quantity'];
                    $brand_preference = trim($_POST['brand_preference']);
                    $color_preference = trim($_POST['color_preference']);
                    $urgency = trim($_POST['urgency']);
                    $reason = trim($_POST['reason']);
                    $preferred_model = trim($_POST['preferred_model']);
                    $budget_approval = isset($_POST['budget_approval']) ? 1 : 0;
                    $vendor_recommendation = trim($_POST['vendor_recommendation']);
                    
                    $validation_errors = [];
                    if($employee_id <= 0) $validation_errors[] = "Please verify your identity first";
                    if(empty($item_type)) $validation_errors[] = "Please select item type";
                    if(empty($accessory_name)) $validation_errors[] = "Please enter accessory name";
                    if($quantity <= 0) $validation_errors[] = "Quantity must be at least 1";
                    if(empty($reason)) $validation_errors[] = "Please provide a reason";
                    
                    $attachment = uploadFile($_FILES['attachment'], 'accessories', 'ACCY');
                    
                    if(empty($validation_errors)) {
                        $request_no = 'ACCY-' . date('YmdHis') . rand(100, 999);
                        $request_data = json_encode(['item_type' => $item_type, 'accessory_name' => $accessory_name, 'quantity' => $quantity, 'brand_preference' => $brand_preference, 'color_preference' => $color_preference, 'urgency' => $urgency, 'preferred_model' => $preferred_model, 'budget_approval' => $budget_approval, 'vendor_recommendation' => $vendor_recommendation]);
                        $description = "Accessories Request\nItem Type: $item_type\nAccessory: $accessory_name\nQuantity: $quantity\nUrgency: $urgency\nReason: $reason";
                        $urgency_level = ($urgency == 'emergency') ? 'critical' : ($urgency == 'urgent' ? 'high' : 'medium');
                        
                        $pdo->beginTransaction();
                        try {
                            $stmt = $pdo->prepare("INSERT INTO requests (request_no, employee_id, request_type, description, request_data, urgency_level, requested_date, status, notes, created_at) 
                                                  VALUES (?, ?, 'accessories', ?, ?, ?, NOW(), 'pending', ?, NOW())");
                            $stmt->execute([$request_no, $employee_id, $description, $request_data, $urgency_level, $attachment]);
                            $request_id = $pdo->lastInsertId();
                            
                            $stmt = $pdo->prepare("INSERT INTO accessories_requests (request_id, accessory_type, accessory_name, quantity, brand_preference, color_preference, urgency, reason, preferred_model, budget_approval, created_at) 
                                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                            $stmt->execute([$request_id, $item_type, $accessory_name, $quantity, $brand_preference, $color_preference, $urgency, $reason, $preferred_model, $budget_approval]);
                            
                            if($attachment) {
                                $stmt = $pdo->prepare("INSERT INTO request_attachments (request_id, file_name, file_path, file_type, uploaded_by, created_at) VALUES (?, ?, ?, 'document', NULL, NOW())");
                                $stmt->execute([$request_id, $_FILES['attachment']['name'], $attachment]);
                            }
                            
                            $pdo->commit();
                            $success = "Accessories request submitted successfully! Request No: " . $request_no;
                        } catch(Exception $e) {
                            $pdo->rollBack();
                            $error = "Database Error: " . $e->getMessage();
                            error_log("Accessories Request Error: " . $e->getMessage());
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
                        <p class="mb-3">Our IT team will review your accessories request.</p>
                        <a href="accessories_request.php" class="btn-submit d-inline-block" style="text-decoration: none;">Submit Another Request</a>
                        <a href="my_submissions.php" class="btn-reset d-inline-block" style="text-decoration: none; margin-left: 10px;">View My Requests</a>
                    </div>
                <?php else: ?>
                    <?php if(isset($error)): ?>
                        <div class="alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="accessoriesForm">
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

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Item Type *</label>
                                <select name="item_type" class="form-select" id="itemTypeSelect" required>
                                    <option value="">Select Item Type</option>
                                    <?php foreach($itemTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['name']); ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                    <?php endforeach; ?>
                                    <option value="other">Other</option>
                                </select>
                                <small class="text-muted">Select the type/category of the accessory</small>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Accessory Name *</label>
                                <input type="text" name="accessory_name" class="form-control" required placeholder="e.g., Dell Wireless Mouse, HP Keyboard">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-4">
                                <label class="form-label">Quantity *</label>
                                <input type="number" name="quantity" class="form-control" required min="1" value="1">
                            </div>
                            <div class="col-md-3 mb-4">
                                <label class="form-label">Brand Preference</label>
                                <input type="text" name="brand_preference" class="form-control" placeholder="Dell, HP, Logitech">
                            </div>
                            <div class="col-md-3 mb-4">
                                <label class="form-label">Color Preference</label>
                                <select name="color_preference" class="form-select">
                                    <option value="">Any</option>
                                    <option value="black">Black</option>
                                    <option value="white">White</option>
                                    <option value="silver">Silver</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-4">
                                <label class="form-label">Urgency *</label>
                                <select name="urgency" class="form-select" required>
                                    <option value="normal">Normal - 3-5 days</option>
                                    <option value="urgent">Urgent - Within 2 days</option>
                                    <option value="emergency">Emergency - Immediately</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Preferred Model</label>
                                <input type="text" name="preferred_model" class="form-control" placeholder="Logitech MX Master 3, Dell KB216">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Vendor Recommendation</label>
                                <select name="vendor_recommendation" class="form-select">
                                    <option value="">Select Vendor (Optional)</option>
                                    <?php foreach($vendors as $vendor): ?>
                                        <option value="<?php echo htmlspecialchars($vendor['vendor_name']); ?>"><?php echo htmlspecialchars($vendor['vendor_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Recommended vendor for this accessory</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="budget_approval" class="form-check-input" id="budgetApproval">
                                <label class="form-check-label" for="budgetApproval">I have budget approval for this purchase</label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Reason for Request *</label>
                            <textarea name="reason" rows="3" class="form-control" required placeholder="Please explain why you need this accessory..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Upload Supporting Document</label>
                            <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#ef4444;"></i>
                                <p class="mb-0">Click to upload supporting document</p>
                                <small>JPG, PNG, PDF (Max 5MB)</small>
                                <input type="file" name="attachment" id="fileInput" style="display:none">
                            </div>
                            <div id="fileName" class="mt-2 small text-muted"></div>
                        </div>

                        <div class="alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            By submitting this form, you confirm that the accessory request information provided is accurate to the best of your knowledge.
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
            
            $('#itemTypeSelect').on('change', function() {
                var selected = $(this).val();
                if(selected && selected !== 'other' && !$('input[name="accessory_name"]').val()) {
                    $('input[name="accessory_name"]').val(selected);
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
            
            $('#accessoriesForm').submit(function(e) {
                if($('#employee_verified').val() !== '1' || !$('#selected_employee_id').val()) {
                    e.preventDefault();
                    alert('Please verify your PF number first');
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