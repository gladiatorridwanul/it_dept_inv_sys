<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// ============================================
// FIX: Check if version column exists, if not add it
// ============================================
try {
    $check = $pdo->query("SHOW COLUMNS FROM licenses LIKE 'version'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE licenses ADD COLUMN version VARCHAR(50) DEFAULT NULL AFTER software_name");
    }
} catch(PDOException $e) {
    error_log("Error checking/adding version column: " . $e->getMessage());
}

// Get all data for dropdowns
$items = $pdo->query("SELECT id, name, item_code FROM items WHERE is_active = 1 ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
$sub_categories = [];
try {
    $sub_categories = $pdo->query("SELECT id, name, category_id FROM sub_categories WHERE is_active = 1 ORDER BY name")->fetchAll();
} catch(PDOException $e) {
    error_log("Sub categories table not found: " . $e->getMessage());
}
$vendors = $pdo->query("SELECT id, vendor_name FROM vendors WHERE is_active = 1 ORDER BY vendor_name")->fetchAll();
$employees = $pdo->query("SELECT id, pf_no, full_name, designation, department FROM employees WHERE is_active = 1 ORDER BY full_name")->fetchAll();

// Handle form submissions (except add - now handled by add_license.php)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'edit_license':
                addOrUpdateLicense($pdo, 'edit');
                break;
            case 'deactivate_license':
                deactivateLicense($pdo);
                break;
            case 'activate_license':
                activateLicense($pdo);
                break;
            case 'assign_license':
                assignLicense($pdo);
                break;
            case 'revoke_license':
                revokeLicense($pdo);
                break;
        }
    }
}

function addOrUpdateLicense($pdo, $mode) {
    $id = $mode == 'edit' ? intval($_POST['id']) : null;
    $software_name = trim($_POST['software_name'] ?? '');
    $license_key = trim($_POST['license_key'] ?? '');
    $version = trim($_POST['version'] ?? '');
    $license_type = $_POST['license_type'] ?? 'perpetual';
    $purchase_date = $_POST['purchase_date'] ?? null;
    $expiry_date = $_POST['expiry_date'] ?? null;
    $cost = isset($_POST['cost']) ? floatval($_POST['cost']) : 0;
    $vendor_id = !empty($_POST['vendor_id']) ? intval($_POST['vendor_id']) : null;
    $item_id = !empty($_POST['item_id']) ? intval($_POST['item_id']) : null;
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $sub_category_id = !empty($_POST['sub_category_id']) ? intval($_POST['sub_category_id']) : null;
    $seats = isset($_POST['seats']) ? intval($_POST['seats']) : 1;
    $notes = trim($_POST['notes'] ?? '');
    $vendor_name = trim($_POST['vendor_name'] ?? '');
    $purchased_from = trim($_POST['purchased_from'] ?? '');
    $invoice_number = trim($_POST['invoice_number'] ?? '');
    $po_number = trim($_POST['po_number'] ?? '');
    $support_contact = trim($_POST['support_contact'] ?? '');
    $support_email = trim($_POST['support_email'] ?? '');
    $user_id = $_SESSION['user_id'] ?? 1;
    
    $today = date('Y-m-d');
    $status = 'active';
    if($expiry_date && $expiry_date < $today) {
        $status = 'expired';
    } elseif($expiry_date && $expiry_date <= date('Y-m-d', strtotime('+30 days'))) {
        $status = 'expiring_soon';
    }
    
    // Handle file upload
    $documentation_file = null;
    if(isset($_FILES['documentation_file']) && $_FILES['documentation_file']['error'] == 0) {
        $upload_dir = '../../uploads/licenses/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['documentation_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'png'];
        if(in_array($ext, $allowed)) {
            $new_filename = 'LIC_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if(move_uploaded_file($_FILES['documentation_file']['tmp_name'], $upload_dir . $new_filename)) {
                $documentation_file = 'uploads/licenses/' . $new_filename;
            }
        }
    }
    
    if($mode == 'add') {
        header("Location: add_license.php");
        exit();
    } else {
        $old_stmt = $pdo->prepare("SELECT * FROM licenses WHERE id = ?");
        $old_stmt->execute([$id]);
        $old_data = $old_stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("UPDATE licenses SET 
            software_name = ?, license_key = ?, version = ?, license_type = ?, 
            purchase_date = ?, expiry_date = ?, vendor_id = ?, vendor_name = ?, 
            item_id = ?, category_id = ?, sub_category_id = ?, seats = ?, 
            status = ?, notes = ?, purchased_from = ?, invoice_number = ?, 
            po_number = ?, support_contact = ?, support_email = ?, 
            documentation_file = COALESCE(?, documentation_file), 
            updated_by = ?, updated_at = NOW() 
            WHERE id = ?"
        );
        $stmt->execute([
            $software_name, $license_key, $version, $license_type,
            $purchase_date, $expiry_date, $vendor_id, $vendor_name,
            $item_id, $category_id, $sub_category_id, $seats,
            $status, $notes, $purchased_from, $invoice_number,
            $po_number, $support_contact, $support_email,
            $documentation_file, $user_id, $id
        ]);
        
        logLicenseHistory($pdo, $id, 'updated', json_encode($old_data), json_encode($_POST), $user_id);
        $_SESSION['success_message'] = "License updated successfully!";
        header("Location: licenses.php");
        exit();
    }
}

