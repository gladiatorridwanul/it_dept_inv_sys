<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $parent_id = $_POST['parent_id'] ?: NULL;
    
    $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
    
    if($stmt->execute([$name, $parent_id])) {
        echo '<div class="alert alert-success">Category added successfully!</div>';
        echo '<script>setTimeout(function(){ window.location.href="list.php"; }, 1500);</script>';
    } else {
        echo '<div class="alert alert-danger">Error adding category!</div>';
    }
}

$categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY name")->fetchAll();
?>

<div class="container-fluid">
    <h2><i class="fas fa-plus-circle"></i> Add Category / Sub-Category</h2>
    <hr>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Category Name *</label>
                        <input type="text" name="name" class="form-control" required>
                        <small class="text-muted">e.g., Hardware, Software, Networking, Peripherals</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Parent Category</label>
                        <select name="parent_id" class="form-control">
                            <option value="">-- Root Category --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Select parent to create sub-category</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Category</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
    
    <div class="alert alert-info mt-3">
        <strong><i class="fas fa-info-circle"></i> Category Hierarchy Example:</strong><br>
        - Hardware (Parent)<br>
        &nbsp;&nbsp;&nbsp;&nbsp;- Laptop (Sub-category)<br>
        &nbsp;&nbsp;&nbsp;&nbsp;- Desktop (Sub-category)<br>
        &nbsp;&nbsp;&nbsp;&nbsp;- Printer (Sub-category)<br>
        - Software (Parent)<br>
        &nbsp;&nbsp;&nbsp;&nbsp;- Operating System (Sub-category)<br>
        &nbsp;&nbsp;&nbsp;&nbsp;- Application Software (Sub-category)
    </div>
</div>

<?php include '../../includes/footer.php'; ?>