<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

if(!canView($pdo, $_SESSION['role'], 'reports') && $_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? 'all';

// ============================================
// FIXED: Get return statistics with proper joins
// ============================================
$stats_query = "
    SELECT 
        COUNT(DISTINCT r.id) as total_returns,
        SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_returns,
        SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_returns,
        SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_returns,
        SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected_returns,
        SUM(CASE WHEN r.status = 'processing' THEN 1 ELSE 0 END) as processing_returns,
        COALESCE(SUM(rdr.total_devices), 0) as total_devices_returned
    FROM return_device_requests rdr
    JOIN requests r ON rdr.request_id = r.id
    WHERE DATE(rdr.created_at) BETWEEN ? AND ?
";
$stmt = $pdo->prepare($stats_query);
$stmt->execute([$date_from, $date_to]);
$stats = $stmt->fetch();

// ============================================
// FIXED: Get detailed return list with proper status
// ============================================
$returns_query = "
    SELECT 
        rdr.id as return_request_id,
        rdr.request_no,
        rdr.return_reason,
        rdr.reason_details,
        rdr.accessories_returned,
        rdr.damage_description,
        rdr.cleaning_done,
        rdr.data_backup_confirmed,
        rdr.exit_clearance,
        rdr.total_devices,
        rdr.created_at as return_created_at,
        r.id as request_id,
        r.status as request_status,
        r.requested_date,
        r.created_at as request_created_at,
        r.resolution_notes,
        e.id as employee_id,
        e.pf_no,
        e.full_name as employee_name,
        e.department,
        e.designation,
        e.phone as employee_phone,
        e.email as employee_email,
        a.assignment_no,
        a.assigned_date,
        a.expected_return_date,
        i.name as item_name,
        i.item_code,
        i.serial_number as item_serial,
        (SELECT COUNT(*) FROM return_device_items WHERE return_request_id = rdr.id) as device_items_count,
        ra.id as approval_id,
        ra.action as last_action,
        ra.notes as approval_notes,
        ra.stock_updated,
        ra.created_at as approval_date,
        u.username as processed_by_name
    FROM return_device_requests rdr
    JOIN requests r ON rdr.request_id = r.id
    JOIN employees e ON r.employee_id = e.id
    LEFT JOIN assignments a ON rdr.assignment_id = a.id
    LEFT JOIN items i ON a.item_id = i.id
    LEFT JOIN return_approvals ra ON rdr.id = ra.return_request_id AND ra.id = (
        SELECT id FROM return_approvals WHERE return_request_id = rdr.id ORDER BY id DESC LIMIT 1
    )
    LEFT JOIN users u ON ra.processed_by = u.id
    WHERE DATE(rdr.created_at) BETWEEN ? AND ?
";

if($status_filter != 'all') {
    $returns_query .= " AND r.status = ?";
    $stmt = $pdo->prepare($returns_query);
    $stmt->execute([$date_from, $date_to, $status_filter]);
} else {
    $stmt = $pdo->prepare($returns_query);
    $stmt->execute([$date_from, $date_to]);
}
$returns = $stmt->fetchAll();

// Get devices for each return request
foreach($returns as $key => $return) {
    $devices_stmt = $pdo->prepare("
        SELECT rdi.*, 
               a.assignment_no,
               i.name as item_name,
               i.item_code,
               i.serial_number,
               e.full_name as previous_employee
        FROM return_device_items rdi
        JOIN assignments a ON rdi.assignment_id = a.id
        JOIN items i ON a.item_id = i.id
        LEFT JOIN employees e ON a.employee_id = e.id
        WHERE rdi.return_request_id = ?
    ");
    $devices_stmt->execute([$return['return_request_id']]);
    $returns[$key]['devices'] = $devices_stmt->fetchAll();
}

// Get approval history for each return
foreach($returns as $key => $return) {
    $approvals_stmt = $pdo->prepare("
        SELECT ra.*, u.username as processed_by_name
        FROM return_approvals ra
        LEFT JOIN users u ON ra.processed_by = u.id
        WHERE ra.return_request_id = ?
        ORDER BY ra.created_at DESC
    ");
    $approvals_stmt->execute([$return['return_request_id']]);
    $returns[$key]['approval_history'] = $approvals_stmt->fetchAll();
}
?>

<style>
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: transform 0.2s;
        height: 100%;
    }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-number { font-size: 32px; font-weight: 700; }
    .stat-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-approved { background: #dbeafe; color: #2563eb; }
    .status-processing { background: #e0e7ff; color: #4338ca; }
    .status-completed { background: #d1fae5; color: #059669; }
    .status-rejected { background: #fee2e2; color: #dc2626; }
    .filter-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    @media print {
        .no-print, .btn, .navbar, .sidebar-wrapper, form, footer { display: none !important; }
        .main-content-wrapper { margin-left: 0 !important; padding: 0 !important; }
        body { background: white; }
        .stat-card { box-shadow: none; border: 1px solid #ddd; }
    }
    .device-details {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
        padding-left: 15px;
        border-left: 2px solid #e2e8f0;
    }
    .expand-icon {
        cursor: pointer;
        transition: transform 0.2s;
    }
    .expand-icon:hover {
        color: #3b82f6;
    }
    .row-details {
        background-color: #f8fafc;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-chart-line text-primary me-2"></i>Device Return Report</h4>
            <p class="text-muted small">Return statistics, history, and approval tracking</p>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary me-2">
                <i class="fas fa-print me-1"></i> Print
            </button>
            <button onclick="exportToExcel()" class="btn btn-sm btn-outline-success">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </button>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card no-print">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Status</label>
                <select name="status" class="form-select">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card border-start border-4 border-primary">
                <div class="stat-number text-primary"><?php echo number_format($stats['total_returns'] ?? 0); ?></div>
                <div class="stat-label">Total Return Requests</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card border-start border-4 border-warning">
                <div class="stat-number text-warning"><?php echo number_format($stats['pending_returns'] ?? 0); ?></div>
                <div class="stat-label">Pending Approval</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card border-start border-4 border-success">
                <div class="stat-number text-success"><?php echo number_format(($stats['completed_returns'] ?? 0) + ($stats['approved_returns'] ?? 0)); ?></div>
                <div class="stat-label">Approved/Completed</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card border-start border-4 border-info">
                <div class="stat-number text-info"><?php echo number_format($stats['total_devices_returned'] ?? 0); ?></div>
                <div class="stat-label">Total Devices Returned</div>
            </div>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h6 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i>Return Request History</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="returnTable">
                    <thead class="table-light">
                        <tr>
                            <th width="30"></th>
                            <th>Request No</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Devices</th>
                            <th>Return Reason</th>
                            <th>Status</th>
                            <th>Requested On</th>
                            <th>Completed On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($returns as $ret): ?>
                        <tr>
                            <td class="text-center">
                                <?php if(!empty($ret['devices'])): ?>
                                    <i class="fas fa-chevron-right expand-icon text-muted" onclick="toggleRowDetails(<?php echo $ret['return_request_id']; ?>)" style="cursor: pointer;"></i>
                                <?php endif; ?>
                             </div>
                            <td><strong><?php echo htmlspecialchars($ret['request_no'] ?? 'N/A'); ?></strong> </div>
                            <td>
                                <strong><?php echo htmlspecialchars($ret['employee_name'] ?? 'N/A'); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($ret['pf_no'] ?? ''); ?></small>
                                <br><small><?php echo htmlspecialchars($ret['designation'] ?: ''); ?></small>
                             </div>
                            <td><?php echo htmlspecialchars($ret['department'] ?: 'N/A'); ?> </div>
                            <td>
                                <span class="badge bg-secondary"><?php echo $ret['total_devices'] ?? count($ret['devices']); ?></span>
                                <?php if(($ret['total_devices'] ?? 0) > 1): ?>
                                    <small class="text-muted"> devices</small>
                                <?php endif; ?>
                             </div>
                            <td>
                                <?php 
                                    $reason_display = str_replace('_', ' ', $ret['return_reason'] ?? 'N/A');
                                    echo ucfirst($reason_display);
                                ?>
                                <?php if(!empty($ret['reason_details'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($ret['reason_details'], 0, 50)); ?></small>
                                <?php endif; ?>
                             </div>
                            <td>
                                <?php
                                    $status = $ret['request_status'] ?? 'pending';
                                    $statusClass = match($status) {
                                        'pending' => 'pending',
                                        'approved' => 'approved',
                                        'processing' => 'processing',
                                        'completed' => 'completed',
                                        'rejected' => 'rejected',
                                        default => 'pending'
                                    };
                                ?>
                                <span class="status-badge status-<?php echo $statusClass; ?>">
                                    <i class="fas 
                                        <?php echo $status == 'pending' ? 'fa-clock' : ($status == 'approved' ? 'fa-check-circle' : ($status == 'completed' ? 'fa-check-double' : ($status == 'rejected' ? 'fa-times-circle' : 'fa-spinner'))); ?> 
                                        me-1"></i>
                                    <?php echo ucfirst($status); ?>
                                </span>
                                <?php if(!empty($ret['stock_updated'])): ?>
                                    <br><small class="text-success"><i class="fas fa-check"></i> Stock updated</small>
                                <?php endif; ?>
                             </div>
                            <td><?php echo date('d-m-Y H:i', strtotime($ret['request_created_at'] ?? $ret['return_created_at'] ?? 'now')); ?> </div>
                            <td>
                                <?php 
                                    if(in_array($status, ['completed', 'rejected']) && !empty($ret['approval_date'])) {
                                        echo date('d-m-Y', strtotime($ret['approval_date']));
                                    } elseif($status == 'approved' && !empty($ret['approval_date'])) {
                                        echo date('d-m-Y', strtotime($ret['approval_date']));
                                    } else {
                                        echo '-';
                                    }
                                ?>
                             </div>
                        </tr>
                        <?php if(!empty($ret['devices'])): ?>
                        <tr class="row-details" id="details-row-<?php echo $ret['return_request_id']; ?>" style="display: none;">
                            <td colspan="9" class="p-3">
                                <div class="device-details">
                                    <strong><i class="fas fa-laptop me-2"></i>Returned Devices:</strong>
                                    <div class="table-responsive mt-2">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Assignment No</th>
                                                    <th>Item Name</th>
                                                    <th>Item Code</th>
                                                    <th>Serial Number</th>
                                                    <th>Device Condition</th>
                                                    <th>Damage Description</th>
                                                    <th>Accessories Returned</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($ret['devices'] as $device): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($device['assignment_no'] ?? 'N/A'); ?> </div>
                                                    <td><?php echo htmlspecialchars($device['item_name'] ?? 'N/A'); ?> </div>
                                                    <td><?php echo htmlspecialchars($device['item_code'] ?? 'N/A'); ?> </div>
                                                    <td><?php echo htmlspecialchars($device['serial_number'] ?? 'N/A'); ?> </div>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?php echo ucfirst(str_replace('_', ' ', $device['device_condition'] ?? 'good')); ?>
                                                        </span>
                                                     </div>
                                                    <td><?php echo htmlspecialchars(substr($device['damage_description'] ?? '', 0, 100)); ?> </div>
                                                    <td><?php echo htmlspecialchars($device['accessories_returned'] ?? 'None'); ?> </div>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if(!empty($ret['approval_history'])): ?>
                                    <div class="mt-3">
                                        <strong><i class="fas fa-history me-2"></i>Approval History:</strong>
                                        <ul class="list-unstyled mt-1">
                                            <?php foreach($ret['approval_history'] as $approval): ?>
                                            <li class="small text-muted">
                                                <i class="fas 
                                                    <?php echo $approval['action'] == 'approved' ? 'fa-check-circle text-success' : ($approval['action'] == 'rejected' ? 'fa-times-circle text-danger' : 'fa-clock text-warning'); ?> 
                                                    me-1"></i>
                                                <strong><?php echo ucfirst($approval['action']); ?></strong> 
                                                by <?php echo htmlspecialchars($approval['processed_by_name'] ?? 'System'); ?>
                                                on <?php echo date('d-m-Y H:i', strtotime($approval['created_at'])); ?>
                                                <?php if(!empty($approval['notes'])): ?>
                                                    - <?php echo htmlspecialchars(substr($approval['notes'], 0, 100)); ?>
                                                <?php endif; ?>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                    <?php if(!empty($ret['resolution_notes'])): ?>
                                    <div class="mt-2">
                                        <strong><i class="fas fa-sticky-note me-2"></i>Resolution Notes:</strong>
                                        <p class="small text-muted mt-1"><?php echo nl2br(htmlspecialchars($ret['resolution_notes'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if(empty($returns)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No return records found in selected date range
                             </div>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                 </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRowDetails(returnRequestId) {
    var row = document.getElementById('details-row-' + returnRequestId);
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}

function exportToExcel() {
    let table = document.getElementById('returnTable');
    let html = table.outerHTML;
    let url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    let downloadLink = document.createElement('a');
    downloadLink.href = url;
    downloadLink.download = 'return_report_' + new Date().toISOString().slice(0,10) + '.xls';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

<?php include '../../includes/footer.php'; ?>