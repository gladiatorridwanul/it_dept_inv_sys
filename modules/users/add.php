<?php
require_once '../../includes/auth.php';
require_once '../../includes/user_functions.php';
include '../../includes/header.php';

if($_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Get all roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_id = intval($_POST['role_id']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Get role name
    $roleStmt = $pdo->prepare("SELECT name, display_name FROM roles WHERE id = ?");
    $roleStmt->execute([$role_id]);
    $role = $roleStmt->fetch();
    $role_name = $role['name'];
    $role_display = $role['display_name'];
    
    // Validation
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if($check->fetch()) {
        $error = "Username already exists!";
    } elseif(empty($username) || empty($full_name)) {
        $error = "Username and Full Name are required!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role_id, role_name, role, is_active, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        if($stmt->execute([$username, $hashed_password, $full_name, $email, $role_id, $role_name, $role_name, $is_active])) {
            $new_id = $pdo->lastInsertId();
            logActivity($pdo, $_SESSION['user_id'], 'user_created', "New user: $username (Role: $role_display)");
            $_SESSION['success'] = "User created successfully!";
            header("Location: list.php");
            exit();
        } else {
            $error = "Failed to create user!";
        }
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
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        padding: 18px 24px;
        border-radius: 20px 20px 0 0;
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
    .info-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        border-left: 3px solid #3b82f6;
    }
    .small-text {
        font-size: 11px;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="form-card">
                <div class="form-header">
                    <h4 class="mb-0 text-white fw-bold"><i class="fas fa-user-plus me-2"></i>Add New User</h4>
                    <p class="mb-0 text-white-50 small mt-1">Create a new system user with role-based permissions</p>
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
                                <label class="form-label required-field">Username</label>
                                <input type="text" name="username" class="form-control form-control-sm" required>
                                <small class="text-muted small-text">Unique login username</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required-field">Full Name</label>
                                <input type="text" name="full_name" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required-field">Password</label>
                                <input type="password" name="password" class="form-control form-control-sm" required>
                                <small class="text-muted small-text">Default: 123456 (user can change later)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required-field">User Role</label>
                                <select name="role_id" class="form-select form-select-sm" required>
                                    <option value="">Select Role</option>
                                    <?php foreach($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>">
                                        <?php echo htmlspecialchars($role['display_name']); ?> (<?php echo htmlspecialchars($role['name']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked>
                                    <label class="form-check-label small" for="isActive">Active Account</label>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="fas fa-save me-2"></i> Create User
                            </button>
                            <a href="list.php" class="btn btn-secondary btn-sm px-4">
                                <i class="fas fa-times me-2"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Role Information Card -->
            <div class="info-card mt-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-info-circle fa-2x text-primary"></i>
                    <div>
                        <strong class="small">Role Information</strong>
                        <p class="mb-0 small text-muted">Roles determine system access permissions. Configure permissions in the Permissions module.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>