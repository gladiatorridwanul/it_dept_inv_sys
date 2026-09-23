<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Ensure PDO connection is available
if (!isset($pdo) || !$pdo) {
    die("Database connection error. Please check your configuration.");
}

// Check if damages table exists
$table_exists = false;
try {
    $check_table = $pdo->query("SHOW TABLES LIKE 'damages'");
    $table_exists = $check_table->rowCount() > 0;
} catch(PDOException $e) {
    error_log("Table check error: " . $e->getMessage());
    $table_exists = false;
}

// If table doesn't exist, show message
if(!$table_exists) {
    ?>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2><i class="fas fa-tools text-danger me-2"></i> Damage Management</h2>
                <hr>
            </div>
        </div>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i> 
            <strong>Damage table not found!</strong> Please run the SQL script to create the damages table.
        </div>
    </div>
    <?php
    include '../../includes/footer.php';
    exit();
}

// Display success message from session
if(isset($_SESSION['damage_success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> ' . htmlspecialchars($_SESSION['damage_success']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['damage_success']);
}

if(isset($_SESSION['damage_error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> ' . htmlspecialchars($_SESSION['damage_error']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['damage_error']);
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$severity_filter = isset($_GET['severity']) ? $_GET['severity'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query conditions
$conditions = [];
$params = [];

if($status_filter != 'all') {
    $conditions[] = "d.status = ?";
    $params[] = $status_filter;
}
if($severity_filter != 'all') {
    $conditions[] = "d.damage_severity = ?";
    $params[] = $severity_filter;
}
if($date_from) {
    $conditions[] = "DATE(d.damage_date) >= ?";
    $params[] = $date_from;
}
if($date_to) {
    $conditions[] = "DATE(d.damage_date) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get damages with proper joins including item price
$damages = [];
$total_estimated_cost = 0;
$total_actual_cost = 0;
$total_item_price = 0;

try {
    // Get damages with item price information
    $query = "
        SELECT 
            d.*,
            i.name as item_name,
            i.item_code,
            i.price as item_price,
            i.brand as item_brand,
            i.serial_number as item_serial,
            i.model_number as item_model,
            i.version as item_version,
            e.full_name as employee_name,
            e.pf_no,
            e.department,
            e.designation,
            u.username as reported_by_name,
            au.username as approved_by_name
        FROM damages d
        LEFT JOIN items i ON d.item_id = i.id
        LEFT JOIN employees e ON d.employee_id = e.id
        LEFT JOIN users u ON d.reported_by = u.id
        LEFT JOIN users au ON d.approved_by = au.id
        $where_clause
        ORDER BY d.created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $damages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    foreach($damages as $damage) {
        $total_estimated_cost += (float)($damage['estimated_cost'] ?? 0);
        $total_actual_cost += (float)($damage['actual_cost'] ?? 0);
        $total_item_price += (float)($damage['item_price'] ?? 0);
    }
    
} catch(PDOException $e) {
    error_log("Error loading damages: " . $e->getMessage());
    $damages = [];
}

// Get statistics for dashboard
$stats = [
    'total' => 0, 'pending' => 0, 'approved' => 0, 'repaired' => 0, 
    'replaced' => 0, 'rejected' => 0, 'minor' => 0, 'moderate' => 0, 
    'severe' => 0, 'critical' => 0
];

try {
    $stats_query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'repaired' THEN 1 ELSE 0 END) as repaired,
            SUM(CASE WHEN status = 'replaced' THEN 1 ELSE 0 END) as replaced,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN damage_severity = 'minor' THEN 1 ELSE 0 END) as minor,
            SUM(CASE WHEN damage_severity = 'moderate' THEN 1 ELSE 0 END) as moderate,
            SUM(CASE WHEN damage_severity = 'severe' THEN 1 ELSE 0 END) as severe,
            SUM(CASE WHEN damage_severity = 'critical' THEN 1 ELSE 0 END) as critical
        FROM damages d
        $where_clause
    ";
    $stmt = $pdo->prepare($stats_query);
    $stmt->execute($params);
    $stats_result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($stats_result) {
        $stats = array_merge($stats, $stats_result);
    }
} catch(PDOException $e) {
    error_log("Error loading stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }
        .stats-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .stats-number { font-size: 28px; font-weight: 800; margin-bottom: 5px; }
        .stats-label { font-size: 11px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
        .stats-icon { font-size: 28px; margin-bottom: 10px; }
        .status-badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #dbeafe; color: #2563eb; }
        .status-repaired { background: #d1fae5; color: #059669; }
        .status-replaced { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        .severity-minor { background: #d1fae5; color: #059669; }
        .severity-moderate { background: #fef3c7; color: #d97706; }
        .severity-severe { background: #fed7aa; color: #ea580c; }
        .severity-critical { background: #fee2e2; color: #dc2626; }
        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .btn-action {
            padding: 4px 8px;
            margin: 2px;
            font-size: 11px;
            border-radius: 6px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .price-cell {
            font-weight: 600;
            text-align: right;
        }
        .badge-item {
            background: #e0e7ff;
            color: #3730a3;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
        }
        .serial-number {
            font-family: monospace;
            font-size: 10px;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
        }
        /* Smaller font for table */
        .table th, .table td {
            font-size: 11px;
            padding: 8px 6px;
        }
        .table th {
            font-weight: 600;
            white-space: nowrap;
        }
        .btn-sm {
            padding: 3px 6px;
            font-size: 10px;
        }
        .form-label {
            font-size: 11px;
        }
        .form-select, .form-control {
            font-size: 11px;
            padding: 5px 8px;
        }
        .filter-card h5 {
            font-size: 14px;
        }
        .stats-number {
            font-size: 24px;
        }
        .stats-label {
            font-size: 10px;
        }
        /* Damage Details Modal Styles */
        .detail-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .detail-label {
            width: 140px;
            font-weight: 600;
            color: #475569;
            flex-shrink: 0;
        }
        .detail-value {
            flex: 1;
            color: #1e293b;
        }
        .detail-value .badge {
            font-size: 12px;
        }
        .detail-section {
            margin-bottom: 15px;
        }
        .detail-section-title {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e2e8f0;
        }
        .modal-body {
            padding: 20px 25px;
            max-height: 70vh;
            overflow-y: auto;
        }
        .attachment-link {
            display: inline-block;
            margin-top: 5px;
        }
        .attachment-link i {
            margin-right: 5px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-tools text-danger me-2"></i> Damage Management</h2>
                    <p class="text-muted">Manage and track all device damage reports</p>
                </div>
                <div>
                    <a href="damage.php" class="btn btn-danger">
                        <i class="fas fa-plus me-2"></i> Report New Damage
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-tools text-warning"></i></div>
                <div class="stats-number"><?php echo number_format($stats['total']); ?></div>
                <div class="stats-label">Total Damages</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-spinner text-warning"></i></div>
                <div class="stats-number"><?php echo number_format($stats['pending']); ?></div>
                <div class="stats-label">Pending</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-check-circle text-success"></i></div>
                <div class="stats-number"><?php echo number_format(($stats['repaired'] ?? 0) + ($stats['replaced'] ?? 0)); ?></div>
                <div class="stats-label">Resolved</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-chart-line text-primary"></i></div>
                <div class="stats-number"><?php echo number_format($stats['critical']); ?></div>
                <div class="stats-label">Critical</div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <h5 class="mb-3"><i class="fas fa-filter me-2"></i> Filter Damages</h5>
        <form method="GET" class="row g-3" id="filterForm">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="repaired" <?php echo $status_filter == 'repaired' ? 'selected' : ''; ?>>Repaired</option>
                    <option value="replaced" <?php echo $status_filter == 'replaced' ? 'selected' : ''; ?>>Replaced</option>
                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Severity</label>
                <select name="severity" class="form-select">
                    <option value="all" <?php echo $severity_filter == 'all' ? 'selected' : ''; ?>>All Severity</option>
                    <option value="minor" <?php echo $severity_filter == 'minor' ? 'selected' : ''; ?>>Minor</option>
                    <option value="moderate" <?php echo $severity_filter == 'moderate' ? 'selected' : ''; ?>>Moderate</option>
                    <option value="severe" <?php echo $severity_filter == 'severe' ? 'selected' : ''; ?>>Severe</option>
                    <option value="critical" <?php echo $severity_filter == 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            <div class="col-md-12 text-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i> Apply Filters</button>
                <a href="list.php" class="btn btn-secondary ms-2"><i class="fas fa-undo me-2"></i> Reset</a>
            </div>
        </form>
    </div>

    <!-- Damage Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i> Damage Records</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="damageTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;">ID</th>
                            <th>Damage No</th>
                            <th>Date</th>
                            <th>Item Details</th>
                            <th>Serial / Model / Version</th>
                            <th>Item Price (TK)</th>
                            <th>Damage Type</th>
                            <th>Severity</th>
                            <th>Est. Cost (TK)</th>
                            <th>Actual Cost (TK)</th>
                            <th>Status</th>
                            <th style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($damages)): ?>
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle me-2"></i> No damage records found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($damages as $damage): ?>
                                <tr>
                                    <td class="text-center"><?php echo (int)$damage['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($damage['damage_no'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo date('d-m-Y', strtotime($damage['damage_date'] ?? $damage['created_at'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($damage['item_name'] ?? 'N/A'); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($damage['item_code'] ?? 'N/A'); ?></small>
                                        <?php if (!empty($damage['item_brand'])): ?>
                                            <br><small><span class="badge-item"><?php echo htmlspecialchars($damage['item_brand']); ?></span></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($damage['item_serial'])): ?>
                                            <div><span class="serial-number">SN: <?php echo htmlspecialchars($damage['item_serial']); ?></span></div>
                                        <?php endif; ?>
                                        <?php if (!empty($damage['item_model'])): ?>
                                            <div><small>Model: <?php echo htmlspecialchars($damage['item_model']); ?></small></div>
                                        <?php endif; ?>
                                        <?php if (!empty($damage['item_version'])): ?>
                                            <div><small>Ver: <?php echo htmlspecialchars($damage['item_version']); ?></small></div>
                                        <?php endif; ?>
                                        <?php if (empty($damage['item_serial']) && empty($damage['item_model']) && empty($damage['item_version'])): ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="price-cell">
                                        <?php if (!empty($damage['item_price']) && $damage['item_price'] > 0): ?>
                                            <strong><?php echo number_format($damage['item_price'], 2); ?></strong>
                                            <br><small class="text-muted">TK</small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $damage['damage_type'] ?? 'N/A')); ?></td>
                                    <td>
                                        <span class="status-badge severity-<?php echo $damage['damage_severity'] ?? 'minor'; ?>">
                                            <?php echo ucfirst($damage['damage_severity'] ?? 'Minor'); ?>
                                        </span>
                                    </td>
                                    <td class="price-cell">
                                        <?php if (!empty($damage['estimated_cost']) && $damage['estimated_cost'] > 0): ?>
                                            <?php echo number_format($damage['estimated_cost'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="price-cell">
                                        <?php if (!empty($damage['actual_cost']) && $damage['actual_cost'] > 0): ?>
                                            <?php echo number_format($damage['actual_cost'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $damage['status'] ?? 'pending'; ?>">
                                            <?php echo ucfirst($damage['status'] ?? 'Pending'); ?>
                                        </span>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-sm btn-outline-info" onclick="viewDamage(<?php echo (int)$damage['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="print_damage.php?id=<?php echo (int)$damage['id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Report">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php if(($damage['status'] ?? '') == 'pending'): ?>
                                                <button class="btn btn-sm btn-outline-success" onclick="updateStatus(<?php echo (int)$damage['id']; ?>, 'approved')" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="updateStatus(<?php echo (int)$damage['id']; ?>, 'rejected')" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if(($damage['status'] ?? '') == 'approved'): ?>
                                                <button class="btn btn-sm btn-outline-warning" onclick="showRepairModal(<?php echo (int)$damage['id']; ?>, '<?php echo htmlspecialchars(addslashes($damage['item_name'] ?? 'Unknown Item')); ?>')" title="Mark as Repaired">
                                                    <i class="fas fa-wrench"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" onclick="showReplaceModal(<?php echo (int)$damage['id']; ?>, '<?php echo htmlspecialchars(addslashes($damage['item_name'] ?? 'Unknown Item')); ?>')" title="Replace Item">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($damages)): ?>
                        <tfoot>
                            <tr class="table-secondary">
                                <th colspan="5" class="text-end">Totals:</th>
                                <th class="price-cell"><strong><?php echo number_format($total_item_price, 2); ?> TK</strong></th>
                                <th colspan="2"></th>
                                <th class="price-cell"><strong><?php echo number_format($total_estimated_cost, 2); ?> TK</strong></th>
                                <th class="price-cell"><strong><?php echo number_format($total_actual_cost, 2); ?> TK</strong></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Damage Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> Damage Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewDetails">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="mt-2 text-muted">Loading damage details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="statusModalHeader">
                <h5 class="modal-title">Update Damage Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="statusForm" action="update_damage.php">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="damage_id" id="status_damage_id">
                    <input type="hidden" name="new_status" id="status_new_status">
                    <div id="statusFormFields"></div>
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Repair Modal -->
<div class="modal fade" id="repairModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-wrench me-2"></i> Record Repair Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="update_damage.php" id="repairForm">
                    <input type="hidden" name="action" value="repair">
                    <input type="hidden" name="damage_id" id="repair_damage_id">
                    <div class="mb-3">
                        <label class="form-label">Item</label>
                        <input type="text" id="repair_item_name" class="form-control bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Actual Repair Cost (TK)</label>
                        <input type="number" name="actual_cost" class="form-control" step="0.01" placeholder="Enter actual repair cost" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Repair Notes</label>
                        <textarea name="repair_notes" rows="3" class="form-control" placeholder="Describe the repair performed..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Repaired By</label>
                        <input type="text" name="repaired_by" class="form-control" placeholder="Name of repair technician/vendor">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Repaired Date</label>
                        <input type="date" name="repaired_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Mark as Repaired</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Replace Modal -->
<div class="modal fade" id="replaceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i> Record Replacement Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="update_damage.php" id="replaceForm">
                    <input type="hidden" name="action" value="replace">
                    <input type="hidden" name="damage_id" id="replace_damage_id">
                    <div class="mb-3">
                        <label class="form-label">Damaged Item</label>
                        <input type="text" id="replace_item_name" class="form-control bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Replacement Item</label>
                        <select name="replacement_item_id" class="form-select select2-item" required>
                            <option value="">-- Select Replacement Item --</option>
                            <?php
                            try {
                                $items = $pdo->query("SELECT id, name, item_code, price FROM items WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                                foreach($items as $item): ?>
                                    <option value="<?php echo (int)$item['id']; ?>">
                                        <?php echo htmlspecialchars($item['item_code'] . ' - ' . $item['name']); ?>
                                        <?php if($item['price'] > 0): ?> (<?php echo number_format($item['price'], 2); ?> TK)<?php endif; ?>
                                    </option>
                                <?php endforeach;
                            } catch(PDOException $e) {
                                error_log("Error loading items: " . $e->getMessage());
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Replacement Cost (TK)</label>
                        <input type="number" name="actual_cost" class="form-control" step="0.01" placeholder="Enter replacement cost">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Replacement Notes</label>
                        <textarea name="repair_notes" rows="3" class="form-control" placeholder="Additional notes about replacement..."></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info">Mark as Replaced</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable only if there are records
    if ($('#damageTable tbody tr').length > 0 && $('#damageTable tbody tr td[colspan]').length === 0) {
        $('#damageTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries"
            },
            responsive: true
        });
    }
    
    // Initialize Select2 if the element exists
    if ($('.select2-item').length) {
        $('.select2-item').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#replaceModal'),
            width: '100%'
        });
    }
});

function viewDamage(id) {
    console.log('Viewing damage ID:', id);
    
    // Show loading state
    $('#viewDetails').html(`
        <div class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x text-muted"></i>
            <p class="mt-3 text-muted">Loading damage details...</p>
        </div>
    `);
    
    // Show modal
    $('#viewModal').modal('show');
    
    // AJAX request to get damage details
    $.ajax({
        url: 'ajax/get_damage_details.php',
        method: 'POST',
        data: { id: id },
        dataType: 'html',
        timeout: 15000,
        success: function(response) {
            console.log('Response received, length:', response.length);
            if (response && response.trim().length > 0) {
                $('#viewDetails').html(response);
            } else {
                $('#viewDetails').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No details found for this damage record.
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            let errorMsg = 'Error loading details. Please try again.';
            try {
                if (xhr.responseText) {
                    const jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse.error) {
                        errorMsg = jsonResponse.error;
                    }
                }
            } catch(e) {
                if (xhr.responseText && xhr.responseText.includes('<html')) {
                    errorMsg = 'Server error occurred. Please check the server logs.';
                } else if (xhr.responseText && xhr.responseText.trim().length > 0 && xhr.responseText.trim().length < 200) {
                    errorMsg = xhr.responseText.trim();
                }
            }
            
            $('#viewDetails').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${errorMsg}
                </div>
                <div class="text-muted small mt-2">
                    <strong>Debug Info:</strong><br>
                    Status: ${xhr.status} ${xhr.statusText}<br>
                    <button class="btn btn-sm btn-outline-secondary mt-2" onclick="viewDamage(${id})">
                        <i class="fas fa-redo me-1"></i> Retry
                    </button>
                </div>
            `);
        }
    });
}

function updateStatus(id, status) {
    let title = '';
    let fields = '';
    
    if(status == 'approved') {
        title = 'Approve Damage Report';
        fields = '<div class="mb-3"><label class="form-label">Approval Notes</label><textarea name="notes" rows="3" class="form-control" placeholder="Enter approval notes..."></textarea></div>';
        $('#statusModalHeader').removeClass().addClass('modal-header bg-success text-white');
    } else if(status == 'rejected') {
        title = 'Reject Damage Report';
        fields = '<div class="mb-3"><label class="form-label">Rejection Reason</label><textarea name="notes" rows="3" class="form-control" required placeholder="Enter rejection reason..."></textarea></div>';
        $('#statusModalHeader').removeClass().addClass('modal-header bg-danger text-white');
    }
    
    $('#statusModalHeader h5').text(title);
    $('#status_damage_id').val(id);
    $('#status_new_status').val(status);
    $('#statusFormFields').html(fields);
    $('#statusModal').modal('show');
}

function showRepairModal(id, itemName) {
    $('#repair_damage_id').val(id);
    $('#repair_item_name').val(itemName);
    $('#repairModal').modal('show');
}

function showReplaceModal(id, itemName) {
    $('#replace_damage_id').val(id);
    $('#replace_item_name').val(itemName);
    $('#replaceModal').modal('show');
}

// Handle Status Form Submit
$('#statusForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    $.ajax({
        url: 'update_damage.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: true
                }).then(() => {
                    window.location.href = 'list.php';
                });
            } else {
                Swal.fire('Error!', response.message || 'Something went wrong', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            Swal.fire('Error!', 'Something went wrong. Please try again.', 'error');
        }
    });
});

// Handle Repair Form Submit
$('#repairForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    $.ajax({
        url: 'update_damage.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: true
                }).then(() => {
                    window.location.href = 'list.php';
                });
            } else {
                Swal.fire('Error!', response.message || 'Something went wrong', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            Swal.fire('Error!', 'Something went wrong. Please try again.', 'error');
        }
    });
});

// Handle Replace Form Submit
$('#replaceForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    $.ajax({
        url: 'update_damage.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: true
                }).then(() => {
                    window.location.href = 'list.php';
                });
            } else {
                Swal.fire('Error!', response.message || 'Something went wrong', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            Swal.fire('Error!', 'Something went wrong. Please try again.', 'error');
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>