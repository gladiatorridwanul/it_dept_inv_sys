<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
$stmt->execute([$id]);
$brand = $stmt->fetch();

if(!$brand) {
    redirect('list.php');
}

$error_message = '';
$success_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    if(empty($name)) {
        $errors[] = "Brand name is required";
    }
    
    if(empty($errors)) {
        // Check if brand name already exists for another brand
        $stmt = $pdo->prepare("SELECT id FROM brands WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if($stmt->fetch()) {
            $error_message = "Brand name already exists!";
        } else {
            $stmt = $pdo->prepare("UPDATE brands SET name = ?, description = ?, is_active = ? WHERE id = ?");
            if($stmt->execute([$name, $description, $is_active, $id])) {
                $success_message = "Brand updated successfully!";
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> ' . $success_message . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                      </div>';
                // Refresh brand data
                $stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
                $stmt->execute([$id]);
                $brand = $stmt->fetch();
            } else {
                $error_message = "Error updating brand!";
            }
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Check if brand is used in items
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM items WHERE brand_id = ?");
$stmt->execute([$id]);
$usedCount = $stmt->fetch()['count'];
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
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-edit text-warning"></i> Edit Brand</h2>
                    <p class="text-muted">Update brand information</p>
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
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Brand Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="brandForm">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label required-field">Brand Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-trademark"></i></span>
                                    <input type="text" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($brand['name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Description</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                    <textarea name="description" rows="4" class="form-control"><?php echo htmlspecialchars($brand['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                                           <?php echo $brand['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        <i class="fas fa-<?php echo $brand['is_active'] ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i>
                                        Active
                                    </label>
                                </div>
                                <small class="text-muted">Inactive brands won't appear in selection lists</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Update Brand
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
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-info-circle"></i> Brand Info</h5>
                    <hr class="bg-light">
                    <p class="mb-1"><i class="fas fa-id-card"></i> <strong>ID:</strong> <?php echo $brand['id']; ?></p>
                    <p class="mb-1"><i class="fas fa-calendar"></i> <strong>Created:</strong> <?php echo date('d-M-Y', strtotime($brand['created_at'])); ?></p>
                    <p class="mb-0">
                        <i class="fas fa-boxes"></i> <strong>Used in:</strong> 
                        <span class="badge bg-warning"><?php echo $usedCount; ?> item(s)</span>
                    </p>
                </div>
            </div>
            
            <?php if($usedCount > 0): ?>
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Note</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0 small">This brand is used in <?php echo $usedCount; ?> item(s). You cannot delete it, but you can deactivate it.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#brandForm').on('submit', function() {
        $('#submitBtn').html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        $('#submitBtn').prop('disabled', true);
    });
});
</script>

<?php include '../../includes/footer.php'; ?>