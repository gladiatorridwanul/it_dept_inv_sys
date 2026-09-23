<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

if($_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$logs = $pdo->query("SELECT l.*, u.username FROM user_activity_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.id DESC LIMIT 200")->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <h2><i class="fas fa-history text-primary"></i> User Activity Logs</h2>
                <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Users</a>
            </div>
            <hr>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-primary text-white">Recent Activities (Last 200 records)</div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr><th>Time</th><th>User</th><th>Action</th><th>Description</th><th>IP Address</th></tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $log): ?>
                    <tr>
                        <td><?php echo date('d-m-Y H:i:s', strtotime($log['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($log['username']); ?></td>
                        <td><span class="badge bg-info"><?php echo htmlspecialchars($log['action']); ?></span></td>
                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                        <td><?php echo $log['ip_address']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($logs)): ?>
                    <tr><td colspan="5" class="text-center">No activity logs found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>