function deactivateLicense($pdo) {
    $id = intval($_POST['id']);
    $user_id = $_SESSION['user_id'] ?? 1;
    $stmt = $pdo->prepare("UPDATE licenses SET is_active = 0, updated_by = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$user_id, $id]);
    logLicenseHistory($pdo, $id, 'deactivated', null, json_encode(['is_active' => 0]), $user_id);
    $_SESSION['success_message'] = "License deactivated successfully!";
    header("Location: licenses.php");
    exit();
}

function activateLicense($pdo) {
    $id = intval($_POST['id']);
    $user_id = $_SESSION['user_id'] ?? 1;
    $stmt = $pdo->prepare("UPDATE licenses SET is_active = 1, updated_by = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$user_id, $id]);
    logLicenseHistory($pdo, $id, 'activated', null, json_encode(['is_active' => 1]), $user_id);
    $_SESSION['success_message'] = "License activated successfully!";
    header("Location: licenses.php");
    exit();
}

function assignLicense($pdo) {
    $license_id = intval($_POST['license_id']);
    $assigned_to_employee_id = !empty($_POST['assigned_to_employee_id']) ? intval($_POST['assigned_to_employee_id']) : null;
    $assigned_date = $_POST['assigned_date'] ?? date('Y-m-d');
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $notes = trim($_POST['notes'] ?? '');
    $user_id = $_SESSION['user_id'] ?? 1;
    
    // Check if employee exists
    $employee_name = '';
    if($assigned_to_employee_id) {
        $emp_check = $pdo->prepare("SELECT id, full_name, designation, department FROM employees WHERE id = ? AND is_active = 1");
        $emp_check->execute([$assigned_to_employee_id]);
        $employee = $emp_check->fetch();
        if(!$employee) {
            $_SESSION['error_message'] = "Invalid employee selected!";
            header("Location: licenses.php");
            exit();
        }
        $employee_name = $employee['full_name'];
    } else {
        $_SESSION['error_message'] = "Please select an employee to assign this license to!";
        header("Location: licenses.php");
        exit();
    }
    
    // Check if license has available seats
    $check_stmt = $pdo->prepare("SELECT seats, used_seats, software_name FROM licenses WHERE id = ?");
    $check_stmt->execute([$license_id]);
    $license = $check_stmt->fetch();
    
    if(!$license) {
        $_SESSION['error_message'] = "License not found!";
        header("Location: licenses.php");
        exit();
    }
    
    if($license['used_seats'] >= $license['seats']) {
        $_SESSION['error_message'] = "No available seats for this license! (Used: " . $license['used_seats'] . "/" . $license['seats'] . ")";
        header("Location: licenses.php");
        exit();
    }
    
    // Insert into license_assignments table
    try {
        $pdo->beginTransaction();
        
        // Insert assignment - using the correct table structure
        $stmt = $pdo->prepare("INSERT INTO license_assignments 
            (license_id, employee_id, employee_name, assigned_date, notes, created_at, status, assigned_by) 
            VALUES (?, ?, ?, ?, ?, NOW(), 'active', ?)");
        $stmt->execute([
            $license_id, 
            $assigned_to_employee_id, 
            $employee_name, 
            $assigned_date, 
            $notes, 
            $user_id
        ]);
        
        $assignment_id = $pdo->lastInsertId();
        
        // Update used_seats in licenses table
        $update_stmt = $pdo->prepare("UPDATE licenses SET used_seats = used_seats + 1 WHERE id = ?");
        $update_stmt->execute([$license_id]);
        
        // Log the assignment
        logLicenseHistory($pdo, $license_id, 'assigned', null, json_encode([
            'employee_id' => $assigned_to_employee_id,
            'employee_name' => $employee_name,
            'assigned_date' => $assigned_date
        ]), $user_id);
        
        $pdo->commit();
        
        // Store assignment details in session for success popup
        $_SESSION['assignment_success'] = [
            'license_name' => $license['software_name'],
            'employee_name' => $employee_name,
            'assigned_date' => $assigned_date,
            'assignment_id' => $assignment_id,
            'message' => "License assigned successfully to " . $employee_name . "!"
        ];
        
        header("Location: licenses.php?assignment_success=1");
        exit();
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error assigning license: " . $e->getMessage();
        header("Location: licenses.php");
        exit();
    }
}

function revokeLicense($pdo) {
    $assignment_id = intval($_POST['assignment_id']);
    $license_id = intval($_POST['license_id']);
    $user_id = $_SESSION['user_id'] ?? 1;
    
    $pdo->beginTransaction();
    try {
        // Get assignment details first for logging
        $get_stmt = $pdo->prepare("SELECT * FROM license_assignments WHERE id = ?");
        $get_stmt->execute([$assignment_id]);
        $assignment = $get_stmt->fetch();
        
        if($assignment) {
            // Update status to revoked
            $stmt = $pdo->prepare("UPDATE license_assignments SET status = 'revoked' WHERE id = ?");
            $stmt->execute([$assignment_id]);
            
            // Update used_seats in licenses table
            $update_stmt = $pdo->prepare("UPDATE licenses SET used_seats = used_seats - 1 WHERE id = ?");
            $update_stmt->execute([$license_id]);
            
            logLicenseHistory($pdo, $license_id, 'revoked', json_encode($assignment), null, $user_id);
        }
        
        $pdo->commit();
        $_SESSION['success_message'] = "License revoked successfully!";
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error revoking license: " . $e->getMessage();
    }
    
    header("Location: licenses.php");
    exit();
}

function logLicenseHistory($pdo, $license_id, $action, $old_value, $new_value, $performed_by) {
    try {
        $stmt = $pdo->prepare("INSERT INTO license_history (license_id, action, old_value, new_value, performed_by, performed_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$license_id, $action, $old_value, $new_value, $performed_by]);
    } catch(PDOException $e) {
        error_log("License history table not found: " . $e->getMessage());
    }
}

// Get all licenses
$licenses = $pdo->query("
    SELECT l.*, 
           v.vendor_name as vendor_company,
           i.name as item_name,
           c.name as category_name,
           sc.name as sub_category_name,
           (SELECT COUNT(*) FROM license_assignments WHERE license_id = l.id AND status = 'active') as assigned_count
    FROM licenses l
    LEFT JOIN vendors v ON l.vendor_id = v.id
    LEFT JOIN items i ON l.item_id = i.id
    LEFT JOIN categories c ON l.category_id = c.id
    LEFT JOIN categories sc ON l.sub_category_id = sc.id
    ORDER BY l.expiry_date ASC
")->fetchAll();

// Get license assignments - only active ones
$assignments = [];
try {
    $assignments = $pdo->query("
        SELECT la.*, l.software_name, 
               e.full_name as employee_name, e.pf_no, e.designation, e.department
        FROM license_assignments la
        JOIN licenses l ON la.license_id = l.id
        LEFT JOIN employees e ON la.employee_id = e.id
        WHERE la.status = 'active'
        ORDER BY la.assigned_date DESC
    ")->fetchAll();
} catch(PDOException $e) {
    error_log("Error loading assignments: " . $e->getMessage());
    $assignments = [];
}

if(isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Check for assignment success popup
$show_assignment_success = isset($_GET['assignment_success']) && $_GET['assignment_success'] == 1 && isset($_SESSION['assignment_success']);
$assignment_success_data = [];
if ($show_assignment_success) {
    $assignment_success_data = $_SESSION['assignment_success'];
    unset($_SESSION['assignment_success']);
}

$show_success_modal = isset($_GET['success']) && $_GET['success'] == 1 && isset($_SESSION['license_success']);
$success_data = [];
if ($show_success_modal) {
    $success_data = $_SESSION['license_success'];
    unset($_SESSION['license_success']);
}

// Build lookup arrays for edit modal
$license_data_json = [];
foreach($licenses as $l) {
    $license_data_json[$l['id']] = $l;
}
$license_data_encoded = json_encode($license_data_json);
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Load jQuery first, then bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .stats-card { background: white; border-radius: 20px; padding: 25px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05); transition: transform 0.3s; }
        .stats-card:hover { transform: translateY(-5px); }
        .stats-number { font-size: 36px; font-weight: 800; }
        .stats-label { font-size: 13px; color: #6c757d; text-transform: uppercase; }
        .status-badge { display: inline-block; padding: 5px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-active { background: #d1fae5; color: #059669; }
        .status-expired { background: #fee2e2; color: #dc2626; }
        .status-expiring_soon { background: #fef3c7; color: #d97706; }
        .section-tab { display: inline-block; padding: 12px 30px; background: white; border-radius: 40px; margin: 0 8px; cursor: pointer; font-weight: 600; border: 1px solid #dee2e6; }
        .section-tab.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: transparent; }
        .action-buttons .btn { padding: 4px 10px; margin: 2px; font-size: 12px; border-radius: 8px; }
        .required-field::after { content: " *"; color: red; }
        .btn-gradient-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; }
        
        /* Success Modal Styles */
        .success-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }
        .success-modal-overlay.show {
            display: flex;
        }
        .success-modal-box {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.4s ease;
            position: relative;
        }
        .success-modal-icon { font-size: 72px; color: #059669; margin-bottom: 15px; animation: bounceIn 0.6s ease; }
        .success-modal-title { font-size: 24px; font-weight: 700; color: #1a202c; margin-bottom: 8px; }
        .success-modal-subtitle { font-size: 16px; color: #4a5568; margin-bottom: 20px; }
        .success-modal-details { background: #f7fafc; border-radius: 10px; padding: 15px 20px; margin-bottom: 25px; text-align: left; }
        .success-modal-details .detail-item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        .success-modal-details .detail-item:last-child { border-bottom: none; }
        .success-modal-details .label { color: #718096; font-weight: 500; }
        .success-modal-details .value { font-weight: 600; color: #2d3748; }
        .success-modal-details .value code { font-size: 12px; background: #edf2f7; padding: 2px 8px; border-radius: 4px; }
        .success-modal-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .success-modal-actions .btn { padding: 10px 30px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-success-modal { background: #059669; color: white; border: none; }
        .btn-success-modal:hover { background: #047857; color: white; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(5,150,105,0.3); }
        .btn-secondary-modal { background: #e2e8f0; color: #4a5568; border: none; }
        .btn-secondary-modal:hover { background: #cbd5e0; color: #2d3748; }

        /* Employee Search Styles */
        .employee-search-container { 
            position: relative;
            width: 100%;
        }
        .employee-search-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: white;
            color: #1a202c;
        }
        .employee-search-input::placeholder {
            color: #a0aec0;
        }
        .employee-search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .employee-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 280px;
            overflow-y: auto;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            z-index: 1000;
            display: none;
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            margin-top: 4px;
        }
        .employee-search-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }
        .employee-search-item:last-child { border-bottom: none; }
        .employee-search-item:hover { background: #f0f7ff; }
        .employee-search-item .emp-name { font-weight: 600; font-size: 14px; color: #1a202c; }
        .employee-search-item .emp-details { font-size: 12px; color: #64748b; margin-top: 2px; }
        .employee-search-item .emp-pf { display: inline-block; background: #e2e8f0; padding: 1px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; color: #475569; margin-left: 6px; }
        .employee-search-empty { padding: 16px; text-align: center; color: #a0aec0; font-size: 14px; }
        .selected-employee-display {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 10px;
            padding: 14px 18px;
            margin-top: 12px;
            display: none;
            align-items: center;
            justify-content: space-between;
        }
        .selected-employee-display .emp-info { display: flex; flex-direction: column; gap: 2px; }
        .selected-employee-display .emp-info .emp-name-display { font-weight: 600; font-size: 15px; color: #065f46; }
        .selected-employee-display .emp-info .emp-details-display { font-size: 13px; color: #047857; }
        .selected-employee-display .clear-emp { cursor: pointer; color: #dc2626; font-size: 13px; font-weight: 500; padding: 4px 12px; border-radius: 6px; transition: background 0.2s; }
        .selected-employee-display .clear-emp:hover { background: #fee2e2; text-decoration: none; }
        .search-hint { font-size: 12px; color: #94a3b8; margin-top: 6px; display: flex; align-items: center; gap: 6px; }
        .search-hint i { color: #64748b; }

        /* View Modal Styles */
        .view-detail-row { display: flex; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .view-detail-row:last-child { border-bottom: none; }
        .view-detail-label { font-weight: 600; color: #475569; width: 180px; flex-shrink: 0; font-size: 14px; }
        .view-detail-value { color: #1a202c; font-size: 14px; }
        .view-detail-value code { background: #f1f5f9; padding: 2px 10px; border-radius: 4px; font-size: 13px; }

        .modal-xl-custom { max-width: 900px; }
        .form-label.required-field::after { content: " *"; color: #dc2626; font-weight: bold; }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.3); }
            50% { opacity: 1; transform: scale(1.1); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); }
        }
        
        .alert-success { background: #d1fae5; color: #065f46; border: none; border-radius: 12px; padding: 15px 20px; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: none; border-radius: 12px; padding: 15px 20px; }
        .alert-dismissible .btn-close { padding: 15px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-key text-primary me-2"></i> License Management</h2>
                    <p class="text-muted">Manage software licenses, track assignments, and monitor renewals</p>
                </div>
                <div>
                    <a href="add_license.php" class="btn btn-gradient-primary">
                        <i class="fas fa-plus me-1"></i> Add License
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="stats-card"><i class="fas fa-key fa-2x text-primary mb-2"></i><div class="stats-number"><?php echo count($licenses); ?></div><div class="stats-label">Total Licenses</div></div></div>
        <div class="col-md-3"><div class="stats-card"><i class="fas fa-check-circle fa-2x text-success mb-2"></i><div class="stats-number"><?php echo count(array_filter($licenses, function($l) { return $l['status'] == 'active' && $l['is_active'] == 1; })); ?></div><div class="stats-label">Active</div></div></div>
        <div class="col-md-3"><div class="stats-card"><i class="fas fa-hourglass-half fa-2x text-warning mb-2"></i><div class="stats-number"><?php echo count(array_filter($licenses, function($l) { return $l['status'] == 'expiring_soon' && $l['is_active'] == 1; })); ?></div><div class="stats-label">Expiring Soon</div></div></div>
        <div class="col-md-3"><div class="stats-card"><i class="fas fa-users fa-2x text-info mb-2"></i><div class="stats-number"><?php echo array_sum(array_column($licenses, 'assigned_count')); ?></div><div class="stats-label">Assignments</div></div></div>
    </div>

    <!-- Tabs -->
    <div class="text-center mb-4">
        <div class="section-tab active" data-section="licenses"><i class="fas fa-list me-2"></i> All Licenses</div>
        <div class="section-tab" data-section="assignments"><i class="fas fa-user-check me-2"></i> Assignments</div>
    </div>

    <!-- Licenses Table -->
    <div id="licensesSection" class="section-content">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr><th>Software</th><th>License Key</th><th>Version</th><th>Type</th><th>Expiry Date</th><th>Seats</th><th>Assigned</th><th>Status</th><th width="200">Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($licenses as $license): ?>
                            <tr class="<?php echo $license['is_active'] == 0 ? 'table-secondary' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($license['software_name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($license['item_name'] ?? 'No device'); ?></small></td>
                                <td><code class="small"><?php echo substr(htmlspecialchars($license['license_key']), 0, 20); ?>...</code></td>
                                <td><?php echo htmlspecialchars($license['version'] ?? '-'); ?></td>
                                <td><?php echo ucfirst($license['license_type']); ?></td>
                                <td><?php echo $license['expiry_date'] ? date('d-m-Y', strtotime($license['expiry_date'])) : 'N/A'; ?></td>
                                <td><?php echo $license['seats']; ?></td>
                                <td><?php echo $license['assigned_count']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $license['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $license['status'])); ?></span>
                                    <?php if($license['is_active'] == 0): ?><span class="status-badge bg-secondary ms-1">Inactive</span><?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewLicense(<?php echo $license['id']; ?>)"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editLicense(<?php echo $license['id']; ?>)"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-success" onclick="showAssignModal(<?php echo $license['id']; ?>, '<?php echo addslashes($license['software_name']); ?>')"><i class="fas fa-user-plus"></i></button>
                                    <?php if($license['is_active'] == 1): ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deactivateItem(<?php echo $license['id']; ?>)"><i class="fas fa-ban"></i></button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-success" onclick="activateItem(<?php echo $license['id']; ?>)"><i class="fas fa-play"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($licenses)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No licenses found. Click "Add License" to get started.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignments Table -->
    <div id="assignmentsSection" class="section-content" style="display:none;">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead><tr><th>License</th><th>Assigned To</th><th>Date</th><th>Expiry</th><th>Status</th><th width="100">Action</th></tr></thead>
                        <tbody>
                            <?php foreach($assignments as $assignment): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($assignment['software_name']); ?></strong></td>
                                <td>
                                    <?php 
                                    if(isset($assignment['employee_name']) && $assignment['employee_name']) {
                                        echo htmlspecialchars($assignment['employee_name']);
                                        if(isset($assignment['pf_no']) && $assignment['pf_no']) {
                                            echo '<br><small>' . htmlspecialchars($assignment['pf_no']) . '</small>';
                                        }
                                        if(isset($assignment['designation']) && $assignment['designation']) {
                                            echo '<br><small>' . htmlspecialchars($assignment['designation']) . '</small>';
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('d-m-Y', strtotime($assignment['assigned_date'])); ?></td>
                                <td><?php echo $assignment['expiry_date'] ? date('d-m-Y', strtotime($assignment['expiry_date'])) : 'N/A'; ?></td>
                                <td><span class="status-badge <?php echo $assignment['status'] == 'active' ? 'status-active' : 'bg-secondary'; ?>"><?php echo ucfirst($assignment['status']); ?></span></td>
                                <td>
                                    <?php if($assignment['status'] == 'active'): ?>
                                    <button class="btn btn-sm btn-danger" onclick="revokeAssignment(<?php echo $assignment['id']; ?>, <?php echo $assignment['license_id']; ?>)">Revoke</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($assignments)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No license assignments found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit License Modal -->
<div class="modal fade" id="licenseModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><span id="modalTitle">Edit License</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="licenseForm">
                    <input type="hidden" name="action" id="formAction" value="edit_license">
                    <input type="hidden" name="id" id="licenseId" value="">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label required-field">Software Name</label><input type="text" name="software_name" id="edit_software_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">License Key</label><input type="text" name="license_key" id="edit_license_key" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Version</label><input type="text" name="version" id="edit_version" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">License Type</label><select name="license_type" id="edit_license_type" class="form-select"><option value="perpetual">Perpetual</option><option value="subscription">Subscription</option><option value="trial">Trial</option></select></div>
                        <div class="col-md-3"><label class="form-label">Seats</label><input type="number" name="seats" id="edit_seats" class="form-control" value="1"></div>
                        <div class="col-md-3"><label class="form-label">Cost (BDT)</label><input type="number" step="0.01" name="cost" id="edit_cost" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Purchase Date</label><input type="date" name="purchase_date" id="edit_purchase_date" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" id="edit_expiry_date" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Select Item</label><select name="item_id" id="edit_item_id" class="form-select"><option value="">-- Select Item --</option><?php foreach($items as $item): ?><option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['item_code'] . ' - ' . $item['name']); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label">Select Vendor</label><select name="vendor_id" id="edit_vendor_id" class="form-select"><option value="">-- Select Vendor --</option><?php foreach($vendors as $vendor): ?><option value="<?php echo $vendor['id']; ?>"><?php echo htmlspecialchars($vendor['vendor_name']); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label">Vendor Name (Manual)</label><input type="text" name="vendor_name" id="edit_vendor_name" class="form-control" placeholder="Enter vendor name if not in list"></div>
                        <div class="col-md-6"><label class="form-label">Select Category</label><select name="category_id" id="edit_category_id" class="form-select"><option value="">-- Select Category --</option><?php foreach($categories as $cat): ?><option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label">Invoice Number</label><input type="text" name="invoice_number" id="edit_invoice_number" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">PO Number</label><input type="text" name="po_number" id="edit_po_number" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Support Contact</label><input type="text" name="support_contact" id="edit_support_contact" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Support Email</label><input type="email" name="support_email" id="edit_support_email" class="form-control"></div>
                        <div class="col-md-12"><label class="form-label">Notes</label><textarea name="notes" id="edit_notes" rows="2" class="form-control"></textarea></div>
                        <div class="col-12"><label class="form-label">Documentation File</label><input type="file" name="documentation_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.png"><small class="text-muted">Upload license agreement or invoice (Max 5MB)</small></div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary ms-2">Update License</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View License Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i> License Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewDetails">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign License Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Assign License</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="assignForm">
                    <input type="hidden" name="action" value="assign_license">
                    <input type="hidden" name="license_id" id="assign_license_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">License</label>
                        <div class="form-control bg-light" style="border-radius:10px; padding:12px 14px;">
                            <strong id="assign_license_name">-- Select License --</strong>
                            <div class="mt-1 small text-muted">
                                <span id="assign_license_details">Seats: -- | Used: --</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assign To (Employee) <span class="text-danger">*</span></label>
                        <div class="employee-search-container">
                            <input type="text" id="employeeSearchInput" class="employee-search-input" 
                                   placeholder="Type PF No. or Employee Name to search..." 
                                   autocomplete="off">
                            <div id="employeeSearchResults" class="employee-search-results"></div>
                        </div>
                        <div class="search-hint">
                            <i class="fas fa-search"></i>
                            <span>Search by PF Number or Employee Name. Type at least 2 characters.</span>
                        </div>
                        <input type="hidden" name="assigned_to_employee_id" id="selectedEmployeeId" value="">
                        <div id="selectedEmployeeDisplay" class="selected-employee-display">
                            <div class="emp-info">
                                <span class="emp-name-display" id="selectedEmployeeName">--</span>
                                <span class="emp-details-display">
                                    <span id="selectedEmployeePF"></span> | <span id="selectedEmployeeDesignation"></span>
                                </span>
                            </div>
                            <span class="clear-emp" onclick="clearEmployeeSelection()">
                                <i class="fas fa-times me-1"></i> Change
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assigned Date</label>
                        <input type="date" name="assigned_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Expiry Date (Optional)</label>
                        <input type="date" name="expiry_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> This will assign the license to the selected employee and reduce available seats by 1.
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success ms-2" id="assignSubmitBtn">
                            <i class="fas fa-check me-1"></i> Assign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Assignment Success Modal -->
<div class="success-modal-overlay <?php echo $show_assignment_success ? 'show' : ''; ?>" id="assignmentSuccessModal">
    <div class="success-modal-box">
        <div class="success-modal-icon"><i class="fas fa-check-circle"></i></div>
        <div class="success-modal-title">License Assigned Successfully!</div>
        <div class="success-modal-subtitle"><?php echo htmlspecialchars($assignment_success_data['message'] ?? 'The license has been assigned to the employee.'); ?></div>
        <div class="success-modal-details">
            <div class="detail-item"><span class="label">License</span><span class="value"><?php echo htmlspecialchars($assignment_success_data['license_name'] ?? 'N/A'); ?></span></div>
            <div class="detail-item"><span class="label">Assigned To</span><span class="value"><?php echo htmlspecialchars($assignment_success_data['employee_name'] ?? 'N/A'); ?></span></div>
            <div class="detail-item"><span class="label">Assigned Date</span><span class="value"><?php echo isset($assignment_success_data['assigned_date']) ? date('d-m-Y', strtotime($assignment_success_data['assigned_date'])) : 'N/A'; ?></span></div>
        </div>
        <div class="success-modal-actions">
            <a href="licenses.php" class="btn btn-success-modal"><i class="fas fa-plus me-2"></i> New Assign</a>
            <a href="licenses.php" class="btn btn-secondary-modal"><i class="fas fa-check me-2"></i> Done</a>
        </div>
    </div>
</div>

<!-- Success Modal (for Add License) -->
<?php if($show_success_modal && !empty($success_data)): ?>
<div class="success-modal-overlay show" id="successModal">
    <div class="success-modal-box">
        <div class="success-modal-icon"><i class="fas fa-check-circle"></i></div>
        <div class="success-modal-title">License Added Successfully!</div>
        <div class="success-modal-subtitle">The license has been added to the system.</div>
        <div class="success-modal-details">
            <div class="detail-item"><span class="label">Software</span><span class="value"><?php echo htmlspecialchars($success_data['software_name'] ?? 'N/A'); ?></span></div>
            <div class="detail-item"><span class="label">License Key</span><span class="value"><code><?php echo htmlspecialchars($success_data['license_key'] ?? 'N/A'); ?></code></span></div>
            <div class="detail-item"><span class="label">License Type</span><span class="value"><?php echo ucfirst($success_data['license_type'] ?? 'N/A'); ?></span></div>
            <div class="detail-item"><span class="label">Status</span><span class="value"><span class="status-badge status-<?php echo $success_data['status'] ?? 'active'; ?>"><?php echo ucfirst(str_replace('_', ' ', $success_data['status'] ?? 'Active')); ?></span></span></div>
        </div>
        <div class="success-modal-actions">
            <a href="licenses.php" class="btn btn-success-modal"><i class="fas fa-list me-2"></i> View All Licenses</a>
            <a href="add_license.php" class="btn btn-secondary-modal"><i class="fas fa-plus me-2"></i> Add Another</a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// ============================================
// LICENSE DATA - Pre-loaded from PHP
// ============================================
var licenseData = <?php echo $license_data_encoded; ?>;

console.log('License data loaded:', Object.keys(licenseData).length);

// ============================================
// EMPLOYEE DATA - Available globally
// ============================================
var employees = <?php 
    $empStmt = $pdo->query("SELECT id, pf_no, full_name, designation, department FROM employees WHERE is_active = 1 ORDER BY full_name");
    echo json_encode($empStmt->fetchAll());
?>;

console.log('Employees loaded:', employees.length);

var selectedEmployee = null;
var employeeSearchTimeout = null;

// ============================================
// EMPLOYEE SEARCH FUNCTIONS
// ============================================

function searchEmployees(query) {
    if(!query || query.length < 2) {
        return [];
    }
    var q = query.toLowerCase();
    var results = [];
    for (var i = 0; i < employees.length; i++) {
        var emp = employees[i];
        if (emp.pf_no && emp.pf_no.toLowerCase().indexOf(q) !== -1) {
            results.push(emp);
        } else if (emp.full_name && emp.full_name.toLowerCase().indexOf(q) !== -1) {
            results.push(emp);
        }
    }
    return results;
}

function renderEmployeeResults(results) {
    var container = document.getElementById('employeeSearchResults');
    if (!container) return;
    
    if(results.length === 0) {
        container.innerHTML = '<div class="employee-search-empty"><i class="fas fa-user-slash me-2"></i> No employees found matching your search.</div>';
        container.style.display = 'block';
        return;
    }
    
    var html = '';
    for (var i = 0; i < results.length; i++) {
        var emp = results[i];
        var fullName = emp.full_name || 'Unknown';
        var pfNo = emp.pf_no || '';
        var designation = emp.designation || 'No designation';
        var department = emp.department || 'No department';
        
        html += '<div class="employee-search-item" onclick="selectEmployee(' + emp.id + ', \'' + escapeHtml(fullName) + '\', \'' + escapeHtml(pfNo) + '\', \'' + escapeHtml(designation) + '\', \'' + escapeHtml(department) + '\')">' +
                    '<div class="emp-name">' + escapeHtml(fullName) + ' <span class="emp-pf">' + escapeHtml(pfNo) + '</span></div>' +
                    '<div class="emp-details">' + escapeHtml(designation) + ' | ' + escapeHtml(department) + '</div>' +
                '</div>';
    }
    container.innerHTML = html;
    container.style.display = 'block';
}

function selectEmployee(id, name, pf, designation, department) {
    selectedEmployee = { id: id, name: name, pf: pf, designation: designation, department: department };
    document.getElementById('selectedEmployeeId').value = id;
    document.getElementById('selectedEmployeeName').textContent = name;
    document.getElementById('selectedEmployeePF').textContent = 'PF: ' + pf;
    document.getElementById('selectedEmployeeDesignation').textContent = designation || 'No designation';
    document.getElementById('selectedEmployeeDisplay').style.display = 'flex';
    document.getElementById('employeeSearchInput').value = name + ' (' + pf + ')';
    document.getElementById('employeeSearchResults').style.display = 'none';
    document.getElementById('assignSubmitBtn').disabled = false;
}

function clearEmployeeSelection() {
    selectedEmployee = null;
    document.getElementById('selectedEmployeeId').value = '';
    document.getElementById('employeeSearchInput').value = '';
    document.getElementById('selectedEmployeeDisplay').style.display = 'none';
    document.getElementById('assignSubmitBtn').disabled = true;
    document.getElementById('employeeSearchInput').focus();
}

function escapeHtml(str) {
    if(!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}

// ============================================
// VIEW LICENSE FUNCTION - Uses pre-loaded data
// ============================================
function viewLicense(id) { 
    var modalBody = document.getElementById('viewDetails');
    if (!modalBody) return;
    
    var data = licenseData[id];
    if (!data) {
        modalBody.innerHTML = '<div class="alert alert-danger text-center py-4"><i class="fas fa-exclamation-triangle me-2"></i> License not found.</div>';
        $('#viewModal').modal('show');
        return;
    }
    
    // Build HTML for view
    var html = '';
    html += '<div class="view-detail-row"><div class="view-detail-label">Software Name</div><div class="view-detail-value"><strong>' + escapeHtml(data.software_name || 'N/A') + '</strong></div></div>';
    html += '<div class="view-detail-row"><div class="view-detail-label">License Key</div><div class="view-detail-value"><code>' + escapeHtml(data.license_key || 'N/A') + '</code></div></div>';
    html += '<div class="view-detail-row"><div class="view-detail-label">Version</div><div class="view-detail-value">' + escapeHtml(data.version || 'N/A') + '</div></div>';
    html += '<div class="view-detail-row"><div class="view-detail-label">License Type</div><div class="view-detail-value">' + escapeHtml(ucfirst(data.license_type || 'N/A')) + '</div></div>';
    html += '<div class="view-detail-row"><div class="view-detail-label">Purchase Date</div><div class="view-detail-value">' + (data.purchase_date ? formatDate(data.purchase_date) : 'N/A') + '</div></div>';
    html += '<div class="view-detail-row"><div class="view-detail-label">Expiry Date</div><div class="view-detail-value">' + (data.expiry_date ? formatDate(data.expiry_date) : 'N/A') + '</div></div>';
    html += '<div class="view-detail-row"><div class="view-detail-label">Cost</div><div class="view-detail-value">' + (data.cost ? parseFloat(data.cost).toFixed(2) + ' BDT' : '0.00 BDT') + '</div></div>';
    html += '<div class="view-detail-row"><div class="view-detail-label">Seats</div><div class="view-detail-value">' + (data.seats || 0) + ' (Used: ' + (data.assigned_count || 0) + ')</div></div>';
    
    // Status
    var statusClass = data.status || 'active';
    var statusLabel = ucfirst(statusClass.replace('_', ' '));
    html += '<div class="view-detail-row"><div class="view-detail-label">Status</div><div class="view-detail-value"><span class="status-badge status-' + statusClass + '">' + statusLabel + '</span></div></div>';
    
    // Vendor
    var vendorName = data.vendor_company || data.vendor_name || '';
    html += '<div class="view-detail-row"><div class="view-detail-label">Vendor</div><div class="view-detail-value">' + escapeHtml(vendorName || 'N/A') + '</div></div>';
    
    // Item
    html += '<div class="view-detail-row"><div class="view-detail-label">Item</div><div class="view-detail-value">' + escapeHtml(data.item_name || 'N/A') + '</div></div>';
    
    // Category
    html += '<div class="view-detail-row"><div class="view-detail-label">Category</div><div class="view-detail-value">' + escapeHtml(data.category_name || 'N/A') + '</div></div>';
    
    // Sub Category
    html += '<div class="view-detail-row"><div class="view-detail-label">Sub Category</div><div class="view-detail-value">' + escapeHtml(data.sub_category_name || 'N/A') + '</div></div>';
    
    // Invoice Number
    html += '<div class="view-detail-row"><div class="view-detail-label">Invoice Number</div><div class="view-detail-value">' + escapeHtml(data.invoice_number || 'N/A') + '</div></div>';
    
    // PO Number
    html += '<div class="view-detail-row"><div class="view-detail-label">PO Number</div><div class="view-detail-value">' + escapeHtml(data.po_number || 'N/A') + '</div></div>';
    
    // Support Contact
    html += '<div class="view-detail-row"><div class="view-detail-label">Support Contact</div><div class="view-detail-value">' + escapeHtml(data.support_contact || 'N/A') + '</div></div>';
    
    // Support Email
    html += '<div class="view-detail-row"><div class="view-detail-label">Support Email</div><div class="view-detail-value">' + escapeHtml(data.support_email || 'N/A') + '</div></div>';
    
    // Notes
    html += '<div class="view-detail-row"><div class="view-detail-label">Notes</div><div class="view-detail-value">' + escapeHtml(data.notes || 'N/A') + '</div></div>';
    
    modalBody.innerHTML = html;
    $('#viewModal').modal('show');
}

function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    var d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return ('0' + d.getDate()).slice(-2) + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + d.getFullYear();
}

// ============================================
// EDIT LICENSE FUNCTION - Uses pre-loaded data
// ============================================
function editLicense(id) {
    var data = licenseData[id];
    if (!data) {
        Swal.fire({
            title: 'Error',
            text: 'License data not found. Please refresh and try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    document.getElementById('modalTitle').textContent = 'Edit License';
    document.getElementById('formAction').value = 'edit_license';
    document.getElementById('licenseId').value = id;
    document.getElementById('edit_software_name').value = data.software_name || '';
    document.getElementById('edit_license_key').value = data.license_key || '';
    document.getElementById('edit_version').value = data.version || '';
    document.getElementById('edit_license_type').value = data.license_type || 'perpetual';
    document.getElementById('edit_seats').value = data.seats || 1;
    document.getElementById('edit_cost').value = data.cost || '';
    document.getElementById('edit_purchase_date').value = data.purchase_date || '';
    document.getElementById('edit_expiry_date').value = data.expiry_date || '';
    document.getElementById('edit_vendor_id').value = data.vendor_id || '';
    document.getElementById('edit_vendor_name').value = data.vendor_name || '';
    document.getElementById('edit_item_id').value = data.item_id || '';
    document.getElementById('edit_category_id').value = data.category_id || '';
    document.getElementById('edit_invoice_number').value = data.invoice_number || '';
    document.getElementById('edit_po_number').value = data.po_number || '';
    document.getElementById('edit_support_contact').value = data.support_contact || '';
    document.getElementById('edit_support_email').value = data.support_email || '';
    document.getElementById('edit_notes').value = data.notes || '';
    
    $('#licenseModal').modal('show');
}

// ============================================
// ASSIGN LICENSE FUNCTION
// ============================================
function showAssignModal(id, name) {
    clearEmployeeSelection();
    document.getElementById('assign_license_id').value = id;
    document.getElementById('assign_license_name').textContent = name || '--';
    document.getElementById('assign_license_details').textContent = 'Loading seats...';
    
    // Check from pre-loaded data
    var data = licenseData[id];
    if (data) {
        var seats = data.seats || 0;
        var used = data.assigned_count || 0;
        document.getElementById('assign_license_details').textContent = 'Seats: ' + seats + ' | Used: ' + used + ' | Available: ' + (seats - used);
    } else {
        document.getElementById('assign_license_details').textContent = 'Seats: -- | Used: --';
    }
    
    document.getElementById('assignForm').reset();
    var dateInput = document.querySelector('#assignForm input[name="assigned_date"]');
    if (dateInput) {
        dateInput.value = '<?php echo date('Y-m-d'); ?>';
    }
    
    document.getElementById('assignSubmitBtn').disabled = true;
    $('#assignModal').modal('show');
    
    setTimeout(function() {
        var input = document.getElementById('employeeSearchInput');
        if (input) {
            input.focus();
            input.select();
        }
    }, 400);
}

// ============================================
// ACTION FUNCTIONS
// ============================================

function revokeAssignment(aid, lid) { 
    Swal.fire({ 
        title: 'Revoke Assignment?', 
        text: "This will free up a seat.", 
        icon: 'warning', 
        showCancelButton: true, 
        confirmButtonColor: '#d33', 
        confirmButtonText: 'Yes, revoke!' 
    }).then(function(r) { 
        if(r.isConfirmed) { 
            var form = document.createElement('form');
            form.method = 'POST';
            var input1 = document.createElement('input');
            input1.type = 'hidden';
            input1.name = 'action';
            input1.value = 'revoke_license';
            form.appendChild(input1);
            var input2 = document.createElement('input');
            input2.type = 'hidden';
            input2.name = 'assignment_id';
            input2.value = aid;
            form.appendChild(input2);
            var input3 = document.createElement('input');
            input3.type = 'hidden';
            input3.name = 'license_id';
            input3.value = lid;
            form.appendChild(input3);
            document.body.appendChild(form);
            form.submit(); 
        } 
    }); 
}

function deactivateItem(id) { 
    Swal.fire({ 
        title: 'Deactivate License?', 
        icon: 'warning', 
        showCancelButton: true, 
        confirmButtonColor: '#d33', 
        confirmButtonText: 'Yes, deactivate!' 
    }).then(function(r) { 
        if(r.isConfirmed) { 
            var form = document.createElement('form');
            form.method = 'POST';
            var input1 = document.createElement('input');
            input1.type = 'hidden';
            input1.name = 'action';
            input1.value = 'deactivate_license';
            form.appendChild(input1);
            var input2 = document.createElement('input');
            input2.type = 'hidden';
            input2.name = 'id';
            input2.value = id;
            form.appendChild(input2);
            document.body.appendChild(form);
            form.submit(); 
        } 
    }); 
}

function activateItem(id) { 
    Swal.fire({ 
        title: 'Activate License?', 
        icon: 'question', 
        showCancelButton: true, 
        confirmButtonColor: '#3085d6', 
        confirmButtonText: 'Yes, activate!' 
    }).then(function(r) { 
        if(r.isConfirmed) { 
            var form = document.createElement('form');
            form.method = 'POST';
            var input1 = document.createElement('input');
            input1.type = 'hidden';
            input1.name = 'action';
            input1.value = 'activate_license';
            form.appendChild(input1);
            var input2 = document.createElement('input');
            input2.type = 'hidden';
            input2.name = 'id';
            input2.value = id;
            form.appendChild(input2);
            document.body.appendChild(form);
            form.submit(); 
        } 
    }); 
}

// ============================================
// INITIALIZATION
// ============================================
$(document).ready(function() {
    console.log('Document ready - initializing...');
    
    $('.section-tab').click(function() { 
        $('.section-tab').removeClass('active'); 
        $(this).addClass('active'); 
        $('.section-content').hide(); 
        $('#' + $(this).data('section') + 'Section').show(); 
    });
    
    // Close success modal when clicking outside
    $(document).on('click', function(e) {
        var modal = document.getElementById('assignmentSuccessModal');
        if (modal && e.target === modal) {
            // Don't close on overlay click - user must click buttons
        }
        var modal2 = document.getElementById('successModal');
        if (modal2 && e.target === modal2) {
            modal2.style.display = 'none';
        }
    });
    
    // Auto-close modal with Escape key (only for add license modal)
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            var modal2 = document.getElementById('successModal');
            if (modal2) {
                modal2.style.display = 'none';
            }
        }
    });
    
    // Employee Search
    $(document).on('input', '#employeeSearchInput', function() {
        clearTimeout(employeeSearchTimeout);
        var query = this.value.trim();
        
        if(query.length < 2) {
            var container = document.getElementById('employeeSearchResults');
            if (container) container.style.display = 'none';
            return;
        }
        
        employeeSearchTimeout = setTimeout(function() {
            var results = searchEmployees(query);
            renderEmployeeResults(results);
        }, 300);
    });
    
    $(document).on('keydown', '#employeeSearchInput', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var container = document.getElementById('employeeSearchResults');
            if (container && container.style.display !== 'none') {
                var firstResult = container.querySelector('.employee-search-item:first-child');
                if (firstResult) firstResult.click();
            }
        }
        if (e.key === 'Escape') {
            var container = document.getElementById('employeeSearchResults');
            if (container) container.style.display = 'none';
            this.blur();
        }
    });
    
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#employeeSearchInput, #employeeSearchResults, .employee-search-container').length) {
            var container = document.getElementById('employeeSearchResults');
            if (container) container.style.display = 'none';
        }
    });
    
    $('#assignModal').on('shown.bs.modal', function() {
        setTimeout(function() {
            var input = document.getElementById('employeeSearchInput');
            if (input) {
                input.focus();
                input.select();
            }
        }, 400);
    });
    
    $('#assignModal').on('hidden.bs.modal', function() {
        var container = document.getElementById('employeeSearchResults');
        if (container) container.style.display = 'none';
        clearEmployeeSelection();
    });
    
    $('#assignForm').on('submit', function(e) {
        var employeeId = document.getElementById('selectedEmployeeId').value;
        if (!employeeId) {
            e.preventDefault();
            Swal.fire({
                title: 'Employee Required',
                text: 'Please search and select an employee to assign this license to.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return false;
        }
        var submitBtn = document.getElementById('assignSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Assigning...';
        }
        return true;
    });
    
    // When assignment success modal is shown, ensure it's visible
    if (document.getElementById('assignmentSuccessModal') && document.getElementById('assignmentSuccessModal').classList.contains('show')) {
        // Modal is already showing
    }
});

console.log('Employee search initialized with ' + employees.length + ' employees');
console.log('License data loaded: ' + Object.keys(licenseData).length + ' licenses');
</script>

<?php include '../../includes/footer.php'; ?>