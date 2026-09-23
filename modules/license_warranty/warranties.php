<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// ============================================
// FIX: Check and add missing columns if needed
// ============================================
try {
    // Check if assigned_to column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'assigned_to'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN assigned_to INT(11) DEFAULT NULL AFTER status");
    }
    
    // Check if assigned_date column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'assigned_date'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN assigned_date DATE DEFAULT NULL AFTER assigned_to");
    }
    
    // Check if assignment_notes column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'assignment_notes'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN assignment_notes TEXT DEFAULT NULL AFTER assigned_date");
    }
    
    // Check if assigned_by column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'assigned_by'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN assigned_by INT(11) DEFAULT NULL AFTER assignment_notes");
    }
    
    // Check if provider_phone column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'provider_phone'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN provider_phone VARCHAR(50) DEFAULT NULL AFTER warranty_provider");
    }
    
    // Check if provider_email column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'provider_email'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN provider_email VARCHAR(100) DEFAULT NULL AFTER provider_phone");
    }
    
    // Check if claim_phone column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'claim_phone'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN claim_phone VARCHAR(50) DEFAULT NULL AFTER coverage_details");
    }
    
    // Check if claim_email column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'claim_email'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN claim_email VARCHAR(100) DEFAULT NULL AFTER claim_phone");
    }
    
    // Check if claim_website column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'claim_website'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN claim_website VARCHAR(255) DEFAULT NULL AFTER claim_email");
    }
    
    // Check if warranty_start_date column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'warranty_start_date'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN warranty_start_date DATE DEFAULT NULL AFTER warranty_type");
    }
    
    // Check if documentation_file column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'documentation_file'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN documentation_file VARCHAR(255) DEFAULT NULL AFTER notes");
    }
    
    // Check if vendor_id column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'vendor_id'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN vendor_id INT(11) DEFAULT NULL AFTER category_id");
    }
    
    // Check if invoice_number column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'invoice_number'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN invoice_number VARCHAR(100) DEFAULT NULL AFTER vendor_id");
    }
    
    // Check if coverage_details column exists
    $check = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'coverage_details'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE warranties ADD COLUMN coverage_details TEXT DEFAULT NULL AFTER invoice_number");
    }
    
} catch(PDOException $e) {
    error_log("Error checking/adding columns: " . $e->getMessage());
}

// Get all data for dropdowns
$items = [];
$categories = [];
$sub_categories = [];
$vendors = [];
$employees = [];

try {
    $items = $pdo->query("SELECT id, name, item_code FROM items WHERE is_active = 1 ORDER BY name")->fetchAll();
} catch(PDOException $e) { error_log("Items error: " . $e->getMessage()); }

try {
    $categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
} catch(PDOException $e) { error_log("Categories error: " . $e->getMessage()); }

try {
    $vendors = $pdo->query("SELECT id, vendor_name FROM vendors WHERE is_active = 1 ORDER BY vendor_name")->fetchAll();
} catch(PDOException $e) { error_log("Vendors error: " . $e->getMessage()); }

try {
    $employees = $pdo->query("SELECT id, pf_no, full_name, designation, department FROM employees WHERE is_active = 1 ORDER BY full_name")->fetchAll();
} catch(PDOException $e) { error_log("Employees error: " . $e->getMessage()); }

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_warranty': addOrUpdateWarranty($pdo, 'add'); break;
            case 'edit_warranty': addOrUpdateWarranty($pdo, 'edit'); break;
            case 'deactivate_warranty': deactivateWarranty($pdo); break;
            case 'activate_warranty': activateWarranty($pdo); break;
            case 'add_claim': addClaim($pdo); break;
            case 'assign_warranty': assignWarranty($pdo); break;
        }
    }
}

