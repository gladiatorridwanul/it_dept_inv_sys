<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Get filters
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'stock_summary';
$user_filter = $_GET['user_filter'] ?? 'all';
$status_filter = $_GET['status_filter'] ?? 'all';
$print_section = $_GET['print_section'] ?? '';

// Get users for filter
$users = $pdo->query("SELECT id, full_name, username FROM users WHERE is_active = 1 ORDER BY full_name")->fetchAll();

// Get status options based on report type
$status_options = [];
if ($report_type == 'requests') {
    $status_options = ['pending', 'under_observation', 'processing', 'completed', 'rejected'];
} elseif ($report_type == 'assignments') {
    $status_options = ['assigned', 'returned', 'damaged', 'replaced'];
} elseif ($report_type == 'damages') {
    $status_options = ['pending', 'approved', 'repaired', 'replaced', 'rejected'];
} elseif ($report_type == 'transfers') {
    $status_options = ['draft', 'pending', 'approved', 'dispatched', 'delivered', 'cancelled'];
} elseif ($report_type == 'unlisted') {
    $status_options = ['pending', 'approved', 'rejected', 'added_to_stock'];
}

// Helper function to get status badge
function getStatusBadge($status, $type = 'default') {
    $colors = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'completed' => 'success',
        'processing' => 'info',
        'dispatched' => 'info',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'assigned' => 'primary',
        'returned' => 'secondary',
        'damaged' => 'danger',
        'replaced' => 'info',
        'under_observation' => 'warning',
        'repaired' => 'success',
        'added_to_stock' => 'success',
        'draft' => 'secondary',
        'active' => 'success',
        'inactive' => 'danger',
        'stock_in' => 'success',
        'return_approved' => 'primary'
    ];
    $color = $colors[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}

// Build where clause for date range
$date_where = "DATE(created_at) BETWEEN ? AND ?";

// Stock Summary Query
$stmt = $pdo->prepare("SELECT 
                        COALESCE(SUM(CASE WHEN transaction_type = 'stock_in' THEN quantity ELSE 0 END), 0) as total_in,
                        COALESCE(SUM(CASE WHEN transaction_type = 'assigned' THEN quantity ELSE 0 END), 0) as total_assigned,
                        COALESCE(SUM(CASE WHEN transaction_type = 'returned' THEN quantity ELSE 0 END), 0) as total_returned,
                        COALESCE(SUM(CASE WHEN transaction_type = 'damaged' THEN quantity ELSE 0 END), 0) as total_damaged
                       FROM stock_transactions 
                       WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$from_date, $to_date]);
$summary = $stmt->fetch();

// Current stock value
$current_stock = $pdo->query("SELECT SUM(price * current_qty) as total FROM items WHERE is_active=1")->fetch();

// Low stock items count
$low_stock_count = $pdo->query("SELECT COUNT(*) as count FROM items WHERE current_qty <= min_qty AND is_active=1 AND current_qty > 0")->fetch();

// Out of stock count
$out_of_stock_count = $pdo->query("SELECT COUNT(*) as count FROM items WHERE current_qty = 0 AND is_active=1")->fetch();

// ====== REPORT DATA FETCHING ======

// 1. STOCK REPORT
$stock_data = [];
if ($report_type == 'stock_summary' || $report_type == 'all') {
    $stock_query = "SELECT 
                        i.id, i.item_code, i.name, i.current_qty, i.min_qty, i.price,
                        i.available_qty, i.total_assigned, i.total_returned,
                        b.name as brand_name,
                        (SELECT COUNT(*) FROM item_serial_numbers ise WHERE ise.item_id = i.id) as serial_count,
                        (SELECT COUNT(*) FROM assignments a WHERE a.item_id = i.id AND a.status = 'assigned') as assigned_count
                    FROM items i
                    LEFT JOIN brands b ON i.brand_id = b.id
                    WHERE i.is_active = 1";
    
    if ($status_filter == 'low_stock') {
        $stock_query .= " AND i.current_qty <= i.min_qty AND i.current_qty > 0";
    } elseif ($status_filter == 'out_of_stock') {
        $stock_query .= " AND i.current_qty = 0";
    } elseif ($status_filter == 'in_stock') {
        $stock_query .= " AND i.current_qty > i.min_qty";
    }
    
    $stock_query .= " ORDER BY i.name";
    $stock_data = $pdo->query($stock_query)->fetchAll();
}

