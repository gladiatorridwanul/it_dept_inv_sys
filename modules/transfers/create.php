<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Get all users for Handled By dropdown
$users = $pdo->query("SELECT id, username, full_name, role FROM users WHERE is_active = 1 ORDER BY full_name")->fetchAll();

// Generate transfer number
function generateTransferNo() {
    return 'DLV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Generate Transfer Tracking Number (Auto-generated)
function generateTrackingNo() {
    return 'TRK-' . date('Ymd') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
}

// ============================================
// FILE UPLOAD FUNCTIONS
// ============================================

/**
 * Create upload directory structure
 */
function createUploadDirectory($base_dir) {
    $year_month = date('Y/m');
    $full_path = $base_dir . $year_month . '/';
    
    if(!is_dir($full_path)) {
        mkdir($full_path, 0777, true);
    }
    
    return [
        'full_path' => $full_path,
        'relative_path' => 'uploads/transfers/' . $year_month . '/',
        'year_month' => $year_month
    ];
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowed_types, $max_size) {
    $errors = [];
    
    // Check for upload errors
    if($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds the upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds the MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        $errors[] = $error_messages[$file['error']] ?? 'Unknown upload error';
        return $errors;
    }
    
    // Check file size
    if($file['size'] > $max_size) {
        $errors[] = 'File size exceeds ' . ($max_size / 1024 / 1024) . 'MB limit';
    }
    
    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, $allowed_types)) {
        $errors[] = 'File type "' . $ext . '" is not allowed. Allowed: ' . implode(', ', $allowed_types);
    }
    
    // Check file mime type (additional security)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    if(isset($allowed_mime_types[$ext]) && $allowed_mime_types[$ext] !== $mime_type) {
        // Some valid files might have different mime types, so only warn
        // Don't block, just log
        error_log("Warning: File " . $file['name'] . " has mime type " . $mime_type . " but extension is " . $ext);
    }
    
    return $errors;
}

/**
 * Upload file to server
 */
function uploadFile($file, $upload_dir, $transfer_id) {
    // Create directory structure
    $dir_info = createUploadDirectory($upload_dir);
    
    // Generate unique filename
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $random = bin2hex(random_bytes(6));
    $new_filename = 'DLV_' . $transfer_id . '_' . date('Ymd_His') . '_' . $random . '.' . $ext;
    
    $full_path = $dir_info['full_path'] . $new_filename;
    $relative_path = $dir_info['relative_path'] . $new_filename;
    
    // Move uploaded file
    if(move_uploaded_file($file['tmp_name'], $full_path)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'original_name' => $file['name'],
            'file_path' => $relative_path,
            'file_size' => $file['size'],
            'full_path' => $full_path
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Failed to move uploaded file'
        ];
    }
}

/**
 * Save attachment record to database
 */
function saveAttachmentRecord($pdo, $transfer_id, $file_info, $user_id) {
    $stmt = $pdo->prepare("INSERT INTO transfer_attachments (
        transfer_id, 
        file_name, 
        original_name, 
        file_path, 
        file_size, 
        uploaded_by
    ) VALUES (?, ?, ?, ?, ?, ?)");
    
    return $stmt->execute([
        $transfer_id,
        $file_info['filename'],
        $file_info['original_name'],
        $file_info['file_path'],
        $file_info['file_size'],
        $user_id
    ]);
}

// ============================================
// MAIN POST HANDLING
// ============================================

