<?php
require_once '../../includes/auth.php';
require_once '../../includes/user_functions.php';
include '../../includes/header.php';

// Check if user has permission to view users
if(!canView($pdo, $_SESSION['role'], 'users') && $_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$user_role = $_SESSION['role'];

// Handle status toggle
if(isset($_GET['toggle_status']) && isset($_GET['id'])) {
    if(canEdit($pdo, $user_role, 'users') || $_SESSION['role'] == 'admin') {
        $user_id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if($user) {
            $new_status = $user['is_active'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            logActivity($pdo, $_SESSION['user_id'], 'user_status_changed', "User ID: $user_id");
            $_SESSION['success'] = "User status updated!";
        }
        header("Location: list.php");
        exit();
    }
}

// Handle delete
if(isset($_GET['delete']) && isset($_GET['id'])) {
    if(canDelete($pdo, $user_role, 'users') || $_SESSION['role'] == 'admin') {
        $user_id = intval($_GET['id']);
        if($user_id != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            logActivity($pdo, $_SESSION['user_id'], 'user_deleted', "User ID: $user_id");
            $_SESSION['success'] = "User deleted!";
        }
        header("Location: list.php");
        exit();
    }
}

// Get all roles for filter
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();

// Get users with role information
$users = $pdo->query("SELECT u.*, r.display_name as role_display, r.name as role_system 
                      FROM users u 
                      LEFT JOIN roles r ON u.role_id = r.id 
                      ORDER BY u.id ASC")->fetchAll();
$total_users = count($users);
$active_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
?>

<style>
    /* Modern Table Design */
    :root {
        --primary-color: #3b82f6;
        --primary-dark: #2563eb;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-400: #9ca3af;
        --gray-500: #6b7280;
        --gray-600: #4b5563;
        --gray-700: #374151;
        --gray-800: #1f2937;
        --gray-900: #111827;
    }
    
    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 28px;
    }
    
    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        transition: all 0.3s ease;
        border: 1px solid var(--gray-200);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
    }
    
    .stat-card.blue::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .stat-card.green::before { background: linear-gradient(90deg, #10b981, #34d399); }
    .stat-card.purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
    .stat-card.orange::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -12px rgba(0,0,0,0.1);
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }
    
    .stat-icon.blue { background: rgba(59,130,246,0.1); color: #3b82f6; }
    .stat-icon.green { background: rgba(16,185,129,0.1); color: #10b981; }
    .stat-icon.purple { background: rgba(139,92,246,0.1); color: #8b5cf6; }
    .stat-icon.orange { background: rgba(245,158,11,0.1); color: #f59e0b; }
    
    .stat-value {
        font-size: 28px;
        font-weight: 800;
        color: var(--gray-800);
        line-height: 1.2;
        margin-bottom: 4px;
    }
    
    .stat-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Main Card */
    .users-card {
        background: white;
        border-radius: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        border: 1px solid var(--gray-200);
        overflow: hidden;
    }
    
    /* Card Header */
    .card-header-modern {
        padding: 20px 24px;
        background: white;
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .header-title h5 {
        font-size: 16px;
        font-weight: 700;
        margin: 0;
        color: var(--gray-800);
    }
    
    .header-title p {
        font-size: 12px;
        color: var(--gray-500);
        margin: 4px 0 0;
    }
    
    /* Action Buttons Group */
    .action-buttons-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .btn-action {
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 500;
        border-radius: 10px;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-action-primary {
        background: var(--primary-color);
        color: white;
        border: none;
    }
    
    .btn-action-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }
    
    .btn-action-outline {
        background: white;
        color: var(--gray-600);
        border: 1px solid var(--gray-200);
    }
    
    .btn-action-outline:hover {
        background: var(--gray-50);
        border-color: var(--gray-300);
    }
    
    /* Search Bar */
    .search-wrapper {
        position: relative;
        width: 260px;
    }
    
    .search-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-400);
        font-size: 14px;
    }
    
    .search-wrapper input {
        width: 100%;
        padding: 8px 12px 8px 36px;
        font-size: 13px;
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        background: white;
        transition: all 0.2s;
    }
    
    .search-wrapper input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }
    
    /* Modern Table */
    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .modern-table thead tr {
        background: var(--gray-50);
    }
    
    .modern-table th {
        padding: 14px 16px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--gray-500);
        border-bottom: 1px solid var(--gray-200);
        text-align: left;
    }
    
    .modern-table td {
        padding: 16px;
        font-size: 13px;
        color: var(--gray-600);
        border-bottom: 1px solid var(--gray-100);
        vertical-align: middle;
    }
    
    .modern-table tbody tr {
        transition: all 0.2s;
    }
    
    .modern-table tbody tr:hover {
        background: var(--gray-50);
    }
    
    /* User Avatar */
    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 14px;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .user-details {
        display: flex;
        flex-direction: column;
    }
    
    .user-name {
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 2px;
    }
    
    .user-username {
        font-size: 11px;
        color: var(--gray-400);
    }
    
    /* Role Badges */
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .role-badge-admin {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: white;
    }
    
    .role-badge-it_staff {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }
    
    .role-badge-custom {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }
    
    /* Status Indicators */
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 500;
    }
    
    .status-active {
        background: #d1fae5;
        color: #059669;
    }
    
    .status-inactive {
        background: #fee2e2;
        color: #dc2626;
    }
    
    /* Table Actions */
    .table-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    
    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        text-decoration: none;
        font-size: 13px;
    }
    
    .btn-icon-view {
        background: #eff6ff;
        color: #3b82f6;
    }
    
    .btn-icon-view:hover {
        background: #3b82f6;
        color: white;
    }
    
    .btn-icon-edit {
        background: #fef3c7;
        color: #d97706;
    }
    
    .btn-icon-edit:hover {
        background: #d97706;
        color: white;
    }
    
    .btn-icon-key {
        background: #e0e7ff;
        color: #4f46e5;
    }
    
    .btn-icon-key:hover {
        background: #4f46e5;
        color: white;
    }
    
    .btn-icon-deactivate {
        background: #fee2e2;
        color: #dc2626;
    }
    
    .btn-icon-deactivate:hover {
        background: #dc2626;
        color: white;
    }
    
    .btn-icon-activate {
        background: #d1fae5;
        color: #059669;
    }
    
    .btn-icon-activate:hover {
        background: #059669;
        color: white;
    }
    
    .btn-icon-delete {
        background: #fef2f2;
        color: #ef4444;
    }
    
    .btn-icon-delete:hover {
        background: #ef4444;
        color: white;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state-icon {
        width: 80px;
        height: 80px;
        background: var(--gray-100);
        border-radius: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    
    .empty-state-icon i {
        font-size: 32px;
        color: var(--gray-400);
    }
    
    .empty-state h6 {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 8px;
    }
    
    .empty-state p {
        font-size: 13px;
        color: var(--gray-500);
    }
    
    /* Footer */
    .card-footer-modern {
        padding: 16px 24px;
        background: var(--gray-50);
        border-top: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    
    .footer-info {
        font-size: 12px;
        color: var(--gray-500);
    }
    
    /* Responsive */
    @media (max-width: 1200px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .card-header-modern {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .search-wrapper {
            width: 100%;
        }
        
        .modern-table th, 
        .modern-table td {
            padding: 12px;
        }
        
        .table-actions {
            flex-direction: column;
        }
        
        .btn-icon {
            width: 28px;
            height: 28px;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .stat-value {
            font-size: 24px;
        }
    }
</style>

<div class="container-fluid">
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon blue">
                <i class="fas fa-users fa-xl"></i>
            </div>
            <div class="stat-value"><?php echo $total_users; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon green">
                <i class="fas fa-user-check fa-xl"></i>
            </div>
            <div class="stat-value"><?php echo $active_users; ?></div>
            <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon purple">
                <i class="fas fa-tags fa-xl"></i>
            </div>
            <div class="stat-value"><?php echo count($roles); ?></div>
            <div class="stat-label">User Roles</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon orange">
                <i class="fas fa-user-shield fa-xl"></i>
            </div>
            <div class="stat-value"><?php echo count(array_filter($users, function($u) { return $u['role_system'] == 'admin'; })); ?></div>
            <div class="stat-label">Administrators</div>
        </div>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius: 14px; font-size: 13px;" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Users Table Card -->
    <div class="users-card">
        <div class="card-header-modern">
            <div class="header-title">
                <h5><i class="fas fa-users text-primary me-2"></i>System Users</h5>
                <p>Manage user accounts, roles, and system access permissions</p>
            </div>
            <div class="action-buttons-group">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search users...">
                </div>
                <a href="add.php" class="btn-action btn-action-primary">
                    <i class="fas fa-plus"></i> Add User
                </a>
                <a href="roles.php" class="btn-action btn-action-outline">
                    <i class="fas fa-tags"></i> Roles
                </a>
                <a href="permissions.php" class="btn-action btn-action-outline">
                    <i class="fas fa-key"></i> Permissions
                </a>
                <a href="activity.php" class="btn-action btn-action-outline">
                    <i class="fas fa-history"></i> Activity
                </a>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="modern-table" id="usersTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </th>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                                </div>
                                <div class="user-details">
                                    <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                    <span class="user-username">@<?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                            </div>
                        </br>
                        <td>
                            <?php if($user['email']): ?>
                                <?php echo htmlspecialchars($user['email']); ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </br>
                        <td>
                            <span class="role-badge role-badge-<?php echo $user['role_system'] == 'admin' ? 'admin' : ($user['role_system'] == 'it_staff' ? 'it_staff' : 'custom'); ?>">
                                <i class="fas fa-<?php echo $user['role_system'] == 'admin' ? 'crown' : 'user-shield'; ?>"></i>
                                <?php echo htmlspecialchars($user['role_display'] ?? $user['role_system']); ?>
                            </span>
                        </br>
                        <td>
                            <span class="status-indicator <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <i class="fas fa-<?php echo $user['is_active'] ? 'circle' : 'times-circle'; ?>"></i>
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </br>
                        <td>
                            <?php echo $user['last_login'] ? date('d-m-Y H:i', strtotime($user['last_login'])) : '<span class="text-muted">Never</span>'; ?>
                        </br>
                        <td>
                            <div class="table-actions">
                                <a href="view.php?id=<?php echo $user['id']; ?>" class="btn-icon btn-icon-view" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn-icon btn-icon-edit" title="Edit User">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="permissions.php?user_id=<?php echo $user['id']; ?>" class="btn-icon btn-icon-key" title="User Permissions">
                                    <i class="fas fa-key"></i>
                                </a>
                                <a href="?toggle_status=1&id=<?php echo $user['id']; ?>" class="btn-icon <?php echo $user['is_active'] ? 'btn-icon-deactivate' : 'btn-icon-activate'; ?>" title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>" onclick="return confirm('Confirm?')">
                                    <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                </a>
                                <?php if($user['id'] != $_SESSION['user_id'] && (canDelete($pdo, $user_role, 'users') || $_SESSION['role'] == 'admin')): ?>
                                <a href="?delete=1&id=<?php echo $user['id']; ?>" class="btn-icon btn-icon-delete" title="Delete User" onclick="return confirm('Delete user permanently?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </br>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($users)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-users-slash"></i>
                                </div>
                                <h6>No Users Found</h6>
                                <p>Click "Add User" to create your first system user.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card-footer-modern">
            <div class="footer-info">
                <i class="fas fa-info-circle me-1"></i> Showing <?php echo count($users); ?> of <?php echo $total_users; ?> users
            </div>
            <div class="footer-info">
                <i class="fas fa-shield-alt me-1"></i> <?php echo $active_users; ?> active accounts
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#usersTable tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        if (text.indexOf(filter) > -1) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update footer info
    let footerInfo = document.querySelector('.card-footer-modern .footer-info:first-child');
    if (footerInfo) {
        footerInfo.innerHTML = `<i class="fas fa-info-circle me-1"></i> Showing ${visibleCount} of ${rows.length} users`;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>