// 2. ASSIGNMENT REPORT
$assignment_data = [];
if ($report_type == 'assignments' || $report_type == 'all') {
    $assign_query = "SELECT 
                        a.id, a.assignment_no, a.assigned_date, a.quantity, a.status,
                        a.return_status, a.returned_date,
                        e.full_name as employee_name, e.pf_no, e.designation,
                        i.name as item_name, i.item_code, i.model_number,
                        ise.serial_number,
                        u.full_name as assigned_by_name,
                        (SELECT full_name FROM users WHERE id = a.return_approved_by) as approved_by_name
                    FROM assignments a
                    LEFT JOIN employees e ON a.employee_id = e.id
                    LEFT JOIN items i ON a.item_id = i.id
                    LEFT JOIN item_serial_numbers ise ON ise.assignment_id = a.id
                    LEFT JOIN users u ON a.assigned_by = u.id
                    WHERE DATE(a.created_at) BETWEEN ? AND ?";
    
    if ($user_filter != 'all') {
        $assign_query .= " AND a.assigned_by = ?";
    }
    if ($status_filter != 'all') {
        $assign_query .= " AND a.status = ?";
    }
    
    $assign_query .= " ORDER BY a.created_at DESC LIMIT 100";
    
    $params = [$from_date, $to_date];
    if ($user_filter != 'all') $params[] = $user_filter;
    if ($status_filter != 'all') $params[] = $status_filter;
    
    $stmt = $pdo->prepare($assign_query);
    $stmt->execute($params);
    $assignment_data = $stmt->fetchAll();
}

// 3. DAMAGE REPORT
$damage_data = [];
if ($report_type == 'damages' || $report_type == 'all') {
    $damage_query = "SELECT 
                        d.id, d.damage_no, d.damage_date, d.reported_date,
                        d.damage_type, d.damage_severity, d.damage_description,
                        d.estimated_cost, d.actual_cost, d.status,
                        d.repair_notes, d.repaired_date, d.repaired_by,
                        e.full_name as employee_name, e.pf_no,
                        i.name as item_name, i.item_code, i.model_number,
                        ise.serial_number,
                        u.full_name as reported_by_name,
                        ap.full_name as approved_by_name,
                        cr.full_name as created_by_name
                    FROM damages d
                    LEFT JOIN employees e ON d.employee_id = e.id
                    LEFT JOIN items i ON d.item_id = i.id
                    LEFT JOIN item_serial_numbers ise ON ise.damage_id = d.id
                    LEFT JOIN users u ON d.reported_by = u.id
                    LEFT JOIN users ap ON d.approved_by = ap.id
                    LEFT JOIN users cr ON d.created_by = cr.id
                    WHERE DATE(d.created_at) BETWEEN ? AND ?";
    
    if ($user_filter != 'all') {
        $damage_query .= " AND d.created_by = ?";
    }
    if ($status_filter != 'all') {
        $damage_query .= " AND d.status = ?";
    }
    
    $damage_query .= " ORDER BY d.created_at DESC LIMIT 100";
    
    $params = [$from_date, $to_date];
    if ($user_filter != 'all') $params[] = $user_filter;
    if ($status_filter != 'all') $params[] = $status_filter;
    
    $stmt = $pdo->prepare($damage_query);
    $stmt->execute($params);
    $damage_data = $stmt->fetchAll();
}

