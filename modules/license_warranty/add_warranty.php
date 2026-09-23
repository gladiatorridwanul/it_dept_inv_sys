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
    
} catch(PDOException $e) {
    error_log("Error checking/adding columns: " . $e->getMessage());
}

// Get all data for dropdowns
$items = [];
$categories = [];
$vendors = [];

try {
    $items = $pdo->query("SELECT id, name, item_code FROM items WHERE is_active = 1 ORDER BY name")->fetchAll();
} catch(PDOException $e) { error_log("Items error: " . $e->getMessage()); }

try {
    $categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
} catch(PDOException $e) { error_log("Categories error: " . $e->getMessage()); }

try {
    $vendors = $pdo->query("SELECT id, vendor_name FROM vendors WHERE is_active = 1 ORDER BY vendor_name")->fetchAll();
} catch(PDOException $e) { error_log("Vendors error: " . $e->getMessage()); }

$show_success_modal = false;
$success_data = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_warranty') {
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
    $user_id = $_SESSION['user_id'] ?? 1;
    
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
    
    try {
        // Build insert query based on available columns
        $columns = [];
        $values = [];
        $placeholders = [];
        
        // Get existing columns
        $col_check = $pdo->query("SHOW COLUMNS FROM warranties");
        $existing_columns = $col_check->fetchAll(PDO::FETCH_COLUMN);
        
        // Define column mapping with default values
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
        
        // Build INSERT statement with only existing columns
        foreach ($field_map as $col => $val) {
            if (in_array($col, $existing_columns)) {
                $columns[] = $col;
                $placeholders[] = '?';
                $values[] = $val;
            }
        }
        
        if (empty($columns)) {
            throw new Exception("No valid columns found for insertion");
        }
        
        $sql = "INSERT INTO warranties (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        $warranty_id = $pdo->lastInsertId();
        
        // Set success data for modal
        $show_success_modal = true;
        $success_data = [
            'item_name' => $item_name,
            'serial_number' => $serial_number,
            'warranty_type' => $warranty_type,
            'warranty_provider' => $warranty_provider,
            'status' => $status,
            'warranty_id' => $warranty_id,
            'warranty_end_date' => $warranty_end_date
        ];
        
        // Store in session for success popup (for redirect)
        $_SESSION['warranty_success'] = $success_data;
        
    } catch(Exception $e) {
        $error = "Error saving warranty: " . $e->getMessage();
    }
}

// Check for success from session (if redirected)
if (isset($_SESSION['warranty_success']) && !$show_success_modal) {
    $success_data = $_SESSION['warranty_success'];
    $show_success_modal = true;
    unset($_SESSION['warranty_success']);
}
?>