function addOrUpdateWarranty($pdo, $mode) {
    $id = $mode == 'edit' ? intval($_POST['id']) : null;
    $item_name = trim($_POST['item_name']);
    $serial_number = trim($_POST['serial_number']);
    $warranty_type = $_POST['warranty_type'];
    $warranty_start_date = $_POST['warranty_start_date'];
    $warranty_end_date = $_POST['warranty_end_date'];
    $warranty_provider = trim($_POST['warranty_provider']);
    $provider_phone = trim($_POST['provider_phone']);
    $provider_email = trim($_POST['provider_email']);
    $item_id = !empty($_POST['item_id']) ? intval($_POST['item_id']) : null;
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $vendor_id = !empty($_POST['vendor_id']) ? intval($_POST['vendor_id']) : null;
    $invoice_number = trim($_POST['invoice_number']);
    $coverage_details = trim($_POST['coverage_details']);
    $claim_phone = trim($_POST['claim_phone']);
    $claim_email = trim($_POST['claim_email']);
    $claim_website = trim($_POST['claim_website']);
    $notes = trim($_POST['notes']);
    $user_id = $_SESSION['user_id'];
    
    $today = date('Y-m-d');
    $status = 'active';
    if($warranty_end_date < $today) {
        $status = 'expired';
    } elseif($warranty_end_date <= date('Y-m-d', strtotime('+30 days'))) {
        $status = 'expiring_soon';
    }
    
    // Handle file upload
    $documentation_file = null;
    if(isset($_FILES['documentation_file']) && $_FILES['documentation_file']['error'] == 0) {
        $upload_dir = '../../uploads/warranties/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['documentation_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'png'];
        if(in_array($ext, $allowed)) {
            $new_filename = 'WAR_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if(move_uploaded_file($_FILES['documentation_file']['tmp_name'], $upload_dir . $new_filename)) {
                $documentation_file = 'uploads/warranties/' . $new_filename;
            }
        }
    }
    
    // Get existing columns
    $col_check = $pdo->query("SHOW COLUMNS FROM warranties");
    $existing_columns = $col_check->fetchAll(PDO::FETCH_COLUMN);
    
    if($mode == 'add') {
        // Build dynamic INSERT
        $field_map = [
            'item_name' => $item_name,
            'serial_number' => $serial_number,
            'warranty_type' => $warranty_type,
            'warranty_start_date' => $warranty_start_date,
            'warranty_end_date' => $warranty_end_date,
            'warranty_provider' => $warranty_provider,
            'provider_phone' => $provider_phone,
            'provider_email' => $provider_email,
            'item_id' => $item_id,
            'category_id' => $category_id,
            'vendor_id' => $vendor_id,
            'invoice_number' => $invoice_number,
            'coverage_details' => $coverage_details,
            'claim_phone' => $claim_phone,
            'claim_email' => $claim_email,
            'claim_website' => $claim_website,
            'status' => $status,
            'notes' => $notes,
            'documentation_file' => $documentation_file,
            'created_by' => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
            'is_active' => 1
        ];
        
        $columns = [];
        $values = [];
        $placeholders = [];
        
        foreach ($field_map as $col => $val) {
            if (in_array($col, $existing_columns)) {
                $columns[] = $col;
                $placeholders[] = '?';
                $values[] = $val;
            }
        }
        
        if (empty($columns)) {
            $_SESSION['error_message'] = "No valid columns found for insertion";
            header("Location: warranties.php");
            exit();
        }
        
        $sql = "INSERT INTO warranties (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $warranty_id = $pdo->lastInsertId();
        
        $_SESSION['success_message'] = "Warranty added successfully!";
        $_SESSION['warranty_success'] = ['id' => $warranty_id, 'name' => $item_name];
    } else {
        // Build dynamic UPDATE
        $set_parts = [];
        $values = [];
        
        $field_map = [
            'item_name' => $item_name,
            'serial_number' => $serial_number,
            'warranty_type' => $warranty_type,
            'warranty_start_date' => $warranty_start_date,
            'warranty_end_date' => $warranty_end_date,
            'warranty_provider' => $warranty_provider,
            'provider_phone' => $provider_phone,
            'provider_email' => $provider_email,
            'item_id' => $item_id,
            'category_id' => $category_id,
            'vendor_id' => $vendor_id,
            'invoice_number' => $invoice_number,
            'coverage_details' => $coverage_details,
            'claim_phone' => $claim_phone,
            'claim_email' => $claim_email,
            'claim_website' => $claim_website,
            'status' => $status,
            'notes' => $notes,
            'updated_by' => $user_id,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Only add documentation_file if a new file was uploaded
        if ($documentation_file) {
            $field_map['documentation_file'] = $documentation_file;
        }
        
        foreach ($field_map as $col => $val) {
            if (in_array($col, $existing_columns)) {
                $set_parts[] = "$col = ?";
                $values[] = $val;
            }
        }
        
        if (empty($set_parts)) {
            $_SESSION['error_message'] = "No valid columns found for update";
            header("Location: warranties.php");
            exit();
        }
        
        $values[] = $id;
        $sql = "UPDATE warranties SET " . implode(', ', $set_parts) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        $_SESSION['success_message'] = "Warranty updated successfully!";
    }
    header("Location: warranties.php");
    exit();
}

