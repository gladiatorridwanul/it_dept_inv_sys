<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;

// Get item details with all relations
$stmt = $pdo->prepare("
    SELECT i.*, 
           c.name as category_name, 
           sc.name as sub_category_name,
           t.name as type_name, 
           b.name as brand_name,
           u.full_name as created_by_name
    FROM items i 
    LEFT JOIN categories c ON i.category_id = c.id 
    LEFT JOIN categories sc ON i.sub_category_id = sc.id
    LEFT JOIN item_types t ON i.type_id = t.id 
    LEFT JOIN brands b ON i.brand_id = b.id
    LEFT JOIN users u ON i.created_by = u.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$item = $stmt->fetch();

if(!$item) {
    echo '<div class="alert alert-danger text-center py-5">
            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
            <h4>Item Not Found</h4>
            <p>The requested item does not exist or has been removed.</p>
            <a href="list.php" class="btn btn-primary mt-3">Back to Items List</a>
          </div>';
    include '../../includes/footer.php';
    exit();
}

// Get item vendors
$stmt = $pdo->prepare("SELECT iv.*, v.vendor_name as name, v.phone, v.email, v.address 
                       FROM item_vendors iv 
                       JOIN vendors v ON iv.vendor_id = v.id 
                       WHERE iv.item_id = ?");
$stmt->execute([$id]);
$itemVendors = $stmt->fetchAll();

// Get serial numbers from item_serial_numbers table with full details
$stmt = $pdo->prepare("
    SELECT 
        isn.id as serial_id,
        isn.serial_number, 
        isn.model_number, 
        isn.version,
        isn.is_assigned,
        isn.assigned_to,
        isn.assigned_date,
        isn.assignment_id,
        isn.created_at,
        e.full_name as assigned_to_name, 
        e.pf_no as assigned_to_pf,
        a.assignment_no,
        a.assigned_date as assignment_assigned_date,
        a.status as assignment_status,
        a.return_status,
        a.returned_date,
        d.id as damage_id,
        d.damage_no,
        d.damage_type,
        d.damage_severity,
        d.status as damage_status,
        d.damage_date,
        d.damage_description,
        rdr.request_no as return_request_no,
        rdr.return_reason,
        rdr.device_condition
    FROM item_serial_numbers isn
    LEFT JOIN assignments a ON isn.assignment_id = a.id
    LEFT JOIN employees e ON isn.assigned_to = e.id
    LEFT JOIN damages d ON isn.damage_id = d.id
    LEFT JOIN return_device_items rdi ON a.id = rdi.assignment_id
    LEFT JOIN return_device_requests rdr ON rdi.return_request_id = rdr.id
    WHERE isn.item_id = ? 
    ORDER BY isn.serial_number
");
$stmt->execute([$id]);
$serialNumbers = $stmt->fetchAll();

// Process serials to determine actual status
$processedSerials = [];
$assignedSerialsCount = 0;
$availableSerialsCount = 0;
$damagedSerialsCount = 0;
$returnedSerialsCount = 0;

foreach($serialNumbers as $serial) {
    $actual_status = 'available';
    
    // Check if serial is assigned (is_assigned = 1)
    if($serial['is_assigned'] == 1) {
        // Check if it's returned
        if($serial['assignment_status'] == 'returned' || $serial['return_status'] == 'returned') {
            $actual_status = 'returned';
            $returnedSerialsCount++;
        } else {
            $actual_status = 'assigned';
            $assignedSerialsCount++;
        }
    } 
    // Check if serial is damaged (is_assigned = 2)
    elseif($serial['is_assigned'] == 2) {
        if($serial['damage_status'] == 'repaired') {
            $actual_status = 'available';
            $availableSerialsCount++;
        } else {
            $actual_status = 'damaged';
            $damagedSerialsCount++;
        }
    } 
    // Check if serial has damage_id (is_assigned might be NULL or 0 but has damage)
    elseif(!empty($serial['damage_id'])) {
        if($serial['damage_status'] == 'repaired') {
            $actual_status = 'available';
            $availableSerialsCount++;
        } else {
            $actual_status = 'damaged';
            $damagedSerialsCount++;
        }
    } 
    // Available (is_assigned = 0 or NULL)
    else {
        $actual_status = 'available';
        $availableSerialsCount++;
    }
    
    $serial['actual_status'] = $actual_status;
    $processedSerials[] = $serial;
}

// Use serial counts for accurate stock information
$totalSerials = count($serialNumbers);
$assignedSerialsCount = $assignedSerialsCount;
$availableSerialsCount = $availableSerialsCount;

// If no serials exist, use current_qty from items table
if($totalSerials == 0) {
    $totalSerials = (int)$item['current_qty'];
    $availableSerialsCount = max(0, $totalSerials - $assignedSerialsCount);
}

// Get price history
$stmt = $pdo->prepare("SELECT iph.*, u.full_name as changed_by_name 
                       FROM item_price_history iph 
                       LEFT JOIN users u ON iph.changed_by = u.id 
                       WHERE iph.item_id = ? 
                       ORDER BY iph.revision_number DESC, iph.created_at DESC");
$stmt->execute([$id]);
$priceHistory = $stmt->fetchAll();

// Get assignment history with serial numbers from item_serial_numbers table
$stmt = $pdo->prepare("
    SELECT 
        a.id as assignment_id,
        a.assignment_no, 
        a.quantity,
        a.assigned_date,
        a.returned_date,
        a.status as assignment_status,
        e.id as employee_id,
        e.full_name as employee_name, 
        e.pf_no,
        e.designation,
        GROUP_CONCAT(
            DISTINCT CONCAT(
                COALESCE(isn.serial_number, 'N/A'),
                IFNULL(CONCAT(' (Model: ', isn.model_number, ')'), ''),
                IFNULL(CONCAT(' | Ver: ', isn.version), '')
            ) SEPARATOR '; '
        ) as serial_details,
        COUNT(DISTINCT isn.id) as total_serials
    FROM assignments a 
    JOIN employees e ON a.employee_id = e.id 
    LEFT JOIN item_serial_numbers isn ON a.id = isn.assignment_id AND isn.is_assigned = 1
    WHERE a.item_id = ? 
    GROUP BY a.id
    ORDER BY a.assigned_date DESC
");
$stmt->execute([$id]);
$assignments = $stmt->fetchAll();

// Calculate statistics
$totalStockValue = $item['price'] * $totalSerials;
$lowStockWarning = $totalSerials <= $item['min_qty'];
$outOfStock = $totalSerials == 0;

function getSerialStatusBadge($serial) {
    $actual_status = $serial['actual_status'];
    
    if($actual_status == 'assigned') {
        return '<span class="badge bg-danger"><i class="fas fa-laptop"></i> Assigned</span>';
    } elseif($actual_status == 'returned') {
        return '<span class="badge bg-info"><i class="fas fa-undo-alt"></i> Returned</span>';
    } elseif($actual_status == 'damaged') {
        return '<span class="badge bg-dark"><i class="fas fa-exclamation-triangle"></i> Damaged</span>';
    } else {
        return '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Available</span>';
    }
}

function getSerialItemClass($serial) {
    $actual_status = $serial['actual_status'];
    if($actual_status == 'damaged') return 'damaged';
    if($actual_status == 'returned') return 'returned';
    if($actual_status == 'assigned') return 'assigned';
    return 'available';
}

// Helper function to show damage status badge
function getDamageStatusBadge($serial) {
    if(empty($serial['damage_id'])) {
        return '';
    }
    
    $damage_status = $serial['damage_status'] ?? 'pending';
    $badges = [
        'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'approved' => '<span class="badge bg-primary">Approved</span>',
        'repaired' => '<span class="badge bg-success"><i class="fas fa-check"></i> Repaired</span>',
        'replaced' => '<span class="badge bg-info">Replaced</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>'
    ];
    return $badges[$damage_status] ?? '<span class="badge bg-secondary">Unknown</span>';
}
?>

<style>
    .stat-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }
    .stat-box-primary { background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); }
    .stat-box-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .stat-box-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    
    .detail-section {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .section-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
        position: relative;
    }
    .section-title:after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 60px;
        height: 2px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .detail-row {
        display: flex;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .detail-label {
        width: 180px;
        font-weight: 600;
        color: #475569;
    }
    .detail-value {
        flex: 1;
        color: #1e293b;
    }
    .price-history-item {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        border-left: 3px solid;
    }
    .vendor-badge {
        display: inline-block;
        padding: 8px 15px;
        background: #f1f5f9;
        border-radius: 10px;
        margin: 5px;
        width: 100%;
    }
    .vendor-badge.primary { background: #10b981; color: white; }
    .quantity-box {
        background: #f8fafc;
        border-radius: 10px;
        padding: 10px;
        text-align: center;
    }
    .serial-item {
        background: #f8fafc;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
        border-left: 4px solid;
    }
    .serial-item.available { border-left-color: #10b981; background: #f0fdf4; }
    .serial-item.assigned { border-left-color: #ef4444; background: #fef2f2; }
    .serial-item.damaged { border-left-color: #6b7280; background: #f3f4f6; }
    .serial-item.returned { border-left-color: #06b6d4; background: #ecfeff; }
    
    .damage-info, .assignment-info {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px dashed #e2e8f0;
    }
    .damage-label {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
    }
    .serial-badge {
        display: inline-block;
        background: #f1f5f9;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        margin: 2px;
        font-family: monospace;
    }
    .assignment-table {
        width: 100%;
    }
    .assignment-table th {
        background: #f8fafc;
        font-size: 0.75rem;
        text-transform: uppercase;
        padding: 12px;
        border-bottom: 2px solid #e2e8f0;
    }
    .assignment-table td {
        padding: 12px;
        vertical-align: middle;
    }
    @media (max-width: 768px) {
        .detail-label { width: 130px; }
        .stat-box { margin-bottom: 15px; }
        .assignment-table th, .assignment-table td { padding: 8px; font-size: 0.75rem; }
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-boxes text-primary"></i> Item Details</h2>
            <p class="text-muted">Complete information about the device/inventory item</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit Item</a>
                <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-6"><div class="stat-box"><i class="fas fa-boxes fa-2x mb-2"></i><h3 class="mb-0"><?php echo $totalSerials; ?></h3><p class="mb-0">Total Serial</p><small>Registered items</small></div></div>
        <div class="col-md-3 col-6"><div class="stat-box stat-box-primary"><i class="fas fa-laptop fa-2x mb-2"></i><h3 class="mb-0"><?php echo $assignedSerialsCount; ?></h3><p class="mb-0">Assigned</p><small>Currently in use</small></div></div>
        <div class="col-md-3 col-6"><div class="stat-box stat-box-success"><i class="fas fa-check-circle fa-2x mb-2"></i><h3 class="mb-0"><?php echo $availableSerialsCount; ?></h3><p class="mb-0">Available</p><small>Ready for assignment</small></div></div>
        <div class="col-md-3 col-6"><div class="stat-box stat-box-warning"><i class="fas fa-money-bill-wave fa-2x mb-2"></i><h3 class="mb-0">৳<?php echo number_format($totalStockValue, 2); ?></h3><p class="mb-0">Total Value</p><small>@ ৳<?php echo number_format($item['price'], 2); ?> each</small></div></div>
    </div>

    <!-- MAIN ROW: Left Column (8) + Right Column (4) -->
    <div class="row">
        
        <!-- ============================================ -->
        <!-- LEFT COLUMN (8) -->
        <!-- ============================================ -->
        <div class="col-lg-8">
            
            <!-- Basic Information -->
            <div class="detail-section">
                <div class="section-title"><i class="fas fa-info-circle me-2 text-primary"></i> Basic Information</div>
                <div class="detail-row"><div class="detail-label">Item Code:</div><div class="detail-value"><span class="badge bg-primary"><?php echo htmlspecialchars($item['item_code']); ?></span></div></div>
                <div class="detail-row"><div class="detail-label">Item Name:</div><div class="detail-value"><strong><?php echo htmlspecialchars($item['name']); ?></strong></div></div>
                <div class="detail-row"><div class="detail-label">Brand:</div><div class="detail-value"><?php echo htmlspecialchars($item['brand_name'] ?? 'N/A'); ?></div></div>
                <div class="detail-row"><div class="detail-label">Item Type:</div><div class="detail-value"><?php echo htmlspecialchars($item['type_name'] ?? 'N/A'); ?></div></div>
                <div class="detail-row"><div class="detail-label">Category:</div><div class="detail-value"><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></div></div>
                <div class="detail-row"><div class="detail-label">Sub-Category:</div><div class="detail-value"><?php echo htmlspecialchars($item['sub_category_name'] ?? 'N/A'); ?></div></div>
                <div class="detail-row"><div class="detail-label">Status:</div><div class="detail-value"><span class="badge bg-<?php echo $item['is_active'] ? 'success' : 'danger'; ?>"><?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?></span></div></div>
                <div class="detail-row"><div class="detail-label">Created By:</div><div class="detail-value"><?php echo htmlspecialchars($item['created_by_name'] ?? 'System'); ?></div></div>
                <div class="detail-row"><div class="detail-label">Created Date:</div><div class="detail-value"><?php echo date('d-m-Y h:i A', strtotime($item['created_at'])); ?></div></div>
            </div>

            <!-- Stock Information -->
            <div class="detail-section">
                <div class="section-title"><i class="fas fa-chart-line me-2 text-primary"></i> Stock Information</div>
                <div class="row">
                    <div class="col-md-4"><div class="quantity-box"><div class="text-primary"><i class="fas fa-database fa-2x"></i><h4 class="mt-2 mb-0"><?php echo $totalSerials; ?></h4><small>Total Serial</small></div></div></div>
                    <div class="col-md-4"><div class="quantity-box"><div class="text-warning"><i class="fas fa-laptop fa-2x"></i><h4 class="mt-2 mb-0"><?php echo $assignedSerialsCount; ?></h4><small>Assigned</small></div></div></div>
                    <div class="col-md-4"><div class="quantity-box"><div class="text-success"><i class="fas fa-check-circle fa-2x"></i><h4 class="mt-2 mb-0"><?php echo $availableSerialsCount; ?></h4><small>Available</small></div></div></div>
                </div>
                <div class="detail-row mt-3"><div class="detail-label">Minimum Stock Alert:</div><div class="detail-value"><?php echo $item['min_qty']; ?> units<?php if($lowStockWarning && !$outOfStock): ?><span class="badge bg-warning ms-2">Low Stock</span><?php elseif($outOfStock): ?><span class="badge bg-danger ms-2">Out of Stock</span><?php endif; ?></div></div>
            </div>

            <!-- Device Specifications -->
            <div class="detail-section">
                <div class="section-title"><i class="fas fa-microchip me-2 text-primary"></i> Device Specifications</div>
                <div class="detail-row"><div class="detail-label">Warranty Period:</div><div class="detail-value"><?php echo $item['warranty_period'] > 0 ? $item['warranty_period'] . ' months' : 'No warranty'; ?></div></div>
                <?php if($item['specification']): ?>
                <div class="detail-row"><div class="detail-label">Specifications:</div><div class="detail-value"><div class="bg-light p-3 rounded"><?php echo nl2br(htmlspecialchars($item['specification'])); ?></div></div></div>
                <?php endif; ?>
            </div>

            <!-- Serial Numbers Section - Shows all serials with models -->
            <?php if(count($processedSerials) > 0): ?>
            <div class="detail-section">
                <div class="section-title"><i class="fas fa-qrcode me-2 text-primary"></i> Serial Numbers & Models 
                    <span class="badge bg-success ms-2">Available: <?php echo $availableSerialsCount; ?></span>
                    <span class="badge bg-danger ms-2">Assigned: <?php echo $assignedSerialsCount; ?></span>
                    <span class="badge bg-info ms-2">Returned: <?php echo $returnedSerialsCount; ?></span>
                    <span class="badge bg-dark ms-2">Damaged: <?php echo $damagedSerialsCount; ?></span>
                </div>
                <?php if(count($processedSerials) > 0): ?>
                <div class="row">
                    <?php foreach($processedSerials as $serial): $item_class = getSerialItemClass($serial); ?>
                    <div class="col-md-6">
                        <div class="serial-item <?php echo $item_class; ?>">
                            <div class="d-flex justify-content-between">
                                <strong><i class="fas fa-microchip"></i> <?php echo htmlspecialchars($serial['serial_number']); ?></strong>
                                <?php echo getSerialStatusBadge($serial); ?>
                            </div>
                            <?php if($serial['model_number']): ?>
                            <div class="small text-muted mt-1"><i class="fas fa-tag"></i> Model: <?php echo htmlspecialchars($serial['model_number']); ?></div>
                            <?php endif; ?>
                            <?php if($serial['version']): ?>
                            <div class="small text-muted mt-1"><i class="fas fa-code-branch"></i> Version: <?php echo htmlspecialchars($serial['version']); ?></div>
                            <?php endif; ?>
                            <?php if($serial['actual_status'] == 'damaged' && $serial['damage_id']): ?>
                            <div class="damage-info">
                                <div class="damage-label">DAMAGE INFORMATION</div>
                                <div class="small"><strong>Damage No:</strong> <?php echo htmlspecialchars($serial['damage_no']); ?></div>
                                <div class="small"><strong>Status:</strong> <?php echo getDamageStatusBadge($serial); ?></div>
                                <?php if($serial['damage_type']): ?>
                                <div class="small"><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $serial['damage_type'])); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if($serial['actual_status'] == 'assigned' && $serial['assignment_no']): ?>
                            <div class="assignment-info">
                                <div class="damage-label">ASSIGNMENT INFORMATION</div>
                                <div class="small"><strong>Assignment No:</strong> <a href="../assignments/view.php?id=<?php echo $serial['assignment_id']; ?>" class="text-primary"><?php echo htmlspecialchars($serial['assignment_no']); ?></a></div>
                                <div class="small"><strong>Assigned to:</strong> <?php echo htmlspecialchars($serial['assigned_to_name'] ?? 'N/A'); ?> (<?php echo $serial['assigned_to_pf']; ?>)</div>
                                <div class="small"><strong>Assigned Since:</strong> <?php echo date('d-m-Y', strtotime($serial['assigned_date'])); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if($serial['actual_status'] == 'returned' && $serial['return_request_no']): ?>
                            <div class="damage-info">
                                <div class="damage-label">RETURN INFORMATION</div>
                                <div class="small"><strong>Return No:</strong> <?php echo htmlspecialchars($serial['return_request_no']); ?></div>
                                <div class="small"><strong>Reason:</strong> <?php echo ucfirst(str_replace('_', ' ', $serial['return_reason'] ?? 'N/A')); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-3">No serial numbers for this item.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Vendors -->
            <div class="detail-section">
                <div class="section-title"><i class="fas fa-truck me-2 text-primary"></i> Suppliers / Vendors</div>
                <?php if(count($itemVendors) > 0): ?>
                    <div class="row">
                        <?php foreach($itemVendors as $vendor): ?>
                        <div class="col-md-6 mb-3"><div class="vendor-badge <?php echo $vendor['is_primary'] ? 'primary' : ''; ?>"><div class="d-flex justify-content-between"><div><strong><?php echo htmlspecialchars($vendor['name']); ?></strong><?php if($vendor['is_primary']): ?><span class="badge bg-white text-success ms-2">Primary</span><?php endif; ?><br><small><i class="fas fa-phone"></i> <?php echo $vendor['phone'] ?? 'N/A'; ?><br><i class="fas fa-envelope"></i> <?php echo $vendor['email'] ?? 'N/A'; ?></small></div><div class="text-end"><div class="fw-bold text-success">৳<?php echo number_format($vendor['purchase_price'], 2); ?></div><small>Purchase Price</small></div></div></div></div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No vendors associated.</p>
                <?php endif; ?>
            </div>

            <!-- Assignment History Table - Shows serial numbers from item_serial_numbers -->
            <div class="detail-section">
                <div class="section-title"><i class="fas fa-laptop me-2 text-primary"></i> Assignment History</div>
                <?php if(count($assignments) > 0): ?>
                    <div class="table-responsive">
                        <table class="assignment-table table table-bordered">
                            <thead>
                                <tr>
                                    <th width="15%">Assignment No</th>
                                    <th width="20%">Employee</th>
                                    <th width="40%">Serial Number(s) & Model</th>
                                    <th width="10%">Qty</th>
                                    <th width="15%">Assigned Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($assignments as $assign): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($assign['assignment_no']); ?></strong>
                                        <br><a href="../assignments/view.php?id=<?php echo $assign['assignment_id']; ?>" class="small text-primary">View Details</a>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($assign['employee_name']); ?>
                                        <br><small class="text-muted"><?php echo $assign['pf_no']; ?></small>
                                        <?php if($assign['designation']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($assign['designation']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if(!empty($assign['serial_details']) && $assign['serial_details'] != 'N/A') {
                                            $serial_list = explode('; ', $assign['serial_details']);
                                            foreach($serial_list as $sl) {
                                                echo '<span class="serial-badge">' . htmlspecialchars($sl) . '</span><br>';
                                            }
                                        } else {
                                            echo '<span class="text-muted">—</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center"><?php echo $assign['quantity']; ?></td>
                                    <td class="text-nowrap"><?php echo date('d-m-Y', strtotime($assign['assigned_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No assignment history.</p>
                <?php endif; ?>
            </div>
            
        </div> <!-- END LEFT COLUMN (col-lg-8) -->

        <!-- ============================================ -->
        <!-- RIGHT COLUMN (4) - SIDEBAR -->
        <!-- ============================================ -->
        <div class="col-lg-4">
            
            <!-- Pricing Details -->
            <div class="detail-section">
                <div class="section-title"><i class="fas fa-tags me-2 text-primary"></i> Pricing Details</div>
                <div class="text-center mb-3"><h1 class="text-success">৳<?php echo number_format($item['price'], 2); ?></h1><p class="text-muted">Current Regular Price</p></div>
                <div class="alert alert-info">
                    <div class="d-flex justify-content-between"><span>Unit Price:</span><strong>৳<?php echo number_format($item['price'], 2); ?></strong></div>
                    <div class="d-flex justify-content-between mt-2"><span>Total Serial:</span><strong><?php echo $totalSerials; ?> units</strong></div>
                    <div class="d-flex justify-content-between mt-2"><span>Assigned:</span><strong><?php echo $assignedSerialsCount; ?> units</strong></div>
                    <div class="d-flex justify-content-between mt-2"><span>Available:</span><strong class="text-success"><?php echo $availableSerialsCount; ?> units</strong></div>
                    <hr><div class="d-flex justify-content-between"><span>Total Value:</span><strong class="text-success">৳<?php echo number_format($totalStockValue, 2); ?></strong></div>
                </div>
            </div>

            <!-- Price Change History -->
            <div class="detail-section">
                <div class="section-title"><i class="fas fa-history me-2 text-primary"></i> Price Change History</div>
                <?php if(count($priceHistory) > 0): ?>
                    <?php foreach($priceHistory as $history): ?>
                    <div class="price-history-item" style="border-left-color: <?php echo $history['revision_number'] == $item['revision_count'] ? '#10b981' : '#667eea'; ?>">
                        <div class="d-flex justify-content-between"><span class="badge" style="background: <?php echo $history['revision_number'] == $item['revision_count'] ? '#10b981' : '#667eea'; ?>">Rev #<?php echo $history['revision_number']; ?></span><small><?php echo date('d-m-Y', strtotime($history['created_at'])); ?></small></div>
                        <div class="row text-center mt-2"><div class="col-5"><small>Old Price</small><?php if($history['old_price']): ?><div><del>৳<?php echo number_format($history['old_price'], 2); ?></del></div><?php else: ?><div>—</div><?php endif; ?></div><div class="col-2"><i class="fas fa-arrow-right"></i></div><div class="col-5"><small>New Price</small><div class="text-success fw-bold">৳<?php echo number_format($history['new_price'], 2); ?></div></div></div>
                        <?php if($history['change_reason']): ?><div class="mt-2 small"><i class="fas fa-quote-left"></i> <?php echo htmlspecialchars($history['change_reason']); ?></div><?php endif; ?>
                        <div class="mt-1"><small><i class="fas fa-user"></i> <?php echo $history['changed_by_name'] ?? 'System'; ?></small></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No price changes recorded.</p>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="detail-section">
                <div class="section-title"><i class="fas fa-bolt me-2 text-primary"></i> Quick Actions</div>
                <div class="d-grid gap-2">
                    <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit Item</a>
                    <a href="../stock_in/stock_in.php?item_id=<?php echo $item['id']; ?>" class="btn btn-success"><i class="fas fa-plus-circle"></i> Add Stock</a>
                    <a href="../assignments/assign.php?item_id=<?php echo $item['id']; ?>" class="btn btn-primary"><i class="fas fa-laptop"></i> Assign Device</a>
                </div>
            </div>
            
            <!-- Stock Alert -->
            <?php if($lowStockWarning || $outOfStock): ?>
            <div class="detail-section mt-3"><div class="section-title text-warning"><i class="fas fa-exclamation-triangle me-2"></i> Stock Alert</div><div class="alert alert-warning mb-0"><?php if($outOfStock): ?><i class="fas fa-ban me-2"></i> <strong>OUT OF STOCK</strong>. Please restock.<?php else: ?><i class="fas fa-exclamation-triangle me-2"></i> <strong>LOW STOCK</strong><br>Current: <?php echo $totalSerials; ?> | Min: <?php echo $item['min_qty']; ?><?php endif; ?></div></div>
            <?php endif; ?>
            
            <!-- Damage Summary -->
            <?php if($damagedSerialsCount > 0): ?>
            <div class="detail-section mt-3"><div class="section-title text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Damage Summary</div><div class="alert alert-danger mb-0"><i class="fas fa-tools me-2"></i> <strong><?php echo $damagedSerialsCount; ?> unit(s)</strong> damaged.</div></div>
            <?php endif; ?>
            
            <!-- Return Summary -->
            <?php if($returnedSerialsCount > 0): ?>
            <div class="detail-section mt-3"><div class="section-title text-info"><i class="fas fa-undo-alt me-2"></i> Return Summary</div><div class="alert alert-info mb-0"><i class="fas fa-check-circle me-2"></i> <strong><?php echo $returnedSerialsCount; ?> unit(s)</strong> returned to stock.</div></div>
            <?php endif; ?>
            
        </div> <!-- END RIGHT COLUMN (col-lg-4) -->
        
    </div> <!-- END MAIN ROW -->
</div> <!-- END CONTAINER -->

<?php include '../../includes/footer.php'; ?>