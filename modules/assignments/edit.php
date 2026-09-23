<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Get assignment ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id <= 0) {
    header("Location: list.php");
    exit();
}

// Get assignment details with serial info - Fixed query without assignment_id in JOIN
$stmt = $pdo->prepare("
    SELECT a.*, e.full_name as employee_name, e.pf_no, e.designation, e.department, 
           i.name as item_name, i.item_code, i.current_qty as item_stock, i.brand, i.model_number,
           i.serial_number as item_serial
    FROM assignments a 
    JOIN employees e ON a.employee_id = e.id 
    JOIN items i ON a.item_id = i.id 
    WHERE a.id = ?
");
$stmt->execute([$id]);
$assignment = $stmt->fetch();

if(!$assignment) {
    header("Location: list.php");
    exit();
}

// Get the assigned serial from item_serial_numbers table separately
$assigned_serial = null;
$assigned_model = null;
$stmt = $pdo->prepare("
    SELECT serial_number, model_number 
    FROM item_serial_numbers 
    WHERE item_id = ? AND is_assigned = 1 AND assigned_to = ?
    LIMIT 1
");
$stmt->execute([$assignment['item_id'], $assignment['employee_id']]);
$serial_info = $stmt->fetch();
if ($serial_info) {
    $assigned_serial = $serial_info['serial_number'];
    $assigned_model = $serial_info['model_number'];
}

// Get original quantity for stock calculation
$original_quantity = $assignment['quantity'];

// Include barcode library
$barcode_available = false;
if(file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
    if(class_exists('Picqer\\Barcode\\BarcodeGeneratorPNG')) {
        $barcode_available = true;
    }
}

if(!$barcode_available && file_exists('../../includes/simple_barcode.php')) {
    require_once '../../includes/simple_barcode.php';
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $item_id = $_POST['item_id'];
    $quantity = (int)$_POST['quantity'];
    $serial_number = $_POST['serial_number'] ?? null;
    $model_number = $_POST['model_number'] ?? null;
    $assigned_date = $_POST['assigned_date'];
    $expected_return_date = $_POST['expected_return_date'] ?: null;
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    
    $error = null;
    
    $pdo->beginTransaction();
    
    try {
        // If serial number changed, update serial assignments
        if ($serial_number && $serial_number != $assigned_serial) {
            // Free up old serial if existed
            if ($assigned_serial) {
                $stmt = $pdo->prepare("
                    UPDATE item_serial_numbers 
                    SET is_assigned = 0, assigned_to = NULL, assigned_date = NULL
                    WHERE serial_number = ? AND item_id = ?
                ");
                $stmt->execute([$assigned_serial, $assignment['item_id']]);
            }
            
            // Assign new serial
            $stmt = $pdo->prepare("
                UPDATE item_serial_numbers 
                SET is_assigned = 1, assigned_to = ?, assigned_date = ?
                WHERE item_id = ? AND serial_number = ? AND is_assigned = 0
            ");
            $stmt->execute([$employee_id, $assigned_date, $item_id, $serial_number]);
            
            if ($stmt->rowCount() == 0) {
                throw new Exception("Selected serial number is not available!");
            }
            
            // Update model if provided
            if ($model_number) {
                $stmt = $pdo->prepare("
                    UPDATE item_serial_numbers 
                    SET model_number = ?
                    WHERE serial_number = ? AND item_id = ?
                ");
                $stmt->execute([$model_number, $serial_number, $item_id]);
            }
        }
        
        // Update assignment
        $stmt = $pdo->prepare("UPDATE assignments SET 
                               employee_id = ?, 
                               item_id = ?, 
                               quantity = ?, 
                               assigned_date = ?, 
                               expected_return_date = ?, 
                               status = ?,
                               notes = ?
                               WHERE id = ?");
        $stmt->execute([$employee_id, $item_id, $quantity, $assigned_date, 
                       $expected_return_date, $status, $notes, $id]);
        
        // Adjust stock
        if($item_id != $assignment['item_id']) {
            // Return old item stock
            $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty + ? WHERE id = ?");
            $stmt->execute([$original_quantity, $assignment['item_id']]);
            
            // Deduct new item stock
            $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty - ? WHERE id = ?");
            $stmt->execute([$quantity, $item_id]);
        } else {
            // Same item - adjust quantity difference
            $stock_adjustment = $original_quantity - $quantity;
            if($stock_adjustment > 0) {
                $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty + ? WHERE id = ?");
                $stmt->execute([$stock_adjustment, $item_id]);
            } elseif($stock_adjustment < 0) {
                $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty - ? WHERE id = ?");
                $stmt->execute([abs($stock_adjustment), $item_id]);
            }
        }
        
        // Regenerate barcode if status is assigned
        if($status == 'assigned') {
            $barcodeDir = '../../uploads/barcodes/';
            if(!is_dir($barcodeDir)) mkdir($barcodeDir, 0777, true);
            $barcodeFile = 'barcode_' . $assignment['assignment_no'] . '.png';
            
            if($barcode_available) {
                $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
                $barcode = $generator->getBarcode($assignment['assignment_no'], $generator::TYPE_CODE_128);
                file_put_contents($barcodeDir . $barcodeFile, $barcode);
            } elseif(function_exists('SimpleBarcode')) {
                $barcodeData = SimpleBarcode::generate($assignment['assignment_no']);
                file_put_contents($barcodeDir . $barcodeFile, $barcodeData);
            }
            
            $stmt = $pdo->prepare("UPDATE assignments SET barcode_path = ? WHERE id = ?");
            $stmt->execute(['uploads/barcodes/' . $barcodeFile, $id]);
        }
        
        $pdo->commit();
        
        $_SESSION['assignment_updated'] = [
            'id' => $id,
            'no' => $assignment['assignment_no']
        ];
        
        header("Location: list.php?updated=1&id=" . $id);
        exit();
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Get items for selection
$items = $pdo->query("
    SELECT i.id, i.name, i.item_code, i.current_qty, i.brand, i.model_number,
           (SELECT COUNT(*) FROM item_serial_numbers isn WHERE isn.item_id = i.id AND isn.is_assigned = 0) as available_serials
    FROM items i
    WHERE i.is_active = 1 
    ORDER BY i.name
")->fetchAll();

// Get available serials for current item
$availableSerials = [];
if($assignment['item_id']) {
    $stmt = $pdo->prepare("
        SELECT serial_number, model_number FROM item_serial_numbers 
        WHERE item_id = ? AND is_assigned = 0
    ");
    $stmt->execute([$assignment['item_id']]);
    $availableSerials = $stmt->fetchAll();
}

// Get employees
$employees = $pdo->query("
    SELECT id, pf_no, full_name, designation, department 
    FROM employees 
    WHERE is_active = 1 
    ORDER BY full_name
")->fetchAll();

// Get assigned by user name
$assigned_by_name = '';
if($assignment['assigned_by']) {
    $user_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $user_stmt->execute([$assignment['assigned_by']]);
    $user = $user_stmt->fetch();
    $assigned_by_name = $user ? htmlspecialchars($user['username']) : 'System';
} else {
    $assigned_by_name = 'System';
}
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
        border-left: 4px solid #ffc107;
    }
    .current-info {
        background: #e8f5e9;
        border-left: 4px solid #4caf50;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .serial-model-row {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    .serial-model-row .form-group {
        flex: 1;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-edit text-warning"></i> Edit Assignment</h2>
                    <p class="text-muted">Assignment #: <strong><?php echo htmlspecialchars($assignment['assignment_no']); ?></strong></p>
                </div>
                <div>
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-list"></i> Back to List
                    </a>
                    <a href="print.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-info">
                        <i class="fas fa-print"></i> Print
                    </a>
                    <a href="barcode_label.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-primary">
                        <i class="fas fa-qrcode"></i> Barcode
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Assignment Details</h5>
                </div>
                <div class="card-body">
                    <!-- Current Information Display -->
                    <div class="current-info">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <strong><i class="fas fa-info-circle text-primary"></i> Current Assignment:</strong><br>
                                Employee: <?php echo htmlspecialchars($assignment['employee_name']); ?> (<?php echo htmlspecialchars($assignment['pf_no']); ?>)<br>
                                Device: <?php echo htmlspecialchars($assignment['item_name']); ?> (<?php echo htmlspecialchars($assignment['item_code']); ?>)<br>
                                <?php if($assigned_serial): ?>
                                    Serial: <?php echo htmlspecialchars($assigned_serial); ?><br>
                                <?php endif; ?>
                                Quantity: <?php echo $original_quantity; ?> | Status: <?php echo ucfirst($assignment['status']); ?>
                            </div>
                            <div class="mt-2 mt-md-0">
                                <span class="badge bg-secondary">Created: <?php echo date('d-m-Y', strtotime($assignment['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" id="assignmentForm">
                        <!-- Employee Selection -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label required-field">Employee</label>
                                <select name="employee_id" class="form-select" required id="employee_id">
                                    <option value="">Select Employee</option>
                                    <?php foreach($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" 
                                        <?php echo $assignment['employee_id'] == $emp['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['pf_no'] . ' - ' . $emp['full_name']); ?> 
                                        (<?php echo htmlspecialchars($emp['designation']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Item Selection -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label required-field">Device/Item</label>
                                <select name="item_id" class="form-select" required id="item_id" onchange="updateItemInfo()">
                                    <option value="">Select Device</option>
                                    <?php foreach($items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>" 
                                        data-stock="<?php echo $item['current_qty']; ?>"
                                        data-brand="<?php echo htmlspecialchars($item['brand'] ?? ''); ?>"
                                        data-model="<?php echo htmlspecialchars($item['model_number'] ?? ''); ?>"
                                        data-serials="<?php echo $item['available_serials']; ?>"
                                        <?php echo $assignment['item_id'] == $item['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($item['name'] . ' (' . $item['item_code'] . ') - Stock: ' . $item['current_qty']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="itemInfo" class="mt-2 small text-muted"></div>
                            </div>
                        </div>
                        
                        <!-- Serial Number Selection -->
                        <div class="row" id="serialRow" style="display: <?php echo ($assigned_serial || count($availableSerials) > 0) ? 'block' : 'none'; ?>">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Serial Number <?php if($assigned_serial || count($availableSerials) > 0) echo '<span class="text-danger">*</span>'; ?></label>
                                <select name="serial_number" class="form-select" id="serial_number">
                                    <option value="">-- Select Serial Number --</option>
                                    <?php if($assigned_serial): ?>
                                    <option value="<?php echo htmlspecialchars($assigned_serial); ?>" selected data-model="<?php echo htmlspecialchars($assigned_model ?? ''); ?>">
                                        <?php echo htmlspecialchars($assigned_serial); ?> (Currently Assigned)
                                    </option>
                                    <?php endif; ?>
                                    <?php foreach($availableSerials as $serial): ?>
                                    <option value="<?php echo htmlspecialchars($serial['serial_number']); ?>" 
                                        data-model="<?php echo htmlspecialchars($serial['model_number'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($serial['serial_number']); ?>
                                        <?php if($serial['model_number']): ?> (Model: <?php echo htmlspecialchars($serial['model_number']); ?>)<?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Model Number Display -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Model Number</label>
                                <input type="text" name="model_number" class="form-control" id="model_number" 
                                       value="<?php echo htmlspecialchars($assigned_model ?? $assignment['model_number'] ?? ''); ?>"
                                       placeholder="Auto-populated from serial selection">
                                <small class="text-muted">Will be auto-populated when serial is selected</small>
                            </div>
                        </div>
                        
                        <!-- Quantity & Dates -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label required-field">Quantity</label>
                                <input type="number" name="quantity" class="form-control" required 
                                       id="quantity" min="1" value="<?php echo $assignment['quantity']; ?>">
                                <small id="qtyWarning" class="text-danger"></small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label required-field">Assigned Date</label>
                                <input type="date" name="assigned_date" class="form-control" required 
                                       value="<?php echo $assignment['assigned_date']; ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Expected Return Date</label>
                                <input type="date" name="expected_return_date" class="form-control" 
                                       value="<?php echo $assignment['expected_return_date']; ?>">
                                <small class="text-muted">Optional</small>
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="assigned" <?php echo $assignment['status'] == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                    <option value="returned" <?php echo $assignment['status'] == 'returned' ? 'selected' : ''; ?>>Returned</option>
                                    <option value="damaged" <?php echo $assignment['status'] == 'damaged' ? 'selected' : ''; ?>>Damaged</option>
                                    <option value="replaced" <?php echo $assignment['status'] == 'replaced' ? 'selected' : ''; ?>>Replaced</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Notes / Remarks</label>
                                <textarea name="notes" rows="3" class="form-control" 
                                          placeholder="Any additional information..."><?php echo htmlspecialchars($assignment['notes']); ?></textarea>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm info-card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Important Notes</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-microchip text-info"></i> <strong>Serial Number:</strong></li>
                        <li class="ms-3">- Only available (unassigned) serial numbers are shown</li>
                        <li class="ms-3">- Selecting a serial number will auto-populate the model</li>
                        <li class="ms-3">- If device has serial numbers, selection is required</li>
                        <li class="mt-2"><i class="fas fa-chart-line text-warning"></i> <strong>Stock Adjustment:</strong></li>
                        <li class="ms-3">- Changing quantity will adjust inventory automatically</li>
                        <li class="ms-3">- Changing device will return old device to stock</li>
                        <li class="ms-3">- Marking as "Returned" will free up the serial number</li>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Assignment Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Created:</span>
                        <strong><?php echo date('d-m-Y H:i', strtotime($assignment['created_at'])); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Assigned By:</span>
                        <strong><?php echo $assigned_by_name; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const originalItemId = <?php echo $assignment['item_id']; ?>;
const originalQuantity = <?php echo $original_quantity; ?>;
const itemsData = <?php echo json_encode($items); ?>;
const assignedSerial = <?php echo json_encode($assigned_serial); ?>;

function updateItemInfo() {
    const itemSelect = document.getElementById('item_id');
    const selectedOption = itemSelect.options[itemSelect.selectedIndex];
    const stock = selectedOption.getAttribute('data-stock');
    const brand = selectedOption.getAttribute('data-brand');
    const model = selectedOption.getAttribute('data-model');
    const hasSerials = parseInt(selectedOption.getAttribute('data-serials') || 0);
    const itemInfo = document.getElementById('itemInfo');
    const serialRow = document.getElementById('serialRow');
    const serialSelect = document.getElementById('serial_number');
    
    if(selectedOption.value) {
        let infoHtml = `<i class="fas fa-info-circle"></i> `;
        if(brand) infoHtml += `Brand: ${brand} | `;
        if(model) infoHtml += `Model: ${model} | `;
        infoHtml += `Available Stock: ${stock}`;
        if(hasSerials > 0) infoHtml += ` | Available Serials: ${hasSerials}`;
        itemInfo.innerHTML = infoHtml;
        
        // Load serial numbers for this item if changed
        if(parseInt(selectedOption.value) !== originalItemId) {
            loadSerialNumbers(selectedOption.value);
        }
        
        // Update quantity validation
        const quantityInput = document.getElementById('quantity');
        quantityInput.max = parseInt(stock) + originalQuantity;
        validateQuantity();
        
        // Show/hide serial row based on available serials or current assignment
        if (hasSerials > 0 || assignedSerial) {
            serialRow.style.display = 'block';
            if (serialSelect) serialSelect.required = true;
        } else {
            serialRow.style.display = 'none';
            if (serialSelect) serialSelect.required = false;
        }
    } else {
        itemInfo.innerHTML = '';
    }
}

function loadSerialNumbers(itemId) {
    $.ajax({
        url: '../ajax/get_available_serials.php',
        method: 'POST',
        data: { item_id: itemId },
        dataType: 'json',
        success: function(response) {
            const serialSelect = document.getElementById('serial_number');
            const serialRow = document.getElementById('serialRow');
            
            if (response.serials && response.serials.length > 0) {
                let options = '<option value="">-- Select Serial Number --</option>';
                response.serials.forEach(serial => {
                    options += `<option value="${escapeHtml(serial.serial_number)}" data-model="${escapeHtml(serial.model_number || '')}">${escapeHtml(serial.serial_number)}</option>`;
                });
                serialSelect.innerHTML = options;
                serialRow.style.display = 'block';
                serialSelect.required = true;
            } else {
                serialRow.style.display = 'none';
                serialSelect.required = false;
            }
        },
        error: function() {
            document.getElementById('serialRow').style.display = 'none';
        }
    });
}

function validateQuantity() {
    const itemSelect = document.getElementById('item_id');
    const selectedOption = itemSelect.options[itemSelect.selectedIndex];
    const stock = parseInt(selectedOption.getAttribute('data-stock') || 0);
    const quantity = parseInt(document.getElementById('quantity').value || 0);
    const qtyWarning = document.getElementById('qtyWarning');
    
    let maxAllowed = stock;
    
    if(parseInt(itemSelect.value) === originalItemId) {
        maxAllowed = stock + originalQuantity;
    }
    
    if(quantity > maxAllowed) {
        qtyWarning.innerHTML = `⚠️ Only ${maxAllowed} items available for this device!`;
        document.getElementById('submitBtn').disabled = true;
    } else if(quantity < 1) {
        qtyWarning.innerHTML = `⚠️ Quantity must be at least 1!`;
        document.getElementById('submitBtn').disabled = true;
    } else {
        qtyWarning.innerHTML = '';
        document.getElementById('submitBtn').disabled = false;
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

// Serial number change handler
document.getElementById('serial_number')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const model = selectedOption.getAttribute('data-model');
    if(model) {
        document.getElementById('model_number').value = model;
    }
});

// Event listeners
document.getElementById('quantity').addEventListener('input', validateQuantity);
document.getElementById('item_id').addEventListener('change', function() {
    updateItemInfo();
    validateQuantity();
});

// Initial validation
updateItemInfo();
validateQuantity();

document.getElementById('assignmentForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;
});
</script>

<?php include '../../includes/footer.php'; ?>