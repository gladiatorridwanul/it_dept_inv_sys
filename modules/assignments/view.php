<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;

// Get assignment details with serial info - Fixed query
$stmt = $pdo->prepare("
    SELECT a.*, 
           e.full_name as employee_name, 
           e.pf_no, 
           e.designation, 
           e.department,
           e.phone as employee_phone,
           e.email as employee_email,
           i.name as item_name, 
           i.item_code,
           i.specification,
           i.serial_number as item_serial,
           i.model_number,
           i.version,
           i.brand_id,
           b.name as brand_name,
           u.full_name as assigned_by_name
    FROM assignments a 
    JOIN employees e ON a.employee_id = e.id 
    JOIN items i ON a.item_id = i.id 
    LEFT JOIN brands b ON i.brand_id = b.id
    LEFT JOIN users u ON a.assigned_by = u.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$assignment = $stmt->fetch();

if(!$assignment) {
    echo '<div class="alert alert-danger text-center py-5">
            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
            <h4>Assignment Not Found</h4>
            <p>The requested assignment does not exist or has been removed.</p>
            <a href="list.php" class="btn btn-primary mt-3">Back to Assignments List</a>
          </div>';
    include '../../includes/footer.php';
    exit();
}

// Get assigned serial from item_serial_numbers table
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

// Get return information if any
$return_info = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM stock_returns WHERE assignment_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$id]);
    $return_info = $stmt->fetch();
} catch(PDOException $e) {
    // Table might not exist, ignore
}

// Calculate days since assignment
$assigned_date = new DateTime($assignment['assigned_date']);
$today = new DateTime();
$days_assigned = $assigned_date->diff($today)->days;

$statusClass = $assignment['status'] == 'assigned' ? 'success' : ($assignment['status'] == 'returned' ? 'info' : 'warning');
$statusIcon = $assignment['status'] == 'assigned' ? 'check-circle' : ($assignment['status'] == 'returned' ? 'undo-alt' : 'exclamation-triangle');

// Check if barcode exists
$barcode_path = $assignment['barcode_path'] ?? '';
$barcode_full_path = '';
$has_barcode = false;

if(!empty($barcode_path)) {
    // Clean the path - remove any leading slashes or duplicate paths
    $clean_path = ltrim($barcode_path, '/');
    // Remove any duplicate 'uploads' if present
    $clean_path = preg_replace('#^uploads/?#', '', $clean_path);
    $barcode_full_path = '/uploads/barcodes/' . $clean_path;
    
    // Check if file exists
    $file_check_path = '../../uploads/barcodes/' . $clean_path;
    if(file_exists($file_check_path)) {
        $has_barcode = true;
    } else {
        // Try alternative path
        $alt_path = '../../' . $barcode_path;
        if(file_exists($alt_path)) {
            $has_barcode = true;
            $barcode_full_path = '/' . ltrim($barcode_path, '/');
        }
    }
}
?>

<style>
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        overflow: hidden;
    }
    .card-header-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
    }
    .card-header-custom.bg-info {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .section-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e2e8f0;
        position: relative;
    }
    .section-title:after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 50px;
        height: 2px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .detail-row {
        display: flex;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .detail-label {
        width: 160px;
        font-weight: 600;
        color: #475569;
    }
    .detail-value {
        flex: 1;
        color: #1e293b;
    }
    .status-badge {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 30px;
        font-size: 14px;
        font-weight: 600;
    }
    .info-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 20px;
    }
    .barcode-preview {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        margin-top: 15px;
    }
    .barcode-preview img {
        max-width: 100%;
        height: auto;
    }
    .serial-info {
        background: #e0f2fe;
        border-left: 4px solid #0ea5e9;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .barcode-placeholder {
        background: #f8fafc;
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 30px 20px;
        text-align: center;
        color: #94a3b8;
    }
    .barcode-placeholder i {
        font-size: 48px;
        display: block;
        margin-bottom: 10px;
    }
    @media (max-width: 768px) {
        .detail-label {
            width: 120px;
        }
        .action-buttons {
            flex-direction: column;
        }
        .action-buttons .btn {
            width: 100%;
        }
    }
</style>

