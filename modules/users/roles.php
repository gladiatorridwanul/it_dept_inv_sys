<?php
require_once '../../includes/auth.php';
require_once '../../includes/user_functions.php';
include '../../includes/header.php';

if($_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Handle role deletion
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $role_id = intval($_GET['delete']);
    
    // Check if role has users
    $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
    $check->execute([$role_id]);
    $user_count = $check->fetchColumn();
    
    if($user_count > 0) {
        $_SESSION['error'] = "Cannot delete role: $user_count user(s) are assigned to this role.";
    } else {
        // Check if it's a system role
        $stmt = $pdo->prepare("SELECT is_system FROM roles WHERE id = ?");
        $stmt->execute([$role_id]);
        $role = $stmt->fetch();
        
        if($role && $role['is_system'] == 1) {
            $_SESSION['error'] = "Cannot delete system role!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ? AND is_system = 0");
            $stmt->execute([$role_id]);
            
            // Also delete associated permissions
            $stmt = $pdo->prepare("DELETE FROM permissions WHERE role_id = ?");
            $stmt->execute([$role_id]);
            
            $_SESSION['success'] = "Role deleted successfully!";
        }
    }
    header("Location: roles.php");
    exit();
}

// Handle role add/edit
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name']);
    $display_name = trim($_POST['display_name']);
    $description = trim($_POST['description']);
    
    if(empty($name) || empty($display_name)) {
        $_SESSION['error'] = "Role name and display name are required!";
    } else {
        // Convert name to lowercase and replace spaces with underscores
        $name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $name));
        
        // Check for duplicate name
        $check = $pdo->prepare("SELECT id FROM roles WHERE name = ? AND id != ?");
        $check->execute([$name, $id]);
        if($check->fetch()) {
            $_SESSION['error'] = "Role name already exists!";
        } else {
            if($id > 0) {
                // Check if it's a system role before editing
                $stmt = $pdo->prepare("SELECT is_system FROM roles WHERE id = ?");
                $stmt->execute([$id]);
                $role = $stmt->fetch();
                
                if($role && $role['is_system'] == 1) {
                    // System roles can only update display_name and description
                    $stmt = $pdo->prepare("UPDATE roles SET display_name = ?, description = ? WHERE id = ? AND is_system = 1");
                    $stmt->execute([$display_name, $description, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE roles SET name = ?, display_name = ?, description = ? WHERE id = ? AND is_system = 0");
                    $stmt->execute([$name, $display_name, $description, $id]);
                }
                $_SESSION['success'] = "Role updated successfully!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO roles (name, display_name, description, is_system) VALUES (?, ?, ?, 0)");
                $stmt->execute([$name, $display_name, $description]);
                $_SESSION['success'] = "Role created successfully!";
            }
        }
    }
    header("Location: roles.php");
    exit();
}

$roles = $pdo->query("SELECT r.*, 
                      (SELECT COUNT(*) FROM users WHERE role_id = r.id) as user_count 
                      FROM roles r 
                      ORDER BY r.is_system DESC, r.id ASC")->fetchAll();
                      
$edit_role = null;
if(isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach($roles as $r) {
        if($r['id'] == $edit_id) {
            $edit_role = $r;
            break;
        }
    }
}
?>

<style>
    .role-card { transition: transform 0.3s; }
    .role-card:hover { transform: translateY(-5px); }
    .system-badge { background: #fef3c7; color: #d97706; font-size: 11px; padding: 2px 8px; border-radius: 20px; }
    .user-count-badge { background: #e2e8f0; color: #475569; font-size: 11px; padding: 2px 8px; border-radius: 20px; }
    .role-icon { width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
    .role-icon i { font-size: 24px; color: white; }
    .required-field::after { content: " *"; color: red; }
    .action-buttons .btn { padding: 4px 8px; margin: 2px; font-size: 12px; }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-tags text-primary"></i> User Roles Management</h2>
                    <p class="text-muted">Create, edit, and manage user roles for access control</p>
                </div>
                <div>
                    <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Users</a>
                    <a href="permissions.php" class="btn btn-outline-info"><i class="fas fa-lock"></i> Manage Permissions</a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Add/Edit Role Form -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-<?php echo $edit_role ? 'edit' : 'plus'; ?> me-2"></i> <?php echo $edit_role ? 'Edit Role' : 'Add New Role'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if($edit_role): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_role['id']; ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label required-field">Role Name (System Name)</label>
                            <input type="text" name="name" class="form-control" value="<?php echo $edit_role ? htmlspecialchars($edit_role['name']) : ''; ?>" required <?php echo $edit_role && $edit_role['is_system'] ? 'readonly' : ''; ?>>
                            <small class="text-muted">Unique identifier (e.g., manager, support_staff). System roles cannot be renamed.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required-field">Display Name</label>
                            <input type="text" name="display_name" class="form-control" value="<?php echo $edit_role ? htmlspecialchars($edit_role['display_name']) : ''; ?>" required>
                            <small class="text-muted">Human-readable name (e.g., Manager, Support Staff)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control"><?php echo $edit_role ? htmlspecialchars($edit_role['description']) : ''; ?></textarea>
                            <small class="text-muted">Brief description of what this role can do</small>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> <?php echo $edit_role ? 'Update Role' : 'Create Role'; ?></button>
                            <?php if($edit_role): ?>
                            <a href="roles.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i> Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Quick Tips -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i> Role Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>System Roles</strong> (Admin, IT Staff) cannot be deleted</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>Custom Roles</strong> can be fully customized</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> After creating a role, set its permissions in <strong>Permission Management</strong></li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Roles with assigned users cannot be deleted</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Roles List -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> Existing Roles</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Role Name</th>
                                    <th>Display Name</th>
                                    <th>Users</th>
                                    <th>Status</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($roles as $role): ?>
                                <tr>
                                    <td><?php echo $role['id']; ?></td>
                                    <td>
                                        <code><?php echo htmlspecialchars($role['name']); ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($role['display_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($role['description'], 0, 50)); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="user-count-badge">
                                            <i class="fas fa-users me-1"></i> <?php echo $role['user_count']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($role['is_system']): ?>
                                        <span class="system-badge">
                                            <i class="fas fa-shield-alt me-1"></i> System Role
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-plus-circle me-1"></i> Custom
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?edit=<?php echo $role['id']; ?>" class="btn btn-sm btn-warning" title="Edit Role">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="permissions.php?role_id=<?php echo $role['id']; ?>" class="btn btn-sm btn-primary" title="Manage Permissions">
                                                <i class="fas fa-key"></i>
                                            </a>
                                            <?php if(!$role['is_system'] && $role['user_count'] == 0): ?>
                                            <a href="?delete=<?php echo $role['id']; ?>" class="btn btn-sm btn-danger" title="Delete Role" onclick="return confirm('Delete this role permanently?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Permission Summary for Each Role -->
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-simple me-2"></i> Permission Summary</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Module Access</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($roles as $role): 
                                    // Get permission count for this role
                                    $permStmt = $pdo->prepare("SELECT COUNT(DISTINCT module) as module_count, COUNT(*) as perm_count FROM permissions WHERE role_id = ?");
                                    $permStmt->execute([$role['id']]);
                                    $permStats = $permStmt->fetch();
                                    $moduleCount = $permStats['module_count'] ?? 0;
                                    $permCount = $permStats['perm_count'] ?? 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($role['display_name']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $moduleCount; ?> Modules</span>
                                        <span class="badge bg-secondary ms-1"><?php echo $permCount; ?> Permissions</span>
                                    </td>
                                    <td>
                                        <a href="permissions.php?role_id=<?php echo $role['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Configure
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>