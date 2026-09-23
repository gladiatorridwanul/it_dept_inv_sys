<?php
// Start session at the very beginning
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database configuration
require_once '../config/database.php';

// Include header
include '../includes/header.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user current data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo '<div class="alert alert-danger">User not found!</div>';
    include '../includes/footer.php';
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email already exists for another user
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = "Email already exists for another user";
        }
    }
    
    // Handle password change
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New password and confirm password do not match";
        }
    }
    
    if (empty($errors)) {
        // Update basic info
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $user_id]);
        
        // Update password if provided
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            // Update session name
            $_SESSION['full_name'] = $full_name;
            $_SESSION['user_name'] = $full_name;
            
            $success = "Profile updated successfully! Password has been changed.";
        } else {
            // Update session name
            $_SESSION['full_name'] = $full_name;
            $_SESSION['user_name'] = $full_name;
            
            $success = "Profile updated successfully!";
        }
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
    }
    .profile-avatar {
        width: 100px;
        height: 100px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        margin: 0 auto 15px;
    }
    .form-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .form-section-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e2e8f0;
    }
    .form-label {
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
    }
    .form-control {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 15px;
    }
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }
    .btn-update {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
    }
    .btn-update:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    }
    .info-card {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
    }
    .info-item {
        padding: 10px 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .info-item:last-child {
        border-bottom: none;
    }
    .info-label {
        font-weight: 600;
        color: #475569;
        width: 120px;
        display: inline-block;
    }
    .info-value {
        color: #1e293b;
    }
    @media (max-width: 768px) {
        .profile-header {
            padding: 20px;
        }
        .info-label {
            width: 100%;
            display: block;
            margin-bottom: 5px;
        }
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-user-circle"></i> My Profile</h2>
            <p class="text-muted">Manage your account information and password</p>
            <hr>
        </div>
    </div>

    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-4">
            <div class="profile-header text-center">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                <p class="mb-0">@<?php echo htmlspecialchars($user['username']); ?></p>
                <p><span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span></p>
            </div>

            <div class="info-card">
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-user"></i> Username:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-envelope"></i> Email:</span>
                    <span class="info-value"><?php echo !empty($user['email']) ? htmlspecialchars($user['email']) : 'Not provided'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-calendar"></i> Member Since:</span>
                    <span class="info-value"><?php echo date('d-m-Y', strtotime($user['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-clock"></i> Last Login:</span>
                    <span class="info-value"><?php echo $user['last_login'] ? date('d-m-Y H:i', strtotime($user['last_login'])) : 'Never'; ?></span>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <form method="POST">
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-user-edit"></i> Basic Information
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
                            <small class="text-muted">Optional but recommended for notifications</small>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-lock"></i> Change Password
                    </div>
                    <p class="text-muted small mb-3">Leave password fields empty if you don't want to change password</p>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="Enter current password">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-update">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>