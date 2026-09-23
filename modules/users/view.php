<?php
require_once '../../includes/auth.php';
require_once '../../includes/user_functions.php';
include '../../includes/header.php';

if($_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if(!$user) {
    header("Location: list.php");
    exit();
}

// Get user activity
$activity_stmt = $pdo->prepare("SELECT * FROM user_activity_logs WHERE user_id = ? ORDER BY id DESC LIMIT 20");
$activity_stmt->execute([$id]);
$activities = $activity_stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <h2><i class="fas fa-user-circle text-primary"></i> User Details</h2>
                <div>
                    <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>
                    <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">Profile Information</div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 40px; color: white; font-weight: bold;">
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        </div>
                    </div>
                    <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <hr>
                    <p><strong>Role:</strong> <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : 'bg-info'; ?>"><?php echo strtoupper($user['role']); ?></span></p>
                    <p><strong>Status:</strong> <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Joined:</strong> <?php echo date('d M Y', strtotime($user['created_at'])); ?></p>
                    <p><strong>Last Login:</strong> <?php echo $user['last_login'] ? date('d M Y H:i', strtotime($user['last_login'])) : 'Never'; ?></p>
                    <p><strong>Last IP:</strong> <?php echo $user['last_ip'] ?? '-'; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">Recent Activity (Last 20)</div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr><th>Time</th><th>Action</th><th>Description</th><th>IP</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($activities as $act): ?>
                            <tr>
                                <td><?php echo date('d-m-Y H:i:s', strtotime($act['created_at'])); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($act['action']); ?></span></td>
                                <td><?php echo htmlspecialchars($act['description']); ?></td>
                                <td><?php echo $act['ip_address']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($activities)): ?>
                            <tr><td colspan="4" class="text-center">No activity records found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>