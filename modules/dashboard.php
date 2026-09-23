<?php
// Include auth and header FIRST, before any session/DB access.
// auth.php pulls in config/session_fix.php, which sets the correct
// session save path + cookie params BEFORE the session is opened,
// then verifies login (redirecting if needed) and gives us $pdo.
// This makes dashboard.php bootstrap identically to every sidebar page.
require_once '../includes/auth.php';
include '../includes/header.php';

// ==================== MAIN STATISTICS ====================

// Items Statistics
$totalItems = $pdo->query("SELECT SUM(current_qty) as total FROM items WHERE is_active=1")->fetch();
$totalItemsCount = $pdo->query("SELECT COUNT(*) as count FROM items WHERE is_active=1")->fetch();
$lowStockItems = $pdo->query("SELECT COUNT(*) as count FROM items WHERE current_qty <= min_qty AND is_active=1")->fetch();
$outOfStockItems = $pdo->query("SELECT COUNT(*) as count FROM items WHERE current_qty = 0 AND is_active=1")->fetch();
$totalStockValue = $pdo->query("SELECT SUM(price * current_qty) as total FROM items WHERE is_active=1")->fetch();

// Employee Statistics
$totalEmployees = $pdo->query("SELECT COUNT(*) as total FROM employees WHERE is_active=1")->fetch();
$totalDepartments = $pdo->query("SELECT COUNT(DISTINCT department) as total FROM employees WHERE department IS NOT NULL AND department != ''")->fetch();

// Vendor Statistics
$totalVendors = $pdo->query("SELECT COUNT(*) as total FROM vendors WHERE is_active=1")->fetch();

// Assignment Statistics
$activeAssignments = $pdo->query("SELECT COUNT(*) as total FROM assignments WHERE status='assigned'")->fetch();
$totalAssignments = $pdo->query("SELECT COUNT(*) as total FROM assignments")->fetch();
$returnedAssignments = $pdo->query("SELECT COUNT(*) as total FROM assignments WHERE status='returned'")->fetch();

// Request Statistics
$pendingRequests = $pdo->query("SELECT COUNT(*) as total FROM requests WHERE status='pending'")->fetch();
$underObservationRequests = $pdo->query("SELECT COUNT(*) as total FROM requests WHERE status='under_observation'")->fetch();
$processingRequests = $pdo->query("SELECT COUNT(*) as total FROM requests WHERE status='processing'")->fetch();
$completedRequests = $pdo->query("SELECT COUNT(*) as total FROM requests WHERE status='completed'")->fetch();
$rejectedRequests = $pdo->query("SELECT COUNT(*) as total FROM requests WHERE status='rejected'")->fetch();
$totalRequests = $pdo->query("SELECT COUNT(*) as total FROM requests")->fetch();

// Bill & Finance Statistics
$totalBills = $pdo->query("SELECT SUM(total_amount) as total FROM bills")->fetch();
$totalPaid = $pdo->query("SELECT SUM(paid_amount) as total FROM bills")->fetch();
$pendingBills = $pdo->query("SELECT COUNT(*) as count FROM bills WHERE status != 'paid'")->fetch();
$totalBillsCount = $pdo->query("SELECT COUNT(*) as count FROM bills")->fetch();

