<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// ============================================
// FIX: Check and add missing columns if needed
// ============================================
try {
    // Check if version column exists
    $check = $pdo->query("SHOW COLUMNS FROM licenses LIKE 'version'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE licenses ADD COLUMN version VARCHAR(50) DEFAULT NULL AFTER software_name");
    }
    
    // Check if vendor_name column exists
    $check = $pdo->query("SHOW COLUMNS FROM licenses LIKE 'vendor_name'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE licenses ADD COLUMN vendor_name VARCHAR(200) DEFAULT NULL AFTER vendor_id");
    }
    
    // Check if purchased_from column exists
    $check = $pdo->query("SHOW COLUMNS FROM licenses LIKE 'purchased_from'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE licenses ADD COLUMN purchased_from VARCHAR(255) DEFAULT NULL AFTER sub_category_id");
    }
    
    // Check if cost column exists
    $check = $pdo->query("SHOW COLUMNS FROM licenses LIKE 'cost'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE licenses ADD COLUMN cost DECIMAL(12,2) DEFAULT 0.00 AFTER seats");
    }
    
} catch(PDOException $e) {
    error_log("Error checking/adding columns: " . $e->getMessage());
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
$employees = $pdo->query("SELECT id, pf_no, full_name, designation FROM employees WHERE is_active = 1 ORDER BY full_name")->fetchAll();

// Handle form submission
$show_success_modal = false;
$success_data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_license') {
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
    
    try {
        // Build insert query based on available columns
        $columns = [];
        $values = [];
        $placeholders = [];
        
        // Get existing columns
        $col_check = $pdo->query("SHOW COLUMNS FROM licenses");
        $existing_columns = $col_check->fetchAll(PDO::FETCH_COLUMN);
        
        // Define column mapping with default values
        $field_map = [
            'software_name' => $software_name,
            'license_key' => $license_key,
            'version' => $version,
            'license_type' => $license_type,
            'purchase_date' => $purchase_date,
            'expiry_date' => $expiry_date,
            'cost' => $cost,
            'vendor_id' => $vendor_id,
            'vendor_name' => $vendor_name,
            'item_id' => $item_id,
            'category_id' => $category_id,
            'sub_category_id' => $sub_category_id,
            'seats' => $seats,
            'used_seats' => 0,
            'status' => $status,
            'notes' => $notes,
            'purchased_from' => $purchased_from,
            'invoice_number' => $invoice_number,
            'po_number' => $po_number,
            'support_contact' => $support_contact,
            'support_email' => $support_email,
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
        
        $sql = "INSERT INTO licenses (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        $license_id = $pdo->lastInsertId();
        
        // Log history
        try {
            $hist_stmt = $pdo->prepare("INSERT INTO license_history (license_id, action, old_value, new_value, performed_by, performed_at) VALUES (?, 'created', NULL, ?, ?, NOW())");
            $hist_stmt->execute([$license_id, json_encode($_POST), $user_id]);
        } catch(PDOException $e) {
            error_log("License history table not found: " . $e->getMessage());
        }
        
        // Set success data for modal
        $show_success_modal = true;
        $success_data = [
            'software_name' => $software_name,
            'license_key' => $license_key,
            'license_type' => $license_type,
            'status' => $status,
            'license_id' => $license_id
        ];
        
    } catch(Exception $e) {
        $error = "Error saving license: " . $e->getMessage();
    }
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
            <h4 class="fw-bold mb-1"><i class="fas fa-key text-primary me-2"></i>Add New License</h4>
            <p class="text-muted small mb-0">Add a new software license to the inventory</p>
        </div>
        <a href="licenses.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Licenses
        </a>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="licenseForm">
        <input type="hidden" name="action" value="add_license">
        
        <div class="row">
            <div class="col-lg-8">
                <!-- License Information -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-info-circle"></i>License Information</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required-field">Software Name</label>
                            <input type="text" name="software_name" id="software_name" class="form-control" required placeholder="e.g., Microsoft Office 365">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">License Key</label>
                            <input type="text" name="license_key" id="license_key" class="form-control" placeholder="Enter license key or serial">
                            <div class="help-text">The unique license key or serial number</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Version</label>
                            <input type="text" name="version" id="version" class="form-control" placeholder="e.g., 2024, v3.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">License Type</label>
                            <select name="license_type" id="license_type" class="form-select">
                                <option value="perpetual">Perpetual</option>
                                <option value="subscription">Subscription</option>
                                <option value="trial">Trial</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Seats</label>
                            <input type="number" name="seats" id="seats" class="form-control" value="1" min="1">
                            <div class="help-text">Number of users/devices covered</div>
                        </div>
                    </div>
                </div>

                <!-- Dates & Cost -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-calendar-alt"></i>Dates & Cost</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Purchase Date</label>
                            <input type="date" name="purchase_date" id="purchase_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Expiry Date</label>
                            <input type="date" name="expiry_date" id="expiry_date" class="form-control">
                            <div class="help-text">Leave empty for perpetual licenses</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Cost (BDT)</label>
                            <input type="number" step="0.01" name="cost" id="cost" class="form-control" placeholder="0.00">
                            <div class="help-text">Total cost of the license</div>
                        </div>
                    </div>
                </div>

                <!-- Vendor & Item Association -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-building"></i>Vendor & Item Association</div>
                    <div class="row g-3">
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
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Vendor Name (Manual)</label>
                            <input type="text" name="vendor_name" id="vendor_name" class="form-control" placeholder="Enter vendor name if not in list">
                            <div class="help-text">Leave blank if vendor is selected above</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Select Item</label>
                            <select name="item_id" id="item_id" class="form-select">
                                <option value="">-- Select Item --</option>
                                <?php foreach($items as $item): ?>
                                <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['item_code'] . ' - ' . $item['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">Associated hardware/item (optional)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Select Category</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="">-- Select Category --</option>
                                <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">License category (optional)</div>
                        </div>
                    </div>
                </div>

                <!-- Invoice & Support -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-file-invoice"></i>Invoice & Support</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Invoice Number</label>
                            <input type="text" name="invoice_number" id="invoice_number" class="form-control" placeholder="INV-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">PO Number</label>
                            <input type="text" name="po_number" id="po_number" class="form-control" placeholder="PO-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Support Contact</label>
                            <input type="text" name="support_contact" id="support_contact" class="form-control" placeholder="John Doe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Support Email</label>
                            <input type="email" name="support_email" id="support_email" class="form-control" placeholder="support@vendor.com">
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-sticky-note"></i>Notes & Documentation</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="Additional notes about this license..."></textarea>
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
                    <a href="licenses.php" class="btn btn-gradient-secondary">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-gradient-primary" id="submitBtn">
                        <i class="fas fa-save me-2"></i> Save License
                    </button>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-info-circle"></i>Instructions</div>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-2"><i class="fas fa-asterisk text-danger"></i> <strong>Software Name</strong> is required</li>
                        <li class="mb-2"><i class="fas fa-clock text-warning"></i> Set <strong>Expiry Date</strong> to track renewals</li>
                        <li class="mb-2"><i class="fas fa-users text-info"></i> <strong>Seats</strong> determines how many can use this license</li>
                        <li class="mb-2"><i class="fas fa-file-alt text-secondary"></i> Upload <strong>Documentation</strong> for reference</li>
                        <li><i class="fas fa-check-circle text-success"></i> License will be active by default</li>
                    </ul>
                </div>

                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-question-circle"></i>License Types</div>
                    <div class="small">
                        <p class="mb-1"><strong>Perpetual</strong> - One-time purchase, no expiry</p>
                        <p class="mb-1"><strong>Subscription</strong> - Recurring payment, has expiry</p>
                        <p class="mb-0"><strong>Trial</strong> - Limited time evaluation</p>
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
        <div class="success-modal-title">License Added Successfully!</div>
        <div class="success-modal-subtitle">
            The license has been added to the system.
        </div>
        <div class="success-modal-details">
            <div class="detail-item">
                <span class="label">Software</span>
                <span class="value"><?php echo htmlspecialchars($success_data['software_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">License Key</span>
                <span class="value"><code><?php echo htmlspecialchars($success_data['license_key'] ?? 'N/A'); ?></code></span>
            </div>
            <div class="detail-item">
                <span class="label">License Type</span>
                <span class="value"><?php echo ucfirst($success_data['license_type'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Status</span>
                <span class="value"><span class="status-badge status-<?php echo $success_data['status'] ?? 'active'; ?>"><?php echo ucfirst(str_replace('_', ' ', $success_data['status'] ?? 'Active')); ?></span></span>
            </div>
            <?php if(isset($success_data['license_id'])): ?>
            <div class="detail-item">
                <span class="label">License ID</span>
                <span class="value">#<?php echo $success_data['license_id']; ?></span>
            </div>
            <?php endif; ?>
        </div>
        <div class="success-modal-actions">
            <a href="licenses.php" class="btn btn-success-modal">
                <i class="fas fa-list me-2"></i> View All Licenses
            </a>
            <a href="add_license.php" class="btn btn-secondary-modal">
                <i class="fas fa-plus me-2"></i> Add Another
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
    $('#licenseForm').on('submit', function(e) {
        const softwareName = $('#software_name').val().trim();
        if(!softwareName) {
            e.preventDefault();
            alert('Please enter the Software Name.');
            $('#software_name').focus();
            return false;
        }
        
        const submitBtn = $('#submitBtn');
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i> Saving...');
        submitBtn.prop('disabled', true);
    });
});
</script>

<?php include '../../includes/footer.php'; ?>