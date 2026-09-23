<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if(!$category) {
    redirect('list.php');
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $parent_id = $_POST['parent_id'] ?: NULL;
    
    // Prevent setting parent as itself
    if($parent_id == $id) {
        echo '<div class="alert alert-danger">Cannot set category as its own parent!</div>';
    } else {
        $stmt = $pdo->prepare("UPDATE categories SET name=?, parent_id=? WHERE id=?");
        
        if($stmt->execute([$name, $parent_id, $id])) {
            echo '<div class="alert alert-success">Category updated successfully!</div>';
            echo '<script>setTimeout(function(){ window.location.href="list.php"; }, 1500);</script>';
        } else {
            echo '<div class="alert alert-danger">Error updating category!</div>';
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 AND id != $id ORDER BY name")->fetchAll();
?>

<div class="container-fluid">
    <h2><i class="fas fa-edit"></i> Edit Category</h2>
    <hr>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Category Name *</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Parent Category</label>
                        <select name="parent_id" class="form-control">
                            <option value="">-- Root Category --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category['parent_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Category</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>