function deactivateWarranty($pdo) { 
    $id = intval($_POST['id']); 
    $stmt = $pdo->prepare("UPDATE warranties SET is_active = 0 WHERE id = ?"); 
    $stmt->execute([$id]); 
    $_SESSION['success_message'] = "Warranty deactivated!"; 
    header("Location: warranties.php"); 
    exit(); 
}

function activateWarranty($pdo) { 
    $id = intval($_POST['id']); 
    $stmt = $pdo->prepare("UPDATE warranties SET is_active = 1 WHERE id = ?"); 
    $stmt->execute([$id]); 
    $_SESSION['success_message'] = "Warranty activated!"; 
    header("Location: warranties.php"); 
    exit(); 
}

function addClaim($pdo) { 
    $warranty_id = intval($_POST['warranty_id']);
    $claim_date = $_POST['claim_date'];
    $issue_description = trim($_POST['issue_description']);
    $user_id = $_SESSION['user_id'];
    
    // Check if warranty_claims table exists, if not create it
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS warranty_claims (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            warranty_id INT(11) NOT NULL,
            claim_date DATE NOT NULL,
            issue_description TEXT NOT NULL,
            claim_status ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
            resolution_notes TEXT DEFAULT NULL,
            resolved_date DATE DEFAULT NULL,
            created_by INT(11) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
        )");
    } catch(PDOException $e) {
        error_log("Error creating warranty_claims table: " . $e->getMessage());
    }
    
    $stmt = $pdo->prepare("INSERT INTO warranty_claims (warranty_id, claim_date, issue_description, claim_status, created_by, created_at) VALUES (?, ?, ?, 'pending', ?, NOW())"); 
    $stmt->execute([$warranty_id, $claim_date, $issue_description, $user_id]); 
    $_SESSION['success_message'] = "Claim submitted successfully!"; 
    header("Location: warranties.php"); 
    exit(); 
}

function assignWarranty($pdo) {
    $warranty_id = intval($_POST['warranty_id']);
    $employee_id = intval($_POST['employee_id']);
    $assigned_date = $_POST['assigned_date'];
    $notes = trim($_POST['assignment_notes']);
    $user_id = $_SESSION['user_id'];
    
    try {
        // Check if warranties table has assigned_to column
        $columns = $pdo->query("SHOW COLUMNS FROM warranties")->fetchAll(PDO::FETCH_COLUMN);
        if(in_array('assigned_to', $columns) && in_array('assigned_date', $columns) && in_array('assignment_notes', $columns) && in_array('assigned_by', $columns)) {
            $stmt = $pdo->prepare("UPDATE warranties SET assigned_to = ?, assigned_date = ?, assignment_notes = ?, assigned_by = ? WHERE id = ?");
            $stmt->execute([$employee_id, $assigned_date, $notes, $user_id, $warranty_id]);
            $_SESSION['success_message'] = "Warranty assigned successfully!";
        } else {
            // Add columns if they don't exist
            try {
                if(!in_array('assigned_to', $columns)) {
                    $pdo->exec("ALTER TABLE warranties ADD COLUMN assigned_to INT(11) DEFAULT NULL AFTER status");
                }
                if(!in_array('assigned_date', $columns)) {
                    $pdo->exec("ALTER TABLE warranties ADD COLUMN assigned_date DATE DEFAULT NULL AFTER assigned_to");
                }
                if(!in_array('assignment_notes', $columns)) {
                    $pdo->exec("ALTER TABLE warranties ADD COLUMN assignment_notes TEXT DEFAULT NULL AFTER assigned_date");
                }
                if(!in_array('assigned_by', $columns)) {
                    $pdo->exec("ALTER TABLE warranties ADD COLUMN assigned_by INT(11) DEFAULT NULL AFTER assignment_notes");
                }
                // Now try again
                $stmt = $pdo->prepare("UPDATE warranties SET assigned_to = ?, assigned_date = ?, assignment_notes = ?, assigned_by = ? WHERE id = ?");
                $stmt->execute([$employee_id, $assigned_date, $notes, $user_id, $warranty_id]);
                $_SESSION['success_message'] = "Warranty assigned successfully!";
            } catch(PDOException $e) {
                $_SESSION['error_message'] = "Error updating warranty: " . $e->getMessage();
            }
        }
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Error assigning warranty: " . $e->getMessage();
    }
    header("Location: warranties.php");
    exit();
}