// Handle file upload
$upload_dir = '../../uploads/transfers/';
if(!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Ensure the directory is writable
if(!is_writable($upload_dir)) {
    chmod($upload_dir, 0777);
}

$show_success_modal = false;
$success_transfer_no = '';
$success_tracking_no = '';
$success_employee_name = '';
$success_product_name = '';
$success_file_count = 0;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transfer_no = generateTransferNo();
    $transfer_date = $_POST['transfer_date'];
    $from_location = "IT Department";
    $from_department = "Information Technology";
    $from_address = "660 Washpur, PO: Shyamlapur, PS: Hazaribagh, Dhaka 1310";
    
    // Use delivery_location as to_location
    $to_location = $_POST['delivery_location'];
    
    // to_department from form or employee's department
    $to_department = isset($_POST['to_department']) ? $_POST['to_department'] : '';
    
    $to_attn = isset($_POST['to_attn']) ? $_POST['to_attn'] : '';
    $to_address = isset($_POST['to_address']) ? $_POST['to_address'] : '';
    
    $item_id = $_POST['item_id'] ? intval($_POST['item_id']) : null;
    $assignment_id = $_POST['assignment_id'] ? intval($_POST['assignment_id']) : null;
    $employee_id = $_POST['employee_id'] ? intval($_POST['employee_id']) : null;
    $employee_name = isset($_POST['employee_name']) ? $_POST['employee_name'] : '';
    $employee_pf_no = isset($_POST['employee_pf_no']) ? $_POST['employee_pf_no'] : '';
    $employee_designation = isset($_POST['employee_designation']) ? $_POST['employee_designation'] : '';
    $employee_department = isset($_POST['employee_department']) ? $_POST['employee_department'] : '';
    $employee_phone = isset($_POST['employee_phone']) ? $_POST['employee_phone'] : '';
    $employee_email = isset($_POST['employee_email']) ? $_POST['employee_email'] : '';
    
    $product_name = $_POST['product_name'];
    $product_type = isset($_POST['product_type']) ? $_POST['product_type'] : '';
    $quantity = $_POST['quantity'];
    $serial_id = isset($_POST['serial_id']) ? $_POST['serial_id'] : '';
    $serial_number = isset($_POST['serial_number']) ? $_POST['serial_number'] : '';
    $model_number = isset($_POST['model_number']) ? $_POST['model_number'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
    $handled_by = $_POST['handled_by'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $tracking_no = generateTrackingNo();
    
    $pdo->beginTransaction();
    
    try {
        // Insert into device_transfers
        $stmt = $pdo->prepare("INSERT INTO device_transfers (
            transfer_no, transfer_date, 
            from_location, from_department, from_address,
            to_location, to_department, to_attn, to_address,
            item_id, assignment_id, employee_id, employee_name, employee_pf_no, 
            employee_designation, employee_department, employee_phone, employee_email,
            product_name, product_type, quantity, serial_numbers, description, reason, 
            status, delivery_status, handled_by, notes, tracking_no, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, ?)");
        
        $stmt->execute([
            $transfer_no, $transfer_date,
            $from_location, $from_department, $from_address,
            $to_location, $to_department, $to_attn, $to_address,
            $item_id, $assignment_id, $employee_id, $employee_name, $employee_pf_no,
            $employee_designation, $employee_department, $employee_phone, $employee_email,
            $product_name, $product_type, $quantity, $serial_number, $description, $reason,
            $handled_by, $notes, $tracking_no, $_SESSION['user_id']
        ]);
        
        $transfer_id = $pdo->lastInsertId();
        
        // Add to delivery log
        $log_stmt = $pdo->prepare("INSERT INTO delivery_logs (transfer_id, action, status_from, status_to, performed_by) VALUES (?, 'created', NULL, 'pending', ?)");
        $log_stmt->execute([$transfer_id, $_SESSION['user_id']]);
        
        // ============================================
        // PROCESS FILE UPLOADS
        // ============================================
        $uploaded_files = [];
        $upload_errors = [];
        
        if(isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            // Allowed file types
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
            $max_file_size = 5 * 1024 * 1024; // 5MB
            
            $total_files = count($_FILES['attachments']['name']);
            
            for($i = 0; $i < $total_files; $i++) {
                $file = [
                    'name' => $_FILES['attachments']['name'][$i],
                    'type' => $_FILES['attachments']['type'][$i],
                    'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                    'error' => $_FILES['attachments']['error'][$i],
                    'size' => $_FILES['attachments']['size'][$i]
                ];
                
                // Skip if no file (error UPLOAD_ERR_NO_FILE)
                if($file['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                
                // Validate file
                $validation_errors = validateFileUpload($file, $allowed_types, $max_file_size);
                
                if(!empty($validation_errors)) {
                    $upload_errors[] = "File '{$file['name']}': " . implode(', ', $validation_errors);
                    continue;
                }
                
                // Upload file
                $upload_result = uploadFile($file, $upload_dir, $transfer_id);
                
                if($upload_result['success']) {
                    // Save to database
                    if(saveAttachmentRecord($pdo, $transfer_id, $upload_result, $_SESSION['user_id'])) {
                        $uploaded_files[] = $upload_result['original_name'];
                    } else {
                        $upload_errors[] = "File '{$file['name']}': Failed to save to database";
                    }
                } else {
                    $upload_errors[] = "File '{$file['name']}': " . $upload_result['error'];
                }
            }
            
            // Store upload summary in session for success message
            if(!empty($uploaded_files)) {
                $_SESSION['uploaded_files'] = $uploaded_files;
            }
            if(!empty($upload_errors)) {
                $_SESSION['upload_errors'] = $upload_errors;
            }
        }
        
        $pdo->commit();
        
        // Set success variables for modal
        $show_success_modal = true;
        $success_transfer_no = $transfer_no;
        $success_tracking_no = $tracking_no;
        $success_employee_name = $employee_name;
        $success_product_name = $product_name;
        $success_file_count = count($uploaded_files);
        
        // Build success message
        $success_msg = "Delivery request created successfully!<br>Delivery No: " . $transfer_no . "<br>Tracking No: " . $tracking_no;
        
        if(!empty($uploaded_files)) {
            $success_msg .= "<br><br><strong>Files uploaded (" . count($uploaded_files) . "):</strong><br>";
            $success_msg .= implode('<br>', array_map(function($f) { return '📎 ' . htmlspecialchars($f); }, $uploaded_files));
        }
        
        if(!empty($upload_errors)) {
            $success_msg .= "<br><br><strong>Upload warnings:</strong><br>";
            $success_msg .= implode('<br>', array_map(function($e) { return '⚠️ ' . htmlspecialchars($e); }, $upload_errors));
        }
        
        $_SESSION['success'] = $success_msg;
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>

<style>
    .form-section {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
    }
    .form-section-title {
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e2e8f0;
    }
    .required:after { content: " *"; color: #dc2626; }
    .item-search-container, .serial-search-container { position: relative; margin-bottom: 15px; }
    .item-search-results, .serial-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 350px;
        overflow-y: auto;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .item-search-item, .serial-search-item {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s;
        font-size: 13px;
    }
    .item-search-item:hover, .serial-search-item:hover { background: #f8fafc; }
    .item-search-item .source-badge {
        font-size: 9px;
        padding: 1px 8px;
        border-radius: 10px;
        font-weight: 600;
    }
    .source-badge.request { background: #fce7f3; color: #be185d; }
    .source-badge.admin { background: #dbeafe; color: #1e40af; }
    .source-badge.transfer { background: #fef3c7; color: #92400e; }
    .selected-card {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        padding: 15px;
        margin-top: 15px;
    }
    .info-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-top: 15px;
    }
    .info-row { display: flex; margin-bottom: 8px; }
    .info-label { width: 120px; font-weight: 600; color: #475569; }
    .info-value { flex: 1; color: #1e293b; }
    .fixed-info {
        background: #f1f5f9;
        padding: 12px 15px;
        border-radius: 12px;
        margin-bottom: 15px;
    }
    .tracking-number {
        font-family: monospace;
        font-size: 14px;
        font-weight: 600;
        background: #e0f2fe;
        padding: 8px 12px;
        border-radius: 8px;
        display: inline-block;
    }
    .search-hint {
        font-size: 12px;
        color: #64748b;
        margin-top: 5px;
    }
    .search-hint i {
        margin-right: 4px;
    }

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
        max-width: 550px;
        width: 90%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: slideUp 0.4s ease;
        position: relative;
    }
    .success-modal-icon {
        font-size: 72px;
        color: #059669;
        margin-bottom: 15px;
        animation: bounceIn 0.6s ease;
    }
    .success-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1a202c;
        margin-bottom: 8px;
    }
    .success-modal-subtitle {
        font-size: 16px;
        color: #4a5568;
        margin-bottom: 20px;
    }
    .success-modal-details {
        background: #f7fafc;
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 25px;
        text-align: left;
    }
    .success-modal-details .detail-item {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        border-bottom: 1px solid #edf2f7;
        font-size: 14px;
    }
    .success-modal-details .detail-item:last-child {
        border-bottom: none;
    }
    .success-modal-details .label {
        color: #718096;
        font-weight: 500;
    }
    .success-modal-details .value {
        font-weight: 600;
        color: #2d3748;
        font-family: monospace;
    }
    .success-modal-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .success-modal-actions .btn {
        padding: 10px 30px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    .btn-success-modal {
        background: #059669;
        color: white;
        border: none;
    }
    .btn-success-modal:hover {
        background: #047857;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(5,150,105,0.3);
    }
    .btn-secondary-modal {
        background: #e2e8f0;
        color: #4a5568;
        border: none;
    }
    .btn-secondary-modal:hover {
        background: #cbd5e0;
        color: #2d3748;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideUp {
        from { 
            opacity: 0;
            transform: translateY(40px) scale(0.95);
        }
        to { 
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    @keyframes bounceIn {
        0% { 
            opacity: 0;
            transform: scale(0.3); 
        }
        50% { 
            opacity: 1;
            transform: scale(1.1); 
        }
        70% { 
            transform: scale(0.9); 
        }
        100% { 
            transform: scale(1); 
        }
    }
</style>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-truck text-primary me-2"></i>Create Delivery Request</h4>
            <p class="text-muted small mb-0">Send assigned device to employee location via courier</p>
        </div>
        <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
    </div>
    
    <?php if(isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
        <button type="button" class="btn-close p-2" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" id="deliveryForm">
        <div class="row">
            <div class="col-lg-8">
                <!-- Delivery Information -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-info-circle me-2 text-primary"></i>Delivery Information</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold required">Delivery Date</label>
                            <input type="date" name="transfer_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold required">Handled By (IT Staff)</label>
                            <select name="handled_by" class="form-select" required>
                                <option value="">-- Select Person --</option>
                                <?php foreach($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>">
                                    <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?> 
                                    (<?php echo ucfirst($user['role']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Transfer Tracking No</label>
                            <div class="tracking-number"><i class="fas fa-qrcode me-1"></i> <?php echo generateTrackingNo(); ?></div>
                            <input type="hidden" name="tracking_no" value="<?php echo generateTrackingNo(); ?>">
                            <small class="text-muted">Auto-generated tracking number</small>
                        </div>
                    </div>
                </div>
                
                <!-- From Location (Fixed - Read Only) -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-map-marker-alt me-2 text-danger"></i>From (Sender) - Fixed Information</div>
                    <div class="fixed-info">
                        <div class="row">
                            <div class="col-md-6"><small class="text-muted">Location</small><div><strong>IT Department</strong></div></div>
                            <div class="col-md-6"><small class="text-muted">Department</small><div><strong>Information Technology</strong></div></div>
                            <div class="col-12 mt-2"><small class="text-muted">Address</small><div><strong>660 Washpur, PO: Shyamlapur, PS: Hazaribagh, Dhaka 1310</strong></div></div>
                        </div>
                    </div>
                    <input type="hidden" name="from_location" value="IT Department">
                    <input type="hidden" name="from_department" value="Information Technology">
                    <input type="hidden" name="from_address" value="660 Washpur, PO: Shyamlapur, PS: Hazaribagh, Dhaka 1310">
                </div>
                
                <!-- Step 1: Search Assigned Item -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-search me-2 text-warning"></i>Step 1: Select Assigned Device</div>
                    <div class="alert alert-info py-2 px-3 mb-3">
                        <i class="fas fa-info-circle me-2"></i> Search for devices that are currently assigned to employees (from both IT Admin assignments and Public Requests).
                    </div>
                    <div class="item-search-container">
                        <label class="form-label small fw-semibold">Search Assigned Device</label>
                        <input type="text" id="itemSearch" class="form-control" placeholder="Search by Item Code (e.g., ITM-000001), Item Name, or Assignment No...">
                        <div class="search-hint">
                            <i class="fas fa-lightbulb text-warning"></i> 
                            Searches across all assignments - IT Admin, Public Requests, and Device Transfers
                        </div>
                        <div id="itemSearchResults" class="item-search-results"></div>
                    </div>
                    <input type="hidden" name="item_id" id="selectedItemId" value="">
                    <div id="selectedItemDisplay" style="display: none;"></div>
                </div>
                
                <!-- Step 2: Select Serial Number -->
                <div class="form-section" id="serialSection" style="display: none;">
                    <div class="form-section-title"><i class="fas fa-qrcode me-2 text-success"></i>Step 2: Select Serial Number</div>
                    <div class="serial-search-container">
                        <label class="form-label small fw-semibold">Select Serial Number</label>
                        <select id="serialSelect" class="form-select">
                            <option value="">-- Select Serial Number --</option>
                        </select>
                    </div>
                    <input type="hidden" name="serial_id" id="selectedSerialId" value="">
                    <input type="hidden" name="assignment_id" id="selectedAssignmentId" value="">
                    <input type="hidden" name="serial_number" id="selectedSerialNumber" value="">
                    <input type="hidden" name="model_number" id="selectedModelNumber" value="">
                </div>
                
                <!-- Step 3: Employee & Assignment Information -->
                <div class="form-section" id="employeeSection" style="display: none;">
                    <div class="form-section-title"><i class="fas fa-user me-2 text-info"></i>Step 3: Employee & Assignment Details</div>
                    <div id="employeeInfoDisplay"></div>
                    <input type="hidden" name="employee_id" id="employeeId">
                    <input type="hidden" name="employee_name" id="employeeName">
                    <input type="hidden" name="employee_pf_no" id="employeePfNo">
                    <input type="hidden" name="employee_designation" id="employeeDesignation">
                    <input type="hidden" name="employee_department" id="employeeDepartment">
                    <input type="hidden" name="employee_phone" id="employeePhone">
                    <input type="hidden" name="employee_email" id="employeeEmail">
                </div>
                
                <!-- Step 4: Delivery Location -->
                <div class="form-section" id="locationSection" style="display: none;">
                    <div class="form-section-title"><i class="fas fa-map-marker-alt me-2 text-danger"></i>Step 4: Delivery Location</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold required">Delivery Location</label>
                            <input type="text" name="delivery_location" id="deliveryLocation" class="form-control" placeholder="Office address or delivery point" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Recipient Name</label>
                            <input type="text" name="to_attn" id="toAttn" class="form-control" placeholder="Person receiving the device">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Full Delivery Address</label>
                            <textarea name="to_address" id="toAddress" rows="2" class="form-control" placeholder="Complete delivery address"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Product Details -->
                <div class="form-section" id="productSection" style="display: none;">
                    <div class="form-section-title"><i class="fas fa-box me-2 text-warning"></i>Device Details</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold required">Device Name</label>
                            <input type="text" name="product_name" id="productName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Brand/Model</label>
                            <input type="text" name="product_type" id="productType" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold required">Quantity</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description / Specifications</label>
                            <textarea name="description" id="description" rows="2" class="form-control" placeholder="Device specifications"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Reason for Delivery</label>
                            <textarea name="reason" rows="2" class="form-control" placeholder="Reason for sending this device"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-paperclip me-2 text-secondary"></i>Additional Information</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Attachments (Gate pass, Authorization)</label>
                            <input type="file" name="attachments[]" id="attachments" class="form-control" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">
                            <small class="text-muted">Upload supporting documents (Max 5MB each). Allowed: JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX</small>
                            <div id="fileList" class="mt-2"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Internal Notes</label>
                            <textarea name="notes" rows="2" class="form-control" placeholder="Any internal notes"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-info-circle me-2 text-info"></i>Delivery Process</div>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-2">1. Search for the assigned device</li>
                        <li class="mb-2">2. Select the specific serial number</li>
                        <li class="mb-2">3. Verify employee details automatically</li>
                        <li class="mb-2">4. Enter delivery location details</li>
                        <li class="mb-2">5. Submit delivery request</li>
                    </ul>
                </div>
                
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-building me-2 text-secondary"></i>From (IT Department) - Fixed</div>
                    <div class="small">
                        <strong>IT Department</strong><br>
                        Information Technology<br>
                        660 Washpur, PO: Shyamlapur<br>
                        PS: Hazaribagh, Dhaka 1310
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-end mt-3 mb-4">
            <a href="list.php" class="btn btn-secondary btn-sm px-4 me-2">Cancel</a>
            <button type="submit" class="btn btn-primary btn-sm px-4" id="submitBtn">
                <i class="fas fa-save me-2"></i> Create Delivery Request
            </button>
        </div>
    </form>
</div>

<!-- Success Modal -->
<?php if($show_success_modal): ?>
<div class="success-modal-overlay" id="successModal">
    <div class="success-modal-box">
        <div class="success-modal-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="success-modal-title">Delivery Request Created!</div>
        <div class="success-modal-subtitle">
            Your delivery request has been submitted successfully.
        </div>
        <div class="success-modal-details">
            <div class="detail-item">
                <span class="label">Delivery Number</span>
                <span class="value"><?php echo htmlspecialchars($success_transfer_no); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Tracking Number</span>
                <span class="value"><?php echo htmlspecialchars($success_tracking_no); ?></span>
            </div>
            <?php if($success_employee_name): ?>
            <div class="detail-item">
                <span class="label">Employee</span>
                <span class="value"><?php echo htmlspecialchars($success_employee_name); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-item">
                <span class="label">Device</span>
                <span class="value"><?php echo htmlspecialchars($success_product_name); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Status</span>
                <span class="value"><span class="badge bg-warning text-dark">Pending</span></span>
            </div>
            <?php if($success_file_count > 0): ?>
            <div class="detail-item">
                <span class="label">Attachments</span>
                <span class="value"><?php echo $success_file_count; ?> file(s) uploaded</span>
            </div>
            <?php endif; ?>
        </div>
        <div class="success-modal-actions">
            <a href="list.php" class="btn btn-success-modal">
                <i class="fas fa-list me-2"></i> View All Transfers
            </a>
            <a href="create.php" class="btn btn-secondary-modal">
                <i class="fas fa-plus me-2"></i> Create Another
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let searchTimeout;
    
    // File list display
    $('#attachments').on('change', function() {
        const files = $(this)[0].files;
        let html = '';
        if(files.length > 0) {
            html = '<div class="alert alert-info py-1 px-2 small">';
            for(let i = 0; i < files.length; i++) {
                html += '<div><i class="fas fa-file me-1"></i> ' + files[i].name + 
                        ' (' + (files[i].size / 1024).toFixed(1) + ' KB)</div>';
            }
            html += '</div>';
        }
        $('#fileList').html(html);
    });
    
    // Replace the search function with this updated version
$('#itemSearch').on('input', function() {
    clearTimeout(searchTimeout);
    const query = $(this).val();
    
    if(query.length < 1) {
        $('#itemSearchResults').hide();
        return;
    }
    
    $('#itemSearchResults').html('<div class="item-search-item text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Searching...</div>').show();
    
    searchTimeout = setTimeout(() => {
        $.ajax({
            url: 'ajax/get_all_assigned_items.php',
            type: 'GET',
            data: { search: query },
            dataType: 'json',
            timeout: 15000,
            success: function(response) {
                console.log('Search Response:', response); // Debug log
                
                if(response.success && response.items && response.items.length > 0) {
                    let html = '';
                    response.items.forEach(item => {
                        let sourceBadge = '';
                        if(item.source === 'request') {
                            sourceBadge = '<span class="source-badge request">Public Request</span>';
                        } else if(item.source === 'transfer') {
                            sourceBadge = '<span class="source-badge transfer">Device Transfer</span>';
                        } else {
                            sourceBadge = '<span class="source-badge admin">IT Admin</span>';
                        }
                        
                        html += `<div class="item-search-item" onclick="selectItem(${item.id}, 
                                    '${escapeHtml(item.name)}', 
                                    '${escapeHtml(item.brand_name || '')}', 
                                    '${escapeHtml(item.item_code)}', 
                                    '${escapeHtml(item.specification || '')}',
                                    '${escapeHtml(item.source || 'admin')}',
                                    '${escapeHtml(item.assignment_no || '')}',
                                    ${item.assignment_id || 0})">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-laptop text-primary me-2 mt-1"></i>
                                        <div class="w-100">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                                <div>
                                                    <strong><span class="badge bg-secondary me-1">${escapeHtml(item.item_code)}</span> ${escapeHtml(item.name)}</strong>
                                                </div>
                                                ${sourceBadge}
                                            </div>
                                            ${item.assignment_no ? `<div><small class="text-muted">Assignment: <strong>${escapeHtml(item.assignment_no)}</strong></small></div>` : ''}
                                            ${item.employee_name ? `<div><small class="text-success"><i class="fas fa-user-check me-1"></i> Assigned to: ${escapeHtml(item.employee_name)}</small></div>` : ''}
                                        </div>
                                    </div>
                                </div>`;
                    });
                    $('#itemSearchResults').html(html).show();
                } else {
                    const msg = response.message || 'No assigned items found';
                    $('#itemSearchResults').html(`<div class="item-search-item text-muted"><i class="fas fa-info-circle me-1"></i> ${msg}</div>`).show();
                }
            },
            error: function(xhr, status, error) {
                console.error('Search Error:', error);
                console.error('Response Text:', xhr.responseText);
                
                // Try to parse error response
                let errorMsg = 'Error loading items. Please refresh.';
                try {
                    const jsonResponse = JSON.parse(xhr.responseText);
                    if(jsonResponse.message) {
                        errorMsg = jsonResponse.message;
                    }
                } catch(e) {
                    // If not JSON, show the raw response for debugging
                    if(xhr.responseText) {
                        errorMsg = 'Server error: ' + xhr.responseText.substring(0, 100);
                    }
                }
                
                $('#itemSearchResults').html(`<div class="item-search-item text-danger"><i class="fas fa-exclamation-triangle me-1"></i> ${errorMsg}</div>`).show();
            }
        });
    }, 400);
});
    
    $(document).on('click', function(e) {
        if(!$(e.target).closest('#itemSearch, #itemSearchResults').length) {
            $('#itemSearchResults').hide();
        }
    });
});

function selectItem(id, name, brand, code, spec, source, assignmentNo, assignmentId) {
    $('#selectedItemId').val(id);
    $('#productName').val(name);
    $('#productType').val(brand);
    $('#description').val(spec);
    
    // If assignment ID is passed, set it
    if(assignmentId > 0) {
        $('#selectedAssignmentId').val(assignmentId);
    }
    
    const sourceLabel = source === 'request' ? 'Public Request' : 
                        source === 'transfer' ? 'Device Transfer' : 'IT Admin';
    
    const html = `<div class="selected-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <i class="fas fa-check-circle text-success me-2"></i><strong>Selected Device:</strong>
                            <div class="mt-1">
                                <span class="badge bg-primary me-2">${escapeHtml(code)}</span>
                                <strong>${escapeHtml(name)}</strong>
                                <span class="badge bg-secondary ms-2">${escapeHtml(sourceLabel)}</span>
                            </div>
                            ${assignmentNo ? `<div class="mt-1"><small class="text-muted">Assignment: <strong>${escapeHtml(assignmentNo)}</strong></small></div>` : ''}
                            ${brand ? `<div class="mt-1"><small class="text-muted">Brand: ${escapeHtml(brand)}</small></div>` : ''}
                            ${spec ? `<div class="mt-1"><small class="text-muted">Specs: ${escapeHtml(spec.substring(0, 150))}${spec.length > 150 ? '...' : ''}</small></div>` : ''}
                            <div class="mt-2 text-warning small"><i class="fas fa-exclamation-triangle me-1"></i> This item is currently assigned. Transfer requires approval.</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearSelectedItem()"><i class="fas fa-times"></i> Change</button>
                    </div>
                </div>`;
    
    $('#selectedItemDisplay').html(html).show();
    $('#itemSearch').val('');
    $('#itemSearchResults').hide();
    
    // Load serial numbers
    loadSerials(id);
}

function loadSerials(itemId) {
    $('#serialSelect').html('<option value="">Loading serial numbers...</option>');
    $('#serialSection').show();
    $('#serialSection .alert-info, #serialSection .alert-success, #serialSection .alert-warning, #serialSection .alert-danger').remove();
    $('#serialSection').prepend(`<div class="alert alert-info py-2 px-3 mb-3"><i class="fas fa-spinner fa-spin me-2"></i> Loading serial numbers...</div>`);
    
    $.ajax({
        url: 'ajax/get_all_assigned_serials.php',
        type: 'GET',
        data: { item_id: itemId },
        dataType: 'json',
        timeout: 15000,
        success: function(response) {
            $('#serialSection .alert-info').remove();
            
            if(response.success && response.serials && response.serials.length > 0) {
                let html = '<option value="">-- Select Serial Number --</option>';
                let foundSerials = 0;
                
                response.serials.forEach(serial => {
                    let displayText = '';
                    let hasValidData = false;
                    
                    if(serial.serial_number && serial.serial_number !== '') {
                        displayText = serial.serial_number;
                        hasValidData = true;
                    } else if(serial.assignment_no) {
                        displayText = 'Assignment: ' + serial.assignment_no;
                        hasValidData = true;
                    }
                    
                    if(hasValidData) {
                        foundSerials++;
                        
                        if(serial.model_number && serial.model_number !== '') {
                            displayText += ` - Model: ${escapeHtml(serial.model_number)}`;
                        }
                        
                        if(serial.employee_name && serial.employee_name !== '') {
                            displayText += ` (${escapeHtml(serial.employee_name)})`;
                        }
                        
                        // Determine source for display
                        let sourceLabel = serial.source === 'request' ? '📝 Request' : 
                                         serial.source === 'transfer' ? '🚚 Transfer' : '👨‍💻 Admin';
                        
                        let value = serial.serial_id > 0 ? serial.serial_id : 'assign_' + serial.assignment_id;
                        
                        html += `<option value="${value}" 
                                    data-serial-id="${serial.serial_id || 0}"
                                    data-assignment-id="${serial.assignment_id || 0}"
                                    data-employee-id="${serial.employee_id || 0}"
                                    data-employee-name="${escapeHtml(serial.employee_name || '')}"
                                    data-employee-pf="${escapeHtml(serial.pf_no || '')}"
                                    data-employee-designation="${escapeHtml(serial.designation || '')}"
                                    data-employee-department="${escapeHtml(serial.department || '')}"
                                    data-employee-phone="${escapeHtml(serial.phone || '')}"
                                    data-employee-email="${escapeHtml(serial.email || '')}"
                                    data-serial-number="${escapeHtml(serial.serial_number || '')}"
                                    data-model-number="${escapeHtml(serial.model_number || '')}"
                                    data-version="${escapeHtml(serial.version || '')}"
                                    data-assignment-no="${escapeHtml(serial.assignment_no || '')}"
                                    data-assigned-date="${serial.assigned_date || ''}"
                                    data-source="${escapeHtml(serial.source || 'admin')}">
                                ${escapeHtml(displayText)} ${sourceLabel}
                            </option>`;
                    }
                });
                
                if(foundSerials > 0) {
                    $('#serialSelect').html(html);
                    $('#serialSelect').prop('disabled', false);
                    $('#serialSection').prepend(`<div class="alert alert-success py-2 px-3 mb-3"><i class="fas fa-check-circle me-2"></i> Found ${foundSerials} serial number(s). Please select one.</div>`);
                    setTimeout(() => { $('#serialSection .alert-success').fadeOut(3000); }, 4000);
                } else {
                    $('#serialSelect').html('<option value="">No serial numbers found</option>');
                    $('#serialSelect').prop('disabled', true);
                    $('#serialSection').prepend(`<div class="alert alert-warning py-2 px-3 mb-3"><i class="fas fa-exclamation-triangle me-2"></i> No serial numbers found for this item.</div>`);
                }
            } else {
                $('#serialSelect').html('<option value="">No serial numbers found</option>');
                $('#serialSelect').prop('disabled', true);
                $('#serialSection').prepend(`<div class="alert alert-warning py-2 px-3 mb-3"><i class="fas fa-exclamation-triangle me-2"></i> No serial numbers found for this item.</div>`);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#serialSection .alert-info').remove();
            $('#serialSelect').html('<option value="">Error loading serial numbers</option>');
            $('#serialSelect').prop('disabled', true);
            $('#serialSection').prepend(`<div class="alert alert-danger py-2 px-3 mb-3"><i class="fas fa-exclamation-triangle me-2"></i> Error loading serial numbers. Please refresh.</div>`);
        }
    });
}

// Serial selection - Updates Employee & Assignment Info
$(document).ready(function() {
    $('#serialSelect').on('change', function() {
        const selectedOption = $(this).find(':selected');
        const val = selectedOption.val();
        
        if(val && val !== '') {
            // Get all data from the selected option
            const serialId = selectedOption.data('serial-id') || 0;
            const assignmentId = selectedOption.data('assignment-id') || 0;
            const employeeId = selectedOption.data('employee-id') || 0;
            const employeeName = selectedOption.data('employee-name') || '';
            const employeePf = selectedOption.data('employee-pf') || '';
            const employeeDesignation = selectedOption.data('employee-designation') || '';
            const employeeDepartment = selectedOption.data('employee-department') || '';
            const employeePhone = selectedOption.data('employee-phone') || '';
            const employeeEmail = selectedOption.data('employee-email') || '';
            const serialNumber = selectedOption.data('serial-number') || '';
            const modelNumber = selectedOption.data('model-number') || '';
            const assignmentNo = selectedOption.data('assignment-no') || '';
            const assignedDate = selectedOption.data('assigned-date') || '';
            const source = selectedOption.data('source') || 'admin';
            
            // Set hidden fields
            $('#selectedSerialId').val(serialId);
            $('#selectedAssignmentId').val(assignmentId);
            $('#selectedSerialNumber').val(serialNumber);
            $('#selectedModelNumber').val(modelNumber);
            
            $('#employeeId').val(employeeId);
            $('#employeeName').val(employeeName);
            $('#employeePfNo').val(employeePf);
            $('#employeeDesignation').val(employeeDesignation);
            $('#employeeDepartment').val(employeeDepartment);
            $('#employeePhone').val(employeePhone);
            $('#employeeEmail').val(employeeEmail);
            
            // Auto-fill delivery fields
            if(employeeDepartment) {
                $('#deliveryLocation').val(employeeDepartment);
            }
            if(employeeName) {
                $('#toAttn').val(employeeName);
            }
            
            // Source label for display
            const sourceLabel = source === 'request' ? 'Public Request' : 
                               source === 'transfer' ? 'Device Transfer' : 'IT Admin';
            
            // Build employee info display
            let employeeHtml = `<div class="info-card">
                                    <h6 class="mb-3"><i class="fas fa-user-circle me-2"></i> Employee Information
                                        <span class="badge bg-secondary ms-2">${escapeHtml(sourceLabel)}</span>
                                    </h6>`;
            
            if(employeeName) {
                employeeHtml += `<div class="info-row">
                                    <div class="info-label">Employee Name:</div>
                                    <div class="info-value"><strong>${escapeHtml(employeeName)}</strong></div>
                                </div>`;
            } else {
                employeeHtml += `<div class="info-row">
                                    <div class="info-label">Employee Name:</div>
                                    <div class="info-value text-muted">Not assigned</div>
                                </div>`;
            }
            
            if(employeePf) {
                employeeHtml += `<div class="info-row">
                                    <div class="info-label">PF No:</div>
                                    <div class="info-value">${escapeHtml(employeePf)}</div>
                                </div>`;
            }
            if(employeeDesignation) {
                employeeHtml += `<div class="info-row">
                                    <div class="info-label">Designation:</div>
                                    <div class="info-value">${escapeHtml(employeeDesignation)}</div>
                                </div>`;
            }
            if(employeeDepartment) {
                employeeHtml += `<div class="info-row">
                                    <div class="info-label">Department:</div>
                                    <div class="info-value">${escapeHtml(employeeDepartment)}</div>
                                </div>`;
            }
            if(employeePhone) {
                employeeHtml += `<div class="info-row">
                                    <div class="info-label">Phone:</div>
                                    <div class="info-value">${escapeHtml(employeePhone)}</div>
                                </div>`;
            }
            if(employeeEmail) {
                employeeHtml += `<div class="info-row">
                                    <div class="info-label">Email:</div>
                                    <div class="info-value">${escapeHtml(employeeEmail)}</div>
                                </div>`;
            }
            
            employeeHtml += `<hr>
                            <h6 class="mb-3"><i class="fas fa-clipboard-list me-2"></i> Assignment Information</h6>`;
            
            if(assignmentNo) {
                employeeHtml += `<div class="info-row">
                                    <div class="info-label">Assignment No:</div>
                                    <div class="info-value"><strong>${escapeHtml(assignmentNo)}</strong></div>
                                </div>`;
            } else {
                employeeHtml += `<div class="info-row">
                                    <div class="info-label">Assignment No:</div>
                                    <div class="info-value text-muted">No assignment found</div>
                                </div>`;
            }
            
            if(assignedDate) {
                employeeHtml += `<div class="info-row">
                                    <div class="info-label">Assigned Date:</div>
                                    <div class="info-value">${assignedDate}</div>
                                </div>`;
            }
            
            employeeHtml += `<hr>
                            <h6 class="mb-3"><i class="fas fa-microchip me-2"></i> Device Information</h6>
                            <div class="info-row">
                                <div class="info-label">Serial No:</div>
                                <div class="info-value"><strong>${escapeHtml(serialNumber)}</strong></div>
                            </div>`;
            
            if(modelNumber) {
                employeeHtml += `<div class="info-row">
                                    <div class="info-label">Model No:</div>
                                    <div class="info-value">${escapeHtml(modelNumber)}</div>
                                </div>`;
            }
            
            employeeHtml += `</div>`;
            
            $('#employeeInfoDisplay').html(employeeHtml);
            $('#employeeSection').show();
            $('#locationSection').show();
            $('#productSection').show();
            
            // Scroll to employee section
            $('html, body').animate({
                scrollTop: $('#employeeSection').offset().top - 100
            }, 500);
        } else {
            // Clear if no serial selected
            $('#employeeSection').hide();
            $('#locationSection').hide();
            $('#productSection').hide();
            $('#employeeInfoDisplay').html('');
        }
    });
});

function clearSelectedItem() {
    $('#selectedItemId').val('');
    $('#productName').val('');
    $('#productType').val('');
    $('#description').val('');
    $('#selectedItemDisplay').hide();
    $('#serialSection').hide();
    $('#employeeSection').hide();
    $('#locationSection').hide();
    $('#productSection').hide();
    $('#itemSearch').val('').focus();
    $('#serialSelect').html('<option value="">-- Select Serial Number --</option>');
    $('#employeeInfoDisplay').html('');
}

function escapeHtml(text) {
    if(!text) return '';
    return String(text).replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}

// Close success modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('successModal');
    if (modal && e.target === modal) {
        modal.style.display = 'none';
    }
});

// Auto-close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('successModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>