// 4. REQUESTS REPORT
$request_data = [];
if ($report_type == 'requests' || $report_type == 'all') {
    $request_query = "SELECT 
                        r.id, r.request_no, r.request_type, r.status, r.priority,
                        r.requested_date, r.resolved_date,
                        r.description, r.resolution_notes,
                        e.full_name as employee_name, e.pf_no, e.designation,
                        i.name as item_name, i.item_code,
                        u.full_name as created_by_name,
                        ap.full_name as accepted_by_name,
                        pr.full_name as processed_by_name
                    FROM requests r
                    LEFT JOIN employees e ON r.employee_id = e.id
                    LEFT JOIN items i ON r.item_id = i.id
                    LEFT JOIN users u ON r.created_by = u.id
                    LEFT JOIN users ap ON r.accepted_by = ap.id
                    LEFT JOIN users pr ON r.processed_by = pr.id
                    WHERE DATE(r.created_at) BETWEEN ? AND ?";
    
    if ($user_filter != 'all') {
        $request_query .= " AND r.created_by = ?";
    }
    if ($status_filter != 'all') {
        $request_query .= " AND r.status = ?";
    }
    if ($report_type == 'requests') {
        $request_query .= " ORDER BY r.created_at DESC LIMIT 100";
    }
    
    $params = [$from_date, $to_date];
    if ($user_filter != 'all') $params[] = $user_filter;
    if ($status_filter != 'all') $params[] = $status_filter;
    
    $stmt = $pdo->prepare($request_query);
    $stmt->execute($params);
    $request_data = $stmt->fetchAll();
}

// 5. UNLISTED DEVICE SUBMISSIONS REPORT
$unlisted_data = [];
if ($report_type == 'unlisted' || $report_type == 'all') {
    $unlisted_query = "SELECT 
                        us.id, us.submission_no, us.device_name, us.status,
                        us.created_at, us.reviewed_at, us.admin_notes,
                        us.total_devices,
                        e.full_name as employee_name, e.pf_no, e.designation,
                        u.full_name as created_by_name,
                        rv.full_name as reviewed_by_name,
                        (SELECT COUNT(*) FROM submission_devices sd WHERE sd.submission_id = us.id) as device_count
                    FROM unlisted_device_submissions us
                    LEFT JOIN employees e ON us.employee_id = e.id
                    LEFT JOIN users u ON us.submitted_by = u.id
                    LEFT JOIN users rv ON us.reviewed_by = rv.id
                    WHERE DATE(us.created_at) BETWEEN ? AND ?";
    
    if ($user_filter != 'all') {
        $unlisted_query .= " AND us.created_by = ?";
    }
    if ($status_filter != 'all') {
        $unlisted_query .= " AND us.status = ?";
    }
    
    $unlisted_query .= " ORDER BY us.created_at DESC LIMIT 100";
    
    $params = [$from_date, $to_date];
    if ($user_filter != 'all') $params[] = $user_filter;
    if ($status_filter != 'all') $params[] = $status_filter;
    
    $stmt = $pdo->prepare($unlisted_query);
    $stmt->execute($params);
    $unlisted_data = $stmt->fetchAll();
}

// 6. TRANSFERS REPORT
$transfer_data = [];
if ($report_type == 'transfers' || $report_type == 'all') {
    $transfer_query = "SELECT 
                        t.id, t.transfer_no, t.transfer_date, t.status,
                        t.from_location, t.to_location,
                        t.product_name, t.quantity,
                        t.tracking_no, t.handled_by, t.received_by,
                        t.created_at, t.updated_at,
                        u.full_name as created_by_name,
                        ap.full_name as approved_by_name
                    FROM device_transfers t
                    LEFT JOIN users u ON t.created_by = u.id
                    LEFT JOIN users ap ON t.approved_by = ap.id
                    WHERE DATE(t.created_at) BETWEEN ? AND ?";
    
    if ($user_filter != 'all') {
        $transfer_query .= " AND t.created_by = ?";
    }
    if ($status_filter != 'all') {
        $transfer_query .= " AND t.status = ?";
    }
    
    $transfer_query .= " ORDER BY t.created_at DESC LIMIT 100";
    
    $params = [$from_date, $to_date];
    if ($user_filter != 'all') $params[] = $user_filter;
    if ($status_filter != 'all') $params[] = $status_filter;
    
    $stmt = $pdo->prepare($transfer_query);
    $stmt->execute($params);
    $transfer_data = $stmt->fetchAll();
}