// Get all warranties - using safe column names
$warranties = [];
try {
    // First, check what columns exist
    $columns = $pdo->query("SHOW COLUMNS FROM warranties")->fetchAll(PDO::FETCH_COLUMN);
    
    // Build query based on existing columns
    $select_fields = ['id'];
    $fields_to_check = [
        'item_name', 'serial_number', 'warranty_type', 'warranty_start_date', 
        'warranty_end_date', 'warranty_provider', 'provider_phone', 'provider_email',
        'status', 'is_active', 'created_at', 'assigned_to', 'assigned_date',
        'item_id', 'category_id', 'vendor_id', 'invoice_number', 'coverage_details',
        'claim_phone', 'claim_email', 'claim_website', 'notes', 'documentation_file'
    ];
    
    foreach ($fields_to_check as $field) {
        if (in_array($field, $columns)) {
            $select_fields[] = $field;
        }
    }
    
    $select_str = implode(', ', $select_fields);
    $query = "SELECT $select_str FROM warranties ORDER BY warranty_end_date ASC";
    $warranties = $pdo->query($query)->fetchAll();
    
    // Fetch employee names for assigned warranties
    foreach($warranties as &$w) {
        if(isset($w['assigned_to']) && $w['assigned_to']) {
            $stmt = $pdo->prepare("SELECT full_name, pf_no, designation FROM employees WHERE id = ?");
            $stmt->execute([$w['assigned_to']]);
            $emp = $stmt->fetch();
            $w['assigned_to_name'] = $emp ? $emp['full_name'] . ' (' . ($emp['designation'] ?? '') . ')' : 'Unknown';
            $w['assigned_to_pf'] = $emp ? $emp['pf_no'] : '';
        } else {
            $w['assigned_to_name'] = 'Not Assigned';
            $w['assigned_to_pf'] = '';
        }
    }
    
} catch(PDOException $e) {
    error_log("Error loading warranties: " . $e->getMessage());
    $warranties = [];
}

