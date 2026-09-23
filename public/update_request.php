<?php
// Start session at the very beginning
session_start();

require_once '../config/database.php';

// Check login status for header display
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userName = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
$userRole = $_SESSION['role'] ?? '';

// PF Verification variables - Same as submit_unlisted_device.php
$employee_found = false;
$employee_info = null;
$employee_id = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : 
               (isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0);

// Get item types for dropdown
$item_types = $pdo->query("SELECT id, name FROM item_types WHERE is_active = 1 ORDER BY name")->fetchAll();

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
        $upload_dir = '../uploads/updates/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $new_filename = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if(move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                return 'uploads/updates/' . $new_filename;
            }
        }
    }
    return null;
}

// Handle AJAX request for employee verification - Same as submit_unlisted_device.php
if(isset($_GET['action']) && $_GET['action'] == 'verify_employee' && isset($_GET['pf_no'])) {
    header('Content-Type: application/json');
    $pf_no = trim($_GET['pf_no']);
    
    $stmt = $pdo->prepare("SELECT id, pf_no, full_name, designation, department, phone, email FROM employees WHERE pf_no = ? AND is_active = 1");
    $stmt->execute([$pf_no]);
    $employee = $stmt->fetch();
    
    if($employee) {
        echo json_encode(['success' => true, 'data' => $employee]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found with PF Number: ' . $pf_no]);
    }
    exit();
}

// Handle AJAX request for device by item type and name
if(isset($_GET['action']) && $_GET['action'] == 'get_device_by_type_and_name' && isset($_GET['item_type_id']) && isset($_GET['device_name'])) {
    header('Content-Type: application/json');
    $item_type_id = (int)$_GET['item_type_id'];
    $device_name = trim($_GET['device_name']);
    
    $stmt = $pdo->prepare("SELECT id, name, item_code, serial_number, specification FROM items WHERE type_id = ? AND name = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$item_type_id, $device_name]);
    $device = $stmt->fetch();
    
    if($device) {
        echo json_encode(['success' => true, 'data' => $device]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Device not found']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Update Device - IT Support</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Cambria', 'Georgia', 'Times New Roman', serif; 
            background: #f5f7fa; 
        }
        
        /* Navbar - Full Width with Smaller height matching submit_unlisted_device.php */
        .navbar { 
            background: #ffffff; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.04); 
            padding: 0.4rem 0; 
            position: fixed; 
            width: 100%; 
            top: 0; 
            left: 0;
            right: 0;
            z-index: 1000; 
        }
        .navbar .container {
            max-width: 100%;
            padding-left: 20px;
            padding-right: 20px;
        }
        .navbar-brand { 
            font-size: 1.1rem; 
            font-weight: 700; 
            color: #3b82f6; 
            font-family: 'Cambria', 'Georgia', serif;
        }
        .navbar-brand i { color: #3b82f6; margin-right: 6px; }
        .nav-link { 
            font-weight: 500; 
            color: #4b5563; 
            transition: all 0.2s ease; 
            margin: 0 0.3rem; 
            font-size: 0.8rem;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .nav-link:hover { color: #3b82f6; transform: translateY(-1px); }
        .btn-login { 
            background: #3b82f6; 
            color: white; 
            border: none; 
            padding: 5px 18px; 
            border-radius: 30px; 
            font-weight: 600; 
            font-size: 0.75rem; 
            text-decoration: none; 
            display: inline-block; 
            font-family: 'Cambria', 'Georgia', serif;
        }
        .btn-login:hover { background: #2563eb; transform: translateY(-1px); color: white; }
        .btn-dashboard { 
            background: #10b981; 
            color: white; 
            border: none; 
            padding: 5px 18px; 
            border-radius: 30px; 
            font-weight: 600; 
            font-size: 0.75rem; 
            text-decoration: none; 
            display: inline-block; 
            font-family: 'Cambria', 'Georgia', serif;
        }
        .btn-dashboard:hover { background: #059669; transform: translateY(-1px); color: white; }
        .btn-logout { 
            background: #ef4444; 
            color: white; 
            border: none; 
            padding: 5px 18px; 
            border-radius: 30px; 
            font-weight: 600; 
            font-size: 0.75rem; 
            text-decoration: none; 
            display: inline-block; 
            font-family: 'Cambria', 'Georgia', serif;
        }
        .btn-logout:hover { background: #dc2626; transform: translateY(-1px); color: white; }
        
        /* Main Content */
        .main-content { padding-top: 65px; min-height: calc(100vh - 80px); }
        .form-card { 
            background: white; 
            border-radius: 20px; 
            padding: 25px 30px; 
            max-width: 950px; 
            margin: 0 auto 30px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.05); 
        }
        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { 
            font-size: 1.5rem; 
            font-weight: 700; 
            color: #1f2937; 
            margin-bottom: 8px; 
            font-family: 'Cambria', 'Georgia', serif;
        }
        .form-header .underline { 
            width: 55px; 
            height: 3px; 
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); 
            margin: 12px auto 0; 
            border-radius: 3px; 
        }
        .form-label { 
            font-weight: 600; 
            color: #334155; 
            margin-bottom: 6px; 
            font-size: 0.8rem;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .form-control, .form-select { 
            border: 2px solid #e2e8f0; 
            border-radius: 12px; 
            padding: 10px 14px; 
            font-size: 0.85rem;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .form-control:focus, .form-select:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.1); outline: none; }
        .btn-submit { 
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); 
            color: white; 
            padding: 10px 30px; 
            border-radius: 50px; 
            font-weight: 600; 
            border: none;
            font-family: 'Cambria', 'Georgia', serif;
            font-size: 0.85rem;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(245,158,11,0.3); }
        .btn-reset { 
            background: #e2e8f0; 
            color: #475569; 
            padding: 10px 30px; 
            border-radius: 50px; 
            font-weight: 600; 
            border: none; 
            margin-left: 12px;
            font-family: 'Cambria', 'Georgia', serif;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
        }
        .btn-reset:hover { background: #cbd5e1; color: #1f2937; }
        
        /* Footer - Full Width with Smaller height matching submit_unlisted_device.php */
        footer { 
            background: #1f2937; 
            color: white; 
            padding: 12px 0; 
            margin-top: 30px; 
            text-align: center; 
            width: 100%;
        }
        footer .container {
            max-width: 100%;
            padding-left: 20px;
            padding-right: 20px;
        }
        .copyright { 
            text-align: center; 
            color: #9ca3af; 
            font-size: 0.65rem;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .upload-area { 
            border: 2px dashed #e2e8f0; 
            border-radius: 12px; 
            padding: 15px; 
            text-align: center; 
            cursor: pointer; 
            background: white;
            transition: all 0.2s ease;
        }
        .upload-area:hover { border-color: #f59e0b; background: #fffbeb; }
        
        /* Employee Info Card - Same as submit_unlisted_device.php */
        .employee-info-card { 
            background: #eff6ff; 
            border-radius: 12px; 
            padding: 15px; 
            margin-bottom: 20px; 
            border-left: 4px solid #3b82f6; 
        }
        
        .device-card { 
            background: #fffbeb; 
            border-radius: 12px; 
            padding: 15px; 
            margin: 15px 0; 
            display: none; 
            border-left: 4px solid #f59e0b; 
        }
        
        /* Mobile Responsive - Same as submit_unlisted_device.php */
        @media (max-width: 768px) {
            .form-card { 
                padding: 18px 20px; 
                margin: 0 12px 20px;
                border-radius: 18px;
            }
            .form-header h2 { font-size: 1.3rem; }
            .btn-submit, .btn-reset { 
                width: 100%; 
                margin: 6px 0; 
                display: block;
                text-align: center;
            }
            .btn-reset { margin-left: 0; }
            .navbar-brand { font-size: 1rem; }
            .nav-link { font-size: 0.7rem; margin: 0 0.2rem; }
            .btn-login, .btn-dashboard, .btn-logout { padding: 4px 12px; font-size: 0.7rem; }
            .main-content { padding-top: 60px; }
            .row {
                margin-left: 0;
                margin-right: 0;
            }
            .col-md-6 {
                padding-left: 8px;
                padding-right: 8px;
            }
            .upload-area { padding: 12px; }
            .upload-area i.fa-2x { font-size: 1.6rem; }
            .text-center .btn-reset, .text-center .btn-submit {
                width: auto;
                display: inline-block;
                margin: 5px 5px;
            }
            .employee-info-card { padding: 12px; }
            .employee-info-card .col-md-6 { margin-bottom: 8px; }
            .device-card { padding: 12px; }
            .device-card .col-md-4 { margin-bottom: 8px; }
            .navbar .container {
                padding-left: 15px;
                padding-right: 15px;
            }
            footer .container {
                padding-left: 15px;
                padding-right: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .form-card { padding: 15px 16px; }
            .form-header h2 { font-size: 1.2rem; }
            .form-label { font-size: 0.75rem; }
            .form-control, .form-select { padding: 8px 12px; font-size: 0.8rem; }
            .btn-submit, .btn-reset { padding: 8px 20px; font-size: 0.8rem; }
            .navbar .container {
                padding-left: 12px;
                padding-right: 12px;
            }
            footer .container {
                padding-left: 12px;
                padding-right: 12px;
            }
            .text-center .btn-reset, .text-center .btn-submit {
                width: calc(50% - 12px);
                margin: 5px 6px;
            }
        }
        
        /* Laptop/Desktop fine-tuning */
        @media (min-width: 992px) {
            .form-card { padding: 30px 35px; }
            .navbar .container {
                max-width: 1400px;
                padding-left: 30px;
                padding-right: 30px;
            }
            footer .container {
                max-width: 1400px;
                padding-left: 30px;
                padding-right: 30px;
            }
        }
        
        .alert { border-radius: 16px; }
        small.text-muted { font-size: 0.7rem; }
    </style>
</head>
<body>
    <!-- Navbar - Full Width same as submit_unlisted_device.php -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-laptop-code"></i> IT Inventory</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php#requests">Request Form</a></li>
                    <li class="nav-item"><a class="nav-link" href="track_request.php">Track Request</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_submissions.php"><i class="fas fa-clipboard-list me-1"></i> My Reports</a></li>
                    <?php if($isLoggedIn): ?>
                        <li class="nav-item me-2"><a class="btn btn-dashboard" href="../modules/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="btn btn-logout" href="../modules/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-login" href="../modules/login.php"><i class="fas fa-lock me-1"></i> Staff Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="form-card">
                <div class="form-header">
                    <h2><i class="fas fa-edit"></i> Update Device Information</h2>
                    <div class="underline"></div>
                    <p class="text-muted mt-2" style="font-size: 0.8rem;">Request to update device details, specifications, or configuration in the inventory</p>
                </div>

                <?php
                // Process form submission
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    // Get employee_id from POST (either from verified employee)
                    if(isset($_POST['verified_employee_id']) && !empty($_POST['verified_employee_id'])) {
                        $employee_id = (int)$_POST['verified_employee_id'];
                    } else {
                        $employee_id = 0;
                    }
                    
                    $item_type_id = (int)$_POST['item_type_id'];
                    $device_name = trim($_POST['device_name']);
                    $update_type = trim($_POST['update_type']);
                    $current_config = trim($_POST['current_config']);
                    $requested_config = trim($_POST['requested_config']);
                    $reason = trim($_POST['reason']);
                    
                    // Get device ID from database based on item_type_id and device_name
                    $device_id = 0;
                    if($item_type_id > 0 && !empty($device_name)) {
                        $stmt = $pdo->prepare("SELECT id FROM items WHERE type_id = ? AND name = ? AND is_active = 1 LIMIT 1");
                        $stmt->execute([$item_type_id, $device_name]);
                        $device = $stmt->fetch();
                        if($device) {
                            $device_id = $device['id'];
                        }
                    }
                    
                    $validation_errors = [];
                    if($employee_id <= 0) $validation_errors[] = "Please verify your identity first";
                    if($item_type_id <= 0) $validation_errors[] = "Please select item type";
                    if(empty($device_name)) $validation_errors[] = "Please enter device name";
                    if($device_id <= 0) $validation_errors[] = "Device not found in inventory";
                    if(empty($update_type)) $validation_errors[] = "Please select update type";
                    if(empty($requested_config)) $validation_errors[] = "Please provide requested configuration";
                    if(empty($reason)) $validation_errors[] = "Please provide a reason";
                    
                    $supporting_doc = uploadFile($_FILES['supporting_doc'], 'updates', 'UPD');
                    
                    if(empty($validation_errors)) {
                        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
                        $stmt->execute([$device_id]);
                        $device = $stmt->fetch();
                        
                        $request_no = 'UPD-' . date('YmdHis') . rand(100, 999);
                        $request_data = json_encode(['device_id' => $device_id, 'device_name' => $device['name'], 'update_type' => $update_type, 'current_config' => $current_config, 'requested_config' => $requested_config]);
                        $description = "Device Update Request\nDevice: {$device['name']}\nUpdate Type: $update_type\nCurrent Configuration: $current_config\nRequested Configuration: $requested_config\nReason: $reason";
                        
                        $pdo->beginTransaction();
                        try {
                            $stmt = $pdo->prepare("INSERT INTO requests (request_no, employee_id, request_type, description, request_data, urgency_level, requested_date, status, notes, created_at) 
                                                  VALUES (?, ?, 'update', ?, ?, 'medium', NOW(), 'pending', ?, NOW())");
                            $stmt->execute([$request_no, $employee_id, $description, $request_data, $supporting_doc]);
                            $request_id = $pdo->lastInsertId();
                            
                            $stmt = $pdo->prepare("INSERT INTO update_device_requests (request_id, device_id, update_type, current_value, requested_value, reason, supporting_doc, created_at) 
                                                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                            $stmt->execute([$request_id, $device_id, $update_type, $current_config, $requested_config, $reason, $supporting_doc]);
                            
                            if($supporting_doc) {
                                $stmt = $pdo->prepare("INSERT INTO request_attachments (request_id, file_name, file_path, file_type, uploaded_by, created_at) VALUES (?, ?, ?, 'document', NULL, NOW())");
                                $stmt->execute([$request_id, $_FILES['supporting_doc']['name'], $supporting_doc]);
                            }
                            
                            $pdo->commit();
                            $success = "Update request submitted successfully! Request No: " . $request_no;
                        } catch(Exception $e) {
                            $pdo->rollBack();
                            $error = "Database Error: " . $e->getMessage();
                        }
                    } else {
                        $error = implode("<br>", $validation_errors);
                    }
                }
                ?>

                <?php if(isset($success)): ?>
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h5><?php echo $success; ?></h5>
                        <p>Our IT team will review your update request.</p>
                        <a href="update_request.php" class="btn btn-submit mt-3">Submit Another Request</a>
                        <a href="my_submissions.php" class="btn btn-outline-primary mt-3 ms-2">View My Requests</a>
                    </div>
                <?php else: ?>
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="updateForm">
                        <!-- Employee Information Section - Full Width with PF Verification same as submit_unlisted_device.php -->
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-user"></i> Select Your Name *</label>
                            
                            <?php if($employee_found && $employee_info): ?>
                                <!-- Employee already verified via session/URL -->
                                <div class="employee-info-card">
                                    <div class="row">
                                        <div class="col-md-6"><strong>PF No:</strong> <?php echo htmlspecialchars($employee_info['pf_no']); ?></div>
                                        <div class="col-md-6"><strong>Name:</strong> <?php echo htmlspecialchars($employee_info['full_name']); ?></div>
                                        <div class="col-md-6"><strong>Designation:</strong> <?php echo htmlspecialchars($employee_info['designation']); ?></div>
                                        <div class="col-md-6"><strong>Department:</strong> <?php echo htmlspecialchars($employee_info['department']); ?></div>
                                    </div>
                                </div>
                                <input type="hidden" name="verified_employee_id" value="<?php echo $employee_id; ?>">
                                <input type="hidden" id="employee_verified" value="1">
                            <?php else: ?>
                                <!-- PF Verification Section - Same as submit_unlisted_device.php -->
                                <div class="row">
                                    <div class="col-md-8 mb-2 mb-md-0">
                                        <input type="text" id="pf_no_search" class="form-control" placeholder="Enter your PF Number to verify" autocomplete="off">
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" id="verifyPfBtn" class="btn btn-primary w-100">Verify PF</button>
                                    </div>
                                </div>
                                <div id="employee_info_display" style="display:none;" class="mt-3"></div>
                                <input type="hidden" name="verified_employee_id" id="selected_employee_id" value="">
                                <input type="hidden" id="employee_verified" value="0">
                                <small class="text-muted">Enter your PF Number (e.g., 100003) to verify your identity</small>
                            <?php endif; ?>
                        </div>

                        <!-- Item Type Dropdown -->
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-tag"></i> Item Type *</label>
                            <select name="item_type_id" id="itemTypeSelect" class="form-select" required>
                                <option value="">Select Item Type</option>
                                <?php foreach($item_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Select the type/category of the device</small>
                        </div>

                        <!-- Item Name - Text Field -->
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-laptop"></i> Item Name *</label>
                            <input type="text" name="device_name" id="deviceNameInput" class="form-control" placeholder="Enter the exact device name as in inventory" required autocomplete="off">
                            <small class="text-muted">Enter the exact device name (e.g., Dell Latitude 3420, HP LaserJet Pro M402dn)</small>
                            <div id="deviceSearchStatus" class="mt-1 small"></div>
                        </div>

                        <div id="deviceInfo" class="device-card">
                            <div class="row">
                                <div class="col-md-4"><small>Item Code</small><br><strong id="displayItemCode"></strong></div>
                                <div class="col-md-4"><small>Device Name</small><br><span id="displayDeviceName"></span></div>
                                <div class="col-md-4"><small>Serial Number</small><br><span id="displaySerial"></span></div>
                                <div class="col-md-12 mt-2"><small>Current Specifications</small><br><span id="displaySpec"></span></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">What needs to be updated? *</label>
                            <select name="update_type" class="form-select" required>
                                <option value="">Select Update Type</option>
                                <option value="specification">📋 Device Specifications</option>
                                <option value="owner">👤 Device Owner/User</option>
                                <option value="location">📍 Location/Department</option>
                                <option value="software">💻 Installed Software</option>
                                <option value="configuration">⚙️ Configuration Settings</option>
                                <option value="warranty">📅 Warranty Information</option>
                                <option value="other">❓ Other</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Current Configuration</label>
                                <input type="text" name="current_config" class="form-control" placeholder="Current configuration/value">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Requested Configuration *</label>
                                <input type="text" name="requested_config" class="form-control" required placeholder="What should be updated to?">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Reason for Update *</label>
                            <textarea name="reason" rows="3" class="form-control" required placeholder="Please explain why this update is needed"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Upload Supporting Document</label>
                            <div class="upload-area" onclick="document.getElementById('docInput').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#f59e0b;"></i>
                                <p class="mb-0">Click to upload supporting document</p>
                                <small>JPG, PNG, PDF (Max 5MB)</small>
                                <input type="file" name="supporting_doc" id="docInput" style="display:none">
                            </div>
                            <div id="docFileName" class="mt-2 small text-muted"></div>
                        </div>

                        <!-- Declaration - Same as submit_unlisted_device.php -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            By submitting this form, you confirm that the update information provided is accurate to the best of your knowledge.
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn-submit" id="submitBtn" <?php echo (!$employee_found) ? 'disabled' : ''; ?>><i class="fas fa-paper-plane"></i> Submit Request</button>
                            <button type="reset" class="btn-reset">Reset</button>
                            <a href="index.php" class="btn-reset" style="text-decoration: none;">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer - Full Width same as submit_unlisted_device.php -->
    <footer>
        <div class="container">
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> IT Inventory Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            <?php if(!$employee_found): ?>
            // PF Verification AJAX - Same as submit_unlisted_device.php
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
                            $('#employee_info_display').html('<div class="employee-info-card"><div class="row"><div class="col-md-6"><strong>PF No:</strong> ' + emp.pf_no + '</div><div class="col-md-6"><strong>Name:</strong> ' + emp.full_name + '</div><div class="col-md-6"><strong>Designation:</strong> ' + (emp.designation || 'N/A') + '</div><div class="col-md-6"><strong>Department:</strong> ' + (emp.department || 'N/A') + '</div></div></div>').show();
                            $('#selected_employee_id').val(emp.id);
                            $('#employee_verified').val('1');
                            $('#submitBtn').prop('disabled', false);
                            $('#pf_no_search').prop('readonly', true);
                            $('#verifyPfBtn').html('<i class="fas fa-check"></i> Verified').removeClass('btn-primary').addClass('btn-success');
                        } else {
                            alert(response.message);
                            $('#employee_info_display').hide();
                            $('#selected_employee_id').val('');
                            $('#employee_verified').val('0');
                            $('#submitBtn').prop('disabled', true);
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
            
            // Debounce timer for device search
            var searchTimeout;
            
            // Item Type and Device Name selection handler - loads device details
            function loadDeviceDetails() {
                var itemTypeId = $('#itemTypeSelect').val();
                var deviceName = $('#deviceNameInput').val().trim();
                
                if(itemTypeId && deviceName) {
                    $('#deviceSearchStatus').html('<i class="fas fa-spinner fa-spin"></i> <span class="text-muted">Searching device...</span>');
                    
                    $.ajax({
                        url: window.location.href + '?action=get_device_by_type_and_name&item_type_id=' + itemTypeId + '&device_name=' + encodeURIComponent(deviceName),
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if(response.success) {
                                var device = response.data;
                                $('#displayItemCode').text(device.item_code || 'N/A');
                                $('#displayDeviceName').text(device.name || 'N/A');
                                $('#displaySerial').text(device.serial_number || 'N/A');
                                $('#displaySpec').text(device.specification || 'No specifications');
                                $('#deviceInfo').fadeIn();
                                $('#deviceSearchStatus').html('<i class="fas fa-check-circle text-success"></i> <span class="text-success">Device found in inventory</span>');
                            } else {
                                $('#deviceInfo').hide();
                                $('#deviceSearchStatus').html('<i class="fas fa-exclamation-triangle text-danger"></i> <span class="text-danger">Device not found. Please check the name.</span>');
                            }
                        },
                        error: function() {
                            $('#deviceInfo').hide();
                            $('#deviceSearchStatus').html('<i class="fas fa-exclamation-triangle text-danger"></i> <span class="text-danger">Error searching device</span>');
                        }
                    });
                } else {
                    $('#deviceInfo').hide();
                    $('#deviceSearchStatus').html('');
                }
            }
            
            // Trigger device search on item type change
            $('#itemTypeSelect').on('change', function() {
                loadDeviceDetails();
            });
            
            // Trigger device search on device name input with debounce
            $('#deviceNameInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    loadDeviceDetails();
                }, 500);
            });
            
            // File upload handler
            $('#docInput').on('change', function() {
                if(this.files && this.files[0]) {
                    $('#docFileName').html('<i class="fas fa-check-circle text-success"></i> ' + this.files[0].name);
                }
            });
            
            // Reset button handler - Same as submit_unlisted_device.php
            $('button[type="reset"]').on('click', function(e) {
                e.preventDefault();
                location.reload();
            });
            
            // Form submission validation
            $('#updateForm').submit(function(e) {
                <?php if(!$employee_found): ?>
                if($('#employee_verified').val() !== '1' || !$('#selected_employee_id').val()) {
                    e.preventDefault();
                    alert('Please verify your PF number first');
                    return false;
                }
                <?php endif; ?>
                
                if(!$('#itemTypeSelect').val()) {
                    e.preventDefault();
                    alert('Please select Item Type');
                    return false;
                }
                
                if(!$('#deviceNameInput').val().trim()) {
                    e.preventDefault();
                    alert('Please enter Item Name');
                    return false;
                }
                
                // Check if device was found
                if($('#deviceInfo').css('display') !== 'block') {
                    e.preventDefault();
                    alert('Please enter a valid device name that exists in the inventory');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>