<div class="container-fluid">
    <!-- Header with Actions -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-laptop text-primary"></i> Assignment Details</h2>
            <p class="text-muted">Complete information about device assignment</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="edit.php?id=<?php echo $assignment['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Assignment Information -->
            <div class="info-card">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i> Assignment Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Assignment No:</div>
                                <div class="detail-value">
                                    <strong><?php echo htmlspecialchars($assignment['assignment_no']); ?></strong>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Status:</div>
                                <div class="detail-value">
                                    <span class="status-badge bg-<?php echo $statusClass; ?> text-white">
                                        <i class="fas fa-<?php echo $statusIcon; ?> me-1"></i>
                                        <?php echo ucfirst($assignment['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Assigned Date:</div>
                                <div class="detail-value"><?php echo date('d-m-Y', strtotime($assignment['assigned_date'])); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Expected Return Date:</div>
                                <div class="detail-value">
                                    <?php echo $assignment['expected_return_date'] ? date('d-m-Y', strtotime($assignment['expected_return_date'])) : '<span class="text-muted">Not specified</span>'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Days Assigned:</div>
                                <div class="detail-value">
                                    <?php echo $days_assigned; ?> days
                                    <?php if($assignment['expected_return_date'] && new DateTime($assignment['expected_return_date']) < $today && $assignment['status'] == 'assigned'): ?>
                                        <span class="badge bg-danger ms-2">Overdue</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Quantity:</div>
                                <div class="detail-value"><?php echo $assignment['quantity']; ?> unit(s)</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Assigned By:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($assignment['assigned_by_name'] ?? 'System'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Created At:</div>
                                <div class="detail-value"><?php echo date('d-m-Y h:i A', strtotime($assignment['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($assignment['notes']): ?>
                    <div class="info-box mt-3">
                        <strong><i class="fas fa-sticky-note me-2"></i> Assignment Notes:</strong>
                        <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($assignment['notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Employee Information -->
            <div class="info-card">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i> Employee Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Full Name:</div>
                                <div class="detail-value"><strong><?php echo htmlspecialchars($assignment['employee_name']); ?></strong></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">PF Number:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($assignment['pf_no']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Designation:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($assignment['designation'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Department:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($assignment['department'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Phone:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($assignment['employee_phone'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Email:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($assignment['employee_email'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Device Information -->
            <div class="info-card">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-microchip me-2"></i> Device Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Device Name:</div>
                                <div class="detail-value"><strong><?php echo htmlspecialchars($assignment['item_name']); ?></strong></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Item Code:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($assignment['item_code']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Brand:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($assignment['brand_name'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Model Number:</div>
                                <div class="detail-value">
                                    <?php 
                                    $display_model = $assigned_model ?? $assignment['model_number'] ?? 'N/A';
                                    echo htmlspecialchars($display_model); 
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Serial Number:</div>
                                <div class="detail-value">
                                    <?php if($assigned_serial): ?>
                                        <code><?php echo htmlspecialchars($assigned_serial); ?></code>
                                        <span class="badge bg-info ms-2">Assigned</span>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Version:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($assignment['version'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($assignment['specification']): ?>
                    <div class="info-box mt-3">
                        <strong><i class="fas fa-info-circle me-2"></i> Specifications:</strong>
                        <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($assignment['specification'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Serial Number Info Box -->
                    <?php if($assigned_serial): ?>
                    <div class="serial-info">
                        <strong><i class="fas fa-microchip me-2"></i> Assigned Device Details:</strong><br>
                        Serial Number: <code><?php echo htmlspecialchars($assigned_serial); ?></code><br>
                        Model: <?php echo htmlspecialchars($assigned_model ?? $assignment['model_number'] ?? 'N/A'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Return Information (if returned) -->
            <?php if($return_info): ?>
            <div class="info-card">
                <div class="card-header-custom bg-info">
                    <h5 class="mb-0"><i class="fas fa-undo-alt me-2"></i> Return Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Return Date:</div>
                                <div class="detail-value"><?php echo date('d-m-Y', strtotime($return_info['return_date'])); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Condition:</div>
                                <div class="detail-value">
                                    <span class="badge bg-<?php echo $return_info['condition_status'] == 'good' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $return_info['condition_status'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Returned By:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($assignment['employee_name']); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php if($return_info['damage_details']): ?>
                    <div class="info-box mt-2">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i> Damage Details:</strong>
                        <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($return_info['damage_details'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <!-- Barcode Preview -->
            <div class="info-card">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i> Barcode</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <?php if($has_barcode): ?>
                        <div class="barcode-preview">
                            <img src="<?php echo $barcode_full_path; ?>" alt="Barcode for <?php echo htmlspecialchars($assignment['assignment_no']); ?>">
                            <p class="mt-2 mb-0"><strong><?php echo htmlspecialchars($assignment['assignment_no']); ?></strong></p>
                        </div>
                        <div class="action-buttons mt-3">
                            <a href="barcode_label.php?id=<?php echo $assignment['id']; ?>" target="_blank" class="btn btn-primary">
                                <i class="fas fa-print"></i> Print Barcode
                            </a>
                            <a href="<?php echo $barcode_full_path; ?>" download class="btn btn-dark">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="barcode-placeholder">
                            <i class="fas fa-qrcode"></i>
                            <p class="mb-0">No barcode generated yet</p>
                            <small class="text-muted">Click below to generate and print</small>
                        </div>
                        <div class="action-buttons mt-3">
                            <a href="barcode_label.php?id=<?php echo $assignment['id']; ?>" target="_blank" class="btn btn-primary">
                                <i class="fas fa-qrcode"></i> Generate Barcode
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="info-card">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i> Quick Actions</h5>
                </div>
                <div class="card-body p-4">
                    <div class="action-buttons">
                        <a href="print.php?id=<?php echo $assignment['id']; ?>" target="_blank" class="btn btn-info">
                            <i class="fas fa-print"></i> Print Acknowledgement
                        </a>
                        <?php if($assignment['status'] == 'assigned'): ?>
                            <a href="../returns/return.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn btn-warning">
                                <i class="fas fa-undo-alt"></i> Return Device
                            </a>
                        <?php endif; ?>
                        <a href="edit.php?id=<?php echo $assignment['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-edit"></i> Edit Assignment
                        </a>
                    </div>
                </div>
            </div>

            <!-- Assignment Stats -->
            <div class="info-card">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> Assignment Stats</h5>
                </div>
                <div class="card-body p-4">
                    <div class="info-box mb-2">
                        <div class="d-flex justify-content-between">
                            <span>Total Duration:</span>
                            <strong><?php echo $days_assigned; ?> days</strong>
                        </div>
                    </div>
                    <div class="info-box mb-2">
                        <div class="d-flex justify-content-between">
                            <span>Status:</span>
                            <strong class="text-<?php echo $statusClass; ?>"><?php echo ucfirst($assignment['status']); ?></strong>
                        </div>
                    </div>
                    <?php if($assignment['expected_return_date']): ?>
                    <div class="info-box">
                        <div class="d-flex justify-content-between">
                            <span>Return Status:</span>
                            <?php if(new DateTime($assignment['expected_return_date']) < $today && $assignment['status'] == 'assigned'): ?>
                                <strong class="text-danger">Overdue</strong>
                            <?php else: ?>
                                <strong class="text-success">On Time</strong>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>