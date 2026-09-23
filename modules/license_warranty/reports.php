<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Get statistics for reports
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM licenses WHERE is_active = 1 GROUP BY status");
$license_stats = $stmt->fetchAll();

$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM warranties WHERE is_active = 1 GROUP BY status");
$warranty_stats = $stmt->fetchAll();

$stmt = $pdo->query("SELECT priority, COUNT(*) as count FROM user_tasks WHERE is_active = 1 GROUP BY priority");
$task_priority_stats = $stmt->fetchAll();

// Get expiring soon data
$stmt = $pdo->query("SELECT COUNT(*) as count FROM licenses WHERE status = 'expiring_soon' AND is_active = 1");
$expiring_licenses = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM warranties WHERE status = 'expiring_soon' AND is_active = 1");
$expiring_warranties = $stmt->fetch()['count'];

// Get expired data
$stmt = $pdo->query("SELECT COUNT(*) as count FROM licenses WHERE status = 'expired' AND is_active = 1");
$expired_licenses = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM warranties WHERE status = 'expired' AND is_active = 1");
$expired_warranties = $stmt->fetch()['count'];

// Get total values
$stmt = $pdo->query("SELECT COUNT(*) as total_licenses FROM licenses WHERE is_active = 1");
$total_licenses = $stmt->fetch()['total_licenses'];

$stmt = $pdo->query("SELECT COUNT(*) as total_warranties FROM warranties WHERE is_active = 1");
$total_warranties = $stmt->fetch()['total_warranties'];

$stmt = $pdo->query("SELECT COUNT(*) as total_tasks FROM user_tasks WHERE is_active = 1 AND status != 'completed'");
$pending_tasks = $stmt->fetch()['total_tasks'];

// Get recent items
$recent_licenses = $pdo->query("SELECT * FROM licenses WHERE is_active = 1 ORDER BY created_at DESC LIMIT 10")->fetchAll();
$recent_warranties = $pdo->query("SELECT * FROM warranties WHERE is_active = 1 ORDER BY created_at DESC LIMIT 10")->fetchAll();
$recent_tasks = $pdo->query("SELECT * FROM user_tasks WHERE is_active = 1 ORDER BY due_date ASC LIMIT 10")->fetchAll();

// Get upcoming expirations
$upcoming_licenses = $pdo->query("SELECT * FROM licenses WHERE status = 'expiring_soon' AND expiry_date >= CURDATE() ORDER BY expiry_date ASC LIMIT 10")->fetchAll();
$upcoming_warranties = $pdo->query("SELECT * FROM warranties WHERE status = 'expiring_soon' AND warranty_end_date >= CURDATE() ORDER BY warranty_end_date ASC LIMIT 10")->fetchAll();
?>

