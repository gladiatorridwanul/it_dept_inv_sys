<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Include barcode library (if installed)
$barcode_available = false;
if(file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
    if(class_exists('Picqer\Barcode\BarcodeGeneratorPNG')) {
        $barcode_available = true;
    }
}

// Fallback barcode generator using GD
if(!$barcode_available) {
    require_once '../../includes/simple_barcode.php';
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $item_ids = $_POST['item_ids'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $serial_numbers = $_POST['serial_numbers'] ?? [];
    $model_numbers = $_POST['model_numbers'] ?? [];
    $versions = $_POST['versions'] ?? [];
    $assigned_date = $_POST['assigned_date'];
    $expected_return_date = $_POST['expected_return_date'] ?: null;
    $notes = trim($_POST['notes'] ?? '');
    
    // Get employee details
    $stmt = $pdo->prepare("SELECT full_name, pf_no FROM employees WHERE id = ? AND is_active = 1");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    
    if(!$employee) {
        $error = "Employee not found or inactive!";
    } else {
        $assignment_count = 0;
        $errors = [];
        $assignment_numbers = [];
        
        $pdo->beginTransaction();
        
        try {
            foreach($item_ids as $index => $item_id) {
                if(empty($item_id)) continue;
                
                $quantity = (int)($quantities[$index] ?? 1);
                $serial_number = $serial_numbers[$index] ?? null;
                $model_number = $model_numbers[$index] ?? null;
                $version = $versions[$index] ?? null;
                
                // Check if specific serial is available and not assigned
                if($serial_number) {
                    $stmt = $pdo->prepare("
                        SELECT isn.*, i.name, i.item_code, i.current_qty
                        FROM item_serial_numbers isn
                        JOIN items i ON isn.item_id = i.id
                        WHERE isn.item_id = ? AND isn.serial_number = ? AND (isn.is_assigned = 0 OR isn.is_assigned IS NULL)
                    ");
                    $stmt->execute([$item_id, $serial_number]);
                    $serialItem = $stmt->fetch();
                    
                    if(!$serialItem) {
                        $errors[] = "Serial number $serial_number is already assigned or not available!";
                        continue;
                    }
                } else {
                    // Check stock availability for any unit
                    $stmt = $pdo->prepare("
                        SELECT i.*, COALESCE(SUM(a.quantity), 0) as assigned_qty
                        FROM items i
                        LEFT JOIN assignments a ON i.id = a.item_id AND a.status = 'assigned' AND a.return_status = 'active'
                        WHERE i.id = ? AND i.is_active = 1 AND i.current_qty > 0
                        GROUP BY i.id
                        HAVING i.current_qty > COALESCE(SUM(a.quantity), 0)
                    ");
                    $stmt->execute([$item_id]);
                    $item = $stmt->fetch();
                    
                    if(!$item) {
                        $errors[] = "Item ID $item_id not available or out of stock!";
                        continue;
                    }
                    
                    if($item['current_qty'] - ($item['assigned_qty'] ?? 0) < $quantity) {
                        $errors[] = "Insufficient stock for {$item['name']}!";
                        continue;
                    }
                }
                
                $assignment_no = generateNumber('ASN-', 'assignments', 'assignment_no');
                $assignment_numbers[] = $assignment_no;
                
                // Insert assignment
                $stmt = $pdo->prepare("INSERT INTO assignments (assignment_no, employee_id, item_id, quantity, assigned_date, 
                                      expected_return_date, notes, assigned_by, created_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$assignment_no, $employee_id, $item_id, $quantity, $assigned_date, 
                               $expected_return_date, $notes, $_SESSION['user_id']]);
                $assignment_id = $pdo->lastInsertId();
                
                // Update stock
                $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty - ? WHERE id = ?");
                $stmt->execute([$quantity, $item_id]);
                
                // Mark serial number as assigned if selected
                if($serial_number) {
                    $stmt = $pdo->prepare("
                        UPDATE item_serial_numbers 
                        SET is_assigned = 1, assigned_to = ?, assigned_date = ?, assignment_id = ?
                        WHERE item_id = ? AND serial_number = ?
                    ");
                    $stmt->execute([$employee_id, $assigned_date, $assignment_id, $item_id, $serial_number]);
                }
                
                // Generate barcode
                $barcodeDir = '../../uploads/barcodes/';
                if(!is_dir($barcodeDir)) mkdir($barcodeDir, 0777, true);
                $barcodeFile = 'barcode_' . $assignment_no . '.png';
                
                if($barcode_available) {
                    $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
                    $barcode = $generator->getBarcode($assignment_no, $generator::TYPE_CODE_128);
                    file_put_contents($barcodeDir . $barcodeFile, $barcode);
                } else {
                    $barcodeData = SimpleBarcode::generate($assignment_no);
                    file_put_contents($barcodeDir . $barcodeFile, $barcodeData);
                }
                
                // Save barcode path
                $stmt = $pdo->prepare("UPDATE assignments SET barcode_path = ? WHERE id = ?");
                $stmt->execute(['uploads/barcodes/' . $barcodeFile, $assignment_id]);
                
                $assignment_count++;
            }
            
            if(!empty($errors)) {
                throw new Exception(implode(", ", $errors));
            }
            
            $pdo->commit();
            
            $_SESSION['assignment_success'] = [
                'count' => $assignment_count,
                'employee' => $employee['full_name'],
                'assignment_numbers' => $assignment_numbers
            ];
            
            // Store success data for the modal
            $show_success_modal = true;
            $success_count = $assignment_count;
            $success_employee = $employee['full_name'];
            $success_numbers = $assignment_numbers;
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get items with their available serial numbers and versions
$items = $pdo->query("
    SELECT i.id, i.name, i.item_code, i.current_qty, i.brand, i.model_number, i.version,
           COALESCE(SUM(a.quantity), 0) as assigned_qty,
           (SELECT COUNT(*) FROM item_serial_numbers isn WHERE isn.item_id = i.id AND (isn.is_assigned = 0 OR isn.is_assigned IS NULL)) as available_serials
    FROM items i
    LEFT JOIN assignments a ON i.id = a.item_id AND a.status = 'assigned' AND a.return_status = 'active'
    WHERE i.is_active = 1 AND i.current_qty > 0
    GROUP BY i.id
    HAVING i.current_qty > COALESCE(SUM(a.quantity), 0)
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .info-card {
        background: #f8f9fa;
        border-left: 4px solid #28a745;
    }
    .search-container {
        position: relative;
    }
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 300px;
        overflow-y: auto;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        border-radius: 0 0 8px 8px;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .search-result-item {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        transition: background 0.2s;
    }
    .search-result-item:hover {
        background: #f0f8ff;
    }
    .search-result-item .main-text {
        font-weight: 600;
        font-size: 14px;
    }
    .search-result-item .sub-text {
        font-size: 12px;
        color: #666;
        margin-top: 3px;
    }
    .selected-item-display {
        background: #e8f5e9;
        border: 1px solid #4caf50;
        border-radius: 8px;
        padding: 12px 15px;
        margin-top: 10px;
        display: none;
    }
    .clear-selection {
        cursor: pointer;
        color: #dc3545;
        font-size: 12px;
    }
    .clear-selection:hover {
        text-decoration: underline;
    }
    .device-item {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #e2e8f0;
        position: relative;
    }
    .device-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e2e8f0;
    }
    .device-number {
        font-weight: 700;
        color: #667eea;
    }
    .remove-device {
        color: #dc3545;
        cursor: pointer;
        font-size: 14px;
    }
    .remove-device:hover {
        text-decoration: underline;
    }
    .add-device-btn {
        margin-top: 15px;
        width: 100%;
    }
    .serial-model-version-row {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        padding: 15px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        display: none;
    }
    .serial-model-version-row .form-group {
        flex: 1;
    }
    .info-badge {
        background: #e2e8f0;
        border-radius: 20px;
        padding: 2px 8px;
        font-size: 11px;
        color: #475569;
        margin-left: 8px;
    }
    .attr-badge {
        background: #e0e7ff;
        color: #4338ca;
        border-radius: 12px;
        padding: 2px 8px;
        font-size: 11px;
        margin-left: 6px;
    }
    
    /* Success Modal Styles */
    .success-modal-overlay {
        display: <?php echo isset($show_success_modal) && $show_success_modal ? 'flex' : 'none'; ?>;
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
        color: #28a745;
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
    }
    .btn-success-modal {
        background: #28a745;
        color: white;
        border: none;
        text-decoration: none;
    }
    .btn-success-modal:hover {
        background: #218838;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(40,167,69,0.3);
    }
    .btn-secondary-modal {
        background: #e2e8f0;
        color: #4a5568;
        border: none;
        text-decoration: none;
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
                    <h2><i class="fas fa-laptop text-primary"></i> Assign Device to Employee</h2>
                    <p class="text-muted">Search and assign IT equipment to employees with barcode tracking</p>
                </div>
                <div>
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-list"></i> Assignment List
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Assignment Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="assignmentForm">
                        <!-- Employee Search Section -->
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label class="form-label required-field">Search Employee</label>
                                <div class="search-container">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" id="employeeSearch" class="form-control" 
                                               placeholder="Type PF No. or Name to search..." autocomplete="off">
                                        <span class="input-group-text" id="clearEmployee" style="cursor: pointer; display: none;">
                                            <i class="fas fa-times-circle text-danger"></i>
                                        </span>
                                    </div>
                                    <div id="employeeResults" class="search-results"></div>
                                </div>
                                <input type="hidden" name="employee_id" id="employee_id" required>
                                <div id="selectedEmployeeDisplay" class="selected-item-display">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><i class="fas fa-user-check text-success"></i> Selected Employee:</strong>
                                            <span id="selectedEmployeeName"></span>
                                            <br>
                                            <small><span id="selectedEmployeePF"></span></small>
                                        </div>
                                        <span class="clear-selection" onclick="clearEmployeeSelection()">
                                            <i class="fas fa-times"></i> Change
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Multiple Devices Section -->
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label required-field">Devices to Assign</label>
                                <div id="devicesContainer">
                                    <div class="device-item" data-device-index="0">
                                        <div class="device-header">
                                            <span class="device-number">Device #1</span>
                                            <span class="remove-device" style="display:none;" onclick="removeDevice(0)">Remove</span>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 mb-2">
                                                <label class="form-label">Search Device/Item</label>
                                                <div class="search-container">
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-laptop"></i></span>
                                                        <input type="text" class="form-control item-search-input" 
                                                               placeholder="Type item name or code to search..." autocomplete="off"
                                                               data-device-index="0">
                                                    </div>
                                                    <div class="search-results item-results" data-device-index="0"></div>
                                                </div>
                                                <input type="hidden" name="item_ids[]" class="item-id-input" data-device-index="0">
                                                <input type="hidden" name="quantities[]" class="quantity-input" value="1">
                                            </div>
                                        </div>
                                        <!-- Serial, Model, Version Fields Row -->
                                        <div class="serial-model-version-row" id="serialModelVersionRow_0" style="display: none;">
                                            <div class="form-group">
                                                <label class="form-label">Serial Number <span class="text-danger serial-required" style="display: none;">*</span></label>
                                                <select name="serial_numbers[]" class="form-control serial-select" data-device-index="0">
                                                    <option value="">-- Select Serial Number --</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Model Number</label>
                                                <select name="model_numbers[]" class="form-control model-select" data-device-index="0">
                                                    <option value="">-- Select Model Number --</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Version</label>
                                                <select name="versions[]" class="form-control version-select" data-device-index="0">
                                                    <option value="">-- Select Version --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="selected-item-display" data-device-index="0" style="display: none;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><i class="fas fa-check-circle text-success"></i> Selected Device:</strong>
                                                    <span class="selected-item-name"></span>
                                                    <span class="attr-badge" id="attrBadge_0" style="display: none;"><i class="fas fa-microchip"></i> Has details</span>
                                                    <br>
                                                    <small>
                                                        <span class="selected-item-code"></span> | 
                                                        Stock: <span class="selected-item-stock"></span> | 
                                                        Brand: <span class="selected-item-brand"></span>
                                                    </small>
                                                </div>
                                                <span class="clear-selection" onclick="clearItemSelection(0)">
                                                    <i class="fas fa-times"></i> Change
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success btn-sm add-device-btn" id="addDeviceBtn">
                                    <i class="fas fa-plus me-1"></i> Add Another Device
                                </button>
                            </div>
                        </div>
                        
                        <!-- Dates -->
                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Assigned Date</label>
                                <input type="date" name="assigned_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expected Return Date</label>
                                <input type="date" name="expected_return_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                                <small class="text-muted">Optional - leave empty if not applicable</small>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Notes / Remarks</label>
                                <textarea name="notes" rows="3" class="form-control" placeholder="Any additional information about this assignment..."></textarea>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                <i class="fas fa-check-circle"></i> Assign & Generate Documents
                            </button>
                            <a href="list.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm info-card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> How to Assign</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-search text-primary"></i> <strong>Step 1:</strong> Search employee by PF No. or Name</li>
                        <li class="mb-2"><i class="fas fa-search text-primary"></i> <strong>Step 2:</strong> Search device by name or code</li>
                        <li class="mb-2"><i class="fas fa-microchip text-info"></i> <strong>Step 3:</strong> Select Serial Number, Model Number and Version if available</li>
                        <li class="mb-2"><i class="fas fa-ban text-warning"></i> <strong>Note:</strong> Only unassigned serial numbers are shown</li>
                        <li class="mb-2"><i class="fas fa-plus-circle text-success"></i> <strong>Step 4:</strong> Click "Add Another Device" to assign multiple devices</li>
                        <li class="mb-2"><i class="fas fa-print text-info"></i> <strong>Step 5:</strong> Submit - Auto generates:</li>
                        <li class="ms-3"><i class="fas fa-receipt"></i> Assignment Acknowledgement</li>
                        <li class="ms-3"><i class="fas fa-qrcode"></i> Barcode Label for each device</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<?php if(isset($show_success_modal) && $show_success_modal): ?>
<div class="success-modal-overlay" id="successModal">
    <div class="success-modal-box">
        <div class="success-modal-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="success-modal-title">Assignment Successful!</div>
        <div class="success-modal-subtitle">
            <?php echo $success_count; ?> device(s) successfully assigned to <?php echo htmlspecialchars($success_employee); ?>
        </div>
        <div class="success-modal-details">
            <div class="detail-item">
                <span class="label">Employee</span>
                <span class="value"><?php echo htmlspecialchars($success_employee); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Devices Assigned</span>
                <span class="value"><?php echo $success_count; ?></span>
            </div>
            <?php if(!empty($success_numbers)): ?>
            <div class="detail-item">
                <span class="label">Assignment Numbers</span>
                <span class="value"><?php echo implode(', ', array_map('htmlspecialchars', $success_numbers)); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <div class="success-modal-actions">
            <a href="list.php" class="btn btn-success-modal">
                <i class="fas fa-list me-2"></i> View All Assignments
            </a>
            <a href="assign.php" class="btn btn-secondary-modal">
                <i class="fas fa-plus me-2"></i> Assign Another
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Employee Search Data
const employees = <?php 
    $empStmt = $pdo->query("SELECT id, pf_no, full_name, designation, department FROM employees WHERE is_active = 1 ORDER BY full_name");
    echo json_encode($empStmt->fetchAll());
?>;

// Available Items Data
const availableItems = <?php echo json_encode($items); ?>;
let selectedEmployee = null;
let deviceCounter = 1;
let selectedItems = {};
let itemAttributes = {};

// Load serial numbers, model numbers, and versions for an item
function loadItemAttributes(itemId, deviceIndex) {
    console.log("Loading attributes for item:", itemId, "deviceIndex:", deviceIndex);
    
    const row = document.getElementById(`serialModelVersionRow_${deviceIndex}`);
    const serialSelect = document.querySelector(`.serial-select[data-device-index="${deviceIndex}"]`);
    const modelSelect = document.querySelector(`.model-select[data-device-index="${deviceIndex}"]`);
    const versionSelect = document.querySelector(`.version-select[data-device-index="${deviceIndex}"]`);
    const attrBadge = document.getElementById(`attrBadge_${deviceIndex}`);
    
    // Clear previous selections
    if (serialSelect) serialSelect.innerHTML = '<option value="">-- Select Serial Number --</option>';
    if (modelSelect) modelSelect.innerHTML = '<option value="">-- Select Model Number --</option>';
    if (versionSelect) versionSelect.innerHTML = '<option value="">-- Select Version --</option>';
    
    // Show row
    if (row) row.style.display = 'flex';
    
    // Show loading text temporarily
    if (serialSelect) serialSelect.innerHTML = '<option value="">Loading serials...</option>';
    if (modelSelect) modelSelect.innerHTML = '<option value="">Loading models...</option>';
    if (versionSelect) versionSelect.innerHTML = '<option value="">Loading versions...</option>';
    
    $.ajax({
        url: '/modules/ajax/get_item_attributes.php',
        method: 'POST',
        data: { item_id: itemId },
        dataType: 'json',
        timeout: 15000,
        success: function(response) {
            console.log("AJAX Response:", response);
            
            if (!response.success) {
                console.error("Response error:", response.error);
                if (serialSelect) serialSelect.innerHTML = '<option value="">Error loading serials</option>';
                if (modelSelect) modelSelect.innerHTML = '<option value="">Error loading models</option>';
                if (versionSelect) versionSelect.innerHTML = '<option value="">Error loading versions</option>';
                return;
            }
            
            // Store attributes
            itemAttributes[deviceIndex] = {
                hasSerials: response.has_serials,
                hasModels: response.has_models,
                hasVersions: response.has_versions,
                serials: response.serials || [],
                models: response.models || [],
                versions: response.versions || []
            };
            
            const hasSerials = response.has_serials;
            const hasModels = response.has_models;
            const hasVersions = response.has_versions;
            
            console.log("Has Serials:", hasSerials, "Count:", response.serials.length);
            
            // Update badge
            if (attrBadge) {
                if (hasSerials || hasModels || hasVersions) {
                    attrBadge.style.display = 'inline-block';
                } else {
                    attrBadge.style.display = 'none';
                }
            }
            
            // If no attributes at all, hide the row
            if (!hasSerials && !hasModels && !hasVersions) {
                if (row) row.style.display = 'none';
                return;
            }
            
            // Ensure row is visible
            if (row) row.style.display = 'flex';
            
            // Populate Serial Numbers
            if (hasSerials && serialSelect) {
                if (response.serials && response.serials.length > 0) {
                    serialSelect.innerHTML = '<option value="">-- Select Serial Number --</option>';
                    $.each(response.serials, function(i, serial) {
                        serialSelect.innerHTML += `<option value="${escapeHtml(serial.serial_number)}" data-model="${escapeHtml(serial.model_number || '')}" data-version="${escapeHtml(serial.version || '')}">${escapeHtml(serial.serial_number)}</option>`;
                    });
                } else {
                    serialSelect.innerHTML = '<option value="">No serial numbers available</option>';
                }
                serialSelect.disabled = false;
                const serialRequired = row ? row.querySelector('.serial-required') : null;
                if (serialRequired) serialRequired.style.display = 'inline';
            } else if (serialSelect) {
                serialSelect.innerHTML = '<option value="">No serial numbers available</option>';
                serialSelect.disabled = true;
            }
            
            // Populate Model Numbers
            if (hasModels && modelSelect) {
                if (response.models && response.models.length > 0) {
                    modelSelect.innerHTML = '<option value="">-- Select Model Number --</option>';
                    $.each(response.models, function(i, model) {
                        modelSelect.innerHTML += `<option value="${escapeHtml(model)}">${escapeHtml(model)}</option>`;
                    });
                } else {
                    modelSelect.innerHTML = '<option value="">No model numbers available</option>';
                }
                modelSelect.disabled = false;
            } else if (modelSelect) {
                modelSelect.innerHTML = '<option value="">No model numbers available</option>';
                modelSelect.disabled = true;
            }
            
            // Populate Versions
            if (hasVersions && versionSelect) {
                if (response.versions && response.versions.length > 0) {
                    versionSelect.innerHTML = '<option value="">-- Select Version --</option>';
                    $.each(response.versions, function(i, version) {
                        versionSelect.innerHTML += `<option value="${escapeHtml(version)}">${escapeHtml(version)}</option>`;
                    });
                } else {
                    versionSelect.innerHTML = '<option value="">No versions available</option>';
                }
                versionSelect.disabled = false;
            } else if (versionSelect) {
                versionSelect.innerHTML = '<option value="">No versions available</option>';
                versionSelect.disabled = true;
            }
            
            // Auto-fill model and version when serial is selected
            if (serialSelect) {
                serialSelect.onchange = function() {
                    const selectedOption = serialSelect.options[serialSelect.selectedIndex];
                    if (selectedOption && selectedOption.value) {
                        const modelFromSerial = selectedOption.getAttribute('data-model');
                        const versionFromSerial = selectedOption.getAttribute('data-version');
                        
                        if (modelFromSerial && modelSelect) {
                            for (let i = 0; i < modelSelect.options.length; i++) {
                                if (modelSelect.options[i].value === modelFromSerial) {
                                    modelSelect.value = modelFromSerial;
                                    break;
                                }
                            }
                        }
                        if (versionFromSerial && versionSelect) {
                            for (let i = 0; i < versionSelect.options.length; i++) {
                                if (versionSelect.options[i].value === versionFromSerial) {
                                    versionSelect.value = versionFromSerial;
                                    break;
                                }
                            }
                        }
                    }
                };
            }
            
            checkFormComplete();
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error - Status:", status);
            console.error("AJAX Error - Error:", error);
            console.error("AJAX Error - URL:", '/modules/ajax/get_item_attributes.php');
            
            if (serialSelect) serialSelect.innerHTML = '<option value="">Error loading serials - Check console</option>';
            if (modelSelect) modelSelect.innerHTML = '<option value="">Error loading models</option>';
            if (versionSelect) versionSelect.innerHTML = '<option value="">Error loading versions</option>';
            if (attrBadge) attrBadge.style.display = 'none';
        }
    });
}

// DOM Elements
const employeeSearch = document.getElementById('employeeSearch');
const employeeResults = document.getElementById('employeeResults');
const employeeIdInput = document.getElementById('employee_id');
const selectedEmployeeDisplay = document.getElementById('selectedEmployeeDisplay');
const selectedEmployeeName = document.getElementById('selectedEmployeeName');
const selectedEmployeePF = document.getElementById('selectedEmployeePF');
const clearEmployeeBtn = document.getElementById('clearEmployee');

// Item Search Functions
function searchItems(query, deviceIndex) {
    if(!query || query.length < 1) {
        return [];
    }
    
    const lowerQuery = query.toLowerCase();
    return availableItems.filter(item => 
        item.name.toLowerCase().includes(lowerQuery) || 
        item.item_code.toLowerCase().includes(lowerQuery)
    );
}

function renderItemResults(results, deviceIndex) {
    const itemResults = document.querySelector(`.item-results[data-device-index="${deviceIndex}"]`);
    if(!itemResults) return;
    
    if(results.length === 0) {
        itemResults.innerHTML = '<div class="search-result-item text-muted">No available items found.</div>';
        itemResults.style.display = 'block';
        return;
    }
    
    itemResults.innerHTML = results.map(item => {
        const availableStock = item.current_qty - (item.assigned_qty || 0);
        const hasAttrs = (item.available_serials > 0);
        const attrBadge = hasAttrs ? '<span class="info-badge"><i class="fas fa-microchip"></i> Has serials</span>' : '<span class="info-badge"><i class="fas fa-box"></i> No details</span>';
        return `
            <div class="search-result-item" onclick="selectItem(${item.id}, '${escapeHtml(item.name)}', '${escapeHtml(item.item_code)}', ${availableStock}, '${escapeHtml(item.brand || 'N/A')}', ${deviceIndex})">
                <div class="main-text">
                    <strong>${escapeHtml(item.name)}</strong> (${escapeHtml(item.item_code)}) ${attrBadge}
                </div>
                <div class="sub-text">
                    Available: ${availableStock} | Brand: ${escapeHtml(item.brand || 'N/A')}
                    ${item.available_serials > 0 ? ` | Available Serials: ${item.available_serials}` : ''}
                </div>
            </div>
        `;
    }).join('');
    itemResults.style.display = 'block';
}

function selectItem(id, name, code, stock, brand, deviceIndex) {
    console.log("Selecting item:", id, name);
    selectedItems[deviceIndex] = { id, name, code, stock, brand };
    
    const itemIdInput = document.querySelector(`.item-id-input[data-device-index="${deviceIndex}"]`);
    const itemSearchInput = document.querySelector(`.item-search-input[data-device-index="${deviceIndex}"]`);
    const selectedDisplay = document.querySelector(`.selected-item-display[data-device-index="${deviceIndex}"]`);
    const selectedName = selectedDisplay.querySelector('.selected-item-name');
    const selectedCode = selectedDisplay.querySelector('.selected-item-code');
    const selectedStock = selectedDisplay.querySelector('.selected-item-stock');
    const selectedBrand = selectedDisplay.querySelector('.selected-item-brand');
    const itemResults = document.querySelector(`.item-results[data-device-index="${deviceIndex}"]`);
    
    itemIdInput.value = id;
    itemSearchInput.value = `${name} (${code})`;
    selectedName.innerHTML = name;
    selectedCode.innerHTML = `<i class="fas fa-barcode"></i> Code: ${code}`;
    selectedStock.innerHTML = `<i class="fas fa-boxes"></i> Available: ${stock}`;
    selectedBrand.innerHTML = `<i class="fas fa-tag"></i> Brand: ${brand}`;
    selectedDisplay.style.display = 'block';
    if(itemResults) itemResults.style.display = 'none';
    
    // Load attributes
    loadItemAttributes(id, deviceIndex);
    
    checkFormComplete();
}

function clearItemSelection(deviceIndex) {
    delete selectedItems[deviceIndex];
    delete itemAttributes[deviceIndex];
    
    const itemIdInput = document.querySelector(`.item-id-input[data-device-index="${deviceIndex}"]`);
    const itemSearchInput = document.querySelector(`.item-search-input[data-device-index="${deviceIndex}"]`);
    const selectedDisplay = document.querySelector(`.selected-item-display[data-device-index="${deviceIndex}"]`);
    const row = document.getElementById(`serialModelVersionRow_${deviceIndex}`);
    const attrBadge = document.getElementById(`attrBadge_${deviceIndex}`);
    
    if(itemIdInput) itemIdInput.value = '';
    if(itemSearchInput) itemSearchInput.value = '';
    if(selectedDisplay) selectedDisplay.style.display = 'none';
    if(row) row.style.display = 'none';
    if(attrBadge) attrBadge.style.display = 'none';
    
    checkFormComplete();
}

function checkFormComplete() {
    const submitBtn = document.getElementById('submitBtn');
    const hasEmployee = selectedEmployee !== null;
    const hasDevices = Object.keys(selectedItems).length > 0;
    
    if(hasEmployee && hasDevices) {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
    } else {
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.6';
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

// Employee Search Functions
function searchEmployees(query) {
    if(!query || query.length < 1) {
        employeeResults.style.display = 'none';
        return [];
    }
    
    query = query.toLowerCase();
    return employees.filter(emp => 
        emp.pf_no.toLowerCase().includes(query) || 
        emp.full_name.toLowerCase().includes(query)
    );
}

function renderEmployeeResults(results) {
    if(results.length === 0) {
        employeeResults.innerHTML = '<div class="search-result-item text-muted">No employees found</div>';
        employeeResults.style.display = 'block';
        return;
    }
    
    employeeResults.innerHTML = results.map(emp => `
        <div class="search-result-item" onclick="selectEmployee(${emp.id}, '${escapeHtml(emp.full_name)}', '${escapeHtml(emp.pf_no)}', '${escapeHtml(emp.designation || '')}', '${escapeHtml(emp.department || '')}')">
            <div class="main-text">
                <strong>${escapeHtml(emp.pf_no)}</strong> - ${escapeHtml(emp.full_name)}
            </div>
            <div class="sub-text">
                ${escapeHtml(emp.designation || 'No designation')} | ${escapeHtml(emp.department || 'No department')}
            </div>
        </div>
    `).join('');
    employeeResults.style.display = 'block';
}

function selectEmployee(id, name, pf, designation, department) {
    selectedEmployee = { id, name, pf, designation, department };
    employeeIdInput.value = id;
    employeeSearch.value = `${pf} - ${name}`;
    selectedEmployeeName.innerHTML = `${name} <span class="badge bg-secondary">${designation || 'N/A'}</span>`;
    selectedEmployeePF.innerHTML = `<i class="fas fa-id-card"></i> ${pf} | <i class="fas fa-building"></i> ${department || 'N/A'}`;
    selectedEmployeeDisplay.style.display = 'block';
    employeeResults.style.display = 'none';
    clearEmployeeBtn.style.display = 'flex';
    
    checkFormComplete();
}

function clearEmployeeSelection() {
    selectedEmployee = null;
    employeeIdInput.value = '';
    employeeSearch.value = '';
    selectedEmployeeDisplay.style.display = 'none';
    clearEmployeeBtn.style.display = 'none';
    employeeSearch.focus();
    checkFormComplete();
}

// Add new device row
function addDeviceRow() {
    const newIndex = deviceCounter;
    const newRow = `
        <div class="device-item" data-device-index="${newIndex}">
            <div class="device-header">
                <span class="device-number">Device #${deviceCounter + 1}</span>
                <span class="remove-device" onclick="removeDevice(${newIndex})">Remove</span>
            </div>
            <div class="row">
                <div class="col-md-12 mb-2">
                    <label class="form-label">Search Device/Item</label>
                    <div class="search-container">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-laptop"></i></span>
                            <input type="text" class="form-control item-search-input" 
                                   placeholder="Type item name or code to search..." autocomplete="off"
                                   data-device-index="${newIndex}">
                        </div>
                        <div class="search-results item-results" data-device-index="${newIndex}"></div>
                    </div>
                    <input type="hidden" name="item_ids[]" class="item-id-input" data-device-index="${newIndex}">
                    <input type="hidden" name="quantities[]" class="quantity-input" value="1">
                </div>
            </div>
            <div class="serial-model-version-row" id="serialModelVersionRow_${newIndex}" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Serial Number <span class="text-danger serial-required" style="display: none;">*</span></label>
                    <select name="serial_numbers[]" class="form-control serial-select" data-device-index="${newIndex}">
                        <option value="">-- Select Serial Number --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Model Number</label>
                    <select name="model_numbers[]" class="form-control model-select" data-device-index="${newIndex}">
                        <option value="">-- Select Model Number --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Version</label>
                    <select name="versions[]" class="form-control version-select" data-device-index="${newIndex}">
                        <option value="">-- Select Version --</option>
                    </select>
                </div>
            </div>
            <div class="selected-item-display" data-device-index="${newIndex}" style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong><i class="fas fa-check-circle text-success"></i> Selected Device:</strong>
                        <span class="selected-item-name"></span>
                        <span class="attr-badge" id="attrBadge_${newIndex}" style="display: none;"><i class="fas fa-microchip"></i> Has details</span>
                        <br>
                        <small>
                            <span class="selected-item-code"></span> | 
                            Stock: <span class="selected-item-stock"></span> | 
                            Brand: <span class="selected-item-brand"></span>
                        </small>
                    </div>
                    <span class="clear-selection" onclick="clearItemSelection(${newIndex})">
                        <i class="fas fa-times"></i> Change
                    </span>
                </div>
            </div>
        </div>
    `;
    
    $('#devicesContainer').append(newRow);
    deviceCounter++;
    initializeDeviceSearch(newIndex);
}

function removeDevice(deviceIndex) {
    if($('.device-item').length > 1) {
        $(`.device-item[data-device-index="${deviceIndex}"]`).remove();
        delete selectedItems[deviceIndex];
        delete itemAttributes[deviceIndex];
        // Renumber remaining devices
        $('.device-item').each(function(i) {
            $(this).attr('data-device-index', i);
            $(this).find('.device-number').text(`Device #${i + 1}`);
            $(this).find('.item-search-input').attr('data-device-index', i);
            $(this).find('.item-results').attr('data-device-index', i);
            $(this).find('.item-id-input').attr('data-device-index', i);
            $(this).find('.selected-item-display').attr('data-device-index', i);
            $(this).find('.serial-select').attr('data-device-index', i);
            $(this).find('.model-select').attr('data-device-index', i);
            $(this).find('.version-select').attr('data-device-index', i);
            $(this).find('.serial-model-version-row').attr('id', `serialModelVersionRow_${i}`);
            $(this).find('.attr-badge').attr('id', `attrBadge_${i}`);
        });
        deviceCounter = $('.device-item').length;
        checkFormComplete();
    } else {
        alert('At least one device is required!');
    }
}

function initializeDeviceSearch(deviceIndex) {
    const searchInput = document.querySelector(`.item-search-input[data-device-index="${deviceIndex}"]`);
    
    if(!searchInput) return;
    
    let timeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value;
        timeout = setTimeout(() => {
            const results = searchItems(query, deviceIndex);
            renderItemResults(results, deviceIndex);
        }, 300);
    });
    
    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        const resultsDiv = document.querySelector(`.item-results[data-device-index="${deviceIndex}"]`);
        if(!searchInput.contains(e.target) && resultsDiv && !resultsDiv.contains(e.target)) {
            if(resultsDiv) resultsDiv.style.display = 'none';
        }
    });
}

// Employee search timeout
let employeeTimeout;
employeeSearch.addEventListener('input', function() {
    clearTimeout(employeeTimeout);
    const query = this.value;
    employeeTimeout = setTimeout(() => {
        const results = searchEmployees(query);
        renderEmployeeResults(results);
    }, 300);
});

// Close employee results when clicking outside
document.addEventListener('click', function(e) {
    if(!employeeSearch.contains(e.target) && !employeeResults.contains(e.target)) {
        employeeResults.style.display = 'none';
    }
});

// Add device button
document.getElementById('addDeviceBtn').addEventListener('click', addDeviceRow);

// Initialize first device row search
initializeDeviceSearch(0);

function resetForm() {
    clearEmployeeSelection();
    // Clear all devices except first
    $('.device-item:not(:first)').remove();
    // Reset first device
    clearItemSelection(0);
    document.querySelector('input[name="assigned_date"]').value = new Date().toISOString().split('T')[0];
    const nextYear = new Date();
    nextYear.setFullYear(nextYear.getFullYear() + 1);
    document.querySelector('input[name="expected_return_date"]').value = nextYear.toISOString().split('T')[0];
    document.querySelector('textarea[name="notes"]').value = '';
    deviceCounter = 1;
    selectedItems = {};
    itemAttributes = {};
    checkFormComplete();
}

document.getElementById('assignmentForm').addEventListener('submit', function(e) {
    if(!selectedEmployee) {
        e.preventDefault();
        alert('Please select an employee');
        employeeSearch.focus();
        return false;
    }
    
    const hasDevices = Object.keys(selectedItems).length > 0;
    if(!hasDevices) {
        e.preventDefault();
        alert('Please select at least one device');
        return false;
    }
    
    // Validate serial selection for devices that have serials available
    for (let i = 0; i < deviceCounter; i++) {
        const attrRow = document.getElementById(`serialModelVersionRow_${i}`);
        if (attrRow && attrRow.style.display !== 'none') {
            const serialSelect = document.querySelector(`.serial-select[data-device-index="${i}"]`);
            const attrs = itemAttributes[i];
            
            if (attrs && attrs.hasSerials && serialSelect && (!serialSelect.value || serialSelect.value === '')) {
                e.preventDefault();
                alert(`Please select a Serial Number for Device #${i + 1}`);
                if (serialSelect) serialSelect.focus();
                return false;
            }
        }
    }
    
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitBtn.disabled = true;
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