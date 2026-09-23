<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Check if damages table exists
$table_exists = false;
try {
    $check_table = $pdo->query("SHOW TABLES LIKE 'damages'");
    $table_exists = $check_table->rowCount() > 0;
} catch(PDOException $e) {
    $table_exists = false;
}

// If table doesn't exist, show message
if(!$table_exists) {
    ?>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2><i class="fas fa-chart-line text-danger me-2"></i> Damage Report</h2>
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

// Get month/year filter
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$department_filter = isset($_GET['department']) ? $_GET['department'] : 'all';

// Get month start and end dates
$month_start = date('Y-m-01', strtotime($selected_month . '-01'));
$month_end = date('Y-m-t', strtotime($selected_month . '-01'));

// Build query conditions
$conditions = [];
$params = [];

$conditions[] = "DATE(d.created_at) BETWEEN ? AND ?";
$params[] = $month_start;
$params[] = $month_end;

if($status_filter != 'all') {
    $conditions[] = "d.status = ?";
    $params[] = $status_filter;
}
if($department_filter != 'all') {
    $conditions[] = "e.department = ?";
    $params[] = $department_filter;
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get damage records
$damages = [];
$total_estimated_cost = 0;
$total_actual_cost = 0;

try {
    // Check if columns exist before selecting them
    $columns = $pdo->query("SHOW COLUMNS FROM damages")->fetchAll(PDO::FETCH_COLUMN);
    
    // Build select fields based on existing columns
    $select_fields = ['d.id', 'd.created_at', 'd.status', 'd.damage_description', 'd.estimated_cost', 'd.actual_cost'];
    if(in_array('damage_type', $columns)) $select_fields[] = 'd.damage_type';
    if(in_array('damage_no', $columns)) $select_fields[] = 'd.damage_no';
    if(in_array('damage_date', $columns)) $select_fields[] = 'd.damage_date';
    
    $query = "
        SELECT " . implode(', ', $select_fields) . ",
               e.full_name as employee_name, 
               e.pf_no, 
               e.department, 
               e.designation,
               i.name as item_name,
               i.item_code,
               i.serial_number as item_serial,
               u.username as reported_by_name
        FROM damages d
        LEFT JOIN employees e ON d.employee_id = e.id
        LEFT JOIN items i ON d.item_id = i.id
        LEFT JOIN users u ON d.reported_by = u.id
        $where_clause
        ORDER BY d.created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $damages = $stmt->fetchAll();
    
    // Calculate totals
    $total_estimated_cost = array_sum(array_column($damages, 'estimated_cost'));
    $total_actual_cost = array_sum(array_column($damages, 'actual_cost'));
    
} catch(PDOException $e) {
    error_log("Error loading damages: " . $e->getMessage());
    $damages = [];
}

// Get departments for filter
$departments = [];
try {
    $departments = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $departments = [];
}

// Get available months for dropdown
$available_months = [];
try {
    $months_query = $pdo->query("
        SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') as month 
        FROM damages 
        ORDER BY month DESC
    ");
    $available_months = $months_query->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $available_months = [];
}

// Calculate statistics for current month
$total_damages = count($damages);
$pending_count = count(array_filter($damages, function($d) { return ($d['status'] ?? '') == 'pending'; }));
$approved_count = count(array_filter($damages, function($d) { return ($d['status'] ?? '') == 'approved'; }));
$repaired_count = count(array_filter($damages, function($d) { return ($d['status'] ?? '') == 'repaired'; }));
$replaced_count = count(array_filter($damages, function($d) { return ($d['status'] ?? '') == 'replaced'; }));
$rejected_count = count(array_filter($damages, function($d) { return ($d['status'] ?? '') == 'rejected'; }));

// Get damage by department for current month
$damage_by_dept = [];
try {
    $dept_query = "
        SELECT e.department, COUNT(*) as count, SUM(d.estimated_cost) as total_cost
        FROM damages d
        LEFT JOIN employees e ON d.employee_id = e.id
        WHERE e.department IS NOT NULL AND e.department != ''
        AND DATE(d.created_at) BETWEEN ? AND ?
        GROUP BY e.department
        ORDER BY count DESC
    ";
    $dept_stmt = $pdo->prepare($dept_query);
    $dept_stmt->execute([$month_start, $month_end]);
    $damage_by_dept = $dept_stmt->fetchAll();
} catch(PDOException $e) {
    $damage_by_dept = [];
}
?>

<style>
    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: transform 0.2s;
        height: 100%;
    }
    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    .stats-number {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .stats-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stats-icon {
        font-size: 28px;
        margin-bottom: 10px;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-approved { background: #dbeafe; color: #2563eb; }
    .status-repaired { background: #d1fae5; color: #059669; }
    .status-replaced { background: #d1fae5; color: #059669; }
    .status-rejected { background: #fee2e2; color: #dc2626; }
    .filter-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .export-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 500;
    }
    .export-btn:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: white;
    }
    .table th {
        background: #f8f9fa;
        font-weight: 600;
        font-size: 0.8rem;
        white-space: nowrap;
    }
    .table td {
        font-size: 0.8rem;
        vertical-align: middle;
    }
    .dept-summary {
        background: #f8fafc;
        border-radius: 8px;
        padding: 12px 15px;
        margin-bottom: 8px;
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-chart-line text-danger me-2"></i> Damage Report</h2>
                    <p class="text-muted">View damage reports and track repair costs</p>
                </div>
                <div>
                    <?php if(!empty($damages)): ?>
                    <button class="btn export-btn" onclick="exportReport()">
                        <i class="fas fa-file-excel me-2"></i> Export to Excel
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-secondary ms-2" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Print Report
                    </button>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <h5 class="mb-3"><i class="fas fa-filter me-2"></i> Filter Report</h5>
        <form method="GET" class="row g-3" id="filterForm">
            <div class="col-md-3">
                <label class="form-label">Select Month</label>
                <select name="month" class="form-select">
                    <?php 
                    $months_list = [];
                    if(empty($available_months)) {
                        // If no data, show current and previous months
                        $months_list[] = date('Y-m');
                        $months_list[] = date('Y-m', strtotime('-1 month'));
                        $months_list[] = date('Y-m', strtotime('-2 month'));
                    } else {
                        $months_list = $available_months;
                    }
                    foreach($months_list as $month):
                        $display_month = date('F Y', strtotime($month . '-01'));
                    ?>
                        <option value="<?php echo $month; ?>" <?php echo $selected_month == $month ? 'selected' : ''; ?>>
                            <?php echo $display_month; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
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
                <label class="form-label">Department</label>
                <select name="department" class="form-select">
                    <option value="all" <?php echo $department_filter == 'all' ? 'selected' : ''; ?>>All Departments</option>
                    <?php foreach($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department_filter == $dept ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i> Apply Filters</button>
                <a href="damage_report.php" class="btn btn-secondary ms-2 w-100"><i class="fas fa-undo me-2"></i> Reset</a>
            </div>
        </form>
    </div>

    <?php if(empty($damages)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i> No damage records found for the selected filters.
        </div>
    <?php else: ?>

    <!-- Statistics Cards Row 1 -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-tools text-warning"></i></div>
                <div class="stats-number"><?php echo $total_damages; ?></div>
                <div class="stats-label">Total Damage Reports</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-spinner text-warning"></i></div>
                <div class="stats-number"><?php echo $pending_count; ?></div>
                <div class="stats-label">Pending</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-check-circle text-success"></i></div>
                <div class="stats-number"><?php echo $approved_count; ?></div>
                <div class="stats-label">Approved</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-wrench text-info"></i></div>
                <div class="stats-number"><?php echo $repaired_count; ?></div>
                <div class="stats-label">Repaired</div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Row 2 -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-exchange-alt text-primary"></i></div>
                <div class="stats-number"><?php echo $replaced_count; ?></div>
                <div class="stats-label">Replaced</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-times-circle text-danger"></i></div>
                <div class="stats-number"><?php echo $rejected_count; ?></div>
                <div class="stats-label">Rejected</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-money-bill-wave text-danger"></i></div>
                <div class="stats-number"><?php echo number_format($total_estimated_cost, 0); ?> TK</div>
                <div class="stats-label">Total Estimated Cost</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-check-double text-success"></i></div>
                <div class="stats-number"><?php echo number_format($total_actual_cost, 0); ?> TK</div>
                <div class="stats-label">Total Actual Cost</div>
            </div>
        </div>
    </div>

    <!-- Department Summary -->
    <?php if(!empty($damage_by_dept)): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-building me-2"></i> Damage Summary by Department</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach($damage_by_dept as $dept): ?>
                <div class="col-md-4">
                    <div class="dept-summary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($dept['department']); ?></strong>
                                <div><small class="text-muted">Total Reports: <?php echo $dept['count']; ?></small></div>
                            </div>
                            <div class="text-end">
                                <span class="text-danger fw-bold"><?php echo number_format($dept['total_cost'], 0); ?> TK</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Damage Details Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i> Damage Details - <?php echo date('F Y', strtotime($selected_month . '-01')); ?></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="damageTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Damage No</th>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>PF No</th>
                            <th>Department</th>
                            <th>Item</th>
                            <th>Item Code</th>
                            <th>Damage Type</th>
                            <th>Description</th>
                            <th>Est. Cost (TK)</th>
                            <th>Actual Cost (TK)</th>
                            <th>Status</th>
                            <th>Reported By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($damages as $damage): ?>
                        <tr>
                            <td><?php echo $damage['id']; ?></div>
                            <td><?php echo htmlspecialchars($damage['damage_no'] ?? 'N/A'); ?></div>
                            <td><?php echo date('d-m-Y', strtotime($damage['created_at'])); ?></div>
                            <td><?php echo htmlspecialchars($damage['employee_name'] ?? 'N/A'); ?></div>
                            <td><?php echo htmlspecialchars($damage['pf_no'] ?? 'N/A'); ?></div>
                            <td><?php echo htmlspecialchars($damage['department'] ?? 'N/A'); ?></div>
                            <td><?php echo htmlspecialchars($damage['item_name'] ?? 'N/A'); ?></div>
                            <td><?php echo htmlspecialchars($damage['item_code'] ?? 'N/A'); ?></div>
                            <td><?php echo ucfirst(str_replace('_', ' ', $damage['damage_type'] ?? 'N/A')); ?></div>
                            <td><?php echo htmlspecialchars(substr($damage['damage_description'] ?? '', 0, 50)); ?>...</div>
                            <td class="text-end"><?php echo number_format($damage['estimated_cost'] ?? 0, 0); ?></div>
                            <td class="text-end"><?php echo number_format($damage['actual_cost'] ?? 0, 0); ?></div>
                            <td>
                                <span class="status-badge status-<?php echo $damage['status'] ?? 'pending'; ?>">
                                    <?php echo ucfirst($damage['status'] ?? 'Pending'); ?>
                                </span>
                             </div>
                            <td><?php echo htmlspecialchars($damage['reported_by_name'] ?? 'N/A'); ?></div>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <th colspan="10" class="text-end">Total:</th>
                            <th class="text-end"><?php echo number_format($total_estimated_cost, 0); ?></th>
                            <th class="text-end"><?php echo number_format($total_actual_cost, 0); ?></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable for better sorting and searching
    if($('#damageTable tbody tr').length > 0) {
        $('#damageTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                emptyTable: "No damage records found"
            },
            columnDefs: [
                { orderable: false, targets: [9] } // Disable sorting on description column
            ]
        });
    }
});

function exportReport() {
    const month = document.querySelector('select[name="month"]').value;
    const status = document.querySelector('select[name="status"]').value;
    const department = document.querySelector('select[name="department"]').value;
    
    window.location.href = 'export_damage_report.php?month=' + month + '&status=' + status + '&department=' + department;
}
</script>

<?php include '../../includes/footer.php'; ?>