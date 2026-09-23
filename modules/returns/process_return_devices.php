<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

// Check permissions
if(!canView($pdo, $_SESSION['role'], 'requests') && $_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Get ID from GET parameter
$return_request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($return_request_id <= 0) {
    $_SESSION['error'] = "Invalid return request ID. Please select a valid return request from the list.";
    header("Location: return_requests_list.php");
    exit();
}

// Get return request details
$stmt = $pdo->prepare("
    SELECT rdr.*, r.status as request_status, r.request_no,
           e.full_name as employee_name, e.pf_no, e.designation, e.department,
           rdr.id as return_request_id
    FROM return_device_requests rdr
    JOIN requests r ON rdr.request_id = r.id
    JOIN employees e ON r.employee_id = e.id
    WHERE rdr.id = ?
");
$stmt->execute([$return_request_id]);
$return_request = $stmt->fetch();

if(!$return_request) {
    $_SESSION['error'] = "Return request not found. ID: " . $return_request_id;
    header("Location: return_requests_list.php");
    exit();
}

// Handle single device processing (return device - changes status, not quantity)
if(isset($_POST['process_single_device']) && isset($_POST['device_item_id'])) {
    $device_item_id = (int)$_POST['device_item_id'];
    $device_condition = trim($_POST['device_condition'] ?? 'good');
    $damage_description = trim($_POST['damage_description'] ?? '');
    $accessories_returned = trim($_POST['accessories_returned'] ?? '');
    $process_notes = trim($_POST['process_notes'] ?? '');
    
    $pdo->beginTransaction();
    try {
        // Get device details with the EXISTING item information
        // IMPORTANT: This gets the actual item_id from the assignment
        $stmt = $pdo->prepare("
            SELECT 
                rdi.id,
                rdi.assignment_id,
                rdi.processing_status,
                a.item_id,
                i.id as existing_item_id,
                i.item_code,
                i.name,
                i.current_qty,
                i.available_qty,
                i.total_assigned,
                i.total_returned
            FROM return_device_items rdi
            JOIN assignments a ON rdi.assignment_id = a.id
            JOIN items i ON a.item_id = i.id
            WHERE rdi.id = ?
        ");
        $stmt->execute([$device_item_id]);
        $device = $stmt->fetch();
        
        if(!$device) {
            throw new Exception("Device not found");
        }
        
        // Check if already processed
        if($device['processing_status'] == 'added_to_stock') {
            throw new Exception("This device has already been returned to stock!");
        }
        
        // CRITICAL: Update the EXISTING item using its actual item_id
        // We DO NOT create a new item - we update the existing one
        // current_qty: NO CHANGE (total stock remains same)
        // available_qty: INCREASE by 1 (becomes available for new assignment)
        // total_assigned: DECREASE by 1 (no longer assigned)
        // total_returned: INCREASE by 1 (record return)
        
        $new_available_qty = $device['available_qty'] + 1;
        $new_total_assigned = max(0, $device['total_assigned'] - 1);
        $new_total_returned = $device['total_returned'] + 1;
        
        // Log the update for debugging
        error_log("Returning Device: " . $device['item_code'] . " (ID: " . $device['existing_item_id'] . ")");
        error_log("Before: available_qty=" . $device['available_qty'] . ", total_assigned=" . $device['total_assigned'] . ", total_returned=" . $device['total_returned']);
        error_log("After: available_qty=" . $new_available_qty . ", total_assigned=" . $new_total_assigned . ", total_returned=" . $new_total_returned);
        
        // Update the EXISTING item in items table
        // Using the existing_item_id from the assignment
        $stmt = $pdo->prepare("
            UPDATE items 
            SET available_qty = ?, 
                total_assigned = ?, 
                total_returned = ?
            WHERE id = ?
        ");
        $stmt->execute([$new_available_qty, $new_total_assigned, $new_total_returned, $device['existing_item_id']]);
        
        // Verify the update worked
        if($stmt->rowCount() == 0) {
            throw new Exception("Failed to update item. Item ID: " . $device['existing_item_id']);
        }
        
        // Update return_device_item with final condition and status
        $stmt = $pdo->prepare("
            UPDATE return_device_items 
            SET device_condition = ?, 
                damage_description = ?, 
                accessories_returned = ?,
                processing_status = 'added_to_stock', 
                stock_added_date = NOW(), 
                processed_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$device_condition, $damage_description, $accessories_returned, $_SESSION['user_id'], $device_item_id]);
        
        // Update assignment status to 'returned'
        $stmt = $pdo->prepare("
            UPDATE assignments 
            SET return_status = 'returned', 
                returned_date = NOW(), 
                return_approved_by = ?, 
                stock_updated = 1,
                status = 'returned'
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $device['assignment_id']]);
        
        // Add stock history record for the return
        $stmt = $pdo->prepare("
            INSERT INTO return_stock_history (return_device_item_id, item_id, quantity_added, previous_stock, new_stock, processed_by, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $device_item_id, 
            $device['existing_item_id'], 
            1,
            $device['available_qty'], 
            $new_available_qty, 
            $_SESSION['user_id'],
            "Device returned. Item Code: {$device['item_code']}. Condition: $device_condition. Now available for reassignment."
        ]);
        
        // Check if all devices in this request are processed
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN processing_status = 'added_to_stock' THEN 1 ELSE 0 END) as processed_count
            FROM return_device_items 
            WHERE return_request_id = ?
        ");
        $stmt->execute([$return_request_id]);
        $status = $stmt->fetch();
        
        // Update the main request status in requests table
        if($status['total'] == $status['processed_count']) {
            // All devices processed - mark request as completed
            $stmt = $pdo->prepare("UPDATE requests SET status = 'completed' WHERE id = ?");
            $stmt->execute([$return_request['request_id']]);
            $_SESSION['success'] = "All devices returned successfully! Devices are now available for new assignments.";
        } else {
            // Mark request as processing
            $stmt = $pdo->prepare("UPDATE requests SET status = 'processing' WHERE id = ?");
            $stmt->execute([$return_request['request_id']]);
            $_SESSION['success'] = "Device '" . htmlspecialchars($device['name']) . "' returned successfully! " . 
                                    ($status['total'] - $status['processed_count']) . " device(s) remaining.";
        }
        
        $pdo->commit();
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        error_log("Return Error: " . $e->getMessage());
    }
    
    header("Location: process_return_devices.php?id=" . $return_request_id);
    exit();
}

// Handle bulk process all pending devices
if(isset($_POST['process_all_devices'])) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            SELECT 
                rdi.id,
                rdi.assignment_id,
                a.item_id,
                i.id as existing_item_id,
                i.item_code,
                i.available_qty,
                i.total_assigned,
                i.total_returned
            FROM return_device_items rdi
            JOIN assignments a ON rdi.assignment_id = a.id
            JOIN items i ON a.item_id = i.id
            WHERE rdi.return_request_id = ? AND (rdi.processing_status = 'pending' OR rdi.processing_status IS NULL)
        ");
        $stmt->execute([$return_request_id]);
        $devices = $stmt->fetchAll();
        
        $processed_count = 0;
        foreach($devices as $device) {
            // Calculate new values
            $new_available_qty = $device['available_qty'] + 1;
            $new_total_assigned = max(0, $device['total_assigned'] - 1);
            $new_total_returned = $device['total_returned'] + 1;
            
            // Update the EXISTING item
            $stmt2 = $pdo->prepare("
                UPDATE items 
                SET available_qty = ?, 
                    total_assigned = ?, 
                    total_returned = ?
                WHERE id = ?
            ");
            $stmt2->execute([$new_available_qty, $new_total_assigned, $new_total_returned, $device['existing_item_id']]);
            
            // Update return_device_item
            $stmt2 = $pdo->prepare("
                UPDATE return_device_items 
                SET processing_status = 'added_to_stock', stock_added_date = NOW(), processed_by = ?
                WHERE id = ?
            ");
            $stmt2->execute([$_SESSION['user_id'], $device['id']]);
            
            // Update assignment
            $stmt2 = $pdo->prepare("
                UPDATE assignments 
                SET return_status = 'returned', returned_date = NOW(), return_approved_by = ?, stock_updated = 1, status = 'returned'
                WHERE id = ?
            ");
            $stmt2->execute([$_SESSION['user_id'], $device['assignment_id']]);
            
            $processed_count++;
        }
        
        // Mark request as completed in requests table
        $stmt = $pdo->prepare("UPDATE requests SET status = 'completed' WHERE id = ?");
        $stmt->execute([$return_request['request_id']]);
        
        $pdo->commit();
        $_SESSION['success'] = "$processed_count device(s) returned successfully! They are now available for new assignments.";
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        error_log("Bulk Return Error: " . $e->getMessage());
    }
    
    header("Location: return_requests_list.php");
    exit();
}

// Get all devices in this return request
$stmt = $pdo->prepare("
    SELECT 
        rdi.id,
        rdi.assignment_id,
        rdi.device_condition,
        rdi.damage_description,
        rdi.accessories_returned,
        rdi.processing_status,
        rdi.stock_added_date,
        a.assignment_no,
        a.assigned_date,
        i.id as item_id,
        i.item_code,
        i.name as item_name,
        i.serial_number,
        i.model_number,
        i.current_qty as current_stock,
        i.available_qty as available_stock,
        i.total_assigned,
        i.total_returned,
        i.min_qty,
        b.name as brand_name
    FROM return_device_items rdi
    JOIN assignments a ON rdi.assignment_id = a.id
    JOIN items i ON a.item_id = i.id
    LEFT JOIN brands b ON i.brand_id = b.id
    WHERE rdi.return_request_id = ?
    ORDER BY rdi.id
");
$stmt->execute([$return_request_id]);
$devices = $stmt->fetchAll();

$total_devices = count($devices);
$processed_count = 0;
$pending_count = 0;

foreach($devices as $d) {
    if($d['processing_status'] == 'added_to_stock') {
        $processed_count++;
    } else {
        $pending_count++;
    }
}

$all_processed = ($total_devices > 0 && $processed_count >= $total_devices);
?>

<style>
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-completed { background: #d1fae5; color: #059669; }
    .device-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #e5e7eb;
        transition: all 0.2s;
    }
    .device-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .device-card.processed {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }
    .stock-info {
        background: #eff6ff;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
    }
    .stock-change {
        display: flex;
        justify-content: space-between;
        margin-top: 5px;
    }
    .stock-label { color: #6b7280; }
    .assigned-before { color: #f59e0b; text-decoration: line-through; }
    .available-after { color: #059669; font-weight: bold; }
    .action-buttons {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }
    .progress-container {
        background: #e5e7eb;
        border-radius: 20px;
        height: 8px;
        overflow: hidden;
        margin: 10px 0;
    }
    .progress-bar-custom {
        background: #3b82f6;
        height: 100%;
        transition: width 0.3s;
    }
    .btn-process {
        background: #3b82f6;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
        width: 100%;
    }
    .btn-process:hover:not(:disabled) {
        background: #2563eb;
    }
    .info-note {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 10px 15px;
        margin-bottom: 15px;
        border-radius: 8px;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-undo-alt text-primary me-2"></i>Process Device Returns</h4>
            <p class="text-muted small">Return Request: <?php echo htmlspecialchars($return_request['request_no']); ?></p>
        </div>
        <div>
            <a href="return_requests_list.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Requests
            </a>
        </div>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Info Note - Explain the process -->
    <div class="info-note">
        <i class="fas fa-info-circle me-2"></i>
        <strong>How Return Processing Works:</strong>
        <ul class="mb-0 mt-1">
            <li>✓ Same Item Code remains <strong>unchanged</strong> (no duplicate created)</li>
            <li>✓ Current Stock: <strong>Stays the same</strong> (device already exists in inventory)</li>
            <li>✓ Available for Assignment: <strong>Increases by 1</strong> (device becomes available for new employee)</li>
            <li>✓ Assigned Count: <strong>Decreases by 1</strong> (no longer assigned to current employee)</li>
            <li>✓ Total Returned: <strong>Increases by 1</strong> (records the return history)</li>
        </ul>
    </div>

    <!-- Request Summary -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-2">Employee Information</h6>
                    <div class="small">
                        <strong>Name:</strong> <?php echo htmlspecialchars($return_request['employee_name']); ?><br>
                        <strong>PF No:</strong> <?php echo htmlspecialchars($return_request['pf_no']); ?><br>
                        <strong>Department:</strong> <?php echo htmlspecialchars($return_request['department']); ?>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="row">
                        <div class="col-4">
                            <div class="small text-muted">Total Devices</div>
                            <h5 class="mb-0"><?php echo $total_devices; ?></h5>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted text-success">Returned</div>
                            <h5 class="mb-0 text-success"><?php echo $processed_count; ?></h5>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted text-warning">Pending</div>
                            <h5 class="mb-0 text-warning"><?php echo $pending_count; ?></h5>
                        </div>
                    </div>
                    <div class="progress-container mt-2">
                        <div class="progress-bar-custom" style="width: <?php echo ($total_devices > 0) ? ($processed_count / $total_devices) * 100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Action -->
    <?php if($pending_count > 0 && !$all_processed): ?>
    <div class="mb-3 text-end">
        <form method="POST" onsubmit="return confirm('Return ALL pending devices? This will make them available for new assignments.')">
            <button type="submit" name="process_all_devices" class="btn btn-primary">
                <i class="fas fa-undo-alt me-1"></i> Return All to Stock (<?php echo $pending_count; ?> Devices)
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Devices List -->
    <div class="row">
        <?php if(empty($devices)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">No devices found in this return request.</div>
            </div>
        <?php else: ?>
            <?php foreach($devices as $device): 
                $is_processed = ($device['processing_status'] == 'added_to_stock');
                $new_available = $device['available_stock'] + 1;
                $new_assigned = max(0, $device['total_assigned'] - 1);
            ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="device-card <?php echo $is_processed ? 'processed' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($device['item_name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($device['item_code']); ?></small>
                        </div>
                        <span class="status-badge status-<?php echo $is_processed ? 'completed' : 'pending'; ?>">
                            <i class="fas fa-<?php echo $is_processed ? 'check-circle' : 'clock'; ?> me-1"></i>
                            <?php echo $is_processed ? 'Returned' : 'Pending Return'; ?>
                        </span>
                    </div>
                    
                    <div class="small mb-2">
                        <div><strong>Serial #:</strong> <?php echo htmlspecialchars($device['serial_number'] ?? 'N/A'); ?></div>
                        <div><strong>Model:</strong> <?php echo htmlspecialchars($device['model_number'] ?? 'N/A'); ?></div>
                        <div><strong>Brand:</strong> <?php echo htmlspecialchars($device['brand_name'] ?? 'N/A'); ?></div>
                        <div><strong>Assignment No:</strong> <?php echo htmlspecialchars($device['assignment_no']); ?></div>
                    </div>
                    
                    <div class="stock-info mb-2">
                        <div class="stock-change">
                            <span class="stock-label">📦 Current Stock:</span>
                            <span><strong><?php echo $device['current_stock']; ?></strong> (no change)</span>
                        </div>
                        <div class="stock-change">
                            <span class="stock-label">👥 Assigned:</span>
                            <span><span class="assigned-before"><?php echo $device['total_assigned']; ?></span> → <strong class="available-after"><?php echo $new_assigned; ?></strong></span>
                        </div>
                        <div class="stock-change">
                            <span class="stock-label">✅ Available:</span>
                            <span><span class="assigned-before"><?php echo $device['available_stock']; ?></span> → <strong class="available-after"><?php echo $new_available; ?></strong></span>
                        </div>
                        <div class="stock-change">
                            <span class="stock-label">📊 Total Returned:</span>
                            <span><strong><?php echo $device['total_returned'] + 1; ?></strong></span>
                        </div>
                    </div>
                    
                    <div class="small text-muted mb-2">
                        <div><strong>Condition:</strong> <?php echo ucfirst(str_replace('_', ' ', $device['device_condition'] ?? 'good')); ?></div>
                        <?php if(!empty($device['damage_description'])): ?>
                            <div><strong>Damage:</strong> <?php echo htmlspecialchars($device['damage_description']); ?></div>
                        <?php endif; ?>
                        <?php if(!empty($device['accessories_returned'])): ?>
                            <div><strong>Accessories:</strong> <?php echo htmlspecialchars($device['accessories_returned']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(!$is_processed): ?>
                        <button class="btn btn-process" onclick="showProcessModal(<?php echo $device['id']; ?>, '<?php echo htmlspecialchars($device['item_code']); ?>', <?php echo $device['available_stock']; ?>, <?php echo $new_available; ?>)">
                            <i class="fas fa-undo-alt me-1"></i> Return Device & Make Available
                        </button>
                    <?php else: ?>
                        <div class="text-center text-success small mt-2">
                            <i class="fas fa-check-circle"></i> Returned and available for new assignment
                            <?php if($device['stock_added_date']): ?>
                                <br><small>Processed on <?php echo date('d-m-Y H:i', strtotime($device['stock_added_date'])); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Process Single Device Modal -->
<div class="modal fade" id="processModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Return Device to Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="process_single_device" value="1">
                    <input type="hidden" name="device_item_id" id="modal_device_id">
                    
                    <div class="alert alert-info">
                        <strong>Device:</strong> <span id="modal_device_name"></span><br>
                        <strong>Item Code:</strong> Will remain <strong>UNCHANGED</strong> (no duplicate)<br>
                        <strong>Effect on Stock:</strong>
                        <ul class="mt-2 mb-0">
                            <li>Item Code: <strong>Same as original</strong></li>
                            <li>Current Stock: <strong>No change</strong></li>
                            <li>Available: <strong><span id="modal_available_change"></span></strong> (becomes available)</li>
                            <li>Assigned: <strong>Decreases by 1</strong></li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Device Condition <span class="text-danger">*</span></label>
                        <select name="device_condition" class="form-select" required>
                            <option value="good">Good - Fully functional (Ready for reassignment)</option>
                            <option value="minor_damage">Minor Damage - Works but has cosmetic issues</option>
                            <option value="major_damage">Major Damage - Requires repair</option>
                            <option value="not_working">Not Working - Dead/Unusable</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Damage Description</label>
                        <textarea name="damage_description" class="form-control" rows="2" placeholder="Describe any damage or issues..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Accessories Returned</label>
                        <input type="text" name="accessories_returned" class="form-control" placeholder="e.g., Charger, Cable, Box, Manual">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Processing Notes</label>
                        <textarea name="process_notes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Return to Stock & Make Available</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showProcessModal(deviceId, deviceCode, currentAvailable, newAvailable) {
    document.getElementById('modal_device_id').value = deviceId;
    document.getElementById('modal_device_name').innerHTML = deviceCode;
    document.getElementById('modal_available_change').innerHTML = currentAvailable + ' → ' + newAvailable + ' units';
    $('#processModal').modal('show');
}
</script>

<?php include '../../includes/footer.php'; ?>