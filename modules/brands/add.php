<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$error_message = '';
$success_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    $errors = [];
    if(empty($name)) {
        $errors[] = "Brand name is required";
    }
    
    if(empty($errors)) {
        // Check if brand already exists
        $stmt = $pdo->prepare("SELECT id FROM brands WHERE name = ?");
        $stmt->execute([$name]);
        if($stmt->fetch()) {
            $error_message = "Brand already exists!";
        } else {
            // Remove created_by from INSERT if column doesn't exist
            $stmt = $pdo->prepare("INSERT INTO brands (name, description, is_active, created_at) VALUES (?, ?, 1, NOW())");
            if($stmt->execute([$name, $description])) {
                $success_message = "Brand added successfully!";
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> ' . $success_message . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                      </div>';
                echo '<script>setTimeout(function(){ window.location.href = "list.php"; }, 1500);</script>';
            } else {
                $error_message = "Error adding brand!";
            }
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<style>
    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .required-field::after {
        content: " *";
        color: red;
        font-weight: bold;
    }
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .info-card {
        background: #f8f9fa;
        border-left: 4px solid #28a745;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-tag text-primary"></i> Add New Brand</h2>
                    <p class="text-muted">Register a new product brand/manufacturer</p>
                </div>
                <div>
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    
    <?php if($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Brand Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="brandForm">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label required-field">Brand Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-trademark"></i></span>
                                    <input type="text" name="name" class="form-control" 
                                           placeholder="e.g., Dell, HP, Apple, Samsung" required
                                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                </div>
                                <small class="text-muted">Unique brand/manufacturer name</small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Description</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                    <textarea name="description" rows="4" class="form-control" 
                                              placeholder="Brand description, website, contact info..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>
                                <small class="text-muted">Optional - additional information about the brand</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Save Brand
                            </button>
                            <a href="list.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm info-card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Quick Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Brand name must be unique</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Common brands: Dell, HP, Lenovo, Apple</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> You can add description for reference</li>
                        <li><i class="fas fa-check-circle text-success"></i> Brands can be activated/deactivated later</li>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Statistics</h5>
                </div>
                <div class="card-body">
                    <?php
                    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM brands");
                    $total = $totalStmt->fetch()['total'];
                    $activeStmt = $pdo->query("SELECT COUNT(*) as active FROM brands WHERE is_active = 1");
                    $active = $activeStmt->fetch()['active'];
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Brands:</span>
                        <strong><?php echo $total; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Active Brands:</span>
                        <strong class="text-success"><?php echo $active; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#brandForm').on('submit', function() {
        $('#submitBtn').html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        $('#submitBtn').prop('disabled', true);
    });
});
</script>

<?php include '../../includes/footer.php'; ?>