<style>
    * { font-family: 'Inter', sans-serif; }
    .form-section {
        background: white;
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 25px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .form-section-title {
        font-weight: 700;
        font-size: 18px;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
        color: #1a202c;
    }
    .form-section-title i {
        color: #3b82f6;
        margin-right: 10px;
    }
    .required-field::after {
        content: " *";
        color: #dc2626;
        font-weight: bold;
    }
    .btn-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
        padding: 10px 35px;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s;
    }
    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(59,130,246,0.4);
        color: white;
    }
    .btn-gradient-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 10px 35px;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s;
    }
    .btn-gradient-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16,185,129,0.4);
        color: white;
    }
    .btn-gradient-secondary {
        background: #e2e8f0;
        color: #4a5568;
        border: none;
        padding: 10px 25px;
        font-weight: 600;
        border-radius: 10px;
    }
    .btn-gradient-secondary:hover {
        background: #cbd5e0;
        color: #2d3748;
    }
    .help-text {
        font-size: 12px;
        color: #718096;
        margin-top: 4px;
    }
    select.form-select, input.form-control, textarea.form-control {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        padding: 10px 14px;
        font-size: 14px;
    }
    select.form-select:focus, input.form-control:focus, textarea.form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    }
    .upload-area {
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        background: #fafbfc;
        transition: all 0.3s ease;
    }
    .upload-area:hover {
        border-color: #3b82f6;
        background: #f0f7ff;
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
        max-width: 500px;
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
    }
    .success-modal-details .value code {
        font-size: 12px;
        background: #edf2f7;
        padding: 2px 8px;
        border-radius: 4px;
    }
    .success-modal-details .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-active { background: #d1fae5; color: #059669; }
    .status-expired { background: #fee2e2; color: #dc2626; }
    .status-expiring_soon { background: #fef3c7; color: #d97706; }
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
            <h4 class="fw-bold mb-1"><i class="fas fa-shield-alt text-success me-2"></i>Add New Warranty</h4>
            <p class="text-muted small mb-0">Register a new warranty for a device or item</p>
        </div>
        <a href="warranties.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Warranties
        </a>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="warrantyForm">
        <input type="hidden" name="action" value="add_warranty">
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Warranty Information -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-info-circle"></i>Warranty Information</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required-field">Item/Device Name</label>
                            <input type="text" name="item_name" id="item_name" class="form-control" required placeholder="e.g., HP Laptop X360">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Serial Number</label>
                            <input type="text" name="serial_number" id="serial_number" class="form-control" placeholder="Enter serial number">
                            <div class="help-text">The device serial number (if applicable)</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Warranty Type</label>
                            <select name="warranty_type" id="warranty_type" class="form-select">
                                <option value="manufacturer">Manufacturer</option>
                                <option value="extended">Extended</option>
                                <option value="service">Service Contract</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold required-field">Start Date</label>
                            <input type="date" name="warranty_start_date" id="warranty_start_date" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold required-field">End Date</label>
                            <input type="date" name="warranty_end_date" id="warranty_end_date" class="form-control" required>
                            <div class="help-text">Warranty expiry date</div>
                        </div>
                    </div>
                </div>

                <!-- Provider Details -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-building"></i>Provider Details</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required-field">Warranty Provider</label>
                            <input type="text" name="warranty_provider" id="warranty_provider" class="form-control" required placeholder="e.g., Dell, HP, Apple">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Provider Phone</label>
                            <input type="text" name="provider_phone" id="provider_phone" class="form-control" placeholder="Contact phone number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Provider Email</label>
                            <input type="email" name="provider_email" id="provider_email" class="form-control" placeholder="support@provider.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Select Vendor</label>
                            <select name="vendor_id" id="vendor_id" class="form-select">
                                <option value="">-- Select Vendor --</option>
                                <?php foreach($vendors as $vendor): ?>
                                <option value="<?php echo $vendor['id']; ?>"><?php echo htmlspecialchars($vendor['vendor_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">Select from existing vendors</div>
                        </div>
                    </div>
                </div>

                <!-- Item & Category Association -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-tags"></i>Item & Category Association</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Select Item</label>
                            <select name="item_id" id="item_id" class="form-select">
                                <option value="">-- Select Item --</option>
                                <?php foreach($items as $item): ?>
                                <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['item_code'] . ' - ' . $item['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">Associated item (optional)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Select Category</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="">-- Select Category --</option>
                                <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">Warranty category (optional)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Invoice Number</label>
                            <input type="text" name="invoice_number" id="invoice_number" class="form-control" placeholder="INV-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Coverage Details</label>
                            <input type="text" name="coverage_details" id="coverage_details" class="form-control" placeholder="What is covered?">
                        </div>
                    </div>
                </div>

                <!-- Claim Information -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-phone-alt"></i>Claim Information</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Claim Phone</label>
                            <input type="text" name="claim_phone" id="claim_phone" class="form-control" placeholder="Phone to file a claim">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Claim Email</label>
                            <input type="email" name="claim_email" id="claim_email" class="form-control" placeholder="claims@provider.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Claim Website</label>
                            <input type="text" name="claim_website" id="claim_website" class="form-control" placeholder="https://support.provider.com">
                        </div>
                    </div>
                </div>

                <!-- Notes & Documentation -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-sticky-note"></i>Notes & Documentation</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="Additional notes about this warranty..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Documentation File</label>
                            <div class="upload-area" onclick="document.getElementById('documentation_file').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#3b82f6;"></i>
                                <p class="mb-0">Click to upload document</p>
                                <small class="text-muted">PDF, DOC, DOCX, JPG, PNG (Max 5MB)</small>
                                <input type="file" name="documentation_file" id="documentation_file" style="display:none" accept=".pdf,.doc,.docx,.jpg,.png">
                            </div>
                            <div id="fileName" class="mt-2 small text-muted"></div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex justify-content-end gap-3 mt-4 mb-4">
                    <a href="warranties.php" class="btn btn-gradient-secondary">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-2"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-gradient-success" id="submitBtn">
                        <i class="fas fa-save me-2"></i> Save Warranty
                    </button>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-info-circle"></i>Instructions</div>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-2"><i class="fas fa-asterisk text-danger"></i> <strong>Item/Device Name</strong> is required</li>
                        <li class="mb-2"><i class="fas fa-calendar text-warning"></i> Set <strong>Start/End Dates</strong> to track warranty period</li>
                        <li class="mb-2"><i class="fas fa-building text-info"></i> <strong>Warranty Provider</strong> is required</li>
                        <li class="mb-2"><i class="fas fa-file-alt text-secondary"></i> Upload <strong>Documentation</strong> for reference</li>
                        <li><i class="fas fa-check-circle text-success"></i> Warranty will be active by default</li>
                    </ul>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-question-circle"></i>Warranty Types</div>
                    <div class="small">
                        <p class="mb-1"><strong>Manufacturer</strong> - Original manufacturer warranty</p>
                        <p class="mb-1"><strong>Extended</strong> - Extended warranty coverage</p>
                        <p class="mb-0"><strong>Service Contract</strong> - Service/maintenance agreement</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Success Modal -->
<?php if($show_success_modal && !empty($success_data)): ?>
<div class="success-modal-overlay" id="successModal">
    <div class="success-modal-box">
        <div class="success-modal-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="success-modal-title">Warranty Added Successfully!</div>
        <div class="success-modal-subtitle">
            The warranty has been added to the system.
        </div>
        <div class="success-modal-details">
            <div class="detail-item">
                <span class="label">Device</span>
                <span class="value"><?php echo htmlspecialchars($success_data['item_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Serial Number</span>
                <span class="value"><code><?php echo htmlspecialchars($success_data['serial_number'] ?? 'N/A'); ?></code></span>
            </div>
            <div class="detail-item">
                <span class="label">Warranty Type</span>
                <span class="value"><?php echo ucfirst($success_data['warranty_type'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Provider</span>
                <span class="value"><?php echo htmlspecialchars($success_data['warranty_provider'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">End Date</span>
                <span class="value"><?php echo isset($success_data['warranty_end_date']) ? date('d-m-Y', strtotime($success_data['warranty_end_date'])) : 'N/A'; ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Status</span>
                <span class="value"><span class="status-badge status-<?php echo $success_data['status'] ?? 'active'; ?>"><?php echo ucfirst(str_replace('_', ' ', $success_data['status'] ?? 'Active')); ?></span></span>
            </div>
            <?php if(isset($success_data['warranty_id'])): ?>
            <div class="detail-item">
                <span class="label">Warranty ID</span>
                <span class="value">#<?php echo $success_data['warranty_id']; ?></span>
            </div>
            <?php endif; ?>
        </div>
        <div class="success-modal-actions">
            <a href="warranties.php" class="btn btn-success-modal">
                <i class="fas fa-list me-2"></i> Warranty List
            </a>
            <a href="add_warranty.php" class="btn btn-secondary-modal">
                <i class="fas fa-plus me-2"></i> More Add
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // File upload handler
    $('#documentation_file').on('change', function() {
        if(this.files && this.files[0]) {
            var fileName = this.files[0].name;
            var fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
            $('#fileName').html('<i class="fas fa-check-circle text-success"></i> ' + fileName + ' (' + fileSize + ' MB)');
        } else {
            $('#fileName').html('');
        }
    });
    
    // Auto-fill item name when item is selected
    $('#item_id').on('change', function() {
        var selected = $(this).find(':selected');
        var name = selected.text();
        if(name && name.includes(' - ')) {
            var parts = name.split(' - ');
            if(parts.length > 1) {
                $('#item_name').val(parts.slice(1).join(' - '));
            }
        }
    });
    
    // Close success modal when clicking outside
    $(document).on('click', function(e) {
        const modal = document.getElementById('successModal');
        if (modal && e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Auto-close modal with Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('successModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
    });
    
    // Form submission handler
    $('#warrantyForm').on('submit', function(e) {
        const itemName = $('#item_name').val().trim();
        if(!itemName) {
            e.preventDefault();
            alert('Please enter the Item/Device Name.');
            $('#item_name').focus();
            return false;
        }
        
        const startDate = $('#warranty_start_date').val();
        const endDate = $('#warranty_end_date').val();
        if(startDate && endDate && endDate < startDate) {
            e.preventDefault();
            alert('End Date cannot be earlier than Start Date.');
            $('#warranty_end_date').focus();
            return false;
        }
        
        const submitBtn = $('#submitBtn');
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i> Saving...');
        submitBtn.prop('disabled', true);
    });
});
</script>

<?php include '../../includes/footer.php'; ?>