<?php
// Start session at the very beginning
session_start();

require_once '../config/database.php';

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userName = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
$userRole = $_SESSION['role'] ?? '';

// Check if employee is already verified via session
$employee_found = false;
$employee_info = null;
$employee_id = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : 0;

if($employee_id > 0) {
    $stmt = $pdo->prepare("SELECT id, pf_no, full_name, designation, department, phone, email FROM employees WHERE id = ? AND is_active = 1");
    $stmt->execute([$employee_id]);
    $employee_info = $stmt->fetch();
    if($employee_info) {
        $employee_found = true;
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
        echo json_encode(['success' => true, 'data' => $employee]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found with PF Number: ' . $pf_no]);
    }
    exit();
}

// Get item types for dropdown
$item_types = $pdo->query("SELECT id, name FROM item_types WHERE is_active = 1 ORDER BY name")->fetchAll();

// Handle file upload function
function uploadAttachment($file, $prefix) {
    if(isset($file) && $file['error'] == 0 && $file['size'] > 0) {
        $upload_dir = '../uploads/assign_requests/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $new_filename = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if(move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                return 'uploads/assign_requests/' . $new_filename;
            }
        }
    }
    return null;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = (int)$_POST['employee_id'];
    $item_type_id = (int)$_POST['item_type_id'];
    $item_name = trim($_POST['item_name']);
    $required_specifications = trim($_POST['required_specifications']);
    $reason = trim($_POST['reason']);
    $project_duration = trim($_POST['project_duration']);
    $urgent_requirement = isset($_POST['urgent_requirement']) ? 1 : 0;
    $supervisor_approval = isset($_POST['supervisor_approval']) ? 1 : 0;
    
    // Handle file upload
    $attachment_path = uploadAttachment($_FILES['attachment'], 'ASNREQ');
    
    $validation_errors = [];
    if($employee_id <= 0) $validation_errors[] = "Please select a valid employee";
    if($item_type_id <= 0) $validation_errors[] = "Please select an item type";
    if(empty($item_name)) $validation_errors[] = "Please enter the item name";
    if(empty($reason)) $validation_errors[] = "Please provide a reason";
    
    if(empty($validation_errors)) {
        $request_no = 'ASN-' . date('YmdHis') . rand(100, 999);
        
        // Get item type name
        $type_stmt = $pdo->prepare("SELECT name FROM item_types WHERE id = ?");
        $type_stmt->execute([$item_type_id]);
        $item_type_name = $type_stmt->fetchColumn();
        
        $request_data = json_encode([
            'item_type_id' => $item_type_id, 
            'item_type_name' => $item_type_name,
            'item_name' => $item_name, 
            'required_specifications' => $required_specifications, 
            'project_duration' => $project_duration, 
            'urgent_requirement' => $urgent_requirement,
            'attachment' => $attachment_path
        ]);
        $description = "New Device Assignment Request\nItem Type: $item_type_name\nItem Name: $item_name\nSpecs: $required_specifications\nReason: $reason";
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO requests (request_no, employee_id, request_type, description, request_data, urgency_level, requested_date, status, created_at) 
                                  VALUES (?, ?, 'device_assignment', ?, ?, 'medium', NOW(), 'pending', NOW())");
            $stmt->execute([$request_no, $employee_id, $description, $request_data]);
            $request_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO assign_device_requests (request_id, device_id, required_specifications, reason, project_duration, supervisor_approval, urgent_requirement, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$request_id, 0, $required_specifications, $reason, $project_duration, $supervisor_approval, $urgent_requirement]);
            
            // Save attachment if uploaded
            if($attachment_path) {
                $stmt = $pdo->prepare("INSERT INTO request_attachments (request_id, file_name, file_path, file_type, uploaded_by, created_at) VALUES (?, ?, ?, 'document', NULL, NOW())");
                $stmt->execute([$request_id, $_FILES['attachment']['name'], $attachment_path]);
            }
            
            $pdo->commit();
            $success = "Assignment request submitted! Request No: " . $request_no;
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Database Error: " . $e->getMessage();
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
    <title>New Device Assignment - IT Support</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cambria', 'Georgia', 'Times New Roman', serif; background: #f5f7fa; }
        
        /* Navbar - Smaller height */
        .navbar { background: #ffffff; box-shadow: 0 2px 15px rgba(0,0,0,0.05); padding: 0.4rem 0; position: fixed; width: 100%; top: 0; z-index: 1000; }
        .navbar-brand { font-size: 1.1rem; font-weight: 700; color: #3b82f6; font-family: 'Cambria', 'Georgia', serif; }
        .navbar-brand i { color: #3b82f6; margin-right: 8px; }
        .nav-link { font-weight: 500; color: #4b5563; transition: all 0.3s ease; margin: 0 0.5rem; font-size: 0.8rem; font-family: 'Cambria', 'Georgia', serif; }
        .nav-link:hover { color: #3b82f6; transform: translateY(-2px); }
        
        .btn-login, .btn-dashboard, .btn-logout { border: none; padding: 5px 18px; border-radius: 30px; font-weight: 600; font-size: 0.75rem; text-decoration: none; display: inline-block; font-family: 'Cambria', 'Georgia', serif; }
        .btn-login { background: #3b82f6; color: white; }
        .btn-login:hover { background: #2563eb; color: white; }
        .btn-dashboard { background: #10b981; color: white; }
        .btn-dashboard:hover { background: #059669; color: white; }
        .btn-logout { background: #ef4444; color: white; }
        .btn-logout:hover { background: #dc2626; color: white; }
        
        .main-content { padding-top: 65px; min-height: 100vh; }
        .form-card { background: white; border-radius: 20px; padding: 30px; max-width: 900px; margin: 0 auto; box-shadow: 0 20px 40px rgba(0,0,0,0.08); }
        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { font-size: 1.5rem; font-weight: 700; color: #1f2937; margin-bottom: 8px; font-family: 'Cambria', 'Georgia', serif; }
        .form-header .underline { width: 50px; height: 3px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); margin: 12px auto 0; border-radius: 3px; }
        .form-label { font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.8rem; }
        .form-control, .form-select { border: 2px solid #e2e8f0; border-radius: 12px; padding: 10px 15px; font-size: 0.85rem; }
        .form-control:focus, .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); outline: none; }
        
        .btn-submit { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 10px 30px; border-radius: 50px; font-weight: 600; border: none; font-size: 0.85rem; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(59,130,246,0.4); }
        .btn-reset { background: #e2e8f0; color: #475569; padding: 10px 30px; border-radius: 50px; font-weight: 600; border: none; margin-left: 15px; font-size: 0.85rem; }
        
        footer { background: #1f2937; color: white; padding: 12px 0; margin-top: 40px; }
        .copyright { text-align: center; color: #9ca3af; font-size: 0.65rem; }
        
        .employee-info-card { background: #eff6ff; border-radius: 12px; padding: 15px; margin-bottom: 20px; border-left: 4px solid #3b82f6; }
        .upload-area { border: 2px dashed #e2e8f0; border-radius: 12px; padding: 15px; text-align: center; cursor: pointer; background: white; margin-top: 5px; }
        .upload-area:hover { border-color: #3b82f6; background: #eff6ff; }
        
        @media (max-width: 768px) {
            .form-card { padding: 20px; margin: 0 15px; }
            .btn-submit, .btn-reset { width: 100%; margin: 5px 0; }
            .navbar { padding: 0.3rem 0; }
            .navbar-brand { font-size: 1rem; }
            .nav-link { font-size: 0.7rem; margin: 0 0.3rem; }
            .btn-dashboard, .btn-logout, .btn-login { padding: 4px 12px; font-size: 0.7rem; }
        }
    </style>
</head>
<body>
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
                    <h2><i class="fas fa-laptop"></i> New Device Assignment</h2>
                    <div class="underline"></div>
                    <p class="text-muted mt-2">Request for new device allocation or assignment</p>
                </div>

                <?php if(isset($success)): ?>
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h5><?php echo $success; ?></h5>
                        <p>Our IT team will review your request.</p>
                        <a href="index.php" class="btn btn-submit mt-3">Submit Another Request</a>
                    </div>
                <?php else: ?>
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Employee Information Section with PF Verification -->
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-user"></i> Select Your Name *</label>
                            
                            <?php if($employee_found && $employee_info): ?>
                                <div class="employee-info-card">
                                    <div class="row">
                                        <div class="col-md-6"><strong>PF No:</strong> <?php echo htmlspecialchars($employee_info['pf_no']); ?></div>
                                        <div class="col-md-6"><strong>Name:</strong> <?php echo htmlspecialchars($employee_info['full_name']); ?></div>
                                        <div class="col-md-6"><strong>Designation:</strong> <?php echo htmlspecialchars($employee_info['designation']); ?></div>
                                        <div class="col-md-6"><strong>Department:</strong> <?php echo htmlspecialchars($employee_info['department']); ?></div>
                                    </div>
                                </div>
                                <input type="hidden" name="employee_id" id="selected_employee_id" value="<?php echo $employee_id; ?>">
                                <input type="hidden" id="employee_verified" value="1">
                            <?php else: ?>
                                <div class="row">
                                    <div class="col-md-8 mb-2 mb-md-0">
                                        <input type="text" id="pf_no_search" class="form-control" placeholder="Enter your PF Number to verify" autocomplete="off">
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" id="verifyPfBtn" class="btn btn-primary w-100">Verify PF</button>
                                    </div>
                                </div>
                                <div id="employee_info_display" style="display:none;" class="mt-3"></div>
                                <input type="hidden" name="employee_id" id="selected_employee_id_hidden" value="">
                                <input type="hidden" id="employee_verified" value="0">
                                <small class="text-muted">Enter your PF Number (e.g., 100003) to verify your identity</small>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><i class="fas fa-tags"></i> Item Type *</label>
                                <select name="item_type_id" class="form-select" id="itemTypeSelect" required>
                                    <option value="">-- Select Item Type --</option>
                                    <?php foreach($item_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><i class="fas fa-box"></i> Item Name *</label>
                                <input type="text" name="item_name" class="form-control" placeholder="e.g., Dell XPS 15, HP LaserJet Pro" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Required Specifications</label>
                            <textarea name="required_specifications" rows="3" class="form-control" placeholder="RAM, Processor, Storage, OS, etc."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Project Duration</label>
                                <select name="project_duration" class="form-select">
                                    <option value="short_term">Short Term (&lt;3 months)</option>
                                    <option value="medium_term">Medium Term (3-12 months)</option>
                                    <option value="long_term">Long Term (>1 year)</option>
                                    <option value="permanent">Permanent</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="urgent_requirement" class="form-check-input" id="urgent">
                                    <label class="form-check-label" for="urgent">Urgent requirement</label>
                                </div>
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="supervisor_approval" class="form-check-input" id="approval">
                                    <label class="form-check-label" for="approval">Supervisor approval obtained</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Reason for Request *</label>
                            <textarea name="reason" rows="3" class="form-control" required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-paperclip"></i> Supporting Document (Optional)</label>
                            <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#3b82f6;"></i>
                                <p class="mb-0">Click to upload document</p>
                                <small>JPG, PNG, PDF, DOC (Max 5MB)</small>
                                <input type="file" name="attachment" id="fileInput" style="display:none" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            </div>
                            <div id="fileName" class="mt-2 small text-muted"></div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn-submit" id="submitBtn" disabled><i class="fas fa-paper-plane"></i> Submit Request</button>
                            <button type="reset" class="btn-reset"><i class="fas fa-undo"></i> Reset</button>
                            <a href="index.php" class="btn-reset">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

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
        <?php if(!$employee_found): ?>
        // PF Verification
        $('#verifyPfBtn').click(function() {
            var pfNo = $('#pf_no_search').val().trim();
            if(!pfNo) {
                alert('Please enter your PF Number');
                return;
            }
            
            $('#verifyPfBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verifying...');
            
            $.ajax({
                url: window.location.href + '?action=verify_employee&pf_no=' + encodeURIComponent(pfNo),
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        var emp = response.data;
                        $('#employee_info_display').html('<div class="employee-info-card"><div class="row"><div class="col-md-6"><strong>PF No:</strong> ' + emp.pf_no + '</div><div class="col-md-6"><strong>Name:</strong> ' + emp.full_name + '</div><div class="col-md-6"><strong>Designation:</strong> ' + (emp.designation || 'N/A') + '</div><div class="col-md-6"><strong>Department:</strong> ' + (emp.department || 'N/A') + '</div></div></div>').show();
                        $('#selected_employee_id_hidden').val(emp.id);
                        $('#employee_verified').val('1');
                        $('#submitBtn').prop('disabled', false);
                        $('#pf_no_search').prop('readonly', true);
                        $('#verifyPfBtn').html('<i class="fas fa-check"></i> Verified').removeClass('btn-primary').addClass('btn-success');
                    } else {
                        alert(response.message);
                        $('#employee_info_display').hide();
                        $('#selected_employee_id_hidden').val('');
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
        <?php else: ?>
        // Employee already verified via session
        $('#submitBtn').prop('disabled', false);
        <?php endif; ?>
        
        // File upload handler
        $('#fileInput').on('change', function() { 
            if(this.files && this.files[0]) { 
                $('#fileName').html('<i class="fas fa-check-circle text-success"></i> ' + this.files[0].name); 
            } 
        });
        
        // Reset form handler
        $('button[type="reset"]').click(function(e) {
            e.preventDefault();
            $('#itemTypeSelect').val('');
            $('input[name="item_name"]').val('');
            $('textarea[name="required_specifications"]').val('');
            $('select[name="project_duration"]').val('short_term');
            $('input[name="urgent_requirement"]').prop('checked', false);
            $('input[name="supervisor_approval"]').prop('checked', false);
            $('textarea[name="reason"]').val('');
            $('#fileInput').val('');
            $('#fileName').html('');
        });
        
        // Form validation before submit
        $('form').on('submit', function(e) {
            <?php if(!$employee_found): ?>
            if($('#employee_verified').val() !== '1' || !$('#selected_employee_id_hidden').val()) {
                e.preventDefault();
                alert('Please verify your PF number first');
                return false;
            }
            <?php endif; ?>
            
            if(!$('#itemTypeSelect').val()) {
                e.preventDefault();
                alert('Please select an item type');
                return false;
            }
            
            var itemName = $('input[name="item_name"]').val().trim();
            if(itemName === '') {
                e.preventDefault();
                alert('Please enter the item name');
                return false;
            }
            
            var reason = $('textarea[name="reason"]').val().trim();
            if(reason === '') {
                e.preventDefault();
                alert('Please provide a reason for the request');
                return false;
            }
            return true;
        });
    </script>
</body>
</html>