// Get recent activities
$recentStock = $pdo->query("SELECT s.*, i.name as item_name, v.name as vendor_name 
                           FROM stock_in s 
                           JOIN items i ON s.item_id=i.id 
                           LEFT JOIN vendors v ON s.vendor_id=v.id 
                           ORDER BY s.id DESC LIMIT 5")->fetchAll();

$recentAssignments = $pdo->query("SELECT a.*, e.full_name, i.name as item_name 
                                 FROM assignments a 
                                 JOIN employees e ON a.employee_id=e.id 
                                 JOIN items i ON a.item_id=i.id 
                                 ORDER BY a.id DESC LIMIT 5")->fetchAll();

$recentRequests = $pdo->query("SELECT r.*, e.full_name 
                               FROM requests r 
                               JOIN employees e ON r.employee_id=e.id 
                               WHERE r.status IN ('pending', 'under_observation', 'processing')
                               ORDER BY r.id DESC LIMIT 5")->fetchAll();

// Get warranty expiring items
$expiringWarranty = $pdo->query("SELECT * FROM items WHERE warranty_status = 'expiring_soon' OR (warranty_end_date IS NOT NULL AND warranty_end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)) LIMIT 5")->fetchAll();

// Get recent bills
$recentBills = $pdo->query("SELECT b.*, v.name as vendor_name 
                           FROM bills b 
                           JOIN vendors v ON b.vendor_id=v.id 
                           ORDER BY b.id DESC LIMIT 5")->fetchAll();

// Monthly trend data (last 6 months)
$monthlyRequests = $pdo->query("SELECT 
                                DATE_FORMAT(requested_date, '%Y-%m') as month,
                                COUNT(*) as count,
                                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                               FROM requests 
                               WHERE requested_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                               GROUP BY DATE_FORMAT(requested_date, '%Y-%m')
                               ORDER BY month DESC")->fetchAll();

// Low stock items for alerts
$low_stock_items = $pdo->query("SELECT i.*, c.name as category_name 
                                FROM items i 
                                LEFT JOIN categories c ON i.category_id = c.id 
                                WHERE i.is_active = 1 AND i.current_qty <= i.min_qty AND i.current_qty > 0 
                                ORDER BY i.current_qty ASC LIMIT 10")->fetchAll();

$out_of_stock_items = $pdo->query("SELECT i.*, c.name as category_name 
                                   FROM items i 
                                   LEFT JOIN categories c ON i.category_id = c.id 
                                   WHERE i.is_active = 1 AND i.current_qty = 0 
                                   ORDER BY i.name ASC LIMIT 10")->fetchAll();

// Get user name for welcome message
$userDisplayName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Staff';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($userDisplayName); ?>! Here's what's happening with your IT inventory today.</p>
            <hr>
        </div>
    </div>
    
    <!-- Main Statistics Cards -->
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Stock Value</h6>
                            <h2 class="mb-0">৳<?php echo number_format($totalStockValue['total'] ?? 0, 2); ?></h2>
                            <small><?php echo number_format($totalItemsCount['count'] ?? 0); ?> Items in Stock</small>
                        </div>
                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Active Employees</h6>
                            <h2 class="mb-0"><?php echo number_format($totalEmployees['total']); ?></h2>
                            <small><?php echo $totalDepartments['total']; ?> Departments</small>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Active Assignments</h6>
                            <h2 class="mb-0"><?php echo number_format($activeAssignments['total']); ?></h2>
                            <small>Total: <?php echo $totalAssignments['total']; ?> Assignments</small>
                        </div>
                        <i class="fas fa-laptop-house fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Pending Requests</h6>
                            <h2 class="mb-0"><?php echo number_format($pendingRequests['total']); ?></h2>
                            <small><?php echo $underObservationRequests['total']; ?> Under Observation</small>
                        </div>
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Statistics -->
    <div class="row mt-2">
        <div class="col-md-3 mb-3">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-primary">Low Stock Alert</h6>
                            <h3><?php echo $lowStockItems['count']; ?></h3>
                            <small>Items below minimum level</small>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-success">Active Vendors</h6>
                            <h3><?php echo $totalVendors['total']; ?></h3>
                            <small>Registered suppliers</small>
                        </div>
                        <i class="fas fa-truck fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-info">Total Bills</h6>
                            <h3>৳<?php echo number_format($totalBills['total'] ?? 0, 2); ?></h3>
                            <small><?php echo $totalBillsCount['count']; ?> Bills</small>
                        </div>
                        <i class="fas fa-file-invoice fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-danger">Pending Bills</h6>
                            <h3><?php echo $pendingBills['count']; ?></h3>
                            <small>Need payment</small>
                        </div>
                        <i class="fas fa-credit-card fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Status Summary -->
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Request Status Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 text-center"><div class="bg-warning p-3 rounded"><h3><?php echo $pendingRequests['total']; ?></h3><small>Pending</small></div></div>
                        <div class="col-md-2 text-center"><div class="bg-info p-3 rounded text-white"><h3><?php echo $underObservationRequests['total']; ?></h3><small>Under Obs.</small></div></div>
                        <div class="col-md-2 text-center"><div class="bg-primary p-3 rounded text-white"><h3><?php echo $processingRequests['total']; ?></h3><small>Processing</small></div></div>
                        <div class="col-md-2 text-center"><div class="bg-success p-3 rounded text-white"><h3><?php echo $completedRequests['total']; ?></h3><small>Completed</small></div></div>
                        <div class="col-md-2 text-center"><div class="bg-danger p-3 rounded text-white"><h3><?php echo $rejectedRequests['total']; ?></h3><small>Rejected</small></div></div>
                        <div class="col-md-2 text-center"><div class="bg-secondary p-3 rounded text-white"><h3><?php echo $totalRequests['total']; ?></h3><small>Total</small></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities Row -->
    <div class="row mt-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-dolly"></i> Recent Stock-In</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Date</th><th>Item</th><th>Qty</th><th>Amount</th></tr></thead>
                            <tbody>
                                <?php foreach($recentStock as $stock): ?>
                                <tr><td><?php echo date('d-m-Y', strtotime($stock['purchase_date'])); ?></td><td><?php echo htmlspecialchars($stock['item_name']); ?></td><td><?php echo $stock['quantity']; ?></td><td>৳<?php echo number_format($stock['total_amount'], 2); ?></td></tr>
                                <?php endforeach; ?>
                                <?php if(empty($recentStock)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No recent stock-in</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer"><a href="../modules/stock/stock_list.php" class="btn btn-sm btn-success">View All Stock</a></div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-info text-white"><h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Recent Assignments</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Date</th><th>Employee</th><th>Item</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach($recentAssignments as $assign): ?>
                                <tr><td><?php echo date('d-m-Y', strtotime($assign['assigned_date'])); ?></td><td><?php echo htmlspecialchars($assign['full_name']); ?></td><td><?php echo htmlspecialchars($assign['item_name']); ?></td><td><span class="badge bg-<?php echo $assign['status'] == 'assigned' ? 'success' : 'warning'; ?>"><?php echo ucfirst($assign['status']); ?></span></td></tr>
                                <?php endforeach; ?>
                                <?php if(empty($recentAssignments)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No recent assignments</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer"><a href="../modules/assignments/list.php" class="btn btn-sm btn-info">View All Assignments</a></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark"><h5 class="mb-0"><i class="fas fa-bell"></i> Recent Requests</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Date</th><th>Employee</th><th>Type</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach($recentRequests as $req): ?>
                                <tr><td><?php echo date('d-m-Y', strtotime($req['requested_date'])); ?></td><td><?php echo htmlspecialchars($req['full_name']); ?></td><td><?php echo ucfirst(str_replace('_', ' ', $req['request_type'])); ?></td><td><span class="badge bg-<?php echo $req['status'] == 'pending' ? 'warning' : ($req['status'] == 'under_observation' ? 'info' : 'primary'); ?>"><?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?></span></td></tr>
                                <?php endforeach; ?>
                                <?php if(empty($recentRequests)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No recent requests</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer"><a href="../modules/requests/all_requests.php" class="btn btn-sm btn-warning">View All Requests</a></div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white"><h5 class="mb-0"><i class="fas fa-file-invoice"></i> Recent Bills</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Bill No</th><th>Vendor</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach($recentBills as $bill): ?>
                                <tr><td><?php echo $bill['bill_no']; ?></td><td><?php echo htmlspecialchars($bill['vendor_name']); ?></td><td><?php echo date('d-m-Y', strtotime($bill['bill_date'])); ?></td><td>৳<?php echo number_format($bill['total_amount'], 2); ?></td><td><span class="badge bg-<?php echo $bill['status'] == 'paid' ? 'success' : 'danger'; ?>"><?php echo ucfirst($bill['status']); ?></span></td></tr>
                                <?php endforeach; ?>
                                <?php if(empty($recentBills)): ?>
                                <tr><td colspan="5" class="text-center text-muted">No recent bills</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer"><a href="../modules/bills/list_bills.php" class="btn btn-sm btn-secondary">View All Bills</a></div>
            </div>
        </div>
    </div>

    <?php if(count($expiringWarranty) > 0): ?>
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white"><h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Warranty Expiring Soon</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th>Item Name</th><th>Item Code</th><th>Serial Number</th><th>Warranty End Date</th><th>Days Left</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach($expiringWarranty as $item): $days_left = !empty($item['warranty_end_date']) ? ceil((strtotime($item['warranty_end_date']) - time()) / (60 * 60 * 24)) : 0; ?>
                                <tr><td><?php echo htmlspecialchars($item['name']); ?></td><td><?php echo $item['item_code']; ?></td><td><?php echo $item['serial_number'] ?? 'N/A'; ?></td><td><?php echo !empty($item['warranty_end_date']) ? date('d-m-Y', strtotime($item['warranty_end_date'])) : 'N/A'; ?></td><td><span class="badge bg-<?php echo $days_left <= 7 ? 'danger' : 'warning'; ?>"><?php echo $days_left > 0 ? $days_left . ' days left' : 'Expired'; ?></span></td><td><a href="../modules/items/edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Renew</a></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(count($monthlyRequests) > 0): ?>
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="fas fa-chart-line"></i> Monthly Request Trend (Last 6 Months)</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Month</th><th>Total Requests</th><th>Completed</th><th>Completion Rate</th></tr></thead>
                            <tbody>
                                <?php foreach($monthlyRequests as $month): $completionRate = $month['count'] > 0 ? round(($month['completed'] / $month['count']) * 100, 1) : 0; ?>
                                <tr><td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td><td><?php echo $month['count']; ?></td><td><?php echo $month['completed']; ?></td><td><div class="progress" style="height: 20px;"><div class="progress-bar bg-success" style="width: <?php echo $completionRate; ?>%"><?php echo $completionRate; ?>%</div></div></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function updateTaskStatus(taskId, status) {
    if(confirm('Update task status to ' + status + '?')) {
        window.location.href = 'update_task.php?id=' + taskId + '&status=' + status;
    }
}
</script>

<?php include '../includes/footer.php'; ?>