// Get warranty claims
$claims = [];
try {
    // Check if warranty_claims table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'warranty_claims'");
    if ($table_check->rowCount() > 0) {
        // Check columns in warranty_claims
        $claim_columns = $pdo->query("SHOW COLUMNS FROM warranty_claims")->fetchAll(PDO::FETCH_COLUMN);
        
        // Build query dynamically
        $claim_select = ['wc.*'];
        if (in_array('item_name', $columns)) {
            $claim_select[] = 'w.item_name';
        }
        $claim_select_str = implode(', ', $claim_select);
        
        $claims = $pdo->query("
            SELECT $claim_select_str
            FROM warranty_claims wc 
            JOIN warranties w ON wc.warranty_id = w.id 
            ORDER BY wc.created_at DESC
        ")->fetchAll();
    }
} catch(PDOException $e) {
    error_log("Error loading claims: " . $e->getMessage());
    $claims = [];
}

// Check for success/error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

$show_success_modal = isset($_SESSION['warranty_success']) && !empty($_SESSION['warranty_success']);
$success_data = [];
if ($show_success_modal) {
    $success_data = $_SESSION['warranty_success'];
    unset($_SESSION['warranty_success']);
}

// Prepare employee data for JavaScript (same as licenses.php)
$employee_data = [];
try {
    $empStmt = $pdo->query("SELECT id, pf_no, full_name, designation, department FROM employees WHERE is_active = 1 ORDER BY full_name");
    $employee_data = $empStmt->fetchAll();
} catch(PDOException $e) {
    $employee_data = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Load jQuery first, then select2, then bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        *{font-family:'Inter',sans-serif}
        .stats-card{background:white;border-radius:20px;padding:25px;text-align:center;box-shadow:0 5px 20px rgba(0,0,0,0.05)}
        .stats-number{font-size:36px;font-weight:800}
        .stats-label{font-size:13px;color:#6c757d;text-transform:uppercase}
        .status-badge{display:inline-block;padding:5px 14px;border-radius:20px;font-size:11px;font-weight:600}
        .status-active{background:#d1fae5;color:#059669}
        .status-expired{background:#fee2e2;color:#dc2626}
        .status-expiring_soon{background:#fef3c7;color:#d97706}
        .section-tab{display:inline-block;padding:12px 30px;background:white;border-radius:40px;margin:0 8px;cursor:pointer;font-weight:600;border:1px solid #dee2e6}
        .section-tab.active{background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white}
        .btn-gradient-success{background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;border:none}
        .btn-gradient-success:hover{background:linear-gradient(135deg,#059669 0%,#047857 100%);color:white}
        .action-btn-group{display:flex;gap:4px;flex-wrap:wrap}
        .action-btn-group .btn{padding:4px 8px;font-size:12px}
        .select2-container .select2-selection--single{height:38px}
        .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:36px}
        .select2-container--default .select2-selection--single .select2-selection__arrow{height:36px}
        
        /* Success Modal Styles */
        .success-modal-overlay {
            display: <?php echo $show_success_modal ? 'flex' : 'none'; ?>;
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
        .success-modal-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .success-modal-actions .btn { padding: 10px 30px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-success-modal { background: #059669; color: white; border: none; }
        .btn-success-modal:hover { background: #047857; color: white; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(5,150,105,0.3); }
        .btn-secondary-modal { background: #e2e8f0; color: #4a5568; border: none; }
        .btn-secondary-modal:hover { background: #cbd5e0; color: #2d3748; }

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
        
        .assigned-badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:10px;font-weight:600}
        .assigned-yes{background:#dbeafe;color:#1d4ed8}
        .assigned-no{background:#f3f4f6;color:#6b7280}
        
        .modal-xl-custom{max-width:900px}
        .form-label.required-field::after { content: " *"; color: #dc2626; font-weight: bold; }

        /* Employee Search Styles - from licenses.php */
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
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2><i class="fas fa-shield-alt text-success me-2"></i> Warranty Management</h2>
                    <p class="text-muted">Manage device warranties and track claims</p>
                </div>
                <div>
                    <a href="add_warranty.php" class="btn btn-gradient-success">
                        <i class="fas fa-plus me-1"></i> Add Warranty
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <?php if($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="stats-card"><i class="fas fa-shield-alt fa-2x text-success mb-2"></i><div class="stats-number"><?php echo count($warranties); ?></div><div class="stats-label">Total Warranties</div></div></div>
        <div class="col-md-3"><div class="stats-card"><i class="fas fa-check-circle fa-2x text-primary mb-2"></i><div class="stats-number"><?php echo count(array_filter($warranties, fn($w)=>($w['status'] ?? 'active') == 'active')); ?></div><div class="stats-label">Active</div></div></div>
        <div class="col-md-3"><div class="stats-card"><i class="fas fa-hourglass-half fa-2x text-warning mb-2"></i><div class="stats-number"><?php echo count(array_filter($warranties, fn($w)=>($w['status'] ?? '') == 'expiring_soon')); ?></div><div class="stats-label">Expiring Soon</div></div></div>
        <div class="col-md-3"><div class="stats-card"><i class="fas fa-file-alt fa-2x text-danger mb-2"></i><div class="stats-number"><?php echo count($claims); ?></div><div class="stats-label">Total Claims</div></div></div>
    </div>

    <!-- Tabs -->
    <div class="text-center mb-4">
        <div class="section-tab active" data-section="warranties"><i class="fas fa-list me-2"></i> All Warranties</div>
        <div class="section-tab" data-section="claims"><i class="fas fa-file-alt me-2"></i> Claims</div>
    </div>

    <!-- Warranties Table -->
    <div id="warrantiesSection" class="section-content">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>Serial Number</th>
                                <th>Type</th>
                                <th>End Date</th>
                                <th>Provider</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th width="280">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($warranties as $w): ?>
                            <tr class="<?php echo (isset($w['is_active']) && $w['is_active'] == 0) ? 'table-secondary' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($w['item_name'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($w['serial_number'] ?? 'N/A'); ?></td>
                                <td><?php echo ucfirst($w['warranty_type'] ?? 'N/A'); ?></td>
                                <td><?php echo isset($w['warranty_end_date']) ? date('d-m-Y', strtotime($w['warranty_end_date'])) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($w['warranty_provider'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="assigned-badge <?php echo (isset($w['assigned_to']) && $w['assigned_to']) ? 'assigned-yes' : 'assigned-no'; ?>">
                                        <?php echo isset($w['assigned_to_name']) ? htmlspecialchars($w['assigned_to_name']) : 'Not Assigned'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $w['status'] ?? 'active'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $w['status'] ?? 'Active')); ?>
                                    </span>
                                    <?php if(isset($w['is_active']) && $w['is_active'] == 0): ?>
                                        <span class="status-badge bg-secondary ms-1 text-white">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <button class="btn btn-sm btn-outline-info" onclick="viewWarranty(<?php echo $w['id']; ?>)"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="editWarranty(<?php echo htmlspecialchars(json_encode($w)); ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="showAssignModal(<?php echo $w['id']; ?>, '<?php echo addslashes($w['item_name'] ?? 'Warranty'); ?>')"><i class="fas fa-user-plus"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="showClaimModal(<?php echo $w['id']; ?>, '<?php echo addslashes($w['item_name'] ?? 'Warranty'); ?>')"><i class="fas fa-file-alt"></i></button>
                                        <?php if(!isset($w['is_active']) || $w['is_active'] == 1): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deactivateItem(<?php echo $w['id']; ?>)"><i class="fas fa-ban"></i></button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-success" onclick="activateItem(<?php echo $w['id']; ?>)"><i class="fas fa-play"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($warranties)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No warranties found. Click "Add Warranty" to get started.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Claims Table -->
    <div id="claimsSection" class="section-content" style="display:none;">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead><tr><th>Device</th><th>Claim Date</th><th>Issue</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach($claims as $c): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($c['item_name'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo isset($c['claim_date']) ? date('d-m-Y', strtotime($c['claim_date'])) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars(substr($c['issue_description'] ?? '', 0, 50)); ?><?php echo (strlen($c['issue_description'] ?? '') > 50) ? '...' : ''; ?></td>
                                <td>
                                    <span class="status-badge <?php echo ($c['claim_status'] ?? 'pending') == 'pending' ? 'bg-warning' : (($c['claim_status'] ?? '') == 'approved' ? 'bg-success' : 'bg-secondary'); ?> text-white">
                                        <?php echo ucfirst($c['claim_status'] ?? 'Pending'); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($claims)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No warranty claims found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Warranty Modal -->
<div class="modal fade" id="warrantyModal" tabindex="-1">
    <div class="modal-dialog modal-xl-custom modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><span id="modalTitle">Add New Warranty</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="warrantyForm">
                    <input type="hidden" name="action" id="formAction" value="add_warranty">
                    <input type="hidden" name="id" id="warrantyId" value="">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label required-field">Item/Device Name</label><input type="text" name="item_name" id="edit_item_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Serial Number</label><input type="text" name="serial_number" id="edit_serial_number" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Warranty Type</label><select name="warranty_type" id="edit_warranty_type" class="form-select"><option value="manufacturer">Manufacturer</option><option value="extended">Extended</option><option value="service">Service Contract</option></select></div>
                        <div class="col-md-4"><label class="form-label required-field">Start Date</label><input type="date" name="warranty_start_date" id="edit_warranty_start_date" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label required-field">End Date</label><input type="date" name="warranty_end_date" id="edit_warranty_end_date" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label required-field">Warranty Provider</label><input type="text" name="warranty_provider" id="edit_warranty_provider" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Provider Phone</label><input type="text" name="provider_phone" id="edit_provider_phone" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Provider Email</label><input type="email" name="provider_email" id="edit_provider_email" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Select Item</label><select name="item_id" id="edit_item_id" class="form-select select2-item"><option value="">Select Item</option><?php foreach($items as $item): ?><option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['item_code'] . ' - ' . $item['name']); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label">Select Vendor</label><select name="vendor_id" id="edit_vendor_id" class="form-select select2-vendor"><option value="">Select Vendor</option><?php foreach($vendors as $vendor): ?><option value="<?php echo $vendor['id']; ?>"><?php echo htmlspecialchars($vendor['vendor_name']); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label">Select Category</label><select name="category_id" id="edit_category_id" class="form-select select2-category"><option value="">Select Category</option><?php foreach($categories as $cat): ?><option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label">Invoice Number</label><input type="text" name="invoice_number" id="edit_invoice_number" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Coverage Details</label><textarea name="coverage_details" id="edit_coverage_details" rows="2" class="form-control"></textarea></div>
                        <div class="col-md-4"><label class="form-label">Claim Phone</label><input type="text" name="claim_phone" id="edit_claim_phone" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Claim Email</label><input type="email" name="claim_email" id="edit_claim_email" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Claim Website</label><input type="text" name="claim_website" id="edit_claim_website" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" id="edit_notes" rows="2" class="form-control"></textarea></div>
                        <div class="col-12"><label class="form-label">Documentation File</label><input type="file" name="documentation_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.png"><small class="text-muted">Upload warranty certificate or documentation (Max 5MB)</small></div>
                    </div>
                    <div class="text-end mt-4"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success ms-2">Save Warranty</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Warranty Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Warranty Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewDetails"></div>
        </div>
    </div>
</div>

<!-- Claim Modal -->
<div class="modal fade" id="claimModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Submit Warranty Claim</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_claim">
                    <input type="hidden" name="warranty_id" id="claim_warranty_id">
                    <div class="mb-3"><label class="form-label">Device</label><input type="text" id="claim_device_name" class="form-control" readonly></div>
                    <div class="mb-3"><label class="form-label">Claim Date</label><input type="date" name="claim_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Issue Description</label><textarea name="issue_description" rows="4" class="form-control" required></textarea></div>
                    <div class="text-end"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger ms-2">Submit Claim</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Assign Modal with Employee Search (like licenses.php) -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Assign Warranty</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="assignForm">
                    <input type="hidden" name="action" value="assign_warranty">
                    <input type="hidden" name="warranty_id" id="assign_warranty_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Device</label>
                        <div class="form-control bg-light" style="border-radius:10px; padding:12px 14px;">
                            <strong id="assign_device_name">-- Select Warranty --</strong>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assign To Employee <span class="text-danger">*</span></label>
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
                        <input type="hidden" name="employee_id" id="selectedEmployeeId" value="">
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
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="assignment_notes" rows="3" class="form-control" placeholder="Additional notes about this assignment"></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary ms-2" id="assignSubmitBtn">
                            <i class="fas fa-user-check me-1"></i> Assign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<?php if($show_success_modal && !empty($success_data)): ?>
<div class="success-modal-overlay show" id="successModal">
    <div class="success-modal-box">
        <div class="success-modal-icon"><i class="fas fa-check-circle"></i></div>
        <div class="success-modal-title">Warranty Added Successfully!</div>
        <div class="success-modal-subtitle">The warranty has been added to the system.</div>
        <div class="success-modal-details">
            <div class="detail-item"><span class="label">Device</span><span class="value"><?php echo htmlspecialchars($success_data['name'] ?? 'N/A'); ?></span></div>
            <div class="detail-item"><span class="label">ID</span><span class="value">#<?php echo htmlspecialchars($success_data['id'] ?? 'N/A'); ?></span></div>
        </div>
        <div class="success-modal-actions">
            <a href="add_warranty.php" class="btn btn-success-modal"><i class="fas fa-plus me-2"></i> More Add</a>
            <a href="warranties.php" class="btn btn-secondary-modal"><i class="fas fa-list me-2"></i> Warranty List</a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// ============================================
// EMPLOYEE DATA - Populated from PHP (same as licenses.php)
// ============================================
var employees = <?php echo json_encode($employee_data); ?>;

console.log('Employees loaded:', employees.length);

var selectedEmployee = null;
var employeeSearchTimeout = null;

// ============================================
// EMPLOYEE SEARCH FUNCTIONS (from licenses.php)
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
// INITIALIZATION - Document Ready
// ============================================
$(document).ready(function() {
    console.log('Document ready - initializing...');
    
    // Initialize Select2 for all dropdowns
    if ($.fn.select2) {
        $('.select2-item, .select2-vendor, .select2-category').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#warrantyModal'),
            width: '100%',
            placeholder: 'Search...',
            allowClear: true
        });
    } else {
        console.warn('Select2 not loaded');
    }
    
    // Tab switching
    $('.section-tab').click(function(){ 
        $('.section-tab').removeClass('active'); 
        $(this).addClass('active'); 
        $('.section-content').hide(); 
        $('#' + $(this).data('section') + 'Section').show(); 
    });
    
    // Auto-fill item name when item is selected in edit modal
    $('#edit_item_id').on('change', function() {
        var selected = $(this).find(':selected');
        var name = selected.text();
        if(name && name.includes(' - ')) {
            var parts = name.split(' - ');
            if(parts.length > 1) {
                $('#edit_item_name').val(parts.slice(1).join(' - '));
            }
        }
    });
    
    // Close success modal when clicking outside
    $(document).on('click', function(e) {
        var modal = document.getElementById('successModal');
        if (modal && e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // ============================================
    // EMPLOYEE SEARCH - Input event (keyup)
    // ============================================
    $(document).on('input', '#employeeSearchInput', function() {
        clearTimeout(employeeSearchTimeout);
        var query = this.value.trim();
        var container = document.getElementById('employeeSearchResults');
        
        if(query.length < 2) {
            if (container) container.style.display = 'none';
            return;
        }
        
        employeeSearchTimeout = setTimeout(function() {
            var results = searchEmployees(query);
            renderEmployeeResults(results);
        }, 300);
    });
    
    // Keyboard events for employee search
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
    
    // Close search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#employeeSearchInput, #employeeSearchResults, .employee-search-container').length) {
            var container = document.getElementById('employeeSearchResults');
            if (container) container.style.display = 'none';
        }
    });
    
    // Focus on search input when modal opens
    $('#assignModal').on('shown.bs.modal', function() {
        setTimeout(function() {
            var input = document.getElementById('employeeSearchInput');
            if (input) {
                input.focus();
                input.select();
            }
        }, 400);
    });
    
    // Clear search when modal closes
    $('#assignModal').on('hidden.bs.modal', function() {
        var container = document.getElementById('employeeSearchResults');
        if (container) container.style.display = 'none';
        clearEmployeeSelection();
    });
    
    // Form submission validation for assignment
    $('#assignForm').on('submit', function(e) {
        var employeeId = document.getElementById('selectedEmployeeId').value;
        if (!employeeId) {
            e.preventDefault();
            Swal.fire({
                title: 'Employee Required',
                text: 'Please search and select an employee to assign this warranty to.',
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
});

// ============================================
// MODAL FUNCTIONS
// ============================================

function editWarranty(w){ 
    $('#modalTitle').text('Edit Warranty'); 
    $('#formAction').val('edit_warranty'); 
    $('#warrantyId').val(w.id); 
    
    // Populate all fields
    $('#edit_item_name').val(w.item_name || ''); 
    $('#edit_serial_number').val(w.serial_number || ''); 
    $('#edit_warranty_type').val(w.warranty_type || 'manufacturer'); 
    $('#edit_warranty_start_date').val(w.warranty_start_date || ''); 
    $('#edit_warranty_end_date').val(w.warranty_end_date || ''); 
    $('#edit_warranty_provider').val(w.warranty_provider || ''); 
    $('#edit_provider_phone').val(w.provider_phone || ''); 
    $('#edit_provider_email').val(w.provider_email || ''); 
    
    // Select2 dropdowns - need to trigger change
    $('#edit_item_id').val(w.item_id || '').trigger('change'); 
    $('#edit_vendor_id').val(w.vendor_id || '').trigger('change'); 
    $('#edit_category_id').val(w.category_id || '').trigger('change'); 
    
    $('#edit_invoice_number').val(w.invoice_number || ''); 
    $('#edit_coverage_details').val(w.coverage_details || ''); 
    $('#edit_claim_phone').val(w.claim_phone || ''); 
    $('#edit_claim_email').val(w.claim_email || ''); 
    $('#edit_claim_website').val(w.claim_website || ''); 
    $('#edit_notes').val(w.notes || ''); 
    
    $('#warrantyModal').modal('show'); 
}

function showClaimModal(id, name){ 
    $('#claim_warranty_id').val(id); 
    $('#claim_device_name').val(name); 
    $('#claimModal').modal('show'); 
}

function showAssignModal(id, name){ 
    clearEmployeeSelection();
    $('#assign_warranty_id').val(id); 
    $('#assign_device_name').text(name || '--'); 
    document.getElementById('assignSubmitBtn').disabled = true;
    // Focus on search input after modal opens
    $('#assignModal').on('shown.bs.modal', function() {
        setTimeout(function() {
            var input = document.getElementById('employeeSearchInput');
            if (input) {
                input.focus();
                input.select();
            }
        }, 500);
    });
    $('#assignModal').modal('show'); 
}

function viewWarranty(id){ 
    $('#viewDetails').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</div>'); 
    $('#viewModal').modal('show'); 
    $.ajax({ 
        url: 'ajax/get_warranty_details.php', 
        method: 'POST', 
        data: { id: id }, 
        success: function(r) { 
            $('#viewDetails').html(r); 
        },
        error: function() {
            $('#viewDetails').html('<div class="alert alert-danger">Error loading warranty details.</div>');
        }
    }); 
}

function deactivateItem(id){ 
    Swal.fire({title:'Deactivate Warranty?',text:'This warranty will be marked as inactive.',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33',confirmButtonText:'Yes, deactivate!'}).then((r)=>{if(r.isConfirmed){$('<form>',{method:'POST'}).append($('<input>',{type:'hidden',name:'action',value:'deactivate_warranty'})).append($('<input>',{type:'hidden',name:'id',value:id})).appendTo('body').submit();}}); 
}

function activateItem(id){ 
    Swal.fire({title:'Activate Warranty?',text:'This warranty will be marked as active.',icon:'question',showCancelButton:true,confirmButtonColor:'#3085d6',confirmButtonText:'Yes, activate!'}).then((r)=>{if(r.isConfirmed){$('<form>',{method:'POST'}).append($('<input>',{type:'hidden',name:'action',value:'activate_warranty'})).append($('<input>',{type:'hidden',name:'id',value:id})).appendTo('body').submit();}}); 
}
</script>

<?php include '../../includes/footer.php'; ?>