// 7. STOCK TRANSACTIONS DETAIL
$stock_transactions = [];
if ($report_type == 'stock_summary' || $report_type == 'all') {
    $trans_query = "SELECT 
                        t.*, i.name, i.item_code, i.model_number,
                        u.full_name as user_name
                    FROM stock_transactions t
                    JOIN items i ON t.item_id = i.id
                    LEFT JOIN users u ON t.created_by = u.id
                    WHERE DATE(t.created_at) BETWEEN ? AND ?
                    ORDER BY t.created_at DESC
                    LIMIT 100";
    
    $stmt = $pdo->prepare($trans_query);
    $stmt->execute([$from_date, $to_date]);
    $stock_transactions = $stmt->fetchAll();
}

// Function to print specific section
function printSection($section_id, $title) {
    echo '<script>
        function print_' . $section_id . '() {
            var printContents = document.getElementById("' . $section_id . '").innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        }
    </script>';
}
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2><i class="fas fa-chart-line"></i> Stock & Inventory Report</h2>
            <p class="text-muted">Complete stock summary with all transactions tracking</p>
        </div>
        <div class="col-md-4 text-end no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print All
            </button>
            <button onclick="exportPDF()" class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4 no-print">
        <div class="card-body">
            <form method="GET" class="row g-3" id="filterForm">
                <div class="col-md-2">
                    <label class="form-label">Report Type</label>
                    <select name="report_type" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $report_type == 'all' ? 'selected' : ''; ?>>All Reports</option>
                        <option value="stock_summary" <?php echo $report_type == 'stock_summary' ? 'selected' : ''; ?>>Stock Summary</option>
                        <option value="assignments" <?php echo $report_type == 'assignments' ? 'selected' : ''; ?>>Assignments</option>
                        <option value="damages" <?php echo $report_type == 'damages' ? 'selected' : ''; ?>>Damages</option>
                        <option value="requests" <?php echo $report_type == 'requests' ? 'selected' : ''; ?>>Requests</option>
                        <option value="transfers" <?php echo $report_type == 'transfers' ? 'selected' : ''; ?>>Transfers</option>
                        <option value="unlisted" <?php echo $report_type == 'unlisted' ? 'selected' : ''; ?>>Unlisted Devices</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">User</label>
                    <select name="user_filter" class="form-select">
                        <option value="all" <?php echo $user_filter == 'all' ? 'selected' : ''; ?>>All Users</option>
                        <?php foreach($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status_filter" class="form-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <?php foreach($status_options as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo $status_filter == $opt ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $opt)); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if($report_type == 'stock_summary' || $report_type == 'all'): ?>
                            <option value="low_stock" <?php echo $status_filter == 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                            <option value="out_of_stock" <?php echo $status_filter == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                            <option value="in_stock" <?php echo $status_filter == 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h6 class="card-title">Stock Value</h6>
                    <h4 class="mb-0">৳<?php echo number_format($current_stock['total'] ?? 0, 2); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h6 class="card-title">Stock In</h6>
                    <h4 class="mb-0"><?php echo number_format($summary['total_in']); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h6 class="card-title">Assigned</h6>
                    <h4 class="mb-0"><?php echo number_format($summary['total_assigned']); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h6 class="card-title">Returned</h6>
                    <h4 class="mb-0"><?php echo number_format($summary['total_returned']); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h6 class="card-title">Damaged</h6>
                    <h4 class="mb-0"><?php echo number_format($summary['total_damaged']); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-secondary">
                <div class="card-body">
                    <h6 class="card-title">Low Stock</h6>
                    <h4 class="mb-0"><?php echo $low_stock_count['count']; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== REPORT SECTIONS WITH PRINT BUTTONS ====== -->
    
    <?php if($report_type == 'stock_summary' || $report_type == 'all'): ?>
    <!-- STOCK REPORT -->
    <div class="card mb-4" id="stockReportSection">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-boxes"></i> Stock Summary</h5>
            <div class="no-print">
                <button onclick="printTable('stockTable', 'Stock Summary Report')" class="btn btn-sm btn-light">
                    <i class="fas fa-print"></i> Print Table
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped datatable" id="stockTable">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Brand</th>
                            <th>Current Qty</th>
                            <th>Min Qty</th>
                            <th>Price</th>
                            <th>Total Value</th>
                            <th>Assigned</th>
                            <th>Serials</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stock_data as $item): 
                            $status = 'In Stock';
                            $status_class = 'success';
                            if($item['current_qty'] == 0) {
                                $status = 'Out of Stock';
                                $status_class = 'danger';
                            } elseif($item['current_qty'] <= $item['min_qty']) {
                                $status = 'Low Stock';
                                $status_class = 'warning';
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['brand_name'] ?? '-'); ?></td>
                            <td><?php echo $item['current_qty']; ?></td>
                            <td><?php echo $item['min_qty']; ?></td>
                            <td>৳<?php echo number_format($item['price'], 2); ?></td>
                            <td>৳<?php echo number_format($item['price'] * $item['current_qty'], 2); ?></td>
                            <td><?php echo $item['assigned_count'] ?? 0; ?></td>
                            <td><?php echo $item['serial_count'] ?? 0; ?></td>
                            <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($report_type == 'assignments' || $report_type == 'all'): ?>
    <!-- ASSIGNMENT REPORT -->
    <div class="card mb-4" id="assignmentReportSection">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-user-check"></i> Assignment Report</h5>
            <div class="no-print">
                <button onclick="printTable('assignmentTable', 'Assignment Report')" class="btn btn-sm btn-light">
                    <i class="fas fa-print"></i> Print Table
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped datatable" id="assignmentTable">
                    <thead>
                        <tr>
                            <th>Assignment No</th>
                            <th>Employee</th>
                            <th>PF No</th>
                            <th>Item</th>
                            <th>Serial</th>
                            <th>Qty</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Return Status</th>
                            <th>Assigned By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($assignment_data as $assign): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assign['assignment_no']); ?></td>
                            <td><?php echo htmlspecialchars($assign['employee_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($assign['pf_no'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($assign['item_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($assign['serial_number'] ?? '-'); ?></td>
                            <td><?php echo $assign['quantity']; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($assign['assigned_date'])); ?></td>
                            <td><?php echo getStatusBadge($assign['status']); ?></td>
                            <td><?php echo $assign['return_status'] ? getStatusBadge($assign['return_status']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($assign['assigned_by_name'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($report_type == 'damages' || $report_type == 'all'): ?>
    <!-- DAMAGE REPORT -->
    <div class="card mb-4" id="damageReportSection">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-tools"></i> Damage Report</h5>
            <div class="no-print">
                <button onclick="printTable('damageTable', 'Damage Report')" class="btn btn-sm btn-light">
                    <i class="fas fa-print"></i> Print Table
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped datatable" id="damageTable">
                    <thead>
                        <tr>
                            <th>Damage No</th>
                            <th>Item</th>
                            <th>Serial</th>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Severity</th>
                            <th>Damage Date</th>
                            <th>Status</th>
                            <th>Cost</th>
                            <th>Reported By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($damage_data as $damage): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($damage['damage_no']); ?></td>
                            <td><?php echo htmlspecialchars($damage['item_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($damage['serial_number'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($damage['employee_name'] ?? '-'); ?></td>
                            <td><span class="badge bg-info"><?php echo ucfirst($damage['damage_type']); ?></span></td>
                            <td><span class="badge bg-warning"><?php echo ucfirst($damage['damage_severity']); ?></span></td>
                            <td><?php echo date('d-m-Y', strtotime($damage['damage_date'])); ?></td>
                            <td><?php echo getStatusBadge($damage['status']); ?></td>
                            <td>৳<?php echo number_format($damage['actual_cost'] ?? 0, 2); ?></td>
                            <td><?php echo htmlspecialchars($damage['reported_by_name'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($report_type == 'requests' || $report_type == 'all'): ?>
    <!-- REQUEST REPORT -->
    <div class="card mb-4" id="requestReportSection">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Request Report</h5>
            <div class="no-print">
                <button onclick="printTable('requestTable', 'Request Report')" class="btn btn-sm btn-light">
                    <i class="fas fa-print"></i> Print Table
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped datatable" id="requestTable">
                    <thead>
                        <tr>
                            <th>Request No</th>
                            <th>Type</th>
                            <th>Employee</th>
                            <th>Item</th>
                            <th>Priority</th>
                            <th>Requested Date</th>
                            <th>Status</th>
                            <th>Resolved Date</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($request_data as $req): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($req['request_no']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $req['request_type'])); ?></span></td>
                            <td><?php echo htmlspecialchars($req['employee_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($req['item_name'] ?? '-'); ?></td>
                            <td><span class="badge bg-<?php echo $req['priority'] == 'high' || $req['priority'] == 'critical' ? 'danger' : ($req['priority'] == 'medium' ? 'warning' : 'info'); ?>"><?php echo ucfirst($req['priority']); ?></span></td>
                            <td><?php echo date('d-m-Y', strtotime($req['requested_date'])); ?></td>
                            <td><?php echo getStatusBadge($req['status']); ?></td>
                            <td><?php echo $req['resolved_date'] ? date('d-m-Y', strtotime($req['resolved_date'])) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($req['created_by_name'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($report_type == 'transfers' || $report_type == 'all'): ?>
    <!-- TRANSFER REPORT -->
    <div class="card mb-4" id="transferReportSection">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Transfer Report</h5>
            <div class="no-print">
                <button onclick="printTable('transferTable', 'Transfer Report')" class="btn btn-sm btn-light">
                    <i class="fas fa-print"></i> Print Table
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped datatable" id="transferTable">
                    <thead>
                        <tr>
                            <th>Transfer No</th>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Tracking</th>
                            <th>Status</th>
                            <th>Handled By</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($transfer_data as $trans): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trans['transfer_no']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($trans['transfer_date'])); ?></td>
                            <td><?php echo htmlspecialchars($trans['product_name']); ?></td>
                            <td><?php echo $trans['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($trans['from_location']); ?></td>
                            <td><?php echo htmlspecialchars($trans['to_location']); ?></td>
                            <td><?php echo htmlspecialchars($trans['tracking_no'] ?? '-'); ?></td>
                            <td><?php echo getStatusBadge($trans['status']); ?></td>
                            <td><?php echo htmlspecialchars($trans['handled_by'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($trans['created_by_name'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($report_type == 'unlisted' || $report_type == 'all'): ?>
    <!-- UNLISTED DEVICES REPORT -->
    <div class="card mb-4" id="unlistedReportSection">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-upload"></i> Unlisted Device Submissions</h5>
            <div class="no-print">
                <button onclick="printTable('unlistedTable', 'Unlisted Device Submissions Report')" class="btn btn-sm btn-light">
                    <i class="fas fa-print"></i> Print Table
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped datatable" id="unlistedTable">
                    <thead>
                        <tr>
                            <th>Submission No</th>
                            <th>Device Name</th>
                            <th>Employee</th>
                            <th>Total Devices</th>
                            <th>Submitted Date</th>
                            <th>Status</th>
                            <th>Reviewed By</th>
                            <th>Review Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($unlisted_data as $unlisted): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($unlisted['submission_no']); ?></td>
                            <td><?php echo htmlspecialchars($unlisted['device_name'] ?: 'Multiple Devices'); ?></td>
                            <td><?php echo htmlspecialchars($unlisted['employee_name'] ?? '-'); ?></td>
                            <td><?php echo $unlisted['device_count'] ?? 0; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($unlisted['created_at'])); ?></td>
                            <td><?php echo getStatusBadge($unlisted['status']); ?></td>
                            <td><?php echo htmlspecialchars($unlisted['reviewed_by_name'] ?? '-'); ?></td>
                            <td><?php echo $unlisted['reviewed_at'] ? date('d-m-Y', strtotime($unlisted['reviewed_at'])) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($report_type == 'stock_summary' || $report_type == 'all'): ?>
    <!-- STOCK TRANSACTIONS -->
    <div class="card mb-4" id="transactionReportSection">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Stock Movement Transactions</h5>
            <div class="no-print">
                <button onclick="printTable('transactionTable', 'Stock Movement Transactions Report')" class="btn btn-sm btn-light">
                    <i class="fas fa-print"></i> Print Table
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped datatable" id="transactionTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Reference</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stock_transactions as $trans): ?>
                        <tr>
                            <td><?php echo date('d-m-Y H:i', strtotime($trans['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($trans['item_code']); ?></td>
                            <td><?php echo htmlspecialchars($trans['name']); ?></td>
                            <td>
                                <?php 
                                    $badge = 'secondary';
                                    if($trans['transaction_type'] == 'stock_in') $badge = 'success';
                                    elseif($trans['transaction_type'] == 'assigned') $badge = 'warning';
                                    elseif($trans['transaction_type'] == 'returned') $badge = 'info';
                                    elseif($trans['transaction_type'] == 'damaged') $badge = 'danger';
                                    elseif($trans['transaction_type'] == 'return_approved') $badge = 'primary';
                                ?>
                                <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst(str_replace('_', ' ', $trans['transaction_type'])); ?></span>
                            </td>
                            <td><?php echo $trans['quantity']; ?></td>
                            <td>
                                <?php if($trans['reference_type'] == 'stock_in'): ?>
                                    <a href="stock_list.php?id=<?php echo $trans['reference_id']; ?>">View Invoice</a>
                                <?php elseif($trans['reference_type'] == 'assignment'): ?>
                                    <a href="../assignments/view.php?id=<?php echo $trans['reference_id']; ?>">View Assignment</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($trans['user_name'] ?? 'System'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    .btn-print, .btn, .no-print {
        display: none !important;
    }
    .card {
        border: 1px solid #000 !important;
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
    .table {
        font-size: 11px;
        width: 100% !important;
    }
    .table-bordered {
        border: 1px solid #000 !important;
    }
    .table-bordered td, .table-bordered th {
        border: 1px solid #000 !important;
        padding: 4px;
    }
    .badge {
        border: 1px solid #000;
        background: #f1f1f1 !important;
        color: #000 !important;
    }
    .container-fluid {
        padding: 0 !important;
    }
    body {
        padding: 10px !important;
        background: white !important;
    }
    .page-container {
        max-width: 100% !important;
    }
    .row {
        display: block !important;
    }
    .col-md-2, .col-md-8, .col-md-4 {
        width: 100% !important;
        max-width: 100% !important;
        flex: none !important;
    }
    .card-body {
        padding: 10px !important;
    }
    .table-responsive {
        overflow: visible !important;
    }
    .datatable {
        font-size: 10px !important;
    }
    .datatable th, .datatable td {
        padding: 3px 5px !important;
    }
}

/* Print-specific styles for individual table printing */
.print-table-only {
    padding: 20px;
}
.print-table-only h2 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 18px;
}
.print-table-only .table {
    font-size: 11px;
    width: 100%;
    border-collapse: collapse;
}
.print-table-only .table th,
.print-table-only .table td {
    border: 1px solid #000;
    padding: 4px 6px;
}
.print-table-only .table th {
    background-color: #f1f1f1;
    font-weight: bold;
    text-align: center;
}
.print-table-only .badge {
    border: 1px solid #000;
    padding: 1px 6px;
    background: #f1f1f1 !important;
    color: #000 !important;
    font-size: 9px;
}
</style>

<script>
function printTable(tableId, reportTitle) {
    // Get the table element
    var table = document.getElementById(tableId);
    if (!table) {
        alert('Table not found!');
        return;
    }
    
    // Clone the table to avoid affecting the original
    var tableClone = table.cloneNode(true);
    
    // Remove DataTable wrapper if exists
    var wrapper = tableClone.closest('.dataTables_wrapper');
    if (wrapper) {
        wrapper.parentNode.removeChild(wrapper);
    }
    
    // Get current date for report
    var currentDate = new Date();
    var dateStr = currentDate.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
    var timeStr = currentDate.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
    
    // Create print content
    var printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>${reportTitle}</title>
            <style>
                body {
                    font-family: 'Cambria', 'Georgia', serif;
                    padding: 20px;
                    margin: 0;
                    font-size: 11px;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                }
                .print-header h1 {
                    font-size: 18px;
                    margin: 0;
                    letter-spacing: 1px;
                }
                .print-header .sub-title {
                    font-size: 12px;
                    color: #333;
                    margin-top: 4px;
                }
                .print-header .report-date {
                    font-size: 11px;
                    margin-top: 4px;
                    color: #555;
                }
                .table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 10px;
                }
                .table th {
                    background-color: #f1f1f1;
                    font-weight: bold;
                    text-align: center;
                    border: 1px solid #000;
                    padding: 4px 6px;
                }
                .table td {
                    border: 1px solid #000;
                    padding: 3px 5px;
                }
                .badge {
                    border: 1px solid #000;
                    padding: 1px 6px;
                    background: #f1f1f1 !important;
                    color: #000 !important;
                    font-size: 8px;
                    border-radius: 3px;
                }
                .print-footer {
                    text-align: center;
                    margin-top: 20px;
                    padding-top: 10px;
                    border-top: 1px solid #000;
                    font-size: 9px;
                    color: #555;
                }
                @page {
                    margin: 0.5in;
                    size: landscape;
                }
                @media print {
                    body { padding: 0; }
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>${reportTitle}</h1>
                <div class="sub-title">UniMed UniHealth Pharmaceutical Ltd. - IT Department</div>
                <div class="report-date">Generated: ${dateStr} at ${timeStr} | Period: ${document.querySelector('input[name="from_date"]')?.value || 'N/A'} to ${document.querySelector('input[name="to_date"]')?.value || 'N/A'}</div>
            </div>
            ${tableClone.outerHTML}
            <div class="print-footer">
                Report generated from UniMed IT Inventory System | ${dateStr} ${timeStr}
            </div>
            <script>
                window.onload = function() {
                    window.print();
                }
            <\/script>
        </body>
        </html>
    `;
    
    // Open print window
    var printWindow = window.open('', '_blank', 'width=1000,height=800');
    if (!printWindow) {
        alert('Please allow popups to print the report.');
        return;
    }
    printWindow.document.write(printContent);
    printWindow.document.close();
}

function exportPDF() {
    window.print();
}

$(document).ready(function() {
    // Initialize DataTables if available
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            pageLength: 50,
            responsive: true,
            order: [[0, 'desc']],
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>