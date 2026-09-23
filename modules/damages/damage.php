<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Define redirect function if not exists
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . $url);
        exit();
    }
}

// Handle file upload
function uploadDocument($file) {
    if(isset($file) && $file['error'] == 0 && $file['size'] > 0) {
        $upload_dir = '../../uploads/damages/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $new_filename = 'DAMAGE_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if(move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                return 'uploads/damages/' . $new_filename;
            }
        }
    }
    return null;
}

$show_success_modal = false;
$success_damage_no = '';
$success_item_name = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = $_POST['item_id'];
    $serial_number = !empty($_POST['serial_number']) ? $_POST['serial_number'] : null;
    $model_number = !empty($_POST['model_number']) ? $_POST['model_number'] : null;
    $version = !empty($_POST['version']) ? $_POST['version'] : null;
    $quantity = $_POST['quantity'];
    $damage_date = $_POST['damage_date'];
    $damage_type = $_POST['damage_type'];
    $damage_severity = $_POST['damage_severity'];
    $damage_description = $_POST['damage_description'];
    $return_request_id = !empty($_POST['return_request_id']) ? $_POST['return_request_id'] : null;
    
    $attachment = uploadDocument($_FILES['attachment']);
    
    $pdo->beginTransaction();
    
    try {
        // Get item details
        $stmt = $pdo->prepare("SELECT name, item_code, current_qty FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        $success_item_name = $item['name'];
        
        // Generate damage number
        $damage_no = 'DMG-' . date('Ymd') . '-' . rand(1000, 9999);
        $success_damage_no = $damage_no;
        
        // Insert damage record
        $stmt = $pdo->prepare("INSERT INTO damages (damage_no, item_id, damage_type, damage_severity, damage_description, damage_date, estimated_cost, reported_date, reported_by, status, attachment_file, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), ?, 'pending', ?, NOW())");
        $stmt->execute([$damage_no, $item_id, $damage_type, $damage_severity, $damage_description, $damage_date, $_SESSION['user_id'], $attachment]);
        $damage_id = $pdo->lastInsertId();
        
        // Update item quantity
        $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty - ? WHERE id = ?");
        $stmt->execute([$quantity, $item_id]);
        
        // If serial number selected, mark it as damaged
        if($serial_number) {
            try {
                // Check if damage_id column exists
                $check_column = $pdo->query("SHOW COLUMNS FROM item_serial_numbers LIKE 'damage_id'");
                if($check_column->rowCount() > 0) {
                    $stmt = $pdo->prepare("UPDATE item_serial_numbers SET is_assigned = 2, damage_id = ? WHERE serial_number = ? AND item_id = ?");
                    $stmt->execute([$damage_id, $serial_number, $item_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE item_serial_numbers SET is_assigned = 2 WHERE serial_number = ? AND item_id = ?");
                    $stmt->execute([$serial_number, $item_id]);
                }
            } catch(PDOException $e) {
                $stmt = $pdo->prepare("UPDATE item_serial_numbers SET is_assigned = 2 WHERE serial_number = ? AND item_id = ?");
                $stmt->execute([$serial_number, $item_id]);
            }
        }
        
        // If return request is linked, update it
        if($return_request_id) {
            $stmt = $pdo->prepare("UPDATE return_device_items SET processing_status = 'damaged' WHERE id = ?");
            $stmt->execute([$return_request_id]);
        }
        
        $pdo->commit();
        
        // Set success modal variables
        $show_success_modal = true;
        $_SESSION['success_message'] = "Damage reported successfully! Damage No: " . $damage_no;
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Get items with available quantity (Total Qty - Assigned Qty)
$items = $pdo->query("
    SELECT i.id, i.name, i.item_code, i.current_qty, i.brand, i.model_number, i.version,
           COALESCE(SUM(a.quantity), 0) as assigned_qty,
           (i.current_qty - COALESCE(SUM(a.quantity), 0)) as available_qty,
           (SELECT COUNT(*) FROM item_serial_numbers isn WHERE isn.item_id = i.id AND (isn.is_assigned = 0 OR isn.is_assigned IS NULL)) as available_serials
    FROM items i
    LEFT JOIN assignments a ON i.id = a.item_id AND a.status = 'assigned' AND a.return_status = 'active'
    WHERE i.is_active = 1
    GROUP BY i.id
    HAVING available_qty > 0
    ORDER BY i.name
")->fetchAll();
?>

<style>
    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .required-field::after {
        content: " *";
        color: red;
        font-weight: bold;
    }
    .card-header {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }
    .serial-model-version-row {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        padding: 15px;
        background: #f8fafc;
        border-radius: 12px;
        border-left: 4px solid #ef4444;
        display: none;
    }
    .serial-model-version-row .form-group {
        flex: 1;
    }
    .return-list-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-top: 15px;
        border: 1px solid #e2e8f0;
        display: none;
    }
    .return-list-container {
        max-height: 300px;
        overflow-y: auto;
    }
    .return-item {
        padding: 12px;
        margin-bottom: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        background: white;
    }
    .return-item:hover {
        background: #eff6ff;
        border-color: #3b82f6;
    }
    .return-item.selected {
        background: #d1fae5;
        border-color: #10b981;
    }
    .return-item .badge-pending { background: #fef3c7; color: #d97706; }
    .return-item .badge-approved { background: #dbeafe; color: #2563eb; }
    .return-item .badge-processing { background: #e0e7ff; color: #4338ca; }
    .return-item .badge-completed { background: #d1fae5; color: #059669; }
    .return-item .badge-rejected { background: #fee2e2; color: #dc2626; }
    .upload-area {
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        cursor: pointer;
        background: white;
        transition: all 0.3s ease;
    }
    .upload-area:hover {
        border-color: #ef4444;
        background: #fef2f2;
    }
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #e2e8f0;
        border-top-color: #ef4444;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
        margin-left: 8px;
        display: none;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    select[disabled] {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
    .stock-badge {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 20px;
        background: #e2e8f0;
        color: #475569;
        margin-left: 8px;
    }
    .selected-item-info {
        background: #e8f5e9;
        border-left: 4px solid #10b981;
        padding: 12px 15px;
        border-radius: 12px;
        margin-top: 10px;
        display: none;
    }
    .selected-item-info p {
        margin: 0 0 5px 0;
    }
    .info-icon {
        width: 32px;
        height: 32px;
        background: #10b98120;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }
    /* Searchable Select Styles */
    .searchable-select-container {
        position: relative;
        width: 100%;
    }
    .searchable-select-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        font-size: 0.85rem;
        cursor: pointer;
        background: white;
    }
    .searchable-select-input:focus {
        outline: none;
        border-color: #2d6a4f;
        box-shadow: 0 0 0 0.2rem rgba(45, 106, 79, 0.25);
    }
    .searchable-select-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 250px;
        overflow-y: auto;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .searchable-select-dropdown.show {
        display: block;
    }
    .searchable-select-option {
        padding: 8px 12px;
        cursor: pointer;
        transition: background 0.2s;
        border-bottom: 1px solid #f0f0f0;
    }
    .searchable-select-option:hover {
        background: #f0fdf4;
    }
    .searchable-select-option.selected {
        background: #d1fae5;
    }
    .searchable-select-option .item-name {
        font-weight: 500;
    }
    .searchable-select-option .item-details {
        font-size: 11px;
        color: #6c757d;
        margin-top: 2px;
    }
    .searchable-select-no-results {
        padding: 12px;
        text-align: center;
        color: #6c757d;
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
        color: #dc2626;
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
    }
    .success-modal-details .value {
        font-weight: 600;
        color: #2d3748;
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
    }
    .btn-success-modal {
        background: #dc2626;
        color: white;
        border: none;
    }
    .btn-success-modal:hover {
        background: #b91c1c;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(220,38,38,0.3);
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

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-exclamation-triangle text-danger"></i> Report Damaged Device</h2>
                    <p class="text-muted">Report damaged IT equipment and track warranty claims</p>
                </div>
                <div>
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-list"></i> Damage List
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0"><i class="fas fa-tools"></i> Damage Report Form</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="damageForm">
                        <!-- Item Selection with Search -->
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label class="form-label required-field">Item *</label>
                                <div class="searchable-select-container" id="itemSearchContainer">
                                    <input type="text" class="searchable-select-input" id="itemSearchInput" placeholder="Type to search item..." autocomplete="off">
                                    <input type="hidden" name="item_id" id="selectedItemId" value="">
                                    <div class="searchable-select-dropdown" id="itemDropdown">
                                        <?php foreach($items as $item): ?>
                                            <div class="searchable-select-option" 
                                                 data-id="<?php echo $item['id']; ?>"
                                                 data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                 data-code="<?php echo htmlspecialchars($item['item_code']); ?>"
                                                 data-brand="<?php echo htmlspecialchars($item['brand'] ?? ''); ?>"
                                                 data-available="<?php echo $item['available_qty']; ?>"
                                                 data-total="<?php echo $item['current_qty']; ?>"
                                                 data-assigned="<?php echo $item['assigned_qty']; ?>"
                                                 data-serials="<?php echo $item['available_serials']; ?>">
                                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <div class="item-details">
                                                    <?php echo htmlspecialchars($item['item_code']); ?> | Available: <?php echo $item['available_qty']; ?>
                                                    <?php if($item['brand']): ?> | Brand: <?php echo htmlspecialchars($item['brand']); ?><?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <small class="text-muted">Type to search items by name, code, or brand. Select from dropdown.</small>
                            </div>
                        </div>
                        
                        <!-- Selected Item Information Display -->
                        <div class="selected-item-info" id="selectedItemInfo">
                            <div class="d-flex align-items-start">
                                <div class="info-icon">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                <div>
                                    <p><strong>Selected Item:</strong> <span id="selectedItemName"></span></p>
                                    <p><strong>Item Code:</strong> <span id="selectedItemCode"></span> | 
                                       <strong>Brand:</strong> <span id="selectedItemBrand"></span></p>
                                    <p><strong>Available Stock:</strong> <span id="selectedItemStock"></span></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Serial, Model, Version Row -->
                        <div class="serial-model-version-row" id="serialModelVersionRow">
                            <div class="form-group">
                                <label class="form-label">Serial Number</label>
                                <select name="serial_number" class="form-select" id="serialSelect">
                                    <option value="">-- Select Serial Number --</option>
                                </select>
                                <div id="serialLoading" class="loading-spinner"></div>
                                <small class="text-muted">Select the serial number of the damaged device</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Model Number</label>
                                <select name="model_number" class="form-select" id="modelSelect">
                                    <option value="">-- Select Model Number --</option>
                                </select>
                                <small class="text-muted">Select model number if applicable</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Version</label>
                                <select name="version" class="form-select" id="versionSelect">
                                    <option value="">-- Select Version --</option>
                                </select>
                                <small class="text-muted">Select version if applicable</small>
                            </div>
                        </div>
                        
                        <!-- Related Return Requests List -->
                        <div class="return-list-card" id="returnListCard">
                            <label class="form-label">Related Return Requests</label>
                            <div id="returnListContainer" class="return-list-container">
                                <div class="text-muted text-center py-3">Select an item to see related return requests</div>
                            </div>
                            <input type="hidden" name="return_request_id" id="selectedReturnId" value="">
                            <small class="text-muted mt-2">Select a return request to link with this damage report</small>
                        </div>
                        
                        <!-- Quantity -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label required-field">Quantity *</label>
                                <input type="number" name="quantity" class="form-control" id="quantity" required min="1" value="1">
                                <small class="text-muted" id="stockInfo"></small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label required-field">Damage Date *</label>
                                <input type="date" name="damage_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label required-field">Damage Type *</label>
                                <select name="damage_type" class="form-select" required>
                                    <option value="physical">Physical Damage</option>
                                    <option value="functional">Functional/Malfunction</option>
                                    <option value="liquid">Liquid Damage</option>
                                    <option value="electrical">Electrical Damage</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Damage Severity -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Damage Severity</label>
                                <select name="damage_severity" class="form-select">
                                    <option value="minor">Minor - Cosmetic damage, still functional</option>
                                    <option value="moderate">Moderate - Affects functionality but repairable</option>
                                    <option value="severe">Severe - Major damage, may require replacement</option>
                                    <option value="critical">Critical - Beyond repair</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Damage Description -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label required-field">Damage Description *</label>
                                <textarea name="damage_description" rows="4" class="form-control" required placeholder="Describe the damage in detail including when and how it occurred..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Attachment Upload -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Supporting Document</label>
                                <div class="upload-area" onclick="document.getElementById('attachmentInput').click()">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#ef4444;"></i>
                                    <p class="mb-0">Click to upload document</p>
                                    <small>JPG, PNG, PDF (Max 5MB)</small>
                                    <input type="file" name="attachment" id="attachmentInput" style="display:none" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                </div>
                                <div id="fileName" class="mt-2 small text-muted"></div>
                            </div>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="reset" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-danger" id="submitBtn">
                                <i class="fas fa-exclamation-triangle"></i> Report Damage
                            </button>
                            <a href="../dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Instructions</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-search text-primary"></i> <strong>Step 1:</strong> Search and select the damaged item</li>
                        <li class="mb-2"><i class="fas fa-microchip text-info"></i> <strong>Step 2:</strong> Select Serial Number, Model and Version if available</li>
                        <li class="mb-2"><i class="fas fa-undo-alt text-warning"></i> <strong>Step 3:</strong> If there's a related return request, select it</li>
                        <li class="mb-2"><i class="fas fa-pen text-success"></i> <strong>Step 4:</strong> Describe the damage in detail</li>
                        <li class="mb-2"><i class="fas fa-paperclip text-secondary"></i> <strong>Step 5:</strong> Upload supporting documents (photos, reports)</li>
                        <li class="mb-2"><i class="fas fa-print text-danger"></i> <strong>Step 6:</strong> Submit - System will generate damage report</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<?php if($show_success_modal): ?>
<div class="success-modal-overlay" id="successModal">
    <div class="success-modal-box">
        <div class="success-modal-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="success-modal-title">Damage Reported Successfully!</div>
        <div class="success-modal-subtitle">
            Damage record has been created and stock has been updated.
        </div>
        <div class="success-modal-details">
            <div class="detail-item">
                <span class="label">Damage Number</span>
                <span class="value"><?php echo htmlspecialchars($success_damage_no); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Item</span>
                <span class="value"><?php echo htmlspecialchars($success_item_name); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Status</span>
                <span class="value"><span class="badge bg-warning text-dark">Pending Review</span></span>
            </div>
        </div>
        <div class="success-modal-actions">
            <a href="list.php" class="btn btn-success-modal">
                <i class="fas fa-list me-2"></i> View All Damages
            </a>
            <a href="damage.php" class="btn btn-secondary-modal">
                <i class="fas fa-plus me-2"></i> Report Another
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Searchable Select Functionality
$(document).ready(function() {
    // Variables for searchable select
    var $searchInput = $('#itemSearchInput');
    var $dropdown = $('#itemDropdown');
    var $selectedItemId = $('#selectedItemId');
    var allOptions = [];
    
    // Store all options data
    $('.searchable-select-option').each(function() {
        allOptions.push({
            element: $(this),
            id: $(this).data('id'),
            name: $(this).data('name'),
            code: $(this).data('code'),
            brand: $(this).data('brand'),
            searchText: ($(this).data('name') + ' ' + $(this).data('code') + ' ' + ($(this).data('brand') || '')).toLowerCase()
        });
    });
    
    // Filter options based on search input
    function filterOptions(searchTerm) {
        var term = searchTerm.toLowerCase();
        var hasResults = false;
        
        $('.searchable-select-option').each(function() {
            var $opt = $(this);
            var text = ($opt.data('name') + ' ' + $opt.data('code') + ' ' + ($opt.data('brand') || '')).toLowerCase();
            
            if (term === '' || text.indexOf(term) > -1) {
                $opt.show();
                hasResults = true;
            } else {
                $opt.hide();
            }
        });
        
        // Show no results message if needed
        if (!hasResults && term !== '') {
            if ($('.searchable-select-no-results').length === 0) {
                $dropdown.append('<div class="searchable-select-no-results">No items found matching "' + escapeHtml(term) + '"</div>');
            } else {
                $('.searchable-select-no-results').show().text('No items found matching "' + escapeHtml(term) + '"');
            }
        } else {
            $('.searchable-select-no-results').remove();
        }
    }
    
    // Select an option
    function selectOption($option) {
        var id = $option.data('id');
        var name = $option.data('name');
        var code = $option.data('code');
        var brand = $option.data('brand');
        var available = $option.data('available');
        var total = $option.data('total');
        var assigned = $option.data('assigned');
        var serials = $option.data('serials');
        
        // Update hidden input
        $selectedItemId.val(id);
        
        // Update search input display
        $searchInput.val(name + ' (' + code + ')');
        
        // Update selected item info display
        $('#selectedItemName').text(name || 'N/A');
        $('#selectedItemCode').text(code || 'N/A');
        $('#selectedItemBrand').text(brand || 'N/A');
        $('#selectedItemStock').text(available + ' units');
        $('#selectedItemInfo').show();
        
        // Update quantity max and stock info
        $('#quantity').attr('max', available);
        $('#quantity').val(1);
        $('#stockInfo').html('Available: ' + available + ' | Total: ' + total + ' | Assigned: ' + assigned);
        
        // Highlight selected option
        $('.searchable-select-option').removeClass('selected');
        $option.addClass('selected');
        
        // Close dropdown
        $dropdown.removeClass('show');
        
        // Load item attributes
        loadItemAttributes(id);
        
        // Load return requests
        loadReturnRequestsByItem(id, null);
    }
    
    // Toggle dropdown
    $searchInput.on('click focus', function(e) {
        e.stopPropagation();
        filterOptions($searchInput.val());
        $dropdown.toggleClass('show');
    });
    
    // Handle input for filtering
    $searchInput.on('keyup', function() {
        filterOptions($(this).val());
        $dropdown.addClass('show');
    });
    
    // Handle option click
    $(document).on('click', '.searchable-select-option', function(e) {
        e.stopPropagation();
        selectOption($(this));
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#itemSearchContainer').length) {
            $dropdown.removeClass('show');
        }
    });
    
    // Handle keyboard navigation
    $searchInput.on('keydown', function(e) {
        var $visibleOptions = $('.searchable-select-option:visible');
        var $selected = $('.searchable-select-option.selected');
        var index = $visibleOptions.index($selected);
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (index < $visibleOptions.length - 1) {
                $visibleOptions.eq(index + 1).addClass('selected').siblings().removeClass('selected');
                $visibleOptions.eq(index + 1)[0].scrollIntoView({ block: 'nearest' });
            } else if ($visibleOptions.length > 0) {
                $visibleOptions.eq(0).addClass('selected').siblings().removeClass('selected');
                $visibleOptions.eq(0)[0].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (index > 0) {
                $visibleOptions.eq(index - 1).addClass('selected').siblings().removeClass('selected');
                $visibleOptions.eq(index - 1)[0].scrollIntoView({ block: 'nearest' });
            } else if ($visibleOptions.length > 0) {
                $visibleOptions.eq($visibleOptions.length - 1).addClass('selected').siblings().removeClass('selected');
                $visibleOptions.eq($visibleOptions.length - 1)[0].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            var $selectedOption = $('.searchable-select-option.selected:visible');
            if ($selectedOption.length) {
                selectOption($selectedOption);
            } else if ($visibleOptions.length === 1) {
                selectOption($visibleOptions.first());
            }
        } else if (e.key === 'Escape') {
            $dropdown.removeClass('show');
        }
    });
    
    // File upload handler
    $('#attachmentInput').on('change', function() {
        if(this.files && this.files[0]) {
            var fileName = this.files[0].name;
            var fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
            $('#fileName').html('<i class="fas fa-check-circle text-success"></i> ' + fileName + ' (' + fileSize + ' MB)');
        } else {
            $('#fileName').html('');
        }
    });
});

function loadItemAttributes(itemId) {
    $('#serialLoading').show();
    $('#serialSelect').prop('disabled', true);
    $('#modelSelect').prop('disabled', true);
    $('#versionSelect').prop('disabled', true);
    
    $.ajax({
        url: '../../modules/ajax/get_item_attributes.php',
        method: 'POST',
        data: { item_id: itemId },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            $('#serialLoading').hide();
            
            if (!response.success) {
                $('#serialModelVersionRow').hide();
                return;
            }
            
            var hasSerials = response.has_serials;
            var hasModels = response.has_models;
            var hasVersions = response.has_versions;
            
            if (hasSerials || hasModels || hasVersions) {
                $('#serialModelVersionRow').show();
            } else {
                $('#serialModelVersionRow').hide();
                return;
            }
            
            // Populate Serial Numbers
            if (hasSerials && response.serials && response.serials.length > 0) {
                $('#serialSelect').html('<option value="">-- Select Serial Number --</option>');
                $.each(response.serials, function(i, serial) {
                    $('#serialSelect').append('<option value="' + escapeHtml(serial.serial_number) + '" data-model="' + escapeHtml(serial.model_number || '') + '" data-version="' + escapeHtml(serial.version || '') + '">' + escapeHtml(serial.serial_number) + '</option>');
                });
                $('#serialSelect').prop('disabled', false);
            } else {
                $('#serialSelect').html('<option value="">No serial numbers available</option>');
                $('#serialSelect').prop('disabled', true);
            }
            
            // Populate Model Numbers
            if (hasModels && response.models && response.models.length > 0) {
                $('#modelSelect').html('<option value="">-- Select Model Number --</option>');
                $.each(response.models, function(i, model) {
                    $('#modelSelect').append('<option value="' + escapeHtml(model) + '">' + escapeHtml(model) + '</option>');
                });
                $('#modelSelect').prop('disabled', false);
            } else {
                $('#modelSelect').html('<option value="">No model numbers available</option>');
                $('#modelSelect').prop('disabled', true);
            }
            
            // Populate Versions
            if (hasVersions && response.versions && response.versions.length > 0) {
                $('#versionSelect').html('<option value="">-- Select Version --</option>');
                $.each(response.versions, function(i, version) {
                    $('#versionSelect').append('<option value="' + escapeHtml(version) + '">' + escapeHtml(version) + '</option>');
                });
                $('#versionSelect').prop('disabled', false);
            } else {
                $('#versionSelect').html('<option value="">No versions available</option>');
                $('#versionSelect').prop('disabled', true);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#serialLoading').hide();
            $('#serialModelVersionRow').hide();
        }
    });
}

function loadReturnRequestsByItem(itemId, serialNumber) {
    if (!itemId) {
        $('#returnListCard').hide();
        return;
    }
    
    $('#returnListContainer').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Loading return requests...</div>');
    $('#returnListCard').show();
    
    $.ajax({
        url: '../../modules/ajax/get_return_requests_by_item.php',
        method: 'POST',
        data: { item_id: itemId, serial_number: serialNumber || '' },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if (response.success && response.returns && response.returns.length > 0) {
                var html = '';
                $.each(response.returns, function(i, ret) {
                    var selectedClass = i === 0 && !$('#selectedReturnId').val() ? 'selected' : '';
                    var statusClass = '';
                    switch(ret.status) {
                        case 'pending': statusClass = 'badge-pending'; break;
                        case 'approved': statusClass = 'badge-approved'; break;
                        case 'processing': statusClass = 'badge-processing'; break;
                        case 'completed': statusClass = 'badge-completed'; break;
                        case 'rejected': statusClass = 'badge-rejected'; break;
                        default: statusClass = 'badge-secondary';
                    }
                    html += `
                        <div class="return-item ${selectedClass}" data-id="${ret.id}" onclick="selectReturn(${ret.id}, this)">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong>${escapeHtml(ret.request_no)}</strong>
                                <span class="badge ${statusClass}">${escapeHtml(ret.status)}</span>
                            </div>
                            <small><i class="fas fa-user"></i> ${escapeHtml(ret.employee_name)}</small><br>
                            <small><i class="fas fa-calendar"></i> ${new Date(ret.created_at).toLocaleDateString()}</small>
                            <small class="text-muted d-block mt-1"><i class="fas fa-comment"></i> ${escapeHtml(ret.return_reason)}</small>
                            ${ret.serial_number ? `<small><i class="fas fa-barcode"></i> Serial: ${escapeHtml(ret.serial_number)}</small><br>` : ''}
                            ${ret.model_number ? `<small><i class="fas fa-microchip"></i> Model: ${escapeHtml(ret.model_number)}</small>` : ''}
                        </div>
                    `;
                });
                $('#returnListContainer').html(html);
                
                if (!$('#selectedReturnId').val() && response.returns.length > 0) {
                    $('#selectedReturnId').val(response.returns[0].id);
                }
            } else {
                $('#returnListContainer').html('<div class="text-muted text-center py-3"><i class="fas fa-inbox"></i> No return requests found for this device.</div>');
                $('#selectedReturnId').val('');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#returnListContainer').html('<div class="text-muted text-center py-3"><i class="fas fa-exclamation-triangle"></i> Unable to load return requests. Please try again.</div>');
        }
    });
}

function selectReturn(returnId, element) {
    $('.return-item').removeClass('selected');
    $(element).addClass('selected');
    $('#selectedReturnId').val(returnId);
}

function resetForm() {
    $('#damageForm')[0].reset();
    $('#selectedItemInfo').hide();
    $('#serialModelVersionRow').hide();
    $('#returnListCard').hide();
    $('#fileName').html('');
    $('#selectedReturnId').val('');
    $('#stockInfo').html('');
    $('#selectedItemId').val('');
    $('#itemSearchInput').val('');
    $('.searchable-select-option').removeClass('selected');
    $('#serialSelect').html('<option value="">-- Select Serial Number --</option>');
    $('#modelSelect').html('<option value="">-- Select Model Number --</option>');
    $('#versionSelect').html('<option value="">-- Select Version --</option>');
    $('#quantity').attr('max', '');
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

// Form validation
$('#damageForm').on('submit', function(e) {
    var itemId = $('#selectedItemId').val();
    if(!itemId) {
        e.preventDefault();
        alert('Please select an item from the search list.');
        return false;
    }
    
    var quantity = parseInt($('#quantity').val());
    var maxQty = parseInt($('#quantity').attr('max'));
    
    if(quantity > maxQty) {
        e.preventDefault();
        alert('Quantity cannot exceed available stock (' + maxQty + ')');
        return false;
    }
    
    var submitBtn = $('#submitBtn');
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    submitBtn.prop('disabled', true);
});

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