<style>
    * {
        font-family: 'Inter', sans-serif;
    }
    .stats-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: all 0.3s;
        border: 1px solid #e2e8f0;
    }
    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    .stats-number {
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 5px;
    }
    .stats-label {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
    }
    .stats-icon {
        font-size: 28px;
        margin-bottom: 10px;
    }
    .report-card {
        background: white;
        border-radius: 16px;
        padding: 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        margin-bottom: 24px;
    }
    .report-header {
        background: #f8fafc;
        padding: 15px 20px;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 600;
    }
    .report-header i {
        margin-right: 8px;
        color: #3b82f6;
    }
    .report-body {
        padding: 15px 20px;
    }
    .list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .list-item:last-child {
        border-bottom: none;
    }
    .list-item .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .badge-active { background: #d1fae5; color: #065f46; }
    .badge-expiring { background: #fef3c7; color: #92400e; }
    .badge-expired { background: #fee2e2; color: #991b1b; }
    .badge-pending { background: #fef3c7; color: #92400e; }
    .badge-in_progress { background: #dbeafe; color: #1e40af; }
    .badge-completed { background: #d1fae5; color: #065f46; }
    .priority-badge { padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; }
    .priority-high { background: #fee2e2; color: #dc2626; }
    .priority-medium { background: #fef3c7; color: #d97706; }
    .priority-low { background: #d1fae5; color: #059669; }
    .btn-link-small {
        font-size: 12px;
        color: #3b82f6;
        text-decoration: none;
    }
    .btn-link-small:hover {
        text-decoration: underline;
    }
    .stat-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .stat-row:last-child {
        border-bottom: none;
    }
    .stat-row-label {
        font-weight: 500;
        color: #475569;
    }
    .stat-row-value {
        font-weight: 600;
        color: #1e293b;
    }
    .quick-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }
    @media (max-width: 768px) {
        .quick-stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="container-fluid px-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1"><i class="fas fa-chart-line text-info me-2"></i>License & Warranty Reports</h4>
                    <p class="text-muted small mb-0">Overview of licenses, warranties, and task management</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <hr class="my-3">
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="quick-stats-grid">
        <div class="stats-card">
            <div class="stats-icon text-primary"><i class="fas fa-key"></i></div>
            <div class="stats-number"><?php echo $total_licenses; ?></div>
            <div class="stats-label">Total Licenses</div>
        </div>
        <div class="stats-card">
            <div class="stats-icon text-success"><i class="fas fa-shield-alt"></i></div>
            <div class="stats-number"><?php echo $total_warranties; ?></div>
            <div class="stats-label">Total Warranties</div>
        </div>
        <div class="stats-card">
            <div class="stats-icon text-warning"><i class="fas fa-tasks"></i></div>
            <div class="stats-number"><?php echo $pending_tasks; ?></div>
            <div class="stats-label">Pending Tasks</div>
        </div>
        <div class="stats-card">
            <div class="stats-icon text-danger"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stats-number"><?php echo $expiring_licenses + $expiring_warranties; ?></div>
            <div class="stats-label">Expiring Soon</div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - License Statistics -->
        <div class="col-lg-6">
            <!-- License Status Summary -->
            <div class="report-card">
                <div class="report-header">
                    <i class="fas fa-chart-pie"></i> License Status Summary
                </div>
                <div class="report-body">
                    <?php foreach($license_stats as $stat): 
                        $badge_class = $stat['status'] == 'active' ? 'badge-active' : ($stat['status'] == 'expiring_soon' ? 'badge-expiring' : 'badge-expired');
                    ?>
                    <div class="stat-row">
                        <span class="stat-row-label"><?php echo ucfirst(str_replace('_', ' ', $stat['status'])); ?></span>
                        <span class="stat-row-value"><?php echo $stat['count']; ?> item(s)</span>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($license_stats)): ?>
                    <div class="text-muted text-center py-3">No license data available</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Licenses -->
            <div class="report-card">
                <div class="report-header">
                    <i class="fas fa-history"></i> Recently Added Licenses
                    <a href="licenses.php" class="float-end btn-link-small">View All →</a>
                </div>
                <div class="report-body">
                    <?php if(count($recent_licenses) > 0): ?>
                        <?php foreach($recent_licenses as $license): ?>
                        <div class="list-item">
                            <div>
                                <strong><?php echo htmlspecialchars($license['software_name'] ?? $license['name']); ?></strong>
                                <br>
                                <small class="text-muted">License Key: <?php echo htmlspecialchars(substr($license['license_key'], 0, 15)) . '...'; ?></small>
                            </div>
                            <div>
                                <span class="badge <?php echo $license['status'] == 'active' ? 'badge-active' : ($license['status'] == 'expiring_soon' ? 'badge-expiring' : 'badge-expired'); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $license['status'])); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-muted text-center py-3">No licenses found</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Expiring Licenses -->
            <div class="report-card">
                <div class="report-header">
                    <i class="fas fa-hourglass-half"></i> Licenses Expiring Soon
                    <a href="licenses.php?status=expiring_soon" class="float-end btn-link-small">View All →</a>
                </div>
                <div class="report-body">
                    <?php if(count($upcoming_licenses) > 0): ?>
                        <?php foreach($upcoming_licenses as $license): ?>
                        <div class="list-item">
                            <div>
                                <strong><?php echo htmlspecialchars($license['software_name'] ?? $license['name']); ?></strong>
                                <br>
                                <small class="text-muted">Expires: <?php echo date('d-m-Y', strtotime($license['expiry_date'])); ?></small>
                            </div>
                            <div>
                                <span class="badge badge-expiring">Expiring Soon</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-muted text-center py-3">No licenses expiring soon</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column - Warranty & Task Statistics -->
        <div class="col-lg-6">
            <!-- Warranty Status Summary -->
            <div class="report-card">
                <div class="report-header">
                    <i class="fas fa-chart-pie"></i> Warranty Status Summary
                </div>
                <div class="report-body">
                    <?php foreach($warranty_stats as $stat): 
                        $badge_class = $stat['status'] == 'active' ? 'badge-active' : ($stat['status'] == 'expiring_soon' ? 'badge-expiring' : 'badge-expired');
                    ?>
                    <div class="stat-row">
                        <span class="stat-row-label"><?php echo ucfirst(str_replace('_', ' ', $stat['status'])); ?></span>
                        <span class="stat-row-value"><?php echo $stat['count']; ?> item(s)</span>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($warranty_stats)): ?>
                    <div class="text-muted text-center py-3">No warranty data available</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Warranties -->
            <div class="report-card">
                <div class="report-header">
                    <i class="fas fa-history"></i> Recently Added Warranties
                    <a href="warranties.php" class="float-end btn-link-small">View All →</a>
                </div>
                <div class="report-body">
                    <?php if(count($recent_warranties) > 0): ?>
                        <?php foreach($recent_warranties as $warranty): ?>
                        <div class="list-item">
                            <div>
                                <strong><?php echo htmlspecialchars($warranty['item_name'] ?? $warranty['name']); ?></strong>
                                <br>
                                <small class="text-muted">Provider: <?php echo htmlspecialchars($warranty['warranty_provider']); ?></small>
                            </div>
                            <div>
                                <span class="badge <?php echo $warranty['status'] == 'active' ? 'badge-active' : ($warranty['status'] == 'expiring_soon' ? 'badge-expiring' : 'badge-expired'); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $warranty['status'])); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-muted text-center py-3">No warranties found</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Task Priority Distribution & Recent Tasks -->
            <div class="report-card">
                <div class="report-header">
                    <i class="fas fa-tasks"></i> Task Priority Distribution
                </div>
                <div class="report-body">
                    <?php 
                    $priority_labels = ['High', 'Medium', 'Low', 'Urgent'];
                    $priority_colors = ['priority-high', 'priority-medium', 'priority-low', 'priority-high'];
                    foreach($task_priority_stats as $stat):
                        $priority_class = $stat['priority'] == 'high' ? 'priority-high' : ($stat['priority'] == 'medium' ? 'priority-medium' : 'priority-low');
                    ?>
                    <div class="stat-row">
                        <span class="stat-row-label">
                            <span class="priority-badge <?php echo $priority_class; ?>"><?php echo ucfirst($stat['priority']); ?></span>
                        </span>
                        <span class="stat-row-value"><?php echo $stat['count']; ?> task(s)</span>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($task_priority_stats)): ?>
                    <div class="text-muted text-center py-3">No task data available</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent/Pending Tasks -->
            <div class="report-card">
                <div class="report-header">
                    <i class="fas fa-clock"></i> Upcoming/Pending Tasks
                    <a href="tasks.php" class="float-end btn-link-small">View All →</a>
                </div>
                <div class="report-body">
                    <?php if(count($recent_tasks) > 0): ?>
                        <?php foreach($recent_tasks as $task): 
                            $priority_class = $task['priority'] == 'high' ? 'priority-high' : ($task['priority'] == 'medium' ? 'priority-medium' : 'priority-low');
                            $status_class = $task['status'] == 'pending' ? 'badge-pending' : ($task['status'] == 'in_progress' ? 'badge-in_progress' : 'badge-completed');
                        ?>
                        <div class="list-item">
                            <div>
                                <strong><?php echo htmlspecialchars($task['task_title']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    Due: <?php echo $task['due_date'] ? date('d-m-Y', strtotime($task['due_date'])) : 'No due date'; ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <div><span class="priority-badge <?php echo $priority_class; ?> mb-1"><?php echo ucfirst($task['priority']); ?></span></div>
                                <div><span class="badge <?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></span></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-muted text-center py-3">No pending tasks</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Footer -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="report-card">
                <div class="report-header">
                    <i class="fas fa-info-circle"></i> Quick Summary
                </div>
                <div class="report-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stat-row">
                                <span class="stat-row-label">Total Active Licenses</span>
                                <span class="stat-row-value text-success"><?php echo $total_licenses - $expired_licenses; ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-row">
                                <span class="stat-row-label">Total Active Warranties</span>
                                <span class="stat-row-value text-success"><?php echo $total_warranties - $expired_warranties; ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-row">
                                <span class="stat-row-label">Expiring Items (30 days)</span>
                                <span class="stat-row-value text-warning"><?php echo $expiring_licenses + $expiring_warranties; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>