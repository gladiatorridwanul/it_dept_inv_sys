<?php
require_once '../../includes/auth.php';
require_once '../../includes/user_functions.php';
include '../../includes/header.php';

if($_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT u.*, r.name as role_system, r.display_name as role_display 
                       FROM users u 
                       LEFT JOIN roles r ON u.role_id = r.id 
                       WHERE u.id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if(!$user) {
    header("Location: list.php");
    exit();
}

// Get all roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role_id = intval($_POST['role_id']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $new_password = trim($_POST['new_password']);
    
    // Get role name
    $roleStmt = $pdo->prepare("SELECT name, display_name FROM roles WHERE id = ?");
    $roleStmt->execute([$role_id]);
    $role = $roleStmt->fetch();
    $role_name = $role['name'];
    
    if(empty($full_name)) {
        $error = "Full Name is required!";
    } else {
        if(!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role_id = ?, role_name = ?, role = ?, is_active = ?, password = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $role_id, $role_name, $role_name, $is_active, $hashed, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role_id = ?, role_name = ?, role = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $role_id, $role_name, $role_name, $is_active, $id]);
        }
        
        logActivity($pdo, $_SESSION['user_id'], 'user_updated', "User ID: $id");
        $_SESSION['success'] = "User updated successfully!";
        header("Location: list.php");
        exit();
    }
}
?>

<style>
    .form-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border: 1px solid #eef2f6;
    }
    .form-header {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        padding: 18px 24px;
        border-radius: 20px 20px 0 0;
    }
    .info-sidebar {
        background: #f8fafc;
        border-radius: 16px;
        padding: 20px;
        border: 1px solid #eef2f6;
    }
    .form-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #475569;
        margin-bottom: 6px;
    }
    .required-field::after {
        content: " *";
        color: #dc2626;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="form-header">
                    <h4 class="mb-0 text-white fw-bold"><i class="fas fa-user-edit me-2"></i>Edit User</h4>
                    <p class="mb-0 text-white-50 small mt-1">Updating user: <strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
                </div>
                
                <div class="p-4">
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close p-2" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control form-control-sm bg-light" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <small class="text-muted small-text">Username cannot be changed</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required-field">Full Name</label>
                                <input type="text" name="full_name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm" value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required-field">User Role</label>
                                <select name="role_id" class="form-select form-select-sm" required>
                                    <?php foreach($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>" <?php echo $user['role_id'] == $role['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role['display_name']); ?> (<?php echo htmlspecialchars($role['name']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label small" for="isActive">Active Account</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control form-control-sm" placeholder="Leave blank to keep current">
                                <small class="text-muted small-text">Enter only if you want to change the password</small>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-warning btn-sm px-4">
                                <i class="fas fa-save me-2"></i> Save Changes
                            </button>
                            <a href="list.php" class="btn btn-secondary btn-sm px-4">
                                <i class="fas fa-times me-2"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="info-sidebar">
                <h6 class="fw-bold mb-3"><i class="fas fa-info-circle text-primary me-2"></i>User Information</h6>
                <div class="mb-3">
                    <small class="text-muted d-block">Created</small>
                    <strong class="small"><?php echo date('d-m-Y H:i', strtotime($user['created_at'])); ?></strong>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Last Login</small>
                    <strong class="small"><?php echo $user['last_login'] ? date('d-m-Y H:i', strtotime($user['last_login'])) : 'Never'; ?></strong>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Current Role</small>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($user['role_display']); ?></span>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <a href="permissions.php?user_id=<?php echo $user['id']; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-key me-2"></i> Edit User Permissions
                    </a>
                    <a href="permissions.php?role_id=<?php echo $user['role_id']; ?>" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-users me-2"></i> Edit Role Permissions
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>