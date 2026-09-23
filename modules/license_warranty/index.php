<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Check if tables exist and get statistics with error handling
$total_licenses = 0;
$expiring_licenses = 0;
$total_warranties = 0;
$expiring_warranties = 0;
$pending_tasks = 0;

try {
    // Check if licenses table has is_active column
    $stmt = $pdo->query("SHOW COLUMNS FROM licenses LIKE 'is_active'");
    $has_is_active = $stmt->rowCount() > 0;
    
    if($has_is_active) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM licenses WHERE is_active = 1");
        $total_licenses = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM licenses WHERE status = 'expiring_soon' AND is_active = 1");
        $expiring_licenses = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranties WHERE is_active = 1");
        $total_warranties = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranties WHERE status = 'expiring_soon' AND is_active = 1");
        $expiring_warranties = $stmt->fetch()['total'];
    } else {
        // Fallback if is_active doesn't exist
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM licenses");
        $total_licenses = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM licenses WHERE status = 'expiring_soon'");
        $expiring_licenses = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranties");
        $total_warranties = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM warranties WHERE status = 'expiring_soon'");
        $expiring_warranties = $stmt->fetch()['total'];
    }
    
    // Get pending tasks
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_tasks WHERE (assigned_to = ? OR assigned_to_employee_id IN (SELECT id FROM employees WHERE user_id = ?)) AND status != 'completed'");
    $stmt->execute([$user_id, $user_id]);
    $pending_tasks = $stmt->fetch()['total'];
    
} catch(PDOException $e) {
    // Log error but continue
    error_log("Database error in license_warranty index: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        * { font-family: 'Inter', sans-serif; }
        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        .stats-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .stats-number { font-size: 36px; font-weight: 800; margin-bottom: 5px; }
        .stats-label { font-size: 13px; color: #6c757d; text-transform: uppercase; letter-spacing: 1px; }
        .stats-icon { font-size: 40px; margin-bottom: 10px; }
        .section-tab {
            display: inline-block;
            padding: 12px 30px;
            background: white;
            border-radius: 40px;
            margin: 0 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            border: 1px solid #dee2e6;
        }
        .section-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        .section-tab:hover:not(.active) { background: #f8f9fa; transform: translateY(-2px); }
        .status-badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-active { background: #d1fae5; color: #059669; }
        .status-expired { background: #fee2e2; color: #dc2626; }
        .status-expiring_soon { background: #fef3c7; color: #d97706; }
        .action-buttons .btn { padding: 4px 10px; margin: 2px; font-size: 12px; border-radius: 8px; }
        .table th { background: #f8fafc; font-weight: 600; font-size: 13px; white-space: nowrap; }
        .table td { font-size: 13px; vertical-align: middle; }
        .required-field::after { content: " *"; color: red; }
        .btn-gradient-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; }
        .btn-gradient-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; }
        .btn-gradient-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; }
        .btn-gradient-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none; }
    </style>
</head>
<body>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-certificate text-primary me-2"></i> License & Warranty Manager</h2>
                    <p class="text-muted">Manage software licenses, device warranties, and track tasks efficiently</p>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3 col-sm-6">
            <div class="stats-card" onclick="location.href='licenses.php'">
                <div class="stats-icon"><i class="fas fa-key text-primary"></i></div>
                <div class="stats-number"><?php echo $total_licenses; ?></div>
                <div class="stats-label">Total Licenses</div>
                <small class="text-warning"><?php echo $expiring_licenses; ?> expiring soon</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stats-card" onclick="location.href='warranties.php'">
                <div class="stats-icon"><i class="fas fa-shield-alt text-success"></i></div>
                <div class="stats-number"><?php echo $total_warranties; ?></div>
                <div class="stats-label">Total Warranties</div>
                <small class="text-warning"><?php echo $expiring_warranties; ?> expiring soon</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stats-card" onclick="location.href='tasks.php'">
                <div class="stats-icon"><i class="fas fa-tasks text-warning"></i></div>
                <div class="stats-number"><?php echo $pending_tasks; ?></div>
                <div class="stats-label">Pending Tasks</div>
                <small>Awaiting completion</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stats-card" onclick="location.href='reports.php'">
                <div class="stats-icon"><i class="fas fa-chart-line text-info"></i></div>
                <div class="stats-number">Reports</div>
                <div class="stats-label">Analytics & Reports</div>
                <small>View insights</small>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fas fa-bolt text-warning me-2"></i> Quick Actions</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="licenses.php?action=add" class="btn btn-gradient-primary"><i class="fas fa-plus me-1"></i> Add License</a>
                        <a href="warranties.php?action=add" class="btn btn-gradient-success"><i class="fas fa-plus me-1"></i> Add Warranty</a>
                        <a href="tasks.php?action=add" class="btn btn-gradient-warning"><i class="fas fa-plus me-1"></i> Create Task</a>
                        <a href="licenses.php?view=expiring" class="btn btn-outline-warning"><i class="fas fa-hourglass-half me-1"></i> Expiring Licenses</a>
                        <a href="warranties.php?view=expiring" class="btn btn-outline-warning"><i class="fas fa-hourglass-half me-1"></i> Expiring Warranties</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Licenses -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-key text-primary me-2"></i> Recent Licenses</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>Software</th><th>Expiry Date</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $recent_licenses = $pdo->query("SELECT software_name, expiry_date, status FROM licenses ORDER BY created_at DESC LIMIT 5")->fetchAll();
                                    foreach($recent_licenses as $lic): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($lic['software_name']); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($lic['expiry_date'])); ?></td>
                                        <td><span class="status-badge status-<?php echo $lic['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $lic['status'])); ?></span></td>
                                    </tr>
                                    <?php endforeach;
                                } catch(PDOException $e) {
                                    echo '<tr><td colspan="3" class="text-center text-muted">No licenses found</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-shield-alt text-success me-2"></i> Recent Warranties</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Device</th><th>End Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php
                                try {
                                    $recent_warranties = $pdo->query("SELECT item_name, warranty_end_date, status FROM warranties ORDER BY created_at DESC LIMIT 5")->fetchAll();
                                    foreach($recent_warranties as $war): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($war['item_name']); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($war['warranty_end_date'])); ?></td>
                                        <td><span class="status-badge status-<?php echo $war['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $war['status'])); ?></span></td>
                                    </tr>
                                    <?php endforeach;
                                } catch(PDOException $e) {
                                    echo '<tr><td colspan="3" class="text-center text-muted">No warranties found</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<?php include '../../includes/footer.php'; ?>